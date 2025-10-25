document.addEventListener('DOMContentLoaded', function() {
    const toggler = document.getElementById('counselorSidebarToggler');
    const sidebar = document.querySelector('.counselor-sidebar');
    const overlay = document.getElementById('counselorSidebarOverlay');

    if (!sidebar) return;

    // Initialize collapsed on small screens
    function initializeCollapsed() {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            sidebar.classList.remove('show');
            overlay && overlay.classList.remove('show');
            // Position the floating button just below the header height
            const header = document.querySelector('header.admin-header');
            const headerRect = header ? header.getBoundingClientRect() : null;
            const topOffset = (headerRect ? headerRect.height : 60) + 10;
            if (toggler) {
                toggler.style.top = topOffset + 'px';
            }
        }
    }
    initializeCollapsed();
    window.addEventListener('resize', initializeCollapsed);

    function openSidebar() {
        sidebar.classList.add('show');
        overlay && overlay.classList.add('show');
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay && overlay.classList.remove('show');
    }

    toggler && toggler.addEventListener('click', function() {
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay && overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
});


