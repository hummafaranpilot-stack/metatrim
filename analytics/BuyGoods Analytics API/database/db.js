const mysql = require('mysql2/promise');
require('dotenv').config();

// Create connection pool
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'buygoods_analytics',
    port: process.env.DB_PORT || 3306,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Test database connection
async function testConnection() {
    try {
        const connection = await pool.getConnection();
        console.log('✓ Database connected successfully');
        connection.release();
        return true;
    } catch (error) {
        console.error('✗ Database connection failed:', error.message);
        return false;
    }
}

// ==================== ORDER OPERATIONS ====================

async function insertOrder(orderData) {
    const sql = `
        INSERT INTO orders (
            order_id, transaction_id, product_id, product_name, product_price,
            quantity, customer_email, customer_name, customer_phone,
            customer_country, customer_state, customer_city, customer_address,
            customer_zip, affiliate_id, affiliate_name, commission,
            payment_method, currency, status, ip_address, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            updated_at = CURRENT_TIMESTAMP
    `;

    const values = [
        orderData.order_id,
        orderData.transaction_id,
        orderData.product_id,
        orderData.product_name,
        orderData.product_price || 0,
        orderData.quantity || 1,
        orderData.customer_email,
        orderData.customer_name,
        orderData.customer_phone,
        orderData.customer_country,
        orderData.customer_state,
        orderData.customer_city,
        orderData.customer_address,
        orderData.customer_zip,
        orderData.affiliate_id,
        orderData.affiliate_name,
        orderData.commission || 0,
        orderData.payment_method,
        orderData.currency || 'USD',
        orderData.status || 'completed',
        orderData.ip_address,
        JSON.stringify(orderData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);
    return result;
}

async function updateOrderStatus(orderId, status) {
    const sql = 'UPDATE orders SET status = ? WHERE order_id = ?';
    const [result] = await pool.execute(sql, [status, orderId]);
    return result;
}

async function getOrders(filters = {}) {
    let sql = 'SELECT * FROM orders WHERE 1=1';
    const values = [];

    if (filters.status) {
        sql += ' AND status = ?';
        values.push(filters.status);
    }

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    if (filters.productId) {
        sql += ' AND product_id = ?';
        values.push(filters.productId);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== RECURRING CHARGE OPERATIONS ====================

async function insertRecurringCharge(chargeData) {
    const sql = `
        INSERT INTO recurring_charges (
            charge_id, order_id, transaction_id, product_id, product_name,
            amount, customer_email, customer_name, affiliate_id, currency, status, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status)
    `;

    const values = [
        chargeData.charge_id,
        chargeData.order_id,
        chargeData.transaction_id,
        chargeData.product_id,
        chargeData.product_name,
        chargeData.amount || 0,
        chargeData.customer_email,
        chargeData.customer_name,
        chargeData.affiliate_id,
        chargeData.currency || 'USD',
        chargeData.status || 'success',
        JSON.stringify(chargeData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);
    return result;
}

async function getRecurringCharges(filters = {}) {
    let sql = 'SELECT * FROM recurring_charges WHERE 1=1';
    const values = [];

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== REFUND OPERATIONS ====================

async function insertRefund(refundData) {
    const sql = `
        INSERT INTO refunds (
            refund_id, order_id, transaction_id, amount, reason, refund_type, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            amount = VALUES(amount)
    `;

    const values = [
        refundData.refund_id,
        refundData.order_id,
        refundData.transaction_id,
        refundData.amount || 0,
        refundData.reason,
        refundData.refund_type || 'full',
        JSON.stringify(refundData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);

    // Update order status
    if (refundData.order_id) {
        await updateOrderStatus(refundData.order_id, 'refunded');
    }

    return result;
}

async function getRefunds(filters = {}) {
    let sql = 'SELECT * FROM refunds WHERE 1=1';
    const values = [];

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== CANCELLATION OPERATIONS ====================

async function insertCancellation(cancelData) {
    const sql = `
        INSERT INTO cancellations (
            cancel_id, order_id, reason, raw_data
        ) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            reason = VALUES(reason)
    `;

    const values = [
        cancelData.cancel_id,
        cancelData.order_id,
        cancelData.reason,
        JSON.stringify(cancelData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);

    // Update order status
    if (cancelData.order_id) {
        await updateOrderStatus(cancelData.order_id, 'cancelled');
    }

    return result;
}

async function getCancellations(filters = {}) {
    let sql = 'SELECT * FROM cancellations WHERE 1=1';
    const values = [];

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== CHARGEBACK OPERATIONS ====================

async function insertChargeback(chargebackData) {
    const sql = `
        INSERT INTO chargebacks (
            chargeback_id, order_id, transaction_id, amount, reason, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            amount = VALUES(amount)
    `;

    const values = [
        chargebackData.chargeback_id,
        chargebackData.order_id,
        chargebackData.transaction_id,
        chargebackData.amount || 0,
        chargebackData.reason,
        JSON.stringify(chargebackData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);

    // Update order status
    if (chargebackData.order_id) {
        await updateOrderStatus(chargebackData.order_id, 'chargeback');
    }

    return result;
}

async function getChargebacks(filters = {}) {
    let sql = 'SELECT * FROM chargebacks WHERE 1=1';
    const values = [];

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== FULFILLMENT OPERATIONS ====================

async function insertFulfillment(fulfillmentData) {
    const sql = `
        INSERT INTO fulfillments (
            fulfillment_id, order_id, tracking_number, carrier, shipped_at, raw_data
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            tracking_number = VALUES(tracking_number),
            carrier = VALUES(carrier)
    `;

    const values = [
        fulfillmentData.fulfillment_id,
        fulfillmentData.order_id,
        fulfillmentData.tracking_number,
        fulfillmentData.carrier,
        fulfillmentData.shipped_at || new Date(),
        JSON.stringify(fulfillmentData.raw_data || {})
    ];

    const [result] = await pool.execute(sql, values);

    // Update order status
    if (fulfillmentData.order_id) {
        await updateOrderStatus(fulfillmentData.order_id, 'fulfilled');
    }

    return result;
}

async function getFulfillments(filters = {}) {
    let sql = 'SELECT * FROM fulfillments WHERE 1=1';
    const values = [];

    if (filters.startDate) {
        sql += ' AND created_at >= ?';
        values.push(filters.startDate);
    }

    if (filters.endDate) {
        sql += ' AND created_at <= ?';
        values.push(filters.endDate);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== WEBHOOK LOG OPERATIONS ====================

async function logWebhook(eventType, payload, ipAddress, processed = false, errorMessage = null) {
    const sql = `
        INSERT INTO webhook_logs (event_type, payload, ip_address, processed, error_message)
        VALUES (?, ?, ?, ?, ?)
    `;

    const values = [
        eventType,
        JSON.stringify(payload),
        ipAddress,
        processed,
        errorMessage
    ];

    const [result] = await pool.execute(sql, values);
    return result;
}

async function getWebhookLogs(filters = {}) {
    let sql = 'SELECT * FROM webhook_logs WHERE 1=1';
    const values = [];

    if (filters.eventType) {
        sql += ' AND event_type = ?';
        values.push(filters.eventType);
    }

    if (filters.processed !== undefined) {
        sql += ' AND processed = ?';
        values.push(filters.processed);
    }

    sql += ' ORDER BY created_at DESC';

    if (filters.limit) {
        sql += ' LIMIT ?';
        values.push(parseInt(filters.limit));
    }

    const [rows] = await pool.execute(sql, values);
    return rows;
}

// ==================== STATISTICS OPERATIONS ====================

async function getDashboardStats(startDate = null, endDate = null) {
    const dateFilter = startDate && endDate
        ? 'AND created_at BETWEEN ? AND ?'
        : '';
    const dateValues = startDate && endDate ? [startDate, endDate] : [];

    // Get order stats
    const [orderStats] = await pool.execute(`
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(product_price * quantity), 0) as total_revenue,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
            COUNT(CASE WHEN status = 'chargeback' THEN 1 END) as chargeback_orders,
            COUNT(CASE WHEN status = 'fulfilled' THEN 1 END) as fulfilled_orders
        FROM orders WHERE 1=1 ${dateFilter}
    `, dateValues);

    // Get refund stats
    const [refundStats] = await pool.execute(`
        SELECT
            COUNT(*) as total_refunds,
            COALESCE(SUM(amount), 0) as refund_amount
        FROM refunds WHERE 1=1 ${dateFilter}
    `, dateValues);

    // Get chargeback stats
    const [chargebackStats] = await pool.execute(`
        SELECT
            COUNT(*) as total_chargebacks,
            COALESCE(SUM(amount), 0) as chargeback_amount
        FROM chargebacks WHERE 1=1 ${dateFilter}
    `, dateValues);

    // Get recurring stats
    const [recurringStats] = await pool.execute(`
        SELECT
            COUNT(*) as total_recurring,
            COALESCE(SUM(amount), 0) as recurring_revenue
        FROM recurring_charges WHERE status = 'success' ${dateFilter}
    `, dateValues);

    // Calculate net revenue
    const totalRevenue = parseFloat(orderStats[0].total_revenue) + parseFloat(recurringStats[0].recurring_revenue);
    const totalDeductions = parseFloat(refundStats[0].refund_amount) + parseFloat(chargebackStats[0].chargeback_amount);
    const netRevenue = totalRevenue - totalDeductions;

    return {
        orders: orderStats[0],
        refunds: refundStats[0],
        chargebacks: chargebackStats[0],
        recurring: recurringStats[0],
        summary: {
            total_revenue: totalRevenue,
            total_deductions: totalDeductions,
            net_revenue: netRevenue
        }
    };
}

async function getRevenueByDay(days = 30) {
    const sql = `
        SELECT
            DATE(created_at) as date,
            COUNT(*) as orders,
            COALESCE(SUM(product_price * quantity), 0) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    `;

    const [rows] = await pool.execute(sql, [days]);
    return rows;
}

async function getTopProducts(limit = 10) {
    const sql = `
        SELECT
            product_id,
            product_name,
            COUNT(*) as total_orders,
            SUM(quantity) as total_quantity,
            SUM(product_price * quantity) as total_revenue
        FROM orders
        WHERE status NOT IN ('refunded', 'cancelled', 'chargeback')
        GROUP BY product_id, product_name
        ORDER BY total_revenue DESC
        LIMIT ?
    `;

    const [rows] = await pool.execute(sql, [limit]);
    return rows;
}

async function getRecentActivity(limit = 20) {
    const sql = `
        (SELECT 'order' as type, order_id as id, product_name as description, product_price as amount, created_at FROM orders ORDER BY created_at DESC LIMIT ?)
        UNION ALL
        (SELECT 'refund' as type, refund_id as id, CONCAT('Refund for order ', order_id) as description, amount, created_at FROM refunds ORDER BY created_at DESC LIMIT ?)
        UNION ALL
        (SELECT 'chargeback' as type, chargeback_id as id, CONCAT('Chargeback for order ', order_id) as description, amount, created_at FROM chargebacks ORDER BY created_at DESC LIMIT ?)
        UNION ALL
        (SELECT 'recurring' as type, charge_id as id, product_name as description, amount, created_at FROM recurring_charges ORDER BY created_at DESC LIMIT ?)
        ORDER BY created_at DESC
        LIMIT ?
    `;

    const [rows] = await pool.execute(sql, [limit, limit, limit, limit, limit]);
    return rows;
}

module.exports = {
    pool,
    testConnection,
    // Orders
    insertOrder,
    updateOrderStatus,
    getOrders,
    // Recurring
    insertRecurringCharge,
    getRecurringCharges,
    // Refunds
    insertRefund,
    getRefunds,
    // Cancellations
    insertCancellation,
    getCancellations,
    // Chargebacks
    insertChargeback,
    getChargebacks,
    // Fulfillments
    insertFulfillment,
    getFulfillments,
    // Logs
    logWebhook,
    getWebhookLogs,
    // Stats
    getDashboardStats,
    getRevenueByDay,
    getTopProducts,
    getRecentActivity
};
