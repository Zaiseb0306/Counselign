-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2025 at 04:54 AM
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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 'try', 'tryan', '2025-10-09 07:06:42', '2025-10-09 07:06:42');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` varchar(50) NOT NULL,
  `consultation_type` varchar(50) NOT NULL,
  `purpose` text DEFAULT NULL,
  `counselor_preference` varchar(100) DEFAULT 'No preference',
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `student_id`, `preferred_date`, `preferred_time`, `consultation_type`, `purpose`, `counselor_preference`, `description`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(4, '2023304958', '2025-10-30', '8:00 AM - 9:00 AM', 'In-person', NULL, '2023303610', 'try', NULL, 'completed', '2025-10-13 10:52:05', '2025-10-21 09:44:28'),
(5, '2023303620', '2025-11-07', '3:00 PM - 4:00 PM', 'In-person', NULL, '2023303610', 'try lang', 'Reason from Counselor: try lang', 'rejected', '2025-10-14 15:05:56', '2025-10-16 01:27:06'),
(6, '2023303620', '2025-10-31', '3:00 PM - 4:00 PM', 'In-person', 'Initial Interview', '2023303610', 'try again', '', 'completed', '2025-10-14 18:19:23', '2025-10-21 08:13:09'),
(7, '2023303620', '2025-10-30', '8:00 AM - 9:00 AM', 'In-person', NULL, '2022311680', 'try lang gihapun', NULL, 'completed', '2025-10-14 18:22:17', '2025-10-21 08:50:27'),
(8, '2023303620', '2025-10-29', '10:00 AM - 11:00 AM', 'In-person', 'Counseling', '2023303610', 'try again', '', 'completed', '2025-10-14 18:23:57', '2025-10-21 07:48:25'),
(12, '2023303620', '2025-10-30', '8:00 AM - 9:00 AM', 'In-person', 'Counseling', '2023303610', 'try', NULL, 'completed', '2025-10-21 08:54:29', NULL),
(13, '2023303620', '2025-10-30', '8:00 AM - 9:00 AM', 'In-person', 'Counseling', '2023303610', 'ascascsc', NULL, 'completed', '2025-10-21 09:11:25', NULL),
(14, '2023303620', '2025-10-30', '8:00 AM - 9:00 AM', 'In-person', 'Counseling', '2022311680', 'scsdcsdc', NULL, 'completed', '2025-10-21 09:37:28', '2025-10-21 09:44:15'),
(16, '2023303620', '2025-10-29', '9:00 AM - 10:00 AM', 'In-person', 'Counseling', '2023303610', 'adadawdwadw', NULL, 'completed', '2025-10-21 09:50:08', NULL),
(20, '2023303620', '2025-10-27', '10:00 AM - 11:00 AM', 'In-person', 'Counseling', '2023303610', 'xsaxasxdasd', NULL, 'pending', '2025-10-21 11:22:05', NULL);

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `prevent_double_booking` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
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
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_double_booking_update` BEFORE UPDATE ON `appointments` FOR EACH ROW BEGIN
                DECLARE conflict_count INT DEFAULT 0;
                
                -- Only check if counselor, date, or time is being changed
                IF (OLD.counselor_preference != NEW.counselor_preference 
                    OR OLD.preferred_date != NEW.preferred_date 
                    OR OLD.preferred_time != NEW.preferred_time) THEN
                    
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
                        SET MESSAGE_TEXT = 'Counselor already has an appointment at this time';
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
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `civil_status` varchar(20) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `time_scheduled` varchar(50) DEFAULT NULL,
  `available_days` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`id`, `counselor_id`, `name`, `degree`, `email`, `contact_number`, `address`, `profile_picture`, `created_at`, `updated_at`, `civil_status`, `sex`, `birthdate`, `time_scheduled`, `available_days`) VALUES
(1, '2022311680', 'Princess Grace Marie Sitoy', 'BS in IT', 'esangairemgrace@gmail.com', '09876543212', 'Galaxy St., Terryhills Subd, Bulua, CDO', 'Photos/profile_pictures/counselor_2022311680_1760489478.png', '2025-10-03 16:16:09', '2025-10-15 00:51:18', NULL, NULL, NULL, NULL, NULL),
(3, '2023303610', 'Rex Dominic Sihay', 'Bachelor Of Science In Information Technology', 'sihay.rexdominic13@gmail.com', '09201839205', 'Zone 3, Cabacungan, Claveria', NULL, '2025-10-12 08:15:07', '2025-10-23 02:24:21', 'Single', 'Male', '2005-03-13', NULL, NULL);

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

