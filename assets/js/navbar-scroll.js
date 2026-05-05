document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.glass-navbar');
    const heroSection = document.querySelector('.hero-slider-section');
    let lastScrollTop = 0;
    let isNavbarVisible = true;

    if (!navbar) return;

    // Set initial state
    navbar.classList.add('navbar-visible');

    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const heroBottom = heroSection ? heroSection.offsetHeight : 600;

        // If user is at the hero section, always show navbar
        if (scrollTop < heroBottom) {
            if (!isNavbarVisible) {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
                isNavbarVisible = true;
            }
            lastScrollTop = scrollTop;
            return;
        }

        // Below hero section - hide on scroll down, show on scroll up
        if (scrollTop > lastScrollTop) {
            // Scrolling down
            if (isNavbarVisible) {
                navbar.classList.remove('navbar-visible');
                navbar.classList.add('navbar-hidden');
                isNavbarVisible = false;
            }
        } else {
            // Scrolling up
            if (!isNavbarVisible) {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
                isNavbarVisible = true;
            }
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, false);
});
