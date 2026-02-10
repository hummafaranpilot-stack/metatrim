/**
 * Test script for BuyGoods Analytics Webhooks
 * Run this to test your webhook endpoints with sample data
 */

const http = require('http');

const BASE_URL = process.env.BASE_URL || 'http://localhost:3000';

// Sample test data
const testData = {
    newOrder: {
        orderId: `TEST-${Date.now()}`,
        transactionId: `TXN-${Date.now()}`,
        productId: 'PROD-001',
        productName: 'Premium Weight Loss Formula',
        productPrice: 69.99,
        quantity: 1,
        customerEmail: 'john.doe@example.com',
        customerName: 'John Doe',
        customerPhone: '+1-555-0123',
        customerCountry: 'United States',
        customerState: 'California',
        customerCity: 'Los Angeles',
        customerAddress: '123 Main Street',
        customerZip: '90001',
        affiliateId: 'AFF-001',
        affiliateName: 'Top Affiliate',
        commission: 25.00,
        paymentMethod: 'credit_card',
        currency: 'USD'
    },

    recurringCharge: {
        chargeId: `RC-${Date.now()}`,
        orderId: `TEST-${Date.now() - 1000}`,
        transactionId: `TXN-RC-${Date.now()}`,
        productId: 'PROD-001',
        productName: 'Premium Weight Loss Formula (Subscription)',
        amount: 49.99,
        customerEmail: 'jane.smith@example.com',
        customerName: 'Jane Smith',
        affiliateId: 'AFF-002',
        currency: 'USD',
        status: 'success'
    },

    refund: {
        refundId: `RF-${Date.now()}`,
        orderId: `TEST-${Date.now() - 2000}`,
        transactionId: `TXN-${Date.now() - 2000}`,
        amount: 69.99,
        reason: 'Customer request - product did not meet expectations',
        refundType: 'full'
    },

    cancellation: {
        cancelId: `CN-${Date.now()}`,
        orderId: `TEST-${Date.now() - 3000}`,
        reason: 'Customer requested subscription cancellation'
    },

    chargeback: {
        chargebackId: `CB-${Date.now()}`,
        orderId: `TEST-${Date.now() - 4000}`,
        transactionId: `TXN-${Date.now() - 4000}`,
        amount: 69.99,
        reason: 'Unauthorized transaction'
    },

    fulfillment: {
        fulfillmentId: `FL-${Date.now()}`,
        orderId: `TEST-${Date.now() - 5000}`,
        trackingNumber: 'USPS123456789',
        carrier: 'USPS',
        shippedAt: new Date().toISOString()
    }
};

// Send POST request
function sendWebhook(endpoint, data) {
    return new Promise((resolve, reject) => {
        const url = new URL(endpoint, BASE_URL);
        const postData = JSON.stringify(data);

        const options = {
            hostname: url.hostname,
            port: url.port || 80,
            path: url.pathname,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData)
            }
        };

        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                try {
                    resolve({ status: res.statusCode, body: JSON.parse(body) });
                } catch {
                    resolve({ status: res.statusCode, body: body });
                }
            });
        });

        req.on('error', reject);
        req.write(postData);
        req.end();
    });
}

// Run tests
async function runTests() {
    console.log('='.repeat(50));
    console.log('  BuyGoods Webhook Test Suite');
    console.log('='.repeat(50));
    console.log(`  Testing against: ${BASE_URL}`);
    console.log('');

    const tests = [
        { name: 'New Order', endpoint: '/webhook/new-order', data: testData.newOrder },
        { name: 'Recurring Charge', endpoint: '/webhook/recurring', data: testData.recurringCharge },
        { name: 'Refund', endpoint: '/webhook/refund', data: testData.refund },
        { name: 'Cancellation', endpoint: '/webhook/cancel', data: testData.cancellation },
        { name: 'Chargeback', endpoint: '/webhook/chargeback', data: testData.chargeback },
        { name: 'Fulfillment', endpoint: '/webhook/fulfilled', data: testData.fulfillment }
    ];

    for (const test of tests) {
        try {
            console.log(`Testing: ${test.name}...`);
            const result = await sendWebhook(test.endpoint, test.data);

            if (result.status === 200 && result.body.success) {
                console.log(`  ✓ ${test.name}: SUCCESS`);
            } else {
                console.log(`  ✗ ${test.name}: FAILED (Status: ${result.status})`);
                console.log(`    Response: ${JSON.stringify(result.body)}`);
            }
        } catch (error) {
            console.log(`  ✗ ${test.name}: ERROR - ${error.message}`);
        }
    }

    console.log('');
    console.log('='.repeat(50));
    console.log('  Tests Complete! Check dashboard for results.');
    console.log('='.repeat(50));
}

// Check if server is running first
async function checkServer() {
    try {
        const url = new URL('/health', BASE_URL);

        return new Promise((resolve) => {
            const req = http.get(url, (res) => {
                resolve(res.statusCode === 200);
            });
            req.on('error', () => resolve(false));
            req.setTimeout(5000, () => {
                req.destroy();
                resolve(false);
            });
        });
    } catch {
        return false;
    }
}

// Main
async function main() {
    console.log('Checking if server is running...');

    const serverUp = await checkServer();

    if (!serverUp) {
        console.log('');
        console.log('ERROR: Server is not running!');
        console.log('Please start the server first with: npm start');
        console.log('');
        process.exit(1);
    }

    console.log('Server is running. Starting tests...');
    console.log('');

    await runTests();
}

main();
