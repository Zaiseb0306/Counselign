<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Helpers\SecureLogHelper;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'email',
        'password',
        'verification_token',
        'is_verified',
        'username',
        'role',
        'profile_picture',
        'created_at',
        'last_login',
        'logout_time',
        'last_activity',
        'last_active_at',
        'last_inactive_at'
    ];  // Disable updated_at since we don't have this column

    protected $validationRules = [
        'user_id' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email]',
        'password' => 'required|min_length[8]' // Changed min_length from 6 to 8 to match frontend validation
    ];

    protected $validationMessages = [
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Please enter a valid email address',
            'is_unique' => 'This email is already registered'
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 6 characters long'
        ]
    ];

    public function generateVerificationToken(): string
    {
        // Generate a 6-character uppercase alphanumeric token (A-Z, 0-9)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $tokenLength = 6;

        $token = '';
        for ($i = 0; $i < $tokenLength; $i++) {
            // Use cryptographically secure randomness
            $randomIndex = random_int(0, $charactersLength - 1);
            $token .= $characters[$randomIndex];
        }

        return $token;
    }

    public function setVerificationToken(int $userId, string $token)
    {
        $this->update($userId, ['verification_token' => $token]);
    }

    public function markUserAsVerified(int $userId)
    {
        $this->update($userId, ['is_verified' => true, 'verification_token' => null]);
    }

    public function getUserByVerificationToken(string $token)
    {
        SecureLogHelper::debug('Searching for user by verification token');
        return $this->where('verification_token', $token)->first();
    }
}
