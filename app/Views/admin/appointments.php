<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - Counselign</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin/appointments.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/appointments.mobile.css') ?>">
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
                            <ul class="navbar-nav nav-links">
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/appointments/scheduled') ?>"><i class="fas fa-calendar-alt"></i>Scheduled Appointments</a></li>
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/appointments/scheduled') ?>"><i class="fas fa-calendar-alt"></i>Scheduled Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>

    <!-- Toast container -->
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
    <main>
        <div class="container-fluid px-4">
            <!-- Dashboard Header Section -->
            <div class="dashboard-header my-4">
                <div class="row">
                    <div class="col-md-8">
                        <h2 class="page-title"><i class="fas fa-boxes me-2"></i>Appointments Breakdown</h2>
                    </div>
                </div>
            </div>

            <!-- Status Categories Cards -->
            <div class="row status-cards mb-4">
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="status-card bg-white rounded shadow-sm">
                        <div class="status-card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="status-title">Pending</h6>
                                    <h3 class="status-count" id="pendingCount">-</h3>
                                </div>
                                <div class="status-icon bg-warning text-white rounded-circle">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="status-card bg-white rounded shadow-sm">
                        <div class="status-card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="status-title">Approved</h6>
                                    <h3 class="status-count" id="approvedCount">-</h3>
                                </div>
                                <div class="status-icon bg-success text-white rounded-circle">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="status-card bg-white rounded shadow-sm">
                        <div class="status-card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="status-title">Completed</h6>
                                    <h3 class="status-count" id="completedCount">-</h3>
                                </div>
                                <div class="status-icon bg-primary text-white rounded-circle">
                                    <i class="fas fa-check-double"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="status-card bg-white rounded shadow-sm">
                        <div class="status-card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="status-title">Rejected</h6>
                                    <h3 class="status-count" id="rejectedCount">-</h3>
                                </div>
                                <div class="status-icon bg-danger text-white rounded-circle">
                                    <i class="fas fa-times"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="status-card bg-white rounded shadow-sm">
                        <div class="status-card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="status-title">Cancelled</h6>
                                    <h3 class="status-count" id="cancelledCount">-</h3>
                                </div>
                                <div class="status-icon bg-secondary text-white rounded-circle">
                                    <i class="fas fa-ban"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter and Appointments Container -->
            <div class="appointments-container bg-white rounded shadow-sm">
                <div class="appointments-header">
                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Appointments List</h5>
                    <div class="filter-controls d-flex align-items-center">
                        <label for="dateRangeFilter" class="me-2">Date:</label>
                        <select class="form-select" id="dateRangeFilter">
                            <option value="all">All Dates</option>
                            <option value="today">Today</option>
                            <option value="thisWeek">This Week</option>
                            <option value="nextWeek">Next Week</option>
                            <option value="nextMonth">Next Month</option>
                            <option value="past">Past Appointments</option>
                        </select>

                        <label for="statusFilter" class="me-2">Status:</label>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Loading indicator -->
                <div id="loadingIndicator" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading appointments...</p>
                </div>

                <!-- No appointments message -->
                <div id="noAppointmentsMessage" class="text-center py-5 d-none">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <p>No appointments found.</p>
                </div>

                <!-- Appointment List -->
                <div id="appointmentsList" class="appointments-list d-none">
                    <!-- Appointments will be dynamically added here -->
                </div>
            </div>
        </div>
    </main>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content appointment-modal-content">
                <div class="modal-header appointment-modal-header">
                    <h5 class="modal-title" id="appointmentDetailsModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Appointment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body appointment-modal-body">
                    <div class="appointment-info-grid">
                        <div class="info-section">
                            <div class="info-item">
                                <i class="fas fa-user-circle info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Student ID</span>
                                    <span class="info-value" id="modalStudentId"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Student Name</span>
                                    <span class="info-value" id="modalStudentName"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Email</span>
                                    <span class="info-value" id="modalEmail"></span>
                                </div>
                            </div>
                        </div>
                        <div class="info-section">
                            <div class="info-item">
                                <i class="fas fa-calendar-alt info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Date</span>
                                    <span class="info-value" id="modalDate"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Time</span>
                                    <span class="info-value" id="modalTime"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-check-circle info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Status</span>
                                    <span id="modalStatus" class="badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="appointment-info-grid mt-3">
                        <div class="info-section">
                            <div class="info-item">
                                <i class="fas fa-users info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Consultation Type</span>
                                    <span class="info-value" id="modalConsultationType"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-laptop info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Method Type</span>
                                    <span class="info-value" id="modalMethodType"></span>
                                </div>
                            </div>
                        </div>
                        <div class="info-section">
                            <div class="info-item">
                                <i class="fas fa-bullseye info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Purpose</span>
                                    <span class="info-value" id="modalPurpose"></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user-md info-icon"></i>
                                <div class="info-content">
                                    <span class="info-label">Counselor Preference</span>
                                    <span class="info-value" id="modalCounselorPreference"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="description-section mt-3">
                        <div class="description-header">
                            <i class="fas fa-file-alt me-2"></i>
                            <span>Description</span>
                        </div>
                        <div id="modalDescription" class="description-content"></div>
                    </div>
                    <div id="modalReasonContainer" class="description-section mt-3" style="display: none;">
                        <div class="description-header">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span>Reason</span>
                        </div>
                        <div id="modalReason" class="description-content"></div>
                    </div>
                    <div class="timestamp-info mt-3">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Created: <span id="modalCreated"></span>
                        </small>
                        <span id="modalUpdated" style="display: none;"></span>
                    </div>
                    <input type="hidden" id="modalAppointmentId">
                </div>
                <div class="modal-footer appointment-modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-labelledby="rejectionReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectionReasonModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Rejection Reason
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectionReasonForm">
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label fw-bold">Please provide a reason for rejecting this appointment:</label>
                            <textarea class="form-control" id="rejectionReason" rows="4"
                                placeholder="Enter the reason for rejection here..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-danger" id="confirmRejectionBtn">
                        <i class="fas fa-check me-1"></i>Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    <!-- Content will be dynamically inserted -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalTitle">Success</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="successModalBody">
                    <!-- Content will be dynamically inserted -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/admin/appointments.js') ?>" defer></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
</body>

</html>