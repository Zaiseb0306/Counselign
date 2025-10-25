<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
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

        $user_id = $this->request->getPost('user_id');
        $password = $this->request->getPost('password');
        $role = $this->request->getPost('role'); // optional

        // Validate password complexity for login
        $validation = \Config\Services::validation();
        $validation->setRules([
            'user_id' => [
                'label' => 'User ID',
                'rules' => 'required|regex_match[/^\\d{10}$/]',
                'errors' => [
                    'required' => 'User ID is required',
                    'regex_match' => 'User ID must be exactly 10 digits',
                ],
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/] ',
                'errors' => [
                    'required' => 'Password is required',
                    'min_length' => 'Password must be at least 8 characters long',
                    'regex_match' => 'Password must include uppercase, lowercase, number, and special character',
                ],
            ],
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(' ', $validation->getErrors()),
            ]);
        }

        $user = $this->userModel->where('user_id', $user_id)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid User ID or password']);
        }

        // Check if user is verified
        if (!$user['is_verified']) {
            return $this->response->setJSON([
                'status' => 'unverified',
                'message' => 'Your account is not verified. Please verify your email.',
                'redirect' => base_url('verify-account/prompt') // Redirect to a prompt for verification
            ]);
        }

        // Optionally check role
        if ($role && $user['role'] !== $role) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Role mismatch']);
        }

        // Update last login time using direct database query
        $db = \Config\Database::connect();
        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $lastLogin = $manilaTime->format('Y-m-d H:i:s');
        $db->table('users')
           ->where('id', $user['id'])
           ->update(['last_login' => $lastLogin]);

        // Set session
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

        // Redirect to a neutral destination regardless of admin role
        if ($user['role'] === 'counselor') {
            $redirect = base_url('counselor/dashboard');
        } else {
            // Admins and regular users go to user dashboard
            $redirect = base_url('student/dashboard');
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

        $role = $this->request->getPost('role'); // Get role from signup form
        // Default to 'stundet' if not provided or invalid
        if (!in_array($role, ['student', 'counselor'])) {
            $role = 'student';
        }

        $userId = trim($this->request->getPost('userId'));
        $email = trim($this->request->getPost('email'));
        $password = trim($this->request->getPost('password'));
        $confirmPassword = trim($this->request->getPost('confirmPassword'));
        $username = trim($this->request->getPost('username')); // Assuming you have a username field in signup form

        log_message('info', "Signup attempt - User ID: $userId, Email: $email");

        // Server-side validation for signup
        $validation = \Config\Services::validation();
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

        if (!$validation->withRequest($this->request)->run()) {
            $response['message'] = implode(' ', $validation->getErrors());
        } else {
            log_message('error', 'Passed all validation, checking for duplicates');
            // Check if user_id or email already exists
            $existing = $this->userModel
                ->where('user_id', $userId)
                ->orWhere('email', $email)
                ->first();

            if ($existing) {
                log_message('error', "Duplicate found: " . json_encode($existing));
                $response['message'] = "User ID or email already exists.";
                log_message('error', "Signup error: User ID or email already exists");
            } else {
                log_message('error', 'No duplicate found, proceeding to insert');
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $default_profile = base_url('/Photos/profile.png');
                $verificationToken = $this->userModel->generateVerificationToken();

                $data = [
                    'user_id' => $userId,
                    'email' => $email,
                    'password' => $hashed_password,
                    'username' => $username, // Add username to data
                    'role' => $role,
                    'profile_picture' => $default_profile,
                    'verification_token' => $verificationToken,
                    'is_verified' => false,
                ];

                try {
                    // Use the UserModel to insert data, which respects allowedFields
                    $newUserId = $this->userModel->insert($data);

                    if ($newUserId) {
                        // Send verification email
                        $emailService = \Config\Services::email();
                        $emailService->setTo($email);
                        $emailService->setFrom('no-reply@counselign.com', 'Counselign'); // Replace with your actual email and name
                        $emailService->setSubject('Account Verification');
                        $message = view('emails/verification_email', ['token' => $verificationToken]); // Create this view file
                        $emailService->setMessage($message);

                        if ($emailService->send()) {
                            log_message('info', "Verification email sent to: $email");
                            $response['status'] = 'success';
                            $response['message'] = "Account created successfully. A verification email has been sent to your email address.";
                        } else {
                            // Log email sending error, but still consider account created
                            log_message('error', "Failed to send verification email to: $email. Error: " . $emailService->printDebugger(['headers', 'subject', 'body']));
                            $response['status'] = 'success'; // Still success, but inform user about email issue.
                            $response['message'] = "Account created, but failed to send verification email. Please check your spam or try again later.";
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
                    log_message('error', 'Stack trace: ' . $e->getTraceAsString());
                }
            }
        }

        return $this->response->setJSON($response);
    }

    public function logout()
    {
        $session = session();
        $session->destroy();
        return redirect()->to('/');
    }

    public function verificationPrompt()
    {
        return view('auth/verification_prompt'); // Create this view file for the modal/page
    }

    public function verifyAccount($token = null)
    {
        $response = [
            'status' => 'error',
            'message' => 'Invalid verification token.'
        ];

        if ($this->request->getMethod() === 'POST') {
            // For JSON requests, get the raw input
            $json = $this->request->getJSON();
            $token = $json->token ?? null;
            log_message('debug', 'Received POST request for verification. Token from JSON: ' . ($token ?? 'null'));
        }

        if (empty($token)) {
            log_message('debug', 'Verification token is empty.');
            return $this->response->setJSON($response);
        }

        $user = $this->userModel->getUserByVerificationToken($token);

        if ($user) {
            log_message('debug', 'User found for token: ' . $token . ', User ID: ' . $user['id']);
            $this->userModel->markUserAsVerified($user['id']);
            
            // Update last login time similar to the login method
            $db = \Config\Database::connect();
            $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
            $lastLogin = $manilaTime->format('Y-m-d H:i:s');
            $db->table('users')
               ->where('id', $user['id'])
               ->update(['last_login' => $lastLogin]);

            $response['status'] = 'success';
            $response['message'] = 'Account verified successfully. You can now log in.';
            
            // Log the user in after successful verification
            $session = session();
            $session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'logged_in' => true,
                'is_verified' => true, // Update verification status in session
                'user_id_display' => $user['user_id'],
                'last_login' => $lastLogin // Use the newly generated lastLogin time
            ]);
            
            // Redirect to a neutral destination regardless of admin role
            if ($user['role'] === 'counselor') {
                $response['redirect'] = base_url('counselor/dashboard');
            } else {
                // Admins and regular users go to user dashboard
                $response['redirect'] = base_url('student/dashboard');
            }
            
            return $this->response->setJSON($response);
        } else {
            log_message('debug', 'No user found for token: ' . $token);
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

        $identifier = $this->request->getPost('identifier'); // Can be email or user_id

        if (empty($identifier)) {
            $response['message'] = 'Please provide your email or user ID.';
            return $this->response->setJSON($response);
        }

        // Find user by email or user_id
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

        // Generate new token and update in DB
        $newVerificationToken = $this->userModel->generateVerificationToken();
        $this->userModel->setVerificationToken($user['id'], $newVerificationToken);

        // Send new verification email
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
            log_message('error', "Failed to resend verification email to: " . $user['email'] . ". Error: " . $emailService->printDebugger(['headers', 'subject', 'body']));
            $response['message'] = 'Failed to send new verification email. Please try again later.';
        }

        return $this->response->setJSON($response);
    }

    public function verifyAdmin()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request method']);
        }

        $user_id = trim($this->request->getPost('user_id'));
        $password = trim($this->request->getPost('password'));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'user_id' => [
                'label' => 'User ID',
                'rules' => 'required',
                'errors' => [
                    'required' => 'User ID is required',
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

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => implode(' ', $validation->getErrors()),
            ]);
        }

        $user = $this->userModel->where('user_id', $user_id)->first();
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