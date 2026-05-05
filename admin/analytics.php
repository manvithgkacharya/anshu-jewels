<?php
require_once '../config.php';

if (!is_admin()) {
    redirect(SITE_URL . 'admin/login.php');
}

$period = sanitize($_GET['period'] ?? 'monthly');

$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM orders WHERE payment_status = 'completed'");
$stmt->execute();
$overall = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue FROM orders WHERE payment_status = 'completed' GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
$stmt->execute();
$daily_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT p.name, COUNT(oi.id) as sales, SUM(oi.product_price * oi.quantity) as revenue FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        GROUP BY p.id 
                        ORDER BY sales DESC 
                        LIMIT 10");
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT DATE(created_at) as date, COUNT(*) as new_users FROM users GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30");
$stmt->execute();
$user_growth = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="admin-sidebar" style="width: 250px;">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-gem text-danger"></i> Anshu Jewels Admin
                </h5>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="coupons.php">
                        <i class="fas fa-ticket-alt"></i> Coupons
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="analytics.php">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="support.php">
                        <i class="fas fa-headset"></i> Support
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li class="nav-item border-top mt-3 pt-3">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-light border-bottom sticky-top">
                <div class="container-fluid">
                    <span class="navbar-text">
                        <i class="fas fa-chart-bar"></i> Analytics & Reports
                    </span>
                </div>
            </nav>

            <div class="admin-content">
                <h2 class="mb-4">Analytics & Reports</h2>

                <!-- Overall Stats -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-shopping-bag text-danger" style="font-size: 2rem;"></i>
                            <h3 class="mt-3"><?php echo $overall['total_orders'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <i class="fas fa-rupiah text-success" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">₹<?php echo number_format($overall['total_revenue'] ?? 0, 0); ?></h3>
                            <p class="text-muted mb-0">Total Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Daily Sales -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Daily Sales (Last 30 Days)</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daily_sales as $sale): ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($sale['date'])); ?></td>
                                                <td><?php echo $sale['orders']; ?></td>
                                                <td>₹<?php echo number_format($sale['revenue'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Top 10 Products</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($top_products as $product): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo $product['name']; ?></h6>
                                                <small class="text-muted"><?php echo $product['sales']; ?> sales</small>
                                            </div>
                                            <span class="badge bg-danger">₹<?php echo number_format($product['revenue'], 0); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
