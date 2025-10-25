<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Configure MySQL Settings for ACID Compliance
 * 
 * This migration sets up MySQL configuration parameters
 * to ensure optimal ACID compliance and performance.
 */
class ConfigureACIDSettings extends Migration
{
    public function up()
    {
        // Only set essential, safe settings that work on all MySQL versions
        // This keeps the migration simple and reduces the chance of errors
        
        // Set transaction isolation level to READ COMMITTED
        $this->db->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
        
        // Enable foreign key checks
        $this->db->query("SET SESSION foreign_key_checks = 1");
        
        // Enable unique checks
        $this->db->query("SET SESSION unique_checks = 1");
        
        // Set SQL mode for strict data validation
        $this->db->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    }

    public function down()
    {
        // Reset to default settings
        $this->db->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $this->db->query("SET SESSION sql_mode = ''");
    }
}
