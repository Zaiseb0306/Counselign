<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CounselorModel;
use App\Models\CounselorAvailabilityModel;
use CodeIgniter\API\ResponseTrait;

class AdminsManagement extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }
        
        return view('admin/admins_management');
    }

    public function viewUsers()
    {
        return view('admin/view_users');
    }

    public function accountSettings()
    {
        return view('admin/account_settings');
    }

    /**
     * Get counselor schedules for admin management table
     * Returns all counselors with their availability data organized by day
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getCounselorSchedules()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access - Please log in as administrator',
                'schedules' => []
            ], 401);
        }

        try {
            $counselorModel = new CounselorModel();
            $availabilityModel = new CounselorAvailabilityModel();

            // Get all active counselors
            $counselors = $counselorModel->getActiveCounselors();
            
            if (empty($counselors)) {
                return $this->respond([
                    'success' => true,
                    'message' => 'No counselors found',
                    'schedules' => []
                ]);
            }

            // Organize schedules by day
            $scheduleData = [
                'Monday' => [],
                'Tuesday' => [],
                'Wednesday' => [],
                'Thursday' => [],
                'Friday' => []
            ];

            // Process each counselor's availability
            foreach ($counselors as $counselor) {
                $counselorId = $counselor['counselor_id'];
                $counselorName = $counselor['name'];
                $counselorDegree = $counselor['degree'];
                
                // Get counselor's availability grouped by day
                $availability = $availabilityModel->getGroupedByDay($counselorId);
                
                // Add counselor to each day they're available
                foreach ($availability as $day => $slots) {
                    if (isset($scheduleData[$day])) {
                        $timeSlots = [];
                        foreach ($slots as $slot) {
                            if (!empty($slot['time_scheduled'])) {
                                $timeSlots[] = $slot['time_scheduled'];
                            }
                        }
                        
                        $scheduleData[$day][] = [
                            'counselor_id' => $counselorId,
                            'name' => $counselorName,
                            'degree' => $counselorDegree,
                            'profile_picture' => $counselor['profile_picture'] ?? null,
                            'time_slots' => $timeSlots,
                            'display_name' => $counselorName . ', ' . $counselorDegree
                        ];
                    }
                }
            }

            // Sort counselors by name within each day
            foreach ($scheduleData as $day => &$counselors) {
                usort($counselors, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }

            return $this->respond([
                'success' => true,
                'message' => 'Counselor schedules retrieved successfully',
                'schedules' => $scheduleData,
                'total_counselors' => count($counselors)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching counselor schedules: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'message' => 'Error retrieving counselor schedules',
                'schedules' => []
            ], 500);
        }
    }

    /**
     * Get counselors available at a specific time and day
     * Returns counselors who are available for the specified time slot
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getCounselorsByTimeSlot()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond([
                'success' => false,
                'message' => 'Unauthorized access - Please log in as administrator',
                'counselors' => []
            ], 401);
        }

        try {
            $day = $this->request->getGet('day');
            $time = $this->request->getGet('time');

            // Validate required parameters
            if (empty($day) || empty($time)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Day and time parameters are required',
                    'counselors' => []
                ], 400);
            }

            // Validate day
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            if (!in_array($day, $validDays)) {
                return $this->respond([
                    'success' => false,
                    'message' => 'Invalid day. Must be Monday through Friday',
                    'counselors' => []
                ], 400);
            }

            $counselorModel = new CounselorModel();
            $availabilityModel = new CounselorAvailabilityModel();

            // Get all counselors
            $counselors = $counselorModel->getActiveCounselors();
            $availableCounselors = [];

            // Check each counselor's availability for the specific time and day
            foreach ($counselors as $counselor) {
                $counselorId = $counselor['counselor_id'];
                
                // Get counselor's availability for the specific day
                $dayAvailability = $availabilityModel->getByDay($counselorId, $day);
                
                // Check if counselor is available at the specific time
                $isAvailable = false;
                $timeSlots = [];
                
                foreach ($dayAvailability as $slot) {
                    $timeSlots[] = $slot['time_scheduled'];
                    
                    // Check if the requested time matches any of the counselor's time slots
                    if ($this->isTimeSlotAvailable($time, $slot['time_scheduled'])) {
                        $isAvailable = true;
                    }
                }
                
                if ($isAvailable) {
                    $availableCounselors[] = [
                        'counselor_id' => $counselorId,
                        'name' => $counselor['name'],
                        'degree' => $counselor['degree'],
                        'email' => $counselor['email'],
                        'contact_number' => $counselor['contact_number'],
                        'profile_picture' => $counselor['profile_picture'] ?? null,
                        'time_slots' => $timeSlots,
                        'display_name' => $counselor['name'] . ', ' . $counselor['degree']
                    ];
                }
            }

            // Sort counselors by name
            usort($availableCounselors, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $this->respond([
                'success' => true,
                'message' => 'Available counselors retrieved successfully',
                'counselors' => $availableCounselors,
                'day' => $day,
                'time' => $time,
                'total_available' => count($availableCounselors)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error fetching counselors by time slot: ' . $e->getMessage());
            
            return $this->respond([
                'success' => false,
                'message' => 'Error retrieving available counselors',
                'counselors' => []
            ], 500);
        }
    }

    /**
     * Check if a specific time is available in a counselor's time slot
     * Handles various time slot formats including 12-hour format
     * 
     * @param string $requestedTime The time to check (HH:MM format)
     * @param string|null $timeSlot The counselor's time slot
     * @return bool
     */
    private function isTimeSlotAvailable(string $requestedTime, ?string $timeSlot): bool
    {
        if (empty($timeSlot)) {
            // If no specific time slot, counselor is available all day
            return true;
        }

        // Handle time range format (e.g., "09:00-17:00" or "9:00 AM-5:00 PM")
        if (strpos($timeSlot, '-') !== false) {
            list($startTime, $endTime) = explode('-', $timeSlot);
            $startTime = trim($startTime);
            $endTime = trim($endTime);
            
            // Convert to minutes for comparison
            $requestedMinutes = $this->timeToMinutes($requestedTime);
            $startMinutes = $this->timeToMinutes($startTime);
            $endMinutes = $this->timeToMinutes($endTime);
            
            if ($requestedMinutes !== null && $startMinutes !== null && $endMinutes !== null) {
                return $requestedMinutes >= $startMinutes && $requestedMinutes <= $endMinutes;
            }
            
            // Fallback to string comparison for exact matches
            return $requestedTime >= $startTime && $requestedTime <= $endTime;
        }

        // Handle single time format (e.g., "09:00" or "9:00 AM")
        if ($timeSlot === $requestedTime) {
            return true;
        }

        // Handle comma-separated times (e.g., "09:00,10:00,11:00")
        if (strpos($timeSlot, ',') !== false) {
            $times = array_map('trim', explode(',', $timeSlot));
            return in_array($requestedTime, $times);
        }

        return false;
    }

    /**
     * Convert time string to minutes since midnight
     * Handles both "HH:MM" (24-hour) and "H:MM AM/PM" (12-hour) formats
     */
    private function timeToMinutes(?string $time): ?int
    {
        if ($time === null || $time === '') {
            return null;
        }
        
        $time = trim($time);
        
        // Handle 12-hour format: "1:30 PM" or "12:00 AM"
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            $ampm = strtoupper($matches[3]);
            
            // Convert to 24-hour format
            if ($ampm === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            
            return ($hour * 60) + $minute;
        }
        
        // Handle 24-hour format: "13:30"
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];
            return ($hour * 60) + $minute;
        }
        
        return null;
    }
}
