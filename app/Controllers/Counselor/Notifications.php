<?php

namespace App\Controllers\Counselor;


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

            // Update last_activity for viewing notifications
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateCounselorActivity($userId, 'view_notifications');

            // Add pending appointments for this counselor as notifications
            // Only include appointments that have unread notifications (is_read = 0)
            $db = \Config\Database::connect();
            
            // Get unread appointment notification IDs
            $unreadAppointmentIds = $db->table('notifications')
                ->select('related_id')
                ->where('user_id', $userId)
                ->where('type', 'appointment')
                ->where('is_read', 0)
                ->get()
                ->getResultArray();
            $unreadAppointmentIdList = array_column($unreadAppointmentIds, 'related_id');
            
            $pendingAppointments = $db->table('appointments a')
                ->select('a.id, a.student_id, a.preferred_date, a.preferred_time, a.status, a.updated_at, a.created_at, a.method_type, a.counselor_preference, a.consultation_type, u.username, spi.first_name, spi.last_name')
                ->join('users u', 'u.user_id = a.student_id', 'left')
                ->join('student_personal_info spi', 'spi.student_id = a.student_id', 'left')
                ->where('a.status', 'pending')
                ->where('a.counselor_preference', $userId)
                ->orderBy('a.created_at', 'DESC')
                ->get()
                ->getResultArray();
            
            // Filter to only include appointments with unread notifications
            if (!empty($unreadAppointmentIdList)) {
                $pendingAppointments = array_filter($pendingAppointments, function($app) use ($unreadAppointmentIdList) {
                    return in_array($app['id'], $unreadAppointmentIdList);
                });
                $pendingAppointments = array_values($pendingAppointments); // Re-index array
            } else {
                $pendingAppointments = []; // No unread appointment notifications
            }

            foreach ($pendingAppointments as $app) {
                // Get student full name from student_personal_info, fallback to username or student_id
                $studentName = '';
                if (!empty($app['first_name']) && !empty($app['last_name'])) {
                    $studentName = trim($app['last_name'] . ', ' . $app['first_name']);
                } elseif (!empty($app['username'])) {
                    $studentName = $app['username'];
                } else {
                    $studentName = $app['student_id'];
                }
                
                $notifications[] = [
                    'type' => 'appointment',
                    'title' => 'Pending Appointment',
                    'message' => 'Student ' . $studentName . ' requested ' . ($app['consultation_type'] ?? 'consultation') . ' on ' . ($app['preferred_date'] ?? '') . ' at ' . ($app['preferred_time'] ?? ''),
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
                $activityHelper->updateCounselorActivity($userId, 'view_notifications');

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


