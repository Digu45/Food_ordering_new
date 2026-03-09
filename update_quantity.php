<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');
$device_id = $_SESSION['unique_device_id'] ?? '';
$id  = $_POST['menu_id']  ?? null;
$qty = (int)($_POST['quantity'] ?? 0);
if (!$id || !$device_id) { echo json_encode(['success'=>false]); exit; }
if ($qty < 1) {
    $pdo->prepare("DELETE FROM menu_items WHERE MenuID=? AND DeviceID=?")->execute([$id, $device_id]);
} else {
    $pdo->prepare("UPDATE menu_items SET Quantity=?, Amount=Rate*? WHERE MenuID=? AND DeviceID=?")->execute([$qty,$qty,$id,$device_id]);
}
echo json_encode(['success'=>true]);
