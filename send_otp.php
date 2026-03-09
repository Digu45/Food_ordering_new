<?php
session_start();
header('Content-Type: application/json');

$mobile = preg_replace('/\D/', '', $_POST['mobile'] ?? '');

if (strlen($mobile) !== 10) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid mobile number']);
    exit;
}

$otp = rand(100000, 999999);
$_SESSION['otp']        = $otp;
$_SESSION['otp_mobile'] = $mobile;
$_SESSION['otp_time']   = time();

// ── Fast2SMS Quick SMS route (no website verification needed) ──
$apiKey  = 'SZXD6GrHo0nKsNwhJaxCE8MAWlRV1ymgec7FO4qbYdUtTi35zu3yvpJ7b4ILKNEQk2R0sia85BVDoOXC';
$message = "Your OTP for " . $_SERVER['HTTP_HOST'] . " is $otp. Valid for 5 minutes. Do not share with anyone.";

$body = json_encode([
    'route'   => 'q',
    'message' => $message,
    'numbers' => $mobile,
]);

$ch = curl_init('https://www.fast2sms.com/dev/bulkV2');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        'authorization: ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
curl_close($ch);

error_log("Fast2SMS response: " . $response . " | curl_err: " . $err);

$res  = json_decode($response, true);
$sent = (!$err && isset($res['return']) && $res['return'] == true);

if ($sent) {
    echo json_encode(['status' => 'success', 'sms' => true]);
} else {
    echo json_encode(['status' => 'success', 'sms' => false, 'otp' => $otp, 'debug' => $response]);
}
exit;