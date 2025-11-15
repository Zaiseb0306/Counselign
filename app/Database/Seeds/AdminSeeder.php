<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        $adminUserId = '0000000001';
        $adminEmail = 'systemsample13@gmail.com';
        $adminUsername = 'admin';
        $adminPasswordPlain = 'Counselign@2025';

        $existing = $userModel->where('user_id', $adminUserId)->first();

        $data = [
            'user_id' => $adminUserId,
            'email' => $adminEmail,
            'username' => $adminUsername,
            'password' => password_hash($adminPasswordPlain, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_verified' => 1,
            'profile_picture' => '/Photos/profile.png',
        ];

        if ($existing) {
            // Preserve existing email if it's different to avoid unique constraint issues
            if (!empty($existing['email']) && $existing['email'] !== $adminEmail) {
                $data['email'] = $existing['email'];
            }
            $userModel->update($existing['id'], $data);
        } else {
            $userModel->insert($data);
        }
    }
}


