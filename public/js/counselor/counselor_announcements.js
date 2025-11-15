// Counselor Announcements and Calendar JavaScript

let currentDate = new Date();
let events = [];
let announcements = [];

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    SecureLogger.info('Counselor Announcements page loaded');
    
    // Initialize calendar
    initializeCalendar();
    
                    // Load announcements and events
                    loadAnnouncements();
                    loadEvents();
    
    // Set up calendar navigation
    setupCalendarNavigation();
});

// Initialize calendar
function initializeCalendar() {
    renderCalendar();
}

// Render calendar for current month
function renderCalendar() {
    const calendarGrid = document.getElementById('calendarGrid');
    const currentMonthElement = document.getElementById('currentMonth');
    
    if (!calendarGrid || !currentMonthElement) return;
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update month display
    currentMonthElement.textContent = currentDate.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    
    // Clear calendar
    calendarGrid.innerHTML = '';
    
    // Add day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day other-month';
        emptyDay.textContent = '';
        calendarGrid.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.innerHTML = `<span class="date-number">${day}</span>`;
        
        const currentDay = new Date(year, month, day);
        const today = new Date();
        
        // Check if it's today
        if (currentDay.toDateString() === today.toDateString()) {
            dayElement.classList.add('today');
        }
        
        // Check if there are events on this day (exclude announcements from markers)
        const dayItems = events.filter(ev => {
            if (!ev.date) return false;
            const d = new Date(ev.date);
            return d.toDateString() === currentDay.toDateString();
        });
        if (dayItems.length > 0) {
            dayElement.classList.add('has-event');
            dayElement.title = dayItems.map(item => item.title).join(', ');
            
            // Badge showing number of events (only events counted)
            const eventsCount = dayItems.filter(i => i.type === 'event').length;
            if (eventsCount > 0) {
                const badge = document.createElement('span');
                badge.className = 'event-count-badge';
                badge.textContent = String(eventsCount);
                dayElement.appendChild(badge);
            }
            
            // No title overlay on dates (kept interactive on click)
            
            // Add click event for showing details
            dayElement.addEventListener('click', function() {
                const dayEvents = dayItems;
                const dayAnnouncements = [];
                showDateDetails(currentDay, dayEvents, dayAnnouncements);
            });
            
            // Add tooltip functionality
            addTooltipToDay(dayElement, dayItems);
        }
        
        calendarGrid.appendChild(dayElement);
    }
}

// Get events and announcements for a specific date
function getItemsForDate(date) {
    // Deprecated in favor of direct events filtering above
    const items = events.filter(ev => {
        const d = new Date(ev.date);
        return d.toDateString() === date.toDateString();
    });
    return items;
}

// Add tooltip to calendar day
function addTooltipToDay(dayElement, dayItems) {
    let tooltip = null;
    
    dayElement.addEventListener('mouseenter', function(e) {
        if (dayItems.length > 0) {
            tooltip = document.createElement('div');
            tooltip.className = 'event-tooltip';
            tooltip.innerHTML = dayItems.map(item => {
                const timeInfo = item.time ? `<br><small>${item.time}</small>` : '';
                return `<div><strong>${item.title}</strong>${timeInfo}</div>`;
            }).join('<br>');
            
            document.body.appendChild(tooltip);
            
            const rect = dayElement.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.opacity = '1';
        }
    });
    
    dayElement.addEventListener('mouseleave', function() {
        if (tooltip) {
            tooltip.remove();
            tooltip = null;
        }
    });
}

// Setup calendar navigation
function setupCalendarNavigation() {
    const prevButton = document.getElementById('prevMonth');
    const nextButton = document.getElementById('nextMonth');
    
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });
    }
}

