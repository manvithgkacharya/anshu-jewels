<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle admin removal
if (isset($_GET['remove_admin'])) {
    $remove_id = (int)$_GET['remove_admin'];
    if ($remove_id === $_SESSION['admin_id']) {
        $error = 'You cannot remove yourself.';
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$remove_id]);
            $success = 'Admin removed successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to remove admin.';
        }
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Explicitly handle checkboxes (booleans)
        $checkboxes = ['razorpay_enabled', 'stripe_enabled', 'paypal_enabled', 'cod_enabled', 'tax_included'];
        foreach ($checkboxes as $checkbox) {
            if (!isset($_POST[$checkbox])) {
                $_POST[$checkbox] = '0';
            }
        }

        $exclude_keys = ['csrf_token', 'new_admin_username', 'new_admin_email', 'new_admin_password', 'new_admin_role'];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, $exclude_keys)) {
                $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                      VALUES (?, ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }
        
        // Handle new admin creation
        if (!empty($_POST['new_admin_email']) && !empty($_POST['new_admin_password']) && !empty($_POST['new_admin_username'])) {
            $new_username = trim($_POST['new_admin_username']);
            $new_email = trim($_POST['new_admin_email']);
            $new_password = password_hash($_POST['new_admin_password'], PASSWORD_DEFAULT);
            $new_role = $_POST['new_admin_role'] ?? 'editor';
            
            // Check if admin already exists
            $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ? OR username = ?");
            $stmt->execute([$new_email, $new_username]);
            if ($stmt->rowCount() > 0) {
                $error = 'Admin with this email or username already exists. Settings were saved.';
            } else {
                $stmt = $db->prepare("INSERT INTO admin_users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_username, $new_email, $new_password, $new_role]);
                $success = 'Settings updated and new admin added successfully!';
            }
        } else {
            $success = 'Settings updated successfully!';
        }
        
        // Handle file uploads for slider images
        for ($i = 1; $i <= 4; $i++) {
            $fileKey = "hero_slide_{$i}_image_file";
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                // Determine file extension
                $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
                // Create unique filename
                $filename = "hero-{$i}-" . time() . "." . $ext;
                $destination = SLIDER_IMAGE_PATH . $filename;
                
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destination)) {
                    // Save to site_settings
                    $settingKey = "hero_slide_{$i}_image";
                    $settingValue = SLIDER_IMAGE_URL . $filename;
                    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) 
                                          VALUES (?, ?) 
                                          ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$settingKey, $settingValue, $settingValue]);
                }
            }
        }
        
        $db->commit();
        if (empty($success)) {
            $success = 'Settings updated successfully!';
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Failed to update settings';
    }
}

// Fetch current settings
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    $settingsArray = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settingsArray = [];
}

// Fetch all admins
try {
    $stmt = $db->query("SELECT id, username, email, role, created_at FROM admin_users ORDER BY created_at DESC");
    $adminsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $adminsList = [];
}

