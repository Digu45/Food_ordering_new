# 🍔 Digus Restaurant — Online Food Ordering System

A production-ready, mobile-first food ordering web app built with **PHP, MySQL, Tailwind CSS, and JavaScript**.

---

## ✨ Features

| Feature | Details |
|---|---|
| 📱 OTP Login | Mobile verification via SMS API |
| 🍽️ Menu Browser | Category filter, veg toggle, search, item detail sheet |
| 🛒 Cart | Real-time qty update, special instructions, CGST/SGST |
| 💳 Checkout | UPI, Card, Cash payment modes with UTR entry |
| ✅ Order Success | Confirmation page with tracking timeline |
| 📋 Order History | All past orders grouped by order ID |
| 🔐 Admin Login | Secure session-based admin access |
| 📊 Admin Dashboard | Live order board, status updates (Pending→Preparing→Completed) |
| 🍴 Menu Management | Add / Edit / Delete menu items with image URL |

---

## 🛠️ Tech Stack

- **Backend**: PHP 8+ with PDO (prepared statements — SQL injection safe)
- **Database**: MySQL / MariaDB
- **Frontend**: Tailwind CSS (CDN), Vanilla JS
- **Fonts**: Playfair Display + DM Sans
- **Icons**: Font Awesome 6

---

## 🚀 Setup Instructions

### 1. Requirements
- XAMPP / WAMP / Laragon (PHP 8.0+, MySQL 5.7+)

### 2. Install
```bash
# Copy to htdocs
cp -r online_food/ C:/xampp/htdocs/

# Start Apache + MySQL in XAMPP
```

### 3. Database Setup
```sql
-- Option A: phpMyAdmin
-- 1. Create database: restaurant_db
-- 2. Import: restaurant_db.sql

-- Option B: Command line
mysql -u root restaurant_db < restaurant_db.sql
```

### 4. Configure
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Your MySQL password
define('DB_NAME', 'restaurant_db');
```

### 5. Open in browser
```
http://localhost/online_food/index.php
http://localhost/online_food/admin/login.php
```

### Admin Credentials
- Username: `digu`
- Password: `1441`

---

## 📁 Project Structure

```
online_food/
├── config.php              ← DB & app settings (edit this)
├── connection.php          ← PDO + MySQLi connection
├── index.php              ← Landing page
├── login.php               ← OTP login
├── home.php                ← Menu browser
├── cart.php                ← Cart page
├── checkout.php            ← Payment selection
├── place_order.php         ← Order processor
├── order_success.php       ← Confirmation page
├── history.php             ← Order history
├── logout.php              ← User logout
├── submit_order.php        ← AJAX: add/update cart item
├── remove_order.php        ← AJAX: remove cart item
├── update_quantity.php     ← AJAX: update qty
├── update_instruction.php  ← AJAX: save special instructions
├── vegapi.php              ← AJAX: veg-only menu filter
├── restaurant_db.sql       ← Complete database schema
├── admin/
│   ├── login.php           ← Admin login
│   ├── auth.php            ← Session guard (include in all admin pages)
│   ├── dashboard.php       ← Order management
│   ├── menu.php            ← Menu CRUD
│   └── logout.php          ← Admin logout
└── includes/
    ├── header.php
    └── footer.php
```

---

## 🔐 Security Improvements (vs original)

- ✅ PDO prepared statements everywhere (no SQL injection)
- ✅ No hardcoded passwords in code (all in config.php)
- ✅ OTP expiry (5 minutes)
- ✅ Input sanitization with htmlspecialchars()
- ✅ Session-based admin authentication
- ✅ Error logging instead of die() with DB errors

---

Project Live Link : (https://food-ordering-1fl2.onrender.com)

## 👨‍💻 Author

**Digvijay Vapilkar**  
GitHub: [Digu45](https://github.com/Digu45)