--
-- Dumping data for table `counselor_availability`
--

INSERT INTO `counselor_availability` (`id`, `counselor_id`, `available_days`, `time_scheduled`, `created_at`) VALUES
(112, '2023303610', 'Tuesday', '7:00 AM-9:00 AM', '2025-10-21 10:49:23'),
(113, '2023303610', 'Monday', '7:00 AM-8:00 AM', '2025-10-21 10:50:10'),
(114, '2023303610', 'Tuesday', '1:00 PM-2:00 PM', '2025-10-21 10:50:38'),
(115, '2023303610', 'Wednesday', '1:00 PM-2:00 PM', '2025-10-21 10:50:38'),
(116, '2023303610', 'Monday', '10:00 AM-11:00 AM', '2025-10-21 11:03:42'),
(117, '2023303610', 'Monday', '1:00 PM-2:00 PM', '2025-10-21 11:03:42'),
(118, '2023303610', 'Tuesday', '10:00 AM-11:00 AM', '2025-10-21 11:03:42'),
(119, '2023303610', 'Wednesday', '10:00 AM-11:00 AM', '2025-10-21 11:03:42'),
(120, '2023303610', 'Wednesday', '7:00 AM-5:30 PM', '2025-10-22 18:07:36'),
(121, '2023303610', 'Thursday', '7:00 AM-5:30 PM', '2025-10-22 18:07:36'),
(122, '2023303610', 'Friday', '7:00 AM-5:30 PM', '2025-10-22 18:07:36');

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

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `date`, `time`, `location`, `created_at`) VALUES
(1, 'GenSan Tuna Festival', 'try lang', '2025-10-11', '19:06:00', 'diri lang gud', '2025-10-09 07:07:10'),
(2, 'try', 'try', '2025-10-30', '07:30:00', 'Sl', '2025-10-14 12:28:21');

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
) ;

--
-- Dumping data for table `follow_up_appointments`
--

