<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;

class NotificationsModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'type',
        'title',
        'message',
        'related_id',
        'is_read',
        'event_date',
        'appointment_date',
        'created_at'
    ];
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = null;
    protected $deletedField = null;
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    protected $allowCallbacks = true;

    public function getUnreadCount($userId)
    {
        $db = \Config\Database::connect();
        
        // Get last active time from user's last activity
        $lastActiveTime = $db->table('users')
                           ->select('last_activity')
                           ->where('user_id', $userId)
                           ->get()
                           ->getRowArray();
        
        if (!$lastActiveTime || !$lastActiveTime['last_activity']) {
            // If no last activity time, consider all recent items as unread (last 30 days)
            $lastActiveTime = date('Y-m-d H:i:s', strtotime('-30 days'));
        } else {
            $lastActiveTime = $lastActiveTime['last_activity'];
        }
        
        try {
            // Count new events
            $eventsCount = $db->table('events')
                            ->where('created_at >', $lastActiveTime)
                            ->countAllResults();
            
            // Count new announcements
            $announcementsCount = $db->table('announcements')
                                   ->where('created_at >', $lastActiveTime)
                                   ->countAllResults();
            
            // Count updated appointments (user only)
            $appointmentsCount = $db->table('appointments')
                                  ->where('student_id', $userId)
                                  ->where('updated_at >', $lastActiveTime)
                                  ->countAllResults();
            
            // Count new messages (student <-> counselor conversations)
            $adminIds = $this->getCounselorIds();
            $messagesCount = 0; // Initialize messagesCount

            if (!empty($adminIds)) {
                $messagesCount = $db->table('messages')
                                  ->groupStart()
                                  ->whereIn('sender_id', $adminIds)
                                  ->where('receiver_id', $userId)
                                  ->groupEnd()
                                  ->orGroupStart()
                                  ->where('sender_id', $userId)
                                  ->whereIn('receiver_id', $adminIds)
                                  ->groupEnd()
                                  ->where('created_at >', $lastActiveTime)
                                  ->countAllResults();
            }

            return $eventsCount + $announcementsCount + $appointmentsCount + $messagesCount;
        } catch (\Exception $e) {
            log_message('error', 'Error in getUnreadCount: ' . $e->getMessage());
            return 0; // Return 0 on error to prevent breaking the UI
        }
    }

    public function getNotifications($userId, $limit = 20)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->find();
    }

    public function markAsRead($notificationId, $userId)
    {
        return $this->where('id', $notificationId)
                    ->where('user_id', $userId)
                    ->set(['is_read' => 1])
                    ->update();
    }

    public function markAllAsRead($userId)
    {
        // Mark all notifications in notifications table as read
        $this->where('user_id', $userId)
             ->where('is_read', 0)
             ->set(['is_read' => 1])
             ->update();
        
        // Mark all events and announcements as read using the EXACT same logic as getRecentNotifications()
        // This ensures we mark exactly what is currently visible in the notifications popup
        $db = \Config\Database::connect();
        
        // Get last active time for filtering (same logic as getRecentNotifications)
        $lastActiveTimeRow = $db->table('users')
            ->select('last_activity')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();
        $lastActiveTime = $lastActiveTimeRow && $lastActiveTimeRow['last_activity'] ? $lastActiveTimeRow['last_activity'] : date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Get all already read event IDs (same as getRecentNotifications)
        $readEvents = $db->table('notification_reads')
            ->select('related_id')
            ->where('user_id', $userId)
            ->where('notification_type', 'event')
            ->get()
            ->getResultArray();
        $readEventIds = array_column($readEvents, 'related_id');
        
        // Get all unread events that would appear in notifications
        // EXACT same query as in getRecentNotifications() - created after last active time AND date >= today
        $eventsQuery = $db->table('events')
            ->select('id, title, date, time, location, created_at')
            ->where('created_at >', $lastActiveTime)
            ->where('date >=', date('Y-m-d')); // Only show events from today onwards
        
        // Exclude read events (same as getRecentNotifications)
        if (!empty($readEventIds)) {
            $eventsQuery->whereNotIn('id', $readEventIds);
        }
        
        $unreadEvents = $eventsQuery->get()->getResultArray();
        
        // Mark all unread events as read (only non-expired events, same check as getRecentNotifications)
        foreach ($unreadEvents as $event) {
            // Skip expired events (same check as in getRecentNotifications)
            if ($this->isEventExpired($event['date'], $event['time'])) {
                continue;
            }
            
            $eventId = $event['id'];
            // Use markEventAsRead to ensure consistency with individual marking
            $this->markEventAsRead($userId, $eventId);
        }
        
        // Get all already read announcement IDs (same as getRecentNotifications)
        $readAnnouncements = $db->table('notification_reads')
            ->select('related_id')
            ->where('user_id', $userId)
            ->where('notification_type', 'announcement')
            ->get()
            ->getResultArray();
        $readAnnouncementIds = array_column($readAnnouncements, 'related_id');
        
        // Get all unread announcements that would appear in notifications
        // EXACT same query as in getRecentNotifications() - created after last active time
        $announcementsQuery = $db->table('announcements')
            ->select('id, title, content, created_at')
            ->where('created_at >', $lastActiveTime);
        
        // Exclude read announcements (same as getRecentNotifications)
        if (!empty($readAnnouncementIds)) {
            $announcementsQuery->whereNotIn('id', $readAnnouncementIds);
        }
        
        $unreadAnnouncements = $announcementsQuery->get()->getResultArray();
        
        // Mark all unread announcements as read using markAnnouncementAsRead for consistency
        foreach ($unreadAnnouncements as $announcement) {
            $announcementId = $announcement['id'];
            // Use markAnnouncementAsRead to ensure consistency with individual marking
            $this->markAnnouncementAsRead($userId, $announcementId);
        }
        
        return true;
    }

    public function markEventAsRead($userId, $eventId)
    {
        $db = \Config\Database::connect();
        // Check if already marked as read
        $exists = $db->table('notification_reads')
            ->where('user_id', $userId)
            ->where('notification_type', 'event')
            ->where('related_id', $eventId)
            ->countAllResults();
        
        if ($exists === 0) {
            $db->table('notification_reads')->insert([
                'user_id' => $userId,
                'notification_type' => 'event',
                'related_id' => $eventId
            ]);
        }
        
        // Also mark notifications table entries as read
        $this->where('user_id', $userId)
             ->where('type', 'event')
             ->where('related_id', $eventId)
             ->set(['is_read' => 1])
             ->update();
    }

    public function markAnnouncementAsRead($userId, $announcementId)
    {
        $db = \Config\Database::connect();
        // Check if already marked as read
        $exists = $db->table('notification_reads')
            ->where('user_id', $userId)
            ->where('notification_type', 'announcement')
            ->where('related_id', $announcementId)
            ->countAllResults();
        
        if ($exists === 0) {
            $db->table('notification_reads')->insert([
                'user_id' => $userId,
                'notification_type' => 'announcement',
                'related_id' => $announcementId
            ]);
        }
        
        // Also mark notifications table entries as read
        $this->where('user_id', $userId)
             ->where('type', 'announcement')
             ->where('related_id', $announcementId)
             ->set(['is_read' => 1])
             ->update();
    }

    public function createNotification($data)
    {
        return $this->insert($data);
    }

    public function getRecentMessagesAsNotifications($userId, $lastActiveTime)
    {
        $db = \Config\Database::connect();
        $adminIds = $this->getCounselorIds();

        if (empty($adminIds)) {
            return [];
        }

        // Use Query Builder for IN and OR conditions
        $builder = $db->table('messages');
        $builder->select('message_id, sender_id, receiver_id, message_text, created_at');
        $builder->groupStart()
            ->whereIn('sender_id', $adminIds)
            ->where('receiver_id', $userId)
            ->groupEnd();
        $builder->orGroupStart()
            ->where('sender_id', $userId)
            ->whereIn('receiver_id', $adminIds)
            ->groupEnd();
        $builder->where('created_at >', $lastActiveTime);
        $builder->orderBy('created_at', 'DESC');

        $messages = $builder->get()->getResultArray();

        $notifications = [];
        foreach ($messages as $msg) {
            $isFromAdmin = in_array($msg['sender_id'], $adminIds);
            $notifications[] = [
                'type' => 'message',
                'title' => $isFromAdmin ? 'New Message from Counselor' : 'Your Message to Counselor',
                'message' => substr($msg['message_text'], 0, 100) . (strlen($msg['message_text']) > 100 ? '...' : ''),
                'created_at' => $msg['created_at'],
                'related_id' => $msg['message_id'],
                'is_read' => 0
            ];
        }
        return $notifications;
    }

    public function getRecentNotifications($userId, $lastActiveTime)
    {
        $db = \Config\Database::connect();
        
        // If $lastActiveTime is not provided, fetch from DB
        if (!$lastActiveTime) {
            $row = $db->table('users')
                ->select('last_activity')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();
            $lastActiveTime = $row && $row['last_activity'] ? $row['last_activity'] : date('Y-m-d H:i:s', strtotime('-30 days'));
        }

        try {
        
        // Get read event/announcement IDs for this user
        $readEvents = $db->table('notification_reads')
            ->select('related_id')
            ->where('user_id', $userId)
            ->where('notification_type', 'event')
            ->get()
            ->getResultArray();
        $readEventIds = array_column($readEvents, 'related_id');
        
        $readAnnouncements = $db->table('notification_reads')
            ->select('related_id')
            ->where('user_id', $userId)
            ->where('notification_type', 'announcement')
            ->get()
            ->getResultArray();
        $readAnnouncementIds = array_column($readAnnouncements, 'related_id');
        
        // Get events created after last active time - only show future events
        $eventsQuery = $db->table('events')
            ->select('id, title, date, time, location, created_at')
            ->where('created_at >', $lastActiveTime)
            ->where('date >=', date('Y-m-d')); // Only show events from today onwards
        
        // Exclude read events
        if (!empty($readEventIds)) {
            $eventsQuery->whereNotIn('id', $readEventIds);
        }
        
        $eventsQuery = $eventsQuery->get()->getResultArray();

        // Get announcements created after last active time
        $announcementsQuery = $db->table('announcements')
            ->select('id, title, content, created_at')
            ->where('created_at >', $lastActiveTime);
        
        // Exclude read announcements
        if (!empty($readAnnouncementIds)) {
            $announcementsQuery->whereNotIn('id', $readAnnouncementIds);
        }
        
        $announcementsQuery = $announcementsQuery->get()->getResultArray();

        // Get notifications from notifications table (appointments and follow-ups)
        $notificationsQuery = $db->table('notifications')
            ->select('id, user_id, type, title, message, related_id, is_read, created_at, appointment_date, event_date')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
        
        // For students: Get appointments with approved, rejected, or cancelled status
        // For counselors: Get cancelled appointments from students
        // Only show appointments that have unread notifications (is_read = 0)
        $userRole = $this->getUserRole($userId);
        $appointmentsQuery = [];
        
        // Get unread appointment notification IDs and their is_read status
        $unreadAppointmentNotifications = $db->table('notifications')
            ->select('related_id, is_read')
            ->where('user_id', $userId)
            ->where('type', 'appointment')
            ->where('is_read', 0)
            ->get()
            ->getResultArray();
        $unreadAppointmentIdList = array_column($unreadAppointmentNotifications, 'related_id');
        $unreadAppointmentMap = [];
        foreach ($unreadAppointmentNotifications as $notif) {
            $unreadAppointmentMap[$notif['related_id']] = $notif['is_read'];
        }
        
        if ($userRole === 'student') {
            // Get appointments updated after last active time - show approved, rejected, cancelled statuses
            // Only include appointments that have unread notifications
            $appointmentsQuery = $db->table('appointments')
                ->select('id, student_id, preferred_date, preferred_time, status, updated_at, counselor_preference, purpose, reason')
                ->where('student_id', $userId)
                ->whereIn('status', ['approved', 'rejected', 'cancelled'])
                ->where('updated_at >', $lastActiveTime)
                ->where('preferred_date >=', date('Y-m-d', strtotime('-7 days'))) // Show appointments from 7 days ago onwards
                ->get()
                ->getResultArray();
            
            // Filter to only include appointments with unread notifications
            if (!empty($unreadAppointmentIdList)) {
                $appointmentsQuery = array_filter($appointmentsQuery, function($app) use ($unreadAppointmentIdList) {
                    return in_array($app['id'], $unreadAppointmentIdList);
                });
                $appointmentsQuery = array_values($appointmentsQuery); // Re-index array
            } else {
                $appointmentsQuery = []; // No unread appointment notifications
            }
        } elseif ($userRole === 'counselor') {
            // Get cancelled appointments from students assigned to this counselor
            // Only include appointments that have unread notifications
            // Join with student_personal_info to get student names
            $appointmentsQuery = $db->table('appointments a')
                ->select('a.id, a.student_id, a.preferred_date, a.preferred_time, a.status, a.updated_at, a.counselor_preference, a.purpose, a.reason, spi.first_name, spi.last_name')
                ->join('student_personal_info spi', 'spi.student_id = a.student_id', 'left')
                ->where('a.counselor_preference', $userId)
                ->where('a.status', 'cancelled')
                ->where('a.updated_at >', $lastActiveTime)
                ->where('a.preferred_date >=', date('Y-m-d', strtotime('-7 days'))) // Show appointments from 7 days ago onwards
                ->get()
                ->getResultArray();
            
            // Filter to only include appointments with unread notifications
            if (!empty($unreadAppointmentIdList)) {
                $appointmentsQuery = array_filter($appointmentsQuery, function($app) use ($unreadAppointmentIdList) {
                    return in_array($app['id'], $unreadAppointmentIdList);
                });
                $appointmentsQuery = array_values($appointmentsQuery); // Re-index array
            } else {
                $appointmentsQuery = []; // No unread appointment notifications
            }
        }

        $notifications = [];

        // Format events - only include non-expired events
        foreach ($eventsQuery as $event) {
            // Skip expired events
            if ($this->isEventExpired($event['date'], $event['time'])) {
                continue;
            }
            
            $notifications[] = [
                'type' => 'event',
                'title' => 'New Event: ' . $event['title'],
                'message' => "A new event has been scheduled for " . date('F j, Y', strtotime($event['date'])) . 
                            " at " . $event['time'] . " in " . $event['location'],
                'created_at' => $event['created_at'],
                'related_id' => $event['id']
            ];
        }

        // Format announcements
        foreach ($announcementsQuery as $announcement) {
            $notifications[] = [
                'type' => 'announcement',
                'title' => 'New Announcement: ' . $announcement['title'],
                'message' => substr($announcement['content'], 0, 100) . '...',
                'created_at' => $announcement['created_at'],
                'related_id' => $announcement['id']
            ];
        }

        // Format appointments - include all statuses but filter by expiration
        foreach ($appointmentsQuery as $appointment) {
            // Skip expired appointments (older than 7 days past appointment date)
            if ($this->isAppointmentExpired($appointment['preferred_date'], $appointment['preferred_time'])) {
                // Only skip if appointment is more than 7 days past
                $appointmentDateTime = $appointment['preferred_date'] . ' ' . $appointment['preferred_time'];
                $appointmentTimestamp = strtotime($appointmentDateTime);
                $sevenDaysAgo = strtotime('-7 days');
                
                if ($appointmentTimestamp < $sevenDaysAgo) {
                    continue;
                }
            }
            
            $reasonText = isset($appointment['reason']) && $appointment['reason'] ? ' Reason: ' . $appointment['reason'] : '';
            $purposeText = isset($appointment['purpose']) && $appointment['purpose'] ? ' Purpose: ' . $appointment['purpose'] : '';
            
            if ($userRole === 'student') {
                // Student notification format
                // Get counselor name for notification
                $counselorName = 'the counselor';
                if (!empty($appointment['counselor_preference']) && $appointment['counselor_preference'] !== 'No preference') {
                    $counselorInfo = $db->table('counselors')
                        ->select('name')
                        ->where('counselor_id', $appointment['counselor_preference'])
                        ->get()
                        ->getRowArray();
                    
                    if ($counselorInfo && !empty($counselorInfo['name'])) {
                        $counselorName = trim($counselorInfo['name']);
                    }
                }
                
                // Get is_read status from notification entry if it exists
                $isRead = isset($unreadAppointmentMap[$appointment['id']]) ? $unreadAppointmentMap[$appointment['id']] : 0;
                
                $notifications[] = [
                    'type' => 'appointment',
                    'title' => 'Appointment Update',
                    'message' => "Your appointment for " . date('F j, Y', strtotime($appointment['preferred_date'])) .
                                " at " . $appointment['preferred_time'] .
                                " with Counselor " . $counselorName .
                                $purposeText .
                                " has been " . strtolower($appointment['status']) . $reasonText,
                    'created_at' => $appointment['updated_at'],
                    'related_id' => $appointment['id'],
                    'is_read' => $isRead
                ];
            } elseif ($userRole === 'counselor') {
                // Counselor notification format for cancelled appointments
                // Get student name from joined student_personal_info data
                $studentName = $appointment['student_id']; // Default fallback
                if (!empty($appointment['first_name']) && !empty($appointment['last_name'])) {
                    $studentName = trim($appointment['last_name'] . ', ' . $appointment['first_name']);
                }
                
                // Get is_read status from notification entry if it exists
                $isRead = isset($unreadAppointmentMap[$appointment['id']]) ? $unreadAppointmentMap[$appointment['id']] : 0;
                
                $notifications[] = [
                    'type' => 'appointment',
                    'title' => 'Appointment Cancelled',
                    'message' => "Student " . $studentName . " has cancelled their appointment scheduled for " .
                                date('F j, Y', strtotime($appointment['preferred_date'])) .
                                " at " . $appointment['preferred_time'] . $reasonText,
                    'created_at' => $appointment['updated_at'],
                    'related_id' => $appointment['id'],
                    'is_read' => $isRead
                ];
            }
        }
        
        // Add notifications from notifications table (appointments and follow-ups)
        foreach ($notificationsQuery as $notification) {
            $notificationMessage = $notification['message'];
            
            // For counselors, replace student_id with student name in appointment and follow-up notifications
            if ($userRole === 'counselor' && ($notification['type'] === 'appointment' || $notification['type'] === 'follow-up' || $notification['type'] === 'follow_up_session')) {
                // Extract student_id from message if present (format: "Student 2023303640" or similar)
                if (preg_match('/Student\s+(\d{10})/', $notificationMessage, $matches)) {
                    $studentId = $matches[1];
                    // Get student name from student_personal_info
                    $studentInfo = $db->table('student_personal_info')
                        ->select('first_name, last_name')
                        ->where('student_id', $studentId)
                        ->get()
                        ->getRowArray();
                    
                    if ($studentInfo && !empty($studentInfo['first_name']) && !empty($studentInfo['last_name'])) {
                        $studentName = trim($studentInfo['last_name'] . ', ' . $studentInfo['first_name']);
                        // Replace student_id with student name in message
                        $notificationMessage = preg_replace('/Student\s+\d{10}/', 'Student ' . $studentName, $notificationMessage);
                    }
                }
            }
            
            // For students, replace counselor ID with counselor name in appointment and follow-up notifications
            if ($userRole === 'student' && ($notification['type'] === 'appointment' || $notification['type'] === 'follow-up' || $notification['type'] === 'follow_up_session')) {
                // Extract counselor ID from message if present (format: "Counselor COUN-2025-1234" or similar)
                if (preg_match('/Counselor\s+([A-Z0-9-]+)/', $notificationMessage, $matches)) {
                    $counselorId = $matches[1];
                    // Check if it's already a name (contains spaces or doesn't match ID pattern)
                    // Only process if it looks like an ID (alphanumeric with dashes, reasonable length)
                    if (preg_match('/^[A-Z0-9-]+$/', $counselorId) && strlen($counselorId) <= 20) {
                        // Get counselor name from counselors table
                        $counselorInfo = $db->table('counselors')
                            ->select('name')
                            ->where('counselor_id', $counselorId)
                            ->get()
                            ->getRowArray();
                        
                        if ($counselorInfo && !empty($counselorInfo['name'])) {
                            $counselorName = trim($counselorInfo['name']);
                            // Replace counselor ID with counselor name in message
                            $notificationMessage = preg_replace('/Counselor\s+' . preg_quote($counselorId, '/') . '/', 'Counselor ' . $counselorName, $notificationMessage);
                        }
                    }
                }
            }
            
            $notifications[] = [
                'id' => $notification['id'],
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notificationMessage,
                'related_id' => $notification['related_id'],
                'is_read' => $notification['is_read'],
                'created_at' => $notification['created_at'],
                'appointment_date' => $notification['appointment_date'] ?? null,
                'event_date' => $notification['event_date'] ?? null
            ];
        }

            // Add messages as notifications
            $messageNotifications = $this->getRecentMessagesAsNotifications($userId, $lastActiveTime);
            $notifications = array_merge($notifications, $messageNotifications);

            // Sort by created_at in descending order
            usort($notifications, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return $notifications;
        } catch (\Exception $e) {
            log_message('error', 'Error in getRecentNotifications: ' . $e->getMessage());
            return []; // Return empty array on error to prevent breaking the UI
        }
    }

    public function getAdminIds()
    {
        $db = \Config\Database::connect();
        $admins = $db->table('users')
            ->select('user_id')
            ->where('role', 'counselor')
            ->get()
            ->getResultArray();

        return array_column($admins, 'user_id');
    }

    public function getCounselorIds()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('users')
            ->select('user_id')
            ->where('role', 'counselor')
            ->get()
            ->getResultArray();

        return array_column($rows, 'user_id');
    }

    /**
     * Check if an event has passed based on date and time
     */
    private function isEventExpired($eventDate, $eventTime)
    {
        $eventDateTime = $eventDate . ' ' . $eventTime;
        $eventTimestamp = strtotime($eventDateTime);
        $currentTimestamp = time();
        
        return $eventTimestamp < $currentTimestamp;
    }

    /**
     * Check if an appointment has passed based on date and time
     */
    private function isAppointmentExpired($appointmentDate, $appointmentTime)
    {
        $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;
        $appointmentTimestamp = strtotime($appointmentDateTime);
        $currentTimestamp = time();
        
        return $appointmentTimestamp < $currentTimestamp;
    }

    /**
     * Get user role from database
     * 
     * @param string $userId User ID
     * @return string|null User role or null if not found
     */
    private function getUserRole(string $userId): ?string
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->table('users')
                ->select('role')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();
            
            return $result ? $result['role'] : null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting user role: ' . $e->getMessage());
            return null;
        }
    }
} 