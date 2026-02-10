<?php
/**
 * Analyze existing orders for IP fraud data
 * Run this to analyze orders that haven't been checked yet
 * Supports dual API keys with automatic fallback
 */

require_once 'config.php';
require_once 'database.php';
require_once 'ipqs.php';

// Check API keys
$apiKeys = [];
if (defined('IPQS_API_KEY') && IPQS_API_KEY !== 'YOUR_API_KEY_HERE') {
    $apiKeys[] = ['key' => IPQS_API_KEY, 'name' => 'Account 1'];
}
if (defined('IPQS_API_KEY_2') && IPQS_API_KEY_2 !== 'YOUR_API_KEY_HERE') {
    $apiKeys[] = ['key' => IPQS_API_KEY_2, 'name' => 'Account 2'];
}

if (empty($apiKeys)) {
    die("Error: Please configure at least one IPQS_API_KEY in config.php.<br><a href='https://www.ipqualityscore.com/create-account'>Get your free API key here</a>");
}

$db = Database::getInstance();
$conn = $db->getConnection();
$currentKeyIndex = 0;
$ipqs = new IPQS($apiKeys[$currentKeyIndex]['key']);
$usedKey = $apiKeys[$currentKeyIndex]['name'];

// Get orders that haven't been analyzed
$stmt = $conn->query("SELECT id, order_id, ip_address FROM orders WHERE ip_analyzed = 0 AND ip_address IS NOT NULL AND ip_address != '' LIMIT 70");
$orders = $stmt->fetchAll();

$analyzed = 0;
$errors = 0;
$results = [];
$keyFailCount = 0;

foreach ($orders as $order) {
    $ip = $order['ip_address'];

    // Skip empty IPs
    if (empty($ip)) continue;

    try {
        $analysis = $ipqs->analyzeIP($ip);

        // If analysis failed, try switching to backup key
        if (!$analysis && count($apiKeys) > 1) {
            $keyFailCount++;
            if ($keyFailCount >= 3) {
                // Switch to next key
                $currentKeyIndex = ($currentKeyIndex + 1) % count($apiKeys);
                $ipqs = new IPQS($apiKeys[$currentKeyIndex]['key']);
                $usedKey = $apiKeys[$currentKeyIndex]['name'];
                $keyFailCount = 0;
                // Try again with new key
                $analysis = $ipqs->analyzeIP($ip);
            }
        } else {
            $keyFailCount = 0;
        }

        if ($analysis) {
            $fraudData = $ipqs->extractFraudData($analysis);

            // Update order with fraud data
            $updateStmt = $conn->prepare("UPDATE orders SET
                ip_country = :country,
                ip_city = :city,
                ip_region = :region,
                ip_proxy = :proxy,
                ip_tor = :tor,
                ip_fraud_score = :fraud_score,
                ip_analyzed = 1
                WHERE id = :id");

            $updateStmt->execute([
                ':country' => $fraudData['country'],
                ':city' => $fraudData['city'],
                ':region' => $fraudData['region'],
                ':proxy' => $fraudData['proxy'] ? 1 : 0,
                ':tor' => $fraudData['tor'] ? 1 : 0,
                ':fraud_score' => $fraudData['fraud_score'],
                ':id' => $order['id']
            ]);

            $results[] = [
                'order_id' => $order['order_id'],
                'ip' => $ip,
                'fraud_score' => $fraudData['fraud_score'],
                'country' => $fraudData['country'],
                'proxy' => $fraudData['proxy'],
                'tor' => $fraudData['tor'],
                'key_used' => $usedKey
            ];

            $analyzed++;
        } else {
            $errors++;
        }

        // Rate limit: 1 request per second on free tier
        usleep(1100000); // 1.1 seconds

    } catch (Exception $e) {
        $errors++;
        error_log("IPQS Error for order {$order['order_id']}: " . $e->getMessage());
    }
}

// Get remaining count
$stmt = $conn->query("SELECT COUNT(*) as remaining FROM orders WHERE ip_analyzed = 0 AND ip_address IS NOT NULL AND ip_address != ''");
$remaining = $stmt->fetch()['remaining'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>IP Analysis Results</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { color: #1e3a5f; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat { text-align: center; padding: 20px; background: #f8fafc; border-radius: 10px; }
        .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-label { color: #64748b; font-size: 0.9rem; }
        .stat.green .stat-value { color: #10b981; }
        .stat.yellow .stat-value { color: #f59e0b; }
        .stat.blue .stat-value { color: #3b82f6; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .score { padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .score.low { background: #d1fae5; color: #059669; }
        .score.suspicious { background: #fef3c7; color: #d97706; }
        .score.risky { background: #fed7aa; color: #c2410c; }
        .score.high { background: #fee2e2; color: #dc2626; }
        .flag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; margin-right: 4px; }
        .flag.proxy { background: #fef3c7; color: #92400e; }
        .flag.tor { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; margin-right: 10px; }
        .btn:hover { background: #4338ca; }
        .btn-secondary { background: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>IP Fraud Analysis Results</h1>

        <div class="stats">
            <div class="stat green">
                <div class="stat-value"><?= $analyzed ?></div>
                <div class="stat-label">Orders Analyzed</div>
            </div>
            <div class="stat yellow">
                <div class="stat-value"><?= $remaining ?></div>
                <div class="stat-label">Remaining</div>
            </div>
            <div class="stat blue">
                <div class="stat-value"><?= $errors ?></div>
                <div class="stat-label">Errors</div>
            </div>
        </div>

        <?php if ($remaining > 0): ?>
            <p style="color:#64748b;margin-bottom:20px;">There are still <?= $remaining ?> orders to analyze. Click "Analyze More" to continue (70 at a time, using <?= count($apiKeys) ?> API key<?= count($apiKeys) > 1 ? 's' : '' ?> with fallback).</p>
            <a href="analyze-ips.php" class="btn">Analyze More</a>
        <?php else: ?>
            <p style="color:#10b981;margin-bottom:20px;">All orders have been analyzed!</p>
        <?php endif; ?>
        <a href="index.html" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (!empty($results)): ?>
    <div class="card">
        <h2>Analysis Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>IP Address</th>
                    <th>Country</th>
                    <th>Fraud Score</th>
                    <th>Flags</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r):
                    $riskClass = 'low';
                    if ($r['fraud_score'] >= 90) $riskClass = 'high';
                    elseif ($r['fraud_score'] >= 85) $riskClass = 'risky';
                    elseif ($r['fraud_score'] >= 75) $riskClass = 'suspicious';
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['order_id']) ?></strong></td>
                    <td><code><?= htmlspecialchars($r['ip']) ?></code></td>
                    <td><?= htmlspecialchars($r['country']) ?></td>
                    <td><span class="score <?= $riskClass ?>"><?= $r['fraud_score'] ?></span></td>
                    <td>
                        <?php if ($r['proxy']): ?><span class="flag proxy">PROXY</span><?php endif; ?>
                        <?php if ($r['tor']): ?><span class="flag tor">TOR</span><?php endif; ?>
                        <?php if (!$r['proxy'] && !$r['tor']): ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</body>
</html>