INSERT INTO `follow_up_appointments` (`id`, `counselor_id`, `student_id`, `parent_appointment_id`, `preferred_date`, `preferred_time`, `consultation_type`, `follow_up_sequence`, `description`, `reason`, `status`, `created_at`, `updated_at`) VALUES
(4, '2023303610', '2023303640', 4, '2025-10-17', '15:30-17:00', 'Follow-up Session', 1, 'asxas', 'xasxa', 'completed', '2025-10-15 20:50:07', '2025-10-21 03:19:27'),
(5, '2023303610', '2023303640', 4, '2025-10-20', '07:00-08:30', 'Follow-up Session', 2, 'rferfwsfec', 'try lang', 'completed', '2025-10-15 20:56:41', '2025-10-21 03:19:30'),
(6, '2023303610', '2023303640', 4, '2025-10-29', '07:00-10:30', 'Follow-up Session', 3, 'try lang naman', 'try again', 'completed', '2025-10-16 00:27:51', '2025-10-21 03:19:32'),
(7, '2023303610', '2023303640', 6, '2025-10-22', '07:00-10:30', 'Follow-up Session', 1, 'try lang', 'efsfef', 'cancelled', '2025-10-20 23:51:19', '2025-10-21 02:14:57'),
(9, '2023303610', '2023304958', 4, '2025-10-29', '1:00 PM-2:00 PM', 'Follow-up Session', 4, 'ewad', 'dcsdcdsfreferf', 'completed', '2025-10-21 03:19:48', '2025-10-22 00:01:52'),
(12, '2023303610', '2023303620', 6, '2025-10-22', '10:00 AM-11:00 AM', 'Follow-up Session', 2, 'try lang', 'try lang', 'completed', '2025-10-21 07:06:42', '2025-10-21 23:49:40'),
(13, '2023303610', '2023303620', 12, '2025-11-05', '10:00 AM-11:00 AM', 'Follow-up Session', 1, 'try lang', 'try lang', 'completed', '2025-10-21 07:07:06', '2025-10-21 07:07:18'),
(14, '2023303610', '2023303620', 12, '2025-10-29', '10:00 AM-11:00 AM', 'Follow-up Session', 2, 'try lang', 'try', 'cancelled', '2025-10-21 07:07:28', '2025-10-22 00:03:39'),
(15, '2023303610', '2023303620', 6, '2025-10-29', '10:00 AM-11:00 AM', 'Follow-up Session', 3, 'sxascasxas', 'sxasxasxas', 'completed', '2025-10-22 00:04:18', '2025-10-22 00:11:45'),
(16, '2023303610', '2023303620', 16, '2025-11-12', '1:00 PM-2:00 PM', 'Follow-up Session', 1, 'wdacdscds', 'try from dart', 'cancelled', '2025-10-22 00:04:51', '2025-10-22 00:11:59'),
(17, '2023303610', '2023303620', 6, '2025-11-11', '1:00 PM-2:00 PM', 'Follow-up Session', 4, 'asxas', 'cancel lang', 'cancelled', '2025-10-22 00:12:23', '2025-10-22 00:17:43'),
(18, '2023303610', '2023303620', 6, '2025-11-26', '10:00 AM-11:00 AM', 'Follow-up Session', 5, 'sxaxascdsc', 'xsaxasxasxas', 'cancelled', '2025-10-22 00:18:26', '2025-10-22 00:18:47'),
(19, '2023303610', '2023303620', 6, '2025-11-19', '10:00 AM-11:00 AM', 'Follow-up Session', 6, 'asxasxas', 'asxasxsa', 'completed', '2025-10-22 00:19:15', '2025-10-22 00:26:09'),
(20, '2023303610', '2023303620', 12, '2025-11-18', '1:00 PM-2:00 PM', 'Follow-up Session', 3, 'sxaxasxx', 'sxasxas', 'pending', '2025-10-22 00:26:32', '2025-10-22 00:26:32');

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

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_at`) VALUES
(3, '2023303620', '2022311680', 'hello', 1, '2025-10-14 05:53:19'),
(4, '2023303620', '2023303610', 'hi', 1, '2025-10-14 05:57:28'),
(5, '2023303620', '1234567890', 'hcaxaxa', 0, '2025-10-14 06:14:11'),
(6, '2023303620', '1234567890', 'hi princess', 0, '2025-10-14 06:23:56'),
(7, '2023303620', '1234567890', 'rex ni', 0, '2025-10-14 06:25:17'),
(8, '2023303620', '2023303610', 'hello', 1, '2025-10-14 06:57:53'),
(9, '2023303610', '2023303620', 'hello', 0, '2025-10-14 07:58:17'),
(10, '2023303610', '2023303620', 'hi', 0, '2025-10-14 09:07:42'),
(11, '2023303620', '2023303610', 'hello', 1, '2025-10-14 09:34:09'),
(12, '2023303610', '2023303620', 'how za', 0, '2025-10-14 14:47:49'),
(13, '2023303620', '2022311680', 'aloha', 1, '2025-10-15 00:56:39'),
(14, '2022311680', '2023303620', 'aloha', 0, '2025-10-15 00:57:02'),
(15, '2023303610', '2023303620', 'asxasxa', 0, '2025-10-15 23:45:03'),
(16, '2023303610', '2023303620', 'sacascaca', 0, '2025-10-15 23:56:43'),
(17, '2023303610', '2023303620', 'ascacascascascascsa', 0, '2025-10-15 23:56:49'),
(18, '2023303610', '2023303620', 'ascxasxasx', 0, '2025-10-16 00:03:15'),
(19, '2023303610', '2023303620', 'hi bro', 0, '2025-10-16 00:03:25'),
(20, '2023303610', '2023303620', 'uftgu8edgawt7d8fx9w8dhaqwgfd8q9spacshcsdhicdssdefcesudygce eyesf esfeb  yudgeuyfcedc edgeufcbefc ed gwefyg8e edygwed', 0, '2025-10-16 03:37:42'),
(21, '2023303620', '2023303610', 'hello', 1, '2025-10-18 04:54:52'),
(22, '2023303610', '2023303620', 'wwjcejdwedwedwe', 0, '2025-10-20 09:28:58'),
(23, '2023303610', '2023303620', 'hello', 0, '2025-10-20 12:12:00'),
(24, '2023303610', '2023303620', 'dasdasas', 0, '2025-10-20 12:12:07'),
(25, '2023303610', '2023303620', 'axasdcawdwedwdaqfqw', 0, '2025-10-21 11:46:56');

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

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2024_01_01_000001', 'App\\Database\\Migrations\\FixForeignKeyConstraints', 'default', 'App', 1761183887, 1),
(2, '2024_01_01_000002', 'App\\Database\\Migrations\\AddBusinessRuleTriggers', 'default', 'App', 1761184144, 2),
(3, '2024_01_01_000003', 'App\\Database\\Migrations\\ConfigureACIDSettings', 'default', 'App', 1761184290, 3),
(4, '2025-09-23-160820', 'App\\Database\\Migrations\\AddNotificationsTable', 'default', 'App', 1761185122, 4),
(5, '2025-09-23-160918', 'App\\Database\\Migrations\\CreateCiSessionsTable', 'default', 'App', 1761185122, 4),
(6, '2025-09-23-160926', 'App\\Database\\Migrations\\AlterNotificationsTableUserIdField', 'default', 'App', 1761185122, 4),
(7, '2025-09-23-163630', 'App\\Database\\Migrations\\AddVerificationToUsers', 'default', 'App', 1761185142, 5),
(8, '2025-09-23-174254', 'App\\Database\\Migrations\\AddResetTokenExpirationToUsers', 'default', 'App', 1761185142, 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `related_id` int(11) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
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

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_code`, `reset_expires_at`, `created_at`) VALUES
(10, '2023303610', '668216', '2025-10-06 10:00:22', '2025-10-06 01:55:22'),
(12, '2023303610', '581210', '2025-10-13 14:38:33', '2025-10-13 06:33:33'),
(17, '2023303640', '161684', '2025-10-19 23:08:43', '2025-10-19 15:03:43');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_academic_info`
--

INSERT INTO `student_academic_info` (`id`, `student_id`, `course`, `year_level`, `academic_status`, `created_at`, `updated_at`) VALUES
(1, '2023303620', 'BSIT', 'III', 'Continuing/Old', '2025-10-12 04:42:02', '2025-10-15 23:25:24');

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

--
-- Dumping data for table `student_address_info`
--

INSERT INTO `student_address_info` (`id`, `student_id`, `permanent_zone`, `permanent_barangay`, `permanent_city`, `permanent_province`, `present_zone`, `present_barangay`, `present_city`, `present_province`, `created_at`, `updated_at`) VALUES
(1, '2023303620', '3', 'Cabacungan', 'Claveria', 'Misamis Oriental', '1', 'Poblacion', 'Claveria', 'Misamis Oriental', '2025-10-12 04:42:02', '2025-10-15 23:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `student_family_info`
--

CREATE TABLE `student_family_info` (
  `id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `spouse` varchar(255) DEFAULT NULL,
  `guardian_contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_family_info`
