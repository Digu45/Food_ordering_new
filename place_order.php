<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (empty($_SESSION['mobile_verified'])) { header('Location: login.php'); exit; }

$device_id  = $_SESSION['unique_device_id'] ?? '';
$mobile     = $_SESSION['mobile']           ?? '';
$name       = $_SESSION['name']             ?? 'Guest';

if (!$device_id) { header('Location: cart.php'); exit; }

$s = $pdo->prepare("SELECT * FROM menu_items WHERE DeviceID=?");
$s->execute([$device_id]);
$items = $s->fetchAll();
if (empty($items)) { header('Location: cart.php'); exit; }

// Payment info from GET
$mode       = $_GET['mode']     ?? 'COD';
$utr        = $_GET['utr']      ?? '';
$order_type = $_GET['type']     ?? 'Dine-in';
$area       = $_GET['area']     ?? '';
$address    = $_GET['address']  ?? '';
$landmark   = $_GET['landmark'] ?? '';

$pay_status = ($mode === 'UPI') ? 'Paid' : (($mode === 'CARD') ? 'Paid' : 'Pending');
$txn_id     = ($mode === 'UPI') ? $utr   : (($mode === 'CARD') ? 'CARD'.time() : null);

// Totals
$sub   = array_sum(array_map(fn($r) => $r['Rate'] * $r['Quantity'], $items));
$cgst  = round($sub * CGST_RATE / 100, 2);
$sgst  = round($sub * SGST_RATE / 100, 2);
$total = round($sub + $cgst + $sgst);
$roff  = $total - ($sub + $cgst + $sgst);

$gid = 'ORD' . date('YmdHis') . strtoupper(substr(uniqid(), -4));

$ins = $pdo->prepare("
    INSERT INTO placeorder
    (mobile_no, customer_name, product_id, qty, rate, amount,
     sub_total, total_tax_amt, rounded_amt, grand_amt,
     order_type, area, address, landmark,
     Instructions, payment_method, payment_status, transaction_id, order_group_id)
    VALUES (?,?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?)
");

foreach ($items as $r) {
    $amt = $r['Rate'] * $r['Quantity'];
    $ins->execute([
        $mobile, $name, $r['MenuID'], $r['Quantity'], $r['Rate'], $amt,
        $sub, $cgst + $sgst, $roff, $total,
        $order_type, $area, $address, $landmark,
        $r['Instructions'] ?? '', $mode, $pay_status, $txn_id, $gid
    ]);
}

// Clear cart
$pdo->prepare("DELETE FROM menu_items WHERE DeviceID=?")->execute([$device_id]);

$_SESSION['last_order'] = [
    'gid'        => $gid,
    'order_type' => $order_type,
    'area'       => $area,
    'address'    => $address,
    'landmark'   => $landmark,
    'total'      => $total,
    'mode'       => $mode,
    'pay_status' => $pay_status,
    'items'      => array_map(fn($r) => ['name'=>$r['MenuName'],'qty'=>$r['Quantity'],'rate'=>$r['Rate']], $items),
];

header('Location: order_success.php'); exit;
