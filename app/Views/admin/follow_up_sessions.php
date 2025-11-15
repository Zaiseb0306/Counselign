<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title>Follow-up Sessions - Admin - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/admin/follow_up_sessions.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/header.css') ?>">
</head>

<body>
    <header class="admin-header text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="UGC Logo" class="logo" />
                    <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>

                    <button class="admin-navbar-toggler d-lg-none align-items-center" type="button" id="adminNavbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span>
                    </button>

                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <ul class="navbar-nav nav-links ms-auto">
                                <li>
                                    <a href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Navbar Drawer for Small Screens -->
    <div class="admin-navbar-drawer d-lg-none" id="adminNavbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Admin Menu</h5>
            <button class="btn-close btn-close-white" id="adminNavbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>


    <main>
        <div class="container-fluid px-2">
            <div class="row">
                <div class="col-12">
                    <div class="follow-up-container">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-calendar-check me-2"></i>
                                Follow-up Sessions - Admin View
                            </h2>
                            <p class="section-subtitle">View all completed appointments and their follow-up sessions</p>
                        </div>

                            <!-- Completed Appointments Section -->
                            <div class="completed-appointments-section">
                                <div class="section-header-bar">
                                    <div class="section-title-wrapper">
                                        <h3 class="subsection-title mb-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            All Completed Appointments
                                        </h3>
                                        <p class="section-description">View and manage follow-up sessions for all completed appointments</p>
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
                                    <p>No completed appointments found.</p>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    <script src="<?= base_url('js/admin/admin_dashboard.js') ?>"></script>
    <script src="<?= base_url('js/admin/follow_up_sessions.js') ?>" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
</body>

</html>
