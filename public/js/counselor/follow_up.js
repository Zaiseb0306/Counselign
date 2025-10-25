// Global variables for follow-up functionality
let currentParentAppointmentId = null;
let currentStudentId = null;
let currentFollowUpSequence = 1;

function navigateToHome() {
    window.location.href = (window.BASE_URL || '/') + 'counselor/dashboard';
}

function navigateToAbout() {
    // Add functionality for About page navigation
    alert("About page functionality not implemented yet.");
}

function navigateToServices() {
    window.location.href = (window.BASE_URL || '/') + 'counselor/services';
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

// Load completed appointments for the logged-in counselor
async function loadCompletedAppointments(searchTerm = '') {
    try {
        let url = (window.BASE_URL || '/') + 'counselor/follow-up/completed-appointments';
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
    const noDataMessage = document.getElementById('noCompletedAppointments');
    const noSearchResults = document.getElementById('noSearchResults');

    if (!container) return;

    if (appointments.length === 0) {
        container.style.display = 'none';
        if (searchTerm) {
            noDataMessage.style.display = 'none';
            noSearchResults.style.display = 'block';
        } else {
            noDataMessage.style.display = 'block';
            noSearchResults.style.display = 'none';
        }
        return;
    }

    container.style.display = 'grid';
    noDataMessage.style.display = 'none';
    noSearchResults.style.display = 'none';

    container.innerHTML = appointments.map(appointment => `
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
            <div class="appointment-student">
                <div class="student-name">${appointment.student_name || 'Unknown Student'}</div>
                <div class="student-id">Student ID: ${appointment.student_id}</div>
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
            </div>
            <button class="follow-up-btn" onclick="openFollowUpSessionsModal(${appointment.id}, '${appointment.student_id}')">
                <i class="fas fa-calendar-days"></i>
                Follow-up Sessions
            </button>
        </div>
    `).join('');
}

// Open follow-up sessions modal
async function openFollowUpSessionsModal(parentAppointmentId, studentId) {
    currentParentAppointmentId = parentAppointmentId;
    currentStudentId = studentId;

    try {
        // Load existing follow-up sessions
        const response = await fetch((window.BASE_URL || '/') + `counselor/follow-up/sessions?parent_appointment_id=${parentAppointmentId}`, {
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
            // Update modal header with student full name if available from the completed card context
            try {
                const label = document.getElementById('followUpSessionsModalLabel');
                if (label) {
                    // Find the card for this parent appointment to read the displayed student_name text
                    const card = document.querySelector(`.appointment-card button.follow-up-btn[onclick*="(${parentAppointmentId},"]`)?.closest('.appointment-card');
                    const nameEl = card?.querySelector('.student-name');
                    const fullName = nameEl?.textContent?.trim();
                    if (fullName) {
                        label.innerHTML = `<i class="fas fa-calendar-alt me-2"></i> Follow-up Sessions - ${fullName}`;
                    }
                }
            } catch (_) {}
            // Show the modal
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
    const createBtn = document.getElementById('createNewFollowUpBtn');

    if (!container) return;

    if (sessions.length === 0) {
        container.style.display = 'none';
        noDataMessage.style.display = 'block';
        if (createBtn) {
            createBtn.disabled = false;
            createBtn.classList.remove('d-none');
        }
        return;
    }

    // Ensure grid layout for desktop (CSS sets columns)
    container.style.display = 'grid';
    noDataMessage.style.display = 'none';
    if (createBtn) {
        createBtn.disabled = true;
        createBtn.classList.add('d-none');
    }

    const lastSession = sessions[sessions.length - 1];
    const lastIsPending = lastSession && lastSession.status === 'pending';
    const lastIsEligibleForNext = lastSession && (lastSession.status === 'completed' || lastSession.status === 'cancelled');

    container.innerHTML = sessions.map(session => `
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
                ${session.description ? `<div class="session-description"><strong>Description:</strong> ${session.description}</div>` : ''}
                ${session.reason ? `<div class="session-reason"><strong>Reason:</strong> ${session.reason}</div>` : ''}
            </div>
            <div class="session-actions d-flex gap-2 flex-wrap">
                <button class="btn btn-success btn-sm" ${(session.status === 'completed' || session.status === 'cancelled') ? 'disabled' : ''} onclick="markFollowUpCompleted(${session.id})">
                    <i class="fas fa-check"></i> Mark as Completed
                </button>
                <button class="btn btn-danger btn-sm" ${session.status !== 'pending' ? 'disabled' : ''} onclick="openCancelFollowUpModal(${session.id})">
                    <i class="fas fa-ban"></i> Cancel
                </button>
                <button class="btn btn-primary btn-sm" ${((!lastIsEligibleForNext) || session.id !== lastSession.id) ? 'disabled' : ''} onclick="createNewFollowUpFromSession(${session.id}, ${session.follow_up_sequence})">
                    <i class="fas fa-plus"></i> Create Next Follow-up
                </button>
            </div>
        </div>
    `).join('');
}

// Create new follow-up from existing session
function createNewFollowUpFromSession(sessionId, currentSequence) {
    currentFollowUpSequence = currentSequence + 1;
    openCreateFollowUpModal();
}

// Mark follow-up as completed
async function markFollowUpCompleted(id) {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfName = csrfMeta?.getAttribute('name') || 'csrf_test_name';
        const csrfHash = csrfMeta?.getAttribute('content') || '';

        const formData = new URLSearchParams();
        formData.append('id', String(id));
        if (csrfHash) formData.append(csrfName, csrfHash);

        const response = await fetch((window.BASE_URL || '/') + 'counselor/follow-up/complete', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        const data = await response.json();
        if (data.status === 'success') {
            showSuccess(data.message || 'Follow-up marked as completed');
            if (currentParentAppointmentId) {
                openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
        } else {
            showError(data.message || 'Failed to complete follow-up');
        }
    } catch (e) {
        console.error(e);
        showError('Failed to complete follow-up: ' + e.message);
    }
}

// Open cancel modal
function openCancelFollowUpModal(id) {
    const idInput = document.getElementById('cancelFollowUpId');
    const reasonInput = document.getElementById('cancelReason');
    if (idInput && reasonInput) {
        idInput.value = String(id);
        reasonInput.value = '';
    }
    const modal = new bootstrap.Modal(document.getElementById('cancelFollowUpModal'));
    modal.show();
}

// Confirm cancel
async function confirmCancelFollowUp() {
    const id = document.getElementById('cancelFollowUpId').value;
    const reason = document.getElementById('cancelReason').value.trim();
    if (!reason) {
        showError('Cancellation reason is required');
        return;
    }
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfName = csrfMeta?.getAttribute('name') || 'csrf_test_name';
        const csrfHash = csrfMeta?.getAttribute('content') || '';

        const form = new URLSearchParams();
        form.append('id', String(id));
        form.append('reason', reason);
        if (csrfHash) form.append(csrfName, csrfHash);

        const response = await fetch((window.BASE_URL || '/') + 'counselor/follow-up/cancel', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: form
        });
        const data = await response.json();
        if (data.status === 'success') {
            showSuccess(data.message || 'Follow-up cancelled');
            const modalEl = document.getElementById('cancelFollowUpModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            if (currentParentAppointmentId) {
                openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
        } else {
            showError(data.message || 'Failed to cancel follow-up');
        }
    } catch (e) {
        console.error(e);
        showError('Failed to cancel follow-up: ' + e.message);
    }
}

// Open create follow-up modal
function openCreateFollowUpModal() {
    // Set the parent appointment ID and student ID
    document.getElementById('parentAppointmentId').value = currentParentAppointmentId;
    document.getElementById('studentId').value = currentStudentId;

    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const minDate = tomorrow.toISOString().split('T')[0];
    document.getElementById('preferredDate').setAttribute('min', minDate);
    document.getElementById('preferredDate').value = minDate;

    // Clear time options
    const timeSelect = document.getElementById('preferredTime');
    timeSelect.innerHTML = '<option value="">Select a time</option>';

    // Load availability for tomorrow
    loadCounselorAvailability(minDate);

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('createFollowUpModal'));
    modal.show();
}

// Load counselor availability for a specific date
async function loadCounselorAvailability(date) {
    try {
        const response = await fetch((window.BASE_URL || '/') + `counselor/follow-up/availability?date=${date}`, {
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
            populateTimeOptions(data.time_slots);
            } else {
            showError(data.message || 'Failed to load counselor availability');
        }
    } catch (error) {
        console.error('Error loading counselor availability:', error);
        showError('Error loading counselor availability: ' + error.message);
    }
}

// Populate time options based on counselor availability
function populateTimeOptions(timeSlots) {
    const timeSelect = document.getElementById('preferredTime');
    
    if (timeSlots.length === 0) {
        timeSelect.innerHTML = '<option value="">No available time slots for this date</option>';
        return;
    }

    timeSelect.innerHTML = '<option value="">Select a time</option>';
    
    timeSlots.forEach(slot => {
        const option = document.createElement('option');
        option.value = slot;
        option.textContent = slot;
        timeSelect.appendChild(option);
    });
}

// Setup modal event listeners
function setupModalEventListeners() {
    // Create new follow-up button
    const createNewFollowUpBtn = document.getElementById('createNewFollowUpBtn');
    if (createNewFollowUpBtn) {
        createNewFollowUpBtn.addEventListener('click', () => {
            currentFollowUpSequence = 1;
            openCreateFollowUpModal();
        });
    }

    // Save follow-up button
    const saveFollowUpBtn = document.getElementById('saveFollowUpBtn');
    if (saveFollowUpBtn) {
        saveFollowUpBtn.addEventListener('click', saveFollowUp);
    }

    // Date change listener for availability loading
    const preferredDateInput = document.getElementById('preferredDate');
    if (preferredDateInput) {
        preferredDateInput.addEventListener('change', function() {
            if (this.value) {
                loadCounselorAvailability(this.value);
            }
        });
    }
}

// Save follow-up appointment
async function saveFollowUp() {
    const form = document.getElementById('createFollowUpForm');
    const formData = new FormData(form);

    // Validate required fields
    const requiredFields = ['parent_appointment_id', 'student_id', 'preferred_date', 'preferred_time', 'consultation_type'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            showError(`Please fill in all required fields`);
            return;
        }
    }

    try {
        const response = await fetch((window.BASE_URL || '/') + 'counselor/follow-up/create', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(formData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Network response was not ok');
        }

        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess(data.message || 'Follow-up appointment created successfully');
            
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createFollowUpModal'));
            if (modal) {
                modal.hide();
            }
            
            // Refresh the follow-up sessions
            if (currentParentAppointmentId) {
                openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
        } else {
            showError(data.message || 'Failed to create follow-up appointment');
        }
    } catch (error) {
        console.error('Error creating follow-up appointment:', error);
        showError('Error creating follow-up appointment: ' + error.message);
    }
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function showError(message) {
    const errorModal = document.getElementById('errorModal');
    const errorBody = document.getElementById('errorModalBody');
    
    if (errorModal && errorBody) {
        errorBody.textContent = message;
        const modal = new bootstrap.Modal(errorModal);
        modal.show();
    } else {
        // Fallback to alert if modal not found
        alert('Error: ' + message);
    }
}

function showSuccess(message) {
    const successModal = document.getElementById('successModal');
    const successBody = document.getElementById('successModalBody');
    
    if (successModal && successBody) {
        successBody.textContent = message;
        const modal = new bootstrap.Modal(successModal);
        modal.show();
    } else {
        // Fallback to alert if modal not found
        alert('Success: ' + message);
    }
}

// Make header sticky on scroll - improved version
function initStickyHeader() {
    const header = document.querySelector('header');

    if (header) {
        // Set header as sticky right from the start
        header.classList.add("sticky-header");

        window.onscroll = function () {
            // Just update the shadow effect based on scroll position
            if (window.pageYOffset > 10) {
                header.classList.add("sticky-header");
            } else {
                header.classList.remove("sticky-header");
            }
        };
    }

    // Robust modal stacking/cleanup to keep page responsive after multiple modals
    document.addEventListener('hidden.bs.modal', function () {
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            // Remove stray backdrops if any remained
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        }
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