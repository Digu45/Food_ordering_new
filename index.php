<?php
session_start();
require_once 'config.php';
if (!empty($_SESSION['mobile_verified'])) {
  header('Location: home.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= RESTAURANT_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      font-family: 'DM Sans', sans-serif;
    }

    body {
      background: linear-gradient(160deg, #1a0a00, #2d1200, #1a0500);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
      padding: 32px 24px;
    }

    @keyframes floatY {

      0%,
      100% {
        transform: translateY(0)
      }

      50% {
        transform: translateY(-14px)
      }
    }

    .anim {
      animation: floatY 3.2s ease-in-out infinite;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(24px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    .fu1 {
      animation: fadeUp .6s .1s both
    }

    .fu2 {
      animation: fadeUp .6s .25s both
    }

    .fu3 {
      animation: fadeUp .6s .4s both
    }

    .fu4 {
      animation: fadeUp .6s .55s both
    }
  </style>
</head>

<body>
  <div class="anim fu1" style="margin-bottom:24px;">
    <div style="width:90px;height:90px;background:linear-gradient(135deg,#f97316,#dc2626);border-radius:28px;display:flex;align-items:center;justify-content:center;box-shadow:0 20px 60px rgba(230,92,0,.4);">
      <i class="fas fa-utensils" style="color:#fff;font-size:38px;"></i>
    </div>
  </div>
  <h1 class="fu2" style="font-family:'Playfair Display',serif;font-size:clamp(32px,8vw,48px);font-weight:900;color:#fff;margin-bottom:8px;"><?= RESTAURANT_NAME ?></h1>
  <p class="fu2" style="color:#fb923c;font-size:14px;margin-bottom:4px;"><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i><?= RESTAURANT_ADDRESS ?></p>
  <p class="fu2" style="color:rgba(255,255,255,.4);font-size:13px;margin-bottom:36px;"><i class="fas fa-phone" style="margin-right:5px;"></i><?= RESTAURANT_PHONE ?></p>

  <!-- Order type pills -->
  <div class="fu3" style="display:flex;gap:12px;margin-bottom:44px;flex-wrap:wrap;justify-content:center;">
    <?php foreach ([['🍽️', 'Dine-in'], ['🛍️', 'Takeaway']] as [$em, $lb]): ?>
      <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:999px;padding:8px 18px;display:flex;align-items:center;gap:8px;">
        <span style="font-size:18px;"><?= $em ?></span>
        <span style="color:rgba(255,255,255,.8);font-size:13px;font-weight:600;"><?= $lb ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="fu4" style="width:100%;max-width:300px;">
    <a href="login.php" style="display:block;width:100%;padding:17px;border-radius:16px;background:linear-gradient(135deg,#e65c00,#f9a84d);color:#fff;font-weight:700;font-size:17px;text-decoration:none;box-shadow:0 8px 32px rgba(230,92,0,.45);">
      <i class="fas fa-arrow-right" style="margin-right:8px;"></i>Order Now
    </a>
    <p style="color:rgba(255,255,255,.3);font-size:12px;margin-top:14px;">Login required to view menu &amp; order</p>

    <!-- Admin Login -->
    <a href="admin/login.php" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;border-radius:16px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.45);font-weight:600;font-size:13px;text-decoration:none;margin-top:12px;transition:.2s;"
      onmouseover="this.style.background='rgba(255,255,255,.13)';this.style.color='rgba(255,255,255,.8)'"
      onmouseout="this.style.background='rgba(255,255,255,.07)';this.style.color='rgba(255,255,255,.45)'">
      <i class="fas fa-user-shield" style="font-size:13px;"></i> Admin Login
    </a>
  </div>
</body>

</html>