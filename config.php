<?php
// ── Database ───────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_db2');

// ── Restaurant Info ────────────────────────────────────────
define('RESTAURANT_NAME',    'Digus Restaurant');
define('RESTAURANT_ADDRESS', 'CBS Stand, Kolhapur');
define('RESTAURANT_PHONE',   '9309475959');
define('UPI_ID',             'diguvapilkar45-1@oksbi');  // ← change to your real UPI ID
define('UPI_NAME',           'Digus Restaurant');  // ← change to your name

// ── Tax Rates (%) ──────────────────────────────────────────
define('CGST_RATE', 2.5);
define('SGST_RATE', 2.5);

// ── SMS OTP ────────────────────────────────────────────────
// Change OTP_MODE to 'sms' to send real SMS in production
define('OTP_MODE',        'sms');
define('SMS_AUTH_KEY',    '359180AQrwQK5INrDt607e889fP1');
define('SMS_SENDER_ID',   'RNSERP');
define('SMS_ROUTE',       4);
define('SMS_TEMPLATE_ID', '1207169703350434137');
define('SMS_API_URL',     'https://otpsms.vision360solutions.in/api/sendhttp.php');
