<?php
/**
 * Setup Withdrawals Table
 * Run this file once to create the withdrawals table
 */

require_once 'config.php';
require_once 'database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Withdrawals Table</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        h1 { color: #f8fafc; margin-bottom: 10px; }
        .success { background: #065f46; border: 1px solid #10b981; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .success h3 { color: #6ee7b7; margin-bottom: 10px; }
        .error { background: #7f1d1d; border: 1px solid #ef4444; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .error h3 { color: #fca5a5; margin-bottom: 10px; }
        .btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Setup Withdrawals Table</h1>

        <?php
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Create withdrawals table
            $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NULL,
                amount DECIMAL(12, 2) NOT NULL,
                withdrawal_date DATE NOT NULL,
                note VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_id (product_id),
                INDEX idx_withdrawal_date (withdrawal_date)
            )";

            $conn->exec($sql);

            // Check if table was created
            $stmt = $conn->query("SHOW TABLES LIKE 'withdrawals'");
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                // Count existing records
                $stmt = $conn->query("SELECT COUNT(*) as count FROM withdrawals");
                $count = $stmt->fetch()['count'];

                echo '<div class="success">';
                echo '<h3>✅ Success!</h3>';
                echo '<p>The <code>withdrawals</code> table has been created successfully.</p>';
                echo '<p>Current records: <strong>' . $count . '</strong></p>';
                echo '</div>';
            }

        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ Error</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <p style="color: #94a3b8; margin-top: 20px;">
            You can now record withdrawals in the Admin panel under the "Withdrawals" section.
        </p>

        <a href="admin.html" class="btn">Go to Admin Panel</a>
        <a href="index.html" class="btn" style="background: #059669; margin-left: 10px;">Go to Dashboard</a>
    </div>
</body>
</html>
