<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (empty($_SESSION['mobile_verified'])) {
  header('Location: login.php'); exit;
}

$device_id = $_SESSION['unique_device_id'] ?? '';
if (!$device_id) { header('Location: cart.php'); exit; }

$order_type = $_GET['type']     ?? 'Dine-in';
$table_id   = $_GET['table']    ?? '';
$area       = $_GET['area']     ?? '';
$address    = $_GET['address']  ?? '';
$landmark   = $_GET['landmark'] ?? '';

$s = $pdo->prepare("SELECT * FROM menu_items WHERE DeviceID=?");
$s->execute([$device_id]);
$items = $s->fetchAll();
if (empty($items)) { header('Location: cart.php'); exit; }

$sub   = array_sum(array_map(fn($r) => $r['Rate'] * $r['Quantity'], $items));
$cgst  = round($sub * CGST_RATE / 100, 2);
$sgst  = round($sub * SGST_RATE / 100, 2);
$total = round($sub + $cgst + $sgst);

// UPI deep links — amount pre-filled for each app
$upiBase    = 'pa='.urlencode(UPI_ID).'&pn='.urlencode(UPI_NAME).'&am='.$total.'&cu=INR&tn='.urlencode('Order at '.RESTAURANT_NAME);
$gpayLink   = 'gpay://upi/pay?'    . $upiBase;
$phonepeLink= 'phonepe://pay?'     . $upiBase;
$paytmLink  = 'paytmmp://pay?'     . $upiBase;
$genericLink= 'upi://pay?'         . $upiBase;

