<?php

namespace App\Controllers\Counselor;

use App\Controllers\BaseController;

class GetAllAppointments extends BaseController
{
    public function index()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $response = [
            'success' => false,
            'message' => '',
            'appointments' => [],
            'labels' => [],
            'completed' => [],
            'approved' => [],
            'rejected' => [],
            'pending' => [],
            'cancelled' => [],
            'totalCompleted' => 0,
            'totalApproved' => 0,
            'totalRejected' => 0,
            'totalPending' => 0,
            'totalCancelled' => 0,
            'monthlyCompleted' => array_fill(0, 12, 0),
            'monthlyApproved' => array_fill(0, 12, 0),
            'monthlyRejected' => array_fill(0, 12, 0),
            'monthlyPending' => array_fill(0, 12, 0),
            'monthlyCancelled' => array_fill(0, 12, 0)
        ];

        try {
            $session = session();
            $loggedIn = $session->get('logged_in');
            $role = $session->get('role');
            $userId = session()->get('user_id_display') ?? session()->get('user_id');
            
            // Log session data for debugging
            log_message('debug', 'GetAllAppointments - Session check: logged_in=' . ($loggedIn ? 'true' : 'false') . ', role=' . $role . ', user_id=' . $userId);
            
            if (!$loggedIn) {
                throw new \Exception('User not logged in');
            }
            
            if ($role !== 'counselor') {
                throw new \Exception('User does not have counselor role. Current role: ' . $role);
            }

            // Get counselor name for the logged-in counselor
            $counselorModel = new \App\Models\CounselorModel();
            $counselor = $counselorModel->getByCounselorId($userId);
            $counselorName = $counselor ? $counselor['name'] : 'Unknown Counselor';

            $timeRange = $this->request->getGet('timeRange') ?? 'weekly';
            $db = \Config\Database::connect();

            // Filter appointments by logged-in counselor (include NULL counselor_preference appointments)
            $counselorFilter = " WHERE (appointments.counselor_preference = " . $db->escape($userId) . " OR appointments.counselor_preference IS NULL)";

            $baseQuery = "SELECT
                        appointments.student_id as user_id,
                        COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), NULL) AS student_name,
                        appointments.preferred_date as appointed_date,
                        appointments.preferred_time as appointed_time,
                        appointments.consultation_type,
                        appointments.purpose,
                        c.name as counselor_name,
                        appointments.status, appointments.reason,
                        MONTH(appointments.preferred_date) as month
                      FROM appointments
