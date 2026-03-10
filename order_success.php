<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['mobile_verified'])) { header('Location: login.php'); exit; }
$o = $_SESSION['last_order'] ?? null;
if (!$o) { header('Location: home.php'); exit; }
$isTakeaway = ($o['order_type'] === 'Takeaway');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Order Placed | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:#f1f5f9; min-height:100vh; padding-bottom:32px; }
@keyframes pop { 0%{transform:scale(.5);opacity:0} 70%{transform:scale(1.15)} 100%{transform:scale(1);opacity:1} }
.pop { animation:pop .5s ease both; }
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.fu1{animation:fadeUp .5s .1s both} .fu2{animation:fadeUp .5s .25s both}
.fu3{animation:fadeUp .5s .4s both} .fu4{animation:fadeUp .5s .55s both}
.step { display:flex; align-items:center; gap:12px; }
.step-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.step-line { width:2px; height:24px; background:#e5e7eb; margin-left:15px; }
</style>
</head>
<body>

<div style="max-width:480px;margin:0 auto;padding:24px 16px;">

  <!-- Success icon -->
  <div class="pop" style="text-align:center;margin-bottom:24px;margin-top:20px;">
    <div style="width:90px;height:90px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 12px 40px rgba(34,197,94,.35);">
      <i class="fas fa-check" style="color:#fff;font-size:38px;"></i>
    </div>
  </div>

  <div class="fu1" style="text-align:center;margin-bottom:24px;">
    <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#111;margin-bottom:6px;">Order Placed!</h1>
    <p style="color:#6b7280;font-size:14px;">Thank you, <?= htmlspecialchars($_SESSION['name'] ?? 'Guest') ?>! 🎉</p>
    <div style="display:inline-block;background:#fff7ed;border:2px solid #fed7aa;border-radius:10px;padding:6px 14px;margin-top:10px;">
      <span style="font-size:12px;color:#9a3412;font-weight:600;">Order ID: </span>
      <span style="font-size:12px;font-family:monospace;font-weight:700;color:#c2410c;"><?= htmlspecialchars($o['gid']) ?></span>
    </div>
  </div>

  <!-- Order type + amount card -->
  <div class="fu2" style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="font-size:28px;"><?= $isTakeaway ? '🛍️' : '🍽️' ?></div>
        <div>
          <div style="font-weight:700;font-size:15px;color:#111;"><?= $o['order_type'] ?></div>
          <?php if ($isTakeaway && $o['area']): ?>
          <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($o['area']) ?></div>
          <?php elseif ($isTakeaway): ?>
          <div style="font-size:12px;color:#6b7280;">Pickup from restaurant</div>
          <?php else: ?>
          <div style="font-size:12px;color:#6b7280;">We'll serve you at your table</div>
          <?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:22px;font-weight:800;color:#e65c00;">₹<?= $o['total'] ?></div>
        <div style="font-size:11px;font-weight:600;color:<?= $o['pay_status']==='Paid'?'#16a34a':'#d97706' ?>;">
          <?= $o['pay_status'] === 'Paid' ? '✅ Paid' : '⏳ Pay at counter' ?>
        </div>
      </div>
    </div>
    <?php if ($isTakeaway && $o['address']): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6;">
      <div style="font-size:12px;color:#9ca3af;font-weight:600;margin-bottom:4px;">Address</div>
      <div style="font-size:13px;color:#374151;"><?= htmlspecialchars($o['address']) ?></div>
      <?php if ($o['landmark']): ?><div style="font-size:12px;color:#9ca3af;margin-top:2px;">Near: <?= htmlspecialchars($o['landmark']) ?></div><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Items -->
  <div class="fu2" style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:12px;">Items Ordered</div>
    <?php foreach ($o['items'] as $it): ?>
    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;">
      <span style="color:#374151;"><?= htmlspecialchars($it['name']) ?> <span style="color:#9ca3af;">×<?= $it['qty'] ?></span></span>
      <span style="font-weight:600;color:#111;">₹<?= number_format($it['rate']*$it['qty'],2) ?></span>
    </div>
    <?php endforeach; ?>
    <div style="border-top:1px solid #f3f4f6;padding-top:10px;margin-top:4px;display:flex;justify-content:space-between;font-weight:700;font-size:15px;">
      <span>Total</span><span style="color:#e65c00;">₹<?= $o['total'] ?></span>
    </div>
  </div>

  <!-- Order tracker -->
  <div class="fu3" style="background:#fff;border-radius:18px;padding:16px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:16px;"><i class="fas fa-clock" style="color:#e65c00;margin-right:7px;"></i>Order Status</div>
    <?php
    $steps = [
      ['Order Placed',  'Your order has been received',   true,  'fa-check-circle',  '#22c55e'],
      ['Preparing',     'Kitchen is working on your order', false, 'fa-fire',         '#f97316'],
      [$isTakeaway ? 'Ready for Pickup' : 'Served', $isTakeaway ? 'Come collect your order' : 'Enjoy your meal!', false, 'fa-star', '#8b5cf6'],
    ];
    foreach ($steps as $i => [$title,$sub,$done,$icon,$col]):
    ?>
    <div class="step" style="margin-bottom:<?= $i<count($steps)-1?'0':'0' ?>;">
      <div>
        <div class="step-dot" style="background:<?= $done?$col:'#f3f4f6' ?>;color:<?= $done?'#fff':'#9ca3af' ?>;">
          <i class="fas <?= $icon ?>"></i>
        </div>
        <?php if ($i < count($steps)-1): ?><div class="step-line"></div><?php endif; ?>
      </div>
      <div style="padding-bottom:<?= $i<count($steps)-1?'24px':'0' ?>;">
        <div style="font-weight:600;font-size:13px;color:<?= $done?'#111':'#9ca3af' ?>;"><?= $title ?></div>
        <div style="font-size:12px;color:#9ca3af;"><?= $sub ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Actions -->
  <div class="fu4" style="display:flex;flex-direction:column;gap:10px;">
    <a href="home.php" style="display:block;padding:15px;border-radius:14px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-weight:700;font-size:15px;text-align:center;text-decoration:none;">
      <i class="fas fa-utensils" style="margin-right:8px;"></i>Order More
    </a>
    <a href="history.php" style="display:block;padding:13px;border-radius:14px;border:2px solid #e5e7eb;color:#374151;font-weight:600;font-size:14px;text-align:center;text-decoration:none;">
      <i class="fas fa-history" style="margin-right:8px;"></i>View All Orders
    </a>
  </div>

</div>
</body>
</html>