// Dynamic QR with amount pre-filled
$qrData = 'upi://pay?' . $upiBase;
$qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Payment | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:#f1f5f9;min-height:100vh;padding-bottom:100px;}
input{outline:none;font-family:inherit;}
.pay-card{border:2px solid #e5e7eb;border-radius:16px;padding:14px 10px;cursor:pointer;transition:all .2s;text-align:center;background:#fff;}
.pay-card.sel{border-color:#e65c00;background:#fff7ed;box-shadow:0 0 0 3px rgba(230,92,0,.12);}
.upi-app-btn{display:flex;align-items:center;justify-content:center;gap:10px;padding:14px;background:#fff;border:2px solid #e5e7eb;border-radius:14px;text-decoration:none;font-weight:700;font-size:14px;color:#111;transition:.2s;}
.upi-app-btn:active{background:#f3f4f6;transform:scale(.97);}
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
  <div style="background:<?= $order_type==='Takeaway'?'#fffbeb':'#f0fdf4' ?>;border:1.5px solid <?= $order_type==='Takeaway'?'#fde68a':'#bbf7d0' ?>;border-radius:14px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:22px;"><?= $order_type==='Takeaway'?'🛍️':'🍽️' ?></span>
    <div>
      <div style="font-weight:700;font-size:14px;color:#111;"><?= $order_type ?><?= $table_id ? ' · Table '.$table_id : '' ?></div>
      <?php if($order_type==='Takeaway' && $area): ?>
        <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($area) ?><?= $landmark?' · '.htmlspecialchars($landmark):'' ?></div>
      <?php else: ?>
        <div style="font-size:12px;color:#6b7280;">Served at your table</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Order summary -->
  <div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:12px;"><i class="fas fa-receipt" style="color:#e65c00;margin-right:7px;"></i>Order Summary</div>
    <?php foreach($items as $r): ?>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
      <?php if($r['MenuImageUrl']): ?>
        <img src="<?= htmlspecialchars($r['MenuImageUrl']) ?>" style="width:44px;height:38px;object-fit:cover;border-radius:8px;flex-shrink:0;" onerror="this.style.display='none'"/>
      <?php else: ?>
        <div style="width:44px;height:38px;background:#f3f4f6;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">🍽️</div>
      <?php endif; ?>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;font-size:13px;color:#111;"><?= htmlspecialchars($r['MenuName']) ?></div>
        <div style="font-size:12px;color:#9ca3af;">x<?= $r['Quantity'] ?> · ₹<?= number_format($r['Rate'],2) ?></div>
      </div>
      <div style="font-weight:700;font-size:13px;color:#111;flex-shrink:0;">₹<?= number_format($r['Rate']*$r['Quantity'],2) ?></div>
    </div>
    <?php endforeach; ?>
    <div style="border-top:1px solid #f3f4f6;padding-top:12px;margin-top:4px;">
      <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;"><span>Item Total</span><span>₹<?= number_format($sub,2) ?></span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:6px;"><span>CGST + SGST</span><span>₹<?= $cgst+$sgst ?></span></div>
      <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;margin-top:8px;">
        <span>Total Payable</span><span style="color:#e65c00;">₹<?= $total ?></span>
      </div>
    </div>
  </div>

  <!-- Payment method -->
  <div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="font-weight:700;font-size:14px;color:#111;margin-bottom:12px;"><i class="fas fa-credit-card" style="color:#e65c00;margin-right:7px;"></i>Payment Method</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div class="pay-card" id="pc-UPI" onclick="selPay('UPI')">
        <div style="font-size:26px;margin-bottom:6px;">📲</div>
        <div style="font-weight:700;font-size:13px;">UPI</div>
        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">GPay · PhonePe · Paytm</div>
      </div>
      <div class="pay-card" id="pc-COD" onclick="selPay('COD')">
        <div style="font-size:26px;margin-bottom:6px;">💵</div>
        <div style="font-weight:700;font-size:13px;">Cash</div>
        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Pay at counter</div>
      </div>
    </div>
    <p id="payErr" style="display:none;color:#dc2626;font-size:12px;margin-top:10px;"><i class="fas fa-exclamation-circle" style="margin-right:5px;"></i>Please select a payment method</p>
  </div>

  <!-- ── UPI Panel ── -->
  <div id="upiPanel" style="display:none;background:#fff;border-radius:18px;padding:18px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">

    <!-- Amount -->
    <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:2px solid #fed7aa;border-radius:14px;padding:14px;text-align:center;margin-bottom:18px;">
      <div style="font-size:12px;color:#9a3412;font-weight:600;margin-bottom:2px;">Pay Exactly</div>
      <div style="font-size:40px;font-weight:900;color:#e65c00;">₹<?= $total ?></div>
      <div style="font-size:11px;color:#9a3412;margin-top:3px;">to <?= UPI_ID ?></div>
    </div>

    <!-- Mobile section — UPI app buttons -->
    <div id="mobileSection">
      <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
        <span style="background:#eff6ff;color:#3b82f6;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;">📱 ON MOBILE</span>
        Tap your UPI app — amount pre-filled
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:6px;">

        <!-- GPay -->
        <a href="<?= $gpayLink ?>" class="upi-app-btn" onclick="appTapped()">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Google_Pay_Logo_%282020%29.svg/120px-Google_Pay_Logo_%282020%29.svg.png"
               height="24" style="object-fit:contain;" onerror="this.outerHTML='💚'"/>
          <span>GPay</span>
        </a>

        <!-- PhonePe -->
        <a href="<?= $phonepeLink ?>" class="upi-app-btn" onclick="appTapped()">
          <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="8" fill="#5F259F"/>
            <text x="5" y="28" font-size="18" fill="white" font-weight="bold">Pe</text>
          </svg>
          <span>PhonePe</span>
        </a>

        <!-- Paytm -->
        <a href="<?= $paytmLink ?>" class="upi-app-btn" onclick="appTapped()">
          <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="8" fill="#00BAF2"/>
            <text x="3" y="27" font-size="13" fill="white" font-weight="bold">Pay</text>
          </svg>
          <span>Paytm</span>
        </a>

        <!-- Other UPI -->
        <a href="<?= $genericLink ?>" class="upi-app-btn" onclick="appTapped()">
          <span style="font-size:22px;">📲</span>
          <span>Other UPI</span>
        </a>
      </div>
      <p style="font-size:11px;color:#9ca3af;text-align:center;margin-bottom:16px;">
        Amount ₹<?= $total ?> will be auto-filled in the app
      </p>
    </div>

    <!-- Divider -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
      <div style="flex:1;height:1px;background:#e5e7eb;"></div>
      <span style="font-size:12px;color:#9ca3af;font-weight:600;">OR</span>
      <div style="flex:1;height:1px;background:#e5e7eb;"></div>
    </div>

    <!-- QR code — for desktop / secondary phone -->
    <div style="border:2px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:14px;">
      <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
        <span style="background:#f0fdf4;color:#16a34a;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;">💻 ON DESKTOP</span>
        Scan QR with your phone
      </div>
      <div style="text-align:center;">
        <img src="<?= $qrUrl ?>" width="200" height="200" style="border-radius:10px;border:1px solid #e5e7eb;display:inline-block;"/>
        <div style="font-size:11px;font-weight:700;color:#16a34a;margin-top:8px;">✅ ₹<?= $total ?> auto-filled when scanned</div>
        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Works with GPay · PhonePe · Paytm · Any UPI app</div>
      </div>
    </div>

    <!-- Copy UPI ID -->
    <div style="border:2px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:16px;">
      <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px;">
        📋 Or copy UPI ID &amp; pay manually
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
        <div style="font-weight:800;color:#111;font-size:15px;letter-spacing:.3px;" id="upiIdText"><?= UPI_ID ?></div>
        <button onclick="copyUpiId()" id="copyBtn"
          style="flex-shrink:0;padding:10px 18px;background:#e65c00;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
          <i class="fas fa-copy" style="margin-right:4px;"></i>Copy
        </button>
      </div>
    </div>

    <!-- I Have Paid -->
    <div id="paidBox" style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:14px;padding:16px;text-align:center;">
      <p style="font-size:12px;color:#166534;margin-bottom:12px;">
        <i class="fas fa-info-circle" style="margin-right:5px;"></i>
        After completing payment in your UPI app, tap below
      </p>
      <button onclick="confirmUpiPaid()"
        style="width:100%;padding:16px;background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;font-weight:800;font-size:16px;border:none;border-radius:12px;cursor:pointer;box-shadow:0 4px 14px rgba(22,163,74,.35);">
        <i class="fas fa-check-circle" style="margin-right:8px;"></i>✅ I Have Paid — Place My Order
      </button>
      <p style="font-size:10px;color:#9ca3af;margin-top:8px;">Owner will verify payment and prepare your order</p>
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

<!-- Fixed Pay button -->
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
  const orderType  = '<?= addslashes($order_type) ?>';
  const tableId    = '<?= addslashes($table_id) ?>';
  const area       = '<?= addslashes($area) ?>';
  const address    = '<?= addslashes($address) ?>';
  const landmark   = '<?= addslashes($landmark) ?>';

  // Auto-hide mobile section on desktop
  window.addEventListener('load', () => {
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    document.getElementById('mobileSection').style.display = isMobile ? 'block' : 'none';
  });

  function appTapped() {
    // After 3s highlight the I Have Paid button
    setTimeout(() => {
      const box = document.getElementById('paidBox');
      box.style.border = '2px solid #e65c00';
      box.style.background = '#fff7ed';
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 3000);
  }

  function selPay(m) {
    method = m;
    ['UPI','COD'].forEach(k => {
      const pc = document.getElementById('pc-' + k);
      if (pc) pc.classList.remove('sel');
      const panel = document.getElementById(k === 'UPI' ? 'upiPanel' : 'codPanel');
      if (panel) panel.style.display = 'none';
    });
    document.getElementById('pc-' + m).classList.add('sel');
    document.getElementById(m === 'UPI' ? 'upiPanel' : 'codPanel').style.display = 'block';
    document.getElementById('payErr').style.display = 'none';
    const btn = document.getElementById('payBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.innerHTML = m === 'UPI'
      ? '<i class="fas fa-qrcode" style="margin-right:8px;"></i>Pay ₹<?= $total ?> via UPI'
      : '<i class="fas fa-check-circle" style="margin-right:8px;"></i>Place Order · Cash ₹<?= $total ?>';
  }

  function copyUpiId() {
    const id = document.getElementById('upiIdText').textContent.trim();
    const btn = document.getElementById('copyBtn');
    const doSuccess = () => {
      btn.innerHTML = '<i class="fas fa-check" style="margin-right:4px;"></i>Copied!';
      btn.style.background = '#16a34a';
      setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-copy" style="margin-right:4px;"></i>Copy';
        btn.style.background = '#e65c00';
      }, 2500);
    };
    navigator.clipboard ? navigator.clipboard.writeText(id).then(doSuccess).catch(() => {
      const el = document.createElement('textarea');
      el.value = id; document.body.appendChild(el); el.select();
      document.execCommand('copy'); document.body.removeChild(el); doSuccess();
    }) : (() => {
      const el = document.createElement('textarea');
      el.value = id; document.body.appendChild(el); el.select();
      document.execCommand('copy'); document.body.removeChild(el); doSuccess();
    })();
  }

  function confirmUpiPaid() {
    if (!confirm('Confirm you have completed ₹<?= $total ?> UPI payment?')) return;
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:7px;"></i>Placing order…';
    window.location.href = `place_order.php?mode=UPI&utr=UPI${Date.now()}&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
  }

  function placeOrder() {
    if (!method) { document.getElementById('payErr').style.display = 'block'; return; }
    if (method === 'UPI') {
      document.getElementById('paidBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    if (!confirm(`Confirm order · ₹<?= $total ?> by Cash?`)) return;
    window.location.href = `place_order.php?mode=COD&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
  }
</script>
</body>
</html>