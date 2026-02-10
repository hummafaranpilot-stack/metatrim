<?php
/**
 * BuyGoods Analytics - Products Migration
 * Adds products table and tracked_product_id column to orders
 * Run this once to enable multi-product tracking
 */

require_once 'config.php';

$isCli = php_sapi_name() === 'cli';

function output($message, $isCli) {
    if ($isCli) {
        echo strip_tags($message) . "\n";
    } else {
        echo "<p>$message</p>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Products Migration</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}";
    echo "pre{background:#1e293b;color:#fff;padding:15px;border-radius:8px;overflow-x:auto;}";
    echo "h1{color:#1e293b;}</style></head><body>";
    echo "<h1>Multi-Product Migration</h1>";
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to database</span>", $isCli);

    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        cost_price DECIMAL(10, 2) DEFAULT 0.00,
        webhook_token VARCHAR(64) UNIQUE NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_token (webhook_token),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    output("<span class='success'>✓ Products table created</span>", $isCli);

    // Add tracked_product_id column to orders table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'tracked_product_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tracked_product_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_tracked_product (tracked_product_id)");
        output("<span class='success'>✓ Added tracked_product_id column to orders</span>", $isCli);
    } else {
        output("<span class='info'>ℹ tracked_product_id column already exists</span>", $isCli);
    }

    // Add tracked_product_id column to recurring_charges table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM recurring_charges LIKE 'tracked_product_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE recurring_charges ADD COLUMN tracked_product_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE recurring_charges ADD INDEX idx_tracked_product (tracked_product_id)");
        output("<span class='success'>✓ Added tracked_product_id column to recurring_charges</span>", $isCli);
    } else {
        output("<span class='info'>ℹ tracked_product_id already exists in recurring_charges</span>", $isCli);
    }

    // Add tracked_product_id column to refunds table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM refunds LIKE 'tracked_product_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE refunds ADD COLUMN tracked_product_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE refunds ADD INDEX idx_tracked_product (tracked_product_id)");
        output("<span class='success'>✓ Added tracked_product_id column to refunds</span>", $isCli);
    } else {
        output("<span class='info'>ℹ tracked_product_id already exists in refunds</span>", $isCli);
    }

    // Add tracked_product_id column to chargebacks table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM chargebacks LIKE 'tracked_product_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE chargebacks ADD COLUMN tracked_product_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE chargebacks ADD INDEX idx_tracked_product (tracked_product_id)");
        output("<span class='success'>✓ Added tracked_product_id column to chargebacks</span>", $isCli);
    } else {
        output("<span class='info'>ℹ tracked_product_id already exists in chargebacks</span>", $isCli);
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ Migration completed successfully!</strong></span>", $isCli);
    output("", $isCli);
    output("<span class='info'>Next steps:</span>", $isCli);
    output("1. Open admin.html to add your products", $isCli);
    output("2. Copy the generated webhook URLs to BuyGoods", $isCli);
    output("3. Delete this migrate-products.php file for security", $isCli);

    if (!$isCli) {
        echo "<p><a href='admin.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;margin-right:10px;'>Open Admin Panel</a>";
        echo "<a href='index.html' style='display:inline-block;padding:10px 20px;background:#10b981;color:white;text-decoration:none;border-radius:6px;'>Open Dashboard</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