function getSetting($key, $default = '') {
    global $settingsArray;
    return $settingsArray[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Anshu Jewels Admin</title>
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
        .admin-content { padding: var(--space-8); max-width: 1200px; }
        .settings-tabs { display: flex; gap: var(--space-2); margin-bottom: var(--space-8); border-bottom: 2px solid var(--border-color); }
        .tab-button { padding: var(--space-3) var(--space-6); background: none; border: none; color: var(--text-secondary); font-weight: 600; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all var(--transition-fast); }
        .tab-button.active { color: var(--accent-color); border-bottom-color: var(--accent-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .settings-section { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); padding: var(--space-6); margin-bottom: var(--space-6); }
        .section-title { font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-4); padding-bottom: var(--space-3); border-bottom: 1px solid var(--border-color); }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/reports.php" class="admin-menu-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link active"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);"><a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link"><i class="fas fa-globe"></i> View Website</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Settings</h1>
            <p style="color: var(--text-secondary); margin-bottom: var(--space-8);">Configure your store settings</p>
            
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
            
            <!-- Tabs -->
            <div class="settings-tabs">
                <button class="tab-button active" onclick="switchTab('general')">
                    <i class="fas fa-store"></i> General
                </button>
                <button class="tab-button" onclick="switchTab('slider')">
                    <i class="fas fa-images"></i> Home Slider
                </button>
                <button class="tab-button" onclick="switchTab('payment')">
                    <i class="fas fa-credit-card"></i> Payment Gateways
                </button>
                <button class="tab-button" onclick="switchTab('email')">
                    <i class="fas fa-envelope"></i> Email
                </button>
                <button class="tab-button" onclick="switchTab('tax')">
                    <i class="fas fa-calculator"></i> Tax & Shipping
                </button>
                <button class="tab-button" onclick="switchTab('admin')">
                    <i class="fas fa-user-shield"></i> Admins
                </button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <?php generateCSRFToken(); ?>
                
                <!-- General Tab -->
                <div id="general-tab" class="tab-content active">
                    <div class="settings-section">
                        <h3 class="section-title">Site Information</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('site_name', 'Anshu Jewels')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Site Tagline</label>
                            <input type="text" name="site_tagline" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('site_tagline', 'Handmade Jewelry with Love')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('contact_email', 'info@anshujewels.com')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('contact_phone', '+91 98765 43210')); ?>">
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3 class="section-title">Branding</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Primary Color (Hex)</label>
                            <input type="color" name="primary_color" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('primary_color', '#f59e0b')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Logo URL</label>
                            <input type="text" name="logo_url" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('logo_url', '')); ?>" 
                                   placeholder="/assets/images/logo.png">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Tab -->
                <div id="payment-tab" class="tab-content">
                    <div class="settings-section">
                        <h3 class="section-title"><i class="fab fa-cc-stripe"></i> Razorpay</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Razorpay Key ID</label>
                            <input type="text" name="razorpay_key_id" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('razorpay_key_id', '')); ?>" 
                                   placeholder="rzp_test_xxxxxxxxxxxxx">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Razorpay Key Secret</label>
                            <input type="password" name="razorpay_key_secret" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('razorpay_key_secret', '')); ?>" 
                                   placeholder="••••••••••••••••">
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="checkbox" name="razorpay_enabled" value="1" 
                                       <?php echo getSetting('razorpay_enabled') ? 'checked' : ''; ?>>
                                <span>Enable Razorpay</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3 class="section-title"><i class="fab fa-stripe"></i> Stripe</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Stripe Publishable Key</label>
                            <input type="text" name="stripe_public_key" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('stripe_public_key', '')); ?>" 
                                   placeholder="pk_test_xxxxxxxxxxxxx">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Stripe Secret Key</label>
                            <input type="password" name="stripe_secret_key" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('stripe_secret_key', '')); ?>" 
                                   placeholder="sk_test_xxxxxxxxxxxxx">
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="checkbox" name="stripe_enabled" value="1" 
                                       <?php echo getSetting('stripe_enabled') ? 'checked' : ''; ?>>
                                <span>Enable Stripe</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3 class="section-title"><i class="fab fa-paypal"></i> PayPal</h3>
                        
                        <div class="form-group">
                            <label class="form-label">PayPal Client ID</label>
                            <input type="text" name="paypal_client_id" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('paypal_client_id', '')); ?>" 
                                   placeholder="AXXXXXXXXXXXXXXXXXXXXXx">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">PayPal Secret</label>
                            <input type="password" name="paypal_secret" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('paypal_secret', '')); ?>" 
                                   placeholder="••••••••••••••••">
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="checkbox" name="paypal_enabled" value="1" 
                                       <?php echo getSetting('paypal_enabled') ? 'checked' : ''; ?>>
                                <span>Enable PayPal</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3 class="section-title">Cash on Delivery</h3>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="checkbox" name="cod_enabled" value="1" 
                                       <?php echo getSetting('cod_enabled', '1') ? 'checked' : ''; ?>>
                                <span>Enable Cash on Delivery</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Email Tab -->
                <div id="email-tab" class="tab-content">
                    <div class="settings-section">
                        <h3 class="section-title">SMTP Configuration</h3>
                        
                        <div class="form-group">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('smtp_host', 'smtp.gmail.com')); ?>" 
                                   placeholder="smtp.gmail.com">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                            <div class="form-group">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-input" 
                                       value="<?php echo htmlspecialchars(getSetting('smtp_port', '587')); ?>" 
                                       placeholder="587">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" <?php echo getSetting('smtp_encryption') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo getSetting('smtp_encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('smtp_username', '')); ?>" 
                                   placeholder="your-email@gmail.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('smtp_password', '')); ?>" 
                                   placeholder="••••••••••••••••">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">From Email</label>
                            <input type="email" name="from_email" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('from_email', 'noreply@anshujewels.com')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" class="form-input" 
                                   value="<?php echo htmlspecialchars(getSetting('from_name', 'Anshu Jewels')); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Tax & Shipping Tab -->
                <div id="tax-tab" class="tab-content">
                    <div class="settings-section">
                        <h3 class="section-title">Tax Settings</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" name="tax_percentage" class="form-input" step="0.01" 
                                   value="<?php echo htmlspecialchars(getSetting('tax_percentage', '18')); ?>">
                            <small style="color: var(--text-tertiary);">GST/VAT percentage</small>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                                <input type="checkbox" name="tax_included" value="1" 
                                       <?php echo getSetting('tax_included') ? 'checked' : ''; ?>>
                                <span>Tax included in product prices</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3 class="section-title">Shipping Settings</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Flat Shipping Rate (₹)</label>
                            <input type="number" name="shipping_rate" class="form-input" step="0.01" 
                                   value="<?php echo htmlspecialchars(getSetting('shipping_rate', '100')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Free Shipping Above (₹)</label>
                            <input type="number" name="free_shipping_above" class="form-input" step="0.01" 
                                   value="<?php echo htmlspecialchars(getSetting('free_shipping_above', '2000')); ?>">
                            <small style="color: var(--text-tertiary);">Orders above this amount get free shipping</small>
                        </div>
                    </div>
                </div>
                
                <!-- Home Slider Tab -->
                <div id="slider-tab" class="tab-content">
                    <?php 
                    $defaultSlideTitles = [
                        1 => '✨ Timeless Elegance',
                        2 => '💍 Pure Craftsmanship',
                        3 => '🌊 Summer Dreams',
                        4 => '✨ Luxury Defined'
                    ];
                    $defaultSlideSubtitles = [
                        1 => 'Discover our handcrafted necklace collection, where every piece tells a story of passion and precision.',
                        2 => 'Exquisite rings designed for those who appreciate the finer things in life. Skin-safe and hypoallergenic.',
                        3 => 'Our new coastal-inspired pieces are perfect for your next getaway. Lightweight, durable, and beautiful.',
                        4 => 'Complete your look with our premium range of accessories. Designed to elevate every outfit.'
                    ];
                    
                    for ($i = 1; $i <= 4; $i++): 
                    ?>
                        <div class="settings-section">
                            <h3 class="section-title">Slide <?php echo $i; ?></h3>
                            
                            <div class="form-group">
                                <label class="form-label">Slide <?php echo $i; ?> Title</label>
                                <input type="text" name="hero_slide_<?php echo $i; ?>_title" class="form-input" 
                                       value="<?php echo htmlspecialchars(getSetting('hero_slide_' . $i . '_title', $defaultSlideTitles[$i])); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Slide <?php echo $i; ?> Subtitle</label>
                                <textarea name="hero_slide_<?php echo $i; ?>_subtitle" class="form-input" rows="2"><?php echo htmlspecialchars(getSetting('hero_slide_' . $i . '_subtitle', $defaultSlideSubtitles[$i])); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Slide <?php echo $i; ?> Background Image</label>
                                <?php 
                                $currentImage = getSetting('hero_slide_' . $i . '_image', SITE_URL . '/assets/images/hero/hero-' . $i . '.jpg'); 
                                if ($currentImage): 
                                ?>
                                    <div style="margin-bottom: var(--space-2);">
                                        <img src="<?php echo htmlspecialchars($currentImage); ?>" alt="Slide <?php echo $i; ?>" style="max-height: 100px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="hero_slide_<?php echo $i; ?>_image_file" class="form-input" accept="image/*">
                                <small style="color: var(--text-tertiary);">Leave empty if you don't want to change the current image.</small>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Admins Tab -->
                <div id="admin-tab" class="tab-content">
                    <div class="settings-section" style="margin-bottom: var(--space-6);">
                        <h3 class="section-title">Current Admins</h3>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--border-color);">
                                        <th style="padding: var(--space-3);">Username</th>
                                        <th style="padding: var(--space-3);">Email</th>
                                        <th style="padding: var(--space-3);">Role</th>
                                        <th style="padding: var(--space-3);">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminsList as $adm): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: var(--space-3);"><?php echo htmlspecialchars($adm['username']); ?></td>
                                        <td style="padding: var(--space-3);"><?php echo htmlspecialchars($adm['email']); ?></td>
                                        <td style="padding: var(--space-3);"><span class="badge" style="background: var(--bg-secondary); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($adm['role']); ?></span></td>
                                        <td style="padding: var(--space-3);">
                                            <?php if ($adm['id'] !== $_SESSION['admin_id']): ?>
                                                <a href="?remove_admin=<?php echo $adm['id']; ?>" class="btn btn-sm" style="background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 0.875rem;" onclick="return confirm('Are you sure you want to remove this admin?');"><i class="fas fa-trash"></i> Remove</a>
                                            <?php else: ?>
                                                <span style="color: var(--text-tertiary); font-size: 0.875rem;">(You)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3 class="section-title">Add New Admin</h3>
                        <p style="color: var(--text-secondary); margin-bottom: var(--space-4);">Create a new admin user who can log into this dashboard. Fill out these fields and click Save All Settings.</p>
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="new_admin_username" class="form-input" placeholder="e.g., john_doe" autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address (Gmail/Other)</label>
                            <input type="email" name="new_admin_email" class="form-input" placeholder="admin@example.com" autocomplete="off">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="new_admin_password" class="form-input" placeholder="Create a strong password" autocomplete="new-password">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="new_admin_role" class="form-select">
                                <option value="editor">Editor (Limited Access)</option>
                                <option value="super_admin">Super Admin (Full Access)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div style="position: sticky; bottom: var(--space-6); background: var(--bg-primary); padding: var(--space-6); border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-xl);">
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
