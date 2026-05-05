<?php
require_once '../config.php';

if (!is_admin()) {
    redirect(SITE_URL . 'admin/login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    redirect(SITE_URL . 'admin/orders.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $status = sanitize($_POST['status'] ?? '');

    if ($action === 'update_status') {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);

        if ($stmt->execute()) {
            $success = 'Order status updated successfully.';
        } else {
            $error = 'Error updating order status.';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    redirect(SITE_URL . 'admin/orders.php');
}

$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Dashboard</title>
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
                    <a class="nav-link active" href="orders.php">
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
                    <a class="nav-link" href="analytics.php">
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
                        <i class="fas fa-shopping-bag"></i> Order Details
                    </span>
                </div>
            </nav>

            <div class="admin-content">
                <div class="mb-4">
                    <a href="orders.php" class="btn btn-outline-danger">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Order Header -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Order <?php echo $order['order_number']; ?></h5>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Order Date</p>
                                        <p class="mb-3"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>

                                        <p class="text-muted mb-1">Payment Method</p>
                                        <p class="mb-3"><?php echo ucfirst($order['payment_method']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Transaction ID</p>
                                        <p class="mb-3"><?php echo $order['transaction_id'] ?? 'N/A'; ?></p>

                                        <p class="text-muted mb-1">Customer Email</p>
                                        <p class="mb-3"><?php echo $order['customer_email']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td><?php echo $item['product_name']; ?></td>
                                                <td>₹<?php echo number_format($item['product_price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>₹<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Shipping Address</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong><?php echo $order['customer_email']; ?></strong></p>
                                <p class="mb-1"><?php echo $order['customer_phone']; ?></p>
                                <p class="mb-0"><?php echo nl2br($order['shipping_address']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary & Status -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm sticky-top" style="top: 80px;">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax:</span>
                                    <span>₹<?php echo number_format($order['tax_amount'], 2); ?></span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>Discount:</span>
                                        <span>-₹<?php echo number_format($order['discount_amount'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong class="text-danger h5">₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>

                                <form method="POST" class="mb-3">
                                    <input type="hidden" name="action" value="update_status">
                                    <label class="form-label">Order Status</label>
                                    <select class="form-select mb-2" name="status">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-save"></i> Update Status
                                    </button>
                                </form>

                                <button class="btn btn-outline-danger w-100" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Order
                                </button>
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
