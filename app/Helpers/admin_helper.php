<?php

/**
 * Admin Helper Functions
 * 
 * Helper functions for admin page components and functionality
 */

if (!function_exists('render_admin_header')) {
    /**
     * Render responsive admin header with drawer
     * 
     * @param array $navItems Array of navigation items for the drawer
     * @return string The rendered header HTML
     */
    function render_admin_header(array $navItems = []): string
    {
        // Default navigation items if none provided
        if (empty($navItems)) {
            $navItems = [
                ['url' => 'admin/dashboard', 'icon' => 'fas fa-home', 'text' => 'Home'],
                ['action' => 'confirmLogout()', 'icon' => 'fas fa-sign-out-alt', 'text' => 'Log Out']
            ];
        }

        $header = '
    <header class="admin-header text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <img src="' . base_url('Photos/counselign_logo.png') . '" alt="UGC Logo" class="logo" />
                    <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    <button class="admin-navbar-toggler d-lg-none align-items-center" type="button" id="adminNavbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span>
                    </button>

                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                            <ul class="navbar-nav nav-links">';
        
        // Add navigation items for desktop
        foreach ($navItems as $item) {
            if (isset($item['url'])) {
                $header .= '<li class="nav-item"><a class="nav-link" href="' . base_url($item['url']) . '"><i class="' . $item['icon'] . '"></i> ' . $item['text'] . '</a></li>';
            } elseif (isset($item['action'])) {
                $header .= '<li class="nav-item"><a class="nav-link" onclick="' . $item['action'] . '"><i class="' . $item['icon'] . '"></i> ' . $item['text'] . '</a></li>';
            }
        }

        $header .= '
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
        <ul class="navbar-nav nav-links p-3">';

        // Add navigation items for mobile drawer
        foreach ($navItems as $item) {
            if (isset($item['url'])) {
                $header .= '<li class="nav-item"><a class="nav-link" href="' . base_url($item['url']) . '"><i class="' . $item['icon'] . '"></i> ' . $item['text'] . '</a></li>';
            } elseif (isset($item['action'])) {
                $header .= '<li class="nav-item"><a class="nav-link" onclick="' . $item['action'] . '"><i class="' . $item['icon'] . '"></i> ' . $item['text'] . '</a></li>';
            }
        }

        $header .= '
        </ul>
    </div>

    <!-- Overlay for Admin Navbar Drawer -->
    <div class="admin-navbar-overlay d-lg-none" id="adminNavbarOverlay"></div>';

        return $header;
    }
}

if (!function_exists('get_admin_nav_items')) {
    /**
     * Get navigation items for specific admin pages
     * 
     * @param string $page The current page identifier
     * @return array Navigation items for the page
     */
    function get_admin_nav_items(string $page = 'dashboard'): array
    {
        $commonItems = [
            ['url' => 'admin/dashboard', 'icon' => 'fas fa-home', 'text' => 'Home'],
            ['action' => 'confirmLogout()', 'icon' => 'fas fa-sign-out-alt', 'text' => 'Log Out']
        ];

        switch ($page) {
            case 'appointments':
                return $commonItems;
            
            case 'view_users':
                return [
                    ['url' => 'admin/admins-management', 'icon' => 'fas fa-tasks', 'text' => 'Management'],
                    ...$commonItems
                ];
            
            case 'counselor_info':
                return [
                    ['url' => 'admin/view-users', 'icon' => 'fas fa-users', 'text' => 'Users'],
                    ...$commonItems
                ];
            
            case 'announcements':
                return [
                    ['url' => 'admin/messages', 'icon' => 'fas fa-envelope', 'text' => 'Messages'],
                    ...$commonItems
                ];
            
            case 'messages':
                return [
                    ['url' => 'admin/announcements', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
                    ...$commonItems
                ];
            
            default:
                return $commonItems;
        }
    }
}