// Load announcements
async function loadAnnouncements() {
    try {
        const response = await fetch(window.BASE_URL + 'counselor/announcements/all');
        const data = await response.json();
        
        if (data.status === 'success' && data.announcements) {
            displayAnnouncements(data.announcements);
            // Update announcements array for calendar
            announcements = data.announcements.map(announcement => ({
                title: announcement.title,
                date: announcement.created_at,
                content: announcement.content,
                type: 'announcement'
            }));
            // Re-render calendar with announcements
            renderCalendar();
        } else {
            console.error('Failed to load announcements:', data.message);
            showErrorMessage('Failed to load announcements');
        }
    } catch (error) {
        console.error('Error loading announcements:', error);
        showErrorMessage('Error loading announcements');
    }
}

// Load events
async function loadEvents() {
    try {
        const response = await fetch(window.BASE_URL + 'counselor/events/all');
        const data = await response.json();
        
        if (data.status === 'success' && data.events) {
            displayEvents(data.events);
            // Update events array for calendar
            events = data.events.map(event => ({
                title: event.title,
                date: event.date,
                time: event.time,
                description: event.description,
                type: 'event'
            }));
            // Re-render calendar with events
            renderCalendar();
                } else {
            console.error('Failed to load events:', data.message);
            showErrorMessage('Failed to load events');
                }
    } catch (error) {
                console.error('Error loading events:', error);
        showErrorMessage('Error loading events');
    }
}

// Display announcements
function displayAnnouncements(announcements) {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    if (announcements.length === 0) {
        announcementsList.innerHTML = '<div class="no-content">No announcements available</div>';
        return;
    }

    announcementsList.innerHTML = announcements.map((announcement, index) => {
        // Parse date for badge
        let announcementDate = announcement.created_at ? new Date(announcement.created_at) : null;
        let badgeMonth = announcementDate ? announcementDate.toLocaleString('en-US', { month: 'short' }).toUpperCase() : '';
        let badgeDay = announcementDate ? String(announcementDate.getDate()).padStart(2, '0') : '';

        // Parse posted date for meta
        let postedDate = announcementDate ? announcementDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) : '';

        return `
            <div class="modern-announcement-card">
                <div class="announcement-badge">
                    <div class="announcement-badge-month">${badgeMonth}</div>
                    <div class="announcement-badge-day">${badgeDay}</div>
                </div>
                <div class="announcement-info">
                    <div class="announcement-title">${announcement.title}</div>
                    <div class="announcement-meta">
                        <span><i class='fas fa-calendar'></i> Posted: ${postedDate}</span>
                    </div>
                    <div class="announcement-description">${announcement.content}</div>
                </div>
            </div>
        `;
    }).join('');
}

// Display events
function displayEvents(events) {
    const eventsList = document.getElementById('eventsList');
    if (!eventsList) return;
    
    if (events.length === 0) {
        eventsList.innerHTML = '<div class="no-content">No events scheduled</div>';
        return;
    }

    eventsList.innerHTML = events.map((event, index) => {
        // Parse date for badge
        let eventDate = event.date ? new Date(event.date) : null;
        let badgeMonth = eventDate ? eventDate.toLocaleString('en-US', { month: 'short' }).toUpperCase() : '';
        let badgeDay = eventDate ? String(eventDate.getDate()).padStart(2, '0') : '';

        // Parse event date for meta
        let eventDateFormatted = eventDate ? eventDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) : '';

        // Format time for display
        let eventTime = event.time ? new Date(`1970-01-01T${event.time}`).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        }) : '';

        return `
            <div class="modern-event-card">
                <div class="event-badge">
                    <div class="event-badge-month">${badgeMonth}</div>
                    <div class="event-badge-day">${badgeDay}</div>
                </div>
                <div class="event-info">
                    <div class="event-title">${event.title}</div>
                    <div class="event-meta">
                        ${eventTime ? `<span><i class='fas fa-clock'></i> ${eventTime}</span>` : ''}
                        ${event.location ? `<span><i class='fas fa-map-marker-alt'></i> ${event.location}</span>` : ''}
                    </div>
                    <div class="event-description">${event.description}</div>
                </div>
            </div>
        `;
    }).join('');
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Show error message
function showErrorMessage(message) {
    console.error(message);
    // You can implement a toast notification or alert here
}

