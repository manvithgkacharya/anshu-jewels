<?php
require_once __DIR__ . '/../config/config.php';

// Get filters
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'default';

// Build query
$query = "SELECT p.*, pi.image_url, c.name as category_name 
          FROM products p 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";

$params = [];

if ($category !== 'all') {
    $query .= " AND c.slug = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sorting
switch ($sort) {
    case 'price-low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price-high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.views DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get categories
    $categoriesStmt = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}

require_once __DIR__ . '/../includes/header.php';
renderHeader('Products', 'Browse our handmade jewelry collection');
?>

<style>
.products-header {
    background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
    padding: var(--space-12) 0 var(--space-8);
    text-align: center;
    border-bottom: 1px solid var(--border-color);
}

.products-title {
    font-size: var(--text-4xl);
    font-weight: 800;
    margin-bottom: var(--space-4);
}

.filters-section {
    padding: var(--space-6) 0;
    background: var(--bg-primary);
    position: sticky;
    top: 60px;
    z-index: var(--z-sticky);
    border-bottom: 1px solid var(--border-color);
}

.filters-container {
    display: flex;
    gap: var(--space-4);
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: var(--space-3) var(--space-4) var(--space-3) var(--space-12);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-full);
    background: var(--bg-secondary);
    color: var(--text-primary);
    transition: all var(--transition-base);
}

.search-input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.search-icon {
    position: absolute;
    left: var(--space-4);
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-tertiary);
}

.sort-select {
    padding: var(--space-3) var(--space-6);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-lg);
    background: var(--bg-secondary);
    color: var(--text-primary);
    cursor: pointer;
}

.products-section {
    padding: var(--space-8) 0;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-6);
}

.product-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all var(--transition-base);
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 280px;
    overflow: hidden;
    background: var(--bg-secondary);
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-badge {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    background: var(--gold-500);
    color: white;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 700;
}

.product-info {
    padding: var(--space-5);
}

.product-category {
    font-size: var(--text-xs);
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--space-2);
}

.product-title {
    font-size: var(--text-lg);
    font-weight: 600;
    margin-bottom: var(--space-3);
    color: var(--text-primary);
}

.product-price {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--accent-color);
    margin-bottom: var(--space-4);
}

.no-products {
    text-align: center;
    padding: var(--space-16) 0;
    color: var(--text-secondary);
}
</style>

<!-- Products Header -->
<div class="products-header">
    <div class="container">
        <h1 class="products-title">✨ Our Collection</h1>
        <p style="color: var(--text-secondary); font-size: var(--text-lg);">
            Discover handcrafted jewelry pieces made with love
        </p>
    </div>
</div>

<!-- Modern Category Slider Styles -->
<style>
.category-section {
    background: var(--bg-primary);
    padding: var(--space-6) 0;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 60px; /* Adjust based on navbar height */
    z-index: var(--z-sticky);
}

.category-list {
    display: flex;
    gap: var(--space-6);
    overflow-x: auto;
    padding: var(--space-2) var(--space-4);
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

.category-list::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    min-width: 80px;
    cursor: pointer;
    text-decoration: none;
    transition: transform var(--transition-fast);
}

.category-item:hover {
    transform: translateY(-2px);
}

.category-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: all var(--transition-base);
    position: relative;
    box-shadow: var(--shadow-sm);
}

.category-item.active .category-circle {
    border-color: var(--gold-500);
    box-shadow: 0 0 0 3px var(--gold-200);
    transform: scale(1.05);
}

[data-theme="dark"] .category-item.active .category-circle {
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
}

.category-icon {
    font-size: 1.5rem;
    color: var(--text-secondary);
    transition: color var(--transition-base);
}

.category-item.active .category-icon {
    color: var(--gold-600);
}

.category-initial {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-tertiary);
    font-family: var(--font-display);
}

.category-item.active .category-initial {
    color: var(--gold-600);
}

