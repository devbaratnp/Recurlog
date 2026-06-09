-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2026 at 07:42 AM
-- Server version: 10.11.18-MariaDB
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ektamultp_recurlog`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`cpses_ekakl8d6c4`@`localhost` PROCEDURE `TruncateAllTables` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE t_name VARCHAR(255);
    
    -- Cursor to find all table names in the current database
    DECLARE table_cursor CURSOR FOR 
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Disable foreign key checks to avoid dependency errors
    SET FOREIGN_KEY_CHECKS = 0;

    OPEN table_cursor;

    read_loop: LOOP
        FETCH table_cursor INTO t_name;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Dynamically build and execute the TRUNCATE command for each table
        SET @sql = CONCAT('TRUNCATE TABLE `', t_name, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE table_cursor;

    -- Re-enable foreign key checks
    SET FOREIGN_KEY_CHECKS = 1;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#1DB954',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `services_for` text DEFAULT NULL COMMENT 'JSON array of service types',
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_assignment_history`
--

CREATE TABLE `fscrm_assignment_history` (
  `id` int(11) NOT NULL,
  `entity_type` varchar(20) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `previous_assignee_id` int(11) DEFAULT NULL,
  `new_assignee_id` int(11) DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_categories`
--

CREATE TABLE `fscrm_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(10) DEFAULT '#1DB954',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_categories`
--

