// Verification Prompt JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    var verificationForm = document.getElementById('verificationForm');
    var tokenContainer = document.getElementById('tokenInputs');
    var tokenInputs = tokenContainer ? Array.prototype.slice.call(tokenContainer.querySelectorAll('.token-box')) : [];
    var verificationMessage = document.getElementById('verificationMessage');
    var resendEmailLink = document.getElementById('resendVerificationEmail');
    var verifyAccountBtn = document.querySelector('#verificationForm button[type="submit"]');
    const originalVerifyAccountBtnText = verifyAccountBtn.innerHTML;

    // Countdown timer variables
    let countdownTimer = null;
    let redirectUrl = '';

    // Helper: build token from 6 inputs
    function buildTokenFromInputs() {
        if (!tokenInputs || tokenInputs.length !== 6) return '';
        return tokenInputs.map(function(input) { return (input.value || '').toUpperCase().trim(); }).join('');
    }

    // Input behaviors: auto-advance, backspace, sanitize/uppercase, paste handling
    if (tokenInputs && tokenInputs.length === 6) {
        tokenInputs.forEach(function(input, index) {
            input.addEventListener('input', function(e) {
                var v = (input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                input.value = v.slice(0, 1);
                if (v && index < tokenInputs.length - 1) {
                    tokenInputs[index + 1].focus();
                    tokenInputs[index + 1].select();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    tokenInputs[index - 1].focus();
                    tokenInputs[index - 1].select();
                }
                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    tokenInputs[index - 1].focus();
                    tokenInputs[index - 1].select();
                }
                if (e.key === 'ArrowRight' && index < tokenInputs.length - 1) {
                    e.preventDefault();
                    tokenInputs[index + 1].focus();
                    tokenInputs[index + 1].select();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
                pasted = pasted.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, tokenInputs.length);
                if (!pasted) return;
                for (var i = 0; i < tokenInputs.length; i++) {
                    tokenInputs[i].value = pasted[i] || '';
                }
                var lastIndex = Math.min(pasted.length, tokenInputs.length) - 1;
                if (lastIndex >= 0) {
                    tokenInputs[lastIndex].focus();
                    tokenInputs[lastIndex].select();
                }
            });
        });

        // Focus first input initially
        if (tokenInputs[0]) {
            try { tokenInputs[0].focus(); tokenInputs[0].select(); } catch (e) {}
        }
    }

    // Verification form submission handler
    verificationForm.onsubmit = function(event) {
        event.preventDefault();
        var token = buildTokenFromInputs();

        if (!token || token.length !== 6 || /[^A-Z0-9]/.test(token)) {
            openConfirmationModal('Please enter a valid 6-character verification token.');
            return;
        }

        // Show loading state
        verifyAccountBtn.disabled = true;
        verifyAccountBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';

        fetch(window.BASE_URL + 'verify-account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                [window.CSRF_TOKEN_NAME]: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ token: token })
        })
        .then(response => response.json())
        .then(data => {
            // Revert button state
            verifyAccountBtn.disabled = false;
            verifyAccountBtn.innerHTML = originalVerifyAccountBtnText;

            if (data.status === 'success') {
                // Hide the form and show success message with countdown
                document.getElementById('verificationForm').classList.add('d-none');
                document.getElementById('resendVerificationEmail').classList.add('d-none');
                document.getElementById('verificationSuccessMessage').classList.remove('d-none');
                
                // Start countdown timer
                startCountdown(data.redirect || window.BASE_URL + 'user/dashboard');
            } else {
                openConfirmationModal(data.message);
                verificationMessage.textContent = data.message;
            }
        })
        .catch(error => {
            // Revert button state
            verifyAccountBtn.disabled = false;
            verifyAccountBtn.innerHTML = originalVerifyAccountBtnText;

            console.error('Error:', error);
            openConfirmationModal('An error occurred during verification.');
            verificationMessage.textContent = 'An error occurred during verification.';
        });
    };

    // Resend verification email handler
    resendEmailLink.onclick = function(event) {
        event.preventDefault();
        const identifier = prompt("Please enter your registered email or user ID to resend the verification email:");
        if (!identifier) {
            return;
        }

        fetch(window.BASE_URL + 'resend-verification-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                [window.CSRF_TOKEN_NAME]: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ identifier: identifier })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                openConfirmationModal(data.message);
                verificationMessage.textContent = data.message;
            } else if (data.status === 'already_verified') {
                openConfirmationModal(data.message);
                const verificationModalInstance = bootstrap.Modal.getInstance(document.getElementById('verificationModal'));
                if (verificationModalInstance) {
                    verificationModalInstance.hide();
                }
                setTimeout(() => {
                    window.location.href = window.BASE_URL + '?open=login';
                }, 1500);
            } else {
                openConfirmationModal(data.message);
                verificationMessage.textContent = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            openConfirmationModal('An error occurred while trying to resend the email.');
            verificationMessage.textContent = 'An error occurred while trying to resend the email.';
        });
    };

    // Countdown timer function
    function startCountdown(redirect) {
        redirectUrl = redirect;
        let countdown = 10;
        const countdownElement = document.getElementById('countdownNumber');
        
        countdownElement.textContent = countdown;
        
        countdownTimer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                window.location.href = redirectUrl;
            }
        }, 1000);
    }

    // Go to dashboard button handler
    document.getElementById('goToDashboardBtn').addEventListener('click', function() {
        clearInterval(countdownTimer);
        window.location.href = redirectUrl;
    });

    // Stay on landing page button handler
    document.getElementById('stayOnLandingBtn').addEventListener('click', function() {
        clearInterval(countdownTimer);
        const verificationModalInstance = bootstrap.Modal.getInstance(document.getElementById('verificationModal'));
        if (verificationModalInstance) {
            verificationModalInstance.hide();
        }
    });

    // Check if redirected from unverified login attempt
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('unverified') === 'true') {
        openVerificationModal("Your account is not verified. Please enter the token to verify your account or resend the verification email.");
    }
});

// Global function to open verification modal (exposed for use by other scripts)
function openVerificationModal(message = "A verification email has been sent to your registered email address. Please enter the token below to verify your account.") {
    document.getElementById('verificationMessage').textContent = message;
    const verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
    verificationModal.show();
}
