-- Add financial columns to orders table if they don't exist
-- Run this before running update-order-financials.php

ALTER TABLE orders
ADD COLUMN IF NOT EXISTS sku_pattern VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS base_price DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS taxes DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS processing_fee DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS allowance_hold DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS net_amount DECIMAL(10, 2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS is_upsell TINYINT(1) DEFAULT 0;

-- Add indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_sku_pattern ON orders(sku_pattern);
