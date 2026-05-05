<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

// Get date range from query params
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

try {
    // Total Revenue
    $revenueStmt = $db->prepare("SELECT 
                                 COALESCE(SUM(final_amount), 0) as total_revenue,
                                 COUNT(*) as total_orders
                                 FROM orders 
                                 WHERE payment_status = 'completed' 
                                 AND created_at BETWEEN ? AND ?");
    $revenueStmt->execute([$startDate, $endDate . ' 23:59:59']);
    $revenueData = $revenueStmt->fetch();
    
    // Daily Sales (last 30 days)
    $dailySalesStmt = $db->query("SELECT 
                                  DATE(created_at) as date,
                                  COUNT(*) as orders,
                                  SUM(final_amount) as revenue
                                  FROM orders 
                                  WHERE payment_status = 'completed'
                                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                  GROUP BY DATE(created_at)
                                  ORDER BY date ASC");
    $dailySales = $dailySalesStmt->fetchAll();
    
    // Top Products
    $topProductsStmt = $db->query("SELECT 
                                   p.title,
                                   p.price,
                                   COUNT(oi.id) as sales_count,
                                   SUM(oi.quantity) as total_quantity,
                                   SUM(oi.product_price * oi.quantity) as total_revenue
                                   FROM products p
                                   LEFT JOIN order_items oi ON p.id = oi.product_id
                                   LEFT JOIN orders o ON oi.order_id = o.id
                                   WHERE o.payment_status = 'completed'
                                   GROUP BY p.id
                                   ORDER BY total_revenue DESC
                                   LIMIT 10");
    $topProducts = $topProductsStmt->fetchAll();
    
    // Customer Statistics
    $customerStatsStmt = $db->query("SELECT 
                                     COUNT(DISTINCT u.id) as total_customers,
                                     COUNT(DISTINCT CASE WHEN o.user_id IS NOT NULL THEN u.id END) as customers_with_orders,
                                     AVG(order_count) as avg_orders_per_customer
                                     FROM users u
                                     LEFT JOIN (
                                         SELECT user_id, COUNT(*) as order_count
                                         FROM orders
                                         WHERE payment_status = 'completed'
                                         GROUP BY user_id
                                     ) o ON u.id = o.user_id");
    $customerStats = $customerStatsStmt->fetch();
    
    // Order Status Distribution
    $orderStatusStmt = $db->query("SELECT 
                                   payment_status,
                                   COUNT(*) as count
                                   FROM orders
                                   GROUP BY payment_status");
    $orderStatus = $orderStatusStmt->fetchAll();
    
    // Monthly Revenue (last 12 months)
    $monthlyRevenueStmt = $db->query("SELECT 
                                      DATE_FORMAT(created_at, '%Y-%m') as month,
                                      COUNT(*) as orders,
                                      SUM(final_amount) as revenue
                                      FROM orders 
                                      WHERE payment_status = 'completed'
                                      AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                      ORDER BY month ASC");
    $monthlyRevenue = $monthlyRevenueStmt->fetchAll();
    
} catch (PDOException $e) {
    $revenueData = ['total_revenue' => 0, 'total_orders' => 0];
    $dailySales = [];
    $topProducts = [];
    $customerStats = ['total_customers' => 0, 'customers_with_orders' => 0, 'avg_orders_per_customer' => 0];
    $orderStatus = [];
    $monthlyRevenue = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Anshu Jewels Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: var(--bg-secondary); }
        .admin-layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background: var(--bg-primary); border-right: 1px solid var(--border-color); padding: var(--space-6); }
        .admin-logo {
            margin-bottom: var(--space-8);
            text-align: center;
        }
        .admin-menu { list-style: none; }
        .admin-menu-item { margin-bottom: var(--space-2); }
        .admin-menu-link { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3); color: var(--text-secondary); text-decoration: none; border-radius: var(--radius-md); transition: all var(--transition-fast); }
        .admin-menu-link:hover, .admin-menu-link.active { background: var(--bg-secondary); color: var(--accent-color); }
        .admin-content { padding: var(--space-8); }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-4); margin-bottom: var(--space-8); }
        .stat-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6); }
        .stat-icon { width: 50px; height: 50px; border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: var(--space-4); }
        .stat-value { font-size: var(--text-3xl); font-weight: 800; margin-bottom: var(--space-2); }
        .stat-label { color: var(--text-secondary); font-size: var(--text-sm); }
        .chart-container { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: var(--space-6); margin-bottom: var(--space-6); }
        .chart-wrapper { position: relative; height: 300px; }
        .data-table { width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); overflow: hidden; }
        .data-table th, .data-table td { padding: var(--space-4); text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background: var(--bg-secondary); font-weight: 600; font-size: var(--text-sm); }
        .date-filter { display: flex; gap: var(--space-4); margin-bottom: var(--space-6); background: var(--bg-primary); padding: var(--space-4); border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/index.php" class="admin-menu-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/products.php" class="admin-menu-link"><i class="fas fa-gem"></i> Products</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link"><i class="fas fa-users"></i> Users</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="admin-menu-link"><i class="fas fa-ticket-alt"></i> Coupons</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/reports.php" class="admin-menu-link active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);"><a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link"><i class="fas fa-globe"></i> View Website</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Reports & Analytics</h1>
                    <p style="color: var(--text-secondary);">Track your business performance</p>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET" style="display: flex; gap: var(--space-4); flex: 1; align-items: center;">
                    <div style="flex: 1;">
                        <label style="font-size: var(--text-sm); color: var(--text-secondary); display: block; margin-bottom: var(--space-2);">Start Date</label>
                        <input type="date" name="start_date" class="form-input" value="<?php echo $startDate; ?>">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: var(--text-sm); color: var(--text-secondary); display: block; margin-bottom: var(--space-2);">End Date</label>
                        <input type="date" name="end_date" class="form-input" value="<?php echo $endDate; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 24px;">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--gold-600);">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($revenueData['total_revenue'], 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($revenueData['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--info);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($customerStats['total_customers']); ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">₹<?php echo $revenueData['total_orders'] > 0 ? number_format($revenueData['total_revenue'] / $revenueData['total_orders'], 0) : 0; ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                <!-- Daily Sales Chart -->
                <div class="chart-container">
                    <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-chart-area"></i> Daily Sales (Last 30 Days)</h3>
                    <div class="chart-wrapper">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
                
                <!-- Order Status Distribution -->
                <div class="chart-container">
                    <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-chart-pie"></i> Order Status</h3>
                    <div class="chart-wrapper">
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Revenue Chart -->
            <div class="chart-container">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 12 Months)</h3>
                <div class="chart-wrapper">
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>
            
            <!-- Top Products Table -->
            <div class="chart-container">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-trophy"></i> Top Selling Products</h3>
                <?php if (empty($topProducts)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--space-8);">No sales data available</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $index => $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <span style="font-size: 1.5rem;">🥇</span>
                                        <?php elseif ($index === 1): ?>
                                            <span style="font-size: 1.5rem;">🥈</span>
                                        <?php elseif ($index === 2): ?>
                                            <span style="font-size: 1.5rem;">🥉</span>
                                        <?php else: ?>
                                            <strong>#<?php echo $index + 1; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($product['title']); ?></strong></td>
                                    <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo number_format($product['total_quantity']); ?> units</td>
                                    <td><strong style="color: var(--accent-color);">₹<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
    <script>
        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(fn($d) => date('M d', strtotime($d['date'])), $dailySales)); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_map(fn($d) => $d['revenue'], $dailySales)); ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(fn($s) => ucfirst($s['payment_status']), $orderStatus)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(fn($s) => $s['count'], $orderStatus)); ?>,
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        // Monthly Revenue Chart
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(fn($m) => date('M Y', strtotime($m['month'] . '-01')), $monthlyRevenue)); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_map(fn($m) => $m['revenue'], $monthlyRevenue)); ?>,
                    backgroundColor: '#f59e0b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
