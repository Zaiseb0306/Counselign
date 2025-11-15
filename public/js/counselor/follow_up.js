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
                    <span>${appointment.method_type}</span>
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
            const modalEl = document.getElementById('followUpSessionsModal');
            if (modalEl) {
                modalEl.setAttribute('data-parent-appointment-id', parentAppointmentId);
                modalEl.setAttribute('data-student-id', studentId);
            }
            const modal = new bootstrap.Modal(modalEl);
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

    // Sort sessions: pending first, then by sequence number
    const sortedSessions = [...sessions].sort((a, b) => {
        // Pending status comes first
        if (a.status === 'pending' && b.status !== 'pending') return -1;
        if (a.status !== 'pending' && b.status === 'pending') return 1;
        
        // Otherwise sort by sequence number
        return a.follow_up_sequence - b.follow_up_sequence;
    });

    // Ensure grid layout for desktop (CSS sets columns)
    container.style.display = 'grid';
    noDataMessage.style.display = 'none';

    // Check if there's a pending session
    const hasPendingSession = sortedSessions.some(s => s.status === 'pending');

    // Show/hide create button based on pending status
    if (createBtn) {
        if (hasPendingSession) {
            createBtn.disabled = true;
            createBtn.classList.add('d-none');
        } else {
            createBtn.disabled = false;
            createBtn.classList.remove('d-none');
        }
    }

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
                ${session.description ? `<div class="session-description"><strong>Description:</strong> ${session.description}</div>` : ''}
                ${session.reason ? `<div class="session-reason"><strong>${session.status === 'cancelled' ? 'Reason For Cancellation:' : 'Reason For Follow-up:'}</strong> ${session.reason}</div>` : ''}
            </div>
            ${session.status === 'pending' ? `
            <div class="session-actions d-flex gap-2 flex-wrap">
                <button class="btn btn-success btn-sm" onclick="markFollowUpCompleted(${session.id})">
                    <i class="fas fa-check"></i> Mark as Completed
                </button>
                <button class="btn btn-warning btn-sm" onclick="openEditFollowUpModal(${session.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger btn-sm" onclick="openCancelFollowUpModal(${session.id})">
                    <i class="fas fa-ban"></i> Cancel
                </button>
            </div>
            ` : ''}
        </div>
    `).join('');
}

// Create new follow-up from existing session
//function createNewFollowUpFromSession(sessionId, currentSequence) {
 //   currentFollowUpSequence = currentSequence + 1;
 //   openCreateFollowUpModal();
//}

// Mark follow-up as completed
async function markFollowUpCompleted(id) {
    // Find the button that was clicked
    const button = document.querySelector(`button[onclick*="markFollowUpCompleted(${id})"]`);
    
    try {
        // Show loading state
        if (button) {
            showButtonLoading(button, 'Completing...');
        }

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
            
            // Refresh the follow-up sessions modal
            if (currentParentAppointmentId) {
                await openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
            
            // Refresh the completed appointments list to update the badge
            await loadCompletedAppointments();
        } else {
            showError(data.message || 'Failed to complete follow-up');
        }
    } catch (e) {
        console.error(e);
        showError('Failed to complete follow-up: ' + e.message);
    } finally {
        // Hide loading state
        if (button) {
            hideButtonLoading(button, 'Mark as Completed');
        }
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
    const confirmBtn = document.getElementById('confirmCancelFollowUpBtn');
    
    if (!reason) {
        showError('Cancellation reason is required');
        return;
    }
    
    // Show loading state
    showButtonLoading(confirmBtn, 'Cancelling...');
    
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
            
            // Refresh the follow-up sessions modal
            if (currentParentAppointmentId) {
                await openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
            
            // Refresh the completed appointments list to update the badge
            await loadCompletedAppointments();
        } else {
            showError(data.message || 'Failed to cancel follow-up');
        }
    } catch (e) {
        console.error(e);
        showError('Failed to cancel follow-up: ' + e.message);
    } finally {
        // Hide loading state
        hideButtonLoading(confirmBtn, 'Confirm Cancellation');
    }
}

// Open create follow-up modal
function openCreateFollowUpModal() {
    // Set the parent appointment ID and student ID
    document.getElementById('parentAppointmentId').value = currentParentAppointmentId;
    document.getElementById('studentId').value = currentStudentId;

    // Reset form
    document.getElementById('createFollowUpForm').reset();
    
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
            // Also load already-booked ranges for this counselor/date to filter options
            let booked = [];
            try {
                const bookedRes = await fetch((window.BASE_URL || '/') + `counselor/follow-up/booked-times?date=${date}`, {
                    method: 'GET',
                    credentials: 'include',
                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
                });
                if (bookedRes.ok) {
                    const bookedData = await bookedRes.json();
                    if (bookedData.status === 'success' && Array.isArray(bookedData.booked)) {
                        booked = bookedData.booked;
                    }
                }
            } catch (_) {}

            populateTimeOptions(data.time_slots, booked);
            } else {
            showError(data.message || 'Failed to load counselor availability');
        }
    } catch (error) {
        console.error('Error loading counselor availability:', error);
        showError('Error loading counselor availability: ' + error.message);
    }
}

// Populate time options based on counselor availability
function populateTimeOptions(timeSlots, bookedTimes = []) {
    const timeSelect = document.getElementById('preferredTime');
    
    if (timeSlots.length === 0) {
        timeSelect.innerHTML = '<option value="">No available time slots for this date</option>';
        return;
    }

    // Generate 30-minute increment range labels from provided slots
    const incrementTimes = generateHalfHourRangeLabelsFromSlots(timeSlots);

    if (incrementTimes.length === 0) {
        timeSelect.innerHTML = '<option value="">No available time slots for this date</option>';
        return;
    }

    timeSelect.innerHTML = '<option value="">Select a time</option>';

    const bookedSet = new Set(bookedTimes);
    incrementTimes.filter(t => !bookedSet.has(t)).forEach(t => {
        const option = document.createElement('option');
        option.value = t;
        option.textContent = t;
        timeSelect.appendChild(option);
    });
}

// Setup modal event listeners
function setupModalEventListeners() {
    // Create new follow-up button
    const createNewFollowUpBtn = document.getElementById('createNewFollowUpBtn');
    if (createNewFollowUpBtn) {
        createNewFollowUpBtn.addEventListener('click', () => {
            // ENSURE IDs ARE SET IF NOT
            if (!currentParentAppointmentId || !currentStudentId) {
                // Try to read IDs from data attributes on the button or modal parent as fallback
                const modal = document.getElementById('followUpSessionsModal');
                if (modal) {
                    const parentId = modal.getAttribute('data-parent-appointment-id');
                    const studentId = modal.getAttribute('data-student-id');
                    if (parentId) currentParentAppointmentId = parentId;
                    if (studentId) currentStudentId = studentId;
                }
            }
            currentFollowUpSequence = 1;
            openCreateFollowUpModal();
        });
    }

    // Save follow-up button
    const saveFollowUpBtn = document.getElementById('saveFollowUpBtn');
    if (saveFollowUpBtn) {
        saveFollowUpBtn.addEventListener('click', saveFollowUp);
    }

    // Update follow-up button
    const updateFollowUpBtn = document.getElementById('updateFollowUpBtn');
    if (updateFollowUpBtn) {
        updateFollowUpBtn.addEventListener('click', updateFollowUp);
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

    // Edit date field is read-only, so no event listener needed
}


// Save follow-up appointment
async function saveFollowUp() {
    const form = document.getElementById('createFollowUpForm');
    const formData = new FormData(form);
    
    // Get parent appointment ID and student ID from the form
    const parentAppointmentId = formData.get('parent_appointment_id');
    const studentId = formData.get('student_id');
    
    // LOG formData for debug
    const dataObj = {};
    for (const [k, v] of formData.entries()) dataObj[k] = v;
    console.log('Submitting follow-up data:', dataObj);
    const saveBtn = document.getElementById('saveFollowUpBtn');

    // Validate required fields
    const requiredFields = ['parent_appointment_id', 'student_id', 'preferred_date', 'preferred_time', 'consultation_type'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            showError(`Please fill in all required fields`);
            return;
        }
    }

    // Show loading state
    showButtonLoading(saveBtn, 'Creating Follow-up...');

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
            let errorMessage = 'Network response was not ok';
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (jsonErr) {
                try {
                    // Fallback: get plain text
                    const errorText = await response.text();
                    errorMessage = errorText || errorMessage;
                } catch (txtErr) {}
            }
            showError(errorMessage);
            console.error('Server response error (raw):', errorMessage, response);
            return;
        }

        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess(data.message || 'Follow-up appointment created successfully');
            
            // Close the create modal
            const createModal = bootstrap.Modal.getInstance(document.getElementById('createFollowUpModal'));
            if (createModal) {
                createModal.hide();
            }
            
            // Update global variables with values from form
            currentParentAppointmentId = parentAppointmentId;
            currentStudentId = studentId;
            
            // Refresh the completed appointments list to update the badge count
            await loadCompletedAppointments();
            
            // Refresh the follow-up sessions modal
            // Use setTimeout to ensure the create modal is fully hidden before showing sessions
            setTimeout(() => {
                openFollowUpSessionsModal(parentAppointmentId, studentId);
            }, 300);
        } else {
            showError(data.message || 'Failed to create follow-up appointment');
        }
    } catch (error) {
        console.error('Error creating follow-up appointment:', error);
        showError('Error creating follow-up appointment: ' + error.message);
    } finally {
        // Hide loading state
        hideButtonLoading(saveBtn, 'Create Follow-up');
    }
}

// Loading button utility functions
function showButtonLoading(button, loadingText) {
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
}

function hideButtonLoading(button, originalText) {
    button.disabled = false;
    
    // Determine the appropriate icon based on the button text
    let icon = 'fas fa-save';
    if (originalText.includes('Create')) {
        icon = 'fas fa-plus';
    } else if (originalText.includes('Mark as Completed')) {
        icon = 'fas fa-check';
    } else if (originalText.includes('Update')) {
        icon = 'fas fa-save';
    } else if (originalText.includes('Confirm Cancellation')) {
        icon = 'fas fa-ban';
    }
    
    button.innerHTML = `<i class="${icon} me-2"></i>${originalText}`;
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

// Time utilities for 12-hour format and 30-minute increments
function parseTime12ToMinutes(timeStr) {
    if (!timeStr) return null;
    const match = String(timeStr).trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    const meridian = match[3].toUpperCase();
    if (hours === 12) hours = 0;
    if (meridian === 'PM') hours += 12;
    return hours * 60 + minutes;
}

function formatMinutesTo12h(totalMinutes) {
    let minutes = totalMinutes % 60;
    let hours24 = Math.floor(totalMinutes / 60) % 24;
    const meridian = hours24 >= 12 ? 'PM' : 'AM';
    let hours12 = hours24 % 12;
    if (hours12 === 0) hours12 = 12;
    const mm = minutes.toString().padStart(2, '0');
    return `${hours12}:${mm} ${meridian}`;
}

function generateHalfHourTimesFromSlots(timeSlots) {
    const unique = new Set();
    const pushIfValid = (t) => {
        if (!t) return;
        const mins = parseTime12ToMinutes(t);
        if (mins !== null) unique.add(formatMinutesTo12h(mins));
    };

    timeSlots.forEach(slot => {
        if (typeof slot !== 'string') return;
        const s = slot.trim();
        if (s.includes('-')) {
            const [startStr, endStr] = s.split('-').map(p => p.trim());
            const start = parseTime12ToMinutes(startStr);
            const end = parseTime12ToMinutes(endStr);
            if (start !== null && end !== null && end > start) {
                // Generate every 30 minutes from start up to but not including end
                for (let t = start; t + 30 <= end; t += 30) {
                    unique.add(formatMinutesTo12h(t));
                }
            } else {
                // Fallback: try to add startStr as a single time
                pushIfValid(startStr);
            }
        } else {
            // Single time value
            pushIfValid(s);
        }
    });

    // Sort ascending by minutes
    const arr = Array.from(unique);
    arr.sort((a, b) => parseTime12ToMinutes(a) - parseTime12ToMinutes(b));
    return arr;
}

// Build 30-minute RANGE labels (e.g., "10:00 AM - 10:30 AM") from availability slots
function generateHalfHourRangeLabelsFromSlots(timeSlots) {
    const rangeSet = new Set();

    timeSlots.forEach(slot => {
        if (typeof slot !== 'string') return;
        const s = slot.trim();
        if (!s) return;
        if (s.includes('-')) {
            const [startStr, endStr] = s.split('-').map(p => p.trim());
            const start = parseTime12ToMinutes(startStr);
            const end = parseTime12ToMinutes(endStr);
            if (start !== null && end !== null && end > start) {
                for (let t = start; t + 30 <= end; t += 30) {
                    const from = formatMinutesTo12h(t);
                    const to = formatMinutesTo12h(t + 30);
                    rangeSet.add(`${from} - ${to}`);
                }
            }
        } else {
            // Single timestamp: create 30-min range from this time
            const fromMin = parseTime12ToMinutes(s);
            if (fromMin !== null) {
                const from = formatMinutesTo12h(fromMin);
                const to = formatMinutesTo12h(fromMin + 30);
                rangeSet.add(`${from} - ${to}`);
            }
        }
    });

    const arr = Array.from(rangeSet);
    // Sort by start minutes
    arr.sort((a, b) => {
        const [aFrom] = a.split('-').map(x => x.trim());
        const [bFrom] = b.split('-').map(x => x.trim());
        return parseTime12ToMinutes(aFrom) - parseTime12ToMinutes(bFrom);
    });
    return arr;
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

// Open edit follow-up modal
function openEditFollowUpModal(sessionId) {
    // Get the session data from the displayed card
    const sessionCard = document.querySelector(`.follow-up-session-card button[onclick*="openEditFollowUpModal(${sessionId})"]`)?.closest('.follow-up-session-card');
    if (!sessionCard) {
        showError('Session data not found');
        return;
    }

    // Extract session data from the card
    const sessionDate = sessionCard.querySelector('.session-date span')?.textContent;
    const sessionTime = sessionCard.querySelector('.session-time span')?.textContent;
    const sessionType = sessionCard.querySelector('.session-type span')?.textContent;
    const sessionDescription = sessionCard.querySelector('.session-description')?.textContent?.replace('Description: ', '') || '';
    const sessionReason = sessionCard.querySelector('.session-reason')?.textContent?.replace(/^(Reason For Follow-up:|Reason For Cancellation:)/, '').trim() || '';

    // Set the session ID
    document.getElementById('editFollowUpId').value = sessionId;

    // Set minimum date to today
    const today = new Date();
    const minDate = today.toISOString().split('T')[0];
    document.getElementById('editPreferredDate').setAttribute('min', minDate);

    // Parse and set the date
    if (sessionDate) {
        const dateObj = new Date(sessionDate);
        if (!isNaN(dateObj.getTime())) {
            document.getElementById('editPreferredDate').value = dateObj.toISOString().split('T')[0];
        }
    }

    // Clear and populate time options
    const timeSelect = document.getElementById('editPreferredTime');
    timeSelect.innerHTML = '<option value="">Select a time</option>';
    
    if (sessionTime) {
        const option = document.createElement('option');
        option.value = sessionTime;
        option.textContent = sessionTime;
        option.selected = true;
        timeSelect.appendChild(option);
    }

    // Set consultation type
    if (sessionType) {
        document.getElementById('editConsultationType').value = sessionType;
    }

    // Set description and reason
    document.getElementById('editDescription').value = sessionDescription;
    document.getElementById('editReason').value = sessionReason;

    // Date and time fields are read-only, so we don't need to load availability
    // They will display the original values chosen by the counselor

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('editFollowUpModal'));
    modal.show();
}

// Load counselor availability for edit modal
async function loadCounselorAvailabilityForEdit(date) {
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
            // Also fetch booked ranges to filter out
            let booked = [];
            try {
                const bookedRes = await fetch((window.BASE_URL || '/') + `counselor/follow-up/booked-times?date=${date}`, {
                    method: 'GET', credentials: 'include', headers: { 'Accept':'application/json','Cache-Control':'no-cache' }
                });
                if (bookedRes.ok) {
                    const bookedData = await bookedRes.json();
                    if (bookedData.status === 'success' && Array.isArray(bookedData.booked)) booked = bookedData.booked;
                }
            } catch(_) {}

            populateTimeOptionsForEdit(data.time_slots, booked);
        } else {
            showError(data.message || 'Failed to load counselor availability');
        }
    } catch (error) {
        console.error('Error loading counselor availability for edit:', error);
        showError('Error loading counselor availability: ' + error.message);
    }
}

// Populate time options for edit modal
function populateTimeOptionsForEdit(timeSlots, bookedTimes = []) {
    const timeSelect = document.getElementById('editPreferredTime');
    const currentValue = timeSelect.value;
    
    if (timeSlots.length === 0) {
        timeSelect.innerHTML = '<option value="">No available time slots for this date</option>';
        return;
    }

    // Generate 30-minute increment range labels from provided slots
    const incrementTimes = generateHalfHourRangeLabelsFromSlots(timeSlots);

    if (incrementTimes.length === 0) {
        timeSelect.innerHTML = '<option value="">No available time slots for this date</option>';
        return;
    }

    timeSelect.innerHTML = '<option value="">Select a time</option>';
    
    // If current value isn't part of availability anymore, keep it selectable and selected
    const bookedSet = new Set(bookedTimes);
    const filtered = incrementTimes.filter(t => !bookedSet.has(t) || t === currentValue);
    const hasCurrent = currentValue && filtered.includes(currentValue);
    
    if (!hasCurrent && currentValue) {
        const currentOpt = document.createElement('option');
        currentOpt.value = currentValue;
        currentOpt.textContent = currentValue + ' (current)';
        currentOpt.selected = true;
        timeSelect.appendChild(currentOpt);
    }

    filtered.forEach(t => {
        const option = document.createElement('option');
        option.value = t;
        option.textContent = t;
        if (t === currentValue) {
            option.selected = true;
        }
        timeSelect.appendChild(option);
    });
}

// Update follow-up appointment
async function updateFollowUp() {
    const form = document.getElementById('editFollowUpForm');
    const formData = new FormData(form);
    const updateBtn = document.getElementById('updateFollowUpBtn');

    // Since preferred_time select is disabled, manually include its value
    const preferredTime = document.getElementById('editPreferredTime').value;
    if (preferredTime) {
        formData.set('preferred_time', preferredTime);
    }

    // Validate required fields
    const requiredFields = ['id', 'preferred_date', 'preferred_time', 'consultation_type'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            showError(`Please fill in all required fields`);
            return;
        }
    }

    // Show loading state
    showButtonLoading(updateBtn, 'Updating...');

    try {
        const response = await fetch((window.BASE_URL || '/') + 'counselor/follow-up/edit', {
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
            showSuccess(data.message || 'Follow-up session updated successfully');
            
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editFollowUpModal'));
            if (modal) {
                modal.hide();
            }
            
            // Refresh the completed appointments list to update the badge
            await loadCompletedAppointments();
            
            // Refresh the follow-up sessions
            if (currentParentAppointmentId) {
                await openFollowUpSessionsModal(currentParentAppointmentId, currentStudentId);
            }
        } else {
            showError(data.message || 'Failed to update follow-up session');
        }
    } catch (error) {
        console.error('Error updating follow-up session:', error);
        showError('Error updating follow-up session: ' + error.message);
    } finally {
        // Hide loading state
        hideButtonLoading(updateBtn, 'Update Follow-up');
    }
}