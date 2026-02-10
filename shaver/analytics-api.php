<?php
/**
 * BuyGoods Analytics - API Endpoints
 * Provides data for the dashboard
 *
 * URL Format: api.php?action=stats
 * Actions: stats, orders, refunds, chargebacks, recurring, activity, products, logs, health
 */

require_once 'analytics-database.php';

header('Content-Type: application/json');

// Get action from URL
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    switch ($action) {
        case 'stats':
            handleStats($db);
            break;

        case 'orders':
            handleOrders($db);
            break;

        case 'refunds':
            handleRefunds($db);
            break;

        case 'chargebacks':
            handleChargebacks($db);
            break;

        case 'recurring':
            handleRecurring($db);
            break;

        case 'activity':
            handleActivity($db);
            break;

        case 'products':
            handleProducts($db);
            break;

        case 'tracked_products':
            handleTrackedProducts($db);
            break;

        case 'logs':
            handleLogs($db);
            break;

        case 'health':
            handleHealth($db);
            break;

        case 'withdrawals':
            handleWithdrawals($db);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ==================== API HANDLERS ====================

function handleStats($db) {
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;
    $trackedProductId = $_GET['tracked_product_id'] ?? null;

    $stats = $db->getDashboardStats($startDate, $endDate, $trackedProductId);

    echo json_encode(['success' => true, 'data' => $stats]);
}

function handleOrders($db) {
    $filters = [
        'status' => $_GET['status'] ?? null,
        'startDate' => $_GET['startDate'] ?? null,
        'endDate' => $_GET['endDate'] ?? null,
        'tracked_product_id' => $_GET['tracked_product_id'] ?? null,
        'limit' => $_GET['limit'] ?? 100
    ];

    $orders = $db->getOrders($filters);

    echo json_encode(['success' => true, 'data' => $orders]);
}

function handleRefunds($db) {
    $filters = [
        'startDate' => $_GET['startDate'] ?? null,
        'endDate' => $_GET['endDate'] ?? null,
        'limit' => $_GET['limit'] ?? 100
    ];

    $refunds = $db->getRefunds($filters);

    echo json_encode(['success' => true, 'data' => $refunds]);
}

function handleChargebacks($db) {
    $filters = [
        'startDate' => $_GET['startDate'] ?? null,
        'endDate' => $_GET['endDate'] ?? null,
        'limit' => $_GET['limit'] ?? 100
    ];

    $chargebacks = $db->getChargebacks($filters);

    echo json_encode(['success' => true, 'data' => $chargebacks]);
}

function handleRecurring($db) {
    $filters = [
        'startDate' => $_GET['startDate'] ?? null,
        'endDate' => $_GET['endDate'] ?? null,
        'limit' => $_GET['limit'] ?? 100
    ];

    $recurring = $db->getRecurringCharges($filters);

    echo json_encode(['success' => true, 'data' => $recurring]);
}

function handleActivity($db) {
    $limit = $_GET['limit'] ?? 20;

    $activity = $db->getRecentActivity($limit);

    echo json_encode(['success' => true, 'data' => $activity]);
}

function handleProducts($db) {
    $limit = $_GET['limit'] ?? 10;

    $products = $db->getTopProducts($limit);

    echo json_encode(['success' => true, 'data' => $products]);
}

function handleLogs($db) {
    $filters = [
        'eventType' => $_GET['eventType'] ?? null,
        'limit' => $_GET['limit'] ?? 50
    ];

    $logs = $db->getWebhookLogs($filters);

    echo json_encode(['success' => true, 'data' => $logs]);
}

function handleHealth($db) {
    // If we got here, database connection is working
    echo json_encode([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => date('c')
    ]);
}

function handleTrackedProducts($db) {
    $products = $db->getProducts();
    echo json_encode(['success' => true, 'data' => $products]);
}

function handleWithdrawals($db) {
    $conn = $db->getConnection();

    $sql = "SELECT w.*, p.name as product_name
            FROM withdrawals w
            LEFT JOIN products p ON w.product_id = p.id
            ORDER BY w.withdrawal_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $withdrawals = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $withdrawals]);
}
