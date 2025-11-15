<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

/**
 * Counselor Availability Model
 * 
 * Handles all database operations for counselor_availability table.
 * Manages counselor schedules with multiple days and time slots.
 */
class CounselorAvailabilityModel extends Model
{
    protected $table = 'counselor_availability';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'counselor_id',
        'available_days',
        'time_scheduled'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null; // No updated_at in your schema

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    /**
     * Validation Rules
     */
    protected $validationRules = [
        'counselor_id' => 'required|max_length[10]',
        'available_days' => 'required|in_list[Monday,Tuesday,Wednesday,Thursday,Friday]',
        'time_scheduled' => 'permit_empty|max_length[255]'
    ];

    /**
     * Validation Messages
     */
    protected $validationMessages = [
        'counselor_id' => [
            'required' => 'Counselor ID is required'
        ],
        'available_days' => [
            'required' => 'Available day is required',
            'in_list' => 'Invalid day selected. Must be Monday-Friday'
        ]
    ];

    /**
     * Get all availability slots for a counselor
     * 
     * @param string $counselorId
     * @return array
     */
    public function getByCounselorId(string $counselorId): array
    {
        return $this->where('counselor_id', $counselorId)
                    ->orderBy('FIELD(available_days, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday")')
                    ->findAll();
    }

    /**
     * Get availability grouped by day for a counselor
     * 
     * @param string $counselorId
     * @return array ['Monday' => [...], 'Tuesday' => [...]]
     */
    public function getGroupedByDay(string $counselorId): array
    {
        $availabilities = $this->getByCounselorId($counselorId);
        $grouped = [];

        foreach ($availabilities as $slot) {
            $day = $slot['available_days'];
            if (!isset($grouped[$day])) {
                $grouped[$day] = [];
            }
            $grouped[$day][] = $slot;
        }

        return $grouped;
    }

    /**
     * Get availability for a specific day
     * 
     * @param string $counselorId
     * @param string $day
     * @return array
     */
    public function getByDay(string $counselorId, string $day): array
    {
        return $this->where('counselor_id', $counselorId)
                    ->where('available_days', $day)
                    ->findAll();
    }

    /**
     * Get all counselors available on a specific day
     * 
     * @param string $day
     * @return array
     */
    public function getCounselorsAvailableOnDay(string $day): array
    {
        return $this->select('DISTINCT counselor_id')
                    ->where('available_days', $day)
                    ->findAll();
    }

    /**
     * Check if counselor is available on a specific day and time
     * 
     * @param string $counselorId
     * @param string $day
     * @param string $time
     * @return bool
     */
    public function isAvailable(string $counselorId, string $day, string $time): bool
    {
        return $this->where('counselor_id', $counselorId)
                    ->where('available_days', $day)
                    ->where('time_scheduled', $time)
                    ->countAllResults() > 0;
    }

    /**
     * Add availability slot for counselor
     * 
     * @param string $counselorId
     * @param string $day
     * @param string|null $time
     * @return int|bool Insert ID or false
     */
    public function addSlot(string $counselorId, string $day, ?string $time = null)
    {
        // Check for duplicate
        if ($this->isDuplicate($counselorId, $day, $time)) {
            return false;
        }

        return $this->insert([
            'counselor_id' => $counselorId,
            'available_days' => $day,
            'time_scheduled' => $time
        ]);
    }

    /**
     * Bulk insert availability slots
     * 
     * @param string $counselorId
     * @param array $slots [['day' => 'Monday', 'time' => '9:00-10:00'], ...]
     * @return bool
     */
    public function addMultipleSlots(string $counselorId, array $slots): bool
    {
        $data = [];
        foreach ($slots as $slot) {
            // Skip duplicates
            if (!$this->isDuplicate($counselorId, $slot['day'], $slot['time'] ?? null)) {
                $data[] = [
                    'counselor_id' => $counselorId,
                    'available_days' => $slot['day'],
                    'time_scheduled' => $slot['time'] ?? null
                ];
            }
        }

        if (empty($data)) {
            return false;
        }

        return $this->insertBatch($data) !== false;
    }

    /**
     * Update availability slot
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSlot(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Delete specific availability slot
     * 
     * @param int $id
     * @return bool
     */
    public function deleteSlot(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Delete all availability for a counselor
     * 
     * @param string $counselorId
     * @return bool
     */
    public function deleteAllByCounselor(string $counselorId): bool
    {
        return $this->where('counselor_id', $counselorId)->delete();
    }

    /**
     * Delete availability for specific day
     * 
     * @param string $counselorId
     * @param string $day
     * @return bool
     */
    public function deleteByDay(string $counselorId, string $day): bool
    {
        return $this->where('counselor_id', $counselorId)
                    ->where('available_days', $day)
                    ->delete();
    }

    /**
     * Replace all availability for a counselor (delete + insert)
     * 
     * @param string $counselorId
     * @param array $slots
     * @return bool
     */
    public function replaceAll(string $counselorId, array $slots): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Delete existing
        $this->deleteAllByCounselor($counselorId);

        // Insert new
        $this->addMultipleSlots($counselorId, $slots);

        $db->transComplete();

        return $db->transStatus();
    }

