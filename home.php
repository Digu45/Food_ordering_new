<?php
session_start();
require_once 'config.php';
require_once 'connection.php';

// ── Must be logged in ──────────────────────────────────────
if (empty($_SESSION['mobile_verified'])) {
    header('Location: login.php'); exit;
}

// Assign device ID if missing
if (empty($_SESSION['unique_device_id'])) {
    $_SESSION['unique_device_id'] = md5(uniqid(rand(), true));
}
$device_id = $_SESSION['unique_device_id'];

// ── Fetch menu ─────────────────────────────────────────────
$allItems = $pdo->query("SELECT * FROM menu_master ORDER BY MenuSubCategoryName, MenuName")->fetchAll();

// Category list with counts
$catCounts = [];
foreach ($allItems as $i) {
    $c = $i['MenuSubCategoryName'] ?? 'Other';
    $catCounts[$c] = ($catCounts[$c] ?? 0) + 1;
}

// Active category filter
$activeCategory = urldecode($_GET['cat'] ?? '');
$displayItems   = $activeCategory
    ? array_filter($allItems, fn($i) => $i['MenuSubCategoryName'] === $activeCategory)
    : $allItems;

// Cart count
$cartCount = (int)$pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE DeviceID = ?")
               ->execute([$device_id]) ? $pdo->query("SELECT COUNT(*) FROM menu_items WHERE DeviceID = '$device_id'")->fetchColumn() : 0;
$cs = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE DeviceID = ?");
$cs->execute([$device_id]);
$cartCount = (int)$cs->fetchColumn();

