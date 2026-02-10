/**
 * BuyGoods Analytics API Server
 * Receives webhooks from BuyGoods and provides analytics dashboard data
 */

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');
const db = require('./database/db');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from public directory
app.use(express.static(path.join(__dirname, 'public')));

// Request logging middleware
app.use((req, res, next) => {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${req.method} ${req.path}`);
    next();
});

// ==================== WEBHOOK ENDPOINTS ====================

/**
 * New Order Webhook
 * Triggered when a new order is placed
 */
app.post('/webhook/new-order', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('📦 New Order Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        // Map BuyGoods payload to our schema
        const orderData = {
            order_id: payload.orderId || payload.order_id || payload.transactionId,
            transaction_id: payload.transactionId || payload.transaction_id,
            product_id: payload.productId || payload.product_id,
            product_name: payload.productName || payload.product_name || payload.productTitle,
            product_price: parseFloat(payload.productPrice || payload.product_price || payload.amount || 0),
            quantity: parseInt(payload.quantity || 1),
            customer_email: payload.email || payload.customerEmail || payload.customer_email,
            customer_name: payload.customerName || payload.customer_name || `${payload.firstName || ''} ${payload.lastName || ''}`.trim(),
            customer_phone: payload.phone || payload.customerPhone || payload.customer_phone,
            customer_country: payload.country || payload.customerCountry,
            customer_state: payload.state || payload.customerState,
            customer_city: payload.city || payload.customerCity,
            customer_address: payload.address || payload.customerAddress,
            customer_zip: payload.zip || payload.postalCode || payload.customerZip,
            affiliate_id: payload.affiliateId || payload.affiliate_id || payload.affId,
            affiliate_name: payload.affiliateName || payload.affiliate_name,
            commission: parseFloat(payload.commission || payload.affiliateCommission || 0),
            payment_method: payload.paymentMethod || payload.payment_method || 'card',
            currency: payload.currency || 'USD',
            status: 'completed',
            ip_address: payload.customerIp || ipAddress,
            raw_data: payload
        };

        await db.insertOrder(orderData);
        await db.logWebhook('new_order', payload, ipAddress, true);

        console.log('✓ Order saved successfully:', orderData.order_id);

        res.status(200).json({
            success: true,
            message: 'Order received and processed',
            order_id: orderData.order_id
        });

    } catch (error) {
        console.error('✗ Error processing new order:', error);
        await db.logWebhook('new_order', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing order',
            error: error.message
        });
    }
});

/**
 * Recurring Charge Webhook
 * Triggered when a recurring subscription payment is processed
 */
app.post('/webhook/recurring', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('🔄 Recurring Charge Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        const chargeData = {
            charge_id: payload.chargeId || payload.charge_id || payload.transactionId || `RC-${Date.now()}`,
            order_id: payload.orderId || payload.order_id || payload.originalOrderId,
            transaction_id: payload.transactionId || payload.transaction_id,
            product_id: payload.productId || payload.product_id,
            product_name: payload.productName || payload.product_name,
            amount: parseFloat(payload.amount || payload.chargeAmount || 0),
            customer_email: payload.email || payload.customerEmail,
            customer_name: payload.customerName || payload.customer_name,
            affiliate_id: payload.affiliateId || payload.affiliate_id,
            currency: payload.currency || 'USD',
            status: payload.status === 'failed' ? 'failed' : 'success',
            raw_data: payload
        };

        await db.insertRecurringCharge(chargeData);
        await db.logWebhook('recurring_charge', payload, ipAddress, true);

        console.log('✓ Recurring charge saved successfully:', chargeData.charge_id);

        res.status(200).json({
            success: true,
            message: 'Recurring charge received and processed',
            charge_id: chargeData.charge_id
        });

    } catch (error) {
        console.error('✗ Error processing recurring charge:', error);
        await db.logWebhook('recurring_charge', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing recurring charge',
            error: error.message
        });
    }
});

/**
 * Refund Webhook
 * Triggered when an order is refunded
 */
app.post('/webhook/refund', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('💸 Refund Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        const refundData = {
            refund_id: payload.refundId || payload.refund_id || `RF-${Date.now()}`,
            order_id: payload.orderId || payload.order_id,
            transaction_id: payload.transactionId || payload.transaction_id,
            amount: parseFloat(payload.amount || payload.refundAmount || 0),
            reason: payload.reason || payload.refundReason || 'Customer request',
            refund_type: payload.refundType || (payload.partialRefund ? 'partial' : 'full'),
            raw_data: payload
        };

        await db.insertRefund(refundData);
        await db.logWebhook('refund', payload, ipAddress, true);

        console.log('✓ Refund saved successfully:', refundData.refund_id);

        res.status(200).json({
            success: true,
            message: 'Refund received and processed',
            refund_id: refundData.refund_id
        });

    } catch (error) {
        console.error('✗ Error processing refund:', error);
        await db.logWebhook('refund', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing refund',
            error: error.message
        });
    }
});

/**
 * Cancellation Webhook
 * Triggered when an order/subscription is cancelled
 */
app.post('/webhook/cancel', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('❌ Cancellation Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        const cancelData = {
            cancel_id: payload.cancelId || payload.cancel_id || `CN-${Date.now()}`,
            order_id: payload.orderId || payload.order_id,
            reason: payload.reason || payload.cancelReason || 'Customer request',
            raw_data: payload
        };

        await db.insertCancellation(cancelData);
        await db.logWebhook('cancellation', payload, ipAddress, true);

        console.log('✓ Cancellation saved successfully:', cancelData.cancel_id);

        res.status(200).json({
            success: true,
            message: 'Cancellation received and processed',
            cancel_id: cancelData.cancel_id
        });

    } catch (error) {
        console.error('✗ Error processing cancellation:', error);
        await db.logWebhook('cancellation', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing cancellation',
            error: error.message
        });
    }
});

/**
 * Chargeback Webhook
 * Triggered when a chargeback is filed
 */
app.post('/webhook/chargeback', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('⚠️ Chargeback Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        const chargebackData = {
            chargeback_id: payload.chargebackId || payload.chargeback_id || `CB-${Date.now()}`,
            order_id: payload.orderId || payload.order_id,
            transaction_id: payload.transactionId || payload.transaction_id,
            amount: parseFloat(payload.amount || payload.chargebackAmount || 0),
            reason: payload.reason || payload.chargebackReason || 'Chargeback filed',
            raw_data: payload
        };

        await db.insertChargeback(chargebackData);
        await db.logWebhook('chargeback', payload, ipAddress, true);

        console.log('✓ Chargeback saved successfully:', chargebackData.chargeback_id);

        res.status(200).json({
            success: true,
            message: 'Chargeback received and processed',
            chargeback_id: chargebackData.chargeback_id
        });

    } catch (error) {
        console.error('✗ Error processing chargeback:', error);
        await db.logWebhook('chargeback', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing chargeback',
            error: error.message
        });
    }
});

/**
 * Fulfillment Webhook
 * Triggered when an order is fulfilled/shipped
 */
app.post('/webhook/fulfilled', async (req, res) => {
    const ipAddress = req.ip || req.connection.remoteAddress;

    try {
        console.log('📬 Fulfillment Webhook Received:', JSON.stringify(req.body, null, 2));

        const payload = req.body;

        const fulfillmentData = {
            fulfillment_id: payload.fulfillmentId || payload.fulfillment_id || `FL-${Date.now()}`,
            order_id: payload.orderId || payload.order_id,
            tracking_number: payload.trackingNumber || payload.tracking_number,
            carrier: payload.carrier || payload.shippingCarrier,
            shipped_at: payload.shippedAt || payload.shipped_at || new Date(),
            raw_data: payload
        };

        await db.insertFulfillment(fulfillmentData);
        await db.logWebhook('fulfillment', payload, ipAddress, true);

        console.log('✓ Fulfillment saved successfully:', fulfillmentData.fulfillment_id);

        res.status(200).json({
            success: true,
            message: 'Fulfillment received and processed',
            fulfillment_id: fulfillmentData.fulfillment_id
        });

    } catch (error) {
        console.error('✗ Error processing fulfillment:', error);
        await db.logWebhook('fulfillment', req.body, ipAddress, false, error.message);

        res.status(500).json({
            success: false,
            message: 'Error processing fulfillment',
            error: error.message
        });
    }
});

// ==================== API ENDPOINTS ====================

/**
 * Get dashboard statistics
 */
app.get('/api/stats', async (req, res) => {
    try {
        const { startDate, endDate } = req.query;
        const stats = await db.getDashboardStats(startDate, endDate);
        res.json({ success: true, data: stats });
    } catch (error) {
        console.error('Error fetching stats:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get revenue by day
 */
app.get('/api/revenue/daily', async (req, res) => {
    try {
        const days = parseInt(req.query.days) || 30;
        const data = await db.getRevenueByDay(days);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching daily revenue:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get top products
 */
app.get('/api/products/top', async (req, res) => {
    try {
        const limit = parseInt(req.query.limit) || 10;
        const data = await db.getTopProducts(limit);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching top products:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get recent activity
 */
app.get('/api/activity/recent', async (req, res) => {
    try {
        const limit = parseInt(req.query.limit) || 20;
        const data = await db.getRecentActivity(limit);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching recent activity:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get orders list
 */
app.get('/api/orders', async (req, res) => {
    try {
        const filters = {
            status: req.query.status,
            startDate: req.query.startDate,
            endDate: req.query.endDate,
            productId: req.query.productId,
            limit: req.query.limit || 100
        };
        const data = await db.getOrders(filters);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching orders:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get refunds list
 */
app.get('/api/refunds', async (req, res) => {
    try {
        const filters = {
            startDate: req.query.startDate,
            endDate: req.query.endDate,
            limit: req.query.limit || 100
        };
        const data = await db.getRefunds(filters);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching refunds:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get chargebacks list
 */
app.get('/api/chargebacks', async (req, res) => {
    try {
        const filters = {
            startDate: req.query.startDate,
            endDate: req.query.endDate,
            limit: req.query.limit || 100
        };
        const data = await db.getChargebacks(filters);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching chargebacks:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get recurring charges list
 */
app.get('/api/recurring', async (req, res) => {
    try {
        const filters = {
            startDate: req.query.startDate,
            endDate: req.query.endDate,
            limit: req.query.limit || 100
        };
        const data = await db.getRecurringCharges(filters);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching recurring charges:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * Get webhook logs
 */
app.get('/api/logs', async (req, res) => {
    try {
        const filters = {
            eventType: req.query.eventType,
            processed: req.query.processed,
            limit: req.query.limit || 50
        };
        const data = await db.getWebhookLogs(filters);
        res.json({ success: true, data });
    } catch (error) {
        console.error('Error fetching logs:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// ==================== UTILITY ENDPOINTS ====================

/**
 * Health check endpoint
 */
app.get('/health', async (req, res) => {
    const dbConnected = await db.testConnection();
    res.json({
        status: dbConnected ? 'healthy' : 'unhealthy',
        database: dbConnected ? 'connected' : 'disconnected',
        timestamp: new Date().toISOString()
    });
});

/**
 * Test webhook endpoint (for testing purposes)
 */
app.post('/webhook/test', (req, res) => {
    console.log('🧪 Test Webhook Received:', JSON.stringify(req.body, null, 2));
    res.json({
        success: true,
        message: 'Test webhook received',
        received: req.body
    });
});

// Serve the dashboard for root path
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        success: false,
        message: 'Endpoint not found'
    });
});

// Error handler
app.use((err, req, res, next) => {
    console.error('Server Error:', err);
    res.status(500).json({
        success: false,
        message: 'Internal server error',
        error: process.env.NODE_ENV === 'development' ? err.message : undefined
    });
});

// ==================== START SERVER ====================

async function startServer() {
    // Test database connection
    const dbConnected = await db.testConnection();

    if (!dbConnected) {
        console.warn('⚠️ Warning: Database connection failed. Server starting without database.');
        console.warn('   Please check your database configuration in .env file');
    }

    app.listen(PORT, () => {
        console.log('');
        console.log('='.repeat(50));
        console.log('  BuyGoods Analytics API Server');
        console.log('='.repeat(50));
        console.log(`  Server running on: http://localhost:${PORT}`);
        console.log(`  Dashboard: http://localhost:${PORT}/`);
        console.log('');
        console.log('  Webhook Endpoints:');
        console.log(`  - New Order:    POST /webhook/new-order`);
        console.log(`  - Recurring:    POST /webhook/recurring`);
        console.log(`  - Refund:       POST /webhook/refund`);
        console.log(`  - Cancel:       POST /webhook/cancel`);
        console.log(`  - Chargeback:   POST /webhook/chargeback`);
        console.log(`  - Fulfilled:    POST /webhook/fulfilled`);
        console.log('');
        console.log('  API Endpoints:');
        console.log(`  - Stats:        GET /api/stats`);
        console.log(`  - Orders:       GET /api/orders`);
        console.log(`  - Activity:     GET /api/activity/recent`);
        console.log('='.repeat(50));
        console.log('');
    });
}

startServer();
