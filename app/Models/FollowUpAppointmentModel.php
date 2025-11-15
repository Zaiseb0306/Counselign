<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

class FollowUpAppointmentModel extends Model
{
    protected $table            = 'follow_up_appointments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'counselor_id',
        'student_id',
        'parent_appointment_id',
        'preferred_date',
        'preferred_time',
        'consultation_type',
        'follow_up_sequence',
        'description',
        'reason',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'counselor_id'       => 'required|max_length[10]',
        'student_id'         => 'required|max_length[100]',
        'preferred_date'     => 'required|valid_date',
        'preferred_time'     => 'required|max_length[50]',
        'consultation_type'  => 'required|max_length[50]',
        'follow_up_sequence' => 'required|integer',
        'status'             => 'in_list[pending,approved,rejected,completed,cancelled]'
    ];
    protected $validationMessages   = [
        'counselor_id' => [
            'required' => 'Counselor ID is required'
        ],
        'student_id' => [
            'required' => 'Student ID is required'
        ],
        'preferred_date' => [
            'required'   => 'Preferred date is required',
            'valid_date' => 'Please provide a valid date'
        ],
        'preferred_time' => [
            'required' => 'Preferred time is required'
        ],
        'consultation_type' => [
            'required' => 'Consultation type is required'
        ],
        'status' => [
            'in_list' => 'Invalid status value'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get follow-up appointments by student ID
     */
    public function getByStudentId($studentId)
    {
        return $this->where('student_id', $studentId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get follow-up appointments by counselor ID
     */
    public function getByCounselorId($counselorId)
    {
        return $this->where('counselor_id', $counselorId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get follow-up appointments by status
     */
    public function getByStatus($status)
    {
        return $this->where('status', $status)
                    ->orderBy('preferred_date', 'ASC')
                    ->orderBy('preferred_time', 'ASC')
                    ->findAll();
    }

    /**
     * Get follow-up chain (all follow-ups related to a parent appointment)
     */
    public function getFollowUpChain($parentAppointmentId)
    {
        return $this->where('parent_appointment_id', $parentAppointmentId)
                    ->orderBy('follow_up_sequence', 'ASC')
                    ->findAll();
    }

    /**
     * Get follow-up appointments with related data (student and counselor info)
     */
    public function getWithDetails($id = null)
    {
        $builder = $this->db->table($this->table);
        $builder->select('follow_up_appointments.*, 
                         users.username as student_name, 
                         users.email as student_email,
                         counselors.name as counselor_name,
                         counselors.email as counselor_email');
        $builder->join('users', 'users.user_id = follow_up_appointments.student_id', 'left');
        $builder->join('counselors', 'counselors.counselor_id = follow_up_appointments.counselor_id', 'left');
        
        if ($id !== null) {
            $builder->where('follow_up_appointments.id', $id);
            return $builder->get()->getRowArray();
        }
        
        return $builder->orderBy('follow_up_appointments.created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Get upcoming follow-up appointments
     */
    public function getUpcoming($counselorId = null, $limit = 10)
    {
        $builder = $this->where('preferred_date >=', date('Y-m-d'))
                        ->whereIn('status', ['pending', 'approved'])
                        ->orderBy('preferred_date', 'ASC')
                        ->orderBy('preferred_time', 'ASC');
        
        if ($counselorId !== null) {
            $builder->where('counselor_id', $counselorId);
        }
        
        return $builder->limit($limit)->findAll();
    }

    /**
     * Update appointment status
     */
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Get next follow-up sequence number for a parent appointment
     */
    public function getNextSequence($parentAppointmentId)
    {
        $result = $this->selectMax('follow_up_sequence')
                       ->where('parent_appointment_id', $parentAppointmentId)
                       ->first();
        
        return ($result['follow_up_sequence'] ?? 0) + 1;
    }

    /**
     * Get count by status
     */
    public function countByStatus($status, $counselorId = null)
    {
        $builder = $this->where('status', $status);
        
        if ($counselorId !== null) {
            $builder->where('counselor_id', $counselorId);
        }
        
        return $builder->countAllResults();
    }

    /**
     * Get appointments for a specific date range
     */
    public function getByDateRange($startDate, $endDate, $counselorId = null)
    {
        $builder = $this->where('preferred_date >=', $startDate)
                        ->where('preferred_date <=', $endDate)
                        ->orderBy('preferred_date', 'ASC')
                        ->orderBy('preferred_time', 'ASC');
        
        if ($counselorId !== null) {
            $builder->where('counselor_id', $counselorId);
        }
        
        return $builder->findAll();
    }

    /**
     * Check if a student has any pending follow-up session
     *
     * @param string $studentId
     * @return bool
     */
    public function hasPendingFollowUp(string $studentId): bool
    {
        return $this->where('student_id', $studentId)
                    ->where('status', 'pending')
                    ->countAllResults() > 0;
    }

    /**
     * Check if counselor has follow-up conflicts on specific date/time
     * 
     * @param string $counselorId
     * @param string $date Format: YYYY-MM-DD
     * @param string $time
     * @param int|null $excludeFollowUpId Optional follow-up ID to exclude from check (for updates)
     * @return bool True if conflict exists, false if available
     */
    public function hasCounselorFollowUpConflict(string $counselorId, string $date, string $time, ?int $excludeFollowUpId = null): bool
    {
        // Get all pending follow-up sessions for the counselor on the specified date
        $builder = $this->where('preferred_date', $date)
                        ->where('counselor_id', $counselorId)
                        ->where('status', 'pending');
        
        if ($excludeFollowUpId !== null) {
            $builder->where('id !=', $excludeFollowUpId);
        }
        
        $followUpSessions = $builder->findAll();
        
        // Check if any of the existing follow-up sessions overlap with the requested time
        foreach ($followUpSessions as $session) {
            if ($this->timeRangesOverlap($time, $session['preferred_time'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get counselor follow-up conflicts on specific date/time
     * 
     * @param string $counselorId
     * @param string $date Format: YYYY-MM-DD
     * @param string $time
     * @param int|null $excludeFollowUpId Optional follow-up ID to exclude from check
     * @return array Array of conflicting follow-up appointments
     */
    public function getCounselorFollowUpConflicts(string $counselorId, string $date, string $time, ?int $excludeFollowUpId = null): array
    {
        // Get all pending follow-up sessions for the counselor on the specified date
        $builder = $this->where('preferred_date', $date)
                        ->where('counselor_id', $counselorId)
                        ->where('status', 'pending');
        
        if ($excludeFollowUpId !== null) {
            $builder->where('id !=', $excludeFollowUpId);
        }
        
        $followUpSessions = $builder->findAll();
        
        // Filter to only return sessions that overlap with the requested time
        $conflictingSessions = [];
        foreach ($followUpSessions as $session) {
            if ($this->timeRangesOverlap($time, $session['preferred_time'])) {
                $conflictingSessions[] = $session;
            }
        }
        
        return $conflictingSessions;
    }

    /**
     * Check if two time ranges overlap
     * 
     * @param string $timeRange1 Format: "HH:MM AM - HH:MM PM" or "HH:MM-HH:MM"
     * @param string $timeRange2 Format: "HH:MM AM - HH:MM PM" or "HH:MM-HH:MM"
     * @return bool True if ranges overlap, false otherwise
     */
    private function timeRangesOverlap(string $timeRange1, string $timeRange2): bool
    {
        // Convert both time ranges to minutes for comparison
        $range1_minutes = $this->convertToMinutesRange($timeRange1);
        $range2_minutes = $this->convertToMinutesRange($timeRange2);
        
        if (!$range1_minutes || !$range2_minutes) {
            // If conversion fails, fall back to exact string match
            return $timeRange1 === $timeRange2;
        }
        
        // Check for overlap: ranges overlap if one starts before the other ends
        return $range1_minutes['start'] < $range2_minutes['end'] && $range2_minutes['start'] < $range1_minutes['end'];
    }

    /**
     * Convert time range to minutes for comparison
     * 
     * @param string $timeRange Format: "HH:MM AM - HH:MM PM" or "HH:MM-HH:MM"
     * @return array|null Array with 'start' and 'end' in minutes since midnight, or null if conversion fails
     */
    private function convertToMinutesRange(string $timeRange): ?array
    {
        // Handle 12-hour format: "9:00 AM - 10:00 AM"
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $timeRange, $matches)) {
            $startHour = (int)$matches[1];
            $startMinute = (int)$matches[2];
            $startPeriod = strtoupper($matches[3]);
            $endHour = (int)$matches[4];
            $endMinute = (int)$matches[5];
            $endPeriod = strtoupper($matches[6]);
            
            // Convert to 24-hour format for minutes calculation
            if ($startPeriod === 'AM' && $startHour === 12) $startHour = 0;
            if ($startPeriod === 'PM' && $startHour !== 12) $startHour += 12;
            if ($endPeriod === 'AM' && $endHour === 12) $endHour = 0;
            if ($endPeriod === 'PM' && $endHour !== 12) $endHour += 12;
            
            return [
                'start' => ($startHour * 60) + $startMinute,
                'end' => ($endHour * 60) + $endMinute
            ];
        }
        
        // Handle 24-hour format: "09:00-10:00"
        if (preg_match('/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', $timeRange, $matches)) {
            $startHour = (int)$matches[1];
            $startMinute = (int)$matches[2];
            $endHour = (int)$matches[3];
            $endMinute = (int)$matches[4];
            
            return [
                'start' => ($startHour * 60) + $startMinute,
                'end' => ($endHour * 60) + $endMinute
            ];
        }
        
        return null;
    }
}