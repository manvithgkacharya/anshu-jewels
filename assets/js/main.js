// ===================================
// ANSHU JEWELS - MAIN JAVASCRIPT
// ===================================

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function () {
  initTheme();
  initMobileMenu();
  initCart();
  initProductFilters();
  initFormValidation();
  initHeroSlider();
  initAdminMobile();
});

// === THEME MANAGEMENT ===
// === THEME MANAGEMENT ===
function initTheme() {
  const themeToggles = [
    document.getElementById('theme-toggle-desktop'),
    document.getElementById('theme-toggle-mobile')
  ];

  const savedTheme = localStorage.getItem('theme') || 'light';

  // Set initial theme
  document.documentElement.setAttribute('data-theme', savedTheme);

  // Theme toggle handler
  themeToggles.forEach(toggle => {
    if (toggle) {
      toggle.addEventListener('click', function () {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        // Update all toggle icons
        updateThemeIcon(newTheme);
      });
    }
  });

  updateThemeIcon(savedTheme);
}

function updateThemeIcon(theme) {
  const themeToggles = [
    document.getElementById('theme-toggle-desktop'),
    document.getElementById('theme-toggle-mobile')
  ];

  themeToggles.forEach(toggle => {
    if (toggle) {
      toggle.innerHTML = theme === 'light' ? '🌙' : '☀️';
    }
  });
}

// === MOBILE MENU ===
function initMobileMenu() {
  const menuBtn = document.getElementById('mobile-menu-btn');
  const drawer = document.getElementById('mobile-drawer');
  const overlay = document.getElementById('mobile-drawer-overlay');
  const closeBtn = document.getElementById('mobile-drawer-close');

  if (menuBtn && drawer && overlay) {
    menuBtn.addEventListener('click', () => {
      drawer.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    });

    const closeDrawer = () => {
      drawer.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
  }
}

// === CART MANAGEMENT ===
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function initCart() {
  updateCartBadge();

  // Add to cart buttons
  document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;
      const productTitle = this.dataset.productTitle;
      const productPrice = parseFloat(this.dataset.productPrice);
      const productImage = this.dataset.productImage;

      addToCart(productId, productTitle, productPrice, productImage);
    });
  });

  // Buy Now buttons
  document.querySelectorAll('.buy-now').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.dataset.productId;
      const productTitle = this.dataset.productTitle;
      const productPrice = parseFloat(this.dataset.productPrice);
      const productImage = this.dataset.productImage;

      addToCart(productId, productTitle, productPrice, productImage);
      window.location.href = 'checkout.php';
    });
  });
}

function addToCart(id, title, price, image) {
  const existingItem = cart.find(item => item.id === id);

  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.push({
      id: id,
      title: title,
      price: price,
      image: image,
      quantity: 1
    });
  }

  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartBadge();
  showNotification('Product added to cart!', 'success');
}

function removeFromCart(id) {
  cart = cart.filter(item => item.id !== id);
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartBadge();
  updateCartDisplay();
}

function updateCartQuantity(id, quantity) {
  const item = cart.find(item => item.id === id);
  if (item) {
    item.quantity = parseInt(quantity);
    if (item.quantity <= 0) {
      removeFromCart(id);
    } else {
      localStorage.setItem('cart', JSON.stringify(cart));
      updateCartDisplay();
    }
  }
}

function updateCartBadge() {
  const badge = document.getElementById('cart-badge');
  const mobileBadge = document.getElementById('mobile-cart-badge');
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

  // Update desktop badge
  if (badge) {
    badge.textContent = totalItems;
    badge.style.display = totalItems > 0 ? 'block' : 'none';
  }

  // Update mobile badge
  if (mobileBadge) {
    mobileBadge.textContent = totalItems;
    mobileBadge.style.display = totalItems > 0 ? 'block' : 'none';
  }
}

