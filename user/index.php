<?php
require_once __DIR__ . '/../includes/header.php';

renderHeader('Home', 'Handmade Jewelry - Unique & Beautiful Designs');

// Fetch featured products
try {
    $stmt = $db->prepare("SELECT p.*, pi.image_url FROM products p 
                          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                          WHERE p.is_featured = 1 AND p.is_active = 1 
                          LIMIT 6");
    $stmt->execute();
    $featuredProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    $featuredProducts = [];
}
?>

<style>
    /* === HERO SLIDER === */
    .hero-slider {
        position: relative;
        height: 600px;
        width: 100%;
        overflow: hidden;
        background: #000;
    }

    .slider-container {
        height: 100%;
        width: 100%;
    }

    .slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1.5s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .slide.active {
        opacity: 1;
        z-index: 1;
    }

    .slide-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
        filter: brightness(0.6);
    }

    .slide.active .slide-image {
        animation: kenBurns 10s linear infinite alternate;
    }

    @keyframes kenBurns {
        from {
            transform: scale(1);
        }

        to {
            transform: scale(1.1);
        }
    }

    .slide-content {
        text-align: center;
        color: white;
        max-width: 800px;
        padding: 0 var(--space-4);
        transform: translateY(30px);
        transition: all 0.8s ease-out 0.5s;
        opacity: 0;
    }

    .slide.active .slide-content {
        transform: translateY(0);
        opacity: 1;
    }

    .hero-cta {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease-out 0.8s;
    }

    .slide.active .hero-cta {
        opacity: 1;
        transform: translateY(0);
    }

    .slide-title {
        font-size: var(--text-5xl);
        font-weight: 800;
        margin-bottom: var(--space-4);
        text-shadow: 0 2px 10px rgba(0, 0, 0, 1) !important;
        color: #ffffff !important;
    }

    .slide-subtitle {
        font-size: var(--text-xl);
        margin-bottom: var(--space-8);
        opacity: 0.9;
        text-shadow: 0 1px 5px rgba(0, 0, 0, 1) !important;
        color: #ffffff !important;
    }

    .slider-nav {
        position: absolute;
        bottom: var(--space-8);
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: var(--space-3);
        z-index: 10;
    }

    .slider-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .slider-dot.active {
        background: var(--gold-400);
        transform: scale(1.2);
        box-shadow: 0 0 10px var(--gold-400);
    }

    .slider-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.1);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: all var(--transition-fast);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
    }

    .slider-arrow:hover {
        background: var(--gold-500);
        border-color: var(--gold-500);
    }

    .arrow-prev {
        left: var(--space-6);
    }

    .arrow-next {
        right: var(--space-6);
    }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .hero-slider {
            height: 400px;
        }

        .slide-title {
            font-size: var(--text-3xl);
        }

        .slide-subtitle {
            font-size: var(--text-base);
        }

        .slider-arrow {
            width: 40px;
            height: 40px;
        }
    }

    .section {
        padding: var(--space-16) 0;
    }

    .section-title {
        text-align: center;
        font-size: var(--text-4xl);
        margin-bottom: var(--space-12);
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

    .product-image {
        width: 100%;
        height: 250px;
        object-fit: cover;
        background: var(--bg-secondary);
    }

    .product-info {
        padding: var(--space-5);
    }

    .product-title {
        font-size: var(--text-lg);
        font-weight: 600;
        margin-bottom: var(--space-2);
        color: var(--text-primary);
    }

    .product-price {
        font-size: var(--text-2xl);
        font-weight: 700;
        color: var(--accent-color);
        margin-bottom: var(--space-4);
    }

    .testimonial-card {
        background: var(--bg-secondary);
        padding: var(--space-6);
        border-radius: var(--radius-xl);
        border: 1px solid var(--border-color);
    }

    .testimonial-text {
        font-style: italic;
        margin-bottom: var(--space-4);
        color: var(--text-secondary);
    }

    .testimonial-author {
        font-weight: 600;
        color: var(--text-primary);
    }

    .faq-item {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        margin-bottom: var(--space-4);
        overflow: hidden;
    }

    .faq-question {
        padding: var(--space-5);
        font-weight: 600;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background var(--transition-fast);
    }

    .faq-question:hover {
        background: var(--bg-secondary);
    }

    .faq-answer {
        padding: 0 var(--space-5) var(--space-5);
        color: var(--text-secondary);
        display: none;
    }

    .faq-item.active .faq-answer {
        display: block;
    }
</style>

<!-- Hero Slider Section -->
<section class="hero-slider">
    <div class="slider-container">
        <!-- Slide 1 -->
        <div class="slide active">
            <img src="<?php echo htmlspecialchars(getSiteSetting('hero_slide_1_image', SITE_URL . '/assets/images/hero/hero-1.jpg')); ?>"
                class="slide-image" alt="Elegant Necklace">
            <div class="slide-content">
                <h1 class="slide-title">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_1_title', '✨ Timeless Elegance')); ?></h1>
                <p class="slide-subtitle">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_1_subtitle', 'Discover our handcrafted necklace collection, where every piece tells a story of passion and precision.')); ?>
                </p>
                <div class="hero-cta">
                    <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">Explore
                        Collection</a>
                    <a href="#featured" class="btn btn-outline btn-lg">Learn More</a>
                </div>
            </div>
        </div>
        <!-- Slide 2 -->
        <div class="slide">
            <img src="<?php echo htmlspecialchars(getSiteSetting('hero_slide_2_image', SITE_URL . '/assets/images/hero/hero-2.jpg')); ?>"
                class="slide-image" alt="Golden Rings">
            <div class="slide-content">
                <h1 class="slide-title">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_2_title', '💍 Pure Craftsmanship')); ?></h1>
                <p class="slide-subtitle">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_2_subtitle', 'Exquisite rings designed for those who appreciate the finer things in life. Skin-safe and hypoallergenic.')); ?>
                </p>
                <div class="hero-cta">
                    <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">Shop Rings</a>
                    <a href="#featured" class="btn btn-outline btn-lg">Our Story</a>
                </div>
            </div>
        </div>
        <!-- Slide 3 -->
        <div class="slide">
            <img src="<?php echo htmlspecialchars(getSiteSetting('hero_slide_3_image', SITE_URL . '/assets/images/hero/hero-3.jpg')); ?>"
                class="slide-image" alt="Beach Jewelry">
            <div class="slide-content">
                <h1 class="slide-title">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_3_title', '🌊 Summer Dreams')); ?></h1>
                <p class="slide-subtitle">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_3_subtitle', 'Our new coastal-inspired pieces are perfect for your next getaway. Lightweight, durable, and beautiful.')); ?>
                </p>
                <div class="hero-cta">
                    <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">See New
                        Arrivals</a>
                </div>
            </div>
        </div>
        <!-- Slide 4 -->
        <div class="slide">
            <img src="<?php echo htmlspecialchars(getSiteSetting('hero_slide_4_image', SITE_URL . '/assets/images/hero/hero-4.jpg')); ?>"
                class="slide-image" alt="Luxury Accessories">
            <div class="slide-content">
                <h1 class="slide-title">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_4_title', '✨ Luxury Defined')); ?></h1>
                <p class="slide-subtitle">
                    <?php echo htmlspecialchars(getSiteSetting('hero_slide_4_subtitle', 'Complete your look with our premium range of accessories. Designed to elevate every outfit.')); ?>
                </p>
                <div class="hero-cta">
                    <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-primary btn-lg">Browse All</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slider Controls -->
    <div class="slider-arrow arrow-prev"><i class="fas fa-chevron-left"></i></div>
    <div class="slider-arrow arrow-next"><i class="fas fa-chevron-right"></i></div>

    <div class="slider-nav">
        <div class="slider-dot active"></div>
        <div class="slider-dot"></div>
        <div class="slider-dot"></div>
        <div class="slider-dot"></div>
    </div>
</section>

<!-- Featured Products Section -->
<section id="featured" class="section">
    <div class="container">
        <h2 class="section-title">✨ Featured Collection</h2>

        <div class="grid grid-cols-3">
            <?php if (empty($featuredProducts)): ?>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="product-card">
                        <img src="https://via.placeholder.com/400x300/f59e0b/ffffff?text=Product+<?php echo $i; ?>"
                            alt="Sample Product <?php echo $i; ?>" class="product-image">
                        <div class="product-info">
                            <h3 class="product-title">Sample Jewelry <?php echo $i; ?></h3>
                            <div class="product-price">₹<?php echo number_format(1000 + ($i * 500), 2); ?></div>
                            <button class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php else: ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <?php $inStock = ($product['stock_quantity'] > 0) && ($product['is_in_stock'] ?? 1); ?>
                    <div class="product-card"
                        onclick="window.location.href='<?php echo SITE_URL; ?>/user/product-detail.php?id=<?php echo $product['id']; ?>'">
                        <div style="position: relative;">
                            <?php if (!$inStock): ?>
                                <div
                                    style="position: absolute; top: var(--space-3); right: var(--space-3); background: var(--error); color: white; padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-weight: 700; font-size: var(--text-xs); z-index: 10;">
                                    OUT OF STOCK
                                </div>
                            <?php endif; ?>
                            <img src="<?php echo $product['image_url'] ?: 'https://via.placeholder.com/400x300/f59e0b/ffffff?text=Product'; ?>"
                                alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                            <button class="btn btn-primary add-to-cart" style="width: 100%;" <?php echo !$inStock ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-title="<?php echo htmlspecialchars($product['title']); ?>"
                                data-product-price="<?php echo $product['price']; ?>"
                                data-product-image="<?php echo $product['image_url']; ?>">
                                <i class="fas fa-shopping-cart"></i> <?php echo $inStock ? 'Add to Cart' : 'Out of Stock'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo SITE_URL; ?>/user/products.php" class="btn btn-outline btn-lg">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <h2 class="section-title">💬 What Our Customers Say</h2>

        <div class="grid grid-cols-3">
            <div class="testimonial-card">
                <p class="testimonial-text">
                    "Absolutely stunning pieces! The craftsmanship is incredible and each piece feels so special.
                    I've received so many compliments!"
                </p>
                <div class="testimonial-author">⭐⭐⭐⭐⭐ - Priya Sharma</div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-text">
                    "The quality is outstanding and the designs are unique. I love supporting handmade jewelry
                    and Anshu Jewels never disappoints!"
                </p>
                <div class="testimonial-author">⭐⭐⭐⭐⭐ - Rahul Verma</div>
            </div>

            <div class="testimonial-card">
                <p class="testimonial-text">
                    "Fast shipping, beautiful packaging, and the jewelry exceeded my expectations.
                    Will definitely be ordering again!"
                </p>
                <div class="testimonial-author">⭐⭐⭐⭐⭐ - Sneha Patel</div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="section">
    <div class="container">
        <h2 class="section-title">❓ Frequently Asked Questions</h2>

        <div style="max-width: 800px; margin: 0 auto;">
            <div class="faq-item">
                <div class="faq-question">
                    <span>Are all pieces handmade?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes! Every piece of jewelry is carefully handcrafted with love and attention to detail.
                    Each item is unique and made to order.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What is your return policy?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We offer a 30-day return policy for all unworn items in their original packaging.
                    Custom orders are non-refundable.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How long does shipping take?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Standard shipping takes 5-7 business days. Express shipping (2-3 days) is available at checkout.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Do you offer custom designs?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes! We love creating custom pieces. Contact us with your ideas and we'll work with you
                    to create something special.
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // FAQ Accordion
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', function () {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');

            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });

            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });

    // Hero Slider Logic
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.arrow-prev');
    const nextBtn = document.querySelector('.arrow-next');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        currentSlide = (n + slides.length) % slides.length;

        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    function startAutoPlay() {
        stopAutoPlay();
        slideInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoPlay() {
        clearInterval(slideInterval);
    }

    // Event Listeners
    prevBtn.addEventListener('click', () => {
        prevSlide();
        startAutoPlay();
    });

    nextBtn.addEventListener('click', () => {
        nextSlide();
        startAutoPlay();
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            startAutoPlay();
        });
    });

    // Pause on hover
    const sliderContainer = document.querySelector('.hero-slider');
    sliderContainer.addEventListener('mouseenter', stopAutoPlay);
    sliderContainer.addEventListener('mouseleave', startAutoPlay);

    // Initial start
    startAutoPlay();
</script>

<?php
require_once __DIR__ . '/../includes/header.php';
renderFooter();
?>