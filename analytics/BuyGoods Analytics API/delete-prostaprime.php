<?php
/**
 * Delete ProstaPrime Product and its Orders
 * Run once to clean up test data
 * DELETE THIS FILE AFTER RUNNING
 */

require_once 'config.php';
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Find ProstaPrime product
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = 'ProstaPrime' OR slug = 'prostaprime'");
    $stmt->execute();
    $product = $stmt->fetch();

    if (!$product) {
        echo "<p style='color: orange;'>ProstaPrime not found in database.</p>";
    } else {
        $productId = $product['id'];
        echo "<h2>Deleting ProstaPrime (ID: $productId)</h2>";

        // Delete orders linked to this product
        $stmt = $conn->prepare("DELETE FROM orders WHERE tracked_product_id = :id");
        $stmt->execute([':id' => $productId]);
        $deletedOrders = $stmt->rowCount();
        echo "<p>Deleted $deletedOrders orders</p>";

        // Delete recurring charges
        $stmt = $conn->prepare("DELETE FROM recurring_charges WHERE tracked_product_id = :id");
        $stmt->execute([':id' => $productId]);
        $deletedRecurring = $stmt->rowCount();
        echo "<p>Deleted $deletedRecurring recurring charges</p>";

        // Delete refunds
        $stmt = $conn->prepare("DELETE FROM refunds WHERE tracked_product_id = :id");
        $stmt->execute([':id' => $productId]);
        $deletedRefunds = $stmt->rowCount();
        echo "<p>Deleted $deletedRefunds refunds</p>";

        // Delete chargebacks
        $stmt = $conn->prepare("DELETE FROM chargebacks WHERE tracked_product_id = :id");
        $stmt->execute([':id' => $productId]);
        $deletedChargebacks = $stmt->rowCount();
        echo "<p>Deleted $deletedChargebacks chargebacks</p>";

        // Delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        echo "<p style='color: green; font-weight: bold;'>ProstaPrime product deleted!</p>";
    }

    echo "<hr>";
    echo "<p style='color: red; font-weight: bold;'>DELETE THIS FILE NOW!</p>";
    echo "<p><a href='admin.html'>Go to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
