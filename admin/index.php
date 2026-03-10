<?php
session_start();
require_once '../config.php';
require_once '../connection.php';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    $row = $pdo->prepare("SELECT * FROM admin WHERE username=? LIMIT 1");
    $row->execute([$u]);
    $admin = $row->fetch();
    if ($admin && ($admin['password'] === $p || password_verify($p, $admin['password']))) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username']  = $u;
        header('Location: dashboard.php'); exit;
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Login | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
body { background: linear-gradient(135deg,#1a0a00,#2d1200); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'DM Sans',sans-serif; }
</style>
</head>
<body>
<div style="width:100%;max-width:400px;padding:24px;">
  <div style="background:#fff;border-radius:24px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="text-align:center;margin-bottom:28px;">
      <div style="width:70px;height:70px;background:linear-gradient(135deg,#e65c00,#dc2626);border-radius:20px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
        <i class="fas fa-user-shield" style="color:#fff;font-size:28px;"></i>
      </div>
      <h1 style="font-size:22px;font-weight:800;color:#111;">Admin Panel</h1>
      <p style="color:#9ca3af;font-size:13px;margin-top:4px;"><?= RESTAURANT_NAME ?></p>
    </div>
    <?php if ($error): ?>
    <div style="background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:12px;font-size:13px;margin-bottom:16px;">
      <i class="fas fa-exclamation-circle" style="margin-right:7px;"></i><?= $error ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div style="margin-bottom:14px;">
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Username</label>
        <input type="text" name="username" placeholder="Enter username" required
               style="width:100%;padding:13px 14px;border:2px solid #e5e7eb;border-radius:12px;font-size:14px;outline:none;"/>
      </div>
      <div style="margin-bottom:20px;">
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Password</label>
        <input type="password" name="password" placeholder="Enter password" required
               style="width:100%;padding:13px 14px;border:2px solid #e5e7eb;border-radius:12px;font-size:14px;outline:none;"/>
      </div>
      <button type="submit" style="width:100%;padding:15px;border:none;border-radius:14px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-size:16px;font-weight:700;cursor:pointer;">
        <i class="fas fa-sign-in-alt" style="margin-right:8px;"></i>Login
      </button>
    </form>
    <div style="text-align:center;margin-top:16px;">
      <a href="../index.php" style="color:#9ca3af;font-size:13px;text-decoration:none;">← Back to Restaurant</a>
    </div>
  </div>
</div>
</body>
</html>
