<?php
/**
 * BuyGoods Analytics - Admin API
 * Product management endpoints
 *
 * Actions: get_products, create_product, update_product, delete_product, get_webhook_url
 */

require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

// Get action from URL or POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// For POST requests, get JSON body
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['action'])) {
    $action = $input['action'];
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    switch ($action) {
        case 'get_products':
            getProducts($conn);
            break;

        case 'create_product':
            createProduct($conn, $input);
            break;

        case 'update_product':
            updateProduct($conn, $input);
            break;

        case 'delete_product':
            deleteProduct($conn, $input);
            break;

        case 'toggle_status':
            toggleStatus($conn, $input);
            break;

        case 'get_webhook_urls':
            getWebhookUrls($conn, $_GET['product_id'] ?? $input['product_id'] ?? null);
            break;

        case 'import_order':
            importOrder($conn, $input);
            break;

        // ==================== PRICING ENDPOINTS ====================
        case 'get_pricing':
            getPricing($conn);
            break;

        case 'get_pricing_by_id':
            getPricingById($conn, $_GET['id'] ?? $input['id'] ?? null);
            break;

        case 'create_pricing':
            createPricing($conn, $input);
            break;

        case 'update_pricing':
            updatePricing($conn, $input);
            break;

        case 'delete_pricing':
            deletePricing($conn, $input);
            break;

        case 'toggle_pricing_status':
            togglePricingStatus($conn, $input);
            break;

        case 'get_base_price':
            getBasePrice($conn, $_GET['sku'] ?? $input['sku'] ?? null, $_GET['date'] ?? $input['date'] ?? null);
            break;

        // ==================== WITHDRAWALS ENDPOINTS ====================
        case 'get_withdrawals':
            getWithdrawals($conn);
            break;

        case 'add_withdrawal':
            addWithdrawal($conn, $input);
            break;

        case 'delete_withdrawal':
            deleteWithdrawal($conn, $_GET['id'] ?? $input['id'] ?? null);
            break;

        // ==================== CSV IMPORT ENDPOINT ====================
        case 'import_csv_orders':
            importCsvOrders($conn);
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

// ==================== PRODUCT HANDLERS ====================

function getProducts($conn) {
    $sql = "SELECT p.*,
            (SELECT COUNT(*) FROM orders WHERE tracked_product_id = p.id) as order_count,
            (SELECT COALESCE(SUM(product_price * quantity), 0) FROM orders WHERE tracked_product_id = p.id AND status = 'completed') as total_revenue
            FROM products p
            ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $products]);
}

function createProduct($conn, $input) {
    $name = trim($input['name'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product name is required']);
        return;
    }

    // Generate slug from name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');

    // Check if slug exists, append number if needed
    $baseSlug = $slug;
    $counter = 1;
    while (slugExists($conn, $slug)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    // Generate unique webhook token
    $webhookToken = bin2hex(random_bytes(32));

    $sql = "INSERT INTO products (name, slug, webhook_token, status)
            VALUES (:name, :slug, :webhook_token, 'active')";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':slug' => $slug,
        ':webhook_token' => $webhookToken
    ]);

    $productId = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Product created successfully',
        'data' => [
            'id' => $productId,
            'name' => $name,
            'slug' => $slug,
            'webhook_token' => $webhookToken
        ]
    ]);
}

function updateProduct($conn, $input) {
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product name is required']);
        return;
    }

    $sql = "UPDATE products SET name = :name WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':id' => $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
}

function deleteProduct($conn, $input) {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    // Check if product has orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE tracked_product_id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete product with existing orders. Deactivate it instead.'
        ]);
        return;
    }

    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
}

function toggleStatus($conn, $input) {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $sql = "UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    // Get new status
    $stmt = $conn->prepare("SELECT status FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated',
        'new_status' => $result['status']
    ]);
}

