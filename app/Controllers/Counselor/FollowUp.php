<?php

namespace App\Controllers\Counselor;

use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;
use App\Models\AppointmentModel;
use App\Models\FollowUpAppointmentModel;
use App\Models\CounselorAvailabilityModel;
use App\Models\NotificationsModel;
use CodeIgniter\API\ResponseTrait;

class FollowUp extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/follow_up');
    }

    /**
     * Get completed appointments for the logged-in counselor
     */
    public function getCompletedAppointments()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            // Normalize counselor ID to match DB constraint (VARCHAR(10)) for create
            if (is_string($counselorId)) {
                $counselorId = trim($counselorId);
                if (strlen($counselorId) > 10) {
                    $counselorId = substr($counselorId, 0, 10);
                }
            }
            // Normalize counselor ID to match DB constraint (VARCHAR(10))
            if (is_string($counselorId)) {
                $counselorId = trim($counselorId);
                if (strlen($counselorId) > 10) {
                    $counselorId = substr($counselorId, 0, 10);
                }
            }
            
            if (!$counselorId) {
                return $this->fail('Invalid session data');
            }

            // Get search parameter
            $searchTerm = $this->request->getGet('search');
            $searchTerm = trim($searchTerm ?? '');

            $appointmentModel = new AppointmentModel();
            
            // Build the query with follow-up count and pending follow-up indicator
            $query = $appointmentModel->select("appointments.*, 
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), users.username) as student_name, 
                    users.email as student_email,
                    (SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id) as follow_up_count,
                    (SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending') as pending_follow_up_count,
                    (SELECT MIN(fua.preferred_date) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending') as next_pending_date")
                ->join('users', 'appointments.student_id = users.user_id', 'left')
                ->join('student_personal_info spi', 'spi.student_id = users.user_id', 'left')
                ->where('appointments.counselor_preference', $counselorId)
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
                    ->groupEnd();
            }

            $completedAppointments = $query->orderBy('pending_follow_up_count', 'DESC')
                ->orderBy('next_pending_date', 'ASC')
                ->orderBy('appointments.preferred_date', 'DESC')
                ->orderBy('appointments.preferred_time', 'DESC')
                ->findAll();

            SecureLogHelper::info('Completed appointments retrieved', ['count' => count($completedAppointments)]);

            return $this->respond([
                'status' => 'success',
                'appointments' => $completedAppointments,
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            SecureLogHelper::error('Error getting completed appointments', ['error' => $e->getMessage()]);
            return $this->fail('Failed to retrieve completed appointments: ' . $e->getMessage());
        }
    }

    /**
     * Get follow-up sessions for a specific parent appointment
     */
    public function getFollowUpSessions()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $parentAppointmentId = $this->request->getGet('parent_appointment_id');
            
            if (!$parentAppointmentId) {
                return $this->fail('Parent appointment ID is required');
            }

            $followUpModel = new FollowUpAppointmentModel();
            
            // Get follow-up sessions for the parent appointment
            $followUpSessions = $followUpModel->getFollowUpChain($parentAppointmentId);

            return $this->respond([
                'status' => 'success',
                'follow_up_sessions' => $followUpSessions
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting follow-up sessions: ' . $e->getMessage());
            return $this->fail('Failed to retrieve follow-up sessions');
        }
    }

    /**
     * Get counselor availability for a specific date
     */
    public function getCounselorAvailability()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $date = $this->request->getGet('date');
            
            if (!$date) {
                return $this->fail('Date is required');
            }

            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            
            if (!$counselorId) {
                return $this->fail('Invalid session data');
            }

            // Get day of week from date
            $dayOfWeek = date('l', strtotime($date));
            
            $availabilityModel = new CounselorAvailabilityModel();
            $availability = $availabilityModel->getGroupedByDay($counselorId);
            
            // Get time slots for the specific day
            $timeSlots = [];
            if (isset($availability[$dayOfWeek])) {
                $timeSlots = array_column($availability[$dayOfWeek], 'time_scheduled');
            }

            return $this->respond([
                'status' => 'success',
                'day_of_week' => $dayOfWeek,
                'time_slots' => $timeSlots
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting counselor availability: ' . $e->getMessage());
            return $this->fail('Failed to retrieve counselor availability');
        }
    }

    /**
     * Get booked time ranges for the counselor for a specific date (approved regular appointments and pending/approved follow-ups)
     * Returns an array of strings matching the preferred_time values (e.g., "10:00 AM - 10:30 AM").
     */
    public function getBookedTimesForDate()
    {
        try {
            if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $date = trim((string) $this->request->getGet('date'));
            if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Invalid or missing date'
                ], 400);
            }

            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            $db = \Config\Database::connect();

            // Approved regular appointments for this counselor
            $appointments = $db->table('appointments')
                ->select('preferred_time')
                ->where('preferred_date', $date)
                ->where('status', 'approved')
                ->where('counselor_preference', $counselorId)
                ->get()->getResultArray();

            // Pending/approved follow-ups for this counselor
            $followUps = $db->table('follow_up_appointments')
                ->select('preferred_time')
                ->where('preferred_date', $date)
                ->where('counselor_id', $counselorId)
                ->whereIn('status', ['pending','approved'])
                ->get()->getResultArray();

            $times = [];
            foreach (array_merge($appointments, $followUps) as $r) {
                $t = trim((string) ($r['preferred_time'] ?? ''));
                if ($t !== '') { $times[] = $t; }
            }

            $times = array_values(array_unique($times));

            return $this->respond([
                'status' => 'success',
                'date' => $date,
                'booked' => $times
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in counselor getBookedTimesForDate: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'Server error'
            ], 500);
        }
    }

