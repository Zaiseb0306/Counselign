<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;
use App\Libraries\QueryCache;

/**
 * Optimized Appointment Model with Caching
 */
class OptimizedAppointmentModel extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'preferred_date',
        'preferred_time',
        'method_type',
        'counselor_preference',
        'description',
        'reason',
        'status',
        'purpose'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    private $queryCache;
    
    public function __construct()
    {
        parent::__construct();
        $this->queryCache = new QueryCache();
    }
    
    /**
     * Get appointments with caching
     */
    public function getAppointmentsByStatus(string $status, int $limit = 50): array
    {
        $cacheKey = $this->queryCache->generateKey('appointments', ['status' => $status], 'by_status');
        
        return $this->queryCache->remember($cacheKey, function() use ($status, $limit) {
            return $this->select('a.*, u.username, u.email, c.name as counselor_name')
                ->from('appointments a')
                ->join('users u', 'a.student_id = u.user_id', 'left')
                ->join('counselors c', 'a.counselor_preference = c.counselor_id', 'left')
                ->where('a.status', $status)
                ->orderBy('a.created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        }, 300); // Cache for 5 minutes
    }
    
    /**
     * Get counselor availability with caching
     */
    public function getCounselorAvailability(string $counselorId): array
    {
        $cacheKey = $this->queryCache->generateKey('counselor_availability', ['counselor_id' => $counselorId], 'availability');
        
        return $this->queryCache->remember($cacheKey, function() use ($counselorId) {
            return $this->db->table('counselor_availability')
                ->where('counselor_id', $counselorId)
                ->orderBy('available_days', 'ASC')
                ->get()
                ->getResultArray();
        }, 600); // Cache for 10 minutes
    }
    
    /**
     * Check appointment conflicts with optimized query
     */
    public function hasConflict(string $date, string $time, string $counselorId = null): bool
    {
        $cacheKey = $this->queryCache->generateKey('appointments', [
            'date' => $date, 
            'time' => $time, 
            'counselor' => $counselorId
        ], 'conflict_check');
        
        return $this->queryCache->remember($cacheKey, function() use ($date, $time, $counselorId) {
            $builder = $this->db->table('appointments');
            $builder->where('preferred_date', $date)
                   ->where('preferred_time', $time)
                   ->where('status', 'approved');
                   
            if ($counselorId) {
                $builder->where('counselor_preference', $counselorId);
            }
            
            return $builder->countAllResults() > 0;
        }, 60); // Cache for 1 minute
    }
    
    /**
     * Invalidate cache when appointment is modified
     */
    public function invalidateCache(int $appointmentId = null): void
    {
        // Invalidate general appointment caches
        $this->queryCache->forget('query_appointments_*');
        
        // Invalidate specific appointment cache if provided
        if ($appointmentId) {
            $appointment = $this->find($appointmentId);
            if ($appointment) {
                $this->queryCache->forgetUser($appointment['student_id'], 'appointments_*');
            }
        }
    }
    
    /**
     * Override insert to invalidate cache
     */
    public function insert($data = null, bool $returnID = true)
    {
        $result = parent::insert($data, $returnID);
        $this->invalidateCache();
        return $result;
    }
    
    /**
     * Override update to invalidate cache
     */
    public function update($id = null, $data = null): bool
    {
        $result = parent::update($id, $data);
        $this->invalidateCache($id);
        return $result;
    }
}
