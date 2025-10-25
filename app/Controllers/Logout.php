<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Logout extends Controller
{
    public function index()
    {
        try {
            // Update last_inactive_at before destroying the session
            if (session()->get('user_id')) {
                $db = \Config\Database::connect();
                $builder = $db->table('users');
                $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
                $manilaNow = $manilaTime->format('Y-m-d H:i:s');
                $builder->set([
                    'logout_time' => $manilaNow,
                    'last_active_at' => $manilaNow,
                    'last_activity' => $manilaNow,
                    'last_inactive_at' => $manilaNow
                ]);
                $builder->where('user_id', session()->get('user_id_display'));
                $builder->update();
            }

            // Clear all session variables
            session()->destroy();

            // Redirect to landing page
            return redirect()->to('/');
        } catch (\Exception $e) {
            log_message('error', 'Error in Logout controller: ' . $e->getMessage());
            // Still redirect even if there's an error
            return redirect()->to('/');
        }
    }
} 