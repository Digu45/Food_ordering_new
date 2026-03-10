<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

if (empty($_SESSION['mobile_verified'])) { header('Location: login.php'); exit; }

$device_id = $_SESSION['unique_device_id'] ?? '';

// Clear cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    if ($device_id) $pdo->prepare("DELETE FROM menu_items WHERE DeviceID=?")->execute([$device_id]);
    header('Location: home.php'); exit;
}

$items = [];
if ($device_id) {
    $s = $pdo->prepare("SELECT * FROM menu_items WHERE DeviceID=?");
    $s->execute([$device_id]);
    $items = $s->fetchAll();
}

$sub   = array_sum(array_map(fn($r) => $r['Rate'] * $r['Quantity'], $items));
$cgst  = round($sub * CGST_RATE / 100, 2);
$sgst  = round($sub * SGST_RATE / 100, 2);
$total = round($sub + $cgst + $sgst);
$roff  = round($total - ($sub + $cgst + $sgst), 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Cart | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:#f1f5f9; min-height:100vh; padding-bottom:100px; }
input,textarea,button { font-family:inherit; outline:none; }
.qty-wrap { display:flex; align-items:center; border:2px solid #e65c00; border-radius:8px; overflow:hidden; }
.qbtn { width:30px; height:30px; background:#e65c00; color:#fff; border:none; font-size:17px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.qnum { min-width:32px; text-align:center; font-weight:700; font-size:13px; color:#e65c00; }
.toast { position:fixed; bottom:90px; left:50%; transform:translateX(-50%); background:#1a1a2e; color:#fff; padding:9px 20px; border-radius:999px; font-size:13px; z-index:999; opacity:0; transition:opacity .3s; pointer-events:none; }
.toast.show { opacity:1; }
.otype-card { border:2px solid #e5e7eb; border-radius:16px; padding:16px; cursor:pointer; transition:all .2s; flex:1; text-align:center; }
.otype-card.sel { border-color:#e65c00; background:#fff7ed; }
.inp { width:100%; padding:13px 14px; border:2px solid #e5e7eb; border-radius:12px; font-size:14px; background:#fafafa; transition:border-color .2s; }
.inp:focus { border-color:#e65c00; background:#fff; }
</style>
</head>
<body>

<!-- Header -->
<div style="background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08);position:sticky;top:0;z-index:40;">
  <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;max-width:560px;margin:0 auto;">
    <a href="home.php" style="width:36px;height:36px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;flex-shrink:0;">
      <i class="fas fa-arrow-left" style="font-size:13px;"></i>
    </a>
    <div style="flex:1;">
      <div style="font-family:'Playfair Display',serif;font-size:19px;font-weight:700;color:#111;">Your Cart</div>
      <div style="font-size:12px;color:#9ca3af;"><?= RESTAURANT_NAME ?></div>
    </div>
    <?php if (!empty($items)): ?>
    <form method="POST" onsubmit="return confirm('Clear all items?')">
      <input type="hidden" name="clear" value="1"/>
      <button style="background:none;border:none;color:#ef4444;font-size:13px;font-weight:600;cursor:pointer;">
        <i class="fas fa-trash" style="margin-right:4px;"></i>Clear
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div style="max-width:560px;margin:0 auto;padding:16px;">

<?php if (empty($items)): ?>
<div style="text-align:center;padding:80px 20px;">
  <div style="font-size:64px;margin-bottom:16px;">🛒</div>
  <div style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#111;margin-bottom:8px;">Cart is empty</div>
  <p style="color:#9ca3af;margin-bottom:24px;">Add some delicious items from our menu</p>
  <a href="home.php" style="display:inline-block;background:#e65c00;color:#fff;padding:12px 28px;border-radius:14px;font-weight:700;text-decoration:none;">Browse Menu</a>
</div>

<?php else: ?>

<!-- Order Type -->
<div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
  <p style="font-weight:700;color:#111;font-size:14px;margin-bottom:12px;">
    <i class="fas fa-concierge-bell" style="color:#e65c00;margin-right:7px;"></i>How would you like your order?
  </p>
  <div style="display:flex;gap:10px;">
    <div class="otype-card sel" id="ot-dinein" onclick="selectType('Dine-in')">
      <div style="font-size:26px;margin-bottom:6px;">🍽️</div>
      <div style="font-weight:700;font-size:13px;color:#111;">Dine-in</div>
      <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Eat at restaurant</div>
    </div>
    <div class="otype-card" id="ot-takeaway" onclick="selectType('Takeaway')">
      <div style="font-size:26px;margin-bottom:6px;">🛍️</div>
      <div style="font-weight:700;font-size:13px;color:#111;">Takeaway</div>
      <div style="font-size:11px;color:#9ca3af;margin-top:2px;">Pick up yourself</div>
    </div>
  </div>

  <!-- Takeaway address fields -->
  <div id="addressSection" style="display:none;margin-top:14px;border-top:1px solid #f3f4f6;padding-top:14px;">
    <p style="font-size:12px;font-weight:600;color:#6b7280;margin-bottom:10px;">Your pickup / contact details</p>
    <div style="margin-bottom:10px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Area / Locality</label>
      <input type="text" id="inp_area" class="inp" placeholder="e.g. CBS Stand, Mahadwar Road"/>
    </div>
    <div style="margin-bottom:10px;">
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Full Address</label>
      <textarea id="inp_address" class="inp" rows="2" placeholder="House no, Building, Street..." style="resize:none;"></textarea>
    </div>
    <div>
      <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Landmark</label>
      <input type="text" id="inp_landmark" class="inp" placeholder="e.g. Near City Mall"/>
    </div>
  </div>
</div>

<!-- Cart items -->
<div style="background:#fff;border-radius:18px;overflow:hidden;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
  <div style="padding:14px 16px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:14px;color:#111;">
    <?= count($items) ?> Item<?= count($items)>1?'s':'' ?>
  </div>
  <div id="cartItems">
  <?php foreach ($items as $row):
    $isVeg  = $row['MenuTypeId'] == 1;
    $rowSub = $row['Rate'] * $row['Quantity'];
  ?>
  <div class="cart-row" style="display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid #f9fafb;"
       data-id="<?= $row['MenuID'] ?>" data-price="<?= $row['Rate'] ?>">
    <?php if ($row['MenuImageUrl']): ?>
    <img src="<?= htmlspecialchars($row['MenuImageUrl']) ?>" style="width:60px;height:52px;object-fit:cover;border-radius:10px;flex-shrink:0;" onerror="this.style.display='none'"/>
    <?php else: ?>
    <div style="width:60px;height:52px;background:#f3f4f6;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px;">🍽️</div>
    <?php endif; ?>
    <div style="flex:1;min-width:0;">
      <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
        <span style="width:12px;height:12px;border-radius:2px;border:2px solid <?= $isVeg?'#16a34a':'#dc2626' ?>;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
          <span style="width:6px;height:6px;border-radius:50%;background:<?= $isVeg?'#16a34a':'#dc2626' ?>;"></span>
        </span>
        <span style="font-weight:600;font-size:14px;color:#111;"><?= htmlspecialchars($row['MenuName']) ?></span>
      </div>
      <div style="font-size:12px;color:#9ca3af;margin-bottom:4px;">₹<?= number_format($row['Rate'],2) ?> each</div>
      <button style="font-size:12px;color:#e65c00;font-weight:600;background:none;border:none;cursor:pointer;padding:0;"
              onclick="openInstr('<?= $row['MenuID'] ?>','<?= addslashes($row['Instructions']??'') ?>')">
        <i class="fas fa-pen" style="font-size:10px;margin-right:4px;"></i><?= $row['Instructions'] ? 'Edit note' : 'Add note' ?>
      </button>
      <?php if ($row['Instructions']): ?>
      <p style="font-size:11px;color:#9ca3af;margin-top:2px;font-style:italic;">"<?= htmlspecialchars($row['Instructions']) ?>"</p>
      <?php endif; ?>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;flex-shrink:0;">
      <span class="item-total" style="font-weight:700;font-size:14px;color:#111;">₹<?= number_format($rowSub,2) ?></span>
      <div class="qty-wrap">
        <button class="qbtn" onclick="decQty(this)">−</button>
        <span class="qnum qty-val"><?= $row['Quantity'] ?></span>
        <button class="qbtn" onclick="incQty(this)">+</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>

<!-- Bill -->
<div style="background:#fff;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
  <div style="font-weight:700;color:#111;font-size:14px;margin-bottom:12px;">Bill Details</div>
  <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:8px;"><span>Item Total</span><span id="bSub">₹<?= number_format($sub,2) ?></span></div>
  <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:8px;"><span>CGST <?= CGST_RATE ?>%</span><span id="bCgst">₹<?= $cgst ?></span></div>
  <div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-bottom:8px;"><span>SGST <?= SGST_RATE ?>%</span><span id="bSgst">₹<?= $sgst ?></span></div>
  <div style="display:flex;justify-content:space-between;font-size:13px;color:#9ca3af;margin-bottom:10px;"><span>Round Off</span><span id="bRoff">₹<?= $roff ?></span></div>
  <div style="display:flex;justify-content:space-between;font-weight:700;font-size:16px;border-top:1px solid #f3f4f6;padding-top:10px;">
    <span>Total</span><span style="color:#e65c00;" id="bTotal">₹<?= $total ?></span>
  </div>
</div>
<?php endif; ?>
</div>

<!-- Bottom CTA -->
<?php if (!empty($items)): ?>
<div style="position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #f3f4f6;padding:14px 16px;">
  <div style="max-width:560px;margin:0 auto;">
    <button onclick="goCheckout()" style="display:block;width:100%;padding:16px;border:none;border-radius:14px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-size:16px;font-weight:700;cursor:pointer;">
      Proceed to Pay · ₹<span id="payAmt"><?= $total ?></span>
    </button>
  </div>
</div>
<?php endif; ?>

<!-- Instruction modal -->
<div id="instrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;align-items:flex-end;">
  <div style="background:#fff;width:100%;max-width:560px;margin:0 auto;border-radius:20px 20px 0 0;padding:20px;">
    <div style="font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:12px;">Special Instructions</div>
    <textarea id="instrText" rows="3" placeholder="e.g. Less spicy, no onion..." class="inp" style="resize:none;margin-bottom:12px;"></textarea>
    <input type="hidden" id="instrId"/>
    <div style="display:flex;gap:10px;">
      <button onclick="closeInstr()" style="flex:1;padding:13px;border:2px solid #e5e7eb;border-radius:12px;background:none;font-weight:600;cursor:pointer;color:#6b7280;">Cancel</button>
      <button onclick="saveInstr()" style="flex:1;padding:13px;border:none;border-radius:12px;background:#e65c00;color:#fff;font-weight:700;cursor:pointer;">Save</button>
    </div>
  </div>
</div>

<div id="toast" class="toast"></div>

<script>
const CGST = <?= CGST_RATE ?>, SGST = <?= SGST_RATE ?>;
let orderType = 'Dine-in';

function selectType(t) {
  orderType = t;
  document.getElementById('ot-dinein').classList.toggle('sel', t === 'Dine-in');
  document.getElementById('ot-takeaway').classList.toggle('sel', t === 'Takeaway');
  document.getElementById('addressSection').style.display = t === 'Takeaway' ? 'block' : 'none';
}

function goCheckout() {
  if (orderType === 'Takeaway') {
    const area = document.getElementById('inp_area').value.trim();
    const addr = document.getElementById('inp_address').value.trim();
    if (!area || !addr) { alert('Please enter your Area and Address for Takeaway.'); return; }
    const params = new URLSearchParams({
      type: orderType,
      area: area,
      address: addr,
      landmark: document.getElementById('inp_landmark').value.trim()
    });
    window.location.href = 'checkout.php?' + params.toString();
  } else {
    window.location.href = 'checkout.php?type=Dine-in';
  }
}

function recalc() {
  let sub = 0;
  document.querySelectorAll('.cart-row').forEach(r => {
    const p = parseFloat(r.dataset.price), q = parseInt(r.querySelector('.qty-val').textContent);
    r.querySelector('.item-total').textContent = '₹' + (p*q).toFixed(2);
    sub += p * q;
  });
  const cgst = sub*CGST/100, sgst = sub*SGST/100, grand = sub+cgst+sgst, rnd = Math.round(grand);
  document.getElementById('bSub').textContent  = '₹'+sub.toFixed(2);
  document.getElementById('bCgst').textContent = '₹'+cgst.toFixed(2);
  document.getElementById('bSgst').textContent = '₹'+sgst.toFixed(2);
  document.getElementById('bRoff').textContent = '₹'+(rnd-grand).toFixed(2);
  document.getElementById('bTotal').textContent = '₹'+rnd;
  const pa = document.getElementById('payAmt'); if(pa) pa.textContent = rnd;
}

function incQty(btn) {
  const row = btn.closest('.cart-row'), el = row.querySelector('.qty-val');
  const nq = parseInt(el.textContent)+1; el.textContent = nq;
  dbQty(row.dataset.id, nq); recalc();
}
function decQty(btn) {
  const row = btn.closest('.cart-row'), el = row.querySelector('.qty-val');
  const cur = parseInt(el.textContent);
  if (cur<=1){ if(!confirm('Remove this item?')) return; rmItem(row); return; }
  el.textContent = cur-1; dbQty(row.dataset.id, cur-1); recalc();
}
function rmItem(row) {
  const fd = new FormData(); fd.append('menu_id',row.dataset.id); fd.append('quantity',0);
  fetch('update_quantity.php',{method:'POST',body:fd}).then(()=>{ row.remove(); recalc(); showToast('Removed'); if(!document.querySelector('.cart-row')) location.reload(); });
}
function dbQty(id,qty) {
  const fd = new FormData(); fd.append('menu_id',id); fd.append('quantity',qty);
  fetch('update_quantity.php',{method:'POST',body:fd});
}
function openInstr(id,cur){ document.getElementById('instrId').value=id; document.getElementById('instrText').value=cur; document.getElementById('instrModal').style.display='flex'; }
function closeInstr(){ document.getElementById('instrModal').style.display='none'; }
function saveInstr(){
  const fd = new FormData(); fd.append('menu_id',document.getElementById('instrId').value); fd.append('instruction',document.getElementById('instrText').value);
  fetch('update_instruction.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success){closeInstr();showToast('Note saved!');location.reload();} });
}
function showToast(msg){ const t=document.getElementById('toast'); t.textContent=msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),2000); }
recalc();
</script>
</body>
</html>
