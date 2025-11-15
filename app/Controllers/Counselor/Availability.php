<?php

namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Models\CounselorAvailabilityModel;

class Availability extends BaseController
{
    /**
     * Validate 12-hour format time string
     * @param string $timeStr Time string in format "H:MM AM/PM" or "HH:MM AM/PM"
     * @return bool
     */
    private function isValid12HourFormat($timeStr)
    {
        return preg_match('/^(1[0-2]|[1-9]):[0-5][0-9]\s*(AM|PM)$/i', trim($timeStr));
    }

    /**
     * Compare two 12-hour format times for ordering
     * @param string $time1 First time string in format "H:MM AM/PM"
     * @param string $time2 Second time string in format "H:MM AM/PM"
     * @return int -1 if time1 < time2, 0 if equal, 1 if time1 > time2
     */
    private function compare12HourTimes($time1, $time2)
    {
        $time1Minutes = $this->timeToMinutes($time1);
        $time2Minutes = $this->timeToMinutes($time2);
        
        if ($time1Minutes < $time2Minutes) return -1;
        if ($time1Minutes > $time2Minutes) return 1;
        return 0;
    }
    
    /**
     * Convert 12-hour format time to minutes for comparison
     * @param string $timeStr Time string in format "H:MM AM/PM"
     * @return int Minutes since midnight
     */
    private function timeToMinutes($timeStr)
    {
        $timeStr = trim($timeStr);
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $timeStr, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $ampm = strtoupper($matches[3]);
            
            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return ($hour * 60) + $minute;
        }
        return 0;
    }
    public function get()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        // Optional: allow fetching availability for a specific counselor by ID
        // Falls back to the logged-in counselor when not provided (preserves existing behavior)
        $requestedCounselorId = trim((string) ($this->request->getGet('counselorId') ?? ''));
        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        if ($requestedCounselorId !== '') {
            $targetCounselorId = $requestedCounselorId;
        } else {
            if (!$userId) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid session']);
            }
            $targetCounselorId = $userId;
        }

        $model = new CounselorAvailabilityModel();
        $grouped = $model->getGroupedByDay($targetCounselorId);

        return $this->response->setJSON([
            'success' => true,
            'availability' => $grouped,
            'counselorId' => $targetCounselorId,
        ]);
    }

    public function save()
    {
        // Allow preflight
        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setJSON(['success' => true]);
        }

        $session = session();
        if (!$session->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid session']);
        }

        $payload = $this->request->getJSON(true);
        if (!$payload) {
            $payload = $this->request->getPost();
        }

        // Expected format: { days: ["Monday",...], timesByDay: { "Monday": ["1:30 PM-3:00 PM", ...] } }
        $days = isset($payload['days']) && is_array($payload['days']) ? $payload['days'] : [];
        $timesByDay = isset($payload['timesByDay']) && is_array($payload['timesByDay']) ? $payload['timesByDay'] : [];

        $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        $cleanDays = array_values(array_intersect($validDays, array_map('strval', $days)));

        // Handle 12-hour format time ranges directly
        $slots = [];
        foreach ($cleanDays as $day) {
            $times = isset($timesByDay[$day]) && is_array($timesByDay[$day]) ? $timesByDay[$day] : [];
            
            if (empty($times) || (count($times) === 1 && ($times[0] === null || $times[0] === ''))) {
                // No times provided -> insert NULL time for the day (all-day)
                $slots[] = ['day' => $day, 'time' => null, 'range' => null];
                continue;
            }

            // Handle 12-hour format time ranges
            foreach ($times as $timeStr) {
                if ($timeStr === null || $timeStr === '') continue;
                
                $timeStr = trim((string)$timeStr);
                
                // Check if it's already a range format (e.g., "1:30 PM-3:00 PM")
                if (strpos($timeStr, '-') !== false) {
                    $parts = explode('-', $timeStr, 2);
                    if (count($parts) === 2) {
                        $from = trim($parts[0]);
                        $to = trim($parts[1]);
                        
                        // Validate 12-hour format
                        if ($this->isValid12HourFormat($from) && $this->isValid12HourFormat($to)) {
                            $slots[] = [
                                'day' => $day, 
                                'time' => $from . '-' . $to, 
                                'range' => ['from' => $from, 'to' => $to]
                            ];
                        }
                    }
                }
            }
            
            // If no valid ranges found for this day, add null entry
            if (empty(array_filter($slots, function($slot) use ($day) { return $slot['day'] === $day; }))) {
                $slots[] = ['day' => $day, 'time' => null, 'range' => null];
            }
        }

        // If nothing to save, just clear existing and succeed
        $model = new CounselorAvailabilityModel();

        // Ensure counselor profile exists to satisfy FK constraint
        try {
            $db = \Config\Database::connect();
            $exists = $db->table('counselors')->where('counselor_id', $userId)->countAllResults() > 0;
            if (!$exists) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please save your Personal Information first (counselor profile not found).'
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Availability counselor precheck error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Server error while validating profile']);
        }

        if (empty($slots)) {
            return $this->response->setJSON(['success' => true]);
        }

        // Replace without explicit transaction to avoid false negatives on some MySQL configs
        try {
            // Append new ranges without overriding existing ones
            $inserted = 0;
            foreach ($cleanDays as $day) {
                // Collect ranges for this day from $slots where time not null
                $ranges = [];
                foreach ($slots as $s) {
                    if ($s['day'] === $day && isset($s['range']) && $s['range']) {
                        $ranges[] = $s['range'];
                    }
                }
                if (!empty($ranges)) {
                    $inserted += $model->addRangesIfNotExist($userId, $day, $ranges);
                }
            }
            return $this->response->setJSON(['success' => true, 'inserted' => $inserted]);
        } catch (\Throwable $e) {
            log_message('error', 'Availability save exception: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save availability',
                'code' => $e->getCode(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * DELETE /counselor/profile/availability
     * Body JSON: { day: "Monday", from: "HH:MM", to: "HH:MM" }
     */
    public function delete()
    {
        // Allow preflight
        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setJSON(['success' => true]);
        }

        $session = session();
        if (!$session->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid session']);
        }

        $payload = $this->request->getJSON(true);
        if (!$payload) {
            $payload = $this->request->getPost();
        }

        $day = isset($payload['day']) ? (string) $payload['day'] : '';
        $from = isset($payload['from']) ? (string) $payload['from'] : '';
        $to = isset($payload['to']) ? (string) $payload['to'] : '';

        $validDays = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        if (!in_array($day, $validDays, true)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Invalid day']);
        }
        // Validate 12-hour format
        if (!$this->isValid12HourFormat($from) || !$this->isValid12HourFormat($to)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'Invalid time format. Expected format: H:MM AM/PM']);
        }
        
        // Compare 12-hour format times directly
        if ($this->compare12HourTimes($from, $to) >= 0) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'From must be earlier than To']);
        }

        try {
            $model = new CounselorAvailabilityModel();
            // Use 12-hour format directly for deletion
            $timeStr = $from . '-' . $to;
            $ok = $model->where('counselor_id', $userId)
                        ->where('available_days', $day)
                        ->where('time_scheduled', $timeStr)
                        ->delete();
            
            if (!$ok) {
                return $this->response->setJSON(['success' => false, 'message' => 'No matching slot found to delete']);
            }
            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Availability delete exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete availability']);
        }
    }
}


