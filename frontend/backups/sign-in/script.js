const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const error = document.getElementById('error');
        const form = document.getElementById('registerForm');

// Function to validate password requirements
function validatePassword() {
    const passwordValue = password.value;
    const minLength = /.{8,}/;
    const uppercase = /[A-Z]/;
    const lowercase = /[a-z]/;
    const number = /[0-9]/;
    const specialChar = /[!@#$%^&*(),.?":{}|<>]/;

    if (!minLength.test(passwordValue)) {
        return "Password must be at least 8 characters long.";
    }
    if (!uppercase.test(passwordValue)) {
        return "Password must contain at least one uppercase letter.";
    }
    if (!lowercase.test(passwordValue)) {
        return "Password must contain at least one lowercase letter.";
    }
    if (!number.test(passwordValue)) {
        return "Password must contain at least one number.";
    }
    if (!specialChar.test(passwordValue)) {
        return "Password must contain at least one special character.";
    }
    return ""; // No error if all conditions are met
}

// Real-time validation
password.addEventListener('input', function () {
    const errorMessage = validatePassword();
    if (errorMessage) {
        error.style.display = 'block';
        error.textContent = errorMessage;
    } else {
        error.style.display = 'none';
    }
});

confirmPassword.addEventListener('input', function () {
    if (password.value !== confirmPassword.value) {
        error.style.display = 'block';
        error.textContent = 'Passwords do not match.';
    } else if (!validatePassword()) {
        error.style.display = 'none';
    }
});

// Form submission validation
form.addEventListener('submit', function (e) {
    const errorMessage = validatePassword();
    if (errorMessage) {
        e.preventDefault();
        error.style.display = 'block';
        error.textContent = errorMessage;
    } else if (password.value !== confirmPassword.value) {
        e.preventDefault();
        error.style.display = 'block';
        error.textContent = 'Passwords do not match.';
    }
});


// Password validation code remains the same...

document.addEventListener('DOMContentLoaded', function() {
    const securityCheckbox = document.getElementById('enableSecurity');
    const securityContainer = document.getElementById('securityQuestionsContainer');
    
    if (!securityCheckbox || !securityContainer) {
        console.error('Required elements not found');
        return;
    }

    securityCheckbox.addEventListener('change', function() {
        if (this.checked) {
            securityContainer.classList.add('active');
            // Enable all questions and answers
            document.querySelectorAll('.security-questions select, .security-questions input[type="text"]').forEach(el => {
                el.disabled = false;
                el.required = true;
            });
        } else {
            securityContainer.classList.remove('active');
            // Disable all questions and answers
            document.querySelectorAll('.security-questions select, .security-questions input[type="text"]').forEach(el => {
                el.disabled = true;
                el.required = false;
                el.value = "";
            });
        }
    });

    // Initialize state - container should be hidden by default
    securityContainer.classList.remove('active');
});

document.addEventListener('DOMContentLoaded', function() {
    // Security questions toggle
    const securityCheckbox = document.getElementById('enableSecurity');
    const securityContainer = document.getElementById('securityQuestionsContainer');
    
    if (securityCheckbox && securityContainer) {
        securityCheckbox.addEventListener('change', function() {
            if (this.checked) {
                securityContainer.classList.add('active');
                document.querySelectorAll('.security-questions select, .security-questions input[type="text"]').forEach(el => {
                    el.disabled = false;
                    el.required = true;
                });
            } else {
                securityContainer.classList.remove('active');
                document.querySelectorAll('.security-questions select, .security-questions input[type="text"]').forEach(el => {
                    el.disabled = true;
                    el.required = false;
                    el.value = "";
                });
            }
        });
        
        // Initialize state
        securityContainer.classList.remove('active');
    }

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
});