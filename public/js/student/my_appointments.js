let pendingSaveContext = null;
let pendingCancelContext = null;
let pendingDeleteId = null;

document.addEventListener('DOMContentLoaded', function () {
    // Initialize variables
    let allAppointments = [];
    const searchInput = document.getElementById('searchInput');
    const dateFilter = document.getElementById('dateFilter');
    const loadingSpinner = document.querySelector('.loading-spinner');
    const emptyState = document.querySelector('.empty-state');
    let counselorsCache = null;

    // Fetch appointments when the page loads
    fetchAppointments();

    // Add event listeners
    if (searchInput) searchInput.addEventListener('input', filterAppointments);
    if (dateFilter) dateFilter.addEventListener('change', filterAppointments);
    
    // Setup counselor schedules in drawer
    setupCounselorSchedulesInDrawer();
    // ==================== Counselors' Schedules Calendar ====================
    (function initializeCounselorsCalendar(){
        const grid = document.getElementById('counselorsCalendarGrid');
        const monthLabel = document.getElementById('counselorsCurrentMonth');
        const prevBtn = document.getElementById('counselorsPrevMonth');
        const nextBtn = document.getElementById('counselorsNextMonth');
        if (!grid || !monthLabel) return;

        let calDate = new Date();

        function formatISO(date){ return date.toISOString().split('T')[0]; }
        function sameDay(a,b){ return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate(); }
        function monthName(idx){ return ['January','February','March','April','May','June','July','August','September','October','November','December'][idx]; }

        function renderCalendar(){
            grid.innerHTML = '';
            monthLabel.textContent = monthName(calDate.getMonth()) + ' ' + calDate.getFullYear();

            const dayHeaders = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            dayHeaders.forEach(d => { const el = document.createElement('div'); el.className='calendar-day-header'; el.textContent=d; grid.appendChild(el); });

            const first = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
            const last = new Date(calDate.getFullYear(), calDate.getMonth()+1, 0);
            const startDow = first.getDay();
            for (let i=0;i<startDow;i++){ const e=document.createElement('div'); e.className='calendar-day other-month'; grid.appendChild(e); }

            for (let day=1; day<=last.getDate(); day++){
                const cell = document.createElement('div');
                cell.className = 'calendar-day';
                cell.textContent = String(day);
                const thisDate = new Date(calDate.getFullYear(), calDate.getMonth(), day);
                if (sameDay(thisDate, new Date())) cell.classList.add('today');

                cell.addEventListener('click', () => openCounselorsBubble(thisDate, cell));
                grid.appendChild(cell);
            }
        }

        async function openCounselorsBubble(dateObj, anchorEl){
            closeCounselorsBubble();
            const bubble = document.createElement('div');
            bubble.className = 'counselors-bubble';
            bubble.innerHTML = '<div class="bubble-header"><i class="fas fa-user-md me-2"></i>Available Counselors</div><div class="bubble-body">Loading...</div>';
            document.body.appendChild(bubble);

            // position near anchor
            const rect = anchorEl.getBoundingClientRect();
            const top = window.scrollY + rect.top - bubble.offsetHeight - 8;
            const left = window.scrollX + rect.left + (rect.width/2) - 160;
            bubble.style.top = Math.max(10, top) + 'px';
            bubble.style.left = Math.max(10, left) + 'px';

            // fetch counselors for that date using availability endpoint (broad day query)
            const dateStr = formatISO(dateObj);
            const dayOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][dateObj.getDay()];
            const url = new URL((window.BASE_URL || '/') + 'student/get-counselors-by-availability');
            url.searchParams.append('date', dateStr);
            url.searchParams.append('day', dayOfWeek);
            // Use full-day overlap so backend returns day-available counselors
            url.searchParams.append('time', '00:00-23:59');
            url.searchParams.append('from', '00:00');
            url.searchParams.append('to', '23:59');
            url.searchParams.append('timeMode', 'overlap');

            try{
                const res = await fetch(url.toString(), { method:'GET', credentials:'include', headers:{ 'Accept':'application/json' } });
                if (!res.ok) throw new Error('Availability request failed: ' + res.status);
                const data = await res.json();
                const body = bubble.querySelector('.bubble-body');
                if (data.status === 'success' && Array.isArray(data.counselors) && data.counselors.length > 0){
                    // Build list with time slots per counselor via a secondary fetch to profile availability if needed
                    const list = document.createElement('div');
                    list.className = 'counselors-list';
                    for (const c of data.counselors){
                        const item = document.createElement('div');
                        item.className = 'counselor-item';
                        item.innerHTML = `<div class="c-name"><i class="fas fa-user me-2"></i>${c.name}</div><div class="c-slots">Loading slots...</div>`;
                        list.appendChild(item);

                        // Try to get time slots for that day from a lightweight endpoint we already have on counselor side
                        try {
                        const availRes = await fetch((window.BASE_URL || '/') + 'counselor/profile/availability?counselorId=' + encodeURIComponent(c.counselor_id));
                            const availData = await availRes.json();
                            const slotsDiv = item.querySelector('.c-slots');
                        const rows = (availData && availData.availability && availData.availability[dayOfWeek]) ? availData.availability[dayOfWeek] : [];
                        const slotStrings = Array.isArray(rows) ? rows.map(r => r && r.time_scheduled).filter(Boolean) : [];
                        if (slotStrings.length > 0){
                            const unique = Array.from(new Set(slotStrings));
                            const formattedSlots = formatTimeSlotsForBadges(unique);
                            const slotBadges = formattedSlots.map(s => `<span class="slot-badge">${s}</span>`).join(' ');
                                slotsDiv.innerHTML = slotBadges;
                            } else {
                                slotsDiv.textContent = 'Available (no specific time slots posted)';
                            }
                        } catch (e) {
                            const slotsDiv = item.querySelector('.c-slots');
                            slotsDiv.textContent = 'Available';
                        }
                    }
                    body.innerHTML = '';
                    body.appendChild(list);
                } else {
                    body.innerHTML = '<div class="text-muted small">No counselors available on this date.</div>';
                }
            } catch(e){
                const body = bubble.querySelector('.bubble-body');
                body.innerHTML = '<div class="text-danger small">Failed to load counselors.</div>';
            }

            // close when clicking outside
            setTimeout(() => {
                function handleDocClick(ev){
                    if (!bubble.contains(ev.target)){
                        closeCounselorsBubble();
                        document.removeEventListener('mousedown', handleDocClick);
                        window.removeEventListener('resize', closeCounselorsBubble);
                        window.removeEventListener('scroll', closeCounselorsBubble, true);
                    }
                }
                document.addEventListener('mousedown', handleDocClick);
                window.addEventListener('resize', closeCounselorsBubble);
                window.addEventListener('scroll', closeCounselorsBubble, true);
            }, 0);
        }

        function closeCounselorsBubble(){
            const existing = document.querySelector('.counselors-bubble');
            if (existing) existing.remove();
        }

        if (prevBtn) prevBtn.addEventListener('click', () => { calDate.setMonth(calDate.getMonth()-1); renderCalendar(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { calDate.setMonth(calDate.getMonth()+1); renderCalendar(); });
        renderCalendar();
    })();


    // Add event listeners for tab changes
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', handleTabChange);
    });

    // Initialize modals
    const editModal = new bootstrap.Modal(document.getElementById('editAppointmentModal'));
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelAppointmentModal'));

    // Add event listeners for modal buttons
    document.getElementById('saveEditBtn').addEventListener('click', saveEdit);
    document.getElementById('confirmCancelBtn').addEventListener('click', confirmCancel);

    async function fetchAppointments() {
        try {
            showLoading();
            const response = await fetch((window.BASE_URL || '/') + 'student/appointments/get-my-appointments', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                allAppointments = data.appointments;
                updateInitialDisplay();

                if (allAppointments.length === 0) {
                    showEmptyState();
                } else {
                    hideEmptyState();
                }
            } else {
                showError(data.message || 'Failed to fetch appointments');
            }
        } catch (error) {
            console.error('Error fetching appointments:', error);
            showError('An error occurred while fetching appointments');
        } finally {
            hideLoading();
        }
    }

    function updateInitialDisplay() {
        // Display approved appointments first
        const approvedAppointments = allAppointments.filter(app => app.status && app.status.toUpperCase() === 'APPROVED');
        displayApprovedAppointments(approvedAppointments);

        // Display pending appointments
        const pendingAppointments = allAppointments.filter(app => app.status && app.status.toUpperCase() === 'PENDING');
        displayPendingAppointments(pendingAppointments);

        // Display all appointments
        displayAppointments(allAppointments, 'allAppointmentsTable');

        // Display appointments for each status tab (approved tab removed - using card display instead)

        const rejectedAppointments = allAppointments.filter(app => app.status && app.status.toUpperCase() === 'REJECTED');
        displayAppointments(rejectedAppointments, 'rejectedAppointmentsTable');

        const completedAppointments = allAppointments.filter(app => app.status && app.status.toUpperCase() === 'COMPLETED');
        displayAppointments(completedAppointments, 'completedAppointmentsTable');

        const cancelledAppointments = allAppointments.filter(app => app.status && app.status.toUpperCase() === 'CANCELLED');
        displayAppointments(cancelledAppointments, 'cancelledAppointmentsTable');
    }

    async function fetchCounselors() {
        if (counselorsCache) return counselorsCache;
        try {
            const response = await fetch((window.BASE_URL || '/') + 'student/get-counselors');
            const data = await response.json();
            if (data.status === 'success' && Array.isArray(data.counselors)) {
                counselorsCache = data.counselors;
                return counselorsCache;
            } else {
                return [];
            }
        } catch (error) {
            console.error('Error fetching counselors:', error);
            return [];
        }
    }

    async function displayApprovedAppointments(appointments) {
        const container = document.getElementById('approvedAppointmentsContainer');
        if (!container) return;
        container.innerHTML = '';

        if (!appointments || appointments.length === 0) {
            container.innerHTML = `
                <div class="no-approved-appointments">
                    <i class="fas fa-calendar-check"></i>
                    <h4>No Approved Appointments</h4>
                    <p>You don't have any approved appointments at the moment.</p>
                </div>
            `;
            return;
        }

        // Display the first approved appointment (most recent)
        const appointment = appointments[0];
        const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
        const qrCodeData = JSON.stringify({
            appointmentId: appointment.id,
            studentId: appointment.student_id,
            date: appointment.preferred_date,
            time: appointment.preferred_time,
            counselor: appointment.counselor_name,
            type: appointment.consultation_type,
            purpose: appointment.purpose,
            ticketId: ticketId
        });

        const ticketHtml = await generateAppointmentTicket(appointment);
        container.innerHTML = ticketHtml;

        // Generate QR code after DOM is updated - wait longer to ensure QRCode library is loaded
        setTimeout(() => {
            generateQRCode(appointment.id, qrCodeData);
        }, 500);

        // Add event listener for download button
        const downloadBtn = container.querySelector('.download-ticket-btn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => downloadAppointmentTicket(appointment));
        }
    }

    async function displayPendingAppointments(appointments) {
        const container = document.getElementById('pendingAppointmentsFormsContainer');
        if (!container) return;
        container.innerHTML = '';

        if (!appointments || appointments.length === 0) {
            container.innerHTML = '<div class="alert alert-info text-center">No pending appointments</div>';
            return;
        }

        // Fetch counselors once
        const counselors = await fetchCounselors();

        appointments.forEach(appointment => {
            const form = document.createElement('form');
            form.className = 'pending-appointment-form mb-2 p-3 border rounded shadow-sm';
            // Build counselor options
            let counselorOptions = '<option value="">Select a counselor</option>';
            counselorOptions += `<option value="No preference"${appointment.counselor_preference === 'No preference' ? ' selected' : ''}>No preference</option>`;
            counselors.forEach(counselor => {
                const selected = appointment.counselor_preference == counselor.counselor_id ? ' selected' : '';
                counselorOptions += `<option value="${counselor.counselor_id}"${selected}>${counselor.name}</option>`;
            });
            form.innerHTML = `
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label mb-1">Preferred Date</label>
                        <input type="date" class="form-control" name="preferred_date" value="${appointment.preferred_date}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Preferred Time</label>
                        <select class="form-control" name="preferred_time" disabled>
                            <option value="">Select a time slot</option>
                            <option${appointment.preferred_time === '8:00 AM - 9:00 AM' ? ' selected' : ''}>8:00 AM - 9:00 AM</option>
                            <option${appointment.preferred_time === '9:00 AM - 10:00 AM' ? ' selected' : ''}>9:00 AM - 10:00 AM</option>
                            <option${appointment.preferred_time === '10:00 AM - 11:00 AM' ? ' selected' : ''}>10:00 AM - 11:00 AM</option>
                            <option${appointment.preferred_time === '1:00 PM - 2:00 PM' ? ' selected' : ''}>1:00 PM - 2:00 PM</option>
                            <option${appointment.preferred_time === '2:00 PM - 3:00 PM' ? ' selected' : ''}>2:00 PM - 3:00 PM</option>
                            <option${appointment.preferred_time === '3:00 PM - 4:00 PM' ? ' selected' : ''}>3:00 PM - 4:00 PM</option>
                            <option${appointment.preferred_time === '4:00 PM - 5:00 PM' ? ' selected' : ''}>4:00 PM - 5:00 PM</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 align-items-center mt-1">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Consultation Type</label>
                        <input type="text" class="form-control" name="consultation_type" value="${appointment.consultation_type || ''}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Purpose</label>
                        <select class="form-control" name="purpose" disabled>
                            <option value="">Select purpose...</option>
                            <option value="Counseling"${appointment.purpose === 'Counseling' ? ' selected' : ''}>Counseling</option>
                            <option value="Psycho-Social Support"${appointment.purpose === 'Psycho-Social Support' ? ' selected' : ''}>Psycho-Social Support</option>
                            <option value="Initial Interview"${appointment.purpose === 'Initial Interview' ? ' selected' : ''}>Initial Interview</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Counselor Preference</label>
                        <select class="form-control" name="counselor_preference" disabled>${counselorOptions}</select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label class="form-label mb-1">Brief Description(Optional)</label>
                        <textarea class="form-control" name="description" rows="2" disabled>${appointment.description || ''}</textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-secondary btn-sm me-2 enable-edit-btn">Enable Edit</button>
                        <button type="button" class="btn btn-primary btn-sm me-2 save-changes-btn" disabled>
                            <i class="fas fa-edit"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-danger btn-sm cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            `;
            setTimeout(() => {
                const enableBtn = form.querySelector('.enable-edit-btn');
                const saveBtn = form.querySelector('.save-changes-btn');
                const deleteBtn = form.querySelector('.delete-btn');
                const cancelBtn = form.querySelector('.cancel-btn');
                const inputs = form.querySelectorAll('input, select, textarea');
                const dateInput = form.querySelector('[name="preferred_date"]');
                const timeSelect = form.querySelector('[name="preferred_time"]');
                const counselorSelect = form.querySelector('[name="counselor_preference"]');
                enableBtn.addEventListener('click', function() {
                    const editing = enableBtn.dataset.editing === 'true';
                    if (!editing) {
                        inputs.forEach(input => {
                            if (input.name !== undefined && input.name !== '') {
                                input.disabled = false;
                                input.readOnly = false;
                            }
                        });
                        saveBtn.disabled = false;
                        enableBtn.textContent = 'Cancel Edit';
                        enableBtn.dataset.editing = 'true';

                        // When entering edit mode, load counselors by availability for current date/time
                        if (dateInput && timeSelect && counselorSelect) {
                            updateCounselorOptionsForForm(dateInput.value, timeSelect.value, counselorSelect, appointment.counselor_preference);
                            // Update options when date/time changes
                            dateInput.addEventListener('change', function() {
                                updateCounselorOptionsForForm(dateInput.value, timeSelect.value, counselorSelect, counselorSelect.value);
                            });
                            timeSelect.addEventListener('change', function() {
                                updateCounselorOptionsForForm(dateInput.value, timeSelect.value, counselorSelect, counselorSelect.value);
                            });
                        }
                    } else {
                        inputs.forEach(input => {
                            if (input.name !== undefined && input.name !== '') {
                                input.disabled = true;
                                input.readOnly = true;
                            }
                        });
                        saveBtn.disabled = true;
                        enableBtn.textContent = 'Enable Edit';
                        enableBtn.dataset.editing = 'false';
                    }
                });
                saveBtn.addEventListener('click', function() {
                    pendingSaveContext = { appointmentId: appointment.id, form };
                    const saveModal = new bootstrap.Modal(document.getElementById('saveChangesModal'));
                    saveModal.show();
                });
                
                cancelBtn.addEventListener('click', function() {
                    pendingCancelContext = { appointmentId: appointment.id };
                    document.getElementById('cancellationReason').value = '';
                    const cancelModal = new bootstrap.Modal(document.getElementById('cancellationReasonModal'));
                    cancelModal.show();
                });
            }, 0);
            container.appendChild(form);
        });
    }

    function displayAppointments(appointments, targetTableId) {
        const tableBody = document.getElementById(targetTableId);
        if (!tableBody) return;

        tableBody.innerHTML = '';

        // Determine if this table should show the reason column
        const showReason = [
            'allAppointmentsTable',
            'rejectedAppointmentsTable',
            'cancelledAppointmentsTable'
        ].includes(targetTableId);

        if (!appointments || appointments.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="${showReason ? 6 : 5}" class="text-center">No appointments found</td></tr>`;
            return;
        }

        appointments.forEach(appointment => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${formatDate(appointment.preferred_date)}</td>
                <td>${appointment.preferred_time}</td>
                <td>${appointment.consultation_type || ''}</td>
                <td>${appointment.purpose || 'N/A'}</td>
                <td>${appointment.counselor_name || 'Not assigned'}</td>
                <td><span class="badge badge-${getStatusClass(appointment.status)}">${appointment.status || 'PENDING'}</span></td>
                ${showReason ? `<td>${appointment.reason ? appointment.reason : ''}</td>` : ''}
            `;
            tableBody.appendChild(row);
        });
    }

    // ---- Availability helpers copied (lightweight) from student_schedule_appointment.js ----
    function getDayOfWeek(dateString) {
        const date = new Date(dateString);
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return days[date.getDay()];
    }

    function normalizePreferredTimeTo24hRange(rangeStr) {
        if (!rangeStr || typeof rangeStr !== 'string') return null;
        const parts = rangeStr.split('-').map(function (p) { return p.trim(); });
        if (parts.length !== 2) return null;
        function to24h(t) {
            const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!match) return null;
            let hour = parseInt(match[1], 10);
            const minute = match[2];
            const ampm = match[3].toUpperCase();
            if (ampm === 'AM') { if (hour === 12) hour = 0; } else { if (hour !== 12) hour += 12; }
            return String(hour).padStart(2, '0') + ':' + minute;
        }
        const start = to24h(parts[0]);
        const end = to24h(parts[1]);
        if (!start || !end) return null;
        return start + '-' + end;
    }

    function extractStartEnd24h(rangeStr) {
        if (!rangeStr || typeof rangeStr !== 'string') return null;
        const parts = rangeStr.split('-').map(function (p) { return p.trim(); });
        if (parts.length !== 2) return null;
        function to24h(t) {
            const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!match) return null;
            let hour = parseInt(match[1], 10);
            const minute = match[2];
            const ampm = match[3].toUpperCase();
            if (ampm === 'AM') { if (hour === 12) hour = 0; } else { if (hour !== 12) hour += 12; }
            return String(hour).padStart(2, '0') + ':' + minute;
        }
        const start = to24h(parts[0]);
        const end = to24h(parts[1]);
        if (!start || !end) return null;
        return { start: start, end: end };
    }

    async function updateCounselorOptionsForForm(preferredDate, preferredTime, counselorSelect, currentValue) {
        if (!counselorSelect) return;

        counselorSelect.disabled = true;
        counselorSelect.innerHTML = '<option value="">Loading available counselors...</option>';

        if (!preferredDate || !preferredTime) {
            counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
            counselorSelect.disabled = false;
            return;
        }

        const dayOfWeek = getDayOfWeek(preferredDate);
        const normalizedTimeRange = normalizePreferredTimeTo24hRange(preferredTime);
        const timeBounds = extractStartEnd24h(preferredTime);

        const url = new URL((window.BASE_URL || '/') + 'student/get-counselors-by-availability');
        url.searchParams.append('date', preferredDate);
        url.searchParams.append('day', dayOfWeek);
        url.searchParams.append('time', normalizedTimeRange || preferredTime);
        if (timeBounds) {
            url.searchParams.append('from', timeBounds.start);
            url.searchParams.append('to', timeBounds.end);
            url.searchParams.append('timeMode', 'overlap');
        }

        try {
            const response = await fetch(url.toString(), { method: 'GET', credentials: 'include', headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' } });
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();
            counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
            counselorSelect.insertAdjacentHTML('beforeend', `<option value="No preference">No preference</option>`);
            if (data.status === 'success' && Array.isArray(data.counselors)) {
                data.counselors.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.counselor_id;
                    opt.textContent = c.name;
                    counselorSelect.appendChild(opt);
                });
                if (currentValue) {
                    counselorSelect.value = currentValue;
                }
                if (data.counselors.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No counselors available for the selected date/time.';
                    opt.disabled = true;
                    counselorSelect.appendChild(opt);
                }
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Error loading counselors';
                opt.disabled = true;
                counselorSelect.appendChild(opt);
            }
        } catch (e) {
            console.log('Error loading counselors by availability:', e);
            counselorSelect.innerHTML = '<option value="">Error loading counselors</option>';
        } finally {
            counselorSelect.disabled = false;
        }
    }

    function handleTabChange(event) {
        const targetTabId = event.target.getAttribute('data-bs-target').replace('#', '');
        let status;
        let targetTableId;

        switch (targetTabId) {
            case 'rejected':
                status = 'REJECTED';
                targetTableId = 'rejectedAppointmentsTable';
                break;
            case 'completed':
                status = 'COMPLETED';
                targetTableId = 'completedAppointmentsTable';
                break;
            case 'cancelled':
                status = 'CANCELLED';
                targetTableId = 'cancelledAppointmentsTable';
                break;
            case 'all':
            default:
                status = 'all';
                targetTableId = 'allAppointmentsTable';
        }

        const filteredAppointments = status === 'all' 
            ? allAppointments 
            : allAppointments.filter(app => app.status && app.status.toUpperCase() === status);

        displayAppointments(filteredAppointments, targetTableId);
    }

    function filterAppointments() {
        const searchTerm = searchInput.value.toLowerCase();
        const dateValue = dateFilter.value;

        let filtered = allAppointments;

        if (searchTerm) {
            filtered = filtered.filter(appointment =>
                Object.values(appointment).some(value =>
                    String(value).toLowerCase().includes(searchTerm)
                )
            );
        }

        if (dateValue) {
            filtered = filtered.filter(appointment =>
                appointment.preferred_date.startsWith(dateValue)
            );
        }

        const activeTab = document.querySelector('.nav-link.active');
        if (activeTab) {
            const tabId = activeTab.getAttribute('data-bs-target').replace('#', '');
            let status;
            let targetTableId;

            switch (tabId) {
                case 'rejected':
                    status = 'REJECTED';
                    targetTableId = 'rejectedAppointmentsTable';
                    break;
                case 'completed':
                    status = 'COMPLETED';
                    targetTableId = 'completedAppointmentsTable';
                    break;
                case 'cancelled':
                    status = 'CANCELLED';
                    targetTableId = 'cancelledAppointmentsTable';
                    break;
                case 'all':
                default:
                    status = 'all';
                    targetTableId = 'allAppointmentsTable';
            }

            if (status !== 'all') {
                filtered = filtered.filter(app => app.status && app.status.toUpperCase() === status);
            }

            displayAppointments(filtered, targetTableId);
        } else {
            displayAppointments(filtered, 'allAppointmentsTable');
        }
    }

    // Edit appointment
    window.editAppointment = function(appointmentId) {
        const appointment = allAppointments.find(app => app.id === appointmentId);
        if (!appointment) return;

        document.getElementById('editAppointmentId').value = appointment.id;
        document.getElementById('editDate').value = appointment.preferred_date;
        document.getElementById('editTime').value = appointment.preferred_time;
        document.getElementById('editConsultationType').value = appointment.consultation_type;
        document.getElementById('editPurpose').value = appointment.purpose;
        document.getElementById('editDescription').value = appointment.description || '';

        editModal.show();
    };

    async function saveEdit() {
        const appointmentId = document.getElementById('editAppointmentId').value;
        const date = document.getElementById('editDate').value;
        const time = document.getElementById('editTime').value;
        const consultationType = document.getElementById('editConsultationType').value;
        const purpose = document.getElementById('editPurpose').value;
        const description = document.getElementById('editDescription').value;

        try {
            const response = await fetch((window.BASE_URL || '/') + 'student/appointments/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    preferred_date: date,
                    preferred_time: time,
                    consultation_type: consultationType,
                    purpose: purpose,
                    description: description
                })
            });

            const data = await response.json();

            if (data.success) {
                editModal.hide();
                fetchAppointments(); // Refresh the appointments list
                showSuccess('Appointment updated successfully');
            } else {
                showError(data.message || 'Failed to update appointment');
            }
        } catch (error) {
            console.error('Error updating appointment:', error);
            showError('An error occurred while updating the appointment');
        }
    }

    // Delete appointment
    window.deleteAppointment = async function(appointmentId) {
        if (!confirm('Are you sure you want to delete this appointment?')) return;

        try {
            const response = await fetch((window.BASE_URL || '/') + `student/appointments/delete/${appointmentId}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                fetchAppointments(); // Refresh the appointments list
                showSuccess('Appointment deleted successfully');
            } else {
                showError(data.message || 'Failed to delete appointment');
            }
        } catch (error) {
            console.error('Error deleting appointment:', error);
            showError('An error occurred while deleting the appointment');
        }
    };

    // Cancel appointment
    window.cancelAppointment = function(appointmentId) {
        document.getElementById('cancelAppointmentId').value = appointmentId;
        document.getElementById('cancelReason').value = '';
        cancelModal.show();
    };

    async function confirmCancel() {
        const appointmentId = document.getElementById('cancelAppointmentId').value;
        const reason = document.getElementById('cancelReason').value;

        if (!reason.trim()) {
            showError('Please provide a reason for cancellation');
            return;
        }

        try {
            const response = await fetch((window.BASE_URL || '/') + 'student/appointments/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    reason: reason
                })
            });

            const data = await response.json();

            if (data.success) {
                cancelModal.hide();
                fetchAppointments(); // Refresh the appointments list
                showSuccess('Appointment cancelled successfully');
            } else {
                showError(data.message || 'Failed to cancel appointment');
            }
        } catch (error) {
            console.error('Error cancelling appointment:', error);
            showError('An error occurred while cancelling the appointment');
        }
    }

    async function updatePendingAppointment(appointmentId, form) {
        const preferred_date = form.querySelector('[name="preferred_date"]').value;
        const preferred_time = form.querySelector('[name="preferred_time"]').value;
        const consultation_type = form.querySelector('[name="consultation_type"]').value;
        const purpose = form.querySelector('[name="purpose"]').value;
        const counselor_preference = form.querySelector('[name="counselor_preference"]').value;
        const description = form.querySelector('[name="description"]').value;

        // Check for counselor conflicts before updating
        const hasConflict = await checkEditConflicts(appointmentId, counselor_preference, preferred_date, preferred_time);
        if (hasConflict) {
            return; // Conflict modal will be shown, don't proceed with update
        }

        try {
            const response = await fetch((window.BASE_URL || '/') + 'student/appointments/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    preferred_date,
                    preferred_time,
                    consultation_type,
                    purpose,
                    counselor_preference,
                    description,
                    status: 'pending' // always keep as pending
                })
            });
            const data = await response.json();
            if (data.success) {
                showSuccess('Appointment updated successfully.');
                fetchAppointments();
            } else {
                showError(data.message || 'Failed to update appointment.');
            }
        } catch (error) {
            showError('An error occurred while updating the appointment.');
        }
    }

    async function deleteAppointment(appointmentId) {
        try {
            const response = await fetch((window.BASE_URL || '/') + `student/appointments/delete/${appointmentId}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            if (data.success) {
                showSuccess('Appointment deleted successfully.');
                fetchAppointments();
            } else {
                showError(data.message || 'Failed to delete appointment.');
            }
        } catch (error) {
            showError('An error occurred while deleting the appointment.');
        }
    }

    async function cancelPendingAppointment(appointmentId, reason) {
        try {
            const response = await fetch((window.BASE_URL || '/') + 'student/appointments/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    reason: reason
                })
            });
            const data = await response.json();
            if (data.success) {
                showSuccess('Appointment cancelled successfully.');
                fetchAppointments();
            } else {
                showError(data.message || 'Failed to cancel appointment.');
            }
        } catch (error) {
            showError('An error occurred while cancelling the appointment.');
        }
    }

    // Utility functions
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }

    function formatTime(timeString) {
        return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }

    function getStatusClass(status) {
        if (!status) return 'pending';
        switch (status.toUpperCase()) {
            case 'APPROVED':
                return 'approved';
            case 'REJECTED':
                return 'rejected';
            case 'COMPLETED':
                return 'completed';
            case 'CANCELLED':
                return 'cancelled';
            case 'PENDING':
            default:
                return 'pending';
        }
    }

    function showLoading() {
        if (loadingSpinner) loadingSpinner.style.display = 'flex';
    }

    function hideLoading() {
        if (loadingSpinner) loadingSpinner.style.display = 'none';
    }

    function showEmptyState() {
        if (emptyState) emptyState.style.display = 'block';
    }

    function hideEmptyState() {
        if (emptyState) emptyState.style.display = 'none';
    }

    function showError(message) {
        // You can implement a toast or alert system here
        alert(message);
    }

    function showSuccess(message) {
        // Update modal message and show success modal
        const successModalMessage = document.getElementById('successModalMessage');
        if (successModalMessage) {
            successModalMessage.textContent = message;
        }
        
        // Show the modal using Bootstrap
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    }

    // Generate appointment ticket HTML
    async function generateAppointmentTicket(appointment) {
        const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
        const qrCodeData = JSON.stringify({
            appointmentId: appointment.id,
            studentId: appointment.student_id,
            date: appointment.preferred_date,
            time: appointment.preferred_time,
            counselor: appointment.counselor_name,
            type: appointment.consultation_type,
            purpose: appointment.purpose,
            ticketId: ticketId
        });

        return `
            <div class="approved-appointment-ticket">
                <div class="ticket-header">
                    <div class="ticket-title-container">
                        <img src="${window.BASE_URL || '/'}Photos/ticket_logo_green.png" alt="Counselign Logo" class="ticket-logo">
                        <h3 class="ticket-title">Appointment Ticket</h3>
                    </div>
                    <span class="ticket-status">Approved</span>
                </div>
                
                <div class="ticket-details">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Date</div>
                            <p class="detail-value">${formatDate(appointment.preferred_date)}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-clock detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Time</div>
                            <p class="detail-value">${appointment.preferred_time}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-user-md detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Counselor</div>
                            <p class="detail-value">${appointment.counselor_name || 'Not assigned'}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-comments detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Consultation Type</div>
                            <p class="detail-value">${appointment.consultation_type || 'Not specified'}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-bullseye detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Purpose</div>
                            <p class="detail-value">${appointment.purpose || 'Not specified'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="ticket-footer">
                    <div class="qr-code-container">
                        <div class="qr-code" id="qr-code-${appointment.id}">
                            <div>Generating QR Code...</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Ticket ID:</div>
                            <div style="font-weight: 600; color: #28a745;">${ticketId}</div>
                        </div>
                    </div>
                    
                    <button class="download-ticket-btn" data-appointment-id="${appointment.id}">
                        <i class="fas fa-download"></i>
                        Download Ticket
                    </button>
                </div>
            </div>
        `;
    }

    // Generate QR code for appointment
    function generateQRCode(appointmentId, qrCodeData) {
        const qrContainer = document.getElementById(`qr-code-${appointmentId}`);
        if (!qrContainer) {
            console.error('QR container not found:', `qr-code-${appointmentId}`);
            return;
        }

        // Wait for qrcode library to load
        const checkQRCodeLibrary = () => {
            if (typeof qrcode !== 'undefined') {
                try {
                    // Clear the container
                    qrContainer.innerHTML = '';

                    // Generate QR code using qrcode-generator (SVG with margin creates proper quiet zone)
                    const qr = qrcode(0, 'M');
                    qr.addData(qrCodeData);
                    qr.make();

                    // Use SVG tag with explicit cell size and margin (quiet zone). Default color is black-on-white.
                    const cellSize = 4; // px per module (scales the final size)
                    const margin = 4;   // modules of quiet zone
                    const svgMarkup = qr.createSvgTag(cellSize, margin);

                    // Inject SVG
                    qrContainer.innerHTML = svgMarkup;

                    // Ensure SVG fits inside its box
                    const svgEl = qrContainer.querySelector('svg');
                    if (svgEl) {
                        svgEl.style.width = '100%';
                        svgEl.style.height = '100%';
                        svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                        // Remove any hardcoded width/height that could overflow
                        svgEl.removeAttribute('width');
                        svgEl.removeAttribute('height');
                    }
                    console.log('QR Code (SVG) generated successfully');
                    
                } catch (error) {
                    console.error('QR Code generation error:', error);
                    qrContainer.innerHTML = `
                        <div style="font-size: 10px; color: #dc3545; text-align: center;">
                            <div style="font-weight: bold;">QR</div>
                            <div>Error</div>
                        </div>
                    `;
                }
            } else {
                // Fallback if qrcode library is not loaded
                console.warn('qrcode library not available, showing placeholder');
                qrContainer.innerHTML = `
                    <div style="font-size: 8px; color: #28a745; text-align: center; line-height: 1.2;">
                        <div style="font-weight: bold; margin-bottom: 2px;">QR CODE</div>
                        <div style="font-size: 6px;">Appointment</div>
                        <div style="font-size: 6px;">ID: ${appointmentId}</div>
                    </div>
                `;
            }
        };

        // Check immediately and also after a delay to ensure library is loaded
        checkQRCodeLibrary();
        
        // If library is not ready, wait a bit more
        if (typeof qrcode === 'undefined') {
            setTimeout(checkQRCodeLibrary, 1000);
        }
    }

    // Download appointment ticket as PDF
    async function downloadAppointmentTicket(appointment) {
        try {
            const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
            const qrCodeData = JSON.stringify({
                appointmentId: appointment.id,
                studentId: appointment.student_id,
                date: appointment.preferred_date,
                time: appointment.preferred_time,
                counselor: appointment.counselor_name,
                type: appointment.consultation_type,
                purpose: appointment.purpose,
                ticketId: ticketId
            });
            
            // Check if jsPDF is available
            if (typeof window.jspdf === 'undefined') {
                showError('PDF library not loaded. Please refresh the page and try again.');
                return;
            }
            
            // Create new PDF document (A4 size: 210mm x 297mm)
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            // Set up colors (RGB values for jsPDF)
            const greenColor = [40, 167, 69]; // #28a745
            const lightGrayColor = [248, 249, 250]; // #f8f9fa
            const darkGrayColor = [108, 117, 125]; // #6c757d
            const borderGrayColor = [233, 236, 239]; // #e9ecef
            
            // Page dimensions
            const pageWidth = 210;
            const pageHeight = 297;
            const margin = 20;
            const contentWidth = pageWidth - (margin * 2);
            
            // Start Y position
            let currentY = margin;
            
            // Helper function to add text with styling
            function addText(x, y, text, options = {}) {
                const {
                    fontSize = 12,
                    fontStyle = 'normal',
                    color = [0, 0, 0],
                    align = 'left'
                } = options;
                
                doc.setFontSize(fontSize);
                doc.setFont('helvetica', fontStyle);
                doc.setTextColor(color[0], color[1], color[2]);
                doc.text(text, x, y);
            }
            
            // Helper function to add rectangle
            function addRect(x, y, width, height, options = {}) {
                const {
                    fillColor = null,
                    strokeColor = [0, 0, 0],
                    lineWidth = 0.5
                } = options;
                
                if (fillColor) {
                    doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
                }
                doc.setDrawColor(strokeColor[0], strokeColor[1], strokeColor[2]);
                doc.setLineWidth(lineWidth);
                
                if (fillColor) {
                    doc.rect(x, y, width, height, 'FD');
                } else {
                    doc.rect(x, y, width, height, 'S');
                }
            }
            
            // Helper function to add rounded rectangle
            function addRoundedRect(x, y, width, height, radius, options = {}) {
                const {
                    fillColor = null,
                    strokeColor = [0, 0, 0],
                    lineWidth = 0.5
                } = options;
                
                if (fillColor) {
                    doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
                }
                doc.setDrawColor(strokeColor[0], strokeColor[1], strokeColor[2]);
                doc.setLineWidth(lineWidth);
                
                // Create rounded rectangle using lines and arcs
                const path = `M ${x + radius} ${y} L ${x + width - radius} ${y} Q ${x + width} ${y} ${x + width} ${y + radius} L ${x + width} ${y + height - radius} Q ${x + width} ${y + height} ${x + width - radius} ${y + height} L ${x + radius} ${y + height} Q ${x} ${y + height} ${x} ${y + height - radius} L ${x} ${y + radius} Q ${x} ${y} ${x + radius} ${y} Z`;
                
                if (fillColor) {
                    doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
                    doc.path(path, 'FD');
                } else {
                    doc.path(path, 'S');
                }
            }
            
            // Main ticket border (2mm thick green border)
            addRoundedRect(margin, currentY, contentWidth, 80, 2, {
                fillColor: [255, 255, 255],
                strokeColor: greenColor,
                lineWidth: 2
            });
            
            // Header section
            const headerY = currentY + 5;
            
            // Logo (40x40px = ~11x11mm)
            try {
                // Try to load and embed the logo image
                const logoUrl = `${window.BASE_URL || '/'}Photos/ticket_logo_green.png`;
                const logoResponse = await fetch(logoUrl);
                if (logoResponse.ok) {
                    const logoBlob = await logoResponse.blob();
                    const logoBase64 = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.readAsDataURL(logoBlob);
                    });
                    
                    // Add logo image to PDF
                    doc.addImage(logoBase64, 'PNG', margin + 5, headerY, 11, 9);
                } else {
                    throw new Error('Logo not found');
                }
            } catch (error) {
                console.warn('Could not load logo, using placeholder:', error);
                // Fallback to placeholder
                addRect(margin + 5, headerY, 11, 11, {
                    fillColor: lightGrayColor,
                    strokeColor: greenColor,
                    lineWidth: 1
                });
                addText(margin + 5.5, headerY + 7, 'LOGO', {
                    fontSize: 6,
                    color: greenColor,
                    align: 'center'
                });
            }
            
            // Title
            addText(margin + 20, headerY + 7, 'Appointment Ticket', {
                fontSize: 16,
                fontStyle: 'bold',
                color: greenColor
            });
            
            // Status badge
            const statusText = 'APPROVED';
            const statusWidth = 25;
            const statusX = margin + contentWidth - statusWidth - 5;
            addRoundedRect(statusX, headerY, statusWidth, 8, 4, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(statusX + statusWidth/2, headerY + 5.5, statusText, {
                fontSize: 8,
                fontStyle: 'bold',
                color: [255, 255, 255],
                align: 'center'
            });
            
            // Header bottom border
            addRect(margin + 5, headerY + 12, contentWidth - 10, 0.5, {
                strokeColor: borderGrayColor,
                lineWidth: 0.5
            });
            
            currentY = headerY + 20;
            
            // Details section (2x3 grid)
            const detailBoxWidth = (contentWidth - 20) / 2;
            const detailBoxHeight = 15;
            const detailSpacing = 5;
            
            // Row 1
            // Date box
            addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8, currentY + 4, 'DATE', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8, currentY + 8, formatDate(appointment.preferred_date), {
                fontSize: 10,
                fontStyle: 'bold',
                color: [33, 37, 41]
            });
            
            // Time box
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 4, 'TIME', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 8, appointment.preferred_time, {
                fontSize: 10,
                fontStyle: 'bold',
                color: [33, 37, 41]
            });
            
            currentY += detailBoxHeight + detailSpacing;
            
            // Row 2
            // Counselor box
            addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8, currentY + 4, 'COUNSELOR', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8, currentY + 8, appointment.counselor_name || 'Not assigned', {
                fontSize: 10,
                fontStyle: 'bold',
                color: [33, 37, 41]
            });
            
            // Consultation Type box
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 4, 'CONSULTATION TYPE', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 8, appointment.consultation_type || 'Not specified', {
                fontSize: 10,
                fontStyle: 'bold',
                color: [33, 37, 41]
            });
            
            currentY += detailBoxHeight + detailSpacing;
            
            // Row 3
            // Purpose box
            addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8, currentY + 4, 'PURPOSE', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8, currentY + 8, appointment.purpose || 'Not specified', {
                fontSize: 10,
                fontStyle: 'bold',
                color: [33, 37, 41]
            });
            
            // Empty box for symmetry (or could add another field in the future)
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, detailBoxWidth, detailBoxHeight, {
                fillColor: lightGrayColor,
                strokeColor: [255, 255, 255],
                lineWidth: 0
            });
            addRect(margin + 5 + detailBoxWidth + detailSpacing, currentY, 1, detailBoxHeight, {
                fillColor: greenColor,
                strokeColor: greenColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 4, 'STATUS', {
                fontSize: 6,
                color: darkGrayColor
            });
            addText(margin + 8 + detailBoxWidth + detailSpacing, currentY + 8, 'APPROVED', {
                fontSize: 10,
                fontStyle: 'bold',
                color: greenColor
            });
            
            currentY += detailBoxHeight + 10;
            
            // Footer section
            addRect(margin + 5, currentY, contentWidth - 10, 0.5, {
                strokeColor: borderGrayColor,
                lineWidth: 0.5
            });
            
            currentY += 5;
            
            // Footer content
            const footerLeftWidth = contentWidth - 35; // Leave space for QR code
            
            // Ticket ID
            addText(margin + 5, currentY, `Ticket ID: ${ticketId}`, {
                fontSize: 8,
                color: darkGrayColor
            });
            
            // Instructions
            addText(margin + 5, currentY + 4, 'Please bring this ticket to your appointment', {
                fontSize: 8,
                color: darkGrayColor
            });
            
            // Generated date
            addText(margin + 5, currentY + 8, `Generated on: ${new Date().toLocaleString()}`, {
                fontSize: 8,
                color: darkGrayColor
            });
            
            // QR Code (30x30mm area) with quiet zone inside
            const qrCodeSize = 30;
            const qrCodeX = margin + contentWidth - qrCodeSize - 5;
            const qrCodeY = currentY;
            
            // QR Code border
            addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
                fillColor: lightGrayColor,
                strokeColor: greenColor,
                lineWidth: 1
            });
            
            // Generate QR Code
            if (typeof qrcode !== 'undefined') {
                try {
                    const qr = qrcode(0, 'M');
                    qr.addData(qrCodeData);
                    qr.make();
                    
                    const qrSize = qr.getModuleCount();
                    // Add a white quiet zone around modules for reliable scanning
                    const quietZone = 2; // mm of quiet zone inside the 30mm box
                    const drawableSize = Math.max(0, qrCodeSize - (quietZone * 2));
                    const cellSize = drawableSize / qrSize;
                    
                    // Draw QR code with improved rendering
                    // First, fill the entire QR area with white background
                    addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
                        fillColor: [255, 255, 255],
                        strokeColor: [255, 255, 255]
                    });
                    
                    // Then draw only the dark modules
                    for (let row = 0; row < qrSize; row++) {
                        for (let col = 0; col < qrSize; col++) {
                            if (qr.isDark(row, col)) {
                                const x = qrCodeX + quietZone + (col * cellSize);
                                const y = qrCodeY + quietZone + (row * cellSize);
                                
                                // Draw each dark cell as a filled rectangle
                                doc.setFillColor(0, 0, 0); // black modules
                                doc.rect(x, y, cellSize, cellSize, 'F');
                            }
                        }
                    }
                    
                    // Add a subtle border around the QR code
                    addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
                        fillColor: null,
                        strokeColor: greenColor,
                        lineWidth: 0.5
                    });
                    
                } catch (error) {
                    console.error('QR Code generation error:', error);
                    // Fallback text
                    addText(qrCodeX + qrCodeSize/2, qrCodeY + qrCodeSize/2, 'QR CODE', {
                        fontSize: 8,
                        fontStyle: 'bold',
                        color: greenColor,
                        align: 'center'
                    });
                }
            } else {
                // Fallback text
                addText(qrCodeX + qrCodeSize/2, qrCodeY + qrCodeSize/2, 'QR CODE', {
                    fontSize: 8,
                    fontStyle: 'bold',
                    color: greenColor,
                    align: 'center'
                });
            }
            
            // Download the PDF
            const fileName = `Appointment_Ticket_${appointment.id}_${ticketId}.pdf`;
            doc.save(fileName);
            
            showSuccess('Appointment ticket downloaded as PDF successfully');
        } catch (error) {
            console.error('Error downloading ticket:', error);
            showError('Failed to download ticket. Please try again.');
        }
    }


    // Save Changes Modal
    const confirmSaveBtn = document.getElementById('confirmSaveChangesBtn');
    if (confirmSaveBtn) {
        confirmSaveBtn.addEventListener('click', function() {
            if (pendingSaveContext) {
                updatePendingAppointment(pendingSaveContext.appointmentId, pendingSaveContext.form);
                bootstrap.Modal.getInstance(document.getElementById('saveChangesModal')).hide();
                pendingSaveContext = null;
            }
        });
    }

    // Cancel Modal
    const confirmCancelBtn = document.getElementById('confirmCancellationBtn');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function() {
            if (pendingCancelContext) {
                const reason = document.getElementById('cancellationReason').value;
                if (reason && reason.trim() !== '') {
                    cancelPendingAppointment(pendingCancelContext.appointmentId, reason);
                    bootstrap.Modal.getInstance(document.getElementById('cancellationReasonModal')).hide();
                    pendingCancelContext = null;
                } else {
                    showError('Cancellation reason is required.');
                }
            }
        });
    }

    // Delete Modal
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (pendingDeleteId) {
                deleteAppointment(pendingDeleteId);
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal')).hide();
                pendingDeleteId = null;
            }
        });
    }
});

