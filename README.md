# Campus Lost & Found Portal

A complete, framework-free PHP + MySQL web application built for college campus item tracking, lost & found reporting, and administrative claim verification.

---

## 🛠️ Technology Stack & Architecture

- **Backend:** Plain PHP 8+ (Procedural / Simple Modular scripts with prepared statements via `mysqli`)
- **Database:** MySQL / MariaDB (`lostfound_db`)
- **Frontend:** Pure semantic HTML5 + Vanilla CSS3 (Zero Bootstrap / Tailwind / external CDNs)
- **Authentication:** Native PHP Sessions (`$_SESSION`) with `password_hash()` and `password_verify()`
- **Security:**
  - Prepared statements on all SQL queries (`mysqli_prepare` / `mysqli_stmt_bind_param`)
  - Image MIME-type & extension validation with unique file naming
  - Role-based authorization gates (`student` vs `admin`)

---

## 📁 Directory Structure

```text
LostNFound/
├── includes/
│   ├── db.php            # MySQLi connection & DB constants
│   ├── auth.php          # Session helper & access guard functions
│   ├── header.php        # Shared responsive navbar & flash message banners
│   └── footer.php        # Shared footer
├── css/
│   └── style.css         # Clean, responsive vanilla CSS stylesheet
├── uploads/              # Storage directory for item images
├── admin/
│   ├── dashboard.php     # Admin KPI analytics & quick moderation overview
│   ├── manage_items.php  # Item feed moderation & deletion
│   └── manage_claims.php # Claim verification (Approve / Reject)
├── index.php             # Public landing & searchable lost/found item feed
├── register.php          # Student account registration
├── login.php             # User & Admin authentication
├── logout.php            # Session destruction
├── report_item.php       # Item reporting form with image upload
├── item_detail.php       # Item view & ownership claim submission
├── my_items.php          # User's personal reported items management
├── my_claims.php         # User's submitted claim tracking
├── test_db.php           # DB connection sanity check
└── schema.sql            # Database schema + seed demo data
```

---

## 🚀 Setup & Installation (XAMPP / WAMP / MAMP / LAMP)

### 1. Place Project in Web Root
Copy or clone the `LostNFound` folder into your local web server root:
- **XAMPP (Windows):** `C:\xampp\htdocs\LostNFound`
- **XAMPP (macOS):** `/Applications/XAMPP/xamppfiles/htdocs/LostNFound`
- **WAMP:** `C:\wamp64\www\LostNFound`
- **Linux (LAMP):** `/var/www/html/LostNFound`

### 2. Import Database Schema & Demo Seed Data
1. Start your **Apache** and **MySQL** services via the XAMPP/WAMP Control Panel.
2. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
3. Click the **Import** tab.
4. Choose the `schema.sql` file from this project and click **Go** (or execute its contents directly in the SQL tab).
   - This creates `lostfound_db` and pre-populates demo accounts, reports, and claims.

### 3. Database Credentials Configuration
If your MySQL setup uses a custom username or password (default is user: `root`, password: `""`), adjust `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lostfound_db');
```

### 4. Access the Portal
Visit: **`http://localhost/LostNFound/index.php`**

---

## 🔑 Demo Login Credentials

All seeded accounts share the default password: **`password123`**

| Role | Name | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Admin Portal Manager | `admin@college.edu` | `password123` |
| **Student** | Alex Rivera | `alex@college.edu` | `password123` |
| **Student** | Sarah Connor | `sarah@college.edu` | `password123` |
| **Student** | David Miller | `david@college.edu` | `password123` |

---

## 🔄 End-to-End User Journey Workflow

1. **Browse Items:** Any student or guest can browse open items on `index.php` with keyword search and category/type filters.
2. **Report Lost / Found:** Log in as a student &rarr; click **Report Item** &rarr; select Lost/Found &rarr; fill details + upload photo &rarr; published as `open`.
3. **Raise a Claim:** Another student browses the portal &rarr; opens the item &rarr; enters proof of ownership in the **Claim Form** &rarr; item status flips to `claimed` and claim moves to `pending`.
4. **Admin Approval:** Log in as `admin@college.edu` &rarr; open **Admin Dashboard** &rarr; click **Manage Claims** &rarr; review proof &rarr; click **Approve**:
   - Claim status updates to `approved`.
   - Item status updates to `resolved`.
   - Any competing pending claims on the same item are safely rejected.
