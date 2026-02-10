<?php
/**
 * BuyGoods Analytics - CSV Import Script
 * Imports historical orders from BuyGoods CSV export
 */

require_once 'config.php';
require_once 'database.php';

// Set execution time limit for large files
set_time_limit(300);

$db = Database::getInstance();
$conn = $db->getConnection();

$imported = 0;
$skipped = 0;
$errors = [];
$showForm = true;
$csvFile = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    if ($_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {
        $csvFile = $_FILES['csvfile']['tmp_name'];
        $showForm = false;
    } else {
        $errors[] = "File upload error: " . $_FILES['csvfile']['error'];
    }
}

// Also support direct file path for local testing
if (isset($_GET['file']) && file_exists($_GET['file'])) {
    $csvFile = $_GET['file'];
    $showForm = false;
}

if ($showForm) {
    // Show upload form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import CSV - BuyGoods Analytics</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
            .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #1e3a5f; margin-bottom: 10px; }
            p { color: #64748b; }
            .upload-area { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; margin: 20px 0; transition: all 0.2s; }
            .upload-area:hover { border-color: #4f46e5; background: #f8fafc; }
            .upload-area input[type="file"] { display: none; }
            .upload-area label { cursor: pointer; display: block; }
            .upload-icon { font-size: 3rem; margin-bottom: 10px; }
            .btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
            .btn:hover { background: #4338ca; }
            .back-link { display: inline-block; margin-top: 20px; color: #4f46e5; text-decoration: none; }
            .info { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 15px; margin-top: 20px; font-size: 0.9rem; }
            .info h4 { color: #0369a1; margin-bottom: 10px; }
            .info ul { margin: 0; padding-left: 20px; color: #0c4a6e; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Import Historical Orders</h1>
            <p>Upload a CSV export from BuyGoods to import historical order data.</p>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('csvfile').click()">
                    <label for="csvfile">
                        <div class="upload-icon">📁</div>
                        <strong>Click to select CSV file</strong>
                        <p style="margin-top:5px;font-size:0.9rem;">or drag and drop here</p>
                    </label>
                    <input type="file" name="csvfile" id="csvfile" accept=".csv" onchange="this.form.submit()">
                </div>
                <button type="submit" class="btn" style="width:100%;">Import Orders</button>
            </form>

            <div class="info">
                <h4>Expected CSV Columns:</h4>
                <ul>
                    <li>Order ID, Customer Name, Customer Email Address</li>
                    <li>Product Names, Total collected (Transaction Amount)</li>
                    <li>Affiliate ID, Affiliate Name, Affiliate Commission Amount</li>
                    <li>Address, City, State, Country, Zip</li>
                    <li>Date Created, Status, Payment Method</li>
                </ul>
            </div>

            <a href="index.html" class="back-link">← Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!$csvFile || !file_exists($csvFile)) {
    die("Error: CSV file not found");
}

// Read and parse CSV
$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Error: Could not open CSV file");
}

// Get header row
$headers = fgetcsv($handle);
if (!$headers) {
    die("Error: Could not read CSV headers");
}

// Clean headers (remove BOM if present)
$headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
$headers = array_map('trim', $headers);

// Map CSV columns to database fields
$columnMap = [
    'Order ID' => 'order_id',
    'External Order ID' => 'transaction_id',
    'Product Names' => 'product_name',
    'Product Codenames' => 'product_id',
    'Total collected (Transaction Amount)' => 'product_price',
    'Customer Email Address' => 'customer_email',
    'Customer Name' => 'customer_name',
    'Customer Phone' => 'customer_phone',
    'Country' => 'customer_country',
    'State' => 'customer_state',
    'City' => 'customer_city',
    'Address' => 'customer_address',
    'Zip' => 'customer_zip',
    'Affiliate ID' => 'affiliate_id',
    'Affiliate Name' => 'affiliate_name',
    'Affiliate Commission Amount' => 'commission',
    'Payment Method' => 'payment_method',
    'Status' => 'status',
    'IP Address' => 'ip_address',
    'Date Created' => 'created_at',
    'Is Test' => 'is_test'
];

// Find column indices
$columnIndices = [];
foreach ($columnMap as $csvCol => $dbCol) {
    $index = array_search($csvCol, $headers);
    if ($index !== false) {
        $columnIndices[$dbCol] = $index;
    }
}

$imported = 0;
$skipped = 0;
$errors = [];
$existingOrders = [];

// Get existing order IDs to avoid duplicates
$stmt = $conn->query("SELECT order_id FROM orders");
while ($row = $stmt->fetch()) {
    $existingOrders[$row['order_id']] = true;
}

// Process each row
$rowNum = 1;
while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;

    // Skip if row is empty
    if (empty(array_filter($row))) {
        continue;
    }

    // Get order ID
    $orderId = isset($columnIndices['order_id']) ? trim($row[$columnIndices['order_id']] ?? '') : '';

    if (empty($orderId)) {
        $skipped++;
        continue;
    }

    // Skip if order already exists
    if (isset($existingOrders[$orderId])) {
        $skipped++;
        continue;
    }

    // Skip test orders
    $isTest = isset($columnIndices['is_test']) ? trim($row[$columnIndices['is_test']] ?? '0') : '0';
    if ($isTest === '1') {
        $skipped++;
        continue;
    }

    // Parse price (remove $ and commas)
    $priceStr = isset($columnIndices['product_price']) ? $row[$columnIndices['product_price']] ?? '0' : '0';
    $price = floatval(preg_replace('/[^0-9.]/', '', $priceStr));

    // Parse commission
    $commissionStr = isset($columnIndices['commission']) ? $row[$columnIndices['commission']] ?? '0' : '0';
    $commission = floatval(preg_replace('/[^0-9.]/', '', $commissionStr));

    // Map status
    $statusRaw = isset($columnIndices['status']) ? strtolower(trim($row[$columnIndices['status']] ?? '')) : '';
    $status = 'completed';
    if (strpos($statusRaw, 'refund') !== false) $status = 'refunded';
    elseif (strpos($statusRaw, 'cancel') !== false) $status = 'cancelled';
    elseif (strpos($statusRaw, 'chargeback') !== false) $status = 'chargeback';

    // Build order data
    $orderData = [
        'order_id' => $orderId,
        'transaction_id' => isset($columnIndices['transaction_id']) ? trim($row[$columnIndices['transaction_id']] ?? '') : $orderId,
        'product_id' => isset($columnIndices['product_id']) ? trim($row[$columnIndices['product_id']] ?? '') : '',
        'product_name' => isset($columnIndices['product_name']) ? trim($row[$columnIndices['product_name']] ?? '') : '',
        'product_price' => $price,
        'quantity' => 1,
        'customer_email' => isset($columnIndices['customer_email']) ? trim($row[$columnIndices['customer_email']] ?? '') : '',
        'customer_name' => isset($columnIndices['customer_name']) ? trim($row[$columnIndices['customer_name']] ?? '') : '',
        'customer_phone' => isset($columnIndices['customer_phone']) ? trim($row[$columnIndices['customer_phone']] ?? '') : '',
        'customer_country' => isset($columnIndices['customer_country']) ? trim($row[$columnIndices['customer_country']] ?? '') : '',
        'customer_state' => isset($columnIndices['customer_state']) ? trim($row[$columnIndices['customer_state']] ?? '') : '',
        'customer_city' => isset($columnIndices['customer_city']) ? trim($row[$columnIndices['customer_city']] ?? '') : '',
        'customer_address' => isset($columnIndices['customer_address']) ? trim($row[$columnIndices['customer_address']] ?? '') : '',
        'customer_zip' => isset($columnIndices['customer_zip']) ? trim($row[$columnIndices['customer_zip']] ?? '') : '',
        'affiliate_id' => isset($columnIndices['affiliate_id']) ? trim($row[$columnIndices['affiliate_id']] ?? '') : '',
        'affiliate_name' => isset($columnIndices['affiliate_name']) ? trim($row[$columnIndices['affiliate_name']] ?? '') : '',
        'commission' => $commission,
        'payment_method' => isset($columnIndices['payment_method']) ? trim($row[$columnIndices['payment_method']] ?? 'card') : 'card',
        'currency' => 'USD',
        'status' => $status,
        'ip_address' => isset($columnIndices['ip_address']) ? trim($row[$columnIndices['ip_address']] ?? '') : '',
        'raw_data' => json_encode($row)
    ];

    // Get created_at date
    $createdAt = isset($columnIndices['created_at']) ? trim($row[$columnIndices['created_at']] ?? '') : '';

    try {
        // Insert order with custom created_at
        $sql = "INSERT INTO orders (
            order_id, transaction_id, product_id, product_name, product_price, quantity,
            customer_email, customer_name, customer_phone, customer_country, customer_state,
            customer_city, customer_address, customer_zip, affiliate_id, affiliate_name,
            commission, payment_method, currency, status, ip_address, raw_data, created_at
        ) VALUES (
            :order_id, :transaction_id, :product_id, :product_name, :product_price, :quantity,
            :customer_email, :customer_name, :customer_phone, :customer_country, :customer_state,
            :customer_city, :customer_address, :customer_zip, :affiliate_id, :affiliate_name,
            :commission, :payment_method, :currency, :status, :ip_address, :raw_data, :created_at
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':order_id' => $orderData['order_id'],
            ':transaction_id' => $orderData['transaction_id'] ?: $orderData['order_id'],
            ':product_id' => $orderData['product_id'],
            ':product_name' => $orderData['product_name'],
            ':product_price' => $orderData['product_price'],
            ':quantity' => $orderData['quantity'],
            ':customer_email' => $orderData['customer_email'],
            ':customer_name' => $orderData['customer_name'],
            ':customer_phone' => $orderData['customer_phone'],
            ':customer_country' => $orderData['customer_country'],
            ':customer_state' => $orderData['customer_state'],
            ':customer_city' => $orderData['customer_city'],
            ':customer_address' => $orderData['customer_address'],
            ':customer_zip' => $orderData['customer_zip'],
            ':affiliate_id' => $orderData['affiliate_id'],
            ':affiliate_name' => $orderData['affiliate_name'],
            ':commission' => $orderData['commission'],
            ':payment_method' => $orderData['payment_method'],
            ':currency' => $orderData['currency'],
            ':status' => $orderData['status'],
            ':ip_address' => $orderData['ip_address'],
            ':raw_data' => $orderData['raw_data'],
            ':created_at' => $createdAt ?: date('Y-m-d H:i:s')
        ]);

        $imported++;
        $existingOrders[$orderId] = true;

    } catch (Exception $e) {
        $errors[] = "Row $rowNum (Order: $orderId): " . $e->getMessage();
    }
}

fclose($handle);

// Output results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSV Import Results</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e3a5f; margin-bottom: 10px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f8fafc; border-radius: 10px; }
        .stat-value { font-size: 2.5rem; font-weight: 700; }
        .stat-label { color: #64748b; font-size: 0.9rem; margin-top: 5px; }
        .stat.green .stat-value { color: #10b981; }
        .stat.yellow .stat-value { color: #f59e0b; }
        .stat.red .stat-value { color: #ef4444; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-top: 20px; max-height: 200px; overflow-y: auto; }
        .errors h3 { color: #dc2626; margin-bottom: 10px; }
        .errors ul { margin: 0; padding-left: 20px; font-size: 0.85rem; color: #991b1b; }
        .success { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin-top: 20px; text-align: center; }
        .success h3 { color: #16a34a; margin-bottom: 5px; }
        a.btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        a.btn:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="card">
        <h1>CSV Import Complete</h1>
        <p>File: <?= htmlspecialchars(basename($csvFile)) ?></p>

        <div class="stats">
            <div class="stat green">
                <div class="stat-value"><?= $imported ?></div>
                <div class="stat-label">Orders Imported</div>
            </div>
            <div class="stat yellow">
                <div class="stat-value"><?= $skipped ?></div>
                <div class="stat-label">Skipped (Duplicates/Tests)</div>
            </div>
            <div class="stat red">
                <div class="stat-value"><?= count($errors) ?></div>
                <div class="stat-label">Errors</div>
            </div>
        </div>

        <?php if (empty($errors)): ?>
            <div class="success">
                <h3>Import Successful!</h3>
                <p>All orders have been imported without errors.</p>
            </div>
        <?php else: ?>
            <div class="errors">
                <h3>Import Errors</h3>
                <ul>
                    <?php foreach (array_slice($errors, 0, 20) as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($errors) > 20): ?>
                        <li>... and <?= count($errors) - 20 ?> more errors</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <a href="index.html" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
