<?php

namespace App\Controllers\Admin;


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
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        return view('admin/follow_up_sessions');
    }

    /**
     * Get all completed appointments and their corresponding follow-up sessions
     */
    public function getAllCompletedAppointments()
    {
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        try {
            // Get search parameter
            $searchTerm = $this->request->getGet('search');
            $searchTerm = trim($searchTerm ?? '');

            $appointmentModel = new AppointmentModel();
            
            // Build the query with follow-up count and pending follow-up indicator
            $query = $appointmentModel->select("appointments.*, 
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), users.username) as student_name, 
                    users.email as student_email,
                    COALESCE(counselors.name, 'No Preference') as counselor_name,
                    (SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id) as follow_up_count,
                    (SELECT COUNT(*) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending') as pending_follow_up_count,
                    (SELECT MIN(fua.preferred_date) FROM follow_up_appointments fua WHERE fua.parent_appointment_id = appointments.id AND fua.status = 'pending') as next_pending_date")
                ->join('users', 'appointments.student_id = users.user_id', 'left')
                ->join('student_personal_info spi', 'spi.student_id = users.user_id', 'left')
                ->join('counselors', 'appointments.counselor_preference = counselors.counselor_id', 'left')
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

            log_message('info', 'Admin FollowUpSessions::getAllCompletedAppointments - Found ' . count($completedAppointments) . ' completed appointments' . (!empty($searchTerm) ? ' for search: ' . $searchTerm : ''));

            return $this->respond([
                'status' => 'success',
                'appointments' => $completedAppointments,
                'search_term' => $searchTerm
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting all completed appointments: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->fail('Failed to retrieve completed appointments: ' . $e->getMessage());
        }
    }

    /**
     * Get follow-up sessions for a specific parent appointment
     */
    public function getFollowUpSessions()
    {
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
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
}
