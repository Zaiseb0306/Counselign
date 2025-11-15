// Fetch and render announcements from the backend
async function fetchAnnouncements() {
    try {
        const res = await fetch((window.BASE_URL || '/') + 'admin/announcements/api');
        if (!res.ok) {
            console.error(`Server responded with status: ${res.status}`);
            const errorText = await res.text();
            console.error('Error response:', errorText);
            return [];
        }
        const data = await res.json();
        return data.success ? data.data : [];
    } catch (error) {
        console.error('Error fetching announcements:', error);
        return [];
    }
}

async function renderAnnouncements() {
    const list = document.getElementById('announcements-list');
    if (!list) {
        console.error('Announcements list element not found');
        return;
    }
    
    list.innerHTML = '<div class="text-center text-secondary">Loading...</div>';
    
    try {
        const announcements = await fetchAnnouncements();
        
        if (announcements.length === 0) {
            list.innerHTML = '<div class="text-center text-secondary">No announcements yet.</div>';
            return;
        }
        
        list.innerHTML = '';
        announcements.forEach((a) => {
            const item = document.createElement('div');
            item.className = 'announcement-item position-relative';

            const titleEl = document.createElement('div');
            titleEl.className = 'announcement-title';
            titleEl.textContent = a.title || '';

            const metaEl = document.createElement('div');
            metaEl.className = 'announcement-meta';
            metaEl.textContent = a.created_at ? new Date(a.created_at).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

            const contentEl = document.createElement('div');
            contentEl.className = 'announcement-content';
            contentEl.textContent = a.content || '';

            const actionsEl = document.createElement('div');
            actionsEl.className = 'announcement-actions';

            const editBtn = document.createElement('button');
            editBtn.className = 'btn btn-sm btn-primary';
            editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
            editBtn.addEventListener('click', function() {
                editAnnouncement(a.id, encodeURIComponent(a.title || ''), encodeURIComponent(a.content || ''));
            });

            const delBtn = document.createElement('button');
            delBtn.className = 'btn btn-sm btn-danger ms-2';
            delBtn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
            delBtn.addEventListener('click', function() {
                deleteAnnouncement(a.id);
            });

            actionsEl.appendChild(editBtn);
            actionsEl.appendChild(delBtn);

            item.appendChild(titleEl);
            item.appendChild(metaEl);
            item.appendChild(contentEl);
            item.appendChild(actionsEl);

            list.appendChild(item);
        });
    } catch (error) {
        console.error('Error rendering announcements:', error);
        list.innerHTML = '<div class="text-center text-danger">Error loading announcements. Please try again later.</div>';
    }
}

