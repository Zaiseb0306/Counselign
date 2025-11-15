document.addEventListener('DOMContentLoaded', function() {
    // Initialize user ID
    let userId = null;
    
    // Calendar state
    let currentDate = new Date();
    let announcements = [];
    let events = [];

    // Fetch user ID and initialize page
    fetchUserIdAndInitialize();

    // Function to fetch user ID and initialize the page
    function fetchUserIdAndInitialize() {
        fetch((window.BASE_URL || '/') + 'student/profile/get')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.user_id) {
                    userId = data.user_id;
                    // Update the user ID in the welcome message only if no display name exists
                    const userIdSpan = document.querySelector('.text-primary i');
                    const userDisplaySpan = document.getElementById('user-id-display');
                    if (userIdSpan && !userDisplaySpan) {
                        // Only update if there's no hidden user-id-display element (meaning no name was found)
                        userIdSpan.textContent = data.user_id;
                    }
                    // Load announcements and events
                    loadAnnouncements();
                    loadEvents();
                    
                    // Initialize calendar functionality
                    initializeCalendar();
                } else {
                    console.error('Failed to get user ID');
                }
            })
            .catch(error => {
                console.error('Error fetching user profile:', error);
            });
    }

    // Function to load announcements
    function loadAnnouncements() {
        fetch((window.BASE_URL || '/') + 'student/announcements/all')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    announcements = (data.announcements || []).map(a => ({
                        ...a,
                        // Normalize for calendar usage
                        date: a.created_at,
                        type: 'announcement'
                    }));
                    renderAnnouncements(announcements);
                    // Ensure calendar reflects newly loaded announcements
                    generateCalendar();
                } else {
                    showEmptyAnnouncements('No announcements available');
                }
            })
            .catch(error => {
                console.error('Error loading announcements:', error);
                showEmptyAnnouncements('Unable to load announcements');
            });
    }

    // Function to load events
    function loadEvents() {
        fetch((window.BASE_URL || '/') + 'student/events/all')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    events = (data.events || []).map(e => ({
                        ...e,
                        type: 'event'
                    }));
                    renderEvents(events);
                    // Refresh calendar to display badges and titles
                    generateCalendar();
                } else {
                    showEmptyEvents('No upcoming events');
                }
            })
            .catch(error => {
                console.error('Error loading events:', error);
                showEmptyEvents('Unable to load events');
            });
    }

    // Function to render announcements
    function renderAnnouncements(announcements) {
        const container = document.getElementById('announcementsList');
        if (!container) return;

        if (!announcements || announcements.length === 0) {
            showEmptyAnnouncements('No announcements available');
            return;
        }

        container.innerHTML = '';

        announcements.forEach(announcement => {
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

            // Build card
            const announcementElement = document.createElement('div');
            announcementElement.className = 'modern-announcement-card';
            announcementElement.innerHTML = `
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
                    <div class="mt-2">
                        <button class="btn btn-sm btn-primary see-announcement-details">See details</button>
                    </div>
                </div>
            `;

            container.appendChild(announcementElement);
            const btn = announcementElement.querySelector('.see-announcement-details');
            if (btn) btn.addEventListener('click', function() { openAnnouncementDetails(announcement); });
        });
    }

    // Function to render events
    function renderEvents(events) {
        const container = document.getElementById('eventsList');
        if (!container) return;

        if (!events || events.length === 0) {
            showEmptyEvents('No upcoming events');
            return;
        }

        container.innerHTML = '';

        events.forEach(event => {
            // Parse date for badge
            let eventDate = event.date ? new Date(event.date) : null;
            let badgeMonth = eventDate ? eventDate.toLocaleString('en-US', { month: 'short' }).toUpperCase() : '';
            let badgeDay = eventDate ? String(eventDate.getDate()).padStart(2, '0') : '';

            // Parse time
            let formattedTime = '';
            if (event.time) {
                const timeObj = new Date(`1970-01-01T${event.time}`);
                formattedTime = timeObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }

            // Event location
            let location = event.location ? event.location : '';

            // Build card
            const eventElement = document.createElement('div');
            eventElement.className = 'modern-event-card';
            eventElement.innerHTML = `
                <div class="event-badge">
                    <div class="event-badge-month">${badgeMonth}</div>
                    <div class="event-badge-day">${badgeDay}</div>
                </div>
                <div class="event-info">
                    <div class="event-title">${event.title}</div>
                    <div class="event-meta">
                        <span><i class='fas fa-clock'></i> ${formattedTime}</span>
                        <span><i class='fas fa-map-marker-alt'></i> ${location}</span>
                    </div>
                    <div class="event-description">${event.description}</div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-primary see-event-details">See details</button>
                    </div>
                </div>
            `;
            container.appendChild(eventElement);
            const btn = eventElement.querySelector('.see-event-details');
            if (btn) btn.addEventListener('click', function() { openEventDetails(event); });
        });
    }

    // Function to show empty announcements message
    function showEmptyAnnouncements(message) {
        const container = document.getElementById('announcementsList');
        if (container) {
            container.innerHTML = `
                <div class="announcement-card">
                    <div class="text-center text-gray-500">
                        ${message}
                    </div>
                </div>
            `;
        }
    }

    // Function to show empty events message
    function showEmptyEvents(message) {
        const container = document.getElementById('eventsList');
        if (container) {
            container.innerHTML = `
                <div class="event-card">
                    <div class="text-center text-gray-500">
                        ${message}
                    </div>
                </div>
            `;
        }
    }

    // Add event listener to profile avatar
    const profileAvatar = document.querySelector('.profile-avatar');
    if (profileAvatar) {
        profileAvatar.addEventListener('click', function() {
            window.location.href = "user_profile.html";
        });
    }

    // ==================== CALENDAR FUNCTIONALITY ====================
    
    // Initialize calendar functionality
    function initializeCalendar() {
        setupCalendarEventListeners();
        generateCalendar();
    }

    // Setup calendar event listeners
    function setupCalendarEventListeners() {
        // Calendar navigation
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                generateCalendar();
            });
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                generateCalendar();
            });
        }
    }

    // Drawer functions removed (inline calendar)

    // Generate calendar
    function generateCalendar() {
        const calendarGrid = document.getElementById('calendarGrid');
        const currentMonthElement = document.getElementById('currentMonth');
        
        if (!calendarGrid || !currentMonthElement) return;

        // Update month display
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        currentMonthElement.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;

        // Clear previous calendar
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
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        // Add empty cells for days before the first day of the month
        for (let i = 0; i < startingDayOfWeek; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day other-month';
            calendarGrid.appendChild(emptyDay);
        }

        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.innerHTML = `<span class="date-number">${day}</span>`;
            
            const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            const dateString = formatDateForComparison(dayDate);
            
            // Check if today
            const today = new Date();
            if (isSameDate(dayDate, today)) {
                dayElement.classList.add('today');
            }
            
            // Check for events on this date (exclude announcements from calendar markers)
            const dayEvents = getEventsForDate(dayDate);
            const totalItems = dayEvents.length;
            
            if (totalItems > 0) {
                dayElement.classList.add('has-event');
                dayElement.setAttribute('data-date', dateString);
                dayElement.setAttribute('data-total-count', totalItems);
                
                // Badge with count
                const badge = document.createElement('span');
                badge.className = 'event-count-badge';
                badge.textContent = String(dayEvents.length);
                dayElement.appendChild(badge);
                
                // Add click event for showing details
                dayElement.addEventListener('click', function() {
                    showDateDetails(dayDate, dayEvents, []);
                });
                
                // Add tooltip
                dayElement.addEventListener('mouseenter', function(e) {
                    showTooltip(e, totalItems, dayEvents.length, 0);
                });
                
                dayElement.addEventListener('mouseleave', function() {
                    hideTooltip();
                });
            }
            
            calendarGrid.appendChild(dayElement);
        }
    }

    // Get events for a specific date
    function getEventsForDate(dateObj) {
        return events.filter(event => {
            if (!event.date) return false;
            const eventDate = new Date(event.date);
            return eventDate.toDateString() === dateObj.toDateString();
        });
    }

    // Get announcements for a specific date
    function getAnnouncementsForDate(dateObj) {
        return announcements.filter(announcement => {
            const itemDate = new Date(announcement.date || announcement.created_at);
            return itemDate.toDateString() === dateObj.toDateString();
        });
    }

    // Format date for comparison (YYYY-MM-DD)
    function formatDateForComparison(date) {
        return date.toISOString().split('T')[0];
    }

    // Check if two dates are the same day
    function isSameDate(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }

    // Show tooltip on hover
    function showTooltip(event, totalCount, eventsCount, announcementsCount) {
        hideTooltip(); // Remove any existing tooltip
        
        const tooltip = document.createElement('div');
        tooltip.className = 'event-tooltip';
        tooltip.id = 'eventTooltip';
        
        let tooltipText = `${eventsCount} event${eventsCount > 1 ? 's' : ''}`;
        
        tooltip.textContent = tooltipText;
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = event.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
        tooltip.style.opacity = '1';
    }

    // Hide tooltip
    function hideTooltip() {
        const existingTooltip = document.getElementById('eventTooltip');
        if (existingTooltip) {
            existingTooltip.remove();
        }
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
                const announcementDate = new Date(announcement.created_at).toLocaleDateString('en-US', {
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

    // Details modals for list cards
    function openAnnouncementDetails(announcement) {
        const modalHTML = `
            <div class="modal fade" id="announcementDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>${announcement.title}</h5>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted"><i class="fas fa-calendar me-2"></i>${new Date(announcement.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</p>
                            <div style="white-space: pre-line;">${announcement.content || ''}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`;
        const existing = document.getElementById('announcementDetailsModal');
        if (existing) existing.remove();
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('announcementDetailsModal'));
        modal.show();
        document.getElementById('announcementDetailsModal').addEventListener('hidden.bs.modal', function(){ this.remove(); });
    }

    function openEventDetails(event) {
        const eventDate = event.date ? new Date(event.date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}) : '';
        const eventTime = event.time ? new Date(`1970-01-01T${event.time}`).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}) : '';
        const modalHTML = `
            <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>${event.title}</h5>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted"><i class="fas fa-calendar me-1"></i>${eventDate} ${eventTime ? '• '+eventTime : ''} ${event.location ? '• '+event.location : ''}</p>
                            <div style="white-space: pre-line;">${event.description || ''}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`;
        const existing = document.getElementById('eventDetailsModal');
        if (existing) existing.remove();
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        modal.show();
        document.getElementById('eventDetailsModal').addEventListener('hidden.bs.modal', function(){ this.remove(); });
    }
}); 