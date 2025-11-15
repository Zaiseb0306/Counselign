<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Account Settings - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin/account_settings.css') . '?v=' . @filemtime(FCPATH . 'css/admin/account_settings.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/admin/header.css') . '?v=' . @filemtime(FCPATH . 'css/admin/header.css') ?>">
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>

    <div class="profile-container">
        <div class="profile-header">
            <div class="wave-shape"></div>
        </div>

        <div class="profile-card">
            <div class="profile-avatar-section">
                <div class="avatar-container">
                    <img src="<?= base_url('Photos/profile.png') ?>" alt="Profile Avatar" class="profile-avatar"
                        id="profile-avatar">
                </div>

                <button class="update-profile-btn" onclick="updateProfilePicture()">
                    <i class="fas fa-camera"></i> Update Profile Photo
                </button>

                <div class="profile-id-container">
                    <h2 class="profile-id-label">Account ID:</h2>
                    <div class="profile-id-value" id="admin-id">Loading...</div>
                </div>
            </div>

            <div class="profile-content">
                <h3 class="section-title">Personal Information</h3>

                <div class="info-section">
                    <div class="info-item">
                        <label class="info-label">Email:</label>
                        <div class="info-value" id="admin-email">Loading...</div>
                        <button class="edit-btn" onclick="editField('email')"><i class="fas fa-pen"></i></button>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Username:</label>
                        <div class="info-value" id="admin-username">Loading...</div>
                        <button class="edit-btn" onclick="editField('username')"><i class="fas fa-pen"></i></button>
                    </div>

                    <div class="info-item">
                        <label class="info-label">Password:</label>
                        <button class="change-password-btn" onclick="changePassword()">
                            <i class="fas fa-lock"></i> Change Password
                        </button>
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
    <script src="<?= base_url('js/admin/account_settings.js?v=1.1') ?>"></script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>"></script>

</body>

</html>