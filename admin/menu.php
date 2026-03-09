<?php
require_once 'auth.php';
require_once '../config.php';
require_once '../connection.php';

$msg = ''; $msgType = 'green';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add' || $act === 'edit') {
        $fields = [
            $_POST['sub_cat'],
            $_POST['name'],
            $_POST['image_url'] ?? '',
            $_POST['description'] ?? '',
            (float)($_POST['rate'] ?? 0),
            (int)($_POST['type_id'] ?? 1),
        ];
        if ($act === 'add') {
            $pdo->prepare("INSERT INTO menu_master (MenuSubCategoryName,MenuName,MenuImageUrl,Description,Rate,MenuTypeId) VALUES(?,?,?,?,?,?)")
                ->execute($fields);
            $msg = '✅ Item added!';
        } else {
            $fields[] = (int)$_POST['mid'];
            $pdo->prepare("UPDATE menu_master SET MenuSubCategoryName=?,MenuName=?,MenuImageUrl=?,Description=?,Rate=?,MenuTypeId=? WHERE MenuId=?")
                ->execute($fields);
            $msg = '✅ Item updated!';
        }
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM menu_master WHERE MenuId=?")->execute([(int)$_POST['mid']]);
        $msg = '🗑️ Item deleted.'; $msgType = 'red';
    }
}

