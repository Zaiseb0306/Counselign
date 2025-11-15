<?php

namespace App\Controllers;


use App\Helpers\SecureLogHelper;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController extends BaseController
{
    private PHPMailer $mailer;
    private \Config\Email $emailConfig;
    private string $recipientEmail = 'counselign2025@gmail.com'; // Contact form recipient

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
            
            log_message('info', 'EmailController initialized with centralized email config');
        } catch (Exception $e) {
            log_message('error', 'EmailController setup failed: ' . $e->getMessage());
        }
    }

    public function sendContactEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request method']);
        }

        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');

        // Validate email format strictly
        $validation = \Config\Services::validation();
        $validation->setRules([
            'name' => [
                'label' => 'Name',
                'rules' => 'required',
                'errors' => [ 'required' => 'Name is required' ],
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'Email is required',
                    'valid_email' => 'Please enter a valid email address',
                ],
            ],
            'subject' => [
                'label' => 'Subject',
                'rules' => 'required',
                'errors' => [ 'required' => 'Subject is required' ],
            ],
            'message' => [
                'label' => 'Message',
                'rules' => 'required',
                'errors' => [ 'required' => 'Message is required' ],
            ],
        ]);
        if (!$validation->run(compact('name','email','subject','message'))) {
            return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $validation->getErrors())]);
        }

        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Recipients
            $this->mailer->addAddress($this->recipientEmail);
            $this->mailer->addReplyTo($email, $name);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Contact Form: " . $subject;
            
            // Email body
            $emailBody = "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            ";
            
            $this->mailer->Body = $emailBody;
            $this->mailer->AltBody = strip_tags($emailBody);

            $this->mailer->send();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Message sent successfully']);
        } catch (Exception $e) {
            log_message('error', 'Email sending failed: ' . $e->getMessage());
            log_message('error', 'PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo);
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to send message. Please try again later.']);
        }
    }
} 
