<?php

namespace App\Controllers\Student;

use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;
use App\Models\StudentPDSModel;
use App\Models\StudentAcademicInfoModel;
use App\Models\StudentPersonalInfoModel;
use App\Models\StudentAddressInfoModel;
use App\Models\StudentFamilyInfoModel;
use App\Models\StudentSpecialCircumstancesModel;
use App\Models\StudentServicesNeededModel;
use App\Models\StudentServicesAvailedModel;
use App\Models\StudentResidenceInfoModel;
use App\Models\StudentOtherInfoModel;
use App\Models\StudentGCSActivitiesModel;
use App\Models\StudentAwardsModel;

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
    protected $otherInfoModel;
    protected $gcsActivitiesModel;
    protected $awardsModel;
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
        $this->otherInfoModel = new StudentOtherInfoModel();
        $this->gcsActivitiesModel = new StudentGCSActivitiesModel();
        $this->awardsModel = new StudentAwardsModel();
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
        $userId = (string) $userId;
        SecureLogHelper::debug('Loading PDS data');
        
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
                $userData = $this->userModel->where('user_id', $userId)->first();
                if ($userData) {
                    $pdsData['user_email'] = $userData['email'];
                } else {
                    $pdsData['user_email'] = '';
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
        $userId = (string) $userId;
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
                    // Update last_activity for PDS save
                    $activityHelper = new UserActivityHelper();
                    $activityHelper->updateStudentActivity($userId, 'save_pds');
                    
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
     * Prepare PDS data from request - UPDATED WITH NEW FIELDS
     */
    private function preparePDSData($request, $userId)
    {
        $pdsData = [];

        // ========================================
        // ACADEMIC INFORMATION (UPDATED)
        // ========================================
        $pdsData['academic'] = [
            'student_id' => $userId,
            'course' => $request->getPost('course') ?: 'N/A',
            'year_level' => $request->getPost('yearLevel') ?: 'N/A',
            'academic_status' => $request->getPost('academicStatus') ?: 'N/A',
            // NEW FIELDS
            'school_last_attended' => $request->getPost('schoolLastAttended') ?: 'N/A',
            'location_of_school' => $request->getPost('locationOfSchool') ?: 'N/A',
            'previous_course_grade' => $request->getPost('previousCourseGrade') ?: 'N/A'
        ];

        // ========================================
        // PERSONAL INFORMATION (UPDATED)
        // ========================================
        $civilStatus = $request->getPost('civilStatus') ?: 'Single';
        $spouse = ($civilStatus === 'Married') ? ($request->getPost('spouse') ?: '') : 'N/A';
        
        $contactNumber = $request->getPost('contactNumber');
        $validContactNumber = '';
        if (!empty($contactNumber) && $contactNumber !== 'N/A') {
            if (preg_match('/^09[0-9]{9}$/', $contactNumber)) {
                $validContactNumber = $contactNumber;
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
            'contact_number' => $validContactNumber,
            'fb_account_name' => $request->getPost('fbAccountName') ?: 'N/A',
            // NEW FIELDS
            'place_of_birth' => $request->getPost('placeOfBirth') ?: 'N/A',
            'religion' => $request->getPost('religion') ?: 'N/A'
        ];
        
        $pdsData['personal'] = $personalData;

        // ========================================
        // ADDRESS INFORMATION (UNCHANGED)
        // ========================================
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

        // ========================================
        // FAMILY INFORMATION (EXTENSIVELY UPDATED)
        // ========================================
        // Helper function to clean age values
        $cleanAge = function($value) {
            if (empty($value) || $value === 'N/A' || $value === '') {
                return null;
            }
            $age = (int) $value;
            return ($age >= 18 && $age <= 120) ? $age : null;
        };
        
        // Helper function to clean contact numbers
        $cleanContactNumber = function($value) {
            if (empty($value) || $value === 'N/A' || $value === '') {
                return null;
            }
            // Validate Philippine phone number format
            if (preg_match('/^09[0-9]{9}$/', $value)) {
                return $value;
            }
            return null; // Invalid format, return null instead of N/A
        };
        
        $pdsData['family'] = [
            'student_id' => $userId,
            'father_name' => $request->getPost('fatherName') ?: 'N/A',
            'father_occupation' => $request->getPost('fatherOccupation') ?: 'N/A',
            'mother_name' => $request->getPost('motherName') ?: 'N/A',
            'mother_occupation' => $request->getPost('motherOccupation') ?: 'N/A',
            'spouse' => $spouse,
            'guardian_contact_number' => $cleanContactNumber($request->getPost('guardianContactNumber')),
            // NEW FIELDS
            'father_educational_attainment' => $request->getPost('fatherEducationalAttainment') ?: 'N/A',
            'father_age' => $cleanAge($request->getPost('fatherAge')),
            'father_contact_number' => $cleanContactNumber($request->getPost('fatherContactNumber')),
            'mother_educational_attainment' => $request->getPost('motherEducationalAttainment') ?: 'N/A',
            'mother_age' => $cleanAge($request->getPost('motherAge')),
            'mother_contact_number' => $cleanContactNumber($request->getPost('motherContactNumber')),
            'parents_permanent_address' => $request->getPost('parentsPermanentAddress') ?: 'N/A',
            'parents_contact_number' => $cleanContactNumber($request->getPost('parentsContactNumber')),
            'spouse_occupation' => $request->getPost('spouseOccupation') ?: 'N/A',
            'spouse_educational_attainment' => $request->getPost('spouseEducationalAttainment') ?: 'N/A',
            'guardian_name' => $request->getPost('guardianName') ?: 'N/A',
            'guardian_age' => $cleanAge($request->getPost('guardianAge')),
            'guardian_occupation' => $request->getPost('guardianOccupation') ?: 'N/A'
        ];

        // ========================================
        // SPECIAL CIRCUMSTANCES (UNCHANGED)
        // ========================================
        $pwd = $request->getPost('pwd') ?: 'No';
        $pwdSpecify = ($pwd === 'Yes' || $pwd === 'Other') ? ($request->getPost('pwdSpecify') ?: '') : 'N/A';
        
        $pwdProofFile = $this->handlePWDProofUpload($request, $userId);
        
        $pdsData['circumstances'] = [
            'student_id' => $userId,
            'is_solo_parent' => $request->getPost('soloParent') ?: 'No',
            'is_indigenous' => $request->getPost('indigenous') ?: 'No',
            'is_breastfeeding' => $request->getPost('breastFeeding') ?: 'N/A',
            'is_pwd' => $pwd,
            'pwd_disability_type' => $pwdSpecify,
            'pwd_proof_file' => $pwdProofFile
        ];

        // ========================================
        // SERVICES NEEDED (UNCHANGED)
        // ========================================
        $servicesNeededJson = $request->getPost('services_needed');
        $pdsData['services_needed'] = $servicesNeededJson ? json_decode($servicesNeededJson, true) : [];
        
        // ========================================
        // SERVICES AVAILED (UNCHANGED)
        // ========================================
        $servicesAvailedJson = $request->getPost('services_availed');
        $pdsData['services_availed'] = $servicesAvailedJson ? json_decode($servicesAvailedJson, true) : [];

        // ========================================
        // RESIDENCE INFORMATION (UNCHANGED)
        // ========================================
        $residence = $request->getPost('residence') ?: 'at home';
        $residenceOther = ($residence === 'other') ? ($request->getPost('resOtherText') ?: '') : 'N/A';
        
        $pdsData['residence'] = [
            'student_id' => $userId,
            'residence_type' => $residence,
            'residence_other_specify' => $residenceOther,
            'has_consent' => $request->getPost('consentAgree') === '1' ? 1 : 0
        ];

        // ========================================
        // OTHER INFORMATION (NEW SECTION)
        // ========================================
        $familyDescriptionJson = $request->getPost('family_description');
        $familyDescription = $familyDescriptionJson ? json_decode($familyDescriptionJson, true) : [];
        
        $pdsData['other_info'] = [
            'student_id' => $userId,
            'course_choice_reason' => $request->getPost('courseChoiceReason') ?: null,
            'family_description' => $familyDescription,
            'family_description_other' => $request->getPost('familyDescriptionOther') ?: null,
            'living_condition' => $request->getPost('livingCondition') ?: null,
            'physical_health_condition' => $request->getPost('physicalHealthCondition') ?: 'No',
            'physical_health_condition_specify' => $request->getPost('physicalHealthConditionSpecify') ?: null,
            'psych_treatment' => $request->getPost('psychTreatment') ?: 'No'
        ];

        // ========================================
        // GCS ACTIVITIES (NEW SECTION)
        // ========================================
        $gcsActivitiesJson = $request->getPost('gcs_activities');
        $pdsData['gcs_activities'] = $gcsActivitiesJson ? json_decode($gcsActivitiesJson, true) : [];

        // ========================================
        // AWARDS (NEW SECTION)
        // ========================================
        $awardsJson = $request->getPost('awards');
        $pdsData['awards'] = $awardsJson ? json_decode($awardsJson, true) : [];

        return $pdsData;
    }

    /**
     * Validate PDS data - UPDATED WITH NEW VALIDATION RULES
     */
    private function validatePDSData($request)
    {
        $errors = [];

        // Required fields validation
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

        // Validate contact number format
        $contactNumber = $request->getPost('contactNumber');
        if (!empty($contactNumber) && $contactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $contactNumber)) {
            $errors[] = 'Contact number must be in format 09XXXXXXXXX';
        }

        // NEW: Validate father contact number
        $fatherContactNumber = $request->getPost('fatherContactNumber');
        if (!empty($fatherContactNumber) && $fatherContactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $fatherContactNumber)) {
            $errors[] = 'Father contact number must be in format 09XXXXXXXXX';
        }

        // NEW: Validate mother contact number
        $motherContactNumber = $request->getPost('motherContactNumber');
        if (!empty($motherContactNumber) && $motherContactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $motherContactNumber)) {
            $errors[] = 'Mother contact number must be in format 09XXXXXXXXX';
        }

        // NEW: Validate parents contact number
        $parentsContactNumber = $request->getPost('parentsContactNumber');
        if (!empty($parentsContactNumber) && $parentsContactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $parentsContactNumber)) {
            $errors[] = 'Parents contact number must be in format 09XXXXXXXXX';
        }

        // Validate guardian contact number
        $guardianContactNumber = $request->getPost('guardianContactNumber');
        if (!empty($guardianContactNumber) && $guardianContactNumber !== 'N/A' && !preg_match('/^09[0-9]{9}$/', $guardianContactNumber)) {
            $errors[] = 'Guardian contact number must be in format 09XXXXXXXXX';
        }

        // NEW: Validate age fields
        $ageFields = [
            'fatherAge' => 'Father age',
            'motherAge' => 'Mother age',
            'guardianAge' => 'Guardian age'
        ];

        foreach ($ageFields as $field => $label) {
            $age = $request->getPost($field);
            if (!empty($age) && ($age < 18 || $age > 120)) {
                $errors[] = $label . ' must be between 18 and 120';
            }
        }

        // Validate PWD fields
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
     * Handle PWD proof file upload (UNCHANGED)
     */
    private function handlePWDProofUpload($request, $userId)
    {
        $file = $request->getFile('pwdProof');
        
        if (!$file || !$file->isValid()) {
            $existingFile = $this->getExistingPWDProofFile($userId);
            if ($existingFile && $existingFile !== 'N/A') {
                return $existingFile;
            }
            return 'N/A';
        }

        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'doc', 'docx', 'xls', 'xlsx'];
        $extension = strtolower($file->getExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            log_message('error', 'Invalid PWD proof file type: ' . $extension);
            return 'N/A';
        }

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
     * Get existing PWD proof file path (UNCHANGED)
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