function getWebhookUrls($conn, $productId) {
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT slug, webhook_token FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        return;
    }

    // Build base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . '://' . $host . $path;

    $token = $product['webhook_token'];

    $urls = [
        'new_order' => $baseUrl . '/webhook.php?type=new-order&token=' . $token,
        'recurring' => $baseUrl . '/webhook.php?type=recurring&token=' . $token,
        'refund' => $baseUrl . '/webhook.php?type=refund&token=' . $token,
        'cancel' => $baseUrl . '/webhook.php?type=cancel&token=' . $token,
        'chargeback' => $baseUrl . '/webhook.php?type=chargeback&token=' . $token,
        'fulfilled' => $baseUrl . '/webhook.php?type=fulfilled&token=' . $token
    ];

    echo json_encode(['success' => true, 'data' => $urls]);
}

function slugExists($conn, $slug) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $result = $stmt->fetch();
    return $result['count'] > 0;
}

function importOrder($conn, $input) {
    $productId = intval($input['product_id'] ?? 0);
    $orderData = $input['order_data'] ?? [];

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Product ID required']);
        return;
    }

    // Map BuyGoods CSV columns to our database (BuyGoods uses specific column names)
    $orderId = $orderData['Order ID'] ?? $orderData['order_id'] ?? null;
    $transactionId = $orderData['Transaction ID'] ?? $orderData['transaction_id'] ?? $orderId;

    // Product info - BuyGoods uses "Product Names"
    $productName = $orderData['Product Names'] ?? $orderData['Product Name'] ?? $orderData['product_name'] ?? null;

    // Amount - BuyGoods uses "Total collected (Transaction Amount)"
    $amountStr = $orderData['Total collected (Transaction Amount)'] ?? $orderData['Amount'] ?? $orderData['Product Price'] ?? '0';
    $productPrice = floatval(str_replace(['$', ','], '', $amountStr));

    $quantity = intval($orderData['Quantity'] ?? $orderData['quantity'] ?? 1);

    // Customer email - BuyGoods uses "Customer Email Address"
    $customerEmail = $orderData['Customer Email Address'] ?? $orderData['Customer Email'] ?? $orderData['Email'] ?? null;

    // Customer name
    $customerName = $orderData['Customer Name'] ?? $orderData['customer_name'] ?? null;
    if (empty($customerName)) {
        $customerName = trim(($orderData['First Name'] ?? '') . ' ' . ($orderData['Last Name'] ?? ''));
    }

    // Customer phone
    $customerPhone = $orderData['Customer Phone'] ?? $orderData['Phone'] ?? null;

    // Customer address details
    $customerCountry = $orderData['Country'] ?? $orderData['country'] ?? null;
    $customerState = $orderData['State'] ?? $orderData['state'] ?? null;
    $customerCity = $orderData['City'] ?? $orderData['city'] ?? null;
    $customerAddress = $orderData['Address'] ?? $orderData['address'] ?? null;
    $customerZip = $orderData['Zip'] ?? $orderData['zip'] ?? null;

    // Affiliate info - BuyGoods uses both ID and Name
    $affiliateId = $orderData['Affiliate ID'] ?? $orderData['affiliate_id'] ?? null;
    $affiliateName = $orderData['Affiliate Name'] ?? $orderData['affiliate_name'] ?? null;

    // IP Address
    $ipAddress = $orderData['IP Address'] ?? $orderData['ip_address'] ?? null;

    // Payment Method - BuyGoods uses "Payment Method"
    $paymentMethod = $orderData['Payment Method'] ?? $orderData['payment_method'] ?? 'card';
    // Normalize payment method display
    if (strtolower($paymentMethod) === 'creditcard') {
        $paymentMethod = 'Credit Card';
    }

    // Currency - default to USD
    $currency = $orderData['Currency'] ?? 'USD';

    // Date - BuyGoods uses "Date Created"
    $dateCreated = $orderData['Date Created'] ?? $orderData['date_created'] ?? $orderData['Created'] ?? null;
    if ($dateCreated) {
        // Parse the date and convert to MySQL format
        $dateCreated = date('Y-m-d H:i:s', strtotime($dateCreated));
    }

    // Status
    $status = strtolower($orderData['Status'] ?? $orderData['status'] ?? 'completed');

    // Normalize status
    if (in_array($status, ['paid', 'complete', 'success'])) {
        $status = 'completed';
    }

    // Skip if no order ID
    if (empty($orderId)) {
        echo json_encode(['success' => false, 'error' => 'No order ID found']);
        return;
    }

    $sql = "INSERT INTO orders (
        tracked_product_id, order_id, transaction_id, product_name, product_price,
        quantity, customer_email, customer_name, customer_phone,
        customer_country, customer_state, customer_city, customer_address, customer_zip,
        affiliate_id, affiliate_name, ip_address, payment_method, currency, status, created_at
    ) VALUES (
        :tracked_product_id, :order_id, :transaction_id, :product_name, :product_price,
        :quantity, :customer_email, :customer_name, :customer_phone,
        :customer_country, :customer_state, :customer_city, :customer_address, :customer_zip,
        :affiliate_id, :affiliate_name, :ip_address, :payment_method, :currency, :status, :created_at
    ) ON DUPLICATE KEY UPDATE
        tracked_product_id = COALESCE(VALUES(tracked_product_id), tracked_product_id),
        product_name = COALESCE(VALUES(product_name), product_name),
        product_price = COALESCE(VALUES(product_price), product_price),
        customer_email = COALESCE(VALUES(customer_email), customer_email),
        customer_name = COALESCE(VALUES(customer_name), customer_name),
        customer_phone = COALESCE(VALUES(customer_phone), customer_phone),
        customer_country = COALESCE(VALUES(customer_country), customer_country),
        customer_state = COALESCE(VALUES(customer_state), customer_state),
        customer_city = COALESCE(VALUES(customer_city), customer_city),
        customer_address = COALESCE(VALUES(customer_address), customer_address),
        affiliate_id = COALESCE(VALUES(affiliate_id), affiliate_id),
        affiliate_name = COALESCE(VALUES(affiliate_name), affiliate_name),
        ip_address = COALESCE(VALUES(ip_address), ip_address),
        payment_method = COALESCE(VALUES(payment_method), payment_method),
        currency = COALESCE(VALUES(currency), currency),
        created_at = COALESCE(VALUES(created_at), created_at),
        status = VALUES(status)";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':tracked_product_id' => $productId,
            ':order_id' => $orderId,
            ':transaction_id' => $transactionId,
            ':product_name' => $productName,
            ':product_price' => $productPrice,
            ':quantity' => $quantity,
            ':customer_email' => $customerEmail,
            ':customer_name' => $customerName,
            ':customer_phone' => $customerPhone,
            ':customer_country' => $customerCountry,
            ':customer_state' => $customerState,
            ':customer_city' => $customerCity,
            ':customer_address' => $customerAddress,
            ':customer_zip' => $customerZip,
            ':affiliate_id' => $affiliateId,
            ':affiliate_name' => $affiliateName,
            ':ip_address' => $ipAddress,
            ':payment_method' => $paymentMethod,
            ':currency' => $currency,
            ':status' => $status,
            ':created_at' => $dateCreated
        ]);

        echo json_encode(['success' => true, 'order_id' => $orderId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==================== PRICING HANDLERS ====================

function getPricing($conn) {
    $sql = "SELECT * FROM product_pricing ORDER BY product_type, is_upsell, bottle_count, date_from DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pricing = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $pricing]);
}

function getPricingById($conn, $id) {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pricing ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM product_pricing WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $pricing = $stmt->fetch();

    if (!$pricing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pricing not found']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $pricing]);
}

function createPricing($conn, $input) {
    $required = ['product_type', 'sku_pattern', 'product_name', 'bottle_count', 'base_price'];
    foreach ($required as $field) {
        if (empty($input[$field]) && $input[$field] !== 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
            return;
        }
    }

    $sql = "INSERT INTO product_pricing
            (product_type, sku_pattern, product_name, bottle_count, is_upsell, is_subscription, date_from, date_to, base_price, recurring_price, shipping, notes, is_active)
            VALUES (:product_type, :sku_pattern, :product_name, :bottle_count, :is_upsell, :is_subscription, :date_from, :date_to, :base_price, :recurring_price, :shipping, :notes, :is_active)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':product_type' => $input['product_type'],
        ':sku_pattern' => $input['sku_pattern'],
        ':product_name' => $input['product_name'],
        ':bottle_count' => intval($input['bottle_count']),
        ':is_upsell' => intval($input['is_upsell'] ?? 0),
        ':is_subscription' => intval($input['is_subscription'] ?? 0),
        ':date_from' => $input['date_from'] ?: null,
        ':date_to' => $input['date_to'] ?: null,
        ':base_price' => floatval($input['base_price']),
        ':recurring_price' => isset($input['recurring_price']) && $input['recurring_price'] !== '' ? floatval($input['recurring_price']) : null,
        ':shipping' => floatval($input['shipping'] ?? 0),
        ':notes' => $input['notes'] ?? '',
        ':is_active' => intval($input['is_active'] ?? 1)
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Pricing created successfully',
        'id' => $conn->lastInsertId()
    ]);
}

function updatePricing($conn, $input) {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pricing ID is required']);
        return;
    }

    $sql = "UPDATE product_pricing SET
            product_type = :product_type,
            sku_pattern = :sku_pattern,
            product_name = :product_name,
            bottle_count = :bottle_count,
            is_upsell = :is_upsell,
            is_subscription = :is_subscription,
            date_from = :date_from,
            date_to = :date_to,
            base_price = :base_price,
            recurring_price = :recurring_price,
            shipping = :shipping,
            notes = :notes,
            is_active = :is_active
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':product_type' => $input['product_type'],
        ':sku_pattern' => $input['sku_pattern'],
        ':product_name' => $input['product_name'],
        ':bottle_count' => intval($input['bottle_count']),
        ':is_upsell' => intval($input['is_upsell'] ?? 0),
        ':is_subscription' => intval($input['is_subscription'] ?? 0),
        ':date_from' => $input['date_from'] ?: null,
        ':date_to' => $input['date_to'] ?: null,
        ':base_price' => floatval($input['base_price']),
        ':recurring_price' => isset($input['recurring_price']) && $input['recurring_price'] !== '' ? floatval($input['recurring_price']) : null,
        ':shipping' => floatval($input['shipping'] ?? 0),
        ':notes' => $input['notes'] ?? '',
        ':is_active' => intval($input['is_active'] ?? 1)
    ]);

    echo json_encode(['success' => true, 'message' => 'Pricing updated successfully']);
}

