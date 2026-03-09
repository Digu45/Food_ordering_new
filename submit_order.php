<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');

$device_id = $_SESSION['unique_device_id'] ?? '';
$mobile    = $_SESSION['mobile'] ?? '';
$d = json_decode(file_get_contents('php://input'), true);
if (!$d || !$device_id) { echo json_encode(['success'=>false]); exit; }

try {
    $pdo->prepare("DELETE FROM menu_items WHERE MenuID=? AND DeviceID=?")->execute([$d['MenuId'], $device_id]);
    $pdo->prepare("INSERT INTO menu_items (MenuID,MenuName,MenuImageUrl,Description,Rate,Quantity,Amount,MobileNo,DeviceID) VALUES(?,?,?,?,?,?,?,?,?)")
        ->execute([$d['MenuId'],$d['MenuName'],$d['MenuImageUrl']??'',$d['Description']??'',$d['Rate'],$d['Quantity'],$d['Amount'],$mobile,$device_id]);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE DeviceID=?");
    $cnt->execute([$device_id]);
    echo json_encode(['success'=>true,'cart_count'=>(int)$cnt->fetchColumn()]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success'=>false]);
}
