-- Add method_type column to appointments table if it doesn't exist
-- This column stores: "In-person", "Online (Video)", or "Online (Audio only)"

-- Check if column exists first (run this query in phpMyAdmin or MySQL client)
-- If it returns 0 rows, then run the ALTER TABLE statement below

-- To check: DESCRIBE appointments; (look for method_type)

-- Add the column if it doesn't exist
ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `method_type` VARCHAR(50) DEFAULT NULL
AFTER `consultation_type`;

-- Or if your MySQL version doesn't support IF NOT EXISTS:
-- ALTER TABLE `appointments`
-- ADD COLUMN `method_type` VARCHAR(50) DEFAULT NULL
-- AFTER `consultation_type`;

