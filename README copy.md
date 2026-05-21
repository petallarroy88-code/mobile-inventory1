# MobileStock Pro — PHP Inventory System
### Mobile Accessories Inventory Management System

---

## 📁 Folder Structure

```
mobile_inventory/
│
├── config/
│   ├── config.php          # App constants (BASE_URL, paths, settings)
│   ├── database.php        # DB connection (mysqli)
│   └── schema.sql          # Full DB schema + seed data
│
├── includes/
│   ├── auth.php            # Login, logout, session, role guards
│   ├── helpers.php         # Utility functions (format, sanitize, etc.)
│   ├── header.php          # Sidebar + topbar layout (top of every page)
│   └── footer.php          # Closing tags + JS (bottom of every page)
│
├── modules/
│   ├── products/
│   │   ├── index.php       # Product list with search & filters
│   │   ├── create.php      # Add new product form
│   │   ├── edit.php        # Edit product
│   │   ├── view.php        # Product detail page
│   │   ├── stock.php       # Stock In / Out / Adjustment
│   │   └── delete.php      # Soft-delete product
│   │
│   ├── categories/
│   │   └── index.php       # Manage categories (CRUD)
│   │
│   ├── suppliers/
│   │   └── index.php       # Manage suppliers (CRUD)
│   │
│   ├── orders/
│   │   ├── index.php       # Purchase orders list + receive
│   │   └── create.php      # PO create handler
│   │
│   ├── reports/
│   │   └── index.php       # Reports: inventory, low stock, top value, movements
│   │
│   └── users/
│       └── index.php       # User management (admin only)
│
├── assets/
│   ├── css/
│   │   └── style.css       # Full UI stylesheet
│   ├── js/
│   │   └── app.js          # Sidebar toggle, modals, confirm dialogs
│   └── img/                # (reserved for static images)
│
├── uploads/
│   └── products/           # Product image uploads
│
├── index.php               # Dashboard (stats, low stock, recent movements)
├── login.php               # Login page
├── logout.php              # Session destroy + redirect
└── README.md               # This file
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.4+
- Apache/Nginx with mod_rewrite
- (XAMPP / Laragon / WAMP all work)

### 2. Database Setup
```sql
-- Run this in phpMyAdmin or MySQL CLI:
SOURCE /path/to/mobile_inventory/config/schema.sql;
```
This creates the database, all tables, default admin, categories, and sample products.

### 3. Configure Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // your MySQL user
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'mobile_inventory');
```

### 4. Configure Base URL
Edit `config/config.php`:
```php
define('BASE_URL', 'http://localhost/mobile_inventory/');
```

### 5. Uploads Folder Permissions
Make sure the uploads directory is writable:
```bash
chmod -R 775 uploads/
```

### 6. Place Files
Copy the entire `mobile_inventory/` folder into your web root:
- XAMPP: `C:/xampp/htdocs/`
- Laragon: `C:/laragon/www/`

### 7. Open in Browser
```
http://localhost/mobile_inventory/
```
**Default credentials:**
- Username: `admin`
- Password: `admin123`

---

## ✅ Features

| Feature | Description |
|---|---|
| 🔐 Authentication | Login/logout with role-based access (Admin, Manager, Staff) |
| 📦 Products | Full CRUD, SKU, brand, compatible phones, image upload |
| 🏷️ Categories | Manage product categories with icons |
| 🚚 Suppliers | Supplier directory with contact info |
| 📊 Stock Movements | Stock In/Out/Adjustment with full audit trail |
| 🛒 Purchase Orders | Create POs, receive inventory (updates stock automatically) |
| 📈 Reports | Inventory by category, low stock, top value, movement history |
| 👥 Users | Admin can create/edit users and assign roles |
| 📱 Responsive | Mobile-friendly sidebar layout |

---

## 🔒 Security Notes
- Passwords hashed with `password_hash()` (bcrypt)
- All inputs sanitized with `htmlspecialchars()`
- Prepared statements used throughout (no SQL injection)
- Session timeout after 1 hour of inactivity
- Role-based access control on sensitive pages

---

## 📝 Default Roles
| Role | Permissions |
|---|---|
| `admin` | Full access including user management |
| `manager` | Products, categories, suppliers, orders, reports |
| `staff` | View products, record stock movements |
