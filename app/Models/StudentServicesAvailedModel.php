<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Services Availed Model (Many-to-Many)
 */
class StudentServicesAvailedModel extends Model
{
    protected $table = 'student_services_availed';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'service_type',
        'other_specify'
    ];
    protected $useTimestamps = false; // Disable automatic timestamps

    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->findAll();
    }

    public function syncServices(string $userId, array $services)
    {
        log_message('debug', 'SyncServicesAvailed - UserId: ' . $userId . ' (Type: ' . gettype($userId) . ')');
        log_message('debug', 'SyncServicesAvailed - Services: ' . json_encode($services));
        
        $this->db->transStart();

        try {
            $this->where('student_id', $userId)->delete();

            if (!empty($services)) {
                foreach ($services as $index => $service) {
                    log_message('debug', 'SyncServicesAvailed - Inserting service ' . $index . ': ' . json_encode($service));
                    $this->insert([
                        'student_id' => (string) $userId,
                        'service_type' => (string) $service['type'],
                        'other_specify' => $service['other'] ? (string) $service['other'] : null
                    ]);
                }
            }

            $this->db->transComplete();
            log_message('debug', 'SyncServicesAvailed - Transaction completed successfully');
            return $this->db->transStatus();
        } catch (\Exception $e) {
            log_message('error', 'SyncServicesAvailed - Exception: ' . $e->getMessage());
            log_message('error', 'SyncServicesAvailed - Stack trace: ' . $e->getTraceAsString());
            $this->db->transRollback();
            return false;
        }
    }

    public function getServicesArray(string $userId): array
    {
        $services = $this->getByUserId($userId);
        $result = [];
        
        foreach ($services as $service) {
            $result[] = [
                'type' => $service['service_type'],
                'other' => $service['other_specify']
            ];
        }
        
        return $result;
    }
}