function deletePricing($conn, $input) {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pricing ID is required']);
        return;
    }

    $sql = "DELETE FROM product_pricing WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Pricing deleted successfully']);
}

function togglePricingStatus($conn, $input) {
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pricing ID is required']);
        return;
    }

    $sql = "UPDATE product_pricing SET is_active = NOT is_active WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    // Get new status
    $stmt = $conn->prepare("SELECT is_active FROM product_pricing WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated',
        'is_active' => (bool)$result['is_active']
    ]);
}

function getBasePrice($conn, $sku, $date = null) {
    if (!$sku) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'SKU is required']);
        return;
    }

    $date = $date ?: date('Y-m-d');

    // Normalize SKU pattern - extract base pattern from full SKU
    // e.g., "metatrim_3" -> "met3", "prostaprime_6" -> "pro6"
    $skuPattern = normalizeSkuPattern($sku);

    // Find matching price based on SKU pattern and date
    $sql = "SELECT * FROM product_pricing
            WHERE sku_pattern = :sku
            AND is_active = 1
            AND (date_from IS NULL OR date_from <= :date)
            AND (date_to IS NULL OR date_to >= :date)
            ORDER BY date_from DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':sku' => $skuPattern, ':date' => $date]);
    $pricing = $stmt->fetch();

    if (!$pricing) {
        // Try without date constraints as fallback
        $sql = "SELECT * FROM product_pricing WHERE sku_pattern = :sku AND is_active = 1 LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sku' => $skuPattern]);
        $pricing = $stmt->fetch();
    }

    if (!$pricing) {
        echo json_encode(['success' => false, 'error' => 'No pricing found for SKU: ' . $sku . ' (pattern: ' . $skuPattern . ')']);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'base_price' => floatval($pricing['base_price']),
            'shipping' => floatval($pricing['shipping']),
            'product_name' => $pricing['product_name'],
            'sku_pattern' => $pricing['sku_pattern']
        ]
    ]);
}

