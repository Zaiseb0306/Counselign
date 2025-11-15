<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title>Follow-up Appointments - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/counselor/follow_up.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/counselor/header.css') ?>">
</head>

<body>
    <header class="counselor-header text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="UGC Logo" class="logo" />
                    <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>

                    <button class="counselor-navbar-toggler d-lg-none align-items-center" type="button" id="counselorNavbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span>
                    </button>

                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <ul class="navbar-nav nav-links ms-auto">
                                <li>
                                    <a href="<?= base_url('counselor/dashboard') ?>"><i class="fas fa-home"></i> Home</a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Counselor Navbar Drawer for Small Screens -->
    <div class="counselor-navbar-drawer d-lg-none" id="counselorNavbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Counselor Menu</h5>
            <button class="btn-close btn-close-white" id="counselorNavbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('counselor/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <!-- Overlay for Counselor Navbar Drawer -->
    <div class="counselor-navbar-overlay d-lg-none" id="counselorNavbarOverlay"></div>


    <main>
        <div class="container-fluid px-2">
            <div class="row">
                <div class="col-12">
                    <div class="follow-up-container">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-calendar-check me-2"></i>
                                For follow-up Session
                            </h2>
                            <p class="section-subtitle">Manage follow-up sessions for completed appointments</p>
                        </div>

                        <!-- Completed Appointments Section -->
                        <div class="completed-appointments-section">
                            <div class="section-header-bar">
                                <div class="section-title-wrapper">
                                    <h3 class="subsection-title mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Completed Appointments
                                    </h3>
                                    <p class="section-description">View and manage follow-up sessions for completed appointments</p>
                                </div>
                                <div class="search-container">
                                    <div class="input-group search-wrapper">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Search appointments...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="completedAppointmentsContainer" class="appointments-grid">
                                <!-- Completed appointments will be loaded here -->
                            </div>
                            <div id="noCompletedAppointments" class="no-data-message" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <p>No completed appointments found. Complete some appointments to create follow-up sessions.</p>
                            </div>
                            <div id="noSearchResults" class="no-data-message" style="display: none;">
                                <i class="fas fa-search"></i>
                                <p>No appointments found matching your search criteria.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Follow-up Sessions Modal -->
    <div class="modal fade" id="followUpSessionsModal" tabindex="-1" aria-labelledby="followUpSessionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="followUpSessionsModalLabel">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Follow-up Sessions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="followUpSessionsContainer">
                        <!-- Follow-up sessions will be loaded here -->
                    </div>
                    <div id="noFollowUpSessions" class="no-data-message" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <p>No follow-up sessions found for this appointment.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="createNewFollowUpBtn">
                        <i class="fas fa-plus me-2"></i>
                        Create New Follow-up
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Follow-up Modal -->
    <div class="modal fade" id="cancelFollowUpModal" tabindex="-1" aria-labelledby="cancelFollowUpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelFollowUpModalLabel">
                        <i class="fas fa-ban me-2"></i>
                        Cancel Follow-up Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelFollowUpForm">
                        <input type="hidden" id="cancelFollowUpId" name="id">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        <div class="mb-6">
                            <label for="cancelReason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancelReason" name="reason" rows="3" placeholder="Provide a clear reason for cancelling this follow-up" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelFollowUpBtn">
                        <i class="fas fa-ban me-2"></i>
                        Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Follow-up Modal -->
    <div class="modal fade" id="createFollowUpModal" tabindex="-1" aria-labelledby="createFollowUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFollowUpModalLabel">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Create Follow-up Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createFollowUpForm">
                        <input type="hidden" id="parentAppointmentId" name="parent_appointment_id">
                        <input type="hidden" id="studentId" name="student_id">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="preferredDate" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="preferredDate" name="preferred_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="preferredTime" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                                    <select class="form-control" id="preferredTime" name="preferred_time" required>
                                        <option value="">Select a time</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="consultationType" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="consultationType" name="consultation_type" required>
                                <option value="">Select consultation type</option>
                                <option value="Individual Counseling">Individual Counseling</option>
                                <option value="Career Guidance">Career Guidance</option>
                                <option value="Academic Counseling">Academic Counseling</option>
                                <option value="Personal Development">Personal Development</option>
                                <option value="Crisis Intervention">Crisis Intervention</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of the follow-up session"></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="reason" class="form-label">Reason for Follow-up</label>
                            <textarea class="form-control" id="reason" name="reason" rows="2" placeholder="Reason for scheduling this follow-up session"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFollowUpBtn">
                        <i class="fas fa-save me-2"></i>
                        Create Follow-up
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Follow-up Modal -->
    <div class="modal fade" id="editFollowUpModal" tabindex="-1" aria-labelledby="editFollowUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFollowUpModalLabel">
                        <i class="fas fa-edit me-2"></i>
                        Edit Follow-up Session
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editFollowUpForm">
                        <input type="hidden" id="editFollowUpId" name="id">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editPreferredDate" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="editPreferredDate" name="preferred_date" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editPreferredTime" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                                    <select class="form-control" id="editPreferredTime" name="preferred_time" required disabled style="background-color: #e9ecef; cursor: not-allowed;">
                                        <option value="">Select a time</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="editConsultationType" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="editConsultationType" name="consultation_type" required>
                                <option value="">Select consultation type</option>
                                <option value="Individual Counseling">Individual Counseling</option>
                                <option value="Career Guidance">Career Guidance</option>
                                <option value="Academic Counseling">Academic Counseling</option>
                                <option value="Personal Development">Personal Development</option>
                                <option value="Crisis Intervention">Crisis Intervention</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" placeholder="Brief description of the follow-up session"></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="editReason" class="form-label">Reason for Follow-up</label>
                            <textarea class="form-control" id="editReason" name="reason" rows="2" placeholder="Reason for scheduling this follow-up session"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="updateFollowUpBtn">
                        <i class="fas fa-save me-2"></i>
                        Update Follow-up
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalTitle">
                        <i class="fas fa-check-circle me-2"></i>
                        Success
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="successModalBody">
                    <!-- Success message will be displayed here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalTitle">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="errorModalBody">
                    <!-- Error message will be displayed here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
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

    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/counselor/follow_up.js') ?>" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/counselor/counselor_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script>
        // Wire confirm cancel button
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('confirmCancelFollowUpBtn');
            if (btn && typeof confirmCancelFollowUp === 'function') {
                btn.addEventListener('click', confirmCancelFollowUp);
            }
        });
    </script>
</body>

</html>