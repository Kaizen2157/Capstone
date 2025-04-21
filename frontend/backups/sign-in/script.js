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

