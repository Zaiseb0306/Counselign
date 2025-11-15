/**
 * User Drawer Functionality
 * Shared JavaScript for responsive hamburger menu across all user pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Expose a safe global confirmLogout if not already defined by page
    if (typeof window.confirmLogout !== 'function') {
        window.confirmLogout = function() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = (window.BASE_URL || '/') + 'auth/logout';
            }
        };
    }

    const drawerToggler = document.getElementById('userNavbarDrawerToggler');
    const drawer = document.getElementById('userNavbarDrawer');
    const overlay = document.getElementById('userNavbarOverlay');
    const drawerClose = document.getElementById('userNavbarDrawerClose');

    if (!drawer || !overlay) {
        return; // Not an user page with drawer markup
    }

    function openDrawer() {
        drawer.classList.add('show');
        overlay.classList.add('show');
    }

    function closeDrawer() {
        drawer.classList.remove('show');
        overlay.classList.remove('show');
    }

    drawerToggler && drawerToggler.addEventListener('click', openDrawer);
    drawerClose && drawerClose.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });

    // Optional: synchronize filters/buttons when present on certain pages
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const dateFilter = document.getElementById('dateFilter');
    const dateFilterMobile = document.getElementById('dateFilterMobile');
    const exportPDF = document.getElementById('exportPDF');
    const exportPDFMobile = document.getElementById('exportPDFMobile');
    const exportExcel = document.getElementById('exportExcel');
    const exportExcelMobile = document.getElementById('exportExcelMobile');

    if (searchInput && searchInputMobile) {
        searchInput.addEventListener('input', function() {
            searchInputMobile.value = this.value;
        });
        searchInputMobile.addEventListener('input', function() {
            searchInput.value = this.value;
        });
    }

    if (dateFilter && dateFilterMobile) {
        dateFilter.addEventListener('change', function() {
            dateFilterMobile.value = this.value;
        });
        dateFilterMobile.addEventListener('change', function() {
            dateFilter.value = this.value;
        });
    }

    if (exportPDF && exportPDFMobile) {
        exportPDFMobile.addEventListener('click', function() {
            exportPDF.click();
        });
    }

    if (exportExcel && exportExcelMobile) {
        exportExcelMobile.addEventListener('click', function() {
            exportExcel.click();
        });
    }
});
