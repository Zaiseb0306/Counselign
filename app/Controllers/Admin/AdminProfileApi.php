<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;
use App\Helpers\SecureLogHelper;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class AdminProfileApi extends BaseController
{
    use ResponseTrait;

    public function updateProfile()
    {
        // Debug: log incoming POST data
        SecureLogHelper::debug('Admin profile update request', $this->request->getPost());

        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->failForbidden('Unauthorized access');
        }

        $user_id = session()->get('user_id');
        $field = $this->request->getPost('field');
        $value = trim($this->request->getPost('value'));

        // Validate input
        if ($field === null || $value === null) {
            return $this->fail('Missing required fields');
        }

        try {
            // Validate email if updating email
            if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $this->fail('Invalid email format');
            }

            // Check if email already exists (if updating email)
            if ($field === 'email') {
                $db = \Config\Database::connect();
                $builder = $db->table('users');
                $builder->where('email', $value);
                $builder->where('user_id !=', $user_id);
                
                if ($builder->countAllResults() > 0) {
                    return $this->fail('This email is already registered to another account');
                }
            }

            // Update the field
            $db = \Config\Database::connect();
            $builder = $db->table('users');
            $builder->set($field, $value);
            $builder->where('id', $user_id);
            $builder->where('role', 'admin');
            
            if ($builder->update() || $db->affectedRows() === 0) {
                // Update last_activity for profile update
                $activityHelper = new UserActivityHelper();
                $adminId = session()->get('user_id_display');
                $activityHelper->updateAdminActivity($adminId, 'update_profile');
                
                // Update session data
                session()->set($field, $value);
                
                return $this->respond([
                    'success' => true,
                    'message' => ucfirst($field) . ' updated successfully'
                ]);
            } else {
                log_message('error', 'Update failed. Last query: ' . $db->getLastQuery());
                log_message('error', 'DB Error: ' . json_encode($db->error()));
                return $this->fail('Failed to update ' . $field);
            }

        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->fail('Error: ' . $e->getMessage());
        }
    }

    public function updatePassword()
    {
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->failForbidden('Unauthorized access');
        }

        $user_id = session()->get('user_id');
        $current_password = $this->request->getPost('current_password');
        $new_password = $this->request->getPost('new_password');
        $confirm_password = $this->request->getPost('confirm_password');

        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            return $this->fail('Missing required fields');
        }

        if ($new_password !== $confirm_password) {
            return $this->fail('New passwords do not match');
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('users');
            $builder->select('password');
            $builder->where('user_id', $user_id);
            $builder->where('role', 'admin');
            $user = $builder->get()->getRow();

            if (!$user) {
                return $this->fail('User not found');
            }

            // Verify current password
            if (!password_verify($current_password, $user->password)) {
                return $this->fail('Current password is incorrect');
            }

            // Update password
            $builder->set('password', password_hash($new_password, PASSWORD_DEFAULT));
            $builder->where('id', $user_id);
            
            if ($builder->update()) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Password updated successfully'
                ]);
            } else {
                return $this->fail('Failed to update password');
            }

        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            return $this->fail('Error: ' . $e->getMessage());
        }
    }

    public function updateProfilePicture()
    {
        log_message('error', '--- updateProfilePicture CALLED ---');

        // Set headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $response = ['success' => false, 'message' => ''];

        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            log_message('error', 'Session check failed');
            $response['message'] = 'Unauthorized access';
            return $this->response->setJSON($response);
        }

        if ($this->request->getMethod() === 'POST') {
            try {
                SecureLogHelper::debug('Profile picture upload attempt');
                $file = $this->request->getFile('profile_picture');
                if (!$file) {
                    $response['message'] = 'No file received';
                    log_message('error', 'No file received');
                    return $this->response->setJSON($response);
                }
                if ($file->getError() !== 0) {
                    $response['message'] = 'File upload error: ' . $file->getError();
                    log_message('error', 'File upload error: ' . $file->getError());
                    return $this->response->setJSON($response);
                }

                $fileName = $file->getName();
                $fileSize = $file->getSize();
                
                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Allowed file types
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                // Validate file type
                if (!in_array($fileExt, $allowed)) {
                    throw new \Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
                }
                
                // Validate file size (5MB max)
                if ($fileSize > 5000000) {
                    throw new \Exception('File is too large. Maximum size is 5MB.');
                }

                // Create upload directory
                $uploadDir = FCPATH . 'Photos/profile_pictures/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        throw new \Exception('Failed to create upload directory');
                    }
                }

                // Generate unique filename
                $newFileName = 'admin_' . session()->get('user_id') . '_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;
                
                // Delete old profile picture if it exists and is not the default
                $db = \Config\Database::connect();
                $query = "SELECT profile_picture FROM users WHERE user_id = ? AND role = 'admin'";
                $result = $db->query($query, [session()->get('user_id')])->getRow();
                
                if ($result) {
                    $oldPicture = $result->profile_picture;
                    if ($oldPicture && $oldPicture !== 'Photos/profile.png') {
                        // Extract the relative path from the stored URL path
                        $oldRelativePath = str_replace('/UGCSystem/', FCPATH, $oldPicture);
                        if (file_exists($oldRelativePath)) {
                            unlink($oldRelativePath);
                        }
                    }
                }
                
                // Move uploaded file
                log_message('error', 'Trying to move file to: ' . $uploadPath);
                if ($file->move($uploadDir, $newFileName)) {
                    log_message('error', 'Move result: ' . ($file->hasMoved() ? 'success' : 'fail'));
                    // Update user's profile picture in database - store as URL path
                    $relativePath = 'Photos/profile_pictures/' . $newFileName;
                    $query = "UPDATE users SET profile_picture = ? WHERE id = ? AND role = 'admin'";
                    $result = $db->query($query, [$relativePath, session()->get('user_id')]);
                    
                    if ($result) {
                        session()->set('profile_picture', $relativePath);
                        $response['success'] = true;
                        $response['message'] = 'Profile picture updated successfully';
                        $response['picture_url'] = $relativePath;
                    } else {
                        // Remove uploaded file if database update fails
                        unlink($uploadPath);
                        throw new \Exception('Failed to update profile picture in database');
                    }
                } else {
                    log_message('error', 'Move failed. Error: ' . $file->getErrorString());
                    throw new \Exception('Failed to upload file: ' . $file->getErrorString());
                }

            } catch (\Exception $e) {
                log_message('error', 'Exception: ' . $e->getMessage());
                $response['message'] = $e->getMessage();
                return $this->response->setJSON($response);
            }
        }

        if (empty($response['message'])) {
            $response['message'] = 'Unknown error: code did not reach expected logic.';
            log_message('error', 'Unknown error: code did not reach expected logic.');
        }
        return $this->response->setJSON($response);
    }
} 