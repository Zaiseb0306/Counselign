<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Counselign</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/admin/admin_announcements.css') ?>">
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

    <div class="container py-5">
        <div class="row">
            <!-- Announcements Column -->
            <div class="col-lg-6 col-12">
                <div class="section-header">
                    <i class="fas fa-bullhorn me-2 text-primary fs-3"></i>
                    <h2 class="fw-bold text-primary">Announcements</h2>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-add-announcement" onclick="openAnnouncementModal()">
                                <i class="fas fa-plus me-1"></i> Add Announcement
                            </button>
                        </div>
                        <div class="category-container">
                            <div id="announcements-list" class="scrollable-list">
                                <!-- Announcement items will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Column -->
            <div class="col-lg-6 col-12">
                <div class="section-header">
                    <i class="fas fa-calendar-alt me-2 text-primary fs-3"></i>
                    <h2 class="fw-bold text-primary">Events</h2>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-add-announcement" onclick="openEventModal()">
                                <i class="fas fa-calendar-plus me-1"></i> Add Event
                            </button>
                        </div>
                        <div class="category-container">
                            <div id="events-list" class="scrollable-list">
                                <!-- Event items will be loaded here -->
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

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEventModal()">&times;</span>
            <h2>Add New Event</h2>
            <form id="eventForm">
                <div class="mb-3">
                    <label for="eventTitle" class="form-label">Event Title</label>
                    <input type="text" class="form-control" id="eventTitle" required>
                </div>
                <div class="mb-3">
                    <label for="eventDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="eventDescription" rows="3" required></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="eventDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="eventDate" required>
                    </div>
                    <div class="col-md-6">
                        <label for="eventTime" class="form-label">Time</label>
                        <input type="time" class="form-control" id="eventTime" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="eventLocation" class="form-label">Location</label>
                    <input type="text" class="form-control" id="eventLocation" required>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-add-announcement">
                        <i class="fas fa-plus me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAnnouncementModal()">&times;</span>
            <h2>Add Announcement</h2>
            <form id="announcement-form" class="row g-3">
                <div class="mb-3">
                    <label for="announcement-title" class="form-label">Announcement Title</label>
                    <input type="text" id="announcement-title" class="form-control" placeholder="Enter title here"
                        required>
                </div>
                <div class="mb-3">
                    <label for="announcement-content" class="form-label">Announcement Content</label>
                    <textarea id="announcement-content" class="form-control" rows="3" placeholder="Enter content here"
                        required></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-add-announcement">
                        <i class="fas fa-plus me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div id="editAnnouncementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditAnnouncementModal()">&times;</span>
            <h2>Edit Announcement</h2>
            <form id="edit-announcement-form" class="row g-3">
                <div class="mb-3">
                    <label for="edit-announcement-title" class="form-label">Announcement Title</label>
                    <input type="text" id="edit-announcement-title" class="form-control" placeholder="Enter title here"
                        required>
                </div>
                <div class="mb-3">
                    <label for="edit-announcement-content" class="form-label">Announcement Content</label>
                    <textarea id="edit-announcement-content" class="form-control" rows="3"
                        placeholder="Enter content here" required></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-add-announcement">
                        <i class="fas fa-plus me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editEventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditEventModal()">&times;</span>
            <h2>Edit Event</h2>
            <form id="edit-event-form" class="row g-3">
                <div class="mb-3">
                    <label for="editEventTitle" class="form-label">Event Title</label>
                    <input type="text" class="form-control" id="editEventTitle" required>
                </div>
                <div class="mb-3">
                    <label for="editEventDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="editEventDescription" rows="3" required></textarea>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="editEventDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="editEventDate" required>
                    </div>
                    <div class="col-md-6">
                        <label for="editEventTime" class="form-label">Time</label>
                        <input type="time" class="form-control" id="editEventTime" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="editEventLocation" class="form-label">Location</label>
                    <input type="text" class="form-control" id="editEventLocation" required>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-add-announcement">
                        <i class="fas fa-plus me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete Modal (reusable) -->
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeConfirmDeleteModal()">&times;</span>
            <h2>Confirm Deletion</h2>
            <p id="confirmDeleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
            <div class="text-end">
                <button type="button" class="btn btn-secondary me-2" onclick="closeConfirmDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash-alt me-1"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url('js/admin/admin_drawer.js') ?>"></script>
    <script src="<?= base_url('js/admin/admin_announcements.js') ?>"></script>
    <script src="<?= base_url('js/admin/logout.js') ?>" defer></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
</body>

</html>