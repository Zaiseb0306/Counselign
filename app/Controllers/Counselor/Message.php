<?php

namespace App\Controllers\Counselor;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;
use CodeIgniter\API\ResponseTrait;

class Message extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return redirect()->to('/');
        }

        return view('counselor/messages');
    }

    public function operations()
    {
        
        // Check if user is logged in and is counselor
        if (!session()->get('logged_in') || session()->get('role') !== 'counselor') {
            return $this->failUnauthorized('User not logged in or not authorized');
        }

        $action = $this->request->getGet('action') ?? $this->request->getPost('action');
        $userId = session()->get('user_id_display');

        try {
            switch ($action) {
                case 'get_conversations':
                    return $this->getConversations($userId);
                case 'get_dashboard_messages':
                    return $this->getDashboardMessages($userId);
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
        
        // Optional limit via query string (e.g., limit=2) for dashboard previews
        $limitParam = (int) ($this->request->getGet('limit') ?? 0);
        $limitSql = $limitParam > 0 ? (' LIMIT ' . $limitParam) : '';

        // Simplified query to avoid subquery scoping issues
        $sql = "SELECT 
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id
                        ELSE m.sender_id
                    END as other_user_id,
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), u.username) as other_username,
                    u.email as other_email,
                    u.profile_picture as other_profile_picture,
                    u.last_activity,
                    u.last_login,
                    u.logout_time,
                    m.created_at as last_message_time,
                    m.message_text as last_message,
                    CASE 
                        WHEN m.sender_id = ? THEN 'sent'
                        ELSE 'received'
                    END as last_message_type,
                    m.is_read
                FROM messages m
                LEFT JOIN users u ON u.user_id = CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END
                LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                WHERE (m.sender_id = ? OR m.receiver_id = ?)
                  AND u.role = 'student' 
                ORDER BY m.created_at DESC" . $limitSql;

        $query = $db->query($sql, [$userId, $userId, $userId, $userId, $userId]);
        $messages = $query->getResultArray();

        // Group by other_user_id to get unique conversations
        $conversations = [];
        $seenUsers = [];
        
        foreach ($messages as $message) {
            $otherUserId = $message['other_user_id'];
            if (!in_array($otherUserId, $seenUsers)) {
                $seenUsers[] = $otherUserId;
                $conversations[] = [
                    'other_user_id' => $message['other_user_id'],
                    'other_username' => $message['other_username'],
                    'other_email' => $message['other_email'],
                    'other_profile_picture' => $message['other_profile_picture'],
                    'last_activity' => $message['last_activity'],
                    'last_login' => $message['last_login'],
                    'logout_time' => $message['logout_time'],
                    'last_message_time' => $message['last_message_time'],
                    'last_message' => $message['last_message'],
                    'last_message_type' => $message['last_message_type'],
                    'unread_count' => ($message['last_message_type'] === 'received' && !$message['is_read']) ? 1 : 0
                ];
            }
        }

        return $this->respond(['success' => true, 'conversations' => $conversations]);
    }

    private function getDashboardMessages($userId)
    {
        $db = \Config\Database::connect();
        
        // Optional limit via query string (e.g., limit=2) for dashboard previews
        $limitParam = (int) ($this->request->getGet('limit') ?? 0);
        $limitSql = $limitParam > 0 ? (' LIMIT ' . $limitParam) : '';

        // Only get messages where counselor is the receiver (messages sent TO the counselor)
        // Simplified query to avoid subquery issues
        $sql = "SELECT 
                    m.sender_id as other_user_id,
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), u.username) as other_username,
                    u.email as other_email,
                    u.profile_picture as other_profile_picture,
                    u.last_activity,
                    u.last_login,
                    u.logout_time,
                    m.created_at as last_message_time,
                    m.message_text as last_message,
                    m.is_read
                FROM messages m
                LEFT JOIN users u ON u.user_id = m.sender_id
                LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                WHERE m.receiver_id = ? AND u.role = 'student'
                ORDER BY m.created_at DESC" . $limitSql;

        $query = $db->query($sql, [$userId]);
        $messages = $query->getResultArray();

        // Group by sender to get unique conversations
        $conversations = [];
        $seenSenders = [];
        
        foreach ($messages as $message) {
            $senderId = $message['other_user_id'];
            if (!in_array($senderId, $seenSenders)) {
                $seenSenders[] = $senderId;
                $conversations[] = [
                    'other_user_id' => $message['other_user_id'],
                    'other_username' => $message['other_username'],
                    'other_email' => $message['other_email'],
                    'other_profile_picture' => $message['other_profile_picture'],
                    'last_activity' => $message['last_activity'],
                    'last_login' => $message['last_login'],
                    'logout_time' => $message['logout_time'],
                    'last_message_time' => $message['last_message_time'],
                    'last_message' => $message['last_message'],
                    'unread_count' => $message['is_read'] ? 0 : 1
                ];
            }
        }

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
        
        // Update last_activity for viewing messages
        $activityHelper = new UserActivityHelper();
        $activityHelper->updateCounselorActivity($userId, 'view_messages');
        
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

        // Update last_activity for sending message (dynamic ID detection)
        $activityHelper = new UserActivityHelper();
        $activityHelper->updateCounselorActivity(null, 'send_message', [
            'sender_id' => $userId,
            'receiver_id' => $receiverId
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
