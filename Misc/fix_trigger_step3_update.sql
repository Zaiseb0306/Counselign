-- STEP 3: Create the UPDATE trigger
-- IMPORTANT INSTRUCTIONS:
-- 1. Copy the entire trigger code below (from CREATE TRIGGER to END)
-- 2. In phpMyAdmin, make sure the "Delimiter" field is set to "$$" (not ";")
-- 3. Paste the trigger code below
-- 4. Click "Go"
-- 5. After completion, change delimiter back to ";" for future queries

CREATE TRIGGER prevent_double_booking_update BEFORE UPDATE ON appointments FOR EACH ROW 
BEGIN
    DECLARE conflict_count INT DEFAULT 0;
    DECLARE individual_count INT DEFAULT 0;
    DECLARE group_count INT DEFAULT 0;
    
    IF (NEW.counselor_preference != OLD.counselor_preference 
        OR NEW.preferred_date != OLD.preferred_date 
        OR NEW.preferred_time != OLD.preferred_time
        OR NEW.consultation_type != OLD.consultation_type) THEN
        
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
        
        ELSEIF NEW.consultation_type = 'Group Consultation' THEN
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

