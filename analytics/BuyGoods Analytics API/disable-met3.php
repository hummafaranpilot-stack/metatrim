<?php
/**
 * Quick script to disable met3 pricing entry
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("UPDATE product_pricing SET is_active = 0 WHERE sku_pattern = 'met3'");
    $stmt->execute();

    $affected = $stmt->rowCount();

    echo "Done! Disabled $affected met3 entry/entries.\n";
    echo "<p><a href='admin.html'>Back to Admin</a></p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
