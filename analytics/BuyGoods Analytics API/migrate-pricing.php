<?php
/**
 * BuyGoods Analytics - Product Pricing Migration
 * Creates product_pricing table and seeds initial data
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
    echo "<!DOCTYPE html><html><head><title>Product Pricing Migration</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}";
    echo "h1{color:#1e293b;}</style></head><body>";
    echo "<h1>Product Pricing Migration</h1>";
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to database</span>", $isCli);

    // Create product_pricing table
    $sql = "CREATE TABLE IF NOT EXISTS product_pricing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_type ENUM('metatrim', 'prostaprime') NOT NULL,
        sku_pattern VARCHAR(50) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        bottle_count INT NOT NULL,
        is_upsell BOOLEAN DEFAULT FALSE,
        is_subscription BOOLEAN DEFAULT FALSE,
        date_from DATE NULL,
        date_to DATE NULL,
        base_price DECIMAL(10, 2) NOT NULL,
        recurring_price DECIMAL(10, 2) DEFAULT NULL COMMENT 'Recurring charge price for subscriptions',
        shipping DECIMAL(10, 2) DEFAULT 0,
        notes VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sku (sku_pattern),
        INDEX idx_product_type (product_type),
        INDEX idx_dates (date_from, date_to),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    output("<span class='success'>✓ Table 'product_pricing' created/verified</span>", $isCli);

    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_pricing");
    $count = $stmt->fetch()['count'];

    if ($count > 0) {
        output("<span class='info'>ℹ Table already has $count records. Skipping seed.</span>", $isCli);
    } else {
        // Seed initial pricing data
        // Format: [product_type, sku_pattern, product_name, bottle_count, is_upsell, is_subscription, date_from, date_to, base_price, recurring_price, shipping, notes]
        $pricingData = [
            // ========== METATRIM - Frontend Offers ==========
            // Before Jan 11, 2026 - 1 Bottle
            ['metatrim', 'met1', 'Meta Trim BHB 1 Bottle', 1, 0, 0, null, '2026-01-10', 88.99, null, 19.99, '1 Bottle ($69 + $19.99 shipping)'],

            // Jan 13-29, 2026 - 2 Bottles
            ['metatrim', 'met2', 'Meta Trim BHB 2 Bottle', 2, 0, 0, '2026-01-13', '2026-01-29', 157.99, null, 19.99, '2 Bottles ($138 + $19.99 shipping)'],

            // Jan 30+ - 2 Bottles (new pricing)
            ['metatrim', 'met2v2', 'Meta Trim BHB 2 Bottle', 2, 0, 0, '2026-01-30', null, 177.99, null, 19.99, '2 Bottles ($158 + $19.99 shipping)'],

            // 3 Bottles (always same)
            ['metatrim', 'met3', 'Meta Trim BHB 3 Bottles', 3, 0, 0, null, null, 177.00, null, 0, '3 Bottles (Free shipping)'],

            // 4 Bottles (Jan 30+)
            ['metatrim', 'met4', 'Meta Trim BHB 4 Bottles', 4, 0, 0, '2026-01-30', null, 256.00, null, 0, '4 Bottles (Free shipping)'],

            // 6 Bottles (before Jan 30)
            ['metatrim', 'met6', 'Meta Trim BHB 6 Bottles', 6, 0, 0, null, '2026-01-29', 234.00, null, 0, '6 Bottles (Free shipping)'],

            // 7 Bottles (Jan 30+)
            ['metatrim', 'met7', 'Meta Trim BHB 7 Bottles', 7, 0, 0, '2026-01-30', null, 294.00, null, 0, '7 Bottles (6+1 Free)'],

            // ========== METATRIM - Subscribe & Save (Jan 30+) ==========
            // 2 Bottles: Initial $142 + $19.99 ship = $161.99, Recurring $110 + ship every 2 months after 60 days
            ['metatrim', 'met2sub', 'Meta Trim BHB 2 Bottle (Subscribe)', 2, 0, 1, '2026-01-30', null, 161.99, 110.00, 19.99, 'Subscribe: Initial $161.99, then $110+ship/2mo'],
            // 4 Bottles: Initial $232, Recurring $196 every 4 months after 120 days
            ['metatrim', 'met4sub', 'Meta Trim BHB 4 Bottles (Subscribe)', 4, 0, 1, '2026-01-30', null, 232.00, 196.00, 0, 'Subscribe: Initial $232, then $196/4mo'],
            // 7 Bottles: Initial $264, Recurring $238 every 6 months after 210 days
            ['metatrim', 'met7sub', 'Meta Trim BHB 7 Bottles (Subscribe)', 7, 0, 1, '2026-01-30', null, 264.00, 238.00, 0, 'Subscribe: Initial $264, then $238/6mo'],

            // ========== METATRIM - Backend Upsells (Always same) ==========
            ['metatrim', 'met1u', 'Meta Trim BHB 1 Bottle (Upgrade)', 1, 1, 0, null, null, 39.00, null, 0, 'Upsell: 1 Bottle'],
            ['metatrim', 'met3u', 'Meta Trim BHB 3 Bottles (Upgrade)', 3, 1, 0, null, null, 99.00, null, 0, 'Upsell: 3 Bottles'],

            // ========== PROSTAPRIME - Frontend Offers ==========
            // Before Jan 11, 2026 - 1 Bottle
            ['prostaprime', 'pro1', 'Prosta Prime Support 1 Bottle', 1, 0, 0, null, '2026-01-10', 88.99, null, 19.99, '1 Bottle ($69 + $19.99 shipping)'],

            // Jan 13-29, 2026 - 2 Bottles
            ['prostaprime', 'pro2', 'Prosta Prime Support 2 Bottles', 2, 0, 0, '2026-01-13', '2026-01-29', 157.99, null, 19.99, '2 Bottles ($138 + $19.99 shipping)'],

            // Jan 30+ - 2 Bottles (new pricing)
            ['prostaprime', 'pro2v2', 'Prosta Prime Support 2 Bottles', 2, 0, 0, '2026-01-30', null, 177.99, null, 19.99, '2 Bottles ($158 + $19.99 shipping)'],

            // 3 Bottles (always same)
            ['prostaprime', 'pro3', 'Prosta Prime Support 3 Bottles', 3, 0, 0, null, null, 177.00, null, 0, '3 Bottles (Free shipping)'],

            // 4 Bottles (Jan 30+)
            ['prostaprime', 'pro4', 'Prosta Prime Support 4 Bottles', 4, 0, 0, '2026-01-30', null, 256.00, null, 0, '4 Bottles (Free shipping)'],

            // 6 Bottles (before Jan 30)
            ['prostaprime', 'pro6', 'Prosta Prime Support 6 Bottles', 6, 0, 0, null, '2026-01-29', 234.00, null, 0, '6 Bottles (Free shipping)'],

            // 7 Bottles (Jan 30+)
            ['prostaprime', 'pro7', 'Prosta Prime Support 7 Bottles', 7, 0, 0, '2026-01-30', null, 294.00, null, 0, '7 Bottles (6+1 Free)'],

            // ========== PROSTAPRIME - Subscribe & Save (Jan 30+) ==========
            // 2 Bottles: Initial $142 + $19.99 ship = $161.99, Recurring $110 + ship every 2 months after 60 days
            ['prostaprime', 'pro2sub', 'Prosta Prime Support 2 Bottles (Subscribe)', 2, 0, 1, '2026-01-30', null, 161.99, 110.00, 19.99, 'Subscribe: Initial $161.99, then $110+ship/2mo'],
            // 4 Bottles: Initial $232, Recurring $196 every 4 months after 120 days
            ['prostaprime', 'pro4sub', 'Prosta Prime Support 4 Bottles (Subscribe)', 4, 0, 1, '2026-01-30', null, 232.00, 196.00, 0, 'Subscribe: Initial $232, then $196/4mo'],
            // 7 Bottles: Initial $264, Recurring $238 every 6 months after 210 days
            ['prostaprime', 'pro7sub', 'Prosta Prime Support 7 Bottles (Subscribe)', 7, 0, 1, '2026-01-30', null, 264.00, 238.00, 0, 'Subscribe: Initial $264, then $238/6mo'],

            // ========== PROSTAPRIME - Backend Upsells (Always same) ==========
            ['prostaprime', 'pro1u', 'Prosta Prime Support 1 Bottle (Upgrade)', 1, 1, 0, null, null, 39.00, null, 0, 'Upsell: 1 Bottle'],
            ['prostaprime', 'pro3u', 'Prosta Prime Support 3 Bottles (Upgrade)', 3, 1, 0, null, null, 99.00, null, 0, 'Upsell: 3 Bottles'],
        ];

        $insertSql = "INSERT INTO product_pricing
            (product_type, sku_pattern, product_name, bottle_count, is_upsell, is_subscription, date_from, date_to, base_price, recurring_price, shipping, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($insertSql);

        foreach ($pricingData as $data) {
            $stmt->execute($data);
        }

        output("<span class='success'>✓ Seeded " . count($pricingData) . " pricing records</span>", $isCli);
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ Migration completed successfully!</strong></span>", $isCli);

    if (!$isCli) {
        echo "<p><a href='admin.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;'>Go to Admin Panel</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
