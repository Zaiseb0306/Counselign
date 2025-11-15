<?php

namespace App\Models;

use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student GCS Activities Model
 * Handles GCS seminars/activities that students want to avail
 */
class StudentGCSActivitiesModel extends Model
{
    protected $table = 'student_gcs_activities';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'activity_type',
        'other_specify',
        'tutorial_subjects'
    ];
    protected $useTimestamps = false;

    /**
     * Get all GCS activities by user ID
     */
    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->findAll();
    }

    /**
     * Sync GCS activities (delete old, insert new)
     */
    public function syncActivities(string $userId, array $activities)
    {
        log_message('debug', 'SyncGCSActivities - UserId: ' . $userId);
        log_message('debug', 'SyncGCSActivities - Activities: ' . json_encode($activities));
        
        $this->db->transStart();

        try {
            // Delete existing activities
            $this->where('student_id', $userId)->delete();

            // Insert new activities
            if (!empty($activities)) {
                foreach ($activities as $index => $activity) {
                    log_message('debug', 'SyncGCSActivities - Inserting activity ' . $index . ': ' . json_encode($activity));
                    
                    $insertData = [
                        'student_id' => (string) $userId,
                        'activity_type' => (string) $activity['type'],
                        'other_specify' => isset($activity['other']) ? (string) $activity['other'] : null,
                        'tutorial_subjects' => isset($activity['tutorial_subjects']) ? (string) $activity['tutorial_subjects'] : null
                    ];
                    
                    $this->insert($insertData);
                }
            }

            $this->db->transComplete();
            log_message('debug', 'SyncGCSActivities - Transaction completed successfully');
            return $this->db->transStatus();
        } catch (\Exception $e) {
            log_message('error', 'SyncGCSActivities - Exception: ' . $e->getMessage());
            log_message('error', 'SyncGCSActivities - Stack trace: ' . $e->getTraceAsString());
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Get activities as array format
     */
    public function getActivitiesArray(string $userId): array
    {
        $activities = $this->getByUserId($userId);
        $result = [];
        
        foreach ($activities as $activity) {
            $result[] = [
                'type' => $activity['activity_type'],
                'other' => $activity['other_specify'],
                'tutorial_subjects' => $activity['tutorial_subjects']
            ];
        }
        
        return $result;
    }

    /**
     * Delete all activities for a user
     */
    public function deleteActivities(string $userId): bool
    {
        try {
            $this->where('student_id', $userId)->delete();
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Delete GCS Activities Error: ' . $e->getMessage());
            return false;
        }
    }
}