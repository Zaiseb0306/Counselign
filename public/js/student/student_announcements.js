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
                    // Update the user ID in the welcome message
                    const userIdSpan = document.querySelector('.text-primary i');
                    if (userIdSpan) {
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
                </div>
            `;

            container.appendChild(announcementElement);
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
                </div>
            `;
            container.appendChild(eventElement);
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
        // Calendar toggle button
        const calendarToggleBtn = document.getElementById('calendarToggleBtn');
        const calendarDrawer = document.getElementById('calendarDrawer');
        const calendarOverlay = document.getElementById('calendarOverlay');
        const calendarCloseBtn = document.getElementById('calendarCloseBtn');

        if (calendarToggleBtn) {
            calendarToggleBtn.addEventListener('click', function() {
                openCalendarDrawer();
            });
        }

        if (calendarCloseBtn) {
            calendarCloseBtn.addEventListener('click', function() {
                closeCalendarDrawer();
            });
        }

        if (calendarOverlay) {
            calendarOverlay.addEventListener('click', function() {
                closeCalendarDrawer();
            });
        }

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

    // Open calendar drawer
    function openCalendarDrawer() {
        const calendarDrawer = document.getElementById('calendarDrawer');
        const calendarOverlay = document.getElementById('calendarOverlay');
        const calendarToggleBtn = document.getElementById('calendarToggleBtn');

        if (calendarDrawer && calendarOverlay && calendarToggleBtn) {
            calendarDrawer.classList.add('open');
            calendarOverlay.classList.add('active');
            calendarToggleBtn.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    // Close calendar drawer
    function closeCalendarDrawer() {
        const calendarDrawer = document.getElementById('calendarDrawer');
        const calendarOverlay = document.getElementById('calendarOverlay');
        const calendarToggleBtn = document.getElementById('calendarToggleBtn');

        if (calendarDrawer && calendarOverlay && calendarToggleBtn) {
            calendarDrawer.classList.remove('open');
            calendarOverlay.classList.remove('active');
            calendarToggleBtn.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

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
            dayElement.textContent = day;
            
            const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            const dateString = formatDateForComparison(dayDate);
            
            // Check if today
            const today = new Date();
            if (isSameDate(dayDate, today)) {
                dayElement.classList.add('today');
            }
            
            // Check for events/announcements on this date
            const dayEvents = getEventsForDate(dayDate);
            const dayAnnouncements = getAnnouncementsForDate(dayDate);
            const totalItems = dayEvents.length + dayAnnouncements.length;
            
            if (totalItems > 0) {
                dayElement.classList.add('has-event');
                dayElement.setAttribute('data-date', dateString);
                dayElement.setAttribute('data-events-count', dayEvents.length);
                dayElement.setAttribute('data-announcements-count', dayAnnouncements.length);
                dayElement.setAttribute('data-total-count', totalItems);
                
                // Add click event for showing details
                dayElement.addEventListener('click', function() {
                    showDateDetails(dayDate, dayEvents, dayAnnouncements);
                });
                
                // Add tooltip
                dayElement.addEventListener('mouseenter', function(e) {
                    showTooltip(e, totalItems, dayEvents.length, dayAnnouncements.length);
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
        
        let tooltipText = `${totalCount} item${totalCount > 1 ? 's' : ''}`;
        if (eventsCount > 0 && announcementsCount > 0) {
            tooltipText += ` (${eventsCount} event${eventsCount > 1 ? 's' : ''}, ${announcementsCount} announcement${announcementsCount > 1 ? 's' : ''})`;
        } else if (eventsCount > 0) {
            tooltipText += ` (${eventsCount} event${eventsCount > 1 ? 's' : ''})`;
        } else if (announcementsCount > 0) {
            tooltipText += ` (${announcementsCount} announcement${announcementsCount > 1 ? 's' : ''})`;
        }
        
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
        // Create modal HTML
        const modalHTML = `
            <div class="modal fade" id="dateDetailsModal" tabindex="-1" aria-labelledby="dateDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="dateDetailsModalLabel">
                                <i class="fas fa-calendar-day me-2"></i>
                                ${formatDateForDisplay(clickedDate)}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <div class="col-md-6 mb-3">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="card-title text-primary">${event.title}</h6>
                                <p class="card-text">${event.description || 'No description available'}</p>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-clock me-2"></i>
                                    <span>${eventTime}</span>
                                    ${event.location ? `<i class="fas fa-map-marker-alt ms-3 me-2"></i><span>${event.location}</span>` : ''}
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
                    <div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success">${announcement.title}</h6>
                                <p class="card-text">${announcement.content}</p>
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
}); 