$items   = $pdo->query("SELECT * FROM menu_master ORDER BY MenuSubCategoryName, MenuName")->fetchAll();
$subcats = array_unique(array_column($items, 'MenuSubCategoryName'));

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM menu_master WHERE MenuId=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Menu Manager | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  * { font-family:'DM Sans',sans-serif; }
  .font-display { font-family:'Playfair Display',serif; }
  .field { width:100%; border:2px solid #e5e7eb; border-radius:10px; padding:10px 14px; font-size:14px; outline:none; transition:.2s; }
  .field:focus { border-color:#e65c00; }
</style>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Nav -->
<div class="bg-white border-b border-gray-200 sticky top-0 z-40">
  <div class="flex items-center justify-between px-4 py-4 max-w-6xl mx-auto">
    <div class="flex items-center gap-3">
      <a href="dashboard.php" class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
        <i class="fas fa-arrow-left text-sm"></i>
      </a>
      <h1 class="font-display text-xl font-bold text-gray-900">Menu Manager</h1>
    </div>
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
            class="bg-orange-500 text-white px-4 py-2 rounded-xl font-semibold text-sm">
      <i class="fas fa-plus mr-1"></i>Add Item
    </button>
  </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-5">

  <?php if ($msg): ?>
  <div class="mb-4 bg-<?= $msgType ?>-50 border border-<?= $msgType ?>-200 text-<?= $msgType ?>-700 px-4 py-3 rounded-xl text-sm">
    <?= $msg ?>
  </div>
  <?php endif; ?>

  <!-- Search + stats -->
  <div class="bg-white rounded-2xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-4">
    <input type="text" id="search" placeholder="Search menu…"
           class="field w-full sm:w-64" oninput="filterMenu(this.value)"/>
    <div class="flex gap-4 text-sm">
      <span class="text-gray-500">Total: <strong><?= count($items) ?></strong></span>
      <span class="text-green-600">Veg: <strong><?= count(array_filter($items, fn($i)=>$i['MenuTypeId']<=1)) ?></strong></span>
      <span class="text-red-600">Non-veg: <strong><?= count(array_filter($items, fn($i)=>$i['MenuTypeId']==2)) ?></strong></span>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b text-gray-500 font-semibold text-xs uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left">Item</th>
            <th class="px-4 py-3 text-left hidden md:table-cell">Category</th>
            <th class="px-4 py-3 text-right">Price</th>
            <th class="px-4 py-3 text-center">Type</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="menuTbl">
        <?php foreach ($items as $item): $isVeg = $item['MenuTypeId'] <= 1; ?>
        <tr class="border-b border-gray-50 hover:bg-gray-50 menu-row" data-nm="<?= strtolower(htmlspecialchars($item['MenuName'])) ?>">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <?php if ($item['MenuImageUrl']): ?>
              <img src="<?= htmlspecialchars($item['MenuImageUrl']) ?>" class="w-10 h-9 object-cover rounded-lg" onerror="this.style.display='none'"/>
              <?php endif; ?>
              <div>
                <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['MenuName']) ?></p>
                <p class="text-xs text-gray-400 truncate max-w-xs"><?= htmlspecialchars($item['Description'] ?? '') ?></p>
              </div>
            </div>
          </td>
          <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= htmlspecialchars($item['MenuSubCategoryName']) ?></td>
          <td class="px-4 py-3 text-right font-bold text-gray-800">₹<?= number_format($item['Rate'],2) ?></td>
          <td class="px-4 py-3 text-center text-xs font-bold <?= $isVeg?'text-green-600':'text-red-600' ?>">
            <?= $isVeg ? '🟢 Veg' : '🔴 Non-Veg' ?>
          </td>
          <td class="px-4 py-3 text-center">
            <div class="flex justify-center gap-3">
              <a href="?edit=<?= $item['MenuId'] ?>" class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></a>
              <form method="POST" class="inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="mid" value="<?= $item['MenuId'] ?>"/>
                <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add / Edit modal -->
<div id="addModal" class="<?= $edit ? '' : 'hidden' ?> fixed inset-0 bg-black/50 z-50 flex items-end md:items-center justify-center p-4">
  <div class="bg-white rounded-3xl w-full max-w-md p-6 overflow-y-auto max-h-[90vh]">
    <div class="flex justify-between items-center mb-4">
      <h2 class="font-display text-xl font-bold"><?= $edit ? 'Edit' : 'Add' ?> Item</h2>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>"/>
      <?php if ($edit): ?><input type="hidden" name="mid" value="<?= $edit['MenuId'] ?>"/><?php endif; ?>
      <div class="space-y-3">
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-1">Item Name *</label>
          <input type="text" name="name" required class="field" value="<?= htmlspecialchars($edit['MenuName'] ?? '') ?>" placeholder="e.g. Butter Chicken"/>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-1">Category</label>
          <input type="text" name="sub_cat" class="field" list="cats" value="<?= htmlspecialchars($edit['MenuSubCategoryName'] ?? '') ?>" placeholder="e.g. Main Course"/>
          <datalist id="cats"><?php foreach ($subcats as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold text-gray-600 block mb-1">Price ₹ *</label>
            <input type="number" name="rate" step="0.01" required class="field" value="<?= $edit['Rate'] ?? '' ?>" placeholder="250"/>
          </div>
          <div>
            <label class="text-xs font-semibold text-gray-600 block mb-1">Type</label>
            <select name="type_id" class="field">
              <option value="1" <?= ($edit['MenuTypeId']??1)==1?'selected':'' ?>>🟢 Veg</option>
              <option value="2" <?= ($edit['MenuTypeId']??1)==2?'selected':'' ?>>🔴 Non-Veg</option>
            </select>
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-1">Image URL</label>
          <input type="url" name="image_url" class="field" value="<?= htmlspecialchars($edit['MenuImageUrl'] ?? '') ?>" placeholder="https://..."/>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600 block mb-1">Description</label>
          <textarea name="description" rows="2" class="field resize-none" placeholder="Short description"><?= htmlspecialchars($edit['Description'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="flex gap-3 mt-5">
        <button type="button" onclick="closeModal()" class="flex-1 py-3 rounded-xl border-2 border-gray-200 font-semibold text-gray-600">Cancel</button>
        <button type="submit" class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-bold"><?= $edit ? 'Update' : 'Add Item' ?></button>
      </div>
    </form>
  </div>
</div>

<script>
function closeModal() {
  document.getElementById('addModal').classList.add('hidden');
  if (location.search.includes('edit=')) location.href = 'menu.php';
}
function filterMenu(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.menu-row').forEach(r => r.style.display = r.dataset.nm.includes(q) ? '' : 'none');
}
</script>
</body>
</html>
