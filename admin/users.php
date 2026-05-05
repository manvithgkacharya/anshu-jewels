<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle user block/unblock
if (isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];
    
    try {
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        $success = $newStatus ? 'User activated successfully!' : 'User blocked successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to update user status';
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    try {
        // Check if user has orders
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $orderCount = $stmt->fetch()['count'];
        
        if ($orderCount > 0) {
            $error = 'Cannot delete user with existing orders. Please block the user instead.';
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $success = 'User deleted successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Failed to delete user';
    }
}

// Fetch all users
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

try {
    $query = "SELECT u.*, 
              COUNT(DISTINCT o.id) as order_count,
              COALESCE(SUM(o.final_amount), 0) as total_spent
              FROM users u
              LEFT JOIN orders o ON u.id = o.user_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    if ($statusFilter === 'active') {
        $query .= " AND u.is_active = 1";
    } elseif ($statusFilter === 'blocked') {
        $query .= " AND u.is_active = 0";
    }
    
    $query .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// Fetch user details for modal
$viewUser = null;
$userOrders = [];
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    try {
        $stmt = $db->prepare("SELECT u.*,
                              COUNT(DISTINCT o.id) as order_count,
                              COALESCE(SUM(o.final_amount), 0) as total_spent
                              FROM users u
                              LEFT JOIN orders o ON u.id = o.user_id
                              WHERE u.id = ?
                              GROUP BY u.id");
        $stmt->execute([$viewId]);
        $viewUser = $stmt->fetch();
        
        // Fetch user's orders
        $ordersStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $ordersStmt->execute([$viewId]);
        $userOrders = $ordersStmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Failed to load user details';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Anshu Jewels Admin</title>
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
        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4); margin-bottom: var(--space-6); }
        .stat-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-4); }
        .stat-value { font-size: var(--text-3xl); font-weight: 800; color: var(--accent-color); }
        .stat-label { color: var(--text-secondary); font-size: var(--text-sm); margin-top: var(--space-2); }
        .data-table { width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-xl); overflow: hidden; }
        .data-table th, .data-table td { padding: var(--space-4); text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background: var(--bg-secondary); font-weight: 600; font-size: var(--text-sm); text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table tr:last-child td { border-bottom: none; }
        .status-badge { display: inline-block; padding: var(--space-1) var(--space-3); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-blocked { background: #fee2e2; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto; padding: var(--space-8); }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-primary); border-radius: var(--radius-xl); padding: var(--space-8); max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); padding-bottom: var(--space-4); border-bottom: 2px solid var(--border-color); }
        .close-modal { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
        .user-section { margin-bottom: var(--space-6); padding: var(--space-4); background: var(--bg-secondary); border-radius: var(--radius-lg); }
        .user-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-500), var(--gold-700)); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white; margin: 0 auto var(--space-4); }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/users.php" class="admin-menu-link active"><i class="fas fa-users"></i> Users</a></li>
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
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">User Management</h1>
                    <p style="color: var(--text-secondary);">Manage customer accounts</p>
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
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></div>
                    <div class="stat-label"><i class="fas fa-users"></i> Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($users, fn($u) => !$u['is_active'])); ?></div>
                    <div class="stat-label"><i class="fas fa-user-slash"></i> Blocked Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($users); ?></div>
                    <div class="stat-label"><i class="fas fa-user-friends"></i> Total Users</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" style="display: flex; gap: var(--space-4); flex: 1;">
                    <input type="text" name="search" class="form-input" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" style="flex: 1;">
                    
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Users</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked Only</option>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>
            
            <!-- Users Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: var(--space-8); color: var(--text-secondary);">
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-500), var(--gold-700)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $user['order_count']; ?></td>
                                <td><strong>₹<?php echo number_format($user['total_spent'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'blocked'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Blocked'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <a href="?view=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                            <input type="hidden" name="toggle_status" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline" 
                                                    style="color: <?php echo $user['is_active'] ? 'var(--error)' : 'var(--success)'; ?>;">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- User Details Modal -->
    <?php if ($viewUser): ?>
    <div id="userModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: var(--text-2xl); font-weight: 700;">User Details</h2>
                <button onclick="closeModal()" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- User Profile -->
            <div class="user-section">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($viewUser['name'], 0, 1)); ?>
                </div>
                <h3 style="text-align: center; margin-bottom: var(--space-2);">
                    <?php echo htmlspecialchars($viewUser['name']); ?>
                </h3>
                <p style="text-align: center; color: var(--text-secondary); margin-bottom: var(--space-4);">
                    <?php echo htmlspecialchars($viewUser['email']); ?>
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4); text-align: center;">
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent-color);">
                            <?php echo $viewUser['order_count']; ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: var(--text-sm);">Orders</div>
                    </div>
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent-color);">
                            ₹<?php echo number_format($viewUser['total_spent'], 0); ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: var(--text-sm);">Total Spent</div>
                    </div>
                    <div>
                        <div style="font-size: var(--text-2xl); font-weight: 700; color: var(--accent-color);">
                            <?php echo date('M Y', strtotime($viewUser['created_at'])); ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: var(--text-sm);">Member Since</div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="user-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-address-card"></i> Contact Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div>
                        <strong>Email:</strong><br>
                        <a href="mailto:<?php echo htmlspecialchars($viewUser['email']); ?>">
                            <?php echo htmlspecialchars($viewUser['email']); ?>
                        </a>
                    </div>
                    <div>
                        <strong>Phone:</strong><br>
                        <?php echo htmlspecialchars($viewUser['phone'] ?? 'Not provided'); ?>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span class="status-badge status-<?php echo $viewUser['is_active'] ? 'active' : 'blocked'; ?>">
                            <?php echo $viewUser['is_active'] ? 'Active' : 'Blocked'; ?>
                        </span>
                    </div>
                    <div>
                        <strong>Joined:</strong><br>
                        <?php echo date('F d, Y', strtotime($viewUser['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Purchase History -->
            <div class="user-section">
                <h3 style="margin-bottom: var(--space-4);"><i class="fas fa-shopping-bag"></i> Recent Orders</h3>
                <?php if (empty($userOrders)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--space-4);">
                        No orders yet
                    </p>
                <?php else: ?>
                    <table style="width: 100%;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-2); text-align: left;">Order #</th>
                                <th style="padding: var(--space-2); text-align: left;">Date</th>
                                <th style="padding: var(--space-2); text-align: left;">Amount</th>
                                <th style="padding: var(--space-2); text-align: left;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userOrders as $order): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-2);">
                                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </td>
                                    <td style="padding: var(--space-2);">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td style="padding: var(--space-2);">
                                        <strong>₹<?php echo number_format($order['final_amount'], 2); ?></strong>
                                    </td>
                                    <td style="padding: var(--space-2);">
                                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <form method="POST" style="flex: 1;">
                    <input type="hidden" name="user_id" value="<?php echo $viewUser['id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $viewUser['is_active'] ? 0 : 1; ?>">
                    <input type="hidden" name="toggle_status" value="1">
                    <button type="submit" class="btn <?php echo $viewUser['is_active'] ? 'btn-outline' : 'btn-primary'; ?>" 
                            style="width: 100%; <?php echo $viewUser['is_active'] ? 'color: var(--error);' : ''; ?>">
                        <i class="fas fa-<?php echo $viewUser['is_active'] ? 'ban' : 'check'; ?>"></i>
                        <?php echo $viewUser['is_active'] ? 'Block User' : 'Activate User'; ?>
                    </button>
                </form>
                
                <button onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">
                    Close
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="<?php echo JS_URL; ?>main.js"></script>
    <script>
        function closeModal() {
            window.location.href = '<?php echo SITE_URL; ?>/admin/users.php';
        }
    </script>
</body>
</html>
