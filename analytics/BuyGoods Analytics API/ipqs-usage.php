<?php
/**
 * IPQualityScore Usage API
 * Returns current API usage stats for both accounts
 */

require_once 'config.php';

header('Content-Type: application/json');

// Function to fetch IPQS usage for a single key
function fetchIPQSUsage($apiKey) {
    if (!$apiKey || $apiKey === 'YOUR_API_KEY_HERE') {
        return null;
    }

    $url = "https://ipqualityscore.com/api/json/account/" . $apiKey;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || (isset($data['success']) && $data['success'] === false)) {
        return null;
    }

    return [
        'requests_today' => $data['requests'] ?? $data['usage'] ?? 0,
        'daily_limit' => 35,
        'monthly_used' => $data['credits_used'] ?? $data['monthly_usage'] ?? $data['usage'] ?? 0,
        'monthly_limit' => 1000
    ];
}

// Fetch usage for both accounts
$account1 = null;
$account2 = null;

if (defined('IPQS_API_KEY')) {
    $account1 = fetchIPQSUsage(IPQS_API_KEY);
}

if (defined('IPQS_API_KEY_2')) {
    $account2 = fetchIPQSUsage(IPQS_API_KEY_2);
}

// If both API calls failed, fallback to database
if (!$account1 && !$account2) {
    require_once 'database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE ip_analyzed = 1 AND DATE(updated_at) = CURDATE()");
    $todayCount = $stmt->fetch()['cnt'] ?? 0;

    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE ip_analyzed = 1");
    $totalCount = $stmt->fetch()['cnt'] ?? 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'account1' => [
                'requests_today' => (int)$todayCount,
                'daily_limit' => 35,
                'monthly_used' => (int)$totalCount,
                'monthly_limit' => 1000
            ],
            'account2' => null,
            'source' => 'database'
        ]
    ]);
    exit;
}

// Return usage data for both accounts
echo json_encode([
    'success' => true,
    'data' => [
        'account1' => $account1 ?: ['requests_today' => 0, 'daily_limit' => 35, 'monthly_used' => 0, 'monthly_limit' => 1000],
        'account2' => $account2,
        'source' => 'api'
    ]
]);
