<?php

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use App\Models\AppointmentModel;
use App\Models\FollowUpAppointmentModel;
use App\Models\CounselorAvailabilityModel;
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
                    ->orLike('appointments.consultation_type', $searchTerm)
                    ->orLike('appointments.purpose', $searchTerm)
                    ->orLike('appointments.reason', $searchTerm)
                    ->groupEnd();
            }

            $completedAppointments = $query->orderBy('pending_follow_up_count', 'DESC')
                ->orderBy('next_pending_date', 'ASC')
                ->orderBy('appointments.preferred_date', 'DESC')
                ->orderBy('appointments.preferred_time', 'DESC')
                ->findAll();

            log_message('info', 'Counselor FollowUp::getCompletedAppointments - Found ' . count($completedAppointments) . ' completed appointments' . (!empty($searchTerm) ? ' for search: ' . $searchTerm : ''));

            return $this->respond([
                'status' => 'success',
                'appointments' => $completedAppointments,
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting completed appointments: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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
     * Create a new follow-up appointment
     */
    public function createFollowUp()
    {
        // Log the request method and headers for debugging
        log_message('debug', 'FollowUp::createFollowUp - Request method: ' . $this->request->getMethod());
        log_message('debug', 'FollowUp::createFollowUp - Request headers: ' . json_encode($this->request->getHeaders()));
        log_message('debug', 'FollowUp::createFollowUp - Raw input: ' . $this->request->getBody());
        
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            log_message('error', 'FollowUp::createFollowUp - Authentication failed: logged_in=' . (session()->get('logged_in') ? 'true' : 'false') . ', role=' . session()->get('role'));
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            $counselorId = session()->get('user_id_display') ?? session()->get('user_id');
            
            if (!$counselorId) {
                log_message('error', 'FollowUp::createFollowUp - Invalid session data: counselorId is null');
                return $this->fail('Invalid session data');
            }

            // Log counselor ID length
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

            // Check counselor ID length against database constraint
            if (strlen($counselorId) > 10) {
                log_message('error', 'FollowUp::createFollowUp - Counselor ID too long: ' . $counselorId . ' (length: ' . strlen($counselorId) . ', max: 10)');
                return $this->fail('Counselor ID is too long for database constraint');
            }

            $followUpModel = new FollowUpAppointmentModel();
            
            // Get next sequence number for this parent appointment
            $nextSequence = $followUpModel->getNextSequence($parentAppointmentId);

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
                
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Follow-up appointment created successfully',
                    'follow_up_id' => $insertId
                ]);
            } else {
                $errors = $followUpModel->errors();
                log_message('error', 'FollowUp::createFollowUp - Model validation failed: ' . json_encode($errors));
                return $this->fail('Validation failed: ' . implode(', ', $errors));
            }

        } catch (\Exception $e) {
            log_message('error', 'FollowUp::createFollowUp - Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
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

            $followUpModel->update($sessionId, ['status' => 'completed']);

            return $this->respond([
                'status' => 'success',
                'message' => 'Follow-up marked as completed'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FollowUp::completeFollowUp - Exception: ' . $e->getMessage());
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

            $followUpModel->update($sessionId, [
                'status' => 'cancelled',
                'reason' => $reason,
            ]);

            return $this->respond([
                'status' => 'success',
                'message' => 'Follow-up cancelled successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FollowUp::cancelFollowUp - Exception: ' . $e->getMessage());
            return $this->fail('Failed to cancel follow-up');
        }
    }
}
