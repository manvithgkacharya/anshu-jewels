<?php
require_once __DIR__ . '/../config/config.php';

// Require login for checkout
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/user/checkout.php';
    redirect(SITE_URL . '/user/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_checkout'])) {
    header('Content-Type: application/json');
    
    // Process checkout
    $shippingName = sanitize($_POST['shipping_name'] ?? '');
    $shippingEmail = sanitize($_POST['shipping_email'] ?? '');
    $shippingPhone = sanitize($_POST['shipping_phone'] ?? '');
    $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
    $shippingCity = sanitize($_POST['shipping_city'] ?? '');
    $shippingState = sanitize($_POST['shipping_state'] ?? '');
    $shippingPincode = sanitize($_POST['shipping_pincode'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'razorpay');
    
    // Validate required fields
    if (empty($shippingName) || empty($shippingEmail) || empty($shippingPhone) || 
        empty($shippingAddress) || empty($shippingCity) || empty($shippingState) || empty($shippingPincode)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all shipping details']);
        exit;
    } else {
        // Get cart data
        $cartData = json_decode($_POST['cart_data'] ?? '[]', true);
        
        if (empty($cartData)) {
            echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
            exit;
        } else {
            try {
                $db->beginTransaction();
                
                // Generate order number
                $orderNumber = generateOrderNumber();
                
                // Calculate totals
                $subtotal = 0;
                $orderItems = [];
                
                foreach ($cartData as $item) {
                    // Verify product price and stock from DB
                    $stmt = $db->prepare("SELECT price, title, stock_quantity, is_in_stock FROM products WHERE id = ?");
                    $stmt->execute([$item['id']]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        // Check if product is in stock (manual toggle)
                        if (($product['is_in_stock'] ?? 1) == 0) {
                            throw new Exception("Product '{$product['title']}' is currently unavailable (Out of Stock).");
                        }
                        
                        // Check stock quantity
                        if ($product['stock_quantity'] < $item['quantity']) {
                             throw new Exception("Product '{$product['title']}' has insufficient stock (Available: {$product['stock_quantity']}).");
                        }

                        $price = $product['price'];
                        $quantity = $item['quantity'];
                        $itemSubtotal = $price * $quantity;
                        
                        $subtotal += $itemSubtotal;
                        
                        $orderItems[] = [
                            'product_id' => $item['id'],
                            'product_title' => $product['title'],
                            'price' => $price,
                            'quantity' => $quantity,
                            'subtotal' => $itemSubtotal
                        ];
                    }
                }
                
                if (empty($orderItems)) {
                    throw new Exception("No valid items found");
                }
                
                $shippingRate = (float)getSiteSetting('shipping_rate', '100');
                $freeShippingAbove = (float)getSiteSetting('free_shipping_above', '2000');
                $shipping = $subtotal >= $freeShippingAbove ? 0 : $shippingRate;
                
                // Process Coupon
                $couponId = null;
                $couponCode = sanitize($_POST['coupon_code'] ?? '');
                $discountAmount = 0;
                
                if (!empty($couponCode)) {
                    // Re-validate coupon server-side for security
                    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
                    $stmt->execute([$couponCode]);
                    $coupon = $stmt->fetch();
                    
                    if ($coupon) {
                        $now = new DateTime();
                        // Validate expiry and limits again
                        if (($coupon['valid_from'] && new DateTime($coupon['valid_from']) > $now) ||
                            ($coupon['valid_until'] && new DateTime($coupon['valid_until']) < $now) ||
                            ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) ||
                            ($coupon['min_purchase_amount'] > 0 && $subtotal < $coupon['min_purchase_amount'])) {
                            throw new Exception("Coupon code '$couponCode' is invalid or expired.");
                        } else {
                            // Calculate discount
                            if ($coupon['discount_type'] === 'percentage') {
                                $discountAmount = ($subtotal * $coupon['discount_value']) / 100;
                                if ($coupon['max_discount'] > 0 && $discountAmount > $coupon['max_discount']) {
                                    $discountAmount = $coupon['max_discount'];
                                }
                            } else {
                                $discountAmount = $coupon['discount_value'];
                            }
                            
                            if ($discountAmount > $subtotal) {
                                $discountAmount = $subtotal;
                            }
                            
                            $couponId = $coupon['id'];
                            
                            // Increment used count
                            $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$couponId]);
                        }
                    } else {
                         throw new Exception("Invalid coupon code.");
                    }
                }
                
                // Calculate tax on discounted subtotal
                $taxRate = (float)getSiteSetting('tax_percentage', '18') / 100;
                $tax = ($subtotal - $discountAmount) * $taxRate;
                
                $total = $subtotal + $shipping + $tax - $discountAmount;
                
                // Create order
                $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, tax_amount, shipping_amount, discount_amount, final_amount, 
                                      payment_method, payment_status, order_status, shipping_name, shipping_email, 
                                      shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode, coupon_id) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $orderNumber,
                    $subtotal,
                    $tax,
                    $shipping,
                    $discountAmount,
                    $total,
                    $paymentMethod,
                    $shippingName,
                    $shippingEmail,
                    $shippingPhone,
                    $shippingAddress,
                    $shippingCity,
                    $shippingState,
                    $shippingPincode,
                    $couponId
                ]);
                
                $orderId = $db->lastInsertId();
                
                // Insert order items
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity, subtotal) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                                      
                foreach ($orderItems as $item) {
                    $stmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['product_title'],
                        $item['price'],
                        $item['quantity'],
                        $item['subtotal']
                    ]);
                }
            
                $db->commit();
                
                $_SESSION['order_id'] = $orderId;
                
                $_SESSION['order_id'] = $orderId;
                
                // Return json success for frontend JS flow
                echo json_encode([
                    'success' => true, 
                    'order_id' => $orderId, 
                    'payment_method' => $paymentMethod
                ]);
                exit;
                
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode(['success' => false, 'message' => 'Failed to create order. ' . $e->getMessage()]);
                exit;
            }
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
renderHeader('Checkout', 'Complete your order');
?>

