<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (empty($_SESSION['mobile_verified'])) {
  header('Location: login.php');
  exit;
}

$device_id  = $_SESSION['unique_device_id'] ?? '';
if (!$device_id) {
  header('Location: cart.php');
  exit;
}

$order_type = $_GET['type']     ?? 'Dine-in';
$table_id   = $_GET['table']    ?? '';
$area       = $_GET['area']     ?? '';
$address    = $_GET['address']  ?? '';
$landmark   = $_GET['landmark'] ?? '';

$s = $pdo->prepare("SELECT * FROM menu_items WHERE DeviceID=?");
$s->execute([$device_id]);
$items = $s->fetchAll();
if (empty($items)) {
  header('Location: cart.php');
  exit;
}

$sub   = array_sum(array_map(fn($r) => $r['Rate'] * $r['Quantity'], $items));
$cgst  = round($sub * CGST_RATE / 100, 2);
$sgst  = round($sub * SGST_RATE / 100, 2);
$total = round($sub + $cgst + $sgst);

$upiLink = 'upi://pay?pa=' . urlencode(UPI_ID) . '&pn=' . urlencode(UPI_NAME) . '&am=' . $total . '&cu=INR&tn=' . urlencode('Order at ' . RESTAURANT_NAME);
$qrUrl   = 'https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=' . urlencode($upiLink) . '&choe=UTF-8';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment | <?= RESTAURANT_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f1f5f9;
      min-height: 100vh;
      padding-bottom: 100px;
    }

    input {
      outline: none;
      font-family: inherit;
    }

    .pay-card {
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px 10px;
      cursor: pointer;
      transition: all .2s;
      text-align: center;
    }

    .pay-card.sel {
      border-color: #e65c00;
      background: #fff7ed;
      box-shadow: 0 0 0 3px rgba(230, 92, 0, .12);
    }
  </style>
</head>

