<?php
require_once __DIR__ . '/../config/config.php';

$productId = $_GET['id'] ?? 0;

// Fetch product details
try {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = ? AND p.is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: ' . SITE_URL . '/user/products.php');
        exit();
    }
    
    // Update view count
    $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$productId]);
    
    // Get product images
    $imagesStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
    $imagesStmt->execute([$productId]);
    $images = $imagesStmt->fetchAll();
    
    // Get related products
    $relatedStmt = $db->prepare("SELECT p.*, pi.image_url FROM products p 
                                 LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                                 WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
                                 LIMIT 4");
    $relatedStmt->execute([$product['category_id'], $productId]);
    $relatedProducts = $relatedStmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: ' . SITE_URL . '/user/products.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';
renderHeader($product['title'], htmlspecialchars($product['description']));
?>

<style>
.product-detail-section {
    padding: var(--space-12) 0;
}

.product-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-12);
}

.product-gallery {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.main-image-container {
    width: 100%;
    height: 500px;
    border-radius: var(--radius-xl);
    overflow: hidden;
    background: var(--bg-secondary);
    margin-bottom: var(--space-4);
    border: 1px solid var(--border-color);
}

.main-product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnails {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-3);
}

.product-thumbnail {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: var(--radius-md);
    cursor: pointer;
    border: 2px solid var(--border-color);
    transition: all var(--transition-fast);
}

.product-thumbnail:hover,
.product-thumbnail.active {
    border-color: var(--accent-color);
    transform: scale(1.05);
}

.product-details {
    padding: var(--space-6);
}

.product-breadcrumb {
    font-size: var(--text-sm);
    color: var(--text-tertiary);
    margin-bottom: var(--space-4);
}

.product-detail-title {
    font-size: var(--text-4xl);
    font-weight: 800;
    margin-bottom: var(--space-4);
}

.product-detail-price {
    font-size: var(--text-4xl);
    font-weight: 800;
    color: var(--accent-color);
    margin-bottom: var(--space-6);
}

.product-meta {
    display: flex;
    gap: var(--space-6);
    margin-bottom: var(--space-6);
    padding-bottom: var(--space-6);
    border-bottom: 1px solid var(--border-color);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--text-secondary);
}

.product-description {
    margin-bottom: var(--space-8);
    line-height: 1.8;
    color: var(--text-secondary);
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-6);
}

.quantity-label {
    font-weight: 600;
}

.quantity-input {
    width: 80px;
    padding: var(--space-3);
    text-align: center;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: var(--text-lg);
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: var(--space-4);
    margin-bottom: var(--space-8);
}

.related-section {
    margin-top: var(--space-16);
    padding-top: var(--space-16);
    border-top: 1px solid var(--border-color);
}

@media (max-width: 768px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: var(--space-6);
    }
    
    .product-gallery {
        position: relative;
        top: 0;
    }
    
    .main-image-container {
        height: 350px;
    }
}
</style>

<section class="product-detail-section">
    <div class="container">
        <div class="product-layout">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <div class="main-image-container">
                    <img id="main-product-image" 
                         src="<?php echo !empty($images) ? $images[0]['image_url'] : 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Product'; ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>" 
                         class="main-product-image">
                </div>
                
                <?php if (count($images) > 1): ?>
                    <div class="thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="<?php echo $image['image_url']; ?>" 
                                 alt="Product thumbnail" 
                                 class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Details -->
            <div class="product-details">
                <div class="product-breadcrumb">
                    <a href="<?php echo SITE_URL; ?>/user/index.php">Home</a> / 
                    <a href="<?php echo SITE_URL; ?>/user/products.php">Products</a> / 
                    <a href="<?php echo SITE_URL; ?>/user/products.php?category=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </div>
                
                <h1 class="product-detail-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                
                <div class="product-detail-price">
                    <?php echo formatCurrency($product['price']); ?>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span><?php echo htmlspecialchars($product['category_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?php echo number_format($product['views']); ?> views</span>
                    </div>
                    <?php 
                    $isInStock = ($product['stock_quantity'] > 0) && ($product['is_in_stock'] ?? 1);
                    ?>
                    <?php if ($isInStock): ?>
                        <div class="meta-item" style="color: var(--success);">
                            <i class="fas fa-check-circle"></i>
                            <span>In Stock</span>
                        </div>
                    <?php else: ?>
                        <div class="meta-item" style="color: var(--error);">
                            <i class="fas fa-times-circle"></i>
                            <span>Out of Stock</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-description">
                    <h3 style="margin-bottom: var(--space-3);">Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <?php if ($isInStock): ?>
                <div class="quantity-selector">
                    <span class="quantity-label">Quantity:</span>
                    <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="btn btn-primary btn-lg buy-now" style="flex: 1;"
                            <?php echo !$isInStock ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-title="<?php echo htmlspecialchars($product['title']); ?>"
                            data-product-price="<?php echo $product['price']; ?>"
                            data-product-image="<?php echo !empty($images) ? $images[0]['image_url'] : ''; ?>">
                        <i class="fas fa-bolt"></i> <?php echo $isInStock ? 'Buy Now' : 'Out of Stock'; ?>
                    </button>
                    <button class="btn btn-outline btn-lg add-to-cart" style="flex: 1;"
                            <?php echo !$isInStock ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-title="<?php echo htmlspecialchars($product['title']); ?>"
                            data-product-price="<?php echo $product['price']; ?>"
                            data-product-image="<?php echo !empty($images) ? $images[0]['image_url'] : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
                
                <div class="card" style="background: var(--bg-secondary); margin-top: var(--space-6);">
                    <h4 style="margin-bottom: var(--space-3);"><i class="fas fa-truck"></i> Shipping Information</h4>
                    <p style="color: var(--text-secondary); margin-bottom: var(--space-2);">
                        <i class="fas fa-check"></i> Free shipping on orders over ₹2000
                    </p>
                    <p style="color: var(--text-secondary); margin-bottom: var(--space-2);">
                        <i class="fas fa-check"></i> Delivery in 5-7 business days
                    </p>
                    <p style="color: var(--text-secondary);">
                        <i class="fas fa-check"></i> 30-day return policy
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="related-section">
                <h2 class="section-title">You May Also Like</h2>
                <div class="grid grid-cols-4">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="product-card" onclick="window.location.href='<?php echo SITE_URL; ?>/user/product-detail.php?id=<?php echo $related['id']; ?>'" style="cursor: pointer;">
                            <div class="product-image-container">
                                <img src="<?php echo $related['image_url'] ?: 'https://via.placeholder.com/400x400/f59e0b/ffffff?text=Product'; ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                     class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                                <div class="product-price"><?php echo formatCurrency($related['price']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
renderFooter();
?>
