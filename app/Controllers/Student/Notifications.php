<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use App\Helpers\UserActivityHelper;
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

            // Update last_activity for viewing notifications
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateStudentActivity($userId, 'view_notifications');

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

        try {
            $input = $this->request->getJSON(true);
            $notificationId = $input['notification_id'] ?? null;
            $notificationType = $input['type'] ?? null;
            $relatedId = $input['related_id'] ?? null;
            $markAll = $input['mark_all'] ?? false;

            if ($markAll) {
                // Mark all notifications as read
                $this->notificationsModel->markAllAsRead($userId);
                
                // Update last_activity for viewing notifications
                $activityHelper = new UserActivityHelper();
                $activityHelper->updateStudentActivity($userId, 'view_notifications');

                return $this->respond([
                    'status' => 'success',
                    'message' => 'All notifications marked as read.'
                ]);
            } else if ($notificationId) {
                // Mark single notification as read
                $this->notificationsModel->markAsRead($notificationId, $userId);
                
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Notification marked as read.'
                ]);
            } else if ($notificationType && $relatedId) {
                // Mark event or announcement as read
                if ($notificationType === 'event') {
                    $this->notificationsModel->markEventAsRead($userId, (int)$relatedId);
                } else if ($notificationType === 'announcement') {
                    $this->notificationsModel->markAnnouncementAsRead($userId, (int)$relatedId);
                } else {
                    return $this->fail('Invalid notification type');
                }
                
                return $this->respond([
                    'status' => 'success',
                    'message' => 'Notification marked as read.'
                ]);
            } else {
                return $this->fail('Missing required parameters');
            }
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