<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle order status update
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $orderStatus = sanitize($_POST['order_status']);
    $paymentStatus = sanitize($_POST['payment_status']);
    
    try {
        $stmt = $db->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
        $success = 'Order status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to update order status';
    }
}

// Handle refund
if (isset($_POST['process_refund'])) {
    $orderId = (int)$_POST['order_id'];
    $refundAmount = (float)$_POST['refund_amount'];
    $refundReason = sanitize($_POST['refund_reason']);
    
    try {
        $db->beginTransaction();
        
        // Update order status
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'refunded', order_status = 'cancelled' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // You would integrate with payment gateway here to process actual refund
        // For now, we'll just update the database
        
        $db->commit();
        $success = 'Refund processed successfully!';
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Failed to process refund';
    }
}

// Handle delete order
if (isset($_POST['delete_order'])) {
    $orderId = (int)$_POST['order_id'];
    
    try {
        // Delete order items first (if not cascading)
        $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        // Delete order
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        
        $success = 'Order deleted permanently!';
    } catch (PDOException $e) {
        $error = 'Failed to delete order: ' . $e->getMessage();
    }
}

// Fetch all orders
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

try {
    $query = "SELECT o.*, u.name as customer_name, u.email as customer_email,
              COUNT(oi.id) as item_count
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $query .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    if ($statusFilter !== 'all') {
        $query .= " AND o.payment_status = ?";
        $params[] = $statusFilter;
    }
    
    $query .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// Fetch order details for modal
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    try {
        $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                              FROM orders o
                              LEFT JOIN users u ON o.user_id = u.id
                              WHERE o.id = ?");
        $stmt->execute([$viewId]);
        $viewOrder = $stmt->fetch();
        
        // Fetch order items
        $itemsStmt = $db->prepare("SELECT oi.*, p.title as product_title
                                   FROM order_items oi
                                   LEFT JOIN products p ON oi.product_id = p.id
                                   WHERE oi.order_id = ?");
        $itemsStmt->execute([$viewId]);
        $orderItems = $itemsStmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Failed to load order details';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Anshu Jewels Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .filters-bar { display: flex; gap: var(--space-4); margin-bottom: var(--space-6); background: var(--bg-primary); padding: var(--space-4); border-radius: var(--radius-lg); border: 1px solid var(--border-color); }
        .data-table { width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); overflow: hidden; }
        .data-table th, .data-table td { padding: var(--space-4); text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background: var(--bg-secondary); font-weight: 600; font-size: var(--text-sm); text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table tr:last-child td { border-bottom: none; }
        .status-badge { display: inline-block; padding: var(--space-1) var(--space-3); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-refunded { background: #e0e7ff; color: #3730a3; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: var(--space-8); }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-primary); border-radius: var(--radius-xl); padding: var(--space-8); max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); padding-bottom: var(--space-4); border-bottom: 2px solid var(--border-color); }
        .close-modal { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
        .order-section { margin-bottom: var(--space-6); padding: var(--space-4); background: var(--bg-secondary); border-radius: var(--radius-lg); }
        .order-items-table { width: 100%; margin-top: var(--space-4); }
        .order-items-table th, .order-items-table td { padding: var(--space-3); text-align: left; border-bottom: 1px solid var(--border-color); }
        @media print {
            .admin-sidebar, .admin-header, .filters-bar, .no-print { display: none !important; }
            .modal-content { box-shadow: none; border: 1px solid #000; }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/index.php" class="admin-menu-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/products.php" class="admin-menu-link"><i class="fas fa-gem"></i> Products</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link active"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link"><i class="fas fa-users"></i> Users</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="admin-menu-link"><i class="fas fa-ticket-alt"></i> Coupons</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);"><a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link"><i class="fas fa-globe"></i> View Website</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Order Management</h1>
                    <p style="color: var(--text-secondary);">Manage customer orders and refunds</p>
                </div>
                <button id="theme-toggle-desktop" class="btn btn-secondary">🌙</button>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: var(--space-6);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: var(--space-6);">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div style="margin-bottom: var(--space-6); background: var(--bg-primary); padding: var(--space-4); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <form method="GET" style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
                    <input type="text" name="search" class="form-input" placeholder="Search by ID, Name, Phone..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" style="flex: 1; min-width: 200px;">
                    
                    <select name="status" class="form-select" style="min-width: 150px;">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
            
            <!-- Orders Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
                                No orders found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td><?php echo $order['item_count']; ?> item(s)</td>
                                <td><strong>₹<?php echo number_format($order['final_amount'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo $order['payment_status']; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo ucfirst($order['order_status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>')" class="btn btn-sm btn-outline" style="color: var(--error); border-color: var(--error);" title="Delete Permanently">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- Order Details Modal -->
    <?php if ($viewOrder): ?>
    <div id="orderModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: var(--text-2xl); font-weight: 700;">
                    Order #<?php echo htmlspecialchars($viewOrder['order_number']); ?>
                </h2>
                <button onclick="closeModal()" class="close-modal no-print">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Customer Information -->
            <div class="order-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-user"></i> Customer Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div>
                        <strong>Name:</strong> <?php echo htmlspecialchars($viewOrder['customer_name']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($viewOrder['customer_email']); ?>
                    </div>
                    <div>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($viewOrder['customer_phone'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <strong>Order Date:</strong> <?php echo date('F d, Y H:i', strtotime($viewOrder['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="order-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-shipping-fast"></i> Shipping Address</h3>
                <p style="margin: 0; line-height: 1.6;">
                    <?php echo htmlspecialchars($viewOrder['shipping_name']); ?><br>
                    <?php echo htmlspecialchars($viewOrder['shipping_address']); ?><br>
                    <?php echo htmlspecialchars($viewOrder['shipping_city']); ?>, 
                    <?php echo htmlspecialchars($viewOrder['shipping_state']); ?> - 
                    <?php echo htmlspecialchars($viewOrder['shipping_pincode']); ?>
                </p>
            </div>
            
            <!-- Order Items -->
            <div class="order-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-box"></i> Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_title']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['product_price'], 2); ?></td>
                                <td><strong>₹<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Order Summary -->
            <div class="order-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-receipt"></i> Order Summary</h3>
                <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Subtotal:</span>
                        <strong>₹<?php echo number_format($viewOrder['total_amount'], 2); ?></strong>
                    </div>
                    <?php if ($viewOrder['discount_amount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; color: var(--success);">
                        <span>Discount:</span>
                        <strong>-₹<?php echo number_format($viewOrder['discount_amount'], 2); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Shipping:</span>
                        <strong>₹<?php echo number_format($viewOrder['shipping_amount'] ?? 0, 2); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Tax (<?php echo getSiteSetting('tax_percentage', '18'); ?>%):</span>
                        <strong>₹<?php echo number_format($viewOrder['tax_amount'], 2); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: var(--space-3); border-top: 2px solid var(--border-color); font-size: var(--text-xl);">
                        <span>Total:</span>
                        <strong style="color: var(--accent-color);">₹<?php echo number_format($viewOrder['final_amount'], 2); ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Update Status -->
            <div class="order-section no-print">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-edit"></i> Update Order Status</h3>
                <form method="POST" action="">
                    <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-4);">
                        <div class="form-group">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="pending" <?php echo $viewOrder['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $viewOrder['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $viewOrder['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $viewOrder['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Order Status</label>
                            <select name="order_status" class="form-select">
                                <option value="pending" <?php echo $viewOrder['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $viewOrder['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $viewOrder['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $viewOrder['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $viewOrder['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Actions -->
            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button onclick="window.print()" class="btn btn-secondary no-print">
                    <i class="fas fa-print"></i> Print Shipping Label
                </button>
                
                <?php if ($viewOrder['payment_status'] === 'completed'): ?>
                    <a href="<?php echo SITE_URL; ?>/api/invoice.php?order_id=<?php echo $viewOrder['id']; ?>" class="btn btn-outline no-print">
                        <i class="fas fa-download"></i> Download Invoice
                    </a>
                    <button onclick="showRefundForm()" class="btn btn-outline no-print" style="color: var(--error);">
                        <i class="fas fa-undo"></i> Process Refund
                    </button>
                <?php endif; ?>
                
                <button onclick="closeModal()" class="btn btn-outline no-print" style="margin-left: auto;">
                    Close
                </button>
            </div>
            
            <!-- Refund Form (Hidden by default) -->
            <div id="refundForm" style="display: none; margin-top: var(--space-6); padding: var(--space-6); background: #fee2e2; border-radius: var(--radius-lg);">
                <h4 style="margin-bottom: var(--space-4); color: var(--error);">Process Refund</h4>
                <form method="POST" action="">
                    <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                    <input type="hidden" name="process_refund" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Refund Amount</label>
                        <input type="number" name="refund_amount" class="form-input" step="0.01" 
                               value="<?php echo $viewOrder['final_amount']; ?>" max="<?php echo $viewOrder['final_amount']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Refund Reason</label>
                        <textarea name="refund_reason" class="form-textarea" rows="3" required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: var(--space-3);">
                        <button type="submit" class="btn btn-primary" style="background: var(--error);">
                            <i class="fas fa-check"></i> Confirm Refund
                        </button>
                        <button type="button" onclick="hideRefundForm()" class="btn btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 style="font-size: var(--text-xl); font-weight: 700; color: var(--error);">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
                </h2>
                <button onclick="closeDeleteModal()" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="text-align: center; padding: var(--space-4) 0;">
                <p style="margin-bottom: var(--space-4); font-size: var(--text-lg);">
                    Are you sure you want to delete order <strong id="deleteOrderNumber"></strong>?
                </p>
                <p style="color: var(--error); margin-bottom: var(--space-6); font-weight: 600;">
                    This action is permanent and cannot be undone.
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <input type="hidden" name="delete_order" value="1">
                    
                    <div style="display: flex; gap: var(--space-4); justify-content: center;">
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-outline">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="background: var(--error); border-color: var(--error);">
                            Yes, Delete Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
    <script>
        function closeModal() {
            window.location.href = '<?php echo SITE_URL; ?>/admin/orders.php';
        }
        
        function showRefundForm() {
            document.getElementById('refundForm').style.display = 'block';
        }
        
        function hideRefundForm() {
            document.getElementById('refundForm').style.display = 'none';
        }

        function confirmDelete(orderId, orderNumber) {
            document.getElementById('deleteOrderId').value = orderId;
            document.getElementById('deleteOrderNumber').textContent = '#' + orderNumber;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