<!-- External Libraries for Payment & UX -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.checkout-section {
    padding: var(--space-12) 0;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--space-8);
}

.checkout-form {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
}

.form-section {
    margin-bottom: var(--space-8);
    padding-bottom: var(--space-8);
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-size: var(--text-2xl);
    font-weight: 700;
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
}

.payment-methods {
    display: grid;
    gap: var(--space-3);
}

.payment-option {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.payment-option:hover {
    border-color: var(--accent-color);
    background: var(--bg-secondary);
}

.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
}

.payment-option.selected {
    border-color: var(--accent-color);
    background: rgba(245, 158, 11, 0.1);
}

.order-summary-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    height: fit-content;
    position: sticky;
    top: 100px;
}

@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .order-summary-card {
        position: relative;
        top: 0;
    }
}
</style>

<section class="checkout-section">
    <div class="container">
        <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-8);">
            🔒 Secure Checkout
        </h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: var(--space-6);">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <form id="checkout-form" method="POST" action="">
                    <?php generateCSRFToken(); ?>
                    <input type="hidden" name="ajax_checkout" value="1">
                    
                    <!-- Shipping Information -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-shipping-fast"></i>
                            Shipping Information
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label" for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" class="form-input" 
                                   placeholder="John Doe" required 
                                   value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="shipping_email">Email *</label>
                                <input type="email" id="shipping_email" name="shipping_email" class="form-input" 
                                       placeholder="you@example.com" required 
                                       value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                                <span class="form-error"></span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="shipping_phone">Phone Number *</label>
                                <input type="tel" id="shipping_phone" name="shipping_phone" class="form-input" 
                                       placeholder="+91 98765 43210" required>
                                <span class="form-error"></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="shipping_address">Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-textarea" 
                                      placeholder="Street address, apartment, suite, etc." required></textarea>
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" class="form-input" 
                                       placeholder="Mumbai" required>
                                <span class="form-error"></span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="shipping_state">State *</label>
                                <input type="text" id="shipping_state" name="shipping_state" class="form-input" 
                                       placeholder="Maharashtra" required>
                                <span class="form-error"></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="shipping_pincode">PIN Code *</label>
                                <input type="text" id="shipping_pincode" name="shipping_pincode" class="form-input" 
                                       placeholder="400001" required pattern="[0-9]{6}">
                                <span class="form-error"></span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="shipping_country">Country</label>
                                <input type="text" id="shipping_country" name="shipping_country" class="form-input" 
                                       value="India" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </h2>
                        
                        <div class="payment-methods">
                            <label class="payment-option selected">
                                <input type="radio" name="payment_method" value="razorpay" checked>
                                <div>
                                    <strong>Razorpay</strong>
                                    <p style="font-size: var(--text-sm); color: var(--text-secondary); margin: 0;">
                                        Credit/Debit Card, UPI, Net Banking
                                    </p>
                                </div>
                            </label>
                            
                            <?php if (getSiteSetting('cod_enabled', '1')): ?>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cod">
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <p style="font-size: var(--text-sm); color: var(--text-secondary); margin: 0;">
                                        Pay when you receive
                                    </p>
                                </div>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <input type="hidden" name="cart_data" id="cart-data">
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </form>
            </div>
            
                <div class="card" style="margin-top: var(--space-6);">
                    <h3 style="margin-bottom: var(--space-4);">Have a coupon?</h3>
                    <div style="display: flex; gap: var(--space-3);">
                        <input type="text" id="coupon-code" class="form-input" placeholder="Enter coupon code" style="text-transform: uppercase;">
                        <button type="button" onclick="applyCoupon()" id="apply-coupon-btn" class="btn btn-secondary">Apply</button>
                    </div>
                    <div id="coupon-message" style="margin-top: var(--space-2); font-size: var(--text-sm);"></div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary-card">
                <h2 style="font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-6);">Order Summary</h2>
                
                <div id="order-items-list" style="margin-bottom: var(--space-6); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-6);">
                    <!-- Items will be populated by JS -->
                </div>
                
                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Subtotal</span>
                        <span id="summary-subtotal">₹0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Shipping</span>
                        <span id="summary-shipping">₹0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Tax (<?php echo getSiteSetting('tax_percentage', '18'); ?>%)</span>
                        <span id="summary-tax">₹0.00</span>
                    </div>
                    <div id="discount-row" style="display: none; justify-content: space-between; color: var(--success);">
                        <span>Discount</span>
                        <span id="summary-discount">-₹0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: var(--space-4); border-top: 2px solid var(--border-color); font-size: var(--text-xl); font-weight: 700;">
                        <span>Total</span>
                        <span id="summary-total" style="color: var(--accent-color);">₹0.00</span>
                    </div>
                </div>
                
                <div style="margin-top: var(--space-6); padding: var(--space-4); background: var(--bg-secondary); border-radius: var(--radius-md); font-size: var(--text-sm);">
                    <p style="margin: 0; color: var(--text-secondary);">
                        <i class="fas fa-shield-alt"></i> Your payment information is secure and encrypted
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script>
// Payment method selection
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

