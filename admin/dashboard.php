<?php
require_once 'auth.php';
require_once '../config.php';
require_once '../connection.php';

// ── SMS helper (Fast2SMS — no DLT template needed) ───────────────────────────
function sendSMS($mobile, $message)
{
  $apiKey = 'SZXD6GrHo0nKsNwhJaxCE8MAWlRV1ymgec7FO4qbYdUtTi35zu3yvpJ7b4ILKNEQk2R0sia85BVDoOXC';
  $body = json_encode([
    'route'   => 'q',
    'message' => $message,
    'numbers' => $mobile,
  ]);
  $ch = curl_init('https://www.fast2sms.com/dev/bulkV2');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
      'authorization: ' . $apiKey,
      'Content-Type: application/json',
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 15,
  ]);
  $response = curl_exec($ch);
  $err      = curl_error($ch);
  curl_close($ch);
  error_log("Fast2SMS to=$mobile | resp=$response | err=$err");
  return $response;
}

// ── AJAX: update order status ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  header('Content-Type: application/json');
  $gid     = trim($_POST['gid']    ?? '');
  $status  = trim($_POST['status'] ?? '');
  $allowed = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];
  if ($gid && in_array($status, $allowed)) {
    $pdo->prepare("UPDATE placeorder SET status=? WHERE order_group_id=?")->execute([$status, $gid]);

    // When status = Ready → send SMS to customer
    if ($status === 'Ready') {
      $r = $pdo->prepare("SELECT mobile_no, customer_name, order_type FROM placeorder WHERE order_group_id=? LIMIT 1");
      $r->execute([$gid]);
      $ord = $r->fetch();
      if ($ord) {
        if ($ord['order_type'] === 'Takeaway') {
          $sms = "Hi {$ord['customer_name']}! Your order at " . RESTAURANT_NAME . " is READY for pickup. Please come and collect it. Thank you!";
        } else {
          $sms = "Hi {$ord['customer_name']}! Your order at " . RESTAURANT_NAME . " is READY and will be served at your table shortly. Enjoy your meal!";
        }
        sendSMS($ord['mobile_no'], $sms);
      }
    }
    echo json_encode(['ok' => true, 'sms_sent' => $status === 'Ready']);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

// ── AJAX: mark payment as paid ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
  header('Content-Type: application/json');
  $gid = trim($_POST['gid'] ?? '');
  if ($gid) {
    $pdo->prepare("UPDATE placeorder SET payment_status='Paid' WHERE order_group_id=?")->execute([$gid]);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

// ── AJAX: new order polling (returns count of orders newer than a timestamp) ─
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['poll_new'])) {
  header('Content-Type: application/json');
  $since = $_POST['since'] ?? date('Y-m-d H:i:s');
  $cnt = $pdo->prepare("SELECT COUNT(DISTINCT order_group_id) FROM placeorder WHERE created_at > ?");
  $cnt->execute([$since]);
  echo json_encode(['new_count' => (int)$cnt->fetchColumn()]);
  exit;
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalOrders  = $pdo->query("SELECT COUNT(DISTINCT order_group_id) FROM placeorder")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(DISTINCT order_group_id) FROM placeorder WHERE status='Pending'")->fetchColumn();
$unpaidCount  = $pdo->query("SELECT COUNT(DISTINCT order_group_id) FROM placeorder WHERE payment_status='Pending' AND status NOT IN ('Cancelled')")->fetchColumn();
$todayRev     = $pdo->query("SELECT COALESCE(SUM(grand_amt),0) FROM placeorder WHERE DATE(created_at)=CURDATE() AND status!='Cancelled'")->fetchColumn();
$menuCount    = $pdo->query("SELECT COUNT(*) FROM menu_master")->fetchColumn();

// ── Orders ────────────────────────────────────────────────────────────────────
$orders = $pdo->query("
    SELECT p.order_group_id AS gid,
           MAX(p.mobile_no) AS mobile_no,
           MAX(p.customer_name) AS customer_name,
           MAX(p.grand_amt) AS grand_amt,
           MAX(p.status) AS status,
           MAX(p.order_type) AS order_type,
           MAX(p.table_id) AS table_id,
           MAX(p.area) AS area,
           MAX(p.address) AS address,
           MAX(p.landmark) AS landmark,
           MAX(p.payment_method) AS payment_method,
           MIN(p.payment_status) AS payment_status, -- MIN: 'Paid' < 'Pending' alphabetically
           MAX(p.transaction_id) AS transaction_id,
           MAX(p.created_at) AS created_at,
           GROUP_CONCAT(CONCAT(p.qty,'x ', COALESCE(m.MenuName,'Item')) ORDER BY p.OrderId SEPARATOR ', ') AS summary,
           GROUP_CONCAT(CONCAT('<b>',p.qty,'x</b> ', COALESCE(m.MenuName,'Item'), ' — ₹',p.rate,' each') ORDER BY p.OrderId SEPARATOR '<br>') AS itemsHtml
    FROM placeorder p
    LEFT JOIN menu_master m ON m.MenuId = p.product_id
    GROUP BY p.order_group_id
    ORDER BY
        FIELD(MAX(p.status),'Pending','Preparing','Ready','Completed','Cancelled'),
        MAX(p.created_at) DESC
    LIMIT 300
")->fetchAll();

$serverTime = date('Y-m-d H:i:s');

$statusColors = [
  'Pending'   => ['bg' => '#fef3c7', 'color' => '#92400e', 'border' => '#fcd34d'],
  'Preparing' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'border' => '#93c5fd'],
  'Ready'     => ['bg' => '#d1fae5', 'color' => '#065f46', 'border' => '#6ee7b7'],
  'Completed' => ['bg' => '#f0fdf4', 'color' => '#166534', 'border' => '#86efac'],
  'Cancelled' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'border' => '#fca5a5'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | <?= RESTAURANT_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'DM Sans', sans-serif;
    }

    body {
      background: #f1f5f9;
      min-height: 100vh;
    }

    /* Status badges */
    .sbadge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
    }

    .s-Pending {
      background: #fef3c7;
      color: #92400e;
    }

    .s-Preparing {
      background: #dbeafe;
      color: #1e40af;
    }

    .s-Ready {
      background: #d1fae5;
      color: #065f46;
    }

    .s-Completed {
      background: #f0fdf4;
      color: #166534;
    }

    .s-Cancelled {
      background: #fee2e2;
      color: #991b1b;
    }

    /* Filter pills */
    .fpill {
      padding: 7px 16px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      border: 2px solid #e5e7eb;
      cursor: pointer;
      background: #fff;
      color: #6b7280;
      white-space: nowrap;
    }

    .fpill.on {
      background: #e65c00;
      color: #fff;
      border-color: #e65c00;
    }

    /* Status dropdown */
    .status-select {
      -webkit-appearance: none;
      appearance: none;
      padding: 8px 32px 8px 12px;
      border-radius: 10px;
      border: 2px solid #e5e7eb;
      font-size: 13px;
      font-weight: 600;
      background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 10px center;
      cursor: pointer;
      outline: none;
      min-width: 150px;
    }

    .status-select.s-Pending {
      background-color: #fef3c7;
      color: #92400e;
      border-color: #fcd34d;
    }

    .status-select.s-Preparing {
      background-color: #dbeafe;
      color: #1e40af;
      border-color: #93c5fd;
    }

    .status-select.s-Ready {
      background-color: #d1fae5;
      color: #065f46;
      border-color: #6ee7b7;
    }

    .status-select.s-Completed {
      background-color: #f0fdf4;
      color: #166534;
      border-color: #86efac;
    }

    .status-select.s-Cancelled {
      background-color: #fee2e2;
      color: #991b1b;
      border-color: #fca5a5;
    }

    /* Order cards */
    .order-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
      margin-bottom: 14px;
      overflow: hidden;
      transition: .2s;
      border: 2px solid transparent;
    }

    .order-card:hover {
      box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
    }

    .order-card.is-pending {
      border-color: #fcd34d;
    }

    .order-card.is-new {
      animation: newPulse 1.5s ease-in-out 4;
    }

    @keyframes newPulse {

      0%,
      100% {
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06)
      }

      50% {
        box-shadow: 0 0 0 4px rgba(230, 92, 0, .3), 0 2px 8px rgba(0, 0, 0, .1)
      }
    }

    /* New order banner */
    #newOrderBanner {
      display: none;
      position: fixed;
      top: 70px;
      left: 50%;
      transform: translateX(-50%);
      background: #e65c00;
      color: #fff;
      padding: 12px 24px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 14px;
      z-index: 200;
      box-shadow: 0 8px 24px rgba(230, 92, 0, .4);
      cursor: pointer;
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      padding: 13px 20px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      z-index: 999;
      opacity: 0;
      transition: .3s;
      pointer-events: none;
      max-width: 320px;
    }

    .toast.show {
      opacity: 1;
    }

    .toast.success {
      background: #1a1a2e;
      color: #fff;
    }

    .toast.sms {
      background: #7c3aed;
      color: #fff;
    }

    .toast.error {
      background: #dc2626;
      color: #fff;
    }

    /* Pay button */
    .pay-btn {
      padding: 7px 16px;
      border: none;
      border-radius: 9px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      background: #22c55e;
      color: #fff;
      transition: .2s;
    }

    .pay-btn:hover {
      background: #16a34a;
    }

    .pay-btn:disabled {
      opacity: .6;
      cursor: not-allowed;
    }
  </style>
