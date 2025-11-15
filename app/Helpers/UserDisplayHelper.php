<?php

namespace App\Helpers;

use App\Models\StudentPersonalInfoModel;
use App\Models\CounselorModel;

/**
 * UserDisplayHelper - Helper for getting user display names
 * 
 * This helper provides methods to get user display names for students and counselors
 * while maintaining the user_id_display for system functions.
 */
class UserDisplayHelper
{
    /**
     * Get user display name based on role
     * 
     * @param string $userId The user ID
     * @param string $role The user role (student, counselor, admin)
     * @return array Array containing display_name and user_id_display
     */
    public function getUserDisplayInfo(string $userId, string $role): array
    {
        try {
            $displayName = '';
            $userIdDisplay = $userId; // Keep original for functions
            
            switch ($role) {
                case 'student':
                    $displayName = $this->getStudentDisplayName($userId);
                    break;
                case 'counselor':
                    $displayName = $this->getCounselorDisplayName($userId);
                    break;
                case 'admin':
                    $displayName = $this->getAdminDisplayName($userId);
                    break;
                default:
                    $displayName = $userId;
            }
            
            return [
                'display_name' => $displayName,
                'user_id_display' => $userIdDisplay,
                'has_name' => !empty($displayName) && $displayName !== $userId
            ];
        } catch (\Exception $e) {
            log_message('error', "Exception in getUserDisplayInfo for user {$userId}: " . $e->getMessage());
            return [
                'display_name' => $userId,
                'user_id_display' => $userId,
                'has_name' => false
            ];
        }
    }
    
    /**
     * Get student display name (first_name + last_name)
     * 
     * @param string $studentId The student ID
     * @return string The display name or student ID if no name found
     */
    private function getStudentDisplayName(string $studentId): string
    {
        try {
            $studentModel = new StudentPersonalInfoModel();
            $student = $studentModel->getByUserId($studentId);
            
            if (!$student) {
                return $studentId;
            }
            
            $firstName = trim($student['first_name'] ?? '');
            $lastName = trim($student['last_name'] ?? '');
            
            if (empty($firstName) && empty($lastName)) {
                return $studentId;
            }
            
            return trim($firstName . ' ' . $lastName);
        } catch (\Exception $e) {
            log_message('error', "Exception getting student display name for {$studentId}: " . $e->getMessage());
            return $studentId;
        }
    }
    
    /**
     * Get counselor display name
     * 
     * @param string $counselorId The counselor ID
     * @return string The display name or counselor ID if no name found
     */
    private function getCounselorDisplayName(string $counselorId): string
    {
        try {
            $counselorModel = new CounselorModel();
            $counselor = $counselorModel->where('counselor_id', $counselorId)->first();
            
            if (!$counselor) {
                return $counselorId;
            }
            
            $name = trim($counselor['name'] ?? '');
            
            if (empty($name)) {
                return $counselorId;
            }
            
            return $name;
        } catch (\Exception $e) {
            log_message('error', "Exception getting counselor display name for {$counselorId}: " . $e->getMessage());
            return $counselorId;
        }
    }
    
    /**
     * Get admin display name (admin uses username or user_id)
     * 
     * @param string $adminId The admin ID
     * @return string The display name or admin ID if no name found
     */
    private function getAdminDisplayName(string $adminId): string
    {
        try {
            $db = \Config\Database::connect();
            $user = $db->table('users')
                ->select('username')
                ->where('user_id', $adminId)
                ->where('role', 'admin')
                ->get()
                ->getRowArray();
            
            if (!$user) {
                return $adminId;
            }
            
            $username = trim($user['username'] ?? '');
            
            if (empty($username)) {
                return $adminId;
            }
            
            return $username;
        } catch (\Exception $e) {
            log_message('error', "Exception getting admin display name for {$adminId}: " . $e->getMessage());
            return $adminId;
        }
    }
}