// Check for counselor conflicts before editing appointment
async function checkEditConflicts(appointmentId, counselorId, date, time) {
    try {
        if (!counselorId || counselorId === 'No preference' || !date || !time) {
            return false; // No specific counselor selected, no conflict check needed
        }

        const response = await fetch((window.BASE_URL || '/') + 'student/check-edit-conflicts?' + 
            new URLSearchParams({
                appointment_id: appointmentId,
                counselor_id: counselorId,
                date: date,
                time: time
            }), {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to check counselor availability');
        }

        const data = await response.json();

        if (data.status === 'success' && data.hasConflict) {
            showEditConflictModal(data.message, data.conflictType);
            return true; // Has conflict
        }

        return false; // No conflict
    } catch (error) {
        console.error('Error checking edit conflicts:', error);
        showError('Error checking counselor availability. Please try again.');
        return false;
    }
}

// Show edit conflict modal
function showEditConflictModal(message, conflictType) {
    // Remove any existing conflict modal
    const existingModal = document.querySelector('.edit-conflict-modal');
    if (existingModal) {
        document.body.removeChild(existingModal);
    }

    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'edit-conflict-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center';
    modal.style.zIndex = '9999';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';

    // Create modal content
    const modalContent = document.createElement('div');
    modalContent.className = 'bg-white rounded-lg p-6 max-w-md mx-4 text-center relative';
    modalContent.style.width = '400px';
    modalContent.style.borderRadius = '8px';
    modalContent.style.maxWidth = '90vw';
    modalContent.style.maxHeight = '90vh';
    modalContent.style.overflow = 'auto';

    // Modal content HTML
    modalContent.innerHTML = `
        <div class="mb-4">
            <div class="text-red-500 mb-4">
                <i class="fas fa-exclamation-triangle text-4xl"></i>
            </div>
            <h3 class="text-xl font-semibold mb-3 text-gray-800">Counselor Not Available</h3>
            <p class="text-gray-700 mb-4 text-sm leading-relaxed">${message}</p>
            <p class="text-gray-600 mb-4 text-xs">Please choose a different time slot or select another counselor.</p>
        </div>
        <div class="flex justify-center mb-2">
            <button id="editConflictModalOk" type="button" style="
                background: linear-gradient(90deg, #2563EB 0%, #1D4ED8 100%);
                color: #FFFFFF;
                font-weight: 600;
                padding: 12px 24px;
                border-radius: 10px;
                border: none;
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                cursor: pointer;
                outline: none;
            ">
                OK, I Understand
            </button>
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    // Add event listener for OK button
    const okButton = document.getElementById('editConflictModalOk');
    if (okButton) {
        okButton.addEventListener('click', function() {
            document.body.removeChild(modal);
        });
    }

    // Add event listener for clicking outside modal
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });

    // Add event listener for ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.querySelector('.edit-conflict-modal')) {
            document.body.removeChild(modal);
        }
    });
}

// Setup counselor schedules to load when calendar drawer is opened
function setupCounselorSchedulesInDrawer() {
    const toggleBtn = document.getElementById('counselorsCalendarToggleBtn');
    const drawer = document.getElementById('counselorsCalendarDrawer');
    
    if (!toggleBtn || !drawer) return;
    
    let schedulesLoaded = false;
    
    // Add event listener to load schedules when drawer is opened
    toggleBtn.addEventListener('click', async () => {
        if (!schedulesLoaded) {
            await loadCounselorSchedules();
            schedulesLoaded = true;
        }
    });
}

// Load and display counselor schedules
async function loadCounselorSchedules() {
    const container = document.getElementById('counselorSchedulesContainer');
    if (!container) return;

    try {
        const response = await fetch((window.BASE_URL || '/') + 'student/get-counselor-schedules', {
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

        if (data.status === 'success' && data.schedules) {
            displayCounselorSchedules(data.schedules);
        } else {
            showCounselorSchedulesError('Failed to load counselor schedules');
        }
    } catch (error) {
        console.error('Error loading counselor schedules:', error);
        showCounselorSchedulesError('An error occurred while loading counselor schedules');
    }
}

// Display counselor schedules in the UI
function displayCounselorSchedules(schedules) {
    const container = document.getElementById('counselorSchedulesContainer');
    if (!container) return;

    const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    
    // Create schedules grid
    const schedulesGrid = document.createElement('div');
    schedulesGrid.className = 'schedules-grid';

    daysOfWeek.forEach(day => {
        const dayCard = createDayScheduleCard(day, schedules[day] || []);
        schedulesGrid.appendChild(dayCard);
    });

    // Clear loading state and add schedules
    container.innerHTML = '';
    container.appendChild(schedulesGrid);
}

// Create a day schedule card
function createDayScheduleCard(day, counselors) {
    const dayCard = document.createElement('div');
    dayCard.className = 'day-schedule-card';

    // Create day header with gradient background
    const dayHeader = document.createElement('div');
    dayHeader.className = `day-header ${day.toLowerCase()}`;
    dayHeader.textContent = day;

    // Create counselors list container
    const counselorsList = document.createElement('div');
    counselorsList.className = 'counselors-list';

    if (counselors && counselors.length > 0) {
        counselors.forEach(counselor => {
            const counselorItem = createCounselorItem(counselor);
            counselorsList.appendChild(counselorItem);
        });
    } else {
        const noCounselorsDiv = document.createElement('div');
        noCounselorsDiv.className = 'no-counselors';
        noCounselorsDiv.innerHTML = `
            <i class="fas fa-user-slash"></i>
            <h4>No Counselors Available</h4>
            <p>No counselors are scheduled for ${day}</p>
        `;
        counselorsList.appendChild(noCounselorsDiv);
    }

    dayCard.appendChild(dayHeader);
    dayCard.appendChild(counselorsList);

    return dayCard;
}

// Create a counselor item
function createCounselorItem(counselor) {
    const counselorItem = document.createElement('div');
    counselorItem.className = 'counselor-item';

    const counselorName = document.createElement('div');
    counselorName.className = 'counselor-name';
    counselorName.innerHTML = `
        <i class="fas fa-user-md"></i>
        ${counselor.counselor_name}
    `;

    const timeSlots = document.createElement('div');
    timeSlots.className = 'time-slots';

    if (counselor.time_scheduled) {
        // Parse time_scheduled string and create badges
        const timeSlotsArray = counselor.time_scheduled.split(',').map(slot => slot.trim()).filter(slot => slot);
        
        timeSlotsArray.forEach(slot => {
            const timeBadge = document.createElement('span');
            timeBadge.className = 'time-slot-badge';
            timeBadge.textContent = formatTimeSlot(slot);
            timeSlots.appendChild(timeBadge);
        });
    } else {
        const noTimeSlot = document.createElement('span');
        noTimeSlot.className = 'time-slot-badge';
        noTimeSlot.textContent = 'Available';
        timeSlots.appendChild(noTimeSlot);
    }

    counselorItem.appendChild(counselorName);
    counselorItem.appendChild(timeSlots);

    return counselorItem;
}

// Format time slot for display
function formatTimeSlot(timeSlot) {
    if (!timeSlot) return 'Available';
    
    // If it's already in 12-hour format, return as is
    if (timeSlot.includes('AM') || timeSlot.includes('PM')) {
        return timeSlot;
    }
    
    // If it's in 24-hour format, convert to 12-hour
    if (timeSlot.includes(':')) {
        const [hour, minute] = timeSlot.split(':');
        const hourNum = parseInt(hour);
        const ampm = hourNum >= 12 ? 'PM' : 'AM';
        const displayHour = hourNum === 0 ? 12 : hourNum > 12 ? hourNum - 12 : hourNum;
        return `${displayHour}:${minute} ${ampm}`;
    }
    
    return timeSlot;
}

// Show error message for counselor schedules
function showCounselorSchedulesError(message) {
    const container = document.getElementById('counselorSchedulesContainer');
    if (!container) return;

    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <h4>Error Loading Schedules</h4>
        <p>${message}</p>
    `;

    container.innerHTML = '';
    container.appendChild(errorDiv);
} 