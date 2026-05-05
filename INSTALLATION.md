# Anshu Jewels - Complete Installation Guide

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher (with mod_rewrite enabled)
- **Browser**: Modern browser with JavaScript enabled

## Installation Methods

### Method 1: Automatic Installation (Recommended)

1. **Open Installation Wizard**
   - Navigate to: `http://localhost/anshu-jewels/install.php`
   - Follow the step-by-step wizard

2. **Step 1: Database Configuration**
   - Enter database host (default: localhost)
   - Enter database user (default: root)
   - Enter database password (leave blank if none)
   - Enter database name (default: anshu_jewels)
   - Click "Next Step"

3. **Step 2: Import Database Schema**
   - Click "Import Database Schema"
   - Wait for import to complete

4. **Step 3: Create Admin User**
   - Enter admin name
   - Enter admin email
   - Enter admin password
   - Click "Create Admin User"

5. **Step 4: Complete**
   - Installation is complete
   - Delete `install.php` for security
   - Access website and admin panel

### Method 2: Manual Installation

#### Step 1: Create Database
```bash
mysql -u root -p
CREATE DATABASE anshu_jewels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### Step 2: Import Schema
```bash
mysql -u root -p anshu_jewels < database.sql
```

#### Step 3: Configure Database
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'anshu_jewels');
```

#### Step 4: Create Admin User
Using phpMyAdmin:
```sql
INSERT INTO admin_users (name, email, password, role, is_active) 
VALUES ('Admin', 'admin@anshu-jewels.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/LLG', 'super_admin', 1);
```

**Default Admin Credentials:**
- Email: `admin@anshu-jewels.com`
- Password: `password`

## Post-Installation Setup

### 1. Delete Installation File
```bash
rm install.php
```

### 2. Create Required Directories
```bash
mkdir -p assets/images
mkdir -p uploads/products
mkdir -p uploads/files
chmod 755 uploads/
```

### 3. Configure Payment Gateway
1. Login to Admin Panel
2. Go to Settings
3. Select payment gateway (Razorpay/Stripe/PayPal)
4. Enter API credentials
5. Save settings

### 4. Create Product Categories
1. Go to Admin > Products
2. Create categories:
   - Earrings
   - Necklaces
   - Bracelets
   - Rings
   - Anklets

### 5. Add Sample Products
1. Go to Admin > Products
2. Click "Add Product"
3. Fill in details:
   - Name
   - Category
   - Description
   - Price
   - Upload product file
4. Save product

### 6. Create Discount Coupons
1. Go to Admin > Coupons
2. Click "Add Coupon"
3. Enter:
   - Code (e.g., WELCOME20)
   - Discount type (Flat/Percentage)
   - Discount value
   - Max uses
4. Save coupon

### 7. Update FAQ
1. Go to Admin > Support
2. Add FAQ entries
3. Set display order

### 8. Configure Site Settings
1. Go to Admin > Settings
2. Update:
   - Site name
   - Footer text
   - GST percentage
   - Currency

## Access Points

### User Website
- **URL**: `http://localhost/anshu-jewels/`
- **Features**: Browse products, create account, purchase

### Admin Panel
- **URL**: `http://localhost/anshu-jewels/admin/`
- **Default Email**: `admin@anshu-jewels.com`
- **Default Password**: `password`

### Installation Wizard
- **URL**: `http://localhost/anshu-jewels/install.php`
- **Note**: Delete after installation

## Troubleshooting

### Database Connection Error
**Problem**: "Connection failed: Access denied"

**Solutions**:
1. Verify MySQL is running
2. Check database credentials in config.php
3. Ensure database exists
4. Check MySQL user permissions

### Blank Page
**Problem**: White screen with no content

**Solutions**:
1. Check PHP error logs
2. Enable error reporting in config.php
3. Verify all files uploaded correctly
4. Check file permissions (755 for directories, 644 for files)

### Payment Gateway Error
**Problem**: Payment page shows error

**Solutions**:
1. Verify API keys are correct
2. Check payment gateway account
3. Use sandbox credentials for testing
4. Check browser console for errors

### File Upload Issues
**Problem**: Cannot upload product files

**Solutions**:
1. Check directory permissions: `chmod 755 uploads/`
2. Verify PHP upload limits in php.ini
3. Check available disk space
4. Verify file format is allowed

### Login Issues
**Problem**: Cannot login to admin panel

**Solutions**:
1. Verify admin user exists in database
2. Check password hash is correct
3. Clear browser cookies
4. Try incognito/private mode
5. Reset password using forgot password link

## Security Checklist

- [ ] Change default admin password
- [ ] Delete install.php file
- [ ] Set proper file permissions (755 for dirs, 644 for files)
- [ ] Enable HTTPS (update SITE_URL in config.php)
- [ ] Configure firewall rules
- [ ] Set up database backups
- [ ] Review and update payment gateway credentials
- [ ] Configure email notifications
- [ ] Set up activity logging
- [ ] Regular security updates

## Backup & Recovery

### Backup Database
```bash
mysqldump -u root -p anshu_jewels > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u root -p anshu_jewels < backup_20240101.sql
```

### Backup Files
```bash
tar -czf anshu-jewels-backup-$(date +%Y%m%d).tar.gz /path/to/anshu-jewels/
```

## Performance Optimization

1. **Enable Caching**
   - Configure browser caching in .htaccess
   - Implement database query caching

2. **Optimize Images**
   - Compress product images
   - Use appropriate formats (JPEG/PNG/WebP)

3. **Minify Assets**
   - Minify CSS and JavaScript
   - Use CDN for static files

4. **Database Optimization**
   - Add indexes to frequently queried columns
   - Archive old orders
   - Optimize tables regularly

## Maintenance Tasks

### Daily
- Monitor sales and orders
- Check support tickets
- Review activity logs

### Weekly
- Backup database
- Check payment gateway transactions
- Review user registrations

### Monthly
- Analyze sales reports
- Update product inventory
- Review and optimize performance
- Check security logs

## Common Tasks

### Add New Product
1. Admin > Products > Add Product
2. Fill in details
3. Upload product file
4. Upload screenshots
5. Set price and category
6. Save product

### Create Discount Code
1. Admin > Coupons > Add Coupon
2. Enter code and discount details
3. Set usage limits
4. Save coupon

### View Orders
1. Admin > Orders
2. Filter by status if needed
3. Click order to view details
4. Update order status if needed

### Manage Users
1. Admin > Users
2. View user details
3. Block/unblock users
4. View purchase history

### Configure Payment
1. Admin > Settings
2. Select payment gateway
3. Enter API credentials
4. Save settings

## Support

For issues or questions:
- Email: info@anshu-jewels.com
- Check README.md for documentation
- Review SETUP.md for initial setup

## Next Steps

1. Customize branding and colors
2. Add product images and descriptions
3. Set up payment gateway with live credentials
4. Create initial product catalog
5. Test complete purchase flow
6. Configure email notifications
7. Set up SSL/HTTPS
8. Launch website

---

**Version**: 1.0
**Last Updated**: December 2024
**Support**: info@anshu-jewels.com
