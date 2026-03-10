<?php
require_once 'auth.php';

$gid    = $_POST['gid']    ?? '';
$action = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'dashboard';

if (!$gid) { header('Location: dashboard.php'); exit; }

if ($action === 'status') {
    $status = $_POST['status'] ?? 'Pending';
    $pdo->prepare("UPDATE placeorder SET status=? WHERE order_group_id=?")->execute([$status, $gid]);
}

if ($action === 'mark_paid') {
    $pdo->prepare("UPDATE placeorder SET payment_status='Paid' WHERE order_group_id=?")->execute([$gid]);
}

header('Location: ' . ($redirect === 'orders' ? 'orders.php' : 'dashboard.php'));
exit;
