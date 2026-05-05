# 🎨 Anshu Jewels - Handmade Jewelry E-Commerce Platform

A fully responsive, feature-rich e-commerce website for selling handmade jewelry with both user and admin functionality.

## ✨ Features

### User Features
- 🔐 **Authentication**: Secure signup/login with password hashing
- 🏠 **Landing Page**: Hero section, featured products, testimonials, FAQ
- 🛍️ **Product Browsing**: Advanced filters, search, sorting by price/popularity
- 📱 **Responsive Design**: Native app-like mobile experience with bottom navigation
- 🌓 **Dark/Light Mode**: Theme toggle with localStorage persistence
- 🛒 **Shopping Cart**: Add/remove items, quantity management, real-time calculations
- 💳 **Checkout**: Secure payment integration (Razorpay/Stripe/PayPal)
- 📦 **Order Management**: View order history, download invoices
- 🎟️ **Coupon System**: Apply discount codes at checkout
- 👤 **User Profile**: Manage account details, view purchase history

### Admin Features
- 🔑 **Admin Dashboard**: Sales statistics, recent orders, analytics
- 📦 **Product Management**: Add/edit/delete products with image upload
- 📊 **Order Management**: View all orders, manage refunds, print shipping labels
- 👥 **User Management**: View users, block/unblock, view purchase history
- 🎫 **Coupon Management**: Create discount codes with expiry and usage limits
- 📈 **Reports & Analytics**: Daily/monthly sales, top products, revenue summaries
- 💬 **Support Management**: Handle customer support tickets
- ⚙️ **Settings**: Configure payment gateways, taxes, branding, email notifications

## 🚀 Technology Stack

- **Frontend**: PHP, HTML5, CSS3, JavaScript
- **Backend**: PHP with PDO
- **Database**: MySQL
- **Design**: ReactBits-inspired UI with premium aesthetics
- **Icons**: Font Awesome 6
- **Fonts**: Inter, Bricolage Grotesque (Google Fonts)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Modern web browser

## 🛠️ Installation

### 1. Clone or Download the Project

```bash
cd "c:\Users\USER\OneDrive\ドキュメント\Web Dev\anshu-jewels"
```

### 2. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE anshu_jewels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p anshu_jewels < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`

### 3. Configure Database Connection

Edit `config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'anshu_jewels');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
```

### 4. Set Up File Permissions

Ensure the uploads directory is writable:

```bash
chmod -R 755 assets/uploads/
```

### 5. Start the Development Server

Using PHP built-in server:

```bash
php -S localhost:8000
```

Or configure your Apache/Nginx virtual host to point to the project directory.

### 6. Access the Application

- **User Site**: http://localhost:8000/user/index.php
- **Admin Panel**: http://localhost:8000/admin/login.php

## 🔑 Default Admin Credentials

- **Username**: admin
- **Email**: admin@anshujewels.com
- **Password**: admin123

**⚠️ Important**: Change the default admin password immediately after first login!

## 📁 Project Structure

```
anshu-jewels/
├── config/              # Configuration files
│   ├── database.php     # Database connection
│   └── config.php       # Site configuration
├── includes/            # Reusable PHP components
│   └── header.php       # Header and footer templates
├── assets/              # Static assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # Site images
│   └── uploads/        # User uploaded files
├── user/               # User-facing pages
│   ├── index.php       # Landing page
│   ├── products.php    # Product listing
│   ├── product-detail.php
│   ├── cart.php        # Shopping cart
│   ├── checkout.php    # Checkout page
│   ├── orders.php      # Order history
│   ├── login.php       # User login
│   ├── signup.php      # User registration
│   └── profile.php     # User profile
├── admin/              # Admin panel
│   ├── index.php       # Dashboard
│   ├── products.php    # Product management
│   ├── orders.php      # Order management
│   ├── users.php       # User management
│   ├── coupons.php     # Coupon management
│   ├── reports.php     # Analytics
│   └── settings.php    # Site settings
├── api/                # API endpoints
└── database/           # Database files
    └── schema.sql      # Database schema
```

## 🎨 Design Features

- **ReactBits-Inspired UI**: Modern, premium design with smooth animations
- **Mobile-First**: Responsive design that works perfectly on all devices
- **Bottom Navigation**: Native app-like experience on mobile
- **Dark/Light Mode**: Automatic theme switching with user preference
- **Glassmorphism**: Modern UI effects for cards and modals
- **Micro-animations**: Smooth transitions and hover effects
- **Premium Color Palette**: Gold accents for luxury jewelry feel

## 🔧 Configuration

### Payment Gateway Setup

Edit `config/config.php` or use the admin settings panel:

```php
// Razorpay
'razorpay_key_id' => 'your_key_id',
'razorpay_key_secret' => 'your_secret',

// Stripe
'stripe_public_key' => 'your_public_key',
'stripe_secret_key' => 'your_secret_key',

// PayPal
'paypal_client_id' => 'your_client_id',
'paypal_secret' => 'your_secret',
```

### Email Configuration

Configure SMTP settings in the admin panel or `site_settings` table:

- SMTP Host
- SMTP Port
- SMTP Username
- SMTP Password

### Tax Settings

Configure GST/VAT percentage in admin settings (default: 18%)

## 📱 Mobile Features

- **Bottom Navigation Bar**: Quick access to Home, Products, Cart, Profile
- **App Bar**: Mobile-optimized top navigation
- **Drawer Menu**: Slide-out navigation menu
- **Touch-Optimized**: Large buttons and touch targets (44px minimum)
- **Swipeable**: Smooth scrolling and gestures
- **Native Feel**: Material Design-inspired components

## 🔒 Security Features

- **Password Hashing**: bcrypt for secure password storage
- **Prepared Statements**: SQL injection prevention
- **CSRF Protection**: Token-based form security
- **Input Sanitization**: XSS attack prevention
- **Session Security**: Secure session management
- **File Upload Validation**: Secure file handling

## 📊 Sample Data

The database schema includes:
- Default admin user
- 5 product categories (Necklaces, Earrings, Bracelets, Rings, Anklets)
- Site settings with default values

Add sample products through the admin panel.

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database exists

### Images Not Displaying
- Check file permissions on `assets/uploads/`
- Verify image paths in database

### Session Issues
- Ensure PHP session is enabled
- Check session save path permissions

## 📝 License

This project is created for Anshu Jewels. All rights reserved.

## 👨‍💻 Support

For support or questions, contact: admin@anshujewels.com

## 🎯 Future Enhancements

- [ ] Wishlist functionality
- [ ] Product reviews and ratings
- [ ] Social media integration
- [ ] Email marketing integration
- [ ] Advanced analytics dashboard
- [ ] Multi-currency support
- [ ] Inventory management
- [ ] Automated email notifications

---

Made with ❤️ for Anshu Jewels
