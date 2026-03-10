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
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
        <div class="pay-card" id="pc-UPI" onclick="selPay('UPI')">
          <div style="font-size:26px;margin-bottom:6px;">📲</div>
          <div style="font-weight:700;font-size:13px;">UPI</div>
          <div style="font-size:11px;color:#9ca3af;margin-top:2px;">GPay·PhonePe</div>
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
    <div id="upiPanel" style="display:none;background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
      <div style="font-weight:700;font-size:15px;color:#111;margin-bottom:14px;text-align:center;">
        <i class="fas fa-qrcode" style="color:#e65c00;margin-right:7px;"></i>Pay ₹<?= $total ?> via UPI
      </div>

      <!-- Tab switcher: Mobile / Desktop -->
      <div style="display:flex;background:#f3f4f6;border-radius:12px;padding:4px;margin-bottom:16px;">
        <button id="tab-mobile" onclick="switchTab('mobile')"
          style="flex:1;padding:9px;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;">
          📱 On Mobile
        </button>
        <button id="tab-desktop" onclick="switchTab('desktop')"
          style="flex:1;padding:9px;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;background:transparent;color:#6b7280;">
          💻 On Desktop
        </button>
      </div>

      <!-- MOBILE TAB -->
      <div id="panel-mobile">
        <p style="font-size:12px;color:#6b7280;text-align:center;margin-bottom:12px;">
          Tap your UPI app below — amount & UPI ID are pre-filled
        </p>

        <!-- UPI app buttons -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
          <a href="<?= $upiLink ?>&amp;mc=5411" id="gpayBtn"
            style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:#fff;border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;font-weight:700;font-size:13px;color:#111;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Google_Pay_Logo_%282020%29.svg/120px-Google_Pay_Logo_%282020%29.svg.png" height="22" style="object-fit:contain;"/> GPay
          </a>
          <a href="phonepe://pay?pa=<?= urlencode(UPI_ID) ?>&pn=<?= urlencode(UPI_NAME) ?>&am=<?= $total ?>&cu=INR"
            style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:#fff;border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;font-weight:700;font-size:13px;color:#111;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/54/PhonePe_Logo.png/240px-PhonePe_Logo.png" height="22" style="object-fit:contain;"/> PhonePe
          </a>
          <a href="paytmmp://pay?pa=<?= urlencode(UPI_ID) ?>&pn=<?= urlencode(UPI_NAME) ?>&am=<?= $total ?>&cu=INR"
            style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:#fff;border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;font-weight:700;font-size:13px;color:#111;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/200px-Paytm_Logo_%28standalone%29.svg.png" height="22" style="object-fit:contain;"/> Paytm
          </a>
          <a href="<?= $upiLink ?>"
            style="display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:#fff;border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;font-weight:700;font-size:13px;color:#111;">
            <span style="font-size:20px;">📲</span> Other UPI
          </a>
        </div>

        <!-- OR: Manual UPI ID copy -->
        <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:12px 14px;margin-bottom:14px;">
          <div style="font-size:11px;color:#9a3412;font-weight:600;margin-bottom:6px;text-align:center;">— OR pay manually —</div>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <div>
              <div style="font-size:11px;color:#9a3412;">UPI ID</div>
              <div style="font-weight:700;color:#111;font-size:15px;" id="upiIdText"><?= UPI_ID ?></div>
            </div>
            <button onclick="copyUpiId()"
              style="padding:8px 14px;background:#e65c00;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;" id="copyBtn">
              <i class="fas fa-copy" style="margin-right:4px;"></i>Copy ID
            </button>
          </div>
          <div style="text-align:center;margin-top:6px;font-weight:900;color:#e65c00;font-size:22px;">₹<?= $total ?></div>
        </div>
      </div>

      <!-- DESKTOP TAB -->
      <div id="panel-desktop" style="display:none;">
        <p style="font-size:12px;color:#6b7280;text-align:center;margin-bottom:12px;">
          Open any UPI app on your phone and scan this QR code
        </p>
        <div style="text-align:center;margin-bottom:14px;">
          <div style="display:inline-block;padding:12px;border:2px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.06);">
            <img src="upi_qr.jpg" width="200" height="200" style="display:block;border-radius:8px;" alt="UPI QR"/>
          </div>
          <p style="font-size:11px;color:#9ca3af;margin-top:6px;">GPay · PhonePe · Paytm · Any UPI app</p>
        </div>
        <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:10px 14px;margin-bottom:14px;text-align:center;">
          <div style="font-size:11px;color:#9a3412;font-weight:600;">Pay to UPI ID: <strong><?= UPI_ID ?></strong></div>
          <div style="font-weight:900;color:#e65c00;font-size:24px;margin-top:4px;">₹<?= $total ?></div>
        </div>
      </div>

      <!-- UTR input + confirm (shown in both tabs) -->
      <div style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:12px;padding:14px;">
        <p style="font-size:12px;font-weight:700;color:#166534;margin-bottom:4px;">
          <i class="fas fa-shield-alt" style="margin-right:5px;"></i>Enter Transaction ID after paying
        </p>
        <p style="font-size:11px;color:#6b7280;margin-bottom:10px;">
          GPay/PhonePe → History → Last transaction → Copy UTR / Transaction ID
        </p>
        <input type="text" id="utrInput"
          placeholder="e.g. 407612345678 or T2503110012"
          style="width:100%;padding:12px 14px;border:2px solid #d1fae5;border-radius:10px;font-size:14px;font-family:monospace;background:#fff;outline:none;margin-bottom:10px;"
          oninput="this.style.borderColor=this.value.length>=8?'#22c55e':'#d1fae5'"
        />
        <button onclick="confirmUpiPaid()"
          style="width:100%;padding:14px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-weight:700;font-size:15px;border:none;border-radius:10px;cursor:pointer;box-shadow:0 4px 14px rgba(230,92,0,.35);">
          <i class="fas fa-check-circle" style="margin-right:7px;"></i>✅ Confirm Payment & Place Order
        </button>
        <p style="font-size:10px;color:#9ca3af;margin-top:8px;text-align:center;">
          Transaction ID is required to verify your payment with the restaurant
        </p>
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
      ['UPI', 'COD'].forEach(k => {
        const pc = document.getElementById('pc-' + k);
        if (pc) pc.classList.remove('sel');
        const panel = document.getElementById({UPI:'upiPanel',COD:'codPanel'}[k]);
        if (panel) panel.style.display = 'none';
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
        COD: '<i class="fas fa-check-circle" style="margin-right:8px;"></i>Place Order · Pay ₹<?= $total ?> Cash',
      };
      btn.innerHTML = labels[m];
    }

    // Auto-detect mobile or desktop and switch tab
    window.addEventListener('load', () => {
      const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);
      switchTab(isMobile ? 'mobile' : 'desktop');
    });

    function switchTab(tab) {
      document.getElementById('panel-mobile').style.display  = tab === 'mobile'  ? 'block' : 'none';
      document.getElementById('panel-desktop').style.display = tab === 'desktop' ? 'block' : 'none';
      const activeStyle  = 'background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;';
      const inactiveStyle = 'background:transparent;color:#6b7280;';
      document.getElementById('tab-mobile').style.cssText  += tab === 'mobile'  ? activeStyle : inactiveStyle;
      document.getElementById('tab-desktop').style.cssText += tab === 'desktop' ? activeStyle : inactiveStyle;
    }

    function copyUpiId() {
      const id = document.getElementById('upiIdText').textContent;
      navigator.clipboard.writeText(id).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.innerHTML = '<i class="fas fa-check" style="margin-right:4px;"></i>Copied!';
        btn.style.background = '#16a34a';
        setTimeout(() => {
          btn.innerHTML = '<i class="fas fa-copy" style="margin-right:4px;"></i>Copy ID';
          btn.style.background = '#e65c00';
        }, 2000);
      });
    }

    // Called when customer taps Confirm Payment
    function confirmUpiPaid() {
      const utr = document.getElementById('utrInput').value.trim();
      if (!utr || utr.length < 8) {
        document.getElementById('utrInput').style.borderColor = '#dc2626';
        document.getElementById('utrInput').focus();
        alert('Please enter your UPI Transaction ID / UTR number after paying.\n\nOpen GPay → Last transaction → Copy the transaction ID.');
        return;
      }
      if (!confirm('Confirm ₹<?= $total ?> paid via UPI?\nTransaction ID: ' + utr)) return;
      const btn = event.target;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:7px;"></i>Placing order…';
      window.location.href = `place_order.php?mode=UPI&utr=${encodeURIComponent(utr)}&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
    }

    function placeOrder() {
      if (!method) {
        document.getElementById('payErr').style.display = 'block';
        return;
      }
      if (method === 'UPI') {
        // UPI is handled by confirmUpiPaid() button — scroll to it
        document.getElementById('paidConfirmBox').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.getElementById('paidConfirmBox').style.border = '2px solid #e65c00';
        return;
      }
      if (!confirm(`Confirm order · ₹<?= $total ?> by Cash?`)) return;
      window.location.href = `place_order.php?mode=${method}&type=${encodeURIComponent(orderType)}&table=${encodeURIComponent(tableId)}&area=${encodeURIComponent(area)}&address=${encodeURIComponent(address)}&landmark=${encodeURIComponent(landmark)}`;
    }
  </script>


</body>

</html>