<body>

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#e65c00,#c0392b);padding:16px;position:sticky;top:0;z-index:40;">
    <div style="display:flex;align-items:center;gap:12px;max-width:560px;margin:0 auto;">
      <a href="cart.php" style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;flex-shrink:0;">
        <i class="fas fa-arrow-left" style="color:#fff;font-size:13px;"></i>
      </a>
      <div>
        <div style="font-family:'Playfair Display',serif;font-size:19px;font-weight:700;color:#fff;">Payment</div>
        <div style="font-size:12px;color:rgba(255,255,255,.75);"><?= RESTAURANT_NAME ?></div>
      </div>
    </div>
  </div>

  <div style="max-width:560px;margin:0 auto;padding:16px;">

    <!-- Order type banner -->
    <div style="background:<?= $order_type === 'Takeaway' ? '#fffbeb' : '#f0fdf4' ?>;border:1.5px solid <?= $order_type === 'Takeaway' ? '#fde68a' : '#bbf7d0' ?>;border-radius:14px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
      <span style="font-size:22px;"><?= $order_type === 'Takeaway' ? '🛍️' : '🍽️' ?></span>
      <div>
        <div style="font-weight:700;font-size:14px;color:#111;"><?= $order_type ?></div>
        <?php if ($order_type === 'Takeaway' && $area): ?>
          <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($area) ?><?= $landmark ? ' · ' . htmlspecialchars($landmark) : '' ?></div>
        <?php elseif ($order_type === 'Takeaway'): ?>
          <div style="font-size:12px;color:#6b7280;">Pickup from restaurant</div>
        <?php else: ?>
          <div style="font-size:12px;color:#6b7280;">Served at your table</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order summary -->
    <div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
      <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:12px;"><i class="fas fa-receipt" style="color:#e65c00;margin-right:7px;"></i>Order Summary</div>
      <?php foreach ($items as $r): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
          <?php if ($r['MenuImageUrl']): ?>
            <img src="<?= htmlspecialchars($r['MenuImageUrl']) ?>" style="width:44px;height:38px;object-fit:cover;border-radius:8px;flex-shrink:0;" onerror="this.style.display='none'" />
          <?php else: ?>
            <div style="width:44px;height:38px;background:#f3f4f6;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">🍽️</div>
          <?php endif; ?>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:13px;color:#111;"><?= htmlspecialchars($r['MenuName']) ?></div>
            <div style="font-size:12px;color:#9ca3af;">x<?= $r['Quantity'] ?> · ₹<?= number_format($r['Rate'], 2) ?></div>
          </div>
          <div style="font-weight:700;font-size:13px;color:#111;flex-shrink:0;">₹<?= number_format($r['Rate'] * $r['Quantity'], 2) ?></div>
        </div>
      <?php endforeach; ?>
      <div style="border-top:1px solid #f3f4f6;padding-top:12px;margin-top:4px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;"><span>Item Total</span><span>₹<?= number_format($sub, 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;"><span>CGST + SGST</span><span>₹<?= $cgst + $sgst ?></span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;margin-top:8px;">
          <span>Total Payable</span><span style="color:#e65c00;">₹<?= $total ?></span>
        </div>
      </div>
    </div>

    <!-- Payment method -->
    <div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
      <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:12px;"><i class="fas fa-credit-card" style="color:#e65c00;margin-right:7px;"></i>Payment Method</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
        <div class="pay-card" id="pc-UPI" onclick="selPay('UPI')">
          <div style="font-size:26px;margin-bottom:6px;">📲</div>
          <div style="font-weight:700;font-size:13px;">UPI</div>
          <div style="font-size:11px;color:#9ca3af;margin-top:2px;">GPay·PhonePe</div>
        </div>
        <div class="pay-card" id="pc-CARD" onclick="selPay('CARD')">
          <div style="font-size:26px;margin-bottom:6px;">💳</div>
          <div style="font-weight:700;font-size:13px;">Card</div>
          <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Debit/Credit</div>
        </div>
        <div class="pay-card" id="pc-COD" onclick="selPay('COD')">
          <div style="font-size:26px;margin-bottom:6px;">💵</div>
          <div style="font-weight:700;font-size:13px;">Cash</div>
          <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Pay at counter</div>
        </div>
      </div>
      <p id="payErr" style="display:none;color:#dc2626;font-size:12px;margin-top:10px;"><i class="fas fa-exclamation-circle" style="margin-right:5px;"></i>Please select a payment method</p>
    </div>

    <!-- UPI panel -->
    <div id="upiPanel" style="display:none;background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center;">
      <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:14px;">Scan QR to Pay</div>
      <div style="display:inline-block;padding:10px;border:2px solid #e5e7eb;border-radius:16px;margin-bottom:12px;">
        <img src="<?= $qrUrl ?>" width="200" height="200" style="display:block;" onerror="this.parentElement.innerHTML='<p style=\'color:#dc2626;font-size:12px;padding:20px;\'>QR load failed. Use button below.</p>'" />
      </div>
      <div style="background:#fff7ed;border-radius:12px;padding:10px 14px;margin-bottom:14px;">
        <div style="font-size:12px;color:#9a3412;margin-bottom:4px;">UPI ID</div>
        <div style="font-weight:700;color:#111;font-size:15px;"><?= UPI_ID ?></div>
        <div style="font-weight:900;color:#e65c00;font-size:22px;margin-top:4px;">₹<?= $total ?></div>
      </div>
      <a href="<?= htmlspecialchars($upiLink) ?>" style="display:block;padding:12px;background:#22c55e;color:#fff;font-weight:700;border-radius:12px;text-decoration:none;margin-bottom:14px;">
        <i class="fas fa-external-link-alt" style="margin-right:7px;"></i>Open UPI App · ₹<?= $total ?>
      </a>
      <div style="text-align:left;">
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Enter UTR / Transaction ID <span style="color:#dc2626;">*</span></label>
        <input type="text" id="utrInput" placeholder="e.g. T2406131234567890"
          style="width:100%;padding:13px 14px;border:2px solid #e5e7eb;border-radius:12px;font-size:14px;font-family:monospace;" />
        <p style="font-size:11px;color:#9ca3af;margin-top:6px;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Find UTR in your UPI app after payment</p>
      </div>
    </div>

    <!-- Card panel -->
    <div id="cardPanel" style="display:none;background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
      <div style="display:flex;align-items:center;gap:12px;background:#eff6ff;border-radius:12px;padding:14px;">
        <i class="fas fa-credit-card" style="color:#3b82f6;font-size:22px;"></i>
        <div>
          <div style="font-weight:600;font-size:14px;color:#111;">Card Payment</div>
          <div style="font-size:12px;color:#6b7280;margin-top:2px;">Pay ₹<?= $total ?> at the counter with your card</div>
        </div>
      </div>
    </div>

    <!-- Cash panel -->
    <div id="codPanel" style="display:none;background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
      <div style="display:flex;align-items:center;gap:12px;background:#f0fdf4;border-radius:12px;padding:14px;">
        <i class="fas fa-money-bill-wave" style="color:#22c55e;font-size:22px;"></i>
        <div>
          <div style="font-weight:600;font-size:14px;color:#111;">Cash Payment</div>
          <div style="font-size:12px;color:#6b7280;margin-top:2px;">Pay ₹<?= $total ?> in cash at the counter</div>
        </div>
      </div>
    </div>

  </div>

  <!-- Pay button -->
  <div style="position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #f3f4f6;padding:14px 16px;">
    <div style="max-width:560px;margin:0 auto;">
      <button id="payBtn" onclick="placeOrder()" disabled
        style="display:block;width:100%;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-size:16px;font-weight:700;cursor:pointer;opacity:.5;">
        <i class="fas fa-lock" style="margin-right:8px;"></i>Pay ₹<?= $total ?>
      </button>
    </div>
  </div>

  <script>
    let method = null;
    const orderType = '<?= addslashes($order_type) ?>';
    const tableId = '<?= addslashes($table_id) ?>';
    const area = '<?= addslashes($area) ?>';
    const address = '<?= addslashes($address) ?>';
    const landmark = '<?= addslashes($landmark) ?>';

    function selPay(m) {
      method = m;
      ['UPI', 'CARD', 'COD'].forEach(k => {
        document.getElementById('pc-' + k).classList.remove('sel');
        document.getElementById({
          UPI: 'upiPanel',
          CARD: 'cardPanel',
          COD: 'codPanel'
        } [k]).style.display = 'none';
      });
      document.getElementById('pc-' + m).classList.add('sel');
      document.getElementById({
        UPI: 'upiPanel',
        CARD: 'cardPanel',
        COD: 'codPanel'
      } [m]).style.display = 'block';
      document.getElementById('payErr').style.display = 'none';
      const btn = document.getElementById('payBtn');
      btn.disabled = false;
      btn.style.opacity = '1';
      const labels = {
        UPI: '<i class="fas fa-qrcode" style="margin-right:8px;"></i>Confirm UPI · ₹<?= $total ?>',
        CARD: '<i class="fas fa-credit-card" style="margin-right:8px;"></i>Pay ₹<?= $total ?> by Card',
        COD: '<i class="fas fa-check-circle" style="margin-right:8px;"></i>Place Order · Pay ₹<?= $total ?> Cash',
      };
      btn.innerHTML = labels[m];
    }

    function placeOrder() {
      if (!method) {
        document.getElementById('payErr').style.display = 'block';
        return;
      }
      if (method === 'UPI') {
        const utr = document.getElementById('utrInput').value.trim();
        if (!utr) {
          alert('Please enter the UTR / Transaction ID after making UPI payment.');
          return;
        }
        if (!confirm('Confirm order · ₹<?= $total ?> via UPI?')) return;
        window.location.href = `place_order.php?mode=UPI&utr=${encodeURIComponent(utr)}&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
      } else {
        if (!confirm(`Confirm order · ₹<?= $total ?> by ${method}?`)) return;
        window.location.href = `place_order.php?mode=${method}&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
      }
    }
  </script>
</body>

</html>