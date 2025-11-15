<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services" />
    <meta name="keywords" content="counseling, guidance, university, support" />
    <title>Counselor's Information - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('css/admin/counselor_info.css') . '?v=' . @filemtime(FCPATH . 'css/admin/counselor_info.css') ?>" rel="stylesheet" />
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
                                <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/admins-management') ?>"><i class="fas fa-tasks"></i>Management</a></li>
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/admins-management') ?>"><i class="fas fa-tasks"></i>Management</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>

    <!-- Main Content Area -->
    <div class="counselor-layout">
        <button class="counselor-sidebar-toggle d-lg-none" type="button" id="counselorSidebarToggler" aria-label="Toggle counselors list">
            <span class="navbar-toggler-icon"><i class="fas fa-users"></i></span>
        </button>
        <!-- Sidebar -->
        <div class="counselor-sidebar">
            <h2>Counselors</h2>
            <div class="counselor-list">
                <!-- Counselors will be dynamically loaded here -->
            </div>
            <button class="add-counselor" hidden>
                <i class="fas fa-plus"></i> Add New Counselor
            </button>
        </div>

        <!-- Main Content - Counselor Form -->
        <div class="counselor-form-container">
            <div class="counselor-details">
                <div class="profile-image-container text-center">
                    <img src="<?= base_url('Photos/profile.png') ?>" alt="Counselor Profile" class="profile-image" id="main-profile-image">
                </div>

                <div class="details-right">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Counselor's ID:</label>
                            <div class="info-display" id="counselorId">-</div>
                        </div>

                        <div class="form-group">
                            <label>Name:</label>
                            <div class="info-display" id="name">-</div>
                        </div>

                        <div class="form-group">
                            <label>Degree:</label>
                            <div class="info-display" id="degree">-</div>
                        </div>

                        <div class="form-group">
                            <label>Email:</label>
                            <div class="info-display" id="email">-</div>
                        </div>

                        <div class="form-group">
                            <label>Contact Number:</label>
                            <div class="info-display" id="contactNumber">-</div>
                        </div>

                        <div class="form-group">
                            <label>Address:</label>
                            <div class="info-display" id="address">-</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Availability:</label>
                        <div class="info-display" id="availableDays">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay for Counselor Sidebar (Small Screens) -->
    <div class="counselor-sidebar-overlay d-lg-none" id="counselorSidebarOverlay"></div>

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <!-- Update Modal -->
    <div class="modal-overlay" id="updateModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Counselor Information</h3>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateCounselorForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="modal-counselorId">Counselor's ID:</label>
                            <input type="text" id="modal-counselorId" class="form-input" readonly>
                        </div>

                        <div class="form-group">
                            <label for="modal-specialization">Specialization:</label>
                            <input type="text" id="modal-specialization" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-name">Name:</label>
                            <input type="text" id="modal-name" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-degree">Degree:</label>
                            <input type="text" id="modal-degree" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-email">Email:</label>
                            <input type="email" id="modal-email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-contactNumber">Contact Number:</label>
                            <input type="text" id="modal-contactNumber" class="form-input" required
                                pattern="[\+]?[0-9]{11,13}" title="Please enter a valid phone number">
                        </div>

                        <div class="form-group">
                            <label for="modal-licenseNumber">License Number:</label>
                            <input type="text" id="modal-licenseNumber" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-address">Address:</label>
                            <input type="text" id="modal-address" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="modal-startTime">Schedule:</label>
                            <div class="time-input-group">
                                <input type="time" id="modal-startTime" class="form-input" required>
                                <span class="time-separator">to</span>
                                <input type="time" id="modal-endTime" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-buttons">
                        <button type="button" class="cancel-btn" id="cancelUpdate">Cancel</button>
                        <button type="submit" class="confirm-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/utils/timeFormatter.js') ?>"></script>
    <script src="<?= base_url('js/admin/counselor_info.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="<?= base_url('js/admin/counselor_info_mobile.js') . '?v=' . @filemtime(FCPATH . 'js/admin/counselor_info_mobile.js') ?>"></script>
</body>

</html>