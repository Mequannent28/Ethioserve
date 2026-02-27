-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ethioserve
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `app_signaling`
--

DROP TABLE IF EXISTS `app_signaling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_signaling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `call_id` varchar(100) NOT NULL,
  `caller_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','busy','ended') DEFAULT 'pending',
  `room_id` varchar(255) DEFAULT NULL,
  `call_type` varchar(50) DEFAULT 'telemed',
  `is_video` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `call_id` (`call_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_signaling`
--

LOCK TABLES `app_signaling` WRITE;
/*!40000 ALTER TABLE `app_signaling` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_signaling` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_notifications`
--

DROP TABLE IF EXISTS `booking_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('booking_created','booking_confirmed','booking_cancelled','payment_received','seats_assigned') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `booking_notifications_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bus_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_notifications`
--

LOCK TABLES `booking_notifications` WRITE;
/*!40000 ALTER TABLE `booking_notifications` DISABLE KEYS */;
INSERT INTO `booking_notifications` VALUES (1,1,1,'booking_created','Booking Confirmed - Awaiting Approval','Your booking BUS-1748BFEB has been created successfully. Total amount: 1,100 ETB. The bus company will review and assign seats shortly.',0,'2026-02-17 19:28:52'),(2,1,5,'booking_created','New Booking Received','New booking BUS-1748BFEB received for Mequannent Gashaw Mequannent + 0 other(s). Total: 1,100 ETB. Please review and assign seats.',0,'2026-02-17 19:28:52'),(3,2,10,'booking_created','Booking Confirmed - Awaiting Approval','Your booking BUS-1F31D212 has been created successfully. Total amount: 1,100 ETB. The bus company will review and assign seats shortly.',0,'2026-02-17 19:30:59'),(4,2,5,'booking_created','New Booking Received','New booking BUS-1F31D212 received for Mequannent e Mequannent + 0 other(s). Total: 1,100 ETB. Please review and assign seats.',0,'2026-02-17 19:30:59'),(5,2,10,'seats_assigned','Seats Assigned - Booking Confirmed','Your booking #BUS-1F31D212 has been confirmed! Seat number(s): 11.',0,'2026-02-17 19:32:08'),(6,1,1,'seats_assigned','Seats Assigned - Booking Confirmed','Your booking #BUS-1748BFEB has been confirmed! Seat number(s): 21.',0,'2026-02-17 19:32:15'),(7,3,10,'booking_created','Booking Confirmed - Awaiting Approval','Your booking BUS-86ECB0A4 has been created successfully. Total amount: 1,100 ETB. The bus company will review and assign seats shortly.',0,'2026-02-19 10:39:43'),(8,3,5,'booking_created','New Booking Received','New booking BUS-86ECB0A4 received for Alexander asc Haile + 0 other(s). Total: 1,100 ETB. Please review and assign seats.',0,'2026-02-19 10:39:43'),(9,3,10,'seats_assigned','Seats Assigned - Booking Confirmed','Your booking #BUS-86ECB0A4 has been confirmed! Seat number(s): 34.',0,'2026-02-19 10:40:16'),(10,4,10,'booking_created','Booking Confirmed - Awaiting Approval','Your booking BUS-94139015 has been created successfully. Total amount: 1,100 ETB. The bus company will review and assign seats shortly.',0,'2026-02-19 11:51:29'),(11,4,5,'booking_created','New Booking Received','New booking BUS-94139015 received for Mequannent Gashaw Mequannent + 0 other(s). Total: 1,100 ETB. Please review and assign seats.',0,'2026-02-19 11:51:29'),(12,4,10,'seats_assigned','Seats Assigned - Booking Confirmed','Your booking #BUS-94139015 has been confirmed! Seat number(s): 12.',0,'2026-02-19 11:52:01'),(13,5,10,'booking_created','Booking Confirmed - Awaiting Approval','Your booking BUS-9E8E43CC has been created successfully. Total amount: 1,100 ETB. The bus company will review and assign seats shortly.',0,'2026-02-20 14:04:25'),(14,5,5,'booking_created','New Booking Received','New booking BUS-9E8E43CC received for Mequannent Gashaw Asinake + 0 other(s). Total: 1,100 ETB. Please review and assign seats.',0,'2026-02-20 14:04:25'),(15,5,10,'seats_assigned','Seats Assigned - Booking Confirmed','Your booking #BUS-9E8E43CC has been confirmed! Seat number(s): 21.',0,'2026-02-20 14:04:42');
/*!40000 ALTER TABLE `booking_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `booking_type` enum('room','table','hall') NOT NULL,
  `status` enum('pending','approved','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,10,1,'2026-02-16','01:57:00','room','cancelled','2026-02-15 09:57:12',NULL,NULL,NULL),(2,10,1,'2026-02-15','14:49:00','room','cancelled','2026-02-15 10:49:42',NULL,NULL,NULL),(3,10,1,'2026-02-25','01:42:00','room','cancelled','2026-02-15 19:39:57',NULL,NULL,NULL),(4,5,1,'2026-02-18','11:43:00','room','cancelled','2026-02-17 19:42:08',NULL,NULL,NULL);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brokers`
--

DROP TABLE IF EXISTS `brokers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brokers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `brokers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brokers`
--

LOCK TABLES `brokers` WRITE;
/*!40000 ALTER TABLE `brokers` DISABLE KEYS */;
INSERT INTO `brokers` VALUES (1,4,'ETHIO678','Connecting you to the best hotels in Addis.',0.00),(2,16,'ABEL2026','Top-rated broker connecting you to premium services.',0.00),(3,17,'MART2026','Your trusted partner for real estate and rentals.',0.00);
/*!40000 ALTER TABLE `brokers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bus_bookings`
--

DROP TABLE IF EXISTS `bus_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bus_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(20) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `travel_date` date NOT NULL,
  `seat_numbers` varchar(100) DEFAULT NULL,
  `num_passengers` int(11) DEFAULT 1,
  `passenger_names` text DEFAULT NULL,
  `passenger_phones` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `pickup_point` varchar(255) DEFAULT NULL,
  `dropoff_point` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `passenger_first_names` text DEFAULT NULL COMMENT 'JSON array of first names',
  `passenger_middle_names` text DEFAULT NULL COMMENT 'JSON array of middle names',
  `passenger_last_names` text DEFAULT NULL COMMENT 'JSON array of last names',
  `passenger_dobs` text DEFAULT NULL COMMENT 'JSON array of dates of birth',
  `passenger_genders` text DEFAULT NULL COMMENT 'JSON array of genders',
  `passenger_emails` text DEFAULT NULL COMMENT 'JSON array of emails',
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `special_requirements` text DEFAULT NULL COMMENT 'Wheelchair, dietary, etc.',
  `owner_response` text DEFAULT NULL COMMENT 'Owner response message',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `customer_id` (`customer_id`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `bus_bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bus_bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus_bookings`
--

LOCK TABLES `bus_bookings` WRITE;
/*!40000 ALTER TABLE `bus_bookings` DISABLE KEYS */;
INSERT INTO `bus_bookings` VALUES (1,'BUS-1748BFEB',1,3,'2026-02-17','21',1,'Mequannent Gashaw Mequannent','0912698553',1100.00,'Megenagna','Bahir Dar Bus Station','cash','paid','confirmed','2026-02-17 19:28:52','[\"Mequannent\"]','[\"Gashaw\"]','[\"Mequannent\"]','[\"2026-02-12\"]','[\"Male\"]','[\"mequannentgashaw12@gmail.com\"]','Mequannent Mequannent','0912698553','asd','','2026-02-17 19:32:15',NULL,NULL),(2,'BUS-1F31D212',10,3,'2026-02-17','11',1,'Mequannent e Mequannent','+251911111111',1100.00,'Megenagna','Bahir Dar Bus Station','cash','paid','confirmed','2026-02-17 19:30:59','[\"Mequannent\"]','[\"e\"]','[\"Mequannent\"]','[\"2026-02-12\"]','[\"Female\"]','[\"mequannentgashaw12@gmail.com\"]','Mequannent Mequannent','0912698553','zsd','','2026-02-17 19:32:08',NULL,NULL),(3,'BUS-86ECB0A4',10,3,'2026-02-19','34',1,'Alexander asc Haile','+251911111111',1100.00,'Megenagna','Bahir Dar Bus Station','cash','paid','confirmed','2026-02-19 10:39:42','[\"Alexander\"]','[\"asc\"]','[\"Haile\"]','[\"2026-02-18\"]','[\"Male\"]','[\"customer1@ethioserve.com\"]','Alexander Haile','1234567','Asdfgh','','2026-02-19 10:40:16',NULL,NULL),(4,'BUS-94139015',10,3,'2026-02-19','12',1,'Mequannent Gashaw Mequannent','+251911111111',1100.00,'Megenagna','Bahir Dar Bus Station','cash','paid','confirmed','2026-02-19 11:51:29','[\"Mequannent\"]','[\"Gashaw\"]','[\"Mequannent\"]','[\"2026-02-17\"]','[\"Male\"]','[\"customer1@ethioserve.com\"]','','','','','2026-02-19 11:52:01',NULL,NULL),(5,'BUS-9E8E43CC',10,3,'2026-02-20','21',1,'Mequannent Gashaw Asinake','+251911111111',1100.00,'Megenagna','Bahir Dar Bus Station','cash','paid','confirmed','2026-02-20 14:04:24','[\"Mequannent\"]','[\"Gashaw\"]','[\"Asinake\"]','[\"2026-02-12\"]','[\"Male\"]','[\"customer1@ethioserve.com\"]','asdf','1234567','zsdfg','','2026-02-20 14:04:42',NULL,NULL);
/*!40000 ALTER TABLE `bus_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bus_types`
--

DROP TABLE IF EXISTS `bus_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bus_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `seat_layout` varchar(50) DEFAULT '2-2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bus_types`
--

LOCK TABLES `bus_types` WRITE;
/*!40000 ALTER TABLE `bus_types` DISABLE KEYS */;
INSERT INTO `bus_types` VALUES (1,'Standard','Regular bus with 2-2 seating','2-2'),(2,'VIP','Luxury bus with extra legroom','2-1'),(3,'Sleeper','Overnight bus with beds','1-1'),(4,'Mini Bus','Smaller bus for short routes','2-2');
/*!40000 ALTER TABLE `bus_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buses`
--

DROP TABLE IF EXISTS `buses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `bus_type_id` int(11) DEFAULT NULL,
  `bus_number` varchar(20) NOT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `total_seats` int(11) DEFAULT 50,
  `amenities` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bus_number` (`bus_number`),
  KEY `company_id` (`company_id`),
  KEY `bus_type_id` (`bus_type_id`),
  CONSTRAINT `buses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `transport_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `buses_ibfk_2` FOREIGN KEY (`bus_type_id`) REFERENCES `bus_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buses`
--

LOCK TABLES `buses` WRITE;
/*!40000 ALTER TABLE `buses` DISABLE KEYS */;
INSERT INTO `buses` VALUES (1,1,2,'GB-001','AA-1234-A1',45,'AC,WiFi,USB Charging,Reclining Seats',1),(2,1,1,'GB-002','AA-1235-A1',50,'AC,Reclining Seats',1),(3,2,1,'WB-001','AA-2345-B1',50,'AC,Reclining Seats',1),(4,2,2,'WB-002','AA-2346-B1',40,'AC,WiFi,USB Charging',1),(5,3,2,'GN-001','AA-3456-C1',35,'AC,WiFi,USB Charging,Entertainment,Luxury Seats',1),(6,3,3,'GN-002','AA-3457-C1',24,'AC,WiFi,Beds,Privacy Curtains',1),(7,4,1,'GD-001','AA-4567-D1',55,'AC,Fans',1),(8,4,1,'GD-002','AA-4568-D1',55,'AC,Fans',1),(9,5,1,'AW-001','AA-5678-E1',50,'AC,Reclining Seats',1),(10,5,2,'AW-002','AA-5679-E1',42,'AC,WiFi,USB Charging',1);
/*!40000 ALTER TABLE `buses` ENABLE KEYS */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Breakfast'),(2,'Lunch'),(3,'Dinner'),(4,'Drinks'),(5,'Desserts'),(6,'Main Course'),(7,'Appetizer'),(8,'Dessert');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_events`
--

DROP TABLE IF EXISTS `comm_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `event_date` datetime NOT NULL,
  `category` enum('festival','business','workshop','party','other') DEFAULT 'festival',
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_events`
--

LOCK TABLES `comm_events` WRITE;
/*!40000 ALTER TABLE `comm_events` DISABLE KEYS */;
INSERT INTO `comm_events` VALUES (1,'Great Ethiopian Run 2026','Join thousands in the annual 10k race through the heart of Addis.','Meskel Square','2026-11-15 07:00:00','festival','https://images.unsplash.com/photo-1533350895246-83470656543b?w=600','2026-02-18 18:43:34'),(2,'Addis Tech Conference','Networking event for software developers and tech leaders.','Skyline Hotel','2026-03-20 09:00:00','business','https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=600','2026-02-18 18:43:34');
/*!40000 ALTER TABLE `comm_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_lost_found`
--

DROP TABLE IF EXISTS `comm_lost_found`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_lost_found` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('lost','found') NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('active','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comm_lost_found_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_lost_found`
--

LOCK TABLES `comm_lost_found` WRITE;
/*!40000 ALTER TABLE `comm_lost_found` DISABLE KEYS */;
/*!40000 ALTER TABLE `comm_lost_found` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_marketplace`
--

DROP TABLE IF EXISTS `comm_marketplace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_marketplace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('available','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comm_marketplace_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_marketplace`
--

LOCK TABLES `comm_marketplace` WRITE;
/*!40000 ALTER TABLE `comm_marketplace` DISABLE KEYS */;
INSERT INTO `comm_marketplace` VALUES (1,'iPhone 15 Pro - Almost New','Perfect condition, 256GB, battery 100%. Selling because of upgrade.',125000.00,'0911223344','https://images.unsplash.com/photo-1696446701796-da61225697cc?w=600','electronics',NULL,'available','2026-02-18 18:43:34'),(2,'Coffee Table (Solid Wood)','Handcrafted coffee table, perfect for your living room.',8500.00,'0922334455','https://images.unsplash.com/photo-1533090161767-e6ffed986c88?w=600','furniture',NULL,'available','2026-02-18 18:43:34'),(3,'1256','qwert',1234.00,'1234356','../uploads/community/market_69961bff02a1d.png','furniture',NULL,'available','2026-02-18 20:07:27'),(4,'mob','wsdfghj',12345.00,'12345678','../uploads/community/market_6996f9ebcf832.jpg','electronics',NULL,'available','2026-02-19 11:54:19');
/*!40000 ALTER TABLE `comm_marketplace` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_messages`
--

DROP TABLE IF EXISTS `comm_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `item_type` enum('marketplace','lostfound') DEFAULT 'marketplace',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `comm_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `comm_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_messages`
--

LOCK TABLES `comm_messages` WRITE;
/*!40000 ALTER TABLE `comm_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `comm_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_news`
--

DROP TABLE IF EXISTS `comm_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `category` enum('general','politics','lifestyle','business','tech') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_news`
--

LOCK TABLES `comm_news` WRITE;
/*!40000 ALTER TABLE `comm_news` DISABLE KEYS */;
INSERT INTO `comm_news` VALUES (1,'Addis Ababa Light Rail Update','The light rail service announces new evening schedules to accommodate late-night commuters.','https://images.unsplash.com/photo-1590674116497-ef667a489a2c?w=600','general','2026-02-18 18:43:34'),(2,'Startup Expo 2026','Hundreds of local tech startups are gathering at Millenium Hall this weekend.','https://images.unsplash.com/photo-1540575861501-7ad05823c9f5?w=600','business','2026-02-18 18:43:34'),(3,'New Park Opening in Bole','A new community park with free Wi-Fi and sports facilities has opened near Bole Medhanialem.','https://images.unsplash.com/photo-1585827267152-3246c48e2f04?w=600','lifestyle','2026-02-18 18:43:34');
/*!40000 ALTER TABLE `comm_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_news_comments`
--

DROP TABLE IF EXISTS `comm_news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_news_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `news_id` (`news_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comm_news_comments_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `comm_news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comm_news_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_news_comments`
--

LOCK TABLES `comm_news_comments` WRITE;
/*!40000 ALTER TABLE `comm_news_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comm_news_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comm_social_links`
--

DROP TABLE IF EXISTS `comm_social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comm_social_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `category` enum('tv','radio','news','government') DEFAULT 'news',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comm_social_links`
--

LOCK TABLES `comm_social_links` WRITE;
/*!40000 ALTER TABLE `comm_social_links` DISABLE KEYS */;
INSERT INTO `comm_social_links` VALUES (1,'EBC (Ethiopian Broadcasting Corporation)','https://www.youtube.com/embed/live_stream?channel=UC7Gf_S64eR3D9wFPyqf_pBg','fa-tv','tv'),(2,'Fana Broadcasting Corporate','https://www.youtube.com/embed/live_stream?channel=UCv-YgUa3h3oN3M_S1A6H0hA','fa-broadcast-tower','tv'),(3,'Walta TV','https://www.youtube.com/embed/live_stream?channel=UC6f-R09t6G_b7eD0-0p7Vdg','fa-newspaper','news'),(4,'Addis Media Network (AMCO)','https://www.youtube.com/embed/live_stream?channel=UC8c6R-lH58lG6MhM0mD_J4Q','fa-city','tv'),(5,'Ethiopia News Agency (ENA)','https://www.ena.et','fa-rss','news'),(6,'Sheger FM 102.1','http://shegerfm.com','fa-microphone','radio'),(7,'Afro FM 105.4','http://afro105fm.com','fa-music','radio');
/*!40000 ALTER TABLE `comm_social_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_interests`
--

DROP TABLE IF EXISTS `dating_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_interests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_interests`
--

LOCK TABLES `dating_interests` WRITE;
/*!40000 ALTER TABLE `dating_interests` DISABLE KEYS */;
INSERT INTO `dating_interests` VALUES (7,'Art'),(9,'Coffee'),(4,'Cooking'),(8,'Fitness'),(10,'Gaming'),(5,'Movies'),(2,'Music'),(6,'Sports'),(3,'Tech'),(1,'Travel');
/*!40000 ALTER TABLE `dating_interests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_matches`
--

DROP TABLE IF EXISTS `dating_matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`),
  CONSTRAINT `dating_matches_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dating_matches_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_matches`
--

LOCK TABLES `dating_matches` WRITE;
/*!40000 ALTER TABLE `dating_matches` DISABLE KEYS */;
INSERT INTO `dating_matches` VALUES (1,48,49,'2026-02-20 13:36:11'),(2,49,50,'2026-02-23 11:16:11'),(3,50,51,'2026-02-23 13:08:06');
/*!40000 ALTER TABLE `dating_matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_messages`
--

DROP TABLE IF EXISTS `dating_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `message_type` enum('text','image','location','video_call','voice','forwarded') DEFAULT 'text',
  `attachment_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_edited` tinyint(1) DEFAULT 0,
  `edited_at` timestamp NULL DEFAULT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `forwarded_from` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `idx_pinned` (`is_pinned`),
  KEY `idx_dating_pin` (`is_pinned`,`sender_id`,`receiver_id`),
  CONSTRAINT `dating_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dating_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_messages`
--

LOCK TABLES `dating_messages` WRITE;
/*!40000 ALTER TABLE `dating_messages` DISABLE KEYS */;
INSERT INTO `dating_messages` VALUES (1,10,53,'HUU&#039;','text',NULL,NULL,0,'2026-02-20 08:34:06',0,0,NULL,NULL,NULL),(2,10,48,'Helolo i wanna chat with u','text',NULL,NULL,0,'2026-02-20 08:36:53',0,0,NULL,NULL,NULL),(3,47,48,'hi selam','text',NULL,NULL,0,'2026-02-20 13:24:10',0,0,NULL,NULL,NULL),(4,47,48,'','image','uploads/dating_chat/1771593864_Test.png',NULL,0,'2026-02-20 13:24:24',0,0,NULL,NULL,NULL),(5,47,48,'this is me nice to meet u','text',NULL,NULL,0,'2026-02-20 13:24:32',0,0,NULL,NULL,NULL),(6,47,48,'helooooooooo','text',NULL,NULL,0,'2026-02-20 13:33:29',0,0,NULL,NULL,NULL),(7,49,48,'hello selam','text',NULL,NULL,1,'2026-02-20 13:36:25',0,0,NULL,NULL,NULL),(8,49,48,'hi  dave','text',NULL,NULL,1,'2026-02-20 13:36:48',0,0,NULL,NULL,NULL),(9,49,48,'hi','text',NULL,NULL,1,'2026-02-20 13:40:12',0,0,NULL,NULL,NULL),(10,48,49,'yes i am here','text',NULL,NULL,1,'2026-02-20 13:42:01',0,0,NULL,NULL,NULL),(11,48,49,'what are u doing today','text',NULL,NULL,1,'2026-02-20 13:42:25',0,0,NULL,NULL,NULL),(12,49,48,'can i call u','text',NULL,NULL,1,'2026-02-20 13:42:40',0,0,NULL,NULL,NULL),(13,48,49,'u can','text',NULL,NULL,1,'2026-02-20 13:45:18',0,0,NULL,NULL,NULL),(14,48,49,'How old are u','text',NULL,NULL,1,'2026-02-20 13:51:55',0,0,NULL,NULL,NULL),(15,49,48,'selam new','text',NULL,NULL,1,'2026-02-20 13:54:09',0,0,NULL,NULL,NULL),(16,48,49,'selam workneh endet neh','text',NULL,NULL,1,'2026-02-20 13:54:39',0,0,NULL,NULL,NULL),(17,49,48,'gonna up messi opelus','text',NULL,NULL,1,'2026-02-20 13:55:11',0,0,NULL,NULL,NULL),(18,48,49,'kkkk','text',NULL,NULL,1,'2026-02-20 13:55:21',0,0,NULL,NULL,NULL),(19,48,49,'can u send me ur pic','text',NULL,NULL,1,'2026-02-20 13:55:33',0,0,NULL,NULL,NULL),(20,48,49,'','image','uploads/dating_chat/1771595788_workneh.jpg',NULL,1,'2026-02-20 13:56:28',0,0,NULL,NULL,NULL),(21,48,49,'i like your picture','text',NULL,NULL,1,'2026-02-20 13:56:37',0,0,NULL,NULL,NULL),(22,49,48,'i love you more than i can say bro','text',NULL,NULL,1,'2026-02-20 13:58:33',0,0,NULL,NULL,NULL),(23,10,51,'qwae','text',NULL,NULL,0,'2026-02-20 14:26:20',0,0,NULL,NULL,NULL),(24,48,49,'d','text',NULL,NULL,1,'2026-02-21 06:38:13',0,0,NULL,NULL,NULL),(25,48,49,'work','text',NULL,NULL,1,'2026-02-21 06:38:21',0,0,NULL,NULL,NULL),(26,48,49,'jezbaw ]','text',NULL,NULL,1,'2026-02-21 08:54:02',0,0,NULL,NULL,NULL),(28,49,48,'where did u disaperd too bro','text',NULL,NULL,1,'2026-02-21 10:00:31',0,0,NULL,NULL,NULL),(29,74,48,'hi nice to meet u','text',NULL,NULL,0,'2026-02-21 12:52:39',0,0,NULL,NULL,NULL),(30,75,48,'Hello ma love','text',NULL,NULL,0,'2026-02-21 13:13:23',0,0,NULL,NULL,NULL),(31,75,48,'Uuhbvvbb','text',NULL,NULL,0,'2026-02-21 13:14:10',0,0,NULL,NULL,NULL),(32,75,48,'','image','uploads/dating_chat/1771679684_Screenshot_20260221-104456.jpg',NULL,0,'2026-02-21 13:14:44',0,0,NULL,NULL,NULL),(33,48,49,'hihhh','text',NULL,NULL,1,'2026-02-21 13:15:28',0,0,NULL,NULL,NULL),(34,48,49,'','image','uploads/dating_chat/1771679766_passport.jpg',NULL,1,'2026-02-21 13:16:06',0,0,NULL,NULL,NULL),(35,48,49,'how are u','text',NULL,NULL,1,'2026-02-21 13:16:15',0,0,NULL,NULL,NULL),(36,48,49,'how was ur day','text',NULL,NULL,1,'2026-02-21 13:16:23',0,0,NULL,NULL,NULL),(37,49,48,'I live u','text',NULL,NULL,1,'2026-02-21 13:16:57',0,0,NULL,NULL,NULL),(38,49,48,'I live u','text',NULL,NULL,1,'2026-02-21 13:16:59',0,0,NULL,NULL,NULL),(39,48,49,'mee too','text',NULL,NULL,1,'2026-02-21 13:17:04',0,0,NULL,NULL,NULL),(40,49,48,'Uuu','text',NULL,NULL,1,'2026-02-21 13:17:18',0,0,NULL,NULL,NULL),(41,48,49,'can u send ur image','text',NULL,NULL,1,'2026-02-21 13:17:26',0,0,NULL,NULL,NULL),(42,49,48,'','image','uploads/dating_chat/1771679852_Screenshot_20260221-104456.jpg',NULL,1,'2026-02-21 13:17:32',0,0,NULL,NULL,NULL),(43,48,49,'fuck u','text',NULL,NULL,1,'2026-02-21 13:17:39',0,0,NULL,NULL,NULL),(44,49,48,'','image','uploads/dating_chat/1771679883_Screenshot_20260212-210345.jpg',NULL,1,'2026-02-21 13:18:03',0,0,NULL,NULL,NULL),(46,48,68,'fuck u','text',NULL,NULL,0,'2026-02-21 14:15:52',0,0,NULL,NULL,NULL),(47,48,68,'','image','uploads/dating_chat/1771679852_Screenshot_20260221-104456.jpg',NULL,0,'2026-02-21 14:19:16',0,0,NULL,NULL,NULL),(48,50,49,'hi','text',NULL,NULL,0,'2026-02-23 11:16:20',0,0,NULL,NULL,NULL),(49,50,49,'yet neh','text',NULL,NULL,0,'2026-02-23 11:16:30',0,0,NULL,NULL,NULL),(50,50,49,'','image','uploads/dating_chat/1771845486_Test.png',NULL,0,'2026-02-23 11:18:06',0,0,NULL,NULL,NULL),(51,48,49,'hi','text',NULL,NULL,1,'2026-02-23 12:03:26',0,0,NULL,43,NULL),(52,48,49,'','','uploads/dating_chat/voice_1771850159.webm',NULL,1,'2026-02-23 12:35:59',0,0,NULL,NULL,NULL),(53,48,49,'','','uploads/dating_chat/voice_1771850169.webm',NULL,1,'2026-02-23 12:36:09',0,0,NULL,NULL,NULL),(54,48,49,'','voice','uploads/dating_chat/voice_1771850546.webm',NULL,1,'2026-02-23 12:42:26',0,0,NULL,NULL,NULL),(55,48,49,'','voice','uploads/dating_chat/voice_1771850555.webm',NULL,1,'2026-02-23 12:42:35',0,0,NULL,NULL,NULL),(56,48,49,'','voice','uploads/dating_chat/voice_1771850587.webm',NULL,1,'2026-02-23 12:43:07',0,0,NULL,NULL,NULL),(57,51,50,'hi','text',NULL,NULL,1,'2026-02-23 13:08:12',0,0,NULL,NULL,NULL),(58,49,48,'hi','text',NULL,NULL,1,'2026-02-23 13:10:14',0,0,NULL,NULL,NULL),(59,48,49,'','voice','uploads/dating_chat/voice_1771852216.webm',NULL,1,'2026-02-23 13:10:16',0,0,NULL,NULL,NULL),(60,50,51,'HI','text',NULL,NULL,0,'2026-02-23 14:17:59',0,0,NULL,NULL,NULL);
/*!40000 ALTER TABLE `dating_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_profiles`
--


DROP TABLE IF EXISTS `dating_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `looking_for` enum('male','female','everyone') DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(500) DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `dating_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_profiles`
--

LOCK TABLES `dating_profiles` WRITE;
/*!40000 ALTER TABLE `dating_profiles` DISABLE KEYS */;
INSERT INTO `dating_profiles` VALUES (1,1,35,'male','female','Adventurer at heart. Looking for someone to travel the world with.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400',NULL,1,'2026-02-18 18:34:25','2026-02-18 18:34:25'),(2,9,27,'female','male','Coffee lover and tech enthusiast. Let\'s talk about the future.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=400',NULL,0,'2026-02-18 18:34:25','2026-02-18 18:34:25'),(3,4,31,'male','female','Passionate about music and art. Concerts are my happy place.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400',NULL,1,'2026-02-18 18:34:25','2026-02-18 18:34:25'),(4,16,31,'female','male','Fitness junkie. Catch me at the gym or on a mountain trail.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400',NULL,0,'2026-02-18 18:34:25','2026-02-18 18:34:25'),(5,17,25,'male','female','Foodie by day, gamer by night. I make a mean lasagna.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400',NULL,0,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(6,26,25,'female','male','Adventurer at heart. Looking for someone to travel the world with.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400',NULL,0,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(7,27,24,'male','female','Coffee lover and tech enthusiast. Let\'s talk about the future.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=400',NULL,1,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(8,28,26,'female','male','Passionate about music and art. Concerts are my happy place.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400',NULL,1,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(9,29,34,'male','female','Fitness junkie. Catch me at the gym or on a mountain trail.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400',NULL,0,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(10,30,31,'female','male','Foodie by day, gamer by night. I make a mean lasagna.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400',NULL,0,'2026-02-18 18:34:26','2026-02-18 18:34:26'),(11,48,24,'female','male','Addis Ababa',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&amp;amp;fit=crop&amp;amp;w=400&amp;amp;h=600&amp;amp;q=80','Coffee, Travel, Music',0,'2026-02-20 13:58:18','2026-02-19 01:55:50'),(12,49,27,'male','female','Bole, Addis',NULL,NULL,'Bole, Addis','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&amp;amp;fit=crop&amp;amp;w=400&amp;amp;h=600&amp;amp;q=80','Coffee, Travel, Music',0,'2026-02-21 13:21:14','2026-02-19 01:55:50'),(13,50,22,'female','male','Kazanchis',NULL,NULL,'Kazanchis','https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&h=600&q=80','Coffee, Travel, Music',0,'2026-02-19 01:55:50','2026-02-19 01:55:50'),(14,51,29,'male','female','Piazza',NULL,NULL,'Piazza','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=600&q=80','Coffee, Travel, Music',0,'2026-02-19 01:55:51','2026-02-19 01:55:51'),(15,52,25,'female','male','Sarbet',NULL,NULL,'Sarbet','https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&h=600&q=80','Coffee, Travel, Music',0,'2026-02-19 01:55:51','2026-02-19 01:55:51'),(16,53,25,'male','female','Hello! I am a test account created to explore the EthioServe dating platform. I love technology and meeting new people.',NULL,NULL,'Bole, Addis Ababa','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=600&q=80','Technology, Travel, Music',0,'2026-02-19 02:01:54','2026-02-19 02:01:54'),(17,68,25,'female','male','Verified dating user.',NULL,NULL,'Addis Ababa','https://images.unsplash.com/photo-1511367461989-f85a21fda167?w=400&h=400&fit=crop',NULL,0,'2026-02-23 11:15:03','2026-02-23 11:15:03');
/*!40000 ALTER TABLE `dating_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_reports`
--

DROP TABLE IF EXISTS `dating_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `reported_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','resolved','ignored') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `reported_id` (`reported_id`),
  CONSTRAINT `dating_reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dating_reports_ibfk_2` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_reports`
--

LOCK TABLES `dating_reports` WRITE;
/*!40000 ALTER TABLE `dating_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `dating_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_swipes`
--

DROP TABLE IF EXISTS `dating_swipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_swipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `swiper_id` int(11) NOT NULL,
  `swiped_id` int(11) NOT NULL,
  `type` enum('like','dislike','superlike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_swipe` (`swiper_id`,`swiped_id`),
  KEY `swiped_id` (`swiped_id`),
  CONSTRAINT `dating_swipes_ibfk_1` FOREIGN KEY (`swiper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dating_swipes_ibfk_2` FOREIGN KEY (`swiped_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_swipes`
--

LOCK TABLES `dating_swipes` WRITE;
/*!40000 ALTER TABLE `dating_swipes` DISABLE KEYS */;
INSERT INTO `dating_swipes` VALUES (1,48,49,'like','2026-02-20 08:37:57'),(2,48,4,'superlike','2026-02-20 08:38:07'),(3,48,29,'like','2026-02-20 08:38:10'),(4,49,28,'like','2026-02-20 08:39:06'),(5,48,53,'like','2026-02-20 08:40:35'),(6,48,27,'like','2026-02-20 08:40:58'),(7,48,17,'dislike','2026-02-20 08:43:01'),(8,48,1,'dislike','2026-02-20 08:43:02'),(9,48,51,'dislike','2026-02-20 08:43:02'),(10,49,50,'like','2026-02-20 13:35:57'),(11,49,26,'like','2026-02-20 13:35:59'),(12,49,52,'like','2026-02-20 13:35:59'),(13,49,16,'like','2026-02-20 13:36:00'),(14,49,9,'like','2026-02-20 13:36:01'),(15,49,30,'like','2026-02-20 13:36:02'),(16,49,48,'like','2026-02-20 13:36:11'),(17,50,51,'like','2026-02-23 11:16:06'),(18,50,49,'like','2026-02-23 11:16:11'),(19,51,9,'like','2026-02-23 13:08:04'),(20,51,50,'like','2026-02-23 13:08:06'),(21,51,48,'like','2026-02-23 13:08:06'),(22,51,68,'like','2026-02-23 13:08:06'),(23,50,53,'like','2026-02-23 14:17:47'),(24,50,27,'like','2026-02-23 14:17:48'),(25,50,4,'like','2026-02-23 14:17:48'),(26,50,1,'like','2026-02-23 14:17:49'),(27,50,17,'like','2026-02-23 14:17:49'),(28,50,29,'like','2026-02-23 14:17:50');
/*!40000 ALTER TABLE `dating_swipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dating_user_interests`
--

DROP TABLE IF EXISTS `dating_user_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dating_user_interests` (
  `user_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`interest_id`),
  KEY `interest_id` (`interest_id`),
  CONSTRAINT `dating_user_interests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dating_user_interests_ibfk_2` FOREIGN KEY (`interest_id`) REFERENCES `dating_interests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dating_user_interests`
--

LOCK TABLES `dating_user_interests` WRITE;
/*!40000 ALTER TABLE `dating_user_interests` DISABLE KEYS */;
/*!40000 ALTER TABLE `dating_user_interests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_messages`
--

DROP TABLE IF EXISTS `doctor_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('customer','doctor') NOT NULL DEFAULT 'customer',
  `provider_id` int(11) NOT NULL COMMENT 'health_providers.id (doctor)',
  `customer_id` int(11) NOT NULL COMMENT 'users.id (patient)',
  `message` text DEFAULT NULL,
  `message_type` enum('text','image','file','location','voice') DEFAULT 'text',
  `attachment_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_provider_customer` (`provider_id`,`customer_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_messages`
--

LOCK TABLES `doctor_messages` WRITE;
/*!40000 ALTER TABLE `doctor_messages` DISABLE KEYS */;
INSERT INTO `doctor_messages` VALUES (1,57,'customer',1,57,'Hello Doctor, I need a consultation.','text',NULL,0,'2026-02-19 20:40:18'),(2,1,'doctor',1,57,'Your appointment has been received! The doctor will review your request and contact you soon. Please keep an eye on this chat for updates.','text',NULL,0,'2026-02-19 20:41:11'),(3,1,'doctor',1,57,'Your appointment has been received! The doctor will review your request and contact you soon. Please keep an eye on this chat for updates.','text',NULL,0,'2026-02-19 20:43:42'),(4,7,'doctor',7,10,'Your appointment has been received! The doctor will review your request and contact you soon. Please keep an eye on this chat for updates.','text',NULL,1,'2026-02-19 20:44:25'),(5,10,'customer',7,10,'Hello Doctore can u commincate with me','text',NULL,1,'2026-02-19 20:44:48'),(6,7,'doctor',7,10,'Your appointment has been received! The doctor will review your request and contact you soon. Please keep an eye on this chat for updates.','text',NULL,1,'2026-02-19 20:46:57'),(7,10,'customer',7,10,'asdfg','text',NULL,1,'2026-02-19 20:47:04'),(8,7,'doctor',7,10,'Your appointment status has been updated to: Confirmed','text',NULL,1,'2026-02-19 21:01:27'),(9,7,'doctor',7,10,'Your appointment status has been updated to: Confirmed','text',NULL,1,'2026-02-19 21:01:31'),(10,7,'doctor',7,10,'yes i am here','text',NULL,1,'2026-02-19 21:01:53'),(11,10,'customer',7,10,'whre is your loccation','text',NULL,1,'2026-02-19 21:05:40'),(12,7,'doctor',7,10,'https://www.google.com/maps?q=9.04062086929914,38.822954390197374','location',NULL,1,'2026-02-19 21:20:38'),(13,10,'customer',7,10,'','file','uploads/chat/1771536084_ticket_BUS-94139015.html',1,'2026-02-19 21:21:24'),(14,10,'customer',7,10,'hello dr how are u','text',NULL,1,'2026-02-20 14:01:09'),(15,7,'doctor',7,10,'doctor','text',NULL,1,'2026-02-20 14:01:09'),(16,10,'customer',7,10,'','file','uploads/chat/1771596099_chrome_proxy.exe',1,'2026-02-20 14:01:39'),(17,10,'customer',7,10,'https://www.google.com/maps?q=8.967017945440423,38.839266387744615','location',NULL,1,'2026-02-20 14:01:53'),(18,10,'customer',7,10,'https://www.google.com/maps?q=8.967017945440423,38.839266387744615','location',NULL,1,'2026-02-20 14:01:59'),(19,48,'customer',8,48,'hi','text',NULL,0,'2026-02-21 13:31:25');
/*!40000 ALTER TABLE `doctor_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `education_resources`
--

DROP TABLE IF EXISTS `education_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `education_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `type` enum('textbook','teacher_guide','video') DEFAULT 'textbook',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_id` varchar(50) DEFAULT NULL,
  `pages` int(11) DEFAULT 0,
  `units` int(11) DEFAULT 0,
  `edition` varchar(50) DEFAULT '2023',
  `status` enum('active','draft','archived') DEFAULT 'active',
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `education_resources`
--

LOCK TABLES `education_resources` WRITE;
/*!40000 ALTER TABLE `education_resources` DISABLE KEYS */;
INSERT INTO `education_resources` VALUES (1,1,'Amharic','textbook','Grade 1 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 1 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:01','2026-02-20 08:03:01'),(2,1,'English','textbook','Grade 1 English Student Textbook','Official Ethiopian New Curriculum Grade 1 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:01','2026-02-20 08:03:01'),(3,1,'Mathematics','textbook','Grade 1 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 1 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:01','2026-02-20 08:03:01'),(4,1,'General Science','textbook','Grade 1 General Science Student Textbook','Official Ethiopian New Curriculum Grade 1 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:01','2026-02-20 08:03:01'),(5,1,'Environmental Science','textbook','Grade 1 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 1 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(6,1,'HPE','textbook','Grade 1 HPE Student Textbook','Official Ethiopian New Curriculum Grade 1 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(7,1,'Visual and Performing Art','textbook','Grade 1 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 1 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade1/Grade%201_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(8,2,'Amharic','textbook','Grade 2 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 2 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(9,2,'English','textbook','Grade 2 English Student Textbook','Official Ethiopian New Curriculum Grade 2 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(10,2,'Mathematics','textbook','Grade 2 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 2 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(11,2,'General Science','textbook','Grade 2 General Science Student Textbook','Official Ethiopian New Curriculum Grade 2 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(12,2,'Environmental Science','textbook','Grade 2 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 2 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(13,2,'HPE','textbook','Grade 2 HPE Student Textbook','Official Ethiopian New Curriculum Grade 2 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(14,2,'Visual and Performing Art','textbook','Grade 2 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 2 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade2/Grade%202_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(15,3,'Amharic','textbook','Grade 3 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 3 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(16,3,'English','textbook','Grade 3 English Student Textbook','Official Ethiopian New Curriculum Grade 3 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(17,3,'Mathematics','textbook','Grade 3 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 3 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(18,3,'General Science','textbook','Grade 3 General Science Student Textbook','Official Ethiopian New Curriculum Grade 3 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:02','2026-02-20 08:03:02'),(19,3,'Environmental Science','textbook','Grade 3 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 3 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(20,3,'HPE','textbook','Grade 3 HPE Student Textbook','Official Ethiopian New Curriculum Grade 3 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(21,3,'Visual and Performing Art','textbook','Grade 3 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 3 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade3/Grade%203_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(22,4,'Amharic','textbook','Grade 4 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 4 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(23,4,'English','textbook','Grade 4 English Student Textbook','Official Ethiopian New Curriculum Grade 4 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(24,4,'Mathematics','textbook','Grade 4 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 4 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(25,4,'General Science','textbook','Grade 4 General Science Student Textbook','Official Ethiopian New Curriculum Grade 4 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(26,4,'Environmental Science','textbook','Grade 4 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 4 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(27,4,'HPE','textbook','Grade 4 HPE Student Textbook','Official Ethiopian New Curriculum Grade 4 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(28,4,'Visual and Performing Art','textbook','Grade 4 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 4 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade4/Grade%204_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(29,5,'Amharic','textbook','Grade 5 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 5 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(30,5,'English','textbook','Grade 5 English Student Textbook','Official Ethiopian New Curriculum Grade 5 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(31,5,'Mathematics','textbook','Grade 5 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 5 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(32,5,'General Science','textbook','Grade 5 General Science Student Textbook','Official Ethiopian New Curriculum Grade 5 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(33,5,'Environmental Science','textbook','Grade 5 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 5 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(34,5,'Social Studies','textbook','Grade 5 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 5 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(35,5,'Civics','textbook','Grade 5 Civics Student Textbook','Official Ethiopian New Curriculum Grade 5 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(36,5,'HPE','textbook','Grade 5 HPE Student Textbook','Official Ethiopian New Curriculum Grade 5 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(37,5,'Visual and Performing Art','textbook','Grade 5 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 5 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade5/Grade%205_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:03','2026-02-20 08:03:03'),(38,6,'Amharic','textbook','Grade 6 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 6 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(39,6,'English','textbook','Grade 6 English Student Textbook','Official Ethiopian New Curriculum Grade 6 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(40,6,'Mathematics','textbook','Grade 6 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 6 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(41,6,'General Science','textbook','Grade 6 General Science Student Textbook','Official Ethiopian New Curriculum Grade 6 General Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(42,6,'Environmental Science','textbook','Grade 6 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 6 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(43,6,'Social Studies','textbook','Grade 6 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 6 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(44,6,'Civics','textbook','Grade 6 Civics Student Textbook','Official Ethiopian New Curriculum Grade 6 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(45,6,'HPE','textbook','Grade 6 HPE Student Textbook','Official Ethiopian New Curriculum Grade 6 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(46,6,'Visual and Performing Art','textbook','Grade 6 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 6 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade6/Grade%206_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(47,7,'Amharic','textbook','Grade 7 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 7 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(48,7,'English','textbook','Grade 7 English Student Textbook','Official Ethiopian New Curriculum Grade 7 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(49,7,'Mathematics','textbook','Grade 7 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 7 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(50,7,'Environmental Science','textbook','Grade 7 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 7 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(51,7,'Social Studies','textbook','Grade 7 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 7 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(52,7,'Civics','textbook','Grade 7 Civics Student Textbook','Official Ethiopian New Curriculum Grade 7 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(53,7,'HPE','textbook','Grade 7 HPE Student Textbook','Official Ethiopian New Curriculum Grade 7 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(54,7,'Visual and Performing Art','textbook','Grade 7 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 7 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade7/Grade%207_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(55,8,'Amharic','textbook','Grade 8 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 8 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(56,8,'English','textbook','Grade 8 English Student Textbook','Official Ethiopian New Curriculum Grade 8 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(57,8,'Mathematics','textbook','Grade 8 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 8 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:04','2026-02-20 08:03:04'),(58,8,'Environmental Science','textbook','Grade 8 Environmental Science Student Textbook','Official Ethiopian New Curriculum Grade 8 Environmental Science Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Environmental%20Science_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(59,8,'Social Studies','textbook','Grade 8 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 8 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(60,8,'Civics','textbook','Grade 8 Civics Student Textbook','Official Ethiopian New Curriculum Grade 8 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(61,8,'HPE','textbook','Grade 8 HPE Student Textbook','Official Ethiopian New Curriculum Grade 8 HPE Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_HPE_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(62,8,'Visual and Performing Art','textbook','Grade 8 Visual and Performing Art Student Textbook','Official Ethiopian New Curriculum Grade 8 Visual and Performing Art Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade8/Grade%208_Visual%20and%20Performing%20Art_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(63,9,'Amharic','textbook','Grade 9 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 9 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(64,9,'English','textbook','Grade 9 English Student Textbook','Official Ethiopian New Curriculum Grade 9 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(65,9,'Mathematics','textbook','Grade 9 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 9 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(66,9,'Biology','textbook','Grade 9 Biology Student Textbook','Official Ethiopian New Curriculum Grade 9 Biology Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Biology_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(67,9,'Physics','textbook','Grade 9 Physics Student Textbook','Official Ethiopian New Curriculum Grade 9 Physics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Physics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(68,9,'Chemistry','textbook','Grade 9 Chemistry Student Textbook','Official Ethiopian New Curriculum Grade 9 Chemistry Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Chemistry_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(69,9,'Social Studies','textbook','Grade 9 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 9 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(70,9,'Civics','textbook','Grade 9 Civics Student Textbook','Official Ethiopian New Curriculum Grade 9 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(71,9,'ICT','textbook','Grade 9 ICT Student Textbook','Official Ethiopian New Curriculum Grade 9 ICT Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade9/Grade%209_ICT_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(72,10,'Amharic','textbook','Grade 10 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 10 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(73,10,'English','textbook','Grade 10 English Student Textbook','Official Ethiopian New Curriculum Grade 10 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(74,10,'Mathematics','textbook','Grade 10 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 10 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(75,10,'Biology','textbook','Grade 10 Biology Student Textbook','Official Ethiopian New Curriculum Grade 10 Biology Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Biology_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(76,10,'Physics','textbook','Grade 10 Physics Student Textbook','Official Ethiopian New Curriculum Grade 10 Physics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Physics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(77,10,'Chemistry','textbook','Grade 10 Chemistry Student Textbook','Official Ethiopian New Curriculum Grade 10 Chemistry Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Chemistry_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:05','2026-02-20 08:03:05'),(78,10,'Social Studies','textbook','Grade 10 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 10 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(79,10,'Civics','textbook','Grade 10 Civics Student Textbook','Official Ethiopian New Curriculum Grade 10 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(80,10,'ICT','textbook','Grade 10 ICT Student Textbook','Official Ethiopian New Curriculum Grade 10 ICT Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade10/Grade%2010_ICT_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(81,11,'Amharic','textbook','Grade 11 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 11 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(82,11,'English','textbook','Grade 11 English Student Textbook','Official Ethiopian New Curriculum Grade 11 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(83,11,'Mathematics','textbook','Grade 11 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 11 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(84,11,'Biology','textbook','Grade 11 Biology Student Textbook','Official Ethiopian New Curriculum Grade 11 Biology Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Biology_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(85,11,'Physics','textbook','Grade 11 Physics Student Textbook','Official Ethiopian New Curriculum Grade 11 Physics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Physics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(86,11,'Chemistry','textbook','Grade 11 Chemistry Student Textbook','Official Ethiopian New Curriculum Grade 11 Chemistry Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Chemistry_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(87,11,'Social Studies','textbook','Grade 11 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 11 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(88,11,'Civics','textbook','Grade 11 Civics Student Textbook','Official Ethiopian New Curriculum Grade 11 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(89,11,'ICT','textbook','Grade 11 ICT Student Textbook','Official Ethiopian New Curriculum Grade 11 ICT Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade11/Grade%2011_ICT_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(90,12,'Amharic','textbook','Grade 12 Amharic Student Textbook','Official Ethiopian New Curriculum Grade 12 Amharic Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Amharic_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(91,12,'English','textbook','Grade 12 English Student Textbook','Official Ethiopian New Curriculum Grade 12 English Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_English_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(92,12,'Mathematics','textbook','Grade 12 Mathematics Student Textbook','Official Ethiopian New Curriculum Grade 12 Mathematics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Mathematics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(93,12,'Biology','textbook','Grade 12 Biology Student Textbook','Official Ethiopian New Curriculum Grade 12 Biology Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Biology_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(94,12,'Physics','textbook','Grade 12 Physics Student Textbook','Official Ethiopian New Curriculum Grade 12 Physics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Physics_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(95,12,'Chemistry','textbook','Grade 12 Chemistry Student Textbook','Official Ethiopian New Curriculum Grade 12 Chemistry Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Chemistry_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:06','2026-02-20 08:03:06'),(96,12,'Social Studies','textbook','Grade 12 Social Studies Student Textbook','Official Ethiopian New Curriculum Grade 12 Social Studies Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Social%20Study_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:07','2026-02-20 08:03:07'),(97,12,'Civics','textbook','Grade 12 Civics Student Textbook','Official Ethiopian New Curriculum Grade 12 Civics Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_Citizenship%20Education_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:07','2026-02-20 08:03:07'),(98,12,'ICT','textbook','Grade 12 ICT Student Textbook','Official Ethiopian New Curriculum Grade 12 ICT Student Textbook. Direct High-Quality PDF.','https://www.anrseb.gov.et/wp-content/uploads/New_Text_Books/Grade12/Grade%2012_ICT_Textbook.pdf',NULL,NULL,0,0,'2023','active',0,'2026-02-20 08:03:07','2026-02-20 08:03:07');
/*!40000 ALTER TABLE `education_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_materials`
--

DROP TABLE IF EXISTS `exchange_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exchange_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `condition` enum('new','used') DEFAULT 'used',
  `image_url` varchar(500) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('available','sold','hidden','pending') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `exchange_materials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_materials`
--
LOCK TABLES `exchange_materials` WRITE;
/*!40000 ALTER TABLE `exchange_materials` DISABLE KEYS */;
INSERT INTO `exchange_materials` VALUES (1,10,'Mobile','iPhone 15 Pro Max - 256GB','Brand new iPhone 15 Pro Max, Natural Titanium. Never opened, full warranty.',125000.00,'new','https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80','Bole, Addis Ababa','0911223344','available','2026-02-18 18:05:57','2026-02-18 18:05:57'),(2,11,'Computers','MacBook Pro M2 - 16GB RAM','Slightly used MacBook Pro with M2 chip. Excellent condition, no scratches.',180000.00,'used','https://images.unsplash.com/photo-1517336714460-4c98882c3fae?auto=format&fit=crop&w=800&q=80','Haya Hulet, Addis Ababa','0922334455','available','2026-02-18 18:05:57','2026-02-18 18:05:57'),(3,12,'Electronics','Sony PS5 Console with 2 Controllers','PlayStation 5 Disc Version. Includes 2 DualSense controllers and 3 games.',65000.00,'new','https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80','Piazza, Addis Ababa','0933445566','available','2026-02-18 18:05:57','2026-02-18 18:05:57'),(4,10,'Furniture','Modern Leather Sofa Set','Elegant 3-seater leather sofa. Dark brown color, very comfortable.',45000.00,'used','https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80','Sarbet, Addis Ababa','0911223344','available','2026-02-18 18:05:58','2026-02-18 18:05:58'),(5,24,'Vehicles','Toyota Vitz 2015','Very clean Toyota Vitz, 2015 model. Low mileage, fuel efficient.',1200000.00,'used','https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&w=800&q=80','Gurd Shola, Addis Ababa','0944556677','available','2026-02-18 18:05:58','2026-02-18 18:05:58');
/*!40000 ALTER TABLE `exchange_materials` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `experiences`
--

DROP TABLE IF EXISTS `experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `experiences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experiences`
--

LOCK TABLES `experiences` WRITE;
/*!40000 ALTER TABLE `experiences` DISABLE KEYS */;
/*!40000 ALTER TABLE `experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flight_bookings`
--

DROP TABLE IF EXISTS `flight_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flight_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `passenger_name` varchar(100) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `pnr_code` varchar(10) DEFAULT NULL,
  `trip_type` enum('one_way','round_trip') DEFAULT 'one_way',
  `seat_number` varchar(10) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pnr_code` (`pnr_code`),
  KEY `customer_id` (`customer_id`),
  KEY `flight_id` (`flight_id`),
  CONSTRAINT `flight_bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flight_bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flight_bookings`
--

LOCK TABLES `flight_bookings` WRITE;
/*!40000 ALTER TABLE `flight_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `flight_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flights`
--

DROP TABLE IF EXISTS `flights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `airline` varchar(100) NOT NULL,
  `flight_number` varchar(20) DEFAULT NULL,
  `origin` varchar(100) DEFAULT 'Addis Ababa (ADD)',
  `destination` varchar(100) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_seats` int(11) DEFAULT 50,
  `status` enum('scheduled','delayed','cancelled','completed') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_number` (`flight_number`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flights`
--

LOCK TABLES `flights` WRITE;
/*!40000 ALTER TABLE `flights` DISABLE KEYS */;
INSERT INTO `flights` VALUES (1,'Ethiopian Airlines','ET302','Addis Ababa (ADD)','Nairobi (NBO)','2026-02-17 12:28:01','2026-02-17 14:28:01',8500.00,50,'scheduled','2026-02-15 09:28:01'),(2,'Ethiopian Airlines','ET500','Addis Ababa (ADD)','Washington D.C. (IAD)','2026-02-18 12:28:01','2026-02-19 02:28:01',45000.00,50,'scheduled','2026-02-15 09:28:01'),(3,'Emirates','EK723','Addis Ababa (ADD)','Dubai (DXB)','2026-02-16 12:28:01','2026-02-16 16:28:01',22000.00,50,'scheduled','2026-02-15 09:28:01'),(4,'Ethiopian Airlines','ET700','Addis Ababa (ADD)','London (LHR)','2026-02-20 12:28:01','2026-02-20 20:28:01',35000.00,50,'scheduled','2026-02-15 09:28:01'),(5,'Lufthansa','LH591','Addis Ababa (ADD)','Frankfurt (FRA)','2026-02-17 13:20:40','2026-02-17 16:20:40',35000.00,50,'scheduled','2026-02-15 10:20:40'),(6,'Turkish Airlines','TK606','Addis Ababa (ADD)','Istanbul (IST)','2026-02-17 13:20:40','2026-02-17 16:20:40',28000.00,50,'scheduled','2026-02-15 10:20:40'),(7,'Qatar Airways','QR1428','Addis Ababa (ADD)','Doha (DOH)','2026-02-17 13:20:40','2026-02-17 16:20:40',25000.00,50,'scheduled','2026-02-15 10:20:40');
/*!40000 ALTER TABLE `flights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freelance_services`
--

DROP TABLE IF EXISTS `freelance_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `freelance_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `price_type` enum('fixed','hourly','negotiable') DEFAULT 'fixed',
  `price` decimal(12,2) DEFAULT NULL,
  `delivery_days` int(11) DEFAULT 7,
  `image_url` text DEFAULT NULL,
  `portfolio_images` text DEFAULT NULL,
  `status` enum('active','paused','deleted') DEFAULT 'active',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `freelance_services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freelance_services`
--

LOCK TABLES `freelance_services` WRITE;
/*!40000 ALTER TABLE `freelance_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `freelance_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_ambulance_requests`
--

DROP TABLE IF EXISTS `health_ambulance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_ambulance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `gps_lat` decimal(10,8) DEFAULT NULL,
  `gps_lng` decimal(11,8) DEFAULT NULL,
  `emergency_type` varchar(100) DEFAULT NULL,
  `status` enum('searching','dispatched','arrived','completed','cancelled') DEFAULT 'searching',
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `health_ambulance_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_ambulance_requests_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `health_providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_ambulance_requests`
--
LOCK TABLES `health_ambulance_requests` WRITE;
/*!40000 ALTER TABLE `health_ambulance_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_ambulance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_appointments`
--

DROP TABLE IF EXISTS `health_appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `appointment_type` enum('in_person','virtual','lab_test') DEFAULT 'in_person',
  `scheduled_at` datetime NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `health_appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_appointments_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `health_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_appointments`
--
LOCK TABLES `health_appointments` WRITE;
/*!40000 ALTER TABLE `health_appointments` DISABLE KEYS */;
INSERT INTO `health_appointments` VALUES (1,57,1,'virtual','2026-02-26 13:44:00','pending','sdf',NULL,NULL,'2026-02-19 20:41:11'),(2,57,1,'in_person','2026-02-19 23:46:00','pending','sdf',NULL,NULL,'2026-02-19 20:43:42'),(3,10,7,'in_person','2026-03-05 14:44:00','confirmed','asdfvb',NULL,NULL,'2026-02-19 20:44:25'),(4,10,7,'in_person','2026-02-21 23:46:00','confirmed','s',NULL,NULL,'2026-02-19 20:46:57');
/*!40000 ALTER TABLE `health_appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_pharmacy_orders`
--

DROP TABLE IF EXISTS `health_pharmacy_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_pharmacy_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pharmacy_id` int(11) NOT NULL,
  `items` text NOT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `prescription_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `pharmacy_id` (`pharmacy_id`),
  CONSTRAINT `health_pharmacy_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_pharmacy_orders_ibfk_2` FOREIGN KEY (`pharmacy_id`) REFERENCES `health_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_pharmacy_orders`
--

LOCK TABLES `health_pharmacy_orders` WRITE;
/*!40000 ALTER TABLE `health_pharmacy_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_pharmacy_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_providers`
--

DROP TABLE IF EXISTS `health_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('doctor','pharmacy','laboratory','ambulance_service') NOT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `image_url` varchar(500) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `availability_hours` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `specialty_id` (`specialty_id`),
  KEY `fk_hp_user` (`user_id`),
  CONSTRAINT `fk_hp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `health_providers_ibfk_1` FOREIGN KEY (`specialty_id`) REFERENCES `health_specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `health_providers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_providers`
--

LOCK TABLES `health_providers` WRITE;
/*!40000 ALTER TABLE `health_providers` DISABLE KEYS */;
INSERT INTO `health_providers` VALUES (1,58,'Dr. Abebe Molla','doctor',1,'Experienced GP with over 10 years in clinical practice.','Bole Medhanealem','0911000111',4.90,'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400',1,NULL,'2026-02-18 18:25:57'),(2,NULL,'Dr. Tigist Hailu','doctor',2,'Dedicated pediatrician specializing in neonatal care.','Kazanchis','0911222333',4.80,'https://images.unsplash.com/photo-1559839734-2b71f15367ed?w=400',1,NULL,'2026-02-18 18:25:57'),(3,NULL,'St. Paul Pharmacy','pharmacy',NULL,'Your reliable neighborhood pharmacy with 24/7 delivery.','CMC','0922444555',4.70,'https://images.unsplash.com/photo-1586015555751-63bb77f4322a?w=400',1,NULL,'2026-02-18 18:25:57'),(4,NULL,'International Clinical Lab','laboratory',NULL,'Accredited laboratory with digital results within 24 hours.','Piassa','0933555666',4.60,'https://images.unsplash.com/photo-1579152276503-363d596395ec?w=400',1,NULL,'2026-02-18 18:25:58'),(5,NULL,'Ethio Mercy Ambulance','ambulance_service',NULL,'Emergency transport with advanced life support equipment.','Addis Ababa','912',5.00,'https://images.unsplash.com/photo-1587748803976-293046bc9625?w=400',1,NULL,'2026-02-18 18:25:58'),(6,NULL,'Kenema Pharmacy','pharmacy',NULL,'State-owned reliable pharmacy with affordable pricing.','Megenagna','0944111222',4.50,'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?w=400',1,NULL,'2026-02-18 18:25:58'),(7,57,'Dr. Dawit Girma','doctor',3,'Specialist in heart conditions and echocardiography.','Bole','0911777888',4.90,'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=400',1,NULL,'2026-02-18 18:25:58'),(8,NULL,'Dr. Yalemwork Belay','doctor',1,'Experienced General Practitioner dedicated to providing compassionate and comprehensive healthcare in Addis Ababa.','Bole, Addis Ababa','0911000000',4.90,'../uploads/doctors/yalemwork.jpg',1,'Mon-Fri: 8:00 AM - 5:00 PM','2026-02-18 18:59:57');
/*!40000 ALTER TABLE `health_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_records`
--

DROP TABLE IF EXISTS `health_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `record_type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `health_providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_records`
--

LOCK TABLES `health_records` WRITE;
/*!40000 ALTER TABLE `health_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_reviews`
--

DROP TABLE IF EXISTS `health_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `health_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `health_reviews_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `health_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_reviews`
--

LOCK TABLES `health_reviews` WRITE;
/*!40000 ALTER TABLE `health_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_specialties`
--

DROP TABLE IF EXISTS `health_specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_specialties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_specialties`
--

LOCK TABLES `health_specialties` WRITE;
/*!40000 ALTER TABLE `health_specialties` DISABLE KEYS */;
INSERT INTO `health_specialties` VALUES (1,'General Medicine','fas fa-user-md','Primary healthcare and general checkups.'),(2,'Pediatrics','fas fa-child','Specialized care for infants, children, and adolescents.'),(3,'Cardiology','fas fa-heartbeat','Heart and blood vessel related conditions.'),(4,'Dermatology','fas fa-allergies','Skin, hair, and nail health.'),(5,'Dentistry','fas fa-tooth','Dental and oral health care.'),(6,'Neurology','fas fa-brain','Brain and nervous system specialists.');
/*!40000 ALTER TABLE `health_specialties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_service_bookings`
--

DROP TABLE IF EXISTS `home_service_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_service_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `option_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `service_address` text NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `provider_id` (`provider_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `home_service_bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `home_service_bookings_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `home_service_providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `home_service_bookings_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `home_service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_service_bookings`
--

LOCK TABLES `home_service_bookings` WRITE;
/*!40000 ALTER TABLE `home_service_bookings` DISABLE KEYS */;
INSERT INTO `home_service_bookings` VALUES (1,10,NULL,6,30,'2026-02-23 09:27:00','pending','12345','1234567','ASD',2000.00,'2026-02-20 06:24:10');
/*!40000 ALTER TABLE `home_service_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_service_categories`
--

DROP TABLE IF EXISTS `home_service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_service_categories`
--

LOCK TABLES `home_service_categories` WRITE;
/*!40000 ALTER TABLE `home_service_categories` DISABLE KEYS */;
INSERT INTO `home_service_categories` VALUES (1,'Plumber','fas fa-faucet','Pipe repair, leak fixing, and water system maintenance.','2026-02-18 18:21:22'),(2,'Electrician','fas fa-plug','Wiring, lighting, and electrical repairs.','2026-02-18 18:21:22'),(3,'Carpenter','fas fa-hammer','Furniture making and wood repairs.','2026-02-18 18:21:22'),(4,'Cleaning','fas fa-broom','Professional home and office cleaning.','2026-02-18 18:21:22'),(5,'Painter','fas fa-paint-roller','Interior and exterior painting services.','2026-02-18 18:21:23'),(6,'Appliance Repair','fas fa-tools','Fixing refrigerators, TVs, and more.','2026-02-18 18:21:23');
/*!40000 ALTER TABLE `home_service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_service_options`
--

DROP TABLE IF EXISTS `home_service_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_service_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `home_service_options_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `home_service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_service_options`
--

LOCK TABLES `home_service_options` WRITE;
/*!40000 ALTER TABLE `home_service_options` DISABLE KEYS */;
INSERT INTO `home_service_options` VALUES (1,1,'Pipe Repair',NULL,500.00),(2,1,'Leak Fixing',NULL,400.00),(3,1,'Faucet Installation',NULL,300.00),(4,1,'Bathroom Plumbing',NULL,1200.00),(5,1,'Water Heater Repair',NULL,1500.00),(6,2,'Wiring',NULL,2000.00),(7,2,'Switch/Socket Installation',NULL,200.00),(8,2,'Lighting Setup',NULL,800.00),(9,2,'Electrical Troubleshooting',NULL,600.00),(10,2,'Generator Maintenance',NULL,2500.00),(11,3,'Furniture Making',NULL,5000.00),(12,3,'Door/Window Repair',NULL,1000.00),(13,3,'Custom Cabinets',NULL,8000.00),(14,3,'Wood Polishing',NULL,1200.00),(15,3,'Interior Woodwork',NULL,4000.00),(16,4,'Deep Cleaning',NULL,3000.00),(17,4,'Office Cleaning',NULL,5000.00),(18,4,'Carpet Cleaning',NULL,1500.00),(19,4,'Upholstery Cleaning',NULL,2000.00),(20,4,'Post-Renovation Cleaning',NULL,4500.00),(21,5,'Interior Painting',NULL,5000.00),(22,5,'Exterior Painting',NULL,7000.00),(23,5,'Wall Preparation',NULL,1000.00),(24,5,'Texture Painting',NULL,3500.00),(25,5,'Decorative Painting',NULL,4500.00),(26,6,'Refrigerator Repair',NULL,1200.00),(27,6,'Washing Machine Repair',NULL,1000.00),(28,6,'Microwave Repair',NULL,500.00),(29,6,'Air Conditioner Service',NULL,1500.00),(30,6,'TV Repair',NULL,2000.00);
/*!40000 ALTER TABLE `home_service_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_service_providers`
--

DROP TABLE IF EXISTS `home_service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_service_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_image` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `experience_years` int(11) DEFAULT 1,
  `degree_type` varchar(255) DEFAULT NULL,
  `certification` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `service_areas` text DEFAULT NULL,
  `availability_status` enum('available','busy','offline') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `home_service_providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_service_providers`
--

LOCK TABLES `home_service_providers` WRITE;
/*!40000 ALTER TABLE `home_service_providers` DISABLE KEYS */;
INSERT INTO `home_service_providers` VALUES (1,NULL,1,'Expert professional with over 5 years of experience in electrical systems, plumbing, and home repairs. Dedicated to providing high-quality service and customer satisfaction.',4.80,0,5,'B.Sc. in Electrical Engineering','Certified Master Electrician','Addis Ababa','[\"Bole\", \"Kazanchis\", \"CMC\"]','available','2026-02-18 18:21:31'),(2,NULL,2,'Expert plumber specialized in modern bathroom fittings and leak repairs.',0.00,0,8,'Advanced Diploma in Civil Engineering','National Plumbing Certificate Tier 1','Arada, Addis Ababa','[\"Piazza\", \"Arat Kilo\", \"Meskel Square\"]','available','2026-02-20 06:28:46'),(3,NULL,70,'Expert electrician with certification in industrial wiring and home safety systems.',0.00,0,10,'B.Sc. in Electrical Engineering','Certified Senior Electrician','Bole, Addis Ababa',NULL,'available','2026-02-20 06:38:12'),(4,NULL,71,'Specialized in modern plumbing, leak detection, and sanitary installations.',0.00,0,7,'Diploma in Sanitary Engineering','Grade A Plumbing License','Arada, Addis Ababa',NULL,'available','2026-02-20 06:38:13'),(5,NULL,72,'Master carpenter for custom furniture, cabinet installations, and wood repairs.',0.00,0,15,'TVET Level 4 Carpentry','Master Artisan Certificate','Kolfe Keranio, Addis Ababa',NULL,'available','2026-02-20 06:38:13'),(6,NULL,73,'Professional technician for refrigerators, washing machines, and electronics.',0.00,0,6,'Electronics Technology Certificate','Certified Appliance Specialist','Yeka, Addis Ababa',NULL,'available','2026-02-20 06:38:14');
/*!40000 ALTER TABLE `home_service_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_service_reviews`
--

DROP TABLE IF EXISTS `home_service_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_service_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `customer_id` (`customer_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `home_service_reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `home_service_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `home_service_reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `home_service_reviews_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `home_service_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_service_reviews`
--

LOCK TABLES `home_service_reviews` WRITE;
/*!40000 ALTER TABLE `home_service_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `home_service_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hotels`
--

DROP TABLE IF EXISTS `hotels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `cuisine_type` varchar(100) DEFAULT NULL,
  `opening_hours` varchar(100) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `delivery_time` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hotels`
--

LOCK TABLES `hotels` WRITE;
/*!40000 ALTER TABLE `hotels` DISABLE KEYS */;
INSERT INTO `hotels` VALUES (1,2,'Hilton Addis Ababa','Classic luxury with authentic Ethiopian hospitality.','Menelik II Avenue, Addis Ababa','Ethiopian & International','24/7',4.8,500.00,'30-45 min','https://images.unsplash.com/photo-1541014741259-df529411b96a?auto=format&fit=crop&w=1200&q=80','+251115170000','hilton@ethiopia.com','approved'),(2,3,'Sheraton Addis','State-of-the-art sanctuary in the heart of Ethiopia.','Taitu Street, Addis Ababa','Fine Dining','06:00 AM - 11:00 PM',4.9,800.00,'40-60 min','https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1200&q=80','+251115171717','sheraton@ethiopia.com','approved'),(3,13,'Lucy International Hotel','Premium hospitality and dining experience in the heart of Ethiopia.','Bole Road, Addis Ababa','Ethiopian & Continental','06:00 AM - 11:00 PM',4.6,200.00,'25-40 min','https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(4,14,'Getfam Hotel','Premium hospitality and dining experience in the heart of Ethiopia.','Kazanchis, Addis Ababa','Ethiopian & International','24/7',4.4,200.00,'25-40 min','https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(5,15,'Eliana Hotel','Premium hospitality and dining experience in the heart of Ethiopia.','Piazza, Addis Ababa','Traditional Ethiopian','07:00 AM - 10:00 PM',4.3,200.00,'25-40 min','https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(6,19,'Sheraton Addis, a Luxury Collection Hotel','Nestled on a hilltop overlooking the city, Sheraton Addis is a landmark of luxury and elegance in the heart of Ethiopia.','Taitu Street, Addis Ababa','International & Ethiopian Fusion',NULL,4.7,0.00,NULL,'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80','+251115171717','sheraton@ethiopia.com','approved'),(7,19,'Ethiopian Skylight Hotel','The largest hotel in Ethiopia, located just minutes from Bole International Airport, offering world-class luxury and comfort.','Bole Airport Area, Addis Ababa','Continental & Traditional',NULL,4.8,0.00,NULL,'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(8,19,'Hyatt Regency Addis Ababa','Modern and stylish hotel located on Meskel Square, offering sophisticated dining and the highest service standards.','Meskel Square, Addis Ababa','International',NULL,4.8,0.00,NULL,'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(9,19,'Radisson Blu Hotel, Addis Ababa','A premier upscale hotel located in the city center, perfect for business travelers and luxury seekers.','Kazanchis, Addis Ababa','Mediterranean & Local',NULL,4.7,0.00,NULL,'https://images.unsplash.com/photo-1517840901100-8179e982acb7?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(10,20,'Yod Abyssinia Traditional Restaurant','Iconic cultural restaurant offering an unforgettable Ethiopian dining experience with traditional music, dance performances, and authentic cuisine since 1997.','Bole Medhanialem, Addis Ababa','Traditional Ethiopian','11:00 AM - 11:00 PM',4.8,300.00,'40-55 min','https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(11,20,'2000 Habesha Cultural Restaurant','A vibrant cultural hub serving authentic Ethiopian dishes with live traditional performances, known for its colorful Mesob dining experience.','Bole Road, Addis Ababa','Traditional Ethiopian','10:00 AM - 12:00 AM',4.7,350.00,'35-50 min','https://images.unsplash.com/photo-1541510965749-fdd893598387?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(12,20,'Lucy Restaurant & Lounge','Named after the famous fossil, Lucy offers a sophisticated blend of Ethiopian and international cuisine in an elegant upscale setting near Bole.','Atlas Area, Bole, Addis Ababa','Ethiopian & International','07:00 AM - 11:00 PM',4.6,400.00,'30-45 min','https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(13,20,'Kategna Traditional Food','Famous for the best kategna (toasted injera with kibbeh and berbere) and traditional breakfast in the city. A beloved local favorite.','Multiple Locations, Addis Ababa','Traditional Ethiopian','06:30 AM - 10:00 PM',4.9,200.00,'25-35 min','https://images.unsplash.com/photo-1547928576-96541f94f997?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(14,20,'Totot Cultural Restaurant','Specializing in Southern Ethiopian flavors and cultural shows. Famous for its premium Kitfo and Gurage specialties.','Haya Hulet, Addis Ababa','Southern Ethiopian / Gurage','10:00 AM - 11:00 PM',4.7,350.00,'35-50 min','https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(15,20,'Four Sisters Restaurant','Located in historic Gondar, this legendary restaurant is run by four real sisters serving family recipes passed down for generations.','Piazza Area, Gondar','Northern Ethiopian / Amhara','08:00 AM - 9:30 PM',4.9,250.00,'30-40 min','https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(16,20,'Makush Art Gallery & Restaurant','A stunning restaurant surrounded by Ethiopia\'s largest private art collection. Fine dining meets culture with curated Ethiopian fusion cuisine.','Bole Atlas, Addis Ababa','Ethiopian Fusion & Fine Dining','11:30 AM - 10:30 PM',4.8,500.00,'45-60 min','https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(17,20,'Ben Abeba Restaurant','An architectural marvel perched on a cliff in Lalibela, offering panoramic views and an eclectic mix of Ethiopian and Scottish cuisine.','Hilltop, Lalibela','Ethiopian & Scottish Fusion','07:30 AM - 9:00 PM',4.8,300.00,'30-45 min','https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(18,20,'Dashen Traditional Restaurant','Named after Ethiopia\'s highest mountain, Dashen serves hearty traditional Ethiopian food in a warm, family-style mesob dining setup.','Kazanchis, Addis Ababa','Traditional Ethiopian','09:00 AM - 10:30 PM',4.5,250.00,'30-45 min','https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(19,20,'Habesha Restaurant','One of Addis Ababa\'s most popular dining spots for both locals and visitors, offering generous portions and an authentic atmosphere.','Bole Rwanda, Addis Ababa','Traditional Ethiopian','10:00 AM - 11:00 PM',4.6,280.00,'30-45 min','https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(20,20,'Saro-Maria Hotel Restaurant','An upscale hotel restaurant offering a refined Ethiopian dining experience with international flair, located in the heart of Bole.','Bole Sub-City, Addis Ababa','Ethiopian & Continental','06:00 AM - 11:30 PM',4.7,450.00,'40-55 min','https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(21,20,'Tomoca Coffee','Ethiopia\'s most legendary coffee house since 1953. The birthplace of premium Ethiopian coffee culture, serving the finest Arabica beans.','Wavel Street, Piazza, Addis Ababa','Coffee & Light Bites','06:00 AM - 8:00 PM',4.9,50.00,'15-25 min','https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(22,20,'Addis in Dar Restaurant','A modern Ethiopian restaurant bringing Addis Ababa flavors with a contemporary twist. Popular among young professionals and food enthusiasts.','Sarbet, Addis Ababa','Modern Ethiopian','11:00 AM - 11:00 PM',4.5,300.00,'30-40 min','https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(23,20,'Lime Tree Caf├⌐ & Restaurant','A beloved Addis Ababa gem known for its garden seating, healthy options, and a perfect fusion of Ethiopian and European cuisines.','Bole Medhanialem, Addis Ababa','Ethiopian-European Fusion','07:00 AM - 10:00 PM',4.6,350.00,'35-50 min','https://images.unsplash.com/photo-1537047902294-62a40c20a6ae?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(24,20,'Castelli\'s Italian-Ethiopian Restaurant','An Addis Ababa institution since 1948, Castelli\'s blends Italian and Ethiopian gastronomy in a vintage, elegantly decorated dining hall.','Piazza, Churchill Avenue, Addis Ababa','Italian-Ethiopian','12:00 PM - 10:00 PM',4.7,600.00,'45-60 min','https://images.unsplash.com/photo-1550966871-3ed3cdb51f3a?auto=format&fit=crop&w=800&q=80','+251115170000','info@ethioserve.com','approved'),(25,32,'Haile Resort','Luxury resort by the lake.','Hawassa','International','24/7',4.8,1000.00,'45 min',NULL,'+251462200000','haile@hailehotels.com','approved'),(26,33,'Kuriftu Resort','Premium spa and resort.','Bishoftu','Traditional & European','06:00-22:00',4.9,1500.00,'60 min',NULL,'+251114331000','info@kurifturesort.com','approved'),(27,34,'Skylight Hotel','Modern luxury near airport.','Bole, Addis Ababa','Chinese, Ethio, International','24/7',4.7,2000.00,'30 min',NULL,'+251116618060','info@ethiopianskylighthotel.com','approved'),(28,35,'Elilly International','Centrally located luxury.','Kazanchis, Addis Ababa','Buffet','07:00-23:00',4.5,800.00,'40 min',NULL,'+251115587777','info@elillyhotel.com','approved'),(29,36,'Jupiter International','Business hotel in Kazanchis.','Kazanchis','Grill & Bar','24/7',4.3,500.00,'35 min',NULL,'+251115527333','info@jupiterhotel.com','approved'),(30,37,'Golden Tulip','International brand in Bole.','Bole','Fine Dining','24/7',4.6,1200.00,'25 min',NULL,'+251116170740','info@goldentuliptana.com','approved');
/*!40000 ALTER TABLE `hotels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','shortlisted','interviewed','hired','rejected') DEFAULT 'pending',
  `interview_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_application` (`job_id`,`applicant_id`),
  KEY `applicant_id` (`applicant_id`),
  CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_applications`
--

LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
INSERT INTO `job_applications` VALUES (1,1,48,'ghj',NULL,'pending',NULL,NULL,'2026-02-23 14:14:52','2026-02-23 14:14:52');
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_categories`
--

DROP TABLE IF EXISTS `job_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-briefcase',
  `color` varchar(30) DEFAULT '#1565C0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_categories`
--

LOCK TABLES `job_categories` WRITE;
/*!40000 ALTER TABLE `job_categories` DISABLE KEYS */;
INSERT INTO `job_categories` VALUES (1,'Technology & IT','fas fa-laptop-code','#1565C0','2026-02-19 20:19:50'),(2,'Design & Creative','fas fa-palette','#AD1457','2026-02-19 20:19:50'),(3,'Marketing & Sales','fas fa-bullhorn','#E65100','2026-02-19 20:19:50'),(4,'Healthcare','fas fa-heartbeat','#2E7D32','2026-02-19 20:19:51'),(5,'Education & Training','fas fa-graduation-cap','#6A1B9A','2026-02-19 20:19:51'),(6,'Construction & Labor','fas fa-hard-hat','#F57F17','2026-02-19 20:19:51'),(7,'Finance & Accounting','fas fa-chart-line','#00695C','2026-02-19 20:19:51'),(8,'Legal & Admin','fas fa-balance-scale','#4527A0','2026-02-19 20:19:51'),(9,'Writing & Translation','fas fa-pen','#0277BD','2026-02-19 20:19:51'),(10,'Customer Service','fas fa-headset','#558B2F','2026-02-19 20:19:51'),(11,'Engineering','fas fa-cogs','#455A64','2026-02-19 20:19:51'),(12,'Other','fas fa-briefcase','#78909C','2026-02-19 20:19:51');
/*!40000 ALTER TABLE `job_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_companies`
--

DROP TABLE IF EXISTS `job_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `logo_url` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `job_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_companies`
--

LOCK TABLES `job_companies` WRITE;
/*!40000 ALTER TABLE `job_companies` DISABLE KEYS */;
INSERT INTO `job_companies` VALUES (1,56,'Red Cloud ICT Solution',NULL,NULL,NULL,'Addis Ababa','Technology',NULL,1,0.00,'2026-02-19 20:24:06'),(2,48,'ertyu',NULL,NULL,NULL,'ertyu','',NULL,0,0.00,'2026-02-23 14:13:52');
/*!40000 ALTER TABLE `job_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_experience`
--

DROP TABLE IF EXISTS `job_experience`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_experience` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `job_title` varchar(200) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_id` (`profile_id`),
  CONSTRAINT `job_experience_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `job_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_experience`
--

LOCK TABLES `job_experience` WRITE;
/*!40000 ALTER TABLE `job_experience` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_experience` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_listings`
--

DROP TABLE IF EXISTS `job_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_listings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `posted_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `job_type` enum('full_time','part_time','contract','internship','freelance','daily_labor') NOT NULL DEFAULT 'full_time',
  `category_id` int(11) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `is_remote` tinyint(1) DEFAULT 0,
  `salary_min` decimal(12,2) DEFAULT NULL,
  `salary_max` decimal(12,2) DEFAULT NULL,
  `salary_period` enum('hour','day','week','month','project') DEFAULT 'month',
  `currency` varchar(10) DEFAULT 'ETB',
  `skills_required` text DEFAULT NULL,
  `experience_level` enum('entry','mid','senior','any') DEFAULT 'any',
  `education_level` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','closed','draft') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `posted_by` (`posted_by`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `job_listings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `job_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_listings_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_listings_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_listings`
--

LOCK TABLES `job_listings` WRITE;
/*!40000 ALTER TABLE `job_listings` DISABLE KEYS */;
INSERT INTO `job_listings` VALUES (1,2,48,'sdf','asdfgnm','wdfgh','full_time',10,'ertyu',0,2345.00,22345.00,'month','ETB','java','any',NULL,'2026-02-24','active',1,'2026-02-23 14:13:53');
/*!40000 ALTER TABLE `job_listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_messages`
--

DROP TABLE IF EXISTS `job_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','file','location') DEFAULT 'text',
  `attachment_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `job_messages_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_messages`
--

LOCK TABLES `job_messages` WRITE;
/*!40000 ALTER TABLE `job_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_notifications`
--

DROP TABLE IF EXISTS `job_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `job_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_notifications`
--

LOCK TABLES `job_notifications` WRITE;
/*!40000 ALTER TABLE `job_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_profiles`
--

DROP TABLE IF EXISTS `job_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `education` text DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `cv_url` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `availability` enum('immediately','within_week','within_month','not_looking') DEFAULT 'immediately',
  `expected_salary` varchar(100) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `job_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_profiles`
--

LOCK TABLES `job_profiles` WRITE;
/*!40000 ALTER TABLE `job_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_reviews`
--

DROP TABLE IF EXISTS `job_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `type` enum('company_review','freelancer_review') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `reviewee_id` (`reviewee_id`),
  CONSTRAINT `job_reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_reviews_ibfk_2` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_reviews`
--

LOCK TABLES `job_reviews` WRITE;
/*!40000 ALTER TABLE `job_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_saved`
--

DROP TABLE IF EXISTS `job_saved`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_saved` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_save` (`user_id`,`job_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `job_saved_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_saved_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_saved`
--

LOCK TABLES `job_saved` WRITE;
/*!40000 ALTER TABLE `job_saved` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_saved` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `listings`
--

DROP TABLE IF EXISTS `listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `listings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('house_rent','car_rent','bus_ticket','home_service') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `area_sqm` int(11) DEFAULT 0,
  `features` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `status` enum('available','taken','pending') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listings`
--

LOCK TABLES `listings` WRITE;
/*!40000 ALTER TABLE `listings` DISABLE KEYS */;
INSERT INTO `listings` VALUES (1,4,'house_rent','Luxury Villa in Bole','Stunning 4-bedroom villa with private garden, modern kitchen, marble floors, and 24/7 security. Perfect for families looking for premium living in the heart of Bole.',55000.00,'Bole, Addis Ababa','https://images.unsplash.com/photo-1580587767526-cf3660a9dd38?auto=format&fit=crop&w=800&q=80','https://www.youtube.com/embed/dQw4w9WgXcQ',4,5,250,'Garden,Parking,Security,Swimming Pool','+251911223344','Abebe Kebede','available','2026-02-15 09:28:01'),(2,4,'house_rent','Modern 2BR Apartment CMC','Newly built apartment with open-plan living, balcony views, elevator access, and underground parking. Walking distance to shopping centers.',22000.00,'CMC, Addis Ababa','https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80',NULL,2,2,120,'Elevator,Parking,Balcony','+251922334455','Meron Tadesse','available','2026-02-15 09:28:01'),(3,4,'house_rent','Cozy Studio near Meskel Square','Furnished studio apartment ideal for young professionals. Includes Wi-Fi, water heater, and a small kitchenette.',12000.00,'Meskel Square, Addis Ababa','https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80',NULL,1,1,45,'Furnished,Wi-Fi,Water Heater','+251933445566','Sara Hailu','available','2026-02-15 09:28:01'),(4,4,'house_rent','Penthouse with City View','Luxurious penthouse on the 12th floor with panoramic city views, 3 bedrooms, walk-in closets, and a private rooftop terrace.',85000.00,'Kazanchis, Addis Ababa','https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80','https://www.youtube.com/embed/dQw4w9WgXcQ',3,3,200,'Rooftop Terrace,City View,Walk-in Closet,Gym','+251944556677','Daniel Girma','available','2026-02-15 09:28:01'),(5,4,'house_rent','Family Home in Ayat','Spacious family home with a large compound, servant quarters, and ample parking. Quiet neighborhood near schools.',35000.00,'Ayat, Addis Ababa','https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80',NULL,3,2,180,'Compound,Servant Quarter,Parking,Near Schools','+251955667788','Tigist Mulugeta','available','2026-02-15 09:28:01'),(6,4,'car_rent','Toyota Land Cruiser V8','Full options Land Cruiser, perfect for field trips and long-distance travel. Leather interior, AC, GPS navigation.',3500.00,'Meskel Square, Addis Ababa','https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80','https://www.youtube.com/embed/dQw4w9WgXcQ',0,0,0,'GPS,Leather Seats,AC,4WD','+251911001100','Yonas Motors','available','2026-02-15 09:28:01'),(7,4,'car_rent','Toyota Corolla 2023','Brand new Corolla with automatic transmission, fuel efficient and comfortable for city driving.',1800.00,'Bole, Addis Ababa','https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=800&q=80',NULL,0,0,0,'Automatic,AC,Bluetooth,USB','+251922112233','Star Rent','available','2026-02-15 09:28:01'),(8,4,'car_rent','Hyundai Santa Fe','SUV perfect for family outings and weekend getaways. Spacious interior with 7 seats.',2500.00,'Piassa, Addis Ababa','https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=800&q=80',NULL,0,0,0,'7 Seats,AC,Bluetooth,Cruise Control','+251933223344','Addis Car Hire','available','2026-02-15 09:28:01'),(9,4,'car_rent','Mercedes-Benz E-Class','Premium luxury sedan for business meetings, weddings, and VIP transport. Chauffeur available.',5000.00,'Kazanchis, Addis Ababa','https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&w=800&q=80','https://www.youtube.com/embed/dQw4w9WgXcQ',0,0,0,'Luxury,Chauffeur Available,Leather,Premium Sound','+251944334455','Royal Motors','available','2026-02-15 09:28:01'),(10,4,'house_rent','Modern Apt CMC','2 BR Furnished Apartment.',30000.00,'CMC',NULL,NULL,0,0,0,'Elevator, Parking, WiFi',NULL,NULL,'available','2026-02-15 10:20:40'),(11,4,'house_rent','Cosy Studio Piassa','Studio for young professionals.',15000.00,'Piassa',NULL,NULL,0,0,0,'Near Metro, Safe Area',NULL,NULL,'available','2026-02-15 10:20:40'),(12,4,'house_rent','Big Mansion Ayat','6 BR with large compound.',85000.00,'Ayat',NULL,NULL,0,0,0,'Compound, Guard House',NULL,NULL,'available','2026-02-15 10:20:40'),(13,4,'house_rent','Office Space Kazanchis','150 SQM premium office.',45000.00,'Kazanchis',NULL,NULL,0,0,0,'AC, Power Backup',NULL,NULL,'available','2026-02-15 10:20:40'),(14,4,'house_rent','G+1 Home Lebu','3 BR family home.',40000.00,'Lebu',NULL,NULL,0,0,0,'Quiet Area',NULL,NULL,'available','2026-02-15 10:20:40'),(15,4,'car_rent','Mercedes E-Class','Luxury for weddings/VIP.',6000.00,'Addis Ababa',NULL,NULL,0,0,0,'Chauffeur, Black',NULL,NULL,'available','2026-02-15 10:20:40'),(16,4,'car_rent','Nissan Patrol','Off road beast.',4500.00,'Addis Ababa',NULL,NULL,0,0,0,'Tough, 4WD',NULL,NULL,'available','2026-02-15 10:20:40'),(17,4,'car_rent','Suzuki Dzire','Budget city transport.',1200.00,'Addis Ababa',NULL,NULL,0,0,0,'Manual, Efficiency',NULL,NULL,'available','2026-02-15 10:20:40');
/*!40000 ALTER TABLE `listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lms_answers`
--

DROP TABLE IF EXISTS `lms_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `lms_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `lms_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_answers`
--

LOCK TABLES `lms_answers` WRITE;
/*!40000 ALTER TABLE `lms_answers` DISABLE KEYS */;
INSERT INTO `lms_answers` VALUES (1,3,1,NULL,0),(2,3,2,NULL,0),(3,3,3,NULL,0),(4,3,4,NULL,0),(5,3,5,NULL,0),(6,4,1,NULL,0),(7,4,2,NULL,0),(8,4,3,NULL,0),(9,4,4,NULL,0),(10,4,5,NULL,0),(11,5,1,'A',0),(12,5,2,'B',0),(13,5,3,'C',1),(14,5,4,'B',1),(15,5,5,'D',1),(16,7,1,'A',0),(17,7,2,'B',0),(18,7,3,'C',1),(19,7,4,'B',1),(20,7,5,'D',1),(21,10,36,'C',1),(22,10,37,'B',1),(23,10,38,'D',0),(24,10,39,'A',0),(25,10,40,'B',0),(26,12,11,'B',1),(27,12,12,'A',0),(28,12,13,'B',1),(29,12,14,'C',1),(30,12,15,'C',1),(31,13,11,'B',1),(32,13,12,'A',0),(33,13,13,'B',1),(34,13,14,'C',1),(35,13,15,'C',1),(36,14,76,NULL,0),(37,14,77,NULL,0),(38,14,78,NULL,0),(39,14,79,NULL,0),(40,14,80,NULL,0),(41,16,36,'A',0),(42,16,37,'A',0),(43,16,38,'A',0),(44,16,39,'A',0),(45,16,40,'A',0),(46,17,36,'A',0),(47,17,37,'A',0),(48,17,38,'A',0),(49,17,39,'A',0),(50,17,40,'A',0),(51,18,36,NULL,0),(52,18,37,NULL,0),(53,18,38,NULL,0),(54,18,39,NULL,0),(55,18,40,NULL,0),(56,19,36,NULL,0),(57,19,37,NULL,0),(58,19,38,NULL,0),(59,19,39,NULL,0),(60,19,40,NULL,0),(61,22,36,NULL,0),(62,22,37,NULL,0),(63,22,38,NULL,0),(64,22,39,NULL,0),(65,22,40,NULL,0);
/*!40000 ALTER TABLE `lms_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lms_attempts`
--

DROP TABLE IF EXISTS `lms_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_points` int(11) DEFAULT 0,
  `earned_points` int(11) DEFAULT 0,
  `status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_seconds` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `lms_attempts_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `lms_exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_attempts`
--

LOCK TABLES `lms_attempts` WRITE;
/*!40000 ALTER TABLE `lms_attempts` DISABLE KEYS */;
INSERT INTO `lms_attempts` VALUES (1,47,1,0.00,0,0,'in_progress','2026-02-15 18:20:42',NULL,0),(2,47,1,0.00,0,0,'in_progress','2026-02-15 18:21:55',NULL,0),(3,47,1,0.00,5,0,'completed','2026-02-15 18:22:21','2026-02-15 18:22:46',20),(4,47,1,0.00,5,0,'completed','2026-02-15 18:22:55','2026-02-15 18:22:57',24),(5,47,1,60.00,5,3,'completed','2026-02-15 18:22:59','2026-02-15 18:24:03',59),(6,47,1,0.00,0,0,'in_progress','2026-02-15 18:24:51',NULL,0),(7,47,1,60.00,5,3,'completed','2026-02-15 18:24:56','2026-02-15 18:24:57',63),(8,47,1,0.00,0,0,'in_progress','2026-02-15 18:24:59',NULL,0),(9,47,1,0.00,0,0,'in_progress','2026-02-15 18:25:07',NULL,0),(10,10,8,40.00,5,2,'completed','2026-02-15 18:25:13','2026-02-15 18:27:17',116),(11,47,24,0.00,0,0,'in_progress','2026-02-15 18:25:26',NULL,0),(12,47,3,80.00,5,4,'completed','2026-02-15 18:53:58','2026-02-15 18:57:03',174),(13,47,3,80.00,5,4,'completed','2026-02-15 18:57:03','2026-02-15 18:57:03',175),(14,1,16,0.00,5,0,'completed','2026-02-15 19:50:03','2026-02-15 19:50:37',22),(15,1,16,0.00,0,0,'in_progress','2026-02-15 19:50:38',NULL,0),(16,1,8,0.00,5,0,'completed','2026-02-15 19:51:21','2026-02-15 19:51:45',17),(17,1,8,0.00,5,0,'completed','2026-02-15 19:51:48','2026-02-15 19:51:48',19),(18,1,8,0.00,5,0,'completed','2026-02-15 19:51:58','2026-02-15 19:52:40',30),(19,1,8,0.00,5,0,'completed','2026-02-15 19:52:42','2026-02-15 19:52:52',33),(20,1,8,0.00,0,0,'in_progress','2026-02-15 19:52:54',NULL,0),(21,1,8,0.00,0,0,'in_progress','2026-02-15 19:53:09',NULL,0),(22,1,8,0.00,5,0,'completed','2026-02-15 19:57:04','2026-02-15 19:57:08',3),(23,47,33,0.00,0,0,'in_progress','2026-02-17 20:02:05',NULL,0),(24,10,1,0.00,0,0,'in_progress','2026-02-19 11:56:28',NULL,0),(25,47,7,0.00,0,0,'in_progress','2026-02-20 13:15:15',NULL,0);
/*!40000 ALTER TABLE `lms_attempts` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `lms_exams`
--
DROP TABLE IF EXISTS `lms_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `chapter` int(11) NOT NULL DEFAULT 1,
  `chapter_title` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `pass_percentage` int(11) DEFAULT 50,
  `total_questions` int(11) DEFAULT 10,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `status` enum('active','draft','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_exams`
--

LOCK TABLES `lms_exams` WRITE;
/*!40000 ALTER TABLE `lms_exams` DISABLE KEYS */;
INSERT INTO `lms_exams` VALUES (1,1,'Mathematics',1,'Numbers 1-20','Grade 1 Mathematics ΓÇö Chapter 1: Numbers 1-20','Test your knowledge of Numbers 1-20 from Grade 1 Mathematics.',15,50,5,'easy','active','2026-02-15 18:14:34'),(2,1,'Mathematics',2,'Addition and Subtraction','Grade 1 Mathematics ΓÇö Chapter 2: Addition and Subtraction','Test your knowledge of Addition and Subtraction from Grade 1 Mathematics.',15,50,5,'easy','active','2026-02-15 18:14:35'),(3,2,'Mathematics',1,'Numbers up to 100','Grade 2 Mathematics ΓÇö Chapter 1: Numbers up to 100','Test your knowledge of Numbers up to 100 from Grade 2 Mathematics.',15,50,5,'easy','active','2026-02-15 18:14:35'),(4,3,'Mathematics',1,'Multiplication Basics','Grade 3 Mathematics ΓÇö Chapter 1: Multiplication Basics','Test your knowledge of Multiplication Basics from Grade 3 Mathematics.',15,50,5,'easy','active','2026-02-15 18:14:36'),(5,4,'Mathematics',1,'Long Division','Grade 4 Mathematics ΓÇö Chapter 1: Long Division','Test your knowledge of Long Division from Grade 4 Mathematics.',15,50,5,'medium','active','2026-02-15 18:14:36'),(6,5,'Mathematics',1,'Fractions','Grade 5 Mathematics ΓÇö Chapter 1: Fractions','Test your knowledge of Fractions from Grade 5 Mathematics.',15,50,5,'medium','active','2026-02-15 18:14:36'),(7,6,'Mathematics',1,'Decimals and Percentages','Grade 6 Mathematics ΓÇö Chapter 1: Decimals and Percentages','Test your knowledge of Decimals and Percentages from Grade 6 Mathematics.',15,50,5,'medium','active','2026-02-15 18:14:37'),(8,7,'Mathematics',1,'Algebra - Linear Equations','Grade 7 Mathematics ΓÇö Chapter 1: Algebra - Linear Equations','Test your knowledge of Algebra - Linear Equations from Grade 7 Mathematics.',15,50,5,'medium','active','2026-02-15 18:14:37'),(9,8,'Mathematics',1,'Geometry - Shapes & Angles','Grade 8 Mathematics ΓÇö Chapter 1: Geometry - Shapes & Angles','Test your knowledge of Geometry - Shapes & Angles from Grade 8 Mathematics.',15,50,5,'medium','active','2026-02-15 18:14:37'),(10,9,'Mathematics',1,'Quadratic Equations','Grade 9 Mathematics ΓÇö Chapter 1: Quadratic Equations','Test your knowledge of Quadratic Equations from Grade 9 Mathematics.',15,50,5,'hard','active','2026-02-15 18:14:38'),(11,10,'Mathematics',1,'Trigonometry','Grade 10 Mathematics ΓÇö Chapter 1: Trigonometry','Test your knowledge of Trigonometry from Grade 10 Mathematics.',15,50,5,'hard','active','2026-02-15 18:14:38'),(12,11,'Mathematics',1,'Calculus - Limits & Derivatives','Grade 11 Mathematics ΓÇö Chapter 1: Calculus - Limits & Derivatives','Test your knowledge of Calculus - Limits & Derivatives from Grade 11 Mathematics.',15,50,5,'hard','active','2026-02-15 18:14:38'),(13,12,'Mathematics',1,'Probability & Statistics','Grade 12 Mathematics ΓÇö Chapter 1: Probability & Statistics','Test your knowledge of Probability & Statistics from Grade 12 Mathematics.',15,50,5,'hard','active','2026-02-15 18:14:38'),(14,1,'English',1,'Alphabet & Phonics','Grade 1 English ΓÇö Chapter 1: Alphabet & Phonics','Test your knowledge of Alphabet & Phonics from Grade 1 English.',15,50,5,'easy','active','2026-02-15 18:14:39'),(15,2,'English',1,'Alphabet & Phonics','Grade 2 English ΓÇö Chapter 1: Alphabet & Phonics','Test your knowledge of Alphabet & Phonics from Grade 2 English.',15,50,5,'easy','active','2026-02-15 18:14:39'),(16,3,'English',1,'Alphabet & Phonics','Grade 3 English ΓÇö Chapter 1: Alphabet & Phonics','Test your knowledge of Alphabet & Phonics from Grade 3 English.',15,50,5,'easy','active','2026-02-15 18:14:39'),(17,4,'English',1,'Alphabet & Phonics','Grade 4 English ΓÇö Chapter 1: Alphabet & Phonics','Test your knowledge of Alphabet & Phonics from Grade 4 English.',15,50,5,'easy','active','2026-02-15 18:14:39'),(18,5,'English',1,'Parts of Speech','Grade 5 English ΓÇö Chapter 1: Parts of Speech','Test your knowledge of Parts of Speech from Grade 5 English.',15,50,5,'medium','active','2026-02-15 18:14:40'),(19,6,'English',1,'Parts of Speech','Grade 6 English ΓÇö Chapter 1: Parts of Speech','Test your knowledge of Parts of Speech from Grade 6 English.',15,50,5,'medium','active','2026-02-15 18:14:40'),(20,7,'English',1,'Parts of Speech','Grade 7 English ΓÇö Chapter 1: Parts of Speech','Test your knowledge of Parts of Speech from Grade 7 English.',15,50,5,'medium','active','2026-02-15 18:14:40'),(21,8,'English',1,'Parts of Speech','Grade 8 English ΓÇö Chapter 1: Parts of Speech','Test your knowledge of Parts of Speech from Grade 8 English.',15,50,5,'medium','active','2026-02-15 18:14:40'),(22,9,'English',1,'Essay Writing & Grammar','Grade 9 English ΓÇö Chapter 1: Essay Writing & Grammar','Test your knowledge of Essay Writing & Grammar from Grade 9 English.',15,50,5,'medium','active','2026-02-15 18:14:41'),(23,10,'English',1,'Essay Writing & Grammar','Grade 10 English ΓÇö Chapter 1: Essay Writing & Grammar','Test your knowledge of Essay Writing & Grammar from Grade 10 English.',15,50,5,'medium','active','2026-02-15 18:14:42'),(24,11,'English',1,'Essay Writing & Grammar','Grade 11 English ΓÇö Chapter 1: Essay Writing & Grammar','Test your knowledge of Essay Writing & Grammar from Grade 11 English.',15,50,5,'medium','active','2026-02-15 18:14:42'),(25,12,'English',1,'Essay Writing & Grammar','Grade 12 English ΓÇö Chapter 1: Essay Writing & Grammar','Test your knowledge of Essay Writing & Grammar from Grade 12 English.',15,50,5,'medium','active','2026-02-15 18:14:42'),(26,1,'Environmental Science',1,'Living & Non-Living Things','Grade 1 Environmental Science ΓÇö Chapter 1: Living & Non-Living Things','Test your knowledge of Living & Non-Living Things from Grade 1 Environmental Science.',15,50,5,'easy','active','2026-02-15 18:14:43'),(27,7,'Biology',1,'Cell Biology','Grade 7 Biology ΓÇö Chapter 1: Cell Biology','Test your knowledge of Cell Biology from Grade 7 Biology.',15,50,5,'medium','active','2026-02-15 18:14:43'),(28,9,'Biology',1,'Genetics & Heredity','Grade 9 Biology ΓÇö Chapter 1: Genetics & Heredity','Test your knowledge of Genetics & Heredity from Grade 9 Biology.',15,50,5,'hard','active','2026-02-15 18:14:43'),(29,7,'Physics',1,'Force & Motion','Grade 7 Physics ΓÇö Chapter 1: Force & Motion','Test your knowledge of Force & Motion from Grade 7 Physics.',15,50,5,'medium','active','2026-02-15 18:14:43'),(30,9,'Physics',1,'Electricity & Magnetism','Grade 9 Physics ΓÇö Chapter 1: Electricity & Magnetism','Test your knowledge of Electricity & Magnetism from Grade 9 Physics.',15,50,5,'hard','active','2026-02-15 18:14:44'),(31,7,'Chemistry',1,'Matter & Its Properties','Grade 7 Chemistry ΓÇö Chapter 1: Matter & Its Properties','Test your knowledge of Matter & Its Properties from Grade 7 Chemistry.',15,50,5,'medium','active','2026-02-15 18:14:44'),(32,9,'Chemistry',1,'The Periodic Table','Grade 9 Chemistry ΓÇö Chapter 1: The Periodic Table','Test your knowledge of The Periodic Table from Grade 9 Chemistry.',15,50,5,'hard','active','2026-02-15 18:14:44'),(33,5,'General Science',1,'The Human Body','Grade 5 General Science ΓÇö Chapter 1: The Human Body','Test your knowledge of The Human Body from Grade 5 General Science.',15,50,5,'medium','active','2026-02-15 18:14:44'),(34,7,'History',1,'Ancient Ethiopian History','Grade 7 History ΓÇö Chapter 1: Ancient Ethiopian History','Test your knowledge of Ancient Ethiopian History from Grade 7 History.',15,50,5,'medium','active','2026-02-15 18:14:45'),(35,9,'History',1,'Modern Ethiopian History','Grade 9 History ΓÇö Chapter 1: Modern Ethiopian History','Test your knowledge of Modern Ethiopian History from Grade 9 History.',15,50,5,'medium','active','2026-02-15 18:14:45'),(36,7,'Geography',1,'Ethiopian Geography','Grade 7 Geography ΓÇö Chapter 1: Ethiopian Geography','Test your knowledge of Ethiopian Geography from Grade 7 Geography.',15,50,5,'medium','active','2026-02-15 18:14:45'),(37,5,'Civics',1,'Rights & Responsibilities','Grade 5 Civics ΓÇö Chapter 1: Rights & Responsibilities','Test your knowledge of Rights & Responsibilities from Grade 5 Civics.',15,50,5,'easy','active','2026-02-15 18:14:45'),(38,9,'Economics',1,'Basic Economic Concepts','Grade 9 Economics ΓÇö Chapter 1: Basic Economic Concepts','Test your knowledge of Basic Economic Concepts from Grade 9 Economics.',15,50,5,'medium','active','2026-02-15 18:14:46'),(39,9,'ICT',1,'Computer Basics','Grade 9 ICT ΓÇö Chapter 1: Computer Basics','Test your knowledge of Computer Basics from Grade 9 ICT.',15,50,5,'medium','active','2026-02-15 18:14:46'),(40,1,'Amharic',1,'ßìèßï░ßêïßë╡ (Letters)','Grade 1 Amharic ΓÇö Chapter 1: ßìèßï░ßêïßë╡ (Letters)','Test your knowledge of ßìèßï░ßêïßë╡ (Letters) from Grade 1 Amharic.',15,50,5,'easy','active','2026-02-15 18:14:47');
/*!40000 ALTER TABLE `lms_exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lms_questions`
--

DROP TABLE IF EXISTS `lms_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lms_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `option_d` varchar(500) NOT NULL,
  `correct_answer` char(1) NOT NULL,
  `explanation` text DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  CONSTRAINT `lms_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `lms_exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lms_questions`
--

LOCK TABLES `lms_questions` WRITE;
/*!40000 ALTER TABLE `lms_questions` DISABLE KEYS */;
INSERT INTO `lms_questions` VALUES (1,1,'What comes after 5?','4','6','7','3','B','After 5 comes 6 in counting order.',1,1),(2,1,'How many fingers do you have on one hand?','4','10','5','3','C','One hand has 5 fingers.',1,2),(3,1,'What is 2 + 3?','4','6','5','7','C','2 + 3 = 5',1,3),(4,1,'Which number is bigger: 8 or 3?','3','8','They are equal','Neither','B','8 is bigger than 3.',1,4),(5,1,'What is 10 - 4?','5','7','4','6','D','10 - 4 = 6',1,5),(6,2,'What is 1 + 1?','3','1','2','0','C','1 + 1 = 2',1,1),(7,2,'What is 7 - 3?','4','5','3','2','A','7 - 3 = 4',1,2),(8,2,'What is 4 + 5?','8','10','9','7','C','4 + 5 = 9',1,3),(9,2,'If you have 6 apples and eat 2, how many are left?','3','4','5','8','B','6 - 2 = 4 apples remain.',1,4),(10,2,'What is 3 + 3 + 3?','6','12','9','10','C','3 + 3 + 3 = 9',1,5),(11,3,'What is 15 + 10?','20','25','30','35','B','15 + 10 = 25',1,1),(12,3,'Which number comes after 49?','48','40','50','59','C','After 49 comes 50.',1,2),(13,3,'What is the value of 5 tens?','5','50','500','55','B','5 tens = 50',1,3),(14,3,'Count by 2s: 2, 4, 6, __?','7','10','8','9','C','Counting by 2: 2, 4, 6, 8',1,4),(15,3,'What is 50 - 20?','20','40','30','25','C','50 - 20 = 30',1,5),(16,4,'What is 3 ├ù 4?','7','12','10','14','B','3 ├ù 4 = 12',1,1),(17,4,'What is 5 ├ù 2?','10','7','25','52','A','5 ├ù 2 = 10',1,2),(18,4,'What is 6 ├ù 3?','9','63','18','15','C','6 ├ù 3 = 18',1,3),(19,4,'If each bag has 4 oranges, how many in 3 bags?','7','12','15','43','B','3 bags ├ù 4 = 12 oranges',1,4),(20,4,'What is 7 ├ù 1?','8','6','1','7','D','Any number ├ù 1 = itself. So 7 ├ù 1 = 7',1,5),(21,5,'What is 24 ├╖ 6?','3','4','5','6','B','24 ├╖ 6 = 4',1,1),(22,5,'What is 36 ├╖ 9?','3','5','4','6','C','36 ├╖ 9 = 4',1,2),(23,5,'What is the remainder of 17 ├╖ 5?','1','3','2','4','C','17 ├╖ 5 = 3 remainder 2',1,3),(24,5,'What is 100 ├╖ 10?','1','100','10','1000','C','100 ├╖ 10 = 10',1,4),(25,5,'What is 45 ├╖ 5?','7','8','10','9','D','45 ├╖ 5 = 9',1,5),(26,6,'What is 1/2 + 1/2?','2/4','1','1/4','2/2','B','1/2 + 1/2 = 1 (or 2/2 = 1)',1,1),(27,6,'Which fraction is larger: 1/3 or 1/2?','1/3','1/2','They are equal','Cannot tell','B','1/2 is larger because dividing into 2 parts gives bigger pieces than 3 parts.',1,2),(28,6,'Simplify 4/8','2/4','1/2','4/8','1/4','B','4/8 = 1/2 after dividing both by 4.',1,3),(29,6,'What is 3/4 of 20?','10','12','15','18','C','3/4 ├ù 20 = 60/4 = 15',1,4),(30,6,'Convert 1/4 to a percentage','40%','50%','25%','75%','C','1/4 = 0.25 = 25%',1,5),(31,7,'What is 0.5 + 0.25?','0.30','0.75','0.52','0.70','B','0.5 + 0.25 = 0.75',1,1),(32,7,'Convert 75% to a decimal','7.5','0.075','0.75','75.0','C','75% = 75/100 = 0.75',1,2),(33,7,'What is 10% of 200?','10','20','2','100','B','10% ├ù 200 = 20',1,3),(34,7,'What is 3.14 rounded to the nearest whole number?','3','4','3.1','3.2','A','3.14 rounds down to 3.',1,4),(35,7,'Which is greater: 0.6 or 0.55?','0.55','0.6','They are equal','Cannot tell','B','0.6 = 0.60, which is greater than 0.55.',1,5),(36,8,'Solve: x + 5 = 12','x = 5','x = 17','x = 7','x = 6','C','x = 12 - 5 = 7',1,1),(37,8,'Solve: 2x = 10','x = 20','x = 5','x = 8','x = 12','B','x = 10/2 = 5',1,2),(38,8,'What is the value of 3(x+2) when x=4?','14','18','12','10','B','3(4+2) = 3├ù6 = 18',1,3),(39,8,'Simplify: 4x + 3x','12x','7x','43x','7x┬▓','B','4x + 3x = 7x (combine like terms)',1,4),(40,8,'If 3x - 6 = 9, what is x?','1','3','5','15','C','3x = 15, x = 5',1,5),(41,9,'How many degrees in a full circle?','180┬░','90┬░','360┬░','270┬░','C','A full circle has 360 degrees.',1,1),(42,9,'What is the sum of angles in a triangle?','360┬░','90┬░','270┬░','180┬░','D','The sum of interior angles in a triangle is always 180┬░.',1,2),(43,9,'A right angle measures how many degrees?','45┬░','90┬░','180┬░','60┬░','B','A right angle is exactly 90 degrees.',1,3),(44,9,'How many sides does a hexagon have?','5','7','6','8','C','A hexagon has 6 sides.',1,4),(45,9,'What is the area of a rectangle with length 8 and width 5?','13','40','26','45','B','Area = length ├ù width = 8 ├ù 5 = 40',1,5),(46,10,'What is the standard form of a quadratic equation?','y = mx + b','ax┬▓ + bx + c = 0','a/b = c/d','y = kx','B','The standard form is ax┬▓ + bx + c = 0 where a Γëá 0.',1,1),(47,10,'Solve: x┬▓ = 25','x = 5','x = -5','x = 5 or x = -5','x = 25','C','ΓêÜ25 = ┬▒5, so x = 5 or x = -5.',1,2),(48,10,'What is the discriminant of ax┬▓ + bx + c?','a┬▓ - 4bc','b┬▓ - 4ac','4ac - b┬▓','b┬▓ + 4ac','B','The discriminant is b┬▓ - 4ac.',1,3),(49,10,'If discriminant > 0, how many real solutions?','0','1','2','3','C','If b┬▓ - 4ac > 0, there are 2 distinct real solutions.',1,4),(50,10,'Factor: x┬▓ - 9','(x-3)(x-3)','(x+3)(x-3)','(x+9)(x-1)','(x-3)(x+9)','B','x┬▓ - 9 is a difference of squares: (x+3)(x-3)',1,5),(51,11,'What is sin(30┬░)?','1','ΓêÜ3/2','1/2','ΓêÜ2/2','C','sin(30┬░) = 1/2 is a standard trigonometric value.',1,1),(52,11,'What is the Pythagorean theorem?','a + b = c','a┬▓ + b┬▓ = c┬▓','a ├ù b = c','a/b = c','B','For a right triangle: a┬▓ + b┬▓ = c┬▓ where c is the hypotenuse.',1,2),(53,11,'cos(0┬░) equals?','0','-1','1','1/2','C','cos(0┬░) = 1',1,3),(54,11,'In a right triangle, the longest side is called?','Base','Height','Hypotenuse','Perpendicular','C','The hypotenuse is the longest side, opposite the right angle.',1,4),(55,11,'What is tan(45┬░)?','0','ΓêÜ2','1','1/2','C','tan(45┬░) = sin(45┬░)/cos(45┬░) = 1',1,5),(56,12,'What is the derivative of x┬▓?','x','2x','x┬▓','2x┬▓','B','Using the power rule: d/dx(x┬▓) = 2x.',1,1),(57,12,'What is the limit of 1/x as x ΓåÆ Γê₧?','Γê₧','1','0','-1','C','As x gets larger, 1/x approaches 0.',1,2),(58,12,'Derivative of a constant is?','1','The constant itself','Γê₧','0','D','The derivative of any constant is 0.',1,3),(59,12,'What is d/dx(3x┬│)?','3x┬▓','9x┬▓','9x┬│','x┬│','B','d/dx(3x┬│) = 3┬╖3x┬▓ = 9x┬▓',1,4),(60,12,'What does the integral of v(t) represent?','Acceleration','Velocity','Displacement','Force','C','The integral of velocity gives displacement.',1,5),(61,13,'A fair coin is flipped. P(heads) is?','1','1/3','0','1/2','D','A fair coin has equal probability: P(H) = 1/2.',1,1),(62,13,'The mean of 2, 4, 6 is?','3','4','12','6','B','Mean = (2+4+6)/3 = 12/3 = 4',1,2),(63,13,'What is the mode of: 3, 5, 3, 7, 3, 9?','3','5','7','9','A','Mode is the most frequent value. 3 appears 3 times.',1,3),(64,13,'Standard deviation measures what?','Central tendency','Spread of data','Probability','Frequency','B','Standard deviation measures how spread out data is from the mean.',1,4),(65,13,'Rolling a die, P(even number) is?','1/6','1/3','1/2','2/3','C','Even numbers: 2,4,6 ΓåÆ 3 out of 6 = 1/2',1,5),(66,14,'How many letters are in the English alphabet?','24','25','26','27','C','The English alphabet has 26 letters (A-Z).',1,1),(67,14,'Which is a vowel?','B','C','A','D','C','A, E, I, O, U are vowels. A is a vowel.',1,2),(68,14,'What sound does \"C\" make in \"cat\"?','S','K','Ch','Sh','B','\"C\" makes a hard \"K\" sound in \"cat\".',1,3),(69,14,'Which word rhymes with \"bat\"?','Ball','Cat','Dog','Sun','B','\"Bat\" and \"cat\" both end with \"-at\".',1,4),(70,14,'Complete: The ___ is red.','car','run','big','happy','A','\"The car is red.\" is a complete sentence with a noun.',1,5),(71,15,'How many letters are in the English alphabet?','24','25','26','27','C','The English alphabet has 26 letters (A-Z).',1,1),(72,15,'Which is a vowel?','B','C','A','D','C','A, E, I, O, U are vowels. A is a vowel.',1,2),(73,15,'What sound does \"C\" make in \"cat\"?','S','K','Ch','Sh','B','\"C\" makes a hard \"K\" sound in \"cat\".',1,3),(74,15,'Which word rhymes with \"bat\"?','Ball','Cat','Dog','Sun','B','\"Bat\" and \"cat\" both end with \"-at\".',1,4),(75,15,'Complete: The ___ is red.','car','run','big','happy','A','\"The car is red.\" is a complete sentence with a noun.',1,5),(76,16,'How many letters are in the English alphabet?','24','25','26','27','C','The English alphabet has 26 letters (A-Z).',1,1),(77,16,'Which is a vowel?','B','C','A','D','C','A, E, I, O, U are vowels. A is a vowel.',1,2),(78,16,'What sound does \"C\" make in \"cat\"?','S','K','Ch','Sh','B','\"C\" makes a hard \"K\" sound in \"cat\".',1,3),(79,16,'Which word rhymes with \"bat\"?','Ball','Cat','Dog','Sun','B','\"Bat\" and \"cat\" both end with \"-at\".',1,4),(80,16,'Complete: The ___ is red.','car','run','big','happy','A','\"The car is red.\" is a complete sentence with a noun.',1,5),(81,17,'How many letters are in the English alphabet?','24','25','26','27','C','The English alphabet has 26 letters (A-Z).',1,1),(82,17,'Which is a vowel?','B','C','A','D','C','A, E, I, O, U are vowels. A is a vowel.',1,2),(83,17,'What sound does \"C\" make in \"cat\"?','S','K','Ch','Sh','B','\"C\" makes a hard \"K\" sound in \"cat\".',1,3),(84,17,'Which word rhymes with \"bat\"?','Ball','Cat','Dog','Sun','B','\"Bat\" and \"cat\" both end with \"-at\".',1,4),(85,17,'Complete: The ___ is red.','car','run','big','happy','A','\"The car is red.\" is a complete sentence with a noun.',1,5),(86,18,'Which is a noun?','Run','Beautiful','Table','Quickly','C','A noun is a person, place, or thing. \"Table\" is a thing.',1,1),(87,18,'Identify the verb: \"She sings beautifully.\"','She','sings','beautifully','None','B','\"Sings\" is the action word (verb) in the sentence.',1,2),(88,18,'What is an adjective?','A doing word','A naming word','A describing word','A joining word','C','An adjective describes a noun (e.g., big, red, happy).',1,3),(89,18,'\"He ran quickly.\" ΓÇö \"quickly\" is a(n)?','Noun','Verb','Adjective','Adverb','D','An adverb modifies a verb. \"Quickly\" tells how he ran.',1,4),(90,18,'Which is a conjunction?','and','big','run','the','A','\"And\" joins words or sentences. It is a conjunction.',1,5),(91,19,'Which is a noun?','Run','Beautiful','Table','Quickly','C','A noun is a person, place, or thing. \"Table\" is a thing.',1,1),(92,19,'Identify the verb: \"She sings beautifully.\"','She','sings','beautifully','None','B','\"Sings\" is the action word (verb) in the sentence.',1,2),(93,19,'What is an adjective?','A doing word','A naming word','A describing word','A joining word','C','An adjective describes a noun (e.g., big, red, happy).',1,3),(94,19,'\"He ran quickly.\" ΓÇö \"quickly\" is a(n)?','Noun','Verb','Adjective','Adverb','D','An adverb modifies a verb. \"Quickly\" tells how he ran.',1,4),(95,19,'Which is a conjunction?','and','big','run','the','A','\"And\" joins words or sentences. It is a conjunction.',1,5),(96,20,'Which is a noun?','Run','Beautiful','Table','Quickly','C','A noun is a person, place, or thing. \"Table\" is a thing.',1,1),(97,20,'Identify the verb: \"She sings beautifully.\"','She','sings','beautifully','None','B','\"Sings\" is the action word (verb) in the sentence.',1,2),(98,20,'What is an adjective?','A doing word','A naming word','A describing word','A joining word','C','An adjective describes a noun (e.g., big, red, happy).',1,3),(99,20,'\"He ran quickly.\" ΓÇö \"quickly\" is a(n)?','Noun','Verb','Adjective','Adverb','D','An adverb modifies a verb. \"Quickly\" tells how he ran.',1,4),(100,20,'Which is a conjunction?','and','big','run','the','A','\"And\" joins words or sentences. It is a conjunction.',1,5),(101,21,'Which is a noun?','Run','Beautiful','Table','Quickly','C','A noun is a person, place, or thing. \"Table\" is a thing.',1,1),(102,21,'Identify the verb: \"She sings beautifully.\"','She','sings','beautifully','None','B','\"Sings\" is the action word (verb) in the sentence.',1,2),(103,21,'What is an adjective?','A doing word','A naming word','A describing word','A joining word','C','An adjective describes a noun (e.g., big, red, happy).',1,3),(104,21,'\"He ran quickly.\" ΓÇö \"quickly\" is a(n)?','Noun','Verb','Adjective','Adverb','D','An adverb modifies a verb. \"Quickly\" tells how he ran.',1,4),(105,21,'Which is a conjunction?','and','big','run','the','A','\"And\" joins words or sentences. It is a conjunction.',1,5),(106,22,'What is a thesis statement?','The first sentence','The conclusion','The main argument of an essay','A quote','C','A thesis statement clearly states the main argument.',1,1),(107,22,'Which sentence is in passive voice?','The cat caught the mouse.','She wrote a letter.','The letter was written by her.','They play football.','C','\"Was written by her\" = passive. The subject receives the action.',1,2),(108,22,'\"Their, There, They\'re\" ΓÇö Which means \"belonging to them\"?','There','Their','They\'re','All of them','B','\"Their\" is possessive (belonging to them).',1,3),(109,22,'A paragraph should have a?','Title','Topic sentence','Bibliography','Footnote','B','Every paragraph needs a topic sentence that states the main idea.',1,4),(110,22,'What does \"revise\" mean in writing?','Delete everything','Read once','Review and improve','Submit','C','Revising means reviewing and improving your writing.',1,5),(111,23,'What is a thesis statement?','The first sentence','The conclusion','The main argument of an essay','A quote','C','A thesis statement clearly states the main argument.',1,1),(112,23,'Which sentence is in passive voice?','The cat caught the mouse.','She wrote a letter.','The letter was written by her.','They play football.','C','\"Was written by her\" = passive. The subject receives the action.',1,2),(113,23,'\"Their, There, They\'re\" ΓÇö Which means \"belonging to them\"?','There','Their','They\'re','All of them','B','\"Their\" is possessive (belonging to them).',1,3),(114,23,'A paragraph should have a?','Title','Topic sentence','Bibliography','Footnote','B','Every paragraph needs a topic sentence that states the main idea.',1,4),(115,23,'What does \"revise\" mean in writing?','Delete everything','Read once','Review and improve','Submit','C','Revising means reviewing and improving your writing.',1,5),(116,24,'What is a thesis statement?','The first sentence','The conclusion','The main argument of an essay','A quote','C','A thesis statement clearly states the main argument.',1,1),(117,24,'Which sentence is in passive voice?','The cat caught the mouse.','She wrote a letter.','The letter was written by her.','They play football.','C','\"Was written by her\" = passive. The subject receives the action.',1,2),(118,24,'\"Their, There, They\'re\" ΓÇö Which means \"belonging to them\"?','There','Their','They\'re','All of them','B','\"Their\" is possessive (belonging to them).',1,3),(119,24,'A paragraph should have a?','Title','Topic sentence','Bibliography','Footnote','B','Every paragraph needs a topic sentence that states the main idea.',1,4),(120,24,'What does \"revise\" mean in writing?','Delete everything','Read once','Review and improve','Submit','C','Revising means reviewing and improving your writing.',1,5),(121,25,'What is a thesis statement?','The first sentence','The conclusion','The main argument of an essay','A quote','C','A thesis statement clearly states the main argument.',1,1),(122,25,'Which sentence is in passive voice?','The cat caught the mouse.','She wrote a letter.','The letter was written by her.','They play football.','C','\"Was written by her\" = passive. The subject receives the action.',1,2),(123,25,'\"Their, There, They\'re\" ΓÇö Which means \"belonging to them\"?','There','Their','They\'re','All of them','B','\"Their\" is possessive (belonging to them).',1,3),(124,25,'A paragraph should have a?','Title','Topic sentence','Bibliography','Footnote','B','Every paragraph needs a topic sentence that states the main idea.',1,4),(125,25,'What does \"revise\" mean in writing?','Delete everything','Read once','Review and improve','Submit','C','Revising means reviewing and improving your writing.',1,5),(126,26,'Which is a living thing?','Rock','Water','Tree','Chair','C','Trees grow, breathe, and reproduce ΓÇö they are living things.',1,1),(127,26,'What do plants need to grow?','Darkness','Water and sunlight','Ice','Rocks','B','Plants need water, sunlight, soil, and air to grow.',1,2),(128,26,'Which animal lives in water?','Cat','Fish','Dog','Hen','B','Fish live in water ΓÇö they breathe through gills.',1,3),(129,26,'What season is hottest?','Winter','Spring','Summer','Autumn','C','Summer is the hottest season of the year.',1,4),(130,26,'Trees give us?','Fire','Oxygen','Salt','Metal','B','Trees produce oxygen through photosynthesis.',1,5),(131,27,'What is the basic unit of life?','Atom','Molecule','Cell','Organ','C','The cell is the basic structural and functional unit of life.',1,1),(132,27,'Which organelle is the \"powerhouse\" of the cell?','Nucleus','Ribosome','Mitochondria','Golgi body','C','Mitochondria produce energy (ATP) for the cell.',1,2),(133,27,'Plant cells have ___ that animal cells don\'t.','Nucleus','Cell wall','Cytoplasm','Ribosomes','B','Plant cells have a rigid cell wall; animal cells do not.',1,3),(134,27,'DNA is found in the?','Cell wall','Nucleus','Cytoplasm','Membrane','B','DNA is stored in the nucleus of the cell.',1,4),(135,27,'Which organelle makes proteins?','Lysosome','Ribosome','Vacuole','Nucleus','B','Ribosomes are responsible for protein synthesis.',1,5),(136,28,'Who is the father of genetics?','Darwin','Mendel','Watson','Linnaeus','B','Gregor Mendel is known as the father of genetics.',1,1),(137,28,'DNA stands for?','Deoxyribose Nucleic Acid','Deoxyribonucleic Acid','Dinitro Nucleotide Acid','Dinucleic Acid','B','DNA = Deoxyribonucleic Acid',1,2),(138,28,'How many chromosomes do humans have?','23','44','46','48','C','Humans have 46 chromosomes (23 pairs).',1,3),(139,28,'A dominant trait is represented by?','Lowercase letter','Capital letter','Number','Symbol','B','Dominant alleles are shown with capital letters (e.g., B).',1,4),(140,28,'Genotype refers to?','Physical appearance','Genetic makeup','Blood type only','Eye color only','B','Genotype is the genetic composition (e.g., Bb, BB).',1,5),(141,29,'What is the SI unit of force?','Watt','Joule','Newton','Pascal','C','The SI unit of force is the Newton (N).',1,1),(142,29,'Speed is measured in?','kg','m/s','liters','m┬▓','B','Speed = distance/time, measured in meters per second (m/s).',1,2),(143,29,'Gravity pulls objects towards?','Sky','Sideways','Earth\'s center','Space','C','Gravity pulls everything towards the center of Earth.',1,3),(144,29,'Newton\'s first law is about?','Force','Inertia','Energy','Gravity','B','Newton\'s first law (law of inertia) states objects stay at rest unless acted upon.',1,4),(145,29,'Which has more inertia?','Feather','Truck','Paper','Balloon','B','More massive objects have more inertia. A truck is most massive.',1,5),(146,30,'Ohm\'s law states V = ?','I/R','I ├ù R','R/I','I + R','B','Ohm\'s Law: Voltage = Current ├ù Resistance (V = IR)',1,1),(147,30,'The SI unit of electric current is?','Volt','Ohm','Ampere','Watt','C','Current is measured in Amperes (A).',1,2),(148,30,'Like poles of a magnet will?','Attract','Repel','Do nothing','Stick together','B','Like poles (N-N or S-S) repel each other.',1,3),(149,30,'What material is a good conductor?','Wood','Rubber','Copper','Glass','C','Copper is an excellent electrical conductor.',1,4),(150,30,'Power (P) = ?','V ├ù I','V + I','V / I','V - I','A','Electrical power P = Voltage ├ù Current',1,5),(151,31,'What are the 3 states of matter?','Hot, cold, warm','Solid, liquid, gas','Earth, water, fire','Hard, soft, flexible','B','Matter exists as solid, liquid, or gas.',1,1),(152,31,'Water boils at what temperature?','50┬░C','100┬░C','200┬░C','0┬░C','B','Water boils at 100┬░C (212┬░F) at sea level.',1,2),(153,31,'The chemical formula for water is?','CO2','NaCl','H2O','O2','C','Water is H2O ΓÇö 2 hydrogen atoms and 1 oxygen atom.',1,3),(154,31,'An atom consists of?','Only protons','Protons, neutrons, electrons','Only electrons','Only neutrons','B','Atoms have protons and neutrons in the nucleus, with electrons orbiting.',1,4),(155,31,'Which is a chemical change?','Cutting paper','Melting ice','Burning wood','Breaking glass','C','Burning creates new substances ΓÇö it is a chemical change.',1,5),(156,32,'Who created the periodic table?','Newton','Einstein','Mendeleev','Bohr','C','Dmitri Mendeleev created the first periodic table in 1869.',1,1),(157,32,'How many elements are in the periodic table?','100','118','92','108','B','There are currently 118 confirmed elements.',1,2),(158,32,'Elements in the same column are called?','Periods','Groups','Rows','Series','B','Vertical columns are called groups or families.',1,3),(159,32,'What is the atomic number of Carbon?','12','14','6','8','C','Carbon has atomic number 6 (6 protons).',1,4),(160,32,'Noble gases are in group?','1','7','17','18','D','Noble gases (He, Ne, Ar, etc.) are in Group 18.',1,5),(161,33,'How many bones does an adult human have?','106','206','306','150','B','An adult human skeleton has 206 bones.',1,1),(162,33,'Which organ pumps blood?','Brain','Lungs','Heart','Kidney','C','The heart pumps blood throughout the body.',1,2),(163,33,'The largest organ in the human body is?','Liver','Brain','Heart','Skin','D','Skin is the largest organ by surface area.',1,3),(164,33,'Blood is filtered by the?','Heart','Lungs','Kidneys','Stomach','C','Kidneys filter blood and produce urine.',1,4),(165,33,'Oxygen is carried by?','White blood cells','Red blood cells','Platelets','Plasma','B','Red blood cells contain hemoglobin which carries oxygen.',1,5),(166,34,'The Aksumite Kingdom was located in?','West Africa','Northern Ethiopia','Southern Africa','Central Asia','B','The Aksumite Kingdom was in northern Ethiopia and Eritrea.',1,1),(167,34,'The Aksumite obelisks are found in?','Lalibela','Axum','Gondar','Harar','B','The famous obelisks (stelae) are in Axum.',1,2),(168,34,'King Ezana was known for?','Building pyramids','Converting to Christianity','Inventing writing','Exploring space','B','King Ezana made Christianity the state religion of Aksum.',1,3),(169,34,'Ethiopia\'s ancient script is called?','Arabic','Latin','Ge\'ez','Greek','C','Ge\'ez is the ancient Ethiopian script, still used in the church.',1,4),(170,34,'The rock-hewn churches of Lalibela were built by?','King Lalibela','King Menelik','King Tewodros','Emperor Haile Selassie','A','King Lalibela ordered the construction of the 11 rock-hewn churches.',1,5),(171,35,'The Battle of Adwa was fought in?','1886','1896','1906','1916','B','The Battle of Adwa was fought on March 1, 1896.',1,1),(172,35,'Ethiopia defeated which country at Adwa?','Britain','France','Italy','Germany','C','Ethiopia defeated Italy at the Battle of Adwa.',1,2),(173,35,'Who led Ethiopia at the Battle of Adwa?','Haile Selassie','Menelik II','Tewodros II','Yohannes IV','B','Emperor Menelik II led Ethiopia to victory at Adwa.',1,3),(174,35,'Ethiopia was the only African country that was never?','Visited','Colonized','Traded with','Named','B','Ethiopia was never colonized (briefly occupied by Italy 1936-41).',1,4),(175,35,'The Ethiopian calendar has how many months?','12','14','13','10','C','The Ethiopian calendar has 13 months (12 months of 30 days + Pagume).',1,5),(176,36,'What is the capital of Ethiopia?','Adama','Bahir Dar','Addis Ababa','Hawassa','C','Addis Ababa is the capital and largest city of Ethiopia.',1,1),(177,36,'The highest mountain in Ethiopia is?','Mount Kenya','Ras Dashen','Kilimanjaro','Mount Entoto','B','Ras Dashen (4,550m) is Ethiopia\'s highest peak.',1,2),(178,36,'Ethiopia is located in which continent?','Asia','Europe','Africa','South America','C','Ethiopia is in East Africa, in the Horn of Africa.',1,3),(179,36,'The Blue Nile originates from?','Lake Victoria','Lake Tana','Red Sea','Lake Turkana','B','The Blue Nile originates from Lake Tana in northern Ethiopia.',1,4),(180,36,'How many regional states does Ethiopia have?','9','10','11','12','C','Ethiopia currently has 11 regional states and 2 chartered cities.',1,5),(181,37,'Every child has the right to?','Drive a car','Education','Work in a factory','Vote','B','Every child has the right to free education.',1,1),(182,37,'Being a good citizen means?','Breaking rules','Respecting others','Being selfish','Ignoring laws','B','Good citizens respect others and follow rules.',1,2),(183,37,'Who makes laws in Ethiopia?','Teachers','Parliament','Students','Police','B','The Parliament (House of Peoples\' Representatives) makes laws.',1,3),(184,37,'The Ethiopian flag colors are?','Red, blue, white','Green, yellow, red','Black, red, gold','Blue, white, green','B','The Ethiopian flag is green, yellow, and red.',1,4),(185,37,'Democracy means?','Rule by one person','Rule by the military','Rule by the people','No rules at all','C','Democracy = government by the people.',1,5),(186,38,'What does \"scarcity\" mean?','Having too much','Limited resources with unlimited wants','Free goods','No demand','B','Scarcity means resources are limited relative to our unlimited wants.',1,1),(187,38,'The term \"GDP\" stands for?','General Domestic Price','Gross Domestic Product','Government Development Plan','General Development Policy','B','GDP = Gross Domestic Product, the total value of goods produced.',1,2),(188,38,'Supply and demand determine?','Weight','Temperature','Price','Distance','C','The interaction of supply and demand determines market price.',1,3),(189,38,'What is inflation?','Decrease in prices','Increase in general price level','Stable prices','Free goods','B','Inflation is a sustained increase in the general price level.',1,4),(190,38,'Ethiopia\'s currency is called?','Dollar','Birr','Shilling','Pound','B','The Ethiopian currency is the Birr (ETB).',1,5),(191,39,'CPU stands for?','Central Processing Unit','Computer Personal Unit','Central Program Utility','Core Processing Unit','A','CPU = Central Processing Unit, the brain of the computer.',1,1),(192,39,'Which is an input device?','Monitor','Printer','Keyboard','Speaker','C','A keyboard is used to input data into the computer.',1,2),(193,39,'1 kilobyte (KB) = ?','100 bytes','1000 bytes','1024 bytes','500 bytes','C','1 KB = 1024 bytes (2^10 bytes).',1,3),(194,39,'RAM stands for?','Read Access Memory','Random Access Memory','Run Active Memory','Readable Active Mode','B','RAM = Random Access Memory, temporary fast storage.',1,4),(195,39,'Which is an operating system?','Microsoft Word','Google Chrome','Windows','PowerPoint','C','Windows is an operating system that manages computer hardware.',1,5),(196,40,'The Amharic alphabet is called?','Arabic','Latin','Fidel (ßìèßï░ßêì)','Greek','C','The Amharic writing system is called Fidel (ßìèßï░ßêì).',1,1),(197,40,'How many base characters (ßìèßï░ßêÄßë╜) are in the Amharic alphabet?','26','33','30','28','B','There are 33 base characters with 7 forms each.',1,2),(198,40,'Which is the first letter of the Amharic alphabet?','ßêÇ','ßêê','ßêá','ßëá','A','ßêÇ (Ha) is the first letter of the Amharic alphabet.',1,3),(199,40,'\"ßèÑßèôßë╡\" means?','Father','Mother','Brother','Sister','B','ßèÑßèôßë╡ means mother in Amharic.',1,4),(200,40,'\"ßïìßêâ\" means?','Food','Fire','Water','Air','C','ßïìßêâ means water in Amharic.',1,5);
/*!40000 ALTER TABLE `lms_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (2,1,2,'Beyaynetu','Large assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=300&q=80',1),(3,2,2,'Special Kitfo','Minced beef seasoned with mitmita and kibbeh.',950.00,'https://images.unsplash.com/photo-1541014741259-df529411b96a?auto=format&fit=crop&w=300&q=80',1),(4,2,4,'Buna (Coffee)','Traditional Ethiopian coffee ceremony style.',150.00,'https://images.unsplash.com/photo-1547825407-2d060104b7f8?auto=format&fit=crop&w=300&q=80',1),(5,3,1,'Special Injera Firfir','Spicy beef firfir with fresh injera and awaze.',280.00,NULL,1),(6,3,2,'Doro Wot','Classic Ethiopian chicken stew with hard-boiled eggs.',450.00,NULL,1),(7,3,2,'Tibs Special','Sauteed beef tips with onions and peppers.',380.00,NULL,1),(8,3,3,'Kitfo','Fresh minced beef with mitmita and kibbeh.',520.00,NULL,1),(9,3,4,'Fresh Juice Combo','Mango, avocado, and papaya layered juice.',120.00,NULL,1),(10,3,5,'Baklava','Sweet pastry with honey and pistachios.',150.00,NULL,1),(11,4,1,'Special Injera Firfir','Spicy beef firfir with fresh injera and awaze.',280.00,NULL,1),(12,4,2,'Doro Wot','Classic Ethiopian chicken stew with hard-boiled eggs.',450.00,NULL,1),(13,4,2,'Tibs Special','Sauteed beef tips with onions and peppers.',380.00,NULL,1),(14,4,3,'Kitfo','Fresh minced beef with mitmita and kibbeh.',520.00,NULL,1),(15,4,4,'Fresh Juice Combo','Mango, avocado, and papaya layered juice.',120.00,NULL,1),(16,4,5,'Baklava','Sweet pastry with honey and pistachios.',150.00,NULL,1),(17,5,1,'Special Injera Firfir','Spicy beef firfir with fresh injera and awaze.',280.00,NULL,1),(18,5,2,'Doro Wot','Classic Ethiopian chicken stew with hard-boiled eggs.',450.00,NULL,1),(19,5,2,'Tibs Special','Sauteed beef tips with onions and peppers.',380.00,NULL,1),(20,5,3,'Kitfo','Fresh minced beef with mitmita and kibbeh.',520.00,NULL,1),(21,5,4,'Fresh Juice Combo','Mango, avocado, and papaya layered juice.',120.00,NULL,1),(22,5,5,'Baklava','Sweet pastry with honey and pistachios.',150.00,NULL,1),(23,10,2,'Kitfo - L','Minced beef top side with seasoned butter, caper served with local cottage cheese and collard greens on injera.',1304.35,'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',1),(24,10,2,'Gomen Kitfo - L','Local cabbage cooked with seasoned butter & caper served with cottage cheese and injera.',790.51,'https://images.unsplash.com/photo-1556909114-44e3e70034e2?auto=format&fit=crop&w=400&q=80',1),(25,10,2,'Zemamujet - L','Local cabbage with seasoned butter, caper served with false banana bread and cottage cheese.',830.04,'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?auto=format&fit=crop&w=400&q=80',1),(26,10,3,'Doro Wott - L','Local chicken cooked with onion, red pepper, seasoned butter served with injera and boiled egg.',1304.35,'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=400&q=80',1),(27,10,2,'Tegabino - L','Ground beans, oil served with injera ΓÇö a creamy chickpea-based fasting dish.',434.78,'https://images.unsplash.com/photo-1536304929831-ee1ca9d44726?auto=format&fit=crop&w=400&q=80',1),(28,10,2,'Bozena Shero - L','Ground beans mixed with meat, seasoned butter served with injera ΓÇö hearty and flavorful.',869.57,'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=400&q=80',1),(29,10,2,'Yod Special Beyaynetu','Grand vegan platter with 7 types of wot (stews) ΓÇö misir, gomen, atkilt, shiro, and more on fresh injera.',650.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(30,10,4,'Buna Ceremony Coffee','Full traditional Ethiopian coffee ceremony ΓÇö roasted, ground, and brewed fresh at your table with popcorn.',180.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(31,11,3,'Kitfo Special','Freshly minced premium beef seasoned with mitmita spice and Ethiopian herbed butter (kibbeh). Served raw, medium, or well done.',750.00,'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',1),(32,11,2,'Tibs Derek (Dry-Fried Beef)','Cubed beef saut├⌐ed with rosemary, hot peppers, onions, and tomatoes ΓÇö served sizzling on a clay plate.',680.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(33,11,3,'Doro Wot','Slow-cooked chicken drumstick in rich berbere sauce with hard-boiled egg ΓÇö Ethiopia\'s national dish.',950.00,'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=400&q=80',1),(34,11,2,'Beyaynetu (Fasting Platter)','Colorful vegan platter with 8 different wots including misir, shiro, gomen, tikil gomen, atkilt, and azifa.',580.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(35,11,3,'Gored Gored','Cubed raw beef tossed in mitmita spice and awaze pepper paste ΓÇö for the adventurous palate.',820.00,'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',1),(36,11,2,'Shiro Wot','Creamy chickpea powder stew simmered with garlic, onions, tomato, and Ethiopian spice blend.',380.00,'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=400&q=80',1),(37,11,4,'Tej (Honey Wine)','Traditional Ethiopian honey wine, delicately sweet and refreshing ΓÇö served in a classic berele glass.',220.00,'https://images.unsplash.com/photo-1474722883778-792e7990302f?auto=format&fit=crop&w=400&q=80',1),(38,11,4,'Spriss (Layered Juice)','Beautiful three-layer fresh fruit juice ΓÇö mango, avocado, and papaya blended separately.',150.00,'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',1),(39,12,3,'Lucy Special Combination','A luxurious platter with kitfo, tibs, gomen, and ayib served together on a large injera ΓÇö chef\'s recommendation.',980.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(40,12,2,'Shiro Wot','Creamy chickpea flour stew simmered with garlic, tomatoes, and Ethiopian spice blend ΓÇö a beloved comfort food.',350.00,'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=400&q=80',1),(41,12,3,'Lamb Tibs Firfir','Tender lamb pieces saut├⌐ed with butter, onion, rosemary and served on torn injera soaked in juices.',880.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(42,12,2,'Misir Wot (Red Lentil)','Split red lentils slow-cooked in berbere sauce ΓÇö rich, spicy, and deeply satisfying vegan option.',320.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(43,12,2,'Derek Tibs - Beef','Dry-fried cubed beef seasoned with rosemary, jalape├▒o, garlic, and onions on a sizzling pan.',750.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(44,12,1,'Enkulal Firfir','Ethiopian-style scrambled eggs with tomatoes, onions, green peppers, and shredded injera.',350.00,'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',1),(45,12,4,'Fresh Avocado Juice','Thick, creamy layered avocado and mango juice topped with a drizzle of lime ΓÇö an Ethiopian caf├⌐ classic.',150.00,'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',1),(46,13,1,'Kategna Special','Toasted injera generously topped with Ethiopian spiced butter (kibbeh) and berbere ΓÇö crunchy, warm, and addictive.',280.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(47,13,1,'Chechebsa (Kita Firfir)','Shredded pan-fried flatbread mixed with Ethiopian spiced butter and berbere ΓÇö a hearty traditional breakfast.',320.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(48,13,1,'Enkulal Firfir (Egg Scramble)','Ethiopian-style scrambled eggs with tomatoes, onions, green peppers and a touch of berbere with bread.',280.00,'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',1),(49,13,1,'Firfir Be Siga','Shredded injera soaked in spicy meat sauce with tender beef chunks ΓÇö perfect morning energy.',420.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(50,13,1,'Ful Medames','Mashed fava beans topped with cumin, olive oil, fresh tomato, egg, jalape├▒o, and warm bread.',350.00,'https://images.unsplash.com/photo-1536304929831-ee1ca9d44726?auto=format&fit=crop&w=400&q=80',1),(51,13,1,'Kinche (Cracked Wheat)','Ethiopian porridge made with cracked wheat or oats cooked in spiced butter ΓÇö simple and nutritious.',220.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(52,13,1,'Genfo','Thick barley flour porridge with a well of spiced butter and berbere in the center ΓÇö an ancient Ethiopian breakfast.',250.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(53,13,4,'Buna (Coffee)','Freshly roasted and brewed Ethiopian coffee served with popcorn ΓÇö the morning ritual.',80.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(54,14,3,'Totot Special Kitfo','Premium-grade minced raw beef with freshly ground mitmita and warm kibbeh ΓÇö the house specialty.',850.00,'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',1),(55,14,2,'Gomen Be Siga','Tender beef slow-cooked with collard greens, garlic, and ginger ΓÇö a Southern Ethiopian classic.',580.00,'https://images.unsplash.com/photo-1556909114-44e3e70034e2?auto=format&fit=crop&w=400&q=80',1),(56,14,2,'Ayib Be Gomen','Fresh homemade Ethiopian cottage cheese mixed with finely chopped collard greens and spices.',320.00,'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=400&q=80',1),(57,14,3,'Kurt (Raw Beef Cubes)','Fresh cubed raw beef dipped in mitmita and awaze ΓÇö a delicacy for meat lovers.',920.00,'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',1),(58,14,3,'Kitfo Leb Leb','Lightly cooked kitfo (medium rare) with cottage cheese and gomen ΓÇö the perfect balance.',780.00,'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',1),(59,14,2,'Kocho Be Kitfo','False banana bread served alongside special kitfo, ayib, and gomen ΓÇö authentic Gurage meal.',950.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(60,14,1,'Bula (Porridge)','Smooth porridge made from false banana starch, served with kibbeh butter ΓÇö a Gurage staple.',280.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(61,15,2,'Four Sisters Combo Plate','Generous beyaynetu with 9 different wots including misir, shiro, gomen, and special alicha ΓÇö feeds two.',550.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(62,15,3,'Tere Siga (Raw Beef)','Fresh-cut raw beef served with awaze paste, senafich (mustard), and traditional accompaniments.',720.00,'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',1),(63,15,3,'Doro Wot Special','Slowly simmered chicken in rich berbere sauce with boiled eggs ΓÇö the queens favorite recipe.',880.00,'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=400&q=80',1),(64,15,2,'Yebeg Alicha','Mild lamb stew cooked with turmeric, garlic, and ginger ΓÇö gentle spiced Northern style.',750.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(65,15,2,'Asa Tibs (Fish Tibs)','Fresh Tana Lake fish saut├⌐ed with onions, tomatoes, peppers, and rosemary herbs.',620.00,'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=400&q=80',1),(66,15,3,'Enjera Be Wot Sampler','A sampler plate of key wot, yebeg wot, and misir wot on a large injera ΓÇö taste everything!',680.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(67,15,4,'Tella (Traditional Beer)','Homemade traditional Ethiopian barley beer ΓÇö mildly fermented, smoky, and refreshing.',80.00,'https://images.unsplash.com/photo-1474722883778-792e7990302f?auto=format&fit=crop&w=400&q=80',1),(68,16,3,'Makush Signature Lamb Tibs','Tender lamb cubes saut├⌐ed with rosemary, jalape├▒o, and caramelized onions on a sizzling clay pot.',920.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(69,16,2,'Injera Lasagna','Creative fusion: layers of injera with spiced minced beef, tomato sauce, and Ethiopian cheese ΓÇö a Makush original.',780.00,'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=400&q=80',1),(70,16,2,'Avocado Tartare','Fresh avocado layered with spiced kitfo, cherry tomatoes, and microgreens ΓÇö a modern Ethiopian starter.',520.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(71,16,3,'Grilled Nile Perch','Pan-seared Nile perch fillet with lemon-berbere butter, roasted vegetables, and injera crisps.',1100.00,'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=400&q=80',1),(72,16,2,'Mushroom Shiro','Elevated version of classic shiro with saut├⌐ed wild mushrooms, truffle oil, and fresh herbs.',480.00,'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=400&q=80',1),(73,16,2,'Tibs Salad Bowl','Warm beef tibs over mixed greens, cherry tomatoes, avocado, and house vinaigrette dressing.',620.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(74,16,4,'Ethiopian Honey Wine Cocktail','Signature cocktail mixing tej (honey wine) with fresh lime, ginger, and sparkling water.',350.00,'https://images.unsplash.com/photo-1474722883778-792e7990302f?auto=format&fit=crop&w=400&q=80',1),(75,16,5,'Bunna Brulee','Ethiopian coffee-infused cr├¿me br├╗l├⌐e with caramelized sugar top ΓÇö a fusion dessert masterpiece.',420.00,'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=400&q=80',1),(76,17,2,'Mixed Wot Platter','A colorful spread of 6 traditional stews on injera ΓÇö includes yebeg wot, misir, gomen, and more.',480.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(77,17,3,'Lamb Shank in Berbere','Slow-roasted lamb shank glazed with berbere-infused sauce, served with roasted vegetables and injera.',750.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(78,17,2,'Highland Shepherd Pie','Scottish classic made with spiced Ethiopian minced beef, topped with creamy mashed potatoes.',550.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(79,17,1,'Ben Abeba Breakfast','Full Ethiopian-Scottish breakfast with ful, eggs, toast, sausage, fresh juice, and coffee.',420.00,'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',1),(80,17,2,'Roasted Beet Salad','Roasted beetroot with goat cheese crumble, walnuts, and honey-lemon dressing.',380.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(81,17,2,'Vegetarian Beyaynetu','Full fasting platter with shiro, misir, azifa, gomen, atkilt, and tikil gomen on injera.',350.00,'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?auto=format&fit=crop&w=400&q=80',1),(82,17,4,'Spris (Layered Juice)','Three layers of fresh fruit juices ΓÇö mango, avocado, and papaya, beautifully layered.',120.00,'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',1),(83,18,2,'Zilzil Tibs','Tender strips of beef saut├⌐ed with onions, green peppers, and rosemary ΓÇö served on a hot mitad.',620.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(84,18,2,'Misir Wot (Red Lentil)','Split red lentils simmered in rich berbere sauce ΓÇö Ethiopia\'s most popular vegan dish.',280.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(85,18,3,'Key Wot (Spicy Beef Stew)','Cubed beef slow-cooked in a thick berbere sauce with onions and kibbeh butter.',720.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(86,18,3,'Alicha Yebeg (Mild Lamb)','Tender lamb cubes in a mild, turmeric-based sauce with potatoes and carrots.',680.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(87,18,2,'Azifa (Lentil Salad)','Cool green lentil salad with mustard, jalape├▒o, onion, and lemon juice ΓÇö refreshing side.',180.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(88,18,2,'Timatim Salata','Fresh tomato salad with onion, green peppers, lemon juice, and olive oil ΓÇö Ethiopian classic.',150.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(89,18,4,'Macchiato (Ethiopian Style)','Strong Ethiopian espresso topped with steamed milk foam ΓÇö the daily ritual of every Ethiopian.',60.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(90,19,3,'Yebeg Tibs (Lamb Tibs)','Juicy cubes of lamb saut├⌐ed with jalape├▒os, onions, and tomatoes, seasoned with Ethiopian herbs.',780.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(91,19,1,'Firfir Be Siga','Shredded injera soaked in spicy berbere-based meat sauce with tender beef ΓÇö hearty breakfast.',350.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(92,19,3,'Dulet','Minced tripe, liver, and lean beef saut├⌐ed with jalape├▒o, onion, and Ethiopian spices ΓÇö a delicacy.',650.00,'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',1),(93,19,2,'Quanta Firfir','Dried beef jerky (quanta) rehydrated and saut├⌐ed with injera, berbere sauce, and kibbeh butter.',520.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(94,19,3,'Shekla Tibs','Hot stone-grilled beef or lamb tibs served sizzling on a clay plate with onions and peppers.',850.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(95,19,2,'Fossolia (Green Beans)','Fresh green beans and carrots saut├⌐ed with garlic, onion, and turmeric ΓÇö light fasting dish.',220.00,'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?auto=format&fit=crop&w=400&q=80',1),(96,19,4,'Tena Adam Tea (Rue Tea)','Traditional Ethiopian herbal tea brewed with rue herb, known for health benefits and aromatic flavor.',50.00,'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80',1),(97,20,3,'Saro-Maria Doro Wot','Premium chicken stew slow-simmered for 6 hours in berbere sauce, served with 2 hardboiled eggs and injera.',950.00,'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=400&q=80',1),(98,20,1,'Ful Medames','Mashed fava beans with cumin, olive oil, fresh tomatoes, green chili, egg, and warm bread.',320.00,'https://images.unsplash.com/photo-1536304929831-ee1ca9d44726?auto=format&fit=crop&w=400&q=80',1),(99,20,1,'Continental Breakfast','Toast, butter, jam, fresh fruits, scrambled eggs, sausage, and freshly brewed coffee.',450.00,'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',1),(100,20,2,'Goulash (Ethiopian Style)','Slow-cooked beef and vegetable stew with Ethiopian spices, served with fresh bread.',680.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(101,20,3,'Tibs Special Platter','A combination of beef, lamb, and chicken tibs served on a sizzling platter with vegetables.',1200.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(102,20,2,'Caesar Salad','Crispy romaine lettuce, parmesan, croutons with house-made dressing ΓÇö Continental classic.',380.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(103,20,4,'Fresh Mango Smoothie','Thick and creamy blended mango smoothie with a hint of honey ΓÇö refreshing and naturally sweet.',130.00,'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',1),(104,21,4,'Tomoca Special Roast','Single-origin, dark-roasted Ethiopian Arabica coffee ΓÇö the legendary Tomoca blend since 1953.',80.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(105,21,4,'Macchiato Doppio','Double-shot Ethiopian espresso with a touch of steamed milk ΓÇö rich, bold, and aromatic.',70.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(106,21,1,'Kolo & Popcorn Platter','Traditional roasted barley and peanut mix served with popcorn ΓÇö the classic Ethiopian coffee companion.',100.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(107,21,4,'Cappuccino','Perfectly frothed Ethiopian Arabica cappuccino with velvety milk foam and cocoa dust.',90.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(108,21,4,'Espresso Con Panna','Rich Ethiopian espresso shot topped with fresh whipped cream ΓÇö a classic indulgence.',100.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(109,21,4,'Bunna Spris','Layered iced coffee drink with milk, espresso, and chocolate ΓÇö Tomoca\'s refreshing take.',120.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(110,21,5,'Himbasha (Sweet Bread)','Traditional Ethiopian celebration bread with cardamom ΓÇö lightly sweet and perfect with coffee.',150.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(111,22,2,'Derek Tibs Special','Generously seasoned dry-fried beef cubes with rosemary, garlic, and jalape├▒o on a sizzling clay mitad.',720.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(112,22,2,'Atkilt Wot (Cabbage & Potato)','Mild-spiced cabbage, carrots, and potatoes cooked with turmeric and garlic ΓÇö a fasting favorite.',250.00,'https://images.unsplash.com/photo-1556909114-44e3e70034e2?auto=format&fit=crop&w=400&q=80',1),(113,22,3,'Awaze Tibs','Beef cubes marinated in awaze (chili paste), then pan-fried with onions and green peppers.',680.00,'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',1),(114,22,2,'Yatakilt Kilkil','Mixed vegetables (potato, carrot, green beans) cooked mild with turmeric and garlic.',280.00,'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?auto=format&fit=crop&w=400&q=80',1),(115,22,3,'Special Kitfo Combo','Kitfo served with kocho (false banana bread), ayib, gomen, and awaze on the side.',880.00,'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',1),(116,22,2,'Suf Fitfit','Shredded injera tossed in sunflower seed sauce ΓÇö a unique fasting dish from Eastern Ethiopia.',350.00,'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',1),(117,22,4,'Kenetto','Fermented honey-based drink with herbs ΓÇö a milder, refreshing version of Tej.',160.00,'https://images.unsplash.com/photo-1474722883778-792e7990302f?auto=format&fit=crop&w=400&q=80',1),(118,23,1,'Lime Tree Breakfast Platter','Mix of ful, scrambled eggs, fresh bread, avocado, and seasonal fruits ΓÇö the ultimate brunch.',420.00,'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',1),(119,23,2,'Grilled Fish with Awaze','Fresh Nile tilapia grilled to perfection, topped with awaze (chili paste) and served with salad.',650.00,'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=400&q=80',1),(120,23,2,'Garden Pasta','Penne pasta with roasted vegetables, fresh basil pesto, and parmesan cheese ΓÇö garden fresh.',480.00,'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=400&q=80',1),(121,23,2,'Chicken Wrap','Grilled chicken breast with fresh vegetables, avocado, and garlic sauce in a warm tortilla wrap.',420.00,'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',1),(122,23,2,'Quinoa Bowl','Nutritious quinoa with roasted sweet potato, avocado, chickpeas, greens, and tahini dressing.',520.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(123,23,5,'Carrot Cake','Moist carrot cake with cream cheese frosting, walnuts, and a sprinkle of cinnamon.',280.00,'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=400&q=80',1),(124,23,4,'Fresh Strawberry Juice','Cold-pressed fresh strawberry juice blended with a squeeze of lime ΓÇö seasonal and refreshing.',140.00,'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',1),(125,24,2,'Spaghetti alla Castelli','Classic Italian spaghetti with a unique Ethiopian twist ΓÇö house-made sauce with berbere-infused meatballs.',580.00,'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=400&q=80',1),(126,24,3,'Veal Scaloppine','Thin-sliced veal in white wine and lemon butter sauce, served with saut├⌐ed vegetables.',1100.00,'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',1),(127,24,2,'Lasagna al Forno','Classic layered lasagna with bolognese, b├⌐chamel, and parmesan ΓÇö baked to golden perfection.',750.00,'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=400&q=80',1),(128,24,3,'Grilled Lamb Chops','Premium lamb chops grilled medium-rare with rosemary and garlic, served with mint sauce.',1350.00,'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',1),(129,24,2,'Minestrone Soup','Hearty Italian vegetable soup with pasta, beans, and fresh herbs ΓÇö a perfect starter.',350.00,'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',1),(130,24,2,'Caprese Salad','Fresh mozzarella, ripe tomatoes, and basil drizzled with extra virgin olive oil and balsamic.',420.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',1),(131,24,5,'Tiramisu Habesha Style','Classic Italian tiramisu infused with Ethiopian coffee ΓÇö a perfect fusion dessert.',380.00,'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=400&q=80',1),(132,24,4,'Espresso Italiano','Authentic Italian espresso made with premium Ethiopian Arabica beans ΓÇö bold and smooth.',90.00,'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',1),(134,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(135,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(136,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(137,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(138,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(139,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(140,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(141,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(142,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(143,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(144,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(145,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(146,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(147,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(148,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(149,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(150,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(151,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(152,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(153,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(154,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(155,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(156,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(157,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(158,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(159,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(160,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(161,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(162,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(163,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(164,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(165,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(166,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(167,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(168,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(169,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(170,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(171,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(172,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(173,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(174,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(175,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(176,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(177,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(178,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(179,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(180,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(181,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(183,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(184,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(185,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(186,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(187,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(188,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(189,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(190,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(191,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(192,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(193,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(194,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(195,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(196,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(197,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(198,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(199,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(200,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(201,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(202,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(203,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(204,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(205,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(206,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(207,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(208,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(209,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(210,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(211,1,1,'Injera with Firfir','Spicy beef firfir served with fresh injera.',350.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(212,1,6,'Special Kitfo','Premium minced beef seasoned with mitmita.',1200.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(213,1,6,'Doro Wot','Traditional Ethiopian spicy chicken stew.',950.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(214,1,6,'Beyaynetu','Assortment of vegan stews on injera.',450.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(215,1,2,'Shiro Tegabino','Thick chickpea stew in a traditional clay pot.',320.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1),(216,1,4,'Ethio Coffee','Traditional roasted coffee.',60.00,'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500',1);
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movies`
--

DROP TABLE IF EXISTS `movies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `cinema` varchar(200) DEFAULT NULL,
  `showtime` datetime DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `poster_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('showing','upcoming','ended') DEFAULT 'showing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movies`
--

LOCK TABLES `movies` WRITE;
/*!40000 ALTER TABLE `movies` DISABLE KEYS */;
/*!40000 ALTER TABLE `movies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (2,1,2,2,450.00),(3,7,4,1,150.00),(4,8,65,2,620.00),(5,8,61,1,550.00),(7,9,2,1,450.00),(8,10,47,1,320.00),(9,10,48,1,280.00),(10,10,52,1,250.00),(12,11,2,1,450.00),(13,12,65,1,620.00),(14,12,61,1,550.00),(15,13,2,1,450.00),(16,13,155,1,320.00),(17,14,47,1,320.00),(18,14,48,1,280.00),(19,15,3,3,950.00),(20,15,4,1,150.00),(21,16,49,1,420.00),(22,16,52,1,250.00),(23,17,139,1,350.00),(24,17,2,1,450.00),(25,17,173,1,320.00),(26,18,205,1,350.00),(27,18,151,1,350.00),(28,18,175,1,350.00),(29,18,155,1,320.00);
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
  `customer_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','on_delivery','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--
LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,10,1,1750.00,'delivered','chapa','pending','2026-02-15 09:51:52'),(2,10,1,500.00,'delivered',NULL,'pending','2026-02-15 10:14:05'),(3,10,1,600.00,'delivered',NULL,'pending','2026-02-15 10:14:05'),(4,10,1,700.00,'delivered',NULL,'pending','2026-02-15 10:14:05'),(5,10,1,800.00,'delivered',NULL,'pending','2026-02-15 10:14:05'),(6,10,1,900.00,'delivered',NULL,'pending','2026-02-15 10:14:05'),(7,1,2,300.00,'pending','chapa','pending','2026-02-15 10:30:11'),(8,1,15,1940.00,'pending','chapa','paid','2026-02-15 10:31:28'),(9,10,1,950.00,'delivered','chapa','paid','2026-02-15 10:48:46'),(10,1,13,1000.00,'pending','chapa','paid','2026-02-15 10:53:58'),(11,10,1,950.00,'delivered','chapa','paid','2026-02-15 10:57:25'),(12,10,15,1320.00,'pending','chapa','paid','2026-02-15 18:17:04'),(13,10,1,920.00,'delivered','chapa','paid','2026-02-15 19:33:47'),(14,2,13,750.00,'pending','chapa','paid','2026-02-17 19:33:55'),(15,10,2,3150.00,'pending','chapa','paid','2026-02-18 10:23:21'),(16,10,13,820.00,'pending','chapa','paid','2026-02-19 09:18:04'),(17,10,1,1270.00,'delivered','chapa','paid','2026-02-19 10:33:10'),(18,10,1,1520.00,'delivered','chapa','paid','2026-02-19 11:47:41');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_history`
--

DROP TABLE IF EXISTS `payment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_status` (`payment_status`),
  CONSTRAINT `payment_history_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bus_bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_history`
--

LOCK TABLES `payment_history` WRITE;
/*!40000 ALTER TABLE `payment_history` DISABLE KEYS */;
INSERT INTO `payment_history` VALUES (1,1,1100.00,'cash','TXN-4C1748BFEE','completed','2026-02-17 19:28:52','Payment for booking BUS-1748BFEB via cash'),(2,2,1100.00,'cash','TXN-4C1F31D215','completed','2026-02-17 19:30:59','Payment for booking BUS-1F31D212 via cash'),(3,3,1100.00,'cash','TXN-6E86ECB0A7','completed','2026-02-19 10:39:43','Payment for booking BUS-86ECB0A4 via cash'),(4,4,1100.00,'cash','TXN-6F9413901A','completed','2026-02-19 11:51:29','Payment for booking BUS-94139015 via cash'),(5,5,1100.00,'cash','TXN-869E8E43DB','completed','2026-02-20 14:04:25','Payment for booking BUS-9E8E43CC via cash');
/*!40000 ALTER TABLE `payment_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_services`
--

DROP TABLE IF EXISTS `provider_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provider_services` (
  `provider_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`provider_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `provider_services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `home_service_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_services_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `home_service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_services`
--

LOCK TABLES `provider_services` WRITE;
/*!40000 ALTER TABLE `provider_services` DISABLE KEYS */;
INSERT INTO `provider_services` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(2,1),(2,2),(3,2),(3,6),(4,1),(5,3),(6,2),(6,6);
/*!40000 ALTER TABLE `provider_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `real_estate_inquiries`
--

DROP TABLE IF EXISTS `real_estate_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `real_estate_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Nullable for guest inquiries',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','responded','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `real_estate_inquiries_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `real_estate_properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `real_estate_inquiries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `real_estate_inquiries`
--

LOCK TABLES `real_estate_inquiries` WRITE;
/*!40000 ALTER TABLE `real_estate_inquiries` DISABLE KEYS */;
INSERT INTO `real_estate_inquiries` VALUES (1,2,NULL,'Alexander Haile','hailealexander128@gmail.com','12345','I am interested in Modern Apartment near Kazanchis. Please contact me.','new','2026-02-17 19:58:32');
/*!40000 ALTER TABLE `real_estate_inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `real_estate_properties`
--

DROP TABLE IF EXISTS `real_estate_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `real_estate_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `type` enum('sale','rent','lease') NOT NULL DEFAULT 'sale',
  `category` enum('apartment','house','villa','condominium','office','land','warehouse','commercial') NOT NULL,
  `location` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT 'Addis Ababa',
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `area_sqm` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `gallery_images` text DEFAULT NULL COMMENT 'JSON array of additional images',
  `amenities` text DEFAULT NULL COMMENT 'JSON array of amenities',
  `status` enum('available','sold','rented','pending') DEFAULT 'available',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `real_estate_properties_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `real_estate_properties`
--

LOCK TABLES `real_estate_properties` WRITE;
/*!40000 ALTER TABLE `real_estate_properties` DISABLE KEYS */;
INSERT INTO `real_estate_properties` VALUES (1,4,'Luxury Villa in Bole','Stunning 5-bedroom villa with a large garden and modern amenities.',25000000.00,'sale','villa','Bole, Addis Ababa','Addis Ababa',5,4,500.00,'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',1,'2026-02-17 19:47:35','2026-02-17 19:47:35'),(2,4,'Modern Apartment near Kazanchis','3-bedroom apartment with city view, fully furnished.',45000.00,'rent','apartment','Kazanchis, Addis Ababa','Addis Ababa',3,2,120.00,'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',1,'2026-02-17 19:47:35','2026-02-17 19:47:35'),(3,4,'Commercial Space in Piassa','Prime office location in the historic center.',80000.00,'rent','office','Piassa, Addis Ababa','Addis Ababa',0,2,200.00,'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',0,'2026-02-17 19:47:35','2026-02-17 19:47:35'),(4,4,'G+2 House in CMC','Spacious family home in a quiet neighborhood.',18500000.00,'sale','house','CMC, Addis Ababa','Addis Ababa',6,5,350.00,'https://images.unsplash.com/photo-1600596542815-2250657d2fc5?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',0,'2026-02-17 19:47:35','2026-02-17 19:47:35'),(5,4,'Furnished Condo in Sarbet','Cozy 2-bedroom condo, perfect for expats.',30000.00,'rent','condominium','Sarbet, Addis Ababa','Addis Ababa',2,1,90.00,'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',1,'2026-02-17 19:47:35','2026-02-17 19:47:35'),(6,4,'Plot of Land in Ayat','500sqm residential land ready for construction.',6000000.00,'sale','land','Ayat, Addis Ababa','Addis Ababa',0,0,500.00,'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',NULL,NULL,'available',0,'2026-02-17 19:47:35','2026-02-17 19:47:35');
/*!40000 ALTER TABLE `real_estate_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referrals`
--

DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `broker_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `broker_id` (`broker_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`broker_id`) REFERENCES `brokers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referrals`
--

LOCK TABLES `referrals` WRITE;
/*!40000 ALTER TABLE `referrals` DISABLE KEYS */;
/*!40000 ALTER TABLE `referrals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `platform` varchar(50) DEFAULT 'WEB',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `activity_type` (`activity_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--
LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rental_requests`
--
DROP TABLE IF EXISTS `rental_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rental_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `rental_requests_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_requests_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_requests`
--

LOCK TABLES `rental_requests` WRITE;
/*!40000 ALTER TABLE `rental_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `rental_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_menu`
--

DROP TABLE IF EXISTS `restaurant_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `restaurant_menu_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_menu`
--

LOCK TABLES `restaurant_menu` WRITE;
/*!40000 ALTER TABLE `restaurant_menu` DISABLE KEYS */;
/*!40000 ALTER TABLE `restaurant_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_order_items`
--

DROP TABLE IF EXISTS `restaurant_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `restaurant_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `restaurant_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_order_items`
--

LOCK TABLES `restaurant_order_items` WRITE;
/*!40000 ALTER TABLE `restaurant_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `restaurant_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_orders`
--

DROP TABLE IF EXISTS `restaurant_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `order_reference` varchar(30) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','ready','on_delivery','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_reference` (`order_reference`),
  KEY `customer_id` (`customer_id`),
  KEY `restaurant_id` (`restaurant_id`),
  CONSTRAINT `restaurant_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `restaurant_orders_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_orders`
--

LOCK TABLES `restaurant_orders` WRITE;
/*!40000 ALTER TABLE `restaurant_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `restaurant_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurants`
--

DROP TABLE IF EXISTS `restaurants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cuisine_type` varchar(100) DEFAULT NULL,
  `opening_hours` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `delivery_time` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurants`
--

LOCK TABLES `restaurants` WRITE;
/*!40000 ALTER TABLE `restaurants` DISABLE KEYS */;
/*!40000 ALTER TABLE `restaurants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `distance_km` decimal(10,2) DEFAULT NULL,
  `estimated_hours` decimal(4,1) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `transport_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `routes`
--

LOCK TABLES `routes` WRITE;
/*!40000 ALTER TABLE `routes` DISABLE KEYS */;
INSERT INTO `routes` VALUES (1,1,'Addis Ababa','Hawassa',275.00,5.0,650.00,1),(2,1,'Addis Ababa','Bahir Dar',565.00,10.0,950.00,1),(3,1,'Addis Ababa','Dire Dawa',515.00,9.0,900.00,1),(4,2,'Addis Ababa','Hawassa',275.00,5.0,550.00,1),(5,2,'Addis Ababa','Jimma',352.00,6.0,700.00,1),(6,2,'Addis Ababa','Gondar',738.00,12.0,1100.00,1),(7,3,'Addis Ababa','Hawassa',275.00,5.0,850.00,1),(8,3,'Addis Ababa','Mekelle',783.00,12.0,1200.00,1),(9,4,'Addis Ababa','Hawassa',275.00,5.0,450.00,1),(10,4,'Addis Ababa','Adama',99.00,2.0,250.00,1),(11,4,'Addis Ababa','Bahir Dar',565.00,10.0,750.00,1),(12,5,'Addis Ababa','Hawassa',275.00,5.0,500.00,1),(13,5,'Addis Ababa','Dire Dawa',515.00,9.0,800.00,1);
/*!40000 ALTER TABLE `routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `operating_days` varchar(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bus_id` (`bus_id`),
  KEY `route_id` (`route_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,1,1,'06:00:00','11:00:00',750.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(2,1,1,'14:00:00','19:00:00',750.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(3,2,2,'20:00:00','06:00:00',1100.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(4,3,4,'07:00:00','12:00:00',600.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(5,3,4,'15:00:00','20:00:00',600.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(6,4,5,'08:00:00','14:00:00',800.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(7,5,7,'06:30:00','11:30:00',950.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(8,6,8,'18:00:00','06:00:00',1400.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(9,7,9,'05:00:00','10:00:00',500.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(10,7,9,'12:00:00','17:00:00',500.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(11,8,10,'06:00:00','08:00:00',300.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(12,8,10,'10:00:00','12:00:00',300.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(13,8,10,'14:00:00','16:00:00',300.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(14,9,12,'07:00:00','12:00:00',550.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(15,9,12,'16:00:00','21:00:00',550.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01'),(16,10,13,'06:00:00','15:00:00',900.00,'Mon,Tue,Wed,Thu,Fri,Sat,Sun',1,'2026-02-15 09:28:01');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seat_bookings`
--

DROP TABLE IF EXISTS `seat_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seat_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `seat_number` varchar(10) NOT NULL,
  `passenger_name` varchar(100) DEFAULT NULL,
  `passenger_phone` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `seat_bookings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bus_bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seat_bookings`
--

LOCK TABLES `seat_bookings` WRITE;
/*!40000 ALTER TABLE `seat_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `seat_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taxi_companies`
--

DROP TABLE IF EXISTS `taxi_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxi_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_vehicles` int(11) DEFAULT 0,
  `license_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `taxi_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxi_companies`
--

LOCK TABLES `taxi_companies` WRITE;
/*!40000 ALTER TABLE `taxi_companies` DISABLE KEYS */;
INSERT INTO `taxi_companies` VALUES (1,21,'Ride Ethiopia','Taxi service provider','+2518291','ride@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:39'),(2,23,'Feres Transport','Taxi service provider','+2516090','feres@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:39'),(3,25,'Yango Ethiopia','Taxi service provider','+251115170003','yango@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:39'),(4,39,'Safe Ride','Taxi service provider','+2518210','safe@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:39'),(5,40,'Little Ride','Taxi service provider','+2516000','little@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:39'),(6,41,'ZayRide','Taxi service provider','+2518199','zay@ethioserve.com',NULL,NULL,0.0,0,NULL,'approved','2026-02-15 10:20:40');
/*!40000 ALTER TABLE `taxi_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taxi_rides`
--

DROP TABLE IF EXISTS `taxi_rides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxi_rides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ride_reference` varchar(20) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `taxi_company_id` int(11) DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `fare` decimal(10,2) DEFAULT NULL,
  `status` enum('requested','accepted','in_progress','completed','cancelled') DEFAULT 'requested',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `passenger_name` varchar(100) DEFAULT NULL,
  `passenger_phone` varchar(20) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ride_reference` (`ride_reference`),
  KEY `customer_id` (`customer_id`),
  KEY `taxi_company_id` (`taxi_company_id`),
  CONSTRAINT `taxi_rides_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxi_rides_ibfk_2` FOREIGN KEY (`taxi_company_id`) REFERENCES `taxi_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxi_rides`
--

LOCK TABLES `taxi_rides` WRITE;
/*!40000 ALTER TABLE `taxi_rides` DISABLE KEYS */;
INSERT INTO `taxi_rides` VALUES (1,NULL,10,1,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(2,NULL,10,1,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(3,NULL,10,1,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(4,NULL,10,2,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(5,NULL,10,2,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(6,NULL,10,2,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(7,NULL,10,3,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(8,NULL,10,3,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(9,NULL,10,3,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(10,NULL,10,4,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(11,NULL,10,4,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(12,NULL,10,4,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(13,NULL,10,5,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(14,NULL,10,5,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:13'),(15,NULL,10,5,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:14'),(16,NULL,10,6,'Location A1','Location B1',300.00,'completed','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:14'),(17,NULL,10,6,'Location A2','Location B2',350.00,'in_progress','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:14'),(18,NULL,10,6,'Location A3','Location B3',400.00,'requested','paid',NULL,NULL,NULL,NULL,NULL,'2026-02-15 10:26:14'),(19,'RIDE-0C334D',24,3,'Addis Ababa, Addis Ababa','Bole International Airport',140.00,'requested','pending','cash','Mequannent Worku','0918592028','','','2026-02-15 10:56:32'),(20,'RIDE-B23BA9',24,1,'Addis Ababa, Addis Ababa','Bole International Airport',165.00,'requested','pending','cash','abebe','0918592028','','','2026-02-15 19:31:39'),(21,'RIDE-B07DB7',24,1,'CMC Area, Addis Ababa','Bole International Airport',165.00,'requested','pending','cash','abebe','0918592028','','','2026-02-17 19:37:31'),(22,'RIDE-1DB2F9',24,1,'CMC Area, Addis Ababa','Bole International Airport',261.00,'requested','pending','cash','abebe','0918592028','','','2026-02-18 17:16:50'),(23,'RIDE-BE0304',10,1,'Addis Ababa, Addis Ababa','Bole International Airport',165.00,'requested','pending','cash','Abeba Tadesse','','','','2026-02-19 10:31:23'),(24,'RIDE-8BD9CC',24,2,'Addis Ababa, Addis Ababa','Bole International Airport',256.00,'requested','pending','cash','abebe','0918592028','','','2026-02-19 11:46:16');
/*!40000 ALTER TABLE `taxi_rides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taxi_vehicles`
--
DROP TABLE IF EXISTS `taxi_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxi_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `taxi_vehicles_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `taxi_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Dumping data for table `taxi_vehicles`
--
LOCK TABLES `taxi_vehicles` WRITE;
/*!40000 ALTER TABLE `taxi_vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `taxi_vehicles` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `transport_companies`
--
DROP TABLE IF EXISTS `transport_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_buses` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transport_companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_companies`
--

LOCK TABLES `transport_companies` WRITE;
/*!40000 ALTER TABLE `transport_companies` DISABLE KEYS */;
INSERT INTO `transport_companies` VALUES (1,5,'Golden Bus','Premium intercity bus service with modern fleet. Comfortable seats, AC, and entertainment.',NULL,'+251911000001','golden@ethioserve.com','Lamberet Bus Station, Addis Ababa',4.5,25,'approved','2026-02-15 09:28:00'),(2,6,'Walya Bus','Reliable and affordable transport across Ethiopia. Known for punctuality.',NULL,'+251911000002','walya@ethioserve.com','Meskel Square Terminal, Addis Ababa',4.3,30,'approved','2026-02-15 09:28:00'),(3,7,'Gion Bus','Luxury travel experience with VIP and sleeper options.',NULL,'+251911000003','gion@ethioserve.com','Bole International Airport Area',4.7,20,'approved','2026-02-15 09:28:00'),(4,8,'Geda Bus','Budget-friendly travel with extensive route network.',NULL,'+251911000004','geda@ethioserve.com','Mercato Bus Terminal',4.1,35,'approved','2026-02-15 09:28:00'),(5,9,'Awash Bus','Connecting major cities with comfortable standard buses.',NULL,'+251911000005','awash@ethioserve.com','Kazanchis Bus Station',4.2,28,'approved','2026-02-15 09:28:00'),(6,18,'Selam Bus Express','Fast and comfortable intercity travel.',NULL,'+251999999999','selam_bus@ethioserve.com','Autobus Tera, Addis Ababa',4.5,15,'approved','2026-02-15 09:28:59');
/*!40000 ALTER TABLE `transport_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','hotel','broker','transport','customer','restaurant','taxi','student','doctor','employer','dating','home_pro') DEFAULT 'customer',
  `grade` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Dumping data for table `users`
--
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (21,'ride_ethiopia','$2y$10$VDXfggb.i1BStAxeewVv9ed/QidgIlk/XWOJ7c3XT44c6N/B1dNsS','ride@ethioserve.com','Ride Ethiopia','+251911100100','taxi',0,'2026-02-15 09:29:17'),(23,'feres','$2y$10$ObS3crijaW8ibrwSY9YOMuVi9ARUY9lfhEOuNNWOA6clbDoFIqNzm','feres@ethioserve.com','Feres Transport','+251911200200','taxi',0,'2026-02-15 09:29:28'),(24,'rider_0918592028','$2y$10$dL.bUCEfeH9e.3lj1H1T0eubtxh5uhrFxsaaZ.aS.vCdHaUO4iKhy','rider_0918592028@ethioserve.temp','Mequannent Worku','0918592028','customer',0,'2026-02-15 09:43:50'),(25,'yango','$2y$10$VjPBJVe3TncPndlv6HT.GuYRFnQFLBlTHtjJsg1UAWVyL.k4b35Gi','yango@ethioserve.com','Yango Ethiopia','+251911300300','taxi',0,'2026-02-15 10:04:19'),(26,'cust1','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust1@test.com','Abebe Kabede',NULL,'customer',0,'2026-02-15 10:19:48'),(27,'cust2','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust2@test.com','Tadesse Lemma',NULL,'customer',0,'2026-02-15 10:19:49'),(28,'cust3','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust3@test.com','Mulugeta Tesfaye',NULL,'customer',0,'2026-02-15 10:19:49'),(29,'cust4','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust4@test.com','Sara Hagos',NULL,'customer',0,'2026-02-15 10:19:49'),(30,'cust5','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust5@test.com','Daniel Girma',NULL,'customer',0,'2026-02-15 10:19:49'),(31,'cust6','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','cust6@test.com','Lidya Solomon',NULL,'customer',0,'2026-02-15 10:19:49'),(32,'haile_resort_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','haile@hailehotels.com','Haile Resort Admin',NULL,'hotel',0,'2026-02-15 10:19:49'),(33,'kuriftu_resort_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','info@kurifturesort.com','Kuriftu Resort Admin',NULL,'hotel',0,'2026-02-15 10:19:49'),(34,'skylight_hotel_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','info@ethiopianskylighthotel.com','Skylight Hotel Admin',NULL,'hotel',0,'2026-02-15 10:19:49'),(35,'elilly_international_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','info@elillyhotel.com','Elilly International Admin',NULL,'hotel',0,'2026-02-15 10:19:50'),(36,'jupiter_international_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','info@jupiterhotel.com','Jupiter International Admin',NULL,'hotel',0,'2026-02-15 10:19:50'),(37,'golden_tulip_owner','$2y$10$Td/vgNsfastC/uqSraemWupzMdkGzuP2E18JNdafnVc6g2UIJGMXS','info@goldentuliptana.com','Golden Tulip Admin',NULL,'hotel',0,'2026-02-15 10:19:50'),(39,'safe_ride','$2y$10$QrT66AqwtLkDVYsr5.LDH.VJcbvDnyU7TnFGUxoSMXUHUH.1J87CG','safe@ethioserve.com','Safe Ride','+2518210','taxi',0,'2026-02-15 10:20:39'),(40,'little_ride','$2y$10$QrT66AqwtLkDVYsr5.LDH.VJcbvDnyU7TnFGUxoSMXUHUH.1J87CG','little@ethioserve.com','Little Ride','+2516000','taxi',0,'2026-02-15 10:20:39'),(41,'zayride','$2y$10$QrT66AqwtLkDVYsr5.LDH.VJcbvDnyU7TnFGUxoSMXUHUH.1J87CG','zay@ethioserve.com','ZayRide','+2518199','taxi',0,'2026-02-15 10:20:39'),(47,'student1','$2y$10$UDuE.Vajj4nM1sEsVUoJw.j5Jx9/ZRtV0tj2hLAIHfZFVeRYMnwJa','student@ethioserve.com','Abebe Student',NULL,'student',0,'2026-02-15 11:26:52'),(48,'selam_dating','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','selam@demo.com','Selam Hailu',NULL,'dating',0,'2026-02-19 01:55:50'),(49,'dawit_dating','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','dawit@demo.com','Dawit Mekonnen',NULL,'dating',0,'2026-02-19 01:55:50'),(50,'beaza_dating','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','beaza@demo.com','Beaza Tadesse',NULL,'dating',0,'2026-02-19 01:55:50'),(51,'aman_dating','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','aman@demo.com','Aman Yoseph',NULL,'dating',0,'2026-02-19 01:55:51'),(52,'eden_dating','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','eden@demo.com','Eden Tesfaye',NULL,'dating',0,'2026-02-19 01:55:51'),(53,'test_dating_user','$2y$10$Jn77CVZ/wlgy6W/wULEQ5u/Gq9EWDp1cJVSz89tpuFmJWBMdxyYom','test_dating@ethioserve.com','Dating Tester',NULL,'customer',0,'2026-02-19 02:01:54'),(54,'lucy_restaurant','$2y$10$oMUZFVl7xXGA3xYeyYbTn.GXrowGcggAVtKYYK3JVCI0cDxrmAZna','rest@demo.com','Lucy Restaurant Pro','0922334455','restaurant',0,'2026-02-19 20:17:57'),(55,'ride_addis','$2y$10$w8QcdzYhSF8D2KOQH9mD.eBx4SP7vVkCmlCFwh3ZFfxdS9z7poBU2','taxi@demo.com','Ride Taxi Service','0933445566','taxi',0,'2026-02-19 20:17:58'),(56,'cloud_company','$2y$10$Sy5/s25BiCMIKDkzE1lK0ezxlvfhn6UybKRYCHeMSTju.1msqkr26','redcloud@demo.com','Red Cloud ICT Solution','0912121212','employer',0,'2026-02-19 20:17:59'),(57,'dr_dawit','$2y$10$eon3YfrBGLa45SD26ZbriuqhTO7Ssoso5UtVGcS.eg5i7hll3RW3O','doctor@demo.com','Dr. Dawit Telemed','0931313131','doctor',0,'2026-02-19 20:17:59'),(58,'dr_abebe','$2y$10$mBsj6Ad84LdQkgxELS6SCOw7gi3SSQMSDpbML0elRfDq6gc6tIlhq','abebe@demo.com','Dr. Abebe Molla',NULL,'doctor',0,'2026-02-19 20:48:05'),(59,'taxi_demo','$2y$10$QR27lAkFn49Nm2mShBJMXevMmEjf2ra2QmzY33QJBrs0/.QubqPUm','taxi_demo@ethioserve.com','Taxi Demo Manager','0933346272','taxi',0,'2026-02-20 06:34:03'),(60,'hotel_demo','$2y$10$xw9cBTyxcayyj8F7L.pLiOesLaV0OQiIwDcGPe7lyk6gR8iZ9OHUW','hotel_demo@ethioserve.com','Hotel Demo Manager','0962481243','hotel',0,'2026-02-20 06:34:04'),(61,'restaurant_demo','$2y$10$wR5iKyq3/U2TcLTaMuuE0.Le.cejHG/Ml1PTUKlyekMoATIewQ226','restaurant_demo@ethioserve.com','Restaurant Demo Owner','0951973428','restaurant',0,'2026-02-20 06:34:04'),(62,'transport_demo','$2y$10$EUuTsIxBY7zp6IYvYOP/GusnPwFYaefx8jDQA8bI6gT3T.qmD1Oo2','transport_demo@ethioserve.com','Transport Demo Owner','0969971395','transport',0,'2026-02-20 06:34:04'),(63,'broker_demo','$2y$10$WLBH615diDxd/lbJUU9KxOqIdiKrKdzlrCdWTIyAl9npRgtEPzvFu','broker_demo@ethioserve.com','Real Estate Broker Demo','0980877307','broker',0,'2026-02-20 06:34:05'),(64,'doctor_demo','$2y$10$Gaex8PDM/Cd9nPPPCmW0xOQqk4LhQRJN36TverB3ZcIEexO3VIUZW','doctor_demo@ethioserve.com','Doctor Demo Account','0990913558','doctor',0,'2026-02-20 06:34:05'),(65,'student_demo','$2y$10$CY0RDZUR76rtW8ePRDSM.eSYc2UUiJ1HNlY/ophstltX4YJUHQRzO','student_demo@ethioserve.com','Education Demo Student','0979209266','student',0,'2026-02-20 06:34:05'),(66,'customer_demo','$2y$10$Yjy.3qvbajV178FjvrWTY.NO0DOhqAIj3.ewayyPn.DynXF8O.4eW','customer_demo@ethioserve.com','General Customer Demo','0981047210','customer',0,'2026-02-20 06:34:05'),(67,'employer_demo','$2y$10$oO5bJ.XT8UCb2u4209fMwu/f0iprzT1YGXIFZOXy20Eq52zudORuK','employer_demo@ethioserve.com','Job Employer Demo','0944264441','employer',0,'2026-02-20 06:34:05'),(68,'dating_demo','$2y$10$QXUDphHZcdLrMMOFlinG.OXLvQdEZj2I21EfPJE.mMbNdtivKrdI.','dating_demo@ethioserve.com','Dating Demo User','0984533133','dating',0,'2026-02-20 06:34:06'),(69,'pro_demo','$2y$10$e1oybpCBU/wiDCR9xgKTaeAcFPTCGw5V997I0qG0fXhNvT/M7UFWO','pro_demo@ethioserve.com','Home Service Pro Demo','0911731197','home_pro',0,'2026-02-20 06:34:06'),(70,'mequannent','$2y$10$dhDVJHHp9.u1H53f1e462u00ZzEn29DKpe2L/B6btVB9g1DQQ0qYK','mequannent@ethioserve.com','Mequannent G.',NULL,'home_pro',0,'2026-02-20 06:38:12'),(71,'chala','$2y$10$RdtT1fafaruDN6GuDYyOieqM17zGSMyBtFDNU2m9N4xeVqDsj46qy','chala@ethioserve.com','Chala K.',NULL,'home_pro',0,'2026-02-20 06:38:13'),(72,'bekele','$2y$10$yxWiX4bdzgM7NM9ErNbu0uEaoqrPwqjzqP3R1fUMJ/apXnoMDmyue','bekele@ethioserve.com','Bekele T.',NULL,'home_pro',0,'2026-02-20 06:38:13'),(73,'muluken','$2y$10$PBdXBzSj.m8/Y5g4qQIvA.MDy0hCX2ykIie16nAGuzzstSddmJGUi','muluken@ethioserve.com','Muluken S.',NULL,'home_pro',0,'2026-02-20 06:38:14'),(74,'Walelgn','$2y$10$18xfLp8E9mb5ArT3rL2ooe8rSbS2fyPSIW0HmZNS.ap/mw3wH34Au','mequannentgashaw12@gmail.com','Gedamu','+251912698553','customer',0,'2026-02-21 12:51:51'),(75,'Gedamu','$2y$10$ugVfJlyAMjntsRD71hpqD.eX4K6xyLz65QYWpQZzS3gKk0cmu7HK.','gedualpha1989@gmail.com','Gedamu','+251912627366','customer',0,'2026-02-21 13:05:03');/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
--
-- Table structure for table `video_calls`
--
DROP TABLE IF EXISTS `video_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caller_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `room_id` varchar(255) NOT NULL,
  `status` enum('pending','accepted','rejected','ended') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `caller_id` (`caller_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `video_calls_ibfk_1` FOREIGN KEY (`caller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_calls_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Dumping data for table `video_calls`
--
LOCK TABLES `video_calls` WRITE;
/*!40000 ALTER TABLE `video_calls` DISABLE KEYS */;
/*!40000 ALTER TABLE `video_calls` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
-- Dump completed on 2026-02-23 17:32:45
