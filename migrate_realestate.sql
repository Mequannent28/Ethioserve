-- ============================================================
-- EthioServe Real Estate Feature Migration
-- Run this in phpMyAdmin or via MySQL CLI
-- ============================================================

-- 1. Add "Property Owner" role support (separate from Broker)
-- The `users` table already has role enum. We need to extend it.
ALTER TABLE `users` MODIFY COLUMN `role` 
  ENUM('customer','hotel','broker','property_owner','transport','restaurant','taxi','student','dating','employer','teacher','parent','school_admin','doctor') 
  NOT NULL DEFAULT 'customer';

-- 2. Rental chat messages (property owner <-> customer per request)
CREATE TABLE IF NOT EXISTS `rental_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL COMMENT 'rental_requests.id',
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `message_type` enum('text','image','payment_proof') DEFAULT 'text',
  `file_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `rch_req_fk` FOREIGN KEY (`request_id`) REFERENCES `rental_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rch_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Add broker_id + referred_from to rental_requests (for optional commission)
ALTER TABLE `rental_requests`
  ADD COLUMN IF NOT EXISTS `broker_id` int(11) DEFAULT NULL COMMENT 'If via referral',
  ADD COLUMN IF NOT EXISTS `referral_code_used` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `chat_initiated` tinyint(1) DEFAULT 0;

-- 4. Payment methods for property owners (QR, Bank, Mobile)
CREATE TABLE IF NOT EXISTS `owner_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `method_type` enum('telebirr','cbe_birr','awash','dashen','bank_transfer','other') DEFAULT 'telebirr',
  `account_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `qr_image_path` varchar(500) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `opm_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Payment proofs submitted by customers
CREATE TABLE IF NOT EXISTS `rental_payment_proofs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image_path` varchar(500) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `rpp_req_fk` FOREIGN KEY (`request_id`) REFERENCES `rental_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Index for fast chat lookup
CREATE INDEX IF NOT EXISTS `idx_chat_request` ON `rental_chat_messages` (`request_id`, `created_at`);
