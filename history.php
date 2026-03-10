<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (empty($_SESSION['mobile_verified'])) { header('Location: login.php'); exit; }

$mobile = $_SESSION['mobile'] ?? '';

$orders = $pdo->prepare("
    SELECT p.order_group_id AS gid,
           p.order_type, p.area, p.address, p.landmark,
           p.grand_amt, p.status, p.payment_method, p.payment_status, p.created_at,
           GROUP_CONCAT(CONCAT(p.qty,'× ', COALESCE(m.MenuName,'Item')) ORDER BY p.OrderId SEPARATOR ', ') AS items
    FROM placeorder p
    LEFT JOIN menu_master m ON m.MenuId = p.product_id
    WHERE p.mobile_no = ?
    GROUP BY p.order_group_id
    ORDER BY p.created_at DESC
");
$orders->execute([$mobile]);
$orders = $orders->fetchAll();

$statusColor = [
    'Pending'   => ['bg'=>'#fef3c7','color'=>'#92400e'],
    'Preparing' => ['bg'=>'#dbeafe','color'=>'#1e40af'],
    'Ready'     => ['bg'=>'#d1fae5','color'=>'#065f46'],
    'Completed' => ['bg'=>'#f0fdf4','color'=>'#166534'],
    'Cancelled' => ['bg'=>'#fee2e2','color'=>'#991b1b'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Orders | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:#f1f5f9; min-height:100vh; padding-bottom:80px; }
</style>
</head>
<body>

<!-- Header -->
<div style="background:linear-gradient(135deg,#e65c00,#c0392b);padding:16px;position:sticky;top:0;z-index:40;">
  <div style="display:flex;align-items:center;gap:12px;max-width:560px;margin:0 auto;">
    <a href="home.php" style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;">
      <i class="fas fa-arrow-left" style="color:#fff;font-size:13px;"></i>
    </a>
    <div>
      <div style="font-family:'Playfair Display',serif;font-size:19px;font-weight:700;color:#fff;">My Orders</div>
      <div style="font-size:12px;color:rgba(255,255,255,.75);">+91 <?= htmlspecialchars($mobile) ?></div>
    </div>
  </div>
</div>

<div style="max-width:560px;margin:0 auto;padding:16px;">

<?php if (empty($orders)): ?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:64px;margin-bottom:16px;">📋</div>
  <div style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#111;margin-bottom:8px;">No orders yet</div>
  <p style="color:#9ca3af;margin-bottom:24px;">Your order history will appear here</p>
  <a href="home.php" style="display:inline-block;background:#e65c00;color:#fff;padding:12px 28px;border-radius:14px;font-weight:700;text-decoration:none;">Order Now</a>
</div>
<?php else: ?>
<?php foreach ($orders as $o):
  $sc = $statusColor[$o['status']] ?? ['bg'=>'#f3f4f6','color'=>'#374151'];
  $isTakeaway = $o['order_type'] === 'Takeaway';
  $dt = date('d M Y, h:i A', strtotime($o['created_at']));
?>
<div style="background:#fff;border-radius:18px;margin-bottom:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">
  <!-- Top row -->
  <div style="padding:14px 16px;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
    <div style="flex:1;min-width:0;">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
        <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
          <?= $o['status'] ?>
        </span>
        <span style="font-size:11px;color:#9ca3af;font-family:monospace;"><?= htmlspecialchars($o['gid']) ?></span>
      </div>
      <div style="font-size:12px;color:#9ca3af;margin-bottom:6px;"><?= $dt ?></div>
      <div style="font-size:13px;color:#374151;"><?= htmlspecialchars($o['items'] ?? '') ?></div>
    </div>
    <div style="text-align:right;flex-shrink:0;">
      <div style="font-size:20px;font-weight:800;color:#e65c00;">₹<?= number_format($o['grand_amt'],2) ?></div>
      <div style="font-size:11px;font-weight:600;color:<?= $o['payment_status']==='Paid'?'#16a34a':'#d97706' ?>;">
        <?= $o['payment_status'] === 'Paid' ? '✅ Paid' : '⏳ Unpaid' ?>
      </div>
    </div>
  </div>
  <!-- Order type row -->
  <div style="padding:10px 16px;background:#fafafa;display:flex;align-items:center;gap:8px;">
    <span style="font-size:16px;"><?= $isTakeaway ? '🛍️' : '🍽️' ?></span>
    <span style="font-size:12px;font-weight:600;color:#374151;"><?= $o['order_type'] ?></span>
    <?php if ($isTakeaway && $o['area']): ?>
    <span style="font-size:12px;color:#9ca3af;">· <?= htmlspecialchars($o['area']) ?></span>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:#9ca3af;"><?= $o['payment_method'] ?></span>
  </div>
  <?php if ($isTakeaway && $o['address']): ?>
  <div style="padding:8px 16px 12px;background:#fafafa;border-top:1px solid #f3f4f6;">
    <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($o['address']) ?><?= $o['landmark']?' · Near: '.htmlspecialchars($o['landmark']):'' ?></div>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div>

<!-- Bottom nav -->
<div style="position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #e5e7eb;display:flex;z-index:40;">
  <?php foreach ([['home.php','fa-home','Home'],['cart.php','fa-shopping-bag','Cart'],['history.php','fa-receipt','Orders'],['logout.php','fa-sign-out-alt','Logout']] as [$href,$icon,$label]): ?>
  <a href="<?= $href ?>" style="flex:1;display:flex;flex-direction:column;align-items:center;padding:10px 0;text-decoration:none;color:<?= $href==='history.php'?'#e65c00':'#9ca3af' ?>;">
    <i class="fas <?= $icon ?>" style="font-size:18px;margin-bottom:3px;"></i>
    <span style="font-size:10px;font-weight:600;"><?= $label ?></span>
  </a>
  <?php endforeach; ?>
</div>

</body>
</html>
