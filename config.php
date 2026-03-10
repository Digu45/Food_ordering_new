<?php
include_once __DIR__ . '/constant.php';

// ── Database ───────────────────────────────────────────────
if($project_mode == "localhost"){
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'restaurant_db2');
    define('DB_PORT', '3306');
}
else{
    define('DB_HOST', '6nognd.h.filess.io');
    define('DB_USER', 'restaurant_db2_solvegrade');
    define('DB_PASS', '9cdc831bf821e1d017ab5e405bd3da6e534116f3'); //  password from filess io.com
    define('DB_NAME', 'restaurant_db2_solvegrade');
    define('DB_PORT', '3307');
}

// ── Restaurant Info ────────────────────────────────────────
define('RESTAURANT_NAME',    'Digus Restaurant');
define('RESTAURANT_ADDRESS', 'Rajarampuri Lane no - 9, Kolhapur');
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