<?php

namespace App\Controllers\Student;

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
            // Check if user is logged in and is a student
            if (!session()->get('logged_in') || session()->get('role') !== 'student') {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User not logged in']);
            }

            $userId = session()->get('user_id_display');
            if (!$userId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User ID not found in session']);
            }

            $lastActiveTime = session()->get('last_activity');
            if (!$lastActiveTime) {
                $lastActiveTime = date('Y-m-d H:i:s', strtotime('-30 days')); // fallback
            }

            $notifications = $this->notificationsModel->getRecentNotifications($userId, $lastActiveTime);

            // Enrich message notifications safely: only received from counselors; attach counselor info
            if (is_array($notifications) && !empty($notifications)) {
                try {
                    // Collect message IDs present in notifications
                    $messageIds = [];
                    foreach ($notifications as $n) {
                        if (isset($n['type']) && $n['type'] === 'message' && !empty($n['related_id'])) {
                            $messageIds[] = $n['related_id'];
                        }
                    }

                    if (!empty($messageIds)) {
                        $db = \Config\Database::connect();

                        // Query only messages RECEIVED by the current user; join users to get counselor name/username
                        $rows = $db->table('messages m')
                            ->select('m.message_id, m.sender_id, m.receiver_id, m.created_at, u.username, c.name as counselor_name')
                            ->join('users u', 'u.user_id = m.sender_id', 'left')
                            ->join('counselors c', 'c.counselor_id = m.sender_id', 'left')
                            ->whereIn('m.message_id', $messageIds)
                            ->where('m.receiver_id', $userId)
                            ->get()
                            ->getResultArray();

                        $byId = [];
                        foreach ($rows as $r) {
                            if (!isset($r['message_id'])) { continue; }
                            $byId[$r['message_id']] = $r;
                        }

                        // Rebuild notifications: keep non-message as-is; for message, include only received and attach counselor info
                        $rebuilt = [];
                        foreach ($notifications as $n) {
                            if (!isset($n['type']) || $n['type'] !== 'message') {
                                $rebuilt[] = $n;
                                continue;
                            }
                            $mid = $n['related_id'] ?? null;
                            if (!$mid || !isset($byId[$mid])) {
                                continue; // drop sent or unknown
                            }
                            $row = $byId[$mid];
                            $displayName = ($row['name'] ?? '') ?: (($row['username'] ?? '') ?: 'Counselor');
                            $n['counselor_id'] = $row['sender_id'] ?? null;
                            $n['counselor_name'] = $displayName;
                            $n['title'] = 'New Message from Counselor ' . $displayName;
                            $rebuilt[] = $n;
                        }

                        $notifications = $rebuilt;
                    }
                } catch (\Throwable $t) {
                    log_message('error', 'Student notifications enrichment failed: ' . $t->getMessage());
                    // Fail open: keep original $notifications to avoid breaking UI
                }
            }
            $unreadCount = $this->notificationsModel->getUnreadCount($userId);

            return $this->response->setJSON([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error in NotificationsController->index: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in file ' . $e->getFile());
            return $this->response->setJSON(['status' => 'error', 'message' => 'An internal server error occurred.']);
        }
    }

    public function markAsRead()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return $this->failUnauthorized('User not logged in');
        }

        $userId = session()->get('user_id_display');
        if (!$userId) {
            return $this->failUnauthorized('User ID not found in session');
        }

        // Since notifications are generated dynamically from events, announcements, appointments, and messages,
        // we don't have a persistent notifications table to mark as read.
        // The "read" state is handled by updating the user's last_activity timestamp.
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
            log_message('error', 'Error marking notifications as read: ' . $e->getMessage());
            return $this->failServerError('Failed to mark notifications as read');
        }
    }

    public function getUnreadCount()
    {
        // Check if user is logged in and is a student
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
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
            log_message('error', 'Error getting unread count: ' . $e->getMessage());
            return $this->failServerError('Failed to get unread count');
        }
    }
} 