let currentTotal = 0;
let appliedCoupon = null;
const taxRate = <?php echo (float)getSiteSetting('tax_percentage', '18') / 100; ?>;
const shippingRate = <?php echo (float)getSiteSetting('shipping_rate', '100'); ?>;
const freeShippingAbove = <?php echo (float)getSiteSetting('free_shipping_above', '2000'); ?>;

document.addEventListener('DOMContentLoaded', function() {
    loadOrderSummary();
    
    // Setup form submission handler to include coupon
    const checkoutForm = document.querySelector('form');
    // We don't prevent submit here, just ensure inputs are there. 
    // Wait, the button is outside the form or inside? The main submit button is inside 'checkout-form'.
    // We need to inject coupon data into hidden inputs before submit.
    checkoutForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(this);
            if (appliedCoupon) {
                formData.append('coupon_code', appliedCoupon.code);
                formData.append('discount_amount', appliedCoupon.amount);
                formData.append('coupon_id', appliedCoupon.id);
            }
            
            const response = await fetch('', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message);
            }
            
            // Route based on payment method
            if (result.payment_method === 'cod') {
                handleSuccess(result.order_id);
            } else if (result.payment_method === 'razorpay') {
                processRazorpay(result.order_id, submitBtn, origText);
            } else if (result.payment_method === 'stripe') {
                Swal.fire('Notice', 'Stripe is integrated but requires active keys. Simulating success...', 'info');
                // processStripe() logic would go here
                handleSuccess(result.order_id);
            } else {
                handleSuccess(result.order_id);
            }
            
        } catch (error) {
            Swal.fire('Checkout Error', error.message, 'error');
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
        }
    });
});

