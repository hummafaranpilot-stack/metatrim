<?php
/**
 * Database Connection Test
 * Upload this to dashboard-v2/ folder and access via browser
 * URL: https://metatrim.trustednutraproduct.com/shaver/test-db.php
 */

require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

try {
    echo "Testing BuyGoods database connection...\n\n";

    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "✓ Database connection successful!\n\n";

    // Test 1: Check if orders table exists
    echo "Test 1: Checking orders table...\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders");
    $result = $stmt->fetch();
    echo "✓ Orders table exists with " . $result['count'] . " records\n\n";

    // Test 2: Check if recurring_charges table exists
    echo "Test 2: Checking recurring_charges table...\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM recurring_charges");
    $result = $stmt->fetch();
    echo "✓ Recurring charges table exists with " . $result['count'] . " records\n\n";

    // Test 3: Check status values in recurring_charges
    echo "Test 3: Checking recurring_charges status values...\n";
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM recurring_charges GROUP BY status");
    $statuses = $stmt->fetchAll();
    foreach ($statuses as $status) {
        echo "  - Status '" . $status['status'] . "': " . $status['count'] . " records\n";
    }
    echo "\n";

    // Test 4: Test getDashboardStats method
    echo "Test 4: Testing getDashboardStats method...\n";
    $stats = $db->getDashboardStats();
    echo "✓ getDashboardStats executed successfully\n";
    echo "  - Total Orders: " . ($stats['orders']['total_orders'] ?? 'NULL') . "\n";
    echo "  - Total Revenue: " . ($stats['summary']['total_revenue'] ?? 'NULL') . "\n\n";

    echo "=== ALL TESTS PASSED ===\n";
    echo "Database is working correctly!\n";

} catch (Exception $e) {
    echo "\n\n✗ ERROR DETECTED:\n";
    echo "Message: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
