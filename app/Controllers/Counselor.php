<?php

namespace App\Controllers;

use App\Models\CounselorModel;
use App\Models\UserModel;

class Counselor extends BaseController
{
    protected $counselorModel;
    protected $userModel;

    public function __construct()
    {
        $this->counselorModel = new CounselorModel();
        $this->userModel = new UserModel();
    }

    /**
     * Save counselor basic information after signup
     * This is called from the landing page after counselor signs up
     */
    public function saveBasicInfo()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }

        $counselorId = trim($this->request->getPost('counselor_id'));
        $name = trim($this->request->getPost('name'));
        $degree = trim($this->request->getPost('degree'));
        $email = trim($this->request->getPost('email'));
        $contactNumber = trim($this->request->getPost('contact_number'));
        $address = trim($this->request->getPost('address'));
        $civilStatus = $this->request->getPost('civil_status');
        $sex = $this->request->getPost('sex');
        $birthdate = $this->request->getPost('birthdate');

        // Validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'counselor_id' => 'required|min_length[3]',
            'name' => 'required|max_length[100]',
            'degree' => 'required|max_length[100]',
            'email' => 'required|valid_email',
            'contact_number' => 'required|regex_match[/^09[0-9]{9}$/]',
            'address' => 'required'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(', ', $validation->getErrors())
            ]);
        }

        // Check if counselor_id exists in users table
        $user = $this->userModel->where('user_id', $counselorId)->first();
        if (!$user) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'User account not found'
            ]);
        }

        // Check if counselor record already exists
        $existingCounselor = $this->counselorModel->getByCounselorId($counselorId);
        if ($existingCounselor) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Counselor information already exists'
            ]);
        }

        // Lookup admin email before saving (used to notify admin)
        $admin = $this->userModel->where('role', 'admin')->first();
        $adminEmail = ($admin && !empty($admin['email'])) ? $admin['email'] : null;

        // Prepare data
        $data = [
            'counselor_id' => $counselorId,
            'name' => $name,
            'degree' => $degree,
            'email' => $email,
            'contact_number' => $contactNumber,
            'address' => $address,
            'profile_picture' => base_url('/Photos/profile.png')
        ];

        if (!empty($civilStatus)) {
            $data['civil_status'] = $civilStatus;
        }
        if (!empty($sex)) {
            $data['sex'] = $sex;
        }
        if (!empty($birthdate)) {
            $data['birthdate'] = $birthdate;
        }

        try {
            $inserted = $this->counselorModel->insert($data);

            if ($inserted) {
                // Send notification email to admin (use pre-fetched admin email if available)
                $this->notifyAdminOfNewCounselor($counselorId, $name, $email, $adminEmail);

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Your information has been saved. Please wait for admin approval.'
                ]);
            } else {
                $errors = $this->counselorModel->errors();
                log_message('error', 'Failed to insert counselor: ' . json_encode($errors));
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to save counselor information: ' . implode(', ', $errors)
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception saving counselor info: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'An error occurred while saving your information'
            ]);
        }
    }

    /**
     * Send email notification to admin about new counselor signup
     */
    private function notifyAdminOfNewCounselor($counselorId, $name, $counselorEmail, $adminEmail = null)
    {
        try {
            // Prefer provided admin email, otherwise fetch from users table
            if (empty($adminEmail)) {
                $admin = $this->userModel->where('role', 'admin')->first();
                $adminEmail = ($admin && !empty($admin['email'])) ? $admin['email'] : null;
            }

            if (empty($adminEmail)) {
                log_message('error', 'No admin account found to send counselor notification');
                return false;
            }

            $emailService = \Config\Services::email();
            $emailConfig = config('Email');
            $emailService->setTo($adminEmail);
            $emailService->setFrom($emailConfig->fromEmail ?? 'no-reply@counselign.com', $emailConfig->fromName ?? 'Counselign System');
            $emailService->setSubject('New Counselor Registration - Pending Approval');
            
            $message = view('emails/admin_counselor_notification', [
                'counselor_id' => $counselorId,
                'name' => $name,
                'email' => $counselorEmail,
                'admin_dashboard_url' => base_url('admin/admins-management')
            ]);
            
            $emailService->setMessage($message);

            if ($emailService->send()) {
                log_message('info', "Admin notification sent for new counselor: $counselorId");
                return true;
            } else {
                log_message('error', "Failed to send admin notification for counselor: $counselorId. " . $emailService->printDebugger(['headers', 'subject', 'body']));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception sending admin notification: ' . $e->getMessage());
            return false;
        }
    }
}