// Add announcement
const announcementForm = document.getElementById('announcement-form');
if (announcementForm) {
    announcementForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form values
        const title = document.getElementById('announcement-title').value.trim();
        const content = document.getElementById('announcement-content').value.trim();
        
        // Client-side validation
        if (!title) {
            showNotification('Announcement title is required.', 'error');
            document.getElementById('announcement-title').focus();
            return;
        }
        
        if (!content) {
            showNotification('Announcement content is required.', 'error');
            document.getElementById('announcement-content').focus();
            return;
        }
        
        // Show loading state
        const submitButton = announcementForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        try {
            const res = await fetch((window.BASE_URL || '/') + 'admin/announcements/api', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, content })
            });
            
            // Check if response is OK before trying to parse JSON
            if (!res.ok) {
                const errorText = await res.text();
                throw new Error(`Server responded with status ${res.status}: ${errorText}`);
            }
            
            // Parse response
            const data = await res.json();
            
            if (data.success) {
                showNotification('Announcement added successfully!', 'success');
                announcementForm.reset();
                closeAnnouncementModal();
                renderAnnouncements();
            } else {
                // This is an expected response structure for validation failures
                showNotification(data.message || 'Failed to add announcement.', 'error');
            }
        } catch (error) {
            console.error('Error submitting announcement:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
}

// Edit announcement
const editAnnouncementForm = document.getElementById('edit-announcement-form');
if (editAnnouncementForm) {
    editAnnouncementForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form values
        const newTitle = document.getElementById('edit-announcement-title').value.trim();
        const newContent = document.getElementById('edit-announcement-content').value.trim();
        
        // Client-side validation
        if (!newTitle) {
            showNotification('Announcement title is required.', 'error');
            document.getElementById('edit-announcement-title').focus();
            return;
        }
        
        if (!newContent) {
            showNotification('Announcement content is required.', 'error');
            document.getElementById('edit-announcement-content').focus();
            return;
        }
        
        if (!currentEditId) {
            showNotification('Error: No announcement selected for editing.', 'error');
            return;
        }
        
        // Show loading state
        const submitButton = editAnnouncementForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        try {
            const res = await fetch((window.BASE_URL || '/') + `admin/announcements/api/${currentEditId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: newTitle, content: newContent })
            });
            
            // Check if response is OK before trying to parse JSON
            if (!res.ok) {
                const errorText = await res.text();
                throw new Error(`Server responded with status ${res.status}: ${errorText}`);
            }
            
            // Parse response
            const data = await res.json();
            
            if (data.success) {
                showNotification('Announcement updated successfully!', 'success');
                closeEditAnnouncementModal();
                renderAnnouncements();
            } else {
                // This is an expected response structure for validation failures
                showNotification(data.message || 'Failed to update announcement.', 'error');
            }
        } catch (error) {
            console.error('Error updating announcement:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
}

// Replace old editAnnouncement with modal version
window.editAnnouncement = function(id, title, content) {
    openEditAnnouncementModal(id, title, content);
};

// Simple confirm modal helpers
let pendingDeleteAction = null;
function openConfirmDeleteModal(message, onConfirm) {
    const modal = document.getElementById('confirmDeleteModal');
    const msg = document.getElementById('confirmDeleteMessage');
    const btn = document.getElementById('confirmDeleteBtn');
    if (!modal || !msg || !btn) {
        // Fallback if modal not present
        if (confirm(message)) onConfirm();
        return;
    }
    msg.textContent = message;
    pendingDeleteAction = onConfirm;
    // Remove old listener to avoid stacking
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.addEventListener('click', function() {
        if (typeof pendingDeleteAction === 'function') {
            pendingDeleteAction();
        }
        closeConfirmDeleteModal();
        pendingDeleteAction = null;
    });
    modal.style.display = 'block';
}

function closeConfirmDeleteModal() {
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) modal.style.display = 'none';
}

// Delete announcement (uses confirm modal)
window.deleteAnnouncement = function(id) {
    openConfirmDeleteModal('Are you sure you want to delete this announcement? This action cannot be undone.', async function() {
        try {
            const res = await fetch((window.BASE_URL || '/') + `admin/announcements/api/${id}`, {
                method: 'DELETE'
            });
            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (jsonErr) {
                showNotification('Server error: Invalid response.', 'error');
                console.error('Invalid JSON from server:', text);
                return;
            }
            if (data.success) {
                showNotification('Announcement deleted.', 'success');
                renderAnnouncements();
            } else {
                showNotification(data.message || 'Failed to delete announcement.', 'error');
            }
        } catch (error) {
            console.error('Error deleting announcement:', error);
            showNotification('An unexpected error occurred.', 'error');
        }
    });
};

// Event Modal Functions
function openEventModal() {
    document.getElementById('eventModal').style.display = 'block';
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
}

// Announcement Modal Functions
function openAnnouncementModal() {
    document.getElementById('announcementModal').style.display = 'block';
}

function closeAnnouncementModal() {
    document.getElementById('announcementModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const announcementModal = document.getElementById('announcementModal');
    const eventModal = document.getElementById('eventModal');
    const editAnnouncementModal = document.getElementById('editAnnouncementModal');
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    
    if (event.target === announcementModal) {
        closeAnnouncementModal();
    }
    if (event.target === eventModal) {
        closeEventModal();
    }
    if (event.target === editAnnouncementModal) {
        closeEditAnnouncementModal();
    }
    if (event.target === confirmDeleteModal) {
        closeConfirmDeleteModal();
    }
}

// Handle event form submission
const eventForm = document.getElementById('eventForm');
if (eventForm) {
    eventForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form values
        const eventTitle = document.getElementById('eventTitle').value.trim();
        const eventDescription = document.getElementById('eventDescription').value.trim();
        const eventDate = document.getElementById('eventDate').value.trim();
        const eventTime = document.getElementById('eventTime').value.trim();
        const eventLocation = document.getElementById('eventLocation').value.trim();
        
        // Client-side validation
        if (!eventTitle) {
            showNotification('Event title is required.', 'error');
            document.getElementById('eventTitle').focus();
            return;
        }
        
        if (!eventDescription) {
            showNotification('Event description is required.', 'error');
            document.getElementById('eventDescription').focus();
            return;
        }
        
        if (!eventDate) {
            showNotification('Event date is required.', 'error');
            document.getElementById('eventDate').focus();
            return;
        }
        
        if (!eventTime) {
            showNotification('Event time is required.', 'error');
            document.getElementById('eventTime').focus();
            return;
        }
        
        if (!eventLocation) {
            showNotification('Event location is required.', 'error');
            document.getElementById('eventLocation').focus();
            return;
        }
        
        // All validation passed, create event data object
        const eventData = {
            title: eventTitle,
            description: eventDescription,
            date: eventDate,
            time: eventTime,
            location: eventLocation
        };
        
        // Show loading state
        const submitButton = eventForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        try {
            const res = await fetch((window.BASE_URL || '/') + 'admin/events/api', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(eventData)
            });
            
            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (jsonErr) {
                showNotification('Server error: Invalid response. Please contact admin.', 'error');
                console.error('Invalid JSON from server:', text);
                return;
            }
            
            if (data.success) {
                showNotification('Event added successfully!', 'success');
                closeEventModal();
                eventForm.reset();
                renderEvents();
            } else {
                showNotification(data.message || 'Failed to add event.', 'error');
            }
        } catch (err) {
            console.error('Fetch error:', err);
            showNotification('An error occurred while communicating with the server.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
}

function showNotification(message, type = 'success') {
    // Remove any existing notification
    const old = document.getElementById('custom-notification');
    if (old) old.remove();

    const notification = document.createElement('div');
    notification.id = 'custom-notification';
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.bottom = '30px';
    notification.style.right = '30px';
    notification.style.padding = '16px 28px';
    notification.style.borderRadius = '8px';
    notification.style.background = type === 'success' ? 'linear-gradient(90deg,#2196f3 60%,#0d47a1 100%)' : '#dc3545';
    notification.style.color = '#fff';
    notification.style.fontWeight = 'bold';
    notification.style.zIndex = 9999;
    notification.style.boxShadow = '0 2px 8px rgba(33,150,243,0.18)';
    notification.style.fontSize = '1rem';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Fetch and render events from the backend
async function fetchEvents() {
    try {
        const res = await fetch((window.BASE_URL || '/') + 'admin/events/api');
        if (!res.ok) {
            console.error(`Server responded with status: ${res.status}`);
            const errorText = await res.text();
            console.error('Error response:', errorText);
            return [];
        }
        const data = await res.json();
        return data.success && Array.isArray(data.data) ? data.data : [];
    } catch (error) {
        console.error('Error fetching events:', error);
        return [];
    }
}

async function renderEvents() {
    const list = document.getElementById('events-list');
    if (!list) {
        console.error('Events list element not found');
        return;
    }
    list.innerHTML = '<div class="text-center text-secondary">Loading...</div>';
    try {
        const events = await fetchEvents();
        if (events.length === 0) {
            list.innerHTML = '<div class="text-center text-secondary">No events yet.</div>';
            return;
        }
        list.innerHTML = '';
        events.forEach((e) => {
            const div = document.createElement('div');
            div.className = 'announcement-item position-relative';
            div.innerHTML = `
                <div class="announcement-title">${e.title}</div>
                <div class="announcement-meta">${e.date ? new Date(e.date + 'T' + (e.time || '00:00')).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''} ${e.location ? '| ' + e.location : ''}</div>
                <div class="announcement-content">${e.description}</div>
                <div class="announcement-actions">
                    <button class="btn btn-sm btn-primary" onclick="editEvent(${e.id}, '${encodeURIComponent(e.title)}', '${encodeURIComponent(e.description)}', '${encodeURIComponent(e.date)}', '${encodeURIComponent(e.time)}', '${encodeURIComponent(e.location)}')"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-sm btn-danger ms-2" onclick="deleteEvent(${e.id})"><i class="fas fa-trash-alt"></i> Delete</button>
                </div>
            `;
            list.appendChild(div);
        });
    } catch (error) {
        console.error('Error rendering events:', error);
        list.innerHTML = '<div class="text-center text-danger">Error loading events. Please try again later.</div>';
    }
}

// Edit event
let currentEventEditId = null;

function openEditEventModal(id, title, description, date, time, location) {
    currentEventEditId = id;
    document.getElementById('editEventTitle').value = decodeURIComponent(title);
    document.getElementById('editEventDescription').value = decodeURIComponent(description);
    document.getElementById('editEventDate').value = decodeURIComponent(date);
    document.getElementById('editEventTime').value = decodeURIComponent(time);
    document.getElementById('editEventLocation').value = decodeURIComponent(location);
    document.getElementById('editEventModal').style.display = 'block';
}

function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
    currentEventEditId = null;
}

// Edit event form submission
const editEventForm = document.getElementById('edit-event-form');
if (editEventForm) {
    editEventForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Get form values
        const newTitle = document.getElementById('editEventTitle').value.trim();
        const newDescription = document.getElementById('editEventDescription').value.trim();
        const newDate = document.getElementById('editEventDate').value.trim();
        const newTime = document.getElementById('editEventTime').value.trim();
        const newLocation = document.getElementById('editEventLocation').value.trim();
        
        // Client-side validation
        if (!newTitle) {
            showNotification('Event title is required.', 'error');
            document.getElementById('editEventTitle').focus();
            return;
        }
        
        if (!newDescription) {
            showNotification('Event description is required.', 'error');
            document.getElementById('editEventDescription').focus();
            return;
        }
        
        if (!newDate) {
            showNotification('Event date is required.', 'error');
            document.getElementById('editEventDate').focus();
            return;
        }
        
        if (!newTime) {
            showNotification('Event time is required.', 'error');
            document.getElementById('editEventTime').focus();
            return;
        }
        
        if (!newLocation) {
            showNotification('Event location is required.', 'error');
            document.getElementById('editEventLocation').focus();
            return;
        }
        
        if (!currentEventEditId) {
            showNotification('Error: No event selected for editing.', 'error');
            return;
        }
        
        // Show loading state
        const submitButton = editEventForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        try {
            const res = await fetch((window.BASE_URL || '/') + `admin/events/api/${currentEventEditId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: newTitle,
                    description: newDescription,
                    date: newDate,
                    time: newTime,
                    location: newLocation
                })
            });
            
            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (jsonErr) {
                showNotification('Server error: Invalid response. Please contact admin.', 'error');
                console.error('Invalid JSON from server:', text);
                return;
            }
            
            if (data.success) {
                showNotification('Event updated successfully!', 'success');
                closeEditEventModal();
                renderEvents();
            } else {
                showNotification(data.message || 'Failed to update event.', 'error');
            }
        } catch (error) {
            console.error('Error updating event:', error);
            showNotification('An unexpected error occurred.', 'error');
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    });
}

