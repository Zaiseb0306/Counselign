<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;


/**
 * Student Address Information Model
 */
class StudentAddressInfoModel extends Model
{
    protected $table = 'student_address_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'permanent_zone',
        'permanent_barangay',
        'permanent_city',
        'permanent_province',
        'present_zone',
        'present_barangay',
        'present_city',
        'present_province'
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
