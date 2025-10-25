<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\StudentPDSModel;
use App\Models\StudentAcademicInfoModel;
use App\Models\StudentPersonalInfoModel;
use App\Models\StudentAddressInfoModel;
use App\Models\StudentFamilyInfoModel;
use App\Models\StudentSpecialCircumstancesModel;
use App\Models\StudentServicesNeededModel;
use App\Models\StudentServicesAvailedModel;
use App\Models\StudentResidenceInfoModel;

class PDS extends BaseController
{
    protected $pdsModel;
    protected $academicModel;
    protected $personalModel;
    protected $addressModel;
    protected $familyModel;
    protected $circumstancesModel;
    protected $servicesNeededModel;
    protected $servicesAvailedModel;
    protected $residenceModel;
    protected $userModel;

    public function __construct()
    {
        $this->pdsModel = new StudentPDSModel();
        $this->academicModel = new StudentAcademicInfoModel();
        $this->personalModel = new StudentPersonalInfoModel();
        $this->addressModel = new StudentAddressInfoModel();
        $this->familyModel = new StudentFamilyInfoModel();
        $this->circumstancesModel = new StudentSpecialCircumstancesModel();
        $this->servicesNeededModel = new StudentServicesNeededModel();
        $this->servicesAvailedModel = new StudentServicesAvailedModel();
        $this->residenceModel = new StudentResidenceInfoModel();
        $this->userModel = new \App\Models\UserModel();
    }

