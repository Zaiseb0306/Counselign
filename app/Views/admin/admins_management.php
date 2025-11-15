<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services" />
    <meta name="keywords" content="counseling, guidance, university, support" />
    <title>Admin's Management - Counselign</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link rel="stylesheet" href="<?= base_url('css/admin/admins_management.css') ?>">
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
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/counselor-info') ?>"><i class="fa fa-user-tie"></i> Counselor Accounts</a></li>
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/view-users') ?>"><i class="fa fa-users"></i> Student Accounts</a></li>
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/counselor-info') ?>"><i class="fa fa-user-tie"></i> Counselor Accounts</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/view-users') ?>"><i class="fa fa-users"></i> Student Accounts</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="profile-sidebar" hidden>
            <div class="admin-profile-container">
                <img id="profile-img" src="<?= base_url('Photos/profile.png') ?>" alt="Admin Avatar" class="admin-avatar profile-img" />
                <h2 class="admin-title">Admin</h2>
            </div>
            <div class="admin-menu">
                <a href="<?= base_url('admin/counselor-info') ?>" class="admin-menu-item">Counselor Accounts</a>
                <a href="<?= base_url('admin/view-users') ?>" class="admin-menu-item">Student Accounts </a>
            </div>
        </div>

        <div class="content-area">
            <div class="schedule-header">
                <h2 class="content-title">Counselor Weekly Schedule</h2>
                <button class="refresh-schedule-btn" id="refreshScheduleBtn" type="button" aria-label="Refresh schedule">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <div class="schedule-grid-container">
                <div class="schedule-grid">
                    <!-- Monday -->
                    <div class="day-column" data-day="Monday">
                        <div class="day-header">
                            <i class="fas fa-calendar-day"></i>
                            <span>Monday</span>
                        </div>
                        <div class="counselor-cards-container" id="monday-schedule">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading schedule...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tuesday -->
                    <div class="day-column" data-day="Tuesday">
                        <div class="day-header">
                            <i class="fas fa-calendar-day"></i>
                            <span>Tuesday</span>
                        </div>
                        <div class="counselor-cards-container" id="tuesday-schedule">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading schedule...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wednesday -->
                    <div class="day-column" data-day="Wednesday">
                        <div class="day-header">
                            <i class="fas fa-calendar-day"></i>
                            <span>Wednesday</span>
                        </div>
                        <div class="counselor-cards-container" id="wednesday-schedule">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading schedule...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Thursday -->
                    <div class="day-column" data-day="Thursday">
                        <div class="day-header">
                            <i class="fas fa-calendar-day"></i>
                            <span>Thursday</span>
                        </div>
                        <div class="counselor-cards-container" id="thursday-schedule">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading schedule...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Friday -->
                    <div class="day-column" data-day="Friday">
                        <div class="day-header">
                            <i class="fas fa-calendar-day"></i>
                            <span>Friday</span>
                        </div>
                        <div class="counselor-cards-container" id="friday-schedule">
                            <div class="loading-placeholder">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading schedule...</p>
                            </div>
                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/utils/timeFormatter.js') ?>"></script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/admin/profile_sync.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/admin/admins_management.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
</body>

</html>