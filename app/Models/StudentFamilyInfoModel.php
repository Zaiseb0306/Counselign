<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Student Family Information Model
 */
class StudentFamilyInfoModel extends Model
{
    protected $table = 'student_family_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'spouse',
        'guardian_contact_number'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'guardian_contact_number' => 'permit_empty|regex_match[/^09[0-9]{9}$/]'
    ];

    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->first();
    }

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