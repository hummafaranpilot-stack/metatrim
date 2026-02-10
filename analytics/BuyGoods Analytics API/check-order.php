<?php
/**
 * Check order data in database
 * Usage: check-order.php?id=9Q8Z2UQH
 * DELETE THIS FILE AFTER USE
 */

require_once 'config.php';
require_once 'database.php';

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die('Usage: check-order.php?id=ORDER_ID');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found: $orderId");
}

echo "<h2>Order: $orderId</h2>";
echo "<table border='1' cellpadding='8'>";
foreach ($order as $key => $value) {
    if ($key === 'raw_data') {
        $value = substr($value, 0, 100) . '...';
    }
    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
}
echo "</table>";

echo "<br><strong>product_price type:</strong> " . gettype($order['product_price']);
echo "<br><strong>product_price value:</strong> " . var_export($order['product_price'], true);
echo "<br><strong>quantity value:</strong> " . var_export($order['quantity'], true);
echo "<br><strong>payment_method:</strong> " . var_export($order['payment_method'], true);
echo "<br><strong>currency:</strong> " . var_export($order['currency'], true);

echo "<br><br><em>DELETE THIS FILE after checking!</em>";
