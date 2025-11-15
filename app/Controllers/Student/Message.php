<?php

namespace App\Controllers\Student;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Controller;
use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;

class Message extends BaseController
{
    public function index()
    {
        // Ensure student is logged in; if not, send back to student dashboard
        if (!session()->get('logged_in') || session()->get('role') !== 'student') {
            return redirect()->to(base_url('student/dashboard'));
        }
        return view('student/messages');
    }
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
                case 'get_counselor_conversations':
                    // Get all counselors with latest message data
                    $counselors = $db->table('counselors c')
                        ->select('c.counselor_id, c.name')
                        ->join('users u', 'u.user_id = c.counselor_id', 'left')
                        ->select('u.profile_picture, u.last_activity, u.last_login, u.logout_time')
                        ->orderBy('c.name', 'ASC')
                        ->get()
                        ->getResultArray();

                    // For each counselor, get latest message data
                    foreach ($counselors as &$counselor) {
                        $counselorId = $counselor['counselor_id'];
                        
                        // Get latest message between student and counselor
                        $latestMessageSql = "SELECT message_text, created_at, sender_id 
                            FROM messages 
                            WHERE (sender_id = ? AND receiver_id = ?) 
                               OR (sender_id = ? AND receiver_id = ?) 
                            ORDER BY created_at DESC LIMIT 1";
                        
                        $latestMessage = $db->query($latestMessageSql, [
                            $user_id, $counselorId, $counselorId, $user_id
                        ])->getRowArray();

                        if ($latestMessage) {
                            $counselor['last_message'] = $latestMessage['message_text'];
                            $counselor['last_message_time'] = $latestMessage['created_at'];
                            $counselor['last_message_type'] = ($latestMessage['sender_id'] === $user_id) ? 'sent' : 'received';
                        } else {
                            $counselor['last_message'] = null;
                            $counselor['last_message_time'] = null;
                            $counselor['last_message_type'] = null;
                        }

                        // Get unread count (messages from counselor that student hasn't read)
                        $unreadCount = $db->table('messages')
                            ->where('sender_id', $counselorId)
                            ->where('receiver_id', $user_id)
                            ->where('is_read', 0)
                            ->countAllResults();

                        $counselor['unread_count'] = $unreadCount;
                    }

                    // Sort by last message time (newest first), then by name
                    usort($counselors, function($a, $b) {
                        if ($a['last_message_time'] && $b['last_message_time']) {
                            return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
                        }
                        if ($a['last_message_time']) return -1;
                        if ($b['last_message_time']) return 1;
                        return strcmp($a['name'], $b['name']);
                    });

                    $response = ['success' => true, 'counselors' => $counselors];
                    break;

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
                    
                    // Update last_activity for viewing messages
                    $activityHelper = new UserActivityHelper();
                    $activityHelper->updateStudentActivity($user_id, 'view_messages');
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

                        // Update last_activity for sending message (dynamic ID detection)
                        $activityHelper = new UserActivityHelper();
                        $activityHelper->updateStudentActivity(null, 'send_message', [
                            'sender_id' => $user_id,
                            'receiver_id' => $receiver_id
                        ]);

                        $response = ['success' => true, 'message' => 'Message sent successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Missing required fields'];
                    }
                    break;

                case 'mark_read':
                    // Mark messages from specific sender as read
                    $sender_id = $this->request->getPost('user_id') ?? $this->request->getGet('user_id');
                    if ($sender_id) {
                        $db->table('messages')
                            ->where('sender_id', $sender_id)
                            ->where('receiver_id', $user_id)
                            ->where('is_read', 0)
                            ->update(['is_read' => 1]);
                    } else {
                        // If no sender specified, mark all as read
                        $db->table('messages')
                            ->where('receiver_id', $user_id)
                            ->where('is_read', 0)
                            ->update(['is_read' => 1]);
                    }
                    $response = ['success' => true, 'message' => 'Messages marked as read'];
                    break;

                case 'get_unread_count':
                    // Get total unread message count from all counselors
                    $unreadCount = $db->table('messages')
                        ->where('receiver_id', $user_id)
                        ->where('is_read', 0)
                        ->join('users u', 'u.user_id = messages.sender_id', 'left')
                        ->where('u.role', 'counselor')
                        ->countAllResults();
                    // Ensure type-safe integer response
                    $response = ['success' => true, 'unread_count' => (int)$unreadCount];
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
