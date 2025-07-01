// Password toggle functionality remains the same
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.textContent = type === 'password' ? '👁️' : '🙈';
});

// Error handling
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const error = params.get('error');

    if (error) {
        const errorMessageDiv = document.getElementById('error-message');
        errorMessageDiv.textContent = decodeURIComponent(error);
        errorMessageDiv.style.display = 'block';
    }
});

// Responsive adjustments
function handleResponsive() {
    const form = document.querySelector('.wrapper form');
    if (window.innerWidth < 768) {
        // Mobile-specific adjustments
    } else {
        // Desktop-specific adjustments
    }
}

// Run on load and resize
handleResponsive();
window.addEventListener('resize', handleResponsive);