function normalizeSkuPattern($sku) {
    // Handle various SKU formats and normalize to our pattern
    $sku = strtolower(trim($sku));

    // MetaTrim patterns
    if (preg_match('/metatrim_?(\d+)/', $sku, $matches)) {
        return 'met' . $matches[1];
    }
    if (preg_match('/metatrimupsell_?(\d+)/', $sku, $matches)) {
        return 'met' . $matches[1] . 'u';
    }
    if (preg_match('/^met(\d+)/', $sku, $matches)) {
        return 'met' . $matches[1];
    }
    if (preg_match('/^met(\d+)u/', $sku, $matches)) {
        return 'met' . $matches[1] . 'u';
    }

    // ProstaPrime patterns
    if (preg_match('/prostaprime_?(\d+)/', $sku, $matches)) {
        return 'pro' . $matches[1];
    }
    if (preg_match('/prostaprimeupsell_?(\d+)/', $sku, $matches)) {
        return 'pro' . $matches[1] . 'u';
    }
    if (preg_match('/^pro(\d+)/', $sku, $matches)) {
        return 'pro' . $matches[1];
    }
    if (preg_match('/^pro(\d+)u/', $sku, $matches)) {
        return 'pro' . $matches[1] . 'u';
    }

    // Return as-is if no pattern matched
    return $sku;
}

