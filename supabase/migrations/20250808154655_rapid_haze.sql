-- NBTS Inventory Management System Database Schema
-- National Blood Transfusion Service Sri Lanka

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: nbts_inventory
CREATE DATABASE IF NOT EXISTS `nbts_inventory` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nbts_inventory`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','inventory_manager','view_only','auditor') NOT NULL DEFAULT 'view_only',
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `branches`
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL UNIQUE,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `district` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `suppliers`
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL UNIQUE,
  `contact_person` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL UNIQUE,
  `type` enum('medical','non_medical') NOT NULL DEFAULT 'medical',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `items`
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) NOT NULL UNIQUE,
  `category_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 0,
  `qr_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `inventory`
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `assigned_staff` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','repair','disposed','lost','damaged') NOT NULL DEFAULT 'active',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `qr_code` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  INDEX `idx_serial` (`serial_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_warranty` (`warranty_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `stock_movements`
CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `movement_type` enum('receipt','issue','transfer_out','transfer_in','adjustment','return','disposal') NOT NULL,
  `from_branch_id` int(11) DEFAULT NULL,
  `to_branch_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `reference_number` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`from_branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`to_branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_movement_type` (`movement_type`),
  INDEX `idx_movement_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `maintenance_schedules`
CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `maintenance_type` enum('preventive','calibration','repair','inspection') NOT NULL,
  `description` text NOT NULL,
  `frequency_days` int(11) NOT NULL DEFAULT 365,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date NOT NULL,
  `assigned_technician` varchar(100) DEFAULT NULL,
  `cost_estimate` decimal(10,2) DEFAULT 0.00,
  `status` enum('scheduled','overdue','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
  INDEX `idx_next_maintenance` (`next_maintenance_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `maintenance_history`
CREATE TABLE `maintenance_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `maintenance_type` enum('preventive','calibration','repair','inspection','emergency') NOT NULL,
  `description` text NOT NULL,
  `maintenance_date` date NOT NULL,
  `technician_name` varchar(100) NOT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `downtime_hours` decimal(5,2) DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `next_maintenance_due` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_maintenance_date` (`maintenance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `documents`
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `document_type` enum('warranty_card','manual','invoice','certificate','photo','other') NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `activity_logs`
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_user_action` (`user_id`, `action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `type` enum('low_stock','warranty_expiry','maintenance_due','system') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_sent_email` tinyint(1) NOT NULL DEFAULT 0,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
  INDEX `idx_type_read` (`type`, `is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Sample Data Insertion

-- Insert branches
INSERT INTO `branches` (`name`, `code`, `address`, `city`, `district`, `phone`, `email`, `manager_name`) VALUES
('National Blood Centre', 'NBC', 'No. 96, Baseline Road, Colombo 09', 'Colombo', 'Colombo', '+94112695395', 'nbc@nbts.health.gov.lk', 'Dr. Samantha Perera'),
('Teaching Hospital Kandy', 'THK', 'Teaching Hospital, Kandy', 'Kandy', 'Kandy', '+94812223337', 'kandy@nbts.health.gov.lk', 'Dr. Nimal Silva'),
('Base Hospital Galle', 'BHG', 'Base Hospital, Galle', 'Galle', 'Galle', '+94912234567', 'galle@nbts.health.gov.lk', 'Dr. Priya Fernando'),
('General Hospital Jaffna', 'GHJ', 'General Hospital, Jaffna', 'Jaffna', 'Jaffna', '+94212223456', 'jaffna@nbts.health.gov.lk', 'Dr. Kumar Raj'),
('District General Hospital Anuradhapura', 'DGHA', 'DGH Anuradhapura', 'Anuradhapura', 'Anuradhapura', '+94252223789', 'anuradhapura@nbts.health.gov.lk', 'Dr. Sunil Bandara');

-- Insert users
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `branch_id`) VALUES
('admin', 'admin@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', NULL),
('inv_manager_nbc', 'manager.nbc@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Saman', 'Wickramasinghe', 'inventory_manager', 1),
('inv_manager_kandy', 'manager.kandy@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nimali', 'Jayawardena', 'inventory_manager', 2),
('viewer_galle', 'viewer.galle@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ruwan', 'Perera', 'view_only', 3),
('auditor', 'auditor@nbts.health.gov.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kamala', 'Rathnayake', 'auditor', NULL);

-- Insert suppliers
INSERT INTO `suppliers` (`name`, `code`, `contact_person`, `address`, `city`, `phone`, `email`) VALUES
('Medequip Lanka (Pvt) Ltd', 'MEL', 'John Perera', 'No. 123, Galle Road, Colombo 03', 'Colombo', '+94112345678', 'info@medequip.lk'),
('Sri Lanka Medical Equipment Corp', 'SLMEC', 'Saman Silva', 'No. 456, Kandy Road, Kurunegala', 'Kurunegala', '+94372234567', 'sales@slmec.lk'),
('Biomedical Solutions Ltd', 'BMS', 'Nimal Fernando', 'No. 789, Matara Road, Galle', 'Galle', '+94912345678', 'contact@biomedical.lk'),
('Healthcare Technologies Pvt Ltd', 'HCT', 'Priya Jayasinghe', 'No. 321, Jaffna Road, Vavuniya', 'Vavuniya', '+94242234567', 'info@healthcare-tech.lk');

-- Insert categories
INSERT INTO `categories` (`name`, `code`, `type`, `description`) VALUES
('Blood Processing Equipment', 'BPE', 'medical', 'Equipment for blood collection and processing'),
('Laboratory Equipment', 'LAB', 'medical', 'General laboratory equipment and instruments'),
('Refrigeration Equipment', 'REF', 'medical', 'Blood storage refrigerators and freezers'),
('Testing Equipment', 'TEST', 'medical', 'Equipment for blood testing and screening'),
('Office Equipment', 'OFF', 'non_medical', 'General office equipment and furniture'),
('IT Equipment', 'IT', 'non_medical', 'Computers, printers and IT infrastructure');

-- Insert items
INSERT INTO `items` (`name`, `code`, `category_id`, `brand`, `model`, `description`, `unit_cost`, `reorder_level`) VALUES
('Blood Collection Scale', 'BCS001', 1, 'Sartorius', 'MILKYSAFE-1', 'Digital scale for blood collection monitoring', 2500.00, 2),
('Centrifuge Machine', 'CM001', 1, 'Hettich', 'ROTOFIX-32A', 'Tabletop centrifuge for blood component separation', 15000.00, 1),
('Blood Bank Refrigerator', 'BBR001', 3, 'Haier', 'HYC-390F', '4°C blood storage refrigerator with alarm', 25000.00, 1),
('Plasma Freezer', 'PF001', 3, 'Thermo Fisher', 'TSX-40086FA', '-40°C plasma storage freezer', 45000.00, 1),
('ELISA Reader', 'ER001', 4, 'BioTek', 'ELx800', 'Microplate reader for blood screening tests', 35000.00, 1),
('Desktop Computer', 'DC001', 6, 'Dell', 'OptiPlex-3070', 'Desktop computer for office use', 800.00, 5),
('Laser Printer', 'LP001', 6, 'HP', 'LaserJet-Pro-M404n', 'Monochrome laser printer', 250.00, 3),
('Office Chair', 'OC001', 5, 'Ergonomic Plus', 'EP-7500', 'Ergonomic office chair with lumbar support', 150.00, 10);

COMMIT;