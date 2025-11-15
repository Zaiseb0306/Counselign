<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Message extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }

        return view('admin/messages');
    }

    public function operations()
    {
        
        // Check if user is logged in and is admin
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        $action = $this->request->getGet('action') ?? $this->request->getPost('action');
        $userId = session()->get('user_id_display');

        try {
            switch ($action) {
                case 'get_conversations':
                    return $this->getConversations($userId);
                case 'get_messages':
                    return $this->getMessages($userId);
                case 'send_message':
                    return $this->sendMessage($userId);
                case 'mark_read':
                    return $this->markMessagesAsRead($userId);
                default:
                    return $this->fail('Invalid action');
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function getConversations($userId)
    {
        $db = \Config\Database::connect();
        
        $sql = "SELECT DISTINCT
                CASE
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as user_id,
                u.user_id AS name,
                (SELECT message_text FROM messages
                 WHERE (sender_id = ? AND receiver_id = user_id)
                    OR (sender_id = user_id AND receiver_id = ?)
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages
                 WHERE (sender_id = ? AND receiver_id = user_id)
                    OR (sender_id = user_id AND receiver_id = ?)
                 ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages
                 WHERE sender_id = user_id
                    AND receiver_id = ?
                    AND is_read = 0) as unread_count,
                CASE
                    WHEN (u.last_active_at >= NOW() - INTERVAL 5 MINUTE OR u.last_login >= NOW() - INTERVAL 5 MINUTE)
                         AND (u.last_inactive_at IS NULL OR u.last_inactive_at < COALESCE(u.last_active_at, u.last_login))
                    THEN 1
                    ELSE 0
                END as is_online,
                CASE
                    WHEN (u.last_active_at >= NOW() - INTERVAL 5 MINUTE OR u.last_login >= NOW() - INTERVAL 5 MINUTE)
                         AND (u.last_inactive_at IS NULL OR u.last_inactive_at < COALESCE(u.last_active_at, u.last_login))
                    THEN 'Online'
                    WHEN u.last_inactive_at IS NOT NULL
                    THEN CASE
                        WHEN TIMESTAMPDIFF(MINUTE, u.last_active_at, NOW()) < 60 
                            THEN CONCAT('Active ', TIMESTAMPDIFF(MINUTE, u.last_active_at, NOW()), ' minutes ago')
                        WHEN TIMESTAMPDIFF(HOUR, u.last_active_at, NOW()) < 24
                            THEN CONCAT('Active ', TIMESTAMPDIFF(HOUR, u.last_active_at, NOW()), ' hour',
                                CASE WHEN TIMESTAMPDIFF(HOUR, u.last_active_at, NOW()) > 1 THEN 's' ELSE '' END, ' ago')
                        ELSE 'Offline'
                    END
                    ELSE 'Offline'
                END as status_text
                FROM messages m
                JOIN users u ON u.user_id = CASE
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY last_message_time DESC";

        $params = [
            $userId,
            $userId, $userId, $userId,
            $userId, $userId,
            $userId, $userId, $userId
        ];
        $query = $db->query($sql, $params);
        $conversations = $query->getResultArray();

        return $this->respond(['success' => true, 'conversations' => $conversations]);
    }

    private function getMessages($userId)
    {
        $db = \Config\Database::connect();
        $otherUserId = $this->request->getGet('user_id');

        if (!empty($otherUserId)) {
            $sql = "SELECT m.*, 
                    CASE
                        WHEN m.sender_id = ? THEN 'sent'
                        ELSE 'received'
                    END as message_type
                    FROM messages m
                    WHERE (m.sender_id = ? AND m.receiver_id = ?)
                       OR (m.sender_id = ? AND m.receiver_id = ?)
                    ORDER BY m.created_at ASC";
            
            $query = $db->query($sql, [
                $userId, $userId, $otherUserId, $otherUserId, $userId
            ]);
        } else {
            $sql = "SELECT * FROM messages WHERE sender_id = ? OR receiver_id = ?";
            $query = $db->query($sql, [$userId, $userId]);
        }

        $messages = $query->getResultArray();
        return $this->respond(['success' => true, 'messages' => $messages]);
    }

    private function sendMessage($userId)
    {
        $db = \Config\Database::connect();
        $receiverId = $this->request->getPost('receiver_id');
        $messageText = $this->request->getPost('message');

        if (empty($receiverId) || empty($messageText)) {
            return $this->fail('Missing required fields');
        }

        // Set Manila timezone
        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $currentTime = $manilaTime->format('Y-m-d H:i:s');

        // Insert message
        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) 
                VALUES (?, ?, ?)";
        
        $db->query($sql, [$userId, $receiverId, $messageText]);

        // Update admin's activity
        $db->table('users')
            ->where('user_id', $userId)
            ->update([
                'last_active_at' => $currentTime,
                'last_activity' => $currentTime
            ]);

        return $this->respond(['success' => true, 'message' => 'Message sent successfully']);
    }

    private function markMessagesAsRead($userId)
    {
        $db = \Config\Database::connect();
        
        $sql = "UPDATE messages SET is_read = TRUE 
                WHERE receiver_id = ? AND is_read = FALSE";
        
        $db->query($sql, [$userId]);
        
        return $this->respond(['success' => true, 'message' => 'Messages marked as read']);
    }
} 