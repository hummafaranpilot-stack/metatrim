<?php
/**
 * Check Orders Table Schema
 * Upload this to dashboard-v2/ folder and access via browser
 * URL: https://metatrim.trustednutraproduct.com/shaver/check-schema.php
 */

require_once 'config.php';
require_once 'database.php';

header('Content-Type: text/plain');

try {
    echo "Checking orders table schema...\n\n";

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get column names from orders table
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Orders table columns:\n";
    echo "====================\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }

    echo "\n\nSample order record:\n";
    echo "===================\n";
    $stmt = $conn->query("SELECT * FROM orders LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        foreach ($order as $key => $value) {
            echo "$key: $value\n";
        }
    } else {
        echo "No orders found\n";
    }

} catch (Exception $e) {
    echo "\n\nERROR: " . $e->getMessage() . "\n";
}
?>
