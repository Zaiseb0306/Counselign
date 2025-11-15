<?php

namespace App\Controllers\Admin;


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

        if (!session()->get('logged_in')) {
            if ($isAjax) {
                return $this->respond(['loggedin' => false]);
            } else {
                return redirect()->to('/UGCSystem/public/auth/logout');
            }
        }

        // Check for role if specified
        $role = $this->request->getGet('role');
        if ($role && session()->get('role') !== $role) {
            if ($isAjax) {
                return $this->respond([
                    'loggedin' => true,
                    'role' => session()->get('role'),
                    'redirect' => session()->get('role') === 'admin'
                        ? '/UGCSystem/public/admin/dashboard'
                        : '/UGCSystem/public/user/dashboard'
                ]);
            } else {
                if (session()->get('role') === 'admin') {
                    return redirect()->to('/UGCSystem/public/admin/dashboard');
                } else {
                    return redirect()->to('/UGCSystem/public/user/dashboard');
                }
            }
        }

        if ($isAjax) {
            return $this->respond([
                'loggedin' => true,
                'user_id' => session()->get('user_id'),
                'role' => session()->get('role')
            ]);
        }

        return $this->respond(['loggedin' => true]);
    }
} 