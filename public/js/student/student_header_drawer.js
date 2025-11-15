document.addEventListener('DOMContentLoaded', function () {
    const navbarDrawerToggler = document.getElementById('navbarDrawerToggler');
    const navbarDrawer = document.getElementById('navbarDrawer');
    const navbarDrawerClose = document.getElementById('navbarDrawerClose');
    const navbarOverlay = document.getElementById('navbarOverlay');

    function openDrawer() {
        if (navbarDrawer) navbarDrawer.classList.add('show');
        if (navbarOverlay) navbarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (navbarDrawerToggler) navbarDrawerToggler.classList.add('active');
    }

    function closeDrawer() {
        if (navbarDrawer) navbarDrawer.classList.remove('show');
        if (navbarOverlay) navbarOverlay.classList.remove('show');
        document.body.style.overflow = '';
        if (navbarDrawerToggler) navbarDrawerToggler.classList.remove('active');
    }

    if (navbarDrawerToggler) {
        navbarDrawerToggler.addEventListener('click', openDrawer);
    }
    if (navbarDrawerClose) {
        navbarDrawerClose.addEventListener('click', closeDrawer);
    }
    if (navbarOverlay) {
        navbarOverlay.addEventListener('click', closeDrawer);
    }
});


