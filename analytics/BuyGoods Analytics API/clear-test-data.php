<?php
/**
 * Clear Test Data
 * Deletes test orders from the database
 * DELETE THIS FILE AFTER USE!
 */

require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Delete test orders (common test data patterns)
$deleted = 0;

// Delete by specific order IDs
$testOrderIds = ['111', 'TEST', 'test'];
foreach ($testOrderIds as $id) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->execute([$id]);
    $deleted += $stmt->rowCount();
}

// Delete orders with test product names
$stmt = $conn->prepare("DELETE FROM orders WHERE product_name LIKE '%TestProd%' OR product_name LIKE '%test%'");
$stmt->execute();
$deleted += $stmt->rowCount();

// Delete orders with test customer names
$stmt = $conn->prepare("DELETE FROM orders WHERE customer_name = 'John Doe' AND customer_email LIKE '%test%'");
$stmt->execute();
$deleted += $stmt->rowCount();

// Also clear related tables
$conn->exec("DELETE FROM webhook_logs WHERE event_type = 'test'");
$conn->exec("DELETE FROM refunds WHERE order_id IN ('111', 'TEST')");
$conn->exec("DELETE FROM chargebacks WHERE order_id IN ('111', 'TEST')");

echo "<!DOCTYPE html><html><head><title>Test Data Cleared</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:500px;margin:50px auto;padding:20px;text-align:center;}";
echo ".success{background:#d1fae5;color:#059669;padding:20px;border-radius:10px;margin:20px 0;}";
echo "a{display:inline-block;padding:12px 24px;background:#4f46e5;color:white;text-decoration:none;border-radius:8px;margin-top:20px;}</style></head><body>";
echo "<h1>Test Data Cleared</h1>";
echo "<div class='success'><strong>$deleted</strong> test record(s) deleted</div>";
echo "<p style='color:#dc2626;font-weight:bold;'>Now delete this file (clear-test-data.php) for security!</p>";
echo "<a href='index.html'>Go to Dashboard</a>";
echo "</body></html>";
