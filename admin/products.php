<?php
require_once __DIR__ . '/../config/config.php';

// Check admin authentication
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
}

$error = '';
$success = '';

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];
    try {
        // Delete related images first
        $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
        // Delete product
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
        $success = 'Product deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to delete product: ' . $e->getMessage();
    }
}

// Handle stock toggle
if (isset($_POST['toggle_stock'])) {
    $productId = (int)$_POST['product_id'];
    $newStatus = (int)$_POST['new_stock_status'];
    try {
        $db->prepare("UPDATE products SET is_in_stock = ? WHERE id = ?")->execute([$newStatus, $productId]);
        $success = 'Stock status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to update stock status';
    }
}

// Handle product add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['toggle_stock'])) {
    $productId = $_POST['product_id'] ?? 0;
    $title = sanitize($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $sku = sanitize($_POST['sku'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isInStock = isset($_POST['is_in_stock']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (empty($title) || empty($price)) {
        $error = 'Title and price are required';
    } else {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            if ($productId > 0) {
                // Update existing product
                $stmt = $db->prepare("UPDATE products SET category_id = ?, title = ?, slug = ?, description = ?, 
                                      price = ?, original_price = ?, stock_quantity = ?, sku = ?, is_active = ?, is_in_stock = ?, is_featured = ? 
                                      WHERE id = ?");
                $stmt->execute([$categoryId, $title, $slug, $description, $price, $originalPrice, 
                               $stockQuantity, $sku, $isActive, $isInStock, $isFeatured, $productId]);
                $success = 'Product updated successfully!';
            } else {
                // Insert new product
                $stmt = $db->prepare("INSERT INTO products (category_id, title, slug, description, price, original_price, 
                                      stock_quantity, sku, is_active, is_in_stock, is_featured) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$categoryId, $title, $slug, $description, $price, $originalPrice, 
                               $stockQuantity, $sku, $isActive, $isInStock, $isFeatured]);
                $productId = $db->lastInsertId();
                $success = 'Product added successfully!';
            }
            
            // Handle image upload
            if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                $uploadDir = PRODUCT_IMAGE_PATH;
                
                foreach ($_FILES['product_images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = time() . '_' . $key . '_' . basename($_FILES['product_images']['name'][$key]);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imageUrl = UPLOAD_URL . 'products/' . $fileName;
                            $isPrimary = $key === 0 ? 1 : 0;
                            
                            $db->prepare("INSERT INTO product_images (product_id, image_url, is_primary, sort_order) 
                                         VALUES (?, ?, ?, ?)")
                               ->execute([$productId, $imageUrl, $isPrimary, $key]);
                        }
                    }
                }
            }
            
        } catch (PDOException $e) {
            $error = 'Failed to save product: ' . $e->getMessage();
        }
    }
}


// Fetch all products
$searchTerm = $_GET['search'] ?? '';
$categoryFilter = (int)($_GET['category'] ?? 0);

