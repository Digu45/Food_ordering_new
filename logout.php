<?php
session_start();
// Clear only user auth — keep device_id and cart intact
unset(
    $_SESSION['mobile'],
    $_SESSION['name'],
    $_SESSION['mobile_verified'],
    $_SESSION['otp'],
    $_SESSION['otp_time'],
    $_SESSION['otp_mobile']
);
// ✅ Go to splash page after logout
header('Location: splash.php'); exit;
