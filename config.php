<?php
include_once __DIR__ . '/constant.php';

// ── Database ───────────────────────────────────────────────
if($project_mode == "localhost"){
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'restaurant_db2');
    define('DB_PORT', '3306');
    define('DB_TYPE', 'mysql');
}
else{
    define('DB_HOST', 'dpg-d6nm7vkr85hc73frec70-a.oregon-postgres.render.com');
    define('DB_USER', 'restaurant_user');
    define('DB_PASS', 'FYVBPg1Aq3QEK3aFFKDC0Nl3urn1aNzs');
    define('DB_NAME', 'restaurant_db_jhzw');
    define('DB_PORT', '5432');
    define('DB_TYPE', 'pgsql');
}

// ── Restaurant Info ────────────────────────────────────────
define('RESTAURANT_NAME',    'Digus Restaurant');
define('RESTAURANT_ADDRESS', 'CBS Stand, Kolhapur');
define('RESTAURANT_PHONE',   '9309475959');
define('UPI_ID',             'diguvapilkar45-1@oksbi');
define('UPI_NAME',           'Digus Restaurant');

// ── Tax Rates (%) ──────────────────────────────────────────
define('CGST_RATE', 2.5);
define('SGST_RATE', 2.5);

// ── SMS OTP ────────────────────────────────────────────────
define('OTP_MODE',        'sms');
define('SMS_AUTH_KEY',    '359180AQrwQK5INrDt607e889fP1');
define('SMS_SENDER_ID',   'RNSERP');
define('SMS_ROUTE',       4);
define('SMS_TEMPLATE_ID', '1207169703350434137');
define('SMS_API_URL',     'https://otpsms.vision360solutions.in/api/sendhttp.php');
?>