# 🚀 QUICK START GUIDE

## ⚡ Get Your Dashboard Running in 5 Minutes

---

## Step 1: Upload to Hosting (2 minutes)

**Upload the entire `dashboard-v2` folder** to your hosting server using FTP/cPanel:

```
your-domain.com/dashboard-v2/    ← Upload here
```

**What to upload:**
- ✅ ALL HTML files (14 pages)
- ✅ ALL PHP files (api.php, buygoods-api.php, webhook.php, database.php, config.php)
- ✅ css/ folder (with style.css and custom.css)
- ✅ js/ folder (all JavaScript files)
- ✅ vendors/ folder (Bootstrap, jQuery, etc.)
- ✅ fonts/ folder
- ✅ images/ folder

**Upload method:**
- **FTP:** Use FileZilla or any FTP client
- **cPanel:** Use File Manager → Upload
- **Command line:** `scp -r dashboard-v2/ user@host:/path/`

---

## Step 2: Configure Database (2 minutes)

**Edit `dashboard-v2/config.php`** on your server:

```php
<?php
// Shaving Analytics Database
define('DB_SHAVING_HOST', 'localhost');
define('DB_SHAVING_NAME', 'your_shaving_database_name');  // ← Change this
define('DB_SHAVING_USER', 'your_database_username');      // ← Change this
define('DB_SHAVING_PASS', 'your_database_password');      // ← Change this

// BuyGoods Analytics Database
define('DB_BUYGOODS_HOST', 'localhost');
define('DB_BUYGOODS_NAME', 'your_buygoods_database_name'); // ← Change this
define('DB_BUYGOODS_USER', 'your_database_username');       // ← Change this
define('DB_BUYGOODS_PASS', 'your_database_password');       // ← Change this

// IPQualityScore API Key (optional - for fraud detection)
define('IPQS_API_KEY', 'your_ipqs_api_key_here');          // ← Change this
?>
```

**Where to find these?**
- You already have these databases from your existing projects
- Use the SAME credentials from your old `api.php` and BuyGoods `config.php`
- **Don't create new databases** - use existing ones!

---

## Step 3: Set Webhook URL (1 minute)

1. Log in to **BuyGoods Dashboard**
2. Go to **Settings** → **Webhooks**
3. Set webhook URL to:
   ```
   https://your-domain.com/dashboard-v2/webhook.php
   ```
4. Enable these events:
   - ✅ New Order
   - ✅ Refund
   - ✅ Chargeback
   - ✅ Recurring Payment

---

## Step 4: Access Your Dashboard! (30 seconds)

Open your browser and go to:

```
https://your-domain.com/dashboard-v2/
```

**You should see:**
- ✅ Purple Melody theme
- ✅ Sidebar with 14 menu items
- ✅ KPI cards showing data
- ✅ Charts and tables

---

## ✅ Quick Test

### Test 1: Dashboard Loads
1. Visit: `https://your-domain.com/dashboard-v2/`
2. **Expected:** Dashboard appears with purple theme

### Test 2: Create Shaving Session
1. Click **Shaving Sessions** in sidebar
2. Enter test Affiliate ID: `TEST123`
3. Click "Start Shaving Session"
4. **Expected:** Session appears in table

### Test 3: Check Orders
1. Click **Orders** in sidebar
2. **Expected:** Your BuyGoods orders appear

---

## 🎯 That's It!

Your unified dashboard is now live and running!

**Access all pages:**
- Main Dashboard: `/dashboard-v2/`
- Sessions: `/dashboard-v2/sessions.html`
- Traffic Analytics: `/dashboard-v2/shaving-analytics.html`
- Orders: `/dashboard-v2/orders.html`
- And 10 more pages...

---

## ⚠️ Important Reminders

### **1. DO NOT Change Tracking Scripts**

These files on your **landing pages** should remain UNCHANGED:
- ❌ Don't modify `shaving-check.php`
- ❌ Don't modify `shaving-metatrim.js`

The dashboard only DISPLAYS their data - they continue working as before!

### **2. No PIN Protection**

There's NO PIN protection as requested. Anyone with the URL can access.

**To add security later:**
```apache
# Create .htaccess in dashboard-v2 folder
AuthType Basic
AuthName "Analytics Dashboard"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

### **3. Use Existing Databases**

You DON'T need to create new databases. The dashboard uses:
- Your existing **Shaving Analytics** database
- Your existing **BuyGoods Analytics** database

Just point `config.php` to them!

---

## 🆘 Having Issues?

### Dashboard shows blank page
- Check `config.php` - are credentials correct?
- Check PHP error logs
- Check browser console (F12) for JavaScript errors

### No data showing
- Verify database tables exist
- Check database credentials in `config.php`
- Test database connection

### Webhooks not working
- Verify webhook URL in BuyGoods settings
- Check `webhook.php` is accessible
- Look at `webhook_logs` table for errors

---

## 📚 Full Documentation

For detailed information, see:
- **DEPLOYMENT-GUIDE.md** - Complete deployment instructions
- **PROJECT-SUMMARY.md** - Full feature list and overview
- **DATABASE-SETUP.sql** - Database schema reference
- **README.md** - Project documentation

---

## 🎉 Enjoy Your New Dashboard!

You now have a professional, unified analytics system combining both Shaving and BuyGoods analytics in one beautiful interface!

**Built with ❤️ using Claude Code**

