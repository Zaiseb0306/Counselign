<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="Counselign">
    <title>All Appointments - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin/view_all_appointments.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/header.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-title-container">
                <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="University Logo" class="logo" />
                <h1>Counselign</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-content">
        <div class="container report-container">
            <div class="page-header">
                <h2><i class="fas fa-chart-line"></i> Appointment Reports</h2>
                <p class="text-muted">View and analyze appointment statistics</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <select class="form-select" id="timeRange">
                                <option value="daily">Daily Report</option>
                                <option value="weekly">Weekly Report</option>
                                <option value="monthly">Monthly Report</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= base_url('admin/history-reports') ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-history"></i> View Past Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- History Modal -->
            <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="historyModalLabel">Report History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date Generated</th>
                                            <th>Report Type</th>
                                            <th>Total Appointments</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        <!-- Data will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Summary -->
            <div class="row stats-summary">
                <div class="col-md-2">
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="completedCount">0</h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card approved">
                        <div class="stat-icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="approvedCount">0</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card rejected">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="rejectedCount">0</h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pendingCount">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card cancelled">
                        <div class="stat-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="cancelledCount">0</h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row charts-section">
                <div class="col-md-8">
                    <div class="chart-container trend-chart shadow rounded p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="m-0">
                                <i class="fas fa-chart-line text-primary"></i>
                                <span class="ms-2 fw-bold">Appointment Trends</span>
                            </h4>
                        </div>
                        <div class="chart-wrapper" style="height: 400px;">
                            <canvas id="appointmentTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-container pie-chart">
                        <h4><i class="fas fa-chart-pie"></i> Status Distribution</h4>
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="appointment-container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-center fw-bold" style="color: #0d6efd;">All Appointment Lists</h2>
                </div>
            </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
            <li class="nav-item col-md-2" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all"
                        type="button">
                        <i class="fas fa-list-alt"></i>
                        <span class="tab-text">All Appointments</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="followup-tab" data-bs-toggle="tab" data-bs-target="#followup"
                    type="button">
                    <i class="fas fa-calendar-plus"></i>
                    <span class="tab-text">Follow-up</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved"
                    type="button">
                    <i class="fas fa-check-circle"></i>
                    <span class="tab-text">Approved</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected"
                    type="button">
                    <i class="fas fa-times-circle"></i>
                    <span class="tab-text">Rejected</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed"
                    type="button">
                    <i class="fas fa-check-double"></i>
                    <span class="tab-text">Completed</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled"
                    type="button">
                    <i class="fas fa-ban"></i>
                    <span class="tab-text">Cancelled</span>
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
            <div class="col-md-4">
                <div class="btn-group w-100">
                    <button class="btn btn-primary" id="exportPDF">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </button>
                    <button class="btn btn-success" id="exportExcel">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                </div>
            </div>
        </div>

            <!-- Tab Content with Scrollable Container -->
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
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="table table-hover mb-0" style="min-width: 1100px;">
                            <thead class="table-light sticky-top">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Method Type</th>
                                <th>Consultation Type</th>
                                <th>Session Type</th>
                                <th>Purpose</th>
                                <th>Counselor</th>
                                <th>Status</th>
                                <th style="width: 60%;">Reason for Status</th>
                            </tr>
                        </thead>
                        <tbody id="allAppointmentsTable">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approved Appointments Tab -->
            <div class="tab-pane fade" id="approved" role="tabpanel">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="table table-hover mb-0" style="min-width: 1100px;">
                            <thead class="table-light sticky-top">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Method Type</th>
                                <th>Consultation Type</th>
                                <th>Session Type</th>
                                <th>Purpose</th>
                                <th>Counselor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="approvedAppointmentsTable">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rejected Appointments Tab -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="table table-hover mb-0" style="min-width: 1100px;">
                            <thead class="table-light sticky-top">
                            <tr>
                                <th>User ID</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Consultation Type</th>
                                <th>Session Type</th>
                                <th>Purpose</th>
                                <th>Counselor</th>
                                <th>Status</th>
                                <th style="width: 60%;">Reason for Status</th>
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
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                        <table class="table table-hover mb-0" style="min-width: 1100px;">
                            <thead class="table-light sticky-top">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Method Type</th>
                                <th>Consultation Type</th>
                                <th>Session Type</th>
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
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                    <table class="table table-hover mb-0" style="min-width: 1100px;">
                        <thead class="table-light sticky-top">
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Method Type</th>
                            <th>Consultation Type</th>
                            <th>Session Type</th>
                            <th>Purpose</th>
                            <th>Counselor</th>
                            <th>Status</th>
                            <th style="width: 60%;">Reason for Status</th>
                        </tr>
            <div class="tab-pane fade" id="followup" role="tabpanel">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                    <table class="table table-hover mb-0" style="min-width: 1100px;">
                        <thead class="table-light sticky-top">
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Method Type</th>
                            <th>Consultation Type</th>
                            <th>Session Type</th>
                            <th>Purpose</th>
                            <th>Counselor</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="followUpAppointmentsTable">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
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

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/admin/view_all_appointments.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script>
        function confirmLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = "<?= base_url('auth/logout') ?>";
            }
        }
    </script>
</body>

</html>