<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\AppointmentModel;
use App\Models\FollowUpAppointmentModel;
use CodeIgniter\API\ResponseTrait;

class FollowUpSessions extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if user is logged in and is student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to('/');
        }

        return view('student/follow_up_sessions');
    }

    /**
     * Get completed appointments for the logged-in student
     */
    public function getCompletedAppointments()
    {
        // Check if user is logged in and is student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $studentId = session()->get('user_id_display') ?? session()->get('user_id');
            
            if (!$studentId) {
                return $this->fail('Invalid session data');
            }

            // Get search parameter
            $searchTerm = $this->request->getGet('search');
            $searchTerm = trim($searchTerm ?? '');

            $appointmentModel = new AppointmentModel();
            
            // Build the query for student's completed appointments with follow-up count and pending follow-up indicator
            $query = $appointmentModel->select("appointments.*, 
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), users.username) as student_name, 
                    users.email as student_email,
                    COALESCE(counselors.name, 'No Preference') as counselor_name,
                    (SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id) as follow_up_count,
                    COALESCE((SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending'), 0) as pending_follow_up_count,
                    (SELECT MIN(fua.preferred_date) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending') as next_pending_date")
                ->join('users', 'appointments.student_id = users.user_id', 'left')
                ->join('student_personal_info spi', 'spi.student_id = users.user_id', 'left')
                ->join('counselors', 'appointments.counselor_preference = counselors.counselor_id', 'left')
                ->where('appointments.student_id', $studentId)
                ->where('appointments.status', 'completed');

            // Add search functionality if search term is provided
            if (!empty($searchTerm)) {
                $query->groupStart()
                    ->like('appointments.student_id', $searchTerm)
                    ->orLike('users.username', $searchTerm)
                    ->orLike('users.email', $searchTerm)
                    ->orLike('spi.first_name', $searchTerm)
                    ->orLike('spi.last_name', $searchTerm)
                    ->orLike('appointments.preferred_date', $searchTerm)
                    ->orLike('appointments.preferred_time', $searchTerm)
                    ->orLike('appointments.method_type', $searchTerm)
                    ->orLike('appointments.purpose', $searchTerm)
                    ->orLike('appointments.reason', $searchTerm)
                    ->orLike('counselors.name', $searchTerm)
                    ->groupEnd();
            }

            $completedAppointments = $query->orderBy('pending_follow_up_count', 'DESC')
                ->orderBy('next_pending_date', 'ASC')
                ->orderBy('appointments.preferred_date', 'DESC')
                ->orderBy('appointments.preferred_time', 'DESC')
                ->findAll();

            log_message('info', 'Student FollowUpSessions::getCompletedAppointments - Found ' . count($completedAppointments) . ' completed appointments for student ' . $studentId . (!empty($searchTerm) ? ' for search: ' . $searchTerm : ''));

            return $this->respond([
                'status' => 'success',
                'appointments' => $completedAppointments,
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting completed appointments for student: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->fail('Failed to retrieve completed appointments: ' . $e->getMessage());
        }
    }

    /**
     * Get follow-up sessions for a specific parent appointment (student's own appointments only)
     */
    public function getFollowUpSessions()
    {
        // Check if user is logged in and is student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $studentId = session()->get('user_id_display') ?? session()->get('user_id');
            $parentAppointmentId = $this->request->getGet('parent_appointment_id');
            
            if (!$studentId) {
                return $this->fail('Invalid session data');
            }
            
            if (!$parentAppointmentId) {
                return $this->fail('Parent appointment ID is required');
            }

            // Verify that the parent appointment belongs to the logged-in student
            $appointmentModel = new AppointmentModel();
            $parentAppointment = $appointmentModel->where('id', $parentAppointmentId)
                ->where('student_id', $studentId)
                ->first();

            if (!$parentAppointment) {
                return $this->fail('Appointment not found or access denied');
            }

            $followUpModel = new FollowUpAppointmentModel();
            
            // Get follow-up sessions for the parent appointment with counselor name
            $followUpSessions = $followUpModel
                ->select('follow_up_appointments.*, COALESCE(counselors.name, "No Preference") as counselor_name')
                ->join('counselors', 'counselors.counselor_id = follow_up_appointments.counselor_id', 'left')
                ->where('parent_appointment_id', $parentAppointmentId)
                ->orderBy('follow_up_sequence', 'ASC')
                ->findAll();

            log_message('info', 'Student FollowUpSessions::getFollowUpSessions - Found ' . count($followUpSessions) . ' follow-up sessions for appointment ' . $parentAppointmentId . ' (student: ' . $studentId . ')');

            return $this->respond([
                'status' => 'success',
                'follow_up_sessions' => $followUpSessions
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting follow-up sessions for student: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->fail('Failed to retrieve follow-up sessions: ' . $e->getMessage());
        }
    }
}