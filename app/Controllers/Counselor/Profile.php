<?php

namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use App\Models\UserModel;
use App\Helpers\UserActivityHelper;
use App\Controllers\BaseController;

class Profile extends BaseController
{
    public function getProfile()
    {
        $session = session();

        if (!$session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not logged in'])->setStatusCode(401);
        }

        $user_id = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
        }

        $userModel = new UserModel();
        $user = $userModel->where('user_id', $user_id)->first();

        if ($user) {
            // Fetch counselor details if exists
            $db = \Config\Database::connect();
            $counselor = $db->table('counselors')->where('counselor_id', $user['user_id'])->get()->getRowArray();
            return $this->response->setJSON([
                'success' => true,
                'user_id' => $user['user_id'],
                'username' => $user['username'] ?? '',
                'email' => $user['email'],
                'role' => $user['role'],
                'last_login' => $user['last_login'] ?? null,
                'profile_picture' => $user['profile_picture'] ?? null,
                'counselor' => $counselor
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found'])->setStatusCode(404);
        }
    }

    public function profile()
    {
        // Debug session data
        $session = session();
        $loggedIn = $session->get('logged_in');
        $role = $session->get('role');
        $userId = $session->get('user_id');
        
        // Log session data for debugging
        log_message('debug', 'Counselor Profile - Session check: logged_in=' . ($loggedIn ? 'true' : 'false') . ', role=' . $role . ', user_id=' . $userId);
        
        return view('counselor/counselor_profile');
    }

    public function updatePersonalInfo()
    {
        $session = session();
        // Allow OPTIONS preflight
        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setJSON(['success' => true]);
        }

        if (empty($session->get('logged_in'))) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized access'])->setStatusCode(403);
        }

        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
        }

        $post = $this->request->getPost();

        $incoming = [
            'name' => trim($post['fullname'] ?? ''),
            'degree' => trim($post['degree'] ?? ''),
            'email' => trim($post['email'] ?? ''),
            'contact_number' => trim($post['contact'] ?? ''),
            'address' => trim($post['address'] ?? ''),
            'birthdate' => trim($post['birthdate'] ?? ''),
            'sex' => trim($post['sex'] ?? ''),
            'civil_status' => trim($post['civil_status'] ?? ''),
        ];

        // Enhanced validation
        $validationErrors = [];
        
        // Email validation
        if (!empty($post['email']) && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = 'Invalid email format';
        }
        
        // Check for duplicate email if email is being changed
        if (!empty($post['email']) && $post['email'] !== 'N/A') {
            $db = \Config\Database::connect();
            $existingCounselor = $db->table('counselors')
                ->where('email', $post['email'])
                ->where('counselor_id !=', $userId)
                ->get()
                ->getRowArray();
            
            if ($existingCounselor) {
                $validationErrors[] = 'This email is already registered to another counselor';
            }
        }
        
        // Date validation for birthdate
        if (!empty($post['birthdate']) && $post['birthdate'] !== 'N/A') {
            $birthdate = \DateTime::createFromFormat('Y-m-d', $post['birthdate']);
            if (!$birthdate || $birthdate->format('Y-m-d') !== $post['birthdate']) {
                $validationErrors[] = 'Invalid birthdate format';
            }
        }
        
        // Contact number validation (basic format check)
        if (!empty($post['contact']) && $post['contact'] !== 'N/A') {
            $contact = preg_replace('/[^0-9+]/', '', $post['contact']);
            if (strlen($contact) < 10) {
                $validationErrors[] = 'Contact number must be at least 10 digits';
            }
        }
        
        if (!empty($validationErrors)) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => implode(', ', $validationErrors)
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('counselors');

        // Filter to existing columns to avoid SQL errors and handle default values
        $fieldNames = $db->getFieldNames('counselors');
        $data = [];
        foreach ($incoming as $key => $value) {
            if (in_array($key, $fieldNames, true)) {
                // Store 'N/A' values for first-time users, but skip empty strings for optional fields
                if ($value !== '' || $value === 'N/A') {
                    $data[$key] = $value;
                }
            }
        }

        // Upsert counselor row by counselor_id with proper error handling
        try {
            $exists = $builder->where('counselor_id', $userId)->countAllResults() > 0;
            $data['counselor_id'] = $userId;
            
            if ($exists) {
                $result = $builder->where('counselor_id', $userId)->update($data);
                log_message('debug', 'Counselor personal info updated for user: ' . $userId);
            } else {
                $result = $builder->insert($data);
                log_message('debug', 'Counselor personal info inserted for user: ' . $userId);
            }
            
            if (!$result) {
                log_message('error', 'Failed to save counselor personal info for user: ' . $userId);
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Failed to save personal information. Please try again.'
                ]);
            }
            
            return $this->response->setJSON(['success' => true]);
            
        } catch (\Exception $e) {
            log_message('error', 'Counselor personal info save error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Database error occurred while saving personal information.'
            ]);
        }
    }

    public function updateProfile()
    {
        $session = session();
        $request = $this->request;

        if (!$session->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not logged in'])->setStatusCode(401);
        }

        $user_id = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
        }

        $username = $request->getPost('username');
        $email = $request->getPost('email');

        // Validate
        if (empty($username) || empty($email)) {
            return $this->response->setJSON(['success' => false, 'message' => 'All fields are required']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid email format']);
        }

        $userModel = new \App\Models\UserModel();
        try {
            $data = [
                'username' => $username,
                'email' => $email
            ];
            // Find the user by user_id
            $user = $userModel->where('user_id', $user_id)->first();
            if ($user) {
                // Check if email is being changed and if it already exists
                if ($user['email'] !== $email) {
                    $existingUser = $userModel->where('email', $email)->first();
                    if ($existingUser) {
                        return $this->response->setJSON(['success' => false, 'message' => 'This email is already registered to another account']);
                    }
                }

                // Use skipValidation to avoid unique email check since we manually checked above
                $userModel->skipValidation(true)->update($user['id'], $data);
                
                // Update last_activity for profile update
                $activityHelper = new UserActivityHelper();
                $activityHelper->updateCounselorActivity($user_id, 'update_profile');
                // Update session data
                $session->set('username', $username);
                $session->set('email', $email);

                $affectedRows = $userModel->db->affectedRows();
                log_message('debug', 'Rows updated: ' . $affectedRows);

                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Database error occurred']);
        }
    }

    public function updateProfilePicture()
    {
        $session = session();

        if (!$session->get('logged_in') || $session->get('role') !== 'counselor') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized access'])->setStatusCode(403);
        }

        // Allow OPTIONS preflight (and simply return OK)
        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setJSON(['success' => true]);
        }
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid method'])->setStatusCode(405);
        }

        try {
            $userId = $session->get('user_id_display') ?? $session->get('user_id');
            if (!$userId) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
            }
            $file = $this->request->getFile('profile_picture');
            if (!$file) {
                return $this->response->setJSON(['success' => false, 'message' => 'No file received']);
            }
            if (!$file->isValid()) {
                return $this->response->setJSON(['success' => false, 'message' => 'File upload error: ' . $file->getErrorString()]);
            }

            $ext = strtolower($file->getExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, JPEG, PNG, GIF']);
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->response->setJSON(['success' => false, 'message' => 'File too large. Max 5MB']);
            }

            $uploadDir = FCPATH . 'Photos/profile_pictures/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'counselor_' . $userId . '_' . time() . '.' . $ext;
            $relativePath = 'Photos/profile_pictures/' . $newFileName;

            // Remove old picture if any and not default
            $userModel = new \App\Models\UserModel();
            $user = $userModel->where('user_id', $userId)->first();
            if ($user && !empty($user['profile_picture']) && $user['profile_picture'] !== 'Photos/profile.png') {
                $oldPath = FCPATH . str_replace(['..', './', '\\'], '', $user['profile_picture']);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            if (!$file->move($uploadDir, $newFileName)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to save uploaded file']);
            }

            // Update users table (source of truth)
            $userModel->where('user_id', $userId)->set(['profile_picture' => $relativePath])->update();
            $session->set('profile_picture', $relativePath);

            // If counselors table also has a profile_picture column, sync it for consistency
            try {
                $db = \Config\Database::connect();
                $counselorFields = $db->getFieldNames('counselors');
                if (in_array('profile_picture', $counselorFields, true)) {
                    $db->table('counselors')
                        ->where('counselor_id', $userId)
                        ->update(['profile_picture' => $relativePath]);
                }
            } catch (\Throwable $syncEx) {
                // Non-fatal: ignore if table/column doesn't exist
            }

            return $this->response->setJSON(['success' => true, 'picture_url' => $relativePath]);
        } catch (\Throwable $e) {
            log_message('error', 'Counselor picture upload error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error uploading file']);
        }
    }
}