INSERT INTO `fscrm_categories` (`id`, `name`, `color`, `created_at`) VALUES
(1, 'Annual Maintenance', '#1DB954', '2026-06-07 17:25:13'),
(2, 'Filter Change', '#0EA5E9', '2026-06-07 17:25:13'),
(3, 'Repair', '#F59E0B', '2026-06-07 17:25:13'),
(4, 'Deep Cleaning', '#8B5CF6', '2026-06-07 17:25:13'),
(5, 'Installation', '#EC4899', '2026-06-07 17:25:13'),
(6, 'Inspection', '#6366F1', '2026-06-07 17:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_customers`
--

CREATE TABLE `fscrm_customers` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `services_for` text DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_customers`
--

INSERT INTO `fscrm_customers` (`id`, `name`, `address`, `area`, `phone`, `services_for`, `location_lat`, `location_lng`, `created_at`, `updated_at`) VALUES
(12, 'Deva Giri', 'Narayanpur', 'Narayanpur', '9847875116', '', NULL, NULL, '2026-06-08 22:57:44', '2026-06-08 22:57:44'),
(14, 'Chopnedi Subedi', 'Jharbaira', 'Narayanpur', '984785150', '', NULL, NULL, '2026-06-08 23:44:58', '2026-06-08 23:44:58'),
(15, 'Bibek Agrovate', 'Jharbaira', 'Narayanpur', '9847832136', '', NULL, NULL, '2026-06-08 23:57:16', '2026-06-08 23:57:16'),
(16, 'Sandesh KC', 'IDHUT PACHADI', 'Ghorahi', '9842001085', '', NULL, NULL, '2026-06-09 04:39:26', '2026-06-09 04:39:26'),
(18, 'MADHAB LOHANI', 'TERAUTA', 'Ghorahi', '9857830377', '', NULL, NULL, '2026-06-09 04:43:35', '2026-06-09 04:43:35'),
(19, 'KISHAN KHADKA', 'GHORAHI', 'Ghorahi', '9851284577', '', NULL, NULL, '2026-06-09 04:46:01', '2026-06-09 04:46:01'),
(20, 'Puspa Paudel', 'Ghorahi', 'Ghorahi', '984-794-1248', '', NULL, NULL, '2026-06-09 05:00:24', '2026-06-09 05:00:24'),
(21, 'Sangam Hotel', 'Narayanpur', 'Narayanpur', '974-9264436', '', NULL, NULL, '2026-06-09 05:45:33', '2026-06-09 05:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_localities`
--

CREATE TABLE `fscrm_localities` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_localities`
--

INSERT INTO `fscrm_localities` (`id`, `name`, `created_at`) VALUES
(5, 'Ghorahi', '2026-06-07 17:25:13'),
(6, 'Narayanpur', '2026-06-07 17:25:13'),
(7, 'Tulsipur', '2026-06-07 17:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_notifications`
--

CREATE TABLE `fscrm_notifications` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_notifications`
--

INSERT INTO `fscrm_notifications` (`id`, `text`, `type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 'Water Quality Test completed for Patel Residence', 'task_completed', 46, 1, '2026-06-07 12:00:00'),
(2, 'Drum Deep Cleaning completed for Singh Niwas', 'task_completed', 59, 1, '2026-06-07 12:00:00'),
(3, 'RO System Audit completed for Khanal House', 'task_completed', 91, 1, '2026-06-07 12:00:00'),
(4, 'Temperature Calibration Check completed for Birgunj Sweets', 'task_completed', 105, 1, '2026-06-07 12:00:00'),
(5, 'Kitchen Exhaust Inspection completed for Hotel Makalu', 'task_completed', 135, 1, '2026-06-07 12:00:00'),
(6, 'Ice Machine Cleaning missed at Birgunj Sweets', 'task_missed', 165, 1, '2026-06-06 12:00:00'),
(7, 'RO Preventive Inspection missed at Sharma Family', 'task_missed', 117, 1, '2026-06-05 12:00:00'),
(8, 'Backup Cooler Temperature Check completed for Modern Pharmacy', 'task_completed', 150, 1, '2026-06-05 12:00:00'),
(9, 'Temperature Log Inspection completed for Modern Pharmacy', 'task_completed', 69, 1, '2026-06-02 12:00:00'),
(10, 'AC Drain Line Repair completed for Modern Pharmacy', 'task_completed', 80, 1, '2026-06-02 12:00:00'),
(11, 'RO System Installation completed for Sharma Family', 'task_completed', 10, 1, '2026-06-01 12:00:00'),
(12, 'Drain Hose Replacement completed for Singh Niwas', 'task_completed', 144, 1, '2026-06-01 12:00:00'),
(13, 'Door Hinge Replacement completed for Birgunj Sweets', 'task_completed', 111, 1, '2026-05-30 12:00:00'),
(14, 'AC Thermostat Calibration completed for Hotel Makalu', 'task_completed', 40, 1, '2026-05-29 12:00:00'),
(15, 'Coil Deep Cleaning completed for Modern Pharmacy', 'task_completed', 74, 1, '2026-05-28 12:00:00'),
(16, 'Smart TV Setup Configuration completed for Khanal House', 'task_completed', 156, 1, '2026-05-26 12:00:00'),
(17, 'Temperature Calibration Check completed for Birgunj Sweets', 'task_completed', 104, 1, '2026-05-24 12:00:00'),
(18, 'Kitchen Exhaust Inspection completed for Hotel Makalu', 'task_completed', 134, 1, '2026-05-24 12:00:00'),
(19, 'RO Pressure Check completed for Patel Residence', 'task_completed', 139, 1, '2026-05-24 12:00:00'),
(20, 'RO Filter Change completed for Sharma Family', 'task_completed', 3, 1, '2026-05-23 12:00:00'),
(21, 'TV Display Repair completed for Gupta Electronics', 'task_completed', 24, 1, '2026-05-23 12:00:00'),
(22, 'Commercial Cooler Inspection completed for Hotel Makalu', 'task_completed', 36, 1, '2026-05-23 12:00:00'),
(23, 'New customer registered: Sharma Family', 'customer_added', NULL, 1, '2026-05-03 00:00:00'),
(24, 'New service added: AC Annual Maintenance for Gupta Electronics', 'service_added', NULL, 1, '2026-05-10 00:00:00'),
(25, 'New customer registered: Hotel Makalu', 'customer_added', NULL, 1, '2026-05-18 00:00:00'),
(26, 'New service added: RO Filter Change for Patel Residence', 'service_added', NULL, 1, '2026-05-23 00:00:00'),
(27, 'Ramesh Yadav completed 15 tasks this week', 'task_completed', NULL, 1, '2026-06-04 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_orders`
--

CREATE TABLE `fscrm_orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(200) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `problem` text DEFAULT NULL,
  `status` enum('pending','assigned','completed','cancelled') DEFAULT 'pending',
  `priority` enum('normal','urgent') DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_staff_name` varchar(200) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dispatch_date` date DEFAULT NULL,
  `dispatch_by` varchar(100) DEFAULT NULL,
  `received_name` varchar(100) DEFAULT NULL,
  `received_contact` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_orders`
--

INSERT INTO `fscrm_orders` (`id`, `customer_id`, `customer_name`, `service_for`, `problem`, `status`, `priority`, `assigned_to`, `assigned_staff_name`, `scheduled_date`, `completed_date`, `notes`, `dispatch_date`, `dispatch_by`, `received_name`, `received_contact`, `signature`, `created_at`, `updated_at`) VALUES
(16, 12, 'Deva Giri', 'Water Pump', 'NEPA flo 1hp 1 pcs Rs7000', 'assigned', '', 12, '', '2026-06-09', NULL, 'no', NULL, '', '', '', '', '2026-06-08 23:18:21', '2026-06-08 23:26:08'),
(17, 12, 'Deva Giri', 'Cooler', 'ultra 130 L Rs.26000', 'assigned', '', 12, '', '0000-00-00', NULL, 'No', NULL, '', '', '', '', '2026-06-08 23:21:57', '2026-06-08 23:21:57'),
(18, 12, 'Deva Giri', 'Washing Machine', 'Lg 8 KG Rs.80000', 'assigned', '', 11, '', '0000-00-00', NULL, '2026/06/01', NULL, '', '', '', '', '2026-06-08 23:38:00', '2026-06-08 23:38:00'),
(19, 12, 'Deva Giri', 'Celing Fan', 'Ultra celing 5 pcs ×2500= 12500', 'assigned', '', 11, '', '0000-00-00', NULL, '2026-06-20', NULL, '', '', '', '', '2026-06-08 23:40:13', '2026-06-08 23:40:13'),
(20, 15, 'Bibek Agrovate', 'Mantinanc!', 'Battery Inverter', 'assigned', '', 13, '', '0000-00-00', NULL, 'No', NULL, '', '', '', '', '2026-06-09 00:01:19', '2026-06-09 00:01:19'),
(21, 15, 'Bibek Agrovate', 'Service', 'Battery Inverter', 'assigned', '', 13, '', '0000-00-00', NULL, '2026-06-08', NULL, '', '', '', '', '2026-06-09 00:04:24', '2026-06-09 00:04:24'),
(25, 19, 'KISHAN KHADKA', 'Chimney problem', 'Chimney', 'assigned', '', 11, '', '0000-00-00', NULL, '', NULL, '', '', '', '', '2026-06-09 04:57:50', '2026-06-09 04:57:50'),
(26, 16, 'Sandesh KC', 'RO Problem', 'Ro Service', 'assigned', '', 11, '', '0000-00-00', NULL, '', NULL, '', '', '', '', '2026-06-09 04:59:23', '2026-06-09 04:59:23'),
(29, 21, 'Sangam Hotel', 'BATTERY', 'GOODYEAR 160 Ah Re.32000\npurano return Rs 5000', 'assigned', '', 11, '', '0000-00-00', NULL, '', NULL, '', '', '', '', '2026-06-09 05:47:38', '2026-06-09 05:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_push_tokens`
--

CREATE TABLE `fscrm_push_tokens` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `platform` enum('android','ios','web') NOT NULL,
  `expo_token` text DEFAULT NULL,
  `endpoint` text DEFAULT NULL,
  `p256dh` text DEFAULT NULL,
  `auth` text DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fscrm_push_tokens`
--

INSERT INTO `fscrm_push_tokens` (`id`, `user_id`, `platform`, `expo_token`, `endpoint`, `p256dh`, `auth`, `device_name`, `app_version`, `notifications_enabled`, `created_at`, `updated_at`) VALUES
(1, 1, 'web', NULL, 'https://fcm.googleapis.com/fcm/send/ddovFJ8EkZ0:APA91bESJn19xzB25f3dC_QDvmhXFA-AvuZL6T2xCAszTck-E-zDc04a-ugkST8Au5SYVYbWZ93cRb6_0RVVnUdyu9mG0AavE9XVTLIQsbZGTllt6iHqs0U7KCGStP5JA4tJxVQ4w_0M', 'BAwdT8RAzLgt2LIeJ9xXn7X8VU7Fz2lw2toMo5hXbW8drizlsveFEupp0piw4tc6FX0QNHjGh6Ta+fiUJOomW70=', 'jUHS4Xh9dUgRpCh+2edXng==', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '', 1, '2026-06-06 11:34:43', '2026-06-07 16:12:10'),
(2, 1, 'web', NULL, 'https://fcm.googleapis.com/fcm/send/dwzK8_GvKxY:APA91bFoWu2hEgx4Y3Hi7q_vZw-pW8gP_XzLViHEgFUxgskxeBAPtstlISzxNj-RRSvCNGJNzaiHHBPvr-z_n9Z7fPCGmajjDdZJRBwcX8DavHmikSsqxeKCQMmmrLFLGjXA5TknB4v4', 'BPRivDRIuSuwDx0IcvhwGa8GEftxvqBmCYM6VkzpE5urJ+9aGJMe3FDmhbHCkwCpBC1nRopGWvlCfLceNnyiXWo=', 'CMnbfFz8lW2pYU7BHaxv5A==', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '', 1, '2026-06-07 09:13:18', '2026-06-07 11:59:23'),
(3, 1, 'web', NULL, 'https://fcm.googleapis.com/fcm/send/fiZ8WvfYs2g:APA91bEtIQzTr3LnVBdyZyZNV5BwvF2bZYErDe9vAP9wXR2LA_lyIyvBzmSofI2LqcvmPCPB00kPORnF0nYg9AJucyaYEUsZjJuVW8y9EyZ-nYtXtPg_KzQQP0AcuCcsITWqBp1ZWneB', 'BOXIvvpkIxCJYeiSw3j66Oq3+7EzEVehjX59yTMXvNkK0gpI4T832ckJEZ+/X8Rwsn5CQ/h4YGDAAGN4JZghSEQ=', 'HFyH+3GOuwJCxEE2mBO/pw==', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '', 1, '2026-06-07 09:14:42', '2026-06-07 09:14:44'),
(4, 1, 'web', NULL, 'https://fcm.googleapis.com/fcm/send/f9ngoikn6Qw:APA91bGYrXvTu_WVrMfs_CNonIJBn9dUKoSkZKxb8hl24ONZ4F_65QvE1gcems5NAq2XJo9VKlviYVirPk1Hj5-Sf9gjiAM1VKQTIKdtzjrGTfVgYAVYNaFyWlnIxLX0Mjp0mdR_R7oN', 'BN2zXrxIXJuhquiZOxF699CTEsaYyCxy5oMBZdebVfbwXEw+rzgDSxgpF5JFSbB111Jwa9Abe+jM9XZYa6MVvgw=', 'u2MqzbRXx9tZo39vfCa3zw==', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', '', 1, '2026-06-07 09:15:18', '2026-06-07 14:32:04');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_services`
--

CREATE TABLE `fscrm_services` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `problem` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `first_scheduled_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rec_value` int(11) DEFAULT NULL,
  `rec_unit` varchar(20) DEFAULT NULL,
  `repeat_from` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_service_types`
--

CREATE TABLE `fscrm_service_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_service_types`
--

INSERT INTO `fscrm_service_types` (`id`, `name`, `created_at`) VALUES
(1, 'RO', '2026-06-07 17:25:13'),
(8, 'Chimney', '2026-06-08 00:01:47'),
(9, 'Battery Inverter', '2026-06-08 23:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_staff`
--

CREATE TABLE `fscrm_staff` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `active_tasks` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_staff`
--

INSERT INTO `fscrm_staff` (`id`, `name`, `phone`, `avatar`, `active_tasks`, `created_at`, `updated_at`) VALUES
(11, 'Gobind Chaudhary', '9868282417', '', 0, '2026-06-08 22:31:52', '2026-06-08 22:31:52'),
(12, 'Sabin Chaudhary', '9860620168', '', 0, '2026-06-08 22:33:48', '2026-06-08 22:33:48'),
(13, 'Bijaya Acharya', '9847835149', '', 0, '2026-06-08 23:50:29', '2026-06-08 23:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_tasks`
--

CREATE TABLE `fscrm_tasks` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `rec_value` int(11) DEFAULT NULL,
  `rec_unit` varchar(20) DEFAULT NULL,
  `repeat_from` varchar(20) DEFAULT NULL,
  `completed_by` varchar(100) DEFAULT NULL,
  `received_name` varchar(100) DEFAULT NULL,
  `received_contact` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_tasks`
--

INSERT INTO `fscrm_tasks` (`id`, `service_id`, `customer_id`, `title`, `status`, `scheduled_date`, `completed_date`, `assigned_to`, `notes`, `category_id`, `is_recurring`, `rec_value`, `rec_unit`, `repeat_from`, `completed_by`, `received_name`, `received_contact`, `signature`, `created_at`, `updated_at`) VALUES
(171, NULL, 12, 'RO', 'pending', '2026-06-01', NULL, 11, 'sales By Gautam Traders', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 23:01:03', '2026-06-08 23:01:03'),
(190, NULL, 12, 'Prod Simple Test 020711', 'pending', '2026-06-09', NULL, 11, 'Prod test', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-09 02:06:13', '2026-06-09 02:06:13'),
(191, NULL, 12, 'Prod Recurring Test 020712', 'pending', '2026-06-09', NULL, 11, 'Prod recurring', 1, 1, 1, 'days', 'last-done', NULL, NULL, NULL, NULL, '2026-06-09 02:06:13', '2026-06-09 02:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `fscrm_users`
--

CREATE TABLE `fscrm_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `staff_id` int(11) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fscrm_users`
--

INSERT INTO `fscrm_users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `staff_id`, `created_by`, `avatar`, `created_at`) VALUES
(1, 'Admin User', 'admin@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'admin', 1, NULL, NULL, NULL, '2026-06-07 17:25:13'),
(2, 'Ramesh Yadav', 'ramesh@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'staff', 1, 1, NULL, NULL, '2026-06-07 17:25:13'),
(3, 'Suresh Thakur', 'suresh@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'staff', 1, 2, NULL, NULL, '2026-06-07 17:25:13'),
(4, 'Bikash Sah', 'bikash@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'staff', 1, 3, NULL, NULL, '2026-06-07 17:25:13'),
(5, 'Anita Devi', 'anita@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'staff', 1, 4, NULL, NULL, '2026-06-07 17:25:13'),
(6, 'Manoj Kumar', 'manoj@demo.com', '$2y$10$q.p/TdZb0fIhn0gkoCA2A.DTSjjdEGMI.oKgI10.LjKd0NNy/rky.', 'staff', 1, 5, NULL, NULL, '2026-06-07 17:25:13'),
(7, 'Gobind Chaudhari', 'gobindchaudhary963@gmail.com', '$2y$10$JmMHFoE6t6bFXBSA9Kd6L.isCRodZsjSGL/rISri8kNwhr0ORXAi2', 'staff', 1, 10, '', NULL, '2026-06-08 22:28:01'),
(8, 'Sabin Chaudhary', 'csabin470@gmail.com', '$2y$10$Uq9UGHbRzosHaBeucv/Mauj.hz06RFTQEJyF266QnSzSTf4BSyvqm', 'staff', 1, 12, '', NULL, '2026-06-08 22:33:48'),
(9, 'Nikesh Gautam', 'gnikesh459@gmail.com', '$2y$10$oE8OwAzKmsfNJ8a5rscKluRXrTBIPukloedwGQVrloGgPv40t4HTW', 'admin', 1, NULL, '', NULL, '2026-06-08 22:41:00'),
(10, 'Nitesh Gsutam', 'omnamaste321@gmail.com', '$2y$10$Z4JUGfpbr.mzXnI0dnoaV.Nx0WihrAw4x6Ad3HvxxxzBPf.wDvvo.', 'admin', 1, NULL, '', NULL, '2026-06-08 22:44:24'),
(11, 'Bijaya Acharya', 'raptiecoblock@gmail.com', '$2y$10$FTywsco/XACPxxCubJ5Se.JqEAOTrqMB8ag9tsxNICKiLm81J9er2', 'staff', 1, 13, '', NULL, '2026-06-08 23:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `priority` varchar(20) DEFAULT 'normal' COMMENT 'normal, urgent, low',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_staff_name` varchar(255) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `service_for` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `first_scheduled_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recurrence_value` int(11) DEFAULT NULL,
  `recurrence_unit` varchar(10) DEFAULT NULL COMMENT 'days, weeks, months, years',
  `recurrence_repeat_from` varchar(20) DEFAULT 'last_service' COMMENT 'last_service or fixed_schedule',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_types`
--

CREATE TABLE `service_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL DEFAULT '',
  `avatar` varchar(500) DEFAULT NULL,
  `active_tasks` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','missed') NOT NULL DEFAULT 'pending',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT 'Admin',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fscrm_assignment_history`
--
ALTER TABLE `fscrm_assignment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_changed_by` (`changed_by`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `fscrm_categories`
--
ALTER TABLE `fscrm_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fscrm_customers`
--
ALTER TABLE `fscrm_customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fscrm_localities`
--
ALTER TABLE `fscrm_localities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `fscrm_notifications`
--
ALTER TABLE `fscrm_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fscrm_orders`
--
ALTER TABLE `fscrm_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `fscrm_push_tokens`
--
ALTER TABLE `fscrm_push_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_platform` (`platform`);

--
-- Indexes for table `fscrm_services`
--
ALTER TABLE `fscrm_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `fscrm_service_types`
--
ALTER TABLE `fscrm_service_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `fscrm_staff`
--
ALTER TABLE `fscrm_staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fscrm_tasks`
--
ALTER TABLE `fscrm_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `fscrm_users`
--
ALTER TABLE `fscrm_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `service_types`
--
ALTER TABLE `service_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fscrm_assignment_history`
--
ALTER TABLE `fscrm_assignment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fscrm_categories`
--
ALTER TABLE `fscrm_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fscrm_customers`
--
ALTER TABLE `fscrm_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `fscrm_localities`
--
ALTER TABLE `fscrm_localities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fscrm_notifications`
--
ALTER TABLE `fscrm_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `fscrm_orders`
--
ALTER TABLE `fscrm_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `fscrm_push_tokens`
--
ALTER TABLE `fscrm_push_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fscrm_services`
--
ALTER TABLE `fscrm_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `fscrm_service_types`
--
ALTER TABLE `fscrm_service_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `fscrm_staff`
--
ALTER TABLE `fscrm_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `fscrm_tasks`
--
ALTER TABLE `fscrm_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `fscrm_users`
--
ALTER TABLE `fscrm_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_types`
--
ALTER TABLE `service_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
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
-- Constraints for table `fscrm_orders`
--
ALTER TABLE `fscrm_orders`
  ADD CONSTRAINT `fscrm_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fscrm_orders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fscrm_services`
--
ALTER TABLE `fscrm_services`
  ADD CONSTRAINT `fscrm_services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fscrm_services_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `fscrm_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fscrm_services_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fscrm_tasks`
--
ALTER TABLE `fscrm_tasks`
  ADD CONSTRAINT `fscrm_tasks_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `fscrm_services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fscrm_tasks_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `fscrm_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fscrm_tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `fscrm_staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fscrm_tasks_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `fscrm_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `services_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
