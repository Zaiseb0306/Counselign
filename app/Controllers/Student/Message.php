<?php

namespace App\Controllers\Student;

use CodeIgniter\Controller;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use App\Controllers\BaseController;

class Message extends BaseController
{
    public function operations()
    {
        $session = session();
        $db = \Config\Database::connect();
        $response = ['success' => false, 'message' => 'Invalid action'];

        // Check if user is logged in
        $user_id = $session->get('user_id_display') ?? $session->get('user_id');
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not logged in']);
        }

        // Get action from GET or POST
        $action = $this->request->getGet('action') ?? $this->request->getPost('action');

        try {
            switch ($action) {
                case 'get_conversations':
                    // Use Query Builder for security and compatibility
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
                            AND is_read = 0) as unread_count
                    FROM messages m
                    JOIN users u ON u.user_id = CASE
                        WHEN m.sender_id = ? THEN m.receiver_id
                        ELSE m.sender_id
                    END
                    WHERE m.sender_id = ? OR m.receiver_id = ?
                    ORDER BY last_message_time DESC";

                    $query = $db->query($sql, [
                        $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id
                    ]);
                    $conversations = $query->getResultArray();
                    $response = ['success' => true, 'conversations' => $conversations];
                    break;

                case 'get_messages':
                    $other_user_id = $this->request->getGet('user_id');
                    if ($other_user_id) {
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
                            $user_id, $user_id, $other_user_id, $other_user_id, $user_id
                        ]);
                        $messages = $query->getResultArray();
                        $response = ['success' => true, 'messages' => $messages];
                    } else {
                        $sql = "SELECT * FROM messages WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at ASC";
                        $query = $db->query($sql, [$user_id, $user_id]);
                        $messages = $query->getResultArray();
                        $response = ['success' => true, 'messages' => $messages];
                    }
                    break;

                case 'send_message':
                    $receiver_id = $this->request->getPost('receiver_id');
                    $message_text = $this->request->getPost('message');
                    if ($receiver_id && $message_text) {
                        // Set Manila timezone
                        $manilaTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
                        $currentTime = $manilaTime->format('Y-m-d H:i:s');

                        // Insert message
                        $db->table('messages')->insert([
                            'sender_id' => $user_id,
                            'receiver_id' => $receiver_id,
                            'message_text' => $message_text
                        ]);

                        // Update user's activity
                        $db->table('users')
                            ->where('user_id', $user_id)
                            ->update([
                                'last_active_at' => $currentTime,
                                'last_activity' => $currentTime
                            ]);

                        $response = ['success' => true, 'message' => 'Message sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Missing required fields'];
                    }
                    break;

                case 'mark_read':
                    $db->table('messages')
                        ->where('receiver_id', $user_id)
                        ->where('is_read', 0)
                        ->update(['is_read' => 1]);
                    $response = ['success' => true, 'message' => 'Messages marked as read'];
                    break;

                default:
                    $response = ['success' => false, 'message' => 'Invalid action'];
                    break;
            }
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->response->setJSON($response);
    }
}
