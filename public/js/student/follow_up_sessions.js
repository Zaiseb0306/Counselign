// Global variables for follow-up functionality
let currentParentAppointmentId = null;
let currentStudentId = null;

function navigateToHome() {
    window.location.href = (window.BASE_URL || '/') + 'student/dashboard';
}

function navigateToAbout() {
    // Add functionality for About page navigation
    alert("About page functionality not implemented yet.");
}

function navigateToServices() {
    window.location.href = (window.BASE_URL || '/') + 'student/services';
}

function navigateToContact() {
    // Add functionality for Contact page navigation
    alert("Contact page functionality not implemented yet.");
}

function cancelAppointment() {
    // Add functionality to cancel the appointment
    alert("Appointment cancelled.");
}

function scheduleAppointment() {
    // Add functionality to schedule the appointment
    alert("Appointment scheduled.");
    scrollToTop();
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize sticky header
    initStickyHeader();

    // Load completed appointments
    loadCompletedAppointments();

    // Setup modal event listeners
    setupModalEventListeners();
});

// Load completed appointments for the logged-in student
async function loadCompletedAppointments(searchTerm = '') {
    try {
        let url = (window.BASE_URL || '/') + 'student/follow-up-sessions/completed-appointments';
        if (searchTerm) {
            url += '?search=' + encodeURIComponent(searchTerm);
        }

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.status === 'success') {
            displayCompletedAppointments(data.appointments, data.search_term);
        } else {
            showError(data.message || 'Failed to load completed appointments');
        }
    } catch (error) {
        console.error('Error loading completed appointments:', error);
        showError('Error loading completed appointments: ' + error.message);
    }
}

// Display completed appointments in card format
function displayCompletedAppointments(appointments, searchTerm = '') {
    const container = document.getElementById('completedAppointmentsContainer');
    const pendingContainer = document.getElementById('pendingFollowUpContainer');
    const pendingSection = document.getElementById('pendingFollowUpSection');
    const noDataMessage = document.getElementById('noCompletedAppointments');
    const noSearchResults = document.getElementById('noSearchResults');

    if (!container) return;

    // Identify appointments with pending follow-ups (but still show ALL completed appointments below)
    const pendingAppointments = appointments.filter(appointment => {
        const pendingCount = parseInt(appointment.pending_follow_up_count) || 0;
        return pendingCount > 0;
    });
    
    // Debug logging
    SecureLogger.info('Total appointments:', appointments.length);
    SecureLogger.info('Pending appointments:', pendingAppointments.length);
    SecureLogger.info('Appointments data:', appointments);

    // Handle pending appointments section
    if (pendingAppointments.length > 0 && !searchTerm) {
        pendingSection.style.display = 'block';
        pendingContainer.innerHTML = pendingAppointments.map(appointment => createAppointmentCard(appointment)).join('');
    } else {
        pendingSection.style.display = 'none';
    }

    // Show all completed appointments regardless of pending status
    if (appointments.length === 0) {
        container.style.display = 'none';
        if (searchTerm) {
            noDataMessage.style.display = 'none';
            noSearchResults.style.display = 'block';
        } else {
            noDataMessage.style.display = 'block';
            noSearchResults.style.display = 'none';
        }
    } else {
        container.style.display = 'grid';
        noDataMessage.style.display = 'none';
        noSearchResults.style.display = 'none';
        container.innerHTML = appointments.map(appointment => createAppointmentCard(appointment)).join('');
    }
}

// Create appointment card HTML
function createAppointmentCard(appointment) {
    return `
        <div class="appointment-card">
            <div class="appointment-header">
                <div class="appointment-status">${appointment.status}</div>
                <div class="header-indicators">
                    <div class="follow-up-count">
                        <i class="fas fa-calendar-plus"></i>
                        Follow-ups: ${appointment.follow_up_count || 0}
                    </div>
                    ${appointment.pending_follow_up_count > 0 ? `
                    <div class="pending-follow-up-indicator">
                        <i class="fas fa-exclamation-triangle"></i>
                        Pending
                    </div>
                    ` : ''}
                </div>
            </div>
            <div class="appointment-details">
                <div class="appointment-date">
                    <i class="fas fa-calendar"></i>
                    <span>${formatDate(appointment.preferred_date)}</span>
                </div>
                <div class="appointment-time">
                    <i class="fas fa-clock"></i>
                    <span>${appointment.preferred_time}</span>
                </div>
                <div class="appointment-type">
                    <i class="fas fa-comments"></i>
                    <span>${appointment.consultation_type}</span>
                </div>
                ${appointment.purpose ? `
                <div class="appointment-purpose">
                    <i class="fas fa-bullseye"></i>
                    <span>${appointment.purpose}</span>
                </div>
                ` : ''}
                ${appointment.reason ? `
                <div class="appointment-reason">
                    <i class="fas fa-clipboard-list"></i>
                    <span>${appointment.reason}</span>
                </div>
                ` : ''}
                ${appointment.description ? `
                <div class="appointment-description">
                    <i class="fas fa-file-text"></i>
                    <span>${appointment.description}</span>
                </div>
                ` : ''}
                ${appointment.counselor_name ? `
                <div class=\"appointment-counselor\"> 
                    <i class=\"fas fa-user-md\"></i>
                    <span>${appointment.counselor_name}</span>
                </div>
                ` : ''}
            </div>
            <button class="follow-up-btn" onclick="openFollowUpSessionsModal(${appointment.id}, '${appointment.student_id}')">
                <i class="fas fa-calendar-days"></i>
                Follow-up Sessions
            </button>
        </div>
    `;
}