// Make editEvent function globally available
window.editEvent = function(id, title, description, date, time, location) {
    openEditEventModal(id, title, description, date, time, location);
};

// Delete event (uses confirm modal)
window.deleteEvent = function(id) {
    openConfirmDeleteModal('Are you sure you want to delete this event? This action cannot be undone.', async function() {
        try {
            const res = await fetch((window.BASE_URL || '/') + `admin/events/api/${id}`, {
                method: 'DELETE'
            });
            let data;
            const text = await res.text();
            try {
                data = JSON.parse(text);
            } catch (jsonErr) {
                showNotification('Server error: Invalid response.', 'error');
                console.error('Invalid JSON from server:', text);
                return;
            }
            if (data.success) {
                showNotification('Event deleted.', 'success');
                renderEvents();
            } else {
                showNotification(data.message || 'Failed to delete event.', 'error');
            }
        } catch (error) {
            console.error('Error deleting event:', error);
            showNotification('An unexpected error occurred.', 'error');
        }
    });
};

// Add this function to open and populate the edit announcement modal
function openEditAnnouncementModal(id, title, content) {
    window.currentEditId = id;
    document.getElementById('edit-announcement-title').value = decodeURIComponent(title);
    document.getElementById('edit-announcement-content').value = decodeURIComponent(content);
    document.getElementById('editAnnouncementModal').style.display = 'block';
}

function closeEditAnnouncementModal() {
    document.getElementById('editAnnouncementModal').style.display = 'none';
    window.currentEditId = null;
}

// Initial render
document.addEventListener('DOMContentLoaded', function() {
    renderAnnouncements();
    renderEvents();
});