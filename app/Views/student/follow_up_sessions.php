<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title>Follow-up Sessions - Student - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/student/follow_up_sessions.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/student/header.css') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <div class="logo-title-container">
                        <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="University Logo" class="logo" />
                        <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    </div>

                    <button class="custom-navbar-toggler d-lg-none align-items-center" type="button" id="navbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-gear"></i></span>
                    </button>

                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <ul class="navbar-nav nav-links ms-auto">
                            <li>
                                <a href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a>

                            </li>
                        </ul>

                    </nav>
                </div>
            </div>
        </div>
    </header>


    <div class="navbar-drawer" id="navbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Student Menu</h5>
            <button class="btn-close btn-close-white" id="navbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>

        </ul>
    </div>

    <div class="navbar-overlay" id="navbarOverlay"></div>


    <main>
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-12">
                    <div class="follow-up-container">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-calendar-check me-2"></i>
                                Follow-up Sessions - Student View
                            </h2>
                            <p class="section-subtitle">View your completed appointments and their follow-up sessions</p>
                        </div>

                        <!-- Pending Follow-up Appointments Section -->
                        <div class="pending-follow-up-section" id="pendingFollowUpSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="subsection-title mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Appointment with a Pending Follow-up
                                </h3>
                            </div>
                            <div id="pendingFollowUpContainer" class="appointments-grid">
                                <!-- Pending follow-up appointments will be loaded here -->
                            </div>
                        </div>

                        <!-- Completed Appointments Section -->
                        <div class="completed-appointments-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="subsection-title mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    My Completed Appointments
                                </h3>
                                <div class="search-container">
                                    <div class="input-group" style="max-width: 300px;">
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
                                <p>No completed appointments found. Complete some appointments to view follow-up sessions.</p>
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

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <!-- Follow-up Sessions Modal (Read-Only) -->
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    <div class="alert alert-danger alert-dismissible fade" id="errorAlert" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <span id="errorMessage"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Success Alert -->
    <div class="alert alert-success alert-dismissible fade" id="successAlert" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
        <i class="fas fa-check-circle me-2"></i>
        <span id="successMessage"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/student/follow_up_sessions.js') ?>"></script>
    <script src="<?= base_url('js/student/student_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script>
        // Set BASE_URL for JavaScript
        window.BASE_URL = '<?= base_url() ?>';
    </script>
    <script src="<?= base_url('js/student/student_header_drawer.js') ?>"></script>
</body>

</html>