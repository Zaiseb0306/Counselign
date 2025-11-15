<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="Counselign">
    <title>Report History - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/counselor/history_reports.css') ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                            <ul class="navbar-nav nav-links">
                            <li>
                                    <a href="<?= base_url('counselor/appointments/view-all') ?>"><i class="fas fa-chart-line"></i> Current Reports</a>
                                </li>
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
        <li class="nav-item"><a class="nav-link" href="<?= base_url('counselor/appointments/view-all') ?>"><i class="fas fa-chart-line"></i> Current Reports</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('counselor/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            
        </ul>
    </div>

    <!-- Overlay for Counselor Navbar Drawer -->
    <div class="counselor-navbar-overlay d-lg-none" id="counselorNavbarOverlay"></div>

    <div class="main-content">
        <div class="container report-container">
            <div class="page-header">
                <h2><i class="fas fa-history"></i> Report History</h2>
                <p class="text-muted">View your past appointment reports and statistics</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="month" class="form-control" id="monthFilter" max="<?= date('Y-m') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-filter"></i></span>
                            <select class="form-select" id="reportTypeFilter">
                                <option value="daily">Daily Reports</option>
                                <option value="weekly">Weekly Reports</option>
                                <option value="yearly">Yearly Reports</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" onclick="loadHistoricalReport()">
                            <i class="fas fa-search"></i> View Report
                        </button>
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

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/counselor/history_reports.js') ?>"></script>
    <script src="<?= base_url('js/counselor/counselor_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
</body>

</html>