// Global variables
let appointmentsData = [];
let currentFilters = {
    status: 'all',
    dateFrom: '',
    dateTo: ''
};

// Document ready function
document.addEventListener('DOMContentLoaded', function () {
    SecureLogger.info('DOM loaded, initializing admin dashboard...');

    // Load admin data
    loadAdminData();

    // Update unread messages badge
    updateMessagesBadgeFromServer();
    setInterval(updateMessagesBadgeFromServer, 10000); // Poll every 10s

    // Check if we're on the appointments page before loading appointments
    const appointmentsTableBody = document.getElementById('appointments-table-body');
    if (appointmentsTableBody) {
        loadAppointments();

        // Set up appointment-related event listeners
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                applyFilters();
            });
        }

        const resetFiltersBtn = document.getElementById('reset-filters');
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', resetFilters);
        }
    }

    SecureLogger.info('DOM loaded, calling loadLatestAppointments...');

    updateRecentMessages();
    // Update messages every 30 seconds
    setInterval(updateRecentMessages, 30000);
});

// Load appointments with current filters
function loadAppointments() {
    // Show loading indicator
    document.getElementById('appointments-table-body').innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

    // Build the query URL with filters
    let url = '../includes/get_appointments.php?';

    if (currentFilters.status && currentFilters.status !== 'all') {
        url += `status=${encodeURIComponent(currentFilters.status)}&`;
    }

    if (currentFilters.dateFrom) {
        url += `date_from=${encodeURIComponent(currentFilters.dateFrom)}&`;
    }

    if (currentFilters.dateTo) {
        url += `date_to=${encodeURIComponent(currentFilters.dateTo)}&`;
    }

    // Fetch data from server
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            appointmentsData = data;
            renderAppointmentsTable(appointmentsData);
            updateAppointmentStats(appointmentsData);
        })
        .catch(error => {
            console.error('Error fetching appointments:', error);
            document.getElementById('appointments-table-body').innerHTML =
                '<tr><td colspan="6" class="text-center text-danger">Error loading appointments. Please try again.</td></tr>';
        });
}

// Render appointments table
function renderAppointmentsTable(appointments) {
    const tableBody = document.getElementById('appointments-table-body');

    if (appointments.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No appointments found</td></tr>';
        return;
    }

    tableBody.innerHTML = '';

    appointments.forEach(appointment => {
        // Format date to display nicely
        const appointmentDate = new Date(appointment.appointment_date);
        const formattedDate = appointmentDate.toLocaleDateString();

        // Create status badge with appropriate color
        let statusBadgeClass = '';
        switch (appointment.status) {
            case 'Pending':
                statusBadgeClass = 'badge-warning';
                break;
            case 'Approved':
                statusBadgeClass = 'badge-primary';
                break;
            case 'Completed':
                statusBadgeClass = 'badge-success';
                break;
            case 'Cancelled':
                statusBadgeClass = 'badge-danger';
                break;
            case 'Rescheduled':
                statusBadgeClass = 'badge-info';
                break;
        }

        // Create row HTML
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${appointment.appointment_id}</td>
            <td>${appointment.user_name}</td>
            <td>${formattedDate}</td>
            <td>${appointment.appointment_time}</td>
            <td>
                <span class="badge ${statusBadgeClass}">${appointment.status}</span>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                        Actions
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" onclick="viewAppointmentDetails(${appointment.appointment_id})">View Details</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'Approved')">Approve</a>
                        <a class="dropdown-item" href="#" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'Completed')">Mark Completed</a>
                        <a class="dropdown-item" href="#" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'Cancelled')">Cancel</a>
                        <a class="dropdown-item" href="#" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'Rescheduled')">Reschedule</a>
                    </div>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Update appointment status
function updateAppointmentStatus(appointmentId, newStatus) {
    if (!confirm(`Are you sure you want to change this appointment status to "${newStatus}"?`)) {
        return;
    }

    // Prepare request body
    const requestBody = {
        appointment_id: appointmentId,
        status: newStatus
    };

    // Send update request
    fetch((window.BASE_URL || '/') + 'admin/update_appointment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
    })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('Success!', `Appointment status updated to ${newStatus}`, 'success');

                // Reload appointments to reflect changes
                loadAppointments();
            } else {
                throw new Error(data.error || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error updating appointment status:', error);
            showNotification('Error', error.message || 'Failed to update appointment status', 'danger');
        });
}

