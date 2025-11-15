<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Student Services Needed Model (Many-to-Many)
 */
class StudentServicesNeededModel extends Model
{
    protected $table = 'student_services_needed';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'service_type',
        'other_specify'
    ];
    protected $useTimestamps = false; // Disable automatic timestamps

    /**
     * Get all services needed by a user
     */
    public function getByUserId(string $userId)
    {
        return $this->where('student_id', $userId)->findAll();
    }

    /**
     * Delete all services and re-insert (for updates)
     */
    public function syncServices(string $userId, array $services)
    {
        log_message('debug', 'SyncServices - UserId: ' . $userId . ' (Type: ' . gettype($userId) . ')');
        log_message('debug', 'SyncServices - Services: ' . json_encode($services));
        
        // Start transaction
        $this->db->transStart();

        try {
            // Delete existing services
            log_message('debug', 'SyncServices - Deleting existing services for user: ' . $userId);
            $this->where('student_id', $userId)->delete();

            // Insert new services
            if (!empty($services)) {
                log_message('debug', 'SyncServices - Inserting ' . count($services) . ' services');
                foreach ($services as $index => $service) {
                    log_message('debug', 'SyncServices - Inserting service ' . $index . ': ' . json_encode($service));
                    $this->insert([
                        'student_id' => (string) $userId,
                        'service_type' => (string) $service['type'],
                        'other_specify' => $service['other'] ? (string) $service['other'] : null
                    ]);
                }
            }

            // Complete transaction
            $this->db->transComplete();
            log_message('debug', 'SyncServices - Transaction completed successfully');

            return $this->db->transStatus();
        } catch (\Exception $e) {
            log_message('error', 'SyncServices - Exception: ' . $e->getMessage());
            log_message('error', 'SyncServices - Stack trace: ' . $e->getTraceAsString());
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Get services as simple array
     */
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