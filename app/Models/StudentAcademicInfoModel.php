<?php

namespace App\Models;

use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Academic Information Model
 * UPDATED: Added school_last_attended, location_of_school, previous_course_grade
 */
class StudentAcademicInfoModel extends Model
{
    protected $table = 'student_academic_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'course',
        'year_level',
        'academic_status',
        // NEW FIELDS
        'school_last_attended',
        'location_of_school',
        'previous_course_grade'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required',
        'course' => 'required|max_length[50]',
        'year_level' => 'required|max_length[10]',
        'academic_status' => 'required|max_length[50]'
    ];

    /**
     * Get academic info by user_id
     */
    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->first();
    }

    /**
     * Update or create academic info
     */
    public function upsert(string $userId, array $data)
    {
        $existing = $this->getByUserId($userId);
        
        if ($existing) {
            return $this->where('student_id', $userId)->set($data)->update();
        } else {
            $data['student_id'] = $userId;
            return $this->insert($data);
        }
    }
}