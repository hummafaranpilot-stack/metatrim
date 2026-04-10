-- ================================================================
-- CLOAKER DATABASE SETUP
-- Run this once in phpMyAdmin on database: u373133718_shavingdb
-- ================================================================

CREATE TABLE IF NOT EXISTS `cloaker_configs` (
  `id`                int AUTO_INCREMENT PRIMARY KEY,
  `domain`            varchar(255) UNIQUE NOT NULL,
  `white_page_url`    text NOT NULL,
  `black_page_url`    text NOT NULL,
  `is_active`         tinyint(1) DEFAULT 0,
  `filter_bots`       tinyint(1) DEFAULT 1,
  `filter_vpn`        tinyint(1) DEFAULT 1,
  `filter_datacenter` tinyint(1) DEFAULT 1,
  `ipqs_threshold`    tinyint DEFAULT 60,
  `blocked_countries` varchar(500) DEFAULT '',
  `allowed_referrers` varchar(1000) DEFAULT '',
  `ip_blacklist`      text DEFAULT '',
  `hits_total`        int DEFAULT 0,
  `hits_white`        int DEFAULT 0,
  `hits_black`        int DEFAULT 0,
  `last_hit`          datetime DEFAULT NULL,
  `created_at`        timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_domain` (`domain`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cloaker_logs` (
  `id`          int AUTO_INCREMENT PRIMARY KEY,
  `domain`      varchar(255) NOT NULL,
  `ip`          varchar(45) NOT NULL,
  `user_agent`  varchar(500) DEFAULT '',
  `referrer`    varchar(500) DEFAULT '',
  `country`     varchar(10) DEFAULT '',
  `fraud_score` tinyint DEFAULT 0,
  `is_vpn`      tinyint(1) DEFAULT 0,
  `is_bot`      tinyint(1) DEFAULT 0,
  `routed_to`   enum('white','black') NOT NULL,
  `reason`      varchar(50) DEFAULT '',
  `created_at`  timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_domain` (`domain`),
  KEY `idx_routed_to` (`routed_to`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_domain_date` (`domain`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- Done. Two tables created: cloaker_configs and cloaker_logs
-- ================================================================
