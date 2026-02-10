<?php
/**
 * BuyGoods Analytics - Recurring Price Migration
 * Adds recurring_price column for Subscribe & Save products
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
    echo "<!DOCTYPE html><html><head><title>Recurring Price Migration</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}";
    echo "h1{color:#1e293b;}</style></head><body>";
    echo "<h1>Recurring Price Migration</h1>";
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to database</span>", $isCli);

    // Check if column already exists
    $stmt = $pdo->query("DESCRIBE product_pricing");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('recurring_price', $columns)) {
        $pdo->exec("ALTER TABLE product_pricing ADD COLUMN recurring_price DECIMAL(10, 2) DEFAULT NULL COMMENT 'Recurring charge price for subscriptions' AFTER base_price");
        output("<span class='success'>✓ Added recurring_price column</span>", $isCli);
    } else {
        output("<span class='info'>ℹ recurring_price column already exists</span>", $isCli);
    }

    // Update existing subscription entries with recurring prices
    // MetaTrim Subscribe & Save
    $updates = [
        // MetaTrim subscriptions
        ['met2sub', 110.00],  // 2 Bottles: recurring $110 + shipping every 2 months
        ['met4sub', 196.00],  // 4 Bottles: recurring $196 every 4 months
        ['met7sub', 238.00],  // 7 Bottles: recurring $238 every 6 months

        // ProstaPrime subscriptions (same pricing structure)
        ['pro2sub', 110.00],  // 2 Bottles: recurring $110 + shipping every 2 months
        ['pro4sub', 196.00],  // 4 Bottles: recurring $196 every 4 months
        ['pro7sub', 238.00],  // 7 Bottles: recurring $238 every 6 months
    ];

    $updateStmt = $pdo->prepare("UPDATE product_pricing SET recurring_price = ? WHERE sku_pattern = ?");
    $updatedCount = 0;

    foreach ($updates as [$sku, $recurringPrice]) {
        $updateStmt->execute([$recurringPrice, $sku]);
        if ($updateStmt->rowCount() > 0) {
            $updatedCount++;
        }
    }

    if ($updatedCount > 0) {
        output("<span class='success'>✓ Updated recurring prices for $updatedCount subscription entries</span>", $isCli);
    } else {
        output("<span class='info'>ℹ No subscription entries found to update (may need to run migrate-pricing.php first)</span>", $isCli);
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ Recurring Price Migration completed!</strong></span>", $isCli);

    if (!$isCli) {
        echo "<p><a href='admin.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;'>Go to Admin Panel</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
