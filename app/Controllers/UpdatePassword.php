<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use App\Models\UserModel;
use App\Helpers\UserActivityHelper;
use CodeIgniter\HTTP\ResponseInterface;

class UpdatePassword extends BaseController
{
    public function index()
    {
        $session = session();
        $request = $this->request;

        // Only allow POST
        if (!$request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method'])->setStatusCode(405);
        }

        if (!$session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not logged in'])->setStatusCode(403);
        }

        $user_id = $session->get('user_id_display') ?? $session->get('user_id');
        $role = $session->get('role');

        $current_password = $request->getPost('current_password') ?? $request->getPost('currentPassword');
        $new_password = $request->getPost('new_password') ?? $request->getPost('newPassword');
        $confirm_password = $request->getPost('confirm_password') ?? $request->getPost('confirmPassword');

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            return $this->response->setJSON(['success' => false, 'message' => 'All password fields are required']);
        }

        if ($new_password !== $confirm_password) {
            return $this->response->setJSON(['success' => false, 'message' => 'New passwords do not match']);
        }

        $userModel = new UserModel();

        // Build query
        $where = ['user_id' => $user_id];
        if ($role === 'admin') {
            $where['role'] = 'admin';
        }

        $user = $userModel->where($where)->first();

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        if (!password_verify($current_password, $user['password'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Current password is incorrect']);
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $currentTime = $manilaTime->format('Y-m-d H:i:s');

        $userModel->skipValidation(true)->update($user['id'], [
            'password' => $new_password_hash
        ]);

        // Update last_activity for password change
        $activityHelper = new UserActivityHelper();
        $activityHelper->updateLastActivity($user_id, 'password_change');

        return $this->response->setJSON(['success' => true, 'message' => 'Password updated successfully']);
    }
}
