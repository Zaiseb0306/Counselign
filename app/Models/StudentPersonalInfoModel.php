<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Personal Information Model
 */
class StudentPersonalInfoModel extends Model
{
    protected $table = 'student_personal_info';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'last_name',
        'first_name',
        'middle_name',
        'date_of_birth',
        'age',
        'sex',
        'civil_status',
        'contact_number',
        'fb_account_name',
        'place_of_birth',
        'religion'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required',
        'last_name' => 'permit_empty',
        'first_name' => 'permit_empty',
        'middle_name' => 'permit_empty',
        'contact_number' => 'permit_empty|regex_match[/^09[0-9]{9}$/]'
    ];

    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->first();
    }

    public function upsert(string $userId, array $data)
    {
        $existing = $this->getByUserId($userId);
        
        if ($existing) {
            // Use where clause instead of passing ID directly to avoid setBind error
            $result = $this->where('student_id', $userId)->set($data)->update();
            log_message('debug', 'Personal data UPDATE - User: ' . $userId . ', Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            if (!$result) {
                log_message('error', 'Personal data UPDATE failed - Errors: ' . json_encode($this->errors()));
            }
            return $result;
        } else {
            $data['student_id'] = $userId;
            $result = $this->insert($data);
            log_message('debug', 'Personal data INSERT - User: ' . $userId . ', Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            if (!$result) {
                log_message('error', 'Personal data INSERT failed - Errors: ' . json_encode($this->errors()));
            }
            return $result;
        }
    }
}