function updateCartDisplay() {
  const cartContainer = document.getElementById('cart-items');
  const cartTotal = document.getElementById('cart-total');

  if (!cartContainer) return;

  if (cart.length === 0) {
    cartContainer.innerHTML = '<p class="text-center">Your cart is empty</p>';
    if (cartTotal) cartTotal.textContent = '₹0.00';
    return;
  }

  let html = '';
  let total = 0;

  cart.forEach(item => {
    const subtotal = item.price * item.quantity;
    total += subtotal;

    html += `
      <div class="cart-item" data-id="${item.id}">
        <img src="${item.image}" alt="${item.title}" class="cart-item-image">
        <div class="cart-item-info">
          <h4>${item.title}</h4>
          <p class="cart-item-price">₹${item.price.toFixed(2)}</p>
        </div>
        <div class="cart-item-controls">
          <input type="number" value="${item.quantity}" min="1" 
                 class="cart-quantity" data-id="${item.id}">
          <button class="btn btn-sm btn-secondary remove-from-cart" data-id="${item.id}">
            Remove
          </button>
        </div>
      </div>
    `;
  });

  cartContainer.innerHTML = html;
  if (cartTotal) cartTotal.textContent = `₹${total.toFixed(2)}`;

  // Attach event listeners
  document.querySelectorAll('.cart-quantity').forEach(input => {
    input.addEventListener('change', function () {
      updateCartQuantity(this.dataset.id, this.value);
    });
  });

  document.querySelectorAll('.remove-from-cart').forEach(btn => {
    btn.addEventListener('click', function () {
      removeFromCart(this.dataset.id);
    });
  });
}

// === PRODUCT FILTERS ===
function initProductFilters() {
  const searchInput = document.getElementById('product-search');
  const categoryFilters = document.querySelectorAll('.category-filter');
  const sortSelect = document.getElementById('product-sort');

  if (searchInput) {
    searchInput.addEventListener('input', debounce(filterProducts, 300));
  }

  categoryFilters.forEach(filter => {
    filter.addEventListener('click', function () {
      categoryFilters.forEach(f => f.classList.remove('active'));
      this.classList.add('active');
      filterProducts();
    });
  });

  if (sortSelect) {
    sortSelect.addEventListener('change', filterProducts);
  }
}

function filterProducts() {
  const searchTerm = document.getElementById('product-search')?.value.toLowerCase() || '';
  const activeCategory = document.querySelector('.category-filter.active')?.dataset.category || 'all';
  const sortBy = document.getElementById('product-sort')?.value || 'default';

  const products = Array.from(document.querySelectorAll('.product-card'));

  products.forEach(product => {
    const title = product.dataset.title?.toLowerCase() || '';
    const category = product.dataset.category || '';

    const matchesSearch = title.includes(searchTerm);
    const matchesCategory = activeCategory === 'all' || category === activeCategory;

    product.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
  });

  // Sort products
  if (sortBy !== 'default') {
    const container = document.getElementById('product-grid');
    const sortedProducts = products.sort((a, b) => {
      const priceA = parseFloat(a.dataset.price);
      const priceB = parseFloat(b.dataset.price);

      if (sortBy === 'price-low') return priceA - priceB;
      if (sortBy === 'price-high') return priceB - priceA;
      if (sortBy === 'popular') return parseInt(b.dataset.views) - parseInt(a.dataset.views);
      return 0;
    });

    if (container) {
      sortedProducts.forEach(product => container.appendChild(product));
    }
  }
}

// === FORM VALIDATION ===
function initFormValidation() {
  const forms = document.querySelectorAll('form[data-validate]');

  forms.forEach(form => {
    form.addEventListener('submit', function (e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });
  });
}

function validateForm(form) {
  let isValid = true;
  const inputs = form.querySelectorAll('[required]');

  inputs.forEach(input => {
    const errorElement = input.nextElementSibling;

    if (!input.value.trim()) {
      isValid = false;
      input.classList.add('error');
      if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.textContent = 'This field is required';
      }
    } else {
      input.classList.remove('error');
      if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.textContent = '';
      }
    }

    // Email validation
    if (input.type === 'email' && input.value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(input.value)) {
        isValid = false;
        input.classList.add('error');
        if (errorElement && errorElement.classList.contains('form-error')) {
          errorElement.textContent = 'Please enter a valid email';
        }
      }
    }
  });

  return isValid;
}

