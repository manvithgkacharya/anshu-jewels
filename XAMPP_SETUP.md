# 🚀 XAMPP Setup Guide for Anshu Jewels

## ✅ Step 1: Files Already Copied!
Your project files have been copied to: `C:\xampp\htdocs\anshu-jewels\`

---

## 📝 Step 2: Create Database

1. **Open phpMyAdmin**:
   - Go to: http://localhost/phpmyadmin
   - Or click "Admin" next to MySQL in XAMPP Control Panel

2. **Create Database**:
   - Click "New" in the left sidebar
   - Database name: `anshu_jewels`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

3. **Import Schema**:
   - Select `anshu_jewels` database from left sidebar
   - Click "Import" tab at the top
   - Click "Choose File"
   - Navigate to: `C:\xampp\htdocs\anshu-jewels\database\schema.sql`
   - Click "Go" at the bottom
   - Wait for success message

4. **Import Sample Data** (Optional but recommended):
   - Still in Import tab
   - Click "Choose File" again
   - Navigate to: `C:\xampp\htdocs\anshu-jewels\database\sample-data.sql`
   - Click "Go"
   - Wait for success message

---

## ⚙️ Step 3: Configure Database Connection

The database config should already be correct, but verify:

**File**: `C:\xampp\htdocs\anshu-jewels\config\database.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
define('DB_NAME', 'anshu_jewels');
```

---

## 🌐 Step 4: Access Your Website

### User Website:
**Homepage**: http://localhost/anshu-jewels/user/index.php

**Other Pages**:
- Products: http://localhost/anshu-jewels/user/products.php
- Login: http://localhost/anshu-jewels/user/login.php
- Signup: http://localhost/anshu-jewels/user/signup.php
- Cart: http://localhost/anshu-jewels/user/cart.php

### Admin Panel:
**Admin Login**: http://localhost/anshu-jewels/admin/login.php

**Default Admin Credentials**:
- Username: `admin`
- Password: `admin123`

**Admin Pages** (after login):
- Dashboard: http://localhost/anshu-jewels/admin/index.php
- Products: http://localhost/anshu-jewels/admin/products.php
- Orders: http://localhost/anshu-jewels/admin/orders.php
- Users: http://localhost/anshu-jewels/admin/users.php
- Coupons: http://localhost/anshu-jewels/admin/coupons.php
- Reports: http://localhost/anshu-jewels/admin/reports.php
- Support: http://localhost/anshu-jewels/admin/support.php
- Settings: http://localhost/anshu-jewels/admin/settings.php

---

## 👤 Test User Account

**Email**: test@example.com  
**Password**: test123

---

## 🎨 Test the Features

### 1. User Flow:
1. Visit homepage
2. Browse products
3. Add items to cart
4. Go to checkout
5. Place order (use COD for testing)
6. View orders

### 2. Admin Flow:
1. Login to admin panel
2. Add/edit products
3. View orders
4. Manage users
5. Create coupons
6. Check analytics

### 3. Mobile View:
- Resize browser to < 768px width
- See bottom navigation bar
- Test mobile menu

---

## 🛠️ Troubleshooting

### Issue: "Database connection failed"
**Solution**: 
- Make sure MySQL is running in XAMPP
- Check database name is `anshu_jewels`
- Verify credentials in `config/database.php`

### Issue: "Page not found"
**Solution**:
- Make sure Apache is running in XAMPP
- Check URL starts with `http://localhost/anshu-jewels/`

### Issue: "Permission denied" or file upload errors
**Solution**:
- Create uploads folder: `C:\xampp\htdocs\anshu-jewels\assets\uploads\products\`
- Right-click folder → Properties → Security → Edit → Give "Users" full control

### Issue: Images not showing
**Solution**:
- Sample products use placeholder images
- Upload real images through admin panel

---

## 📦 Sample Coupons (Already in Database)

- **WELCOME10** - 10% off
- **FLAT500** - ₹500 flat discount
- **FESTIVE20** - 20% off

---

## 🎯 Quick Start Checklist

- [ ] XAMPP Apache & MySQL running
- [ ] Database `anshu_jewels` created
- [ ] Schema imported
- [ ] Sample data imported
- [ ] Visit http://localhost/anshu-jewels/user/index.php
- [ ] Login to admin panel
- [ ] Test adding products
- [ ] Test placing orders

---

## 🚀 You're Ready!

Your e-commerce platform is now running locally. Enjoy exploring all the features!

**Need Help?** Check the README.md and SETUP.md files in the project folder.
