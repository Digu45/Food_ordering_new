<?php
session_start();
require_once '../config.php';
require_once '../connection.php';

if (!empty($_SESSION['admin'])) { header('Location: dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    $s = $pdo->prepare("SELECT * FROM admin WHERE username=? LIMIT 1");
    $s->execute([$u]);
    $a = $s->fetch();
    // Support plain text OR bcrypt hashed
    if ($a && ($p === $a['password'] || password_verify($p, $a['password']))) {
        $_SESSION['admin'] = $a['username'];
        header('Location: dashboard.php'); exit;
    }
    $err = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Login | <?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>* { font-family:'DM Sans',sans-serif; } .font-display { font-family:'Playfair Display',serif; } input { outline:none; }</style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm">
  <div class="text-center mb-8">
    <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <i class="fas fa-lock text-white text-2xl"></i>
    </div>
    <h1 class="font-display text-3xl font-bold text-white"><?= RESTAURANT_NAME ?></h1>
    <p class="text-gray-400 mt-1">Admin Panel</p>
  </div>
  <div class="bg-white rounded-3xl shadow-2xl p-8">
    <?php if ($err): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
      <i class="fas fa-exclamation-circle mr-2"></i><?= $err ?>
    </div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
        <input type="text" name="username" placeholder="admin"
               class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-orange-400"/>
      </div>
      <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
        <input type="password" name="password" placeholder="••••••"
               class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 text-sm focus:border-orange-400"/>
      </div>
      <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-red-500 text-white py-3 rounded-xl font-bold">
        <i class="fas fa-sign-in-alt mr-2"></i>Login
      </button>
    </form>
    <p class="text-center text-xs text-gray-400 mt-4">Default: <code class="bg-gray-100 px-2 py-0.5 rounded">digu / 1441</code></p>
  </div>
</div>
</body>
</html>
