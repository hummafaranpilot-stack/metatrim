<?php
/**
 * Seed MetaTrim Product
 * Run once to add MetaTrim to the products table
 * DELETE THIS FILE AFTER RUNNING
 */

require_once 'config.php';
require_once 'database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if MetaTrim already exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = 'MetaTrim' OR slug = 'metatrim'");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        echo "<p style='color: orange;'>MetaTrim already exists with ID: " . $existing['id'] . "</p>";
    } else {
        // Generate unique webhook token
        $webhookToken = bin2hex(random_bytes(32));

        $sql = "INSERT INTO products (name, slug, webhook_token, status, created_at)
                VALUES ('MetaTrim', 'metatrim', :token, 'active', NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':token' => $webhookToken]);

        $productId = $conn->lastInsertId();

        echo "<h2 style='color: green;'>MetaTrim Added Successfully!</h2>";
        echo "<p><strong>Product ID:</strong> " . $productId . "</p>";
        echo "<p><strong>Webhook Token:</strong> " . $webhookToken . "</p>";
        echo "<hr>";
        echo "<h3>Webhook URLs:</h3>";

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

        echo "<ul>";
        echo "<li><strong>New Order:</strong> " . $baseUrl . "/webhook.php?type=new-order&token=" . $webhookToken . "</li>";
        echo "<li><strong>Recurring:</strong> " . $baseUrl . "/webhook.php?type=recurring&token=" . $webhookToken . "</li>";
        echo "<li><strong>Refund:</strong> " . $baseUrl . "/webhook.php?type=refund&token=" . $webhookToken . "</li>";
        echo "<li><strong>Chargeback:</strong> " . $baseUrl . "/webhook.php?type=chargeback&token=" . $webhookToken . "</li>";
        echo "<li><strong>Cancel:</strong> " . $baseUrl . "/webhook.php?type=cancel&token=" . $webhookToken . "</li>";
        echo "<li><strong>Fulfilled:</strong> " . $baseUrl . "/webhook.php?type=fulfilled&token=" . $webhookToken . "</li>";
        echo "</ul>";
    }

    // Also link existing orders to MetaTrim if they don't have a tracked_product_id
    $stmt = $conn->prepare("SELECT id FROM products WHERE slug = 'metatrim'");
    $stmt->execute();
    $metatrim = $stmt->fetch();

    if ($metatrim) {
        $updateSql = "UPDATE orders SET tracked_product_id = :product_id WHERE tracked_product_id IS NULL";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([':product_id' => $metatrim['id']]);
        $updatedRows = $stmt->rowCount();

        if ($updatedRows > 0) {
            echo "<p style='color: blue;'>Linked " . $updatedRows . " existing orders to MetaTrim.</p>";
        }

        // Also update recurring_charges
        $updateSql = "UPDATE recurring_charges SET tracked_product_id = :product_id WHERE tracked_product_id IS NULL";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([':product_id' => $metatrim['id']]);

        // Also update refunds
        $updateSql = "UPDATE refunds SET tracked_product_id = :product_id WHERE tracked_product_id IS NULL";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([':product_id' => $metatrim['id']]);

        // Also update chargebacks
        $updateSql = "UPDATE chargebacks SET tracked_product_id = :product_id WHERE tracked_product_id IS NULL";
        $stmt = $conn->prepare($updateSql);
        $stmt->execute([':product_id' => $metatrim['id']]);
    }

    echo "<hr>";
    echo "<p style='color: red; font-weight: bold;'>DELETE THIS FILE NOW! Go to admin.html to see MetaTrim.</p>";
    echo "<p><a href='admin.html'>Go to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
