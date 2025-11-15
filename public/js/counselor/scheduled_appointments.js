document.addEventListener('DOMContentLoaded', function () {
    const statusToast = new bootstrap.Toast(document.getElementById('statusToast'));
    const loadingIndicator = document.getElementById('loading-indicator');
    const emptyMessage = document.getElementById('empty-message');
    const appointmentsTableContainer = document.getElementById('appointments-table-container');
    const appointmentsBody = document.getElementById('appointments-body');

    // Store original appointments data for search filtering
    let originalAppointments = [];
    let filteredAppointments = [];

    initStickyHeader();
    initSearchFunctionality();
    loadAppointments();
    loadCounselorSchedule();

    function initStickyHeader() {
        const header = document.querySelector('header');
        const main = document.querySelector('main');
        if (header && main) {
            const headerHeight = header.offsetHeight;
            const placeholder = document.createElement('div');
            placeholder.style.display = 'none';
            placeholder.style.height = headerHeight + 'px';
            document.body.insertBefore(placeholder, main);
            window.onscroll = function () {
                if (window.pageYOffset > 10) { header.classList.add("sticky-header"); placeholder.style.display = 'block'; }
                else { header.classList.remove("sticky-header"); placeholder.style.display = 'none'; }
            };
            window.addEventListener('resize', function () { placeholder.style.height = header.offsetHeight + 'px'; });
        }
    }

    /**
     * Initialize search functionality for appointments table
     */
    function initSearchFunctionality() {
        const searchInput = document.getElementById('appointmentsSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        
        if (!searchInput) {
            return;
        }

        // Search input event listener
        searchInput.addEventListener('input', function() {
            const searchQuery = this.value.trim().toLowerCase();
            filterAppointmentsTable(searchQuery);
            
            // Show/hide clear button
            if (searchQuery.length > 0) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        });

        // Clear search button event listener
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                filterAppointmentsTable('');
            });
        }
    }

    /**
     * Filter appointments table based on search query
     * @param {string} searchQuery - The search query string
     */
    function filterAppointmentsTable(searchQuery) {
        const tableRows = appointmentsBody.querySelectorAll('tr');
        
        if (!searchQuery || searchQuery.length === 0) {
            // Show all rows if search is empty
            tableRows.forEach(row => {
                row.style.display = '';
            });
            
            // Show/hide empty message based on original data
            if (originalAppointments.length === 0) {
                emptyMessage.classList.remove('d-none');
                appointmentsTableContainer.classList.add('d-none');
            } else {
                emptyMessage.classList.add('d-none');
                appointmentsTableContainer.classList.remove('d-none');
            }
            return;
        }

        let visibleRowCount = 0;
        
        // Filter rows based on search query
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let rowText = '';
            
            // Collect all text content from table cells
            cells.forEach(cell => {
                if (cell) {
                    rowText += ' ' + cell.textContent.trim().toLowerCase();
                }
            });
            
            // Check if search query matches any cell content
            if (rowText.includes(searchQuery)) {
                row.style.display = '';
                visibleRowCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide empty message based on filtered results
        if (visibleRowCount === 0 && originalAppointments.length > 0) {
            emptyMessage.textContent = 'No appointments match your search criteria.';
            emptyMessage.classList.remove('d-none');
        } else if (visibleRowCount > 0) {
            emptyMessage.classList.add('d-none');
            appointmentsTableContainer.classList.remove('d-none');
        }
    }

    function loadAppointments() {
        loadingIndicator.classList.remove('d-none');
        appointmentsTableContainer.classList.add('d-none');
        emptyMessage.classList.add('d-none');
        const url = (window.BASE_URL || '/') + `counselor/appointments/scheduled/get?_=${Date.now()}`;
        fetch(url, { method: 'GET', credentials: 'include', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }})
            .then(response => { if (!response.ok) throw new Error(response.status === 401 ? 'Session expired - Please log in again' : `Network error ${response.status}`); return response.json(); })
            .then(data => {
                if (data.status === 'success') {
                    if (Array.isArray(data.appointments) && data.appointments.length > 0) {
                        originalAppointments = data.appointments;
                        filteredAppointments = data.appointments;
                        displayAppointments(data.appointments);
                        appointmentsTableContainer.classList.remove('d-none');
                        emptyMessage.classList.add('d-none');
                    } else {
                        originalAppointments = [];
                        filteredAppointments = [];
                        emptyMessage.textContent = data.message || 'No approved appointments found';
                        emptyMessage.classList.remove('d-none');
                        appointmentsTableContainer.classList.add('d-none');
                    }
                } else {
                    throw new Error(data.message || 'Failed to load appointments');
                }
            })
            .catch(error => {
                originalAppointments = [];
                filteredAppointments = [];
                emptyMessage.textContent = error.message;
                emptyMessage.classList.remove('d-none');
                appointmentsTableContainer.classList.add('d-none');
            })
            .finally(() => { loadingIndicator.classList.add('d-none'); });
    }

    function displayAppointments(appointments) {
        appointmentsBody.innerHTML = '';
        if (!appointments || appointments.length === 0) {
            emptyMessage.classList.remove('d-none');
            appointmentsTableContainer.classList.add('d-none');
            return;
        }
        
        // Store current search query to reapply after rendering
        const searchInput = document.getElementById('appointmentsSearchInput');
        const currentSearchQuery = searchInput ? searchInput.value.trim().toLowerCase() : '';
        appointments.forEach(appointment => {
            const row = document.createElement('tr');
            row.dataset.id = appointment.id;
            if (appointment.status === 'COMPLETED') { row.classList.add('table-success'); }
            else {
                const dateObj = new Date(appointment.appointed_date || appointment.preferred_date);
                if (isToday(dateObj)) row.classList.add('table-primary');
            }
            const dateObj = new Date(appointment.appointed_date || appointment.preferred_date);
            const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const formattedTime = formatTime(appointment.time || appointment.preferred_time);
            let scheduleType = appointment.schedule_type || (appointment.parent_appointment_id ? 'Follow-up session' : 'New');
            if (scheduleType === 'New') scheduleType = 'First Session';
            if (scheduleType === 'Follow-up session' || scheduleType === 'Follow-up') scheduleType = 'Follow-up Session';
            const recordKind = appointment.record_kind || 'appointment';
            const actionHtml = (recordKind === 'follow_up')
                ? '<span class="text-muted">Manage in Follow-up Sessions</span>'
                : (appointment.status === 'COMPLETED' || appointment.status === 'CANCELLED'
                    ? '<span class="text-muted">No actions available</span>'
                    : `<div class="btn-group" role="group">
                        <button class="btn btn-sm btn-success mark-complete-btn" data-id="${appointment.id}"><i class="fas fa-check me-1"></i>Mark Complete</button>
                        <button class="btn btn-sm btn-danger cancel-appointment-btn" data-id="${appointment.id}"><i class="fas fa-times me-1"></i>Cancel</button>
                       </div>`);

            row.innerHTML = `
                <td>${appointment.student_id || 'N/A'}</td>
                <td>${appointment.student_name || 'N/A'}</td>
                <td>${formattedDate || 'Invalid Date'}</td>
                <td>${formattedTime || 'N/A'}</td>
                <td>${appointment.method_type || 'In-person'}</td>
                <td>${appointment.consultation_type || 'Individual Consultation'}</td>
                <td>${scheduleType}</td>
                <td>${appointment.purpose || 'N/A'}</td>
                <td class="text-center">
                    ${appointment.status === 'COMPLETED' ? '<span class="badge bg-success">Completed</span>' : appointment.status === 'CANCELLED' ? '<span class="badge bg-danger">Cancelled</span>' : '<span class="badge bg-primary">Approved</span>'}
                </td>
                <td class="text-center">${actionHtml}</td>`;
            appointmentsBody.appendChild(row);
        });

        document.querySelectorAll('.mark-complete-btn').forEach(btn => btn.addEventListener('click', function () { updateAppointmentStatus(this.getAttribute('data-id'), 'COMPLETED'); }));
        document.querySelectorAll('.cancel-appointment-btn').forEach(btn => btn.addEventListener('click', function () { const id = this.getAttribute('data-id'); new bootstrap.Modal(document.getElementById('cancellationReasonModal'), { backdrop: 'static', keyboard: false }).show(); document.getElementById('cancellationReasonModal').dataset.appointmentId = id; }));

        document.getElementById('confirmCancellationBtn').addEventListener('click', function() {
            const reason = document.getElementById('cancellationReason').value.trim();
            if (!reason) { showToast('Error', 'Please provide a reason for cancellation.'); return; }
            const appointmentId = document.getElementById('cancellationReasonModal').dataset.appointmentId;
            const confirmBtn = document.getElementById('confirmCancellationBtn'); const original = confirmBtn.innerHTML; confirmBtn.disabled = true; confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            const formData = new FormData(); formData.append('appointment_id', appointmentId); formData.append('status', 'CANCELLED'); formData.append('rejection_reason', reason);
            fetch((window.BASE_URL || '/') + 'counselor/appointments/updateAppointmentStatus', { method:'POST', body: formData, credentials:'include', headers:{ 'X-Requested-With':'XMLHttpRequest' }})
                .then(r=>{ if(!r.ok) throw new Error(`Server error: ${r.status}`); return r.json(); })
                .then(data=>{ if (data.status==='success'){ const m = bootstrap.Modal.getInstance(document.getElementById('cancellationReasonModal')); if (m) m.hide(); showToast('Success','Appointment cancelled successfully! An email notification has been sent to the user.'); loadAppointments(); } else { throw new Error(data.message||'Failed to cancel appointment'); } })
                .catch(err=>{ showToast('Error', err.message||'An error occurred while cancelling the appointment.'); })
                .finally(()=>{ confirmBtn.disabled = false; confirmBtn.innerHTML = original; });
        });

        document.getElementById('cancellationReasonModal').addEventListener('hidden.bs.modal', function(){ document.getElementById('cancellationReason').value=''; delete this.dataset.appointmentId; });

        appointmentsTableContainer.classList.remove('d-none');
        emptyMessage.classList.add('d-none');
        
        // Reapply search filter if there's an active search query
        if (currentSearchQuery && currentSearchQuery.length > 0) {
            filterAppointmentsTable(currentSearchQuery);
        }
        
        // Update calendar with appointments
        updateCalendarWithAppointments(appointments);
    }

    function isToday(date){ const t=new Date(); return date.getDate()===t.getDate() && date.getMonth()===t.getMonth() && date.getFullYear()===t.getFullYear(); }
    function formatTime(time){ if(!time) return 'N/A'; if (time.includes('AM') || time.includes('PM')) return time; if (time.includes('-')) { const [s,e]=time.split('-').map(t=>t.trim()); return `${formatSingleTime(s)} - ${formatSingleTime(e)}`; } return formatSingleTime(time); }
    function formatSingleTime(time){ 
        // Check if already in 12-hour format (contains AM/PM)
        if (time.includes('AM') || time.includes('PM')) {
            return time;
        }
        // Convert from 24-hour format to 12-hour format
        const [h,m]=time.split(':'); 
        const hh=parseInt(h,10); 
        const ampm=hh>=12?'PM':'AM'; 
        const fh=hh%12||12; 
        return `${fh}:${m||'00'} ${ampm}`; 
    }

    function updateAppointmentStatus(appointmentId, newStatus){
        const buttons = document.querySelectorAll(`.mark-complete-btn[data-id="${appointmentId}"], .cancel-appointment-btn[data-id="${appointmentId}"]`);
        buttons.forEach(b=>{ b.disabled=true; b.innerHTML='<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'; });
        const formData = new FormData(); formData.append('appointment_id', appointmentId); formData.append('status', newStatus);
        fetch((window.BASE_URL || '/') + 'counselor/appointments/updateAppointmentStatus', { method:'POST', body: formData, credentials:'include', headers:{ 'X-Requested-With':'XMLHttpRequest' }})
            .then(r=>{ if(!r.ok) throw new Error(`Server error: ${r.status}`); return r.json(); })
            .then(data=>{ if (data.status==='success'){ showToast('Success', `Appointment ${newStatus.toLowerCase()} successfully`); loadAppointments(); } else { showToast('Error', data.message||'Failed to update appointment status'); buttons.forEach(b=>{ b.disabled=false; b.innerHTML = newStatus==='COMPLETED' ? '<i class="fas fa-check me-1"></i>Mark Complete' : '<i class="fas fa-times me-1"></i>Cancel'; }); } })
            .catch(err=>{ showToast('Error', err.message||'Failed to update appointment status'); buttons.forEach(b=>{ b.disabled=false; b.innerHTML = newStatus==='COMPLETED' ? '<i class="fas fa-check me-1"></i>Mark Complete' : '<i class="fas fa-times me-1"></i>Cancel'; }); });
    }

    function showToast(title, message){
        const toastTitle = document.querySelector('#statusToast .toast-header strong');
        const toastBody = document.querySelector('#statusToast .toast-body');
        const toastTime = document.querySelector('#statusToast .toast-header small');
        if (toastTitle) toastTitle.textContent = title; if (toastBody) toastBody.textContent = message; if (toastTime) toastTime.textContent = 'Just now';
        const toast = bootstrap.Toast.getInstance(document.getElementById('statusToast')); if (toast) toast.show(); else new bootstrap.Toast(document.getElementById('statusToast')).show();
    }

    // Calendar functionality
    let appointmentCalendar;

    class AppointmentCalendar {
        constructor() {
            this.currentDate = new Date();
            this.appointments = [];
            this.init();
        }

        init() {
            this.renderCalendar();
            this.attachEventListeners();
        }

        attachEventListeners() {
            document.getElementById('prevMonth')?.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.renderCalendar();
            });

            document.getElementById('nextMonth')?.addEventListener('click', () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.renderCalendar();
            });
        }

        setAppointments(appointments) {
            this.appointments = appointments;
            this.renderCalendar();
        }

        getAppointmentCountForDate(date) {
            // Compare using local YYYY-MM-DD to avoid timezone shifts
            const toYmd = (d) => {
                const yr = d.getFullYear();
                const mo = String(d.getMonth() + 1).padStart(2, '0');
                const da = String(d.getDate()).padStart(2, '0');
                return `${yr}-${mo}-${da}`;
            };
            const dateStr = toYmd(date);

            return this.appointments.filter((apt) => {
                // Count only approved/scheduled items
                const status = (apt.status || '').toString().toLowerCase();
                const isApproved = status === 'approved' || status === 'scheduled' || status === 'approved\n';

                // Prefer appointed_date; fall back to preferred_date
                const raw = apt.appointed_date || apt.preferred_date || apt.appointedDate || apt.preferredDate;
                if (!raw || !isApproved) return false;

                const d = new Date(raw);
                if (isNaN(d.getTime())) return false;
                return toYmd(d) === dateStr;
            }).length;
        }

        renderCalendar() {
            SecureLogger.info('Rendering calendar...');
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            const monthYearElement = document.getElementById('monthYear');
            if (monthYearElement) {
                monthYearElement.textContent = `${monthNames[month]} ${year}`;
                SecureLogger.info('Month year set to:', monthYearElement.textContent);
            }

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();

            SecureLogger.info('Calendar data:', { year, month, firstDay, daysInMonth });

            const calendarDays = document.getElementById('calendarDays');
            if (!calendarDays) {
                console.error('Calendar days element not found!');
                return;
            }
            
            calendarDays.innerHTML = '';

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'calendar-day empty';
                calendarDays.appendChild(emptyDay);
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';

                const currentLoopDate = new Date(year, month, day);
                const appointmentCount = this.getAppointmentCountForDate(currentLoopDate);

                // Check if it's today
                if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dayElement.classList.add('today');
                    SecureLogger.info('Today highlighted:', day);
                }

                if (appointmentCount > 0) {
                    dayElement.classList.add('has-appointment');
                    dayElement.innerHTML = `
                        <span class="day-number">${day}</span>
                        <span class="appointment-badge">${appointmentCount}</span>
                    `;
                    dayElement.title = `${appointmentCount} appointment${appointmentCount > 1 ? 's' : ''}`;
                    SecureLogger.info('Day with appointment:', day, 'Count:', appointmentCount);
                } else {
                    dayElement.innerHTML = `<span class="day-number">${day}</span>`;
                }

                calendarDays.appendChild(dayElement);
            }
            
            SecureLogger.info('Calendar rendering complete. Total days added:', daysInMonth);
        }
    }

    // Initialize calendar after class definition
    SecureLogger.info('Initializing calendar from main function...');
    appointmentCalendar = new AppointmentCalendar();

    // Function to update calendar with appointments
    function updateCalendarWithAppointments(appointments) {
        if (appointmentCalendar) {
            appointmentCalendar.setAppointments(appointments);
        }
    }

    /**
     * Load counselor's availability schedule and display it in the sidebar
     */
    function loadCounselorSchedule() {
        const scheduleList = document.querySelector('.schedule-list');
        if (!scheduleList) {
            console.warn('Schedule list element not found');
            return;
        }

        const url = (window.BASE_URL || '/') + `counselor/appointments/schedule?_=${Date.now()}`;
        
        fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network error ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                displayCounselorSchedule(data.schedule);
            } else {
                console.error('Failed to load counselor schedule:', data.message);
                displayDefaultSchedule();
            }
        })
        .catch(error => {
            console.error('Error loading counselor schedule:', error);
            displayDefaultSchedule();
        });
    }

    /**
     * Display the counselor's schedule in the sidebar
     * @param {Array} schedule - Array of schedule objects with day and time properties
     */
    function displayCounselorSchedule(schedule) {
        const scheduleList = document.querySelector('.schedule-list');
        if (!scheduleList) {
            return;
        }

        // Clear existing schedule
        scheduleList.innerHTML = '';

        if (!schedule || schedule.length === 0) {
            scheduleList.innerHTML = '<div class="schedule-row"><span class="text-muted">No schedule set</span></div>';
            return;
        }

        // Group schedule by day to handle multiple time slots per day
        const groupedSchedule = {};
        
        schedule.forEach(item => {
            const day = item.day;
            const time = item.time;
            
            // Initialize day array if it doesn't exist
            if (!groupedSchedule[day]) {
                groupedSchedule[day] = [];
            }
            
            // Add time slot if it exists and is not already in the array
            if (time && time.trim() !== '' && !groupedSchedule[day].includes(time.trim())) {
                groupedSchedule[day].push(time.trim());
            }
        });

        // Sort days in chronological order
        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sortedDays = Object.keys(groupedSchedule).sort((a, b) => {
            return dayOrder.indexOf(a) - dayOrder.indexOf(b);
        });

        // Display schedule rows for each day
        sortedDays.forEach(day => {
            const times = groupedSchedule[day];
            
            if (times.length > 0) {
                // Format time slots to 12-hour format
                const formattedTimes = formatTimeSlotsForBadges(times);
                const timeString = formattedTimes.join(', ');
                const scheduleRow = document.createElement('div');
                scheduleRow.className = 'schedule-row';
                scheduleRow.innerHTML = `<span>${day}</span><span>${timeString}</span>`;
                scheduleList.appendChild(scheduleRow);
            } else {
                // Day without specific time (all day availability)
                const scheduleRow = document.createElement('div');
                scheduleRow.className = 'schedule-row';
                scheduleRow.innerHTML = `<span>${day}</span><span>All day</span>`;
                scheduleList.appendChild(scheduleRow);
            }
        });

        // If no schedule items were added, show default message
        if (scheduleList.children.length === 0) {
            scheduleList.innerHTML = '<div class="schedule-row"><span class="text-muted">No schedule set</span></div>';
        }
    }

    /**
     * Display default schedule when loading fails
     */
    function displayDefaultSchedule() {
        const scheduleList = document.querySelector('.schedule-list');
        if (!scheduleList) {
            return;
        }

        scheduleList.innerHTML = `
            <div class="schedule-row"><span class="text-muted">Schedule not available</span></div>
        `;
    }
});



