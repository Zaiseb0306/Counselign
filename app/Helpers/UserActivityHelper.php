<?php

namespace App\Helpers;

use CodeIgniter\Database\ConnectionInterface;

/**
 * UserActivityHelper - Centralized helper for tracking user last_activity
 * 
 * This helper provides type-safe methods for updating user last_activity
 * across all student and counselor activities in the system.
 * 
 * Usage:
 * - Call updateLastActivity($userId) after any user activity
 * - Use updateLastActivityWithRole($userId, $role) for role-specific activities
 * - All methods include comprehensive error handling and logging
 */
class UserActivityHelper
{
    /**
     * Database connection instance
     */
    private ConnectionInterface $db;

    /**
     * Request instance for accessing POST data
     */
    private $request;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = \Config\Services::request();
    }

    /**
     * Dynamically detect user ID from multiple sources
     * 
     * @param string|null $providedUserId Optional user ID provided directly
     * @param array $data Optional data array containing user IDs
     * @return string|null The detected user ID or null if not found
     */
    private function detectUserId(?string $providedUserId = null, array $data = []): ?string
    {
        // Priority order for user ID detection
        $userIdSources = [
            // 1. Directly provided user ID
            $providedUserId,
            
            // 2. Session-based user IDs
            session()->get('user_id_display'),
            session()->get('user_id'),
            
            // 3. Data array sources (for messages, appointments, etc.)
            $data['user_id'] ?? null,
            $data['user_id_display'] ?? null,
            $data['student_id'] ?? null,
            $data['counselor_id'] ?? null,
            $data['sender_id'] ?? null,
            $data['receiver_id'] ?? null,
            
            // 4. Request-based sources
            $this->request->getPost('user_id'),
            $this->request->getPost('student_id'),
            $this->request->getPost('counselor_id'),
            $this->request->getPost('sender_id'),
        ];

        foreach ($userIdSources as $userId) {
            if (!empty($userId) && is_string($userId)) {
                // Validate that the user exists in the database
                if ($this->validateUserId($userId)) {
                    return $userId;
                }
            }
        }

        return null;
    }

    /**
     * Validate that a user ID exists in the database
     * 
     * @param string $userId The user ID to validate
     * @return bool True if user exists, false otherwise
     */
    private function validateUserId(string $userId): bool
    {
        try {
            $user = $this->db->table('users')
                ->select('user_id')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            return !empty($user);
        } catch (\Exception $e) {
            log_message('error', "Exception validating user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current Manila time in proper format
     * 
     * @return string Current Manila time in Y-m-d H:i:s format
     */
    private function getManilaTime(): string
    {
        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        return $manilaTime->format('Y-m-d H:i:s');
    }

    /**
     * Update last_activity for a user with dynamic ID detection
     * 
     * @param string|null $userId Optional user ID (will be auto-detected if not provided)
     * @param string|null $activityType Optional activity type for logging
     * @param array $data Optional data array for ID detection
     * @return bool True if successful, false otherwise
     */
    public function updateLastActivity(?string $userId = null, ?string $activityType = null, array $data = []): bool
    {
        try {
            // Dynamically detect user ID if not provided
            $detectedUserId = $this->detectUserId($userId, $data);
            
            if (!$detectedUserId) {
                log_message('error', "Could not detect valid user ID for activity: {$activityType}");
                return false;
            }

            $manilaTime = $this->getManilaTime();
            
            $result = $this->db->table('users')
                ->where('user_id', $detectedUserId)
                ->set([
                    'last_activity' => $manilaTime,
                    'last_active_at' => $manilaTime
                ])
                ->update();

            if ($result) {
                $logMessage = "Updated last_activity and last_active_at for user {$detectedUserId}";
                if ($activityType) {
                    $logMessage .= " (Activity: {$activityType})";
                }
                log_message('info', $logMessage);
                return true;
            } else {
                log_message('error', "Failed to update last_activity for user {$detectedUserId}");
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "Exception updating last_activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last_activity with role validation and dynamic ID detection
     * 
     * @param string $expectedRole The expected role (student, counselor, admin)
     * @param string|null $activityType Optional activity type for logging
     * @param string|null $userId Optional user ID (will be auto-detected if not provided)
     * @param array $data Optional data array for ID detection
     * @return bool True if successful, false otherwise
     */
    public function updateLastActivityWithRole(string $expectedRole, ?string $activityType = null, ?string $userId = null, array $data = []): bool
    {
        try {
            // Dynamically detect user ID if not provided
            $detectedUserId = $this->detectUserId($userId, $data);
            
            if (!$detectedUserId) {
                log_message('error', "Could not detect valid user ID for role-based activity: {$activityType}");
                return false;
            }

            // Verify the user exists and has the expected role
            $user = $this->db->table('users')
                ->select('user_id, role')
                ->where('user_id', $detectedUserId)
                ->where('role', $expectedRole)
                ->get()
                ->getRowArray();

            if (!$user) {
                log_message('error', "User {$detectedUserId} not found or role mismatch. Expected: {$expectedRole}");
                return false;
            }

            return $this->updateLastActivity($detectedUserId, $activityType, $data);
        } catch (\Exception $e) {
            log_message('error', "Exception in updateLastActivityWithRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last_activity for multiple users (batch operation)
     * 
     * @param array $userIds Array of user IDs to update
     * @param string|null $activityType Optional activity type for logging
     * @return int Number of users successfully updated
     */
    public function updateLastActivityBatch(array $userIds, ?string $activityType = null): int
    {
        if (empty($userIds)) {
            return 0;
        }

        try {
            $currentTime = date('Y-m-d H:i:s');
            $successCount = 0;

            foreach ($userIds as $userId) {
                $result = $this->db->table('users')
                    ->where('user_id', $userId)
                    ->set('last_activity', $currentTime)
                    ->update();

                if ($result) {
                    $successCount++;
                }
            }

            $logMessage = "Batch updated last_activity for {$successCount}/" . count($userIds) . " users";
            if ($activityType) {
                $logMessage .= " (Activity: {$activityType})";
            }
            log_message('info', $logMessage);

            return $successCount;
        } catch (\Exception $e) {
            log_message('error', "Exception in updateLastActivityBatch: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user's current last_activity timestamp
     * 
     * @param string $userId The user ID to check
     * @return string|null The last_activity timestamp or null if not found
     */
    public function getLastActivity(string $userId): ?string
    {
        try {
            $user = $this->db->table('users')
                ->select('last_activity')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            return $user ? $user['last_activity'] : null;
        } catch (\Exception $e) {
            log_message('error', "Exception getting last_activity for user {$userId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has been active within specified minutes
     * 
     * @param string $userId The user ID to check
     * @param int $minutes Number of minutes to check (default: 30)
     * @return bool True if user was active within the timeframe
     */
    public function isUserActive(string $userId, int $minutes = 30): bool
    {
        try {
            $lastActivity = $this->getLastActivity($userId);
            
            if (!$lastActivity) {
                return false;
            }

            $lastActivityTime = strtotime($lastActivity);
            $cutoffTime = strtotime("-{$minutes} minutes");
            
            return $lastActivityTime > $cutoffTime;
        } catch (\Exception $e) {
            log_message('error', "Exception checking user activity for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update last_activity for student activities with dynamic ID detection
     * 
     * @param string|null $studentId Optional student ID (will be auto-detected if not provided)
     * @param string $activityType The type of activity
     * @param array $data Optional data array for ID detection
     * @return bool True if successful
     */
    public function updateStudentActivity(?string $studentId = null, string $activityType = 'student_activity', array $data = []): bool
    {
        // Add student-specific data sources
        $studentData = array_merge($data, [
            'student_id' => $studentId,
            'sender_id' => $data['sender_id'] ?? null,
            'receiver_id' => $data['receiver_id'] ?? null
        ]);
        
        return $this->updateLastActivityWithRole('student', $activityType, $studentId, $studentData);
    }

    /**
     * Update last_activity for counselor activities with dynamic ID detection
     * 
     * @param string|null $counselorId Optional counselor ID (will be auto-detected if not provided)
     * @param string $activityType The type of activity
     * @param array $data Optional data array for ID detection
     * @return bool True if successful
     */
    public function updateCounselorActivity(?string $counselorId = null, string $activityType = 'counselor_activity', array $data = []): bool
    {
        // Add counselor-specific data sources
        $counselorData = array_merge($data, [
            'counselor_id' => $counselorId,
            'sender_id' => $data['sender_id'] ?? null,
            'receiver_id' => $data['receiver_id'] ?? null
        ]);
        
        return $this->updateLastActivityWithRole('counselor', $activityType, $counselorId, $counselorData);
    }

    /**
     * Update last_activity for admin activities with dynamic ID detection
     * 
     * @param string|null $adminId Optional admin ID (will be auto-detected if not provided)
     * @param string $activityType The type of activity
     * @param array $data Optional data array for ID detection
     * @return bool True if successful
     */
    public function updateAdminActivity(?string $adminId = null, string $activityType = 'admin_activity', array $data = []): bool
    {
        return $this->updateLastActivityWithRole('admin', $activityType, $adminId, $data);
    }

    /**
     * Update all activity fields for logout with dynamic ID detection
     * 
     * @param string|null $userId Optional user ID (will be auto-detected if not provided)
     * @param array $data Optional data array for ID detection
     * @return bool True if successful
     */
    public function updateLogoutActivity(?string $userId = null, array $data = []): bool
    {
        try {
            // Dynamically detect user ID if not provided
            $detectedUserId = $this->detectUserId($userId, $data);
            
            if (!$detectedUserId) {
                log_message('error', "Could not detect valid user ID for logout activity");
                return false;
            }

            $manilaTime = $this->getManilaTime();
            
            $result = $this->db->table('users')
                ->where('user_id', $detectedUserId)
                ->set([
                    'last_activity' => $manilaTime,
                    'last_active_at' => $manilaTime,
                    'last_inactive_at' => $manilaTime,
                    'logout_time' => $manilaTime
                ])
                ->update();

            if ($result) {
                log_message('info', "Updated logout activity for user {$detectedUserId} at {$manilaTime}");
                return true;
            } else {
                log_message('error', "Failed to update logout activity for user {$detectedUserId}");
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "Exception in updateLogoutActivity: " . $e->getMessage());
            return false;
        }
    }
}
