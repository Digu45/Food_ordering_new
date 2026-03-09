<?php
session_start();
require_once 'connection.php';
header('Content-Type: application/json');
$device_id = $_SESSION['unique_device_id'] ?? '';
$d = json_decode(file_get_contents('php://input'), true);
if (!$d || !$device_id) { echo json_encode(['success'=>false]); exit; }
$pdo->prepare("DELETE FROM menu_items WHERE MenuID=? AND DeviceID=?")->execute([$d['MenuID'], $device_id]);
$c = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE DeviceID=?");
$c->execute([$device_id]);
echo json_encode(['success'=>true,'cart_count'=>(int)$c->fetchColumn()]);
