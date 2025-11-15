<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use App\Helpers\UserActivityHelper;

class Logout extends Controller
{
    public function index()
    {
        try {
            // Update all activity fields before destroying the session (dynamic ID detection)
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateLogoutActivity();

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