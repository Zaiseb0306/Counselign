<?php

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Dashboard extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Debug session data
        $session = session();
        $loggedIn = $session->get('logged_in');
        $role = $session->get('role');
        $userId = $session->get('user_id');
        
        // Log session data for debugging
        log_message('debug', 'Counselor Dashboard - Session check: logged_in=' . ($loggedIn ? 'true' : 'false') . ', role=' . $role . ', user_id=' . $userId);
        
        // Ensure user is logged in
        if (!$loggedIn) {
            log_message('debug', 'Counselor Dashboard - User not logged in, redirecting to landing page');
            return redirect()->to('/');
        }
        
        // Check if user has counselor role, if not redirect to appropriate dashboard
        if ($role !== 'counselor') {
            log_message('debug', 'Counselor Dashboard - User role is ' . $role . ', redirecting to appropriate dashboard');
            if ($role === 'admin') {
                return redirect()->to(base_url('admin/dashboard'));
            } else {
                return redirect()->to(base_url('user/dashboard'));
            }
        }

        $data = [
            'title' => 'Counselor Dashboard',
            'username' => $session->get('username'),
            'email' => $session->get('email')
        ];

        return view('counselor/dashboard', $data);
    }

    /**
     * Get recent pending appointments for the logged-in counselor
     * Returns the 2 most recent pending appointments where counselor_preference matches the logged-in counselor
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getRecentPendingAppointments()
    {
        // Verify counselor is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as counselor',
                'appointments' => []
            ], 401);
        }

        try {
            // Use user_id_display which contains the actual counselor ID (e.g., "2023303610")
            // that matches the counselor_preference field in appointments table
            $counselor_id = session()->get('user_id_display') ?? session()->get('user_id');
            $db = \Config\Database::connect();

            // Query to get recent pending appointments for this counselor
            // Only fetch appointments where counselor_preference matches the logged-in counselor
            $query = "SELECT
                        a.id,
                        a.student_id,
                        a.preferred_date,
                        a.preferred_time,
                        a.consultation_type,
                        a.purpose,
                        a.counselor_preference,
                        a.status,
                        a.created_at,
                        u.username,
                        u.email as user_email,
                        CONCAT(sai.course, ' - ', sai.year_level) as course_year
                      FROM appointments a
                      LEFT JOIN users u ON a.student_id = u.user_id
                      LEFT JOIN student_academic_info sai ON sai.student_id = u.user_id
                      WHERE a.status = 'pending'
                      AND a.counselor_preference = ?
                      ORDER BY a.created_at DESC
                      LIMIT 2";

            $appointments = $db->query($query, [$counselor_id])->getResultArray();

            log_message('info', '[Counselor Dashboard] Fetched ' . count($appointments) . ' pending appointments for counselor: ' . $counselor_id);

            return $this->respond([
                'status' => 'success',
                'appointments' => $appointments,
                'count' => count($appointments)
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Counselor Dashboard] Error fetching pending appointments: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'An error occurred while fetching appointments',
                'appointments' => []
            ], 500);
        }
    }
}


