# Unified Analytics Dashboard

**Version:** 2.0
**Status:** In Development
**Created:** February 2026

## Overview

This is a unified professional dashboard combining **Shaving Analytics** and **BuyGoods Analytics** into ONE Melody-themed interface. It replaces the previous separate dashboards with a modern, responsive Bootstrap 4 design.

## What's Been Combined

### 1. Shaving Analytics
- Affiliate traffic tracking and shaving control
- Behavior analytics and session management
- Pakistan Time (PKT) filtering
- Traffic log with detailed metrics

### 2. BuyGoods Analytics
- Real-time order tracking via webhooks
- Revenue analytics with refunds/chargebacks
- Recurring subscription management
- IP fraud detection (IPQualityScore)
- Product performance tracking

## Current Structure

```
dashboard-v2/
├── index.html                 ✅ Main dashboard (combined metrics)
├── sessions.html              ✅ Shaving sessions management
├── history.html               ⏳ Session history (pending)
├── embed-code.html            ⏳ Embed code generator (pending)
├── shaving-analytics.html     ⏳ Traffic analytics (pending)
├── orders.html                ⏳ BuyGoods orders (pending)
├── revenue.html               ⏳ Revenue analytics (pending)
├── recurring.html             ⏳ Recurring charges (pending)
├── refunds.html               ⏳ Refunds (pending)
├── chargebacks.html           ⏳ Chargebacks (pending)
├── fraud-detection.html       ⏳ Fraud detection (pending)
├── products.html              ⏳ Product performance (pending)
├── webhooks.html              ⏳ Webhook logs (pending)
├── admin.html                 ⏳ Admin panel (pending)
│
├── api.php                    ✅ Shaving Analytics API
├── buygoods-api.php           ✅ BuyGoods Analytics API
├── webhook.php                ✅ Webhook receiver
├── database.php               ✅ Database wrapper
├── config.php                 ✅ Configuration
│
├── css/
│   ├── style.css              ✅ Melody master stylesheet (1.1 MB)
│   └── custom.css             ✅ Custom styles for both projects
│
├── js/
│   ├── config.js              ✅ API configuration
│   ├── dashboard.js           ✅ Combined dashboard logic
│   ├── misc.js                ✅ Melody utilities
│   ├── off-canvas.js          ✅ Mobile sidebar
│   ├── settings.js            ✅ Theme settings
│   ├── hoverable-collapse.js  ✅ Sidebar collapse
│   └── todolist.js            ✅ Melody utilities
│
├── vendors/                   ✅ Bootstrap, jQuery, Chart.js, DataTables
├── fonts/                     ✅ Font files
└── images/                    ✅ Logo and icons
```

## Features

### ✅ Completed
1. **Folder Structure** - All directories created
2. **Melody Template Integration** - CSS, JS, and vendors copied
3. **API Files** - Both project APIs integrated
4. **Main Dashboard** - Combined metrics from both systems
5. **Sessions Page** - Create and manage shaving sessions
6. **Custom Styling** - Purple theme (#392C70) applied
7. **Responsive Design** - Mobile-first layout
8. **Unified Navigation** - Sidebar with 16+ menu items organized in sections

### ⏳ Pending
1. **Remaining HTML Pages** - 11 more pages to create
2. **JavaScript Extraction** - Modular JS from shaving.html
3. **Chart Integration** - Revenue and traffic visualizations
4. **Testing** - Full functionality testing
5. **Database Setup** - Combined schema

## Technology Stack

- **Frontend:** Bootstrap 4, Melody Admin Template
- **JavaScript:** jQuery, Chart.js, DataTables
- **Backend:** PHP 7.4+, MySQL
- **APIs:** Shaving Analytics API, BuyGoods Analytics API
- **Webhooks:** BuyGoods webhook integration
- **Fraud Detection:** IPQualityScore API

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Configure Database**
   ```bash
   # Edit config.php with your database credentials
   # Import database schema (when provided)
   ```

2. **Test Dashboard**
   - Navigate to: `http://your-domain/dashboard-v2/`
   - Default page: index.html (Main Dashboard)

3. **Important Notes**
   - NO PIN protection (removed as requested)
   - Tracking scripts (shaving-check.php, shaving-metatrim.js) remain UNCHANGED on landing pages
   - All existing functionality preserved

## Unified Sidebar Navigation

### Main Dashboard
- **Dashboard Overview** - Combined metrics

### Shaving Analytics Section
- **Traffic Analytics** - Affiliate traffic tracking
- **Shaving Sessions** - Create/manage sessions ✅
- **Session History** - Historical data
- **Embed Code** - Code generator

### BuyGoods Analytics Section
- **Orders** - Order dashboard
- **Revenue Analytics** - Revenue charts
- **Recurring Charges** - Subscription management
- **Refunds** - Refund tracking
- **Chargebacks** - Dispute management
- **Fraud Detection** - IP fraud analysis
- **Products** - Product performance
- **Webhook Logs** - Webhook events

### Admin Section
- **Admin Panel** - Settings and controls

## API Endpoints

### Shaving Analytics API (api.php)
- `create_session` - Start new shaving session
- `stop_session` - Stop active session
- `get_sessions` - Get active sessions
- `get_history` - Get session history
- `get_analytics` - Get traffic analytics
- `get_traffic_log` - Get traffic data

### BuyGoods Analytics API (buygoods-api.php)
- `getDashboardStats` - Main KPIs
- `getRecentOrders` - Recent orders list
- `getRevenueChart` - Revenue chart data
- `getOrders` - Orders with filters
- `getRecurring` - Recurring charges
- `getRefunds` - Refund data
- `getChargebacks` - Chargeback data
- `getFraudDetection` - IP fraud analysis
- `getProducts` - Product performance
- `getWebhooks` - Webhook logs

## Color Scheme

- **Primary Purple:** #392C70
- **Secondary Purple:** #5940a8
- **Success Green:** #04B76B
- **Warning Orange:** #F5A623
- **Danger Red:** #FF5E6D
- **Info Blue:** #0B94F7

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Security Features

- Input validation on all forms
- CSRF protection
- SQL injection prevention via prepared statements
- XSS protection
- Secure API endpoints

## Performance

- Optimized CSS (minified vendors)
- Lazy loading for charts
- Auto-refresh every 60 seconds
- Efficient database queries

## Development Status

**Phase 1:** ✅ Foundation & Main Dashboard (COMPLETE)
- Folder structure
- Template integration
- Main dashboard page
- Sessions page
- API integration

**Phase 2:** ⏳ Remaining Pages (IN PROGRESS)
- 11 additional HTML pages
- JavaScript modularization
- Chart integration

**Phase 3:** ⏳ Testing & Optimization (PENDING)
- Cross-browser testing
- Mobile responsive testing
- Performance optimization
- Bug fixes

## Support

For issues or questions:
1. Check API endpoints are accessible
2. Verify database connection in config.php
3. Check browser console for JavaScript errors
4. Review server error logs

## Version History

- **v2.0** (Feb 2026) - Initial unified dashboard creation
  - Combined Shaving + BuyGoods analytics
  - Melody Bootstrap template integration
  - Main dashboard and sessions page completed

## Next Steps

1. Create remaining 11 HTML pages
2. Extract and modularize JavaScript from shaving.html
3. Integrate Chart.js visualizations
4. Complete database schema
5. Full functionality testing
6. Deploy to production

---

**Built with ❤️ using Claude Code**
