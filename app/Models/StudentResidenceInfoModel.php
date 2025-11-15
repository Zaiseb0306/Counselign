<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Residence Information Model
 */
class StudentResidenceInfoModel extends Model
{
    protected $table = 'student_residence_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'residence_type',
        'residence_other_specify',
        'has_consent'
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