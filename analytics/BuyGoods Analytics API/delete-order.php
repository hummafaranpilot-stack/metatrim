<?php
/**
 * Delete a specific order by ID
 * Usage: delete-order.php?id=111
 * DELETE THIS FILE AFTER USE
 */

require_once 'config.php';
require_once 'database.php';

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die('Usage: delete-order.php?id=ORDER_ID');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Delete the order
$stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$deletedOrders = $stmt->rowCount();

// Also clean up webhook logs for this order
$stmt2 = $conn->prepare("DELETE FROM webhook_logs WHERE payload LIKE ?");
$stmt2->execute(['%"orderId":"' . $orderId . '"%']);
$deletedLogs = $stmt2->rowCount();

echo "Deleted $deletedOrders order(s) with ID: $orderId\n";
echo "Deleted $deletedLogs webhook log(s)\n";
echo "\n⚠️ DELETE THIS FILE (delete-order.php) after use for security!";