// === NOTIFICATIONS ===
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
    color: white;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    z-index: 10000;
    animation: slideIn 0.3s ease-out;
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'fadeOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// === UTILITY FUNCTIONS ===
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// === IMAGE GALLERY ===
function initImageGallery() {
  const thumbnails = document.querySelectorAll('.product-thumbnail');
  const mainImage = document.getElementById('main-product-image');

  thumbnails.forEach(thumb => {
    thumb.addEventListener('click', function () {
      thumbnails.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      if (mainImage) {
        mainImage.src = this.src;
      }
    });
  });
}

// === COUPON CODE ===
function applyCoupon() {
  const couponInput = document.getElementById('coupon-code');
  const couponBtn = document.getElementById('apply-coupon');

  if (couponBtn) {
    couponBtn.addEventListener('click', async function () {
      const code = couponInput.value.trim();

      if (!code) {
        showNotification('Please enter a coupon code', 'error');
        return;
      }

      try {
        const response = await fetch('/api/validate-coupon.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: code })
        });

        const data = await response.json();

        if (data.success) {
          showNotification('Coupon applied successfully!', 'success');
          updateCartTotal(data.discount);
        } else {
          showNotification(data.message || 'Invalid coupon code', 'error');
        }
      } catch (error) {
        showNotification('Error applying coupon', 'error');
      }
    });
  }
}

// Initialize cart display if on cart page
if (document.getElementById('cart-items')) {
  updateCartDisplay();
}

// Initialize image gallery if on product detail page
if (document.querySelector('.product-thumbnail')) {
  initImageGallery();
}

// Initialize coupon functionality if on checkout page
if (document.getElementById('apply-coupon')) {
  applyCoupon();
}
// === HERO SLIDER ===
function initHeroSlider() {
  const slider = document.querySelector('.hero-slider');
  if (!slider) return;

  const slides = slider.querySelectorAll('.slide');
  const dots = slider.querySelectorAll('.slider-dot');
  const prevBtn = slider.querySelector('.arrow-prev');
  const nextBtn = slider.querySelector('.arrow-next');

  if (slides.length === 0) return;

  let currentSlide = 0;
  let slideInterval;
  const intervalTime = 6000;

  function goToSlide(n) {
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    currentSlide = (n + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
  }

  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  function startAutoPlay() {
    stopAutoPlay();
    slideInterval = setInterval(nextSlide, intervalTime);
  }

  function stopAutoPlay() {
    if (slideInterval) {
      clearInterval(slideInterval);
    }
  }

  // Event Listeners
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      startAutoPlay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      startAutoPlay();
    });
  }

  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      goToSlide(index);
      startAutoPlay();
    });
  });

  // Pause on hover
  slider.addEventListener('mouseenter', stopAutoPlay);
  slider.addEventListener('mouseleave', startAutoPlay);

  // Initialize
  startAutoPlay();
}

// === ADMIN MOBILE ===
function initAdminMobile() {
    const adminLayout = document.querySelector('.admin-layout');
    if (!adminLayout) return;

    // Create Mobile Header
    const mobileHeader = document.createElement('div');
    mobileHeader.className = 'admin-mobile-header';
    mobileHeader.innerHTML = `
        <div style="font-weight: 800; font-size: 1.25rem;">Admin Panel</div>
        <button id="admin-menu-toggle" class="btn btn-secondary" style="padding: 0.5rem;"><i class="fas fa-bars"></i></button>
    `;
    
    // Insert before admin layout
    adminLayout.parentNode.insertBefore(mobileHeader, adminLayout);

    // Create Overlay
    const overlay = document.createElement('div');
    overlay.className = 'admin-overlay';
    document.body.appendChild(overlay);

    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.getElementById('admin-menu-toggle');

    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        });
        
        // Close sidebar when clicking a link (optional but good for mobile)
        const links = sidebar.querySelectorAll('.admin-menu-link');
        links.forEach(link => {
            link.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            });
        });
    }

    // Wrap tables for mobile scroll
    const tables = adminLayout.querySelectorAll('table');
    tables.forEach(table => {
        // Don't wrap if already inside a scrollable container
        if (!table.parentElement.classList.contains('table-responsive') && !table.parentElement.style.overflowX) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
}
