<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResetTokenExpirationToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'reset_expires_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'after'   => 'verification_token', // Or wherever appropriate
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'reset_expires_at');
    }
}
