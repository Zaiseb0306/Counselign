<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Appointment Model
 * 
 * Handles all database operations for appointments table with ACID compliance.
 * Note: student_id is linked to users.user_id via foreign key.
 */
class AppointmentModel extends BaseModel
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'preferred_date',
        'preferred_time',
        'method_type',
        'consultation_type',
        'counselor_preference',
        'description',
        'reason',
        'status',
        'purpose'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Return type declarations for type safety
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'student_id' => 'required|max_length[10]',
        'preferred_date' => 'required|valid_date[Y-m-d]',
        'preferred_time' => 'required|max_length[50]',
        'method_type' => 'required|max_length[50]',
        'consultation_type' => 'permit_empty|in_list[Individual Consultation,Group Consultation]',
        'counselor_preference' => 'permit_empty|max_length[100]',
        'description' => 'permit_empty',
        'reason' => 'permit_empty',
        'status' => 'permit_empty|in_list[pending,approved,rejected,completed,cancelled]',
        'purpose' => 'required|in_list[Counseling,Psycho-Social Support,Initial Interview]'
    ];

    /**
     * Validation Messages
     */
    protected $validationMessages = [
        'student_id' => [
            'required' => 'Student ID is required'
        ],
        'preferred_date' => [
            'required' => 'Preferred date is required',
            'valid_date' => 'Invalid date format. Use YYYY-MM-DD'
        ],
        'preferred_time' => [
            'required' => 'Preferred time is required'
        ],
        'method_type' => [
            'required' => 'Consultation type is required'
        ],
        'status' => [
            'in_list' => 'Invalid status. Must be: pending, approved, rejected, completed, or cancelled'
        ],
        'purpose' => [
            'required' => 'Purpose of consultation is required',
            'in_list' => 'Invalid purpose. Must be: Counseling, Psycho-Social Support, or Initial Interview'
        ]
    ];

    /**
     * Get all appointments for a specific student
     * 
     * @param string $studentId
     * @return array
     */
    public function getByStudentId(string $studentId): array
    {
        return $this->where('student_id', $studentId)
                    ->orderBy('preferred_date', 'DESC')
                    ->orderBy('preferred_time', 'DESC')
                    ->findAll();
    }

    /**
     * Get appointments by status
     * 
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        return $this->where('status', $status)
                    ->orderBy('preferred_date', 'ASC')
                    ->orderBy('preferred_time', 'ASC')
                    ->findAll();
    }

    /**
     * Get pending appointments
     * 
     * @return array
     */
    public function getPendingAppointments(): array
    {
        return $this->getByStatus('pending');
    }

    /**
     * Get approved appointments
     * 
     * @return array
     */
    public function getApprovedAppointments(): array
    {
        return $this->getByStatus('approved');
    }

    /**
     * Get appointments for a specific counselor
     * 
     * @param string $counselorId
     * @return array
     */
    public function getByCounselor(string $counselorId): array
    {
        return $this->where('counselor_preference', $counselorId)
                    ->orderBy('preferred_date', 'DESC')
                    ->findAll();
    }

    /**
     * Get appointments with student information (JOIN)
     * 
     * @param int|null $appointmentId Optional specific appointment ID
     * @return array|null
     */
    public function getWithStudentInfo(?int $appointmentId = null)
    {
        $builder = $this->select('appointments.*, users.username, users.email, users.profile_picture')
                        ->join('users', 'appointments.student_id = users.user_id', 'left');
        
        if ($appointmentId !== null) {
            return $builder->where('appointments.id', $appointmentId)->first();
        }
        
        return $builder->orderBy('appointments.preferred_date', 'DESC')->findAll();
    }

    /**
     * Get appointments with both student and counselor info
     * 
     * @return array
     */
    public function getWithFullInfo(): array
    {
        return $this->select('
                appointments.*,
                users.username as student_name,
                users.email as student_email,
                counselors.name as counselor_name,
                counselors.degree as counselor_degree
            ')
            ->join('users', 'appointments.student_id = users.user_id', 'left')
            ->join('counselors', 'appointments.counselor_preference = counselors.counselor_id', 'left')
            ->orderBy('appointments.preferred_date', 'DESC')
            ->findAll();
    }

    /**
     * Get upcoming appointments (future dates only)
     * 
     * @param string|null $studentId Optional filter by student
     * @return array
     */
    public function getUpcomingAppointments(?string $studentId = null): array
    {
        $builder = $this->where('preferred_date >=', date('Y-m-d'))
                        ->whereIn('status', ['pending', 'approved'])
                        ->orderBy('preferred_date', 'ASC')
                        ->orderBy('preferred_time', 'ASC');
        
        if ($studentId !== null) {
            $builder->where('student_id', $studentId);
        }
        
        return $builder->findAll();
    }

    /**
     * Get past appointments
     * 
     * @param string|null $studentId Optional filter by student
     * @return array
     */
    public function getPastAppointments(?string $studentId = null): array
    {
        $builder = $this->where('preferred_date <', date('Y-m-d'))
                        ->orderBy('preferred_date', 'DESC');
        
        if ($studentId !== null) {
            $builder->where('student_id', $studentId);
        }
        
        return $builder->findAll();
    }

    /**
     * Get appointments for a specific date
     * 
     * @param string $date Format: YYYY-MM-DD
     * @return array
     */
    public function getByDate(string $date): array
    {
        return $this->where('preferred_date', $date)
                    ->orderBy('preferred_time', 'ASC')
                    ->findAll();
    }

    /**
     * Get appointments within date range
     * 
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate Format: YYYY-MM-DD
     * @return array
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->where('preferred_date >=', $startDate)
                    ->where('preferred_date <=', $endDate)
                    ->orderBy('preferred_date', 'ASC')
                    ->orderBy('preferred_time', 'ASC')
                    ->findAll();
    }

    /**
     * Update appointment status
     * 
     * @param int $appointmentId
     * @param string $status (pending, approved, rejected, completed, cancelled)
     * @return bool
     */
    public function updateStatus(int $appointmentId, string $status): bool
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->update($appointmentId, ['status' => $status]);
    }

    /**
     * Approve appointment
     * 
     * @param int $appointmentId
     * @return bool
     */
    public function approve(int $appointmentId): bool
    {
        return $this->updateStatus($appointmentId, 'approved');
    }

    /**
     * Reject appointment
     * 
     * @param int $appointmentId
     * @return bool
     */
    public function reject(int $appointmentId): bool
    {
        return $this->updateStatus($appointmentId, 'rejected');
    }

    /**
     * Mark appointment as completed
     * 
     * @param int $appointmentId
     * @return bool
     */
    public function complete(int $appointmentId): bool
    {
        return $this->updateStatus($appointmentId, 'completed');
    }

    /**
     * Cancel appointment
     * 
     * @param int $appointmentId
     * @return bool
     */
    public function cancel(int $appointmentId): bool
    {
        return $this->updateStatus($appointmentId, 'cancelled');
    }

    /**
	 * Check if student has an approved upcoming appointment
	 * 
	 * @param string $studentId
	 * @return bool
	 */
	public function hasApprovedAppointment(string $studentId): bool
	{
		return $this->where('student_id', $studentId)
					->where('status', 'approved')
					->where('preferred_date >=', date('Y-m-d'))
					->countAllResults() > 0;
	}

	/**
	 * Check if student has pending appointment
     * 
     * @param string $studentId
     * @return bool
     */
    public function hasPendingAppointment(string $studentId): bool
    {
        return $this->where('student_id', $studentId)
                    ->where('status', 'pending')
                    ->countAllResults() > 0;
    }

    /**
     * Check if time slot is available
     * 
     * @param string $date Format: YYYY-MM-DD
     * @param string $time
     * @param string|null $counselorId Optional counselor filter
     * @return bool
     */
    public function isTimeSlotAvailable(string $date, string $time, ?string $counselorId = null): bool
    {
        $builder = $this->where('preferred_date', $date)
                        ->where('preferred_time', $time)
                        ->whereIn('status', ['pending', 'approved']);
        
        if ($counselorId !== null && $counselorId !== 'No preference') {
            $builder->where('counselor_preference', $counselorId);
        }
        
        return $builder->countAllResults() === 0;
    }

    /**
     * Get appointment statistics for a student
     * 
     * @param string $studentId
     * @return array
     */
    public function getStudentStatistics(string $studentId): array
    {
        return [
            'total' => $this->where('student_id', $studentId)->countAllResults(),
            'pending' => $this->where('student_id', $studentId)->where('status', 'pending')->countAllResults(),
            'approved' => $this->where('student_id', $studentId)->where('status', 'approved')->countAllResults(),
            'completed' => $this->where('student_id', $studentId)->where('status', 'completed')->countAllResults(),
            'rejected' => $this->where('student_id', $studentId)->where('status', 'rejected')->countAllResults(),
            'cancelled' => $this->where('student_id', $studentId)->where('status', 'cancelled')->countAllResults()
        ];
    }

    /**
     * Get overall appointment statistics
     * 
     * @return array
     */
    public function getOverallStatistics(): array
    {
        return [
            'total' => $this->countAll(),
            'pending' => $this->where('status', 'pending')->countAllResults(),
            'approved' => $this->where('status', 'approved')->countAllResults(),
            'completed' => $this->where('status', 'completed')->countAllResults(),
            'rejected' => $this->where('status', 'rejected')->countAllResults(),
            'cancelled' => $this->where('status', 'cancelled')->countAllResults(),
            'today' => $this->where('preferred_date', date('Y-m-d'))->countAllResults(),
            'this_week' => $this->where('preferred_date >=', date('Y-m-d', strtotime('monday this week')))
                                ->where('preferred_date <=', date('Y-m-d', strtotime('sunday this week')))
                                ->countAllResults()
        ];
    }

    /**
     * Get appointments by method type (formerly consultation_type)
     */
    public function getByMethodType(string $methodType): array
    {
        return $this->where('method_type', $methodType)
                    ->orderBy('preferred_date', 'DESC')
                    ->findAll();
    }

    /**
     * Search appointments
     * 
     * @param string $searchTerm
     * @return array
     */
    public function search(string $searchTerm): array
    {
        return $this->select('appointments.*, users.username, users.email')
                    ->join('users', 'appointments.student_id = users.user_id', 'left')
                    ->groupStart()
                        ->like('appointments.student_id', $searchTerm)
                        ->orLike('appointments.method_type', $searchTerm)
                        ->orLike('appointments.counselor_preference', $searchTerm)
                        ->orLike('appointments.description', $searchTerm)
                        ->orLike('users.username', $searchTerm)
                        ->orLike('users.email', $searchTerm)
                    ->groupEnd()
                    ->orderBy('appointments.preferred_date', 'DESC')
                    ->findAll();
    }

    /**
     * Get recent appointments
     * 
     * @param int $limit Number of appointments to retrieve
     * @return array
     */
    public function getRecentAppointments(int $limit = 10): array
    {
        return $this->select('appointments.*, users.username, users.email')
                    ->join('users', 'appointments.student_id = users.user_id', 'left')
                    ->orderBy('appointments.created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Get appointments needing attention (pending + past date)
     * 
     * @return array
     */
    public function getAppointmentsNeedingAttention(): array
    {
        return $this->where('status', 'pending')
                    ->where('preferred_date <', date('Y-m-d'))
                    ->orderBy('preferred_date', 'ASC')
                    ->findAll();
    }

    /**
     * Delete old cancelled/rejected appointments (cleanup)
     * 
     * @param int $daysOld Number of days old
     * @return int Number of deleted records
     */
    public function deleteOldAppointments(int $daysOld = 90): int
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysOld} days"));
        
        $builder = $this->builder();
        $builder->whereIn('status', ['cancelled', 'rejected'])
                ->where('updated_at <', $cutoffDate);
        
        return $builder->delete();
    }

    /**
     * Check if appointment belongs to student
     * 
     * @param int $appointmentId
     * @param string $studentId
     * @return bool
     */
    public function belongsToStudent(int $appointmentId, string $studentId): bool
    {
        return $this->where('id', $appointmentId)
                    ->where('student_id', $studentId)
                    ->countAllResults() > 0;
    }

    /**
     * Get student's latest appointment
     * 
     * @param string $studentId
     * @return array|null
     */
    public function getLatestAppointment(string $studentId): ?array
    {
        return $this->where('student_id', $studentId)
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    /**
     * Callbacks - Before Insert
     */
    protected function beforeInsert(array $data): array
    {
        // Set default status if not provided
        if (!isset($data['data']['status'])) {
            $data['data']['status'] = 'pending';
        }
        
        // Set default counselor preference if not provided
        if (!isset($data['data']['counselor_preference']) || empty($data['data']['counselor_preference'])) {
            $data['data']['counselor_preference'] = 'No preference';
        }
        
        return $data;
    }

    /**
     * Check if counselor has conflicts on specific date/time
     * 
     * @param string $counselorId
     * @param string $date Format: YYYY-MM-DD
     * @param string $time
     * @param int|null $excludeAppointmentId Optional appointment ID to exclude from check (for updates)
     * @return bool True if conflict exists, false if available
     */
    public function hasCounselorConflict(string $counselorId, string $date, string $time, ?int $excludeAppointmentId = null): bool
    {
        // Get all pending/approved appointments for the counselor on the specified date
        $builder = $this->where('preferred_date', $date)
                        ->where('counselor_preference', $counselorId)
                        ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeAppointmentId !== null) {
            $builder->where('id !=', $excludeAppointmentId);
        }
        
        $appointments = $builder->findAll();
        
        // Check if any of the existing appointments overlap with the requested time
        foreach ($appointments as $appointment) {
            if ($this->timeRangesOverlap($time, $appointment['preferred_time'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get counselor conflicts on specific date/time
     * 
     * @param string $counselorId
     * @param string $date Format: YYYY-MM-DD
     * @param string $time
     * @param int|null $excludeAppointmentId Optional appointment ID to exclude from check
     * @return array Array of conflicting appointments
     */
    public function getCounselorConflicts(string $counselorId, string $date, string $time, ?int $excludeAppointmentId = null): array
    {
        // Get all pending/approved appointments for the counselor on the specified date
        $builder = $this->where('preferred_date', $date)
                        ->where('counselor_preference', $counselorId)
                        ->whereIn('status', ['pending', 'approved']);
        
        if ($excludeAppointmentId !== null) {
            $builder->where('id !=', $excludeAppointmentId);
        }
        
        $appointments = $builder->findAll();
        
        // Filter to only return appointments that overlap with the requested time
        $conflictingAppointments = [];
        foreach ($appointments as $appointment) {
            if ($this->timeRangesOverlap($time, $appointment['preferred_time'])) {
                $conflictingAppointments[] = $appointment;
            }
        }
        
        return $conflictingAppointments;
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

    /**
     * Callbacks - Before Update
     */
    protected function beforeUpdate(array $data): array
    {
        // Log status changes for audit trail
        if (isset($data['data']['status'])) {
            log_message('info', "Appointment status changed to: {$data['data']['status']}");
        }
        
        return $data;
    }

    // ========================================
    // ATOMIC OPERATIONS FOR ACID COMPLIANCE
    // ========================================

    /**
     * Create appointment with atomic validation and notification
     * 
     * @param array $appointmentData Appointment data
     * @param array $options Transaction options
     * @return array Result with appointment ID and status
     * @throws \Exception On validation or transaction failure
     */
    public function createAppointmentAtomic(array $appointmentData, array $options = []): array
    {
        // Validate appointment data
        $this->validateAtomicData($appointmentData);

        // Prepare atomic operations
        $operations = [
            $this->createAtomicOperation('validateAppointmentRules', [$appointmentData]),
            $this->createAtomicOperation('checkCounselorAvailability', [$appointmentData]),
            $this->createAtomicOperation('insert', [$appointmentData]),
            $this->createAtomicOperation('createAppointmentNotification', [$appointmentData])
        ];

        try {
            $results = $this->executeWithLocking(
                $operations,
                ['appointments', 'counselor_availability', 'notifications'],
                $options
            );

            $appointmentId = $results[2]['data'] ?? null;
            
            if (!$appointmentId) {
                throw new \Exception('Failed to create appointment');
            }

            $this->logAtomicOperation('createAppointment', $appointmentData, true);
            
            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'message' => 'Appointment created successfully'
            ];

        } catch (\Exception $e) {
            $this->logAtomicOperation('createAppointment', $appointmentData, false);
            throw new \Exception('Failed to create appointment: ' . $e->getMessage());
        }
    }

    /**
     * Update appointment status atomically with validation
     * 
     * @param int $appointmentId Appointment ID
     * @param string $newStatus New status
     * @param string|null $reason Reason for status change
     * @param array $options Transaction options
     * @return array Result of status update
     * @throws \Exception On validation or transaction failure
     */
    public function updateStatusAtomic(int $appointmentId, string $newStatus, ?string $reason = null, array $options = []): array
    {
        // Validate status change
        $validStatuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new \Exception('Invalid appointment status: ' . $newStatus);
        }

        // Prepare update data
        $updateData = ['status' => $newStatus];
        if ($reason !== null) {
            $updateData['reason'] = $reason;
        }

        // Prepare atomic operations
        $operations = [
            $this->createAtomicOperation('validateStatusChange', [$appointmentId, $newStatus]),
            $this->createAtomicOperation('update', [$appointmentId, $updateData]),
            $this->createAtomicOperation('createStatusChangeNotification', [$appointmentId, $newStatus, $reason])
        ];

        try {
            $results = $this->executeWithLocking(
                $operations,
                ['appointments', 'notifications'],
                $options
            );

            $this->logAtomicOperation('updateStatus', [
                'appointment_id' => $appointmentId,
                'new_status' => $newStatus,
                'reason' => $reason
            ], true);

            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'new_status' => $newStatus,
                'message' => 'Appointment status updated successfully'
            ];

        } catch (\Exception $e) {
            $this->logAtomicOperation('updateStatus', [
                'appointment_id' => $appointmentId,
                'new_status' => $newStatus
            ], false);
            throw new \Exception('Failed to update appointment status: ' . $e->getMessage());
        }
    }

    /**
     * Cancel appointment atomically with proper cleanup
     * 
     * @param int $appointmentId Appointment ID
     * @param string $reason Cancellation reason
     * @param array $options Transaction options
     * @return array Result of cancellation
     * @throws \Exception On validation or transaction failure
     */
    public function cancelAppointmentAtomic(int $appointmentId, string $reason, array $options = []): array
    {
        if (empty($reason)) {
            throw new \Exception('Cancellation reason is required');
        }

        // Prepare atomic operations
        $operations = [
            $this->createAtomicOperation('validateCancellation', [$appointmentId]),
            $this->createAtomicOperation('update', [$appointmentId, ['status' => 'cancelled', 'reason' => $reason]]),
            $this->createAtomicOperation('createCancellationNotification', [$appointmentId, $reason]),
            $this->createAtomicOperation('releaseCounselorAvailability', [$appointmentId])
        ];

        try {
            $results = $this->executeWithLocking(
                $operations,
                ['appointments', 'notifications', 'counselor_availability'],
                $options
            );

            $this->logAtomicOperation('cancelAppointment', [
                'appointment_id' => $appointmentId,
                'reason' => $reason
            ], true);

            return [
                'success' => true,
                'appointment_id' => $appointmentId,
                'message' => 'Appointment cancelled successfully'
            ];

        } catch (\Exception $e) {
            $this->logAtomicOperation('cancelAppointment', [
                'appointment_id' => $appointmentId,
                'reason' => $reason
            ], false);
            throw new \Exception('Failed to cancel appointment: ' . $e->getMessage());
        }
    }

    // ========================================
    // ATOMIC OPERATION HELPER METHODS
    // ========================================

    /**
     * Validate appointment business rules
     * 
     * @param array $appointmentData
     * @return bool
     * @throws \Exception
     */
    public function validateAppointmentRules(array $appointmentData): bool
    {
        // Check if student already has pending appointment
        if ($this->hasPendingAppointment($appointmentData['student_id'])) {
            throw new \Exception('Student already has a pending appointment');
        }

        // Check if student already has approved upcoming appointment
        if ($this->hasApprovedAppointment($appointmentData['student_id'])) {
            throw new \Exception('Student already has an approved upcoming appointment');
        }

        // Validate date is not in the past
        if (strtotime($appointmentData['preferred_date']) < strtotime(date('Y-m-d'))) {
            throw new \Exception('Appointment date cannot be in the past');
        }

        return true;
    }

    /**
     * Check counselor availability with row locking
     * 
     * @param array $appointmentData
     * @return bool
     * @throws \Exception
     */
    public function checkCounselorAvailability(array $appointmentData): bool
    {
        if ($appointmentData['counselor_preference'] === 'No preference') {
            return true; // No specific counselor selected
        }

        // Check for conflicts with row locking
        $conflicts = $this->getCounselorConflicts(
            $appointmentData['counselor_preference'],
            $appointmentData['preferred_date'],
            $appointmentData['preferred_time']
        );

        if (!empty($conflicts)) {
            throw new \Exception('Counselor is not available at the requested time');
        }

        return true;
    }

    /**
     * Validate status change is allowed
     * 
     * @param int $appointmentId
     * @param string $newStatus
     * @return bool
     * @throws \Exception
     */
    public function validateStatusChange(int $appointmentId, string $newStatus): bool
    {
        $appointment = $this->find($appointmentId);
        if (!$appointment) {
            throw new \Exception('Appointment not found');
        }

        $currentStatus = $appointment['status'];
        
        // Define allowed status transitions
        $allowedTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['completed', 'cancelled'],
            'rejected' => [], // Terminal state
            'completed' => [], // Terminal state
            'cancelled' => [] // Terminal state
        ];

        if (!in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            throw new \Exception("Cannot change status from {$currentStatus} to {$newStatus}");
        }

        return true;
    }

    /**
     * Validate cancellation is allowed
     * 
     * @param int $appointmentId
     * @return bool
     * @throws \Exception
     */
    public function validateCancellation(int $appointmentId): bool
    {
        $appointment = $this->find($appointmentId);
        if (!$appointment) {
            throw new \Exception('Appointment not found');
        }

        if (!in_array($appointment['status'], ['pending', 'approved'])) {
            throw new \Exception('Only pending or approved appointments can be cancelled');
        }

        return true;
    }

    /**
     * Create appointment notification
     * 
     * @param array $appointmentData
     * @return bool
     */
    public function createAppointmentNotification(array $appointmentData): bool
    {
        // This would integrate with your notification system
        // For now, just log the notification
        log_message('info', "Appointment notification created for student: {$appointmentData['student_id']}");
        return true;
    }

    /**
     * Create status change notification
     * 
     * @param int $appointmentId
     * @param string $newStatus
     * @param string|null $reason
     * @return bool
     */
    public function createStatusChangeNotification(int $appointmentId, string $newStatus, ?string $reason = null): bool
    {
        log_message('info', "Status change notification created for appointment {$appointmentId}: {$newStatus}");
        return true;
    }

    /**
     * Create cancellation notification
     * 
     * @param int $appointmentId
     * @param string $reason
     * @return bool
     */
    public function createCancellationNotification(int $appointmentId, string $reason): bool
    {
        log_message('info', "Cancellation notification created for appointment {$appointmentId}: {$reason}");
        return true;
    }

    /**
     * Release counselor availability after cancellation
     * 
     * @param int $appointmentId
     * @return bool
     */
    public function releaseCounselorAvailability(int $appointmentId): bool
    {
        // This would update counselor availability if needed
        log_message('info', "Counselor availability released for appointment {$appointmentId}");
        return true;
    }
}