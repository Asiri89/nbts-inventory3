-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 16, 2025 at 11:48 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nbts-inventory3`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 15:22:34'),
(2, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 15:42:20'),
(3, 1, 'Create Item', 'items', 9, NULL, '{\"code\": \"5465\", \"name\": \"vhguhg\", \"unit_cost\": 0, \"category_id\": 1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 15:54:50'),
(4, 1, 'Create Item', 'items', 10, NULL, '{\"code\": \"54688\", \"name\": \"vhguhg\", \"unit_cost\": 0, \"category_id\": 1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 15:56:53'),
(5, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:03:23'),
(6, 2, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:03:44'),
(7, 2, 'Add Stock', 'inventory', 9, NULL, '{\"item_id\": 3, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 25000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:28:54'),
(8, 2, 'Add Stock', 'inventory', 10, NULL, '{\"item_id\": 3, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 25000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:30:05'),
(10, 2, 'Create Maintenance Schedule', 'maintenance_schedules', 1, NULL, '{\"inventory_id\": 1, \"maintenance_type\": \"inspection\", \"next_maintenance_date\": \"2025-09-09\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:33:23'),
(11, 2, 'Add Stock', 'inventory', 11, NULL, '{\"item_id\": 7, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 250}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:40:52'),
(12, 2, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:42:48'),
(13, 4, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:43:09'),
(14, 4, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:44:37'),
(15, 5, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:55:11'),
(16, 5, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:55:30'),
(17, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:55:41'),
(18, 1, 'Create User', 'users', 6, NULL, '{\"role\": \"inventory_manager\", \"email\": \"asiriiresh@gmail.com\", \"username\": \"galle_user\", \"branch_id\": 3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:58:58'),
(19, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:59:06'),
(20, 6, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-11 16:59:18'),
(21, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 03:35:08'),
(22, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 07:08:29'),
(23, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 07:08:44'),
(24, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 07:15:03'),
(25, 1, 'Add Stock', 'inventory', 13, NULL, '{\"item_id\": 3, \"quantity\": 2, \"branch_id\": 1, \"unit_cost\": 25000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 07:21:05'),
(26, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 08:50:03'),
(27, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-12 08:55:06'),
(28, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 09:09:54'),
(32, 1, 'Add Stock', 'inventory', 14, NULL, '{\"item_id\": 1, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 2500}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 09:59:38'),
(33, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 14:57:35'),
(34, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 15:19:03'),
(35, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 15:50:16'),
(37, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:16:37'),
(38, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:17:25'),
(39, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:17:51'),
(40, 6, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:18:07'),
(41, 6, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:18:54'),
(42, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 16:18:56'),
(43, 1, 'Allocate Items', 'bulk_allocations', 1, NULL, '{\"4\": [\"iuguyg\"], \"5\": [\"ihiugiy\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 17:59:48'),
(44, 1, 'Accept Allocation', 'allocation_items', NULL, NULL, '{\"action\": \"accept\", \"item_count\": 2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 18:31:55'),
(45, 1, 'Add Stock', 'inventory', 18, NULL, '{\"item_id\": 1, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 2500}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-13 18:32:17'),
(47, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 04:38:01'),
(48, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 06:39:59'),
(50, 1, 'Add Stock', 'inventory', 19, NULL, '{\"item_id\": 1, \"quantity\": 1, \"branch_id\": 1, \"unit_cost\": 2500}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 08:05:38'),
(51, 1, 'Allocate Items', 'bulk_allocations', 2, NULL, '{\"5\": [\"25255\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 08:07:56'),
(52, 1, 'Add Stock', 'inventory', 20, NULL, '{\"item_id\": 2, \"quantity\": 3, \"branch_id\": 1, \"unit_cost\": 15000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 08:08:33'),
(53, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:07:37'),
(54, 1, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:07:43'),
(55, 2, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:07:57'),
(56, 2, 'Generate QR Code', 'items', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:09:09'),
(57, 2, 'Create Maintenance Schedule', 'maintenance_schedules', 2, NULL, '{\"inventory_id\": 2, \"maintenance_type\": \"calibration\", \"next_maintenance_date\": \"2025-09-25\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:10:06'),
(58, 2, 'User Logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-14 10:11:10'),
(59, 1, 'User Login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-16 11:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `allocation_items`
--

DROP TABLE IF EXISTS `allocation_items`;
CREATE TABLE IF NOT EXISTS `allocation_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `allocation_id` int NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `branch_id` int DEFAULT NULL,
  `status` enum('available','allocated','accepted','rejected') DEFAULT 'available',
  `allocated_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `accepted_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_serial` (`allocation_id`,`serial_number`),
  KEY `branch_id` (`branch_id`),
  KEY `accepted_by` (`accepted_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `allocation_items`
