document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    let resetToken = null;
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const successMessage = document.getElementById('successMessage');
    const verifyUserBtn = document.getElementById('verifyUser');
    const verifyAnswersBtn = document.getElementById('verifyAnswers');
    const resetPasswordBtn = document.getElementById('resetPassword');
    const usernameInput = document.getElementById('username');
    const securityQuestionsDiv = document.getElementById('securityQuestions');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Add Enter key listeners
    usernameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            verifyUserBtn.click();
        }
    });

    function setupAnswerInputListeners() {
        const answerInputs = document.querySelectorAll('.answer-input');
        answerInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    verifyAnswersBtn.click();
                }
            });
        });
    }

    newPasswordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            resetPasswordBtn.click();
        }
    });

    confirmPasswordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            resetPasswordBtn.click();
        }
    });

    function validatePassword(password) {
        const errorElement = document.getElementById('passwordError');
        const minLength = /.{8,}/;
        const uppercase = /[A-Z]/;
        const lowercase = /[a-z]/;
        const number = /[0-9]/;
        const specialChar = /[!@#$%^&*(),.?":{}|<>]/;

        if (!minLength.test(password)) {
            errorElement.textContent = "Password must be at least 8 characters long.";
            return errorElement.textContent;
        }
        if (!uppercase.test(password)) {
            errorElement.textContent = "Password must contain at least one uppercase letter.";
            return errorElement.textContent;
        }
        if (!lowercase.test(password)) {
            errorElement.textContent = "Password must contain at least one lowercase letter.";
            return errorElement.textContent;
        }
        if (!number.test(password)) {
            errorElement.textContent = "Password must contain at least one number.";
            return errorElement.textContent;
        }
        if (!specialChar.test(password)) {
            errorElement.textContent = "Password must contain at least one special character.";
            return errorElement.textContent;
        }
        
        errorElement.textContent = "";
        return "";
    }

    newPasswordInput.addEventListener('input', function() {
        validatePassword(this.value);
    });

    confirmPasswordInput.addEventListener('input', function() {
        const errorElement = document.getElementById('confirmError');
        if (this.value !== newPasswordInput.value) {
            errorElement.style.display = 'block';
        } else {
            errorElement.style.display = 'none';
        }
    });

    let currentUser = null;
    let questions = [];

    function showStep(stepNumber) {
        [step1, step2, step3, successMessage].forEach((step, index) => {
            step.classList.toggle('active', index + 1 === stepNumber);
        });
    }

    function renderQuestions() {
        securityQuestionsDiv.innerHTML = questions.map((q, i) => `
            <div class="mb-3">
                <label class="form-label">${q.question}</label>
                <input type="text" class="form-control answer-input" 
                       data-question="${q.question}" 
                       required>
            </div>
        `).join('');
        
        setupAnswerInputListeners();
    }

    verifyUserBtn.addEventListener('click', async function() {
        const email = usernameInput.value.trim();
        if (!email) {
            alert('Please enter your email address');
            return;
        }

        try {
            const response = await fetch('get-security-questions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON but got: ${text.substring(0, 100)}...`);
            }

            const data = await response.json();

            if (data.success) {
                currentUser = data.email;
                questions = data.questions;
                renderQuestions();
                showStep(2);
            } else {
                alert(data.message || 'Email not found in our system');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert(`Error: ${error.message}. Please check console for details.`);
        }
    });

    verifyAnswersBtn.addEventListener('click', async function() {
        const answerInputs = document.querySelectorAll('.answer-input');
        const answers = [];
        
        answerInputs.forEach(input => {
            answers.push({
                question: input.dataset.question,
                answer: input.value.trim().toLowerCase()
            });
            input.classList.remove('is-invalid');
        });

        try {
            verifyAnswersBtn.disabled = true;
            verifyAnswersBtn.textContent = 'Verifying...';

            const response = await fetch('verify-answers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    email: currentUser, 
                    answers: answers 
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server returned an invalid response');
            }

            const data = await response.json();

            if (data.success) {
                showStep(3);
                resetToken = data.token;
            } else {
                answerInputs.forEach(input => {
                    input.classList.add('is-invalid');
                });
                alert(data.message || 'Verification failed. Please try again.');
            }
        } catch (error) {
            console.error('Verification error:', error);
            alert('Error: ' + error.message);
        } finally {
            verifyAnswersBtn.disabled = false;
            verifyAnswersBtn.textContent = 'Verify Answers';
        }
    });

    resetPasswordBtn.addEventListener('click', async function() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (newPassword !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
    
        const passwordError = validatePassword(newPassword);
        if (passwordError) {
            alert(passwordError);
            return;
        }
    
        try {
            const response = await fetch('reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    email: currentUser,
                    newPassword,
                    token: resetToken
                })
            });
        
            const data = await response.json();
        
            if (data.success) {
                showStep(4);
            } else {
                alert(data.message || 'Password reset failed');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });
});