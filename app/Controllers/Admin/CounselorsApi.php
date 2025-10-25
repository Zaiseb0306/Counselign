<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class CounselorsApi extends BaseController
{
    // Helper for debug logging
    private function debug_log($message, $data = null)
    {
        $log = date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            $log .= "\nData: " . print_r($data, true);
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

            // Ensure table exists
            $db->query("CREATE TABLE IF NOT EXISTS counselors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                counselor_id VARCHAR(20) UNIQUE NOT NULL,
                name VARCHAR(100),
                specialization VARCHAR(100),
                degree VARCHAR(100),
                email VARCHAR(100),
                contact_number VARCHAR(20),
                license_number VARCHAR(50),
                address TEXT,
                time_scheduled VARCHAR(50),
                available_days TEXT,
                profile_picture VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Add new columns if they don't exist (MySQL 8+ supports IF NOT EXISTS)
            $db->query("ALTER TABLE counselors 
                ADD COLUMN IF NOT EXISTS time_scheduled VARCHAR(50),
                ADD COLUMN IF NOT EXISTS available_days TEXT");

            $builder = $db->table('counselors');
            $builder->orderBy('name');
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

    // POST: Add or update counselor
    public function save()
    {
        try {
            $this->debug_log("POST request received", $this->request->getPost());

            $db = \Config\Database::connect();

            // Ensure table exists
            $db->query("CREATE TABLE IF NOT EXISTS counselors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                counselor_id VARCHAR(20) UNIQUE NOT NULL,
                name VARCHAR(100),
                specialization VARCHAR(100),
                degree VARCHAR(100),
                email VARCHAR(100),
                contact_number VARCHAR(20),
                license_number VARCHAR(50),
                address TEXT,
                time_scheduled VARCHAR(50),
                available_days TEXT,
                profile_picture VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $post = $this->request->getPost();

            // Validate required fields
            $requiredFields = ['counselorId', 'name', 'specialization', 'degree', 'email', 'contactNumber', 'licenseNumber', 'address'];
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
                'specialization' => $post['specialization'],
                'degree' => $post['degree'],
                'email' => $post['email'],
                'contact_number' => $post['contactNumber'],
                'license_number' => $post['licenseNumber'],
                'address' => $post['address'],
                'time_scheduled' => $post['timeScheduled'] ?? null,
                'available_days' => $post['availableDays'] ?? null
            ];

            if ($exists) {
                $this->debug_log("Updating existing counselor: " . $post['counselorId']);
                if (!empty($uploadResult['path'])) {
                    $params['profile_picture'] = $uploadResult['path'];
                } elseif ($removeProfile) {
                    $params['profile_picture'] = '/UGCSystem/public/Photos/profile.png';
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
            if (!empty($counselor['profile_picture']) && $counselor['profile_picture'] !== '/UGCSystem/public/Photos/profile.png') {
                $picturePath = FCPATH . ltrim(str_replace('/UGCSystem/public', '', $counselor['profile_picture']), '/');
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