LEFT JOIN counselors c ON appointments.counselor_preference = c.counselor_id
LEFT JOIN student_personal_info spi ON spi.student_id = appointments.student_id";

            $allAppointmentsQuery = $baseQuery . $counselorFilter . " ORDER BY preferred_date ASC, preferred_time ASC";
            
            // Debug logging
            log_message('debug', 'GetAllAppointments - User ID: ' . $userId);
            log_message('debug', 'GetAllAppointments - Query: ' . $allAppointmentsQuery);
            
            $allAppointments = $db->query($allAppointmentsQuery)->getResultArray();
            
            log_message('debug', 'GetAllAppointments - Appointments found: ' . count($allAppointments));
            
            // Log status breakdown for debugging
            $statusCounts = ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0];
            foreach ($allAppointments as $appointment) {
                $status = strtolower($appointment['status']);
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }
            log_message('debug', 'GetAllAppointments - Status breakdown: ' . json_encode($statusCounts));
            
            $response['appointments'] = $allAppointments;

            $dateFilter = "";
            $startDateStr = null;
            $endDateStr = null;

            switch ($timeRange) {
                case 'daily':
                    $currentDate = new \DateTime();
                    $startDate = clone $currentDate; while ($startDate->format('N') != 1) { $startDate->modify('-1 day'); }
                    $endDate = clone $startDate; $endDate->modify('+6 days');
                    $startDateStr = $startDate->format('Y-m-d');
                    $endDateStr = $endDate->format('Y-m-d');
                    $dateFilter = " AND preferred_date >= '$startDateStr' AND preferred_date <= '$endDateStr'";
                    break;
                case 'weekly':
                    $currentDate = new \DateTime();
                    $startDate = clone $currentDate; while ($startDate->format('N') != 1) { $startDate->modify('-1 day'); }
                    $startDate->modify('-28 days');
                    $endDate = clone $currentDate; while ($endDate->format('N') != 7) { $endDate->modify('+1 day'); }
                    $startDateStr = $startDate->format('Y-m-d');
                    $endDateStr = $endDate->format('Y-m-d');
                    $dateFilter = " AND preferred_date >= '$startDateStr' AND preferred_date <= '$endDateStr'";
                    break;
                case 'monthly':
                    $currentYear = date('Y');
                    $dateFilter = " AND YEAR(preferred_date) = '$currentYear'";
                    break;
            }

            $query = $baseQuery . $counselorFilter . $dateFilter . " ORDER BY preferred_date ASC, preferred_time ASC";
            $chartAppointments = $db->query($query)->getResultArray();

            $dateFormat = ($timeRange === 'daily' || $timeRange === 'weekly') ? 'Y-m-d' : 'Y-m';
            $stats = [];

            if ($timeRange === 'daily' && $startDateStr && $endDateStr) {
                $currentDate = new \DateTime($startDateStr);
                $endDate = new \DateTime($endDateStr);
                while ($currentDate <= $endDate) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $stats[$dateStr] = ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0];
                    $currentDate->modify('+1 day');
                }
                $response['weekInfo'] = ['startDate' => $startDateStr, 'endDate' => $endDateStr, 'weekDays' => []];
                $tempDate = new \DateTime($startDateStr);
                $endTempDate = new \DateTime($endDateStr);
                while ($tempDate <= $endTempDate) {
                    $response['weekInfo']['weekDays'][] = [
                        'date' => $tempDate->format('Y-m-d'),
                        'dayName' => $tempDate->format('l'),
                        'shortDayName' => $tempDate->format('D'),
                        'dayMonth' => $tempDate->format('M j')
                    ];
                    $tempDate->modify('+1 day');
                }
            } elseif ($timeRange === 'weekly' && $startDateStr && $endDateStr) {
                $currentDate = new \DateTime($startDateStr);
                $lastDate = new \DateTime($endDateStr);
                while ($currentDate->format('N') != 1) { $currentDate->modify('-1 day'); }
                while ($lastDate->format('N') != 7) { $lastDate->modify('+1 day'); }
                while ($currentDate <= $lastDate) {
                    $weekStart = $currentDate->format('Y-m-d');
                    $stats[$weekStart] = ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0];
                    $currentDate->modify('+7 days');
                }
                $response['weekRanges'] = [];
                foreach (array_keys($stats) as $weekStart) {
                    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
                    $response['weekRanges'][] = ['start' => $weekStart, 'end' => $weekEnd];
                }
            }

            $totalStats = ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0];
            $monthlyStats = array_fill(1, 12, ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0]);

            foreach ($chartAppointments as $appointment) {
                $date = date($dateFormat, strtotime($appointment['appointed_date']));
                $month = date('n', strtotime($appointment['appointed_date']));
                if ($timeRange === 'weekly') {
                    $appointmentDate = new \DateTime($appointment['appointed_date']);
                    while ($appointmentDate->format('N') != 1) { $appointmentDate->modify('-1 day'); }
                    $date = $appointmentDate->format('Y-m-d');
                    if (!isset($stats[$date])) continue;
                }
                if (!isset($stats[$date])) {
                    $stats[$date] = ['completed' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0, 'cancelled' => 0];
                }
                $status = strtolower($appointment['status']);
                if (in_array($status, ['completed', 'approved', 'rejected', 'pending', 'cancelled'])) {
                    $stats[$date][$status]++;
                    $totalStats[$status]++;
                    $monthlyStats[$month][$status]++;
                }
            }

            ksort($stats);
            $response['labels'] = array_keys($stats);
            foreach ($stats as $stat) {
                $response['completed'][] = $stat['completed'];
                $response['approved'][] = $stat['approved'];
                $response['rejected'][] = $stat['rejected'];
                $response['pending'][] = $stat['pending'];
                $response['cancelled'][] = $stat['cancelled'];
            }
            if ($timeRange === 'daily' || $timeRange === 'weekly') {
                $response['startDate'] = $startDateStr;
                $response['endDate'] = $endDateStr;
            }
            $response['totalCompleted'] = $totalStats['completed'];
            $response['totalApproved'] = $totalStats['approved'];
            $response['totalRejected'] = $totalStats['rejected'];
            $response['totalPending'] = $totalStats['pending'];
            $response['totalCancelled'] = $totalStats['cancelled'];
            $response['counselorName'] = $counselorName;
            for ($i = 1; $i <= 12; $i++) {
                $response['monthlyCompleted'][$i-1] = $monthlyStats[$i]['completed'];
                $response['monthlyApproved'][$i-1] = $monthlyStats[$i]['approved'];
                $response['monthlyRejected'][$i-1] = $monthlyStats[$i]['rejected'];
                $response['monthlyPending'][$i-1] = $monthlyStats[$i]['pending'];
                $response['monthlyCancelled'][$i-1] = $monthlyStats[$i]['cancelled'];
            }
            $response['success'] = true;
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $this->response->setJSON($response);
    }
}



