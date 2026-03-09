<?php
// includes/header.php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' | ' : '' ?><?= RESTAURANT_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        brand:  { DEFAULT:'#e65c00', light:'#f9a84d', dark:'#b34500' },
        surface:'#1a1a2e',
        card:   '#16213e',
      },
      fontFamily: {
        display: ['"Playfair Display"', 'serif'],
        body:    ['"DM Sans"', 'sans-serif'],
      }
    }
  }
}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  * { font-family: 'DM Sans', sans-serif; }
  h1,h2,h3,.font-display { font-family: 'Playfair Display', serif; }
  .veg-dot   { width:14px;height:14px;border:2px solid #16a34a;display:inline-flex;align-items:center;justify-content:center;border-radius:2px; }
  .veg-dot::after  { content:'';width:7px;height:7px;background:#16a34a;border-radius:50%; }
  .nveg-dot  { width:14px;height:14px;border:2px solid #dc2626;display:inline-flex;align-items:center;justify-content:center;border-radius:2px; }
  .nveg-dot::after { content:'';width:7px;height:7px;background:#dc2626;border-radius:50%; }
  .qty-btn { width:28px;height:28px;border-radius:6px;font-size:16px;font-weight:700;cursor:pointer; }
  .toast { position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:10px 24px;border-radius:999px;font-size:14px;z-index:9999;opacity:0;transition:opacity .3s; }
  .toast.show { opacity:1; }
  ::-webkit-scrollbar { width:4px; }
  ::-webkit-scrollbar-thumb { background:#e65c00;border-radius:4px; }
</style>
</head>
<body class="bg-gray-50 min-h-screen">
