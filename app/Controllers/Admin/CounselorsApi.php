<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class CounselorsApi extends BaseController
{
    // Helper for debug logging
    private function debug_log($message, $data = null)
    {
        $log = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            // Safely stringify additional data without exposing sensitive details
            $stringified = '';
            if (is_string($data)) {
                $stringified = $data;
            } elseif (is_scalar($data)) {
                $stringified = (string) $data;
            } else {
                // Limit size to avoid huge logs
                $encoded = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
                $stringified = $encoded !== false ? substr($encoded, 0, 2000) : '[unserializable data]';
            }
            $log .= "\nData: " . $stringified;
        }
        file_put_contents(ROOTPATH . 'writable/debug.log', $log . "\n\n", FILE_APPEND);
    }

    // Helper for file upload
    private function handleFileUpload($counselorId)
    {
        $this->debug_log("Starting file upload for counselor ID: " . $counselorId);

        $file = $this->request->getFile('profile_picture');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileSize = $file->getSize();
            $fileType = $file->getMimeType();

            $this->debug_log("File details", [
                'name' => $file->getName(),
                'type' => $fileType,
                'size' => $fileSize
            ]);

            // Validate file size (5MB max)
            if ($fileSize > 5 * 1024 * 1024) {
                $this->debug_log("File too large: " . $fileSize . " bytes");
                return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB'];
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($fileType, $allowedTypes)) {
                $this->debug_log("Invalid file type: " . $fileType);
                return ['success' => false, 'message' => 'Invalid file type. Only JPG and PNG files are allowed'];
            }

            // Create directory if it doesn't exist
            $uploadDir = FCPATH . 'Photos/counselor_profiles/';
            if (!is_dir($uploadDir)) {
                $this->debug_log("Creating directory: " . $uploadDir);
                if (!mkdir($uploadDir, 0777, true)) {
                    $this->debug_log("Failed to create directory: " . $uploadDir);
                    return ['success' => false, 'message' => 'Failed to create upload directory'];
                }
            }

            // Generate unique filename
            $extension = $file->getExtension();
            $newFileName = 'counselor_' . $counselorId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $newFileName;

            $this->debug_log("Attempting to upload file to: " . $uploadPath);

            if ($file->move($uploadDir, $newFileName)) {
                $this->debug_log("File uploaded successfully to: " . $uploadPath);
                return [
                    'success' => true,
                    'path' => '/UGCSystem/public/Photos/counselor_profiles/' . $newFileName,
                    'full_path' => $uploadPath
                ];
            }

            $this->debug_log("Failed to move uploaded file.");
            return ['success' => false, 'message' => 'Failed to upload file'];
        }

        $this->debug_log("No file uploaded or file upload error");
        return ['success' => true, 'message' => 'No file uploaded'];
    }

    // GET: Fetch all counselors
    public function index()
    {
        try {
            $this->debug_log("GET request received");

            $db = \Config\Database::connect();

            // Ensure table matches actual database schema (no specialization or license_number columns)
            $db->query("CREATE TABLE IF NOT EXISTS counselors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                counselor_id VARCHAR(20) UNIQUE NOT NULL,
                name VARCHAR(100),
                degree VARCHAR(100),
                email VARCHAR(100),
                contact_number VARCHAR(20),
                address TEXT,
                profile_picture VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                civil_status VARCHAR(20),
                sex VARCHAR(10),
                birthdate DATE
            )");

            
            // Join with users table to include verification status and profile picture from users table
            $builder = $db->table('counselors c');
            $builder->select('c.counselor_id, c.name, c.degree, c.email, 
                             c.contact_number, c.address, c.time_scheduled, 
                             c.available_days, c.created_at, c.civil_status, c.sex, c.birthdate,
                             u.is_verified, u.username, u.email as user_email, u.profile_picture');
            $builder->join('users u', 'u.user_id = c.counselor_id', 'left');
            // Pending verification first, then by name (avoid identifier escaping on SQL function)
            $builder->orderBy('COALESCE(u.is_verified, 0) ASC', '', false);
            $builder->orderBy('c.name', 'ASC');
            $counselors = $builder->get()->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => $counselors
            ]);
        } catch (\Exception $e) {
            $this->debug_log("Error occurred: " . $e->getMessage(), $e->getTraceAsString());
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST: Approve counselor account (set users.is_verified = 1) and notify via email
    public function approve()
    {
        try {
            $db = \Config\Database::connect();
            $data = $this->request->getJSON(true) ?: $this->request->getPost();
            if (empty($data['counselor_id'])) {
                throw new \InvalidArgumentException('counselor_id is required');
            }

            $counselorId = $data['counselor_id'];

            // Fetch counselor email
            $counselor = $db->table('counselors')->where('counselor_id', $counselorId)->get()->getRowArray();
            if (!$counselor) {
                throw new \RuntimeException('Counselor not found');
            }

            // Update users.is_verified
            $db->table('users')->where('user_id', $counselorId)->update(['is_verified' => 1]);

            // Send approval email
            $email = \Config\Services::email();
            $email->setTo($counselor['email']);
            $email->setSubject('Counselign Account Approved');
            $email->setMessage(
                'Dear Counselor ' . ($counselor['name'] ?? 'Counselor') . ',<br><br>' .
                'Your counselor account has been approved. You can now log in and use the system.<br><br>' .
                'Best regards,<br>Counselign Team'
            );
            // Errors from email sending should not block approval; log instead
            if (!$email->send(false)) {
                $this->debug_log('Email send failed on approval', $email->printDebugger(['headers']));
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Counselor approved successfully']);
        } catch (\Throwable $e) {
            $this->debug_log('Approve error: ' . $e->getMessage());
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST: Reject counselor (email reason, then force-delete counselor and user records)
    public function reject()
    {
        try {
            $db = \Config\Database::connect();
            $data = $this->request->getJSON(true) ?: $this->request->getPost();
            if (empty($data['counselor_id'])) {
                throw new \InvalidArgumentException('counselor_id is required');
            }
            $reason = isset($data['reason']) ? trim((string)$data['reason']) : '';
            if ($reason === '') {
                throw new \InvalidArgumentException('Rejection reason is required');
            }

            $counselorId = $data['counselor_id'];
            $counselor = $db->table('counselors')->where('counselor_id', $counselorId)->get()->getRowArray();
            if (!$counselor) {
                throw new \RuntimeException('Counselor not found');
            }

            // Send rejection email first
            $email = \Config\Services::email();
            $email->setTo($counselor['email']);
            $email->setSubject('Counselign Account Rejected');
            $email->setMessage(
                'Dear' . ($counselor['name'] ?? 'Counselor') . ',<br><br>' .
                'We are sorry to inform you that your account registration has been rejected for the following reason:<br><br>' .
                nl2br(htmlspecialchars($reason)) . '<br><br>' .
                'If you believe this is a mistake or wish to re-apply, please contact the administrator.<br><br>' .
                'Regards,<br>Counselign Team'
            );
            if (!$email->send(false)) {
                $this->debug_log('Email send failed on rejection', $email->printDebugger(['headers']));
            }

            // Force delete records (delete counselor first, then user)
            $db->transStart();
            $db->query('SET FOREIGN_KEY_CHECKS=0');
            $db->table('counselors')->where('counselor_id', $counselorId)->delete();
            $db->table('users')->where('user_id', $counselorId)->delete();
            $db->query('SET FOREIGN_KEY_CHECKS=1');
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Failed to delete counselor account');
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Counselor account rejected and removed']);
        } catch (\Throwable $e) {
            $this->debug_log('Reject error: ' . $e->getMessage());
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST: Add or update counselor
    public function save()
    {
        try {
            $this->debug_log("POST request received", $this->request->getPost());

            $db = \Config\Database::connect();

            // Ensure table matches actual database schema (no specialization or license_number columns)
            $db->query("CREATE TABLE IF NOT EXISTS counselors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                counselor_id VARCHAR(20) UNIQUE NOT NULL,
                name VARCHAR(100),
                degree VARCHAR(100),
                email VARCHAR(100),
                contact_number VARCHAR(20),
                address TEXT,
                profile_picture VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                civil_status VARCHAR(20),
                sex VARCHAR(10),
                birthdate DATE,
                time_scheduled VARCHAR(50),
                available_days TEXT
            )");

            $post = $this->request->getPost();

            // Validate required fields (removed specialization and licenseNumber as they don't exist in DB)
            $requiredFields = ['counselorId', 'name', 'degree', 'email', 'contactNumber', 'address'];
            foreach ($requiredFields as $field) {
                if (empty($post[$field])) {
                    throw new \Exception("Missing required field: $field");
                }
            }

            // Handle profile photo removal
            $removeProfile = isset($post['remove_profile']) && $post['remove_profile'] === '1';
            $this->debug_log("Remove profile flag: " . ($removeProfile ? "Yes" : "No"));

            // Handle file upload if present
            $uploadResult = $this->handleFileUpload($post['counselorId']);
            if (!$uploadResult['success'] && $this->request->getFile('profile_picture')->isValid()) {
                throw new \Exception($uploadResult['message']);
            }

            // Check if counselor exists
            $builder = $db->table('counselors');
            $builder->where('counselor_id', $post['counselorId']);
            $exists = $builder->countAllResults() > 0;

            $params = [
                'counselor_id' => $post['counselorId'],
                'name' => $post['name'],
                'degree' => $post['degree'],
                'email' => $post['email'],
                'contact_number' => $post['contactNumber'],
                'address' => $post['address'],
                'time_scheduled' => $post['timeScheduled'] ?? null,
                'available_days' => $post['availableDays'] ?? null
            ];

            if ($exists) {
                $this->debug_log("Updating existing counselor: " . $post['counselorId']);
                if (!empty($uploadResult['path'])) {
                    $params['profile_picture'] = $uploadResult['path'];
                } elseif ($removeProfile) {
                    $params['profile_picture'] = 'Photos/profile_pictures/profile.png';
                }
                $builder->where('counselor_id', $post['counselorId']);
                $builder->update($params);
            } else {
                $this->debug_log("Adding new counselor: " . $post['counselorId']);
                if (!empty($uploadResult['path'])) {
                    $params['profile_picture'] = $uploadResult['path'];
                }
                $builder->insert($params);
            }

            // Fetch the updated data
            $counselor = $db->table('counselors')->where('counselor_id', $post['counselorId'])->get()->getRowArray();

            if (!$counselor) {
                throw new \Exception("Failed to retrieve counselor data after save");
            }

            $this->debug_log("Counselor operation successful", $counselor);

            return $this->response->setJSON([
                'success' => true,
                'message' => $exists ? 'Counselor updated successfully' : 'Counselor added successfully',
                'data' => $counselor
            ]);
        } catch (\Exception $e) {
            $this->debug_log("Error occurred: " . $e->getMessage(), $e->getTraceAsString());
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // DELETE: Delete counselor
    public function delete()
    {
        try {
            $this->debug_log("DELETE request received");

            $db = \Config\Database::connect();
            $data = $this->request->getJSON(true);

            $this->debug_log("Delete data", $data);

            if (empty($data['counselor_id'])) {
                throw new \Exception('Counselor ID is required');
            }

            $counselorId = $data['counselor_id'];

            // Check if counselor exists
            $counselor = $db->table('counselors')->where('counselor_id', $counselorId)->get()->getRowArray();

            if (!$counselor) {
                throw new \Exception('Counselor not found');
            }

            // Delete profile picture if it's not the default
            if (!empty($counselor['profile_picture']) && $counselor['profile_picture'] !== 'Photos/profile_pictures/profile.png') {
                $picturePath = FCPATH . ltrim(str_replace('/Counselign/public', '', $counselor['profile_picture']), '/');
                if (file_exists($picturePath)) {
                    $this->debug_log("Deleting profile picture: " . $picturePath);
                    @unlink($picturePath);
                }
            }

            $db->table('counselors')->where('counselor_id', $counselorId)->delete();

            return $this->response->setJSON(['success' => true, 'message' => 'Counselor deleted successfully']);
        } catch (\Exception $e) {
            $this->debug_log("Error occurred: " . $e->getMessage(), $e->getTraceAsString());
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