// ==================== WITHDRAWALS HANDLERS ====================

function getWithdrawals($conn) {
    $sql = "SELECT w.*, p.name as product_name
            FROM withdrawals w
            LEFT JOIN products p ON w.product_id = p.id
            ORDER BY w.withdrawal_date DESC, w.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $withdrawals = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $withdrawals]);
}

function addWithdrawal($conn, $input) {
    $productId = $input['product_id'] ?? null;
    $amount = floatval($input['amount'] ?? 0);
    $withdrawalDate = $input['withdrawal_date'] ?? date('Y-m-d');
    $note = trim($input['note'] ?? '');

    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
        return;
    }

    $sql = "INSERT INTO withdrawals (product_id, amount, withdrawal_date, note)
            VALUES (:product_id, :amount, :withdrawal_date, :note)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':product_id' => $productId ?: null,
        ':amount' => $amount,
        ':withdrawal_date' => $withdrawalDate,
        ':note' => $note
    ]);

    echo json_encode(['success' => true, 'message' => 'Withdrawal recorded successfully', 'id' => $conn->lastInsertId()]);
}

function deleteWithdrawal($conn, $id) {
    $id = intval($id);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Withdrawal ID is required']);
        return;
    }

    $sql = "DELETE FROM withdrawals WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Withdrawal deleted successfully']);
}

// ==================== CSV IMPORT HANDLER ====================

