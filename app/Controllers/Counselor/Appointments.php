<?php

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Appointments extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/appointments');
    }

    public function getAll()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as counselor',
                'appointments' => []
            ], 401);
        }

        $counselor_id = session()->get('user_id_display') ?? session()->get('user_id');
        $db = \Config\Database::connect();

        // Query to get all appointments with user and counselor information
        // Filtered by counselor_id
        $query = "SELECT
                    a.*,
                    u.email as user_email,
                    u.username,
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), u.username) AS student_name,
                    CONCAT(sai.course, ' - ', sai.year_level) as course_year,
                    sai.course,
                    sai.year_level,
                    COALESCE(c.name, 'No Preference') as counselor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.student_id = u.user_id
                  LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                  LEFT JOIN student_academic_info sai ON sai.student_id = u.user_id
                  LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
                  WHERE a.counselor_preference = ? OR a.counselor_preference IS NULL
                  ORDER BY a.created_at DESC";

        $appointments = $db->query($query, [$counselor_id])->getResultArray();

        return $this->respond([
            'status' => 'success',
            'appointments' => $appointments
        ]);
    }

    public function updateStatus()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');

        if (!$id || !$status) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Missing required parameters'
            ], 400);
        }

        $db = \Config\Database::connect();
        $db->table('appointments')
           ->where('id', $id)
           ->update(['status' => $status]);

        return $this->respond([
            'status' => 'success',
            'message' => 'Appointment status updated successfully'
        ]);
    }

    public function getAppointments()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as counselor',
                'appointments' => []
            ], 401);
        }

        $counselor_id = session()->get('user_id_display') ?? session()->get('user_id');
        $db = \Config\Database::connect();

        $query = $db->table('appointments')
            ->select('appointments.*, users.email as user_email, users.username, 
                     CONCAT(sai.course, " - ", sai.year_level) as course_year,
                     sai.course, sai.year_level, CONCAT(spi.first_name, " ", spi.last_name) as student_name, c.name as counselor_name')
            ->join('users', 'users.user_id = appointments.student_id', 'left')
            ->join('student_academic_info sai', 'sai.student_id = appointments.student_id', 'left')
            ->join('student_personal_info spi', 'spi.student_id = appointments.student_id', 'left')
            ->join('counselors c', 'c.counselor_id = appointments.counselor_preference', 'left')
            ->groupStart()
                ->where('appointments.counselor_preference', $counselor_id)
                ->orWhere('appointments.counselor_preference IS NULL', null, false)
            ->groupEnd()
            ->orderBy('appointments.created_at', 'DESC')
            ->get();

        $appointments = $query->getResultArray();

        return $this->respond([
            'status' => 'success',
            'appointments' => $appointments
        ]);
    }

    public function updateAppointmentStatus()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized access']);
        }

        $appointment_id = $this->request->getPost('appointment_id');
        $new_status = strtolower($this->request->getPost('status'));
        $rejection_reason = $this->request->getPost('rejection_reason');

        if (!$appointment_id || !$new_status) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing required parameters']);
        }

        $valid_statuses = ['approved', 'rejected', 'completed', 'cancelled', 'pending'];
        if (!in_array($new_status, $valid_statuses)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid status value']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $builder = $db->table('appointments');
        $builder->where('id', $appointment_id);
        $updateData = [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (($new_status === 'rejected' || $new_status === 'cancelled') && !empty($rejection_reason)) {
            $updateData['reason'] = 'Reason from Counselor: ' . $rejection_reason;
        }

        $builder->update($updateData);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update appointment status']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Appointment status updated successfully']);
    }

    public function scheduled()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/scheduled_appointments');
    }

    public function getScheduledAppointments()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as counselor',
                'appointments' => []
            ], 401);
        }

        try {
            $counselor_id = session()->get('user_id_display') ?? session()->get('user_id');
            $db = \Config\Database::connect();

            $query = "SELECT
                        a.*, a.updated_at,
                        u.email, u.username, 
                        CONCAT(sai.course, ' - ', sai.year_level) as course_year,
                        sai.course, sai.year_level,
                        CONCAT(spi.first_name, ' ', spi.last_name) as student_name,
                        COALESCE(c.name, 'No Preference') as counselorPreference
                      FROM appointments a
                      LEFT JOIN users u ON a.student_id = u.user_id
                      LEFT JOIN student_academic_info sai ON sai.student_id = u.user_id
                      LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                      LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
                      WHERE a.status = 'approved' 
                      AND (a.counselor_preference = ? OR a.counselor_preference IS NULL)
                      ORDER BY a.preferred_date ASC, a.preferred_time ASC";

            $appointments = $db->query($query, [$counselor_id])->getResultArray();

            if (empty($appointments)) {
                return $this->respond([
                    'status' => 'success',
                    'message' => 'No approved appointments found',
                    'appointments' => []
                ]);
            }

            return $this->respond([
                'status' => 'success',
                'appointments' => $appointments
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Counselor\\Appointments::getScheduledAppointments] Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
                'appointments' => []
            ], 500);
        }
    }

    public function viewAll()
    {
        return view('counselor/view_all_appointments');
    }

    public function followUp()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/follow_up');
    }

    /**
     * Get counselor's availability schedule
     * Returns the counselor's available days and time slots
     */
    public function getCounselorSchedule()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as counselor',
                'schedule' => []
            ], 401);
        }

        try {
            $counselor_id = session()->get('user_id_display') ?? session()->get('user_id');
            $db = \Config\Database::connect();

            // Get counselor's availability from counselor_availability table
            $query = "SELECT available_days, time_scheduled 
                      FROM counselor_availability 
                      WHERE counselor_id = ? 
                      ORDER BY FIELD(available_days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')";

            $results = $db->query($query, [$counselor_id])->getResultArray();

            if (empty($results)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'No schedule found for counselor',
                    'schedule' => []
                ], 404);
            }

            $schedule = [];
            
            // Process each availability entry
            foreach ($results as $result) {
                $day = trim($result['available_days']);
                $time_scheduled = $result['time_scheduled'] ?? null;
                
                if (!empty($day)) {
                    $schedule[] = [
                        'day' => $day,
                        'time' => $time_scheduled
                    ];
                }
            }

            return $this->respond([
                'status' => 'success',
                'schedule' => $schedule
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Counselor\\Appointments::getCounselorSchedule] Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'An error occurred while fetching schedule: ' . $e->getMessage(),
                'schedule' => []
            ], 500);
        }
    }
}