// Load all cart quantities in one query for this device
$cartQtys = [];
$cq = $pdo->prepare("SELECT MenuID, Quantity FROM menu_items WHERE DeviceID = ?");
$cq->execute([$device_id]);
foreach ($cq->fetchAll() as $r) $cartQtys[$r['MenuID']] = $r['Quantity'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Menu | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  * { font-family:'DM Sans',sans-serif; }
  .font-display { font-family:'Playfair Display',serif; }

  /* Category pills */
  .cat-bar { display:flex; overflow-x:auto; gap:8px; padding:8px 16px; scrollbar-width:none; }
  .cat-bar::-webkit-scrollbar { display:none; }
  .pill { white-space:nowrap; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; border:none; cursor:pointer; transition:.2s; text-decoration:none; }
  .pill.on { background:#e65c00; color:#fff; }
  .pill:not(.on) { background:#f3f4f6; color:#374151; }

  /* Menu card */
  .menu-card { background:#fff; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
  .menu-img { width:90px; height:80px; object-fit:cover; border-radius:10px; flex-shrink:0; }

  /* Veg / Non-veg dot */
  .vdot, .nvdot { width:14px; height:14px; border-radius:2px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
  .vdot  { border:2px solid #16a34a; }
  .nvdot { border:2px solid #dc2626; }
  .vdot::after, .nvdot::after { content:''; width:6px; height:6px; border-radius:50%; }
  .vdot::after  { background:#16a34a; }
  .nvdot::after { background:#dc2626; }

  /* Qty controls */
  .qty-wrap { display:flex; align-items:center; border:2px solid #e65c00; border-radius:8px; overflow:hidden; }
  .qbtn { width:28px; height:28px; background:#e65c00; color:#fff; border:none; font-size:17px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; }
  .qnum { min-width:30px; text-align:center; font-weight:700; font-size:13px; color:#e65c00; }
  .add-btn { background:#e65c00; color:#fff; border:none; padding:6px 16px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; white-space:nowrap; }

  /* Bottom nav */
  .bnav { position:fixed; bottom:0; left:0; right:0; z-index:100; background:#1a1a2e; display:flex; justify-content:space-around; padding:10px 0 env(safe-area-inset-bottom,16px); }
  .nitem { display:flex; flex-direction:column; align-items:center; color:#9ca3af; text-decoration:none; font-size:10px; gap:2px; }
  .nitem i { font-size:20px; }
  .nitem.on, .nitem:hover { color:#f9a84d; }

  /* Toggle */
  .tog { width:42px; height:22px; background:#d1d5db; border-radius:999px; position:relative; cursor:pointer; transition:background .2s; flex-shrink:0; }
  .tog.on { background:#e65c00; }
  .tog span { position:absolute; top:2px; left:2px; width:18px; height:18px; background:#fff; border-radius:50%; transition:left .2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
  .tog.on span { left:22px; }

  /* Detail sheet */
  .sheet { position:fixed; inset:0; z-index:200; background:rgba(0,0,0,.5); display:flex; align-items:flex-end; opacity:0; pointer-events:none; transition:opacity .25s; }
  .sheet.open { opacity:1; pointer-events:all; }
  .sheet-body { background:#fff; border-radius:20px 20px 0 0; width:100%; max-height:85vh; overflow-y:auto; transform:translateY(100%); transition:transform .3s; }
  .sheet.open .sheet-body { transform:translateY(0); }

  /* Toast */
  .toast { position:fixed; bottom:72px; left:50%; transform:translateX(-50%); background:#1a1a2e; color:#fff; padding:9px 20px; border-radius:999px; font-size:13px; z-index:999; opacity:0; transition:opacity .3s; white-space:nowrap; pointer-events:none; }
  .toast.show { opacity:1; }

  .section-hdr { padding:6px 16px; font-weight:700; font-size:11px; color:#9ca3af; letter-spacing:.8px; text-transform:uppercase; background:#f9fafb; }
  .pb76 { padding-bottom:76px; }
</style>
</head>
<body class="bg-gray-50">

<!-- ── Top bar ──────────────────────────────────────────── -->
<div class="bg-white shadow-sm sticky top-0 z-50">
  <div class="flex items-center justify-between px-4 py-3">
    <div>
      <h1 class="font-display text-lg font-bold text-gray-900 leading-tight"><?= RESTAURANT_NAME ?></h1>
      <p class="text-xs text-gray-400"><i class="fas fa-map-marker-alt text-orange-500 mr-1"></i><?= RESTAURANT_ADDRESS ?></p>
    </div>
    <div class="flex items-center gap-2">
      <?php if (!empty($_SESSION['mobile_verified'])): ?>
      <span class="text-xs text-gray-500 hidden sm:block"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
      <a href="logout.php" onclick="return confirm('Logout?')"
         class="w-9 h-9 bg-red-50 rounded-full flex items-center justify-center text-red-400">
        <i class="fas fa-sign-out-alt text-sm"></i>
      </a>
      <?php else: ?>
      <a href="login.php" class="bg-orange-500 text-white text-xs font-bold px-3 py-2 rounded-xl">Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Search -->
  <div class="px-4 pb-2">
    <div class="relative">
      <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
      <input id="search" type="text" placeholder="Search dishes..."
             class="w-full pl-9 pr-4 py-2.5 bg-gray-100 rounded-xl text-sm outline-none"/>
    </div>
  </div>

  <!-- Veg toggle -->
  <div class="flex items-center gap-2 px-4 pb-2">
    <div id="vegTog" class="tog"><span></span></div>
    <span class="text-sm font-semibold text-gray-700">Veg Only</span>
  </div>

  <!-- Category pills -->
  <div class="cat-bar">
    <a href="home.php" class="pill <?= !$activeCategory ? 'on' : '' ?>">All (<?= count($allItems) ?>)</a>
    <?php foreach ($catCounts as $cat => $cnt): ?>
    <a href="home.php?cat=<?= urlencode($cat) ?>"
       class="pill <?= $activeCategory === $cat ? 'on' : '' ?>">
      <?= htmlspecialchars($cat) ?> (<?= $cnt ?>)
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Menu list ─────────────────────────────────────────── -->
<div class="pb76" id="menuList">

<?php
$lastCat = '';
foreach ($displayItems as $item):
    $id    = $item['MenuId'];
    $name  = htmlspecialchars($item['MenuName']);
    $rate  = (float)$item['Rate'];
    $img   = htmlspecialchars($item['MenuImageUrl'] ?? '');
    $desc  = htmlspecialchars($item['Description']  ?? '');
    $cat   = $item['MenuSubCategoryName'] ?? 'Other';
    $isVeg = $item['MenuTypeId'] <= 1;
    $qty   = $cartQtys[$id] ?? 0;

    // Section header
    if (!$activeCategory && $cat !== $lastCat):
        $lastCat = $cat;
?>
<div class="section-hdr mt-2"><?= htmlspecialchars($cat) ?></div>
<?php endif; ?>

<div class="menu-card mx-3 my-2 p-3 flex gap-3 menu-row"
     data-name="<?= strtolower($name) ?>"
     data-id="<?= $id ?>" data-nm="<?= $name ?>"
     data-price="<?= $rate ?>" data-img="<?= $img ?>"
     data-desc="<?= $desc ?>" data-veg="<?= $isVeg ? '1' : '0' ?>"
     onclick="openSheet(this)">

  <div class="flex-1 min-w-0">
    <div class="flex items-center gap-1.5 mb-1">
      <span class="<?= $isVeg ? 'vdot' : 'nvdot' ?>"></span>
      <span class="font-semibold text-gray-900 text-sm leading-tight"><?= $name ?></span>
    </div>
    <p class="text-orange-600 font-bold text-sm mb-1">₹<?= number_format($rate, 2) ?></p>
    <?php if ($desc): ?>
    <p class="text-gray-400 text-xs leading-snug line-clamp-2"><?= $desc ?></p>
    <?php endif; ?>
  </div>

  <!-- Right: image always shown + button always at bottom -->
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0;width:90px;">
    <?php if ($img): ?>
    <img src="<?= $img ?>" class="menu-img" loading="lazy"
         onerror="this.outerHTML='<div class=\'menu-img\' style=\'background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:22px;\'>🍽️</div>'"/>
    <?php else: ?>
    <div class="menu-img" style="background:#f3f4f6;display:flex;align-items:center;justify-content:center;font-size:22px;">🍽️</div>
    <?php endif; ?>

    <?php if ($qty > 0): ?>
    <div class="qty-wrap" onclick="event.stopPropagation()">
      <button class="qbtn" onclick="chgQty(<?= $id ?>,-1,this)">−</button>
      <span class="qnum" id="q<?= $id ?>"><?= $qty ?></span>
      <button class="qbtn" onclick="chgQty(<?= $id ?>,1,this)">+</button>
    </div>
    <?php else: ?>
    <button class="add-btn" id="ab<?= $id ?>"
            onclick="event.stopPropagation();addCart(<?= $id ?>,this)">+ ADD</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<div id="noItems" class="hidden text-center py-20">
  <div class="text-5xl mb-3">🔍</div>
  <p class="text-gray-400 font-semibold">No items found</p>
</div>
</div>

<!-- ── Item detail sheet ─────────────────────────────────── -->
<div id="sheet" class="sheet" onclick="if(event.target===this)closeSheet()">
  <div class="sheet-body p-5">
    <div class="flex justify-between items-start mb-3">
      <h2 class="font-display text-xl font-bold pr-4" id="shName"></h2>
      <button onclick="closeSheet()" class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
        <i class="fas fa-times text-gray-500"></i>
      </button>
    </div>
    <img id="shImg" src="" class="w-full h-44 object-cover rounded-2xl mb-3"/>
    <p class="text-orange-600 font-bold text-xl mb-2" id="shPrice"></p>
    <p class="text-gray-500 text-sm leading-relaxed" id="shDesc"></p>
  </div>
</div>

<!-- ── Bottom nav ────────────────────────────────────────── -->
<nav class="bnav">
  <a href="home.php" class="nitem on"><i class="fas fa-utensils"></i><span>Menu</span></a>
  <a href="history.php" class="nitem"><i class="fas fa-receipt"></i><span>Orders</span></a>
  <a href="cart.php" class="nitem relative">
    <i class="fas fa-shopping-bag"></i><span>Cart</span>
    <span class="absolute -top-1 right-3 bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
          id="badge"><?= $cartCount ?></span>
  </a>
  <a href="tel:<?= RESTAURANT_PHONE ?>" class="nitem"><i class="fas fa-phone"></i><span>Call</span></a>
</nav>

<div id="toast" class="toast"></div>

<script>
let cartCount = <?= $cartCount ?>;

// ── Search ────────────────────────────────────────────────
document.getElementById('search').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  let n = 0;
  document.querySelectorAll('.menu-row').forEach(r => {
    const show = r.dataset.name.includes(q);
    r.style.display = show ? '' : 'none';
    if (show) n++;
  });
  document.querySelectorAll('.section-hdr').forEach(h => h.style.display = q ? 'none' : '');
  document.getElementById('noItems').classList.toggle('hidden', n > 0);
});

// ── Veg toggle ────────────────────────────────────────────
const tog = document.getElementById('vegTog');
let vegOn = localStorage.getItem('veg') === '1';
if (vegOn) tog.classList.add('on');
applyVeg();
tog.addEventListener('click', () => {
  vegOn = !vegOn;
  tog.classList.toggle('on', vegOn);
  localStorage.setItem('veg', vegOn ? '1' : '0');
  applyVeg();
});
function applyVeg() {
  document.querySelectorAll('.menu-row').forEach(r => {
    r.style.display = (vegOn && r.dataset.veg !== '1') ? 'none' : '';
  });
}

// ── Add to cart ───────────────────────────────────────────
function addCart(id, btn) {
  const card = document.querySelector(`.menu-row[data-id="${id}"]`);
  btn.disabled = true; btn.textContent = '...';
  post('submit_order.php', {
    MenuId: id, MenuName: card.dataset.nm,
    MenuImageUrl: card.dataset.img, Description: card.dataset.desc,
    Rate: card.dataset.price, Quantity: 1, Amount: card.dataset.price
  }).then(d => {
    if (d.success) {
      cartCount = d.cart_count;
      document.getElementById('badge').textContent = cartCount;
      btn.outerHTML = qtyHtml(id, 1);
      toast('Added 🛒');
    } else { btn.disabled = false; btn.textContent = '+ ADD'; }
  });
}

// ── Change qty ────────────────────────────────────────────
function chgQty(id, delta, btn) {
  const el  = document.getElementById('q' + id);
  const cur = parseInt(el.textContent);
  const nq  = cur + delta;
  if (nq < 1) {
    post('remove_order.php', { MenuID: id }).then(d => {
      cartCount = Math.max(0, cartCount - 1);
      document.getElementById('badge').textContent = cartCount;
      btn.closest('.qty-wrap').outerHTML =
        `<button class="add-btn" id="ab${id}" onclick="event.stopPropagation();addCart(${id},this)">+ ADD</button>`;
      toast('Removed');
    });
    return;
  }
  const card = document.querySelector(`.menu-row[data-id="${id}"]`);
  post('submit_order.php', {
    MenuId: id, MenuName: card.dataset.nm,
    MenuImageUrl: card.dataset.img, Description: card.dataset.desc,
    Rate: card.dataset.price, Quantity: nq, Amount: nq * parseFloat(card.dataset.price)
  }).then(d => {
    if (d.success) { el.textContent = nq; cartCount = d.cart_count; document.getElementById('badge').textContent = cartCount; }
  });
}

// ── Item detail sheet ─────────────────────────────────────
function openSheet(card) {
  if (event.target.closest('.qty-wrap,.add-btn')) return;
  document.getElementById('shName').textContent  = card.dataset.nm;
  document.getElementById('shPrice').textContent = '₹' + parseFloat(card.dataset.price).toFixed(2);
  document.getElementById('shDesc').textContent  = card.dataset.desc || 'No description.';
  const img = document.getElementById('shImg');
  img.src = card.dataset.img || '';
  img.style.display = card.dataset.img ? '' : 'none';
  document.getElementById('sheet').classList.add('open');
}
function closeSheet() { document.getElementById('sheet').classList.remove('open'); }

// ── Helpers ───────────────────────────────────────────────
function qtyHtml(id, q) {
  return `<div class="qty-wrap" onclick="event.stopPropagation()"><button class="qbtn" onclick="chgQty(${id},-1,this)">−</button><span class="qnum" id="q${id}">${q}</span><button class="qbtn" onclick="chgQty(${id},1,this)">+</button></div>`;
}
async function post(url, body) {
  const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  return r.json();
}
function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2000);
}
</script>
</body>
</html>
