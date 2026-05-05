<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

// Fetch dashboard statistics
try {
    // Total orders
    $totalOrdersStmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $totalOrdersStmt->fetch()['count'];
    
    // Total revenue
    $totalRevenueStmt = $db->query("SELECT SUM(final_amount) as total FROM orders WHERE payment_status = 'completed'");
    $totalRevenue = $totalRevenueStmt->fetch()['total'] ?? 0;
    
    // Total users
    $totalUsersStmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $totalUsers = $totalUsersStmt->fetch()['count'];
    
    // Total products
    $totalProductsStmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $totalProducts = $totalProductsStmt->fetch()['count'];
    
    // Recent orders
    $recentOrdersStmt = $db->query("SELECT o.*, u.name as user_name FROM orders o 
                                    LEFT JOIN users u ON o.user_id = u.id 
                                    ORDER BY o.created_at DESC LIMIT 10");
    $recentOrders = $recentOrdersStmt->fetchAll();
    
} catch (PDOException $e) {
    $totalOrders = $totalRevenue = $totalUsers = $totalProducts = 0;
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Anshu Jewels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: var(--bg-secondary);
        }
        
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: var(--bg-primary);
            border-right: 1px solid var(--border-color);
            padding: var(--space-6);
        }
        
        .admin-logo {
            margin-bottom: var(--space-8);
            text-align: center;
        }
        
        .admin-menu {
            list-style: none;
        }
        
        .admin-menu-item {
            margin-bottom: var(--space-2);
        }
        
        .admin-menu-link {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        
        .admin-menu-link:hover,
        .admin-menu-link.active {
            background: var(--bg-secondary);
            color: var(--accent-color);
        }
        
        .admin-content {
            padding: var(--space-8);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-8);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--space-6);
            margin-bottom: var(--space-8);
        }
        
        .stat-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: var(--space-4);
        }
        
        .stat-value {
            font-size: var(--text-3xl);
            font-weight: 800;
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: var(--text-sm);
        }
        
        .data-table {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }
        
        .data-table th,
        .data-table td {
            padding: var(--space-4);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            background: var(--bg-secondary);
            font-weight: 600;
            font-size: var(--text-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                grid-template-columns: 1fr;
            }
            
            .admin-sidebar {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Anshu Jewels" style="max-width: 200px; height: auto;">
            </div>
            
            <ul class="admin-menu">
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="admin-menu-link active">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/products.php" class="admin-menu-link">
                        <i class="fas fa-gem"></i> Products
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="admin-menu-link">
                        <i class="fas fa-ticket-alt"></i> Coupons
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="admin-menu-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);">
                    <a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link">
                        <i class="fas fa-globe"></i> View Website
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">
                        Dashboard
                    </h1>
                    <p style="color: var(--text-secondary);">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!
                    </p>
                </div>
                <button id="theme-toggle-desktop" class="btn btn-secondary">
                    🌙
                </button>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--gold-600);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($totalRevenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--info);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                    <div class="stat-label">Active Products</div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <h2 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-6);">
                    Recent Orders
                </h2>
                
                <?php if (empty($recentOrders)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--space-8);">
                        No orders yet
                    </p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                    <td><strong>₹<?php echo number_format($order['final_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
</body>
</html>
