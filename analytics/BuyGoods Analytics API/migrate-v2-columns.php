<?php
/**
 * BuyGoods Analytics - V2 Columns Migration
 * Adds financial tracking columns to orders table
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
    echo "<!DOCTYPE html><html><head><title>V2 Columns Migration</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}";
    echo "h1{color:#1e293b;}</style></head><body>";
    echo "<h1>V2 Financial Columns Migration</h1>";
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to database</span>", $isCli);

    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $newColumns = [
        'base_price' => 'DECIMAL(10, 2) DEFAULT NULL COMMENT "Base package price from product_pricing"',
        'taxes' => 'DECIMAL(10, 2) DEFAULT NULL COMMENT "Taxes = Total Collected - Base Price"',
        'processing_fee' => 'DECIMAL(10, 2) DEFAULT NULL COMMENT "BuyGoods Payment Processing Fee (10%)"',
        'allowance_hold' => 'DECIMAL(10, 2) DEFAULT NULL COMMENT "BuyGoods Allowance Hold (10%)"',
        'net_amount' => 'DECIMAL(10, 2) DEFAULT NULL COMMENT "Total - Processing Fee - Allowance - Commission"',
        'sku_pattern' => 'VARCHAR(50) DEFAULT NULL COMMENT "Normalized SKU pattern for pricing lookup"',
        'is_upsell' => 'TINYINT(1) DEFAULT 0 COMMENT "1 if backend upsell order"'
    ];

    $addedColumns = [];
    $skippedColumns = [];

    foreach ($newColumns as $colName => $colDef) {
        if (in_array($colName, $columns)) {
            $skippedColumns[] = $colName;
        } else {
            $sql = "ALTER TABLE orders ADD COLUMN $colName $colDef";
            $pdo->exec($sql);
            $addedColumns[] = $colName;
        }
    }

    if (!empty($addedColumns)) {
        output("<span class='success'>✓ Added columns: " . implode(', ', $addedColumns) . "</span>", $isCli);
    }

    if (!empty($skippedColumns)) {
        output("<span class='info'>ℹ Columns already exist: " . implode(', ', $skippedColumns) . "</span>", $isCli);
    }

    // Add index on sku_pattern for faster lookups
    try {
        $pdo->exec("CREATE INDEX idx_orders_sku ON orders (sku_pattern)");
        output("<span class='success'>✓ Added index on sku_pattern</span>", $isCli);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            output("<span class='info'>ℹ Index on sku_pattern already exists</span>", $isCli);
        } else {
            throw $e;
        }
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ V2 Migration completed successfully!</strong></span>", $isCli);

    if (!$isCli) {
        echo "<p><a href='admin.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;'>Go to Admin Panel</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
