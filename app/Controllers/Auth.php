<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CounselorModel;
use App\Helpers\UserActivityHelper;
use App\Helpers\SecureLogHelper;

class Auth extends BaseController
{
    protected $userModel;
    protected $counselorModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->counselorModel = new CounselorModel();
    }

    public function index()
    {
        $data['csrf_token'] = csrf_hash();
        $data['csrf_token_name'] = csrf_token();
        return view('landing', $data);
    }

    public function login()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request method']);
        }

        $identifier = trim($this->request->getPost('identifier'));
        $password = $this->request->getPost('password');

        if (empty($identifier) || empty($password)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please provide both User ID/Email and Password'
            ]);
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/]',
                'errors' => [
                    'required' => 'Password is required',
                    'min_length' => 'Password must be at least 8 characters long',
                    'regex_match' => 'Password must include uppercase, lowercase, number, and special character',
                ],
            ],
        ]);

        $_POST['password'] = $password;

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(' ', $validation->getErrors()),
            ]);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            $user = $this->userModel->where('email', $identifier)->first();
        } else {
            if (!preg_match('/^[a-zA-Z0-9]{3,}$/', $identifier)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid User ID format'
                ]);
            }
            $user = $this->userModel->where('user_id', $identifier)->first();
        }

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
        }

        // Check verification status based on role
        if (!$user['is_verified']) {
            if ($user['role'] === 'counselor') {
                // Counselor accounts need admin approval
                return $this->response->setJSON([
                    'status' => 'unverified',
                    'message' => 'Your counselor account is pending admin approval. You will be notified via email once your account has been verified.'
                ]);
            } else {
                // Student accounts need email verification
                return $this->response->setJSON([
                    'status' => 'unverified',
                    'message' => 'Your account is not verified. Please verify your email.',
                    'redirect' => base_url('verify-account/prompt')
                ]);
            }
        }

        $db = \Config\Database::connect();
        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $lastLogin = $manilaTime->format('Y-m-d H:i:s');
        $db->table('users')
            ->where('id', $user['id'])
            ->update(['last_login' => $lastLogin]);

        $activityHelper = new UserActivityHelper();
        $activityHelper->updateLastActivity($user['user_id'], 'login');

        $session = session();
        $session->set([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'logged_in' => true,
            'user_id_display' => $user['user_id'],
            'last_login' => $lastLogin
        ]);

        switch ($user['role']) {
            case 'admin':
                $redirect = base_url('admin/dashboard');
                break;
            case 'counselor':
                $redirect = base_url('counselor/dashboard');
                break;
            case 'student':
            default:
                $redirect = base_url('student/dashboard');
                break;
        }

        return $this->response->setJSON(['status' => 'success', 'redirect' => $redirect]);
    }

    public function signup()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request method']);
        }

        $response = [
            'status' => 'error',
            'message' => ''
        ];

        $role = $this->request->getPost('role');
        if (!in_array($role, ['student', 'counselor'])) {
            $role = 'student';
        }

        $userId = trim($this->request->getPost('userId'));
        $email = trim($this->request->getPost('email'));
        $password = trim($this->request->getPost('password'));
        $confirmPassword = trim($this->request->getPost('confirmPassword'));
        $username = trim($this->request->getPost('username'));

        log_message('info', "Signup attempt - User ID: $userId, Email: $email, Role: $role");

        // Validation rules based on role
        $validation = \Config\Services::validation();
        
        if ($role === 'student') {
            $validation->setRules([
                'userId' => [
                    'label' => 'User ID',
                    'rules' => 'required|regex_match[/^\\d{10}$/]',
                    'errors' => [
                        'required' => 'User ID is required',
                        'regex_match' => 'User ID must be exactly 10 digits.',
                    ],
                ],
                'email' => [
                    'label' => 'Email',
                    'rules' => 'required|valid_email|is_unique[users.email]',
                    'errors' => [
                        'required' => 'Email is required',
                        'valid_email' => 'Please enter a valid email address',
                        'is_unique' => 'This email is already registered',
                    ],
                ],
                'password' => [
                    'label' => 'Password',
                    'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/]|matches[confirmPassword]',
                    'errors' => [
                        'required' => 'Password is required',
                        'min_length' => 'Password must be at least 8 characters long',
                        'regex_match' => 'Password must include uppercase, lowercase, number, and special character',
                        'matches' => 'Passwords do not match',
                    ],
                ],
                'confirmPassword' => [
                    'label' => 'Password Confirmation',
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Password confirmation is required',
                    ],
                ],
            ]);
        } else {
            // Counselor validation
            $validation->setRules([
                'userId' => [
                    'label' => 'Counselor ID',
                    'rules' => 'required|min_length[3]',
                    'errors' => [
                        'required' => 'Counselor ID is required',
                        'min_length' => 'Counselor ID must be at least 3 characters long',
                    ],
                ],
                'email' => [
                    'label' => 'Email',
                    'rules' => 'required|valid_email|is_unique[users.email]',
                    'errors' => [
                        'required' => 'Email is required',
                        'valid_email' => 'Please enter a valid email address',
                        'is_unique' => 'This email is already registered',
                    ],
                ],
                'password' => [
                    'label' => 'Password',
                    'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/]|matches[confirmPassword]',
                    'errors' => [
                        'required' => 'Password is required',
                        'min_length' => 'Password must be at least 8 characters long',
                        'regex_match' => 'Password must include uppercase, lowercase, number, and special character',
                        'matches' => 'Passwords do not match',
                    ],
                ],
                'confirmPassword' => [
                    'label' => 'Password Confirmation',
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'Password confirmation is required',
                    ],
                ],
            ]);
        }

        if (!$validation->withRequest($this->request)->run()) {
            $response['message'] = implode(' ', $validation->getErrors());
        } else {
            $existing = $this->userModel
                ->where('user_id', $userId)
                ->orWhere('email', $email)
                ->first();

            if ($existing) {
                $response['message'] = "User ID or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $default_profile = base_url('/Photos/profile.png');
                
                $data = [
                    'user_id' => $userId,
                    'email' => $email,
                    'password' => $hashed_password,
                    'username' => $username,
                    'role' => $role,
                    'profile_picture' => $default_profile,
                    'is_verified' => false,
                ];

                // Only generate verification token for students
                if ($role === 'student') {
                    $verificationToken = $this->userModel->generateVerificationToken();
                    $data['verification_token'] = $verificationToken;
                }

                try {
                    $newUserId = $this->userModel->insert($data);

                    if ($newUserId) {
                        if ($role === 'student') {
                            // Send verification email to student
                            $emailService = \Config\Services::email();
                            $emailService->setTo($email);
                            $emailService->setFrom('no-reply@counselign.com', 'Counselign');
                            $emailService->setSubject('Account Verification');
                            $message = view('emails/verification_email', ['token' => $verificationToken]);
                            $emailService->setMessage($message);

                            if ($emailService->send()) {
                                log_message('info', "Verification email sent to: $email");
                                $response['status'] = 'success';
                                $response['message'] = "Account created successfully. A verification email has been sent to your email address.";
                            } else {
                                log_message('error', "Failed to send verification email to: $email");
                                $response['status'] = 'success';
                                $response['message'] = "Account created, but failed to send verification email. Please check your spam or try again later.";
                            }
                        } else {
                            // For counselors, just mark as success and let frontend open the info modal
                            $response['status'] = 'success';
                            $response['message'] = "Counselor account created. Please complete your profile information.";
                        }
                        return $this->response->setJSON($response);
                    } else {
                        $errors = $this->userModel->errors();
                        $response['message'] = "Database error: Could not insert user. Errors: " . json_encode($errors);
                        log_message('error', "Database error: Could not insert user. Errors: " . json_encode($errors));
                    }
                } catch (\Exception $e) {
                    $response['message'] = "System error: " . $e->getMessage();
                    log_message('error', 'System Exception: ' . $e->getMessage());
                }
            }
        }

        return $this->response->setJSON($response);
    }

    public function logout()
    {
        try {
            $activityHelper = new \App\Helpers\UserActivityHelper();
            $activityHelper->updateLogoutActivity();

            $session = session();
            $session->destroy();
            return redirect()->to('/');
        } catch (\Exception $e) {
            log_message('error', 'Error in Auth logout: ' . $e->getMessage());
            $session = session();
            $session->destroy();
            return redirect()->to('/');
        }
    }

    public function verificationPrompt()
    {
        return view('auth/verification_prompt');
    }

    public function verifyAccount($token = null)
    {
        $response = [
            'status' => 'error',
            'message' => 'Invalid verification token.'
        ];

        if ($this->request->getMethod() === 'POST') {
            $json = $this->request->getJSON();
            $token = $json->token ?? null;
            SecureLogHelper::debug('Received POST request for verification');
        }

        if (empty($token)) {
            SecureLogHelper::debug('Verification token is empty');
            return $this->response->setJSON($response);
        }

        $user = $this->userModel->getUserByVerificationToken($token);

        if ($user) {
            SecureLogHelper::logUserAction('Account verification successful', $user['id']);
            $this->userModel->markUserAsVerified($user['id']);

            $db = \Config\Database::connect();
            $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
            $lastLogin = $manilaTime->format('Y-m-d H:i:s');
            $db->table('users')
                ->where('id', $user['id'])
                ->update(['last_login' => $lastLogin]);

            $activityHelper = new UserActivityHelper();
            $activityHelper->updateLastActivity($user['user_id'], 'account_verification');

            $response['status'] = 'success';
            $response['message'] = 'Account verified successfully. You can now log in.';

            $session = session();
            $session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'logged_in' => true,
                'is_verified' => true,
                'user_id_display' => $user['user_id'],
                'last_login' => $lastLogin
            ]);

            if ($user['role'] === 'counselor') {
                $response['redirect'] = base_url('counselor/dashboard');
            } else {
                $response['redirect'] = base_url('student/dashboard');
            }

            return $this->response->setJSON($response);
        } else {
            SecureLogHelper::debug('Verification failed - no user found for token');
            return $this->response->setJSON($response);
        }
    }

    public function resendVerificationEmail()
    {
        $response = [
            'status' => 'error',
            'message' => 'Failed to resend verification email.'
        ];

        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON($response);
        }

        $identifier = $this->request->getPost('identifier');

        if (empty($identifier)) {
            $response['message'] = 'Please provide your email or user ID.';
            return $this->response->setJSON($response);
        }

        $user = $this->userModel->where('email', $identifier)->orWhere('user_id', $identifier)->first();

        if (!$user) {
            $response['message'] = 'No account found with that email or user ID.';
            return $this->response->setJSON($response);
        }

        if ($user['is_verified']) {
            $response['message'] = 'Your account is already verified. Please try logging in.';
            $response['status'] = 'already_verified';
            return $this->response->setJSON($response);
        }

        $newVerificationToken = $this->userModel->generateVerificationToken();
        $this->userModel->setVerificationToken($user['id'], $newVerificationToken);

        $emailService = \Config\Services::email();
        $emailService->setTo($user['email']);
        $emailService->setFrom('no-reply@counselign.com', 'Counselign');
        $emailService->setSubject('Account Verification - Resend');
        $message = view('emails/verification_email', ['token' => $newVerificationToken]);
        $emailService->setMessage($message);

        if ($emailService->send()) {
            log_message('info', "Resent verification email to: " . $user['email']);
            $response['status'] = 'success';
            $response['message'] = 'A new verification email has been sent to your email address.';
        } else {
            log_message('error', "Failed to resend verification email to: " . $user['email']);
            $response['message'] = 'Failed to send new verification email. Please try again later.';
        }

        return $this->response->setJSON($response);
    }

    public function verifyAdmin()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request method']);
        }

        $identifier = trim($this->request->getPost('identifier'));
        $password = trim($this->request->getPost('password'));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'identifier' => [
                'label' => 'Identifier',
                'rules' => 'required',
                'errors' => [
                    'required' => 'User ID or Email is required',
                ],
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password is required',
                ],
            ],
        ]);

        $_POST['identifier'] = $identifier;
        $_POST['password'] = $password;

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(' ', $validation->getErrors()),
            ]);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        if ($isEmail) {
            $user = $this->userModel->where('email', $identifier)->first();
        } else {
            $user = $this->userModel->where('user_id', $identifier)->first();
        }

        if (!$user || $user['role'] !== 'admin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Admin account not found']);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid password']);
        }

        if (!$user['is_verified']) {
            return $this->response->setJSON([
                'status' => 'unverified',
                'message' => 'Your account is not verified. Please verify your email.',
                'redirect' => base_url('verify-account/prompt')
            ]);
        }

        $db = \Config\Database::connect();
        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $lastLogin = $manilaTime->format('Y-m-d H:i:s');
        $db->table('users')
            ->where('id', $user['id'])
            ->update(['last_login' => $lastLogin]);

        $activityHelper = new UserActivityHelper();
        $activityHelper->updateLastActivity($user['user_id'], 'admin_login');

        $session = session();
        $session->set([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'logged_in' => true,
            'user_id_display' => $user['user_id'],
            'last_login' => $lastLogin
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'redirect' => base_url('admin/dashboard')
        ]);
    }
}