<?php
/**
 * Migration: Add IP fraud analysis columns to orders table
 * Run this once to add the new fraud detection fields
 */

require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<h2>Adding Fraud Detection Columns</h2>";

    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'ip_fraud_score'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:orange'>Columns already exist. Migration skipped.</p>";
        echo "<p><a href='index.html'>Go to Dashboard</a></p>";
        exit;
    }

    // Add new columns
    $alterSQL = "ALTER TABLE orders
        ADD COLUMN ip_country VARCHAR(10) AFTER ip_address,
        ADD COLUMN ip_city VARCHAR(100) AFTER ip_country,
        ADD COLUMN ip_region VARCHAR(100) AFTER ip_city,
        ADD COLUMN ip_proxy BOOLEAN DEFAULT FALSE AFTER ip_region,
        ADD COLUMN ip_tor BOOLEAN DEFAULT FALSE AFTER ip_proxy,
        ADD COLUMN ip_fraud_score INT DEFAULT 0 AFTER ip_tor,
        ADD COLUMN ip_analyzed BOOLEAN DEFAULT FALSE AFTER ip_fraud_score,
        ADD INDEX idx_fraud_score (ip_fraud_score)";

    $pdo->exec($alterSQL);

    echo "<p style='color:green'>✓ Successfully added fraud detection columns:</p>";
    echo "<ul>";
    echo "<li>ip_country - Country code from IP</li>";
    echo "<li>ip_city - City from IP</li>";
    echo "<li>ip_region - Region/State from IP</li>";
    echo "<li>ip_proxy - Proxy detection flag</li>";
    echo "<li>ip_tor - TOR network detection flag</li>";
    echo "<li>ip_fraud_score - Risk score (0-100)</li>";
    echo "<li>ip_analyzed - Whether IP has been analyzed</li>";
    echo "</ul>";

    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Get your API key from <a href='https://www.ipqualityscore.com/create-account' target='_blank'>IPQualityScore</a> (free tier available)</li>";
    echo "<li>Update <code>config.php</code> with your API key</li>";
    echo "<li>New orders will automatically be analyzed</li>";
    echo "<li>Use <code>analyze-ips.php</code> to analyze existing orders</li>";
    echo "</ol>";

    echo "<p><a href='index.html' style='display:inline-block;padding:10px 20px;background:#4f46e5;color:white;text-decoration:none;border-radius:6px;margin-top:20px;'>Go to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
