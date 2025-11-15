<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
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
        'father_educational_attainment',
        'father_age',
        'father_contact_number',
        'mother_name',
        'mother_occupation',
        'mother_educational_attainment',
        'mother_age',
        'mother_contact_number',
        'parents_permanent_address',
        'parents_contact_number',
        'spouse',
        'spouse_occupation',
        'spouse_educational_attainment',
        'guardian_name',
        'guardian_age',
        'guardian_occupation',
        'guardian_contact_number'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    
    protected $validationMessages = [];

    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->first();
    }

    public function upsert(string $userId, array $data)
    {
        // Debug: Log the data being saved
        log_message('debug', 'StudentFamilyInfoModel::upsert - User ID: ' . $userId);
        log_message('debug', 'StudentFamilyInfoModel::upsert - Data: ' . json_encode($data));
        
        $existing = $this->getByUserId($userId);
        
        if ($existing) {
            log_message('debug', 'StudentFamilyInfoModel::upsert - Updating existing record ID: ' . $existing['id']);
            
            // Filter out fields that are not in allowedFields and remove student_id from update
            $filteredData = array_intersect_key($data, array_flip($this->allowedFields));
            unset($filteredData['student_id']); // Don't update the student_id field
            
            log_message('debug', 'StudentFamilyInfoModel::upsert - Filtered data for update: ' . json_encode($filteredData));
            
            // Use update() which returns number of affected rows or false on error
            $builder = $this->db->table($this->table);
            $builder->where('student_id', $userId);
            $result = $builder->update($filteredData);
            
            // Check for database errors
            $dbError = $this->db->error();
            if (!empty($dbError['message'])) {
                log_message('error', 'StudentFamilyInfoModel::upsert - Database error: ' . json_encode($dbError));
                return false;
            }
            
            // Update returns number of affected rows, which could be 0 if data unchanged
            // This is not an error, so we return true
            log_message('debug', 'StudentFamilyInfoModel::upsert - Update completed (rows affected: ' . $result . ')');
            return true;
            
        } else {
            log_message('debug', 'StudentFamilyInfoModel::upsert - Inserting new record');
            $data['student_id'] = $userId;
            $result = $this->insert($data);
            
            if (!$result) {
                log_message('error', 'StudentFamilyInfoModel::upsert - Insert FAILED for user: ' . $userId);
                
                // Log any validation errors
                if ($this->errors()) {
                    log_message('error', 'StudentFamilyInfoModel::upsert - Validation errors: ' . json_encode($this->errors()));
                }
                
                // Log database error if any
                $dbError = $this->db->error();
                if (!empty($dbError['message'])) {
                    log_message('error', 'StudentFamilyInfoModel::upsert - Database error: ' . json_encode($dbError));
                }
                
                return false;
            } else {
                log_message('debug', 'StudentFamilyInfoModel::upsert - Insert SUCCESS (ID: ' . $result . ')');
                return true;
            }
        }
    }
}