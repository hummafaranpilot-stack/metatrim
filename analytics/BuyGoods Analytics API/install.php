<?php
/**
 * BuyGoods Analytics - Database Installer
 * Run this once to create all required database tables
 */

require_once 'config.php';

// Check if this is being run from CLI or browser
$isCli = php_sapi_name() === 'cli';

function output($message, $isCli) {
    if ($isCli) {
        echo $message . "\n";
    } else {
        echo "<p>$message</p>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>BuyGoods Analytics - Installer</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}";
    echo "pre{background:#1e293b;color:#fff;padding:15px;border-radius:8px;overflow-x:auto;}";
    echo "h1{color:#1e293b;}</style></head><body>";
    echo "<h1>BuyGoods Analytics - Database Installer</h1>";
}

try {
    // Connect without database first
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to MySQL server</span>", $isCli);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    output("<span class='success'>✓ Database '" . DB_NAME . "' created/verified</span>", $isCli);

    // Select database
    $pdo->exec("USE `" . DB_NAME . "`");

    // Create tables
    $tables = [
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
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
            ip_country VARCHAR(10),
            ip_city VARCHAR(100),
            ip_region VARCHAR(100),
            ip_proxy BOOLEAN DEFAULT FALSE,
            ip_tor BOOLEAN DEFAULT FALSE,
            ip_fraud_score INT DEFAULT 0,
            ip_analyzed BOOLEAN DEFAULT FALSE,
            raw_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX idx_fraud_score (ip_fraud_score)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'recurring_charges' => "CREATE TABLE IF NOT EXISTS recurring_charges (
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
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'refunds' => "CREATE TABLE IF NOT EXISTS refunds (
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
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'cancellations' => "CREATE TABLE IF NOT EXISTS cancellations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cancel_id VARCHAR(100) UNIQUE NOT NULL,
            order_id VARCHAR(100),
            reason TEXT,
            raw_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cancel_id (cancel_id),
            INDEX idx_order_id (order_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'chargebacks' => "CREATE TABLE IF NOT EXISTS chargebacks (
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
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'fulfillments' => "CREATE TABLE IF NOT EXISTS fulfillments (
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
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'webhook_logs' => "CREATE TABLE IF NOT EXISTS webhook_logs (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $name => $sql) {
        $pdo->exec($sql);
        output("<span class='success'>✓ Table '$name' created/verified</span>", $isCli);
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ Installation completed successfully!</strong></span>", $isCli);
    output("", $isCli);
    output("<span class='info'>Next steps:</span>", $isCli);
    output("1. Delete this install.php file for security", $isCli);
    output("2. Open index.html to view your dashboard", $isCli);
    output("3. Configure webhook URLs in BuyGoods", $isCli);

    if (!$isCli) {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
        echo "<h2>Your Webhook URLs</h2>";
        echo "<p>Copy these URLs to your BuyGoods Global IPNs settings:</p>";
        echo "<pre>";
        echo "New order URL:       {$baseUrl}/webhook.php?type=new-order\n";
        echo "Recurring charge URL: {$baseUrl}/webhook.php?type=recurring\n";
        echo "Order refund URL:    {$baseUrl}/webhook.php?type=refund\n";
        echo "Order cancel URL:    {$baseUrl}/webhook.php?type=cancel\n";
        echo "Order chargeback URL: {$baseUrl}/webhook.php?type=chargeback\n";
        echo "Order fulfilled URL: {$baseUrl}/webhook.php?type=fulfilled\n";
        echo "</pre>";
        echo "<p><a href='index.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;'>Open Dashboard</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
    output("", $isCli);
    output("<span class='info'>Please check your database credentials in config.php</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
