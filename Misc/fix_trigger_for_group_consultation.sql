-- Fix the prevent_double_booking trigger to handle consultation_type
-- This allows group consultations to have up to 5 bookings per time slot
-- 
-- IMPORTANT: In phpMyAdmin, run each statement SEPARATELY, one at a time
-- Before running each CREATE TRIGGER statement, change the "Delimiter" field 
-- in phpMyAdmin from ";" to "$$" (without quotes)
--
-- STEP 1: Drop the old triggers (run this first with delimiter ";")
DROP TRIGGER IF EXISTS prevent_double_booking;

DROP TRIGGER IF EXISTS prevent_double_booking_update;

-- STEP 2: Create the INSERT trigger
-- Before running this, change the delimiter to "$$" in phpMyAdmin's delimiter field
CREATE TRIGGER prevent_double_booking BEFORE INSERT ON appointments FOR EACH ROW 
BEGIN
    DECLARE conflict_count INT DEFAULT 0;
    DECLARE individual_count INT DEFAULT 0;
    DECLARE group_count INT DEFAULT 0;
    
    -- For Individual Consultation: block if there's ANY other appointment (individual or group)
    IF NEW.consultation_type = 'Individual Consultation' THEN
        SELECT COUNT(*) INTO conflict_count
        FROM appointments 
        WHERE counselor_preference = NEW.counselor_preference 
        AND preferred_date = NEW.preferred_date 
        AND preferred_time = NEW.preferred_time 
        AND status IN ('pending', 'approved')
        AND counselor_preference != 'No preference'
        AND id != NEW.id;
        
        IF conflict_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'This time slot is already booked. Individual consultations require exclusive time slots.';
        END IF;
    
    -- For Group Consultation: block if there's an individual consultation OR if 5+ group consultations
    ELSEIF NEW.consultation_type = 'Group Consultation' THEN
        -- Check for individual consultations (always blocks group)
        SELECT COUNT(*) INTO individual_count
        FROM appointments 
        WHERE counselor_preference = NEW.counselor_preference 
        AND preferred_date = NEW.preferred_date 
        AND preferred_time = NEW.preferred_time 
        AND status IN ('pending', 'approved')
        AND consultation_type = 'Individual Consultation'
        AND counselor_preference != 'No preference'
        AND id != NEW.id;
        
        IF individual_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'This time slot is already booked for individual consultation. Group consultations cannot share time slots with individual consultations.';
        END IF;
        
        -- Check for group consultation capacity (max 5)
        SELECT COUNT(*) INTO group_count
        FROM appointments 
        WHERE counselor_preference = NEW.counselor_preference 
        AND preferred_date = NEW.preferred_date 
        AND preferred_time = NEW.preferred_time 
        AND status IN ('pending', 'approved')
        AND consultation_type = 'Group Consultation'
        AND counselor_preference != 'No preference'
        AND id != NEW.id;
        
        IF group_count >= 5 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Group consultation slots are full for this time slot (maximum 5 participants).';
        END IF;
    END IF;
END

-- STEP 3: Create the UPDATE trigger
-- Keep delimiter as "$$" in phpMyAdmin
CREATE TRIGGER prevent_double_booking_update BEFORE UPDATE ON appointments FOR EACH ROW 
BEGIN
    DECLARE conflict_count INT DEFAULT 0;
    DECLARE individual_count INT DEFAULT 0;
    DECLARE group_count INT DEFAULT 0;
    
    -- Only check if counselor, date, time, or consultation_type is being changed
    IF (NEW.counselor_preference != OLD.counselor_preference 
        OR NEW.preferred_date != OLD.preferred_date 
        OR NEW.preferred_time != OLD.preferred_time
        OR NEW.consultation_type != OLD.consultation_type) THEN
        
        -- For Individual Consultation: block if there's ANY other appointment (individual or group)
        IF NEW.consultation_type = 'Individual Consultation' THEN
            SELECT COUNT(*) INTO conflict_count
            FROM appointments 
            WHERE counselor_preference = NEW.counselor_preference 
            AND preferred_date = NEW.preferred_date 
            AND preferred_time = NEW.preferred_time 
            AND status IN ('pending', 'approved')
            AND counselor_preference != 'No preference'
            AND id != NEW.id;
            
            IF conflict_count > 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'This time slot is already booked. Individual consultations require exclusive time slots.';
            END IF;
        
        -- For Group Consultation: block if there's an individual consultation OR if 5+ group consultations
        ELSEIF NEW.consultation_type = 'Group Consultation' THEN
            -- Check for individual consultations (always blocks group)
            SELECT COUNT(*) INTO individual_count
            FROM appointments 
            WHERE counselor_preference = NEW.counselor_preference 
            AND preferred_date = NEW.preferred_date 
            AND preferred_time = NEW.preferred_time 
            AND status IN ('pending', 'approved')
            AND consultation_type = 'Individual Consultation'
            AND counselor_preference != 'No preference'
            AND id != NEW.id;
            
            IF individual_count > 0 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'This time slot is already booked for individual consultation. Group consultations cannot share time slots with individual consultations.';
            END IF;
            
            -- Check for group consultation capacity (max 5)
            SELECT COUNT(*) INTO group_count
            FROM appointments 
            WHERE counselor_preference = NEW.counselor_preference 
            AND preferred_date = NEW.preferred_date 
            AND preferred_time = NEW.preferred_time 
            AND status IN ('pending', 'approved')
            AND consultation_type = 'Group Consultation'
            AND counselor_preference != 'No preference'
            AND id != NEW.id;
            
            IF group_count >= 5 THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'Group consultation slots are full for this time slot (maximum 5 participants).';
            END IF;
        END IF;
    END IF;
END

