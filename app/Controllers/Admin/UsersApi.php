<?php
namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\StudentPDSModel;
use App\Models\StudentAcademicInfoModel;
use App\Models\StudentPersonalInfoModel;
use App\Models\StudentAddressInfoModel;
use App\Models\StudentFamilyInfoModel;
use App\Models\StudentResidenceInfoModel;
use App\Models\StudentSpecialCircumstancesModel;
use App\Models\StudentServicesNeededModel;
use App\Models\StudentServicesAvailedModel;
use App\Models\StudentOtherInfoModel;
use App\Models\StudentGCSActivitiesModel;
use App\Models\StudentAwardsModel;
use CodeIgniter\API\ResponseTrait;

class UsersApi extends BaseController
{
    use ResponseTrait;

    public function getAllUsers()
    {
        try {
            $db = \Config\Database::connect();
            
            // Add required columns if they don't exist
            $columns = $db->query("SHOW COLUMNS FROM users")->getResultArray();
            $columnNames = array_column($columns, 'Field');
            
            if (!in_array('last_active_at', $columnNames)) {
                $db->query("ALTER TABLE users ADD COLUMN last_active_at TIMESTAMP NULL DEFAULT NULL");
            }
            if (!in_array('last_inactive_at', $columnNames)) {
                $db->query("ALTER TABLE users ADD COLUMN last_inactive_at TIMESTAMP NULL DEFAULT NULL");
            }

            // Update current user's active status
            if (session()->get('user_id')) {
                $db->query("
                    UPDATE users 
                    SET last_active_at = CURRENT_TIMESTAMP,
                        last_inactive_at = NULL
                    WHERE user_id = ?
                ", [session()->get('user_id')]);
            }

            // Get all users with their online status
            $query = $db->query("
                SELECT
                    user_id,
                    username,
                    email,
                    created_at,
                    last_login,
                    last_active_at,
                    last_activity,
                    last_inactive_at,
                    logout_time,
                    CASE
                        WHEN (last_active_at >= NOW() - INTERVAL 5 MINUTE OR last_login >= NOW() - INTERVAL 5 MINUTE)
                             AND (last_inactive_at IS NULL OR last_inactive_at < COALESCE(last_active_at, last_login))
                        THEN 1
                        ELSE 0
                    END as is_online
                FROM users
                WHERE role = 'student'
            ");
            
            $users = $query->getResultArray();

            // Count active users and format their status
            $activeCount = 0;
            foreach ($users as &$user) {
                // Format created_at
                if ($user['created_at']) {
                    $created_timestamp = strtotime($user['created_at']);
                    if ($created_timestamp !== false) {
                        $user['created_at'] = date('Y-m-d\TH:i:s', $created_timestamp);
                    }
                }
                
                // Format activity status
                if ($user['is_online']) {
                    $user['activity_status'] = 'Currently Active';
                    $activeCount++;
                } else if ($user['last_inactive_at']) {
                    $last_inactive_timestamp = strtotime($user['last_inactive_at']);
                    if ($last_inactive_timestamp !== false) {
                        $seconds_ago = time() - $last_inactive_timestamp;
                        if ($seconds_ago < 3600) { // Less than 1 hour
                            $user['activity_status'] = 'Inactive ' . $this->formatTimeAgo($seconds_ago);
                        } else if ($seconds_ago < 86400) { // Less than 24 hours
                            $hours = floor($seconds_ago / 3600);
                            $user['activity_status'] = 'Inactive ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                        } else {
                            $user['activity_status'] = 'Last seen on ' . date('M j, Y \a\t g:i A', $last_inactive_timestamp);
                        }
                    }
                } else {
                    // Check last_login or last_active_at for users who haven't explicitly logged out
                    $last_activity = max(
                        strtotime($user['last_login'] ?? '0'),
                        strtotime($user['last_active_at'] ?? '0')
                    );
                    
                    if ($last_activity > 0) {
                        $seconds_ago = time() - $last_activity;
                        if ($seconds_ago < 3600) {
                            $user['activity_status'] = 'Active ' . $this->formatTimeAgo($seconds_ago);
                        } else if ($seconds_ago < 86400) {
                            $hours = floor($seconds_ago / 3600);
                            $user['activity_status'] = 'Active ' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                        } else {
                            $user['activity_status'] = 'Last seen on ' . date('M j, Y \a\t g:i A', $last_activity);
                        }
                    } else {
                        $user['activity_status'] = 'Never logged in';
                    }
                }

                // Retrieve comprehensive student data from all related models
                $user['student_data'] = $this->getComprehensiveStudentData($user['user_id']);
            }

            return $this->respond([
                'success' => true,
                'users' => $users,
                'activeCount' => $activeCount
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in UsersApi::getAllUsers: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred while fetching users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve comprehensive student data from all related models
     * 
     * @param string $userId The student's user ID
     * @return array Comprehensive student data including all PDS sections
     */
    private function getComprehensiveStudentData(string $userId): array
    {
        try {
            // Initialize all student models
            $academicModel = new StudentAcademicInfoModel();
            $personalModel = new StudentPersonalInfoModel();
            $addressModel = new StudentAddressInfoModel();
            $familyModel = new StudentFamilyInfoModel();
            $residenceModel = new StudentResidenceInfoModel();
            $circumstancesModel = new StudentSpecialCircumstancesModel();
            $servicesNeededModel = new StudentServicesNeededModel();
            $servicesAvailedModel = new StudentServicesAvailedModel();
            $otherInfoModel = new StudentOtherInfoModel();
            $gcsActivitiesModel = new StudentGCSActivitiesModel();
            $awardsModel = new StudentAwardsModel();

            // Retrieve data from all models
            $academicData = $academicModel->getByUserId($userId);
            $personalData = $personalModel->getByUserId($userId);
            $addressData = $addressModel->getByUserId($userId);
            $familyData = $familyModel->getByUserId($userId);
            $residenceData = $residenceModel->getByUserId($userId);
            $circumstancesData = $circumstancesModel->getByUserId($userId);
            $servicesNeededData = $servicesNeededModel->getServicesArray($userId);
            $servicesAvailedData = $servicesAvailedModel->getServicesArray($userId);
            $otherInfoData = $otherInfoModel->getByUserId($userId);
            $gcsActivitiesData = $gcsActivitiesModel->getActivitiesArray($userId);
            $awardsData = $awardsModel->getAwardsArray($userId);

            // Compile comprehensive student data
            return [
                'academic_info' => $academicData ?: [
                    'course' => null,
                    'year_level' => null,
                    'academic_status' => null,
                    'school_last_attended' => null,
                    'location_of_school' => null,
                    'previous_course_grade' => null
                ],
                'personal_info' => $personalData ?: [
                    'last_name' => null,
                    'first_name' => null,
                    'middle_name' => null,
                    'date_of_birth' => null,
                    'place_of_birth' => null,
                    'age' => null,
                    'sex' => null,
                    'civil_status' => null,
                    'religion' => null,
                    'contact_number' => null,
                    'fb_account_name' => null
                ],
                'address_info' => $addressData ?: [
                    'permanent_zone' => null,
                    'permanent_barangay' => null,
                    'permanent_city' => null,
                    'permanent_province' => null,
                    'present_zone' => null,
                    'present_barangay' => null,
                    'present_city' => null,
                    'present_province' => null
                ],
                'family_info' => $familyData ?: [
                    'father_name' => null,
                    'father_occupation' => null,
                    'father_educational_attainment' => null,
                    'father_age' => null,
                    'father_contact_number' => null,
                    'mother_name' => null,
                    'mother_occupation' => null,
                    'mother_educational_attainment' => null,
                    'mother_age' => null,
                    'mother_contact_number' => null,
                    'parents_permanent_address' => null,
                    'parents_contact_number' => null,
                    'spouse' => null,
                    'spouse_occupation' => null,
                    'spouse_educational_attainment' => null,
                    'guardian_name' => null,
                    'guardian_age' => null,
                    'guardian_occupation' => null,
                    'guardian_contact_number' => null
                ],
                'residence_info' => $residenceData ?: [
                    'residence_type' => null,
                    'residence_other_specify' => null,
                    'has_consent' => null
                ],
                'special_circumstances' => $circumstancesData ?: [
                    'is_solo_parent' => null,
                    'is_indigenous' => null,
                    'is_breastfeeding' => null,
                    'is_pwd' => null,
                    'pwd_disability_type' => null,
                    'pwd_proof_file' => null
                ],
                'services_needed' => $servicesNeededData ?: [],
                'services_availed' => $servicesAvailedData ?: [],
                'other_info' => $otherInfoData ?: [
                    'course_choice_reason' => null,
                    'family_description' => null,
                    'family_description_other' => null,
                    'living_condition' => null,
                    'physical_health_condition' => null,
                    'physical_health_condition_specify' => null,
                    'psych_treatment' => null
                ],
                'gcs_activities' => $gcsActivitiesData ?: [],
                'awards' => $awardsData ?: []
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error retrieving comprehensive student data for user ' . $userId . ': ' . $e->getMessage());
            
            // Return empty structure on error to maintain API consistency
            return [
                'academic_info' => ['course' => null, 'year_level' => null, 'academic_status' => null, 'school_last_attended' => null, 'location_of_school' => null, 'previous_course_grade' => null],
                'personal_info' => ['last_name' => null, 'first_name' => null, 'middle_name' => null, 'date_of_birth' => null, 'place_of_birth' => null, 'age' => null, 'sex' => null, 'civil_status' => null, 'religion' => null, 'contact_number' => null, 'fb_account_name' => null],
                'address_info' => ['permanent_zone' => null, 'permanent_barangay' => null, 'permanent_city' => null, 'permanent_province' => null, 'present_zone' => null, 'present_barangay' => null, 'present_city' => null, 'present_province' => null],
                'family_info' => ['father_name' => null, 'father_occupation' => null, 'father_educational_attainment' => null, 'father_age' => null, 'father_contact_number' => null, 'mother_name' => null, 'mother_occupation' => null, 'mother_educational_attainment' => null, 'mother_age' => null, 'mother_contact_number' => null, 'parents_permanent_address' => null, 'parents_contact_number' => null, 'spouse' => null, 'spouse_occupation' => null, 'spouse_educational_attainment' => null, 'guardian_name' => null, 'guardian_age' => null, 'guardian_occupation' => null, 'guardian_contact_number' => null],
                'residence_info' => ['residence_type' => null, 'residence_other_specify' => null, 'has_consent' => null],
                'special_circumstances' => ['is_solo_parent' => null, 'is_indigenous' => null, 'is_breastfeeding' => null, 'is_pwd' => null, 'pwd_disability_type' => null, 'pwd_proof_file' => null],
                'services_needed' => [],
                'services_availed' => [],
                'other_info' => ['course_choice_reason' => null, 'family_description' => null, 'family_description_other' => null, 'living_condition' => null, 'physical_health_condition' => null, 'physical_health_condition_specify' => null, 'psych_treatment' => null],
                'gcs_activities' => [],
                'awards' => []
            ];
        }
    }

    /**
     * Get individual student PDS data by user ID
     * 
     * @param string $userId The student's user ID
     * @return \CodeIgniter\HTTP\Response JSON response with student PDS data
     */
    public function getStudentPDSData($userId)
    {
        try {
            // Validate user ID format
            if (!preg_match('/^\d{10}$/', $userId)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid user ID format'
                ], 400);
            }

            // Check if user exists and is a student
            $db = \Config\Database::connect();
            $userQuery = $db->query("
                SELECT user_id, username, email, profile_picture
                FROM users 
                WHERE user_id = ? AND role = 'student'
            ", [$userId]);
            
            $user = $userQuery->getRowArray();
            if (!$user) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Get comprehensive student data
            $studentData = $this->getComprehensiveStudentData($userId);

            return $this->respond([
                'success' => true,
                'user_info' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'profile_picture' => $user['profile_picture']
                ],
                'pds_data' => $studentData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error in UsersApi::getStudentPDSData: ' . $e->getMessage());
            return $this->respond([
                'success' => false,
                'message' => 'An error occurred while fetching student PDS data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function formatTimeAgo($seconds) {
        if ($seconds < 60) {
            return "just now";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        }
    }
} 