<?php
/**
 * Unified Analytics Dashboard - Configuration File
 * Combines Shaving Analytics + BuyGoods Analytics
 */

// ================================================================
// SHAVING ANALYTICS DATABASE - NEW HOSTING
// ================================================================
define('DB_SHAVING_HOST', 'localhost');
define('DB_SHAVING_NAME', 'u373133718_shavingdb');
define('DB_SHAVING_USER', 'u373133718_shavingdbuser');
define('DB_SHAVING_PASS', 'Ali547$$$');

// ================================================================
// BUYGOODS ANALYTICS DATABASE - NEW HOSTING
// ================================================================
// Analytics uses separate folder with separate config
define('DB_BUYGOODS_HOST', 'localhost');
define('DB_BUYGOODS_NAME', 'u373133718_shavingdb');
define('DB_BUYGOODS_USER', 'u373133718_shavingdbuser');
define('DB_BUYGOODS_PASS', 'Ali547$$$');

// Legacy defines for backward compatibility
define('DB_HOST', 'localhost');
define('DB_NAME', 'u373133718_shavingdb');
define('DB_USER', 'u373133718_shavingdbuser');
define('DB_PASS', 'Ali547$$$');

// ================================================================
// IPQUALITYSCORE API (Fraud Detection)
// ================================================================
define('IPQS_API_KEY', 'tEViP4U22CdbrwKLreXxYfU7X3Ot66X9');
define('IPQS_API_KEY_2', '46hzjwtx9TllJaZWZrLv5a0ZnBC29Gnd');

// ================================================================
// FRAUD ALERT EMAIL CONFIGURATION
// ================================================================
define('FRAUD_ALERT_EMAIL', 'hummafaran.pilot@gmail.com');
define('FRAUD_ALERT_FROM', 'hummafaran.pilot@gmail.com');
define('FRAUD_ALERT_FROM_NAME', 'BuyGoods Fraud Alert');

// ================================================================
// SMTP CONFIGURATION
// ================================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'hummafaran.pilot@gmail.com');
define('SMTP_PASS', 'aivjmpocokbmgket');

// ================================================================
// TIMEZONE SETTINGS
// ================================================================
date_default_timezone_set('America/New_York');

// ================================================================
// ERROR REPORTING
// ================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ================================================================
// CORS HEADERS
// ================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ================================================================
// DATABASE CONNECTION FUNCTIONS
// ================================================================

/**
 * Get Shaving Analytics Database Connection
 */
function getShavingDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_SHAVING_HOST . ";dbname=" . DB_SHAVING_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_SHAVING_USER, DB_SHAVING_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error' => 'Shaving DB connection failed',
                'details' => $e->getMessage()
            ]));
        }
    }

    return $pdo;
}

/**
 * Get BuyGoods Analytics Database Connection
 */
function getBuyGoodsDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_BUYGOODS_HOST . ";dbname=" . DB_BUYGOODS_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_BUYGOODS_USER, DB_BUYGOODS_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error' => 'BuyGoods DB connection failed',
                'details' => $e->getMessage()
            ]));
        }
    }

    return $pdo;
}

/**
 * Legacy function for backward compatibility
 * Returns Shaving DB by default
 */
function getDB() {
    return getShavingDB();
}
