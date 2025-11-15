<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVerificationToUsers extends Migration
{
    public function up()
    {
        // Check if columns already exist before adding them
        $fields = $this->db->getFieldData('users');
        $fieldNames = array_column($fields, 'name');
        
        $columnsToAdd = [];
        
        if (!in_array('verification_token', $fieldNames)) {
            $columnsToAdd['verification_token'] = [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'password',
            ];
        }
        
        if (!in_array('is_verified', $fieldNames)) {
            $columnsToAdd['is_verified'] = [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'after'      => 'verification_token',
            ];
        }
        
        if (!empty($columnsToAdd)) {
            $this->forge->addColumn('users', $columnsToAdd);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'verification_token');
        $this->forge->dropColumn('users', 'is_verified');
    }
}