try {
    $query = "SELECT p.*, c.name as category_name, 
              (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE 1=1";
              
    $params = [];
    
    if (!empty($searchTerm)) {
        $query .= " AND (p.title LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = [$searchParam, $searchParam, $searchParam];
    }
    
    if ($categoryFilter > 0) {
        $query .= " AND p.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Fetch categories for dropdown
    $categoriesStmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}

// Fetch product for editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$editId]);
        $editProduct = $stmt->fetch();
        
        // Fetch product images
        $imagesStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
        $imagesStmt->execute([$editId]);
        $productImages = $imagesStmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Failed to load product';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Anshu Jewels Admin</title>
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
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: var(--space-4); margin-top: var(--space-6); }
        .product-card-admin { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; transition: all var(--transition-base); }
        .product-card-admin:hover { box-shadow: var(--shadow-lg); }
        .product-image-admin { width: 100%; height: 200px; object-fit: cover; background: var(--bg-secondary); }
        .product-info-admin { padding: var(--space-4); }
        .product-actions { display: flex; gap: var(--space-2); margin-top: var(--space-3); }
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
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/products.php" class="admin-menu-link active"><i class="fas fa-gem"></i> Products</a></li>
                <li class="admin-menu-item"><a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-menu-link"><i class="fas fa-shopping-bag"></i> Orders</a></li>
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
                    <h1 style="font-size: var(--text-4xl); font-weight: 800; margin-bottom: var(--space-2);">Product Management</h1>
                    <p style="color: var(--text-secondary);">Manage your jewelry products</p>
                </div>
                <div style="display: flex; gap: var(--space-3);">
                    <button id="theme-toggle-desktop" class="btn btn-secondary">🌙</button>
                    <button onclick="openModal()" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Add New Product
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
                    <input type="text" name="search" class="form-input" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" style="flex: 1; min-width: 200px;">
                    
                    <select name="category" class="form-select" style="min-width: 150px;">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <?php if (!empty($searchTerm) || $categoryFilter > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/products.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card-admin">
                        <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/250x200/f59e0b/ffffff?text=No+Image'; ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image-admin">
                        <div class="product-info-admin">
                            <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-2);">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </h3>
                            <p style="color: var(--text-secondary); font-size: var(--text-sm); margin-bottom: var(--space-2);">
                                <?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?>
                            </p>
                            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--accent-color); margin-bottom: var(--space-2);">
                                ₹<?php echo number_format($product['price'], 2); ?>
                            </div>
                            <div style="display: flex; gap: var(--space-2); margin-bottom: var(--space-3); flex-wrap: wrap;">
                                <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-error'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <?php if ($product['is_featured']): ?>
                                    <span class="badge badge-primary">Featured</span>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="new_stock_status" value="<?php echo $product['is_in_stock'] ? 0 : 1; ?>">
                                    <input type="hidden" name="toggle_stock" value="1">
                                    <button type="submit" class="badge <?php echo $product['is_in_stock'] ? 'badge-success' : 'badge-error'; ?>" 
                                            style="border: none; cursor: pointer; opacity: 0.9;">
                                        <?php echo $product['is_in_stock'] ? 'In Stock' : 'Out of Stock'; ?>
                                    </button>
                                </form>
                            </div>
                            <div class="product-actions">
                                <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary" style="flex: 1;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['title'])); ?>')" 
                                        class="btn btn-sm btn-outline" style="color: var(--error); border-color: var(--error);" title="Delete Product">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal <?php echo $editProduct ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="font-size: var(--text-2xl); font-weight: 700;">
                    <?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?>
                </h2>
                <button onclick="closeModal()" class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $editProduct['id'] ?? 0; ?>">
                
                <div class="form-group">
                    <label class="form-label">Product Title *</label>
                    <input type="text" name="title" class="form-input" required 
                           value="<?php echo htmlspecialchars($editProduct['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="0">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($editProduct['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="4"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Price *</label>
                        <input type="number" name="price" class="form-input" step="0.01" required 
                               value="<?php echo $editProduct['price'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Original Price</label>
                        <input type="number" name="original_price" class="form-input" step="0.01" 
                               value="<?php echo $editProduct['original_price'] ?? ''; ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-input" 
                               value="<?php echo $editProduct['stock_quantity'] ?? 0; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-input" 
                               value="<?php echo htmlspecialchars($editProduct['sku'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Images</label>
                    <input type="file" name="product_images[]" class="form-input" multiple accept="image/*">
                    <small style="color: var(--text-tertiary);">You can select multiple images. First image will be primary.</small>
                </div>
                
                <div style="display: flex; gap: var(--space-6); margin-bottom: var(--space-6); flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="is_active" <?php echo ($editProduct['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="is_in_stock" <?php echo ($editProduct['is_in_stock'] ?? 1) ? 'checked' : ''; ?>>
                        <span>In Stock</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer;">
                        <input type="checkbox" name="is_featured" <?php echo ($editProduct['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Featured</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: var(--space-3);">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
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
                    Are you sure you want to delete product <strong id="deleteProductTitle"></strong>?
                </p>
                <p style="color: var(--error); margin-bottom: var(--space-6); font-weight: 600;">
                    This action is permanent and cannot be undone.
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <input type="hidden" name="delete_product" value="1">
                    
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
            document.getElementById('productModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
            window.location.href = '<?php echo SITE_URL; ?>/admin/products.php';
        }

        function confirmDelete(id, title) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteProductTitle').textContent = title;
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
            const productModal = document.getElementById('productModal');
            if (event.target === productModal) {
                // closeDeleteModal(); // optional
            }
        }
    </script>
</body>
</html>