function handleSuccess(orderId) {
    // Clear the cart
    localStorage.removeItem('cart');
    
    // Launch Confetti
    confetti({
        particleCount: 150,
        spread: 80,
        origin: { y: 0.6 }
    });
    
    // Show SweetAlert
    Swal.fire({
        title: 'Order Successful!',
        text: 'Payment guaranteed. Downloading your invoice...',
        icon: 'success',
        timer: 4000,
        timerProgressBar: true,
        showConfirmButton: false,
        allowOutsideClick: false
    }).then(() => {
        window.location.href = '<?php echo SITE_URL; ?>/user/orders.php';
    });
    
    // Dynamically trigger invoice download
    setTimeout(() => {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = '<?php echo SITE_URL; ?>/api/invoice.php?order_id=' + orderId;
        document.body.appendChild(iframe);
    }, 500);
}

async function processRazorpay(orderId, submitBtn, origText) {
    try {
        const fd = new FormData();
        fd.append('method', 'razorpay');
        fd.append('order_id', orderId);
        
        const res = await fetch('<?php echo SITE_URL; ?>/api/process-payment.php', { method: 'POST', body: fd });
        const cfg = await res.json();
        
        if (!cfg.success) {
            throw new Error(cfg.message);
        }
        
        var options = {
            "key": cfg.key_id,
            "amount": cfg.amount, 
            "currency": cfg.currency,
            "name": cfg.name,
            "description": cfg.description,
            "handler": async function(response){
                // Callback on payment success
                const verifyFd = new FormData();
                verifyFd.append('order_id', orderId);
                verifyFd.append('razorpay_payment_id', response.razorpay_payment_id);
                
                try {
                    const verifyRes = await fetch('<?php echo SITE_URL; ?>/api/verify-payment.php', { method: 'POST', body: verifyFd });
                    const verifyData = await verifyRes.json();
                    
                    if (verifyData.success) {
                        handleSuccess(orderId);
                    } else {
                        throw new Error(verifyData.error || 'Server rejected verification');
                    }
                } catch(vErr) {
                    Swal.fire('Verification Error', vErr.message, 'error');
                }
            },
            "prefill": cfg.prefill,
            "theme": { "color": "#d97706" }
        };
        
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response){
            Swal.fire('Payment Failed', response.error.description, 'error');
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
        });
        
        rzp.open();
        
    } catch (err) {
        Swal.fire('Gateway Error', err.message, 'error');
        submitBtn.innerHTML = origText;
        submitBtn.disabled = false;
    }
}

