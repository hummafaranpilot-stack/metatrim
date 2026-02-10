-- ================================================================
-- UNIFIED ANALYTICS DASHBOARD - DATABASE SETUP
-- ================================================================
-- Version: 2.0
-- Date: February 2026
--
-- IMPORTANT: This file shows the combined database structure needed
-- for both Shaving Analytics and BuyGoods Analytics.
--
-- You likely already have these tables from your existing projects.
-- This file is for reference and new installations only.
-- ================================================================

-- ================================================================
-- SHAVING ANALYTICS TABLES
-- ================================================================

-- Table: shaving_sessions
-- Stores active and stopped shaving sessions
CREATE TABLE IF NOT EXISTS `shaving_sessions` (
  `id` varchar(50) PRIMARY KEY,
  `affId` varchar(100) NOT NULL,
  `subId` varchar(100) DEFAULT NULL,
  `replaceMode` tinyint(1) DEFAULT 0,
  `replaceAffId` varchar(100) DEFAULT NULL,
  `replaceSubId` varchar(100) DEFAULT NULL,
  `startTime` bigint NOT NULL,
  `stopTime` bigint DEFAULT NULL,
  `visits` int DEFAULT 0,
  `clicks` int DEFAULT 0,
  `status` enum('active', 'stopped') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_affId` (`affId`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: traffic_log
-- Stores all visitor tracking data
CREATE TABLE IF NOT EXISTS `traffic_log` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `sessionId` varchar(50) NOT NULL,
  `affId` varchar(100) DEFAULT NULL,
  `subId` varchar(100) DEFAULT NULL,
  `landingPage` text,
  `referrer` text,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `shaved` enum('yes', 'no') DEFAULT 'no',
  `checkoutReached` tinyint(1) DEFAULT 0,
  `country` varchar(100) DEFAULT NULL,
  `device` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  KEY `idx_sessionId` (`sessionId`),
  KEY `idx_affId` (`affId`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: behavior_events
-- Stores user behavior tracking (scroll, clicks, etc.)
CREATE TABLE IF NOT EXISTS `behavior_events` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `sessionId` varchar(50) NOT NULL,
  `eventType` varchar(50) NOT NULL,
  `eventData` text,
  `scrollDepth` int DEFAULT NULL,
  `timeOnPage` int DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sessionId` (`sessionId`),
  KEY `idx_eventType` (`eventType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- BUYGOODS ANALYTICS TABLES
-- ================================================================

-- Table: orders
-- Stores all BuyGoods orders from webhooks
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `order_id` varchar(100) UNIQUE NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `product_id` varchar(100) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `profit` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('completed', 'pending', 'refunded', 'chargeback') DEFAULT 'completed',
  `payment_method` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fraud_score` int DEFAULT NULL,
  `is_proxy` tinyint(1) DEFAULT 0,
  `country` varchar(100) DEFAULT NULL,
  `affiliate_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: recurring_charges
-- Stores subscription/recurring payment data
CREATE TABLE IF NOT EXISTS `recurring_charges` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `charge_id` varchar(100) UNIQUE NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('active', 'cancelled', 'failed') DEFAULT 'active',
  `next_billing_date` date DEFAULT NULL,
  `billing_cycle` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: refunds
-- Stores refund/cancellation data
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `refund_id` varchar(100) UNIQUE NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `refund_type` enum('full', 'partial') DEFAULT 'full',
  `reason` text,
  `status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: chargebacks
-- Stores chargeback/dispute data
CREATE TABLE IF NOT EXISTS `chargebacks` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `chargeback_id` varchar(100) UNIQUE NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` text,
  `status` enum('pending', 'won', 'lost') DEFAULT 'pending',
  `dispute_date` date DEFAULT NULL,
  `resolution_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: webhook_logs
-- Stores all incoming webhook events for debugging
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `event_id` varchar(100) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `payload` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success', 'failed', 'error') DEFAULT 'success',
  `error_message` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_event_type` (`event_type`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: products (derived/aggregated data)
-- Optional: Stores product performance metrics
CREATE TABLE IF NOT EXISTS `products` (
  `id` int AUTO_INCREMENT PRIMARY KEY,
  `product_id` varchar(100) UNIQUE NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `total_orders` int DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `total_refunds` int DEFAULT 0,
  `refund_rate` decimal(5,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- INDEXES FOR PERFORMANCE
-- ================================================================

-- Add additional indexes for common queries
ALTER TABLE `traffic_log` ADD INDEX `idx_shaved_timestamp` (`shaved`, `timestamp`);
ALTER TABLE `orders` ADD INDEX `idx_status_created` (`status`, `created_at`);
ALTER TABLE `webhook_logs` ADD INDEX `idx_status_created` (`status`, `created_at`);

-- ================================================================
-- NOTES
-- ================================================================

-- 1. If you already have these tables from existing projects,
--    you DO NOT need to run this file.
--
-- 2. Make sure to update config.php with correct database credentials.
--
-- 3. For BuyGoods webhooks to work, ensure your webhook.php is
--    accessible at: https://your-domain.com/dashboard-v2/webhook.php
--
-- 4. IPQualityScore API key is needed for fraud detection.
--    Get one at: https://www.ipqualityscore.com/
--
-- 5. Pakistan Time (PKT) filtering is handled in JavaScript,
--    no database timezone changes needed.
--
-- ================================================================
-- END OF DATABASE SETUP
-- ================================================================
