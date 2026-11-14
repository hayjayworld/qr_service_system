-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql113.infinityfree.com
-- Generation Time: Jul 14, 2025 at 08:57 AM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38704548_hotel_qr_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password_hash`, `full_name`, `email`, `created_at`, `role`, `created_by`) VALUES
(1, 'admin', 'admin', 'System Administrator', 'admin@hotel.com', '2025-05-25 20:07:22', 'admin', NULL),
(3, 'Ayodele101', 'admin', 'Ayodele Jolayemi', '', '2025-06-20 08:43:13', 'staff', 1),
(4, 'Yusuf001', 'admin', 'Yusuf yusuf', '', '2025-06-20 08:43:48', 'manager', 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Bar', 'Alcoholic and non-alcoholic beverages', '2025-05-25 20:07:33'),
(2, 'Kitchen', 'Food and meals prepared by our chefs', '2025-05-25 20:07:33'),
(3, 'Spa', 'Relaxation and beauty treatments', '2025-05-25 20:07:33'),
(4, 'Room Service', 'In-room dining and services', '2025-05-25 20:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `complaint_text` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `location_label` varchar(100) NOT NULL,
  `items` longtext DEFAULT NULL,
  `status` enum('pending','completed','cancelled') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `location_label`, `items`, `status`, `notes`, `created_at`, `cancelled_at`, `completed_at`) VALUES
(1, 'Lobby Bar Table 1', '[{\"id\":2,\"name\":\"Beer\",\"price\":5,\"quantity\":1},{\"id\":4,\"name\":\"Vegetarian Pasta\",\"price\":15.5,\"quantity\":1},{\"id\":7,\"name\":\"Breakfast Tray\",\"price\":18,\"quantity\":1}]', 'cancelled', '', '2025-05-29 03:37:20', '2025-06-01 02:03:58', NULL),
(2, 'Lobby Bar Table 1', '[{\"id\":5,\"name\":\"Massage\",\"price\":75,\"quantity\":1},{\"id\":7,\"name\":\"Breakfast Tray\",\"price\":18,\"quantity\":1}]', 'completed', 'i want this aspa', '2025-06-01 02:54:39', '2025-06-01 02:54:39', '2025-06-01 04:33:41'),
(3, 'Lobby Bar Table 2', '[{\"id\":2,\"name\":\"Beer\",\"price\":5,\"quantity\":2},{\"id\":1,\"name\":\"Cocktail\",\"price\":12.5,\"quantity\":1}]', 'cancelled', 'love this', '2025-06-01 03:57:20', '2025-06-06 17:40:30', NULL),
(4, 'Lobby Bar Table 2', NULL, 'pending', 'asap', '2025-06-09 03:59:50', '2025-06-09 03:59:50', NULL),
(7, 'Lobby Bar Table 3', NULL, 'completed', '', '2025-06-09 04:20:13', '2025-06-09 04:20:13', '2025-06-11 02:01:34'),
(8, 'Lobby Bar Table 1', NULL, 'pending', 'No pepper', '2025-06-23 11:25:40', '2025-06-23 11:25:40', NULL),
(9, 'Lobby Bar Table 2', NULL, 'pending', '', '2025-07-13 04:20:46', '2025-07-13 04:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `service_id`, `price`, `quantity`) VALUES
(1, 1, 2, '5.00', 1),
(2, 1, 4, '15.50', 1),
(3, 1, 7, '18.00', 1),
(4, 2, 5, '75.00', 1),
(5, 2, 7, '18.00', 1),
(6, 3, 2, '5.00', 2),
(7, 3, 1, '12.50', 1),
(8, 4, 8, '12.00', 1),
(9, 4, 4, '15.50', 2),
(10, 7, 2, '5.00', 2),
(11, 7, 3, '22.00', 1),
(12, 8, 7, '18.00', 1),
(13, 8, 3, '22.00', 1),
(14, 8, 2, '5.00', 3),
(15, 9, 2, '5.00', 1),
(16, 9, 3, '22.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `label`, `created_at`) VALUES
(1, 'Room 101', '2025-05-25 20:08:02'),
(2, 'Room 102', '2025-05-25 20:08:02'),
(3, 'Room 201', '2025-05-25 20:08:02'),
(4, 'Room 202', '2025-05-25 20:08:02'),
(5, 'Poolside Table 1', '2025-05-25 20:08:02'),
(6, 'Poolside Table 2', '2025-05-25 20:08:02'),
(7, 'Lobby Bar Table 1', '2025-05-25 20:08:02'),
(8, 'Lobby Bar Table 2', '2025-05-25 20:08:02'),
(9, 'Table 2', '2025-05-25 21:14:39');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `price`, `is_active`, `created_at`) VALUES
(1, 1, 'Cocktail', 'House special cocktail', '12.50', 1, '2025-05-25 20:07:43'),
(2, 1, 'Beer', 'Local craft beer', '5.00', 1, '2025-05-25 20:07:43'),
(3, 2, 'Steak Dinner', '8oz steak with sides', '22.00', 1, '2025-05-25 20:07:43'),
(4, 2, 'Vegetarian Pasta', 'Fresh pasta with seasonal vegetables', '15.50', 1, '2025-05-25 20:07:43'),
(5, 3, 'Massage', '60-minute full body massage', '75.00', 1, '2025-05-25 20:07:43'),
(6, 3, 'Facial', 'Deep cleansing facial treatment', '60.00', 1, '2025-05-25 20:07:43'),
(7, 4, 'Breakfast Tray', 'Continental breakfast delivered to your room', '18.00', 1, '2025-05-25 20:07:43'),
(8, 4, 'Late Night Snack', 'Selection of cheeses and fruits', '12.00', 1, '2025-05-25 20:07:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_pending` (`status`,`id`),
  ADD KEY `idx_created_status` (`created_at`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `label` (`label`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
