<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services - Announcements and Events" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, counselor wellness" />
    <title>Announcements and Events - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/student/student_announcements.css') ?>">
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
        <!-- Announcements and Events Section -->
        <div class="announcements-container">
            <h2 class="section-title">Announcements and Events</h2>

            <!-- Announcements Section -->
            <div class="announcements-section">
                <h3 class="subsection-title">Announcements</h3>
                <div class="scrollable-container">
                    <div class="announcements-list" id="announcementsList">
                        <!-- Announcements will be dynamically loaded here -->
                    </div>
                </div>
            </div>

            <!-- Inline Calendar Section -->
            <div class="calendar-section">
                <h3 class="subsection-title">Calendar</h3>
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button id="prevMonth" class="calendar-nav-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h4 id="currentMonth" class="calendar-month"></h4>
                        <button id="nextMonth" class="calendar-nav-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Calendar will be dynamically generated here -->
                    </div>
                </div>
            </div>

            <!-- Upcoming Events List (full width) -->
            <div class="upcoming-events-section">
                <h3 class="subsection-title">Upcoming Events</h3>
                <div class="scrollable-container">
                    <div class="events-list" id="eventsList">
                        <!-- Events will be dynamically loaded here -->
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

    <?php echo view('modals/student_dashboard_modals'); ?>
    <script src="<?= base_url('js/modals/student_dashboard_modals.js') ?>"></script>
    <script src="<?= base_url('js/student/student_announcements.js') ?>" defer></script>
    <script src="<?= base_url('js/student/student_header_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>