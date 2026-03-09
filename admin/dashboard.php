<?php
require_once 'auth.php';
require_once '../config.php';
require_once '../connection.php';

// AJAX: update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    header('Content-Type: application/json');
    $gid    = trim($_POST['gid']    ?? '');
    $status = trim($_POST['status'] ?? '');
    $allowed = ['Pending','Preparing','Ready','Completed','Cancelled'];
    if ($gid && in_array($status, $allowed)) {
        $pdo->prepare("UPDATE placeorder SET status=? WHERE order_group_id=?")->execute([$status, $gid]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// Stats
$totalOrders  = $pdo->query("SELECT COUNT(DISTINCT order_group_id) FROM placeorder")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(DISTINCT order_group_id) FROM placeorder WHERE status='Pending'")->fetchColumn();
$todayRev     = $pdo->query("SELECT COALESCE(SUM(grand_amt),0) FROM placeorder WHERE DATE(created_at)=CURDATE() AND status='Completed'")->fetchColumn();
$menuCount    = $pdo->query("SELECT COUNT(*) FROM menu_master")->fetchColumn();

// Orders grouped
$orders = $pdo->query("
    SELECT p.order_group_id AS gid,
           p.mobile_no, p.customer_name,
           p.grand_amt, p.status,
           p.order_type, p.area, p.address, p.landmark,
           p.payment_method, p.payment_status, p.transaction_id,
           p.created_at,
           GROUP_CONCAT(CONCAT(p.qty,'x ', COALESCE(m.MenuName, CONCAT('Item#',p.product_id))) SEPARATOR ', ') AS summary
    FROM placeorder p
    LEFT JOIN menu_master m ON m.MenuId = p.product_id
    GROUP BY p.order_group_id
    ORDER BY p.created_at DESC
    LIMIT 300
")->fetchAll();

$statusColors = [
    'Pending'   => ['bg'=>'#fef3c7','color'=>'#92400e','border'=>'#fcd34d'],
    'Preparing' => ['bg'=>'#dbeafe','color'=>'#1e40af','border'=>'#93c5fd'],
    'Ready'     => ['bg'=>'#d1fae5','color'=>'#065f46','border'=>'#6ee7b7'],
    'Completed' => ['bg'=>'#f0fdf4','color'=>'#166534','border'=>'#86efac'],
    'Cancelled' => ['bg'=>'#fee2e2','color'=>'#991b1b','border'=>'#fca5a5'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; font-family:'DM Sans',sans-serif; }
body { background:#f1f5f9; min-height:100vh; }
.sbadge { display:inline-block; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:700; }
.s-Pending   { background:#fef3c7; color:#92400e; }
.s-Preparing { background:#dbeafe; color:#1e40af; }
.s-Ready     { background:#d1fae5; color:#065f46; }
.s-Completed { background:#f0fdf4; color:#166534; }
.s-Cancelled { background:#fee2e2; color:#991b1b; }
.fpill { padding:7px 16px; border-radius:999px; font-size:13px; font-weight:600; border:2px solid #e5e7eb; cursor:pointer; background:#fff; color:#6b7280; }
.fpill.on { background:#e65c00; color:#fff; border-color:#e65c00; }
.status-select {
  -webkit-appearance:none; appearance:none;
  padding:8px 32px 8px 12px; border-radius:10px;
  border:2px solid #e5e7eb; font-size:13px; font-weight:600;
  background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
  cursor:pointer; outline:none; min-width:140px;
}
.status-select.s-Pending   { background-color:#fef3c7; color:#92400e; border-color:#fcd34d; }
.status-select.s-Preparing { background-color:#dbeafe; color:#1e40af; border-color:#93c5fd; }
.status-select.s-Ready     { background-color:#d1fae5; color:#065f46; border-color:#6ee7b7; }
.status-select.s-Completed { background-color:#f0fdf4; color:#166534; border-color:#86efac; }
.status-select.s-Cancelled { background-color:#fee2e2; color:#991b1b; border-color:#fca5a5; }
.order-card { background:#fff; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:12px; overflow:hidden; }
</style>
</head>
<body>

<!-- Nav -->
<div style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:50;">
  <div style="max-width:1100px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:38px;height:38px;background:linear-gradient(135deg,#e65c00,#f9a84d);border-radius:12px;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-utensils" style="color:#fff;font-size:16px;"></i>
      </div>
      <div>
        <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:17px;color:#111;"><?= RESTAURANT_NAME ?></div>
        <div style="font-size:11px;color:#9ca3af;">Admin Panel</div>
      </div>
    </div>
    <div style="display:flex;gap:20px;align-items:center;">
      <a href="menu.php" style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;"><i class="fas fa-book-open" style="color:#e65c00;margin-right:5px;"></i>Menu</a>
      <a href="logout.php" style="font-size:13px;font-weight:600;color:#ef4444;text-decoration:none;"><i class="fas fa-sign-out-alt" style="margin-right:5px;"></i>Logout</a>
    </div>
  </div>
</div>

<div style="max-width:1100px;margin:0 auto;padding:20px;">

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px;">
  <?php foreach([
    ['Total Orders',$totalOrders,'fa-receipt','#3b82f6','#eff6ff'],
    ['Pending',$pendingCount,'fa-clock','#f59e0b','#fffbeb'],
    ["Today's Revenue",'₹'.number_format($todayRev),'fa-rupee-sign','#10b981','#ecfdf5'],
    ['Menu Items',$menuCount,'fa-hamburger','#8b5cf6','#f5f3ff'],
  ] as [$label,$val,$icon,$col,$bg]): ?>
  <div style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <span style="font-size:12px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;"><?= $label ?></span>
      <div style="width:34px;height:34px;background:<?= $bg ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;">
        <i class="fas <?= $icon ?>" style="color:<?= $col ?>;font-size:14px;"></i>
      </div>
    </div>
    <div style="font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:#111;"><?= $val ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters + search -->
<div style="background:#fff;border-radius:16px;padding:14px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05);">
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach(['all','Pending','Preparing','Ready','Completed','Cancelled'] as $f): ?>
    <button class="fpill <?= $f==='all'?'on':'' ?>" data-f="<?= $f ?>" onclick="filterOrders(this)">
      <?= $f==='all'?'All':$f ?>
    </button>
    <?php endforeach; ?>
  </div>
  <!-- Order type filter -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;padding-left:10px;border-left:2px solid #f3f4f6;">
    <button class="fpill on" data-t="all" onclick="filterType(this)">All Types</button>
    <button class="fpill" data-t="Dine-in" onclick="filterType(this)">🍽️ Dine-in</button>
    <button class="fpill" data-t="Takeaway" onclick="filterType(this)">🛍️ Takeaway</button>
  </div>
  <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
    <input type="text" id="searchBox" placeholder="Search name / mobile…"
           style="border:2px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;width:200px;outline:none;"
           oninput="searchOrders(this.value)"/>
    <button onclick="location.reload()" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;color:#6b7280;">
      <i class="fas fa-sync"></i>
    </button>
  </div>
</div>

<!-- Orders -->
<div id="orderList">
  <?php if (empty($orders)): ?>
  <div style="background:#fff;border-radius:16px;padding:60px 20px;text-align:center;">
    <div style="font-size:48px;margin-bottom:12px;">📋</div>
    <p style="color:#9ca3af;font-weight:600;">No orders yet</p>
  </div>
  <?php endif; ?>

  <?php foreach ($orders as $o):
    $st  = $o['status'] ?? 'Pending';
    $sc  = $statusColors[$st] ?? ['bg'=>'#f3f4f6','color'=>'#374151','border'=>'#e5e7eb'];
    $dt  = date('d M Y, h:i A', strtotime($o['created_at']));
    $isTakeaway = $o['order_type'] === 'Takeaway';
  ?>
  <div class="order-card"
       data-status="<?= $st ?>"
       data-type="<?= $o['order_type'] ?>"
       data-search="<?= strtolower($o['mobile_no'].' '.$o['customer_name']) ?>">

    <!-- Top -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:14px 16px 12px;border-bottom:1px solid #f3f4f6;gap:12px;flex-wrap:wrap;">
      <div style="flex:1;min-width:0;">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
          <span class="sbadge s-<?= $st ?>"><?= $st ?></span>
          <!-- Order type badge -->
          <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $isTakeaway?'#fffbeb':'#f0fdf4' ?>;color:<?= $isTakeaway?'#92400e':'#166534' ?>;">
            <?= $isTakeaway ? '🛍️ Takeaway' : '🍽️ Dine-in' ?>
          </span>
          <span style="font-family:monospace;font-size:11px;color:#9ca3af;"><?= htmlspecialchars($o['gid']) ?></span>
          <span style="font-size:11px;color:#9ca3af;"><?= $dt ?></span>
        </div>
        <p style="font-size:14px;font-weight:600;color:#111;margin-bottom:3px;">
          <i class="fas fa-user" style="color:#d1d5db;margin-right:5px;"></i><?= htmlspecialchars($o['customer_name'] ?: 'Guest') ?>
          &nbsp;·&nbsp;
          <i class="fas fa-phone" style="color:#d1d5db;margin-right:5px;"></i><?= htmlspecialchars($o['mobile_no']) ?>
        </p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:3px;"><?= htmlspecialchars($o['summary'] ?? '—') ?></p>
        <!-- Address for takeaway -->
        <?php if ($isTakeaway && ($o['area'] || $o['address'])): ?>
        <p style="font-size:12px;color:#9a3412;background:#fff7ed;display:inline-block;padding:3px 10px;border-radius:8px;margin-top:4px;">
          <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i><?= htmlspecialchars($o['area'] ?: '') ?><?= $o['address'] ? ' — '.htmlspecialchars($o['address']) : '' ?><?= $o['landmark'] ? ' (Near: '.htmlspecialchars($o['landmark']).')' : '' ?>
        </p>
        <?php endif; ?>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <p style="font-size:20px;font-weight:800;color:#e65c00;margin-bottom:3px;">₹<?= number_format($o['grand_amt'],2) ?></p>
        <p style="font-size:11px;font-weight:600;">
          <?php if ($o['payment_method']==='UPI'): ?>
            <span style="color:#8b5cf6;">📲 UPI</span>
          <?php elseif ($o['payment_method']==='CARD'): ?>
            <span style="color:#3b82f6;">💳 Card</span>
          <?php else: ?>
            <span style="color:#10b981;">💵 Cash</span>
          <?php endif; ?>
          &nbsp;·&nbsp;
          <span style="color:<?= $o['payment_status']==='Paid'?'#059669':'#d97706' ?>;"><?= $o['payment_status'] ?></span>
        </p>
        <?php if ($o['transaction_id']): ?>
        <p style="font-size:10px;color:#9ca3af;font-family:monospace;">UTR: <?= htmlspecialchars($o['transaction_id']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status update -->
    <div style="padding:12px 16px;display:flex;align-items:center;gap:12px;background:#fafafa;flex-wrap:wrap;">
      <span style="font-size:12px;font-weight:600;color:#9ca3af;white-space:nowrap;">Update Status:</span>
      <select class="status-select s-<?= $st ?>"
              data-gid="<?= htmlspecialchars($o['gid']) ?>"
              onchange="updateStatus(this)">
        <?php foreach(['Pending','Preparing','Ready','Completed','Cancelled'] as $sv): ?>
        <option value="<?= $sv ?>" <?= $st===$sv?'selected':'' ?>><?= $sv ?></option>
        <?php endforeach; ?>
      </select>
      <span class="save-indicator" style="display:none;font-size:12px;color:#e65c00;font-weight:600;">
        <i class="fas fa-spinner fa-spin"></i> Saving…
      </span>
      <span class="saved-indicator" style="display:none;font-size:12px;color:#10b981;font-weight:600;">
        <i class="fas fa-check"></i> Saved
      </span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:8px;">Auto-refreshes every 60s · <?= count($orders) ?> orders</p>
</div>

<script>
function updateStatus(sel) {
  const gid    = sel.dataset.gid;
  const status = sel.value;
  const row    = sel.closest('[style*="padding:12"]');
  const card   = sel.closest('.order-card');
  const saving = row.querySelector('.save-indicator');
  const saved  = row.querySelector('.saved-indicator');
  saving.style.display = 'inline-flex'; saved.style.display = 'none'; sel.disabled = true;
  $.post('dashboard.php', { update_status:1, gid:gid, status:status }, function(data) {
    sel.disabled = false; saving.style.display = 'none';
    if (data.ok) {
      sel.className = 'status-select s-' + status;
      const badge = card.querySelector('.sbadge');
      if (badge) { badge.textContent = status; badge.className = 'sbadge s-'+status; }
      card.dataset.status = status;
      saved.style.display = 'inline-flex';
      setTimeout(() => saved.style.display = 'none', 2000);
    } else { alert('Failed. Please try again.'); }
  }, 'json').fail(function() { sel.disabled=false; saving.style.display='none'; alert('Network error.'); });
}

let activeStatus = 'all', activeType = 'all';
function applyFilters() {
  document.querySelectorAll('.order-card').forEach(c => {
    const okStatus = activeStatus === 'all' || c.dataset.status === activeStatus;
    const okType   = activeType   === 'all' || c.dataset.type   === activeType;
    c.style.display = (okStatus && okType) ? '' : 'none';
  });
}
function filterOrders(btn) {
  document.querySelectorAll('[data-f]').forEach(b => b.classList.remove('on'));
  btn.classList.add('on'); activeStatus = btn.dataset.f; applyFilters();
}
function filterType(btn) {
  document.querySelectorAll('[data-t]').forEach(b => b.classList.remove('on'));
  btn.classList.add('on'); activeType = btn.dataset.t; applyFilters();
}
function searchOrders(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.order-card').forEach(c => {
    c.style.display = c.dataset.search.includes(q) ? '' : 'none';
  });
}
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