--

INSERT INTO `student_family_info` (`id`, `student_id`, `father_name`, `father_occupation`, `mother_name`, `mother_occupation`, `spouse`, `guardian_contact_number`, `created_at`, `updated_at`) VALUES
(2, '2023303620', 'Sihay, Ricky P.', 'Company Worker', 'Sihay, Leila B.', 'Housewife', 'N/A', '09064659426', '2025-10-12 05:41:13', '2025-10-15 23:25:24');

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
  `age` int(3) DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Legally Separated','Annulled') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `fb_account_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_personal_info`
--

INSERT INTO `student_personal_info` (`id`, `student_id`, `last_name`, `first_name`, `middle_name`, `date_of_birth`, `age`, `sex`, `civil_status`, `contact_number`, `fb_account_name`, `created_at`, `updated_at`) VALUES
(2, '2023303620', 'Beronilla', 'Rex Dominic', 'Fajardo', '2005-03-13', 20, 'Male', 'Single', '09619335143', 'Rex D Beronilla', '2025-10-12 06:04:56', '2025-10-15 23:25:24');

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

--
-- Dumping data for table `student_residence_info`
--

INSERT INTO `student_residence_info` (`id`, `student_id`, `residence_type`, `residence_other_specify`, `has_consent`, `created_at`, `updated_at`) VALUES
(1, '2023303620', 'boarding house', 'N/A', 1, '2025-10-12 04:42:02', '2025-10-15 23:25:24');

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

--
-- Dumping data for table `student_services_availed`
--

INSERT INTO `student_services_availed` (`id`, `student_id`, `service_type`, `other_specify`, `created_at`) VALUES
(17, '2023303620', 'insurance', NULL, '2025-10-16 07:25:24');

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

--
-- Dumping data for table `student_services_needed`
--

INSERT INTO `student_services_needed` (`id`, `student_id`, `service_type`, `other_specify`, `created_at`) VALUES
(16, '2023303620', 'counseling', NULL, '2025-10-16 07:25:24');

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

--
-- Dumping data for table `student_special_circumstances`
--