function importCsvOrders($conn) {
    try {
        // Check for file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = isset($_FILES['csv_file']) ? $_FILES['csv_file']['error'] : 'No file';
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No CSV file uploaded or upload error: ' . $uploadError]);
            return;
        }

        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product ID is required']);
            return;
        }

    // Verify product exists
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE id = :id");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();
    if (!$product) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        return;
    }

    // Read CSV file
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if (!$handle) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to read CSV file']);
        return;
    }

    // Read header row
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'CSV file is empty or invalid']);
        return;
    }

    // Map column names to indices
    $columnMap = [];
    foreach ($header as $index => $name) {
        $columnMap[trim($name)] = $index;
    }

    // Helper function to safely get column value
    $getCol = function($row, $colName) use ($columnMap) {
        if (!isset($columnMap[$colName])) return '';
        $idx = $columnMap[$colName];
        return isset($row[$idx]) ? trim($row[$idx]) : '';
    };

    // Required columns from BuyGoods export
    $requiredCols = ['Order ID', 'Customer Name', 'Customer Email Address', 'Status'];
    foreach ($requiredCols as $col) {
        if (!isset($columnMap[$col])) {
            fclose($handle);
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required column: $col"]);
            return;
        }
    }

    // Get existing order IDs to avoid duplicates
    $stmt = $conn->prepare("SELECT order_id FROM orders");
    $stmt->execute();
    $existingOrders = array_column($stmt->fetchAll(), 'order_id');
    $existingOrdersMap = array_flip($existingOrders);

    // Process CSV rows
    $totalInCsv = 0;
    $newOrders = 0;
    $existingCount = 0;
    $newOrderIds = [];
    $errors = [];
    $ordersToAnalyze = [];

    while (($row = fgetcsv($handle)) !== false) {
        $totalInCsv++;

        // Get order ID
        $orderId = trim($row[$columnMap['Order ID']] ?? '');
        if (empty($orderId)) {
            continue;
        }

        // Check if order exists
        if (isset($existingOrdersMap[$orderId])) {
            $existingCount++;
            continue;
        }

        // Parse row data using safe column getter
        $status = strtolower($getCol($row, 'Status'));
        if (!in_array($status, ['pending', 'completed', 'refunded', 'cancelled', 'chargeback', 'fulfilled'])) {
            $status = 'completed'; // Default to completed for BuyGoods orders
        }

        $orderData = [
            'order_id' => $orderId,
            'transaction_id' => $getCol($row, 'External Order ID') ?: null,
            'status' => $status,
            'customer_name' => $getCol($row, 'Customer Name'),
            'customer_email' => $getCol($row, 'Customer Email Address'),
            'customer_phone' => $getCol($row, 'Customer Phone'),
            'customer_address' => $getCol($row, 'Address'),
            'customer_city' => $getCol($row, 'City'),
            'customer_state' => $getCol($row, 'State'),
            'customer_zip' => $getCol($row, 'Zip'),
            'customer_country' => $getCol($row, 'Country'),
            'product_name' => $getCol($row, 'Product Names'),
            'product_price' => floatval(str_replace(['$', ','], '', $getCol($row, 'Total collected (Transaction Amount)') ?: '0')),
            'quantity' => 1,
            'sku_pattern' => $getCol($row, 'SKU'),
            'affiliate_id' => $getCol($row, 'Affiliate ID'),
            'affiliate_name' => $getCol($row, 'Affiliate Name'),
            'commission' => floatval(str_replace(['$', ','], '', $getCol($row, 'Affiliate Commission Amount') ?: '0')),
            'ip_address' => $getCol($row, 'IP Address'),
            'created_at' => $getCol($row, 'Date Created') ?: date('Y-m-d H:i:s'),
            'tracked_product_id' => $productId,
        ];

        // Insert order
        try {
            $sql = "INSERT INTO orders (
                order_id, transaction_id, status, customer_name, customer_email, customer_phone,
                customer_address, customer_city, customer_state, customer_zip, customer_country,
                product_name, product_price, quantity, sku_pattern, affiliate_id, affiliate_name, commission,
                ip_address, created_at, tracked_product_id
            ) VALUES (
                :order_id, :transaction_id, :status, :customer_name, :customer_email, :customer_phone,
                :customer_address, :customer_city, :customer_state, :customer_zip, :customer_country,
                :product_name, :product_price, :quantity, :sku_pattern, :affiliate_id, :affiliate_name, :commission,
                :ip_address, :created_at, :tracked_product_id
            )";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':order_id' => $orderData['order_id'],
                ':transaction_id' => $orderData['transaction_id'],
                ':status' => $orderData['status'],
                ':customer_name' => $orderData['customer_name'],
                ':customer_email' => $orderData['customer_email'],
                ':customer_phone' => $orderData['customer_phone'],
                ':customer_address' => $orderData['customer_address'],
                ':customer_city' => $orderData['customer_city'],
                ':customer_state' => $orderData['customer_state'],
                ':customer_zip' => $orderData['customer_zip'],
                ':customer_country' => $orderData['customer_country'],
                ':product_name' => $orderData['product_name'],
                ':product_price' => $orderData['product_price'],
                ':quantity' => $orderData['quantity'],
                ':sku_pattern' => $orderData['sku_pattern'],
                ':affiliate_id' => $orderData['affiliate_id'],
                ':affiliate_name' => $orderData['affiliate_name'],
                ':commission' => $orderData['commission'],
                ':ip_address' => $orderData['ip_address'],
                ':created_at' => $orderData['created_at'],
                ':tracked_product_id' => $orderData['tracked_product_id'],
            ]);

            $newOrders++;
            $newOrderIds[] = $orderId;

            // Track for IPQS analysis if IP exists
            if (!empty($orderData['ip_address'])) {
                $ordersToAnalyze[] = [
                    'order_id' => $orderId,
                    'ip_address' => $orderData['ip_address']
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Order $orderId: " . $e->getMessage();
        }
    }

    fclose($handle);

    // Run IPQS analysis on new orders (if configured)
    $ipqsAnalyzed = 0;
    if (count($ordersToAnalyze) > 0) {
        $ipqsAnalyzed = runIpqsOnOrders($conn, $ordersToAnalyze);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_in_csv' => $totalInCsv,
            'new_orders' => $newOrders,
            'existing_orders' => $existingCount,
            'ipqs_analyzed' => $ipqsAnalyzed,
            'new_order_ids' => $newOrderIds,
            'errors' => $errors
        ]
    ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}

