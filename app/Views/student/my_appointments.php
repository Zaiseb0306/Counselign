<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="University Guidance Counseling System">
    <title>My Appointments - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign_logo.png') ?>" sizes="16x16 32x32" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('Photos/counselign_logo.png') ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/student/my_appointments.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            <li><a href="<?= base_url('student/schedule-appointment') ?>"><i class="fas fa-plus-circle"></i> Schedule an Appointment</a></li>
                            <li><a href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
                            
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Navbar Drawer for Small Screens -->
    <div class="navbar-drawer d-lg-none" id="navbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Student Menu</h5>
            <button class="btn-close btn-close-white" id="navbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/schedule-appointment') ?>"><i class="fas fa-plus-circle"></i> Schedule an Appointment</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            
        </ul>
    </div>

    <!-- Overlay for Navbar Drawer -->
    <div class="navbar-overlay d-lg-none" id="navbarOverlay"></div>

    <div class="main-content">
        <div class="container appointment-container">
            <div class="page-header">
                <h2><i class="fas fa-list-alt"></i> My Appointments</h2>
                <p class="text-muted">View and manage your counseling appointments</p>
            </div>

            <!-- Approved Appointments Section -->
            <section class="approved-appointments-section mb-4">
                <div class="section-title"><i class="fas fa-check-circle"></i> Approved Appointment</div>
                <div id="approvedAppointmentsContainer">
                    <!-- JS will render approved appointment details here -->
                </div>
            </section>

            <!-- Pending Appointments Section -->
            <section class="pending-appointments-section mb-4">
                <div class="section-title"><i class="fas fa-hourglass-half"></i> Pending Appointment</div>
                <div id="pendingAppointmentsFormsContainer">
                    <!-- JS will render pending appointment forms here -->
                </div>
            </section>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        All Appointments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button">
                        Rejected
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button">
                        Completed
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button">
                        Cancelled
                    </button>
                </li>
            </ul>

            <!-- Filter Options -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search appointments...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="month" class="form-control" id="dateFilter">
                    </div>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="appointmentTabContent">
                <!-- Loading Spinner -->
                <div class="loading-spinner" style="display: none;">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State Message -->
                <div class="empty-state alert alert-info text-center" style="display: none;">
                    <i class="fas fa-info-circle me-2"></i>
                    No appointments found.
                </div>

                <!-- All Appointments Tab -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Consultation Type</th>
                                    <th>Method Type</th>
                                    <th>Purpose</th>
                                    <th>Counselor</th>
                                    <th>Status</th>
                                    <th>Reason for Cancellation or Rejection</th>
                                </tr>
                            </thead>
                            <tbody id="allAppointmentsTable">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Rejected Appointments Tab -->
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Consultation Type</th>
                                    <th>Method Type</th>
                                    <th>Purpose</th>
                                    <th>Counselor</th>
                                    <th>Status</th>
                                    <th>Reason for Rejection</th>
                                </tr>
                            </thead>
                            <tbody id="rejectedAppointmentsTable">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Completed Appointments Tab -->
                <div class="tab-pane fade" id="completed" role="tabpanel">
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Consultation Type</th>
                                    <th>Method Type</th>
                                    <th>Purpose</th>
                                    <th>Counselor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="completedAppointmentsTable">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cancelled Appointments Tab -->
                <div class="tab-pane fade" id="cancelled" role="tabpanel">
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Consultation Type</th>
                                    <th>Method Type</th>
                                    <th>Purpose</th>
                                    <th>Counselor</th>
                                    <th>Status</th>
                                    <th>Reason for Cancellation</th>
                                </tr>
                            </thead>
                            <tbody id="cancelledAppointmentsTable">
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Counselors' Schedules Toggle Button -->
    <button class="calendar-toggle-btn" id="counselorsCalendarToggleBtn" title="View Counselors' Schedules">
        <i class="fas fa-user-md"></i>
        <span>Counselors' Schedules</span>
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Counselors' Schedules Calendar Drawer -->
    <div class="calendar-drawer" id="counselorsCalendarDrawer">
        <div class="calendar-drawer-header">
            <h3 class="calendar-drawer-title">
                <i class="fas fa-user-md me-2"></i>
                Counselors' Schedules
            </h3>
            <button class="calendar-close-btn" id="counselorsCalendarCloseBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="calendar-drawer-content">
            <div class="calendar-container">
                <div class="calendar-header">
                    <button id="counselorsPrevMonth" class="calendar-nav-btn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <h4 id="counselorsCurrentMonth" class="calendar-month"></h4>
                    <button id="counselorsNextMonth" class="calendar-nav-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-grid" id="counselorsCalendarGrid">
                    <!-- Calendar will be dynamically generated here -->
                </div>
            </div>
            
            <!-- Counselor Schedules Display Section -->
            <div class="counselor-schedules-section">
                <div class="section-header">
                    <h4><i class="fas fa-user-md me-2"></i>Counselor Schedules</h4>
                    <p class="text-muted">View all counselors and their available time slots by day</p>
                </div>
                
                <div class="schedules-container" id="counselorSchedulesContainer">
                    <div class="loading-schedules">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading counselor schedules...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Counselors Calendar Drawer Overlay -->
    <div class="calendar-overlay" id="counselorsCalendarOverlay"></div>


    <!-- Edit Appointment Modal -->
    <div class="modal fade" id="editAppointmentModal" tabindex="-1" aria-labelledby="editAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAppointmentModalLabel">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAppointmentForm">
                        <input type="hidden" id="editAppointmentId">
                        <div class="mb-3">
                            <label for="editDate" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTime" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                            <select class="form-select" id="editTime" required>
                                <option value="">Select a time slot</option>
                            </select>
                            <small class="form-text text-muted">Time slots will be filtered based on counselor availability</small>
                        </div>
                        <div class="mb-3">
                            <label for="editConsultationType" class="form-label">Consultation Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="editConsultationType" required>
                                <option value="">Select consultation type</option>
                                <option value="Individual Consultation">Individual Consultation</option>
                                <option value="Group Consultation">Group Consultation</option>
                            </select>
                            <small id="editConsultationTypeHelp" class="form-text text-muted"></small>
                        </div>
                        <div class="mb-3">
                            <label for="editMethodType" class="form-label">Method Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="editMethodType" required>
                                <option value="">Select a method type</option>
                                <option value="In-person">In-person</option>
                                <option value="Online (Video)">Online (Video)</option>
                                <option value="Online (Audio only)">Online (Audio only)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPurpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                            <select class="form-select" id="editPurpose" required>
                                <option value="">Select purpose...</option>
                                <option value="Counseling">Counseling</option>
                                <option value="Psycho-Social Support">Psycho-Social Support</option>
                                <option value="Initial Interview">Initial Interview</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCounselorPreference" class="form-label">Counselor Preference <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCounselorPreference" required>
                                <option value="">Select a counselor</option>
                                <option value="No preference">No preference</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="editDescription" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveEditBtn">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Appointment Modal -->
    <div class="modal fade" id="cancelAppointmentModal" tabindex="-1" aria-labelledby="cancelAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelAppointmentModalLabel">Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancelAppointmentForm">
                        <input type="hidden" id="cancelAppointmentId">
                        <div class="mb-3">
                            <label for="cancelReason" class="form-label">Reason for Cancellation</label>
                            <textarea class="form-control" id="cancelReason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirm Cancellation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Changes Confirmation Modal -->
    <div class="modal fade" id="saveChangesModal" tabindex="-1" aria-labelledby="saveChangesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="saveChangesModalLabel">
                        <i class="fas fa-edit me-2"></i>Confirm Save Changes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to save the changes to this appointment?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSaveChangesBtn">
                        <i class="fas fa-check me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancellation Reason Modal -->
    <div class="modal fade" id="cancellationReasonModal" tabindex="-1" aria-labelledby="cancellationReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancellationReasonModalLabel">
                        <i class="fas fa-times-circle me-2"></i>Cancellation Reason
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="cancellationReasonForm">
                        <div class="mb-3">
                            <label for="cancellationReason" class="form-label fw-bold">Please provide a reason for cancelling this appointment:</label>
                            <textarea class="form-control" id="cancellationReason" rows="4" placeholder="Enter the reason for cancellation here..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancellationBtn">
                        <i class="fas fa-check me-1"></i>Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">
                        <i class="fas fa-trash me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this appointment?
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-check me-1"></i>Delete
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
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Success
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="successModalMessage">Operation completed successfully.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>OK
                    </button>
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

    <!-- Scripts -->
    <?php echo view('modals/student_dashboard_modals'); ?>
    <script src="<?= base_url('js/modals/student_dashboard_modals.js') ?>"></script>
    <script src="<?= base_url('js/utils/timeFormatter.js') ?>"></script>
    <script src="<?= base_url('js/student/my_appointments.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script>
        (function() {
            const toggleBtn = document.getElementById('counselorsCalendarToggleBtn');
            const drawer = document.getElementById('counselorsCalendarDrawer');
            const overlay = document.getElementById('counselorsCalendarOverlay');
            const closeBtn = document.getElementById('counselorsCalendarCloseBtn');
            function openDrawer(){ if (drawer&&overlay&&toggleBtn){ drawer.classList.add('open'); overlay.classList.add('active'); toggleBtn.classList.add('active'); document.body.style.overflow='hidden'; } }
            function closeDrawer(){ if (drawer&&overlay&&toggleBtn){ drawer.classList.remove('open'); overlay.classList.remove('active'); toggleBtn.classList.remove('active'); document.body.style.overflow=''; } }
            if (toggleBtn) toggleBtn.addEventListener('click', openDrawer);
            if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);
        })();
    </script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script src="<?= base_url('js/student/student_header_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        // Debug QRCode library loading
        window.addEventListener('load', function() {
            console.log('QRCode library loaded:', typeof qrcode !== 'undefined');
            if (typeof qrcode !== 'undefined') {
                console.log('QRCode library available');
            }
        });
    </script>
    
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    
</body>

</html>