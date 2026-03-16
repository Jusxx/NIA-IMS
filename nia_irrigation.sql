-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 08:31 AM
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
-- Database: `nia_irrigation`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `alert_type` varchar(80) NOT NULL,
  `severity` enum('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `status` enum('Open','Acknowledged','Resolved') NOT NULL DEFAULT 'Open',
  `service_area_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `canals`
--

CREATE TABLE `canals` (
  `canal_id` int(11) NOT NULL,
  `canal_name` varchar(150) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credential_prints`
--

CREATE TABLE `credential_prints` (
  `print_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `printed_by` int(11) NOT NULL,
  `printed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drainages`
--

CREATE TABLE `drainages` (
  `drainage_id` int(11) NOT NULL,
  `drainage_name` varchar(150) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

CREATE TABLE `farmers` (
  `farmer_id` int(11) NOT NULL,
  `farmer_name` varchar(150) NOT NULL,
  `association_name` varchar(150) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `is_president` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `onboarded_via` enum('Website','Field Operator','Admin') NOT NULL DEFAULT 'Admin',
  `onboarded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`farmer_id`, `farmer_name`, `association_name`, `address`, `phone`, `service_area_id`, `created_at`, `user_id`, `is_president`, `created_by`, `onboarded_via`, `onboarded_at`) VALUES
(1, 'Jus Alaed', 'RMC', 'Koronadal City', '09462697122', 2, '2026-01-15 11:43:16', 11, 0, NULL, 'Admin', NULL),
(2, 'test3', 'RMC', 'Koronadal City', '09352626931', 1, '2026-01-16 14:58:52', 16, 0, NULL, 'Admin', NULL),
(3, 'test5', 'RMC', 'Koronadal City', '09111111111', 3, '2026-01-16 15:00:50', 17, 0, NULL, 'Admin', NULL),
(4, 'Alaed Jus', 'RM', 'Bo. 2', '09063675854', 1, '2026-02-07 18:33:08', 18, 0, NULL, 'Admin', NULL),
(5, 'Des', 'Rmc', 'kor city', '09063675854', 1, '2026-02-07 18:43:37', 19, 1, 9, 'Admin', '2026-02-08 02:43:37'),
(6, 'test', 'test', 'test', '09063675854', 1, '2026-02-07 18:46:54', 20, 1, 9, 'Admin', '2026-02-08 02:46:54'),
(7, 'test', 'test', 'test', '630063675854', 1, '2026-02-07 18:47:34', 21, 1, 9, 'Field Operator', '2026-02-08 02:47:34'),
(8, 'test', 'test', 'test', '639063675854', 1, '2026-02-07 18:48:19', 22, 1, 9, 'Field Operator', '2026-02-08 02:48:19'),
(9, 'test', 'test', 'test', '09063675854', 1, '2026-02-07 18:51:10', 23, 1, 9, 'Field Operator', '2026-02-08 02:51:10'),
(10, 'test', 'test', 'test', '9063675854', 1, '2026-02-07 18:52:56', 24, 1, 9, 'Admin', '2026-02-08 02:52:56'),
(11, '1', '1', '1', '9063675854', 1, '2026-02-07 18:57:45', 25, 1, 9, 'Admin', '2026-02-08 02:57:45'),
(12, 'des nat', 'farmer of bo', 'bo 2', '0063675854', 3, '2026-02-07 19:04:32', 26, 1, 9, 'Admin', '2026-02-08 03:04:32'),
(13, 'testsss', 'testsss', 'testsss', '0063675854', 4, '2026-02-07 19:05:37', 27, 1, 9, 'Admin', '2026-02-08 03:05:37'),
(14, 'sdas', 'asd', 'asd', '639063675854', 4, '2026-02-07 19:07:49', 28, 1, 9, 'Admin', '2026-02-08 03:07:49'),
(15, 'sdasd', 'sadsadsa', 'asdsad', '639063675854', 4, '2026-02-07 19:11:12', 29, 1, 9, 'Admin', '2026-02-08 03:11:12'),
(16, '213123', '123123', '123123', '639063675854', 4, '2026-02-07 19:13:51', 30, 1, 9, 'Admin', '2026-02-08 03:13:51'),
(17, 'Desiery N', 'NDMU', 'Koronadal City', '639063675854', 4, '2026-02-07 19:16:09', 31, 1, 9, 'Admin', '2026-02-08 03:16:09'),
(19, 'Des', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 3, '2026-02-23 00:31:42', 33, 1, 9, 'Admin', '2026-02-23 08:31:42'),
(20, 'DES', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639777762166', 1, '2026-02-23 06:35:36', 34, 1, 9, 'Admin', '2026-02-23 14:35:36'),
(21, 'test1', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 3, '2026-02-23 06:37:41', 35, 1, 9, 'Admin', '2026-02-23 14:37:41'),
(22, 'des', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 3, '2026-02-23 07:55:27', 36, 1, 9, 'Admin', '2026-02-23 15:55:27'),
(23, 'Des', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 3, '2026-02-23 08:29:50', 37, 1, 9, 'Admin', '2026-02-23 16:29:50'),
(24, 'Des', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 3, '2026-02-23 08:38:48', 38, 1, 9, 'Admin', '2026-02-23 16:38:48'),
(25, 'DOMINIC PATIDA', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639777762166', 3, '2026-02-23 09:05:54', 39, 1, 9, 'Admin', '2026-02-23 17:05:54'),
(26, 'JUS', 'RMC', 'Purok Waling-Waling St, Brgy Zone 2', '639462697911', 2, '2026-02-23 10:13:46', 40, 1, 9, 'Admin', '2026-02-23 18:13:46'),
(27, 'DOM', 'RMC', 'Prk. Riverside, Bo. 2, Koronadal City', '639777762166', 5, '2026-02-27 01:12:46', 41, 1, 9, 'Admin', '2026-02-27 09:12:46'),
(28, 'DES', 'RMC', 'Prk. Riverside, Bo. 2, Koronadal City', '639063675854', 5, '2026-02-27 02:13:29', 42, 1, 9, 'Admin', '2026-02-27 10:13:29'),
(29, 'DES', 'RMC', 'Prk. Riverside, Bo. 2, Koronadal City', '639063675854', 5, '2026-02-27 02:19:38', 43, 1, 9, 'Admin', '2026-02-27 10:19:38'),
(30, '1', '1', '1', '639063675854', 5, '2026-02-27 02:25:41', 44, 1, 9, 'Admin', '2026-02-27 10:25:41'),
(31, 'dessx', 'RMC', 'Prk. Riverside, Bo. 2, Koronadal City', '639063675854', 5, '2026-02-27 02:28:28', 45, 1, 9, 'Admin', '2026-02-27 10:28:28'),
(32, '12', 'LPU', 'Purok Waling-Waling St, Brgy Zone 2', '639777762166', 5, '2026-02-27 02:45:12', 46, 1, 9, 'Admin', '2026-02-27 10:45:12'),
(33, 'test123', '123', '123', '639123123123', 7, '2026-03-02 03:25:15', 48, 1, 9, 'Admin', '2026-03-02 11:25:15'),
(34, 'done1', 'test', 'Purok Waling-Waling St, Brgy Zone 2', '639123123123', 5, '2026-03-02 05:18:39', 49, 1, 9, 'Admin', '2026-03-02 13:18:39'),
(35, 'Desiery Natingas', 'RM', 'Purok Waling-Waling St, Brgy Zone 2', '639063675854', 6, '2026-03-02 05:53:14', 50, 1, 9, 'Admin', '2026-03-02 13:53:14'),
(36, 'demotest3', '123', '123', '639063675854', 5, '2026-03-02 06:43:23', 51, 1, 9, 'Admin', '2026-03-02 14:43:23'),
(37, 'another', '123', '123', '639213123123', 5, '2026-03-02 07:50:08', 52, 1, 9, 'Admin', '2026-03-02 15:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_lots`
--

CREATE TABLE `farmer_lots` (
  `lot_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `lot_code` varchar(50) DEFAULT NULL,
  `area_ha` decimal(10,2) DEFAULT NULL,
  `canal_width_m` decimal(8,2) DEFAULT NULL,
  `canal_length_m` decimal(8,2) DEFAULT NULL,
  `canal_id` int(11) DEFAULT NULL,
  `drainage_id` int(11) DEFAULT NULL,
  `location_desc` varchar(200) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `title_photo_path` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_lots`
--

INSERT INTO `farmer_lots` (`lot_id`, `farmer_id`, `lot_code`, `area_ha`, `canal_width_m`, `canal_length_m`, `canal_id`, `drainage_id`, `location_desc`, `latitude`, `longitude`, `title_photo_path`, `remarks`, `created_at`) VALUES
(1, 20, 'code 1', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.3242400, 124.9570325, 'uploads/lot_titles/lot_title_20_1771828536_da7a4197.png', NULL, '2026-02-23 06:35:36'),
(2, 21, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.3272764, 124.9485030, 'uploads/lot_titles/lot_title_21_1771828661_9fa97b7a.png', NULL, '2026-02-23 06:37:41'),
(3, 22, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.4687054, 124.8705903, 'uploads/lot_titles/lot_title_22_1771833327_cc0242f6.png', NULL, '2026-02-23 07:55:27'),
(4, 23, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.4817645, 124.8562181, 'uploads/lot_titles/lot_title_23_1771835390_7e718280.jpg', NULL, '2026-02-23 08:29:51'),
(5, 24, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.3970450, 124.9393988, 'uploads/lot_titles/lot_title_24_1771835928_455d794f.png', NULL, '2026-02-23 08:38:48'),
(6, 25, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.4188809, 124.9393988, 'uploads/lot_titles/lot_title_25_1771837554_936b974c.jpg', NULL, '2026-02-23 09:05:54'),
(7, 26, '123', NULL, NULL, NULL, NULL, NULL, 'Koronadal, South Cotabato, Soccsksargen, 9506, Philippines', 6.5004041, 124.8435437, 'uploads/lot_titles/lot_title_26_1771841626_d4b3f2d9.png', NULL, '2026-02-23 10:13:46'),
(8, 27, 'Lot 1', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_27_1772154766_39735f73.png', NULL, '2026-02-27 01:12:46'),
(9, 28, 'Lot 1', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_28_1772158409_6f45297d.png', NULL, '2026-02-27 02:13:29'),
(10, 29, 'code 1', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_29_1772158778_31b2669f.png', NULL, '2026-02-27 02:19:38'),
(11, 30, '123', NULL, NULL, NULL, NULL, NULL, 'kanto lang', 6.6332343, 124.7708090, 'uploads/lot_titles/lot_title_30_1772159141_84632389.png', NULL, '2026-02-27 02:25:41'),
(12, 31, 'Lot 1', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_31_1772159308_724189ce.png', NULL, '2026-02-27 02:28:28'),
(13, 32, '123', NULL, NULL, NULL, NULL, NULL, 'KCC Mall de Zamboanga, Governor Camins Avenue, Zone Ⅱ, Santa Maria, Zamboanga City, Zamboanga Peninsula, 7000, Philippines', 6.9192453, 122.0736024, 'uploads/lot_titles/lot_title_32_1772160312_85797882.png', NULL, '2026-02-27 02:45:12'),
(14, 33, '123', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_33_1772421915_af86488d.png', NULL, '2026-03-02 03:25:15'),
(15, 34, '123', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_34_1772428719_7ddb5e59.png', NULL, '2026-03-02 05:18:39'),
(16, 35, '123', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_35_1772430794_8e391dfb.png', NULL, '2026-03-02 05:53:14'),
(17, 36, '123', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_36_1772433803_46411342.png', NULL, '2026-03-02 06:43:23'),
(18, 37, '123', NULL, NULL, NULL, NULL, NULL, 'Santo Niño, Koronadal, South Cotabato, Soccsksargen, Philippines', 6.4934666, 124.8680177, 'uploads/lot_titles/lot_title_37_1772437808_3f798caf.png', NULL, '2026-03-02 07:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `farmer_requests`
--

CREATE TABLE `farmer_requests` (
  `request_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `canal_id` int(11) DEFAULT NULL,
  `drainage_id` int(11) DEFAULT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `request_type` enum('Irrigation Request','Schedule Adjustment','Water Allocation','Technical Concern') DEFAULT 'Irrigation Request',
  `request_source` enum('Website','Field Operator','Admin') NOT NULL DEFAULT 'Website',
  `collected_by` int(11) DEFAULT NULL,
  `issue_category` varchar(80) DEFAULT NULL,
  `request_details` text DEFAULT NULL,
  `requested_by_user_id` int(11) DEFAULT NULL,
  `requested_by_role` enum('Farmer','President','Field Operator') NOT NULL DEFAULT 'Farmer',
  `preferred_date` date DEFAULT NULL,
  `preferred_start` time DEFAULT NULL,
  `preferred_end` time DEFAULT NULL,
  `urgency` enum('Normal','Urgent') NOT NULL DEFAULT 'Normal',
  `location_desc` varchar(200) DEFAULT NULL,
  `landmark` varchar(200) DEFAULT NULL,
  `maps_link` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `status` enum('Pending','On Process','Approved','Rejected','Technician Assigned','Irrigation Started','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_technician_id` int(11) DEFAULT NULL,
  `request_stage` enum('Pending','Approved','Rejected','Scheduled','Assigned','In Progress','Completed') DEFAULT 'Pending',
  `approval_mode` enum('Manual','Auto') NOT NULL DEFAULT 'Manual',
  `auto_suggested_date` date DEFAULT NULL,
  `auto_suggested_start` time DEFAULT NULL,
  `auto_suggested_end` time DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `decision_reason` varchar(255) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_requests`
--

INSERT INTO `farmer_requests` (`request_id`, `farmer_id`, `lot_id`, `canal_id`, `drainage_id`, `service_area_id`, `request_type`, `request_source`, `collected_by`, `issue_category`, `request_details`, `requested_by_user_id`, `requested_by_role`, `preferred_date`, `preferred_start`, `preferred_end`, `urgency`, `location_desc`, `landmark`, `maps_link`, `photo_path`, `latitude`, `longitude`, `location_lat`, `location_lng`, `status`, `created_at`, `assigned_technician_id`, `request_stage`, `approval_mode`, `auto_suggested_date`, `auto_suggested_start`, `auto_suggested_end`, `review_notes`, `decision_reason`, `reviewed_by`, `reviewed_at`, `approved_by`, `approved_at`, `technician_id`, `started_at`, `completed_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'test', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-15 12:55:57', NULL, 'Pending', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'test', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-15 12:57:57', NULL, 'Pending', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'test', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-15 13:00:02', NULL, 'Pending', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, 'test', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-16 14:30:36', NULL, 'Approved', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:07:39', NULL, NULL, NULL),
(5, 3, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'no water', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-16 15:01:11', NULL, 'Approved', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-23 18:10:13', NULL, NULL, NULL),
(6, 1, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'water', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-16 15:40:03', 13, 'Assigned', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-01-19 17:24:38', NULL, NULL, NULL),
(7, 1, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, 'tes', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-01-19 09:40:37', NULL, 'Approved', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-08 02:35:20', NULL, NULL, NULL),
(8, 17, NULL, NULL, NULL, NULL, 'Technical Concern', 'Website', NULL, NULL, 'test', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'On Process', '2026-02-07 19:18:54', 13, 'In Progress', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-08 03:25:39', NULL, NULL, NULL),
(9, 24, NULL, NULL, NULL, NULL, 'Irrigation Request', 'Website', NULL, NULL, 'Magpatubig lang po.', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-02-23 08:45:31', 13, 'In Progress', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-27 17:39:55', NULL, NULL, NULL),
(10, 25, NULL, NULL, NULL, NULL, 'Irrigation Request', 'Website', NULL, NULL, 'PATUBIG', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-02-23 09:55:54', 13, 'In Progress', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-23 17:56:09', NULL, NULL, NULL),
(11, 27, NULL, NULL, NULL, NULL, 'Irrigation Request', 'Website', NULL, NULL, 'water irrigation', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-02-27 01:17:38', 13, 'Assigned', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-27 09:18:30', NULL, NULL, NULL),
(12, 31, NULL, NULL, NULL, NULL, 'Irrigation Request', 'Website', NULL, NULL, '123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-02-27 02:29:31', 13, 'Assigned', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-27 10:29:44', NULL, NULL, NULL),
(13, 32, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, 'hatdog', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-02-27 02:45:55', 13, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-02-27 10:46:15', NULL, NULL, NULL),
(14, 31, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, '12312', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-03-02 03:17:43', NULL, 'Approved', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 11:17:54', NULL, NULL, NULL),
(15, 31, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, '123123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-03-02 05:01:19', NULL, 'Approved', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:01:52', NULL, NULL, NULL),
(16, 31, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, '123123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-03-02 05:13:34', 13, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:14:24', NULL, NULL, NULL),
(17, 34, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, '123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-03-02 05:18:58', 47, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:22:28', NULL, NULL, NULL),
(18, 35, NULL, NULL, NULL, NULL, 'Water Allocation', 'Website', NULL, NULL, '123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-03-02 05:53:37', 47, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:53:55', NULL, NULL, NULL),
(19, 35, NULL, NULL, NULL, NULL, 'Irrigation Request', 'Website', NULL, NULL, 'sad', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rejected', '2026-03-02 05:58:21', 47, 'Rejected', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 13:58:50', NULL, NULL, NULL),
(20, 35, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, '12345', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-03-02 06:29:15', 13, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 14:29:34', NULL, NULL, NULL),
(21, 36, NULL, NULL, NULL, NULL, 'Schedule Adjustment', 'Website', NULL, NULL, '123', NULL, 'Farmer', NULL, NULL, NULL, 'Normal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Completed', '2026-03-02 06:43:42', 13, 'Completed', 'Manual', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, '2026-03-02 14:43:57', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `form_templates`
--

CREATE TABLE `form_templates` (
  `template_id` int(11) NOT NULL,
  `template_name` varchar(150) NOT NULL,
  `form_type` enum('Farmer Registration','Irrigation Request') NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_templates`
--

INSERT INTO `form_templates` (`template_id`, `template_name`, `form_type`, `is_active`, `created_at`) VALUES
(1, 'NIA Farmer Registration Form', 'Farmer Registration', 1, '2026-02-07 18:31:00'),
(2, 'NIA Irrigation Request Form', 'Irrigation Request', 1, '2026-02-07 18:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `form_template_fields`
--

CREATE TABLE `form_template_fields` (
  `field_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `field_label` varchar(150) NOT NULL,
  `field_key` varchar(80) NOT NULL,
  `field_type` enum('text','number','date','phone','textarea','select','checkbox') NOT NULL DEFAULT 'text',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_template_fields`
--

INSERT INTO `form_template_fields` (`field_id`, `template_id`, `field_label`, `field_key`, `field_type`, `sort_order`, `is_required`) VALUES
(1, 1, 'Farmer Name', 'farmer_name', 'text', 1, 1),
(2, 1, 'Association', 'association_name', 'text', 2, 0),
(3, 1, 'Address', 'address', 'text', 3, 0),
(4, 1, 'Cellphone Number', 'phone', 'phone', 4, 1),
(5, 1, 'Is President', 'is_president', 'checkbox', 5, 0),
(6, 1, 'Lot Code', 'lot_code', 'text', 6, 0),
(7, 1, 'Lot Area (ha)', 'area_ha', 'number', 7, 1),
(8, 1, 'Canal', 'canal_id', 'select', 8, 1),
(9, 1, 'Drainage', 'drainage_id', 'select', 9, 0),
(10, 1, 'Canal Width (m)', 'canal_width_m', 'number', 10, 0),
(11, 1, 'Canal Length (m)', 'canal_length_m', 'number', 11, 0),
(12, 1, 'Location Description', 'location_desc', 'textarea', 12, 0),
(13, 2, 'Farmer Name', 'farmer_name', 'text', 1, 1),
(14, 2, 'Lot', 'lot_id', 'select', 2, 1),
(15, 2, 'Preferred Date', 'preferred_date', 'date', 3, 0),
(16, 2, 'Preferred Start', 'preferred_start', 'text', 4, 0),
(17, 2, 'Preferred End', 'preferred_end', 'text', 5, 0),
(18, 2, 'Request Details', 'request_details', 'textarea', 6, 0);

-- --------------------------------------------------------

--
-- Table structure for table `irrigation_batches`
--

CREATE TABLE `irrigation_batches` (
  `batch_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `batch_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `irrigation_schedules`
--

CREATE TABLE `irrigation_schedules` (
  `schedule_id` int(11) NOT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `schedule_source` enum('Manual','Auto') NOT NULL DEFAULT 'Manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `irrigation_schedules`
--

INSERT INTO `irrigation_schedules` (`schedule_id`, `service_area_id`, `schedule_date`, `start_time`, `end_time`, `created_by`, `status`, `schedule_source`, `created_at`, `request_id`) VALUES
(1, 1, '2026-01-15', '09:00:00', '10:00:00', 6, 'Active', 'Manual', '2026-01-15 06:10:17', NULL),
(2, 1, '2026-01-15', '08:00:00', '09:00:00', 10, 'Completed', 'Manual', '2026-01-15 08:08:44', NULL),
(3, 1, '2026-01-15', '16:00:00', '17:00:00', 10, 'Completed', 'Manual', '2026-01-15 09:13:50', NULL),
(4, 2, '2026-01-15', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-01-15 09:28:13', NULL),
(5, 3, '2026-01-20', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-01-16 15:45:18', NULL),
(6, 2, '2026-01-19', '19:00:00', '22:00:00', 9, 'Active', 'Manual', '2026-01-19 09:24:31', 6),
(7, 2, '2026-01-20', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-01-19 09:24:54', 6),
(8, 4, '2026-02-09', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-07 19:23:18', 8),
(9, 4, '2026-02-10', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-07 19:26:01', 8),
(10, 3, '2026-02-23', '17:00:00', '22:00:00', 9, 'Active', 'Manual', '2026-02-23 08:53:51', 9),
(11, 3, '2026-02-25', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-23 09:56:21', 10),
(12, 5, '2026-02-28', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-27 01:18:54', 11),
(13, 5, '2026-03-01', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-27 02:31:30', 12),
(14, 5, '2026-03-11', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-02-27 02:47:24', 13),
(15, 5, '2026-04-09', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-03-02 05:23:19', 17),
(16, 6, '2026-04-11', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-03-02 05:54:33', 18),
(17, 6, '2026-03-02', '14:00:00', '22:00:00', 9, 'Active', 'Manual', '2026-03-02 05:59:14', 19),
(18, 6, '2026-04-04', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-03-02 06:36:57', 20),
(19, 5, '2026-04-06', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-03-02 06:39:20', 16),
(20, 5, '2026-04-11', '08:00:00', '10:00:00', 9, 'Active', 'Manual', '2026-03-02 06:44:08', 21);

-- --------------------------------------------------------

--
-- Table structure for table `paper_forms`
--

CREATE TABLE `paper_forms` (
  `form_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `form_type` enum('Farmer Registration','Irrigation Request') NOT NULL,
  `issued_to_farmer_id` int(11) DEFAULT NULL,
  `issued_to_name` varchar(150) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `returned_at` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `status` enum('Issued','Returned','Encoded','Cancelled') NOT NULL DEFAULT 'Issued',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paper_forms`
--

INSERT INTO `paper_forms` (`form_id`, `template_id`, `form_type`, `issued_to_farmer_id`, `issued_to_name`, `issued_by`, `issued_at`, `returned_at`, `encoded_by`, `status`, `notes`) VALUES
(1, 1, 'Farmer Registration', 4, NULL, 9, '2026-02-08 02:35:42', NULL, NULL, 'Issued', NULL),
(2, 1, 'Farmer Registration', 17, NULL, 12, '2026-02-23 08:37:10', NULL, NULL, 'Issued', NULL),
(3, 1, 'Farmer Registration', 17, NULL, 9, '2026-02-23 11:11:06', NULL, NULL, 'Issued', NULL),
(4, 1, 'Farmer Registration', 17, NULL, 9, '2026-02-23 11:14:09', NULL, NULL, 'Issued', NULL),
(5, 1, 'Farmer Registration', 17, NULL, 9, '2026-02-23 11:29:44', NULL, NULL, 'Issued', NULL),
(6, 1, 'Farmer Registration', 21, NULL, 9, '2026-02-23 14:40:24', NULL, NULL, 'Issued', NULL),
(7, 1, 'Farmer Registration', NULL, NULL, 9, '2026-02-23 17:41:27', NULL, NULL, 'Issued', NULL),
(8, 2, 'Irrigation Request', 32, NULL, 12, '2026-02-27 12:58:10', NULL, NULL, 'Issued', NULL),
(9, 1, 'Farmer Registration', NULL, NULL, 9, '2026-03-02 08:32:32', NULL, NULL, 'Issued', NULL),
(10, 1, 'Farmer Registration', 1, NULL, 9, '2026-03-02 08:56:49', NULL, NULL, 'Issued', NULL),
(11, 1, 'Farmer Registration', 25, NULL, 9, '2026-03-02 09:00:00', NULL, NULL, 'Issued', NULL),
(12, 1, 'Farmer Registration', 25, NULL, 9, '2026-03-02 09:08:16', NULL, NULL, 'Issued', NULL),
(13, 1, 'Farmer Registration', 25, NULL, 9, '2026-03-02 09:12:41', NULL, NULL, 'Issued', NULL),
(14, 1, 'Farmer Registration', 25, NULL, 9, '2026-03-02 10:00:39', NULL, NULL, 'Issued', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_exports`
--

CREATE TABLE `report_exports` (
  `export_id` int(11) NOT NULL,
  `report_name` varchar(120) NOT NULL,
  `filters_json` text DEFAULT NULL,
  `file_format` enum('PDF','Excel') NOT NULL,
  `exported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_approvals`
--

CREATE TABLE `request_approvals` (
  `approval_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `role_required` enum('Administrator','Operations Staff') NOT NULL,
  `method` enum('Manual','Auto') NOT NULL DEFAULT 'Manual',
  `status` enum('Pending','Approved','Declined') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_attachments`
--

CREATE TABLE `request_attachments` (
  `attachment_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(80) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_areas`
--

CREATE TABLE `service_areas` (
  `service_area_id` int(11) NOT NULL,
  `area_name` varchar(150) NOT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `total_area_ha` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_areas`
--

INSERT INTO `service_areas` (`service_area_id`, `area_name`, `municipality`, `province`, `total_area_ha`, `created_at`, `latitude`, `longitude`) VALUES
(1, 'Canal A', 'Koronadal', 'South Cotabato', 120.50, '2026-01-15 06:09:56', NULL, NULL),
(2, 'Canal B', 'Koronadal City', 'South Cotabato', 1.00, '2026-01-15 09:28:00', NULL, NULL),
(3, 'Tantangan Canal A', 'Tantangan', 'South Cotabato', 200.00, '2026-01-16 14:59:49', NULL, NULL),
(4, 'Canal A 1123', 'Koronadal City', 'South Cotabato', 123123.00, '2026-02-07 19:04:56', 6.4844827, 124.4700063),
(5, 'Bo. 2', 'Koronadal City', 'South Cotabato', 500.00, '2026-02-27 01:09:09', 6.4934666, 124.8680177),
(6, 'Tampakan', 'Tampakan', 'South Cotabato', 500.00, '2026-02-27 01:10:09', 6.4437607, 124.9269132),
(7, 'Namnama', 'Koronadal City', 'South Cotabato', NULL, '2026-02-27 01:10:33', 6.5247540, 124.8630109);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `sms_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sms_type` enum('Approved','Declined','Info') NOT NULL DEFAULT 'Info',
  `provider` enum('PhilSMS') NOT NULL DEFAULT 'PhilSMS',
  `recipient_role` enum('Farmer','President') DEFAULT NULL,
  `status` enum('Queued','Sent','Failed') NOT NULL DEFAULT 'Queued',
  `provider_message_id` varchar(80) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `payload_json` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`sms_id`, `farmer_id`, `request_id`, `phone`, `message`, `sms_type`, `provider`, `recipient_role`, `status`, `provider_message_id`, `error_message`, `payload_json`, `sent_at`) VALUES
(1, 1, 7, '639462697122', 'NIA: Your irrigation request #7 is approved.', 'Approved', 'PhilSMS', 'Farmer', 'Sent', NULL, NULL, '{\"recipient\":\"639462697122\",\"sender_id\":\"NIA\",\"type\":\"plain\",\"message\":\"NIA: Your irrigation request #7 is approved.\"}', '2026-02-07 18:35:22'),
(2, 5, NULL, '639063675854', 'NIA: Your account is ready. Username: farmertest02. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Sent', NULL, NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"NIA\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: farmertest02. Please log in and change your password on first login.\"}', '2026-02-07 18:43:39'),
(3, 6, NULL, '639063675854', 'NIA: Your account is ready. Username: testfarmer04. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Sent', NULL, NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: testfarmer04. Please log in and change your password on first login.\"}', '2026-02-07 18:46:56'),
(4, 7, NULL, '630063675854', 'NIA: Your account is ready. Username: 123456. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Sent', NULL, NULL, '{\"recipient\":\"630063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: 123456. Please log in and change your password on first login.\"}', '2026-02-07 18:47:35'),
(5, 8, NULL, '639063675854', 'NIA: Your account is ready. Username: 1111. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Sent', NULL, NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: 1111. Please log in and change your password on first login.\"}', '2026-02-07 18:48:20'),
(6, 9, NULL, '639063675854', 'NIA: Your account is ready. Username: 1234512345. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: 1234512345. Please log in and change your password on first login.\"}', '2026-02-07 18:51:12'),
(7, 10, NULL, '9063675854', 'NIA: Your account is ready. Username: ssssssss. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, 'Invalid phone format', '{\"recipient_raw\":\"9063675854\"}', '2026-02-07 18:52:56'),
(8, 11, NULL, '9063675854', 'NIA: Your account is ready. Username: 11. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, 'Invalid phone format', '{\"recipient_raw\":\"9063675854\"}', '2026-02-07 18:57:45'),
(9, 12, NULL, '0063675854', 'NIA: Your account is ready. Username: 111111. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, 'Invalid phone format', '{\"recipient_raw\":\"0063675854\"}', '2026-02-07 19:04:32'),
(10, 13, NULL, '0063675854', 'NIA: Your account is ready. Username: wat. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, 'Invalid phone format', '{\"recipient_raw\":\"0063675854\"}', '2026-02-07 19:05:37'),
(11, 14, NULL, '639063675854', 'NIA: Your account is ready. Username: 12312321. Please log in and change your password on first login.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Your account is ready. Username: 12312321. Please log in and change your password on first login.\"}', '2026-02-07 19:07:51'),
(12, 15, NULL, '639063675854', 'NIA: Account created. Username: 12345321. Please log in to NIA XII IMS and change your password.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Account created. Username: 12345321. Please log in to NIA XII IMS and change your password.\"}', '2026-02-07 19:11:13'),
(13, 16, NULL, '639063675854', 'NIA: Username 2312321. Access NIA XII IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '69878ef38d489', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Username 2312321. Access NIA XII IMS. Update your password.\"}', '2026-02-07 19:13:54'),
(14, 17, NULL, '639063675854', 'NIA: Username des123. Access NIA XII IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '69878f7d390bf', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Username des123. Access NIA XII IMS. Update your password.\"}', '2026-02-07 19:16:12'),
(15, 17, 8, '639063675854', 'NIA: Request #8 is approved. Note: goods.', 'Approved', 'PhilSMS', 'President', 'Sent', '698790f105666', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Request #8 is approved. Note: goods.\"}', '2026-02-07 19:22:24'),
(16, 17, 8, '639063675854', 'NIA: Request #8 is approved.', 'Approved', 'PhilSMS', 'President', 'Sent', '698791b6a9e9c', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Request #8 is approved.\"}', '2026-02-07 19:25:42'),
(17, 17, 8, '639063675854', 'NIA: Request #8 assigned. 2026-02-10 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '698791ce260a7', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Request #8 assigned. 2026-02-10 08:00-10:00.\"}', '2026-02-07 19:26:05'),
(18, 19, NULL, '639063675854', 'NIA: Username ftest1. Access NIA XII IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '699b9ffcf38a0', NULL, '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Username ftest1. Access NIA XII IMS. Update your password.\"}', '2026-02-23 00:31:44'),
(19, 20, NULL, '639777762166', 'NIA: Account created. Username doms123. Please login and change your password.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"recipient\":\"639777762166\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Account created. Username doms123. Please login and change your password.\"}', '2026-02-23 06:35:38'),
(20, 21, NULL, '639063675854', 'NIA: Account created. Username anoman. Please login and change your password.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"recipient\":\"639063675854\",\"sender_id\":\"PhilSMS\",\"type\":\"plain\",\"message\":\"NIA: Account created. Username anoman. Please login and change your password.\"}', '2026-02-23 06:37:42'),
(21, 22, NULL, '639063675854', 'NIA: Account created. Username desbayot. Please login and change your password.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Account created. Username desbayot. Please login and change your password.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}', '2026-02-23 07:55:29'),
(22, 23, NULL, '639063675854', 'NIA XII IMS: Your account is ready. Username: desdes. Please sign in to the portal.', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA XII IMS: Your account is ready. Username: desdes. Please sign in to the portal.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}', '2026-02-23 08:29:55'),
(23, 24, NULL, '639063675854', 'NIA: Username: desdesdes. Password: 123456. Access the website IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '699c122759a3d', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Username: desdesdes. Password: 123456. Access the website IMS. Update your password.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c122759a3d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: desdesdes. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Username: desdesdes. Password: 123456. Access the website IMS. Update your password.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c122759a3d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: desdesdes. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 08:38:51'),
(24, 24, 9, '639063675854', 'NIA: Request #9 is on process.', 'Info', 'PhilSMS', 'President', 'Sent', '699c15471db6e', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #9 is on process.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c15471db6e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is on process.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #9 is on process.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c15471db6e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is on process.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 08:52:11'),
(25, 24, 9, '639063675854', 'NIA: Request #9 is approved.', 'Approved', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Failed\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #9 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":403,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #9 is approved.\",\"ok\":false,\"status\":403,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\"}]}', '2026-02-23 08:52:19'),
(26, 24, 9, '639063675854', 'NIA: Request #9 assigned. 2026-02-23 17:00-22:00.', 'Info', 'PhilSMS', NULL, 'Sent', '699c15af8e7bb', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #9 assigned. 2026-02-23 17:00-22:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c15af8e7bb\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 assigned. 2026-02-23 17:00-22:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #9 assigned. 2026-02-23 17:00-22:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c15af8e7bb\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 assigned. 2026-02-23 17:00-22:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 08:53:55'),
(27, 24, 9, '639063675854', 'NIA: Request #9 is approved.', 'Approved', 'PhilSMS', 'President', 'Sent', '699c162711235', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #9 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c162711235\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #9 is approved.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c162711235\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 08:55:55'),
(28, 25, NULL, '639777762166', 'NIA: Username: dominicbading. Password: 123456. Access the website IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '699c18834dd9c', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Username: dominicbading. Password: 123456. Access the website IMS. Update your password.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c18834dd9c\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: dominicbading. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Username: dominicbading. Password: 123456. Access the website IMS. Update your password.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c18834dd9c\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: dominicbading. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 09:05:59'),
(29, 25, 10, '639777762166', 'NIA: Request #10 is approved.', 'Approved', 'PhilSMS', 'President', 'Sent', '699c2447a84bf', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #10 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c2447a84bf\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #10 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #10 is approved.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c2447a84bf\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #10 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 09:56:11'),
(30, 25, 10, '639777762166', 'NIA: Request #10 assigned. 2026-02-25 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '699c24534e992', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #10 assigned. 2026-02-25 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c24534e992\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #10 assigned. 2026-02-25 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #10 assigned. 2026-02-25 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c24534e992\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #10 assigned. 2026-02-25 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 09:56:23'),
(31, 3, 5, '639111111111', 'NIA: Request #5 is approved.', 'Approved', 'PhilSMS', 'Farmer', 'Sent', '699c279365c87', NULL, '{\"request_payload\":{\"recipient\":\"639111111111\",\"type\":\"plain\",\"message\":\"NIA: Request #5 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c279365c87\\\",\\\"to\\\":\\\"639111111111\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #5 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #5 is approved.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c279365c87\\\",\\\"to\\\":\\\"639111111111\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #5 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 10:10:15'),
(32, 24, 9, '639063675854', 'NIA: Request #9 is approved.', 'Approved', 'PhilSMS', 'President', 'Sent', '699c2832c8b57', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #9 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c2832c8b57\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #9 is approved.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c2832c8b57\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #9 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 10:12:54'),
(33, 26, NULL, '639462697911', 'NIA: Username: jus123123. Password: 123456. Access the website IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '699c286891bd7', NULL, '{\"request_payload\":{\"recipient\":\"639462697911\",\"type\":\"plain\",\"message\":\"NIA: Username: jus123123. Password: 123456. Access the website IMS. Update your password.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c286891bd7\\\",\\\"to\\\":\\\"639462697911\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: jus123123. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Username: jus123123. Password: 123456. Access the website IMS. Update your password.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"699c286891bd7\\\",\\\"to\\\":\\\"639462697911\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: jus123123. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-23 10:13:48'),
(34, 27, NULL, '639777762166', 'NIA: Username: dom123. Password: 123456. Access the website IMS. Update your password.', 'Info', 'PhilSMS', 'President', 'Sent', '69a0ef9632182', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Username: dom123. Password: 123456. Access the website IMS. Update your password.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0ef9632182\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: dom123. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Username: dom123. Password: 123456. Access the website IMS. Update your password.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0ef9632182\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Username: dom123. Password: 123456. Access the website IMS. Update your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 01:12:48'),
(35, 27, 11, '639777762166', 'NIA: Request #11 is on process.', 'Info', 'PhilSMS', 'President', 'Sent', '69a0f0e4a06be', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #11 is on process.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f0e4a06be\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 is on process.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #11 is on process.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f0e4a06be\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 is on process.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 01:18:23'),
(36, 27, 11, '639777762166', 'NIA: Request #11 is approved.', 'Approved', 'PhilSMS', 'President', 'Sent', '69a0f0ee61425', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #11 is approved.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f0ee61425\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #11 is approved.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f0ee61425\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 is approved.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 01:18:32'),
(37, 27, 11, '639777762166', 'NIA: Request #11 assigned. 2026-02-28 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a0f105f405f', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #11 assigned. 2026-02-28 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f105f405f\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 assigned. 2026-02-28 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #11 assigned. 2026-02-28 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f105f405f\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #11 assigned. 2026-02-28 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 01:18:56'),
(38, 27, NULL, '639777762166', 'NIA XII notice: irrigation update.', 'Info', 'PhilSMS', 'President', 'Sent', '69a0f56696e7f', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA XII notice: irrigation update.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f56696e7f\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Your membership is now inactive. Please visit the NIA office for verification and account reactivation.\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_1\",\"message\":\"NIA XII notice: irrigation update.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0f56696e7f\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 01:37:37'),
(39, 28, NULL, '639063675854', 'NIA XII notice: irrigation update.', 'Info', 'PhilSMS', 'President', 'Sent', '69a0fdd33ca1b', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA XII notice: irrigation update.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0fdd33ca1b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DES, your account has been created. Username: dessss, Password: 123456. Please log in and change your password. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_1\",\"message\":\"NIA XII notice: irrigation update.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0fdd33ca1b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:13:33'),
(40, 28, NULL, '639063675854', 'NIA XII notice: irrigation update.', 'Info', 'PhilSMS', 'President', 'Sent', '69a0fef035aca', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA XII notice: irrigation update.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0fef035aca\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DES, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_1\",\"message\":\"NIA XII notice: irrigation update.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a0fef035aca\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:18:18'),
(41, 29, NULL, '639063675854', 'Dear DES, your account has been created. Username: desx. Please contact the office for your temporary password. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear DES, your account has been created. Username: desx. Please contact the office for your temporary password. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DES, your account has been created. Username: desx, Password: 123456. Please log in and change your password. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_custom\",\"message\":\"Dear DES, your account has been created. Username: desx. Please contact the office for your temporary password. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:19:40'),
(42, 29, NULL, '639063675854', 'NIA XII notice: irrigation update.', 'Info', 'PhilSMS', 'President', 'Sent', '69a10082b3483', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA XII notice: irrigation update.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10082b3483\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DES, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_1\",\"message\":\"NIA XII notice: irrigation update.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10082b3483\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:25:01'),
(43, 30, NULL, '639063675854', 'Dear 1, your account has been created. Username: 1xxx, Password: 123456. Please log in and change your password. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear 1, your account has been created. Username: 1xxx, Password: 123456. Please log in and change your password. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear 1, your account has been created. Username: 1xxx, Password: 123456. Please log in and change your password. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:25:42'),
(44, 30, NULL, '639063675854', 'Dear 1, NIA IMS login details. Username 1xxx. Passcode 123456. Sign in then change passcode.', 'Info', 'PhilSMS', 'President', 'Sent', '69a100ae57495', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear 1, NIA IMS login details. Username 1xxx. Passcode 123456. Sign in then change passcode.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a100ae57495\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear 1, NIA IMS login details. Username 1xxx. Passcode 123456. Sign in then change passcode.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear 1, NIA IMS login details. Username 1xxx. Passcode 123456. Sign in then change passcode.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a100ae57495\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear 1, NIA IMS login details. Username 1xxx. Passcode 123456. Sign in then change passcode.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:25:44'),
(45, 31, NULL, '639063675854', 'Dear dessx, NIA IMS login details. Username: dessx, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a10153df216', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, NIA IMS login details. Username: dessx, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10153df216\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, NIA IMS login details. Username: dessx, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, NIA IMS login details. Username: dessx, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10153df216\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, NIA IMS login details. Username: dessx, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:28:30'),
(46, 31, 12, '639063675854', 'Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a1019fe322c', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1019fe322c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1019fe322c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:29:46'),
(47, 31, NULL, '639063675854', 'NIA XII notice: irrigation update.', 'Info', 'PhilSMS', 'President', 'Sent', '69a101d17292c', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA XII notice: irrigation update.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a101d17292c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"},{\"label\":\"fallback_1\",\"message\":\"NIA XII notice: irrigation update.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a101d17292c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA XII notice: irrigation update.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:30:36'),
(48, 31, 12, '639063675854', 'NIA: Request #12 assigned. 2026-03-01 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a10209e99f7', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #12 assigned. 2026-03-01 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10209e99f7\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #12 assigned. 2026-03-01 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #12 assigned. 2026-03-01 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10209e99f7\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #12 assigned. 2026-03-01 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:31:32'),
(49, 31, 12, '639063675854', 'Dear dessx, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a1021a747a0', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1021a747a0\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1021a747a0\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:31:49'),
(50, 31, 12, '639063675854', 'Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a10226d14f9', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10226d14f9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10226d14f9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:32:01'),
(51, 32, NULL, '639777762166', 'Dear Nikki S, NIA IMS login details. Username: nikkis, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a105406e0e3', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, NIA IMS login details. Username: nikkis, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105406e0e3\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, NIA IMS login details. Username: nikkis, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, NIA IMS login details. Username: nikkis, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105406e0e3\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, NIA IMS login details. Username: nikkis, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:45:15'),
(52, 32, 13, '639777762166', 'Congratulations Nikki S! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a1057eefd8b', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Congratulations Nikki S! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1057eefd8b\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Nikki S! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Nikki S! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1057eefd8b\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Nikki S! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:46:17'),
(53, 32, 13, '639777762166', 'NIA: Request #13 assigned. 2026-03-11 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a105c47e2d3', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA: Request #13 assigned. 2026-03-11 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105c47e2d3\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #13 assigned. 2026-03-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #13 assigned. 2026-03-11 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105c47e2d3\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #13 assigned. 2026-03-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:47:27');
INSERT INTO `sms_logs` (`sms_id`, `farmer_id`, `request_id`, `phone`, `message`, `sms_type`, `provider`, `recipient_role`, `status`, `provider_message_id`, `error_message`, `payload_json`, `sent_at`) VALUES
(54, 32, 13, '639777762166', 'Dear Nikki S, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a105d8eb19b', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105d8eb19b\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a105d8eb19b\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:47:47'),
(55, 32, NULL, '639777762166', 'Dear Nikki S, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:48:28'),
(56, 32, NULL, '639777762166', 'NIA IMS: Nikki S, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a106045695a', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA IMS: Nikki S, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a106045695a\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: Nikki S, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: Nikki S, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a106045695a\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: Nikki S, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:48:30'),
(57, 31, NULL, '639063675854', 'Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a106a04c80a', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a106a04c80a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a106a04c80a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:51:06'),
(58, 31, NULL, '639063675854', 'Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:53:22'),
(59, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a1072a7eab9', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1072a7eab9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1072a7eab9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:53:25'),
(60, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is ACTIVE. You may access services.', 'Info', 'PhilSMS', 'President', 'Sent', '69a107855e8a6', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is ACTIVE. You may access services.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a107855e8a6\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is ACTIVE. You may access services.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is ACTIVE. You may access services.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a107855e8a6\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is ACTIVE. You may access services.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:54:55'),
(61, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is ACTIVE. You may access services.', 'Info', 'PhilSMS', 'President', 'Sent', '69a1078a28031', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is ACTIVE. You may access services.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1078a28031\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is ACTIVE. You may access services.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is ACTIVE. You may access services.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1078a28031\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is ACTIVE. You may access services.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:55:00'),
(62, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a10791d9c7a', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10791d9c7a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a10791d9c7a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:55:08'),
(63, 31, NULL, '639063675854', 'Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a107c164b3b', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a107c164b3b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a107c164b3b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:55:56'),
(64, 31, NULL, '639063675854', 'Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:56:11'),
(65, 31, NULL, '639063675854', 'Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a1083ea28a8', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1083ea28a8\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1083ea28a8\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:58:01'),
(66, 31, NULL, '639063675854', 'Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:58:13'),
(67, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a1084dc61f3', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1084dc61f3\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1084dc61f3\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:58:16'),
(68, 31, NULL, '639063675854', 'Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 02:58:17'),
(69, 31, NULL, '639063675854', 'NIA IMS: dessx, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a108515bb6b', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a108515bb6b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a108515bb6b\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: dessx, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:58:19'),
(70, 32, 13, '639777762166', 'Dear Nikki S, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a1086f4c1e6', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1086f4c1e6\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1086f4c1e6\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 02:58:49'),
(71, 30, NULL, '639063675854', 'Dear 1, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear 1, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear 1, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-02-27 05:51:52'),
(72, 30, NULL, '639063675854', 'NIA IMS: 1, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a130ffe1c95', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: 1, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a130ffe1c95\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: 1, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: 1, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a130ffe1c95\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: 1, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 05:51:54'),
(73, 32, NULL, '639777762166', 'Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a1332cb6cff', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1332cb6cff\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1332cb6cff\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 06:01:11'),
(74, 32, NULL, '639777762166', 'Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a1332eb96e5', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1332eb96e5\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a1332eb96e5\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Nikki S, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 06:01:13'),
(75, 30, NULL, '639063675854', 'Dear 1, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a166687aa6d', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear 1, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a166687aa6d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear 1, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear 1, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a166687aa6d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear 1, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 09:39:47'),
(76, 24, 9, '639063675854', 'Congratulations Des! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a16675bb41c', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations Des! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a16675bb41c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Des! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Des! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a16675bb41c\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Des! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-02-27 09:40:00'),
(77, 31, NULL, '639063675854', 'Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a4e0da132d8', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4e0da132d8\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4e0da132d8\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 00:59:06'),
(78, 27, NULL, '639777762166', 'Dear DOM, your membership is now ACTIVE. You may now access all services. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a4e594dd8ba', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear DOM, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4e594dd8ba\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOM, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DOM, your membership is now ACTIVE. You may now access all services. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4e594dd8ba\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOM, your membership is now ACTIVE. You may now access all services. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 01:19:17'),
(79, 27, NULL, '639777762166', 'Dear DOM, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear DOM, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":404,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DOM, your account has been set to INACTIVE. Please contact the office for assistance. - NIA IMS\",\"ok\":false,\"status\":404,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Your message contains spam words.\\\"}\"}]}', '2026-03-02 02:20:22'),
(80, 27, NULL, '639777762166', 'NIA IMS: DOM, membership is INACTIVE. Please contact the office.', 'Info', 'PhilSMS', 'President', 'Sent', '69a4f3e8640c2', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"NIA IMS: DOM, membership is INACTIVE. Please contact the office.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4f3e8640c2\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: DOM, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: DOM, membership is INACTIVE. Please contact the office.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a4f3e8640c2\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: DOM, membership is INACTIVE. Please contact the office.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 02:20:24'),
(81, 31, 14, '639063675854', 'Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a5016470a1e', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5016470a1e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5016470a1e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 03:17:56'),
(82, 33, NULL, '639123123123', 'Dear test123, NIA IMS login details. Username: test123123, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a5031d96353', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"Dear test123, NIA IMS login details. Username: test123123, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5031d96353\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear test123, NIA IMS login details. Username: test123123, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear test123, NIA IMS login details. Username: test123123, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5031d96353\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear test123, NIA IMS login details. Username: test123123, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 03:25:17'),
(83, 31, 15, '639063675854', 'Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Failed\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":403,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":false,\"status\":403,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\"}]}', '2026-03-02 05:01:54'),
(84, 1, 4, '639462697122', 'Congratulations Jus Alaed! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'Farmer', 'Sent', '69a51b1d9c922', NULL, '{\"request_payload\":{\"recipient\":\"639462697122\",\"type\":\"plain\",\"message\":\"Congratulations Jus Alaed! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b1d9c922\\\",\\\"to\\\":\\\"639462697122\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Jus Alaed! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Jus Alaed! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b1d9c922\\\",\\\"to\\\":\\\"639462697122\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Jus Alaed! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:07:42'),
(85, 25, 10, '639777762166', 'Dear DOMINIC PATIDA, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a51b2c41c49', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear DOMINIC PATIDA, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b2c41c49\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOMINIC PATIDA, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DOMINIC PATIDA, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b2c41c49\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOMINIC PATIDA, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:07:56'),
(86, 25, 10, '639777762166', 'Dear DOMINIC PATIDA, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a51b31068f5', NULL, '{\"request_payload\":{\"recipient\":\"639777762166\",\"type\":\"plain\",\"message\":\"Dear DOMINIC PATIDA, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b31068f5\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOMINIC PATIDA, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear DOMINIC PATIDA, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51b31068f5\\\",\\\"to\\\":\\\"639777762166\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear DOMINIC PATIDA, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:08:01'),
(87, 31, 16, '639063675854', 'Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Failed\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":403,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations dessx! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":false,\"status\":403,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\"}]}', '2026-03-02 05:14:27'),
(88, 34, NULL, '639123123123', 'Dear done1, NIA IMS login details. Username: done1, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a51db29f866', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"Dear done1, NIA IMS login details. Username: done1, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51db29f866\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, NIA IMS login details. Username: done1, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear done1, NIA IMS login details. Username: done1, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51db29f866\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, NIA IMS login details. Username: done1, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:18:43'),
(89, 34, 17, '639123123123', 'Congratulations done1! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a51e96b9367', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"Congratulations done1! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51e96b9367\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations done1! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations done1! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51e96b9367\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations done1! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:22:31'),
(90, 34, 17, '639123123123', 'NIA: Request #17 assigned. 2026-04-09 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a51ec98d5f4', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"NIA: Request #17 assigned. 2026-04-09 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51ec98d5f4\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #17 assigned. 2026-04-09 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #17 assigned. 2026-04-09 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51ec98d5f4\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #17 assigned. 2026-04-09 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:23:22'),
(91, 34, 17, '639123123123', 'Dear done1, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a51efe71af9', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"Dear done1, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51efe71af9\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear done1, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a51efe71af9\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:24:14');
INSERT INTO `sms_logs` (`sms_id`, `farmer_id`, `request_id`, `phone`, `message`, `sms_type`, `provider`, `recipient_role`, `status`, `provider_message_id`, `error_message`, `payload_json`, `sent_at`) VALUES
(92, 24, 9, '639063675854', 'Dear Des, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a521e5773a5', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Des, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a521e5773a5\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Des, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Des, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a521e5773a5\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Des, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:36:37'),
(93, 17, 8, '639063675854', 'Dear Desiery N, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a524e3036f4', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery N, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a524e3036f4\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery N, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery N, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a524e3036f4\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery N, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:49:23'),
(94, 34, 17, '639123123123', 'Dear done1, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a5251446e2f', NULL, '{\"request_payload\":{\"recipient\":\"639123123123\",\"type\":\"plain\",\"message\":\"Dear done1, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5251446e2f\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear done1, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5251446e2f\\\",\\\"to\\\":\\\"639123123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear done1, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:50:12'),
(95, 35, NULL, '639063675854', 'Dear Desiery Natingas, NIA IMS login details. Username: desieryn, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a525cc770dd', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery Natingas, NIA IMS login details. Username: desieryn, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a525cc770dd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, NIA IMS login details. Username: desieryn, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery Natingas, NIA IMS login details. Username: desieryn, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a525cc770dd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, NIA IMS login details. Username: desieryn, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:53:16'),
(96, 35, 18, '639063675854', 'Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a525f47d961', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a525f47d961\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a525f47d961\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:53:56'),
(97, 35, 18, '639063675854', 'NIA: Request #18 assigned. 2026-04-11 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5261c7ff16', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #18 assigned. 2026-04-11 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5261c7ff16\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #18 assigned. 2026-04-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #18 assigned. 2026-04-11 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5261c7ff16\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #18 assigned. 2026-04-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:54:36'),
(98, 35, 18, '639063675854', 'Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a52629b8876', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52629b8876\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52629b8876\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:54:50'),
(99, 35, 18, '639063675854', 'Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a52640082e0', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52640082e0\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52640082e0\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:55:12'),
(100, 35, 19, '639063675854', 'Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a5271b7c26d', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5271b7c26d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5271b7c26d\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:58:52'),
(101, 35, 19, '639063675854', 'NIA: Request #19 assigned. 2026-03-02 14:00-22:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5273447f82', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #19 assigned. 2026-03-02 14:00-22:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5273447f82\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #19 assigned. 2026-03-02 14:00-22:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #19 assigned. 2026-03-02 14:00-22:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5273447f82\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #19 assigned. 2026-03-02 14:00-22:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:59:16'),
(102, 35, 19, '639063675854', 'Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Failed\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":403,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery Natingas, irrigation has started on your farm today. - NIA IMS\",\"ok\":false,\"status\":403,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\"}]}', '2026-03-02 05:59:26'),
(103, 35, 19, '639063675854', 'Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a527416721a', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a527416721a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a527416721a\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear Desiery Natingas, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 05:59:29'),
(104, 35, 20, '639063675854', 'Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a52e50868fd', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52e50868fd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52e50868fd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations Desiery Natingas! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:29:37'),
(105, 35, 19, '639063675854', 'NIA: Request #19 is declined.', 'Declined', 'PhilSMS', 'President', 'Sent', '69a52e92581cd', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #19 is declined.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52e92581cd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #19 is declined.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #19 is declined.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a52e92581cd\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #19 is declined.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:30:42'),
(106, 35, 20, '639063675854', 'NIA: Request #20 assigned. 2026-04-04 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5300ad0555', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #20 assigned. 2026-04-04 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5300ad0555\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #20 assigned. 2026-04-04 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #20 assigned. 2026-04-04 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5300ad0555\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #20 assigned. 2026-04-04 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:36:59'),
(107, NULL, 16, '639063675854', 'NIA IMS: test3, you have a pending irrigation task. Please check your website.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5309a9edb9', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\",\"sender_id\":\"PhilSMS\",\"schedule_time\":\"2026-04-06 07:50\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5309a9edb9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5309a9edb9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:39:23'),
(108, 31, 16, '639063675854', 'NIA: Request #16 assigned. 2026-04-06 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5309c9a453', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #16 assigned. 2026-04-06 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5309c9a453\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #16 assigned. 2026-04-06 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #16 assigned. 2026-04-06 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5309c9a453\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #16 assigned. 2026-04-06 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:39:25'),
(109, 31, 16, '639063675854', 'Dear dessx, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a530a241a61', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a530a241a61\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a530a241a61\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear dessx, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:39:30'),
(110, 31, 16, '639063675854', 'Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Failed', NULL, '{\"status\":\"error\",\"message\":\"Failed\"}', '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":403,\"provider_response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear dessx, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":false,\"status\":403,\"response\":\"{\\\"status\\\":\\\"error\\\",\\\"message\\\":\\\"Failed\\\"}\"}]}', '2026-03-02 06:39:33'),
(111, NULL, NULL, '639063675854', 'NIA IMS: Technician, you have a pending irrigation task. Please check your website.', 'Info', 'PhilSMS', NULL, 'Sent', '69a5315695dba', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: Technician, you have a pending irrigation task. Please check your website.\",\"sender_id\":\"PhilSMS\",\"schedule_time\":\"2026-03-02 07:44\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5315695dba\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: Technician, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: Technician, you have a pending irrigation task. Please check your website.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5315695dba\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: Technician, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:42:31'),
(112, 36, NULL, '639063675854', 'Dear demotest3, NIA IMS login details. Username: demotest3, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a5318cce7a4', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear demotest3, NIA IMS login details. Username: demotest3, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5318cce7a4\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, NIA IMS login details. Username: demotest3, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear demotest3, NIA IMS login details. Username: demotest3, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a5318cce7a4\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, NIA IMS login details. Username: demotest3, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:43:25'),
(113, 36, 21, '639063675854', 'Congratulations demotest3! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS', 'Approved', 'PhilSMS', 'President', 'Sent', '69a531aed5a33', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Congratulations demotest3! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531aed5a33\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations demotest3! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Congratulations demotest3! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531aed5a33\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Congratulations demotest3! Your application has been APPROVED. Please coordinate with the office for further instructions. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:43:59'),
(114, NULL, 21, '639063675854', 'NIA IMS: test3, you have a pending irrigation task. Please check your website.', 'Info', 'PhilSMS', NULL, 'Sent', '69a531bbe004e', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\",\"sender_id\":\"PhilSMS\",\"schedule_time\":\"2026-04-11 07:50\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531bbe004e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531bbe004e\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA IMS: test3, you have a pending irrigation task. Please check your website.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:44:12'),
(115, 36, 21, '639063675854', 'NIA: Request #21 assigned. 2026-04-11 08:00-10:00.', 'Info', 'PhilSMS', NULL, 'Sent', '69a531be0b501', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"NIA: Request #21 assigned. 2026-04-11 08:00-10:00.\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531be0b501\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #21 assigned. 2026-04-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"NIA: Request #21 assigned. 2026-04-11 08:00-10:00.\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531be0b501\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"NIA: Request #21 assigned. 2026-04-11 08:00-10:00.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:44:14'),
(116, 36, 21, '639063675854', 'Dear demotest3, irrigation has started on your farm today. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a531c50c8c9', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear demotest3, irrigation has started on your farm today. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531c50c8c9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear demotest3, irrigation has started on your farm today. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531c50c8c9\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, irrigation has started on your farm today. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:44:21'),
(117, 36, 21, '639063675854', 'Dear demotest3, irrigation has been successfully completed. Thank you. - NIA IMS', 'Info', 'PhilSMS', 'President', 'Sent', '69a531c9ac437', NULL, '{\"request_payload\":{\"recipient\":\"639063675854\",\"type\":\"plain\",\"message\":\"Dear demotest3, irrigation has been successfully completed. Thank you. - NIA IMS\",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531c9ac437\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear demotest3, irrigation has been successfully completed. Thank you. - NIA IMS\",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a531c9ac437\\\",\\\"to\\\":\\\"639063675854\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear demotest3, irrigation has been successfully completed. Thank you. - NIA IMS\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 06:44:26'),
(118, 37, NULL, '639213123123', 'Dear another, NIA IMS login details. Username: another1, Password: 123456. Please log in and change your password. ', 'Info', 'PhilSMS', 'President', 'Sent', '69a541339dacb', NULL, '{\"request_payload\":{\"recipient\":\"639213123123\",\"type\":\"plain\",\"message\":\"Dear another, NIA IMS login details. Username: another1, Password: 123456. Please log in and change your password. \",\"sender_id\":\"PhilSMS\"},\"provider_status\":200,\"provider_response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a541339dacb\\\",\\\"to\\\":\\\"639213123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear another, NIA IMS login details. Username: another1, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\",\"attempts\":[{\"label\":\"original\",\"message\":\"Dear another, NIA IMS login details. Username: another1, Password: 123456. Please log in and change your password. \",\"ok\":true,\"status\":200,\"response\":\"{\\\"status\\\":\\\"success\\\",\\\"message\\\":\\\"Your message was successfully delivered\\\",\\\"data\\\":{\\\"uid\\\":\\\"69a541339dacb\\\",\\\"to\\\":\\\"639213123123\\\",\\\"from\\\":\\\"PhilSMS\\\",\\\"message\\\":\\\"Dear another, NIA IMS login details. Username: another1, Password: 123456. Please log in and change your password.\\\",\\\"status\\\":\\\"Delivered\\\",\\\"cost\\\":\\\"1\\\",\\\"sms_count\\\":1}}\"}]}', '2026-03-02 07:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `sms_recipients`
--

CREATE TABLE `sms_recipients` (
  `recipient_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 10, 'Task Updated', 'Task #1 set to In Progress', '2026-01-15 09:13:55'),
(2, 10, 'Task Updated', 'Task #1 set to In Progress', '2026-01-15 09:13:57'),
(3, 10, 'Task Updated', 'Task #1 set to In Progress', '2026-01-15 09:13:59'),
(4, 9, 'Schedule Created', 'Created schedule #4 and auto-created task.', '2026-01-15 09:28:13'),
(5, 9, 'Task Updated', 'Task #1 set to In Progress', '2026-01-15 09:44:16'),
(6, 9, 'Task Updated', 'Task #1 set to In Progress', '2026-01-15 09:44:19'),
(7, 9, 'Request Status Updated', 'Request #1 set to Approved', '2026-01-15 12:56:17'),
(8, 9, 'Request Status Updated', 'Request #2 set to Approved', '2026-01-15 12:58:19'),
(9, 9, 'Request Status Updated', 'Request #3 set to Rejected', '2026-01-15 13:00:19'),
(10, 9, 'Request Status Updated', 'Request #3 set to Rejected', '2026-01-15 13:00:25'),
(11, 9, 'Request Status Updated', 'Request #3 set to Rejected', '2026-01-15 13:00:27'),
(12, 9, 'Task Updated', 'Task #1 set to In Progress', '2026-01-16 12:43:01'),
(13, 9, 'Task Updated', 'Task #1 set to In Progress', '2026-01-16 12:43:06'),
(14, 9, 'Task Updated', 'Task #2 set to Missed', '2026-01-16 12:43:10'),
(15, 9, 'Task Updated', 'Task #1 set to Completed', '2026-01-16 12:43:12'),
(16, 9, 'Request Status Updated', 'Request #3 set to Rejected', '2026-01-16 12:43:52'),
(17, 9, 'Request Status Updated', 'Request #3 set to Approved', '2026-01-16 12:43:57'),
(18, 9, 'User Created', 'Created user #16 (Farmer)', '2026-01-16 14:58:52'),
(19, 9, 'User Created', 'Created user #17 (Farmer)', '2026-01-16 15:00:50'),
(20, 12, 'Request Status Updated', 'Request #6 set to Approved', '2026-01-16 15:40:43'),
(21, 9, 'Schedule Created', 'Created schedule #5 and auto-created task.', '2026-01-16 15:45:18'),
(22, 9, 'Request Status Updated', 'Request #6 set to Approved', '2026-01-16 15:45:55'),
(23, 9, 'Request Status Updated', 'Request #6 set to Pending', '2026-01-16 15:46:04'),
(24, 9, 'Request Status Updated', 'Request #6 set to Rejected', '2026-01-16 15:46:07'),
(25, 9, 'Request Status Updated', 'Request #6 set to Approved', '2026-01-19 08:10:03'),
(26, 9, 'Request Stage Updated', 'Request #6 set to On Process', '2026-01-19 08:48:11'),
(27, 9, 'Request Stage Updated', 'Request #6 set to Approved', '2026-01-19 08:48:16'),
(28, 9, 'Request Stage Updated', 'Request #6 set to Approved', '2026-01-19 08:48:44'),
(29, 9, 'Request Stage Updated', 'Request #6 set to Rejected', '2026-01-19 08:48:48'),
(30, 9, 'Request Stage Updated', 'Request #6 set to Approved', '2026-01-19 08:49:16'),
(31, 9, 'Schedule Created', 'Created schedule #6 and auto-created task. Linked to request #6.', '2026-01-19 09:24:31'),
(32, 9, 'Request Stage Updated', 'Request #6 set to Approved', '2026-01-19 09:24:38'),
(33, 9, 'Schedule Created', 'Created schedule #7 and auto-created task. Linked to request #6.', '2026-01-19 09:24:54'),
(34, 13, 'Task Updated', 'Task #5 set to In Progress', '2026-01-19 09:25:17'),
(35, 13, 'Task Updated', 'Task #5 set to Completed', '2026-01-19 09:25:21'),
(36, 13, 'Task Updated', 'Task #4 set to In Progress', '2026-01-19 09:25:23'),
(37, 13, 'Task Updated', 'Task #4 set to Completed', '2026-01-19 09:25:24'),
(38, 9, 'Request Stage Updated', 'Request #7 set to Approved', '2026-01-19 09:41:10'),
(39, 9, 'User Created', 'Created user #18 (Farmer)', '2026-02-07 18:33:08'),
(40, 9, 'Request Stage Updated', 'Request #7 set to Approved', '2026-02-07 18:35:20'),
(41, 9, 'Request Stage Updated', 'Request #8 set to Approved | Note: goods', '2026-02-07 19:22:21'),
(42, 9, 'Schedule Created', 'Created schedule #8 and auto-created task. Linked to request #8.', '2026-02-07 19:23:18'),
(43, 9, 'Request Stage Updated', 'Request #8 set to Approved', '2026-02-07 19:25:39'),
(44, 9, 'Schedule Created', 'Created schedule #9 and auto-created task. Linked to request #8.', '2026-02-07 19:26:05'),
(45, 13, 'Task Updated', 'Task #7 set to In Progress', '2026-02-07 19:26:37'),
(46, 13, 'Task Updated', 'Task #7 set to Completed', '2026-02-07 19:26:39'),
(47, 9, 'Request Stage Updated', 'Request #8 set to Completed', '2026-02-07 19:27:29'),
(48, 9, 'Farmer Onboarded', 'Created farmer #20 and linked account #34.', '2026-02-23 06:35:36'),
(49, 9, 'Farmer Onboarded', 'Created farmer #21 and linked account #35.', '2026-02-23 06:37:41'),
(50, 9, 'Farmer Onboarded', 'Created farmer #22 and linked account #36.', '2026-02-23 07:55:27'),
(51, 9, 'Farmer Onboarding SMS Failed', 'Farmer #22 | Phone 639063675854 | {\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '2026-02-23 07:55:29'),
(52, 9, 'Farmer Onboarded', 'Created farmer #23 and linked account #37.', '2026-02-23 08:29:51'),
(53, 9, 'Farmer Onboarding SMS Failed', 'Farmer #23 | Phone 639063675854 | {\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '2026-02-23 08:29:55'),
(54, 9, 'Farmer Onboarded', 'Created farmer #24 and linked account #38.', '2026-02-23 08:38:48'),
(55, 9, 'Request Stage Updated', 'Request #9 set to On Process', '2026-02-23 08:52:08'),
(56, 9, 'Request Stage Updated', 'Request #9 set to Approved', '2026-02-23 08:52:16'),
(57, 9, 'Schedule Created', 'Created schedule #10 and auto-created task. Linked to request #9.', '2026-02-23 08:53:55'),
(58, 9, 'Request Stage Updated', 'Request #9 set to Assigned', '2026-02-23 08:55:49'),
(59, 9, 'Request Stage Updated', 'Request #9 set to Approved', '2026-02-23 08:55:52'),
(60, 9, 'Farmer Onboarded', 'Created farmer #25 and linked account #39.', '2026-02-23 09:05:54'),
(61, 9, 'Request Stage Updated', 'Request #10 set to Pending', '2026-02-23 09:56:04'),
(62, 9, 'Request Stage Updated', 'Request #10 set to Approved', '2026-02-23 09:56:09'),
(63, 9, 'Schedule Created', 'Created schedule #11 and auto-created task. Linked to request #10.', '2026-02-23 09:56:23'),
(64, 9, 'Request Stage Updated', 'Request #5 set to Approved', '2026-02-23 10:10:13'),
(65, 9, 'Request Stage Updated', 'Request #9 set to Approved', '2026-02-23 10:12:50'),
(66, 9, 'Farmer Onboarded', 'Created farmer #26 and linked account #40.', '2026-02-23 10:13:46'),
(67, 9, 'Request Stage Updated', 'Request #10 set to In Progress', '2026-02-23 10:19:46'),
(68, 9, 'Farmer Onboarded', 'Created farmer #27 and linked account #41.', '2026-02-27 01:12:46'),
(69, 9, 'Request Stage Updated', 'Request #11 set to On Process', '2026-02-27 01:18:21'),
(70, 9, 'Request Stage Updated', 'Request #11 set to Approved', '2026-02-27 01:18:30'),
(71, 9, 'Schedule Created', 'Created schedule #12 and auto-created task. Linked to request #11.', '2026-02-27 01:18:56'),
(72, 9, 'Task Updated', 'Task #10 set to In Progress', '2026-02-27 01:22:39'),
(73, 9, 'Task Updated', 'Task #10 set to Completed', '2026-02-27 01:22:45'),
(74, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Inactive.', '2026-02-27 01:29:12'),
(75, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Active.', '2026-02-27 01:33:55'),
(76, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Inactive.', '2026-02-27 01:34:00'),
(77, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Active.', '2026-02-27 01:34:28'),
(78, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Inactive.', '2026-02-27 01:37:37'),
(79, 9, 'Farmer Onboarded', 'Created farmer #28 and linked account #42.', '2026-02-27 02:13:29'),
(80, 9, 'Farmer Membership Updated', 'Farmer #28 (DES) membership set to Inactive.', '2026-02-27 02:18:18'),
(81, 9, 'Farmer Onboarded', 'Created farmer #29 and linked account #43.', '2026-02-27 02:19:38'),
(82, 9, 'Farmer Onboarding SMS Failed', 'Farmer #29 | Phone 639063675854 | {\"status\":\"error\",\"message\":\"Your message contains spam words.\"}', '2026-02-27 02:19:40'),
(83, 9, 'Farmer Membership Updated', 'Farmer #29 (DES) membership set to Inactive.', '2026-02-27 02:25:01'),
(84, 9, 'Farmer Onboarded', 'Created farmer #30 and linked account #44.', '2026-02-27 02:25:41'),
(85, 9, 'Farmer Onboarded', 'Created farmer #31 and linked account #45.', '2026-02-27 02:28:28'),
(86, 9, 'Request Stage Updated', 'Request #12 set to Approved', '2026-02-27 02:29:44'),
(87, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:30:36'),
(88, 9, 'Schedule Created', 'Created schedule #13 and auto-created task. Linked to request #12.', '2026-02-27 02:31:32'),
(89, 9, 'Task Updated', 'Task #11 set to In Progress', '2026-02-27 02:31:47'),
(90, 9, 'Task Updated', 'Task #11 set to Completed', '2026-02-27 02:31:59'),
(91, 9, 'Farmer Onboarded', 'Created farmer #32 and linked account #46.', '2026-02-27 02:45:12'),
(92, 9, 'Request Stage Updated', 'Request #13 set to Approved', '2026-02-27 02:46:15'),
(93, 9, 'Schedule Created', 'Created schedule #14 and auto-created task. Linked to request #13.', '2026-02-27 02:47:27'),
(94, 13, 'Task Updated', 'Task #12 set to In Progress', '2026-02-27 02:47:45'),
(95, 9, 'Farmer Membership Updated', 'Farmer #32 (Nikki S) membership set to Inactive.', '2026-02-27 02:48:30'),
(96, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-02-27 02:51:06'),
(97, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:53:25'),
(98, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-02-27 02:54:55'),
(99, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-02-27 02:55:00'),
(100, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:55:08'),
(101, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-02-27 02:55:56'),
(102, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:56:11'),
(103, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-02-27 02:58:01'),
(104, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:58:16'),
(105, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Inactive.', '2026-02-27 02:58:20'),
(106, 9, 'Task Updated', 'Task #12 set to Completed', '2026-02-27 02:58:48'),
(107, 9, 'Request Stage Updated', 'Request #12 set to Assigned', '2026-02-27 02:59:14'),
(108, 9, 'Task Log Updated', 'Task #12 saved via Task Logging | Status: Completed -> Completed | Start: 2026-02-27T10:47 | End: 2026-02-27T10:58 | Time fields updated', '2026-02-27 03:25:26'),
(109, 9, 'Farmer Membership Updated', 'Farmer #30 (1) membership set to Inactive.', '2026-02-27 05:51:54'),
(110, 9, 'Farmer Membership Updated', 'Farmer #32 (Nikki S) membership set to Active.', '2026-02-27 06:01:11'),
(111, 9, 'Farmer Membership Updated', 'Farmer #32 (Nikki S) membership set to Active.', '2026-02-27 06:01:13'),
(112, 9, 'User Created', 'Created user #47 (Irrigation Technician)', '2026-02-27 06:27:33'),
(113, 9, 'Request Stage Updated', 'Request #13 set to Assigned', '2026-02-27 06:47:11'),
(114, 9, 'Request Stage Updated', 'Request #13 set to In Progress', '2026-02-27 06:50:27'),
(115, 9, 'Request Stage Updated', 'Request #13 set to Completed', '2026-02-27 06:50:39'),
(116, 9, 'Farmer Membership Updated', 'Farmer #30 (1) membership set to Active.', '2026-02-27 09:39:47'),
(117, 9, 'Request Stage Updated', 'Request #9 set to Approved', '2026-02-27 09:39:55'),
(118, 9, 'Farmer Updated', 'Updated farmer #32 (12) | Membership: Active', '2026-02-27 09:44:51'),
(119, 9, 'Farmer Updated', 'Updated farmer #32 (12) | Membership: Active', '2026-02-27 09:45:02'),
(120, 9, 'Form Reprint', 'Reprinted NIA-2026-00005 (NIA Farmer Registration Form) for Desiery N', '2026-03-02 00:56:21'),
(121, 9, 'Form Issued', 'Issued NIA-2026-00010 (NIA Farmer Registration Form) to Jus Alaed', '2026-03-02 00:56:49'),
(122, 9, 'Farmer Membership Updated', 'Farmer #31 (dessx) membership set to Active.', '2026-03-02 00:59:06'),
(123, 9, 'Form Issued', 'Issued NIA-2026-00011 (NIA Farmer Registration Form) to DOMINIC PATIDA', '2026-03-02 01:00:00'),
(124, 9, 'Form Issued', 'Issued NIA-2026-00012 (NIA Farmer Registration Form) to DOMINIC PATIDA', '2026-03-02 01:08:16'),
(125, 9, 'Form Issued', 'Issued NIA-2026-00013 (NIA Farmer Registration Form) to DOMINIC PATIDA', '2026-03-02 01:12:41'),
(126, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Active.', '2026-03-02 01:19:17'),
(127, 9, 'Form Issued', 'Issued NIA-2026-00014 (NIA Farmer Registration Form) to DOMINIC PATIDA', '2026-03-02 02:00:39'),
(128, 9, 'Farmer Membership Updated', 'Farmer #27 (DOM) membership set to Inactive.', '2026-03-02 02:20:24'),
(129, 9, 'Request Stage Updated', 'Request #14 set to Approved', '2026-03-02 03:17:54'),
(130, 9, 'Farmer Onboarded', 'Created farmer #33 and linked account #48.', '2026-03-02 03:25:15'),
(131, 9, 'Request Stage Updated', 'Request #15 set to Approved', '2026-03-02 05:01:52'),
(132, 9, 'Request Stage Updated', 'Request #4 set to Approved', '2026-03-02 05:07:39'),
(133, 9, 'Task Updated', 'Task #9 set to In Progress', '2026-03-02 05:07:53'),
(134, 9, 'Task Updated', 'Task #9 set to Completed', '2026-03-02 05:07:59'),
(135, 9, 'Request Stage Updated', 'Request #16 set to Approved', '2026-03-02 05:14:24'),
(136, 9, 'Farmer Onboarded', 'Created farmer #34 and linked account #49.', '2026-03-02 05:18:39'),
(137, 9, 'Request Stage Updated', 'Request #17 set to Approved', '2026-03-02 05:22:28'),
(138, 9, 'Schedule Created', 'Created schedule #15 and auto-created task. Linked to request #17.', '2026-03-02 05:23:22'),
(139, 9, 'Request Stage Updated', 'Request #17 set to Pending', '2026-03-02 05:23:34'),
(140, 9, 'Request Stage Updated', 'Request #17 set to Assigned', '2026-03-02 05:23:41'),
(141, 9, 'Task Updated', 'Task #13 set to In Progress', '2026-03-02 05:24:12'),
(142, 9, 'Task Updated', 'Task #8 set to In Progress', '2026-03-02 05:36:35'),
(143, 9, 'Task Updated', 'Task #6 set to In Progress', '2026-03-02 05:49:21'),
(144, 9, 'Task Updated', 'Task #13 set to Completed', '2026-03-02 05:50:08'),
(145, 9, 'Farmer Onboarded', 'Created farmer #35 and linked account #50.', '2026-03-02 05:53:14'),
(146, 9, 'Request Stage Updated', 'Request #18 set to Approved', '2026-03-02 05:53:55'),
(147, 9, 'Schedule Created', 'Created schedule #16 and auto-created task. Linked to request #18.', '2026-03-02 05:54:36'),
(148, 9, 'Task Updated', 'Task #14 set to In Progress', '2026-03-02 05:54:47'),
(149, 9, 'Task Updated', 'Task #14 set to Completed', '2026-03-02 05:55:08'),
(150, 9, 'Request Stage Updated', 'Request #19 set to Approved', '2026-03-02 05:58:50'),
(151, 9, 'Schedule Created', 'Created schedule #17 and auto-created task. Linked to request #19.', '2026-03-02 05:59:16'),
(152, 9, 'Task Updated', 'Task #15 set to In Progress', '2026-03-02 05:59:23'),
(153, 9, 'Task Updated', 'Task #15 set to Completed', '2026-03-02 05:59:27'),
(154, 9, 'User Status Updated', 'User #13 set to Inactive', '2026-03-02 06:26:26'),
(155, 9, 'User Status Updated', 'User #13 set to Active', '2026-03-02 06:26:46'),
(156, 9, 'User Updated', 'Updated user #13 (Irrigation Technician)', '2026-03-02 06:28:30'),
(157, 9, 'Request Stage Updated', 'Request #20 set to Approved', '2026-03-02 06:29:34'),
(158, 9, 'Request Stage Updated', 'Request #19 set to Rejected', '2026-03-02 06:30:40'),
(159, 9, 'Schedule Created', 'Created schedule #18 and auto-created task. Linked to request #20.', '2026-03-02 06:36:59'),
(160, 9, 'Request Stage Updated', 'Request #20 set to In Progress', '2026-03-02 06:38:47'),
(161, 9, 'Request Stage Updated', 'Request #20 set to Completed', '2026-03-02 06:38:53'),
(162, 9, 'Schedule Created', 'Created schedule #19 and auto-created task. Linked to request #16.', '2026-03-02 06:39:25'),
(163, 9, 'Task Updated', 'Task #17 set to In Progress', '2026-03-02 06:39:28'),
(164, 9, 'Task Updated', 'Task #17 set to Completed', '2026-03-02 06:39:31'),
(165, 9, 'Farmer Onboarded', 'Created farmer #36 and linked account #51.', '2026-03-02 06:43:23'),
(166, 9, 'Request Stage Updated', 'Request #21 set to Approved', '2026-03-02 06:43:57'),
(167, 9, 'Schedule Created', 'Created schedule #20 and auto-created task. Linked to request #21.', '2026-03-02 06:44:14'),
(168, 9, 'Task Updated', 'Task #18 set to In Progress', '2026-03-02 06:44:18'),
(169, 9, 'Task Updated', 'Task #18 set to Completed', '2026-03-02 06:44:24'),
(170, 9, 'Farmer Onboarded', 'Created farmer #37 and linked account #52.', '2026-03-02 07:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `status` enum('Due','In Progress','Completed','Missed') NOT NULL DEFAULT 'Due',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `schedule_id`, `assigned_user_id`, `status`, `started_at`, `ended_at`, `remarks`, `issues`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 'Completed', '2026-01-15 17:13:55', '2026-01-16 20:43:12', NULL, NULL, '2026-01-15 09:13:50', '2026-01-16 12:43:12'),
(2, 4, NULL, 'Missed', '2026-01-16 20:43:10', '2026-01-16 20:43:10', NULL, NULL, '2026-01-15 09:28:13', '2026-01-16 12:43:10'),
(3, 5, NULL, 'Due', NULL, NULL, NULL, NULL, '2026-01-16 15:45:18', '2026-01-16 15:45:18'),
(4, 6, 13, 'Completed', '2026-01-19 17:25:23', '2026-01-19 17:25:24', NULL, NULL, '2026-01-19 09:24:31', '2026-01-19 09:25:24'),
(5, 7, 13, 'Completed', '2026-01-19 17:25:17', '2026-01-19 17:25:21', NULL, NULL, '2026-01-19 09:24:54', '2026-01-19 09:25:21'),
(6, 8, 13, 'In Progress', '2026-03-02 13:49:20', NULL, NULL, NULL, '2026-02-07 19:23:18', '2026-03-02 05:49:20'),
(7, 9, 13, 'Completed', '2026-02-08 03:26:37', '2026-02-08 03:26:39', NULL, NULL, '2026-02-07 19:26:01', '2026-02-07 19:26:39'),
(8, 10, 13, 'In Progress', '2026-03-02 13:36:35', NULL, NULL, NULL, '2026-02-23 08:53:51', '2026-03-02 05:36:35'),
(9, 11, 13, 'Completed', '2026-03-02 13:07:53', '2026-03-02 13:07:58', NULL, NULL, '2026-02-23 09:56:21', '2026-03-02 05:07:58'),
(10, 12, 13, 'Completed', '2026-02-27 09:22:39', '2026-02-27 09:22:45', NULL, NULL, '2026-02-27 01:18:54', '2026-02-27 01:22:45'),
(11, 13, 13, 'Completed', '2026-02-27 10:31:00', '2026-02-27 10:31:00', '', '', '2026-02-27 02:31:30', '2026-02-27 02:59:28'),
(12, 14, 13, 'Completed', '2026-02-27 10:47:00', '2026-02-27 10:58:00', '', '', '2026-02-27 02:47:24', '2026-02-27 03:25:26'),
(13, 15, 47, 'Completed', '2026-03-02 13:24:12', '2026-03-02 13:50:08', NULL, NULL, '2026-03-02 05:23:19', '2026-03-02 05:50:08'),
(14, 16, 47, 'Completed', '2026-03-02 13:54:47', '2026-03-02 13:55:08', NULL, NULL, '2026-03-02 05:54:33', '2026-03-02 05:55:08'),
(15, 17, 47, 'Completed', '2026-03-02 13:59:23', '2026-03-02 13:59:27', NULL, NULL, '2026-03-02 05:59:14', '2026-03-02 05:59:27'),
(16, 18, 13, 'Due', NULL, NULL, NULL, NULL, '2026-03-02 06:36:57', '2026-03-02 06:36:57'),
(17, 19, 13, 'Completed', '2026-03-02 14:39:28', '2026-03-02 14:39:31', NULL, NULL, '2026-03-02 06:39:20', '2026-03-02 06:39:31'),
(18, 20, 13, 'Completed', '2026-03-02 14:44:18', '2026-03-02 14:44:24', NULL, NULL, '2026-03-02 06:44:08', '2026-03-02 06:44:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_change_required` tinyint(4) NOT NULL DEFAULT 0,
  `role` enum('Administrator','Operations Staff','Irrigation Technician','IMO','Monitoring','Farmer','SWRFT','WRFO Gatekeeper','WRFO Scheduler') NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `username`, `password`, `password_change_required`, `role`, `phone`, `email`, `is_active`, `created_at`) VALUES
(1, 'Admin User', 'admin', '$2y$10$2r0I7dXH7QHQtV0YULrFBOx7LdxPMiGdb9KjA0jHfI4i7TfZ2N8yS', 0, '', '09170000001', 'admin@example.com', 1, '2025-11-28 10:28:18'),
(2, 'Staff One', 'staff1', '$2y$10$yw3c1V9dUhvf1y2qx4t8O.Ez8HPiLgJmMGFQ.FPbY6Aq3hGfmr2jS', 0, '', '09170000002', 'staff1@example.com', 1, '2025-11-28 10:28:18'),
(3, 'Farmer One', 'farmer1', '$2y$10$F1pRjU3zE6kMZpHJG76Ju.MVfQj9hR1pXKiYvj.4SyG0xQcmT0aQO', 0, '', '09170000003', 'farmer1@example.com', 1, '2025-11-28 10:28:18'),
(4, 'Test User', 'test1', '$2y$10$eD0eI1G3F2pP5mQbP9d1vO7QxYt4a6d8k3u6m0GQ2XfLw9Qn1r1sK', 0, 'Operations Staff', '09170000004', 'test1@example.com', 1, '2026-01-15 02:44:06'),
(5, 'admin', 'test', 'test12345', 0, 'Administrator', '09462691122', 'dealajus@gmail.com', 1, '2026-01-15 02:46:14'),
(6, 'Test User', 'testt', '$2y$10$ZLFbwNBGUTp0TPLM1agkpurI7TOI3I0UclQ6DRx9/lQXytkaRdvMK', 0, 'Operations Staff', '09170000004', 'test1@example.com', 1, '2026-01-15 02:50:51'),
(8, 'Admin', 'admin1', '$2y$10$mUZQ2qc2KfWUpejOOmIjN.gLnyPukgp1IiHmr7nuCw3pBqREoXqma', 0, 'Administrator', '09170000004', 'test1@example.com', 1, '2026-01-15 03:15:29'),
(9, 'Administrator', 'admintest', '$2y$10$ONCNzUMHkyx/CTGwKTYyHuBNXuwp7uFzZWg98fOzz93zuw00CCdsS', 0, 'Administrator', '09170000004', 'test1@example.com', 1, '2026-01-15 04:33:03'),
(10, 'Test User 1', 'test2', '$2y$10$ugLWgrCUs6dvSbLfiO1N7ukEh/6QP.JobIKgUZN0iwAVGRHX.ffTG', 0, 'Operations Staff', '09170000009', 'test1@example.com', 1, '2026-01-15 06:31:35'),
(11, 'Jus Alaed', 'farmertest', '$2y$10$rIAN0HhbA6VMBVTnpkqyW.cNitvPhSv9vG3QKwPNknZVC5Lzy573i', 0, 'Farmer', '09462697122', NULL, 1, '2026-01-15 11:43:16'),
(12, 'Staff One', 'stafftest', '$2y$10$//0bbYdRHGW9gh6JDwV3eOjlEZGS8QquHlW/1/jX6QTseo2/wsw1e', 0, 'Operations Staff', '09170000002', 'staff1@example.com', 1, '2026-01-16 12:48:11'),
(13, 'test3', 'techtest', '$2y$10$//0bbYdRHGW9gh6JDwV3eOjlEZGS8QquHlW/1/jX6QTseo2/wsw1e', 0, 'Irrigation Technician', '09063675854', 'techtest@gmail.com', 1, '2026-01-16 12:51:55'),
(14, 'test', 'farmertest1', '$2y$10$zV.UOdnhqbMrSMbT0PzA.eBwC8NuqonP6tj7CCQWE7n1hgm6DxE1y', 0, 'Farmer', '09462697122', 'test@gmail.com', 1, '2026-01-16 14:36:40'),
(15, 'test3', 'farmertest2', '$2y$10$lYtEcOZWJMy2vDlILT/cf.yLdrsn26SRwTogiZXAa0dxM.GQRQ/Ou', 0, 'Farmer', '09462697111', 'test@gmail.com', 1, '2026-01-16 14:52:53'),
(16, 'test3', 'farmertest3', '$2y$10$vA33bac7GeGg6y973IjL9uuW2fn4mihTzgGq04vl7bNbXE6vm4j7m', 0, 'Farmer', '09352626931', 'test@gmail.com', 1, '2026-01-16 14:58:52'),
(17, 'test5', 'farmertest4', '$2y$10$sl8CNXaRdadhUvQFpME/7e42bdRaS6vyOQyc1W9ztOwPZF.fYOuMu', 0, 'Farmer', '09111111111', 'test@gmail.com', 1, '2026-01-16 15:00:50'),
(18, 'Alaed Jus', 'farmertest01', '$2y$10$03UDrAb9DG7AJEdeuir7fuBJvgkRuuVjIKxkJBAAftATHMcbfTFk2', 0, 'Farmer', '09063675854', 'alaedjus@gmail.com', 1, '2026-02-07 18:33:08'),
(19, 'Des', 'farmertest02', '$2y$10$HO2Z8FVFor8xgHWs.ByhpuXPGmoSwOMMB.QefJvzBcOXqyYLvRmW2', 1, 'Farmer', '09063675854', NULL, 1, '2026-02-07 18:43:37'),
(20, 'test', 'testfarmer04', '$2y$10$5yombO96NW0QH5mYs/ZoNuHbwuybkN6P73DsubI6d6Rzv1BnU89Xu', 1, 'Farmer', '09063675854', NULL, 1, '2026-02-07 18:46:54'),
(21, 'test', '123456', '$2y$10$ysoibjRGd4KIr/FzajfyNuR.OCWom23sgKrQyV7EG3VrEX9qzqp.a', 1, 'Farmer', '630063675854', NULL, 1, '2026-02-07 18:47:34'),
(22, 'test', '1111', '$2y$10$rCqu2DREj1ppfTeqePEuO.At9GilSGIDy2WLMy1ZOuImGxAHECeYS', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-07 18:48:19'),
(23, 'test', '1234512345', '$2y$10$unYFf7qY7vDbKL.899FwHOjhcVedD03q4aMBHGRJNDc002q4L2YUq', 1, 'Farmer', '09063675854', NULL, 1, '2026-02-07 18:51:10'),
(24, 'test', 'ssssssss', '$2y$10$8yr2c6mvUtB8TAWSbjHOZutG0aeOBO6Zq7.rf0GcK69X6d1dICWmG', 1, 'Farmer', '9063675854', NULL, 1, '2026-02-07 18:52:56'),
(25, '1', '11', '$2y$10$GTfaUy9soNU.HxQ1TQ.6wOsX3pqmz5NpNjohjPO4xydB0DYpmSTd6', 1, 'Farmer', '9063675854', NULL, 1, '2026-02-07 18:57:45'),
(26, 'des nat', '111111', '$2y$10$oeYQB3EAPUAFJ2MZIs15a.gNi3cLOY3mHlTKLGeuPrhc.RlAJ1Yfe', 1, 'Farmer', '0063675854', NULL, 1, '2026-02-07 19:04:32'),
(27, 'testsss', 'wat', '$2y$10$pEV8IEqMOKHxl/8MJinvlORO6e2G2hftgmAyTWnxnx500OqIa8XoW', 1, 'Farmer', '0063675854', NULL, 1, '2026-02-07 19:05:37'),
(28, 'sdas', '12312321', '$2y$10$BFVMxyNGpt52CreKZjn.RuFVPEhEj26v6KE3NsiiV0ibcLTZ7/9E6', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-07 19:07:49'),
(29, 'sdasd', '12345321', '$2y$10$TTUIT35xpeC2w8lpJpHuyueqtkwSyq6T0l9xh4vj4/AVunQLkOL.a', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-07 19:11:11'),
(30, '213123', '2312321', '$2y$10$MZyOoZFis8HEBwQVWMM1zOEGCsQXKS5Cdn6lvUajNCFGoET1uZig2', 0, 'Farmer', '639063675854', NULL, 1, '2026-02-07 19:13:51'),
(31, 'Desiery N', 'des123', '$2y$10$ayW.MC67voaXlzL3Wb6Jx.bOCGzzROal9LVO8s9RrsAtAlttsV81.', 0, 'Farmer', '639063675854', NULL, 1, '2026-02-07 19:16:09'),
(33, 'Des', 'ftest1', '$2y$10$HohMUbKrcYmxekAqF28I2OM/NkxzJ3KVQdakatkroD9Vqdr0x0tRa', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-23 00:31:42'),
(34, 'DES', 'doms123', '$2y$10$mekBxFbg88.i1wtql28Vne2ENvnjYCYV1ZnHoqdRzf6/VDq6u1Bye', 1, 'Farmer', '639777762166', NULL, 1, '2026-02-23 06:35:36'),
(35, 'test1', 'anoman', '$2y$10$uLRHqTCVuzTHwPfJolg9n.381cujOP2x8xsNNbA1oCZ44AGlmMwtK', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-23 06:37:41'),
(36, 'des', 'desbayot', '$2y$10$YOFhpnsVsiVqg7t01s9cNu.L2rvlaw9IgMlmiLKGiIpWMMqLXsi22', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-23 07:55:27'),
(37, 'Des', 'desdes', '$2y$10$7cvSnIhT4WWxb7K1sUXyn.fqwmjIsQb4RBUfRkk.TgeAdElQLQx4u', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-23 08:29:51'),
(38, 'Des', 'desdesdes', '$2y$10$jJ5jwDPMYVjyzPd4fM7PmOTiNW5d6NQO9LGxwFZHtIhYnOZVcEU22', 0, 'Farmer', '639063675854', NULL, 1, '2026-02-23 08:38:48'),
(39, 'DOMINIC PATIDA', 'dominicbading', '$2y$10$/7mDhsUulEEbJtVvnQ/1seZjuTd3Rk9v.TPENNvWyFpTawjUvVxay', 0, 'Farmer', '639777762166', NULL, 1, '2026-02-23 09:05:54'),
(40, 'JUS', 'jus123123', '$2y$10$jXZlW06XnamHJ5o06ClWZeamyEQNHCxdUR2.Hr1/LztqcXtEMSrhC', 0, 'Farmer', '639462697911', NULL, 1, '2026-02-23 10:13:46'),
(41, 'DOM', 'dom123', '$2y$10$VR6HffcK92rctoCcay5VyuSGu0yiRiwqMbCzbGl8iNZTdpuPwGjXa', 0, 'Farmer', '639777762166', NULL, 0, '2026-02-27 01:12:46'),
(42, 'DES', 'dessss', '$2y$10$5zPeAV8Ni17A7H5iAPnJSO5CkPXHENjd/lMX96FXegdYUuWsieuaq', 1, 'Farmer', '639063675854', NULL, 0, '2026-02-27 02:13:29'),
(43, 'DES', 'desx', '$2y$10$ucFXJLYVzYLlIdc48NZR8uTpT5KIzo9Xjagldz4wvL7ResT37Ynw.', 1, 'Farmer', '639063675854', NULL, 0, '2026-02-27 02:19:38'),
(44, '1', '1xxx', '$2y$10$l6aLtBQb4c8hcgb7sYKu2.Sd0qlUV9S3fDFpxRbc/8qDEFzgJOQzW', 1, 'Farmer', '639063675854', NULL, 1, '2026-02-27 02:25:41'),
(45, 'dessx', 'dessx', '$2y$10$lWlaScHWvf9Potj7JSAXiutLs03QaveCryHMp7SmpkyL4CVriIlXS', 0, 'Farmer', '639063675854', NULL, 1, '2026-02-27 02:28:28'),
(46, '12', 'nikkis', '$2y$10$yt5xDRnQQu0ubaAZlfYnx.eTMLBCU5DU2GhaCY0hye/oNg.UseIHq', 0, 'Farmer', '639777762166', NULL, 1, '2026-02-27 02:45:12'),
(47, 'technian', 'technian1', '$2y$10$bYUxD1.9KArnwS6A/Vba.ODJI/kKVntfLj7IulfPn.9sjGufwyupy', 0, 'Irrigation Technician', '', '', 1, '2026-02-27 06:27:33'),
(48, 'test123', 'test123123', '$2y$10$Wif8ss2Y31e/.HRnTXOlIunBZ4m1gI3huEkK8qNXqod3iQN0U9cL2', 0, 'Farmer', '639123123123', NULL, 1, '2026-03-02 03:25:15'),
(49, 'done1', 'done1', '$2y$10$sKrtZBr7yNfdmyZzGYX1E.q0SIKK7m2ydD1j3Zq5kBsi9h6vstUwq', 0, 'Farmer', '639123123123', NULL, 1, '2026-03-02 05:18:39'),
(50, 'Desiery Natingas', 'desieryn', '$2y$10$i82Ff.ZcIOL2X6isp1XUHuPKCIOMLliF7fbFyIC0fgvvilLe4VvgG', 0, 'Farmer', '639063675854', NULL, 1, '2026-03-02 05:53:14'),
(51, 'demotest3', 'demotest3', '$2y$10$UorcA7XVPstxwwCKJMnDsO4a8wDNAfWeZ6I4YyS2y4k.9P8cPhl3W', 0, 'Farmer', '639063675854', NULL, 1, '2026-03-02 06:43:23'),
(52, 'another', 'another1', '$2y$10$77JRNius4rn9/xq4.5gmHektarQGBoFoaumN3QnwVHwzYnGhBG9K6', 0, 'Farmer', '639213123123', NULL, 1, '2026-03-02 07:50:08');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_today_active_irrigations`
-- (See below for the actual view)
--
CREATE TABLE `v_today_active_irrigations` (
`schedule_id` int(11)
,`schedule_date` date
,`start_time` time
,`end_time` time
,`status` enum('Active','Completed','Cancelled')
,`service_area_id` int(11)
,`area_name` varchar(150)
,`municipality` varchar(100)
,`province` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_upcoming_schedules`
-- (See below for the actual view)
--
CREATE TABLE `v_upcoming_schedules` (
`schedule_id` int(11)
,`schedule_date` date
,`start_time` time
,`end_time` time
,`status` enum('Active','Completed','Cancelled')
,`service_area_id` int(11)
,`area_name` varchar(150)
,`municipality` varchar(100)
,`province` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `water_allocations`
--

CREATE TABLE `water_allocations` (
  `allocation_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `allocated_liters` decimal(12,2) DEFAULT NULL,
  `season` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `water_release_logs`
--

CREATE TABLE `water_release_logs` (
  `log_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `released_by` int(11) DEFAULT NULL,
  `released_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_today_active_irrigations`
--
DROP TABLE IF EXISTS `v_today_active_irrigations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_today_active_irrigations`  AS SELECT `s`.`schedule_id` AS `schedule_id`, `s`.`schedule_date` AS `schedule_date`, `s`.`start_time` AS `start_time`, `s`.`end_time` AS `end_time`, `s`.`status` AS `status`, `s`.`service_area_id` AS `service_area_id`, `sa`.`area_name` AS `area_name`, `sa`.`municipality` AS `municipality`, `sa`.`province` AS `province` FROM (`irrigation_schedules` `s` join `service_areas` `sa` on(`sa`.`service_area_id` = `s`.`service_area_id`)) WHERE `s`.`schedule_date` = curdate() AND `s`.`status` = 'Active' AND curtime() between `s`.`start_time` and `s`.`end_time` ;

-- --------------------------------------------------------

--
-- Structure for view `v_upcoming_schedules`
--
DROP TABLE IF EXISTS `v_upcoming_schedules`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_upcoming_schedules`  AS SELECT `s`.`schedule_id` AS `schedule_id`, `s`.`schedule_date` AS `schedule_date`, `s`.`start_time` AS `start_time`, `s`.`end_time` AS `end_time`, `s`.`status` AS `status`, `s`.`service_area_id` AS `service_area_id`, `sa`.`area_name` AS `area_name`, `sa`.`municipality` AS `municipality`, `sa`.`province` AS `province` FROM (`irrigation_schedules` `s` join `service_areas` `sa` on(`sa`.`service_area_id` = `s`.`service_area_id`)) WHERE `s`.`schedule_date` >= curdate() ORDER BY `s`.`schedule_date` ASC, `s`.`start_time` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `idx_alerts_status` (`status`),
  ADD KEY `idx_alerts_severity` (`severity`),
  ADD KEY `idx_alerts_service_area` (`service_area_id`),
  ADD KEY `idx_alerts_schedule` (`schedule_id`),
  ADD KEY `idx_alerts_created_at` (`created_at`),
  ADD KEY `fk_alerts_ack_user` (`acknowledged_by`),
  ADD KEY `fk_alerts_res_user` (`resolved_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `canals`
--
ALTER TABLE `canals`
  ADD PRIMARY KEY (`canal_id`);

--
-- Indexes for table `credential_prints`
--
ALTER TABLE `credential_prints`
  ADD PRIMARY KEY (`print_id`),
  ADD KEY `fk_print_farmer` (`farmer_id`),
  ADD KEY `fk_print_user` (`printed_by`);

--
-- Indexes for table `drainages`
--
ALTER TABLE `drainages`
  ADD PRIMARY KEY (`drainage_id`);

--
-- Indexes for table `farmers`
--
ALTER TABLE `farmers`
  ADD PRIMARY KEY (`farmer_id`),
  ADD UNIQUE KEY `uniq_farmers_user_id` (`user_id`),
  ADD KEY `fk_farmer_service_area` (`service_area_id`),
  ADD KEY `idx_farmers_user_id` (`user_id`),
  ADD KEY `fk_farmer_created_by` (`created_by`);

--
-- Indexes for table `farmer_lots`
--
ALTER TABLE `farmer_lots`
  ADD PRIMARY KEY (`lot_id`),
  ADD KEY `fk_lot_farmer` (`farmer_id`),
  ADD KEY `fk_lot_canal` (`canal_id`),
  ADD KEY `fk_lot_drainage` (`drainage_id`);

--
-- Indexes for table `farmer_requests`
--
ALTER TABLE `farmer_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `fk_request_technician` (`assigned_technician_id`),
  ADD KEY `fk_request_area` (`service_area_id`),
  ADD KEY `fk_request_lot` (`lot_id`),
  ADD KEY `fk_request_canal` (`canal_id`),
  ADD KEY `fk_request_drainage` (`drainage_id`),
  ADD KEY `fk_request_requested_by` (`requested_by_user_id`),
  ADD KEY `fk_request_collected_by` (`collected_by`);

--
-- Indexes for table `form_templates`
--
ALTER TABLE `form_templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `form_template_fields`
--
ALTER TABLE `form_template_fields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `fk_ftf_template` (`template_id`);

--
-- Indexes for table `irrigation_batches`
--
ALTER TABLE `irrigation_batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `irrigation_schedules`
--
ALTER TABLE `irrigation_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `service_area_id` (`service_area_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_sched_request` (`request_id`);

--
-- Indexes for table `paper_forms`
--
ALTER TABLE `paper_forms`
  ADD PRIMARY KEY (`form_id`),
  ADD KEY `fk_form_template` (`template_id`),
  ADD KEY `fk_form_farmer` (`issued_to_farmer_id`),
  ADD KEY `fk_form_issued_by` (`issued_by`),
  ADD KEY `fk_form_encoded_by` (`encoded_by`);

--
-- Indexes for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD PRIMARY KEY (`export_id`),
  ADD KEY `idx_report_exports_report_name` (`report_name`),
  ADD KEY `idx_report_exports_created_at` (`created_at`),
  ADD KEY `idx_report_exports_exported_by` (`exported_by`);

--
-- Indexes for table `request_approvals`
--
ALTER TABLE `request_approvals`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `fk_approval_request` (`request_id`),
  ADD KEY `fk_approval_user` (`decided_by`);

--
-- Indexes for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `fk_attach_request` (`request_id`);

--
-- Indexes for table `service_areas`
--
ALTER TABLE `service_areas`
  ADD PRIMARY KEY (`service_area_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`sms_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `fk_sms_request` (`request_id`);

--
-- Indexes for table `sms_recipients`
--
ALTER TABLE `sms_recipients`
  ADD PRIMARY KEY (`recipient_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD UNIQUE KEY `uniq_schedule_task` (`schedule_id`),
  ADD KEY `idx_tasks_schedule` (`schedule_id`),
  ADD KEY `idx_tasks_assigned_user` (`assigned_user_id`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_started_at` (`started_at`),
  ADD KEY `idx_tasks_ended_at` (`ended_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `water_allocations`
--
ALTER TABLE `water_allocations`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `service_area_id` (`service_area_id`);

--
-- Indexes for table `water_release_logs`
--
ALTER TABLE `water_release_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `service_area_id` (`service_area_id`),
  ADD KEY `released_by` (`released_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `canals`
--
ALTER TABLE `canals`
  MODIFY `canal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credential_prints`
--
ALTER TABLE `credential_prints`
  MODIFY `print_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drainages`
--
ALTER TABLE `drainages`
  MODIFY `drainage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmers`
--
ALTER TABLE `farmers`
  MODIFY `farmer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `farmer_lots`
--
ALTER TABLE `farmer_lots`
  MODIFY `lot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `farmer_requests`
--
ALTER TABLE `farmer_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `form_templates`
--
ALTER TABLE `form_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `form_template_fields`
--
ALTER TABLE `form_template_fields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `irrigation_batches`
--
ALTER TABLE `irrigation_batches`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `irrigation_schedules`
--
ALTER TABLE `irrigation_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `paper_forms`
--
ALTER TABLE `paper_forms`
  MODIFY `form_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `report_exports`
--
ALTER TABLE `report_exports`
  MODIFY `export_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_approvals`
--
ALTER TABLE `request_approvals`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_areas`
--
ALTER TABLE `service_areas`
  MODIFY `service_area_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `sms_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `sms_recipients`
--
ALTER TABLE `sms_recipients`
  MODIFY `recipient_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `water_allocations`
--
ALTER TABLE `water_allocations`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `water_release_logs`
--
ALTER TABLE `water_release_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `fk_alerts_ack_user` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alerts_res_user` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alerts_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `irrigation_schedules` (`schedule_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alerts_service_area` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`) ON UPDATE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `credential_prints`
--
ALTER TABLE `credential_prints`
  ADD CONSTRAINT `fk_print_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_print_user` FOREIGN KEY (`printed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `farmers`
--
ALTER TABLE `farmers`
  ADD CONSTRAINT `fk_farmer_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_farmer_service_area` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`),
  ADD CONSTRAINT `fk_farmers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `farmer_lots`
--
ALTER TABLE `farmer_lots`
  ADD CONSTRAINT `fk_lot_canal` FOREIGN KEY (`canal_id`) REFERENCES `canals` (`canal_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lot_drainage` FOREIGN KEY (`drainage_id`) REFERENCES `drainages` (`drainage_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lot_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE;

--
-- Constraints for table `farmer_requests`
--
ALTER TABLE `farmer_requests`
  ADD CONSTRAINT `farmer_requests_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`),
  ADD CONSTRAINT `fk_request_area` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_canal` FOREIGN KEY (`canal_id`) REFERENCES `canals` (`canal_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_collected_by` FOREIGN KEY (`collected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_drainage` FOREIGN KEY (`drainage_id`) REFERENCES `drainages` (`drainage_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_lot` FOREIGN KEY (`lot_id`) REFERENCES `farmer_lots` (`lot_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_requested_by` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_technician` FOREIGN KEY (`assigned_technician_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `form_template_fields`
--
ALTER TABLE `form_template_fields`
  ADD CONSTRAINT `fk_ftf_template` FOREIGN KEY (`template_id`) REFERENCES `form_templates` (`template_id`) ON DELETE CASCADE;

--
-- Constraints for table `irrigation_batches`
--
ALTER TABLE `irrigation_batches`
  ADD CONSTRAINT `irrigation_batches_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `irrigation_schedules` (`schedule_id`),
  ADD CONSTRAINT `irrigation_batches_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `irrigation_schedules`
--
ALTER TABLE `irrigation_schedules`
  ADD CONSTRAINT `fk_sched_request` FOREIGN KEY (`request_id`) REFERENCES `farmer_requests` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `irrigation_schedules_ibfk_1` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`),
  ADD CONSTRAINT `irrigation_schedules_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `paper_forms`
--
ALTER TABLE `paper_forms`
  ADD CONSTRAINT `fk_form_encoded_by` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_form_farmer` FOREIGN KEY (`issued_to_farmer_id`) REFERENCES `farmers` (`farmer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_form_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_form_template` FOREIGN KEY (`template_id`) REFERENCES `form_templates` (`template_id`) ON DELETE CASCADE;

--
-- Constraints for table `report_exports`
--
ALTER TABLE `report_exports`
  ADD CONSTRAINT `fk_report_exports_user` FOREIGN KEY (`exported_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `request_approvals`
--
ALTER TABLE `request_approvals`
  ADD CONSTRAINT `fk_approval_request` FOREIGN KEY (`request_id`) REFERENCES `farmer_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_approval_user` FOREIGN KEY (`decided_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD CONSTRAINT `fk_attach_request` FOREIGN KEY (`request_id`) REFERENCES `farmer_requests` (`request_id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `fk_sms_request` FOREIGN KEY (`request_id`) REFERENCES `farmer_requests` (`request_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `sms_recipients`
--
ALTER TABLE `sms_recipients`
  ADD CONSTRAINT `sms_recipients_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `irrigation_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `water_allocations`
--
ALTER TABLE `water_allocations`
  ADD CONSTRAINT `water_allocations_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`),
  ADD CONSTRAINT `water_allocations_ibfk_2` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`);

--
-- Constraints for table `water_release_logs`
--
ALTER TABLE `water_release_logs`
  ADD CONSTRAINT `water_release_logs_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `irrigation_schedules` (`schedule_id`),
  ADD CONSTRAINT `water_release_logs_ibfk_2` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`service_area_id`),
  ADD CONSTRAINT `water_release_logs_ibfk_3` FOREIGN KEY (`released_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
