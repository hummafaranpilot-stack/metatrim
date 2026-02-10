<?php
/**
 * BuyGoods Analytics - API Endpoints
 * Provides data for the dashboard
 *
 * URL Format: api.php?action=stats
 * Actions: stats, orders, refunds, chargebacks, recurring, activity, products, logs, health
 */

require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Get action from GET or POST body
$action = $_GET['action'] ?? '';
$postData = [];

// Parse POST JSON body if present
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $postData['action'] ?? '';
}

try {
    $db = Database::getInstance();

    switch ($action) {
        case 'stats':
            handleStats($db, $postData);
            break;

        // Dashboard camelCase aliases
        case 'getDashboardStats':
            handleGetDashboardStats($db, $postData);
            break;

        case 'getRecentOrders':
            handleGetRecentOrders($db, $postData);
            break;

        case 'getRevenueChart':
            handleGetRevenueChart($db, $postData);
            break;

        case 'orders':
            handleOrders($db, $postData);
            break;

        case 'refunds':
            handleRefunds($db, $postData);
            break;

        case 'chargebacks':
            handleChargebacks($db, $postData);
            break;

        case 'recurring':
            handleRecurring($db, $postData);
            break;

        case 'activity':
            handleActivity($db, $postData);
            break;

        case 'products':
            handleProducts($db, $postData);
            break;

        case 'tracked_products':
            handleTrackedProducts($db);
            break;

        case 'logs':
            handleLogs($db, $postData);
            break;

        case 'health':
            handleHealth($db);
            break;

        case 'withdrawals':
            handleWithdrawals($db);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
            exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('BuyGoods API Error [' . $action . ']: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ==================== API HANDLERS ====================

function handleStats($db, $postData = []) {
    $startDate = $postData['startDate'] ?? $_GET['startDate'] ?? null;
    $endDate = $postData['endDate'] ?? $_GET['endDate'] ?? null;
    $trackedProductId = $postData['tracked_product_id'] ?? $_GET['tracked_product_id'] ?? null;

    $stats = $db->getDashboardStats($startDate, $endDate, $trackedProductId);

    echo json_encode(['success' => true, 'data' => $stats]);
}

// Dashboard-specific handler - transforms nested stats to flat format
function handleGetDashboardStats($db, $postData = []) {
    $startDate = $postData['startDate'] ?? $_GET['startDate'] ?? null;
    $endDate = $postData['endDate'] ?? $_GET['endDate'] ?? null;
    $trackedProductId = $postData['tracked_product_id'] ?? $_GET['tracked_product_id'] ?? null;

    // Get raw stats from database
    $rawStats = $db->getDashboardStats($startDate, $endDate, $trackedProductId);

    // Get today's stats
    $todayStats = $db->getDashboardStats(date('Y-m-d'), date('Y-m-d'), $trackedProductId);

    // Get active subscriptions count
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT COUNT(*) as count FROM recurring_charges WHERE status = 'success'");
    $activeSubscriptions = $stmt->fetch()['count'] ?? 0;

    // Get pending refunds count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'");
    $pendingRefunds = $stmt->fetch()['count'] ?? 0;

    // Transform to flat structure that dashboard expects
    $dashboardData = [
        'totalRevenue' => floatval($rawStats['summary']['total_revenue'] ?? 0),
        'totalOrders' => intval($rawStats['orders']['total_orders'] ?? 0),
        'netProfit' => floatval($rawStats['summary']['net_revenue'] ?? 0),
        'todayOrders' => intval($todayStats['orders']['total_orders'] ?? 0),
        'todayRevenue' => floatval($todayStats['summary']['total_revenue'] ?? 0),
        'pendingRefunds' => intval($pendingRefunds),
        'activeSubscriptions' => intval($activeSubscriptions),
        'completedOrders' => intval($rawStats['orders']['completed_orders'] ?? 0),
        'refundedOrders' => intval($rawStats['orders']['refunded_orders'] ?? 0),
        'chargebackOrders' => intval($rawStats['orders']['chargeback_orders'] ?? 0),
        'totalRefunds' => intval($rawStats['refunds']['total_refunds'] ?? 0),
        'refundAmount' => floatval($rawStats['refunds']['refund_amount'] ?? 0),
        'totalChargebacks' => intval($rawStats['chargebacks']['total_chargebacks'] ?? 0),
        'chargebackAmount' => floatval($rawStats['chargebacks']['chargeback_amount'] ?? 0),
        'recurringRevenue' => floatval($rawStats['recurring']['recurring_revenue'] ?? 0)
    ];

    echo json_encode(['success' => true, 'data' => $dashboardData]);
}

// Get recent orders for dashboard
function handleGetRecentOrders($db, $postData = []) {
    $limit = intval($postData['limit'] ?? $_GET['limit'] ?? 5);

    $conn = $db->getConnection();
    $sql = "SELECT o.*, p.name as product_name
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            ORDER BY o.created_at DESC
            LIMIT :limit";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $orders]);
}

// Get revenue chart data (daily revenue for last N days)
function handleGetRevenueChart($db, $postData = []) {
    $days = intval($postData['days'] ?? $_GET['days'] ?? 30);

    $conn = $db->getConnection();
    $sql = "SELECT DATE(created_at) as date,
                   SUM(CASE WHEN status = 'completed' THEN COALESCE(product_price * quantity, 0) ELSE 0 END) as revenue
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $chartData = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $chartData]);
}

function handleOrders($db, $postData = []) {
    $filters = [
        'status' => $postData['status'] ?? $_GET['status'] ?? null,
        'startDate' => $postData['startDate'] ?? $_GET['startDate'] ?? null,
        'endDate' => $postData['endDate'] ?? $_GET['endDate'] ?? null,
        'tracked_product_id' => $postData['tracked_product_id'] ?? $_GET['tracked_product_id'] ?? null,
        'limit' => $postData['limit'] ?? $_GET['limit'] ?? 100
    ];

    $orders = $db->getOrders($filters);

    echo json_encode(['success' => true, 'data' => $orders]);
}

function handleRefunds($db, $postData = []) {
    $filters = [
        'startDate' => $postData['startDate'] ?? $_GET['startDate'] ?? null,
        'endDate' => $postData['endDate'] ?? $_GET['endDate'] ?? null,
        'limit' => $postData['limit'] ?? $_GET['limit'] ?? 100
    ];

    $refunds = $db->getRefunds($filters);

    echo json_encode(['success' => true, 'data' => $refunds]);
}

function handleChargebacks($db, $postData = []) {
    $filters = [
        'startDate' => $postData['startDate'] ?? $_GET['startDate'] ?? null,
        'endDate' => $postData['endDate'] ?? $_GET['endDate'] ?? null,
        'limit' => $postData['limit'] ?? $_GET['limit'] ?? 100
    ];

    $chargebacks = $db->getChargebacks($filters);

    echo json_encode(['success' => true, 'data' => $chargebacks]);
}

function handleRecurring($db, $postData = []) {
    $filters = [
        'startDate' => $postData['startDate'] ?? $_GET['startDate'] ?? null,
        'endDate' => $postData['endDate'] ?? $_GET['endDate'] ?? null,
        'limit' => $postData['limit'] ?? $_GET['limit'] ?? 100
    ];

    $recurring = $db->getRecurringCharges($filters);

    echo json_encode(['success' => true, 'data' => $recurring]);
}

function handleActivity($db, $postData = []) {
    $limit = $postData['limit'] ?? $_GET['limit'] ?? 20;

    $activity = $db->getRecentActivity($limit);

    echo json_encode(['success' => true, 'data' => $activity]);
}

function handleProducts($db, $postData = []) {
    $limit = $postData['limit'] ?? $_GET['limit'] ?? 10;

    $products = $db->getTopProducts($limit);

    echo json_encode(['success' => true, 'data' => $products]);
}

function handleLogs($db, $postData = []) {
    $filters = [
        'eventType' => $postData['eventType'] ?? $_GET['eventType'] ?? null,
        'limit' => $postData['limit'] ?? $_GET['limit'] ?? 50
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
