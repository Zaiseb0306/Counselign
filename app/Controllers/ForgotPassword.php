<?php
namespace App\Controllers;
use CodeIgniter\Controller;
use PHPMailer\PHPMailer\PHPMailer;

class ForgotPassword extends Controller
{
    private $systemEmail = 'systemsample13@gmail.com';
    public function sendCode()
    {
        $input = $this->request->getJSON()->input ?? '';
        
        // Validate input - if it's not an email, it must be exactly 10 digits
        $validation = \Config\Services::validation();
        
        // Check if input is an email or user_id
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            // It's an email, validate as email
            $validation->setRules([
                'input' => [
                    'label' => 'Email',
                    'rules' => 'required|valid_email',
                    'errors' => [
                        'required' => 'Email is required.',
                        'valid_email' => 'Please enter a valid email address.',
                    ],
                ],
            ]);
        } else {
            // It's not an email, validate as 10-digit user_id
            $validation->setRules([
                'input' => [
                    'label' => 'User ID',
                    'rules' => 'required|regex_match[/^\\d{10}$/]',
                    'errors' => [
                        'required' => 'User ID is required.',
                        'regex_match' => 'User ID must be exactly 10 digits.',
                    ],
                ],
            ]);
        }
        
        if (!$validation->run(['input' => $input])) {
            return $this->response->setJSON(['status'=>'error','message'=> implode(' ', $validation->getErrors())]);
        }
        $db = \Config\Database::connect();
        $email = '';
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $email = $input;
        } else {
            $user = $db->table('users')->where('user_id', $input)->get()->getRow();
            if ($user && $user->email) $email = $user->email;
        }
        if (!$email) return $this->response->setJSON(['status'=>'error','message'=>'User not found.']);
        date_default_timezone_set('Asia/Manila');
        $code = rand(100000,999999);
        $createdAt = date('Y-m-d H:i:s');
        $expiry = date('Y-m-d H:i:s', strtotime($createdAt . ' +5 minutes')); // Set expiry to 5 minutes
        $db->table('password_resets')->insert([
            'user_id' => $input,
            'reset_code' => $code,
            'reset_expires_at' => $expiry,
            'created_at' => $createdAt
        ]);
        log_message('debug', 'SQL Insert to password_resets (sendCode): ' . $db->getLastQuery());
        
        // Send email via PHPMailer (setup required)
        $mail = new PHPMailer(true); // Create the object first

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'systemsample13@gmail.com';
        $mail->Password = 'qxcikmevrevrqzsa';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom($this->systemEmail, 'Counselign');

        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Code';
        $mail->Body = "Hello,\n\nWe received a request to reset your password for your University Guidance Counseling System account.\n\nYour password reset code is: $code\n\nPlease enter this code in the password reset form. This code will expire in 5 minutes.\n\nIf you did not request a password reset, please ignore this email or contact support.\n\nThank you,\nUniversity Guidance Counseling System Team";
        $mail->send();
        return $this->response->setJSON(['status'=>'success']);
    }

    public function verifyCode()
    {
        $code = $this->request->getJSON()->code ?? '';
        $db = \Config\Database::connect();
        $row = $db->table('password_resets')->where('reset_code', $code)->get()->getRow();
        log_message('debug', 'SQL Select from password_resets (verifyCode): ' . $db->getLastQuery());

        if ($row) {
            // Check if the code has expired
            if (strtotime($row->reset_expires_at) < time()) {
                // Delete the expired code
                $db->table('password_resets')->where('reset_code', $code)->delete();
                log_message('debug', 'SQL Delete expired code (verifyCode): ' . $db->getLastQuery());
                return $this->response->setJSON(['status'=>'error','message'=>'Invalid or expired code.']);
            }

            session()->set('reset_user_id', $row->user_id);
            // Delete the used code after successful verification
            $db->table('password_resets')->where('reset_code', $code)->delete();
            log_message('debug', 'SQL Delete used code (verifyCode): ' . $db->getLastQuery());
            return $this->response->setJSON(['status'=>'success']);
        }
        return $this->response->setJSON(['status'=>'error','message'=>'Invalid or expired code.']);
    }

    public function setPassword()
    {
        $password = $this->request->getJSON()->password ?? '';
        // Enforce password rules for reset
        $validation = \Config\Services::validation();
        $validation->setRules([
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).{8,}$/]',
                'errors' => [
                    'required' => 'Password is required',
                    'min_length' => 'Password must be at least 8 characters long',
                    'regex_match' => 'Password must include uppercase, lowercase, number, and special character',
                ],
            ],
        ]);
        if (!$validation->run(['password' => $password])) {
            return $this->response->setJSON(['status'=>'error','message'=> implode(' ', $validation->getErrors())]);
        }
        $user_id = session()->get('reset_user_id');
        if (!$user_id) return $this->response->setJSON(['status'=>'error','message'=>'Session expired.']);
        $db = \Config\Database::connect();
        $db->table('users')->where('user_id', $user_id)
            ->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        log_message('debug', 'SQL Update users password (setPassword): ' . $db->getLastQuery());

        // Clear the reset token from the password_resets table after successful password update
        $db->table('password_resets')->where('user_id', $user_id)->delete();
        log_message('debug', 'SQL Delete reset token (setPassword): ' . $db->getLastQuery());

        session()->remove('reset_user_id');
        return $this->response->setJSON(['status'=>'success']);
    }
}