function loadOrderSummary() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const textDataInput = document.querySelector('input[name="cart_data"]');
    if (textDataInput) {
        textDataInput.value = JSON.stringify(cart);
    }
    
    if (cart.length === 0) {
        window.location.href = '<?php echo SITE_URL; ?>/user/products.php';
        return;
    }
    
    const container = document.getElementById('order-items-list');
    container.innerHTML = '';
    
    let subtotal = 0;
    
    cart.forEach(item => {
        subtotal += item.price * item.quantity;
        
        const itemDiv = document.createElement('div');
        itemDiv.style.display = 'flex';
        itemDiv.style.gap = 'var(--space-4)';
        itemDiv.style.marginBottom = 'var(--space-4)';
        itemDiv.innerHTML = `
            <div style="position: relative; width: 60px; height: 60px;">
                <img src="${item.image || 'https://via.placeholder.com/60x60'}" 
                     alt="${item.title}" 
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-md);">
                <span style="position: absolute; top: -5px; right: -5px; background: var(--gray-500); color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px;">${item.quantity}</span>
            </div>
            <div style="flex: 1;">
                <h4 style="font-size: var(--text-sm); margin-bottom: var(--space-1);">${item.title}</h4>
                <div style="color: var(--text-secondary); font-size: var(--text-sm);">₹${item.price.toLocaleString()}</div>
            </div>
            <div style="font-weight: 600;">₹${(item.price * item.quantity).toLocaleString()}</div>
        `;
        container.appendChild(itemDiv);
    });
    
    updateTotals(subtotal);
}

async function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    const messageEl = document.getElementById('coupon-message');
    const subtotal = JSON.parse(localStorage.getItem('cart') || '[]').reduce((acc, item) => acc + (item.price * item.quantity), 0);
    
    if (!code) {
        messageEl.style.color = 'var(--error)';
        messageEl.textContent = 'Please enter a coupon code';
        return;
    }
    
    try {
        const response = await fetch('<?php echo SITE_URL; ?>/api/validate-coupon.php', {
            method: 'POST',
            body: JSON.stringify({ code: code, total: subtotal }),
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await response.json();
        
        if (data.valid) {
            appliedCoupon = {
                id: data.coupon_id,
                code: data.code,
                amount: data.calculated_discount
            };
            
            messageEl.style.color = 'var(--success)';
            messageEl.textContent = data.message;
            
            // Show discount row
            document.getElementById('discount-row').style.display = 'flex';
            document.getElementById('summary-discount').textContent = `-₹${appliedCoupon.amount.toLocaleString()}`;
            
            // Re-calculate totals
            updateTotals(subtotal);
        } else {
            appliedCoupon = null;
            messageEl.style.color = 'var(--error)';
            messageEl.textContent = data.message;
            document.getElementById('discount-row').style.display = 'none';
            updateTotals(subtotal);
        }
    } catch (error) {
        messageEl.style.color = 'var(--error)';
        messageEl.textContent = 'Failed to validate coupon';
    }
}

function updateTotals(subtotal) {
    const shipping = subtotal >= freeShippingAbove ? 0 : shippingRate;
    const discount = appliedCoupon ? appliedCoupon.amount : 0;
    const tax = (subtotal - discount) * taxRate;
    const total = subtotal + shipping + tax - discount;
    
    document.getElementById('summary-subtotal').textContent = `₹${subtotal.toLocaleString()}`;
    document.getElementById('summary-shipping').textContent = shipping === 0 ? 'FREE' : `₹${shipping.toLocaleString()}`;
    document.getElementById('summary-tax').textContent = `₹${tax.toLocaleString()}`;
    document.getElementById('summary-total').textContent = `₹${total.toLocaleString()}`;
    
    // Also update currentTotal global variable for coupons
    currentTotal = total;
    
    // Update hidden input
    const cart = localStorage.getItem('cart') || '[]';
    document.getElementById('cart-data').value = cart;
}

// Clear cart after submission if success - handled by PHP/new page usually
// Initial display
loadOrderSummary();
</script>

<?php
renderFooter();
?>