// View appointment details
function viewAppointmentDetails(appointmentId) {
    const appointment = appointmentsData.find(a => a.appointment_id == appointmentId);

    if (!appointment) {
        showNotification('Error', 'Appointment not found', 'danger');
        return;
    }

    // Format date
    const appointmentDate = new Date(appointment.appointment_date);
    const formattedDate = appointmentDate.toLocaleDateString();

    // Create modal content
    const modalContent = `
        <div class="appointment-details">
            <p><strong>Appointment ID:</strong> ${appointment.appointment_id}</p>
            <p><strong>Client Name:</strong> ${appointment.user_name}</p>
            <p><strong>Email:</strong> ${appointment.user_email}</p>
            <p><strong>Date:</strong> ${formattedDate}</p>
            <p><strong>Time:</strong> ${appointment.appointment_time}</p>
            <p><strong>Status:</strong> ${appointment.status}</p>
            <p><strong>Service:</strong> ${appointment.service || 'N/A'}</p>
            <p><strong>Notes:</strong> ${appointment.notes || 'No additional notes'}</p>
        </div>
    `;

    // Show modal with appointment details
    document.getElementById('appointmentDetailsModalBody').innerHTML = modalContent;
    $('#appointmentDetailsModal').modal('show');
}

// Apply filters from form
function applyFilters() {
    const statusFilter = document.getElementById('status-filter').value;
    const dateFromFilter = document.getElementById('date-from').value;
    const dateToFilter = document.getElementById('date-to').value;

    currentFilters = {
        status: statusFilter,
        dateFrom: dateFromFilter,
        dateTo: dateToFilter
    };

    loadAppointments();
}

// Reset filters
function resetFilters() {
    document.getElementById('status-filter').value = 'all';
    document.getElementById('date-from').value = '';
    document.getElementById('date-to').value = '';

    currentFilters = {
        status: 'all',
        dateFrom: '',
        dateTo: ''
    };

    loadAppointments();
}

// Update appointment statistics
function updateAppointmentStats(appointments) {
    // Count appointments by status
    const stats = {
        total: appointments.length,
        pending: 0,
        approved: 0,
        completed: 0,
        cancelled: 0
    };

    appointments.forEach(appointment => {
        switch (appointment.status) {
            case 'Pending':
                stats.pending++;
                break;
            case 'Approved':
                stats.approved++;
                break;
            case 'Completed':
                stats.completed++;
                break;
            case 'Cancelled':
                stats.cancelled++;
                break;
        }
    });

    // Update stats in the DOM
    document.getElementById('total-appointments').textContent = stats.total;
    document.getElementById('pending-appointments').textContent = stats.pending;
    document.getElementById('approved-appointments').textContent = stats.approved;
    document.getElementById('completed-appointments').textContent = stats.completed;
    document.getElementById('cancelled-appointments').textContent = stats.cancelled;
}

