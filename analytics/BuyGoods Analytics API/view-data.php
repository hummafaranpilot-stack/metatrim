<?php
/**
 * View Raw Webhook Data
 * Shows exactly what BuyGoods sends in each webhook
 */

require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get webhook logs with raw payload
$stmt = $conn->query("SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 20");
$logs = $stmt->fetchAll();

// Get orders with raw data
$stmt2 = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
$orders = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BuyGoods Raw Data Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #1e293b; }
        h2 { color: #2563eb; margin-top: 30px; }
        .card { background: white; padding: 20px; border-radius: 10px; margin: 15px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .event-type { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .new_order { background: #d1fae5; color: #059669; }
        .refund { background: #fef3c7; color: #d97706; }
        .chargeback { background: #fee2e2; color: #dc2626; }
        .recurring_charge { background: #e0e7ff; color: #4f46e5; }
        .cancellation { background: #f3f4f6; color: #6b7280; }
        .fulfillment { background: #dbeafe; color: #2563eb; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
        .timestamp { color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; }
        .back-link { display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin-bottom: 20px; }
        .back-link:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <a href="index.html" class="back-link">← Back to Dashboard</a>

    <h1>BuyGoods Raw Data Viewer</h1>
    <p>This page shows the exact data BuyGoods sends via webhooks.</p>

    <h2>Webhook Logs (Raw Payloads)</h2>
    <?php if (empty($logs)): ?>
        <div class="card">No webhook logs yet.</div>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
            <div class="card">
                <span class="event-type <?= htmlspecialchars($log['event_type']) ?>"><?= htmlspecialchars($log['event_type']) ?></span>
                <span class="timestamp"><?= $log['created_at'] ?></span>
                <pre><?= json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Orders Data (What We Captured)</h2>
    <?php if (empty($orders)): ?>
        <div class="card">No orders yet.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="card">
                <h3>Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                <table>
                    <tr><th>Field</th><th>Value</th></tr>
                    <tr><td>Order ID</td><td><?= htmlspecialchars($order['order_id'] ?? '-') ?></td></tr>
                    <tr><td>Transaction ID</td><td><?= htmlspecialchars($order['transaction_id'] ?? '-') ?></td></tr>
                    <tr><td>Product ID</td><td><?= htmlspecialchars($order['product_id'] ?? '-') ?></td></tr>
                    <tr><td>Product Name</td><td><?= htmlspecialchars($order['product_name'] ?? '-') ?></td></tr>
                    <tr><td>Product Price</td><td>$<?= number_format($order['product_price'] ?? 0, 2) ?></td></tr>
                    <tr><td>Quantity</td><td><?= htmlspecialchars($order['quantity'] ?? '-') ?></td></tr>
                    <tr><td>Customer Name</td><td><?= htmlspecialchars($order['customer_name'] ?? '-') ?></td></tr>
                    <tr><td>Customer Email</td><td><?= htmlspecialchars($order['customer_email'] ?? '-') ?></td></tr>
                    <tr><td>Customer Phone</td><td><?= htmlspecialchars($order['customer_phone'] ?? '-') ?></td></tr>
                    <tr><td>Customer Country</td><td><?= htmlspecialchars($order['customer_country'] ?? '-') ?></td></tr>
                    <tr><td>Customer State</td><td><?= htmlspecialchars($order['customer_state'] ?? '-') ?></td></tr>
                    <tr><td>Customer City</td><td><?= htmlspecialchars($order['customer_city'] ?? '-') ?></td></tr>
                    <tr><td>Customer Address</td><td><?= htmlspecialchars($order['customer_address'] ?? '-') ?></td></tr>
                    <tr><td>Customer ZIP</td><td><?= htmlspecialchars($order['customer_zip'] ?? '-') ?></td></tr>
                    <tr><td>Affiliate ID</td><td><?= htmlspecialchars($order['affiliate_id'] ?? '-') ?></td></tr>
                    <tr><td>Affiliate Name</td><td><?= htmlspecialchars($order['affiliate_name'] ?? '-') ?></td></tr>
                    <tr><td>Commission</td><td>$<?= number_format($order['commission'] ?? 0, 2) ?></td></tr>
                    <tr><td>Payment Method</td><td><?= htmlspecialchars($order['payment_method'] ?? '-') ?></td></tr>
                    <tr><td>Status</td><td><?= htmlspecialchars($order['status'] ?? '-') ?></td></tr>
                    <tr><td>IP Address</td><td><?= htmlspecialchars($order['ip_address'] ?? '-') ?></td></tr>
                    <tr><td>Date</td><td><?= htmlspecialchars($order['created_at'] ?? '-') ?></td></tr>
                </table>
                <h4>Raw Data from BuyGoods:</h4>
                <pre><?= json_encode(json_decode($order['raw_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Available Fields from BuyGoods</h2>
    <div class="card">
        <p>Based on BuyGoods documentation, they typically send:</p>
        <table>
            <tr><th>Field</th><th>Description</th></tr>
            <tr><td>orderId / transactionId</td><td>Unique order identifier</td></tr>
            <tr><td>productId</td><td>Product ID in BuyGoods</td></tr>
            <tr><td>productName / productTitle</td><td>Name of the product</td></tr>
            <tr><td>productPrice / amount</td><td>Price of the product</td></tr>
            <tr><td>quantity</td><td>Number of items ordered</td></tr>
            <tr><td>email / customerEmail</td><td>Customer's email address</td></tr>
            <tr><td>customerName / firstName + lastName</td><td>Customer's full name</td></tr>
            <tr><td>phone / customerPhone</td><td>Customer's phone number</td></tr>
            <tr><td>country</td><td>Customer's country</td></tr>
            <tr><td>state</td><td>Customer's state/region</td></tr>
            <tr><td>city</td><td>Customer's city</td></tr>
            <tr><td>address</td><td>Customer's street address</td></tr>
            <tr><td>zip / postalCode</td><td>Customer's postal code</td></tr>
            <tr><td>affiliateId / affId</td><td>Affiliate's ID</td></tr>
            <tr><td>affiliateName</td><td>Affiliate's name</td></tr>
            <tr><td>commission</td><td>Affiliate commission amount</td></tr>
            <tr><td>paymentMethod</td><td>Payment method used</td></tr>
            <tr><td>currency</td><td>Currency code (USD, etc.)</td></tr>
            <tr><td>customerIp</td><td>Customer's IP address</td></tr>
        </table>
        <p style="margin-top:15px; color:#64748b;"><strong>Note:</strong> The exact fields depend on your BuyGoods account settings and whether "Add Affiliate Stats" is enabled.</p>
    </div>
</body>
</html>
