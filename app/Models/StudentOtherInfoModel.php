<?php

namespace App\Models;

use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Other Information Model
 * Handles course choice reason, family description, living condition, health info
 */
class StudentOtherInfoModel extends Model
{
    protected $table = 'student_other_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'course_choice_reason',
        'family_description',
        'family_description_other',
        'living_condition',
        'physical_health_condition',
        'physical_health_condition_specify',
        'psych_treatment'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required'
    ];

    /**
     * Get other info by user_id
     */
    public function getByUserId(string $userId)
    {
        $data = $this->where('student_id', $userId)->first();
        
        // Decode JSON field
        if ($data && isset($data['family_description'])) {
            $data['family_description'] = json_decode($data['family_description'], true);
        }
        
        return $data;
    }

    /**
     * Update or create other info
     */
    public function upsert(string $userId, array $data)
    {
        $existing = $this->where('student_id', $userId)->first();
        
        // Encode JSON field
        if (isset($data['family_description']) && is_array($data['family_description'])) {
            $data['family_description'] = json_encode($data['family_description']);
        }
        
        if ($existing) {
            return $this->where('student_id', $userId)->set($data)->update();
        } else {
            $data['student_id'] = $userId;
            return $this->insert($data);
        }
    }
}