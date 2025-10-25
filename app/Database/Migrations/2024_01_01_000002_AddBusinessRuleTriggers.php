<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add Database Triggers for Business Rule Enforcement
 * 
 * This migration adds triggers to enforce business rules at the database level
 * for better ACID compliance and data integrity.
 */
class AddBusinessRuleTriggers extends Migration
{
    public function up()
    {
        // Only add the most essential triggers for ACID compliance
        // This keeps the migration simple and reduces the chance of conflicts
        
        // 1. Trigger to prevent double booking of appointments
        $this->db->query("DROP TRIGGER IF EXISTS prevent_double_booking");
        
        $this->db->query("
            CREATE TRIGGER prevent_double_booking
            BEFORE INSERT ON appointments
            FOR EACH ROW
            BEGIN
                DECLARE conflict_count INT DEFAULT 0;
                
                -- Check for conflicts with same counselor, date, and time
                SELECT COUNT(*) INTO conflict_count
                FROM appointments 
                WHERE counselor_preference = NEW.counselor_preference 
                AND preferred_date = NEW.preferred_date 
                AND preferred_time = NEW.preferred_time 
                AND status IN ('pending', 'approved')
                AND counselor_preference != 'No preference';
                
                IF conflict_count > 0 THEN
                    SIGNAL SQLSTATE '45000' 
                    SET MESSAGE_TEXT = 'Counselor already has an appointment at this time';
                END IF;
            END
        ");

        // 2. Trigger to maintain follow-up appointment sequence
        $this->db->query("DROP TRIGGER IF EXISTS maintain_followup_sequence");
        
        $this->db->query("
            CREATE TRIGGER maintain_followup_sequence
            BEFORE INSERT ON follow_up_appointments
            FOR EACH ROW
            BEGIN
                IF NEW.parent_appointment_id IS NOT NULL THEN
                    SET NEW.follow_up_sequence = (
                        SELECT COALESCE(MAX(follow_up_sequence), 0) + 1 
                        FROM follow_up_appointments 
                        WHERE parent_appointment_id = NEW.parent_appointment_id
                    );
                END IF;
            END
        ");
    }

    public function down()
    {
        // Drop only the triggers we created
        $this->db->query("DROP TRIGGER IF EXISTS prevent_double_booking");
        $this->db->query("DROP TRIGGER IF EXISTS maintain_followup_sequence");
    }
}
