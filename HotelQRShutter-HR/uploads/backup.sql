-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hotel_qr_system
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `created_by` (`created_by`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`),
  CONSTRAINT `admin_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','','',NULL,NULL,'admin','System Administrator','admin@hotel.com',NULL,NULL,NULL,NULL,1,'2025-05-25 13:07:22',1,NULL),(3,'staff','Ahmad','okikiola','male','965434567','$2y$10$Yw8FhWs8ZnigmQRaVl65FO2QSqHj7uufVHCeOfk34z.rhdrCRr3cS',NULL,'hayjayworld@gmail.com','2025-09-09','ajbjsjsbjx k keuec','mon','4567890-33',1,'2025-09-18 20:15:24',3,1);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Bar','Alcoholic and non-alcoholic beverages','2025-05-25 13:07:33'),(2,'Kitchen','Food and meals prepared by our chefs','2025-05-25 13:07:33'),(3,'Spa','Relaxation and beauty treatments','2025-05-25 13:07:33'),(4,'Room Service','In-room dining and services','2025-05-25 13:07:33');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaints`
--

DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(255) NOT NULL,
  `complaint_text` text NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (1,'Lobby Bar Table 1','something is wrong with the food i ordered!','read','2025-07-13 02:11:24',NULL),(2,'Lobby Bar Table 1','something is wrong with the food i ordered!','unread','2025-07-13 04:20:01',NULL);
/*!40000 ALTER TABLE `complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_stay` int(11) DEFAULT 1,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discounts`
--