// Drawer functionality removed (inline calendar now on page)

// Format date for comparison (YYYY-MM-DD)
function formatDateForComparison(date) {
    return date.toISOString().split('T')[0];
}

// Show date details modal
function showDateDetails(clickedDate, events, announcements) {
    const itemsCount = (events ? events.length : 0) + (announcements ? announcements.length : 0);
    const sizeClass = itemsCount <= 1 ? '' : (itemsCount <= 3 ? 'modal-lg' : 'modal-xl');
    const inlineMaxWidth = itemsCount <= 1 ? 'max-width: 600px;' : (itemsCount <= 3 ? 'max-width: 900px;' : 'max-width: 1140px;');
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="dateDetailsModal" tabindex="-1" aria-labelledby="dateDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog ${sizeClass} modal-dialog-centered" style="${inlineMaxWidth}">
                <div class="modal-content">
                        <div class="modal-header">
                        <h5 class="modal-title" id="dateDetailsModalLabel">
                            <i class="fas fa-calendar-day me-2"></i>
                            ${formatDateForDisplay(clickedDate)}
                        </h5>
                    </div>
                    <div class="modal-body">
                        ${generateDateDetailsContent(events, announcements)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('dateDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('dateDetailsModal'));
    modal.show();
    
    // Clean up when modal is hidden
    document.getElementById('dateDetailsModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Format date for display
function formatDateForDisplay(date) {
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Generate content for date details modal
function generateDateDetailsContent(events, announcements) {
    let content = '';
    
    if (events.length > 0) {
        const eventCol = events.length > 1 ? 'col-md-6' : 'col-12';
        content += `
            <div class="mb-4">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-calendar-check me-2"></i>
                    Events (${events.length})
                </h6>
                <div class="row">
        `;
        
        events.forEach(event => {
            const eventTime = event.time ? new Date(`1970-01-01T${event.time}`).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            }) : 'Time TBD';
            
            content += `
                <div class="${eventCol} mb-3">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">${event.title}</h6>
                            <p class="card-text" style="white-space: pre-line;">${event.description || 'No description available'}</p>
                            <div class="d-flex align-items-center text-muted small gap-3">
                                <span class="d-flex align-items-center"><i class="fas fa-clock me-1"></i><span>${eventTime}</span></span>
                                ${event.location ? `<span class=\"d-flex align-items-center\"><i class=\"fas fa-map-marker-alt me-1\"></i><span>${event.location}</span></span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        content += `
                </div>
            </div>
        `;
    }
    
    if (announcements.length > 0) {
        const annCol = announcements.length > 1 ? 'col-md-6' : 'col-12';
        content += `
            <div class="mb-4">
                <h6 class="text-success mb-3">
                    <i class="fas fa-bullhorn me-2"></i>
                    Announcements (${announcements.length})
                </h6>
                <div class="row">
        `;
        
        announcements.forEach(announcement => {
            const announcementDate = new Date(announcement.date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            content += `
                <div class="${annCol} mb-3">
                    <div class="card border-success">
                        <div class="card-body">
                            <h6 class="card-title text-success">${announcement.title}</h6>
                            <p class="card-text" style="white-space: pre-line;">${announcement.content}</p>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="fas fa-calendar me-2"></i>
                                <span>Posted: ${announcementDate}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        content += `
                </div>
            </div>
        `;
    }
    
    if (events.length === 0 && announcements.length === 0) {
        content = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                <p>No events or announcements for this date.</p>
            </div>
        `;
    }
    
    return content;
}

// Set BASE_URL if not already set
if (typeof window.BASE_URL === 'undefined') {
    window.BASE_URL = window.location.origin + '/';
}