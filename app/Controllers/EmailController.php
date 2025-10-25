<?php

namespace App\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController extends BaseController
{
    private $mailer;
    private $systemEmail = 'systemsample13@gmail.com'; // Replace with your system email
    private $systemEmailPassword = 'qxcikmevrevrqzsa'; // Replace with your email password
    private $recipientEmail = 'counselign2025@gmail.com'; // Replace with recipient email

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    private function setupMailer()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com'; // Replace with your SMTP host
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->systemEmail;
            $this->mailer->Password = $this->systemEmailPassword;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;

            // Default sender
            $this->mailer->setFrom($this->systemEmail, 'Counselign');
        } catch (Exception $e) {
            log_message('error', 'Mailer setup failed: ' . $e->getMessage());
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
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to send message. Please try again later.']);
        }
    }
} 