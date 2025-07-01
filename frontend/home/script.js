// Existing animation code remains the same
const descriptions = document.querySelectorAll('.description');

descriptions.forEach((desc, index) => {
    if (index % 2 === 0) {
        desc.classList.add('from-left');
    } else {
        desc.classList.add('from-right');
    }
});

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.2 });

descriptions.forEach(desc => {
    observer.observe(desc);
});

// Mobile menu functionality - only activate below 1200px
const menuToggle = document.querySelector('.mobile-menu-toggle');
const navLinks = document.querySelector('nav .links');

function handleMenuToggle() {
    if (window.innerWidth <= 1200) {
        menuToggle.addEventListener('click', toggleMenu);
    } else {
        menuToggle.removeEventListener('click', toggleMenu);
        navLinks.classList.remove('show');
    }
}

function toggleMenu() {
    navLinks.classList.toggle('show');
}

// Initialize and handle resize
window.addEventListener('resize', () => {
    handleMenuToggle();
    
    // Adjust animations for mobile
    if (window.innerWidth <= 768) {
        descriptions.forEach(desc => {
            desc.classList.remove('from-left', 'from-right');
        });
    }
});

// Initial setup
handleMenuToggle();
if (window.innerWidth <= 768) {
    descriptions.forEach(desc => {
        desc.classList.remove('from-left', 'from-right');
    });
}