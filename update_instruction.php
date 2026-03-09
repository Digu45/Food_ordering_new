<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');
$device_id = $_SESSION['unique_device_id'] ?? '';
$id   = $_POST['menu_id']     ?? null;
$note = $_POST['instruction'] ?? '';
if (!$id || !$device_id) { echo json_encode(['success'=>false]); exit; }
$pdo->prepare("UPDATE menu_items SET Instructions=? WHERE MenuID=? AND DeviceID=?")->execute([$note,$id,$device_id]);
echo json_encode(['success'=>true]);
