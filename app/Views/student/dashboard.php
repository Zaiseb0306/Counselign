<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <title>Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/student/student_dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/student/header.css') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <div class="logo-title-container">
                        <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="Counselign logo" class="logo" />
                        <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    </div>
                    <button class="custom-navbar-toggler align-items-center" type="button" id="navbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-gear"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navbar Drawer (always available) -->
    <div class="navbar-drawer" id="navbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Student Menu</h5>
            <button class="btn-close btn-close-white" id="navbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/announcements') ?>"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/schedule-appointment') ?>"><i class="fas fa-plus-circle"></i> Schedule an Appointment</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/my-appointments') ?>"><i class="fas fa-list-alt"></i> My Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/follow-up-sessions') ?>"><i class="fas fa-clipboard-list"></i> Follow Up Sessions</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/profile') ?>"><i class="fas fa-user"></i> User Profile</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Navbar Drawer -->
    <div class="navbar-overlay" id="navbarOverlay"></div>

    <main class="flex-grow py-4 px-8">
        <!-- Interactive Profile Picture Section -->
        <div class="profile-display flex justify-between items-center">
            <div class="flex items-center space-x-4"></div>
            <button class="profile-avatar" type="button">
                <img id="profile-img" src="<?= base_url('Photos/profile.png') ?>" alt="User Avatar" class="profile-img" />
            </button>
            <div class="profile-details-wrapper">
                <?php
                $userDisplayHelper = new \App\Helpers\UserDisplayHelper();
                $userInfo = $userDisplayHelper->getUserDisplayInfo(session()->get('user_id_display'), session()->get('role'));
                ?>
                <div class="fs-12 fw-bold" style="color: #003366;">
                    Hello! 
                    <span class="text-primary">
                        <i><?= $userInfo['display_name'] ?></i>
                    </span>
                    <?php if ($userInfo['has_name']): ?>
                        <span class="small text-muted" style="display: none;" id="user-id-display"><?= $userInfo['user_id_display'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="small text-secondary">Last login: <?php 
                    $lastLogin = session()->get('last_login');
                    if ($lastLogin) {
                        $dateTime = new \DateTime($lastLogin);
                        echo $dateTime->format('M j, g:i A');
                    } else {
                        echo 'N/A';
                    }
                ?></div>
            </div>
            <div class="ml-auto flex items-center space-x-6">

                <a href="<?= base_url('student/messages') ?>" title="Messages" style="text-decoration:none;">
                    <div class="message-icon-container">
                        <i class="fas fa-comments text-2xl" style="color: #003366; cursor: pointer;"></i>
                        <span id="messageBadge" class="message-badge hidden"></span>
                    </div>
                </a>
                <div class="relative notification-icon-container">
                    <i class="fas fa-bell text-2xl" id="notificationIcon" title="Notifications"
                        style="color: #003366; cursor: pointer;"></i>
                    <span id="notificationBadge" class="notification-badge hidden"></span>
                </div>
            </div>
        </div>

        <!-- Content Panel -->
        <div class="content-panel mt-4">
            <h3 class="text-2xl font-extrabold mb-4">Welcome to Your Safe Space</h3>
            <p class="text-lg italic mb-8">
                "At our University Guidance Counseling, we understand that opening up can be challenging. However, we
                want to assure you that you are not alone. We are here to listen and support you without
                judgment."
            </p>
            <div class="wave"></div>
        </div>

        <!-- Notifications Dropdown -->
        <div id="notificationsDropdown" class="absolute bg-white rounded-lg shadow-lg border">
            <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-blue-800">Notifications</h3>
                <button id="markAllReadBtn" class="btn btn-sm btn-outline-primary" title="Mark all as read">
                    <i class="fas fa-check-double"></i> Clear All
                </button>
            </div>
            <div class="notifications-list max-h-64 overflow-y-auto">
                <!-- Notifications will be dynamically populated here -->
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

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-labelledby="appointmentDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentDetailsLabel">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="appointmentDetailsBody">
                    <!-- Appointment details will be injected here -->
                </div>
                <div class="d-flex justify-content-end mt-3 gap-2 p-3" style="position: relative; z-index: 10;">
                    <a href="<?= base_url('student/my-appointments') ?>" class="btn btn-primary" style="pointer-events: auto;">
                        <i class="fas fa-clipboard-list me-1"></i> Manage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PDS Reminder Popup Modal -->
    <div id="pdsReminderModal" class="modal fade" tabindex="-1" aria-labelledby="pdsReminderLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="false">
        <div class="modal-dialog pds-reminder-dialog">
            <div class="modal-content pds-reminder-modal">
                <div class="modal-header pds-reminder-header">
                    <h5 class="modal-title" id="pdsReminderLabel">
                        <i class="fas fa-clipboard-list me-2"></i>
                        PDS Reminder
                    </h5>
                    <button type="button" class="btn-close btn-close-white" id="closePdsReminder" aria-label="Close"></button>
                </div>
                <div class="modal-body pds-reminder-body">
                    <div class="pds-reminder-content">
                        <div class="pds-reminder-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="pds-reminder-text">
                            <h6 class="pds-reminder-title">Update Your PDS!</h6>
                            <p class="pds-reminder-message">
                                Keep your Personal Data Sheet updated for timely counseling services.
                            </p>
                        </div>
                    </div>
                    <div class="pds-reminder-timer">
                        <div class="timer-bar">
                            <div class="timer-progress" id="timerProgress"></div>
                        </div>
                        <span class="timer-text">Auto-close in <span id="timerCountdown">20</span>s</span>
                    </div>
                </div>
                <div class="modal-footer pds-reminder-footer">
                    <a href="<?= base_url('student/profile') ?>" class="btn btn-primary pds-reminder-btn">
                        <i class="fas fa-user-edit me-1"></i>
                        Update Now
                    </a>
                    <button type="button" class="btn btn-secondary pds-reminder-btn" id="dismissPdsReminder">
                        <i class="fas fa-times me-1"></i>
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php echo view('modals/student_dashboard_modals'); ?>
    <script src="<?= base_url('js/modals/student_dashboard_modals.js') ?>"></script>
    <script src="<?= base_url('js/student/student_dashboard.js') ?>"></script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
</body>

</html>