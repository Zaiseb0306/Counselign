<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Helpers\UserActivityHelper;
use CodeIgniter\API\ResponseTrait;
use App\Models\AppointmentsModel;

class Appointments extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }
        
        return view('admin/appointments');
    }

    public function getAll()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as administrator',
                'appointments' => []
            ], 401);
        }

        $db = \Config\Database::connect();

        // Use counselor's exact working pattern but for ALL appointments (no WHERE clause)
        $query = "SELECT
                    a.*,
                    u.email as user_email,
                    u.username,
                    COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), u.username) AS student_name,
                    CONCAT(sai.course, ' - ', sai.year_level) as course_year,
                    sai.course,
                    sai.year_level,
                    COALESCE(c.name, 'No Preference') as counselor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.student_id = u.user_id
                  LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                  LEFT JOIN student_academic_info sai ON sai.student_id = u.user_id
                  LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
                  ORDER BY a.created_at DESC";

        $appointments = $db->query($query)->getResultArray();

        return $this->respond([
            'status' => 'success',
            'appointments' => $appointments
        ]);
    }

    public function getLatest()
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Log file (optional, for debugging)
        $logFile = WRITEPATH . 'appointments_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);

        try {
            // Get database connection
            $db = \Config\Database::connect();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - DB connected\n", FILE_APPEND);

            // Test if table exists
            $tables = $db->query("SHOW TABLES LIKE 'appointments'")->getResult();
            if (count($tables) === 0) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                exit;
            }

            // Get appointments
            $query = "SELECT id, student_id, preferred_date, preferred_time, created_at
                      FROM appointments
                      ORDER BY created_at DESC, preferred_time DESC
                      LIMIT 2";
            $appointments = $db->query($query)->getResultArray();

            echo json_encode([
                'success' => true,
                'data' => $appointments ?: []
            ]);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found " . count($appointments) . " appointments\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Response sent successfully\n", FILE_APPEND);
            exit;

        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return $this->failServerError('Server error');
        }
    }

    public function updateStatus()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');

        if (!$id || !$status) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Missing required parameters'
            ], 400);
        }

        $db = \Config\Database::connect();
        
        // Get appointment details to find the student
        $appointment = $db->table('appointments')
            ->where('id', $id)
            ->get()
            ->getRowArray();
        
        if ($appointment) {
            // Update appointment status
            $db->table('appointments')
               ->where('id', $id)
               ->update(['status' => $status]);
            
            // Update last_activity for both admin and student
            $activityHelper = new UserActivityHelper();
            $adminId = session()->get('user_id_display');
            $activityHelper->updateAdminActivity($adminId, 'update_appointment_status');
            $activityHelper->updateStudentActivity($appointment['student_id'], 'appointment_status_updated');
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Appointment status updated successfully'
        ]);
    }

    public function getAppointments()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized access - Please log in as administrator',
                'appointments' => []
            ])->setStatusCode(401);
        }

        $db = \Config\Database::connect();

        // Use the counselor's proven JOIN pattern, but without counselor filtering (admin sees ALL)
        $query = "SELECT
                    a.*,
                    u.email as user_email,
                    u.username,
                    CONCAT(sai.course, ' - ', sai.year_level) as course_year,
                    sai.course,
                    sai.year_level,
                    CONCAT(spi.first_name, ' ', spi.last_name) as student_name,
                    COALESCE(c.name, 'No Preference') as counselor_name
                  FROM appointments a
                  LEFT JOIN users u ON a.student_id = u.user_id
                  LEFT JOIN student_academic_info sai ON sai.student_id = u.user_id
                  LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                  LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
                  ORDER BY a.created_at DESC";

        $appointments = $db->query($query)->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'appointments' => $appointments
        ]);
    }

    private function sendAppointmentEmail($user_email, $appointment_details, $status, $rejection_reason = null)
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'systemsample13@gmail.com';
            $mail->Password = 'qxcikmevrevrqzsa';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('systemsample13@gmail.com', 'University Guidance Counseling System');
            $mail->addAddress($user_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Status Update';

            // Format date and time
            $date = date('F j, Y', strtotime($appointment_details['preferred_date']));
            $time = $appointment_details['preferred_time'];

            // Create email body based on status
            if ($status === 'approved') {
                $mail->Body = "
                    <h2>Appointment Approved!</h2>
                    <p>Dear {$appointment_details['user_username']},</p>
                    <p>Your counseling appointment has been approved. Here are the details:</p>
                    <ul>
                        <li><strong>Date:</strong> {$date}</li>
                        <li><strong>Time:</strong> {$time}</li>
                        <li><strong>Consultation Type:</strong> {$appointment_details['method_type']}</li>
                        <li><strong>Counselor:</strong> {$appointment_details['counselor_name']}</li>
                    </ul>
                    <p>Please arrive 10 minutes before your scheduled time. If you need to reschedule or cancel, please do so at least 24 hours in advance.</p>
                    <p>Best regards,<br>University Guidance Counseling Office</p>";
            } else if ($status === 'rejected') {
                $mail->Body = "
                    <h2>Appointment Rejected</h2>
                    <p>Dear {$appointment_details['user_username']},</p>
                    <p>We regret to inform you that your counseling appointment has been rejected.</p>
                    <p><strong>Appointment Details:</strong></p>
                    <ul>
                        <li><strong>Date:</strong> {$date}</li>
                        <li><strong>Time:</strong> {$time}</li>
                        <li><strong>Consultation Type:</strong> {$appointment_details['method_type']}</li>
                    </ul>
                    <p><strong>Reason for Rejection:</strong><br>{$rejection_reason}</p>
                    <p>Please feel free to schedule another appointment with different details.</p>
                    <p>Best regards,<br>University Guidance Counseling Office</p>";
            } else if ($status === 'cancelled') {
                $mail->Body = "
                    <h2>Appointment Cancelled</h2>
                    <p>Dear {$appointment_details['user_username']},</p>
                    <p>We regret to inform you that your counseling appointment has been cancelled by the admin.</p>
                    <p><strong>Appointment Details:</strong></p>
                    <ul>
                        <li><strong>Date:</strong> {$date}</li>
                        <li><strong>Time:</strong> {$time}</li>
                        <li><strong>Consultation Type:</strong> {$appointment_details['method_type']}</li>
                        <li><strong>Counselor:</strong> {$appointment_details['counselor_name']}</li>
                    </ul>
                    <p><strong>Reason for Cancellation:</strong><br>{$rejection_reason}</p>
                    <p>Please feel free to schedule another appointment.</p>
                    <p>Best regards,<br>University Guidance Counseling Office</p>";
            }

            $mail->send();
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateAppointmentStatus()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access - Please log in as administrator'
            ])->setStatusCode(401);
        }

        $appointment_id = $this->request->getPost('appointment_id');
        $new_status = strtolower($this->request->getPost('status')); // Convert to lowercase
        $rejection_reason = $this->request->getPost('rejection_reason');

        if (!$appointment_id || !$new_status) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing required parameters']);
        }

        // If status is rejected, require rejection reason
        if ($new_status === 'rejected' && empty($rejection_reason)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Rejection reason is required'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Set timezone to Asia/Manila
        $dt = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $manilaTime = $dt->format('Y-m-d H:i:s');

        // Get appointment details with user and counselor information before updating
        $appointmentQuery = "SELECT 
            a.*,
            u.email as user_email,
            u.username as user_username,
            COALESCE(c.name, 'No Preference') as counselor_name
            FROM appointments a
            LEFT JOIN users u ON a.student_id = u.user_id
            LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
            WHERE a.id = ?";
        $appointment_details = $db->query($appointmentQuery, [$appointment_id])->getRowArray();

        // Update appointment status and reason
        $builder = $db->table('appointments');
        $builder->where('id', $appointment_id);
        $updateData = [
            'status' => $new_status,
            'updated_at' => $manilaTime
        ];

        // Add reason if status is rejected or cancelled
        if (($new_status === 'rejected' || $new_status === 'cancelled') && !empty($rejection_reason)) {
            $updateData['reason'] = 'Reason from Admin: ' . $rejection_reason;
        }

        $builder->update($updateData);

        // Get student_id for notification
        $user = $db->table('appointments')->select('student_id')->where('id', $appointment_id)->get()->getRowArray();
        if ($user) {
            // Create notification for student when status is approved, rejected, or cancelled
            if (in_array($new_status, ['approved', 'rejected', 'cancelled'])) {
                $this->createNotification($db, $user['student_id'], $appointment_id, $new_status, $rejection_reason);
            }
            
            // Send email notification
            if ($appointment_details && in_array($new_status, ['approved', 'rejected', 'cancelled'])) {
                $this->sendAppointmentEmail(
                    $appointment_details['user_email'],
                    $appointment_details,
                    $new_status,
                    $rejection_reason
                );
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update appointment status']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Appointment status updated successfully']);
    }

    public function scheduled()
    {
        // Check if admin is logged in
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return redirect()->to('/');
        }
        
        return view('admin/scheduled_appointments');
    }

    public function getScheduledAppointments()
    {
        try {
            // Check if admin is logged in
            if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Unauthorized access - Please log in as administrator',
                    'appointments' => []
                ], 401);
            }

            $db = \Config\Database::connect();
            
            // Simplified query to get approved appointments with user information
            $query = "SELECT
                        a.*,
                        a.updated_at,
                        u.email,
                        u.username,
                        COALESCE(CONCAT(spi.last_name, ', ', spi.first_name), u.username) AS student_name,
                        COALESCE(c.name, a.counselor_preference, 'No Preference') as counselorPreference
                      FROM appointments a
                      LEFT JOIN users u ON a.student_id = u.user_id
                      LEFT JOIN student_personal_info spi ON spi.student_id = u.user_id
                      LEFT JOIN counselors c ON c.counselor_id = a.counselor_preference
                      WHERE a.status = 'approved'
                      ORDER BY a.preferred_date ASC, a.preferred_time ASC";

            $appointments = $db->query($query)->getResultArray();

            // Log for debugging
            log_message('info', 'Admin getScheduledAppointments query executed successfully. Found ' . count($appointments) . ' approved appointments.');

            if (empty($appointments)) {
                return $this->respond([
                    'status' => 'success',
                    'message' => 'No approved appointments found',
                    'appointments' => []
                ]);
            }

            return $this->respond([
                'status' => 'success',
                'appointments' => $appointments
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            log_message('error', 'Admin getScheduledAppointments error: ' . $e->getMessage());
            
            return $this->respond([
                'status' => 'error',
                'message' => 'Database error occurred while fetching scheduled appointments',
                'appointments' => []
            ], 500);
        }
    }

    public function viewAll()
    {
        return view('admin/view_all_appointments');
    }

    // Helper function for notification
    private function createNotification($db, $student_id, $appointment_id, $status, $rejection_reason = null)
    {
        try {
            $notificationsModel = new \App\Models\NotificationsModel();
            
            $type = 'appointment';
            $title = 'Appointment ' . ucfirst($status);

            // Fetch appointment details for date/time
            $appt = $db->table('appointments')->select('preferred_date, preferred_time')->where('id', $appointment_id)->get()->getRowArray();
            $date = $appt ? date('F j, Y', strtotime($appt['preferred_date'])) : '';
            $time = $appt ? $appt['preferred_time'] : '';

            // Create appropriate message based on status
            if ($status === 'approved') {
                $message = "Congratulations! Your appointment on {$date} at {$time} has been approved. Please check your scheduled appointments for details.";
            } else if ($status === 'rejected') {
                $message = "We're sorry, but your appointment on {$date} at {$time} was rejected.";
                if ($rejection_reason) {
                    $message .= " Reason: {$rejection_reason}.";
                }
                $message .= " If you have questions, please contact the counseling office.";
            } else if ($status === 'cancelled') {
                $message = "Your appointment on {$date} at {$time} has been cancelled by the admin.";
                if ($rejection_reason) {
                    $message .= " Reason: {$rejection_reason}.";
                }
            } else {
                $message = "Your appointment status has been updated to {$status}.";
            }

            $notificationData = [
                'user_id' => $student_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_id' => $appointment_id,
                'is_read' => 0
            ];
            
            $notificationsModel->createNotification($notificationData);
        } catch (\Exception $e) {
            log_message('error', 'Error creating admin appointment notification: ' . $e->getMessage());
        }
    }

    /**
     * Track export activity for admin reports
     */
    public function trackExport()
    {
        try {
            if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $adminId = session()->get('user_id_display') ?? session()->get('user_id');
            $exportType = $this->request->getPost('export_type') ?? 'appointments_report';
            
            // Update last_activity for exporting reports
            $activityHelper = new UserActivityHelper();
            $activityHelper->updateAdminActivity($adminId, 'export_reports');
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Export activity tracked'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error tracking admin export activity: ' . $e->getMessage());
            return $this->respond([
                'status' => 'error',
                'message' => 'Error tracking export activity'
            ], 500);
        }
    }
}