</head>

<body>

  <!-- New order notification banner -->
  <div id="newOrderBanner" onclick="location.reload()">
    🔔 New order received! Click to refresh
  </div>

  <!-- ── Top Nav ──────────────────────────────────────────────────────────────── -->
  <div style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:100;">
    <div style="max-width:1200px;margin:0 auto;padding:13px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#e65c00,#f9a84d);border-radius:12px;display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-utensils" style="color:#fff;font-size:16px;"></i>
        </div>
        <div>
          <div style="font-family:'Playfair Display',serif;font-weight:700;font-size:17px;color:#111;"><?= RESTAURANT_NAME ?></div>
          <div style="font-size:11px;color:#9ca3af;" id="liveClock">Admin Dashboard</div>
        </div>
      </div>
      <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
        <!-- Pending badge in nav -->
        <?php if ($pendingCount > 0): ?>
          <span style="background:#ef4444;color:#fff;border-radius:999px;padding:4px 12px;font-size:12px;font-weight:700;">
            🔔 <?= $pendingCount ?> Pending
          </span>
        <?php endif; ?>
        <?php if ($unpaidCount > 0): ?>
          <span style="background:#f59e0b;color:#fff;border-radius:999px;padding:4px 12px;font-size:12px;font-weight:700;">
            💰 <?= $unpaidCount ?> Unpaid
          </span>
        <?php endif; ?>
        <a href="menu.php" style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;"><i class="fas fa-book-open" style="color:#e65c00;margin-right:5px;"></i>Menu</a>
        <a href="../index.php" target="_blank" style="font-size:13px;font-weight:600;color:#374151;text-decoration:none;"><i class="fas fa-external-link-alt" style="color:#e65c00;margin-right:5px;"></i>View Site</a>
        <a href="logout.php" style="font-size:13px;font-weight:600;color:#ef4444;text-decoration:none;"><i class="fas fa-sign-out-alt" style="margin-right:5px;"></i>Logout</a>
      </div>
    </div>
  </div>

  <div style="max-width:1200px;margin:0 auto;padding:20px;">

    <!-- ── Stats cards ───────────────────────────────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px;">
      <?php foreach (
        [
          ['Total Orders',   $totalOrders,                   'fa-receipt',    '#3b82f6', '#eff6ff'],
          ['🔴 Pending',      $pendingCount,                  'fa-clock',      '#ef4444', '#fee2e2'],
          ['💰 Unpaid',       $unpaidCount,                   'fa-rupee-sign', '#f59e0b', '#fffbeb'],
          ["Today Revenue",  '₹' . number_format($todayRev, 0), 'fa-chart-line', '#10b981', '#ecfdf5'],
          ['Menu Items',     $menuCount,                     'fa-hamburger',  '#8b5cf6', '#f5f3ff'],
        ] as [$label, $val, $icon, $col, $bg]
      ): ?>
        <div style="background:#fff;border-radius:16px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;"><?= $label ?></span>
            <div style="width:34px;height:34px;background:<?= $bg ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;">
              <i class="fas <?= $icon ?>" style="color:<?= $col ?>;font-size:14px;"></i>
            </div>
          </div>
          <div style="font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:#111;"><?= $val ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Filters ───────────────────────────────────────────────────────────────── -->
    <div style="background:#fff;border-radius:16px;padding:14px 16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
      <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">

        <!-- Status filters -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button class="fpill on" data-f="all" onclick="filterBy(this,'status')">All</button>
          <button class="fpill" data-f="Pending" onclick="filterBy(this,'status')">⏳ Pending <?php if ($pendingCount > 0): ?><span style="background:#ef4444;color:#fff;border-radius:999px;padding:1px 6px;font-size:10px;margin-left:3px;"><?= $pendingCount ?></span><?php endif; ?></button>
          <button class="fpill" data-f="Preparing" onclick="filterBy(this,'status')">👨‍🍳 Preparing</button>
          <button class="fpill" data-f="Ready" onclick="filterBy(this,'status')">✅ Ready</button>
          <button class="fpill" data-f="Completed" onclick="filterBy(this,'status')">🏁 Completed</button>
          <button class="fpill" data-f="Cancelled" onclick="filterBy(this,'status')">❌ Cancelled</button>
        </div>

        <!-- Order type filters -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;padding-left:12px;border-left:2px solid #f3f4f6;">
          <button class="fpill on" data-t="all" onclick="filterBy(this,'type')">All Types</button>
          <button class="fpill" data-t="Dine-in" onclick="filterBy(this,'type')">🍽️ Dine-in</button>
          <button class="fpill" data-t="Takeaway" onclick="filterBy(this,'type')">🛍️ Takeaway</button>
        </div>

        <!-- Search + refresh -->
        <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
          <input id="searchBox" type="text" placeholder="Search name / mobile…"
            style="border:2px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:13px;width:210px;outline:none;"
            oninput="searchOrders(this.value)" />
          <button onclick="location.reload()" title="Refresh now"
            style="padding:8px 13px;border:2px solid #e5e7eb;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;color:#6b7280;">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- ── HOW IT WORKS info strip ───────────────────────────────────────────────── -->
    <div style="background:linear-gradient(135deg,#1a0a00,#3d1a00);border-radius:16px;padding:16px 20px;margin-bottom:18px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
      <span style="color:#f9a84d;font-weight:700;font-size:13px;white-space:nowrap;">📋 How orders work:</span>
      <div style="display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:rgba(255,255,255,.7);">
        <span>🔔 <b style="color:#fff;">New order</b> → page auto-alerts you</span>
        <span>👨‍🍳 Change status to <b style="color:#fcd34d;">Preparing</b> → kitchen starts</span>
        <span>✅ Change to <b style="color:#86efac;">Ready</b> → SMS sent to customer automatically</span>
        <span>💰 Click <b style="color:#86efac;">Mark Paid</b> after collecting money</span>
        <span>🍽️ <b style="color:#fff;">Table number</b> shown for dine-in</span>
        <span>🗺️ <b style="color:#fff;">Address</b> shown for takeaway</span>
      </div>
    </div>

    <!-- ── Order list ─────────────────────────────────────────────────────────────── -->
    <div id="orderList">
      <?php if (empty($orders)): ?>
        <div style="background:#fff;border-radius:16px;padding:60px 20px;text-align:center;">
          <div style="font-size:48px;margin-bottom:12px;">📋</div>
          <p style="color:#9ca3af;font-weight:600;font-size:16px;">No orders yet</p>
          <p style="color:#d1d5db;font-size:13px;margin-top:6px;">Orders will appear here automatically when customers place them</p>
        </div>
      <?php endif; ?>

      <?php foreach ($orders as $o):
        $st  = $o['status'] ?? 'Pending';
        $sc  = $statusColors[$st];
        $dt  = date('d M Y, h:i A', strtotime($o['created_at']));
        $isTakeaway = $o['order_type'] === 'Takeaway';
        $isPaid     = $o['payment_status'] === 'Paid';
        $isNew      = (strtotime($o['created_at']) > time() - 120); // new if < 2 min ago
      ?>
        <div class="order-card <?= $st === 'Pending' ? 'is-pending' : '' ?> <?= $isNew ? 'is-new' : '' ?>"
          data-status="<?= $st ?>"
          data-type="<?= $o['order_type'] ?>"
          data-search="<?= strtolower($o['mobile_no'] . ' ' . $o['customer_name']) ?>">

          <!-- ── Card top: all info ── -->
          <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:16px 18px 12px;border-bottom:1px solid #f3f4f6;gap:14px;flex-wrap:wrap;">

            <!-- LEFT: badges + customer + items + location -->
            <div style="flex:1;min-width:0;">

              <!-- Row 1: status + type + table + order ID + time -->
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                <span class="sbadge s-<?= $st ?>"><?= $st ?></span>

                <!-- Dine-in / Takeaway badge -->
                <span style="padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $isTakeaway ? '#fffbeb' : '#f0fdf4' ?>;color:<?= $isTakeaway ? '#92400e' : '#166534' ?>;">
                  <?= $isTakeaway ? '🛍️ Takeaway' : '🍽️ Dine-in' ?>
                </span>

                <!-- Table number for Dine-in -->
                <?php if (!$isTakeaway): ?>
                  <span style="padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:#f0f9ff;color:#0369a1;">
                    🪑 <?= $o['table_id'] ? 'Table ' . $o['table_id'] : 'Table TBD' ?>
                  </span>
                <?php endif; ?>

                <span style="font-family:monospace;font-size:11px;color:#9ca3af;"><?= htmlspecialchars($o['gid']) ?></span>
                <span style="font-size:11px;color:#9ca3af;"><i class="fas fa-clock" style="margin-right:3px;"></i><?= $dt ?></span>
                <?php if ($isNew): ?>
                  <span style="background:#e65c00;color:#fff;border-radius:999px;padding:2px 8px;font-size:10px;font-weight:700;animation:newPulse 1s infinite;">🆕 NEW</span>
                <?php endif; ?>
              </div>

              <!-- Row 2: Customer name + phone -->
              <p style="font-size:15px;font-weight:700;color:#111;margin-bottom:4px;">
                <i class="fas fa-user" style="color:#d1d5db;margin-right:6px;font-size:12px;"></i><?= htmlspecialchars($o['customer_name'] ?: 'Guest') ?>
                <span style="font-size:13px;color:#6b7280;font-weight:400;"> · <i class="fas fa-phone" style="font-size:11px;margin-right:3px;"></i><?= htmlspecialchars($o['mobile_no']) ?></span>
              </p>

              <!-- Row 3: Items ordered -->
              <p style="font-size:13px;color:#374151;margin-bottom:6px;line-height:1.5;">
                <i class="fas fa-utensils" style="color:#d1d5db;margin-right:6px;font-size:11px;"></i><?= htmlspecialchars($o['summary'] ?? '—') ?>
              </p>

              <!-- Row 4: Takeaway address / Table info -->
              <?php if ($isTakeaway && ($o['area'] || $o['address'])): ?>
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:8px 12px;margin-top:4px;display:inline-block;">
                  <div style="font-size:11px;font-weight:700;color:#9a3412;margin-bottom:2px;"><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i>PICKUP ADDRESS</div>
                  <div style="font-size:13px;color:#7c2d12;">
                    <?= htmlspecialchars($o['area'] ?: '') ?>
                    <?= $o['address'] ? ' — ' . htmlspecialchars($o['address']) : '' ?>
                    <?= $o['landmark'] ? ' (Near: ' . htmlspecialchars($o['landmark']) . ')' : '' ?>
                  </div>
                </div>
              <?php elseif (!$isTakeaway): ?>
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:8px 12px;margin-top:4px;display:inline-block;">
                  <div style="font-size:11px;font-weight:700;color:#0369a1;margin-bottom:2px;"><i class="fas fa-chair" style="margin-right:5px;"></i>DINE-IN — SERVE AT TABLE</div>
                  <div style="font-size:13px;color:#0c4a6e;font-weight:600;">
                    <?= $o['table_id'] ? 'Table Number: ' . $o['table_id'] : 'Table number not specified' ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- RIGHT: Amount + payment + mark paid button -->
            <div style="text-align:right;flex-shrink:0;min-width:160px;">
              <p style="font-size:24px;font-weight:800;color:#e65c00;margin-bottom:5px;">₹<?= number_format($o['grand_amt'], 2) ?></p>

              <!-- Payment method -->
              <p style="font-size:13px;font-weight:600;margin-bottom:4px;">
                <?php if ($o['payment_method'] === 'UPI'): ?>
                  <span style="color:#8b5cf6;">📲 UPI</span>
                <?php elseif ($o['payment_method'] === 'CARD'): ?>
                  <span style="color:#3b82f6;">💳 Card</span>
                <?php else: ?>
                  <span style="color:#6b7280;">💵 Cash</span>
                <?php endif; ?>
                &nbsp;·&nbsp;
                <span id="payLabel_<?= htmlspecialchars($o['gid']) ?>"
                  style="font-weight:700;color:<?= $isPaid ? '#059669' : '#dc2626' ?>;">
                  <?= $isPaid ? '✅ Paid' : '❌ Unpaid' ?>
                </span>
              </p>

              <!-- UTR for UPI payments -->
              <?php if ($o['transaction_id']): ?>
                <p style="font-size:11px;color:#9ca3af;font-family:monospace;margin-bottom:6px;">
                  UTR: <?= htmlspecialchars($o['transaction_id']) ?>
                </p>
              <?php endif; ?>

              <!-- Mark Paid button -->
              <?php if (!$isPaid && $st !== 'Cancelled'): ?>
                <button class="pay-btn" id="payBtn_<?= htmlspecialchars($o['gid']) ?>"
                  onclick="markPaid('<?= htmlspecialchars($o['gid']) ?>', this)">
                  <i class="fas fa-check" style="margin-right:5px;"></i>Mark as Paid
                </button>
              <?php else: ?>
                <span style="font-size:12px;color:#9ca3af;font-style:italic;"><?= $isPaid ? 'Payment received' : '' ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- ── Card bottom: status controls + SMS note ── -->
          <div style="padding:12px 18px;background:#fafafa;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:700;color:#6b7280;white-space:nowrap;">Update Status:</span>

            <select class="status-select s-<?= $st ?>"
              data-gid="<?= htmlspecialchars($o['gid']) ?>"
              data-type="<?= $o['order_type'] ?>"
              onchange="updateStatus(this)">
              <option value="Pending" <?= $st === 'Pending'  ? 'selected' : '' ?>>⏳ Pending</option>
              <option value="Preparing" <?= $st === 'Preparing' ? 'selected' : '' ?>>👨‍🍳 Preparing</option>
              <option value="Ready" <?= $st === 'Ready'    ? 'selected' : '' ?>>✅ Ready</option>
              <option value="Completed" <?= $st === 'Completed' ? 'selected' : '' ?>>🏁 Completed</option>
              <option value="Cancelled" <?= $st === 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
            </select>

            <span class="save-indicator" style="display:none;font-size:12px;color:#e65c00;font-weight:600;">
              <i class="fas fa-spinner fa-spin"></i> Saving…
            </span>
            <span class="saved-indicator" style="display:none;font-size:12px;color:#10b981;font-weight:600;"></span>

            <!-- SMS note: shown only when status is Ready -->
            <span style="font-size:11px;color:#9ca3af;margin-left:auto;">
              <i class="fas fa-sms" style="color:#8b5cf6;margin-right:4px;"></i>SMS sent to customer when status → Ready
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:10px;padding-bottom:30px;">
      <i class="fas fa-sync-alt" style="margin-right:5px;"></i>Page checks for new orders every 15s · <?= count($orders) ?> total orders
    </p>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast success"></div>

  <script>
    const serverTime = '<?= $serverTime ?>';
    let lastKnownTime = serverTime;
    let activeStatus = 'all';
    let activeType = 'all';

    // ── Live clock ──────────────────────────────────────────────────────────────
    setInterval(() => {
      const now = new Date();
      document.getElementById('liveClock').textContent =
        now.toLocaleDateString('en-IN', {
          weekday: 'short',
          day: 'numeric',
          month: 'short'
        }) + ' · ' +
        now.toLocaleTimeString('en-IN', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
    }, 1000);

    // ── Poll for new orders every 15s ───────────────────────────────────────────
    setInterval(() => {
      $.post('dashboard.php', {
        poll_new: 1,
        since: lastKnownTime
      }, function(data) {
        if (data.new_count > 0) {
          document.getElementById('newOrderBanner').style.display = 'block';
          document.title = '🔔 (' + data.new_count + ') New Order! | Dashboard';
          // Play a beep if browser allows
          try {
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.3;
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
          } catch (e) {}
        }
      }, 'json');
    }, 15000);

    // Auto full reload every 60s
    setTimeout(() => location.reload(), 60000);

    // ── Update order status ─────────────────────────────────────────────────────
    function updateStatus(sel) {
      const gid = sel.dataset.gid;
      const status = sel.value;
      const type = sel.dataset.type;
      const row = sel.closest('div[style*="padding:12px"]');
      const card = sel.closest('.order-card');
      const saving = row.querySelector('.save-indicator');
      const saved = row.querySelector('.saved-indicator');

      saving.style.display = 'inline-flex';
      saved.style.display = 'none';
      sel.disabled = true;

      $.post('dashboard.php', {
        update_status: 1,
        gid,
        status
      }, function(data) {
        sel.disabled = false;
        saving.style.display = 'none';

        if (data.ok) {
          // Update badge
          sel.className = 'status-select s-' + status;
          const badge = card.querySelector('.sbadge');
          if (badge) {
            badge.textContent = status;
            badge.className = 'sbadge s-' + status;
          }
          card.dataset.status = status;

          // Highlight/de-highlight pending border
          card.classList.toggle('is-pending', status === 'Pending');

          // Show saved message
          if (status === 'Ready') {
            const dest = type === 'Takeaway' ? 'customer to come collect order' : 'customer their order is ready';
            saved.innerHTML = '<i class="fas fa-check"></i> Saved! <span style="color:#8b5cf6;"><i class="fas fa-sms"></i> SMS sent to ' + dest + '</span>';
            showToast('✅ Status → Ready! SMS sent to customer 📱', 'sms');
          } else if (status === 'Cancelled') {
            saved.innerHTML = '<i class="fas fa-check"></i> Order cancelled';
            showToast('Order cancelled', 'error');
          } else {
            saved.innerHTML = '<i class="fas fa-check"></i> Status updated to ' + status;
            showToast('Status updated → ' + status, 'success');
          }
          saved.style.display = 'inline-flex';
          setTimeout(() => saved.style.display = 'none', 4000);

          applyFilters();
        } else {
          alert('Failed to update. Please try again.');
        }
      }, 'json').fail(() => {
        sel.disabled = false;
        saving.style.display = 'none';
        alert('Network error. Check connection.');
      });
    }

    // ── Mark order as paid ──────────────────────────────────────────────────────
    function markPaid(gid, btn) {
      if (!confirm('Confirm payment received for this order?')) return;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

      $.post('dashboard.php', {
        mark_paid: 1,
        gid
      }, function(data) {
        if (data.ok) {
          btn.style.display = 'none';
          const lbl = document.getElementById('payLabel_' + gid);
          if (lbl) {
            lbl.textContent = '✅ Paid';
            lbl.style.color = '#059669';
          }
          showToast('💰 Payment marked as Paid!', 'success');
        } else {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i>Mark as Paid';
          alert('Failed. Try again.');
        }
      }, 'json');
    }

    // ── Filters ─────────────────────────────────────────────────────────────────
    function applyFilters() {
      document.querySelectorAll('.order-card').forEach(c => {
        const okS = activeStatus === 'all' || c.dataset.status === activeStatus;
        const okT = activeType === 'all' || c.dataset.type === activeType;
        c.style.display = (okS && okT) ? '' : 'none';
      });
    }

    function filterBy(btn, kind) {
      const attr = kind === 'status' ? 'data-f' : 'data-t';
      document.querySelectorAll('[' + attr + ']').forEach(b => b.classList.remove('on'));
      btn.classList.add('on');
      if (kind === 'status') activeStatus = btn.dataset.f;
      else activeType = btn.dataset.t;
      applyFilters();
    }

    function searchOrders(q) {
      q = q.toLowerCase();
      document.querySelectorAll('.order-card').forEach(c => {
        c.style.display = c.dataset.search.includes(q) ? '' : 'none';
      });
    }

    // ── Toast ───────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.className = 'toast ' + type + ' show';
      setTimeout(() => t.classList.remove('show'), 4000);
    }
  </script>
</body>

</html>