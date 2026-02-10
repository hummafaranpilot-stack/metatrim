-- BuyGoods Analytics Database Schema
-- Run this script to create the required tables

CREATE DATABASE IF NOT EXISTS buygoods_analytics;
USE buygoods_analytics;

-- Orders table - stores all new orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    transaction_id VARCHAR(100),
    product_id VARCHAR(100),
    product_name VARCHAR(255),
    product_price DECIMAL(10, 2),
    quantity INT DEFAULT 1,
    customer_email VARCHAR(255),
    customer_name VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_country VARCHAR(100),
    customer_state VARCHAR(100),
    customer_city VARCHAR(100),
    customer_address TEXT,
    customer_zip VARCHAR(20),
    affiliate_id VARCHAR(100),
    affiliate_name VARCHAR(255),
    commission DECIMAL(10, 2),
    payment_method VARCHAR(50),
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'refunded', 'cancelled', 'chargeback', 'fulfilled') DEFAULT 'completed',
    ip_address VARCHAR(45),
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_product_id (product_id),
    INDEX idx_affiliate_id (affiliate_id)
);

-- Recurring charges table
CREATE TABLE IF NOT EXISTS recurring_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    charge_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(100),
    transaction_id VARCHAR(100),
    product_id VARCHAR(100),
    product_name VARCHAR(255),
    amount DECIMAL(10, 2),
    customer_email VARCHAR(255),
    customer_name VARCHAR(255),
    affiliate_id VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('success', 'failed', 'refunded') DEFAULT 'success',
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_charge_id (charge_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Refunds table
CREATE TABLE IF NOT EXISTS refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(100),
    transaction_id VARCHAR(100),
    amount DECIMAL(10, 2),
    reason TEXT,
    refund_type ENUM('full', 'partial') DEFAULT 'full',
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_refund_id (refund_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Cancellations table
CREATE TABLE IF NOT EXISTS cancellations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cancel_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(100),
    reason TEXT,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cancel_id (cancel_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Chargebacks table
CREATE TABLE IF NOT EXISTS chargebacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chargeback_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(100),
    transaction_id VARCHAR(100),
    amount DECIMAL(10, 2),
    reason TEXT,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chargeback_id (chargeback_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Fulfillments table
CREATE TABLE IF NOT EXISTS fulfillments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fulfillment_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(100),
    tracking_number VARCHAR(255),
    carrier VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fulfillment_id (fulfillment_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Webhook logs table - for debugging and auditing
CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    payload JSON,
    ip_address VARCHAR(45),
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_processed (processed)
);

-- Daily statistics summary table (for faster dashboard queries)
CREATE TABLE IF NOT EXISTS daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE UNIQUE NOT NULL,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    total_refunds INT DEFAULT 0,
    refund_amount DECIMAL(12, 2) DEFAULT 0,
    total_chargebacks INT DEFAULT 0,
    chargeback_amount DECIMAL(12, 2) DEFAULT 0,
    total_cancellations INT DEFAULT 0,
    total_recurring INT DEFAULT 0,
    recurring_revenue DECIMAL(12, 2) DEFAULT 0,
    net_revenue DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stat_date (stat_date)
);