INSERT INTO `student_special_circumstances` (`id`, `student_id`, `is_solo_parent`, `is_indigenous`, `is_breastfeeding`, `is_pwd`, `pwd_disability_type`, `pwd_proof_file`, `created_at`, `updated_at`) VALUES
(1, '2023303620', 'Yes', 'Yes', 'N/A', 'Yes', 'Asthma', 'Photos/pwd_proofs/pwd_proof_2023303620_1760316767.png', '2025-10-12 04:42:02', '2025-10-15 23:25:24');

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
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `username`, `email`, `password`, `verification_token`, `reset_expires_at`, `is_verified`, `role`, `created_at`, `profile_picture`, `last_login`, `logout_time`, `last_activity`, `last_active_at`, `last_inactive_at`) VALUES
(1, '0000000001', 'CounselignAdmin', 'technorex13@gmail.com', '$2y$10$PC5UrxtIFNH6Kh1hdps/euy3XbXTKYpyY8bUeA.Uldqj6PdV8BbsW', NULL, NULL, 1, 'admin', '2025-09-25 12:44:46', 'Photos/profile_pictures/admin_1_1759996110.png', '2025-10-22 12:26:13', '2025-10-22 13:51:30', '2025-10-22 13:51:30', '2025-10-22 13:51:30', '2025-10-22 13:51:30'),
(20, '2022311680', 'Freynsis Greys', 'esangairemgrace@gmail.com', '$2y$10$1EeQMD6EBK4Lu1dJQlUPOOJHsCCvUXd1cYZpr3aG8ntawO/4Vg.Da', NULL, NULL, 1, 'counselor', '2025-10-03 12:30:26', 'Photos/profile_pictures/counselor_2022311680_1760489478.png', '2025-10-15 00:50:45', '2025-10-05 14:23:02', '2025-10-14 16:59:02', '2025-10-15 00:57:02', '2025-10-05 14:23:02'),
(21, '2023303610', 'rexd', 'sihay.rexdominic13@gmail.com', '$2y$10$9.6QtCLHL93wI8Ntro9EqOZX8Oe7OFbxf67Zn637tWfcpxQGB8Vve', NULL, NULL, 1, 'counselor', '2025-09-25 06:38:06', 'Photos/profile_pictures/counselor_2023303610_1760250516.png', '2025-10-23 02:17:54', '2025-10-22 14:33:34', '2025-10-22 14:33:34', '2025-10-22 14:33:34', '2025-10-22 14:33:34'),
(24, '2023304958', 'Mil', 'milwaukeearrubio@gmail.com', '$2y$10$UI.XJG3nPuh4wVBqHZnHZeQTDmgXF6Y0854GeCwAuEgE/I50AHjRW', NULL, NULL, 1, 'student', '2025-09-25 12:48:40', 'Photos/profile.png', '2025-09-25 12:56:50', '2025-09-25 12:57:55', '2025-09-25 12:57:55', '2025-09-25 12:57:55', '2025-09-25 12:57:55'),
(30, '2023303620', 'syrex', 'rexsihay@gmail.com', '$2y$10$LTxNJwXigT3/XQIsHQVw1eWJQNaRzEiq2LGNKQl5CO3IXxkE20dJW', NULL, NULL, 1, 'student', '2025-10-05 16:25:12', 'Photos/profile_pictures/student_2023303620_1760251620.png', '2025-10-23 02:16:35', '2025-10-21 17:56:37', '2025-10-22 18:09:19', '2025-10-21 17:56:37', '2025-10-21 17:56:37'),
(35, '2023303640', 'demoacc', 'counselign2025@gmail.com', '$2y$10$WACan4mm3m.aeWy1ctr5LeUCfNo8uF81No./F7Z1mE.2oO3NJbBbq', NULL, NULL, 1, 'student', '2025-10-19 14:54:02', 'http://192.168.18.65/Counselign/public/Photos/profile.png', '2025-10-19 15:10:17', NULL, NULL, NULL, NULL);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_code` (`reset_code`);

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
-- Indexes for table `student_family_info`
--
ALTER TABLE `student_family_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `counselor_availability`
--
ALTER TABLE `counselor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `follow_up_appointments`
--
ALTER TABLE `follow_up_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `student_academic_info`
--
ALTER TABLE `student_academic_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_address_info`
--
ALTER TABLE `student_address_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_family_info`
--
ALTER TABLE `student_family_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_personal_info`
--
ALTER TABLE `student_personal_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_residence_info`
--
ALTER TABLE `student_residence_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_services_availed`
--
ALTER TABLE `student_services_availed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `student_services_needed`
--
ALTER TABLE `student_services_needed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `student_special_circumstances`
--
ALTER TABLE `student_special_circumstances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

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
  ADD CONSTRAINT `fk_parent_appointment` FOREIGN KEY (`parent_appointment_id`) REFERENCES `follow_up_appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follow_up_appointments_ibfk_1` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`counselor_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `student_family_info`
--
ALTER TABLE `student_family_info`
  ADD CONSTRAINT `student_family_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
