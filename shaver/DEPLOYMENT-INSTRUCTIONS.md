# Dashboard v2 - Separate Analytics & Shaver Structure

## ✅ What Was Done

Created a **two-tab structure** with completely separate Analytics and Shaver dashboards:

### 1. **Landing Page (index.html)**
- Clean, modern landing page with two big cards
- **Analytics Card** → Opens BuyGoods Analytics dashboard
- **Shaver Card** → Opens Shaving Analytics dashboard

### 2. **Analytics Dashboard (analytics.html)**
- Complete working BuyGoods Analytics from `BuyGoods Analytics API` folder
- Shows all order data, revenue, refunds, chargebacks, etc.
- Uses separate API files: `analytics-api.php`, `analytics-database.php`, `analytics-config.php`

### 3. **Shaver Dashboard (shaving-analytics.html)**
- Old shaving analytics design with all features
- Traffic analytics, active sessions, session history
- Includes sidebar navigation to all shaving pages

---

## 📁 Files to Upload

Upload these files to: `https://metatrim.trustednutraproduct.com/shaver/`

### New/Updated Files:
```
dashboard-v2/
├── index.html (NEW - landing page with two tabs)
├── analytics.html (NEW - working BuyGoods Analytics)
├── analytics-api.php (NEW - API for analytics page)
├── analytics-database.php (NEW - database for analytics)
├── analytics-config.php (NEW - config for analytics)
├── shaving-analytics.html (EXISTING - already works)
├── sessions.html (EXISTING - already works)
├── history.html (EXISTING - already works)
├── embed-code.html (EXISTING - already works)
└── ... (all other existing files)
```

### Files You DON'T Need to Upload:
- `buygoods-api.php` (not used anymore in this structure)
- `index-old-unified.html` (backup of old unified dashboard)

---

## 🚀 How It Works

### User Flow:

1. **User visits:** `https://metatrim.trustednutraproduct.com/shaver/`
   - Sees landing page with two options

2. **Click "BuyGoods Analytics"**
   - Opens `analytics.html`
   - Shows complete BuyGoods analytics dashboard
   - All data from `jojofwjv_analytics` database
   - Fully functional with all features

3. **Click "Shaving Analytics"**
   - Opens `shaving-analytics.html`
   - Shows shaving traffic analytics
   - Sidebar navigation to:
     - Traffic Analytics
     - Shaving Sessions
     - Session History
     - Embed Code

---

## 📊 Features Included

### **Analytics Dashboard:**
✅ Real-time order tracking
✅ Revenue analytics with charts
✅ Refunds & chargebacks monitoring
✅ Recurring subscriptions tracking
✅ Fraud detection alerts
✅ Webhook logs
✅ Product performance
✅ Admin panel

### **Shaver Dashboard:**
✅ Traffic analytics & monitoring
✅ Active shaving sessions
✅ Session history & logs
✅ Affiliate tracking
✅ Behavior events
✅ Embed code generator
✅ Country & device tracking

---

## 🔧 Configuration

### Analytics Database Config (`analytics-config.php`):
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jojofwjv_analytics');
define('DB_USER', 'jojofwjv_analytics');
define('DB_PASS', 'YP6BxKSyheB4NMTdfe6v');
```

### Shaver Database Config (`config.php` - EXISTING):
```php
define('DB_SHAVING_HOST', 'localhost');
define('DB_SHAVING_NAME', 'jojofwjv_shaving_db');
define('DB_SHAVING_USER', 'jojofwjv_shaving_db');
define('DB_SHAVING_PASS', 'yDe7CnRPaVNwPM8TnaFn');
```

---

## ✅ Testing Checklist

After upload, test:

### **1. Landing Page (index.html)**
- [ ] Page loads without errors
- [ ] Two cards display properly
- [ ] Icons render correctly
- [ ] Hover effects work
- [ ] Responsive on mobile

### **2. Analytics Dashboard (analytics.html)**
- [ ] Page loads and shows data
- [ ] All tabs work (Overview, Orders, Revenue, etc.)
- [ ] Stats cards show correct numbers
- [ ] Charts render properly
- [ ] No console errors

### **3. Shaver Dashboard (shaving-analytics.html)**
- [ ] Page loads and shows traffic data
- [ ] Sidebar navigation works
- [ ] Stats cards populated
- [ ] Traffic table shows data
- [ ] Period filters work (Today, Yesterday, This Week)

---

## 📝 Notes

- **Completely separate**: Analytics and Shaver use different APIs and databases
- **No conflicts**: Each has its own PHP files (analytics-api.php vs api.php)
- **Old design preserved**: Shaving pages keep the original design
- **All historical data**: Both dashboards access existing databases

---

## 🐛 Troubleshooting

### If Analytics page shows no data:
1. Check `analytics-config.php` database credentials
2. Verify `jojofwjv_analytics` database exists
3. Test API: `https://yourdomain.com/shaver/analytics-api.php?action=stats`

### If Shaver page shows no data:
1. Check `config.php` database credentials
2. Verify `jojofwjv_shaving_db` database exists
3. Test API: `https://yourdomain.com/shaver/api.php?action=getAnalytics`

### If landing page doesn't load:
1. Clear browser cache
2. Check console for errors
3. Verify Font Awesome CSS loads

---

## 🎉 Summary

You now have:
✅ **Two completely separate dashboards** (Analytics & Shaver)
✅ **Clean landing page** to choose between them
✅ **All old features preserved** in both systems
✅ **Old designs maintained** exactly as they were
✅ **All historical data accessible** from both dashboards

Upload the files and test! 🚀
