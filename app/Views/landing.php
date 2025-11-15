<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services" />
    <meta name="keywords" content="counseling, guidance, university, support" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title>Counselign</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= base_url('css/landing.css?v=1.2') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="UGC Logo" class="logo" />
                    <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    <button class="custom-navbar-toggler d-lg-none align-items-center" type="button" id="navbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i
                                class="fas fa-bars"></i></span>
                    </button>

                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <ul class="navbar-nav nav-links">
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('services') ?>"><i
                                            class="fas fa-handshake"></i> Services</a></li>
                                <li id="openContactModal" class="nav-item"><a class="nav-link" href="#"><i class="fas fa-envelope"></i>
                                        Contact</a></li>

                                <li id="openLoginModal" class="nav-item"><a class="nav-link" href="#"><i
                                            class="fas fa-user-circle"></i>Login</a></li>
                                <li id="openSignUpModal" class="nav-item"><a class="nav-link" href="#"><i
                                            class="fas fa-user-plus"></i>
                                        Signup</a></li>
                            </ul>
                        </div>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <!-- Navbar Drawer for Small Screens -->
    <div class="navbar-drawer d-lg-none" id="navbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Counselign Menu</h5>
            <button class="btn-close btn-close-white" id="navbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('services') ?>"><i class="fas fa-handshake"></i> Services</a></li>
            <li id="openContactModalDrawer" class="nav-item"><a class="nav-link" href="#"><i class="fas fa-envelope"></i> Contact</a></li>
            <li id="openLoginModalDrawer" class="nav-item"><a class="nav-link" href="#"><i class="fas fa-user-circle"></i> Login</a></li>
            <li id="openSignUpModalDrawer" class="nav-item"><a class="nav-link" href="#"><i class="fas fa-user-plus"></i> Signup</a></li>
        </ul>
    </div>

    <!-- Overlay for Navbar Drawer -->
    <div class="navbar-overlay d-lg-none" id="navbarOverlay"></div>

    <main class="text-center py-5">
        <div class="quote-panel mb-5">
            <h3 class="h3 fw-bold mb-4">Your Future Starts Here</h3>
            <p class="lead fst-italic mb-5">"Your voice matters. Don't be afraid to open up; our counseling services are
                a safe space where your thoughts and feelings are kept confidential. Remember, seeking help is a sign of
                strength."</p>
        </div>

        <!-- Sign Up & Login Buttons -->


        <!-- SERVICE CARDS SECTION -->
        <section class="mt-5">
            <h4 class="h4 fw-bold text-center mb-5">Why Choose Us?</h4>
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 p-4 shadow">
                            <img alt="Icon representing personalized guidance" class="img-fluid mx-auto mb-4"
                                height="100" src="Photos/MISC/high_five.png" width="100" />
                            <h5 class="h5 fw-bold mb-3">Personalized Guidance</h5>
                            <p>We provide tailored advice to match your unique strengths and aspirations.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 p-4 shadow">
                            <img alt="Icon representing comprehensive resources" class="img-fluid mx-auto mb-4"
                                height="100" src="Photos/MISC/protection.png" width="100" />
                            <h5 class="h5 fw-bold mb-3">Your Privacy Matters</h5>
                            <p>We prioritize your confidentiality. Everything you share with us is kept safe and secure.
                                You can
                                express your thoughts and feelings freely, knowing that your information will not be
                                shared with
                                anyone else.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 p-4 shadow">
                            <img alt="Icon representing experienced counselors" class="img-fluid mx-auto mb-4"
                                height="100" src="Photos/MISC/mental_health.png" width="100" />
                            <h5 class="h5 fw-bold mb-3">Experienced Counselors</h5>
                            <p>Our team consists of seasoned professionals with a wealth of knowledge.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- COUNSELOR BASIC INFO MODAL -->
    <div class="modal fade" id="counselorInfoModal" tabindex="-1" aria-labelledby="counselorInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="counselorInfoModalLabel">Counselor Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="c_info_counselor_id" class="form-label">Counselor ID</label>
                        <input type="text" id="c_info_counselor_id" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="c_info_name" class="form-label">Full Name</label>
                        <input type="text" id="c_info_name" class="form-control" placeholder="Enter your full name">
                    </div>
                    <div class="mb-3">
                        <label for="c_info_degree" class="form-label">Degree</label>
                        <input type="text" id="c_info_degree" class="form-control" placeholder="Enter your degree (e.g., RGC, RPm)">
                    </div>
                    <div class="mb-3">
                        <label for="c_info_email" class="form-label">Email</label>
                        <input type="email" id="c_info_email" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="c_info_contact" class="form-label">Contact Number</label>
                        <input type="text" id="c_info_contact" class="form-control" placeholder="09XXXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label for="c_info_address" class="form-label">Address</label>
                        <textarea id="c_info_address" class="form-control" rows="2" placeholder="Enter your address"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="c_info_birthdate" class="form-label">Birthdate (optional)</label>
                        <input type="date" id="c_info_birthdate" class="form-control">
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label for="c_info_civil_status" class="form-label">Civil Status (optional)</label>
                            <select id="c_info_civil_status" class="form-select">
                                <option value="">Select</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Widowed</option>
                                <option>Legally Separated</option>
                                <option>Annulled</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="c_info_sex" class="form-label">Sex (optional)</label>
                            <select id="c_info_sex" class="form-select">
                                <option value="">Select</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                    </div>
                    <div id="counselorInfoWarning" class="text-danger mt-3 d-none"></div>
                    <div class="d-grid gap-2 mt-3">
                        <button id="counselorInfoSubmitBtn" class="btn btn-primary">Save Information</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGIN MODAL -->
    <!-- LOGIN MODAL -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="loginIdentifierInput" placeholder="User ID or Email" class="form-control">
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" id="passwordInput" placeholder="Enter password" class="form-control">
                        <button id="toggleLoginPassword" class="btn position-absolute end-0 top-0">
                            <i class="fas fa-eye" id="eyeIconLogin"></i>
                        </button>
                    </div>
                    <div id="loginWarning" class="text-danger mb-3 d-none">
                        <p id="loginIdentifierError" class="d-none">*Please enter a valid User ID or Email</p>
                        <p id="loginPasswordError" class="d-none">*Please enter your password</p>
                        <p id="loginInvalidError" class="d-none">*Invalid credentials</p>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <a href="#" id="forgotPasswordLink" class="text-primary text-decoration-none">Forgot Password?</a>
                        <a href="#" id="goToSignUpLink" class="text-primary text-decoration-none">Don't have an account? Sign up</a>
                    </div>
                    <button id="loginBtn" class="btn btn-primary w-100 mb-2">Login</button>
                    <button id="openAdminModalBtn" class="btn btn-outline-dark w-100">Admin Login</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMIN PASSWORD MODAL -->
    <div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-labelledby="adminPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminPasswordModalLabel">Admin Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" id="adminIdentifierInput" placeholder="Admin ID or Email" class="form-control">
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" id="adminPasswordInput" placeholder="Enter admin password" class="form-control">
                        <button id="toggleAdminPassword" class="btn position-absolute end-0 top-0">
                            <i class="fas fa-eye" id="eyeIconAdmin"></i>
                        </button>
                    </div>
                    <div id="adminLoginWarning" class="text-danger mb-3 d-none">
                        <p id="adminIdentifierError" class="d-none">*Please enter your Admin ID or Email</p>
                        <p id="adminPasswordError" class="d-none">*Please enter your password</p>
                        <p id="adminInvalidError" class="d-none">*Invalid Admin credentials</p>
                    </div>
                    <div class="d-grid gap-2">
                        <button id="adminLoginBtn" class="btn btn-primary">Continue to Admin</button>
                        <button id="adminBackToLoginBtn" class="btn btn-outline-secondary">Back to Login</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SIGN-UP MODAL -->
    <div class="modal fade" id="signUpModal" tabindex="-1" aria-labelledby="signUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signUpModalLabel">Sign Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <select id="signUpRole" class="form-select">
                            <option text-muted disabled selected>Select your role</option>
                            <option value="student">Student</option>
                            <option value="counselor">Counselor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input id="signUpUserIdInput" type="text" placeholder="Preferred User ID" class="form-control"
                            maxlength="10" pattern="[0-9]{10}" title="User ID must be exactly 10 digits.">
                    </div>
                    <div class="mb-3">
                        <input id="signUpUsernameInput" type="text" placeholder="Preferred Username" class="form-control">
                    </div>

                    <div class="mb-3">
                        <input id="signUpEmailInput" type="email" placeholder="Email" class="form-control">
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" id="signUpPasswordInput" placeholder="Password" class="form-control">
                        <button id="toggleSignUpPassword" class="btn position-absolute end-0 top-0">
                            <i class="fas fa-eye" id="eyeIconSignUp"></i>
                        </button>
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" id="confirmPasswordInput" placeholder="Confirm Password"
                            class="form-control">
                        <button id="toggleConfirmPassword" class="btn position-absolute end-0 top-0">
                            <i class="fas fa-eye" id="eyeIconConfirm"></i>
                        </button>
                    </div>
                    <div id="signUpWarning" class="text-danger mb-3 d-none">
                        <p id="signUpUserIdError" class="d-none">*Please enter a valid User ID</p>
                        <p id="signUpEmailError" class="d-none">*Please enter a valid email address</p>
                        <p id="signUpUsernameError" class="d-none">*Please enter your username</p>
                        <p id="signUpPasswordError" class="d-none">*Password must be at least 8 characters long</p>
                        <p id="signUpConfirmError" class="d-none">*Passwords do not match</p>
                        <p id="signUpExistingError" class="d-none">*User ID or email already exists</p>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="termsCheckbox" class="form-check-input">
                            <label for="termsCheckbox" class="form-check-label">I agree to the <a href="#"
                                    id="termsLink" class="text-primary text-decoration-none">Terms and
                                    Conditions</a></label>
                        </div>
                    </div>
                    <button id="signUpBtn" class="btn btn-primary w-100 mb-3">Sign Up</button>
                    <div class="text-center">
                        <a href="#" id="backToLoginLink" class="text-primary text-decoration-none">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FORGOT PASSWORD MODAL -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Enter your email address or user ID and we'll send you a code to reset your password.</p>
                    <div class="mb-3">
                        <input type="text" id="forgotPasswordInput" placeholder="Enter your email or user ID" class="form-control">
                    </div>
                    <div id="forgotPasswordWarning" class="text-danger mb-3 d-none">
                        <p id="forgotPasswordInputError" class="d-none">*Please enter a valid email or user ID</p>
                    </div>
                    <button id="resetPasswordBtn" class="btn btn-primary w-100">Send Reset Code</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CODE ENTRY MODAL -->
    <div class="modal fade" id="codeEntryModal" tabindex="-1" aria-labelledby="codeEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeEntryModalLabel">Enter Reset Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-4">A reset token email has been sent to your registered email address. Please enter the code below to proceed with the password reset process.</p>
                    <div id="resetCodeInputs" class="token-inputs mb-3" aria-label="Enter 6-digit reset code">
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 1" required>
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 2" required>
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 3" required>
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 4" required>
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 5" required>
                        <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" class="token-box" aria-label="Code digit 6" required>
                    </div>
                    <div id="resetCodeWarning" class="text-danger mb-3 d-none"></div>
                    <button id="verifyCodeBtn" class="btn btn-primary w-100">Verify Code</button>
                    <div class="text-center mt-3">
                        <p><a href="#" id="resendResetCodeLink" class="text-primary text-decoration-none">Didn't receive the code? Resend</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW PASSWORD MODAL -->
    <div class="modal fade" id="newPasswordModal" tabindex="-1" aria-labelledby="newPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newPasswordModalLabel">Set New Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 position-relative">
                        <input type="password" id="newPasswordInput" placeholder="New Password" class="form-control">
                        <button id="toggleNewPassword" class="btn position-absolute end-0 top-0"><i class="fas fa-eye" id="eyeIconNewPassword"></i></button>
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="password" id="confirmNewPasswordInput" placeholder="Confirm New Password" class="form-control">
                        <button id="toggleConfirmNewPassword" class="btn position-absolute end-0 top-0"><i class="fas fa-eye" id="eyeIconConfirmNewPassword"></i></button>
                    </div>
                    <div id="newPasswordWarning" class="text-danger mb-3 d-none"></div>
                    <button id="setNewPasswordBtn" class="btn btn-success w-100">Set Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- RESEND RESET CODE MODAL -->
    <div class="modal fade" id="resendResetCodeModal" tabindex="-1" aria-labelledby="resendResetCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resendResetCodeModalLabel">Resend Reset Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Enter your registered email address or user ID to resend the password reset code.</p>
                    <div class="mb-3">
                        <label for="resendResetCodeInput" class="form-label">Email or User ID</label>
                        <input type="text" id="resendResetCodeInput" class="form-control" placeholder="Enter your email or user ID">
                    </div>
                    <div id="resendResetCodeWarning" class="text-danger mb-3 d-none">
                        <p id="resendResetCodeInputError" class="d-none">*Please enter a valid email or user ID</p>
                    </div>
                    <div class="d-grid gap-2">
                        <button id="resendResetCodeBtn" class="btn btn-primary">Send Reset Code</button>
                        <button id="resendResetCodeCancelBtn" class="btn btn-outline-secondary">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TERMS AND CONDITIONS MODAL -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-start text-muted">
                        <p>Last updated: OCTOBER 2025</p>

                        <h5 class="fw-bold">1. Acceptance of Terms</h5>
                        <p>By accessing and using the Counselign System, you accept and agree to be
                            bound by these Terms and Conditions.</p>

                        <h5 class="fw-bold">2. Privacy Policy</h5>
                        <p>Your privacy is important to us. All personal information provided will be handled in
                            accordance with our Privacy Policy.</p>

                        <h5 class="fw-bold">3. User Responsibilities</h5>
                        <p>Users are responsible for maintaining the confidentiality of their account information and
                            for all activities that occur under their account.</p>

                        <h5 class="fw-bold">4. Data Protection</h5>
                        <p>We implement appropriate security measures to protect your personal information. However, no
                            method of transmission over the internet is 100% secure.</p>

                        <h5 class="fw-bold">5. Changes to Terms</h5>
                        <p>We reserve the right to modify these terms at any time. Users will be notified of any
                            significant changes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTACT MODAL -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Contact Us</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">Get in Touch</h6>
                        <p class="text-muted">Have questions or need assistance? We're here to help!</p>
                    </div>
                    <div class="mb-3">
                        <label for="contactName" class="form-label">Your Name</label>
                        <input type="text" id="contactName" class="form-control" placeholder="Enter your name">
                    </div>
                    <div class="mb-3">
                        <label for="contactEmail" class="form-label">Email Address</label>
                        <input type="email" id="contactEmail" class="form-control" placeholder="Enter your email">
                    </div>
                    <div class="mb-3">
                        <label for="contactSubject" class="form-label">Subject</label>
                        <input type="text" id="contactSubject" class="form-control" placeholder="Enter subject">
                    </div>
                    <div class="mb-3">
                        <label for="contactMessage" class="form-label">Message</label>
                        <textarea id="contactMessage" class="form-control" rows="4" placeholder="Enter your message"></textarea>
                    </div>
                    <div id="contactWarning" class="text-danger mb-3 d-none">
                        <p id="contactNameError" class="d-none">*Please enter your name</p>
                        <p id="contactEmailError" class="d-none">*Please enter a valid email address</p>
                        <p id="contactSubjectError" class="d-none">*Please enter a subject</p>
                        <p id="contactMessageError" class="d-none">*Please enter your message</p>
                    </div>
                    <button id="contactSubmitBtn" class="btn btn-primary w-100">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo view('auth/verification_prompt'); ?>
    <?php echo view('modals/confirmation_modal'); // Include the new confirmation modal 
    ?>
    <script src="<?= base_url('js/landing.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script>
        window.CSRF_TOKEN_NAME = "<?= csrf_token() ?>";
        window.BASE_URL = "<?= base_url() ?>";
        <?php if (!session()->get('logged_in')): ?>
            if (window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/')) {
                window.location.href = window.BASE_URL;
            }
        <?php endif; ?>
    </script>


</body>

</html>