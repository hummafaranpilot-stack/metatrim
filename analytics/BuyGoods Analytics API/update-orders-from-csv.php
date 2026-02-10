<?php
/**
 * Update Existing Orders from CSV
 * This script updates orders that already exist in the database with missing fields from CSV
 * (commission, SKU, product_id, etc.)
 */

require_once 'config.php';
require_once 'database.php';

set_time_limit(300);

$db = Database::getInstance();
$conn = $db->getConnection();

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Update Orders from CSV</h1>";
echo "<pre>";

// Process all CSV files in the data folder
$dataDir = __DIR__ . '/data';
$csvFiles = glob($dataDir . '/*.csv');

if (empty($csvFiles)) {
    echo "No CSV files found in data folder.\n";
    exit;
}

$totalUpdated = 0;
$totalSkipped = 0;

foreach ($csvFiles as $csvFile) {
    echo "\n========================================\n";
    echo "Processing: " . basename($csvFile) . "\n";
    echo "========================================\n";

    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        echo "Error: Could not open file\n";
        continue;
    }

    // Get header row
    $headers = fgetcsv($handle);
    if (!$headers) {
        echo "Error: Could not read headers\n";
        fclose($handle);
        continue;
    }

    // Clean headers
    $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
    $headers = array_map('trim', $headers);

    // Find column indices
    $colIndex = [];
    foreach ($headers as $i => $h) {
        $colIndex[$h] = $i;
    }

    // Check required columns exist
    if (!isset($colIndex['Order ID'])) {
        echo "Error: 'Order ID' column not found\n";
        fclose($handle);
        continue;
    }

    $updated = 0;
    $skipped = 0;

    while (($row = fgetcsv($handle)) !== false) {
        if (empty(array_filter($row))) continue;

        $orderId = trim($row[$colIndex['Order ID']] ?? '');
        if (empty($orderId)) {
            $skipped++;
            continue;
        }

        // Get values from CSV
        $commission = 0;
        if (isset($colIndex['Affiliate Commission Amount'])) {
            $commission = floatval(preg_replace('/[^0-9.]/', '', $row[$colIndex['Affiliate Commission Amount']] ?? '0'));
        }

        $sku = '';
        if (isset($colIndex['SKU'])) {
            $sku = trim($row[$colIndex['SKU']] ?? '');
        }

        $productId = '';
        if (isset($colIndex['Product Codenames'])) {
            $productId = trim($row[$colIndex['Product Codenames']] ?? '');
        }

        $taxes = 0;
        if (isset($colIndex['Taxes'])) {
            $taxes = floatval(preg_replace('/[^0-9.]/', '', $row[$colIndex['Taxes']] ?? '0'));
        }

        // Update the order
        $sql = "UPDATE orders SET
            commission = :commission,
            sku_pattern = :sku_pattern
            WHERE order_id = :order_id";

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':commission' => $commission,
            ':sku_pattern' => $sku ?: null,
            ':order_id' => $orderId
        ]);

        if ($stmt->rowCount() > 0) {
            echo "[$orderId] UPDATED - Commission: \$$commission, SKU: $sku\n";
            $updated++;
        } else {
            echo "[$orderId] NOT FOUND in database\n";
            $skipped++;
        }
    }

    fclose($handle);

    echo "\nFile complete: $updated updated, $skipped skipped\n";
    $totalUpdated += $updated;
    $totalSkipped += $skipped;
}

echo "\n========================================\n";
echo "ALL DONE!\n";
echo "Total Updated: $totalUpdated\n";
echo "Total Skipped: $totalSkipped\n";
echo "========================================\n";
echo "</pre>";
echo "<p><a href='index.html'>← Back to Dashboard</a></p>";
?>
