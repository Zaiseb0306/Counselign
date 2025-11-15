<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class CounselorInfo extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Load the counselor information view
        return view('admin/counselor_info');
    }

    public function createAnnouncement()
    {
        // Handle announcement creation
        if ($this->request->getMethod() === 'post') {
            // Add your announcement creation logic here
            return redirect()->to('admin/counselor-info')->with('success', 'Announcement created successfully');
        }
    }

    /**
     * Get counselor's availability schedule by counselor_id
     * Returns the counselor's available days and time slots
     */
    public function getCounselorSchedule()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as admin',
                'schedule' => []
            ], 401);
        }

        $counselor_id = $this->request->getGet('counselor_id');
        
        if (!$counselor_id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Counselor ID is required',
                'schedule' => []
            ], 400);
        }

        try {
            $db = \Config\Database::connect();

            // Get counselor's availability from counselor_availability table
            $query = "SELECT available_days, time_scheduled 
                      FROM counselor_availability 
                      WHERE counselor_id = ? 
                      ORDER BY FIELD(available_days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')";

            $results = $db->query($query, [$counselor_id])->getResultArray();

            if (empty($results)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'No schedule found for counselor',
                    'schedule' => []
                ], 404);
            }

            $schedule = [];
            
            // Process each availability entry
            foreach ($results as $result) {
                $day = trim($result['available_days']);
                $time_scheduled = $result['time_scheduled'] ?? null;
                
                if (!empty($day)) {
                    $schedule[] = [
                        'day' => $day,
                        'time' => $time_scheduled
                    ];
                }
            }

            return $this->respond([
                'status' => 'success',
                'schedule' => $schedule
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Admin\\CounselorInfo::getCounselorSchedule] Error: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'An error occurred while fetching schedule: ' . $e->getMessage(),
                'schedule' => []
            ], 500);
        }
    }
} 