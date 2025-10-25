<?php

namespace App\Controllers\Counselor;

use CodeIgniter\Controller;
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
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }
        return view('counselor/history_reports');
    }

    public function getHistoryData()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

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
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $month = $this->request->getGet('month') ?? date('Y-m');
        $reportType = $this->request->getGet('type') ?? 'monthly';

        try {
            $firstDay = new \DateTime($month . '-01');
            $lastDay = clone $firstDay; $lastDay->modify('last day of this month');

            $labels = []; $completed = []; $approved = []; $rejected = []; $pending = []; $cancelled = [];

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
            } elseif ($reportType === 'weekly') {
                $firstMonday = clone $firstDay; $dayOfWeek = $firstMonday->format('N');
                if ($dayOfWeek > 1) { $firstMonday->modify('-' . ($dayOfWeek - 1) . ' days'); }
                $lastSunday = clone $lastDay; if ($lastSunday->format('N') != 7) { $lastSunday->modify('next sunday'); }

                $query = $this->db->query("
                    SELECT
                        YEARWEEK(preferred_date, 1) as week,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM appointments
                    WHERE preferred_date BETWEEN ? AND ?
                    GROUP BY YEARWEEK(preferred_date, 1)
                    ORDER BY YEARWEEK(preferred_date, 1)
                ", [$firstMonday->format('Y-m-d'), $lastSunday->format('Y-m-d')]);

                foreach ($query->getResult() as $row) {
                    $labels[] = 'Week ' . substr($row->week, -2);
                    $completed[] = (int)$row->completed;
                    $approved[] = (int)$row->approved;
                    $rejected[] = (int)$row->rejected;
                    $pending[] = (int)$row->pending;
                    $cancelled[] = (int)$row->cancelled;
                }
            } else {
                $query = $this->db->query("
                    SELECT
                        MONTH(preferred_date) as month,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM appointments
                    WHERE YEAR(preferred_date) = ?
                    GROUP BY MONTH(preferred_date)
                    ORDER BY MONTH(preferred_date)
                ", [date('Y', strtotime($month . '-01'))]);

                $labels = range(1, 12);
                foreach ($query->getResult() as $row) {
                    $completed[$row->month - 1] = (int)$row->completed;
                    $approved[$row->month - 1] = (int)$row->approved;
                    $rejected[$row->month - 1] = (int)$row->rejected;
                    $pending[$row->month - 1] = (int)$row->pending;
                    $cancelled[$row->month - 1] = (int)$row->cancelled;
                }
            }

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
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}



