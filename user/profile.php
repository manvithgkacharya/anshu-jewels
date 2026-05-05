<?php
require_once __DIR__ . '/../config/config.php';

// Require login
if (!isLoggedIn()) {
    redirect(SITE_URL . '/user/login.php');
}

$error = '';
$success = '';

// Fetch user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        redirect(SITE_URL . '/user/login.php');
    }
} catch (PDOException $e) {
    $error = 'Failed to load profile';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            try {
                // Check if email is already taken by another user
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    $error = 'Email already in use';
                } else {
                    // Update profile
                    $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
                    
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    $success = 'Profile updated successfully!';
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                }
            } catch (PDOException $e) {
                $error = 'Failed to update profile';
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                $success = 'Password changed successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to change password';
            }
        }
    }
}

// Fetch user's recent orders
try {
    $ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $ordersStmt->execute([$_SESSION['user_id']]);
    $recentOrders = $ordersStmt->fetchAll();
} catch (PDOException $e) {
    $recentOrders = [];
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('My Profile', 'Manage your account');
?>

<style>
.profile-section {
    padding: var(--space-12) 0;
}

.profile-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: var(--space-8);
}

.profile-sidebar {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold-500), var(--gold-700));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: white;
    margin: 0 auto var(--space-4);
}

.profile-name {
    text-align: center;
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: var(--space-2);
}

.profile-email {
    text-align: center;
    color: var(--text-secondary);
    font-size: var(--text-sm);
    margin-bottom: var(--space-6);
}

.profile-stats {
    display: grid;
    gap: var(--space-3);
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-color);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-content {
    display: grid;
    gap: var(--space-6);
}

.profile-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-6);
}

.card-title {
    font-size: var(--text-2xl);
    font-weight: 700;
}

.orders-table {
    width: 100%;
}

.orders-table th,
.orders-table td {
    padding: var(--space-3);
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.orders-table th {
    font-weight: 600;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: relative;
        top: 0;
    }
}
</style>

<section class="profile-section">
    <div class="container">
        <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-8);">
            👤 My Profile
        </h1>
        
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
        
        <div class="profile-layout">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span style="color: var(--text-secondary);">Member Since</span>
                        <strong><?php echo date('M Y', strtotime($user['created_at'])); ?></strong>
                    </div>
                    <div class="stat-item">
                        <span style="color: var(--text-secondary);">Total Orders</span>
                        <strong><?php echo count($recentOrders); ?></strong>
                    </div>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/api/logout.php" class="btn btn-outline" style="width: 100%; margin-top: var(--space-6);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </aside>
            
            <!-- Main Content -->
            <div class="profile-content">
                <!-- Profile Information -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2 class="card-title">Profile Information</h2>
                    </div>
                    
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="+91 98765 43210">
                            <span class="form-error"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2 class="card-title">Change Password</h2>
                    </div>
                    
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" 
                                   placeholder="••••••••" required>
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                   placeholder="••••••••" required minlength="6">
                            <span class="form-error"></span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="••••••••" required>
                            <span class="form-error"></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <!-- Recent Orders -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Orders</h2>
                        <a href="<?php echo SITE_URL; ?>/user/orders.php" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    
                    <?php if (empty($recentOrders)): ?>
                        <p style="color: var(--text-secondary); text-align: center; padding: var(--space-8);">
                            No orders yet. <a href="<?php echo SITE_URL; ?>/user/products.php">Start shopping!</a>
                        </p>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td><strong>₹<?php echo number_format($order['final_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
renderFooter();
?>
