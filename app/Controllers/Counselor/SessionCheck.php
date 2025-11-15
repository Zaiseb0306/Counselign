<?php

namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SessionCheck extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $isAjax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['HTTP_ACCEPT']) &&
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        );

        $session = session();
        $loggedIn = $session->get('logged_in');
        $role = $session->get('role');
        $userId = $session->get('user_id');

        // Log session data for debugging
        log_message('debug', 'Counselor SessionCheck - logged_in=' . ($loggedIn ? 'true' : 'false') . ', role=' . $role . ', user_id=' . $userId);

        if (!$loggedIn) {
            if ($isAjax) {
                return $this->respond(['loggedin' => false]);
            } else {
                return redirect()->to('/');
            }
        }

        // Check for role if specified
        $requestedRole = $this->request->getGet('role');
        if ($requestedRole && $role !== $requestedRole) {
            if ($isAjax) {
                return $this->respond([
                    'loggedin' => true,
                    'role' => $role,
                    'redirect' => $role === 'admin'
                        ? base_url('admin/dashboard')
                        : ($role === 'counselor' ? base_url('counselor/dashboard') : base_url('user/dashboard'))
                ]);
            } else {
                if ($role === 'admin') {
                    return redirect()->to(base_url('admin/dashboard'));
                } elseif ($role === 'counselor') {
                    return redirect()->to(base_url('counselor/dashboard'));
                } else {
                    return redirect()->to(base_url('user/dashboard'));
                }
            }
        }

        if ($isAjax) {
            return $this->respond([
                'loggedin' => true,
                'user_id' => $userId,
                'role' => $role
            ]);
        }

        return $this->respond(['loggedin' => true]);
    }
}
