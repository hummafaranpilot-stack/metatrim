<?php
/**
 * Test Fraud Alert Email
 * Usage: test-fraud-alert.php?order_id=9Q8Z2UPK
 * DELETE THIS FILE AFTER TESTING
 */

require_once 'config.php';
require_once 'database.php';
require_once 'smtp-mailer.php';

header('Content-Type: text/html; charset=UTF-8');

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    die('<h2>Error: Please provide order_id parameter</h2><p>Example: ?order_id=9Q8Z2UPK</p>');
}

// Get order from database
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('<h2>Error: Order not found</h2>');
}

// Prepare order data
$orderData = [
    'order_id' => $order['order_id'],
    'product_name' => $order['product_name'],
    'product_price' => $order['product_price'],
    'customer_name' => $order['customer_name'],
    'customer_email' => $order['customer_email'],
    'customer_phone' => $order['customer_phone'],
    'customer_address' => $order['customer_address'],
    'customer_city' => $order['customer_city'],
    'customer_state' => $order['customer_state'],
    'customer_country' => $order['customer_country'],
    'customer_zip' => $order['customer_zip'],
    'affiliate_id' => $order['affiliate_id'],
    'affiliate_name' => $order['affiliate_name'],
    'commission' => $order['commission'],
    'ip_address' => $order['ip_address']
];

// Prepare fraud data
$fraudData = [
    'fraud_score' => (int)$order['ip_fraud_score'],
    'proxy' => (bool)$order['ip_proxy'],
    'tor' => (bool)$order['ip_tor'],
    'country' => $order['ip_country'],
    'city' => $order['ip_city'],
    'region' => $order['ip_region']
];

$ipqsScore = $fraudData['fraud_score'];

// Calculate custom score
$customScore = 0;
$customFlags = [];

$isAllCaps = function($str) {
    return $str && strlen($str) > 2 && $str === strtoupper($str) && preg_match('/[A-Z]/', $str);
};

$hasMiddleName = function($name) {
    return $name && count(preg_split('/\s+/', trim($name))) >= 3;
};

if ($isAllCaps($orderData['customer_name'] ?? '')) {
    $customFlags[] = 'CAPS Name (+35)';
    $customScore += 35;
}
if ($isAllCaps($orderData['customer_address'] ?? '')) {
    $customFlags[] = 'CAPS Address (+25)';
    $customScore += 25;
}
if ($isAllCaps($orderData['customer_city'] ?? '')) {
    $customFlags[] = 'CAPS City (+15)';
    $customScore += 15;
}
if (!empty($orderData['customer_email'])) {
    $emailLocal = explode('@', $orderData['customer_email'])[0];
    if ($isAllCaps($emailLocal)) {
        $customFlags[] = 'CAPS Email (+20)';
        $customScore += 20;
    }
}
if ($hasMiddleName($orderData['customer_name'] ?? '')) {
    $customFlags[] = 'Middle Name (+10)';
    $customScore += 10;
}

// Check conditions
$shouldSend = false;
$alertReason = '';

if ($ipqsScore >= 50) {
    $shouldSend = true;
    $alertReason = "IPQS High Risk ({$ipqsScore})";
} elseif ($ipqsScore >= 20 && $customScore >= 50) {
    $shouldSend = true;
    $alertReason = "Combined Risk - IPQS: {$ipqsScore}, Our Analysis: {$customScore}";
}

