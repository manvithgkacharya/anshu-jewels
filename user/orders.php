<?php
require_once __DIR__ . '/../config/config.php';

// Require login
if (!isLoggedIn()) {
    redirect(SITE_URL . '/user/login.php');
}

$success = $_GET['success'] ?? '';
$paymentSuccess = $_GET['payment_success'] ?? '';
$downloadOrderId = $_GET['order_id'] ?? '';

// Fetch user's orders
try {
    $stmt = $db->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                          FROM orders o 
                          LEFT JOIN order_items oi ON o.id = oi.order_id 
                          WHERE o.user_id = ? 
                          GROUP BY o.id 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('My Orders', 'View your order history');
?>

<style>
.orders-section {
    padding: var(--space-12) 0;
    min-height: 70vh;
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-8);
}

.order-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
    transition: all var(--transition-base);
}

.order-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--border-color);
}

.order-info {
    flex: 1;
}

.order-number {
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: var(--space-2);
}

.order-date {
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.order-status {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: 600;
    margin-bottom: var(--space-2);
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-failed {
    background: #fee2e2;
    color: #991b1b;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.detail-label {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-weight: 600;
}

.order-actions {
    display: flex;
    gap: var(--space-3);
}

.empty-orders {
    text-align: center;
    padding: var(--space-16);
}

.empty-icon {
    font-size: 5rem;
    color: var(--text-tertiary);
    margin-bottom: var(--space-6);
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        gap: var(--space-4);
    }
    
    .order-status {
        text-align: left;
    }
    
    .order-details {
        grid-template-columns: 1fr;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<section class="orders-section">
    <div class="container">
        <div class="orders-header">
            <div>
                <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">
                    📦 My Orders
                </h1>
                <p style="color: var(--text-secondary);">Track and manage your orders</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: var(--space-6);">
                <i class="fas fa-check-circle"></i> Order placed successfully! You will receive a confirmation email shortly.
            </div>
        <?php endif; ?>
        
        <?php if ($paymentSuccess): ?>
            <div class="alert alert-success" style="margin-bottom: var(--space-6);">
                <i class="fas fa-check-circle"></i> Payment completed successfully! Your invoice is downloading...
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3 style="margin-bottom: var(--space-4);">No orders yet</h3>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">
                    Start shopping to see your orders here!
                </p>
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-gem"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-number">
                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </div>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i> 
                                Placed on <?php echo date('F d, Y', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="order-status">
                            <div class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </div>
                            <div style="font-size: var(--text-2xl); font-weight: 800; color: var(--accent-color);">
                                ₹<?php echo number_format($order['final_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <span class="detail-label">Items</span>
                            <span class="detail-value"><?php echo $order['item_count']; ?> item(s)</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value"><?php echo ucfirst($order['payment_method']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Status</span>
                            <span class="detail-value"><?php echo ucfirst($order['order_status']); ?></span>
                        </div>
                    </div>
                    
                    <div style="background: var(--bg-secondary); padding: var(--space-4); border-radius: var(--radius-md); margin-bottom: var(--space-4);">
                        <strong style="display: block; margin-bottom: var(--space-2);">
                            <i class="fas fa-shipping-fast"></i> Shipping Address:
                        </strong>
                        <p style="color: var(--text-secondary); margin: 0;">
                            <?php echo htmlspecialchars($order['shipping_name']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                            <?php echo htmlspecialchars($order['shipping_state']); ?> - 
                            <?php echo htmlspecialchars($order['shipping_pincode']); ?>
                        </p>
                    </div>
                    
                    <div class="order-actions">
                        <button class="btn btn-secondary view-details-btn" data-order-id="<?php echo $order['id']; ?>">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                        <?php if ($order['payment_status'] === 'completed'): ?>
                            <a href="<?php echo SITE_URL; ?>/api/invoice.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-download"></i> Download Invoice
                            </a>
                        <?php endif; ?>
                        <?php if ($order['order_status'] === 'pending'): ?>
                            <button class="btn btn-outline cancel-order-btn" data-order-id="<?php echo $order['id']; ?>" style="color: var(--error);">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: var(--space-8); color: var(--text-secondary);">
                <p>Need help with an order? <a href="<?php echo SITE_URL; ?>/user/contact.php">Contact Support</a></p>
            </div>
        <?php endif; ?>
    </div>
<!-- Order Details Modal -->
<div id="order-modal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title">Order Details</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body" id="modal-body">
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: var(--bg-primary);
    margin: 10% auto;
    padding: 0;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    width: 90%;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: var(--space-4) var(--space-6);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: var(--text-xl);
    font-weight: 700;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: var(--text-2xl);
    color: var(--text-secondary);
    cursor: pointer;
    transition: color 0.2s;
}

.modal-close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: var(--space-6);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('order-modal');
    const modalBody = document.getElementById('modal-body');
    const closeBtn = document.querySelector('.modal-close');
    
    // Close modal logic
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => {
        if (e.target === modal) modal.style.display = 'none';
    };

    // View Details Logic
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            modal.style.display = 'block';
            modalBody.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            
            try {
                const response = await fetch(`<?php echo SITE_URL; ?>/api/get-order-details.php?id=${orderId}`);
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                let html = `
                    <div style="margin-bottom: var(--space-6);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--space-2);">
                            <strong>Order #${data.order.order_number}</strong>
                            <span>${new Date(data.order.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="status-badge status-${data.order.payment_status}">
                            Payment: ${data.order.payment_status}
                        </div>
                    </div>
                    
                    <h4 style="margin-bottom: var(--space-3); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-2);">Items</h4>
                    <div style="margin-bottom: var(--space-6);">
                `;
                
                data.items.forEach(item => {
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color);">
                            <div>
                                <div style="font-weight: 600;">${item.product_title}</div>
                                <div style="font-size: var(--text-sm); color: var(--text-secondary);">
                                    Qty: ${item.quantity} x ₹${parseFloat(item.product_price).toFixed(2)}
                                </div>
                            </div>
                            <div style="font-weight: 600;">₹${parseFloat(item.subtotal).toFixed(2)}</div>
                        </div>
                    `;
                });
                
                html += `
                    </div>
                    <div style="display: flex; flex-direction: column; gap: var(--space-2); padding-top: var(--space-4); border-top: 1px solid var(--border-color); margin-bottom: var(--space-4);">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Subtotal:</span>
                            <span>₹${parseFloat(data.order.total_amount).toFixed(2)}</span>
                        </div>
                        ${data.order.discount_amount > 0 ? `
                        <div style="display: flex; justify-content: space-between; color: var(--success);">
                            <span>Discount:</span>
                            <span>-₹${parseFloat(data.order.discount_amount).toFixed(2)}</span>
                        </div>
                        ` : ''}
                        <div style="display: flex; justify-content: space-between;">
                            <span>Shipping:</span>
                            <span>₹${parseFloat(data.order.shipping_amount || 0).toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Tax (18%):</span>
                            <span>₹${parseFloat(data.order.tax_amount).toFixed(2)}</span>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: var(--text-lg); padding-top: var(--space-4); border-top: 2px solid var(--border-color); margin-bottom: var(--space-6);">
                        <span>Total Paid:</span>
                        <span>₹${parseFloat(data.order.final_amount).toFixed(2)}</span>
                    </div>

                    ${['pending', 'processing'].includes(data.order.order_status) ? `
                        <div style="text-align: center; border-top: 1px solid var(--border-color); padding-top: var(--space-4);">
                            <button class="btn btn-outline modal-cancel-btn" data-order-id="${data.order.id}" style="color: var(--error); border-color: var(--error); width: 100%;">
                                <i class="fas fa-times"></i> Cancel This Order
                            </button>
                            <p style="font-size: var(--text-xs); color: var(--text-tertiary); margin-top: var(--space-2);">
                                You can cancel this order as it is still in <strong>${data.order.order_status}</strong> status.
                            </p>
                        </div>
                    ` : ''}
                `;
                
                modalBody.innerHTML = html;
                
            } catch (error) {
                modalBody.innerHTML = `<div class="alert alert-error">${error.message}</div>`;
            }
        });
    });

    // Unified Cancel Order Function
    async function handleCancelOrder(orderId, button) {
        if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) return;
        
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        button.disabled = true;
        
        try {
            const response = await fetch('<?php echo SITE_URL; ?>/api/cancel-order.php', {
                method: 'POST',
                body: JSON.stringify({ order_id: orderId }),
                headers: { 'Content-Type': 'application/json' }
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Order cancelled successfully');
                window.location.reload();
            } else {
                throw new Error(result.error || 'Failed to cancel order');
            }
        } catch (error) {
            alert(error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    // List buttons
    document.querySelectorAll('.cancel-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            handleCancelOrder(this.dataset.orderId, this);
        });
    });

    // Modal button (using delegation)
    modalBody.addEventListener('click', function(e) {
        const cancelBtn = e.target.closest('.modal-cancel-btn');
        if (cancelBtn) {
            handleCancelOrder(cancelBtn.dataset.orderId, cancelBtn);
        }
    });
});
</script>

<?php
renderFooter();
?>
