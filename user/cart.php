<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';

renderHeader('Shopping Cart', 'Review your cart items');
?>

<style>
.cart-section {
    padding: var(--space-12) 0;
    min-height: 70vh;
}

.cart-header {
    margin-bottom: var(--space-8);
}

.cart-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--space-8);
}

.cart-items-container {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
}

.cart-item {
    display: flex;
    gap: var(--space-4);
    padding: var(--space-5);
    border-bottom: 1px solid var(--border-color);
    transition: background var(--transition-fast);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item:hover {
    background: var(--bg-secondary);
}

.cart-item-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--radius-md);
    flex-shrink: 0;
}

.cart-item-info {
    flex: 1;
}

.cart-item-title {
    font-size: var(--text-lg);
    font-weight: 600;
    margin-bottom: var(--space-2);
}

.cart-item-price {
    font-size: var(--text-xl);
    font-weight: 700;
    color: var(--accent-color);
    margin-bottom: var(--space-3);
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.cart-quantity {
    width: 80px;
    padding: var(--space-2);
    text-align: center;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-weight: 600;
}

.cart-summary {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--space-4);
    color: var(--text-secondary);
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding-top: var(--space-4);
    margin-top: var(--space-4);
    border-top: 2px solid var(--border-color);
    font-size: var(--text-2xl);
    font-weight: 800;
}

.empty-cart {
    text-align: center;
    padding: var(--space-16);
}

.empty-cart-icon {
    font-size: 5rem;
    color: var(--text-tertiary);
    margin-bottom: var(--space-6);
}

@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
    
    .cart-summary {
        position: relative;
        top: 0;
    }
    
    .cart-item {
        flex-direction: column;
    }
    
    .cart-item-image {
        width: 100%;
        height: 200px;
    }
}
</style>

<section class="cart-section">
    <div class="container">
        <div class="cart-header">
            <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">
                🛒 Shopping Cart
            </h1>
            <p style="color: var(--text-secondary);">Review your items before checkout</p>
        </div>
        
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items-container">
                <h3 style="margin-bottom: var(--space-6);">Cart Items</h3>
                <div id="cart-items">
                    <!-- Cart items will be populated by JavaScript -->
                    <div class="empty-cart">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 style="margin-bottom: var(--space-4);">Your cart is empty</h3>
                        <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">
                            Start adding some beautiful jewelry pieces!
                        </p>
                        <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-gem"></i> Browse Products
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <h3 style="margin-bottom: var(--space-6);">Order Summary</h3>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="cart-subtotal">₹0.00</span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span id="cart-shipping">₹0.00</span>
                </div>
                
                <div class="summary-row">
                    <span>Tax (<?php echo getSiteSetting('tax_percentage', '18'); ?>%):</span>
                    <span id="cart-tax">₹0.00</span>
                </div>
                
                <div id="cart-discount-row" class="summary-row" style="display: none; color: var(--success);">
                    <span>Discount:</span>
                    <span id="cart-discount">-₹0.00</span>
                </div>
                
                <div class="summary-total">
                    <span>Total:</span>
                    <span id="cart-total">₹0.00</span>
                </div>
                
                <button class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-6);" 
                        onclick="window.location.href='<?php echo SITE_URL; ?>/user/checkout.php'">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </button>
                
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-outline" style="width: 100%; margin-top: var(--space-3); text-align: center; display: block;">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
                
                <div style="margin-top: var(--space-6); padding: var(--space-4); background: var(--bg-secondary); border-radius: var(--radius-md);">
                    <h4 style="margin-bottom: var(--space-3); font-size: var(--text-sm);">
                        <i class="fas fa-tag"></i> Have a coupon code?
                    </h4>
                    <div style="display: flex; gap: var(--space-2);">
                        <input type="text" id="coupon-code" class="form-input" placeholder="Enter code" style="flex: 1;">
                        <button id="apply-coupon" class="btn btn-secondary">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Global state for cart page
let appliedCoupon = null;
const taxRate = <?php echo (float)getSiteSetting('tax_percentage', '18') / 100; ?>;
const shippingRate = <?php echo (float)getSiteSetting('shipping_rate', '100'); ?>;
const freeShippingAbove = <?php echo (float)getSiteSetting('free_shipping_above', '2000'); ?>;

