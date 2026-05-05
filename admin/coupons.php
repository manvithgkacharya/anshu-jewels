<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle coupon deletion (POST)
if (isset($_POST['delete_coupon'])) {
    $couponId = (int)$_POST['coupon_id'];
    try {
        $db->prepare("DELETE FROM coupons WHERE id = ?")->execute([$couponId]);
        $success = 'Coupon deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to delete coupon: ' . $e->getMessage();
    }
}

// Handle coupon add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_coupon'])) {
    $couponId = $_POST['coupon_id'] ?? 0;
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $discountType = sanitize($_POST['discount_type'] ?? 'percentage');
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minPurchase = !empty($_POST['min_purchase_amount']) ? (float)$_POST['min_purchase_amount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $validFrom = $_POST['valid_from'] ?? date('Y-m-d');
    $validUntil = $_POST['valid_until'] ?? null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($code) || empty($discountValue)) {
        $error = 'Code and discount value are required';
    } else {
        try {
            if ($couponId > 0) {
                // Update existing coupon
                $stmt = $db->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, 
                                      min_purchase_amount = ?, usage_limit = ?, valid_from = ?, valid_until = ?, is_active = ? 
                                      WHERE id = ?");
                $stmt->execute([$code, $discountType, $discountValue, $minPurchase, $usageLimit, 
                               $validFrom, $validUntil, $isActive, $couponId]);
                $success = 'Coupon updated successfully!';
            } else {
                // Insert new coupon
                $stmt = $db->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_purchase_amount, 
                                      usage_limit, valid_from, valid_until, is_active) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discountType, $discountValue, $minPurchase, $usageLimit, 
                               $validFrom, $validUntil, $isActive]);
                $success = 'Coupon created successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Failed to save coupon: ' . $e->getMessage();
        }
    }
}

// Fetch all coupons
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';

try {
    $query = "SELECT c.*, COUNT(o.id) as usage_count 
              FROM coupons c 
              LEFT JOIN orders o ON c.id = o.coupon_id 
              WHERE 1=1";
              
    $params = [];
    
    if (!empty($searchTerm)) {
        $query .= " AND c.code LIKE ?";
        $params[] = "%$searchTerm%";
    }
    
    if ($statusFilter === 'active') {
        $query .= " AND c.is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $query .= " AND c.is_active = 0";
    }
    
    $query .= " GROUP BY c.id ORDER BY c.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    $coupons = [];
    $error = "DB Error: " . $e->getMessage();
}

