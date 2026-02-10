<?php
/**
 * BuyGoods Analytics - Database Class
 */

require_once 'analytics-config.php';

class Database {
    private $conn;
    private static $instance = null;

    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // ==================== ORDER OPERATIONS ====================

    public function insertOrder($data) {
        $sql = "INSERT INTO orders (
            tracked_product_id, order_id, transaction_id, product_id, product_name, product_price,
            quantity, customer_email, customer_name, customer_phone,
            customer_country, customer_state, customer_city, customer_address,
            customer_zip, affiliate_id, affiliate_name, commission,
            payment_method, currency, status, ip_address,
            ip_country, ip_city, ip_region, ip_proxy, ip_tor, ip_fraud_score, ip_analyzed,
            base_price, taxes, processing_fee, allowance_hold, net_amount, sku_pattern, is_upsell,
            raw_data
        ) VALUES (
            :tracked_product_id, :order_id, :transaction_id, :product_id, :product_name, :product_price,
            :quantity, :customer_email, :customer_name, :customer_phone,
            :customer_country, :customer_state, :customer_city, :customer_address,
            :customer_zip, :affiliate_id, :affiliate_name, :commission,
            :payment_method, :currency, :status, :ip_address,
            :ip_country, :ip_city, :ip_region, :ip_proxy, :ip_tor, :ip_fraud_score, :ip_analyzed,
            :base_price, :taxes, :processing_fee, :allowance_hold, :net_amount, :sku_pattern, :is_upsell,
            :raw_data
        ) ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            tracked_product_id = COALESCE(VALUES(tracked_product_id), tracked_product_id),
            ip_country = COALESCE(VALUES(ip_country), ip_country),
            ip_city = COALESCE(VALUES(ip_city), ip_city),
            ip_region = COALESCE(VALUES(ip_region), ip_region),
            ip_proxy = COALESCE(VALUES(ip_proxy), ip_proxy),
            ip_tor = COALESCE(VALUES(ip_tor), ip_tor),
            ip_fraud_score = COALESCE(VALUES(ip_fraud_score), ip_fraud_score),
            ip_analyzed = COALESCE(VALUES(ip_analyzed), ip_analyzed),
            base_price = COALESCE(VALUES(base_price), base_price),
            taxes = COALESCE(VALUES(taxes), taxes),
            processing_fee = COALESCE(VALUES(processing_fee), processing_fee),
            allowance_hold = COALESCE(VALUES(allowance_hold), allowance_hold),
            net_amount = COALESCE(VALUES(net_amount), net_amount),
            sku_pattern = COALESCE(VALUES(sku_pattern), sku_pattern),
            is_upsell = COALESCE(VALUES(is_upsell), is_upsell),
            updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':tracked_product_id' => $data['tracked_product_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':product_id' => $data['product_id'] ?? null,
            ':product_name' => $data['product_name'] ?? null,
            ':product_price' => $data['product_price'] ?? 0,
            ':quantity' => $data['quantity'] ?? 1,
            ':customer_email' => $data['customer_email'] ?? null,
            ':customer_name' => $data['customer_name'] ?? null,
            ':customer_phone' => $data['customer_phone'] ?? null,
            ':customer_country' => $data['customer_country'] ?? null,
            ':customer_state' => $data['customer_state'] ?? null,
            ':customer_city' => $data['customer_city'] ?? null,
            ':customer_address' => $data['customer_address'] ?? null,
            ':customer_zip' => $data['customer_zip'] ?? null,
            ':affiliate_id' => $data['affiliate_id'] ?? null,
            ':affiliate_name' => $data['affiliate_name'] ?? null,
            ':commission' => $data['commission'] ?? 0,
            ':payment_method' => $data['payment_method'] ?? 'card',
            ':currency' => $data['currency'] ?? 'USD',
            ':status' => $data['status'] ?? 'completed',
            ':ip_address' => $data['ip_address'] ?? null,
            ':ip_country' => $data['ip_country'] ?? null,
            ':ip_city' => $data['ip_city'] ?? null,
            ':ip_region' => $data['ip_region'] ?? null,
            ':ip_proxy' => $data['ip_proxy'] ?? 0,
            ':ip_tor' => $data['ip_tor'] ?? 0,
            ':ip_fraud_score' => $data['ip_fraud_score'] ?? 0,
            ':ip_analyzed' => $data['ip_analyzed'] ?? 0,
            ':base_price' => $data['base_price'] ?? null,
            ':taxes' => $data['taxes'] ?? null,
            ':processing_fee' => $data['processing_fee'] ?? null,
            ':allowance_hold' => $data['allowance_hold'] ?? null,
            ':net_amount' => $data['net_amount'] ?? null,
            ':sku_pattern' => $data['sku_pattern'] ?? null,
            ':is_upsell' => $data['is_upsell'] ?? 0,
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);
    }

    public function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':status' => $status, ':order_id' => $orderId]);
    }

    public function getOrders($filters = []) {
        $sql = "SELECT o.*, p.name as tracked_product_name
                FROM orders o
                LEFT JOIN products p ON o.tracked_product_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['startDate'])) {
            $sql .= " AND o.created_at >= :startDate";
            $params[':startDate'] = $filters['startDate'];
        }

        if (!empty($filters['endDate'])) {
            $sql .= " AND o.created_at <= :endDate";
            $params[':endDate'] = $filters['endDate'];
        }

        if (!empty($filters['tracked_product_id'])) {
            $sql .= " AND o.tracked_product_id = :tracked_product_id";
            $params[':tracked_product_id'] = $filters['tracked_product_id'];
        }

        $sql .= " ORDER BY o.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==================== RECURRING CHARGE OPERATIONS ====================

    public function insertRecurringCharge($data) {
        $sql = "INSERT INTO recurring_charges (
            tracked_product_id, charge_id, order_id, transaction_id, product_id, product_name,
            amount, customer_email, customer_name, affiliate_id, currency, status, raw_data
        ) VALUES (
            :tracked_product_id, :charge_id, :order_id, :transaction_id, :product_id, :product_name,
            :amount, :customer_email, :customer_name, :affiliate_id, :currency, :status, :raw_data
        ) ON DUPLICATE KEY UPDATE status = VALUES(status), tracked_product_id = COALESCE(VALUES(tracked_product_id), tracked_product_id)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':tracked_product_id' => $data['tracked_product_id'] ?? null,
            ':charge_id' => $data['charge_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':product_id' => $data['product_id'] ?? null,
            ':product_name' => $data['product_name'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':customer_email' => $data['customer_email'] ?? null,
            ':customer_name' => $data['customer_name'] ?? null,
            ':affiliate_id' => $data['affiliate_id'] ?? null,
            ':currency' => $data['currency'] ?? 'USD',
            ':status' => $data['status'] ?? 'success',
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);
    }

    public function getRecurringCharges($filters = []) {
        $sql = "SELECT * FROM recurring_charges WHERE 1=1";
        $params = [];

        if (!empty($filters['startDate'])) {
            $sql .= " AND created_at >= :startDate";
            $params[':startDate'] = $filters['startDate'];
        }

        if (!empty($filters['endDate'])) {
            $sql .= " AND created_at <= :endDate";
            $params[':endDate'] = $filters['endDate'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==================== REFUND OPERATIONS ====================

    public function insertRefund($data) {
        $sql = "INSERT INTO refunds (
            tracked_product_id, refund_id, order_id, transaction_id, amount, reason, refund_type, raw_data
        ) VALUES (
            :tracked_product_id, :refund_id, :order_id, :transaction_id, :amount, :reason, :refund_type, :raw_data
        ) ON DUPLICATE KEY UPDATE amount = VALUES(amount), tracked_product_id = COALESCE(VALUES(tracked_product_id), tracked_product_id)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':tracked_product_id' => $data['tracked_product_id'] ?? null,
            ':refund_id' => $data['refund_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':reason' => $data['reason'] ?? null,
            ':refund_type' => $data['refund_type'] ?? 'full',
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);

        if ($result && !empty($data['order_id'])) {
            $this->updateOrderStatus($data['order_id'], 'refunded');
        }

        return $result;
    }

    public function getRefunds($filters = []) {
        $sql = "SELECT * FROM refunds WHERE 1=1";
        $params = [];

        if (!empty($filters['startDate'])) {
            $sql .= " AND created_at >= :startDate";
            $params[':startDate'] = $filters['startDate'];
        }

        if (!empty($filters['endDate'])) {
            $sql .= " AND created_at <= :endDate";
            $params[':endDate'] = $filters['endDate'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==================== CANCELLATION OPERATIONS ====================

    public function insertCancellation($data) {
        $sql = "INSERT INTO cancellations (
            cancel_id, order_id, reason, raw_data
        ) VALUES (
            :cancel_id, :order_id, :reason, :raw_data
        ) ON DUPLICATE KEY UPDATE reason = VALUES(reason)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':cancel_id' => $data['cancel_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':reason' => $data['reason'] ?? null,
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);

        if ($result && !empty($data['order_id'])) {
            $this->updateOrderStatus($data['order_id'], 'cancelled');
        }

        return $result;
    }

    // ==================== CHARGEBACK OPERATIONS ====================

    public function insertChargeback($data) {
        $sql = "INSERT INTO chargebacks (
            tracked_product_id, chargeback_id, order_id, transaction_id, amount, reason, raw_data
        ) VALUES (
            :tracked_product_id, :chargeback_id, :order_id, :transaction_id, :amount, :reason, :raw_data
        ) ON DUPLICATE KEY UPDATE amount = VALUES(amount), tracked_product_id = COALESCE(VALUES(tracked_product_id), tracked_product_id)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':tracked_product_id' => $data['tracked_product_id'] ?? null,
            ':chargeback_id' => $data['chargeback_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':reason' => $data['reason'] ?? null,
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);

        if ($result && !empty($data['order_id'])) {
            $this->updateOrderStatus($data['order_id'], 'chargeback');
        }

        return $result;
    }

    public function getChargebacks($filters = []) {
        $sql = "SELECT * FROM chargebacks WHERE 1=1";
        $params = [];

        if (!empty($filters['startDate'])) {
            $sql .= " AND created_at >= :startDate";
            $params[':startDate'] = $filters['startDate'];
        }

        if (!empty($filters['endDate'])) {
            $sql .= " AND created_at <= :endDate";
            $params[':endDate'] = $filters['endDate'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==================== FULFILLMENT OPERATIONS ====================

    public function insertFulfillment($data) {
        $sql = "INSERT INTO fulfillments (
            fulfillment_id, order_id, tracking_number, carrier, shipped_at, raw_data
        ) VALUES (
            :fulfillment_id, :order_id, :tracking_number, :carrier, :shipped_at, :raw_data
        ) ON DUPLICATE KEY UPDATE tracking_number = VALUES(tracking_number), carrier = VALUES(carrier)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':fulfillment_id' => $data['fulfillment_id'] ?? null,
            ':order_id' => $data['order_id'] ?? null,
            ':tracking_number' => $data['tracking_number'] ?? null,
            ':carrier' => $data['carrier'] ?? null,
            ':shipped_at' => $data['shipped_at'] ?? date('Y-m-d H:i:s'),
            ':raw_data' => json_encode($data['raw_data'] ?? [])
        ]);

        if ($result && !empty($data['order_id'])) {
            $this->updateOrderStatus($data['order_id'], 'fulfilled');
        }

        return $result;
    }

    // ==================== WEBHOOK LOG OPERATIONS ====================

    public function logWebhook($eventType, $payload, $ipAddress, $processed = false, $errorMessage = null) {
        $sql = "INSERT INTO webhook_logs (event_type, payload, ip_address, processed, error_message)
                VALUES (:event_type, :payload, :ip_address, :processed, :error_message)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':event_type' => $eventType,
            ':payload' => json_encode($payload),
            ':ip_address' => $ipAddress,
            ':processed' => $processed ? 1 : 0,
            ':error_message' => $errorMessage
        ]);
    }

    public function getWebhookLogs($filters = []) {
        $sql = "SELECT * FROM webhook_logs WHERE 1=1";
        $params = [];

        if (!empty($filters['eventType'])) {
            $sql .= " AND event_type = :eventType";
            $params[':eventType'] = $filters['eventType'];
        }

        $sql .= " ORDER BY created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==================== STATISTICS OPERATIONS ====================

    public function getDashboardStats($startDate = null, $endDate = null, $trackedProductId = null) {
        $dateFilter = "";
        $productFilter = "";
        $params = [];

        if ($startDate && $endDate) {
            $dateFilter = "AND created_at BETWEEN :startDate AND :endDate";
            $params[':startDate'] = $startDate;
            $params[':endDate'] = $endDate;
        }

        if ($trackedProductId) {
            $productFilter = "AND tracked_product_id = :tracked_product_id";
            $params[':tracked_product_id'] = $trackedProductId;
        }

        // Order stats
        $sql = "SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(product_price * quantity), 0) as total_revenue,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
            COUNT(CASE WHEN status = 'chargeback' THEN 1 END) as chargeback_orders,
            COUNT(CASE WHEN status = 'fulfilled' THEN 1 END) as fulfilled_orders
        FROM orders WHERE 1=1 $dateFilter $productFilter";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $orderStats = $stmt->fetch();

        // Refund stats
        $refundParams = [];
        $refundDateFilter = "";
        $refundProductFilter = "";
        if ($startDate && $endDate) {
            $refundDateFilter = "AND created_at BETWEEN :startDate AND :endDate";
            $refundParams[':startDate'] = $startDate;
            $refundParams[':endDate'] = $endDate;
        }
        if ($trackedProductId) {
            $refundProductFilter = "AND tracked_product_id = :tracked_product_id";
            $refundParams[':tracked_product_id'] = $trackedProductId;
        }
        $sql = "SELECT COUNT(*) as total_refunds, COALESCE(SUM(amount), 0) as refund_amount
                FROM refunds WHERE 1=1 $refundDateFilter $refundProductFilter";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($refundParams);
        $refundStats = $stmt->fetch();

        // Chargeback stats
        $sql = "SELECT COUNT(*) as total_chargebacks, COALESCE(SUM(amount), 0) as chargeback_amount
                FROM chargebacks WHERE 1=1 $refundDateFilter $refundProductFilter";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($refundParams);
        $chargebackStats = $stmt->fetch();

        // Recurring stats
        $sql = "SELECT COUNT(*) as total_recurring, COALESCE(SUM(amount), 0) as recurring_revenue
                FROM recurring_charges WHERE status = 'success' $refundDateFilter $refundProductFilter";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($refundParams);
        $recurringStats = $stmt->fetch();

        // Calculate net revenue
        $totalRevenue = floatval($orderStats['total_revenue']) + floatval($recurringStats['recurring_revenue']);
        $totalDeductions = floatval($refundStats['refund_amount']) + floatval($chargebackStats['chargeback_amount']);
        $netRevenue = $totalRevenue - $totalDeductions;

        return [
            'orders' => $orderStats,
            'refunds' => $refundStats,
            'chargebacks' => $chargebackStats,
            'recurring' => $recurringStats,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_deductions' => $totalDeductions,
                'net_revenue' => $netRevenue
            ]
        ];
    }

    // Get all products for dropdown
    public function getProducts() {
        $sql = "SELECT id, name, slug, status FROM products WHERE status = 'active' ORDER BY name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get product by webhook token
    public function getProductByToken($token) {
        $sql = "SELECT * FROM products WHERE webhook_token = :token AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    public function getTopProducts($limit = 10) {
        $sql = "SELECT
            product_id,
            product_name,
            COUNT(*) as total_orders,
            SUM(quantity) as total_quantity,
            SUM(product_price * quantity) as total_revenue
        FROM orders
        WHERE status NOT IN ('refunded', 'cancelled', 'chargeback')
        GROUP BY product_id, product_name
        ORDER BY total_revenue DESC
        LIMIT " . intval($limit);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentActivity($limit = 20) {
        $sql = "(SELECT 'order' as type, order_id as id, product_name as description, product_price as amount, created_at FROM orders ORDER BY created_at DESC LIMIT :limit)
        UNION ALL
        (SELECT 'refund' as type, refund_id as id, CONCAT('Refund for order ', order_id) as description, amount, created_at FROM refunds ORDER BY created_at DESC LIMIT :limit)
        UNION ALL
        (SELECT 'chargeback' as type, chargeback_id as id, CONCAT('Chargeback for order ', order_id) as description, amount, created_at FROM chargebacks ORDER BY created_at DESC LIMIT :limit)
        UNION ALL
        (SELECT 'recurring' as type, charge_id as id, product_name as description, amount, created_at FROM recurring_charges ORDER BY created_at DESC LIMIT :limit)
        ORDER BY created_at DESC
        LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', intval($limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ==================== PRICING OPERATIONS ====================

    /**
     * Normalize SKU pattern from BuyGoods format
     * e.g., "metatrim_3" -> "met3", "prostaprimeupsell_1" -> "pro1u"
     */
    public function normalizeSkuPattern($sku) {
        if (empty($sku)) return null;

        $sku = strtolower(trim($sku));

        // Map product prefixes
        $prefixMap = [
            'metatrim' => 'met',
            'prostaprime' => 'pro'
        ];

        // Check for upsell
        $isUpsell = (strpos($sku, 'upsell') !== false);

        // Extract product type and bottle count
        foreach ($prefixMap as $fullName => $shortName) {
            if (strpos($sku, $fullName) === 0) {
                // Extract number - look for _N pattern
                if (preg_match('/_(\d+)/', $sku, $matches)) {
                    $bottleCount = $matches[1];
                    return $shortName . $bottleCount . ($isUpsell ? 'u' : '');
                }
            }
        }

        return null;
    }

    /**
     * Get base price from product_pricing table
     * Returns total base price (base_price + shipping) for matching SKU and date
     */
    public function getBasePrice($skuPattern, $orderDate = null, $isUpsell = false) {
        if (empty($skuPattern)) return null;

        $orderDate = $orderDate ?? date('Y-m-d');

        // Build query to find matching pricing
        $sql = "SELECT base_price, shipping, bottle_count, is_upsell, is_subscription
                FROM product_pricing
                WHERE sku_pattern = :sku_pattern
                AND is_active = 1
                AND (date_from IS NULL OR date_from <= :order_date)
                AND (date_to IS NULL OR date_to >= :order_date)
                ORDER BY
                    CASE WHEN date_from IS NOT NULL AND date_to IS NOT NULL THEN 0
                         WHEN date_from IS NOT NULL THEN 1
                         WHEN date_to IS NOT NULL THEN 2
                         ELSE 3 END,
                    date_from DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sku_pattern' => $skuPattern,
            ':order_date' => $orderDate
        ]);

        $result = $stmt->fetch();

        if ($result) {
            return [
                'base_price' => floatval($result['base_price']),
                'shipping' => floatval($result['shipping']),
                'total' => floatval($result['base_price']) + floatval($result['shipping']),
                'bottle_count' => intval($result['bottle_count']),
                'is_upsell' => (bool)$result['is_upsell'],
                'is_subscription' => (bool)$result['is_subscription']
            ];
        }

        return null;
    }

    /**
     * Calculate financial fields for an order
     * Returns: base_price, taxes, processing_fee, allowance_hold, net_amount
     */
    public function calculateFinancials($totalCollected, $skuPattern, $commission, $orderDate = null) {
        $pricing = $this->getBasePrice($skuPattern, $orderDate);

        if (!$pricing) {
            // Cannot calculate without base price
            return null;
        }

        $basePrice = $pricing['total']; // base_price + shipping
        $taxes = round($totalCollected - $basePrice, 2);
        $processingFee = round($totalCollected * 0.10, 2); // 10% processing fee
        $allowanceHold = round($totalCollected * 0.10, 2); // 10% allowance hold
        $netAmount = round($totalCollected - $processingFee - $allowanceHold - floatval($commission), 2);

        return [
            'base_price' => $basePrice,
            'taxes' => max(0, $taxes), // Taxes shouldn't be negative
            'processing_fee' => $processingFee,
            'allowance_hold' => $allowanceHold,
            'net_amount' => $netAmount,
            'is_upsell' => $pricing['is_upsell'] ? 1 : 0
        ];
    }
}
