-- STEP 1: Drop the old triggers
-- Run this FIRST in phpMyAdmin with default delimiter ";"

DROP TRIGGER IF EXISTS prevent_double_booking;

DROP TRIGGER IF EXISTS prevent_double_booking_update;

