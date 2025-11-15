<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class HistoryReports extends Controller
{
    protected $request;
    protected $helpers = ['url', 'form'];
    protected $db;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        return view('admin/history_reports');
    }

    public function getHistoryData()
    {
        $query = $this->db->table('appointments')
            ->select('appointments.*, users.firstname, users.lastname, counselors.firstname as counselor_firstname, counselors.lastname as counselor_lastname')
            ->join('users', 'users.user_id = appointments.student_id')
            ->join('counselors', 'counselors.id = appointments.counselor_id')
            ->where('appointments.status', 'completed')
            ->orderBy('appointments.created_at', 'DESC')
            ->get();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $query->getResult()
        ]);
    }

    public function getHistoricalData()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');
        // Prevent access to future months
        $currentMonth = date('Y-m');
        if ($month > $currentMonth) {
            $month = $currentMonth;
        }
        $reportType = $this->request->getGet('type') ?? 'monthly';

        try {
            // Get the first and last day of the selected month
            $firstDay = new \DateTime($month . '-01');
            $lastDay = clone $firstDay;
            $lastDay->modify('last day of this month');

            // Initialize arrays for the response
            $labels = [];
            $completed = [];
            $approved = [];
            $rejected = [];
            $pending = [];
            $cancelled = [];

            if ($reportType === 'daily') {
                $query = $this->db->query("
                    SELECT
                        DATE(preferred_date) as date,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM appointments
                    WHERE preferred_date BETWEEN ? AND ?
                    GROUP BY DATE(preferred_date)
                    ORDER BY DATE(preferred_date)
                ", [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);

                foreach ($query->getResult() as $row) {
                    $labels[] = date('j', strtotime($row->date));
                    $completed[] = (int)$row->completed;
                    $approved[] = (int)$row->approved;
                    $rejected[] = (int)$row->rejected;
                    $pending[] = (int)$row->pending;
                    $cancelled[] = (int)$row->cancelled;
                }

                // Add follow-up sessions (pending/completed/cancelled)
                $fu = $this->db->query("
                    SELECT DATE(preferred_date) as date,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                           0 as approved,
                           0 as rejected,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM follow_up_appointments
                    WHERE preferred_date BETWEEN ? AND ?
                    GROUP BY DATE(preferred_date)
                    ORDER BY DATE(preferred_date)
                ", [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);
                // map day->index
                $dayToIndex = [];
                $i = 0; foreach ($query->getResult() as $row) { $dayToIndex[date('Y-m-d', strtotime($row->date))] = $i++; }
                foreach ($fu->getResult() as $row) {
                    $key = date('Y-m-d', strtotime($row->date));
                    // find index by day number match if exact date not present
                    $dayNum = (int)date('j', strtotime($row->date));
                    $idx = array_search($dayNum, $labels);
                    if ($idx === false) continue;
                    $completed[$idx] += (int)$row->completed;
                    $pending[$idx] += (int)$row->pending;
                    $cancelled[$idx] += (int)$row->cancelled;
                }
            } elseif ($reportType === 'weekly') {
                $firstMonday = clone $firstDay;
                $dayOfWeek = $firstMonday->format('N');
                
                if ($dayOfWeek > 1) {
                    $firstMonday->modify('-' . ($dayOfWeek - 1) . ' days');
                }

                $lastSunday = clone $lastDay;
                $lastDayOfWeek = $lastSunday->format('N');
                if ($lastDayOfWeek < 7) {
                    $lastSunday->modify('+' . (7 - $lastDayOfWeek) . ' days');
                }

                $query = $this->db->query("
                    WITH RECURSIVE dates AS (
                        SELECT ? as date
                        UNION ALL
                        SELECT DATE_ADD(date, INTERVAL 1 DAY)
                        FROM dates
                        WHERE DATE_ADD(date, INTERVAL 1 DAY) <= ?
                    ),
                    weeks AS (
                        SELECT
                            MIN(d.date) as week_start,
                            DATE_ADD(MIN(d.date), INTERVAL 6 DAY) as week_end
                        FROM dates d
                        GROUP BY YEARWEEK(d.date, 1)
                    )
                    SELECT
                        w.week_start,
                        w.week_end,
                        COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed,
                        COUNT(DISTINCT CASE WHEN a.status = 'approved' THEN a.id END) as approved,
                        COUNT(DISTINCT CASE WHEN a.status = 'rejected' THEN a.id END) as rejected,
                        COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending,
                        COUNT(DISTINCT CASE WHEN a.status = 'cancelled' THEN a.id END) as cancelled
                    FROM weeks w
                    LEFT JOIN appointments a ON a.preferred_date BETWEEN w.week_start AND w.week_end
                    GROUP BY w.week_start, w.week_end
                    ORDER BY w.week_start
                ", [$firstMonday->format('Y-m-d'), $lastSunday->format('Y-m-d')]);

                $weekCount = 1;
                $weekStarts = [];
                foreach ($query->getResult() as $row) {
                    $weekStart = new \DateTime($row->week_start);
                    $weekEnd = new \DateTime($row->week_end);
                    
                    $labels[] = "Week " . $weekCount . " (" . 
                               $weekStart->format('M j') . "-" .
                               $weekEnd->format('j') . ")";
                    
                    $completed[] = (int)$row->completed;
                    $approved[] = (int)$row->approved;
                    $rejected[] = (int)$row->rejected;
                    $pending[] = (int)$row->pending;
                    $cancelled[] = (int)$row->cancelled;
                    
                    $weekStarts[] = $weekStart->format('Y-m-d');
                    $weekCount++;
                }

                // Add follow-ups by bucketing each date to its week start
                $fuAll = $this->db->query("SELECT preferred_date, status FROM follow_up_appointments WHERE preferred_date BETWEEN ? AND ?",
                    [$firstMonday->format('Y-m-d'), $lastSunday->format('Y-m-d')])->getResultArray();
                foreach ($fuAll as $fuRow) {
                    $d = new \DateTime($fuRow['preferred_date']);
                    while ($d->format('N') != 1) { $d->modify('-1 day'); }
                    $wkStart = $d->format('Y-m-d');
                    $idx = array_search($wkStart, $weekStarts);
                    if ($idx === false) continue;
                    $st = strtolower($fuRow['status']);
                    if ($st === 'completed') $completed[$idx]++;
                    if ($st === 'pending') $pending[$idx]++;
                    if ($st === 'cancelled') $cancelled[$idx]++;
                }
            } elseif ($reportType === 'yearly') {
                // Get the selected year from the month parameter
                $selectedYear = (int)date('Y', strtotime($month . '-01'));
                
                $query = $this->db->query("
                    SELECT
                        YEAR(preferred_date) as year,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM appointments
                    WHERE YEAR(preferred_date) = ?
                    GROUP BY YEAR(preferred_date)
                    ORDER BY YEAR(preferred_date)
                ", [$selectedYear]);

                foreach ($query->getResult() as $row) {
                    $labels[] = $row->year;
                    $completed[] = (int)$row->completed;
                    $approved[] = (int)$row->approved;
                    $rejected[] = (int)$row->rejected;
                    $pending[] = (int)$row->pending;
                    $cancelled[] = (int)$row->cancelled;
                }

                // Add follow-up yearly counts
                $fuYears = $this->db->query("SELECT YEAR(preferred_date) as year,
                           SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
                        FROM follow_up_appointments
                        WHERE YEAR(preferred_date) = ?
                        GROUP BY YEAR(preferred_date)", [$selectedYear])->getResult();
                foreach ($fuYears as $row) {
                    $idx = array_search($row->year, $labels);
                    if ($idx === false) continue;
                    $completed[$idx] += (int)$row->completed;
                    $pending[$idx] += (int)$row->pending;
                    $cancelled[$idx] += (int)$row->cancelled;
                }
            } else {
                $query = $this->db->query("
                    SELECT
                        HOUR(STR_TO_DATE(preferred_time, '%h:%i %p')) as hour_of_day,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                    FROM appointments
                    WHERE preferred_date BETWEEN ? AND ?
                    GROUP BY HOUR(STR_TO_DATE(preferred_time, '%h:%i %p'))
                    ORDER BY hour_of_day
                ", [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);

                foreach ($query->getResult() as $row) {
                    $hour = str_pad($row->hour_of_day, 2, '0', STR_PAD_LEFT);
                    $labels[] = $hour . ':00';
                    $completed[] = (int)$row->completed;
                    $approved[] = (int)$row->approved;
                    $rejected[] = (int)$row->rejected;
                    $pending[] = (int)$row->pending;
                }
            }

            // Get total counts (appointments + follow-ups) based on report type
            if ($reportType === 'yearly') {
                // For yearly reports, use the selected year from the month parameter
                $selectedYear = (int)date('Y', strtotime($month . '-01'));
                $totalQuery = $this->db->query("
                    SELECT
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as total_cancelled
                    FROM appointments
                    WHERE YEAR(preferred_date) = ?
                ", [$selectedYear]);

                $totals = $totalQuery->getRow();

                // Follow-up totals for the year
                $fuTotals = $this->db->query("
                    SELECT
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as total_cancelled
                    FROM follow_up_appointments
                    WHERE YEAR(preferred_date) = ?
                ", [$selectedYear])->getRow();
            } else {
                // For daily, weekly, and monthly reports, use the date range
                $totalQuery = $this->db->query("
                    SELECT
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as total_rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as total_cancelled
                    FROM appointments
                    WHERE preferred_date BETWEEN ? AND ?
                ", [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);

                $totals = $totalQuery->getRow();

                // Follow-up totals for the date range
                $fuTotals = $this->db->query("
                    SELECT
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as total_cancelled
                    FROM follow_up_appointments
                    WHERE preferred_date BETWEEN ? AND ?
                ", [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')])->getRow();
            }
            
            $totals->total_completed += (int)($fuTotals->total_completed ?? 0);
            $totals->total_pending += (int)($fuTotals->total_pending ?? 0);
            $totals->total_cancelled += (int)($fuTotals->total_cancelled ?? 0);

            return $this->response->setJSON([
                'labels' => $labels,
                'completed' => $completed,
                'approved' => $approved,
                'rejected' => $rejected,
                'pending' => $pending,
                'cancelled' => $cancelled,
                'totalCompleted' => (int)$totals->total_completed,
                'totalApproved' => (int)$totals->total_approved,
                'totalRejected' => (int)$totals->total_rejected,
                'totalPending' => (int)$totals->total_pending,
                'totalCancelled' => (int)$totals->total_cancelled
            ]);

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
} 