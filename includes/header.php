<?php
require_once __DIR__ . '/../config/config.php';

function renderHeader($pageTitle = 'Anshu Jewels', $description = 'Handmade Jewelry') {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Anshu Jewels</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>mobile.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="<?php echo SITE_URL; ?>/user/index.php" class="navbar-brand">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Anshu Jewels" style="height: 100px;">
            </a>
            
            <ul class="navbar-menu">
                <li><a href="<?php echo SITE_URL; ?>/user/index.php" class="navbar-link <?php echo $currentPage == 'index' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/user/products.php" class="navbar-link <?php echo $currentPage == 'products' ? 'active' : ''; ?>">Products</a></li>
                <li><a href="<?php echo SITE_URL; ?>/user/cart.php" class="navbar-link <?php echo $currentPage == 'cart' ? 'active' : ''; ?>">
                    Cart <span id="cart-badge" class="badge badge-primary" style="display: none;">0</span>
                </a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/user/profile.php" class="navbar-link <?php echo $currentPage == 'profile' ? 'active' : ''; ?>">Profile</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/orders.php" class="navbar-link <?php echo $currentPage == 'orders' ? 'active' : ''; ?>">Orders</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/user/login.php" class="navbar-link <?php echo $currentPage == 'login' ? 'active' : ''; ?>">Login</a></li>
                <?php endif; ?>
                <li><button id="theme-toggle-desktop" class="btn btn-sm btn-secondary">🌙</button></li>
            </ul>
        </div>
    </nav>

    <!-- Mobile App Bar -->
    <div class="mobile-app-bar">
        <div class="mobile-app-bar-content">
            <button id="mobile-menu-btn" class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-logo">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Anshu Jewels" style="height: 60px;">
            </div>
            <button id="theme-toggle-mobile" class="mobile-menu-btn">🌙</button>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div id="mobile-drawer" class="mobile-drawer">
        <div class="mobile-drawer-header">
            <button id="mobile-drawer-close" class="mobile-drawer-close">
                <i class="fas fa-times"></i>
            </button>
            <h3>Menu</h3>
        </div>
        <ul class="mobile-drawer-menu">
            <li class="mobile-drawer-item">
                <a href="<?php echo SITE_URL; ?>/user/index.php" class="mobile-drawer-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="mobile-drawer-item">
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="mobile-drawer-link">
                    <i class="fas fa-gem"></i> Products
                </a>
            </li>
            <?php if (isLoggedIn()): ?>
                <li class="mobile-drawer-item">
                    <a href="<?php echo SITE_URL; ?>/user/profile.php" class="mobile-drawer-link">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
                <li class="mobile-drawer-item">
                    <a href="<?php echo SITE_URL; ?>/user/orders.php" class="mobile-drawer-link">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                </li>
                <li class="mobile-drawer-item">
                    <a href="<?php echo SITE_URL; ?>/api/logout.php" class="mobile-drawer-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            <?php else: ?>
                <li class="mobile-drawer-item">
                    <a href="<?php echo SITE_URL; ?>/user/login.php" class="mobile-drawer-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </li>
                <li class="mobile-drawer-item">
                    <a href="<?php echo SITE_URL; ?>/user/signup.php" class="mobile-drawer-link">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <div id="mobile-drawer-overlay" class="mobile-drawer-overlay"></div>

    <!-- Main Content -->
    <main>
<?php
}

function renderFooter() {
?>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav">
        <ul class="mobile-nav-items">
            <li class="mobile-nav-item">
                <a href="<?php echo SITE_URL; ?>/user/index.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home mobile-nav-icon"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gem mobile-nav-icon"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo SITE_URL; ?>/user/cart.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart mobile-nav-icon"></i>
                    <span>Cart</span>
                    <span id="mobile-cart-badge" class="mobile-nav-badge" style="display: none;">0</span>
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo isLoggedIn() ? SITE_URL . '/user/profile.php' : SITE_URL . '/user/login.php'; ?>" 
                   class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user mobile-nav-icon"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer -->
    <footer style="background: var(--bg-secondary); padding: var(--space-12) 0; margin-top: var(--space-16); border-top: 1px solid var(--border-color);">
        <div class="container">
            <div class="grid grid-cols-4">
                <div>
                    <h4 style="margin-bottom: var(--space-4);">About Us</h4>
                    <p style="color: var(--text-secondary); font-size: var(--text-sm);">
                        Handcrafted jewelry with love and passion. Each piece tells a unique story.
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: var(--space-4);">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--space-2);"><a href="<?php echo SITE_URL; ?>/user/products.php">Shop</a></li>
                        <li style="margin-bottom: var(--space-2);"><a href="<?php echo SITE_URL; ?>/user/index.php#faq">FAQ</a></li>
                        <li style="margin-bottom: var(--space-2);"><a href="<?php echo SITE_URL; ?>/user/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: var(--space-4);">Customer Service</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--space-2);"><a href="<?php echo SITE_URL; ?>/user/orders.php">Track Order</a></li>
                        <li style="margin-bottom: var(--space-2);"><a href="#">Returns</a></li>
                        <li style="margin-bottom: var(--space-2);"><a href="#">Shipping Info</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: var(--space-4);">Connect With Us</h4>
                    <div style="display: flex; gap: var(--space-3);">
                        <a href="#" style="font-size: 1.5rem;"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.instagram.com/theanshujewels?igsh=MXZ6cW80Y2Y4anAzcQ==" target="_blank" rel="noopener noreferrer" style="font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/917760876753" target="_blank" rel="noopener noreferrer" style="font-size: 1.5rem;"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <div style="text-align: center; margin-top: var(--space-8); padding-top: var(--space-8); border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-tertiary); font-size: var(--text-sm);">
                    © <?php echo date('Y'); ?> Anshu Jewels. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo JS_URL; ?>main.js"></script>
</body>
</html>
<?php
}
?>
