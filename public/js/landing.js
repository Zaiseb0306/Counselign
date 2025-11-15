document.addEventListener('DOMContentLoaded', function() {
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    const adminPasswordModal = new bootstrap.Modal(document.getElementById('adminPasswordModal'));
    const signUpModal = new bootstrap.Modal(document.getElementById('signUpModal'));
    const forgotPasswordModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
    const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
    const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
    const verificationModalInstance = new bootstrap.Modal(document.getElementById('verificationModal'));
    const counselorInfoModalInstance = (function(){
        const el = document.getElementById('counselorInfoModal');
        return el ? new bootstrap.Modal(el) : null;
    })();
    const urlParams = new URLSearchParams(window.location.search);
    const openParam = urlParams.get('open');
    
    // Contact modal event listener
    document.getElementById('openContactModal').addEventListener('click', function(e) {
        e.preventDefault();
        contactModal.show();
    });

    // --- Counselor Info submission ---
    (function initCounselorInfoFlow(){
        const submitBtn = document.getElementById('counselorInfoSubmitBtn');
        const modalEl = document.getElementById('counselorInfoModal');
        if (!submitBtn || !modalEl) return;

        function resetWarning() {
            const warn = document.getElementById('counselorInfoWarning');
            if (warn) { warn.classList.add('d-none'); warn.textContent = ''; }
        }

        function showWarn(msg) {
            const warn = document.getElementById('counselorInfoWarning');
            if (warn) { warn.textContent = msg; warn.classList.remove('d-none'); }
        }

        function getField(id){ return document.getElementById(id); }

        submitBtn.addEventListener('click', function(e){
            e.preventDefault();
            resetWarning();

            const counselor_id = (getField('c_info_counselor_id')?.value || '').trim();
            const name = (getField('c_info_name')?.value || '').trim();
            const degree = (getField('c_info_degree')?.value || '').trim();
            const email = (getField('c_info_email')?.value || '').trim();
            const contact_number = (getField('c_info_contact')?.value || '').trim();
            const address = (getField('c_info_address')?.value || '').trim();
            const civil_status = getField('c_info_civil_status')?.value || '';
            const sex = getField('c_info_sex')?.value || '';
            const birthdate = getField('c_info_birthdate')?.value || '';

            if (!counselor_id || !name || !degree || !email || !contact_number || !address) {
                return showWarn('Please fill in all required fields.');
            }
            if (!isValidEmail(email)) {
                return showWarn('Please enter a valid email address.');
            }
            if (!/^09[0-9]{9}$/.test(contact_number)) {
                return showWarn('Contact number must be in the format 09XXXXXXXXX.');
            }

            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            const formData = new FormData();
            formData.append('counselor_id', counselor_id);
            formData.append('name', name);
            formData.append('degree', degree);
            formData.append('email', email);
            formData.append('contact_number', contact_number);
            formData.append('address', address);
            if (civil_status) formData.append('civil_status', civil_status);
            if (sex) formData.append('sex', sex);
            if (birthdate) formData.append('birthdate', birthdate);
            formData.append(window.CSRF_TOKEN_NAME, document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch((window.BASE_URL || '/') + 'counselor/save-basic-info', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
                if (data.status === 'success') {
                    try { bootstrap.Modal.getInstance(modalEl).hide(); } catch (e) {}
                    openConfirmationModal('Your information has been saved. Please wait for admin approval.');
                    // Clear fields
                    ['c_info_name','c_info_degree','c_info_email','c_info_contact','c_info_address','c_info_civil_status','c_info_sex','c_info_birthdate']
                        .forEach(function(id){ const el = getField(id); if (el) el.value = ''; });
                } else {
                    showWarn(data.message || 'Failed to save information.');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
                showWarn('An error occurred. Please try again.');
            });
        });
    })();

    // Contact form submission
    document.getElementById('contactSubmitBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset error messages
        document.getElementById('contactWarning').classList.add('d-none');
        document.getElementById('contactNameError').classList.add('d-none');
        document.getElementById('contactEmailError').classList.add('d-none');
        document.getElementById('contactSubjectError').classList.add('d-none');
        document.getElementById('contactMessageError').classList.add('d-none');
        
        // Get form data
        const name = document.getElementById('contactName').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const subject = document.getElementById('contactSubject').value.trim();
        const message = document.getElementById('contactMessage').value.trim();
        
        // Basic validation
        let isValid = true;
        
        if (!name) {
            document.getElementById('contactNameError').classList.remove('d-none');
            document.getElementById('contactWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!email || !isValidEmail(email)) {
            document.getElementById('contactEmailError').classList.remove('d-none');
            document.getElementById('contactWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!subject) {
            document.getElementById('contactSubjectError').classList.remove('d-none');
            document.getElementById('contactWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!message) {
            document.getElementById('contactMessageError').classList.remove('d-none');
            document.getElementById('contactWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (isValid) {
            const submitBtn = document.getElementById('contactSubmitBtn');
            const originalBtnText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
            
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append(window.CSRF_TOKEN_NAME, document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch(window.BASE_URL + 'email/sendContactEmail', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.status === 'success') {
                    openConfirmationModal('Thank you for your message! We will get back to you soon.');
                    document.getElementById('contactName').value = '';
                    document.getElementById('contactEmail').value = '';
                    document.getElementById('contactSubject').value = '';
                    document.getElementById('contactMessage').value = '';
                    contactModal.hide();
                } else {
                    openConfirmationModal(data.message || 'Failed to send message. Please try again later.');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                console.error('Error:', error);
                openConfirmationModal('An error occurred. Please try again later.');
            });
        }
    });

    // Add Enter key functionality for login modal
    document.getElementById('loginModal').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            document.getElementById('loginBtn').click();
        }
    });

    // Add Enter key functionality for signup modal
    document.getElementById('signUpModal').addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            document.getElementById('signUpBtn').click();
        }
    });
    
    // If open=login parameter exists, open the login modal
    if (openParam === 'login') {
        setTimeout(() => {
            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        }, 300);
    }
    
    // Open modals
    document.getElementById('openLoginModal').addEventListener('click', function() {
        loginModal.show();
    });
    
    document.getElementById('openSignUpModal').addEventListener('click', function() {
        signUpModal.show();
    });
    
    // Switch between modals
    document.getElementById('goToSignUpLink').addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.hide();
        setTimeout(() => {
            signUpModal.show();
        }, 500);
    });
    
    document.getElementById('backToLoginLink').addEventListener('click', function(e) {
        e.preventDefault();
        signUpModal.hide();
        setTimeout(() => {
            loginModal.show();
        }, 500);
    });
    
    // Forgot password link
    document.getElementById('forgotPasswordLink').addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.hide();
        setTimeout(() => {
            forgotPasswordModal.show();
        }, 500);
    });
    
    // Terms link
    document.getElementById('termsLink').addEventListener('click', function(e) {
        e.preventDefault();
        termsModal.show();
    });
    
    // Password visibility toggle helper
    function togglePasswordWithIcon(inputId, buttonId, iconId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        if (!input || !button) return;

        let currentIcon = document.getElementById(iconId) || button.querySelector('.toggle-password');

        if (input.type === 'password') {
            input.type = 'text';
            if (currentIcon && currentIcon.tagName && currentIcon.tagName.toLowerCase() === 'img') {
                currentIcon.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
                currentIcon.alt = 'Hide password';
            } else {
                const img = document.createElement('img');
                img.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
                img.alt = 'Hide password';
                img.className = 'toggle-password custom-hide-icon';
                img.style.width = '20px';
                img.style.height = '20px';
                img.style.cursor = 'pointer';
                img.id = iconId;
                if (currentIcon) currentIcon.replaceWith(img); else button.appendChild(img);
            }
        } else {
            input.type = 'password';
            if (currentIcon && currentIcon.tagName && currentIcon.tagName.toLowerCase() === 'img') {
                const icon = document.createElement('i');
                icon.className = 'fas fa-eye';
                icon.style.cursor = 'pointer';
                icon.id = iconId;
                currentIcon.replaceWith(icon);
            } else if (currentIcon) {
                currentIcon.classList.remove('fa-eye-slash');
                currentIcon.classList.add('fa-eye');
            }
        }
    }

    // Password visibility toggle for login
    document.getElementById('toggleLoginPassword').addEventListener('click', function() {
        togglePasswordWithIcon('passwordInput', 'toggleLoginPassword', 'eyeIconLogin');
    });
    
    // Password visibility toggle for sign up
    document.getElementById('toggleSignUpPassword').addEventListener('click', function() {
        togglePasswordWithIcon('signUpPasswordInput', 'toggleSignUpPassword', 'eyeIconSignUp');
    });
    
    // Password visibility toggle for confirm password
    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        togglePasswordWithIcon('confirmPasswordInput', 'toggleConfirmPassword', 'eyeIconConfirm');
    });
    
    // Password visibility toggle for new password
    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        togglePasswordWithIcon('newPasswordInput', 'toggleNewPassword', 'eyeIconNewPassword');
    });
    
    // Password visibility toggle for confirm new password
    document.getElementById('toggleConfirmNewPassword').addEventListener('click', function() {
        togglePasswordWithIcon('confirmNewPasswordInput', 'toggleConfirmNewPassword', 'eyeIconConfirmNewPassword');
    });
    
    // --- Regular User/Counselor Login --- 
    const loginIdentifierInput = document.getElementById('loginIdentifierInput');
    const openAdminModalBtn = document.getElementById('openAdminModalBtn');

    // Login form submission
    document.getElementById('loginBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset error messages
        document.getElementById('loginWarning').classList.add('d-none');
        document.getElementById('loginIdentifierError').classList.add('d-none');
        document.getElementById('loginPasswordError').classList.add('d-none');
        document.getElementById('loginInvalidError').classList.add('d-none');
        
        // Get form data
        const identifier = document.getElementById('loginIdentifierInput').value.trim();
        const password = document.getElementById('passwordInput').value.trim();
        
        // Basic validation
        let isValid = true;
        
        if (!identifier) {
            document.getElementById('loginIdentifierError').textContent = "Please enter your User ID or Email.";
            document.getElementById('loginIdentifierError').classList.remove('d-none');
            document.getElementById('loginWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!password) {
            document.getElementById('loginPasswordError').classList.remove('d-none');
            document.getElementById('loginWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (isValid) {
            const loginBtn = document.getElementById('loginBtn');
            const originalLoginBtnText = loginBtn.innerHTML;

            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging In...';

            const formData = new FormData();
            formData.append('identifier', identifier);
            formData.append('password', password);
            
            fetch(window.BASE_URL + 'index.php/auth/login', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalLoginBtnText;

                if (data.status === 'success') {
                    window.location.href = data.redirect.startsWith('http') || data.redirect.startsWith('/') ? data.redirect : window.BASE_URL + data.redirect;
                } else if (data.status === 'unverified') {
                    loginModal.hide();
                    setTimeout(() => {
                        openConfirmationModal(data.message);
                        // Only show verification modal for student (server includes redirect in that case)
                        if (data.redirect) {
                            try { verificationModalInstance.show(); } catch (e) {}
                        }
                    }, 500);
                } else {
                    document.getElementById('loginInvalidError').textContent = data.message;
                    document.getElementById('loginInvalidError').classList.remove('d-none');
                    document.getElementById('loginWarning').classList.remove('d-none');
                }
            })
            .catch(error => {
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalLoginBtnText;
                console.error('Error:', error);
                document.getElementById('loginInvalidError').textContent = 'An error occurred. Please try again.';
                document.getElementById('loginInvalidError').classList.remove('d-none');
                document.getElementById('loginWarning').classList.remove('d-none');
            });
        }
    });

    // --- Admin Login ---
    if (openAdminModalBtn) {
        openAdminModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const adminIdInput = document.getElementById('adminIdentifierInput');
            if (adminIdInput) adminIdInput.value = loginIdentifierInput.value;
            try { loginModal.hide(); } catch (e) {}
            setTimeout(() => adminPasswordModal.show(), 300);
        });
    }

    // Toggle password visibility in admin modal
    document.getElementById('toggleAdminPassword').addEventListener('click', function() {
        togglePasswordWithIcon('adminPasswordInput', 'toggleAdminPassword', 'eyeIconAdmin');
    });

    // Admin modal: submit
    document.getElementById('adminLoginBtn').addEventListener('click', function(e) {
        e.preventDefault();

        document.getElementById('adminLoginWarning').classList.add('d-none');
        document.getElementById('adminIdentifierError').classList.add('d-none');
        document.getElementById('adminPasswordError').classList.add('d-none');
        document.getElementById('adminInvalidError').classList.add('d-none');

        const adminIdentifier = document.getElementById('adminIdentifierInput').value.trim();
        const adminPassword = document.getElementById('adminPasswordInput').value.trim();

        let adminValid = true;
        if (!adminIdentifier) {
            document.getElementById('adminIdentifierError').classList.remove('d-none');
            document.getElementById('adminLoginWarning').classList.remove('d-none');
            adminValid = false;
        }
        if (!adminPassword) {
            document.getElementById('adminPasswordError').classList.remove('d-none');
            document.getElementById('adminLoginWarning').classList.remove('d-none');
            adminValid = false;
        }

        if (!adminValid) return;

        const btn = document.getElementById('adminLoginBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';

        const formData = new FormData();
        formData.append('identifier', adminIdentifier);
        formData.append('password', adminPassword);

        fetch((window.BASE_URL || '/') + 'index.php/auth/verify-admin', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.status === 'success') {
                try { adminPasswordModal.hide(); } catch (e) {}
                try { loginModal.hide(); } catch (e) {}
                window.location.href = data.redirect.startsWith('http') || data.redirect.startsWith('/') ? data.redirect : (window.BASE_URL || '/') + data.redirect;
            } else if (data.status === 'unverified') {
                adminPasswordModal.hide();
                setTimeout(() => {
                    openConfirmationModal(data.message);
                    if (data.redirect) {
                        try { verificationModalInstance.show(); } catch (e) {}
                    }
                }, 300);
            } else {
                document.getElementById('adminInvalidError').textContent = data.message || 'Invalid Admin credentials';
                document.getElementById('adminInvalidError').classList.remove('d-none');
                document.getElementById('adminLoginWarning').classList.remove('d-none');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            document.getElementById('adminInvalidError').textContent = 'An error occurred. Please try again.';
            document.getElementById('adminInvalidError').classList.remove('d-none');
            document.getElementById('adminLoginWarning').classList.remove('d-none');
        });
    });

    // Admin modal: Back to Login
    document.getElementById('adminBackToLoginBtn').addEventListener('click', function(e) {
        e.preventDefault();
        try { adminPasswordModal.hide(); } catch (e) {}
        setTimeout(() => loginModal.show(), 300);
    });

    // Helper function to validate email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    const signUpUserIdInput = document.getElementById('signUpUserIdInput');

    // Function to filter input for numeric only and max length 10
    function filterNumericInput(inputElement) {
        inputElement.value = inputElement.value.replace(/\D/g, '');
        if (inputElement.value.length > 10) {
            inputElement.value = inputElement.value.slice(0, 10);
        }
    }

    // Filter Signup User ID (always numeric)
    signUpUserIdInput.addEventListener('input', function() {
        filterNumericInput(this);
    });

    // Sign Up form submission
    document.getElementById('signUpBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset error messages
        document.getElementById('signUpWarning').classList.add('d-none');
        document.getElementById('signUpUserIdError').classList.add('d-none');
        document.getElementById('signUpEmailError').classList.add('d-none');
        document.getElementById('signUpPasswordError').classList.add('d-none');
        document.getElementById('signUpConfirmError').classList.add('d-none');
        document.getElementById('signUpExistingError').classList.add('d-none');
        
        // Get form data
        const userId = document.getElementById('signUpUserIdInput').value.trim();
        const email = document.getElementById('signUpEmailInput').value.trim();
        const password = document.getElementById('signUpPasswordInput').value.trim();
        const confirmPassword = document.getElementById('confirmPasswordInput').value.trim();
        const termsChecked = document.getElementById('termsCheckbox').checked;
        const username = document.getElementById('signUpUsernameInput').value.trim();
        const selectedRole = document.getElementById('signUpRole').value;

        // Basic validation
        let isValid = true;
        
        // User ID validation
        if (!userId) {
            document.getElementById('signUpUserIdError').textContent = "Please enter your User ID or Counselor ID.";
            document.getElementById('signUpUserIdError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        } else if (selectedRole === 'student' && !/^\d{10}$/.test(userId)) {
            document.getElementById('signUpUserIdError').textContent = "User ID must be exactly 10 digits.";
            document.getElementById('signUpUserIdError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        } else if (selectedRole === 'counselor' && userId.length < 3) {
            document.getElementById('signUpUserIdError').textContent = "Counselor ID must be at least 3 characters long.";
            document.getElementById('signUpUserIdError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!email || !isValidEmail(email)) {
            document.getElementById('signUpEmailError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        }

        if (!username) {
            document.getElementById('signUpUsernameError').textContent = "Please enter your username.";
            document.getElementById('signUpUsernameError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!password || password.length < 8) {
            document.getElementById('signUpPasswordError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (password !== confirmPassword) {
            document.getElementById('signUpConfirmError').classList.remove('d-none');
            document.getElementById('signUpWarning').classList.remove('d-none');
            isValid = false;
        }
        
        if (!termsChecked) {
            openConfirmationModal('Please agree to the Terms and Conditions.');
            isValid = false;
        }
        
        if (isValid) {
            const signUpBtn = document.getElementById('signUpBtn');
            const originalSignUpBtnText = signUpBtn.innerHTML;

            signUpBtn.disabled = true;
            signUpBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing Up...';

            const formData = new FormData();
            formData.append('role', selectedRole);
            formData.append('userId', userId);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('confirmPassword', confirmPassword);
            formData.append('username', username);
            formData.append(window.CSRF_TOKEN_NAME, document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            fetch(window.BASE_URL + 'index.php/auth/signup', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                signUpBtn.disabled = false;
                signUpBtn.innerHTML = originalSignUpBtnText;

                if (data.status === 'success') {
                    document.getElementById('signUpUserIdInput').value = '';
                    document.getElementById('signUpEmailInput').value = '';
                    document.getElementById('signUpPasswordInput').value = '';
                    document.getElementById('confirmPasswordInput').value = '';
                    document.getElementById('termsCheckbox').checked = false;
                    document.getElementById('signUpUsernameInput').value = '';
                    // Branch depending on role
                    signUpModal.hide();
                    setTimeout(() => {
                        if (selectedRole === 'student') {
                            try { verificationModalInstance.show(); } catch (e) {}
                        } else if (selectedRole === 'counselor') {
                            if (counselorInfoModalInstance) {
                                const cid = document.getElementById('c_info_counselor_id');
                                const cemail = document.getElementById('c_info_email');
                                if (cid) cid.value = userId;
                                if (cemail) cemail.value = email;
                                counselorInfoModalInstance.show();
                            } else {
                                openConfirmationModal('Counselor account created. Please wait for admin approval.');
                            }
                        }
                    }, 500);
                } else {
                    document.getElementById('signUpExistingError').textContent = data.message;
                    document.getElementById('signUpExistingError').classList.remove('d-none');
                    document.getElementById('signUpWarning').classList.remove('d-none');
                }
            })
            .catch(error => {
                signUpBtn.disabled = false;
                signUpBtn.innerHTML = originalSignUpBtnText;
                console.error('Error:', error);
                document.getElementById('signUpExistingError').textContent = 'An error occurred. Please try again.';
                document.getElementById('signUpExistingError').classList.remove('d-none');
                document.getElementById('signUpWarning').classList.remove('d-none');
            });
        }
    });

    // --- Forgot Password Section ---
    document.getElementById('resetPasswordBtn').onclick = function() {
        const btn = this;
        const input = document.getElementById('forgotPasswordInput').value.trim();
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

        fetch(window.BASE_URL + 'forgot-password/send-code', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({input: input})
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                new bootstrap.Modal(document.getElementById('codeEntryModal')).show();
                setTimeout(() => openConfirmationModal('A reset code has been sent to your email. It will expire in 5 minutes.'), 300);
            } else {
                document.getElementById('forgotPasswordWarning').classList.remove('d-none');
                document.getElementById('forgotPasswordInputError').textContent = data.message;
                document.getElementById('forgotPasswordInputError').classList.remove('d-none');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };

    // Resend reset code link handler
    document.getElementById('resendResetCodeLink').addEventListener('click', function(e) {
        e.preventDefault();
        const resendResetCodeModal = new bootstrap.Modal(document.getElementById('resendResetCodeModal'));
        resendResetCodeModal.show();
    });

    // Resend reset code modal handlers
    const resendResetCodeModal = document.getElementById('resendResetCodeModal');
    const resendResetCodeInput = document.getElementById('resendResetCodeInput');
    const resendResetCodeBtn = document.getElementById('resendResetCodeBtn');
    const resendResetCodeCancelBtn = document.getElementById('resendResetCodeCancelBtn');
    const resendResetCodeWarning = document.getElementById('resendResetCodeWarning');
    const resendResetCodeInputError = document.getElementById('resendResetCodeInputError');

    if (resendResetCodeModal && resendResetCodeInput && resendResetCodeBtn) {
        function isValidEmailOrUserId(input) {
            const trimmedInput = input.trim();
            if (!trimmedInput) return false;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(trimmedInput)) return true;
            const userIdRegex = /^[a-zA-Z0-9]{3,}$/;
            if (userIdRegex.test(trimmedInput)) return true;
            return false;
        }

        resendResetCodeBtn.addEventListener('click', function() {
            resendResetCodeWarning.classList.add('d-none');
            resendResetCodeInputError.classList.add('d-none');
            
            const identifier = resendResetCodeInput.value.trim();
            
            if (!isValidEmailOrUserId(identifier)) {
                resendResetCodeInputError.classList.remove('d-none');
                resendResetCodeWarning.classList.remove('d-none');
                return;
            }

            const originalBtnText = resendResetCodeBtn.innerHTML;
            resendResetCodeBtn.disabled = true;
            resendResetCodeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

            fetch(window.BASE_URL + 'forgot-password/send-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({input: identifier})
            })
            .then(response => response.json())
            .then(data => {
                resendResetCodeBtn.disabled = false;
                resendResetCodeBtn.innerHTML = originalBtnText;

                if (data.status === 'success') {
                    const resendModalInstance = bootstrap.Modal.getInstance(document.getElementById('resendResetCodeModal'));
                    if (resendModalInstance) {
                        resendModalInstance.hide();
                    }
                    resendResetCodeInput.value = '';
                    openConfirmationModal('A new reset code has been sent to your email. It will expire in 5 minutes.');
                } else {
                    resendResetCodeInputError.textContent = data.message;
                    resendResetCodeInputError.classList.remove('d-none');
                    resendResetCodeWarning.classList.remove('d-none');
                }
            })
            .catch(error => {
                resendResetCodeBtn.disabled = false;
                resendResetCodeBtn.innerHTML = originalBtnText;
                console.error('Error:', error);
                openConfirmationModal('An error occurred while trying to resend the reset code.');
            });
        });

        resendResetCodeCancelBtn.addEventListener('click', function() {
            const resendModalInstance = bootstrap.Modal.getInstance(document.getElementById('resendResetCodeModal'));
            if (resendModalInstance) {
                resendModalInstance.hide();
            }
            resendResetCodeInput.value = '';
            resendResetCodeWarning.classList.add('d-none');
            resendResetCodeInputError.classList.add('d-none');
        });

        resendResetCodeInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                resendResetCodeBtn.click();
            }
        });

        resendResetCodeModal.addEventListener('hidden.bs.modal', function() {
            resendResetCodeInput.value = '';
            resendResetCodeWarning.classList.add('d-none');
            resendResetCodeInputError.classList.add('d-none');
        });
    }

    // Build reset code from 6 inputs
    function buildResetCode() {
        const container = document.getElementById('resetCodeInputs');
        if (!container) return '';
        const inputs = Array.prototype.slice.call(container.querySelectorAll('.token-box'));
        if (!inputs || inputs.length !== 6) return '';
        return inputs.map(function(input){ return (input.value || '').replace(/[^0-9]/g, ''); }).join('');
    }

    // Initialize behaviors for 6-input reset code
    (function initResetCodeInputs(){
        const container = document.getElementById('resetCodeInputs');
        if (!container) return;
        const inputs = Array.prototype.slice.call(container.querySelectorAll('.token-box'));
        if (!inputs || inputs.length !== 6) return;

        inputs.forEach(function(input, index){
            input.addEventListener('input', function(){
                var v = (input.value || '').replace(/[^0-9]/g, '');
                input.value = v.slice(0,1);
                if (v && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
            });
            input.addEventListener('keydown', function(e){
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].select();
                }
                if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    inputs[index - 1].focus();
                    inputs[index - 1].select();
                }
                if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                    e.preventDefault();
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
            });
            input.addEventListener('paste', function(e){
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
                pasted = pasted.replace(/[^0-9]/g, '').slice(0, inputs.length);
                if (!pasted) return;
                for (var i = 0; i < inputs.length; i++) {
                    inputs[i].value = pasted[i] || '';
                }
                var lastIndex = Math.min(pasted.length, inputs.length) - 1;
                if (lastIndex >= 0) {
                    inputs[lastIndex].focus();
                    inputs[lastIndex].select();
                }
            });
        });

        const codeModal = document.getElementById('codeEntryModal');
        if (codeModal) {
            codeModal.addEventListener('shown.bs.modal', function(){
                try { inputs[0].focus(); inputs[0].select(); } catch(e) {}
            });
        }
    })();

    document.getElementById('verifyCodeBtn').onclick = function() {
        const btn = this;
        const code = buildResetCode();
        if (!code || code.length !== 6 || /\D/.test(code)) {
            const warn = document.getElementById('resetCodeWarning');
            warn.classList.remove('d-none');
            warn.textContent = 'Please enter the 6-digit code sent to your email.';
            return;
        }
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';

        fetch(window.BASE_URL + 'forgot-password/verify-code', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({code: code})
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('codeEntryModal')).hide();
                new bootstrap.Modal(document.getElementById('newPasswordModal')).show();
                setTimeout(() => openConfirmationModal('Code verified! You may now set a new password.'), 300);
            } else {
                document.getElementById('resetCodeWarning').classList.remove('d-none');
                document.getElementById('resetCodeWarning').textContent = data.message;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };

    document.getElementById('setNewPasswordBtn').onclick = function() {
        const btn = this;
        const password = document.getElementById('newPasswordInput').value;
        const confirm = document.getElementById('confirmNewPasswordInput').value;
        if (password !== confirm || password.length < 8) {
            document.getElementById('newPasswordWarning').classList.remove('d-none');
            document.getElementById('newPasswordWarning').textContent = 'Passwords must match and be at least 8 characters.';
            return;
        }
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        fetch(window.BASE_URL + 'forgot-password/set-password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({password: password})
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.status === 'success') {
                openConfirmationModal('Password reset successful! You can now log in with your new password.');
                bootstrap.Modal.getInstance(document.getElementById('newPasswordModal')).hide();
            } else {
                document.getElementById('newPasswordWarning').classList.remove('d-none');
                document.getElementById('newPasswordWarning').textContent = data.message;
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };

    // Handle opening and closing of Navbar Drawer
    const navbarDrawerToggler = document.getElementById('navbarDrawerToggler');
    const navbarDrawer = document.getElementById('navbarDrawer');
    const navbarDrawerClose = document.getElementById('navbarDrawerClose');
    const navbarOverlay = document.getElementById('navbarOverlay');

    function openDrawer() {
        navbarDrawer.classList.add('show');
        navbarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        navbarDrawer.classList.remove('show');
        navbarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (navbarDrawerToggler) {
        navbarDrawerToggler.addEventListener('click', openDrawer);
    }

    if (navbarDrawerClose) {
        navbarDrawerClose.addEventListener('click', closeDrawer);
    }

    if (navbarOverlay) {
        navbarOverlay.addEventListener('click', closeDrawer);
    }

    document.getElementById('openLoginModalDrawer').addEventListener('click', function() {
        closeDrawer();
        setTimeout(() => {
            loginModal.show();
        }, 300);
    });

    document.getElementById('openSignUpModalDrawer').addEventListener('click', function() {
        closeDrawer();
        setTimeout(() => {
            signUpModal.show();
        }, 300);
    });

    document.getElementById('openContactModalDrawer').addEventListener('click', function() {
        closeDrawer();
        setTimeout(() => {
            contactModal.show();
        }, 300);
    });

    // Add event listener for signup role select to change user ID input placeholder
    document.getElementById('signUpRole').addEventListener('change', function() {
        const selectedRole = this.value;
        const signUpUserIdInput = document.getElementById('signUpUserIdInput');
        if (selectedRole === 'student') {
            signUpUserIdInput.placeholder = 'Preferred User ID (10 digits)';
            signUpUserIdInput.setAttribute('pattern', '[0-9]{10}');
            signUpUserIdInput.setAttribute('title', 'Preferred User ID must be exactly 10 digits.');
        } else if (selectedRole === 'counselor') {
            signUpUserIdInput.placeholder = 'Preferred Counselor ID(Valid)';
            signUpUserIdInput.removeAttribute('pattern');
            signUpUserIdInput.removeAttribute('title');
        }
    });

    // Initialize placeholder based on default selected role
    const initialSignUpRole = document.getElementById('signUpRole').value;
    if (initialSignUpRole === 'student') {
        signUpUserIdInput.placeholder = 'Preferred User ID (10 digits)';
        signUpUserIdInput.setAttribute('pattern', '[0-9]{10}');
        signUpUserIdInput.setAttribute('title', 'Preferred User ID must be exactly 10 digits.');
    } else if (initialSignUpRole === 'counselor') {
        signUpUserIdInput.placeholder = 'Preferred Counselor ID(Valid)';
        signUpUserIdInput.removeAttribute('pattern');
        signUpUserIdInput.removeAttribute('title');
    }
});