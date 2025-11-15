<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;


/**
 * Student Special Circumstances Model
 */
class StudentSpecialCircumstancesModel extends Model
{
    protected $table = 'student_special_circumstances';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'is_solo_parent',
        'is_indigenous',
        'is_breastfeeding',
        'is_pwd',
        'pwd_disability_type',
        'pwd_proof_file'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

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