<?php

namespace App\Controllers\Student;


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
            return $this->response->setJSON([
                'success' => true,
                'user_id' => $user['user_id'],
                'username' => $user['username'] ?? '',
                'courseYear' => $user['course_year'] ?? '',
                'email' => $user['email'],
                'role' => $user['role'],
                'profile_picture' => $user['profile_picture'] ?? 'Photos/profile.png',
                'last_login' => $user['last_login'] ?? null
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found'])->setStatusCode(404);
        }
    }

    public function profile()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to('/');
        }

        return view('student/student_profile');
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
                $activityHelper->updateStudentActivity($user_id, 'update_profile');
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

        if (!$session->get('logged_in') || $session->get('role') !== 'student') {
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

            $newFileName = 'student_' . $userId . '_' . time() . '.' . $ext;
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

            return $this->response->setJSON(['success' => true, 'picture_url' => $relativePath]);
        } catch (\Throwable $e) {
            log_message('error', 'Student profile picture upload error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'An error occurred while uploading the picture']);
        }
    }
}
