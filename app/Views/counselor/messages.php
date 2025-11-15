<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Messages - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('css/counselor/counselor_messages.css') ?>" rel="stylesheet" />
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
                            <ul class="navbar-nav nav-links ms-auto">
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('counselor/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <!-- Overlay for Counselor Navbar Drawer -->
    <div class="counselor-navbar-overlay d-lg-none" id="counselorNavbarOverlay"></div>

    <!-- Mobile Sidebar Toggle Button -->
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Mobile Sidebar Overlay -->
    <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

    <!-- Main Content -->
    <div class="messages-wrapper">
        <div class="messages-layout">
            <!-- Conversations Sidebar -->
            <div class="conversations-sidebar" id="conversationsSidebar">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">
                        <i class="fas fa-comments me-2"></i>
                        Conversations
                    </h3>
                </div>
                
                <!-- Search Box -->
                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search conversations...">
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="conversations-list">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading conversations...</span>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="chat-user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h4 class="user-name">Messages</h4>
                            <span class="user-status">Select a conversation to start messaging</span>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="messages-area" id="messages-container">
                    <div class="empty-chat" id="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h5>No Messages Yet</h5>
                        <p>Select a conversation from the list to view messages.</p>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="message-input-section">
                    <div class="input-container">
                        <textarea id="message-input" class="message-input" 
                            placeholder="Select a conversation to reply..." disabled></textarea>
                        <button id="send-button" class="send-button" disabled>
                            <i class="fas fa-paper-plane"></i>
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
    <script src="<?= base_url('js/counselor/counselor_messages.js') ?>" defer></script>
    <script src="<?= base_url('js/counselor/counselor_drawer.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>

</body>

</html>