LOCK TABLES `discounts` WRITE;
/*!40000 ALTER TABLE `discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,2,5.00,1),(2,1,4,15.50,1),(3,1,7,18.00,1),(4,2,5,75.00,1),(5,2,7,18.00,1),(6,3,2,5.00,2),(7,3,1,12.50,1),(8,4,8,12.00,1),(9,4,4,15.50,2),(10,7,2,5.00,2),(11,7,3,22.00,1),(12,8,2,5.00,2),(13,8,7,18.00,1),(14,9,2,5.00,2),(15,9,7,18.00,1),(16,10,7,18.00,1),(17,11,7,18.00,1),(18,12,7,18.00,1),(19,13,7,18.00,1);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_label` varchar(100) NOT NULL,
  `items` longtext DEFAULT NULL,
  `status` enum('pending','completed','cancelled') CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_pending` (`status`,`id`),
  KEY `idx_created_status` (`created_at`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'Lobby Bar Table 1','[{\"id\":2,\"name\":\"Beer\",\"price\":5,\"quantity\":1},{\"id\":4,\"name\":\"Vegetarian Pasta\",\"price\":15.5,\"quantity\":1},{\"id\":7,\"name\":\"Breakfast Tray\",\"price\":18,\"quantity\":1}]','cancelled','','2025-05-28 20:37:20','2025-05-31 19:03:58',NULL),(2,'Lobby Bar Table 1','[{\"id\":5,\"name\":\"Massage\",\"price\":75,\"quantity\":1},{\"id\":7,\"name\":\"Breakfast Tray\",\"price\":18,\"quantity\":1}]','completed','i want this aspa','2025-05-31 19:54:39','2025-05-31 19:54:39','2025-05-31 21:33:41'),(3,'Lobby Bar Table 2','[{\"id\":2,\"name\":\"Beer\",\"price\":5,\"quantity\":2},{\"id\":1,\"name\":\"Cocktail\",\"price\":12.5,\"quantity\":1}]','cancelled','love this','2025-05-31 20:57:20','2025-06-06 10:40:30',NULL),(4,'Lobby Bar Table 2',NULL,'completed','asap','2025-06-08 20:59:50','2025-06-08 20:59:50','2025-07-13 02:57:48'),(7,'Lobby Bar Table 3',NULL,'completed','','2025-06-08 21:20:13','2025-06-08 21:20:13','2025-06-10 19:01:34'),(8,'Lobby Bar Table 1',NULL,'pending','','2025-07-08 16:43:30','2025-07-08 16:43:30',NULL),(9,'Lobby Bar Table 2',NULL,'completed','','2025-07-13 03:20:20','2025-07-13 03:20:20','2025-07-13 03:28:40'),(10,'Lobby Bar Table 1',NULL,'cancelled','','2025-07-13 03:21:56','2025-07-13 03:28:24',NULL),(11,'Lobby Bar Table 1',NULL,'cancelled','','2025-07-13 03:25:23','2025-07-13 03:28:08',NULL),(12,'Lobby Bar Table 1',NULL,'cancelled','','2025-07-13 03:26:14','2025-07-13 03:27:53',NULL),(13,'Lobby Bar Table 1',NULL,'cancelled','','2025-07-13 03:26:51','2025-07-13 03:27:39',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_codes`
--

DROP TABLE IF EXISTS `qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `label` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_codes`
--

LOCK TABLES `qr_codes` WRITE;
/*!40000 ALTER TABLE `qr_codes` DISABLE KEYS */;
INSERT INTO `qr_codes` VALUES (1,'Room 101','2025-05-25 13:08:02'),(2,'Room 102','2025-05-25 13:08:02'),(3,'Room 201','2025-05-25 13:08:02'),(4,'Room 202','2025-05-25 13:08:02'),(5,'Poolside Table 1','2025-05-25 13:08:02'),(6,'Poolside Table 2','2025-05-25 13:08:02'),(7,'Lobby Bar Table 1','2025-05-25 13:08:02'),(8,'Lobby Bar Table 2','2025-05-25 13:08:02'),(9,'Table 2','2025-05-25 14:14:39');
/*!40000 ALTER TABLE `qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `adults` int(11) NOT NULL,
  `children` int(11) DEFAULT 0,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(10) NOT NULL DEFAULT 'Mr',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `id_type` enum('passport','national_id','drivers_license','other') DEFAULT 'national_id',
  `institution` varchar(255) DEFAULT NULL,
  `discount_code` varchar(20) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `fk_reservation_discount` (`discount_code`),
  CONSTRAINT `fk_reservation_discount` FOREIGN KEY (`discount_code`) REFERENCES `discounts` (`code`) ON DELETE SET NULL,
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`discount_code`) REFERENCES `discounts` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,1,'Ahmad','hayjayworld@gmail.com','08140367343','2025-08-15','2025-08-16',2,0,'must be awesome','completed','2025-08-14 23:33:07','Mr','','',NULL,'national_id',NULL,NULL,0.00),(2,1,'Mrs okiki ahmad','hayjayworld@gmail.com','09876534567','2025-09-16','2025-09-17',1,0,'erp[]piuyfdfgj','cancelled','2025-09-16 13:04:15','Mrs','okiki','ahmad',NULL,'national_id',NULL,NULL,0.00),(3,1,'Mr Ahmad okikiola','hayjayworld@gmail.com','09876534567','2025-09-16','2025-09-17',2,0,'asfghjuytresdfjkl;','confirmed','2025-09-16 15:00:26','Mr','Ahmad','okikiola',NULL,'national_id',NULL,NULL,0.00);
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `can_manage_users` tinyint(1) DEFAULT 0,
  `can_manage_services` tinyint(1) DEFAULT 0,
  `can_manage_categories` tinyint(1) DEFAULT 0,
  `can_manage_orders` tinyint(1) DEFAULT 0,
  `can_manage_qr` tinyint(1) DEFAULT 0,
  `can_manage_reservations` tinyint(1) DEFAULT 0,
  `can_manage_complaints` tinyint(1) DEFAULT 0,
  `can_view_analytics` tinyint(1) DEFAULT 0,
  `can_manage_settings` tinyint(1) DEFAULT 0,
  `can_manage_discounts` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','Full system access',1,1,1,1,1,1,1,1,1,1,'2025-09-18 19:58:11'),(2,'Manager','Management level access',0,1,1,1,1,1,1,1,0,0,'2025-09-18 19:58:11'),(3,'Staff','Basic operational access',0,0,0,1,0,1,0,0,0,0,'2025-09-18 19:58:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `max_occupancy` int(11) NOT NULL,
  `amenities` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,'Room 101','Standard','Video provides a powerful way to help you prove your point. When you click Online Video, you can paste in the embed code for the video you want to add. You can also type a keyword to search online for the video that best fits your document.',20000.00,2,'Tv, WIFI',NULL,'2025-08-14 16:12:55','2025-08-14 16:12:55');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,'Cocktail','House special cocktail',12.50,'uploads/services/service_68cd647fbe345.jpeg',1,'2025-05-25 13:07:43'),(2,1,'Beer','Local craft beer',5.00,'uploads/services/service_68cd645b64366.jpeg',1,'2025-05-25 13:07:43'),(3,2,'Steak Dinner','8oz steak with sides',22.00,'uploads/services/service_68cd6491ee827.jpeg',1,'2025-05-25 13:07:43'),(4,2,'Vegetarian Pasta','Fresh pasta with seasonal vegetables',15.50,NULL,1,'2025-05-25 13:07:43'),(5,3,'Massage','60-minute full body massage',75.00,NULL,1,'2025-05-25 13:07:43'),(6,3,'Facial','Deep cleansing facial treatment',60.00,NULL,1,'2025-05-25 13:07:43'),(7,4,'Breakfast Tray','Continental breakfast delivered to your room',18.00,NULL,1,'2025-05-25 13:07:43'),(8,4,'Late Night Snack','Selection of cheeses and fruits',12.00,NULL,1,'2025-05-25 13:07:43'),(10,1,'Maltina','befrage drink',1000.00,NULL,1,'2025-09-19 13:02:34');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-19 16:16:07