    /**
     * Load complete PDS data for the logged-in student
     */
    public function loadPDS()
    {
        $session = session();

        if (!$session->get('logged_in') || $session->get('role') !== 'student') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized access'])->setStatusCode(401);
        }

        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        $userId = (string) $userId; // Force string conversion for varchar(10) column
        log_message('debug', 'PDS Load - Session data: user_id_display=' . $session->get('user_id_display') . ', user_id=' . $session->get('user_id') . ', username=' . $session->get('username'));
        log_message('debug', 'PDS Load - Using userId: ' . $userId . ' (Type: ' . gettype($userId) . ')');
        
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
        }

        try {
            $pdsData = $this->pdsModel->getCompletePDS($userId);
            
            // Get user's email from users table
            $userData = $this->userModel->find($userId);
            if ($userData) {
                $pdsData['user_email'] = $userData['email'];
                log_message('debug', 'PDS Load - User email: ' . $userData['email']);
            } else {
                // Try alternative lookup methods
                $userData = $this->userModel->where('user_id', $userId)->first();
                if ($userData) {
                    $pdsData['user_email'] = $userData['email'];
                    log_message('debug', 'PDS Load - User email (by user_id): ' . $userData['email']);
                } else {
                    // Try by username or other fields
                    $session = session();
                    $username = $session->get('username');
                    if ($username) {
                        $userData = $this->userModel->where('username', $username)->first();
                        if ($userData) {
                            $pdsData['user_email'] = $userData['email'];
                            log_message('debug', 'PDS Load - User email (by username): ' . $userData['email']);
                        } else {
                            log_message('error', 'PDS Load - User not found for ID: ' . $userId . ', Username: ' . $username);
                            $pdsData['user_email'] = ''; // Set empty email to prevent errors
                        }
                    } else {
                        log_message('error', 'PDS Load - User not found for ID: ' . $userId . ', No username in session');
                        $pdsData['user_email'] = ''; // Set empty email to prevent errors
                    }
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $pdsData
            ]);
        } catch (\Exception $e) {
            log_message('error', 'PDS Load Error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to load PDS data'])->setStatusCode(500);
        }
    }

    /**
     * Save complete PDS data for the logged-in student
     */
    public function savePDS()
    {
        $session = session();

        if (!$session->get('logged_in') || $session->get('role') !== 'student') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized access'])->setStatusCode(401);
        }

        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        $userId = (string) $userId; // Force string conversion for varchar(10) column
        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid session data'])->setStatusCode(400);
        }

        try {
            $request = $this->request;
            
            // Validate required fields
            $validationResult = $this->validatePDSData($request);
            if (!$validationResult['valid']) {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => $validationResult['message']
                ])->setStatusCode(400);
            }
            
            // Prepare PDS data structure
            $pdsData = $this->preparePDSData($request, $userId);
            
            // Save complete PDS
            try {
                $result = $this->pdsModel->saveCompletePDS($userId, $pdsData);
                
                if ($result) {
                    log_message('debug', 'PDS Save - Successfully saved data for user: ' . $userId);
                    return $this->response->setJSON(['success' => true, 'message' => 'PDS data saved successfully']);
                } else {
                    log_message('error', 'PDS Save - Failed to save data for user: ' . $userId);
                    return $this->response->setJSON(['success' => false, 'message' => 'Failed to save PDS data']);
                }
            } catch (\Exception $e) {
                log_message('error', 'PDS Save - Exception during save: ' . $e->getMessage());
                log_message('error', 'PDS Save - Stack trace: ' . $e->getTraceAsString());
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to save PDS data: ' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            log_message('error', 'PDS Save Error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save PDS data'])->setStatusCode(500);
        }
    }

    /**
     * Prepare PDS data from request
     */
    private function preparePDSData($request, $userId)
    {
        $pdsData = [];

        // Academic Information
        $pdsData['academic'] = [
            'student_id' => $userId,
            'course' => $request->getPost('course') ?: 'N/A',
            'year_level' => $request->getPost('yearLevel') ?: 'N/A',
            'academic_status' => $request->getPost('academicStatus') ?: 'N/A'
        ];

        // Personal Information
        $civilStatus = $request->getPost('civilStatus') ?: 'Single';
        $spouse = ($civilStatus === 'Married') ? ($request->getPost('spouse') ?: '') : 'N/A';
        
        // Handle contact number validation properly
        $contactNumber = $request->getPost('contactNumber');
        $validContactNumber = '';
        if (!empty($contactNumber) && $contactNumber !== 'N/A') {
            // Validate format
            if (preg_match('/^09[0-9]{9}$/', $contactNumber)) {
                $validContactNumber = $contactNumber;
            } else {
                $validContactNumber = ''; // Invalid format, store empty
            }
        }
        
        $personalData = [
            'student_id' => $userId,
            'last_name' => $request->getPost('lastName') ?: 'N/A',
            'first_name' => $request->getPost('firstName') ?: 'N/A',
            'middle_name' => $request->getPost('middleName') ?: 'N/A',
            'date_of_birth' => $request->getPost('dateOfBirth') ?: null,
            'age' => $request->getPost('age') ?: null,
            'sex' => $request->getPost('sex') ?: 'N/A',
            'civil_status' => $civilStatus,
            'contact_number' => $validContactNumber, // Use validated contact number
            'fb_account_name' => $request->getPost('fbAccountName') ?: 'N/A'
        ];
        
        // Debug: Log personal data
        log_message('debug', 'PDS Personal Data: ' . json_encode($personalData));
        
        // Validate that required personal fields are not empty
        if (empty($personalData['first_name']) || $personalData['first_name'] === 'N/A') {
            log_message('error', 'First name is empty or N/A');
        }
        if (empty($personalData['last_name']) || $personalData['last_name'] === 'N/A') {
            log_message('error', 'Last name is empty or N/A');
        }
        
        $pdsData['personal'] = $personalData;

        // Address Information
        $pdsData['address'] = [
            'student_id' => $userId,
            'permanent_zone' => $request->getPost('permanentZone') ?: 'N/A',
            'permanent_barangay' => $request->getPost('permanentBarangay') ?: 'N/A',
            'permanent_city' => $request->getPost('permanentCity') ?: 'N/A',
            'permanent_province' => $request->getPost('permanentProvince') ?: 'N/A',
            'present_zone' => $request->getPost('presentZone') ?: 'N/A',
            'present_barangay' => $request->getPost('presentBarangay') ?: 'N/A',
            'present_city' => $request->getPost('presentCity') ?: 'N/A',
            'present_province' => $request->getPost('presentProvince') ?: 'N/A'
        ];

        // Family Information
        $pdsData['family'] = [
            'student_id' => $userId,
            'father_name' => $request->getPost('fatherName') ?: 'N/A',
            'father_occupation' => $request->getPost('fatherOccupation') ?: 'N/A',
            'mother_name' => $request->getPost('motherName') ?: 'N/A',
            'mother_occupation' => $request->getPost('motherOccupation') ?: 'N/A',
            'spouse' => $spouse,
            'guardian_contact_number' => $request->getPost('guardianContactNumber') ?: 'N/A'
        ];

        // Special Circumstances
        $pwd = $request->getPost('pwd') ?: 'No';
        $pwdSpecify = ($pwd === 'Yes' || $pwd === 'Other') ? ($request->getPost('pwdSpecify') ?: '') : 'N/A';
        
        $pwdProofFile = $this->handlePWDProofUpload($request, $userId);
        log_message('debug', 'PWD Proof File Result: ' . $pwdProofFile);
        
        $pdsData['circumstances'] = [
            'student_id' => $userId,
            'is_solo_parent' => $request->getPost('soloParent') ?: 'No',
            'is_indigenous' => $request->getPost('indigenous') ?: 'No',
            'is_breastfeeding' => $request->getPost('breastFeeding') ?: 'N/A',
            'is_pwd' => $pwd,
            'pwd_disability_type' => $pwdSpecify,
            'pwd_proof_file' => $pwdProofFile
        ];

        // Services Needed
        $servicesNeededJson = $request->getPost('services_needed');
        $pdsData['services_needed'] = $servicesNeededJson ? json_decode($servicesNeededJson, true) : [];
        log_message('debug', 'Services Needed Data: ' . json_encode($pdsData['services_needed']));
        
        // Services Availed
        $servicesAvailedJson = $request->getPost('services_availed');
        $pdsData['services_availed'] = $servicesAvailedJson ? json_decode($servicesAvailedJson, true) : [];
        log_message('debug', 'Services Availed Data: ' . json_encode($pdsData['services_availed']));

        // Residence Information
        $residence = $request->getPost('residence') ?: 'at home';
        $residenceOther = ($residence === 'other') ? ($request->getPost('resOtherText') ?: '') : 'N/A';
        
        $pdsData['residence'] = [
            'student_id' => $userId,
            'residence_type' => $residence,
            'residence_other_specify' => $residenceOther,
            'has_consent' => $request->getPost('consentAgree') === '1' ? 1 : 0
        ];

        return $pdsData;
    }

    /**
     * Validate PDS data
     */
    private function validatePDSData($request)
    {
        $errors = [];

        // Validate required fields
        $requiredFields = [
            'course' => 'Course',
            'yearLevel' => 'Year Level',
            'academicStatus' => 'Academic Status',
            'lastName' => 'Last Name',
            'firstName' => 'First Name',
            'sex' => 'Sex',
            'civilStatus' => 'Civil Status'
        ];

        foreach ($requiredFields as $field => $label) {
            $value = $request->getPost($field);
            if (empty($value) || $value === 'N/A') {
                $errors[] = $label . ' is required';
            }
        }
        
        // Debug: Log the received data
        log_message('debug', 'PDS Save - Received data: ' . json_encode($request->getPost()));


        // Validate contact number format if provided
        $contactNumber = $request->getPost('contactNumber');
        if (!empty($contactNumber) && $contactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $contactNumber)) {
            $errors[] = 'Contact number must be in format 09XXXXXXXXX';
        }

        // Validate guardian contact number format if provided
        $guardianContactNumber = $request->getPost('guardianContactNumber');
        if (!empty($guardianContactNumber) && $guardianContactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $guardianContactNumber)) {
            $errors[] = 'Guardian contact number must be in format 09XXXXXXXXX';
        }

        // Validate PWD fields if PWD is Yes or Other
        $pwd = $request->getPost('pwd');
        if ($pwd === 'Yes' || $pwd === 'Other') {
            $pwdSpecify = $request->getPost('pwdSpecify');
            if (empty($pwdSpecify) || $pwdSpecify === 'N/A') {
                $errors[] = 'PWD disability type must be specified when PWD is Yes or Other';
            }
        }

        // Validate spouse field if married
        $civilStatus = $request->getPost('civilStatus');
        if ($civilStatus === 'Married') {
            $spouse = $request->getPost('spouse');
            if (empty($spouse) || $spouse === 'N/A') {
                $errors[] = 'Spouse name is required when civil status is Married';
            }
        }

        // Validate consent
        $consentAgree = $request->getPost('consentAgree');
        if ($consentAgree !== '1') {
            $errors[] = 'You must agree to participate in this survey';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => implode('. ', $errors)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Handle PWD proof file upload
     * Preserves existing file if no new file is uploaded
     */
    private function handlePWDProofUpload($request, $userId)
    {
        $file = $request->getFile('pwdProof');
        log_message('debug', 'PWD Proof Upload - File received: ' . ($file ? 'Yes' : 'No'));
        log_message('debug', 'PWD Proof Upload - All files: ' . json_encode($request->getFiles()));
        
        // If no new file is uploaded, preserve existing file
        if (!$file || !$file->isValid()) {
            log_message('debug', 'PWD Proof Upload - No new file uploaded, preserving existing file');
            
            // Get existing file path from database
            $existingFile = $this->getExistingPWDProofFile($userId);
            if ($existingFile && $existingFile !== 'N/A') {
                log_message('debug', 'PWD Proof Upload - Preserving existing file: ' . $existingFile);
                return $existingFile;
            }
            
            log_message('debug', 'PWD Proof Upload - No existing file found, returning N/A');
            return 'N/A';
        }
        
        log_message('debug', 'PWD Proof Upload - File name: ' . $file->getName() . ', Size: ' . $file->getSize());

        // Validate file type
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'doc', 'docx', 'xls', 'xlsx'];
        $extension = strtolower($file->getExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            log_message('error', 'Invalid PWD proof file type: ' . $extension);
            return 'N/A';
        }

        // Validate file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            log_message('error', 'PWD proof file too large: ' . $file->getSize());
            return 'N/A';
        }

        try {
            $uploadDir = FCPATH . 'Photos/pwd_proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'pwd_proof_' . $userId . '_' . time() . '.' . $extension;
            $relativePath = 'Photos/pwd_proofs/' . $newFileName;

            if ($file->move($uploadDir, $newFileName)) {
                log_message('debug', 'PWD Proof Upload - New file uploaded successfully: ' . $relativePath);
                return $relativePath;
            } else {
                log_message('error', 'Failed to move PWD proof file');
                return 'N/A';
            }
        } catch (\Exception $e) {
            log_message('error', 'PWD proof upload error: ' . $e->getMessage());
            return 'N/A';
        }
    }

    /**
     * Get existing PWD proof file path from database
     */
    private function getExistingPWDProofFile($userId)
    {
        try {
            $existingRecord = $this->circumstancesModel->where('student_id', $userId)->first();
            if ($existingRecord && !empty($existingRecord['pwd_proof_file']) && $existingRecord['pwd_proof_file'] !== 'N/A') {
                return $existingRecord['pwd_proof_file'];
            }
            return null;
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving existing PWD proof file: ' . $e->getMessage());
            return null;
        }
    }
}