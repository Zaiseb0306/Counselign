<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Appointments - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin/scheduled_appointments.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/header.css') ?>">


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
                            <ul class="navbar-nav nav-links">
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/appointments') ?>"><i class="fas fa-list-alt"></i> Appointments</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
                                <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/appointments') ?>"><i class="fas fa-list-alt"></i> Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>

    <!-- Toast notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="statusToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <small id="toastTime">Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Status updated successfully.
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <main class="bg-light p-4">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h2 fw-bold text-primary mb-0">Consultation Schedule Queries</h2>
                <div class="search-container">
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="appointmentsSearchInput" placeholder="Search appointments..." aria-label="Search appointments">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="csq-layout">
                <div class="csq-left">
                    <div class="csq-card">
                        <!-- Loading Indicator -->
                        <div id="loading-indicator" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading appointments...</p>
                        </div>

                        <!-- Empty Message -->
                        <div id="empty-message" class="alert alert-info text-center d-none">
                            <i class="fas fa-info-circle me-2"></i> No scheduled appointments found.
                        </div>

                        <div class="table-bordered csq-table-wrap" id="appointments-table-container">
                            <!-- Table Section -->
                            <table class="table csq-table" id="appointments-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Student ID</th>
                                        <th scope="col">Student Name</th>
                                        <th scope="col">Appointed Date</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Consultation Type</th>
                                        <th scope="col">Purpose</th>
                                        <th scope="col">Appointed Counselor</th>
                                        <th scope="col" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="appointments-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <aside class="csq-right">
                    <div class="csq-card csq-sidebar-container">
                        <div class="sidebar-card mini-calendar-card">
                            <div class="mini-cal-header">
                                <button class="mini-cal-btn" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                                <div class="mini-cal-title" id="monthYear"></div>
                                <button class="mini-cal-btn" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="mini-cal-week">
                                <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                            </div>
                            <div class="mini-cal-days" id="calendarDays"></div>
                            <div class="mini-cal-legend">
                                <div class="legend-item"><span class="legend-dot has-appointment"></span><span>Has Appointments</span></div>
                                <div class="legend-item"><span class="legend-dot today"></span><span>Today</span></div>
                            </div>
                        </div>
                    </div>
                </aside>
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

    

    <!-- Cancellation Reason Modal -->
    <div class="modal fade" id="cancellationReasonModal" tabindex="-1" aria-labelledby="cancellationReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="cancellationReasonModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Cancellation Reason
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancellationReasonForm">
                        <div class="mb-3">
                            <label for="cancellationReason" class="form-label fw-bold">Please provide a reason for cancelling this appointment:</label>
                            <textarea class="form-control" id="cancellationReason" rows="4"
                                placeholder="Enter the reason for cancellation here..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" id="confirmCancellationBtn">
                        <i class="fas fa-check me-1"></i>Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/admin/scheduled_appointments.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    
</body>

</html>