--

INSERT INTO `allocation_items` (`id`, `allocation_id`, `serial_number`, `branch_id`, `status`, `allocated_at`, `accepted_at`, `accepted_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, ',,,,', NULL, 'available', NULL, NULL, NULL, NULL, '2025-08-13 17:59:20', '2025-08-13 17:59:20'),
(2, 1, 'iuguyg', 4, 'allocated', '2025-08-13 23:29:48', NULL, NULL, NULL, '2025-08-13 17:59:20', '2025-08-13 17:59:48'),
(3, 1, 'ihiugiy', 5, 'allocated', '2025-08-13 23:29:48', NULL, NULL, NULL, '2025-08-13 17:59:20', '2025-08-13 17:59:48'),
(4, 2, '25255', 5, 'allocated', '2025-08-14 13:37:56', NULL, NULL, NULL, '2025-08-14 08:06:23', '2025-08-14 08:07:56'),
(5, 3, '2154', NULL, 'available', NULL, NULL, NULL, NULL, '2025-08-14 08:09:00', '2025-08-14 08:09:00'),
(6, 4, 'sss', NULL, 'available', NULL, NULL, NULL, NULL, '2025-08-16 11:13:10', '2025-08-16 11:13:10'),
(7, 5, 'jklkm', NULL, 'available', NULL, NULL, NULL, NULL, '2025-08-16 11:13:47', '2025-08-16 11:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `district` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `address`, `city`, `district`, `phone`, `email`, `manager_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'National Blood Centre', 'NBC', 'No. 96, Baseline Road, Colombo 09', 'Colombo', 'Colombo', '+94112695395', 'nbc@nbts.health.gov.lk', 'Dr. Samantha Perera', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(2, 'Teaching Hospital Kandy', 'THK', 'Teaching Hospital, Kandy', 'Kandy', 'Kandy', '+94812223337', 'kandy@nbts.health.gov.lk', 'Dr. Nimal Silva', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(3, 'Base Hospital Galle', 'BHG', 'Base Hospital, Galle', 'Galle', 'Galle', '+94912234567', 'galle@nbts.health.gov.lk', 'Dr. Priya Fernando', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(4, 'General Hospital Jaffna', 'GHJ', 'General Hospital, Jaffna', 'Jaffna', 'Jaffna', '+94212223456', 'jaffna@nbts.health.gov.lk', 'Dr. Kumar Raj', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(5, 'District General Hospital Anuradhapura', 'DGHA', 'DGH Anuradhapura', 'Anuradhapura', 'Anuradhapura', '+94252223789', 'anuradhapura@nbts.health.gov.lk', 'Dr. Sunil Bandara', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_allocations`
--

DROP TABLE IF EXISTS `bulk_allocations`;
CREATE TABLE IF NOT EXISTS `bulk_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `total_quantity` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `status` enum('pending','allocated','completed') DEFAULT 'pending',
  `notes` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bulk_allocations`
--

INSERT INTO `bulk_allocations` (`id`, `item_id`, `supplier_id`, `purchase_date`, `invoice_number`, `total_quantity`, `unit_cost`, `total_cost`, `warranty_start_date`, `warranty_end_date`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 3, '2025-08-13', '', 3, 25000.00, 75000.00, NULL, NULL, 'allocated', '', 1, '2025-08-13 17:59:19', '2025-08-13 17:59:48'),
(2, 1, NULL, '2025-08-14', '', 1, 2500.00, 2500.00, NULL, NULL, 'allocated', '', 1, '2025-08-14 08:06:23', '2025-08-14 08:07:56'),
(3, 2, NULL, '2025-08-14', '', 1, 15000.00, 15000.00, NULL, NULL, 'pending', '', 1, '2025-08-14 08:09:00', '2025-08-14 08:09:00'),
(4, 2, NULL, '2025-08-16', '', 1, 15000.00, 15000.00, NULL, NULL, 'pending', '', 1, '2025-08-16 11:13:10', '2025-08-16 11:13:10'),
(5, 1, NULL, '2025-08-16', '', 1, 2500.00, 2500.00, NULL, NULL, 'pending', '', 1, '2025-08-16 11:13:47', '2025-08-16 11:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('medical','non_medical') NOT NULL DEFAULT 'medical',
  `description` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `code`, `type`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Blood Processing Equipment', 'BPE', 'medical', 'Equipment for blood collection and processing', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(3, 'Refrigeration Equipment', 'REF', 'medical', 'Blood storage refrigerators and freezers', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(4, 'Testing Equipment', 'TEST', 'medical', 'Equipment for blood testing and screening', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(5, 'Office Equipment', 'OFF', 'non_medical', 'General office equipment and furniture', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(6, 'IT Equipment', 'IT', 'non_medical', 'Computers, printers and IT infrastructure', 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `document_type` enum('warranty_card','manual','invoice','certificate','photo','other') NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `allocation_id` int DEFAULT NULL,
  `is_allocated` tinyint(1) DEFAULT '0',
  `invoice_number` varchar(100) DEFAULT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `assigned_staff` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','repair','disposed','lost','damaged') NOT NULL DEFAULT 'active',
  `quantity` int NOT NULL DEFAULT '1',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `total_cost` decimal(10,2) DEFAULT '0.00',
  `qr_code` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `branch_id` (`branch_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `idx_serial` (`serial_number`),
  KEY `idx_status` (`status`),
  KEY `idx_warranty` (`warranty_end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_id`, `branch_id`, `serial_number`, `batch_number`, `purchase_date`, `supplier_id`, `allocation_id`, `is_allocated`, `invoice_number`, `warranty_start_date`, `warranty_end_date`, `installation_date`, `location`, `assigned_staff`, `status`, `quantity`, `unit_cost`, `total_cost`, `qr_code`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:04:54', '2025-08-11 16:04:54'),
(2, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:12:57', '2025-08-13 09:53:51'),
(3, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:13:03', '2025-08-11 16:13:03'),
(4, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:15:20', '2025-08-13 09:55:30'),
(5, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:22:12', '2025-08-11 16:22:12'),
(6, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:22:17', '2025-08-11 16:22:17'),
(7, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:24:21', '2025-08-11 16:24:21'),
(8, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:24:29', '2025-08-14 07:52:40'),
(9, 3, 1, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, NULL, '', '2025-08-11 16:28:54', '2025-08-11 16:28:54'),
(10, 3, 3, '', '', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', '2025-08-10', '', '', 'active', 1, 25000.00, 25000.00, 'inv_10_1754929805.png', '', '2025-08-11 16:30:05', '2025-08-13 09:53:23'),
(11, 7, 1, 'ddd', 'ddd', '2025-08-11', 3, NULL, 0, '', '2025-08-14', '2026-08-14', NULL, '', '', 'active', 1, 250.00, 250.00, 'inv_11_1754930452.png', '', '2025-08-11 16:40:52', '2025-08-13 16:16:03'),
(12, 3, 1, '', '', '2025-08-12', 3, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, 'active', 2, 25000.00, 50000.00, NULL, '', '2025-08-12 07:16:17', '2025-08-12 07:16:17'),
(13, 3, 1, '', '', '2025-08-12', 3, NULL, 0, '', NULL, NULL, '2025-08-21', 'ss', 'ss', 'repair', 2, 25000.00, 50000.00, 'inv_13_1754983265.png', '', '2025-08-12 07:21:05', '2025-08-13 16:24:36'),
(14, 1, 1, '', '', '2025-08-13', NULL, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, 'active', 1, 2500.00, 2500.00, 'inv_14_1755079178.png', '', '2025-08-13 09:59:37', '2025-08-13 09:59:39'),
(15, 3, 1, 'iuguyg', NULL, '0000-00-00', 3, 1, 1, '', NULL, NULL, NULL, NULL, NULL, 'active', 1, 25000.00, 25000.00, NULL, NULL, '2025-08-13 18:30:13', '2025-08-13 18:30:13'),
(16, 3, 1, 'iuguyg', NULL, '0000-00-00', 3, 1, 1, '', NULL, NULL, '2025-08-15', 'iuyiu', 'jhf', 'disposed', 1, 25000.00, 25000.00, NULL, 'kguyg', '2025-08-13 18:31:55', '2025-08-14 06:48:52'),
(17, 3, 1, 'ihiugiy', NULL, '0000-00-00', 3, 1, 1, '', NULL, NULL, NULL, NULL, NULL, 'active', 1, 25000.00, 25000.00, NULL, NULL, '2025-08-13 18:31:55', '2025-08-13 18:31:55'),
(18, 1, 1, '', '', '2025-08-13', NULL, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, 'active', 1, 2500.00, 2500.00, NULL, '', '2025-08-13 18:32:17', '2025-08-13 18:32:53'),
(19, 1, 1, '555', '555', '2025-08-14', 3, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, 'active', 1, 2500.00, 2500.00, NULL, '', '2025-08-14 08:05:38', '2025-08-14 08:05:38'),
(20, 2, 1, '654654', '', '2025-08-14', NULL, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, 'active', 3, 15000.00, 45000.00, NULL, '', '2025-08-14 08:08:33', '2025-08-14 08:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) NOT NULL,
  `category_id` int NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `description` text,
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `reorder_level` int DEFAULT '0',
  `qr_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `code`, `category_id`, `brand`, `model`, `description`, `unit_of_measure`, `unit_cost`, `reorder_level`, `qr_code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Blood Collection Scale', 'BCS001', 1, 'Sartorius', 'MILKYSAFE-1', 'Digital scale for blood collection monitoring', 'pcs', 2500.00, 2, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(2, 'Centrifuge Machine', 'CM001', 1, 'Hettich', 'ROTOFIX-32A', 'Tabletop centrifuge for blood component separation', 'pcs', 15000.00, 1, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(3, 'Blood Bank Refrigerator', 'BBR001', 3, 'Haier', 'HYC-390F', '4°C blood storage refrigerator with alarm', 'pcs', 25000.00, 1, 'item_3_1755166148.png', 1, '2025-08-11 15:17:04', '2025-08-14 10:09:09'),
(4, 'Plasma Freezer', 'PF001', 3, 'Thermo Fisher', 'TSX-40086FA', '-40°C plasma storage freezer', 'pcs', 45000.00, 1, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(5, 'ELISA Reader', 'ER001', 4, 'BioTek', 'ELx800', 'Microplate reader for blood screening tests', 'pcs', 35000.00, 1, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(6, 'Desktop Computer', 'DC001', 6, 'Dell', 'OptiPlex-3070', 'Desktop computer for office use', 'pcs', 800.00, 5, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(7, 'Laser Printer', 'LP001', 6, 'HP', 'LaserJet-Pro-M404n', 'Monochrome laser printer', 'pcs', 250.00, 3, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(8, 'Office Chair', 'OC001', 5, 'Ergonomic Plus', 'EP-7500', 'Ergonomic office chair with lumbar support', 'pcs', 150.00, 10, NULL, 1, '2025-08-11 15:17:04', '2025-08-11 15:17:04'),
(9, 'vhguhg', '5465', 1, 'hgfg', 'jgvh', '0', 'set', 0.00, 0, NULL, 1, '2025-08-11 15:54:50', '2025-08-11 15:54:50'),
(10, 'vhguhg', '54688', 1, 'hgfg', 'jgvh', '0', 'set', 0.00, 0, 'item_10_1754927813.png', 0, '2025-08-11 15:56:53', '2025-08-11 15:56:56');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_history`
--

DROP TABLE IF EXISTS `maintenance_history`;
CREATE TABLE IF NOT EXISTS `maintenance_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `schedule_id` int DEFAULT NULL,
  `maintenance_type` enum('preventive','calibration','repair','inspection','emergency') NOT NULL,
  `description` text NOT NULL,
  `maintenance_date` date NOT NULL,
  `technician_name` varchar(100) NOT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `downtime_hours` decimal(5,2) DEFAULT '0.00',
  `parts_replaced` text,
  `findings` text,
  `recommendations` text,
  `next_maintenance_due` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `schedule_id` (`schedule_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_maintenance_date` (`maintenance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
CREATE TABLE IF NOT EXISTS `maintenance_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `maintenance_type` enum('preventive','calibration','repair','inspection') NOT NULL,
  `description` text NOT NULL,
  `frequency_days` int NOT NULL DEFAULT '365',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date NOT NULL,
  `assigned_technician` varchar(100) DEFAULT NULL,
  `cost_estimate` decimal(10,2) DEFAULT '0.00',
  `status` enum('scheduled','overdue','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `idx_next_maintenance` (`next_maintenance_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `maintenance_schedules`
--

INSERT INTO `maintenance_schedules` (`id`, `inventory_id`, `maintenance_type`, `description`, `frequency_days`, `last_maintenance_date`, `next_maintenance_date`, `assigned_technician`, `cost_estimate`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'inspection', 'jgfgf', 365, NULL, '0000-00-00', '', 0.00, 'scheduled', '', '2025-08-11 16:33:23', '2025-08-11 16:33:23'),
(2, 2, 'calibration', 'lknlk', 365, NULL, '0000-00-00', '', 0.00, 'scheduled', '', '2025-08-14 10:10:06', '2025-08-14 10:10:06');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `type` enum('low_stock','warranty_expiry','maintenance_due','system') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_sent_email` tinyint(1) NOT NULL DEFAULT '0',
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `idx_type_read` (`type`,`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `branch_id`, `type`, `title`, `message`, `is_read`, `is_sent_email`, `related_table`, `related_id`, `created_at`) VALUES
(1, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at National Blood Centre has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-11 16:07:48'),
(3, NULL, 3, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at Base Hospital Galle has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-11 16:32:44'),
(4, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'inspection maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 1, '2025-08-11 16:33:40'),
(5, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Laser Printer (LP001) at National Blood Centre has low stock. Current: 1, Reorder Level: 3', 0, 0, 'items', 7, '2025-08-11 16:40:54'),
(6, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Laser Printer (LP001) at National Blood Centre has low stock. Current: 1, Reorder Level: 3', 0, 0, 'items', 7, '2025-08-12 03:35:08'),
(7, NULL, 3, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at Base Hospital Galle has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-12 03:35:08'),
(8, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'inspection maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 1, '2025-08-12 03:35:08'),
(9, NULL, 3, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at Base Hospital Galle has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-13 09:09:54'),
(10, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Laser Printer (LP001) at National Blood Centre has low stock. Current: 1, Reorder Level: 3', 0, 0, 'items', 7, '2025-08-13 09:09:54'),
(11, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'inspection maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 1, '2025-08-13 09:09:54'),
(15, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Blood Collection Scale (BCS001) at National Blood Centre has low stock. Current: 1, Reorder Level: 2', 0, 0, 'items', 1, '2025-08-13 10:05:34'),
(17, NULL, 5, 'system', 'New Item Allocation', '1 units of Blood Bank Refrigerator allocated to your branch. Please review and accept.', 0, 0, 'bulk_allocations', 1, '2025-08-13 17:59:48'),
(18, NULL, 4, 'system', 'New Item Allocation', '1 units of Blood Bank Refrigerator allocated to your branch. Please review and accept.', 0, 0, 'bulk_allocations', 1, '2025-08-13 17:59:48'),
(20, NULL, 3, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at Base Hospital Galle has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-13 18:33:02'),
(21, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Laser Printer (LP001) at National Blood Centre has low stock. Current: 1, Reorder Level: 3', 0, 0, 'items', 7, '2025-08-13 18:33:02'),
(22, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Blood Collection Scale (BCS001) at National Blood Centre has low stock. Current: 2, Reorder Level: 2', 0, 0, 'items', 1, '2025-08-13 18:33:02'),
(23, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'inspection maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 1, '2025-08-13 18:33:02'),
(25, NULL, 5, 'system', 'New Item Allocation', '1 units of Blood Collection Scale allocated to your branch. Please review and accept.', 0, 0, 'bulk_allocations', 2, '2025-08-14 08:07:56'),
(26, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'calibration maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 2, '2025-08-14 10:10:12'),
(27, NULL, 3, 'low_stock', 'Low Stock Alert', 'Item Blood Bank Refrigerator (BBR001) at Base Hospital Galle has low stock. Current: 1, Reorder Level: 1', 0, 0, 'items', 3, '2025-08-16 11:05:22'),
(28, NULL, 1, 'low_stock', 'Low Stock Alert', 'Item Laser Printer (LP001) at National Blood Centre has low stock. Current: 1, Reorder Level: 3', 0, 0, 'items', 7, '2025-08-16 11:05:22'),
(29, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'inspection maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 1, '2025-08-16 11:05:22'),
(30, NULL, 1, 'maintenance_due', 'Maintenance Due Alert', 'calibration maintenance for Blood Bank Refrigerator (S/N: ) at National Blood Centre is due - today', 0, 0, 'maintenance_schedules', 2, '2025-08-16 11:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `movement_type` enum('receipt','issue','transfer_out','transfer_in','adjustment','return','disposal') NOT NULL,
  `from_branch_id` int DEFAULT NULL,
  `to_branch_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `total_cost` decimal(10,2) DEFAULT '0.00',
  `reference_number` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `transfer_status` enum('pending','in_transit','received','rejected') DEFAULT 'received',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `from_branch_id` (`from_branch_id`),
  KEY `to_branch_id` (`to_branch_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `inventory_id`, `movement_type`, `from_branch_id`, `to_branch_id`, `quantity`, `unit_cost`, `total_cost`, `reference_number`, `reason`, `notes`, `created_by`, `approved_by`, `approval_status`, `transfer_status`, `created_at`) VALUES
(1, 9, 'receipt', NULL, 1, 1, 25000.00, 25000.00, '', 'Initial stock receipt', NULL, 2, NULL, 'approved', 'received', '2025-08-11 16:28:54'),
(2, 10, 'receipt', NULL, 1, 1, 25000.00, 25000.00, '', 'Initial stock receipt', NULL, 2, NULL, 'approved', 'received', '2025-08-11 16:30:05'),
(3, 10, 'transfer_out', 1, 3, 1, 25000.00, 25000.00, '', 'Maintenance support', '', 2, NULL, 'approved', 'received', '2025-08-11 16:31:46'),
(4, 10, 'transfer_in', 1, 3, 1, 25000.00, 25000.00, '', 'Maintenance support', '', 2, NULL, 'approved', 'received', '2025-08-11 16:31:46'),
(5, 11, 'receipt', NULL, 1, 1, 250.00, 250.00, '', 'Initial stock receipt', NULL, 2, NULL, 'approved', 'received', '2025-08-11 16:40:52'),
(6, 13, 'receipt', NULL, 1, 2, 25000.00, 50000.00, '', 'Central procurement', NULL, 1, NULL, 'approved', 'received', '2025-08-12 07:21:05'),
(7, 10, 'transfer_out', 3, 1, 1, 25000.00, 25000.00, '', 'Equipment redistribution', '', 1, NULL, 'approved', 'pending', '2025-08-13 09:53:23'),
(8, 2, 'transfer_out', 1, 1, 1, 25000.00, 25000.00, '', 'Equipment redistribution', '', 1, NULL, 'approved', 'pending', '2025-08-13 09:53:51'),
(9, 4, 'transfer_out', 1, 2, 1, 25000.00, 25000.00, '', 'Equipment redistribution', '', 1, NULL, 'approved', 'pending', '2025-08-13 09:55:30'),
(10, 14, 'receipt', NULL, 1, 1, 2500.00, 2500.00, '', 'Central procurement', NULL, 1, NULL, 'approved', 'received', '2025-08-13 09:59:38'),
(11, 11, 'transfer_out', 1, 3, 1, 250.00, 250.00, '', 'Maintenance support', '', 1, NULL, 'approved', 'pending', '2025-08-13 16:16:03'),
(12, 16, 'transfer_in', 1, 1, 1, 25000.00, 25000.00, NULL, 'Central allocation accepted', NULL, 1, NULL, 'approved', 'received', '2025-08-13 18:31:55'),
(13, 17, 'transfer_in', 1, 1, 1, 25000.00, 25000.00, NULL, 'Central allocation accepted', NULL, 1, NULL, 'approved', 'received', '2025-08-13 18:31:55'),
(14, 18, 'receipt', NULL, 1, 1, 2500.00, 2500.00, '', 'Central procurement', NULL, 1, NULL, 'approved', 'received', '2025-08-13 18:32:17'),
(15, 18, 'transfer_out', 1, 3, 1, 2500.00, 2500.00, '', 'Branch requirement', '', 1, NULL, 'approved', 'pending', '2025-08-13 18:32:53'),
(16, 8, 'transfer_out', 1, 1, 1, 25000.00, 25000.00, '', 'Maintenance support', '', 1, NULL, 'approved', 'pending', '2025-08-14 07:52:40'),
(17, 19, 'receipt', NULL, 1, 1, 2500.00, 2500.00, '', 'Central procurement', NULL, 1, NULL, 'approved', 'received', '2025-08-14 08:05:38'),
(18, 20, 'receipt', NULL, 1, 3, 15000.00, 45000.00, '', 'Central procurement', NULL, 1, NULL, 'approved', 'received', '2025-08-14 08:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `address` text,
  `city` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `code`, `contact_person`, `address`, `city`, `phone`, `email`, `tax_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Medequip Lanka (Pvt) Ltd', 'MEL', 'John Perera', 'No. 123, Galle Road, Colombo 03', 'Colombo', '+94112345678', 'info@medequip.lk', NULL, 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(2, 'Sri Lanka Medical Equipment Corp', 'SLMEC', 'Saman Silva', 'No. 456, Kandy Road, Kurunegala', 'Kurunegala', '+94372234567', 'sales@slmec.lk', NULL, 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(3, 'Biomedical Solutions Ltd', 'BMS', 'Nimal Fernando', 'No. 789, Matara Road, Galle', 'Galle', '+94912345678', 'contact@biomedical.lk', NULL, 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(4, 'Healthcare Technologies Pvt Ltd', 'HCT', 'Priya Jayasinghe', 'No. 321, Jaffna Road, Vavuniya', 'Vavuniya', '+94242234567', 'info@healthcare-tech.lk', NULL, 1, '2025-08-11 15:17:03', '2025-08-11 15:17:03');

-- --------------------------------------------------------

--
-- Table structure for table `transfer_requests`
--

DROP TABLE IF EXISTS `transfer_requests`;
CREATE TABLE IF NOT EXISTS `transfer_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `from_branch_id` int NOT NULL,
  `to_branch_id` int NOT NULL,
  `item_id` int NOT NULL,
  `requested_quantity` int NOT NULL,
  `reason` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `requested_by` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_branch_id` (`from_branch_id`),
  KEY `to_branch_id` (`to_branch_id`),
  KEY `item_id` (`item_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','inventory_manager','view_only','auditor') NOT NULL DEFAULT 'view_only',
  `branch_id` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `role`, `branch_id`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', NULL, 1, '2025-08-16 16:35:22', '2025-08-11 15:17:03', '2025-08-16 11:05:22'),
(2, 'inv_manager_nbc', 'manager.nbc@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Saman', 'Wickramasinghe', 'inventory_manager', 1, 1, '2025-08-14 15:37:57', '2025-08-11 15:17:03', '2025-08-14 10:07:57'),
(3, 'inv_manager_kandy', 'manager.kandy@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nimali', 'Jayawardena', 'inventory_manager', 2, 1, NULL, '2025-08-11 15:17:03', '2025-08-11 15:17:03'),
(4, 'viewer_galle', 'viewer.galle@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ruwan', 'Perera', 'view_only', 3, 1, '2025-08-11 22:13:09', '2025-08-11 15:17:03', '2025-08-11 16:43:09'),
(5, 'auditor', 'auditor@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kamala', 'Rathnayake', 'auditor', NULL, 1, '2025-08-11 22:25:11', '2025-08-11 15:17:03', '2025-08-11 16:55:11'),
(6, 'galle_user', 'asiriiresh@gmail.com', '$2y$10$iamLGNHHifAM1Egx5EDsU.hwgnGnGE4f6U2zJZZ.dg1SZTOXEZ.we', 'galle', 'user', 'inventory_manager', 3, 1, '2025-08-13 21:48:07', '2025-08-11 16:58:58', '2025-08-13 16:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `module_name` varchar(50) NOT NULL,
  `permission_type` enum('create','read','update','delete','export') NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`role_name`,`module_name`,`permission_type`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `role_name`, `module_name`, `permission_type`, `is_allowed`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'users', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(2, 'admin', 'users', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(3, 'admin', 'users', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(4, 'admin', 'users', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(5, 'admin', 'branches', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(6, 'admin', 'branches', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(7, 'admin', 'branches', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(8, 'admin', 'branches', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(9, 'admin', 'suppliers', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(10, 'admin', 'suppliers', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(11, 'admin', 'suppliers', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(12, 'admin', 'suppliers', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(13, 'admin', 'categories', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(14, 'admin', 'categories', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(15, 'admin', 'categories', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(16, 'admin', 'categories', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(17, 'admin', 'items', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(18, 'admin', 'items', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(19, 'admin', 'items', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(20, 'admin', 'items', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(21, 'admin', 'inventory', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(22, 'admin', 'inventory', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(23, 'admin', 'inventory', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(24, 'admin', 'inventory', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(25, 'admin', 'movements', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(26, 'admin', 'movements', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(27, 'admin', 'movements', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(28, 'admin', 'movements', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(29, 'admin', 'maintenance', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(30, 'admin', 'maintenance', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(31, 'admin', 'maintenance', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(32, 'admin', 'maintenance', 'delete', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(33, 'admin', 'reports', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(34, 'admin', 'reports', 'export', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(35, 'admin', 'settings', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(36, 'admin', 'settings', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(37, 'admin', 'logs', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(38, 'inventory_manager', 'suppliers', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(39, 'inventory_manager', 'suppliers', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(40, 'inventory_manager', 'suppliers', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(41, 'inventory_manager', 'categories', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(42, 'inventory_manager', 'categories', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(43, 'inventory_manager', 'categories', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(44, 'inventory_manager', 'items', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(45, 'inventory_manager', 'items', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(46, 'inventory_manager', 'items', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(47, 'inventory_manager', 'inventory', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(48, 'inventory_manager', 'inventory', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(49, 'inventory_manager', 'inventory', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(50, 'inventory_manager', 'movements', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(51, 'inventory_manager', 'movements', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(52, 'inventory_manager', 'movements', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(53, 'inventory_manager', 'maintenance', 'create', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(54, 'inventory_manager', 'maintenance', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(55, 'inventory_manager', 'maintenance', 'update', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(56, 'inventory_manager', 'reports', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(57, 'inventory_manager', 'reports', 'export', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(58, 'view_only', 'suppliers', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(59, 'view_only', 'categories', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(60, 'view_only', 'items', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(61, 'view_only', 'inventory', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(62, 'view_only', 'movements', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(63, 'view_only', 'maintenance', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(64, 'view_only', 'reports', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(65, 'auditor', 'suppliers', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(66, 'auditor', 'categories', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(67, 'auditor', 'items', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(68, 'auditor', 'inventory', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(69, 'auditor', 'movements', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(70, 'auditor', 'maintenance', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(71, 'auditor', 'reports', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(72, 'auditor', 'reports', 'export', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34'),
(73, 'auditor', 'logs', 'read', 1, '2025-08-12 04:51:34', '2025-08-12 04:51:34');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `allocation_items`
--
ALTER TABLE `allocation_items`
  ADD CONSTRAINT `allocation_items_ibfk_1` FOREIGN KEY (`allocation_id`) REFERENCES `bulk_allocations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allocation_items_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `allocation_items_ibfk_3` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bulk_allocations`
--
ALTER TABLE `bulk_allocations`
  ADD CONSTRAINT `bulk_allocations_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `bulk_allocations_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bulk_allocations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `inventory_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `maintenance_history`
--
ALTER TABLE `maintenance_history`
  ADD CONSTRAINT `maintenance_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_history_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_history_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `maintenance_schedules`
--
ALTER TABLE `maintenance_schedules`
  ADD CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transfer_requests`
--
ALTER TABLE `transfer_requests`
  ADD CONSTRAINT `transfer_requests_ibfk_1` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `transfer_requests_ibfk_2` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `transfer_requests_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `transfer_requests_ibfk_4` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `transfer_requests_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
