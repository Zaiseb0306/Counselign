<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use App\Helpers\UserActivityHelper;

class TestActivity extends Controller
{
    public function testLogout()
    {
        try {
            $userId = '2023303620'; // Test with a known user ID
            
            $activityHelper = new UserActivityHelper();
            $result = $activityHelper->updateLogoutActivity($userId);
            
            // Check the database to verify the update
            $db = \Config\Database::connect();
            $user = $db->table('users')
                ->select('last_activity, last_active_at, last_inactive_at, logout_time')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();
            
            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Activity updated successfully',
                    'user_id' => $userId,
                    'database_values' => $user
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to update activity',
                    'database_values' => $user
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }
    
    public function testLogin()
    {
        try {
            $userId = '2023303620'; // Test with a known user ID
            
            $activityHelper = new UserActivityHelper();
            $result = $activityHelper->updateLastActivity($userId, 'test_login');
            
            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Activity updated successfully',
                    'user_id' => $userId
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to update activity'
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }
}