.category-name {
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--text-secondary);
    text-align: center;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-item.active .category-name {
    color: var(--gold-600);
    font-weight: 700;
}

/* Category Images */
.category-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

<!-- Category Slider -->
<div class="category-section">
    <div class="container">
        <div class="category-list">
            <!-- All Products -->
            <a href="?category=all" class="category-item <?php echo $category === 'all' ? 'active' : ''; ?>">
                <div class="category-circle">
                    <i class="fas fa-th-large category-icon"></i>
                </div>
                <span class="category-name">All</span>
            </a>

            <!-- Dynamic Categories -->
            <?php 
            // Icons map for demo purposes (usually this would come from DB)
            $categoryIcons = [
                'rings' => 'fa-ring',
                'necklaces' => 'fa-gem',
                'earrings' => 'fa-star',
                'bracelets' => 'fa-circle-notch',
                'wedding' => 'fa-heart',
                'gifts' => 'fa-gift'
            ];
            ?>
            
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat['slug']; ?>" class="category-item <?php echo $category === $cat['slug'] ? 'active' : ''; ?>">
                    <div class="category-circle">
                        <?php if (!empty($cat['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($cat['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($cat['name']); ?>" 
                                 class="category-img">
                        <?php else: ?>
                            <?php 
                                $slug = strtolower($cat['slug']);
                                $iconClass = 'fa-gem'; // Default
                                foreach ($categoryIcons as $key => $val) {
                                    if (strpos($slug, $key) !== false) {
                                        $iconClass = $val;
                                        break;
                                    }
                                }
                            ?>
                            <i class="fas <?php echo $iconClass; ?> category-icon"></i>
                        <?php endif; ?>
                    </div>
                    <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="filters-section">
    <div class="container">
        <form method="GET" class="filters-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" id="product-search" class="search-input" 
                       placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <select name="sort" id="product-sort" class="sort-select" onchange="this.form.submit()">
                <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Latest</option>
                <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            </select>
            
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        </form>
    </div>
</div>

<!-- Products Grid -->
<section class="products-section">
    <div class="container">
        <?php if (empty($products)): ?>
            <div class="no-products">
                <i class="fas fa-gem" style="font-size: 4rem; margin-bottom: var(--space-4); opacity: 0.3;"></i>
                <h3>No products found</h3>
                <p>Try adjusting your search or filters</p>
                <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary mt-6">
                    View All Products
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid" id="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php $inStock = ($product['stock_quantity'] > 0) && ($product['is_in_stock'] ?? 1); ?>
                    <div class="product-card" 
                         data-title="<?php echo htmlspecialchars($product['title']); ?>"
                         data-category="<?php echo htmlspecialchars($product['category_name']); ?>"
                         data-price="<?php echo $product['price']; ?>"
                         data-views="<?php echo $product['views']; ?>"
                         onclick="window.location.href='<?php echo SITE_URL; ?>/user/product-detail.php?id=<?php echo $product['id']; ?>'">
                        
                        <div class="product-image-container" style="position: relative;">
                            <?php if (!$inStock): ?>
                                <div style="position: absolute; top: var(--space-3); right: var(--space-3); background: var(--error); color: white; padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-weight: 700; font-size: var(--text-xs); z-index: 10;">
                                    OUT OF STOCK
                                </div>
                            <?php endif; ?>
                            <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/300x300/f59e0b/ffffff?text=Product'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                 class="product-image">
                            <?php if ($product['is_featured']): ?>
                                <span class="product-badge">Featured</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?>
                            </div>
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                            <button class="btn btn-primary add-to-cart" style="width: 100%;"
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-title="<?php echo htmlspecialchars($product['title']); ?>"
                                    data-product-price="<?php echo $product['price']; ?>"
                                    data-product-image="<?php echo $product['image_url']; ?>"
                                    onclick="event.stopPropagation();">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-8">
                <p style="color: var(--text-secondary);">
                    Showing <?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
renderFooter();
?>
