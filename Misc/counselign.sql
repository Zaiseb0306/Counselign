-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 04:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `counselign`
--
CREATE DATABASE IF NOT EXISTS `counselign` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `counselign`;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` varchar(50) NOT NULL,
  `consultation_type` varchar(50) DEFAULT NULL,
  `method_type` varchar(50) NOT NULL,
  `purpose` text DEFAULT NULL,
  `counselor_preference` varchar(100) DEFAULT 'No preference',
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `prevent_double_booking` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    DECLARE conflict_count INT DEFAULT 0;
    DECLARE individual_count INT DEFAULT 0;
    DECLARE group_count INT DEFAULT 0;
    
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_double_booking_update` BEFORE UPDATE ON `appointments` FOR EACH ROW BEGIN
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
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counselors`
--

CREATE TABLE `counselors` (
  `id` int(11) NOT NULL,
  `counselor_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `civil_status` varchar(20) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counselor_availability`
--

CREATE TABLE `counselor_availability` (
  `id` int(11) NOT NULL,
  `counselor_id` varchar(10) NOT NULL,
  `available_days` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `time_scheduled` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follow_up_appointments`
--

CREATE TABLE `follow_up_appointments` (
  `id` int(11) NOT NULL,
  `counselor_id` varchar(10) NOT NULL,
  `student_id` varchar(100) NOT NULL,
  `parent_appointment_id` int(11) DEFAULT NULL COMMENT 'References the initial appointment or previous follow-up',
  `preferred_date` date NOT NULL,
  `preferred_time` varchar(50) NOT NULL,
  `consultation_type` varchar(50) NOT NULL,
  `follow_up_sequence` int(11) NOT NULL DEFAULT 1 COMMENT 'Track the sequence: 1st follow-up, 2nd follow-up, etc.',
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `follow_up_appointments`
--
DELIMITER $$
CREATE TRIGGER `maintain_followup_sequence` BEFORE INSERT ON `follow_up_appointments` FOR EACH ROW BEGIN
                IF NEW.parent_appointment_id IS NOT NULL THEN
                    SET NEW.follow_up_sequence = (
                        SELECT COALESCE(MAX(follow_up_sequence), 0) + 1 
                        FROM follow_up_appointments 
                        WHERE parent_appointment_id = NEW.parent_appointment_id
                    );
                END IF;
            END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` varchar(10) DEFAULT NULL,
  `receiver_id` varchar(10) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `event_date` datetime DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `reset_code` varchar(10) NOT NULL,
  `reset_expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_info`
--

CREATE TABLE `student_academic_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year_level` varchar(10) NOT NULL,
  `academic_status` varchar(50) NOT NULL,
  `school_last_attended` varchar(255) DEFAULT NULL,
  `location_of_school` varchar(255) DEFAULT NULL,
  `previous_course_grade` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_address_info`
--

CREATE TABLE `student_address_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `permanent_zone` varchar(50) DEFAULT NULL,
  `permanent_barangay` varchar(100) DEFAULT NULL,
  `permanent_city` varchar(100) DEFAULT NULL,
  `permanent_province` varchar(100) DEFAULT NULL,
  `present_zone` varchar(50) DEFAULT NULL,
  `present_barangay` varchar(100) DEFAULT NULL,
  `present_city` varchar(100) DEFAULT NULL,
  `present_province` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_awards`
--

CREATE TABLE `student_awards` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `award_name` varchar(255) NOT NULL,
  `school_organization` varchar(255) NOT NULL,
  `year_received` varchar(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_family_info`
--

CREATE TABLE `student_family_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father_educational_attainment` varchar(100) DEFAULT NULL,
  `father_age` int(3) DEFAULT NULL,
  `father_contact_number` varchar(20) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `mother_educational_attainment` varchar(100) DEFAULT NULL,
  `mother_age` int(3) DEFAULT NULL,
  `mother_contact_number` varchar(20) DEFAULT NULL,
  `parents_permanent_address` text DEFAULT NULL,
  `parents_contact_number` varchar(20) DEFAULT NULL,
  `spouse` varchar(255) DEFAULT NULL,
  `spouse_occupation` varchar(100) DEFAULT NULL,
  `spouse_educational_attainment` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_age` int(3) DEFAULT NULL,
  `guardian_occupation` varchar(100) DEFAULT NULL,
  `guardian_contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `student_gcs_activities`
--

CREATE TABLE `student_gcs_activities` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `activity_type` enum('adjustment','building_self_confidence','developing_communication_skills','study_habits','time_management','tutorial_with_peers','other') NOT NULL,
  `other_specify` varchar(255) DEFAULT NULL,
  `tutorial_subjects` text DEFAULT NULL COMMENT 'For tutorial_with_peers type',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_other_info`
--

CREATE TABLE `student_other_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `course_choice_reason` text DEFAULT NULL,
  `family_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of: harmonious, conflict, separated_parents, parents_working_abroad, other' CHECK (json_valid(`family_description`)),
  `family_description_other` varchar(255) DEFAULT NULL,
  `living_condition` enum('good_environment','not_good_environment') DEFAULT NULL,
  `physical_health_condition` enum('No','Yes') DEFAULT 'No',
  `physical_health_condition_specify` text DEFAULT NULL,
  `psych_treatment` enum('No','Yes') DEFAULT 'No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_personal_info`
--

CREATE TABLE `student_personal_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Legally Separated','Annulled') DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `fb_account_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_residence_info`
--

CREATE TABLE `student_residence_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `residence_type` enum('at home','boarding house','USTP-Claveria Dormitory','relatives','friends','other') DEFAULT NULL,
  `residence_other_specify` varchar(255) DEFAULT NULL,
  `has_consent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_services_availed`
--

CREATE TABLE `student_services_availed` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `service_type` enum('counseling','insurance','special_lanes','safe_learning','equal_access','other') NOT NULL,
  `other_specify` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_services_needed`
--

CREATE TABLE `student_services_needed` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `service_type` enum('counseling','insurance','special_lanes','safe_learning','equal_access','other') NOT NULL,
  `other_specify` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_special_circumstances`
--

CREATE TABLE `student_special_circumstances` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `is_solo_parent` enum('Yes','No') DEFAULT NULL,
  `is_indigenous` enum('Yes','No') DEFAULT NULL,
  `is_breastfeeding` enum('Yes','No','N/A') DEFAULT NULL,
  `is_pwd` enum('Yes','No','Other') DEFAULT NULL,
  `pwd_disability_type` varchar(255) DEFAULT NULL,
  `pwd_proof_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_token` varchar(6) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `role` enum('student','admin','counselor') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `logout_time` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `last_inactive_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_ibfk_1` (`student_id`) USING BTREE,
  ADD KEY `idx_appointment_counselor_date_status` (`counselor_preference`,`preferred_date`,`status`),
  ADD KEY `idx_appointment_student_status` (`student_id`,`status`);

--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `counselors`
--
ALTER TABLE `counselors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `counselor_ibfk_1` (`counselor_id`);

--
-- Indexes for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `idx_counselor_availability_day` (`counselor_id`,`available_days`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `follow_up_appointments`
--
ALTER TABLE `follow_up_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_counselor` (`counselor_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_parent_appointment` (`parent_appointment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_followup_parent_sequence` (`parent_appointment_id`,`follow_up_sequence`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `messages_ibfk_1` (`sender_id`),
  ADD KEY `messages_ibfk_2` (`receiver_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_code` (`reset_code`),
  ADD KEY `password_resets_fk2` (`user_id`);

--
-- Indexes for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_academic_course` (`course`,`year_level`);

--
-- Indexes for table `student_address_info`
--
ALTER TABLE `student_address_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_awards`
--
ALTER TABLE `student_awards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_awards` (`student_id`),
  ADD KEY `idx_awards_student_year` (`student_id`,`year_received`);

--
-- Indexes for table `student_family_info`
--
ALTER TABLE `student_family_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_gcs_activities`
--
ALTER TABLE `student_gcs_activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_activity_type` (`student_id`,`activity_type`),
  ADD KEY `idx_student_activities` (`student_id`,`activity_type`),
  ADD KEY `idx_gcs_activities_student` (`student_id`);

--
-- Indexes for table `student_other_info`
--
ALTER TABLE `student_other_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_student_other_info` (`student_id`);

--
-- Indexes for table `student_personal_info`
--
ALTER TABLE `student_personal_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_residence_info`
--
ALTER TABLE `student_residence_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_services_availed`
--
ALTER TABLE `student_services_availed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_service_type` (`student_id`,`service_type`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_user_services_availed` (`student_id`,`service_type`);

--
-- Indexes for table `student_services_needed`
--
ALTER TABLE `student_services_needed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_service_needed_type` (`student_id`,`service_type`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_user_services_needed` (`student_id`,`service_type`);

--
-- Indexes for table `student_special_circumstances`
--
ALTER TABLE `student_special_circumstances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_pwd_status` (`is_pwd`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `follow_up_appointments`
--
ALTER TABLE `follow_up_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_address_info`
--
ALTER TABLE `student_address_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_awards`
--
ALTER TABLE `student_awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_family_info`
--
ALTER TABLE `student_family_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_gcs_activities`
--
ALTER TABLE `student_gcs_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_other_info`
--
ALTER TABLE `student_other_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_personal_info`
--
ALTER TABLE `student_personal_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_residence_info`
--
ALTER TABLE `student_residence_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_services_availed`
--
ALTER TABLE `student_services_availed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_services_needed`
--
ALTER TABLE `student_services_needed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_special_circumstances`
--
ALTER TABLE `student_special_circumstances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_fk2` FOREIGN KEY (`counselor_preference`) REFERENCES `counselors` (`counselor_id`),
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `counselors`
--
ALTER TABLE `counselors`
  ADD CONSTRAINT `counselor_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  ADD CONSTRAINT `counselor_availability_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_up_appointments`
--
ALTER TABLE `follow_up_appointments`
  ADD CONSTRAINT `fk_parent_appointment` FOREIGN KEY (`parent_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_up_appointments_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_fk2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  ADD CONSTRAINT `student_academic_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_address_info`
--
ALTER TABLE `student_address_info`
  ADD CONSTRAINT `student_address_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_awards`
--
ALTER TABLE `student_awards`
  ADD CONSTRAINT `student_awards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_family_info`
--
ALTER TABLE `student_family_info`
  ADD CONSTRAINT `student_family_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_gcs_activities`
--
ALTER TABLE `student_gcs_activities`
  ADD CONSTRAINT `student_gcs_activities_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_other_info`
--
ALTER TABLE `student_other_info`
  ADD CONSTRAINT `student_other_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_personal_info`
--
ALTER TABLE `student_personal_info`
  ADD CONSTRAINT `student_personal_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_residence_info`
--
ALTER TABLE `student_residence_info`
  ADD CONSTRAINT `student_residence_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_services_availed`
--
ALTER TABLE `student_services_availed`
  ADD CONSTRAINT `student_services_availed_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_services_needed`
--
ALTER TABLE `student_services_needed`
  ADD CONSTRAINT `student_services_needed_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_special_circumstances`
--
ALTER TABLE `student_special_circumstances`
  ADD CONSTRAINT `student_special_circumstances_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
