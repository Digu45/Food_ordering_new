<?php require_once 'auth.php'; ?>
<?php
$status_filter = $_GET['status'] ?? '';
$date_filter   = $_GET['date']   ?? '';
$search        = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($status_filter) { $where[] = "p.status=?"; $params[] = $status_filter; }
if ($date_filter)   { $where[] = "DATE(p.created_at)=?"; $params[] = $date_filter; }
if ($search)        { $where[] = "(p.customer_name LIKE ? OR p.mobile_no LIKE ? OR p.order_group_id LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql = "
    SELECT p.order_group_id AS gid, p.customer_name, p.mobile_no,
           p.order_type, p.area, p.address, p.landmark,
           p.grand_amt, p.status, p.payment_method, p.payment_status,
           p.transaction_id, p.created_at,
           GROUP_CONCAT(CONCAT(p.qty,'× ', COALESCE(m.MenuName,'Item')) ORDER BY p.OrderId SEPARATOR ', ') AS items
    FROM placeorder p
    LEFT JOIN menu_master m ON m.MenuId = p.product_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY p.order_group_id
    ORDER BY p.created_at DESC
";
$st = $pdo->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

$statusColors = [
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
<title>All Orders | <?= RESTAURANT_NAME ?> Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:#f1f5f9; }
.sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#1a0a00; z-index:100; }
.main { margin-left:220px; min-height:100vh; padding:24px; }
.nav-item { display:flex; align-items:center; gap:10px; padding:12px 20px; color:rgba(255,255,255,.6); text-decoration:none; font-size:14px; font-weight:600; transition:.2s; }
.nav-item:hover, .nav-item.on { background:rgba(255,255,255,.1); color:#f9a84d; border-left:3px solid #f9a84d; }
.badge { display:inline-block; padding:4px 12px; border-radius:999px; font-size:11px; font-weight:700; }
.inp { padding:10px 14px; border:2px solid #e5e7eb; border-radius:10px; font-size:13px; outline:none; background:#fff; }
.inp:focus { border-color:#e65c00; }
@media(max-width:768px) { .sidebar{display:none;} .main{margin-left:0;} }
</style>
</head>
<body>
<div class="sidebar">
  <div style="padding:20px;border-bottom:1px solid rgba(255,255,255,.1);">
    <div style="color:#f9a84d;font-size:18px;font-weight:800;"><?= RESTAURANT_NAME ?></div>
    <div style="color:rgba(255,255,255,.4);font-size:12px;">Admin Panel</div>
  </div>
  <nav style="padding:10px 0;">
    <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt" style="width:18px;"></i>Dashboard</a>
    <a href="orders.php"    class="nav-item on"><i class="fas fa-receipt" style="width:18px;"></i>All Orders</a>
    <a href="menu.php"      class="nav-item"><i class="fas fa-utensils" style="width:18px;"></i>Menu Items</a>
    <a href="logout.php"    class="nav-item"><i class="fas fa-sign-out-alt" style="width:18px;"></i>Logout</a>
  </nav>
</div>

<div class="main">
  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:800;color:#111;">All Orders</h1>
    <p style="color:#9ca3af;font-size:13px;"><?= count($orders) ?> orders found</p>
  </div>

  <!-- Filters -->
  <form method="GET" style="background:#fff;border-radius:16px;padding:16px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
    <div>
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Search</label>
      <input class="inp" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name, mobile, order ID..."/>
    </div>
    <div>
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Status</label>
      <select class="inp" name="status">
        <option value="">All Status</option>
        <?php foreach (['Pending','Preparing','Ready','Completed','Cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Date</label>
      <input class="inp" type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>"/>
    </div>
    <button type="submit" style="padding:10px 20px;border:none;border-radius:10px;background:#e65c00;color:#fff;font-size:13px;font-weight:700;cursor:pointer;">
      <i class="fas fa-search" style="margin-right:6px;"></i>Filter
    </button>
    <a href="orders.php" style="padding:10px 16px;border:2px solid #e5e7eb;border-radius:10px;color:#374151;font-size:13px;font-weight:600;text-decoration:none;">Clear</a>
  </form>

  <!-- Orders -->
  <?php if (empty($orders)): ?>
  <div style="text-align:center;padding:60px;background:#fff;border-radius:18px;">
    <div style="font-size:48px;margin-bottom:12px;">📋</div>
    <div style="font-size:16px;font-weight:700;color:#111;">No orders found</div>
  </div>
  <?php else: ?>
  <div style="background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
          <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Order ID</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Customer</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Items</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Type</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Amount</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Payment</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Status</th>
            <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
            $sc = $statusColors[$o['status']] ?? ['bg'=>'#f3f4f6','color'=>'#374151'];
            $isTakeaway = $o['order_type'] === 'Takeaway';
          ?>
          <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:12px 16px;">
              <div style="font-family:monospace;font-size:11px;color:#6b7280;"><?= htmlspecialchars($o['gid']) ?></div>
              <div style="font-size:11px;color:#9ca3af;"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></div>
            </td>
            <td style="padding:12px 16px;">
              <div style="font-weight:600;color:#111;"><?= htmlspecialchars($o['customer_name']) ?></div>
              <div style="color:#9ca3af;"><?= $o['mobile_no'] ?></div>
            </td>
            <td style="padding:12px 16px;max-width:200px;">
              <div style="color:#374151;"><?= htmlspecialchars($o['items']) ?></div>
            </td>
            <td style="padding:12px 16px;">
              <span style="font-size:12px;"><?= $isTakeaway ? '🛍️ Takeaway' : '🍽️ Dine-in' ?></span>
              <?php if ($isTakeaway && $o['area']): ?>
              <div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?= htmlspecialchars($o['area']) ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;">
              <div style="font-weight:700;color:#e65c00;">₹<?= number_format($o['grand_amt'],2) ?></div>
              <div style="font-size:11px;color:<?= $o['payment_status']==='Paid'?'#16a34a':'#d97706' ?>;font-weight:600;"><?= $o['payment_status'] ?></div>
            </td>
            <td style="padding:12px 16px;">
              <div style="font-size:12px;color:#9ca3af;"><?= $o['payment_method'] ?></div>
              <?php if ($o['transaction_id']): ?>
              <div style="font-size:11px;font-family:monospace;color:#9ca3af;"><?= htmlspecialchars($o['transaction_id']) ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;">
              <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;"><?= $o['status'] ?></span>
            </td>
            <td style="padding:12px 16px;">
              <form method="POST" action="update_order.php" style="display:flex;gap:5px;">
                <input type="hidden" name="gid" value="<?= htmlspecialchars($o['gid']) ?>"/>
                <input type="hidden" name="redirect" value="orders"/>
                <select name="status" style="padding:6px 8px;border:2px solid #e5e7eb;border-radius:8px;font-size:12px;font-weight:600;">
                  <?php foreach (['Pending','Preparing','Ready','Completed','Cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="status" style="padding:6px 10px;border:none;border-radius:8px;background:#e65c00;color:#fff;font-size:12px;font-weight:700;cursor:pointer;">✓</button>
                <?php if ($o['payment_status'] !== 'Paid'): ?>
                <button type="submit" name="action" value="mark_paid" style="padding:6px 10px;border:none;border-radius:8px;background:#22c55e;color:#fff;font-size:12px;font-weight:700;cursor:pointer;">₹</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