echo "<h2>Fraud Alert Test - Order #{$orderId}</h2>";
echo "<p><strong>IPQS Score:</strong> {$ipqsScore}</p>";
echo "<p><strong>Our Analysis Score:</strong> {$customScore}</p>";
echo "<p><strong>Proxy:</strong> " . ($fraudData['proxy'] ? 'Yes' : 'No') . "</p>";
echo "<p><strong>TOR:</strong> " . ($fraudData['tor'] ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Should Send Email:</strong> " . ($shouldSend ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Reason:</strong> " . ($alertReason ?: 'Does not meet conditions') . "</p>";
echo "<hr>";

if (!$shouldSend) {
    echo "<h3 style='color:orange'>Email conditions NOT met</h3>";
    echo "<p>Conditions: IPQS >= 50 OR (IPQS 20-49 AND Our Analysis >= 50)</p>";
    exit;
}

// Build and send email
$ipqsRisk = $ipqsScore >= 85 ? 'HIGH RISK' : ($ipqsScore >= 75 ? 'SUSPICIOUS' : ($ipqsScore >= 50 ? 'MODERATE' : 'LOW'));

$estTime = date('M j, Y g:i A') . ' EST';
$pktTime = date('g:i A', strtotime('+10 hours')) . ' PKT';

$to = FRAUD_ALERT_EMAIL;
$subject = "⚠️ FRAUD ALERT: Order #{$orderData['order_id']} - IPQS: {$ipqsScore} | OA: {$customScore}";

$fromName = defined('FRAUD_ALERT_FROM_NAME') ? FRAUD_ALERT_FROM_NAME : 'BuyGoods Analytics';
$fromEmail = defined('FRAUD_ALERT_FROM') ? FRAUD_ALERT_FROM : 'noreply@trustednutraproduct.com';

// HTML Email Body - Using tables for email client compatibility
$proxyBadge = $fraudData['proxy'] ? "<span style='background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>YES</span>" : "<span style='background:#d1fae5;color:#059669;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>No</span>";
$torBadge = $fraudData['tor'] ? "<span style='background:#fee2e2;color:#dc2626;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>YES</span>" : "<span style='background:#d1fae5;color:#059669;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>No</span>";
$scoreBadgeColor = $ipqsScore >= 75 ? '#fee2e2;color:#dc2626' : ($ipqsScore >= 50 ? '#fef3c7;color:#d97706' : '#d1fae5;color:#059669');

$flagsHtml = '';
if (empty($customFlags)) {
    $flagsHtml = "<tr><td style='padding:12px;text-align:center;'><span style='background:#d1fae5;color:#059669;padding:6px 14px;border-radius:12px;font-size:12px;font-weight:600;'>✓ No suspicious patterns</span></td></tr>";
} else {
    $flagsHtml = "<tr><td style='padding:12px;'>";
    foreach ($customFlags as $flag) {
        $flagsHtml .= "<span style='display:inline-block;background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:4px;font-size:11px;margin:2px;'>⚠️ {$flag}</span> ";
    }
    $flagsHtml .= "</td></tr>";
}

$body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#f1f5f9;margin:0;padding:20px;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='max-width:600px;margin:0 auto;'>
        <tr>
            <td style='background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;padding:30px 20px;text-align:center;border-radius:12px 12px 0 0;'>
                <div style='font-size:20px;font-weight:600;margin-bottom:8px;'>⚠️ FRAUD ALERT</div>
                <div style='font-size:56px;font-weight:700;line-height:1;'>{$ipqsScore}</div>
                <div style='font-size:14px;opacity:0.9;margin-top:8px;'>IPQS Fraud Score - {$ipqsRisk}</div>
            </td>
        </tr>
        <tr>
            <td style='background:#ffffff;padding:24px;'>
                <!-- Score Boxes -->
                <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
                    <tr>
                        <td width='48%' style='background:#fee2e2;padding:16px;text-align:center;border-radius:8px;'>
                            <div style='font-size:36px;font-weight:700;color:#dc2626;'>{$ipqsScore}</div>
                            <div style='font-size:12px;color:#64748b;margin-top:4px;'>IPQS Score</div>
                        </td>
                        <td width='4%'></td>
                        <td width='48%' style='background:#e0e7ff;padding:16px;text-align:center;border-radius:8px;'>
                            <div style='font-size:36px;font-weight:700;color:#4338ca;'>{$customScore}</div>
                            <div style='font-size:12px;color:#64748b;margin-top:4px;'>Our Analysis</div>
                        </td>
                    </tr>
                </table>

                <!-- Order Information -->
                <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;margin-bottom:16px;'>
                    <tr><td style='background:#f8fafc;padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #e2e8f0;'>📦 Order Information</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Order ID:</span> <strong>{$orderData['order_id']}</strong></td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Product:</span> {$orderData['product_name']}</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Amount:</span> <strong>\${$orderData['product_price']}</strong></td></tr>
                    <tr><td style='padding:12px 16px;'><span style='color:#64748b;'>Date:</span> {$estTime} <span style='color:#94a3b8;'>({$pktTime})</span></td></tr>
                </table>

                <!-- Customer Information -->
                <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;margin-bottom:16px;'>
                    <tr><td style='background:#f8fafc;padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #e2e8f0;'>👤 Customer Information</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Name:</span> {$orderData['customer_name']}</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Email:</span> {$orderData['customer_email']}</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Phone:</span> " . ($orderData['customer_phone'] ?? 'N/A') . "</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Address:</span> " . ($orderData['customer_address'] ?? 'N/A') . "</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>City/State:</span> " . ($orderData['customer_city'] ?? '') . ", " . ($orderData['customer_state'] ?? '') . "</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Country:</span> {$orderData['customer_country']}</td></tr>
                    <tr><td style='padding:12px 16px;'><span style='color:#64748b;'>ZIP:</span> " . ($orderData['customer_zip'] ?? 'N/A') . "</td></tr>
                </table>

                <!-- Affiliate Information -->
                <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;margin-bottom:16px;'>
                    <tr><td style='background:#f8fafc;padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #e2e8f0;'>🤝 Affiliate Information</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Affiliate ID:</span> " . ($orderData['affiliate_id'] ?? 'Direct') . "</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Affiliate Name:</span> " . ($orderData['affiliate_name'] ?? 'N/A') . "</td></tr>
                    <tr><td style='padding:12px 16px;'><span style='color:#64748b;'>Commission:</span> \$" . number_format($orderData['commission'] ?? 0, 2) . "</td></tr>
                </table>

                <!-- IPQS Analysis -->
                <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;margin-bottom:16px;'>
                    <tr><td style='background:#f8fafc;padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #e2e8f0;'>🛡️ IPQualityScore Analysis</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>IP Address:</span> <code style='background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:13px;'>{$orderData['ip_address']}</code></td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>IP Location:</span> " . ($fraudData['city'] ?? 'Unknown') . ", " . ($fraudData['region'] ?? '') . ", " . ($fraudData['country'] ?? 'Unknown') . "</td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Fraud Score:</span> <span style='background:{$scoreBadgeColor};padding:4px 10px;border-radius:12px;font-size:12px;font-weight:600;'>{$ipqsScore}</span></td></tr>
                    <tr><td style='padding:12px 16px;border-bottom:1px solid #f1f5f9;'><span style='color:#64748b;'>Proxy:</span> {$proxyBadge}</td></tr>
                    <tr><td style='padding:12px 16px;'><span style='color:#64748b;'>TOR:</span> {$torBadge}</td></tr>
                </table>

                <!-- Our Analysis -->
                <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e2e8f0;border-radius:8px;'>
                    <tr><td style='background:#f8fafc;padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid #e2e8f0;'>🔍 Our Pattern Analysis (Score: {$customScore})</td></tr>
                    {$flagsHtml}
                </table>
            </td>
        </tr>
        <tr>
            <td style='background:#f8fafc;padding:20px;text-align:center;font-size:12px;color:#64748b;border-radius:0 0 12px 12px;border-top:1px solid #e2e8f0;'>
                <strong>Alert Reason:</strong> {$alertReason}<br><br>
                BuyGoods Analytics Fraud Alert System<br>
                This is an automated alert. Please review carefully.
            </td>
        </tr>
    </table>
</body>
</html>";

echo "<h3>Sending email via SMTP to: {$to}</h3>";
echo "<p>From: {$fromName} &lt;{$fromEmail}&gt;</p>";
echo "<p>Subject: {$subject}</p>";
echo "<p>SMTP: " . SMTP_HOST . ":" . SMTP_PORT . "</p>";

// Send email via SMTP
$sent = sendSMTPEmail($to, $subject, $body);

if ($sent) {
    echo "<h2 style='color:green'>✅ EMAIL SENT SUCCESSFULLY!</h2>";
    echo "<p>Check your inbox at: {$to}</p>";
} else {
    echo "<h2 style='color:red'>❌ EMAIL FAILED TO SEND</h2>";
    echo "<p>Check server mail configuration.</p>";
}

echo "<hr><h3>Email Preview:</h3>";
echo $body;
