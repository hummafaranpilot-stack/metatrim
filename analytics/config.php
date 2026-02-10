<?php
/**
 * BuyGoods Analytics - Configuration File
 * Edit these settings to match your hosting
 */

// Database Configuration - NEW HOSTING
define('DB_HOST', 'localhost');
define('DB_NAME', 'u373133718_analyticsdb');
define('DB_USER', 'u373133718_analyticsuser');
define('DB_PASS', 'Ali547$$$');

// Timezone (adjust to your timezone)
date_default_timezone_set('America/New_York');

// IPQualityScore API Keys (for fraud detection)
// Get your free API key at: https://www.ipqualityscore.com/create-account
// Primary account
define('IPQS_API_KEY', 'tEViP4U22CdbrwKLreXxYfU7X3Ot66X9');
// Secondary/backup account
define('IPQS_API_KEY_2', '46hzjwtx9TllJaZWZrLv5a0ZnBC29Gnd');

// Fraud Alert Email Configuration
define('FRAUD_ALERT_EMAIL', 'hummafaran.pilot@gmail.com');
define('FRAUD_ALERT_FROM', 'hummafaran.pilot@gmail.com');
define('FRAUD_ALERT_FROM_NAME', 'BuyGoods Fraud Alert');

// SMTP Configuration - Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'hummafaran.pilot@gmail.com');
define('SMTP_PASS', 'aivjmpocokbmgket');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS - Allow requests from any origin (adjust for security in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