    /**
     * Check for duplicate slot
     * 
     * @param string $counselorId
     * @param string $day
     * @param string|null $time
     * @return bool
     */
    protected function isDuplicate(string $counselorId, string $day, ?string $time): bool
    {
        $builder = $this->where('counselor_id', $counselorId)
                        ->where('available_days', $day);

        if ($time === null) {
            $builder->where('time_scheduled IS NULL');
        } else {
            $builder->where('time_scheduled', $time);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Get unique days counselor is available
     * 
     * @param string $counselorId
     * @return array
     */
    public function getUniqueDays(string $counselorId): array
    {
        $results = $this->select('DISTINCT available_days')
                        ->where('counselor_id', $counselorId)
                        ->orderBy('FIELD(available_days, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday")')
                        ->findAll();

        return array_column($results, 'available_days');
    }

    /**
     * Get all time slots for a counselor on a specific day
     * 
     * @param string $counselorId
     * @param string $day
     * @return array
     */
    public function getTimeSlots(string $counselorId, string $day): array
    {
        $results = $this->select('time_scheduled')
                        ->where('counselor_id', $counselorId)
                        ->where('available_days', $day)
                        ->where('time_scheduled IS NOT NULL')
                        ->findAll();

        return array_column($results, 'time_scheduled');
    }

    /**
     * Count availability slots for counselor
     * 
     * @param string $counselorId
     * @return int
     */
    public function countSlots(string $counselorId): int
    {
        return $this->where('counselor_id', $counselorId)->countAllResults();
    }

    /**
     * Delete a specific consolidated time range for a counselor on a given day.
     * Expected $from and $to in HH:MM 24h format, matching time_scheduled "HH:MM-HH:MM".
     *
     * @param string $counselorId
     * @param string $day
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function deleteByDayAndRange(string $counselorId, string $day, string $from, string $to): bool
    {
        $timeStr = sprintf('%s-%s', $from, $to);
        return $this->where('counselor_id', $counselorId)
                    ->where('available_days', $day)
                    ->where('time_scheduled', $timeStr)
                    ->delete() !== false;
    }

    /**
     * Replace all availability rows for a counselor using per-row inserts.
     * This avoids issues seen with insertBatch on some environments.
     *
     * @param string $counselorId
     * @param array $rows Each item: ['day' => 'Monday', 'time' => '09:00'|null]
     * @return bool
     */
    public function replaceAllRows(string $counselorId, array $rows): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        // Clear existing
        $this->where('counselor_id', $counselorId)->delete();

        $builder = $db->table($this->table);
        foreach ($rows as $r) {
            $day = isset($r['day']) ? (string) $r['day'] : '';
            $time = array_key_exists('time', $r) ? $r['time'] : null;
            if ($time === '' || $time === 'null') { $time = null; }
            $ok = $builder->insert([
                'counselor_id' => $counselorId,
                'available_days' => $day,
                'time_scheduled' => $time,
            ]);
            if ($ok === false) {
                $db->transRollback();
                return false;
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Add ranges if not existing (no override).
     * time_scheduled should be a consolidated string like "HH:MM-HH:MM" or NULL.
     *
     * @param string $counselorId
     * @param string $day
     * @param array $ranges Each: ['from' => 'HH:MM', 'to' => 'HH:MM']
     * @return int number of inserted rows
     */
    public function addRangesIfNotExist(string $counselorId, string $day, array $ranges): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);
        $inserted = 0;
        foreach ($ranges as $r) {
            $from = isset($r['from']) ? (string) $r['from'] : '';
            $to = isset($r['to']) ? (string) $r['to'] : '';
            $timeStr = null;
            if ($from !== '' && $to !== '') {
                $timeStr = sprintf('%s-%s', $from, $to);
            }

            $exists = $this->where('counselor_id', $counselorId)
                           ->where('available_days', $day)
                           ->where('time_scheduled', $timeStr)
                           ->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $ok = $builder->insert([
                'counselor_id' => $counselorId,
                'available_days' => $day,
                'time_scheduled' => $timeStr,
            ]);
            if ($ok !== false) {
                $inserted++;
            }
        }
        return $inserted;
    }
}