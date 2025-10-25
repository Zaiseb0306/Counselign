<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Appointment extends BaseController
{
    public function schedule()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to('/');
        }

        return view('student/student_schedule_appointment');
    }

    public function checkPendingAppointment()
    {
        try {
            $session = session();
            
            // Check if user is logged in
            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }
            
            $user_id = $session->get('user_id_display');
            
            if (!$user_id) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User ID not found in session'
                ]);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('appointments');
            $builder->where('student_id', $user_id);
            $builder->where('status', 'pending');
            $pendingAppointment = $builder->get()->getRowArray();

            return $this->response->setJSON([
                'status' => 'success',
                'hasPending' => !empty($pendingAppointment),
                'appointment' => $pendingAppointment
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in checkPendingAppointment: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error checking pending appointment: ' . $e->getMessage()
                ]);
        }
    }

    public function checkAppointmentEligibility()
    {
        try {
            $session = session();

            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            $userId = $session->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User ID not found in session'
                ]);
            }

            $appointmentModel = new \App\Models\AppointmentModel();
            $followUpModel = new \App\Models\FollowUpAppointmentModel();

            $hasPending = $appointmentModel->hasPendingAppointment($userId);
            $hasApproved = $appointmentModel->hasApprovedAppointment($userId);
            $hasPendingFollowUp = $followUpModel->hasPendingFollowUp($userId);

            // Allowed only when no pending, no approved upcoming, and no pending follow-up
            $allowed = !$hasPending && !$hasApproved && !$hasPendingFollowUp;

            return $this->response->setJSON([
                'status' => 'success',
                'hasPending' => $hasPending,
                'hasApproved' => $hasApproved,
                'hasPendingFollowUp' => $hasPendingFollowUp,
                'allowed' => $allowed
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in checkAppointmentEligibility: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error checking appointment eligibility'
                ]);
        }
    }

    public function getCounselors()
    {
        try {
            // Check if user is logged in
            $session = session();
            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            $db = \Config\Database::connect();
            
            // Check if table exists
            $tables = $db->listTables();
            if (!in_array('counselors', $tables)) {
                return $this->response->setStatusCode(500)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Counselors table does not exist'
                    ]);
            }

            $builder = $db->table('counselors');

            // Build a safe select list based on existing columns to avoid SQL errors
            $existingCounselorFields = $db->getFieldNames('counselors');
            $selectFields = ['counselors.counselor_id', 'counselors.name'];
            if (in_array('specialization', $existingCounselorFields, true)) {
                $selectFields[] = 'counselors.specialization';
            }

            // If users table and profile_picture column exist, include it via LEFT JOIN
            $tables = $db->listTables();
            if (in_array('users', $tables, true)) {
                $userFields = $db->getFieldNames('users');
                if (in_array('profile_picture', $userFields, true)) {
                    $builder->join('users', 'counselors.counselor_id = users.user_id', 'left');
                    $selectFields[] = 'users.profile_picture AS profile_picture';
                }
            }

            $counselors = $builder
                ->select(implode(', ', $selectFields))
                ->orderBy('counselors.name')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'counselors' => $counselors
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in getCounselors: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error loading counselors: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Get counselors by availability for specific date and time
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getCounselorsByAvailability()
    {
        try {
            // Check if user is logged in
            $session = session();
            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            // Get date and time from query parameters
            $preferredDate = $this->request->getGet('date');
            $preferredTime = $this->request->getGet('time'); // expected normalized or human readable
            $requestedDay = $this->request->getGet('day');
            $from24 = $this->request->getGet('from');
            $to24 = $this->request->getGet('to');
            $timeMode = $this->request->getGet('timeMode');

            if (empty($preferredDate) || empty($preferredTime)) {
                return $this->response->setStatusCode(400)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Date and time parameters are required'
                    ]);
            }

            // Get day of week from input (prefer explicit param, fallback to date)
            $dayOfWeek = !empty($requestedDay) ? $requestedDay : date('l', strtotime($preferredDate));
            
            $db = \Config\Database::connect();
            
            // Check if counselors table exists
            $tables = $db->listTables();
            if (!in_array('counselors', $tables)) {
                return $this->response->setStatusCode(500)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Counselors table does not exist'
                    ]);
            }

            $builder = $db->table('counselors');

            // Build a safe select list based on existing columns
            $existingFields = $db->getFieldNames('counselors');
            $selectFields = ['counselor_id', 'name'];
            if (in_array('specialization', $existingFields, true)) {
                $selectFields[] = 'specialization';
            }

            // Get all counselors first
            $allCounselors = $builder
                ->select(implode(', ', $selectFields))
                ->orderBy('name')
                ->get()
                ->getResultArray();

            // Filter counselors by availability
            $availableCounselors = [];
            
            foreach ($allCounselors as $counselor) {
                $isAvailable = $this->isCounselorAvailable($counselor, $dayOfWeek, $preferredTime, $from24, $to24, $timeMode);
                if ($isAvailable) {
                    $availableCounselors[] = $counselor;
                }
            }

            return $this->response->setJSON([
                'status' => 'success',
                'counselors' => $availableCounselors,
                'dayOfWeek' => $dayOfWeek,
                'preferredTime' => $preferredTime
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in getCounselorsByAvailability: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error loading counselors by availability: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * Check if counselor is available for specific day and time
     * 
     * @param array $counselor
     * @param string $dayOfWeek
     * @param string $preferredTime
     * @return bool
     */
    private function isCounselorAvailable(array $counselor, string $dayOfWeek, string $preferredTime, ?string $from24 = null, ?string $to24 = null, ?string $timeMode = null): bool
    {
        try {
            $db = \Config\Database::connect();
            
            // Pull availability rows from counselor_availability for the specific day
            $rows = $db->table('counselor_availability')
                ->select('available_days, time_scheduled')
                ->where('counselor_id', $counselor['counselor_id'])
                ->where('available_days', $dayOfWeek)
                ->get()
                ->getResultArray();

            if (empty($rows)) {
                return false;
            }

            // If no specific time provided, any row for that day qualifies
            if (empty($preferredTime) && empty($from24) && empty($to24)) {
                return true;
            }

            // Determine comparison strategy: overlap (preferred) or substring fallback
            $useOverlap = ($timeMode === 'overlap') && !empty($from24) && !empty($to24);
            $preferredStart = $useOverlap ? $this->toMinutes($from24) : null;
            $preferredEnd = $useOverlap ? $this->toMinutes($to24) : null;

            foreach ($rows as $r) {
                $slot = (string) ($r['time_scheduled'] ?? '');
                if ($slot === '' || strtolower($slot) === 'null') {
                    // Null/empty time means whole day availability for that day
                    return true;
                }

                if ($useOverlap) {
                    // Parse stored time range (handles both "HH:MM-HH:MM" and "H:MM AM/PM-H:MM AM/PM")
                    $parts = explode('-', $slot);
                    if (count($parts) !== 2) {
                        continue;
                    }
                    $slotStart = $this->toMinutes(trim($parts[0]));
                    $slotEnd = $this->toMinutes(trim($parts[1]));
                    if ($slotStart === null || $slotEnd === null) {
                        continue;
                    }
                    if ($this->rangesOverlap($preferredStart, $preferredEnd, $slotStart, $slotEnd)) {
                        return true;
                    }
                } else {
                    // Fallback: substring contains (works for both formats)
                    if (strpos($slot, $preferredTime) !== false) {
                        return true;
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            log_message('error', 'Error checking counselor availability: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert a time string to minutes since midnight
     * Handles both "HH:MM" (24-hour) and "H:MM AM/PM" (12-hour) formats
     */
    private function toMinutes(?string $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }
        
        $time = trim($time);
        
        // Handle 12-hour format: "1:30 PM" or "12:00 AM"
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $ampm = strtoupper($matches[3]);
            
            // Convert to 24-hour format
            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return ($hour * 60) + $minute;
        }
        
        // Handle 24-hour format: "13:30"
        $m = [];
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            return ($h * 60) + $min;
        }
        
        return null;
    }

    /**
     * Check if [aStart,aEnd) overlaps with [bStart,bEnd)
     */
    private function rangesOverlap(int $aStart, int $aEnd, int $bStart, int $bEnd): bool
    {
        return ($aStart < $bEnd) && ($bStart < $aEnd);
    }

    public function save()
    {
        $session = session();
        $response = [
            'status' => 'error',
            'message' => ''
        ];

        if (!$session->get('logged_in')) {
            $response['message'] = 'You must be logged in to schedule an appointment.';
            return $this->response->setJSON($response);
        }

        $user_id = $session->get('user_id_display');
        if (!$user_id) {
            $response['message'] = 'User ID not found. Please log in again.';
            return $this->response->setJSON($response);
        }

        if ($this->request->getMethod() === 'POST') {
            $preferred_date = trim($this->request->getPost('preferredDate'));
            $preferred_time = trim($this->request->getPost('preferredTime'));
            $consultation_type = trim($this->request->getPost('consultationType'));
            $purpose = trim($this->request->getPost('purpose'));
            $counselor_preference = trim($this->request->getPost('counselorPreference') ?? 'No preference');
            $description = trim($this->request->getPost('description'));

            if (empty($preferred_date)) {
                $response['message'] = 'Please select a preferred date.';
                return $this->response->setJSON($response);
            }
            if (empty($preferred_time)) {
                $response['message'] = 'Please select a preferred time.';
                return $this->response->setJSON($response);
            }
            if (empty($consultation_type)) {
                $response['message'] = 'Please select a consultation type.';
                return $this->response->setJSON($response);
            }
            if (empty($purpose)) {
                $response['message'] = 'Please select the purpose of your consultation.';
                return $this->response->setJSON($response);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('appointments');
            $builder->where([
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time
            ]);
            $builder->where('status', 'approved');
            $exists = $builder->countAllResults();

            if ($exists > 0) {
                $response['message'] = 'This time slot is already booked. Please select a different time or date.';
                return $this->response->setJSON($response);
            }

            log_message('error', 'Trying to insert appointment for user_id: ' . $user_id);

            $data = [
                'student_id' => $user_id,
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time,
                'consultation_type' => $consultation_type,
                'purpose' => $purpose,
                'counselor_preference' => $counselor_preference,
                'description' => $description,
                'status' => 'pending'
            ];

            if ($builder->insert($data)) {
                // Set Manila timezone
                $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
                $currentTime = $manilaTime->format('Y-m-d H:i:s');

                // Update user's activity for this specific appointment
                $db->table('users')
                    ->where('user_id', $user_id)
                    ->update([
                        'last_active_at' => $currentTime,
                        'last_activity' => $currentTime
                    ]);

                $response['status'] = 'success';
                $response['message'] = 'Your appointment has been scheduled successfully. Please wait for admin approval.';
                $response['appointment_id'] = $db->insertID();
            } else {
                $response['message'] = 'Database error. Please try again later.';
            }
            return $this->response->setJSON($response);
        } else {
            $response['message'] = 'Invalid request method.';
            return $this->response->setJSON($response);
        }
    }

    public function getMyAppointments()
    {
        $session = session();
        $user_id = $session->get('user_id_display');
        $db = \Config\Database::connect();
        $builder = $db->table('appointments');
        $builder->where('student_id', $user_id);
        $appointments = $builder->get()->getResultArray();

        // Optionally join with counselors to get counselor name
        foreach ($appointments as &$appointment) {
            if (!empty($appointment['counselor_preference']) && $appointment['counselor_preference'] !== 'No preference') {
                $counselor = $db->table('counselors')
                    ->where('counselor_id', $appointment['counselor_preference'])
                    ->get()
                    ->getRowArray();
                $appointment['counselor_name'] = $counselor ? $counselor['name'] : 'Not assigned';
            } else {
                $appointment['counselor_name'] = 'Not assigned';
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    public function viewAppointments()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to('/');
        }

        return view('student/my_appointments');
    }

    public function update()
    {
        $data = $this->request->getJSON(true);
        $appointment_id = $data['appointment_id'] ?? null;

        if (!$appointment_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Appointment ID is required']);
        }

        $updateData = [
            'preferred_date' => $data['preferred_date'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null,
            'consultation_type' => $data['consultation_type'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'counselor_preference' => $data['counselor_preference'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'pending'
        ];

        $db = \Config\Database::connect();
        $builder = $db->table('appointments');
        $builder->where('id', $appointment_id);

        if ($builder->update($updateData)) {
            // Set Manila timezone
            $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
            $currentTime = $manilaTime->format('Y-m-d H:i:s');

            // Get the user_id for this specific appointment
            $appointment = $builder->where('id', $appointment_id)->get()->getRowArray();
            
            if ($appointment) {
                // Update user's activity for this specific appointment
                $db->table('users')
                    ->where('user_id', $appointment['student_id'])
                    ->update([
                        'last_active_at' => $currentTime,
                        'last_activity' => $currentTime
                    ]);
            }

            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to update appointment']);
        }
    }

    /**
     * Check for counselor conflicts before scheduling appointment
     */
    public function checkCounselorConflicts()
    {
        try {
            $session = session();
            
            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            $counselorId = $this->request->getGet('counselor_id');
            $date = $this->request->getGet('date');
            $time = $this->request->getGet('time');

            if (empty($counselorId) || empty($date) || empty($time)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Counselor ID, date, and time are required'
                ]);
            }

            $appointmentModel = new \App\Models\AppointmentModel();
            $followUpModel = new \App\Models\FollowUpAppointmentModel();

            // Check for regular appointment conflicts
            $hasAppointmentConflict = $appointmentModel->hasCounselorConflict($counselorId, $date, $time);
            $appointmentConflicts = $hasAppointmentConflict ? $appointmentModel->getCounselorConflicts($counselorId, $date, $time) : [];

            // Check for follow-up appointment conflicts
            $hasFollowUpConflict = $followUpModel->hasCounselorFollowUpConflict($counselorId, $date, $time);
            $followUpConflicts = $hasFollowUpConflict ? $followUpModel->getCounselorFollowUpConflicts($counselorId, $date, $time) : [];

            $hasConflict = $hasAppointmentConflict || $hasFollowUpConflict;

            return $this->response->setJSON([
                'status' => 'success',
                'hasConflict' => $hasConflict,
                'conflictType' => $hasConflict ? ($hasAppointmentConflict ? 'appointment' : 'follow_up') : null,
                'appointmentConflicts' => $appointmentConflicts,
                'followUpConflicts' => $followUpConflicts,
                'message' => $hasConflict ? 
                    ($hasAppointmentConflict ? 
                        'The selected counselor has a pending or approved appointment at this time.' : 
                        'The selected counselor has a pending follow-up session at this time.') : 
                    'No conflicts found. This time slot is available.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error checking counselor conflicts: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error checking counselor availability'
                ]);
        }
    }

    /**
     * Check for counselor conflicts before editing appointment
     */
    public function checkEditConflicts()
    {
        try {
            $session = session();
            
            if (!$session->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            $appointmentId = $this->request->getGet('appointment_id');
            $counselorId = $this->request->getGet('counselor_id');
            $date = $this->request->getGet('date');
            $time = $this->request->getGet('time');

            if (empty($appointmentId) || empty($counselorId) || empty($date) || empty($time)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Appointment ID, counselor ID, date, and time are required'
                ]);
            }

            $appointmentModel = new \App\Models\AppointmentModel();
            $followUpModel = new \App\Models\FollowUpAppointmentModel();

            // Check for regular appointment conflicts (excluding current appointment)
            $hasAppointmentConflict = $appointmentModel->hasCounselorConflict($counselorId, $date, $time, $appointmentId);
            $appointmentConflicts = $hasAppointmentConflict ? $appointmentModel->getCounselorConflicts($counselorId, $date, $time, $appointmentId) : [];

            // Check for follow-up appointment conflicts
            $hasFollowUpConflict = $followUpModel->hasCounselorFollowUpConflict($counselorId, $date, $time);
            $followUpConflicts = $hasFollowUpConflict ? $followUpModel->getCounselorFollowUpConflicts($counselorId, $date, $time) : [];

            $hasConflict = $hasAppointmentConflict || $hasFollowUpConflict;

            return $this->response->setJSON([
                'status' => 'success',
                'hasConflict' => $hasConflict,
                'conflictType' => $hasConflict ? ($hasAppointmentConflict ? 'appointment' : 'follow_up') : null,
                'appointmentConflicts' => $appointmentConflicts,
                'followUpConflicts' => $followUpConflicts,
                'message' => $hasConflict ? 
                    ($hasAppointmentConflict ? 
                        'The selected counselor has a pending or approved appointment at this time.' : 
                        'The selected counselor has a pending follow-up session at this time.') : 
                    'No conflicts found. This time slot is available.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error checking edit conflicts: ' . $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Error checking counselor availability'
                ]);
        }
    }

    public function delete($id)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('appointments');
        if ($builder->where('id', $id)->delete()) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete appointment']);
        }
    }

    public function cancel()
    {
        $data = $this->request->getJSON(true);
        $appointment_id = $data['appointment_id'] ?? null;
        $reason = 'Reason from Student: ' . $data['reason'] ?? null;

        if (!$appointment_id || !$reason) {
            return $this->response->setJSON(['success' => false, 'message' => 'Appointment ID and reason are required']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('appointments');

        // Get the specific appointment first
        $appointment = $builder->where('id', $appointment_id)->get()->getRowArray();
        
        if (!$appointment) {
            return $this->response->setJSON(['success' => false, 'message' => 'Appointment not found']);
        }

        $updateData = [
            'status' => 'cancelled',
            'reason' => $reason
        ];

        if ($builder->where('id', $appointment_id)->update($updateData)) {
            // Set Manila timezone
            $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
            $currentTime = $manilaTime->format('Y-m-d H:i:s');

            // Update user's activity for this specific appointment
            $db->table('users')
                ->where('user_id', $appointment['student_id'])
                ->update([
                    'last_active_at' => $currentTime,
                    'last_activity' => $currentTime
                ]);

            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to cancel appointment']);
        }
    }

    /**
     * Get counselor schedules with time_scheduled data organized by day
     */
    public function getCounselorSchedules()
    {
        try {
            // Check if user is logged in
            if (!session()->get('logged_in')) {
                return $this->response->setStatusCode(401)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'User not logged in'
                    ]);
            }

            $db = \Config\Database::connect();
            
            // Get all counselors with their availability data
            $builder = $db->table('counselor_availability ca');
            $builder->select('ca.counselor_id, ca.available_days, ca.time_scheduled, c.name as counselor_name');
            $builder->join('counselors c', 'ca.counselor_id = c.counselor_id', 'left');
            $builder->where('ca.available_days IS NOT NULL');
            $builder->where('ca.available_days !=', '');
            $builder->where('ca.time_scheduled IS NOT NULL');
            $builder->where('ca.time_scheduled !=', '');
            
            $schedules = $builder->get()->getResultArray();
            
            // Organize schedules by day
            $schedulesByDay = [];
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            
            foreach ($schedules as $schedule) {
                $availableDays = explode(',', $schedule['available_days']);
                $timeScheduled = $schedule['time_scheduled'];
                
                foreach ($availableDays as $day) {
                    $day = trim($day);
                    if (in_array($day, $daysOfWeek)) {
                        if (!isset($schedulesByDay[$day])) {
                            $schedulesByDay[$day] = [];
                        }
                        
                        $schedulesByDay[$day][] = [
                            'counselor_id' => $schedule['counselor_id'],
                            'counselor_name' => $schedule['counselor_name'] ?: 'Unknown Counselor',
                            'time_scheduled' => $timeScheduled
                        ];
                    }
                }
            }
            
            // Sort counselors alphabetically within each day
            foreach ($schedulesByDay as $day => $counselors) {
                usort($schedulesByDay[$day], function($a, $b) {
                    return strcmp($a['counselor_name'], $b['counselor_name']);
                });
            }
            
            return $this->response->setJSON([
                'status' => 'success',
                'schedules' => $schedulesByDay
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching counselor schedules: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error fetching counselor schedules'
            ]);
        }
    }
}