// Show notification
function showNotification(title, message, type) {
    // If using a notification library, implement accordingly
    // This is a simple implementation that assumes you have a notification container
    const notificationContainer = document.getElementById('notification-container');

    if (notificationContainer) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            <strong>${title}</strong> ${message}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        `;

        notificationContainer.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 150);
        }, 5000);
    } else {
        // Fallback to alert if notification container doesn't exist
        alert(`${title}: ${message}`);
    }
}

// Function to redirect to profile page
function redirectToProfilePage() {
    window.location.href = (window.BASE_URL || '/') + 'admin/admins-management';
}

// Function to format date and time
function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'Never';
    const date = new Date(dateTimeStr);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

// Function to load and display admin data
function loadAdminData() {
    SecureLogger.info('Loading admin data...');
    fetch((window.BASE_URL || '/') + 'admin/dashboard/data', {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => {
            SecureLogger.info('Response status:', response.status);
            return response.text().then(text => {
                SecureLogger.info('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then(data => {
            SecureLogger.info('Parsed data:', data);
            if (data.success) {
                const adminData = data.data;
                SecureLogger.info('Admin data:', adminData);

                // Update profile picture
                const profileImg = document.getElementById('profile-img');
                if (profileImg && adminData.profile_picture) {
                    SecureLogger.info('Updating profile picture:', adminData.profile_picture);
                    profileImg.src = adminData.profile_picture;
                }

                // Update admin name
                const adminName = document.getElementById('adminName');
                if (adminName) {
                    SecureLogger.info('Updating admin name:', adminData.username);
                    adminName.textContent = adminData.username || 'Admin';
                }

                // Update last login time
                const lastLogin = document.getElementById('lastLogin');
                if (lastLogin) {
                    const formattedTime = formatDateTime(adminData.last_login);
                    SecureLogger.info('Updating last login:', formattedTime);
                    lastLogin.textContent = 'Login at: ' + formattedTime;
                }
            } else {
                console.error('Failed to load admin data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading admin data:', error);
        });
}

// Refresh admin data periodically (every 5 minutes)
setInterval(loadAdminData, 300000);


function updateMessagesBadge(count) {
    const badge = document.getElementById('messagesBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-flex';
            badge.classList.add('pulse');
            badge.title = 'New messages!';
            badge.setAttribute('aria-label', 'You have new messages');
        } else {
            badge.style.display = 'none';
            badge.classList.remove('pulse');
            badge.title = '';
            badge.setAttribute('aria-label', '');
        }
    }
}

function updateMessagesBadgeFromServer() {
    fetch((window.BASE_URL || '/') + 'admin/message/operations?action=get_conversations', {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.conversations)) {
                const unread = data.conversations.reduce((sum, conv) => sum + (parseInt(conv.unread_count) || 0), 0);
                updateMessagesBadge(unread);
            }
        })
        .catch(err => {
            // Optionally log error
        });
}

function updateRecentMessages() {
    SecureLogger.info('Updating recent messages...');
    fetch((window.BASE_URL || '/') + 'admin/message/operations?action=get_conversations', {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.conversations)) {
                // Get the two most recent conversations
                const recentMessages = data.conversations.slice(0, 2);
                const messagesContainer = document.querySelector('.dashboard-card .d-flex.flex-column.gap-3');

                if (messagesContainer) {
                    messagesContainer.innerHTML = ''; // Clear existing messages
                    let anyHighlighted = false;

                    if (recentMessages.length === 0) {
                        messagesContainer.innerHTML = `
                        <div class="p-3 bg-light rounded shadow-sm">
                            <p class="text-body-secondary">No messages yet</p>
                        </div>
                    `;
                    } else {
                        recentMessages.forEach(conv => {
                            const messageTime = conv.last_message_time ?
                                new Date(conv.last_message_time).toLocaleString() :
                                'Unknown time';

                            const hasUnread = parseInt(conv.unread_count) > 0;
                            let newBadge = '';
                            if (hasUnread) {
                                newBadge = '<span class="new-badge">New</span>';
                            }
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'p-2 bg-light rounded shadow-sm message-card';
                            messageDiv.innerHTML = `
                            <p class="text-body-secondary">From: ${conv.name} ${newBadge}</p>
                            <div class="message-content" style="transition: all 0.2s ease;" data-user-id="${conv.user_id}">
                                <p class="text-body-secondary">${conv.last_message || 'No message content'}</p>
                                <p class="small text-secondary mt-2">
                                    Received on: ${messageTime}
                                    <span class="badge ${conv.status_text === 'Online' ? 'bg-success' : 'bg-warning'}">
                                        Status: ${conv.status_text}
                                    </span>
                                </p>
                            </div>
                        `;

                            // Highlight if there are unread messages
                            if (hasUnread) {
                                messageDiv.classList.add('dashboard-message-highlight');
                                anyHighlighted = true;
                            }

                            // Add click event to open messages for this user
                            messageDiv.style.cursor = 'pointer';
                            messageDiv.addEventListener('click', function () {
                                const userId = messageDiv.querySelector('.message-content').dataset.userId;
                                localStorage.setItem('selectedConversation', userId);
                                window.location.href = (window.BASE_URL || '/') + 'admin/messages';
                            });

                            messagesContainer.appendChild(messageDiv);
                        });
                    }

                    
                    // Set or remove the highlight flag in localStorage
                    if (anyHighlighted) {
                        localStorage.setItem('dashboardMessageHighlight', 'true');
                    } else {
                        localStorage.removeItem('dashboardMessageHighlight');
                    }
                }
            }
        })
        .catch(err => {
            console.error('Error fetching recent messages:', err);
        });
}

// Call this function to initialize
updateRecentMessages();
// Set interval to update every 30 seconds
setInterval(updateRecentMessages, 30000);