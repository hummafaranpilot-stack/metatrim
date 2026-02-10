-- Create withdrawals table to track BuyGoods payouts
-- Run this SQL in your database (phpMyAdmin or MySQL client)

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    withdrawal_date DATE NOT NULL,
    note VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_id (product_id),
    INDEX idx_withdrawal_date (withdrawal_date),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