// Enhanced cart display with calculations
function renderCartPage() {
    const cartContainer = document.getElementById('cart-items');
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 style="margin-bottom: var(--space-4);">Your cart is empty</h3>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">
                    Start adding some beautiful jewelry pieces!
                </p>
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-gem"></i> Browse Products
                </a>
            </div>
        `;
        updateCartSummary(0, 0, 0, 0, 0);
        return;
    }
    
    let html = '';
    let subtotal = 0;
    
    cart.forEach(item => {
        const price = parseFloat(item.price);
        const itemTotal = price * parseInt(item.quantity);
        subtotal += itemTotal;
        
        html += `
            <div class="cart-item">
                <img src="${item.image || 'https://via.placeholder.com/120'}" alt="${item.title}" class="cart-item-image">
                <div class="cart-item-info">
                    <h4 class="cart-item-title">${item.title}</h4>
                    <div class="cart-item-price">₹${price.toFixed(2)}</div>
                    <div class="cart-item-controls">
                        <input type="number" value="${item.quantity}" min="1" 
                               class="cart-quantity" data-id="${item.id}">
                        <button class="btn btn-sm btn-secondary remove-from-cart" data-id="${item.id}">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: var(--text-lg); font-weight: 700; color: var(--accent-color);">
                        ₹${itemTotal.toFixed(2)}
                    </div>
                </div>
            </div>
        `;
    });
    
    cartContainer.innerHTML = html;
    
    // Calculate totals
    const shippingCost = subtotal >= freeShippingAbove ? 0 : shippingRate;
    const discount = appliedCoupon ? appliedCoupon.amount : 0;
    const taxAmount = (subtotal - discount) * taxRate;
    const finalTotal = subtotal + shippingCost + taxAmount - discount;
    
    updateCartSummary(subtotal, shippingCost, taxAmount, finalTotal, discount);
    
    // Attach event listeners
    document.querySelectorAll('.cart-quantity').forEach(input => {
        input.addEventListener('change', function() {
            updateCartQuantity(this.dataset.id, this.value);
            renderCartPage();
        });
    });
    
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            removeFromCart(this.dataset.id);
            renderCartPage();
        });
    });
}

function updateCartSummary(subtotal, shipping, tax, total, discount) {
    document.getElementById('cart-subtotal').textContent = `₹${subtotal.toFixed(2)}`;
    const shippingEl = document.getElementById('cart-shipping');
    if (shippingEl) shippingEl.textContent = shipping === 0 ? 'FREE' : `₹${shipping.toFixed(2)}`;
    document.getElementById('cart-tax').textContent = `₹${tax.toFixed(2)}`;
    document.getElementById('cart-total').textContent = `₹${total.toFixed(2)}`;
    
    const discountRow = document.getElementById('cart-discount-row');
    const discountEl = document.getElementById('cart-discount');
    if (discount > 0) {
        discountRow.style.display = 'flex';
        discountEl.textContent = `-₹${discount.toFixed(2)}`;
    } else {
        discountRow.style.display = 'none';
    }
}

async function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    if (!code) return;
    
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const subtotal = cart.reduce((acc, item) => acc + (parseFloat(item.price) * parseInt(item.quantity)), 0);
    
    try {
        const response = await fetch('<?php echo SITE_URL; ?>/api/validate-coupon.php', {
            method: 'POST',
            body: JSON.stringify({ code: code, total: subtotal }),
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        const btn = document.getElementById('apply-coupon');
        
        if (data.valid) {
            appliedCoupon = {
                id: data.coupon_id,
                code: data.code,
                amount: data.calculated_discount
            };
            btn.innerHTML = '<i class="fas fa-check"></i> Applied';
            btn.classList.replace('btn-secondary', 'btn-success');
            renderCartPage();
        } else {
            alert(data.message || 'Invalid coupon');
            appliedCoupon = null;
            renderCartPage();
        }
    } catch (error) {
        console.error('Coupon validation error:', error);
    }
}

// Attach coupon listener
document.getElementById('apply-coupon').addEventListener('click', applyCoupon);

// Use DOMContentLoaded to ensure we override main.js correctly
document.addEventListener('DOMContentLoaded', function() {
    window.updateCartDisplay = renderCartPage;
    renderCartPage();
});

// Also set a timeout as a fail-safe against main.js loading late or deferred
setTimeout(() => {
    window.updateCartDisplay = renderCartPage;
}, 500);
</script>

<?php
renderFooter();
?>