// Fetch coupon for editing
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$editId]);
        $editCoupon = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Failed to load coupon';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Anshu Jewels Admin</title>
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
        .coupons-grid { display: grid; gap: var(--space-4); }
        .coupon-card { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-6); display: grid; grid-template-columns: 1fr auto; gap: var(--space-6); align-items: center; }
        .coupon-code { font-size: var(--text-2xl); font-weight: 800; font-family: monospace; color: var(--accent-color); margin-bottom: var(--space-2); }
        .coupon-details { display: flex; gap: var(--space-4); flex-wrap: wrap; color: var(--text-secondary); font-size: var(--text-sm); }
        .coupon-actions { display: flex; gap: var(--space-2); }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: var(--bg-primary); border-radius: var(--radius-xl); padding: var(--space-8); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6); }
        .close-modal { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="admin-menu-link active"><i class="fas fa-ticket-alt"></i> Coupons</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/settings.php" class="admin-menu-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="admin-menu-item" style="margin-top: var(--space-8);"><a href="<?php echo SITE_URL; ?>/user/index.php" class="admin-menu-link"><i class="fas fa-globe"></i> View Website</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/api/logout.php?admin=1" class="admin-menu-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <div>
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Coupon Management</h1>
                    <p style="color: var(--text-secondary);">Create and manage discount coupons</p>
                </div>
                <div style="display: flex; gap: var(--space-3);">
                    <button id="theme-toggle-desktop" class="btn btn-secondary">🌙</button>
                    <button onclick="openModal()" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Create Coupon
                    </button>
                </div>
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
                    <input type="text" name="search" class="form-input" placeholder="Search by code..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" style="flex: 1; min-width: 200px;">
                    
                    <select name="status" class="form-select" style="min-width: 150px;">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <?php if (!empty($searchTerm) || $statusFilter !== 'all'): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/coupons.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Coupons List -->
            <div class="coupons-grid">
                <?php if (empty($coupons)): ?>
                    <div style="text-align: center; padding: var(--space-16); background: var(--bg-primary); border-radius: var(--radius-xl);">
                        <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--text-tertiary); margin-bottom: var(--space-4);"></i>
                        <h3>No coupons yet</h3>
                        <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">Create your first discount coupon</p>
                        <button onclick="openModal()" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Coupon
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($coupons as $coupon): ?>
                        <div class="coupon-card">
                            <div>
                                <div class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                <div class="coupon-details">
                                    <span>
                                        <i class="fas fa-tag"></i>
                                        <?php echo $coupon['discount_type'] === 'percentage' 
                                            ? $coupon['discount_value'] . '% OFF' 
                                            : '₹' . number_format($coupon['discount_value'], 0) . ' OFF'; ?>
                                    </span>
                                    <?php if ($coupon['min_purchase_amount']): ?>
                                        <span><i class="fas fa-shopping-cart"></i> Min: ₹<?php echo number_format($coupon['min_purchase_amount'], 0); ?></span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-calendar"></i> Valid until: <?php echo $coupon['valid_until'] ? date('M d, Y', strtotime($coupon['valid_until'])) : 'No expiry'; ?></span>
                                    <span><i class="fas fa-chart-line"></i> Used: <?php echo $coupon['usage_count']; ?><?php echo $coupon['usage_limit'] ? '/' . $coupon['usage_limit'] : ''; ?></span>
                                    <span class="badge <?php echo $coupon['is_active'] ? 'badge-success' : 'badge-error'; ?>">
                                        <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="coupon-actions">
                                <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $coupon['id']; ?>, '<?php echo $coupon['code']; ?>')" class="btn btn-sm btn-outline" style="color: var(--error); border-color: var(--error);" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Coupon Modal -->
    <div id="couponModal" class="modal <?php echo $editCoupon ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: var(--text-2xl); font-weight: 700;">
                    <?php echo $editCoupon ? 'Edit Coupon' : 'Create New Coupon'; ?>
                </h2>
                <button onclick="closeModal()" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id'] ?? 0; ?>">
                
                <div class="form-group">
                    <label class="form-label">Coupon Code *</label>
                    <input type="text" name="code" class="form-input" required style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($editCoupon['code'] ?? ''); ?>" 
                           placeholder="SUMMER2024">
                    <small style="color: var(--text-tertiary);">Use uppercase letters and numbers only</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Discount Type *</label>
                        <select name="discount_type" class="form-select" required>
                            <option value="percentage" <?php echo ($editCoupon['discount_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="flat" <?php echo ($editCoupon['discount_type'] ?? '') === 'flat' ? 'selected' : ''; ?>>Flat Amount (₹)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Discount Value *</label>
                        <input type="number" name="discount_value" class="form-input" step="0.01" required
                               value="<?php echo $editCoupon['discount_value'] ?? ''; ?>" placeholder="10">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Min Purchase Amount</label>
                        <input type="number" name="min_purchase_amount" class="form-input" step="0.01"
                               value="<?php echo $editCoupon['min_purchase_amount'] ?? ''; ?>" placeholder="1000">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-input"
                               value="<?php echo $editCoupon['usage_limit'] ?? ''; ?>" placeholder="100">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Valid From</label>
                        <input type="date" name="valid_from" class="form-input"
                               value="<?php echo $editCoupon['valid_from'] ?? date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="date" name="valid_until" class="form-input"
                               value="<?php echo $editCoupon['valid_until'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="is_active" <?php echo ($editCoupon['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <span>Active (coupon can be used)</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> <?php echo $editCoupon ? 'Update Coupon' : 'Create Coupon'; ?>
                    </button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                    Are you sure you want to delete coupon <strong id="deleteCouponCode"></strong>?
                </p>
                <p style="color: var(--error); margin-bottom: var(--space-6); font-weight: 600;">
                    This action is permanent and cannot be undone.
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="coupon_id" id="deleteCouponId">
                    <input type="hidden" name="delete_coupon" value="1">
                    
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
        function openModal() {
            document.getElementById('couponModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('couponModal').classList.remove('active');
            window.location.href = '<?php echo SITE_URL; ?>/admin/coupons.php';
        }

        function confirmDelete(id, code) {
            document.getElementById('deleteCouponId').value = id;
            document.getElementById('deleteCouponCode').textContent = code;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close request if clicking outside
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            const couponModal = document.getElementById('couponModal');
            if (event.target === couponModal) {
                // Optional: only close if not editing? 
                // For now, let's keep it manual close to avoid accidental loss of form data
                // closeDeleteModal(); 
            }
        }
    </script>
</body>
</html>
