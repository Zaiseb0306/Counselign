<?php

namespace App\Controllers\Counselor;

use CodeIgniter\API\ResponseTrait;
use App\Models\NotificationsModel;

class Notifications extends \CodeIgniter\Controller
{
    use ResponseTrait;

    protected $notificationsModel;

    public function __construct()
    {
        $this->notificationsModel = new NotificationsModel();
    }

    public function index()
    {
        try {
            // Check if user is logged in and is a counselor
            if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User not logged in']);
            }

            $userId = session()->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User ID not found in session']);
            }

            $lastActiveTime = session()->get('last_activity');
            if (!$lastActiveTime) {
                $lastActiveTime = date('Y-m-d H:i:s', strtotime('-30 days'));
            }

            $notifications = $this->notificationsModel->getRecentNotifications($userId, $lastActiveTime);

            // Add pending appointments for this counselor as notifications
            $db = \Config\Database::connect();
            $pendingAppointments = $db->table('appointments a')
                ->select('a.id, a.student_id, a.preferred_date, a.preferred_time, a.status, a.updated_at, a.created_at, a.consultation_type, a.counselor_preference, u.username')
                ->join('users u', 'u.user_id = a.student_id', 'left')
                ->where('a.status', 'pending')
                ->where('a.counselor_preference', $userId)
                ->orderBy('a.created_at', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($pendingAppointments as $app) {
                $notifications[] = [
                    'type' => 'appointment',
                    'title' => 'Pending Appointment',
                    'message' => 'Student ' . ($app['username'] ?? $app['student_id']) . ' requested ' . ($app['consultation_type'] ?? 'consultation') . ' on ' . ($app['preferred_date'] ?? '') . ' at ' . ($app['preferred_time'] ?? ''),
                    'created_at' => $app['updated_at'] ?? $app['created_at'] ?? date('Y-m-d H:i:s'),
                    'related_id' => $app['id'],
                ];
            }

            // Sort combined notifications by time desc for consistent display
            usort($notifications, function ($a, $b) {
                return strtotime($b['created_at'] ?? '0') <=> strtotime($a['created_at'] ?? '0');
            });

            // Unread count for counselor: use number of displayable notifications
            $unreadCount = count($notifications);

            return $this->response->setJSON([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in Counselor Notifications->index: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'An internal server error occurred.']);
        }
    }

    public function markAsRead()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in');
        }

        $userId = session()->get('user_id_display');
        if (!$userId) {
            return $this->failUnauthorized('User ID not found in session');
        }

        try {
            $db = \Config\Database::connect();
            $db->table('users')
                ->where('user_id', $userId)
                ->set(['last_activity' => date('Y-m-d H:i:s')])
                ->update();

            return $this->respond([
                'status' => 'success',
                'message' => 'Notifications marked as read by updating last activity time.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error marking counselor notifications as read: ' . $e->getMessage());
            return $this->failServerError('Failed to mark notifications as read');
        }
    }

    public function getUnreadCount()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in');
        }

        $userId = session()->get('user_id_display');
        if (!$userId) {
            return $this->failUnauthorized('User ID not found in session');
        }

        try {
            $unreadCount = $this->notificationsModel->getUnreadCount($userId);
            return $this->respond([
                'status' => 'success',
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting counselor unread count: ' . $e->getMessage());
            return $this->failServerError('Failed to get unread count');
        }
    }
}


