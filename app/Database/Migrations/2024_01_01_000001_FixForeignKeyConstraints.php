<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fix Foreign Key Constraints for ACID Compliance
 * 
 * This migration addresses the foreign key constraint issues identified
 * in the ACID compliance analysis.
 */
class FixForeignKeyConstraints extends Migration
{
    public function up()
    {
        // 1. Fix student_services_availed table - Add proper foreign key constraint
        // This table represents a many-to-many relationship, so the constraint should be added
        // Check if constraint already exists before adding
        $constraintExists = $this->db->query("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'student_services_availed' 
            AND CONSTRAINT_NAME = 'student_services_availed_ibfk_1'
        ")->getRow();
        
        if ($constraintExists->count == 0) {
            $this->db->query("
                ALTER TABLE `student_services_availed`
                ADD CONSTRAINT `student_services_availed_ibfk_1` 
                FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
            ");
        }

        // 2. Add missing foreign key constraint for follow_up_appointments counselor_id
        $constraintExists2 = $this->db->query("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'follow_up_appointments' 
            AND CONSTRAINT_NAME = 'follow_up_appointments_ibfk_1'
        ")->getRow();
        
        if ($constraintExists2->count == 0) {
            $this->db->query("
                ALTER TABLE `follow_up_appointments`
                ADD CONSTRAINT `follow_up_appointments_ibfk_1` 
                FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`) ON DELETE CASCADE
            ");
        }

        // 3. Add foreign key constraint for notifications user_id
        // First, ensure the user_id column matches the referenced column type
        $this->db->query("
            ALTER TABLE `notifications` 
            MODIFY COLUMN `user_id` INT(11) NOT NULL
        ");
        
        $constraintExists3 = $this->db->query("
            SELECT COUNT(*) as count 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'notifications' 
            AND CONSTRAINT_NAME = 'notifications_ibfk_1'
        ")->getRow();
        
        if ($constraintExists3->count == 0) {
            $this->db->query("
                ALTER TABLE `notifications`
                ADD CONSTRAINT `notifications_ibfk_1` 
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ");
        }

        // 4. Add check constraints for data integrity
        // Use SHOW CREATE TABLE to check if constraint exists (more compatible)
        $tableInfo = $this->db->query("SHOW CREATE TABLE `appointments`")->getRow();
        $hasStatusCheck = strpos($tableInfo->{'Create Table'}, 'chk_appointment_status') !== false;
        
        if (!$hasStatusCheck) {
            $this->db->query("
                ALTER TABLE `appointments`
                ADD CONSTRAINT `chk_appointment_status` 
                CHECK (`status` IN ('pending', 'approved', 'rejected', 'completed', 'cancelled'))
            ");
        }

        $hasPurposeCheck = strpos($tableInfo->{'Create Table'}, 'chk_appointment_purpose') !== false;
        
        if (!$hasPurposeCheck) {
            $this->db->query("
                ALTER TABLE `appointments`
                ADD CONSTRAINT `chk_appointment_purpose` 
                CHECK (`purpose` IN ('Counseling', 'Psycho-Social Support', 'Initial Interview'))
            ");
        }

        // Check follow_up_appointments table
        $followUpTableInfo = $this->db->query("SHOW CREATE TABLE `follow_up_appointments`")->getRow();
        $hasFollowUpStatusCheck = strpos($followUpTableInfo->{'Create Table'}, 'chk_followup_status') !== false;
        
        if (!$hasFollowUpStatusCheck) {
            $this->db->query("
                ALTER TABLE `follow_up_appointments`
                ADD CONSTRAINT `chk_followup_status` 
                CHECK (`status` IN ('pending', 'rejected', 'completed', 'cancelled'))
            ");
        }

        // 5. Add indexes for better performance and ACID compliance
        $hasIndex1 = strpos($tableInfo->{'Create Table'}, 'idx_appointment_counselor_date_status') !== false;
        
        if (!$hasIndex1) {
            $this->db->query("
                CREATE INDEX `idx_appointment_counselor_date_status` 
                ON `appointments` (`counselor_preference`, `preferred_date`, `status`)
            ");
        }

        $hasIndex2 = strpos($tableInfo->{'Create Table'}, 'idx_appointment_student_status') !== false;
        
        if (!$hasIndex2) {
            $this->db->query("
                CREATE INDEX `idx_appointment_student_status` 
                ON `appointments` (`student_id`, `status`)
            ");
        }

        $hasIndex3 = strpos($followUpTableInfo->{'Create Table'}, 'idx_followup_parent_sequence') !== false;
        
        if (!$hasIndex3) {
            $this->db->query("
                CREATE INDEX `idx_followup_parent_sequence` 
                ON `follow_up_appointments` (`parent_appointment_id`, `follow_up_sequence`)
            ");
        }

        // Check counselor_availability table
        $availabilityTableInfo = $this->db->query("SHOW CREATE TABLE `counselor_availability`")->getRow();
        $hasIndex4 = strpos($availabilityTableInfo->{'Create Table'}, 'idx_counselor_availability_day') !== false;
        
        if (!$hasIndex4) {
            $this->db->query("
                CREATE INDEX `idx_counselor_availability_day` 
                ON `counselor_availability` (`counselor_id`, `available_days`)
            ");
        }

        // 6. Add unique constraints to prevent duplicates
        $servicesAvailedTableInfo = $this->db->query("SHOW CREATE TABLE `student_services_availed`")->getRow();
        $hasUnique1 = strpos($servicesAvailedTableInfo->{'Create Table'}, 'uk_student_service_type') !== false;
        
        if (!$hasUnique1) {
            $this->db->query("
                ALTER TABLE `student_services_availed`
                ADD CONSTRAINT `uk_student_service_type` 
                UNIQUE (`student_id`, `service_type`)
            ");
        }

        $servicesNeededTableInfo = $this->db->query("SHOW CREATE TABLE `student_services_needed`")->getRow();
        $hasUnique2 = strpos($servicesNeededTableInfo->{'Create Table'}, 'uk_student_service_needed_type') !== false;
        
        if (!$hasUnique2) {
            $this->db->query("
                ALTER TABLE `student_services_needed`
                ADD CONSTRAINT `uk_student_service_needed_type` 
                UNIQUE (`student_id`, `service_type`)
            ");
        }

        // 7. Add NOT NULL constraints where appropriate
        // Check current column definitions using SHOW CREATE TABLE
        $appointmentsTableInfo = $this->db->query("SHOW CREATE TABLE `appointments`")->getRow();
        $createTable = $appointmentsTableInfo->{'Create Table'};
        
        // Check if student_id is nullable
        if (strpos($createTable, '`student_id` varchar(10) DEFAULT NULL') !== false || 
            strpos($createTable, '`student_id` varchar(10)') !== false && strpos($createTable, 'NOT NULL') === false) {
            $this->db->query("
                ALTER TABLE `appointments` 
                MODIFY COLUMN `student_id` VARCHAR(10) NOT NULL
            ");
        }

        // Check if preferred_date is nullable
        if (strpos($createTable, '`preferred_date` date DEFAULT NULL') !== false || 
            strpos($createTable, '`preferred_date` date') !== false && strpos($createTable, 'NOT NULL') === false) {
            $this->db->query("
                ALTER TABLE `appointments` 
                MODIFY COLUMN `preferred_date` DATE NOT NULL
            ");
        }

        // Check if preferred_time is nullable
        if (strpos($createTable, '`preferred_time` varchar(50) DEFAULT NULL') !== false || 
            strpos($createTable, '`preferred_time` varchar(50)') !== false && strpos($createTable, 'NOT NULL') === false) {
            $this->db->query("
                ALTER TABLE `appointments` 
                MODIFY COLUMN `preferred_time` VARCHAR(50) NOT NULL
            ");
        }

        // Check if consultation_type is nullable
        if (strpos($createTable, '`consultation_type` varchar(50) DEFAULT NULL') !== false || 
            strpos($createTable, '`consultation_type` varchar(50)') !== false && strpos($createTable, 'NOT NULL') === false) {
            $this->db->query("
                ALTER TABLE `appointments` 
                MODIFY COLUMN `consultation_type` VARCHAR(50) NOT NULL
            ");
        }

        // Check if purpose column is nullable and get max length of existing data
        if (strpos($createTable, '`purpose` varchar(50) DEFAULT NULL') !== false || 
            strpos($createTable, '`purpose` varchar(50)') !== false && strpos($createTable, 'NOT NULL') === false) {
            
            // First, check the maximum length of existing data
            $maxLengthResult = $this->db->query("SELECT MAX(LENGTH(purpose)) as max_length FROM appointments WHERE purpose IS NOT NULL")->getRow();
            $maxLength = $maxLengthResult->max_length ?? 0;
            
            // Use a larger size if needed, but cap at 255
            $purposeSize = max(50, min(255, $maxLength + 10)); // Add 10 characters buffer
            
            $this->db->query("
                ALTER TABLE `appointments` 
                MODIFY COLUMN `purpose` VARCHAR({$purposeSize}) NOT NULL
            ");
        }
    }

    public function down()
    {
        // Remove foreign key constraints
        $this->db->query("ALTER TABLE `student_services_availed` DROP FOREIGN KEY `student_services_availed_ibfk_1`");
        $this->db->query("ALTER TABLE `follow_up_appointments` DROP FOREIGN KEY `follow_up_appointments_ibfk_1`");
        $this->db->query("ALTER TABLE `notifications` DROP FOREIGN KEY `notifications_ibfk_1`");

        // Remove check constraints
        $this->db->query("ALTER TABLE `appointments` DROP CONSTRAINT `chk_appointment_status`");
        $this->db->query("ALTER TABLE `appointments` DROP CONSTRAINT `chk_appointment_purpose`");
        $this->db->query("ALTER TABLE `follow_up_appointments` DROP CONSTRAINT `chk_followup_status`");

        // Remove indexes
        $this->db->query("DROP INDEX `idx_appointment_counselor_date_status` ON `appointments`");
        $this->db->query("DROP INDEX `idx_appointment_student_status` ON `appointments`");
        $this->db->query("DROP INDEX `idx_followup_parent_sequence` ON `follow_up_appointments`");
        $this->db->query("DROP INDEX `idx_counselor_availability_day` ON `counselor_availability`");

        // Remove unique constraints
        $this->db->query("ALTER TABLE `student_services_availed` DROP CONSTRAINT `uk_student_service_type`");
        $this->db->query("ALTER TABLE `student_services_needed` DROP CONSTRAINT `uk_student_service_needed_type`");
    }
}