/**
 * Create a new follow-up appointment
 */
public function createFollowUp()
{
    // Log the request for debugging
    SecureLogHelper::debug('Creating follow-up appointment');
    
    // Check if user is logged in and is counselor
    if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
        SecureLogHelper::error('Follow-up creation failed - authentication failed');
        return $this->failUnauthorized('User not logged in or not authorized');
    }

    try {
        $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
        
        // Normalize counselor ID to match DB constraint (VARCHAR(10))
        if (is_string($counselorId)) {
            $counselorId = trim($counselorId);
            if (strlen($counselorId) > 10) {
                $counselorId = substr($counselorId, 0, 10);
            }
        }
        
        if (!$counselorId) {
            log_message('error', 'FollowUp::createFollowUp - Invalid session data');
            return $this->fail('Invalid session data');
        }

        // Log counselor ID
        log_message('debug', 'FollowUp::createFollowUp - Counselor ID: ' . $counselorId . ' (length: ' . strlen($counselorId) . ')');

        // Get form data
        $parentAppointmentId = $this->request->getPost('parent_appointment_id');
        $studentId = $this->request->getPost('student_id');
        $preferredDate = $this->request->getPost('preferred_date');
        $preferredTime = $this->request->getPost('preferred_time');
        $consultationType = $this->request->getPost('consultation_type');
        $description = $this->request->getPost('description');
        $reason = $this->request->getPost('reason');

        // Log received data for debugging
        log_message('debug', 'FollowUp::createFollowUp - Received data: ' . json_encode([
            'counselor_id' => $counselorId,
            'parent_appointment_id' => $parentAppointmentId,
            'student_id' => $studentId,
            'preferred_date' => $preferredDate,
            'preferred_time' => $preferredTime,
            'consultation_type' => $consultationType
        ]));

        // Validate required fields
        if (!$parentAppointmentId || !$studentId || !$preferredDate || !$preferredTime || !$consultationType) {
            $missingFields = [];
            if (!$parentAppointmentId) $missingFields[] = 'parent_appointment_id';
            if (!$studentId) $missingFields[] = 'student_id';
            if (!$preferredDate) $missingFields[] = 'preferred_date';
            if (!$preferredTime) $missingFields[] = 'preferred_time';
            if (!$consultationType) $missingFields[] = 'consultation_type';
            
            log_message('error', 'FollowUp::createFollowUp - Missing required fields: ' . implode(', ', $missingFields));
            return $this->fail('Missing required fields: ' . implode(', ', $missingFields));
        }

        // Validate counselor ID length
        if (!is_string($counselorId) || strlen($counselorId) === 0 || strlen($counselorId) > 10) {
            log_message('error', 'FollowUp::createFollowUp - Invalid counselor ID length: ' . strlen($counselorId));
            return $this->fail('Invalid counselor session data');
        }

        $followUpModel = new FollowUpAppointmentModel();
        
        // Check for time slot conflicts before creating
        if ($followUpModel->hasCounselorFollowUpConflict($counselorId, $preferredDate, $preferredTime)) {
            log_message('warning', 'FollowUp::createFollowUp - Time slot conflict detected');
            return $this->fail('Time slot conflicts with another follow-up session or appointment');
        }

        // Get next sequence number for this parent appointment
        $nextSequence = $followUpModel->getNextSequence($parentAppointmentId);

        // Set Manila timezone for database operations
        $originalTimezone = $this->setManilaTimezone();
        
        // Prepare data for insertion
        $followUpData = [
            'counselor_id' => $counselorId,
            'student_id' => $studentId,
            'parent_appointment_id' => $parentAppointmentId,
            'preferred_date' => $preferredDate,
            'preferred_time' => $preferredTime,
            'consultation_type' => $consultationType,
            'follow_up_sequence' => $nextSequence,
            'description' => $description ?? '',
            'reason' => $reason ?? '',
            'status' => 'pending'
        ];

        log_message('debug', 'FollowUp::createFollowUp - Attempting to insert: ' . json_encode($followUpData));

        // Insert the follow-up appointment
        if ($followUpModel->insert($followUpData)) {
            $insertId = $followUpModel->getInsertID();
            log_message('info', 'FollowUp::createFollowUp - Successfully created follow-up appointment with ID: ' . $insertId);
            
            // Get the created follow-up data for email notification
            $createdFollowUp = $followUpModel->find($insertId);
            
            // Send email notification to student
            $this->sendFollowUpNotificationToStudent($createdFollowUp, 'created');
            
            // Get counselor name for notification
            $counselorName = $this->getCounselorName($counselorId);
            
            // Create notification for student
            $this->createFollowUpNotification($studentId, $insertId, 'follow_up_session', 'New Follow-up Session Created', 'Counselor ' . $counselorName . ' has scheduled a new follow-up session for ' . date('F j, Y', strtotime($preferredDate)) . ' at ' . $preferredTime . '.');
            
            // Update last_activity for creating follow-up appointment
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateCounselorActivity($counselorId, 'create_follow_up');
            $activityHelper->updateStudentActivity($studentId, 'follow_up_created');
            
            // Restore original timezone
            $this->restoreTimezone($originalTimezone);
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Follow-up appointment created successfully',
                'follow_up_id' => $insertId
            ]);
        } else {
            $errors = $followUpModel->errors();
            log_message('error', 'FollowUp::createFollowUp - Model validation failed: ' . json_encode($errors));
            
            // Restore original timezone
            $this->restoreTimezone($originalTimezone);
            
            return $this->fail('Validation failed: ' . implode(', ', $errors));
        }

    } catch (\Exception $e) {
        log_message('error', 'FollowUp::createFollowUp - Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        log_message('error', 'FollowUp::createFollowUp - Stack trace: ' . $e->getTraceAsString());
        
        // Restore original timezone in case of exception
        if (isset($originalTimezone)) {
            $this->restoreTimezone($originalTimezone);
        }
        
        return $this->fail('Failed to create follow-up appointment: ' . $e->getMessage());
    }
}

    /**
     * Mark a follow-up session as completed
     */
    public function completeFollowUp()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            $sessionId = $this->request->getPost('id');

            if (!$sessionId) {
                return $this->fail('Follow-up session id is required');
            }

            $followUpModel = new FollowUpAppointmentModel();
            $sessionRow = $followUpModel->find($sessionId);
            if (!$sessionRow) {
                return $this->failNotFound('Follow-up session not found');
            }

            // Ensure counselor owns this follow-up
            if ($sessionRow['counselor_id'] !== $counselorId) {
                return $this->failForbidden('You are not allowed to modify this follow-up');
            }

            if ($sessionRow['status'] === 'completed') {
                return $this->respond(['status' => 'success', 'message' => 'Follow-up already completed']);
            }

            // Set Manila timezone for database operations
            $originalTimezone = $this->setManilaTimezone();
            
            // Update with Manila timezone
            $followUpModel->update($sessionId, [
                'status' => 'completed'
            ]);

            // Send email notification to student
            $this->sendFollowUpNotificationToStudent($sessionRow, 'completed');

            // Update last_activity for completing follow-up
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateCounselorActivity($counselorId, 'complete_follow_up');
            $activityHelper->updateStudentActivity($sessionRow['student_id'], 'follow_up_completed');

            // Restore original timezone
            $this->restoreTimezone($originalTimezone);

            return $this->respond([
                'status' => 'success',
                'message' => 'Follow-up marked as completed'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FollowUp::completeFollowUp - Exception: ' . $e->getMessage());
            
            // Restore original timezone in case of exception
            if (isset($originalTimezone)) {
                $this->restoreTimezone($originalTimezone);
            }
            
            return $this->fail('Failed to complete follow-up');
        }
    }

    /**
     * Cancel a follow-up session with reason
     */
    public function cancelFollowUp()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            $sessionId = $this->request->getPost('id');
            $reason = trim((string) $this->request->getPost('reason'));

            if (!$sessionId) {
                return $this->fail('Follow-up session id is required');
            }
            if ($reason === '') {
                return $this->fail('Cancellation reason is required');
            }

            $followUpModel = new FollowUpAppointmentModel();
            $sessionRow = $followUpModel->find($sessionId);
            if (!$sessionRow) {
                return $this->failNotFound('Follow-up session not found');
            }

            // Ensure counselor owns this follow-up
            if ($sessionRow['counselor_id'] !== $counselorId) {
                return $this->failForbidden('You are not allowed to modify this follow-up');
            }

            if ($sessionRow['status'] === 'completed') {
                return $this->fail('Cannot cancel a completed follow-up');
            }

            // Set Manila timezone for database operations
            $originalTimezone = $this->setManilaTimezone();
            
            // Update with Manila timezone
            $followUpModel->update($sessionId, [
                'status' => 'cancelled',
                'reason' => $reason
            ]);

            // Send email notification to student
            $this->sendFollowUpNotificationToStudent($sessionRow, 'cancelled');

            // Get counselor name for notification
            $counselorName = $this->getCounselorName($counselorId);

            // Create notification for student
            $this->createFollowUpNotification($sessionRow['student_id'], $sessionId, 'follow_up_session', 'Follow-up Session Cancelled', 'Counselor ' . $counselorName . ' has cancelled your follow-up session scheduled for ' . date('F j, Y', strtotime($sessionRow['preferred_date'])) . ' at ' . $sessionRow['preferred_time'] . '. Reason: ' . $reason . '.');

            // Update last_activity for cancelling follow-up
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateCounselorActivity($counselorId, 'cancel_follow_up');
            $activityHelper->updateStudentActivity($sessionRow['student_id'], 'follow_up_cancelled');

            // Restore original timezone
            $this->restoreTimezone($originalTimezone);

            return $this->respond([
                'status' => 'success',
                'message' => 'Follow-up cancelled successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FollowUp::cancelFollowUp - Exception: ' . $e->getMessage());
            
            // Restore original timezone in case of exception
            if (isset($originalTimezone)) {
                $this->restoreTimezone($originalTimezone);
            }
            
            return $this->fail('Failed to cancel follow-up');
        }
    }

    /**
     * Edit a pending follow-up session
     */
    public function editFollowUp()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            $sessionId = $this->request->getPost('id');
            $preferredDate = $this->request->getPost('preferred_date');
            $preferredTime = $this->request->getPost('preferred_time');
            $consultationType = $this->request->getPost('consultation_type');
            $description = $this->request->getPost('description');
            $reason = $this->request->getPost('reason');

            if (!$sessionId) {
                return $this->fail('Follow-up session id is required');
            }

            $followUpModel = new FollowUpAppointmentModel();
            $sessionRow = $followUpModel->find($sessionId);
            if (!$sessionRow) {
                return $this->failNotFound('Follow-up session not found');
            }

            // Ensure counselor owns this follow-up
            if ($sessionRow['counselor_id'] !== $counselorId) {
                return $this->failForbidden('You are not allowed to modify this follow-up');
            }

            // Only allow editing pending follow-ups
            if ($sessionRow['status'] !== 'pending') {
                return $this->fail('Only pending follow-up sessions can be edited');
            }

            // Validate required fields
            if (!$preferredDate || !$preferredTime || !$consultationType) {
                return $this->fail('Required fields are missing');
            }

            // Check for conflicts with other follow-up sessions
            if ($followUpModel->hasCounselorFollowUpConflict($counselorId, $preferredDate, $preferredTime, $sessionId)) {
                return $this->fail('Time slot conflicts with another follow-up session');
            }

            // Set Manila timezone for database operations
            $originalTimezone = $this->setManilaTimezone();
            
            // Update with Manila timezone
            $updateData = [
                'preferred_date' => $preferredDate,
                'preferred_time' => $preferredTime,
                'consultation_type' => $consultationType,
                'description' => $description ?? '',
                'reason' => $reason ?? ''
            ];

            if ($followUpModel->update($sessionId, $updateData)) {
                // Get updated session data for email
                $updatedSession = $followUpModel->find($sessionId);
                
                // Send email notification to student
                $this->sendFollowUpNotificationToStudent($updatedSession, 'edited');

                // Get counselor name for notification
                $counselorName = $this->getCounselorName($counselorId);

                // Create notification for student
                $this->createFollowUpNotification($sessionRow['student_id'], $sessionId, 'follow_up_session', 'Follow-up Session Updated', 'Counselor ' . $counselorName . ' has updated your follow-up session. New schedule: ' . date('F j, Y', strtotime($preferredDate)) . ' at ' . $preferredTime . '.');

                // Update last_activity for editing follow-up
                $activityHelper = new UserActivityHelper();
                $activityHelper->updateCounselorActivity($counselorId, 'edit_follow_up');
                $activityHelper->updateStudentActivity($sessionRow['student_id'], 'follow_up_edited');

                // Restore original timezone
                $this->restoreTimezone($originalTimezone);

                return $this->respond([
                    'status' => 'success',
                    'message' => 'Follow-up session updated successfully'
                ]);
            } else {
                $errors = $followUpModel->errors();
                
                // Restore original timezone
                $this->restoreTimezone($originalTimezone);
                
                return $this->fail('Validation failed: ' . implode(', ', $errors));
            }

        } catch (\Exception $e) {
            log_message('error', 'FollowUp::editFollowUp - Exception: ' . $e->getMessage());
            
            // Restore original timezone in case of exception
            if (isset($originalTimezone)) {
                $this->restoreTimezone($originalTimezone);
            }
            
            return $this->fail('Failed to edit follow-up session: ' . $e->getMessage());
        }
    }

    /**
     * Get current datetime in Manila timezone with specified format
     * 
     * @return string Manila timezone datetime in format 'Y-m-d H:i:s'
     */
    private function getManilaDateTime(): string
    {
        try {
            // Set timezone to Asia/Manila
            $manilaTimezone = new \DateTimeZone('Asia/Manila');
            $manilaDateTime = new \DateTime('now', $manilaTimezone);
            
            // Return formatted datetime
            return $manilaDateTime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Fallback to server time if timezone setting fails
            log_message('error', 'Failed to get Manila timezone: ' . $e->getMessage());
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Set Manila timezone for database operations
     * 
     * @return string Original timezone before change
     */
    private function setManilaTimezone(): string
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Manila');
        return $originalTimezone;
    }

    /**
     * Restore original timezone after database operations
     * 
     * @param string $originalTimezone Original timezone to restore
     */
    private function restoreTimezone(string $originalTimezone): void
    {
        date_default_timezone_set($originalTimezone);
    }

    /**
     * Send follow-up notification email to student
     * 
     * @param array $followUpData Follow-up session data
     * @param string $actionType Type of action (created, edited, completed, cancelled)
     */
    private function sendFollowUpNotificationToStudent(array $followUpData, string $actionType): void
    {
        try {
            // Get counselor information for email
            $db = \Config\Database::connect();
            $counselorInfo = $db->table('counselors c')
                ->select('c.counselor_id, c.name, u.email')
                ->join('users u', 'u.user_id = c.counselor_id', 'left')
                ->where('c.counselor_id', $followUpData['counselor_id'])
                ->get()
                ->getRowArray();

            if (!$counselorInfo) {
                log_message('error', 'Counselor information not found for follow-up ID: ' . $followUpData['id']);
                return;
            }

            // Get student email
            $studentEmail = $this->getStudentEmail($followUpData['student_id']);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for follow-up ID: ' . $followUpData['id']);
                return;
            }

            $emailService = new \App\Services\AppointmentEmailService();

            if ($actionType === 'created') {
                $emailSent = $emailService->sendFollowUpCreatedNotification($followUpData['student_id'], $followUpData, $counselorInfo);
            } elseif ($actionType === 'edited') {
                $emailSent = $emailService->sendFollowUpEditedNotification($followUpData['student_id'], $followUpData, $counselorInfo);
            } elseif ($actionType === 'completed') {
                $emailSent = $emailService->sendFollowUpCompletedNotification($followUpData['student_id'], $followUpData, $counselorInfo);
            } elseif ($actionType === 'cancelled') {
                $emailSent = $emailService->sendFollowUpCancelledNotification($followUpData['student_id'], $followUpData, $counselorInfo);
            } else {
                log_message('error', 'Invalid action type for follow-up email notification: ' . $actionType);
                return;
            }

            if ($emailSent) {
                log_message('info', 'Follow-up ' . $actionType . ' notification sent successfully to student: ' . $followUpData['student_id']);
            } else {
                log_message('error', 'Failed to send follow-up ' . $actionType . ' notification to student: ' . $followUpData['student_id']);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error sending follow-up notification to student: ' . $e->getMessage());
        }
    }

    /**
     * Get student email from users table
     * 
     * @param string $studentId Student ID
     * @return string|null Student email or null if not found
     */
    private function getStudentEmail(string $studentId): ?string
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->table('users')
                ->select('email')
                ->where('user_id', $studentId)
                ->get()
                ->getRowArray();

            return $result ? $result['email'] : null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting student email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get counselor name from counselors table
     * 
     * @param string $counselorId Counselor ID
     * @return string Counselor name or counselor ID as fallback
     */
    private function getCounselorName(string $counselorId): string
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->table('counselors')
                ->select('name')
                ->where('counselor_id', $counselorId)
                ->get()
                ->getRowArray();
            
            if ($result && !empty($result['name'])) {
                return trim($result['name']);
            }
            
            return $counselorId; // Fallback to counselor ID if name not found
        } catch (\Exception $e) {
            log_message('error', 'Error getting counselor name: ' . $e->getMessage());
            return $counselorId; // Fallback to counselor ID on error
        }
    }

    /**
     * Create notification for follow-up session actions
     * 
     * @param string $studentId Student ID to receive notification
     * @param int $relatedId Follow-up session ID
     * @param string $type Notification type ('follow_up_session')
     * @param string $title Notification title
     * @param string $message Notification message
     */
    private function createFollowUpNotification(string $studentId, int $relatedId, string $type, string $title, string $message): void
    {
        try {
            $notificationsModel = new NotificationsModel();
            $notificationData = [
                'user_id' => $studentId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_id' => $relatedId,
                'is_read' => 0
            ];
            $notificationsModel->createNotification($notificationData);
        } catch (\Exception $e) {
            log_message('error', 'Error creating follow-up notification: ' . $e->getMessage());
        }
    }
}