// Open follow-up sessions modal
async function openFollowUpSessionsModal(parentAppointmentId, studentId) {
    currentParentAppointmentId = parentAppointmentId;
    currentStudentId = studentId;

    try {
        const response = await fetch((window.BASE_URL || '/') + 'student/follow-up-sessions/sessions?parent_appointment_id=' + parentAppointmentId, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.status === 'success') {
            displayFollowUpSessions(data.follow_up_sessions);
            const modal = new bootstrap.Modal(document.getElementById('followUpSessionsModal'));
            modal.show();
        } else {
            showError(data.message || 'Failed to load follow-up sessions');
        }
    } catch (error) {
        console.error('Error loading follow-up sessions:', error);
        showError('Error loading follow-up sessions: ' + error.message);
    }
}

// Display follow-up sessions in the modal
function displayFollowUpSessions(sessions) {
    const container = document.getElementById('followUpSessionsContainer');
    const noDataMessage = document.getElementById('noFollowUpSessions');

    if (!container) return;

    if (sessions.length === 0) {
        container.style.display = 'none';
        noDataMessage.style.display = 'block';
        return;
    }

    // Sort sessions: pending first, then by sequence number
    const sortedSessions = [...sessions].sort((a, b) => {
        // Pending status comes first
        if (a.status === 'pending' && b.status !== 'pending') return -1;
        if (a.status !== 'pending' && b.status === 'pending') return 1;
        
        // Otherwise sort by sequence number
        return a.follow_up_sequence - b.follow_up_sequence;
    });

    container.style.display = 'grid';
    noDataMessage.style.display = 'none';

    container.innerHTML = sortedSessions.map(session => `
        <div class="follow-up-session-card">
            <div class="session-header">
                <div class="session-sequence">Follow-up #${session.follow_up_sequence}</div>
                <div class="session-status ${session.status}">${session.status}</div>
            </div>
            <div class="session-details">
                <div class="session-date">
                    <i class="fas fa-calendar"></i>
                    <span>${formatDate(session.preferred_date)}</span>
                </div>
                <div class="session-time">
                    <i class="fas fa-clock"></i>
                    <span>${session.preferred_time}</span>
                </div>
                <div class="session-type">
                    <i class="fas fa-comments"></i>
                    <span>${session.consultation_type}</span>
                </div>
                ${session.counselor_name ? `
                <div class=\"session-counselor\">
                    <i class=\"fas fa-user-md\"></i>
                    <span>${session.counselor_name}</span>
                </div>
                ` : ''}
                ${session.description ? `<div class="session-description"><strong>Description:</strong> ${session.description}</div>` : ''}
                ${session.reason ? `<div class="session-reason"><strong>${session.status === 'cancelled' ? 'Reason For Cancellation:' : 'Reason For Follow-up:'}</strong> ${session.reason}</div>` : ''}
            </div>
        </div>
    `).join('');
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (error) {
        console.error('Error formatting date:', error);
        return dateString;
    }
}

// Show error message
function showError(message) {
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    
    if (errorAlert && errorMessage) {
        errorMessage.textContent = message;
        errorAlert.classList.add('show');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            errorAlert.classList.remove('show');
        }, 5000);
    } else {
        console.error('Error:', message);
        alert(message);
    }
}

// Show success message
function showSuccess(message) {
    const successAlert = document.getElementById('successAlert');
    const successMessage = document.getElementById('successMessage');
    
    if (successAlert && successMessage) {
        successMessage.textContent = message;
        successAlert.classList.add('show');
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            successAlert.classList.remove('show');
        }, 3000);
    } else {
        SecureLogger.info('Success:', message);
    }
}

// Initialize sticky header functionality
function initStickyHeader() {
    const header = document.querySelector('header');
    if (!header) return;

    let lastScrollTop = 0;
    let ticking = false;

    function updateHeader() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            header.classList.add('sticky-header');
        } else {
            // Scrolling up
            header.classList.remove('sticky-header');
        }
        
        lastScrollTop = scrollTop;
        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestTick);
}

// Setup modal event listeners
function setupModalEventListeners() {
    // Handle modal backdrop issues
    document.addEventListener('hidden.bs.modal', function (event) {
        // Remove any lingering backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Reset body class
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    // Ensure only one backdrop exists when multiple modals are shown
    document.addEventListener('shown.bs.modal', function () {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            // Keep the last one (top-most modal), remove extra
            for (let i = 0; i < backdrops.length - 1; i++) {
                backdrops[i].remove();
            }
        }
    });
}

// Helper function to scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Search functionality
let searchTimeout;

// Initialize search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Show/hide clear button
            if (searchTerm) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            // Debounce search to avoid too many requests
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadCompletedAppointments(searchTerm);
            }, 300);
        });

        // Clear search functionality
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                loadCompletedAppointments('');
            });
        }
    }
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
});

// Student navbar drawer functionality
document.addEventListener('DOMContentLoaded', function() {
    const drawerToggler = document.getElementById('navbarDrawerToggler');
    const drawer = document.getElementById('navbarDrawer');
    const drawerClose = document.getElementById('navbarDrawerClose');
    const overlay = document.getElementById('navbarOverlay');

    if (!drawerToggler || !drawer || !drawerClose || !overlay) {
        console.warn('Navbar drawer elements not found');
        return;
    }

    function openDrawer() {
        drawer.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    drawerToggler.addEventListener('click', openDrawer);
    drawerClose.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    // Close drawer on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && drawer.classList.contains('show')) {
            closeDrawer();
        }
    });
});
