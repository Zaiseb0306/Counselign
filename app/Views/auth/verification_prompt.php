<!-- Link to external CSS -->
<link rel="stylesheet" href="<?= base_url('css/auth/verification_prompt.css') ?>">

<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verificationModalLabel">Account Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p id="verificationMessage" class="mb-4">A verification email has been sent to your registered email address. Please enter the token below to verify your account.</p>
                <form id="verificationForm" class="mb-3">
                    <div id="tokenInputs" class="token-inputs mb-3" aria-label="Enter 6-character verification token">
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 1" required>
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 2" required>
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 3" required>
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 4" required>
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 5" required>
                        <input type="text" inputmode="latin" pattern="[A-Z0-9]" maxlength="1" class="token-box" aria-label="Token character 6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Verify Account</button>
                </form>
                <p><a href="#" id="resendVerificationEmail" class="text-primary text-decoration-none">Resend Verification Email</a></p>
                
                <!-- Success message with countdown and controls -->
                <div id="verificationSuccessMessage" class="d-none">
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="successText">Account verified successfully!</span>
                    </div>
                    <div class="mb-3">
                        <p class="mb-2">You will be automatically logged in and redirected to your dashboard in:</p>
                        <div class="countdown-timer">
                            <span id="countdownNumber" class="badge bg-primary fs-4">10</span>
                            <span class="ms-2">seconds</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button id="goToDashboardBtn" class="btn btn-success">
                            <i class="fas fa-tachometer-alt me-1"></i>Go to Dashboard Now
                        </button>
                        <button id="stayOnLandingBtn" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i>Stay on Landing Page
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RESEND VERIFICATION MODAL -->
<div class="modal fade" id="resendVerificationModal" tabindex="-1" aria-labelledby="resendVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resendVerificationModalLabel">Resend Verification Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Enter your registered email address or user ID to resend the verification email.</p>
                <div class="mb-3">
                    <label for="resendVerificationInput" class="form-label">Email or User ID</label>
                    <input type="text" id="resendVerificationInput" class="form-control" placeholder="Enter your email or user ID">
                </div>
                <div id="resendVerificationWarning" class="text-danger mb-3 d-none">
                    <p id="resendVerificationInputError" class="d-none">*Please enter a valid email or user ID</p>
                </div>
                <div class="d-grid gap-2">
                    <button id="resendVerificationBtn" class="btn btn-primary">Send Verification Email</button>
                    <button id="resendVerificationCancelBtn" class="btn btn-outline-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link to external JavaScript -->
<script src="<?= base_url('js/auth/verification_prompt.js') ?>"></script>
<script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
