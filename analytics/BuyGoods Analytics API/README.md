# BuyGoods Analytics Dashboard (PHP Version)

A webhook receiver and analytics dashboard for tracking sales data from BuyGoods Advertiser Dashboard. Works on any shared hosting with PHP and MySQL.

## Features

- Receives webhooks from BuyGoods for:
  - New orders
  - Recurring charges
  - Refunds
  - Cancellations
  - Chargebacks
  - Fulfillments
- Real-time analytics dashboard
- MySQL database storage
- Webhook logging for debugging
- Auto-refreshing dashboard (every 30 seconds)

## Files Structure

```
├── index.html      # Analytics Dashboard
├── api.php         # API endpoints for dashboard
├── webhook.php     # Webhook receiver
├── database.php    # Database class
├── config.php      # Configuration (edit this!)
├── install.php     # Database installer (delete after setup)
└── README.md       # This file
```

## Installation on Shared Hosting (cPanel)

### Step 1: Upload Files

1. Login to your cPanel
2. Open File Manager
3. Navigate to `public_html` or create a subdirectory (e.g., `public_html/analytics`)
4. Upload all PHP files and index.html

### Step 2: Create MySQL Database

1. In cPanel, go to **MySQL Databases**
2. Create a new database (e.g., `yourusername_buygoods`)
3. Create a new database user with a strong password
4. Add the user to the database with **ALL PRIVILEGES**

### Step 3: Configure

1. Edit `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'yourusername_buygoods');  // Your database name
define('DB_USER', 'yourusername_dbuser');    // Your database user
define('DB_PASS', 'your_password');          // Your database password
```

### Step 4: Run Installer

1. Open your browser and go to:
   ```
   https://yourdomain.com/analytics/install.php
   ```
2. The installer will create all database tables
3. **DELETE install.php after successful installation** (security!)

### Step 5: Configure BuyGoods Webhooks

In your BuyGoods Advertiser Dashboard, go to **Products Settings > Global IPNs** and enter:

| Field | URL |
|-------|-----|
| New order URL | `https://yourdomain.com/analytics/webhook.php?type=new-order` |
| Recurring charge URL | `https://yourdomain.com/analytics/webhook.php?type=recurring` |
| Order refund URL | `https://yourdomain.com/analytics/webhook.php?type=refund` |
| Order cancel URL | `https://yourdomain.com/analytics/webhook.php?type=cancel` |
| Order chargeback URL | `https://yourdomain.com/analytics/webhook.php?type=chargeback` |
| Order fulfilled URL | `https://yourdomain.com/analytics/webhook.php?type=fulfilled` |

### Step 6: Access Dashboard

Open your dashboard:
```
https://yourdomain.com/analytics/index.html
```

## API Endpoints

### Webhook Endpoints (POST)

| URL | Description |
|-----|-------------|
| `webhook.php?type=new-order` | Receive new order notifications |
| `webhook.php?type=recurring` | Receive recurring charge notifications |
| `webhook.php?type=refund` | Receive refund notifications |
| `webhook.php?type=cancel` | Receive cancellation notifications |
| `webhook.php?type=chargeback` | Receive chargeback notifications |
| `webhook.php?type=fulfilled` | Receive fulfillment notifications |
| `webhook.php?type=test` | Test webhook endpoint |

### Dashboard API Endpoints (GET)

| URL | Description |
|-----|-------------|
| `api.php?action=stats` | Get dashboard statistics |
| `api.php?action=orders` | Get orders list |
| `api.php?action=refunds` | Get refunds list |
| `api.php?action=chargebacks` | Get chargebacks list |
| `api.php?action=recurring` | Get recurring charges list |
| `api.php?action=activity` | Get recent activity |
| `api.php?action=products` | Get top products |
| `api.php?action=logs` | Get webhook logs |
| `api.php?action=health` | Health check |

## Testing Webhooks

You can test the webhook with curl:

```bash
curl -X POST "https://yourdomain.com/analytics/webhook.php?type=new-order" \
  -H "Content-Type: application/json" \
  -d '{
    "orderId": "TEST-001",
    "productId": "PROD-001",
    "productName": "Test Product",
    "productPrice": 49.99,
    "customerEmail": "test@example.com",
    "customerName": "John Doe"
  }'
```

## Troubleshooting

### "Database connection failed" error
- Check your credentials in `config.php`
- Make sure the database user has proper permissions
- Verify the database exists

### Webhooks not being received
- Verify the webhook URLs in BuyGoods are correct
- Check if your hosting blocks POST requests
- Review webhook logs in the dashboard

### Dashboard shows "Server Offline"
- Check if PHP is working on your server
- Verify `api.php` is accessible

## Security Recommendations

1. **Delete install.php** after setup
2. Use HTTPS for all webhook URLs
3. Consider adding .htaccess protection for the dashboard
4. Regularly backup your database

## License

ISC
