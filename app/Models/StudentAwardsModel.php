<?php

namespace App\Models;

use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Awards Model
 * Handles student awards and recognition
 */
class StudentAwardsModel extends Model
{
    protected $table = 'student_awards';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'award_name',
        'school_organization',
        'year_received'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required',
        'award_name' => 'required|max_length[255]',
        'school_organization' => 'required|max_length[255]',
        'year_received' => 'required|max_length[4]|regex_match[/^\d{4}$/]'
    ];

    /**
     * Get all awards by user ID
     */
    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)
                    ->orderBy('year_received', 'DESC')
                    ->findAll();
    }

    /**
     * Sync awards (delete old, insert new)
     */
    public function syncAwards(string $userId, array $awards)
    {
        log_message('debug', 'SyncAwards - UserId: ' . $userId);
        log_message('debug', 'SyncAwards - Awards: ' . json_encode($awards));
        
        $this->db->transStart();

        try {
            // Delete existing awards
            $this->where('student_id', $userId)->delete();

            // Insert new awards
            if (!empty($awards)) {
                foreach ($awards as $index => $award) {
                    // Validate that all required fields are present and not empty
                    if (empty($award['award_name']) || empty($award['school_organization']) || empty($award['year_received'])) {
                        log_message('debug', 'SyncAwards - Skipping empty award at index ' . $index);
                        continue;
                    }

                    log_message('debug', 'SyncAwards - Inserting award ' . $index . ': ' . json_encode($award));
                    
                    $insertData = [
                        'student_id' => (string) $userId,
                        'award_name' => (string) $award['award_name'],
                        'school_organization' => (string) $award['school_organization'],
                        'year_received' => (string) $award['year_received']
                    ];
                    
                    $this->insert($insertData);
                }
            }

            $this->db->transComplete();
            log_message('debug', 'SyncAwards - Transaction completed successfully');
            return $this->db->transStatus();
        } catch (\Exception $e) {
            log_message('error', 'SyncAwards - Exception: ' . $e->getMessage());
            log_message('error', 'SyncAwards - Stack trace: ' . $e->getTraceAsString());
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Get awards as array format
     */
    public function getAwardsArray(string $userId): array
    {
        $awards = $this->getByUserId($userId);
        $result = [];
        
        foreach ($awards as $award) {
            $result[] = [
                'award_name' => $award['award_name'],
                'school_organization' => $award['school_organization'],
                'year_received' => $award['year_received']
            ];
        }
        
        return $result;
    }

    /**
     * Delete all awards for a user
     */
    public function deleteAwards(string $userId): bool
    {
        try {
            $this->where('student_id', $userId)->delete();
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Delete Awards Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get award count for a user
     */
    public function getAwardCount(string $userId): int
    {
        return $this->where('student_id', $userId)->countAllResults();
    }
}