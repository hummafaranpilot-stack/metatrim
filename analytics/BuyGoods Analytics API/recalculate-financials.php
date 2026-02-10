<?php
/**
 * BuyGoods Analytics - Recalculate Financials for Existing Orders
 *
 * This script recalculates base_price, taxes, processing_fee, allowance_hold, and net_amount
 * for all existing orders that have a valid SKU pattern.
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
    echo "<!DOCTYPE html><html><head><title>Recalculate Financials</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:50px auto;padding:20px;background:#f5f5f5;}";
    echo ".success{color:#10b981;}.error{color:#ef4444;}.info{color:#2563eb;}.warning{color:#f59e0b;}";
    echo "h1{color:#1e293b;}table{width:100%;border-collapse:collapse;margin:20px 0;}";
    echo "th,td{padding:8px 12px;border:1px solid #e2e8f0;text-align:left;font-size:13px;}";
    echo "th{background:#f1f5f9;}tr:hover{background:#f8fafc;}</style></head><body>";
    echo "<h1>Recalculate Order Financials</h1>";
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    output("<span class='success'>✓ Connected to database</span>", $isCli);

    // First, ensure the financial columns exist
    $columns = ['base_price', 'taxes', 'processing_fee', 'allowance_hold', 'net_amount', 'sku_pattern', 'is_upsell'];
    foreach ($columns as $col) {
        try {
            $pdo->query("SELECT $col FROM orders LIMIT 1");
        } catch (PDOException $e) {
            output("<span class='error'>✗ Column '$col' missing. Run migrate-orders-v2.php first.</span>", $isCli);
            exit;
        }
    }
    output("<span class='success'>✓ All required columns exist</span>", $isCli);

    // Get all orders that need recalculation
    $stmt = $pdo->query("
        SELECT o.id, o.order_id, o.product_name, o.product_price, o.commission, o.created_at,
               o.base_price as current_base_price, o.net_amount as current_net_amount,
               o.sku_pattern
        FROM orders o
        WHERE o.status = 'completed'
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    output("<span class='info'>ℹ Found " . count($orders) . " completed orders to process</span>", $isCli);

    // Normalize SKU pattern function - handles multiple formats
    function normalizeSkuPattern($input) {
        if (empty($input)) return null;

        $input = strtolower(trim($input));

        // Check if already normalized (met2, pro3u, etc.)
        if (preg_match('/^(met|pro)\d+u?$/', $input)) {
            return $input;
        }

        // Detect product type
        $productType = null;
        if (strpos($input, 'meta') !== false || strpos($input, 'trim') !== false || strpos($input, 'bhb') !== false) {
            $productType = 'met';
        } elseif (strpos($input, 'prosta') !== false || strpos($input, 'prime') !== false) {
            $productType = 'pro';
        }

        if (!$productType) return null;

        // Detect upsell
        $isUpsell = (strpos($input, 'upsell') !== false || strpos($input, 'upgrade') !== false);

        // Extract bottle count - look for patterns like "2 bottle", "3 bottles", "_2", "x2"
        $bottleCount = null;

        // Pattern: "N bottle(s)" or "N Bottle(s)"
        if (preg_match('/(\d+)\s*bottle/i', $input, $matches)) {
            $bottleCount = $matches[1];
        }
        // Pattern: "_N" (like metatrim_2)
        elseif (preg_match('/_(\d+)/', $input, $matches)) {
            $bottleCount = $matches[1];
        }
        // Pattern: just a number at the end
        elseif (preg_match('/(\d+)\s*$/', $input, $matches)) {
            $bottleCount = $matches[1];
        }

        if (!$bottleCount) return null;

        return $productType . $bottleCount . ($isUpsell ? 'u' : '');
    }

    // Get pricing function
    function getBasePrice($pdo, $skuPattern, $orderDate) {
        if (empty($skuPattern)) return null;

        $sql = "SELECT base_price, shipping, bottle_count, is_upsell, is_subscription
                FROM product_pricing
                WHERE sku_pattern = :sku_pattern
                AND is_active = 1
                AND (date_from IS NULL OR date_from <= :order_date)
                AND (date_to IS NULL OR date_to >= :order_date)
                ORDER BY
                    CASE WHEN date_from IS NOT NULL AND date_to IS NOT NULL THEN 0
                         WHEN date_from IS NOT NULL THEN 1
                         WHEN date_to IS NOT NULL THEN 2
                         ELSE 3 END,
                    date_from DESC
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sku_pattern' => $skuPattern,
            ':order_date' => $orderDate
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return [
                'base_price' => floatval($result['base_price']),
                'shipping' => floatval($result['shipping']),
                'total' => floatval($result['base_price']) + floatval($result['shipping']),
                'is_upsell' => (bool)$result['is_upsell']
            ];
        }

        return null;
    }

    // Prepare update statement
    $updateStmt = $pdo->prepare("
        UPDATE orders SET
            sku_pattern = :sku_pattern,
            base_price = :base_price,
            taxes = :taxes,
            processing_fee = :processing_fee,
            allowance_hold = :allowance_hold,
            net_amount = :net_amount,
            is_upsell = :is_upsell
        WHERE id = :id
    ");

    $updated = 0;
    $skipped = 0;
    $noMatch = 0;
    $results = [];
    $skippedNames = []; // Track skipped product names for debugging

    foreach ($orders as $order) {
        $productName = $order['product_name'] ?? '';
        $totalCollected = floatval($order['product_price']);
        $commission = floatval($order['commission'] ?? 0);
        $orderDate = date('Y-m-d', strtotime($order['created_at']));

        // Try to get SKU pattern from product name
        $skuPattern = $order['sku_pattern'];
        if (empty($skuPattern)) {
            $skuPattern = normalizeSkuPattern($productName);
        }

        if (empty($skuPattern)) {
            $skipped++;
            // Track unique product names that couldn't be matched
            if (!in_array($productName, $skippedNames)) {
                $skippedNames[] = $productName;
            }
            continue;
        }

        // Get pricing for this SKU and date
        $pricing = getBasePrice($pdo, $skuPattern, $orderDate);

        if (!$pricing) {
            $noMatch++;
            $results[] = [
                'order_id' => $order['order_id'],
                'product' => $productName,
                'sku' => $skuPattern,
                'date' => $orderDate,
                'status' => 'No pricing match'
            ];
            continue;
        }

        // Calculate financials
        $basePrice = $pricing['total'];
        $taxes = round($totalCollected - $basePrice, 2);
        $taxes = max(0, $taxes); // Taxes shouldn't be negative
        $processingFee = round($totalCollected * 0.10, 2);
        $allowanceHold = round($totalCollected * 0.10, 2);
        $netAmount = round($totalCollected - $processingFee - $allowanceHold - $commission, 2);

        // Update the order
        $updateStmt->execute([
            ':id' => $order['id'],
            ':sku_pattern' => $skuPattern,
            ':base_price' => $basePrice,
            ':taxes' => $taxes,
            ':processing_fee' => $processingFee,
            ':allowance_hold' => $allowanceHold,
            ':net_amount' => $netAmount,
            ':is_upsell' => $pricing['is_upsell'] ? 1 : 0
        ]);

        $updated++;
        $results[] = [
            'order_id' => $order['order_id'],
            'product' => substr($productName, 0, 30),
            'sku' => $skuPattern,
            'collected' => '$' . number_format($totalCollected, 2),
            'base' => '$' . number_format($basePrice, 2),
            'taxes' => '$' . number_format($taxes, 2),
            'net' => '$' . number_format($netAmount, 2),
            'status' => 'Updated'
        ];
    }

    output("", $isCli);
    output("<span class='success'>✓ Updated: $updated orders</span>", $isCli);
    output("<span class='warning'>⚠ Skipped (no SKU): $skipped orders</span>", $isCli);
    output("<span class='warning'>⚠ No pricing match: $noMatch orders</span>", $isCli);

    // Show skipped product names for debugging
    if (!$isCli && count($skippedNames) > 0) {
        echo "<h3 style='margin-top:20px;'>Unrecognized Product Names</h3>";
        echo "<p style='color:#64748b;font-size:13px;'>These product names couldn't be matched to SKU patterns:</p>";
        echo "<ul style='background:#1e293b;padding:1rem 2rem;border-radius:8px;color:#f87171;font-size:13px;'>";
        foreach ($skippedNames as $name) {
            echo "<li>" . htmlspecialchars($name ?: '(empty)') . "</li>";
        }
        echo "</ul>";
    }

    // Show results table
    if (!$isCli && count($results) > 0) {
        echo "<h3>Processing Results (last 50)</h3>";
        echo "<table><thead><tr><th>Order ID</th><th>Product</th><th>SKU</th><th>Collected</th><th>Base</th><th>Taxes</th><th>Net</th><th>Status</th></tr></thead><tbody>";

        $displayResults = array_slice($results, 0, 50);
        foreach ($displayResults as $r) {
            $statusColor = $r['status'] === 'Updated' ? '#10b981' : '#f59e0b';
            echo "<tr>";
            echo "<td>{$r['order_id']}</td>";
            echo "<td>{$r['product']}</td>";
            echo "<td><code>{$r['sku']}</code></td>";
            echo "<td>" . ($r['collected'] ?? '-') . "</td>";
            echo "<td>" . ($r['base'] ?? '-') . "</td>";
            echo "<td>" . ($r['taxes'] ?? '-') . "</td>";
            echo "<td style='color:#10b981;font-weight:600;'>" . ($r['net'] ?? '-') . "</td>";
            echo "<td style='color:$statusColor;'>{$r['status']}</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

        if (count($results) > 50) {
            echo "<p style='color:#64748b;font-size:13px;'>Showing 50 of " . count($results) . " processed orders</p>";
        }
    }

    output("", $isCli);
    output("<span class='success'><strong>✓ Recalculation completed!</strong></span>", $isCli);

    if (!$isCli) {
        echo "<p style='margin-top:20px;'><a href='admin.html' style='display:inline-block;padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;'>Go to Admin Panel</a></p>";
    }

} catch (PDOException $e) {
    output("<span class='error'>✗ Error: " . $e->getMessage() . "</span>", $isCli);
}

if (!$isCli) {
    echo "</body></html>";
}
