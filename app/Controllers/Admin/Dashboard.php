<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        $data = [
            'title' => 'Admin Dashboard',
            'username' => session()->get('username'),
            'email' => session()->get('email')
        ];

        return view('admin/dashboard', $data);
    }

    public function getAdminData()
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Log for debugging
        log_message('debug', 'Admin data request received: ' . date('Y-m-d H:i:s'));

        // Check if user is logged in
        if (!session()->get('logged_in')) {
            log_message('error', 'User not logged in');
            return $this->response->setJSON(['success' => false, 'message' => 'User not logged in']);
        }

        // Check if user is admin
        if (session()->get('role') !== 'admin') {
            log_message('error', 'User not admin: ' . session()->get('role'));
            return $this->response->setStatusCode(403)
                                ->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        try {
            // Get database connection
            $db = \Config\Database::connect();
            
            // Query to fetch user data
            $builder = $db->table('users');
            $builder->select('user_id, username, email, profile_picture, last_login');
            $builder->where('id', session()->get('user_id'));
            $query = $builder->get();
            
            if ($user = $query->getRowArray()) {
                // Always return a full URL for the profile picture
                if (!empty($user['profile_picture'])) {
                    if (strpos($user['profile_picture'], 'http') === 0) {
                        // Already a full URL
                        $user['profile_picture'] = $user['profile_picture'];
                    } else {
                        // Make sure it starts with a single slash
                        $relativePath = '/' . ltrim($user['profile_picture'], '/');
                        // Build the full URL using baseURL
                        $user['profile_picture'] = base_url($relativePath);
                    }
                } else {
                    // Fallback to default profile picture
                    $user['profile_picture'] = base_url('Photos/profile.png');
                }
                
                log_message('debug', 'Admin data fetched successfully: ' . json_encode($user));
                return $this->response->setJSON(['success' => true, 'data' => $user]);
            } else {
                log_message('error', 'No user found with ID: ' . session()->get('user_id'));
                return $this->response->setJSON(['success' => false, 'message' => 'User data not found']);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Database error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function adminsManagement()
    {
        return view('admin/admins_management');
    }
} 