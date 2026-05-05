# 🚀 Quick Setup Guide - Anshu Jewels

## Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web browser

## Setup Steps

### 1. Database Setup

Open MySQL command line or phpMyAdmin and run:

```sql
CREATE DATABASE anshu_jewels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import the schema and sample data:

```bash
# Navigate to project directory
cd "c:\Users\USER\OneDrive\ドキュメント\Web Dev\anshu-jewels"

# Import database schema
mysql -u root -p anshu_jewels < database/schema.sql

# Import sample products and data
mysql -u root -p anshu_jewels < database/sample-data.sql
```

### 2. Configure Database Connection

Edit `config/database.php` if needed (default settings work for most local setups):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'anshu_jewels');
define('DB_USER', 'root');
define('DB_PASS', '');  // Add your MySQL password if you have one
```

### 3. Start the Server

```bash
# From project root directory
php -S localhost:8000
```

### 4. Access the Website

Open your browser and visit:

- **User Website**: http://localhost:8000/user/index.php
- **Admin Panel**: http://localhost:8000/admin/login.php

## Default Login Credentials

### Admin Access
- **Username**: `admin`
- **Email**: `admin@anshujewels.com`
- **Password**: `admin123`

### Test User Account
- **Email**: `test@example.com`
- **Password**: `test123`

## What's Included

✅ **10 Sample Products** across 5 categories  
✅ **3 Sample Coupons** (WELCOME10, FLAT500, FESTIVE20)  
✅ **Test User Account** for shopping  
✅ **Admin Account** for management  

## Testing the Features

### User Side
1. Browse products on the homepage
2. Use filters and search on products page
3. Add items to cart
4. Proceed to checkout
5. Try applying coupon codes

### Admin Side
1. Login to admin panel
2. View dashboard statistics
3. Check recent orders
4. Navigate through admin menu

## Troubleshooting

**Database Connection Error?**
- Verify MySQL is running
- Check credentials in `config/database.php`

**Images Not Showing?**
- Sample data uses placeholder images
- Upload real product images through admin panel (when implemented)

**Port Already in Use?**
```bash
# Use a different port
php -S localhost:8080
```

## Next Steps

1. Change default admin password
2. Add real product images
3. Configure payment gateway (Razorpay/Stripe/PayPal)
4. Set up email SMTP settings
5. Customize branding and colors

## Support

For issues or questions, refer to the main README.md file.

---

**Ready to go!** 🎉 Your Anshu Jewels e-commerce platform is now running!