// Helper function to run IPQS analysis on orders
function runIpqsOnOrders($conn, $orders) {
    // Check if IPQS is configured
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ipqs_api_key'");
    $stmt->execute();
    $apiKeyRow = $stmt->fetch();
    $apiKey = $apiKeyRow ? $apiKeyRow['setting_value'] : null;

    if (empty($apiKey)) {
        return 0; // IPQS not configured
    }

    $analyzed = 0;

    foreach ($orders as $order) {
        $ip = $order['ip_address'];
        $orderId = $order['order_id'];

        // Skip if already analyzed
        $stmt = $conn->prepare("SELECT ip_analyzed FROM orders WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $existing = $stmt->fetch();
        if ($existing && $existing['ip_analyzed']) {
            continue;
        }

        // Call IPQS API
        try {
            $url = "https://ipqualityscore.com/api/json/ip/{$apiKey}/{$ip}?strictness=1&allow_public_access_points=true";
            $response = @file_get_contents($url);

            if ($response === false) {
                continue;
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['success']) || !$data['success']) {
                continue;
            }

            // Update order with IPQS data
            $sql = "UPDATE orders SET
                ip_analyzed = 1,
                ip_fraud_score = :fraud_score,
                ip_proxy = :proxy,
                ip_vpn = :vpn,
                ip_tor = :tor,
                ip_bot = :bot,
                ip_recent_abuse = :recent_abuse,
                ip_country = :country,
                ip_city = :city,
                ip_isp = :isp,
                ip_mobile = :mobile,
                ip_raw_response = :raw_response
                WHERE order_id = :order_id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':fraud_score' => $data['fraud_score'] ?? 0,
                ':proxy' => ($data['proxy'] ?? false) ? 1 : 0,
                ':vpn' => ($data['vpn'] ?? false) ? 1 : 0,
                ':tor' => ($data['tor'] ?? false) ? 1 : 0,
                ':bot' => ($data['bot_status'] ?? false) ? 1 : 0,
                ':recent_abuse' => ($data['recent_abuse'] ?? false) ? 1 : 0,
                ':country' => $data['country_code'] ?? '',
                ':city' => $data['city'] ?? '',
                ':isp' => $data['ISP'] ?? '',
                ':mobile' => ($data['mobile'] ?? false) ? 1 : 0,
                ':raw_response' => json_encode($data),
                ':order_id' => $orderId
            ]);

            $analyzed++;

            // Small delay to respect API rate limits
            usleep(200000); // 200ms delay
        } catch (Exception $e) {
            // Continue to next order on error
            continue;
        }
    }

    return $analyzed;
}
