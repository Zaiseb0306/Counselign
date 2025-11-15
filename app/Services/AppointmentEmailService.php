<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Appointment Email Service
 * 
 * Handles sending email notifications to counselors when students
 * book or edit appointments. Uses PHPMailer for reliable email delivery.
 * Uses centralized email configuration from Config\Email.
 */
class AppointmentEmailService
{
    private PHPMailer $mailer;
    private \Config\Email $emailConfig;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->emailConfig = new \Config\Email();
        $this->setupMailer();
    }

    /**
     * Setup PHPMailer configuration using centralized Email config
     */
    private function setupMailer(): void
    {
        try {
            // Server settings from centralized config
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->emailConfig->SMTPHost;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->emailConfig->SMTPUser;
            $this->mailer->Password = $this->emailConfig->SMTPPass;
            $this->mailer->SMTPSecure = $this->emailConfig->SMTPCrypto === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port = $this->emailConfig->SMTPPort;
            $this->mailer->Timeout = $this->emailConfig->SMTPTimeout;
            $this->mailer->SMTPKeepAlive = $this->emailConfig->SMTPKeepAlive;

            // Default sender from centralized config
            $this->mailer->setFrom($this->emailConfig->fromEmail, $this->emailConfig->fromName);
            
            // Additional settings
            $this->mailer->CharSet = $this->emailConfig->charset;
            $this->mailer->WordWrap = $this->emailConfig->wordWrap;
            $this->mailer->Priority = $this->emailConfig->priority;
            
            log_message('info', 'AppointmentEmailService initialized with centralized email config');
        } catch (Exception $e) {
            log_message('error', 'AppointmentEmailService setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get counselor email address from users table using counselor_id
     * 
     * @param string $counselorId The counselor ID to look up
     * @return string|null The counselor's email address or null if not found
     */
    public function getCounselorEmail(string $counselorId): ?string
    {
        try {
            $db = \Config\Database::connect();
            $user = $db->table('users')
                ->select('email')
                ->where('user_id', $counselorId)
                ->where('role', 'counselor')
                ->get()
                ->getRowArray();

            return $user ? $user['email'] : null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting counselor email for ID ' . $counselorId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send appointment booking notification to counselor
     * 
     * @param string $counselorId The counselor ID
     * @param array $appointmentData The appointment details
     * @param array $studentData The student details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentBookingNotification(string $counselorId, array $appointmentData, array $studentData): bool
    {
        log_message('info', 'Starting appointment booking notification for counselor ID: ' . $counselorId);
        
        $counselorEmail = $this->getCounselorEmail($counselorId);
        
        if (!$counselorEmail) {
            log_message('error', 'Counselor email not found for ID: ' . $counselorId);
            return false;
        }

        log_message('info', 'Found counselor email: ' . $counselorEmail);

        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add counselor as recipient
            $this->mailer->addAddress($counselorEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'New Appointment Booking - ' . $this->emailConfig->fromName;
            
            // Create email body
            $emailBody = $this->createBookingEmailBody($appointmentData, $studentData);
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags($emailBody);

            log_message('info', 'Attempting to send appointment booking notification to: ' . $counselorEmail);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment booking notification sent successfully to counselor: ' . $counselorEmail);
                return true;
            } else {
                log_message('error', 'PHPMailer send() returned false for counselor: ' . $counselorEmail);
                return false;
            }
            
        } catch (Exception $e) {
            log_message('error', 'Failed to send appointment booking notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send appointment editing notification to counselor
     * 
     * @param string $counselorId The counselor ID
     * @param array $appointmentData The updated appointment details
     * @param array $studentData The student details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentEditNotification(string $counselorId, array $appointmentData, array $studentData): bool
    {
        log_message('info', 'Starting appointment edit notification for counselor ID: ' . $counselorId);
        
        $counselorEmail = $this->getCounselorEmail($counselorId);
        
        if (!$counselorEmail) {
            log_message('error', 'Counselor email not found for ID: ' . $counselorId);
            return false;
        }

        log_message('info', 'Found counselor email: ' . $counselorEmail);

        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add counselor as recipient
            $this->mailer->addAddress($counselorEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Updated - ' . $this->emailConfig->fromName;
            
            // Create email body
            $emailBody = $this->createEditEmailBody($appointmentData, $studentData);
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags($emailBody);

            log_message('info', 'Attempting to send appointment edit notification to: ' . $counselorEmail);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment edit notification sent successfully to counselor: ' . $counselorEmail);
                return true;
            } else {
                log_message('error', 'PHPMailer send() returned false for counselor: ' . $counselorEmail);
                return false;
            }
            
        } catch (Exception $e) {
            log_message('error', 'Failed to send appointment edit notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Create HTML email body for appointment booking notification
     * 
     * @param array $appointmentData The appointment details
     * @param array $studentData The student details
     * @return string The HTML email body
     */
    private function createBookingEmailBody(array $appointmentData, array $studentData): string
    {
        $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
        $studentEmail = $studentData['email'];
        $studentId = $studentData['user_id'];
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #060E57; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #060E57; }
                .student-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #28a745; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #060E57; }
                .value { margin-left: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📅 New Appointment Booking</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Counselor,</p>
                    
                    <p>A student has booked a new appointment with you. Please review the details below:</p>
                    
                    <div class='appointment-details'>
                        <h3>📋 Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='student-info'>
                        <h3>👤 Student Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$studentName}</span></p>
                        <p><span class='label'>Student ID:</span><span class='value'>{$studentId}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$studentEmail}</span></p>
                    </div>
                    
                    <p><strong>Action Required:</strong> Please log into the counseling system to review and approve this appointment.</p>
                    
                    <p>Thank you for your service!</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Create HTML email body for appointment editing notification
     * 
     * @param array $appointmentData The updated appointment details
     * @param array $studentData The student details
     * @return string The HTML email body
     */
    private function createEditEmailBody(array $appointmentData, array $studentData): string
    {
        $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
        $studentEmail = $studentData['email'];
        $studentId = $studentData['user_id'];
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #060E57; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ffc107; }
                .student-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #28a745; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #060E57; }
                .value { margin-left: 10px; }
                .notice { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✏️ Appointment Updated</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Counselor,</p>
                    
                    <div class='notice'>
                        <strong>⚠️ Notice:</strong> A student has updated their appointment details. Please review the changes below.
                    </div>
                    
                    <p>The following appointment has been modified:</p>
                    
                    <div class='appointment-details'>
                        <h3>📋 Updated Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='student-info'>
                        <h3>👤 Student Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$studentName}</span></p>
                        <p><span class='label'>Student ID:</span><span class='value'>{$studentId}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$studentEmail}</span></p>
                    </div>
                    
                    <p><strong>Action Required:</strong> Please log into the counseling system to review the updated appointment details.</p>
                    
                    <p>Thank you for your service!</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Test email configuration and sending capability
     * 
     * @param string $testEmail The email address to send test email to
     * @return array Test results with success status and details
     */
    public function testEmailConfiguration(string $testEmail): array
    {
        $results = [
            'config_test' => false,
            'smtp_test' => false,
            'send_test' => false,
            'errors' => []
        ];

        try {
            // Test 1: Configuration loading
            log_message('info', 'Testing email configuration...');
            $results['config_test'] = true;
            log_message('info', 'Email configuration loaded successfully');

            // Test 2: SMTP connection
            log_message('info', 'Testing SMTP connection...');
            $this->mailer->smtpConnect();
            $results['smtp_test'] = true;
            log_message('info', 'SMTP connection successful');
            $this->mailer->smtpClose();

            // Test 3: Send test email
            log_message('info', 'Sending test email to: ' . $testEmail);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($testEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Test Email - ' . $this->emailConfig->fromName;
            $this->mailer->Body = '<h2>Test Email</h2><p>This is a test email to verify email configuration.</p>';
            $this->mailer->AltBody = 'Test Email - This is a test email to verify email configuration.';

            $sendResult = $this->mailer->send();
            if ($sendResult) {
                $results['send_test'] = true;
                log_message('info', 'Test email sent successfully to: ' . $testEmail);
            } else {
                $results['errors'][] = 'Failed to send test email';
                log_message('error', 'Failed to send test email to: ' . $testEmail);
            }

        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            log_message('error', 'Email test failed: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
        }

        return $results;
    }

    /**
     * Send appointment cancellation notification to counselor
     * 
     * @param string $counselorId The counselor ID
     * @param array $appointmentData The appointment data
     * @param array $studentData The student details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentCancellationNotification(string $counselorId, array $appointmentData, array $studentData): bool
    {
        try {
            log_message('info', 'Sending appointment cancellation notification to counselor: ' . $counselorId);
            
            // Get counselor email
            $counselorEmail = $this->getCounselorEmail($counselorId);
            if (!$counselorEmail) {
                log_message('error', 'Counselor email not found for ID: ' . $counselorId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($counselorEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Cancellation - ' . $this->emailConfig->fromName;
            $this->mailer->Body = $this->createCancellationEmailBody($appointmentData, $studentData);
            $this->mailer->AltBody = $this->createCancellationEmailTextBody($appointmentData, $studentData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment cancellation notification sent successfully to: ' . $counselorEmail);
                return true;
            } else {
                log_message('error', 'Failed to send appointment cancellation notification to: ' . $counselorEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending appointment cancellation notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Create HTML email body for appointment cancellation notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $studentData The student details
     * @return string The HTML email body
     */
    private function createCancellationEmailBody(array $appointmentData, array $studentData): string
    {
        $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
        $studentEmail = $studentData['email'];
        $studentId = $studentData['user_id'];
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $cancellationReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
                .student-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ffc107; }
                .cancellation-info { background-color: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ffc107; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #dc3545; }
                .value { margin-left: 10px; }
                .warning { color: #856404; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>❌ Appointment Cancellation</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Counselor,</p>
                    
                    <p>A student has cancelled their appointment with you. Please review the details below:</p>
                    
                    <div class='cancellation-info'>
                        <h3>⚠️ Cancellation Details</h3>
                        <p><span class='label'>Cancellation Reason:</span><span class='value'>{$cancellationReason}</span></p>
                        <p class='warning'>This appointment slot is now available for other students.</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>📋 Cancelled Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='student-info'>
                        <h3>👤 Student Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$studentName}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$studentEmail}</span></p>
                        <p><span class='label'>Student ID:</span><span class='value'>{$studentId}</span></p>
                    </div>
                    
                    <p>Please note that this appointment has been cancelled and the time slot is now available.</p>
                    
                    <p>Best regards,<br>Counselign System</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Create plain text email body for appointment cancellation notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $studentData The student details
     * @return string The plain text email body
     */
    private function createCancellationEmailTextBody(array $appointmentData, array $studentData): string
    {
        $studentName = $studentData['first_name'] . ' ' . $studentData['last_name'];
        $studentEmail = $studentData['email'];
        $studentId = $studentData['user_id'];
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $cancellationReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "APPOINTMENT CANCELLATION NOTIFICATION\n\n" .
               "Dear Counselor,\n\n" .
               "A student has cancelled their appointment with you. Please review the details below:\n\n" .
               "CANCELLATION DETAILS:\n" .
               "Cancellation Reason: {$cancellationReason}\n" .
               "This appointment slot is now available for other students.\n\n" .
               "CANCELLED APPOINTMENT DETAILS:\n" .
               "Date: {$appointmentDate}\n" .
               "Time: {$appointmentTime}\n" .
               "Consultation Type: {$consultationType}\n" .
               "Purpose: {$purpose}\n" .
               "Description: {$description}\n\n" .
               "STUDENT INFORMATION:\n" .
               "Name: {$studentName}\n" .
               "Email: {$studentEmail}\n" .
               "Student ID: {$studentId}\n\n" .
               "Please note that this appointment has been cancelled and the time slot is now available.\n\n" .
               "Best regards,\n" .
               "Counselign System\n\n" .
               "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n" .
               "Please do not reply to this email.";
    }

    /**
     * Send appointment approval notification to student
     * 
     * @param string $studentId The student ID
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentApprovalNotification(string $studentId, array $appointmentData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending appointment approval notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Approved - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createApprovalEmailBody($appointmentData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createApprovalEmailTextBody($appointmentData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment approval notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send appointment approval notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending appointment approval notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send appointment rejection notification to student
     * 
     * @param string $studentId The student ID
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentRejectionNotification(string $studentId, array $appointmentData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending appointment rejection notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Rejected - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createRejectionEmailBody($appointmentData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createRejectionEmailTextBody($appointmentData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment rejection notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send appointment rejection notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending appointment rejection notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send appointment cancellation notification to student
     * 
     * @param string $studentId The student ID
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendAppointmentCancellationByCounselorNotification(string $studentId, array $appointmentData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending appointment cancellation notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Appointment Cancelled by Counselor - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createCounselorCancellationEmailBody($appointmentData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createCounselorCancellationEmailTextBody($appointmentData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Appointment cancellation notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send appointment cancellation notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending appointment cancellation notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Get student email by student ID
     * 
     * @param string $studentId The student ID
     * @return string|null The student email or null if not found
     */
    private function getStudentEmail(string $studentId): ?string
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->table('users')
                ->select('email')
                ->where('user_id', $studentId)
                ->get()
                ->getRowArray();

            return $result ? $result['email'] : null;
        } catch (Exception $e) {
            log_message('error', 'Error getting student email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send follow-up created notification to student
     */
    public function sendFollowUpCreatedNotification(string $studentId, array $followUpData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending follow-up created notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'New Follow-up Session Created - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createFollowUpCreatedEmailBody($followUpData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createFollowUpCreatedEmailTextBody($followUpData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Follow-up created notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send follow-up created notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending follow-up created notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send follow-up edited notification to student
     */
    public function sendFollowUpEditedNotification(string $studentId, array $followUpData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending follow-up edited notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Follow-up Session Updated - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createFollowUpEditedEmailBody($followUpData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createFollowUpEditedEmailTextBody($followUpData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Follow-up edited notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send follow-up edited notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending follow-up edited notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send follow-up completed notification to student
     */
    public function sendFollowUpCompletedNotification(string $studentId, array $followUpData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending follow-up completed notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Follow-up Session Completed - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createFollowUpCompletedEmailBody($followUpData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createFollowUpCompletedEmailTextBody($followUpData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Follow-up completed notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send follow-up completed notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending follow-up completed notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send follow-up cancelled notification to student
     */
    public function sendFollowUpCancelledNotification(string $studentId, array $followUpData, array $counselorData): bool
    {
        try {
            log_message('info', 'Sending follow-up cancelled notification to student: ' . $studentId);
            
            // Get student email
            $studentEmail = $this->getStudentEmail($studentId);
            if (!$studentEmail) {
                log_message('error', 'Student email not found for ID: ' . $studentId);
                return false;
            }

            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($studentEmail);
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Follow-up Session Cancelled - ' . $this->emailConfig->fromName;
            $this->mailer->Body = \App\Services\CounselorEmailTemplates::createFollowUpCancelledEmailBody($followUpData, $counselorData);
            $this->mailer->AltBody = \App\Services\CounselorEmailTemplates::createFollowUpCancelledEmailTextBody($followUpData, $counselorData);

            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                log_message('info', 'Follow-up cancelled notification sent successfully to: ' . $studentEmail);
                return true;
            } else {
                log_message('error', 'Failed to send follow-up cancelled notification to: ' . $studentEmail);
                log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
                return false;
            }

        } catch (Exception $e) {
            log_message('error', 'Error sending follow-up cancelled notification: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
