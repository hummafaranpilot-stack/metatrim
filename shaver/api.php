<?php
/**
 * Affiliate Shaving API
 *
 * REST API endpoints for managing shaving sessions, tracking, and analytics
 */

require_once 'config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($method === 'OPTIONS') {
    exit(0);
}

// Get action from GET or POST body
$request = isset($_GET['action']) ? $_GET['action'] : '';
if (empty($request) && $method === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true);
    $request = $postData['action'] ?? '';
}

try {
    switch ($request) {
        case 'get_sessions':
        case 'getSessions':
            getSessions($pdo);
            break;

        case 'create_session':
        case 'createSession':
            createSession($pdo);
            break;

        case 'stop_session':
        case 'stopSession':
            stopSession($pdo);
            break;

        case 'track_visit':
        case 'trackVisit':
            trackVisit($pdo);
            break;

        case 'track_click':
        case 'trackClick':
            trackClick($pdo);
            break;

        case 'get_history':
        case 'getHistory':
            getHistory($pdo);
            break;

        case 'delete_history':
        case 'deleteHistory':
            deleteHistory($pdo);
            break;

        // Analytics endpoints
        case 'log_traffic':
        case 'logTraffic':
            logTraffic($pdo);
            break;

        case 'get_analytics':
        case 'getAnalytics':
            getAnalytics($pdo);
            break;

        case 'get_traffic_log':
        case 'getTrafficLog':
            getTrafficLog($pdo);
            break;

        // NEW: Behavior tracking endpoints
        case 'log_behavior_event':
        case 'logBehaviorEvent':
            logBehaviorEvent($pdo);
            break;

        case 'update_session_metrics':
        case 'updateSessionMetrics':
            updateSessionMetrics($pdo);
            break;

        case 'get_behavior_details':
        case 'getBehaviorDetails':
            getBehaviorDetails($pdo);
            break;

        case 'get_traffic_chart':
        case 'getTrafficChart':
            getTrafficChart($pdo);
            break;

        case 'get_breakdowns':
        case 'getBreakdowns':
            getBreakdowns($pdo);
            break;

        case 'get_unique_landing_pages':
        case 'getUniqueLandingPages':
            getUniqueLandingPages($pdo);
            break;

        case 'get_unique_affiliates':
        case 'getUniqueAffiliates':
            getUniqueAffiliates($pdo);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Get all active sessions
function getSessions($pdo) {
    $stmt = $pdo->query("
        SELECT
            s.*,
            COALESCE(SUM(CASE WHEN t.event_type = 'visit' THEN 1 ELSE 0 END), 0) as visits,
            COALESCE(SUM(CASE WHEN t.event_type = 'click' THEN 1 ELSE 0 END), 0) as clicks
        FROM shaving_sessions s
        LEFT JOIN shaving_tracking t ON s.id = t.session_id
        WHERE s.active = 1
        GROUP BY s.id
        ORDER BY s.start_time DESC
    ");

    $sessions = $stmt->fetchAll();

    $formattedSessions = array_map(function($session) {
        $startTimestamp = strtotime($session['start_time']) * 1000;

        return [
            'id' => $session['id'],
            'affId' => $session['aff_id'],
            'subId' => $session['sub_id'],
            'replaceMode' => (bool)$session['replace_mode'],
            'replaceAffId' => $session['replace_aff_id'],
            'replaceSubId' => $session['replace_sub_id'],
            'startTime' => $startTimestamp,
            'active' => (bool)$session['active'],
            'visits' => (int)$session['visits'],
            'clicks' => (int)$session['clicks']
        ];
    }, $sessions);

    echo json_encode([
        'success' => true,
        'data' => $formattedSessions,
        'sessions' => $formattedSessions  // For dashboard compatibility
    ]);
}

// Create new shaving session
function createSession($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Support both old format (aff_id, sub_id) and new format (affiliateId, subId, landingPage, etc.)
    $id = $data['id'] ?? uniqid('session_', true);
    $affId = $data['aff_id'] ?? $data['affId'] ?? $data['affiliateId'] ?? '';
    $subId = $data['sub_id'] ?? $data['subId'] ?? '';

    // Old format fields
    $replaceMode = $data['replace_mode'] ?? $data['replaceMode'] ?? false;
    $replaceAffId = $data['replace_aff_id'] ?? $data['replaceAffId'] ?? '';
    $replaceSubId = $data['replace_sub_id'] ?? $data['replaceSubId'] ?? '';

    // New format fields from create-session.html
    $landingPage = $data['landingPage'] ?? '';
    $shavingPercentage = $data['shavingPercentage'] ?? 20;
    $sessionDuration = $data['sessionDuration'] ?? 30; // minutes
    $targetCountry = $data['targetCountry'] ?? '';
    $targetDevice = $data['targetDevice'] ?? '';
    $shavingRules = $data['shavingRules'] ?? '';
    $autoStart = $data['autoStart'] ?? true;
    $trackBehavior = $data['trackBehavior'] ?? true;

    $startTime = $data['start_time'] ?? $data['startTime'] ?? date('Y-m-d H:i:s');

    if (empty($affId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Affiliate ID is required']);
        return;
    }

    // Check if session already exists for this affiliate
    $stmt = $pdo->prepare("SELECT id FROM shaving_sessions WHERE aff_id = ? AND sub_id = ? AND active = 1");
    $stmt->execute([$affId, $subId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Active session already exists for this affiliate ID. Please stop the existing session first.']);
        return;
    }

    // Build session configuration JSON
    $sessionConfig = [
        'landing_page' => $landingPage,
        'shaving_percentage' => (float)$shavingPercentage,
        'session_duration' => (int)$sessionDuration,
        'target_country' => $targetCountry,
        'target_device' => $targetDevice,
        'shaving_rules' => $shavingRules,
        'auto_start' => (bool)$autoStart,
        'track_behavior' => (bool)$trackBehavior
    ];

    // Insert session into database
    $stmt = $pdo->prepare("
        INSERT INTO shaving_sessions (id, aff_id, sub_id, replace_mode, replace_aff_id, replace_sub_id, start_time, active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");

    try {
        $stmt->execute([
            $id,
            $affId,
            $subId,
            $replaceMode ? 1 : 0,
            $replaceAffId,
            $replaceSubId,
            $startTime
        ]);

        // Log session configuration (you could store this in a separate table or JSON field)
        // For now, we'll just log it to PHP error log for tracking
        error_log("Created shaving session: ID=$id, Aff=$affId, Config=" . json_encode($sessionConfig));

        echo json_encode([
            'success' => true,
            'sessionId' => $id,
            'message' => 'Session created successfully',
            'config' => $sessionConfig
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create session: ' . $e->getMessage()
        ]);
    }
}

// Stop a shaving session
function stopSession($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? $data['sessionId'] ?? '';

    if (empty($sessionId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID is required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM shaving_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN event_type = 'visit' THEN 1 ELSE 0 END) as visits,
            SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
        FROM shaving_tracking
        WHERE session_id = ?
    ");
    $stmt->execute([$sessionId]);
    $stats = $stmt->fetch();

    $stopTime = date('Y-m-d H:i:s');
    $startTime = strtotime($session['start_time']);
    $duration = time() - $startTime;

    $stmt = $pdo->prepare("
        INSERT INTO shaving_history
        (session_id, aff_id, sub_id, replace_mode, replace_aff_id, replace_sub_id, start_time, stop_time, total_visits, total_clicks, duration)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $sessionId,
        $session['aff_id'],
        $session['sub_id'],
        $session['replace_mode'],
        $session['replace_aff_id'],
        $session['replace_sub_id'],
        $session['start_time'],
        $stopTime,
        $stats['visits'] ?? 0,
        $stats['clicks'] ?? 0,
        $duration
    ]);

    $stmt = $pdo->prepare("UPDATE shaving_sessions SET active = 0 WHERE id = ?");
    $stmt->execute([$sessionId]);

    echo json_encode(['success' => true]);
}

// Track a visit (shaved)
function trackVisit($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $sessionId = $data['session_id'] ?? $data['sessionId'] ?? '';
    $affId = $data['aff_id'] ?? $data['affId'] ?? '';
    $subId = $data['sub_id'] ?? $data['subId'] ?? '';
    $page = $data['page'] ?? '';
    $referrer = $data['referrer'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO shaving_tracking (session_id, aff_id, sub_id, event_type, page, referrer, timestamp)
        VALUES (?, ?, ?, 'visit', ?, ?, NOW())
    ");

    $stmt->execute([$sessionId, $affId, $subId, $page, $referrer]);

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shaving_tracking WHERE session_id = ? AND event_type = 'visit'");
    $stmt->execute([$sessionId]);
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'totalVisits' => $result['total']
    ]);
}

// Track a click (shaved)
function trackClick($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $sessionId = $data['session_id'] ?? $data['sessionId'] ?? '';
    $affId = $data['aff_id'] ?? $data['affId'] ?? '';
    $subId = $data['sub_id'] ?? $data['subId'] ?? '';
    $page = $data['page'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO shaving_tracking (session_id, aff_id, sub_id, event_type, page, timestamp)
        VALUES (?, ?, ?, 'click', ?, NOW())
    ");

    $stmt->execute([$sessionId, $affId, $subId, $page]);

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shaving_tracking WHERE session_id = ? AND event_type = 'click'");
    $stmt->execute([$sessionId]);
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'totalClicks' => $result['total']
    ]);
}

// Get session history
function getHistory($pdo) {
    $stmt = $pdo->query("
        SELECT * FROM shaving_history
        ORDER BY stop_time DESC
        LIMIT 100
    ");

    $history = $stmt->fetchAll();

    $formattedHistory = array_map(function($item) {
        $startTimestamp = strtotime($item['start_time']) * 1000;
        $stopTimestamp = strtotime($item['stop_time']) * 1000;

        return [
            'id' => $item['session_id'],
            'affId' => $item['aff_id'],
            'subId' => $item['sub_id'],
            'replaceMode' => (bool)$item['replace_mode'],
            'replaceAffId' => $item['replace_aff_id'],
            'replaceSubId' => $item['replace_sub_id'],
            'startTime' => $startTimestamp,
            'stopTime' => $stopTimestamp,
            'visits' => (int)$item['total_visits'],
            'clicks' => (int)$item['total_clicks'],
            'duration' => (int)$item['duration']
        ];
    }, $history);

    echo json_encode([
        'success' => true,
        'data' => $formattedHistory
    ]);
}

// Delete history entry
function deleteHistory($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $historyId = $data['session_id'] ?? $data['historyId'] ?? '';

    if (empty($historyId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID is required']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM shaving_history WHERE session_id = ?");
    $stmt->execute([$historyId]);

    echo json_encode(['success' => true]);
}

// ================================================================
// NEW: ANALYTICS FUNCTIONS
// ================================================================

// Log ALL affiliate traffic
function logTraffic($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $affId = $data['aff_id'] ?? '';
    $subId = $data['sub_id'] ?? '';
    $pageUrl = $data['page_url'] ?? '';
    $referrer = $data['referrer'] ?? '';
    $userAgent = $data['user_agent'] ?? '';
    $wasShaved = $data['was_shaved'] ?? false;
    $shavingSessionId = $data['shaving_session_id'] ?? null;

    // NEW: Behavior tracking fields
    $sessionUUID = $data['session_uuid'] ?? null;
    $screenWidth = $data['screen_width'] ?? null;
    $screenHeight = $data['screen_height'] ?? null;
    $viewportWidth = $data['viewport_width'] ?? null;
    $viewportHeight = $data['viewport_height'] ?? null;

    if (empty($affId)) {
        echo json_encode(['success' => true, 'skipped' => true]);
        return;
    }

    // Get visitor IP
    $ip = getClientIP();

    // Parse browser and device from user agent
    $browserInfo = parseBrowserInfo($userAgent);

    // Get country from IP (using free API)
    $geoInfo = getGeoInfo($ip);

    $stmt = $pdo->prepare("
        INSERT INTO affiliate_traffic
        (aff_id, sub_id, page_url, referrer, user_agent, browser, device, ip_address, country, country_code,
         was_shaved, shaving_session_id, session_uuid, screen_width, screen_height, viewport_width, viewport_height)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $affId,
        $subId,
        $pageUrl,
        $referrer,
        $userAgent,
        $browserInfo['browser'],
        $browserInfo['device'],
        $ip,
        $geoInfo['country'],
        $geoInfo['countryCode'],
        $wasShaved ? 1 : 0,
        $shavingSessionId,
        $sessionUUID,
        $screenWidth,
        $screenHeight,
        $viewportWidth,
        $viewportHeight
    ]);

    $trafficId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'traffic_id' => $trafficId  // CRITICAL: Return traffic_id for behavior tracking
    ]);
}

// Get analytics data
function getAnalytics($pdo) {
    // Get POST data if available
    $postData = json_decode(file_get_contents('php://input'), true);
    $period = $postData['period'] ?? $_GET['period'] ?? 'today';

    // Calculate time filter
    $dateRangeDisplay = '';
    switch ($period) {
        case 'today':
            // Today in PKT (Pakistan Time = UTC + 5)
            // Get current time in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $start = clone $now;
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);

            $dateRangeDisplay = $now->format('M d, Y');

            // Convert to UTC for MySQL (which stores in UTC)
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
            break;

        case 'yesterday':
            // Yesterday in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $yesterday = clone $now;
            $yesterday->modify('-1 day');
            $start = clone $yesterday;
            $start->setTime(0, 0, 0);
            $end = clone $yesterday;
            $end->setTime(23, 59, 59);

            $dateRangeDisplay = $yesterday->format('M d, Y');

            // Convert to UTC
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
            break;

        case 'thisweek':
            // This week (Monday to Sunday) in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $dayOfWeek = $now->format('N'); // 1 (Monday) to 7 (Sunday)

            // Get Monday of this week
            $monday = clone $now;
            $monday->modify('-' . ($dayOfWeek - 1) . ' days');
            $monday->setTime(0, 0, 0);

            // Get Sunday of this week
            $sunday = clone $monday;
            $sunday->modify('+6 days');
            $sunday->setTime(23, 59, 59);

            $dateRangeDisplay = $monday->format('M d') . ' - ' . $sunday->format('M d, Y');

            // Convert to UTC
            $monday->setTimezone(new DateTimeZone('UTC'));
            $sunday->setTimezone(new DateTimeZone('UTC'));

            $startStr = $monday->format('Y-m-d H:i:s');
            $endStr = $sunday->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
            break;

        case 'lastweek':
            // Last week (Monday to Sunday) in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $dayOfWeek = $now->format('N');

            // Get Monday of last week
            $lastMonday = clone $now;
            $lastMonday->modify('-' . ($dayOfWeek + 6) . ' days');
            $lastMonday->setTime(0, 0, 0);

            // Get Sunday of last week
            $lastSunday = clone $lastMonday;
            $lastSunday->modify('+6 days');
            $lastSunday->setTime(23, 59, 59);

            $dateRangeDisplay = $lastMonday->format('M d') . ' - ' . $lastSunday->format('M d, Y');

            // Convert to UTC
            $lastMonday->setTimezone(new DateTimeZone('UTC'));
            $lastSunday->setTimezone(new DateTimeZone('UTC'));

            $startStr = $lastMonday->format('Y-m-d H:i:s');
            $endStr = $lastSunday->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
            break;

        case 'thismonth':
            // This month in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $start = clone $now;
            $start->setDate($now->format('Y'), $now->format('m'), 1);
            $start->setTime(0, 0, 0);

            $end = clone $start;
            $end->modify('last day of this month');
            $end->setTime(23, 59, 59);

            $dateRangeDisplay = $start->format('M d') . ' - ' . $end->format('M d, Y');

            // Convert to UTC
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
            break;

        case 'all':
            $timeFilter = "1=1";
            $dateRangeDisplay = 'All Time';
            break;

        default:
            // Default to today
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $start = clone $now;
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));
            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            $timeFilter = "timestamp >= '$startStr' AND timestamp <= '$endStr'";
    }

    // Total stats with averages and checkout rate
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_visits,
            COUNT(DISTINCT aff_id) as unique_affiliates,
            SUM(was_shaved) as shaved_visits,
            COUNT(DISTINCT ip_address) as unique_visitors,
            AVG(COALESCE(session_duration, 0)) as avg_session_time,
            AVG(COALESCE(max_scroll_depth, 0)) as avg_scroll_depth,
            SUM(CASE WHEN reached_checkout = 1 THEN 1 ELSE 0 END) as checkout_count,
            SUM(COALESCE(total_clicks, 0)) as total_clicks
        FROM affiliate_traffic
        WHERE $timeFilter
    ");
    $totals = $stmt->fetch();

    // Calculate checkout rate as percentage
    $checkoutRate = $totals['total_visits'] > 0
        ? round(($totals['checkout_count'] / $totals['total_visits']) * 100, 2)
        : 0;

    // Top affiliates
    $stmt = $pdo->query("
        SELECT
            aff_id,
            COUNT(*) as visits,
            SUM(was_shaved) as shaved,
            COUNT(DISTINCT ip_address) as unique_ips
        FROM affiliate_traffic
        WHERE $timeFilter
        GROUP BY aff_id
        ORDER BY visits DESC
        LIMIT 20
    ");
    $topAffiliates = $stmt->fetchAll();

    // Browser breakdown
    $stmt = $pdo->query("
        SELECT browser, COUNT(*) as count
        FROM affiliate_traffic
        WHERE $timeFilter AND browser IS NOT NULL AND browser != ''
        GROUP BY browser
        ORDER BY count DESC
        LIMIT 10
    ");
    $browsers = $stmt->fetchAll();

    // Device breakdown
    $stmt = $pdo->query("
        SELECT device, COUNT(*) as count
        FROM affiliate_traffic
        WHERE $timeFilter AND device IS NOT NULL AND device != ''
        GROUP BY device
        ORDER BY count DESC
    ");
    $devices = $stmt->fetchAll();

    // Country breakdown
    $stmt = $pdo->query("
        SELECT country, country_code, COUNT(*) as count
        FROM affiliate_traffic
        WHERE $timeFilter AND country IS NOT NULL AND country != ''
        GROUP BY country, country_code
        ORDER BY count DESC
        LIMIT 15
    ");
    $countries = $stmt->fetchAll();

    // Top referrers
    $stmt = $pdo->query("
        SELECT
            CASE
                WHEN referrer = '' OR referrer IS NULL OR referrer = 'direct' THEN 'Direct'
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1)
            END as source,
            COUNT(*) as count
        FROM affiliate_traffic
        WHERE $timeFilter
        GROUP BY source
        ORDER BY count DESC
        LIMIT 10
    ");
    $referrers = $stmt->fetchAll();

    // Hourly traffic (last 24 hours)
    $stmt = $pdo->query("
        SELECT
            DATE_FORMAT(timestamp, '%Y-%m-%d %H:00') as hour,
            COUNT(*) as visits
        FROM affiliate_traffic
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY hour
        ORDER BY hour ASC
    ");
    $hourlyTraffic = $stmt->fetchAll();

    // Top landing pages - show exact path structure
    $stmt = $pdo->query("
        SELECT
            REPLACE(
                REPLACE(
                    REPLACE(
                        SUBSTRING_INDEX(SUBSTRING_INDEX(page_url, '?', 1), '/', -4),
                        '/index.html', ''
                    ),
                    '.html', ''
                ),
                '.php', ''
            ) as landing_page,
            COUNT(*) as count
        FROM affiliate_traffic
        WHERE $timeFilter AND page_url IS NOT NULL AND page_url != ''
        GROUP BY landing_page
        ORDER BY count DESC
        LIMIT 10
    ");
    $landingPages = $stmt->fetchAll();

    $analyticsData = [
        'totals' => [
            'totalVisits' => (int)$totals['total_visits'],
            'uniqueAffiliates' => (int)$totals['unique_affiliates'],
            'shavedVisits' => (int)$totals['shaved_visits'],
            'uniqueVisitors' => (int)$totals['unique_visitors'],
            'avgSessionTime' => round((float)$totals['avg_session_time'], 2),
            'avgScrollDepth' => normalizeScrollDepth((float)$totals['avg_scroll_depth']), // Normalize to 0-1 decimal
            'checkoutRate' => $checkoutRate / 100, // Convert to 0-1 decimal (frontend will multiply by 100)
            'totalClicks' => (int)$totals['total_clicks']
        ],
        'topAffiliates' => $topAffiliates,
        'browsers' => $browsers,
        'devices' => $devices,
        'countries' => $countries,
        'referrers' => $referrers,
        'landingPages' => $landingPages,
        'hourlyTraffic' => $hourlyTraffic
    ];

    // Flattened version for dashboard compatibility
    $dashboardStats = [
        'totalVisits' => (int)$totals['total_visits'],
        'todayVisits' => (int)$totals['total_visits'],
        'shavedVisits' => (int)$totals['shaved_visits'],
        'uniqueVisitors' => (int)$totals['unique_visitors'],
        'checkoutRate' => $checkoutRate / 100, // Convert to 0-1 decimal (frontend will multiply by 100)
        'uniqueAffiliates' => (int)$totals['unique_affiliates'],
        'avgSessionTime' => round((float)$totals['avg_session_time'], 2),
        'avgScrollDepth' => normalizeScrollDepth((float)$totals['avg_scroll_depth']) // Normalize to 0-1 decimal
    ];

    echo json_encode([
        'success' => true,
        'data' => $analyticsData,
        'stats' => $dashboardStats,  // Flattened for dashboard compatibility
        'period' => $period,
        'dateRange' => $dateRangeDisplay
    ]);
}

// Get recent traffic log with period filtering
function getTrafficLog($pdo) {
    // Get POST data if available
    $postData = json_decode(file_get_contents('php://input'), true);

    $limit = $postData['limit'] ?? $_GET['limit'] ?? 50;
    $limit = min((int)$limit, 200);
    $affId = $postData['aff_id'] ?? $_GET['aff_id'] ?? '';
    $period = $postData['period'] ?? $_GET['period'] ?? 'all';
    $landingPage = $postData['landing_page'] ?? $_GET['landing_page'] ?? '';
    $scrollRange = $postData['scroll_range'] ?? $_GET['scroll_range'] ?? '';
    $checkoutStatus = $postData['checkout_status'] ?? $_GET['checkout_status'] ?? '';

    // Build WHERE clause
    $whereConditions = [];
    $params = [];

    // Add period filter
    if ($period !== 'all') {
        $periodClause = getPeriodWhereClause($period);
        $whereConditions[] = "($periodClause)";
    }

    // Add affiliate filter
    if (!empty($affId)) {
        $whereConditions[] = "aff_id = ?";
        $params[] = $affId;
    }

    // Add landing page filter
    if (!empty($landingPage)) {
        $whereConditions[] = "page_url LIKE ?";
        $params[] = '%' . $landingPage . '%';
    }

    // Add scroll depth range filter
    if (!empty($scrollRange)) {
        switch ($scrollRange) {
            case '0-25':
                $whereConditions[] = "max_scroll_depth >= 0 AND max_scroll_depth < 0.25";
                break;
            case '25-50':
                $whereConditions[] = "max_scroll_depth >= 0.25 AND max_scroll_depth < 0.50";
                break;
            case '50-75':
                $whereConditions[] = "max_scroll_depth >= 0.50 AND max_scroll_depth < 0.75";
                break;
            case '75-100':
                $whereConditions[] = "max_scroll_depth >= 0.75 AND max_scroll_depth <= 1.0";
                break;
        }
    }

    // Add checkout status filter
    if ($checkoutStatus === 'yes') {
        $whereConditions[] = "reached_checkout = 1";
    } elseif ($checkoutStatus === 'no') {
        $whereConditions[] = "reached_checkout = 0";
    }

    $sql = "SELECT * FROM affiliate_traffic";
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(' AND ', $whereConditions);
    }
    $sql .= " ORDER BY timestamp DESC LIMIT $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $traffic = $stmt->fetchAll();

    $formatted = array_map(function($item) {
        // Extract source from referrer
        $source = 'Direct';
        if (!empty($item['referrer']) && $item['referrer'] !== 'direct') {
            $source = parse_url($item['referrer'], PHP_URL_HOST) ?? 'Direct';
        }

        // Extract landing page (last 2-3 path segments)
        $landingPage = 'Unknown';
        if (!empty($item['page_url'])) {
            $path = parse_url($item['page_url'], PHP_URL_PATH);
            if ($path) {
                $segments = array_filter(explode('/', $path));
                $landingPage = implode('/', array_slice($segments, -2));
                $landingPage = str_replace(['.html', '.php'], '', $landingPage);
            }
        }

        return [
            'id' => $item['id'],
            'aff_id' => $item['aff_id'] ?? null,  // Keep actual value, let frontend handle display
            'affId' => $item['aff_id'] ?? null,
            'aff_name' => getAffiliateDisplayName($item['aff_id']),  // Add formatted name
            'subId' => $item['sub_id'],
            'landing_page' => $landingPage,
            'pageUrl' => $item['page_url'],
            'referrer' => $item['referrer'],
            'source' => $source,
            'browser' => $item['browser'] ?? 'Unknown',
            'device' => $item['device'] ?? 'Desktop',
            'ip' => $item['ip_address'],
            'country' => $item['country'] ?? 'Unknown',
            'countryCode' => $item['country_code'],
            'wasShaved' => (bool)$item['was_shaved'],
            'was_shaved' => (bool)$item['was_shaved'],
            'timestamp' => $item['timestamp'],
            // Behavior metrics - map to both old and new naming
            'duration' => $item['session_duration'] ?? 0,
            'session_duration' => $item['session_duration'] ?? 0,
            'scroll_depth' => normalizeScrollDepth($item['max_scroll_depth'] ?? 0),
            'max_scroll_depth' => normalizeScrollDepth($item['max_scroll_depth'] ?? 0),
            'maxScrollDepth' => normalizeScrollDepth($item['max_scroll_depth'] ?? 0),
            'clicks' => $item['total_clicks'] ?? 0,
            'total_clicks' => $item['total_clicks'] ?? 0,
            'totalClicks' => $item['total_clicks'] ?? 0,
            'checkout_completed' => (bool)($item['reached_checkout'] ?? 0),
            'reached_checkout' => (bool)($item['reached_checkout'] ?? 0),
            'reachedCheckout' => (bool)($item['reached_checkout'] ?? 0),
            'timeToFirstClick' => $item['time_to_first_click'],
            'timeToCheckout' => $item['time_to_checkout'],
            'bounce' => (bool)($item['bounce'] ?? 1)
        ];
    }, $traffic);

    echo json_encode([
        'success' => true,
        'data' => $formatted,
        'traffic' => $formatted  // For dashboard compatibility
    ]);
}

// ================================================================
// NEW: BEHAVIOR TRACKING ENDPOINTS
// ================================================================

// Log individual behavior event
function logBehaviorEvent($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $trafficId = $data['traffic_id'] ?? null;
    $sessionUUID = $data['session_uuid'] ?? null;
    $eventType = $data['event_type'] ?? '';
    $eventData = $data['event_data'] ?? [];
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

    if (empty($trafficId) || empty($sessionUUID) || empty($eventType)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Validate event type
    $validEvents = ['page_view', 'scroll', 'click', 'hover', 'checkout_reached', 'tab_hidden', 'tab_visible'];
    if (!in_array($eventType, $validEvents)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid event type']);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_behavior_events
        (traffic_id, session_uuid, event_type, event_data, timestamp)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $trafficId,
        $sessionUUID,
        $eventType,
        json_encode($eventData),
        $timestamp
    ]);

    echo json_encode(['success' => true, 'event_id' => $pdo->lastInsertId()]);
}

// Update session-level metrics
function updateSessionMetrics($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);

    $trafficId = $data['traffic_id'] ?? null;
    $sessionDuration = $data['session_duration'] ?? null;
    $maxScrollDepth = $data['max_scroll_depth'] ?? 0;
    $totalClicks = $data['total_clicks'] ?? 0;
    $reachedCheckout = $data['reached_checkout'] ?? 0;
    $checkoutUrl = $data['checkout_url'] ?? null;
    $timeToFirstClick = $data['time_to_first_click'] ?? null;
    $timeToCheckout = $data['time_to_checkout'] ?? null;
    $screenWidth = $data['screen_width'] ?? null;
    $screenHeight = $data['screen_height'] ?? null;
    $viewportWidth = $data['viewport_width'] ?? null;
    $viewportHeight = $data['viewport_height'] ?? null;
    $pageLoadTime = $data['page_load_time'] ?? null;
    $bounce = $data['bounce'] ?? 1;

    if (empty($trafficId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing traffic_id']);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE affiliate_traffic
        SET session_duration = ?,
            max_scroll_depth = ?,
            total_clicks = ?,
            reached_checkout = ?,
            checkout_url = ?,
            time_to_first_click = ?,
            time_to_checkout = ?,
            screen_width = ?,
            screen_height = ?,
            viewport_width = ?,
            viewport_height = ?,
            page_load_time = ?,
            bounce = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $sessionDuration,
        $maxScrollDepth,
        $totalClicks,
        $reachedCheckout,
        $checkoutUrl,
        $timeToFirstClick,
        $timeToCheckout,
        $screenWidth,
        $screenHeight,
        $viewportWidth,
        $viewportHeight,
        $pageLoadTime,
        $bounce,
        $trafficId
    ]);

    echo json_encode(['success' => true]);
}

// Get detailed behavior events for a traffic session
function getBehaviorDetails($pdo) {
    $trafficId = $_GET['traffic_id'] ?? null;

    if (empty($trafficId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing traffic_id']);
        return;
    }

    // Get session info
    $stmt = $pdo->prepare("SELECT * FROM affiliate_traffic WHERE id = ?");
    $stmt->execute([$trafficId]);
    $sessionInfo = $stmt->fetch();

    if (!$sessionInfo) {
        http_response_code(404);
        echo json_encode(['error' => 'Traffic session not found']);
        return;
    }

    // Get all behavior events
    $stmt = $pdo->prepare("
        SELECT event_type, event_data, timestamp
        FROM user_behavior_events
        WHERE traffic_id = ?
        ORDER BY timestamp ASC
    ");
    $stmt->execute([$trafficId]);
    $events = $stmt->fetchAll();

    // Parse event_data JSON
    foreach ($events as &$event) {
        $event['event_data'] = json_decode($event['event_data'], true);
    }

    echo json_encode([
        'success' => true,
        'session' => [
            'traffic_id' => $sessionInfo['id'],
            'aff_id' => $sessionInfo['aff_id'],
            'sub_id' => $sessionInfo['sub_id'],
            'ip_address' => $sessionInfo['ip_address'],
            'browser' => $sessionInfo['browser'],
            'device' => $sessionInfo['device'],
            'country' => $sessionInfo['country'],
            'timestamp' => $sessionInfo['timestamp'],
            'session_duration' => $sessionInfo['session_duration'],
            'max_scroll_depth' => $sessionInfo['max_scroll_depth'],
            'total_clicks' => $sessionInfo['total_clicks'],
            'reached_checkout' => (bool)$sessionInfo['reached_checkout'],
            'checkout_url' => $sessionInfo['checkout_url'],
            'time_to_first_click' => $sessionInfo['time_to_first_click'],
            'time_to_checkout' => $sessionInfo['time_to_checkout'],
            'bounce' => (bool)$sessionInfo['bounce']
        ],
        'events' => $events
    ]);
}

// ================================================================
// HELPER FUNCTIONS
// ================================================================

// Helper: Get client IP
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return trim($ip);
}

// Helper: Parse browser info from user agent
function parseBrowserInfo($userAgent) {
    $browser = 'Unknown';
    $device = 'Desktop';

    // Detect browser
    if (preg_match('/MSIE|Trident/i', $userAgent)) {
        $browser = 'IE';
    } elseif (preg_match('/Edg/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edg/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
        $browser = 'Opera';
    }

    // Detect device
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
        if (preg_match('/iPad|Tablet/i', $userAgent)) {
            $device = 'Tablet';
        } else {
            $device = 'Mobile';
        }
    }

    return ['browser' => $browser, 'device' => $device];
}

// Helper: Get geo info from IP
function getGeoInfo($ip) {
    $country = 'Unknown';
    $countryCode = 'XX';

    // Skip for local IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        return ['country' => 'Local', 'countryCode' => 'LO'];
    }

    // Use free IP-API (no key required, 45 requests/minute)
    $url = "http://ip-api.com/json/{$ip}?fields=country,countryCode";

    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['country'])) {
            $country = $data['country'];
            $countryCode = $data['countryCode'] ?? 'XX';
        }
    }

    return ['country' => $country, 'countryCode' => $countryCode];
}

// Get traffic chart data for dashboard
function getTrafficChart($pdo) {
    // Get POST data if available
    $postData = json_decode(file_get_contents('php://input'), true);

    $days = $postData['days'] ?? $_GET['days'] ?? 30;
    $days = min((int)$days, 365); // Max 1 year

    // Get daily traffic for the last N days
    $stmt = $pdo->prepare("
        SELECT
            DATE(timestamp) as date,
            COUNT(*) as visits,
            SUM(was_shaved) as shaved_visits,
            COUNT(DISTINCT aff_id) as unique_affiliates
        FROM affiliate_traffic
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");

    $stmt->execute([$days]);
    $chartData = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $chartData
    ]);
}

// Get breakdown data for analytics cards (landing pages, countries, devices, browsers, sources, affiliates)
function getBreakdowns($pdo) {
    $postData = json_decode(file_get_contents('php://input'), true);
    $period = $postData['period'] ?? $_GET['period'] ?? 'today';

    $whereClause = getPeriodWhereClause($period);

    // Top Landing Pages - show exact page structure
    $stmt = $pdo->query("
        SELECT
            CASE
                WHEN page_url IS NULL OR page_url = '' THEN 'Unknown'
                ELSE REPLACE(
                    REPLACE(
                        REPLACE(
                            SUBSTRING_INDEX(SUBSTRING_INDEX(page_url, '?', 1), '/', -3),
                            '/index.html', ''
                        ),
                        '.html', ''
                    ),
                    '.php', ''
                )
            END as label,
            COUNT(*) as value
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY label
        ORDER BY value DESC
        LIMIT 5
    ");
    $landingPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Countries
    $stmt = $pdo->query("
        SELECT
            COALESCE(country, 'Unknown') as label,
            COUNT(*) as value
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY country
        ORDER BY value DESC
        LIMIT 5
    ");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Device Breakdown
    $stmt = $pdo->query("
        SELECT
            COALESCE(device, 'Unknown') as label,
            COUNT(*) as value
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY device
        ORDER BY value DESC
    ");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Browser Breakdown
    $stmt = $pdo->query("
        SELECT
            COALESCE(browser, 'Unknown') as label,
            COUNT(*) as value
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY browser
        ORDER BY value DESC
        LIMIT 5
    ");
    $browsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Traffic Sources - extract domain from referrer
    $stmt = $pdo->query("
        SELECT
            CASE
                WHEN referrer = '' OR referrer IS NULL OR referrer = 'direct' THEN 'Direct'
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1)
            END as label,
            COUNT(*) as value
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY label
        ORDER BY value DESC
        LIMIT 5
    ");
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Affiliates - show actual affiliate IDs with names
    $stmt = $pdo->query("
        SELECT
            aff_id,
            COUNT(*) as value,
            SUM(was_shaved) as shaved,
            COUNT(DISTINCT session_uuid) as sessions
        FROM affiliate_traffic
        WHERE $whereClause
        GROUP BY aff_id
        ORDER BY value DESC
        LIMIT 10
    ");
    $affiliatesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add affiliate names to labels
    $affiliates = array_map(function($row) {
        $row['label'] = getAffiliateDisplayName($row['aff_id']);
        return $row;
    }, $affiliatesRaw);

    echo json_encode([
        'success' => true,
        'landingPages' => $landingPages,
        'countries' => $countries,
        'devices' => $devices,
        'browsers' => $browsers,
        'sources' => $sources,
        'affiliates' => $affiliates
    ]);
}

// Get unique landing pages for filter dropdown
function getUniqueLandingPages($pdo) {
    $stmt = $pdo->query("
        SELECT
            REPLACE(
                REPLACE(
                    REPLACE(
                        SUBSTRING_INDEX(SUBSTRING_INDEX(page_url, '?', 1), '/', -3),
                        '/index.html', ''
                    ),
                    '.html', ''
                ),
                '.php', ''
            ) as landing_page,
            COUNT(*) as visit_count
        FROM affiliate_traffic
        WHERE page_url IS NOT NULL AND page_url != ''
        GROUP BY landing_page
        ORDER BY visit_count DESC, landing_page ASC
        LIMIT 50
    ");

    $landingPages = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => $landingPages
    ]);
}

// Get unique affiliates for filter dropdown
function getUniqueAffiliates($pdo) {
    $stmt = $pdo->query("
        SELECT aff_id, COUNT(*) as visit_count
        FROM affiliate_traffic
        WHERE aff_id IS NOT NULL AND aff_id != ''
        GROUP BY aff_id
        ORDER BY visit_count DESC, aff_id ASC
        LIMIT 100
    ");

    $affiliates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Add affiliate names
    $affiliatesWithNames = array_map(function($affId) {
        return [
            'id' => $affId,
            'name' => getAffiliateDisplayName($affId)
        ];
    }, $affiliates);

    echo json_encode([
        'success' => true,
        'data' => $affiliatesWithNames
    ]);
}

// Helper function to normalize scroll depth to 0-1 range
function normalizeScrollDepth($value) {
    $value = floatval($value);

    // If value is greater than 1, assume it's stored as 0-100 instead of 0-1
    if ($value > 1) {
        return $value / 100;
    }

    return $value;
}

// Load affiliate ID to name mapping from CSV
function getAffiliateNameMapping() {
    static $mapping = null;

    if ($mapping !== null) {
        return $mapping;
    }

    $mapping = [];

    // Try multiple possible paths for the CSV file
    $possiblePaths = [
        __DIR__ . '/affiliates.csv',
        __DIR__ . '/../affiliates.csv',
        'C:/Users/technologyzone.pk/Downloads/AffiliatesIDName_02-07-2026_59_11943.csv'
    ];

    $csvPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $csvPath = $path;
            break;
        }
    }

    if (!$csvPath) {
        return $mapping; // Return empty mapping if file not found
    }

    // Read CSV file
    if (($handle = fopen($csvPath, 'r')) !== false) {
        // Skip header row
        fgetcsv($handle);

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 2) {
                $affId = trim($data[0]);
                $affName = trim($data[1]);
                $mapping[$affId] = $affName;
            }
        }
        fclose($handle);
    }

    return $mapping;
}

// Get affiliate display name (Name - ID format)
function getAffiliateDisplayName($affId) {
    if (empty($affId) || $affId === 'Direct') {
        return 'Direct';
    }

    $mapping = getAffiliateNameMapping();

    if (isset($mapping[$affId])) {
        return $mapping[$affId] . ' - ' . $affId;
    }

    // If name not found, just return ID
    return $affId;
}

// Helper function to get WHERE clause for period filtering
function getPeriodWhereClause($period) {
    switch ($period) {
        case 'today':
            // Today in PKT (Pakistan Time = UTC + 5)
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $start = clone $now;
            $start->setTime(0, 0, 0);
            $end = clone $now;
            $end->setTime(23, 59, 59);

            // Convert to UTC for MySQL
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            return "timestamp >= '$startStr' AND timestamp <= '$endStr'";

        case 'yesterday':
            // Yesterday in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $yesterday = clone $now;
            $yesterday->modify('-1 day');
            $start = clone $yesterday;
            $start->setTime(0, 0, 0);
            $end = clone $yesterday;
            $end->setTime(23, 59, 59);

            // Convert to UTC
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            return "timestamp >= '$startStr' AND timestamp <= '$endStr'";

        case 'thisweek':
            // This week (Monday to Sunday) in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $dayOfWeek = $now->format('N'); // 1 (Monday) to 7 (Sunday)

            // Get Monday of this week
            $monday = clone $now;
            $monday->modify('-' . ($dayOfWeek - 1) . ' days');
            $monday->setTime(0, 0, 0);

            // Get Sunday of this week
            $sunday = clone $monday;
            $sunday->modify('+6 days');
            $sunday->setTime(23, 59, 59);

            // Convert to UTC
            $monday->setTimezone(new DateTimeZone('UTC'));
            $sunday->setTimezone(new DateTimeZone('UTC'));

            $startStr = $monday->format('Y-m-d H:i:s');
            $endStr = $sunday->format('Y-m-d H:i:s');
            return "timestamp >= '$startStr' AND timestamp <= '$endStr'";

        case 'lastweek':
            // Last week (Monday to Sunday) in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $dayOfWeek = $now->format('N');

            // Get Monday of last week
            $lastMonday = clone $now;
            $lastMonday->modify('-' . ($dayOfWeek + 6) . ' days');
            $lastMonday->setTime(0, 0, 0);

            // Get Sunday of last week
            $lastSunday = clone $lastMonday;
            $lastSunday->modify('+6 days');
            $lastSunday->setTime(23, 59, 59);

            // Convert to UTC
            $lastMonday->setTimezone(new DateTimeZone('UTC'));
            $lastSunday->setTimezone(new DateTimeZone('UTC'));

            $startStr = $lastMonday->format('Y-m-d H:i:s');
            $endStr = $lastSunday->format('Y-m-d H:i:s');
            return "timestamp >= '$startStr' AND timestamp <= '$endStr'";

        case 'thismonth':
            // This month in PKT
            $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
            $start = clone $now;
            $start->setDate($now->format('Y'), $now->format('m'), 1);
            $start->setTime(0, 0, 0);

            $end = clone $start;
            $end->modify('last day of this month');
            $end->setTime(23, 59, 59);

            // Convert to UTC
            $start->setTimezone(new DateTimeZone('UTC'));
            $end->setTimezone(new DateTimeZone('UTC'));

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');
            return "timestamp >= '$startStr' AND timestamp <= '$endStr'";

        case 'all':
        default:
            return "1=1";
    }
}
?>
