<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationsModel extends Model
{
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
        
        // Get events created after last active time
        $eventsQuery = $db->table('events')
            ->select('id, title, date, time, location, created_at')
            ->where('created_at >', $lastActiveTime)
            ->get()
            ->getResultArray();

        // Get announcements created after last active time
        $announcementsQuery = $db->table('announcements')
            ->select('id, title, content, created_at')
            ->where('created_at >', $lastActiveTime)
            ->get()
            ->getResultArray();

        // Get appointments updated after last active time
        $appointmentsQuery = $db->table('appointments')
            ->select('id, student_id, preferred_date, preferred_time, status, updated_at, counselor_preference, purpose, reason')
            ->where('student_id', $userId)
            ->where('updated_at >', $lastActiveTime)
            ->get()
            ->getResultArray();

        $notifications = [];

        // Format events
        foreach ($eventsQuery as $event) {
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

        // Format appointments
        foreach ($appointmentsQuery as $appointment) {
            $reasonText = isset($appointment['reason']) && $appointment['reason'] ? ' Reason: ' . $appointment['reason'] : '';
            $purposeText = isset($appointment['purpose']) && $appointment['purpose'] ? ' Purpose: ' . $appointment['purpose'] : '';
            $notifications[] = [
                'type' => 'appointment',
                'title' => 'Appointment Update',
                'message' => "Your appointment for " . date('F j, Y', strtotime($appointment['preferred_date'])) .
                            " at " . $appointment['preferred_time'] .
                            " with counselor preference: " . $appointment['counselor_preference'] .
                            $purposeText .
                            " has been " . strtolower($appointment['status']) . $reasonText,
                'created_at' => $appointment['updated_at'],
                'related_id' => $appointment['id']
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
} 