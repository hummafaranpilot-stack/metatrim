<?php
/**
 * Update Order Financials
 *
 * This script updates all orders with calculated financial fields:
 * - base_price (from product_pricing table)
 * - taxes (collected - base_price)
 * - processing_fee (10% of collected)
 * - allowance_hold (10% of collected)
 * - net_amount (collected - taxes - fees - commission)
 * - sku_pattern (normalized SKU)
 *
 * Run this once to populate missing financial data for existing orders.
 */

require_once 'database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Updating Order Financials</h1>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Base price lookup table (product base price + shipping)
    $basePriceMap = [
        'metatrim_1' => 68.99,
        'metatrim_2' => 177.99,    // $158 + $19.99 shipping
        'metatrim_3' => 177.00,
        'metatrim_6' => 234.00,
        'metatrimupsell_1' => 39.00,
        'prostaprime_1' => 68.99,
        'prostaprime_2' => 177.99,
        'prostaprime_3' => 177.00,
        'prostaprime_6' => 234.00,
        'prostaprimeupsell_1' => 39.00,
    ];

    // Get all orders
    $stmt = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($orders) . " orders to process.\n\n";

    $updated = 0;
    $skipped = 0;

    foreach ($orders as $order) {
        $orderId = $order['order_id'];
        $productId = strtolower($order['product_id'] ?? '');
        $collected = floatval($order['product_price'] ?? 0);
        $commission = floatval($order['commission'] ?? 0);
        $productName = $order['product_name'] ?? '';

        // Skip if no product price
        if ($collected <= 0) {
            echo "[$orderId] SKIPPED - No product price\n";
            $skipped++;
            continue;
        }

        // Normalize SKU pattern from product_id
        $skuPattern = null;
        if (preg_match('/(metatrim|prostaprime)(upsell)?_(\d+)/i', $productId, $matches)) {
            $skuPattern = strtolower($matches[1]) . ($matches[2] ? 'upsell' : '') . '_' . $matches[3];
        }

        // Get base price
        $basePrice = $basePriceMap[$skuPattern] ?? null;

        // If no base price from map, try to extract bottle count and estimate
        if (!$basePrice && $productName) {
            // Try to get bottle count from product name
            if (preg_match('/(\d+)\s*bottle/i', $productName, $matches)) {
                $bottles = intval($matches[1]);
                // Check if it's metatrim or prostaprime
                if (stripos($productId, 'metatrim') !== false || stripos($productName, 'meta trim') !== false) {
                    $skuPattern = 'metatrim_' . $bottles;
                    $basePrice = $basePriceMap[$skuPattern] ?? null;
                } elseif (stripos($productId, 'prostaprime') !== false || stripos($productName, 'prostaprime') !== false) {
                    $skuPattern = 'prostaprime_' . $bottles;
                    $basePrice = $basePriceMap[$skuPattern] ?? null;
                }
            }
        }

        // Calculate financial fields
        $taxes = 0;
        if ($basePrice) {
            $taxes = max(0, round($collected - $basePrice, 2));
        }

        $processingFee = round($collected * 0.10, 2);
        $allowanceHold = round($collected * 0.10, 2);
        $netAmount = round($collected - $taxes - $processingFee - $allowanceHold - $commission, 2);

        // Determine if upsell
        $isUpsell = (stripos($productId, 'upsell') !== false || stripos($productName, 'upgrade') !== false) ? 1 : 0;

        // Update the order
        $updateSql = "UPDATE orders SET
            sku_pattern = :sku_pattern,
            base_price = :base_price,
            taxes = :taxes,
            processing_fee = :processing_fee,
            allowance_hold = :allowance_hold,
            net_amount = :net_amount,
            is_upsell = :is_upsell
            WHERE order_id = :order_id";

        $updateStmt = $conn->prepare($updateSql);
        $result = $updateStmt->execute([
            ':sku_pattern' => $skuPattern,
            ':base_price' => $basePrice,
            ':taxes' => $taxes,
            ':processing_fee' => $processingFee,
            ':allowance_hold' => $allowanceHold,
            ':net_amount' => $netAmount,
            ':is_upsell' => $isUpsell,
            ':order_id' => $orderId
        ]);

        if ($result) {
            echo "[$orderId] UPDATED\n";
            echo "  Product: $productId | $productName\n";
            echo "  SKU Pattern: $skuPattern\n";
            echo "  Collected: \$$collected\n";
            echo "  Base Price: \$" . ($basePrice ?? 'N/A') . "\n";
            echo "  Taxes: \$$taxes\n";
            echo "  Processing Fee: \$$processingFee\n";
            echo "  Allowance Hold: \$$allowanceHold\n";
            echo "  Commission (CPA): \$$commission\n";
            echo "  Net Amount: \$$netAmount\n";
            echo "  Is Upsell: " . ($isUpsell ? 'Yes' : 'No') . "\n";
            echo "\n";
            $updated++;
        } else {
            echo "[$orderId] FAILED to update\n";
            $skipped++;
        }
    }

    echo "\n========================================\n";
    echo "COMPLETE!\n";
    echo "Updated: $updated orders\n";
    echo "Skipped: $skipped orders\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
