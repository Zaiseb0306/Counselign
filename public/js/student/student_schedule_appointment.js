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

    // Initialize button state immediately to prevent loading state on page load
    initializeButtonState();

    // Check appointment eligibility first (pending, approved, or pending follow-up)
    checkAppointmentEligibility().then(() => {
        // Only proceed with other initializations if no pending appointment
        setMinimumAppointmentDate();
        loadCounselors();
        setupFormSubmission();
        setupCounselorAvailabilityFiltering();
        setupConsultationTypeHandling();
        checkForUrlMessage();
        initializeCounselorsCalendarDrawer();
        // Directly load counselor schedules since the toggle/drawer was removed
        loadCounselorSchedules();
        setupAcknowledgmentValidation();
    });
});

// Check for appointment eligibility (pending, approved, pending follow-up)
async function checkAppointmentEligibility() {
    try {
        const response = await fetch((window.BASE_URL || '/') + 'student/check-appointment-eligibility', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            if (response.status === 401) {
                SecureLogger.info('User not logged in, skipping pending appointment check');
                return; // Skip if not logged in
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'success') {
            const appointmentForm = document.getElementById('appointmentForm');
            const formElements = appointmentForm.querySelectorAll('input, select, textarea');
            
            if (data.hasPending || data.hasApproved || data.hasPendingFollowUp) {
                // Priority: pending follow-up > pending > approved
                const messageDiv = document.getElementById('formMessage');
                if (data.hasPendingFollowUp) {
                    messageDiv.textContent = 'You have a pending follow-up session. Please complete or resolve it before scheduling a new appointment.';
                } else if (data.hasPending) {
                    messageDiv.textContent = 'You already have a pending appointment. Please wait for it to be approved before scheduling another one.';
                } else if (data.hasApproved) {
                    messageDiv.textContent = 'You already have an approved upcoming appointment. You cannot schedule another at this time.';
                }
                messageDiv.classList.remove(
                    'hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800',
                    'bg-yellow-100', 'text-yellow-800', 'bg-red-200', 'text-yellow-400'
                );
                messageDiv.classList.add('bg-danger-outline', 'text-warning');
                
                // Disable all form elements
                formElements.forEach(element => {
                    element.disabled = true;
                });
                
                // Hide the submit button
                const submitButton = document.getElementById('scheduleAppointmentBtn');
                if (submitButton) {
                    submitButton.style.display = 'none';
                }
            } else {
                // Enable all form elements
                formElements.forEach(element => {
                    element.disabled = false;
                });
                
                // Show the submit button
                const submitButton = document.getElementById('scheduleAppointmentBtn');
                if (submitButton) {
                    submitButton.style.display = 'block';
                }
            }
        }
    } catch (error) {
        SecureLogger.info('Error checking pending appointment (user may not be logged in):', error.message);
        // Don't show error to user, just log it
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
}

// Set minimum date for appointment
function setMinimumAppointmentDate() {
    // Get tomorrow's date (can't book for today)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const day = String(tomorrow.getDate()).padStart(2, '0');
    const minDate = `${year}-${month}-${day}`;

    // Set the min date attribute of the date input
    const dateInput = document.getElementById('preferredDate');
    if (dateInput) {
        dateInput.setAttribute('min', minDate);
        dateInput.setAttribute('value', minDate);
        // Also refresh available time slots on initial set
        refreshTimeSlotsForDate(minDate);
    }
}

// Load counselors from database
function loadCounselors() {
    const counselorSelect = document.getElementById('counselorPreference');
    if (!counselorSelect) return;

    // Show loading state in the select
    counselorSelect.disabled = true;
    counselorSelect.innerHTML = '<option value="">Loading counselors...</option>';

    fetch((window.BASE_URL || '/') + 'student/get-counselors', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('User not logged in');
                }
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Clear loading state
            counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
            
            if (data.status === 'success' && Array.isArray(data.counselors)) {
                // Add counselors from database
                data.counselors.forEach(counselor => {
                    const option = document.createElement('option');
                    option.value = counselor.counselor_id;
                    option.textContent = `${counselor.name}`;
                    counselorSelect.appendChild(option);
                });

                // If no counselors were found, show a message
                if (data.counselors.length === 0) {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "No counselors available";
                    option.disabled = true;
                    counselorSelect.appendChild(option);
                }
            } else {
                throw new Error('Invalid data format received from server');
            }
        })
        .catch(error => {
            SecureLogger.info('Error loading counselors (user may not be logged in):', error.message);
            counselorSelect.innerHTML = '<option value="">Please log in to see counselors</option>';
        })
        .finally(() => {
            counselorSelect.disabled = false;
        });
}

// Load counselors by availability for specific date and time
function loadCounselorsByAvailability(preferredDate, preferredTime) {
    const counselorSelect = document.getElementById('counselorPreference');
    if (!counselorSelect) return;

    // Show loading state in the select
    counselorSelect.disabled = true;
    counselorSelect.innerHTML = '<option value="">Loading available counselors...</option>';

    // Derive weekday from date and normalize time to 24h "HH:MM-HH:MM" range
    const dayOfWeek = getDayOfWeek(preferredDate);
    const normalizedTimeRange = normalizePreferredTimeTo24hRange(preferredTime);
    const timeBounds = extractStartEnd24h(preferredTime);

    // Build URL with query parameters (send both original and normalized for compatibility)
    const url = new URL((window.BASE_URL || '/') + 'student/get-counselors-by-availability');
    url.searchParams.append('date', preferredDate);
    url.searchParams.append('day', dayOfWeek);
    url.searchParams.append('time', normalizedTimeRange || preferredTime);
    if (timeBounds) {
        url.searchParams.append('from', timeBounds.start);
        url.searchParams.append('to', timeBounds.end);
        url.searchParams.append('timeMode', 'overlap');
    }

    fetch(url.toString(), {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('User not logged in');
                }
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Clear loading state
            counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
            
            if (data.status === 'success' && Array.isArray(data.counselors)) {
                // Add available counselors
                data.counselors.forEach(counselor => {
                    const option = document.createElement('option');
                    option.value = counselor.counselor_id;
                    option.textContent = `${counselor.name}`;
                    counselorSelect.appendChild(option);
                });

                // If no counselors are available for this date/time, show message
                if (data.counselors.length === 0) {
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "No counselors available for the selected date/time. Please choose another time.";
                    option.disabled = true;
                    counselorSelect.appendChild(option);
                }
            } else {
                throw new Error('Invalid data format received from server');
            }
        })
        .catch(error => {
            SecureLogger.info('Error loading counselors by availability:', error.message);
            counselorSelect.innerHTML = '<option value="">Error loading counselors</option>';
        })
        .finally(() => {
            counselorSelect.disabled = false;
        });
}

// Get day of week from date string
function getDayOfWeek(dateString) {
    const date = new Date(dateString);
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[date.getDay()];
}

// Setup counselor availability filtering based on date and time changes
function setupCounselorAvailabilityFiltering() {
    const preferredDateInput = document.getElementById('preferredDate');
    const preferredTimeSelect = document.getElementById('preferredTime');
    const counselorSelect = document.getElementById('counselorPreference');

    if (!preferredDateInput || !preferredTimeSelect || !counselorSelect) {
        SecureLogger.info('Required form elements not found for counselor availability filtering');
        return;
    }

    // Function to filter counselors when both date and time are selected
    function filterCounselorsByAvailability() {
        const selectedDate = preferredDateInput.value;
        const selectedTime = preferredTimeSelect.value;

        // Only filter if both date and time are selected
        if (selectedDate && selectedTime) {
            loadCounselorsByAvailability(selectedDate, selectedTime);
        } else {
            // If either date or time is not selected, load all counselors
            loadCounselors();
        }
    }

    // Add event listeners for date and time changes
    preferredDateInput.addEventListener('change', () => { refreshTimeSlotsForDate(preferredDateInput.value); filterCounselorsByAvailability(); });
    preferredTimeSelect.addEventListener('change', filterCounselorsByAvailability);

    // Initial load - if both date and time are already selected, filter immediately
    if (preferredDateInput.value) { refreshTimeSlotsForDate(preferredDateInput.value); }
    if (preferredDateInput.value && preferredTimeSelect.value) { filterCounselorsByAvailability(); }
}

// Refresh the Preferred Time <select> to show only counselor-available 30-min ranges for the selected date,
// and exclude already-booked ranges based on consultation type
async function refreshTimeSlotsForDate(dateStr){
    try {
        const select = document.getElementById('preferredTime');
        if (!select || !dateStr) return;

        // Determine weekday to derive counselor availability for that date
        const dayOfWeek = getDayOfWeek(dateStr); // e.g., 'Monday'

        // Determine currently selected counselor (if any)
        const counselorSelect = document.getElementById('counselorPreference');
        const selectedCounselorId = counselorSelect ? counselorSelect.value : '';

        // Get selected consultation type
        const consultationTypeSelect = document.getElementById('consultationType');
        const selectedConsultationType = consultationTypeSelect ? consultationTypeSelect.value : '';

        // 1) Load counselors' schedules and build the UNION of 30-min ranges for the selected day
        let availableRanges = [];
        try {
            if (selectedCounselorId && selectedCounselorId !== 'No preference') {
                // Fetch specific counselor availability
                const availRes = await fetch((window.BASE_URL || '/') + 'counselor/profile/availability?counselorId=' + encodeURIComponent(selectedCounselorId), {
                    method: 'GET', credentials: 'include', headers: { 'Accept':'application/json','Cache-Control':'no-cache' }
                });
                if (availRes.ok) {
                    const availData = await availRes.json();
                    const rows = (availData && availData.availability && availData.availability[dayOfWeek]) ? availData.availability[dayOfWeek] : [];
                    const slotStrings = Array.isArray(rows) ? rows.map(r => r && r.time_scheduled).filter(Boolean) : [];
                    availableRanges = generateHalfHourRangeUnion(slotStrings);
                }
            } else {
                // Union of all counselors for that day
                const res = await fetch((window.BASE_URL || '/') + 'student/get-counselor-schedules', {
                    method: 'GET', credentials: 'include', headers: { 'Accept':'application/json','Cache-Control':'no-cache' }
                });
                if (res.ok) {
                    const data = await res.json();
                    const schedules = data && data.schedules ? data.schedules : null;
                    const counselorsForDay = schedules && schedules[dayOfWeek] ? schedules[dayOfWeek] : [];
                    const slotStrings = [];
                    counselorsForDay.forEach(item => {
                        const ts = (item && item.time_scheduled) ? String(item.time_scheduled) : '';
                        if (ts) {
                            ts.split(',').forEach(s => { const v = s.trim(); if (v) slotStrings.push(v); });
                        }
                    });
                    availableRanges = generateHalfHourRangeUnion(slotStrings);
                }
            }
        } catch (_) {}

        // 2) Fetch booked time ranges for this date - filtered by consultation type
        const url = new URL((window.BASE_URL || '/') + 'student/appointments/booked-times');
        url.searchParams.append('date', dateStr);
        if (selectedCounselorId && selectedCounselorId !== 'No preference') {
            url.searchParams.append('counselor_id', selectedCounselorId);
        }
        // Pass consultation type to filter booked times appropriately
        if (selectedConsultationType) {
            url.searchParams.append('consultation_type', selectedConsultationType);
        }
        const res = await fetch(url.toString(), { method:'GET', credentials:'include', headers:{ 'Accept':'application/json' }});
        let booked = [];
        if (res.ok){
            const data = await res.json();
            if (data && data.status === 'success' && Array.isArray(data.booked)) booked = data.booked;
        }

        // 3) Build new options: only counselor-available ranges, minus booked ones
        const fragment = document.createDocumentFragment();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select a time slot';
        fragment.appendChild(placeholder);

        const bookedSet = new Set(booked);
        const slots = availableRanges && availableRanges.length ? availableRanges : [];
        let availableCount = 0;
        for (const s of slots){
            if (!bookedSet.has(s)){
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                fragment.appendChild(opt);
                availableCount++;
            }
        }

        // Replace options
        select.innerHTML = '';
        select.appendChild(fragment);

        // If none available, add a disabled notice
        if (availableCount === 0){
            const none = document.createElement('option');
            none.value = '';
            none.textContent = 'No available time slots for this date';
            none.disabled = true;
            select.appendChild(none);
        }

        // Trigger change in case downstream logic relies on time value
        const event = new Event('change');
        select.dispatchEvent(event);
    } catch(e){
        // Fail silently; do not block user
    }
}

// ===== Utilities to compute 30-min ranges from counselor availability =====
function parseTime12ToMinutes_student(t){
    if (!t) return null;
    const m = String(t).trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!m) return null;
    let h = parseInt(m[1],10);
    const min = parseInt(m[2],10);
    const ampm = m[3].toUpperCase();
    if (h === 12) h = 0;
    if (ampm === 'PM') h += 12;
    return h*60 + min;
}

function formatMinutesTo12h_student(total){
    let minutes = total % 60;
    let h24 = Math.floor(total/60) % 24;
    const ampm = h24 >= 12 ? 'PM' : 'AM';
    let h12 = h24 % 12; if (h12 === 0) h12 = 12;
    const mm = String(minutes).padStart(2,'0');
    return `${h12}:${mm} ${ampm}`;
}

function generateHalfHourRangeUnion(slotStrings){
    const set = new Set();
    slotStrings.forEach(s => {
        const str = String(s).trim();
        if (!str) return;
        if (str.includes('-')) {
            const parts = str.split('-');
            if (parts.length !== 2) return;
            const start = parseTime12ToMinutes_student(parts[0].trim());
            const end = parseTime12ToMinutes_student(parts[1].trim());
            if (start === null || end === null || end <= start) return;
            for (let t = start; t + 30 <= end; t += 30){
                const from = formatMinutesTo12h_student(t);
                const to = formatMinutesTo12h_student(t+30);
                set.add(`${from} - ${to}`);
            }
        }
    });
    const arr = Array.from(set);
    arr.sort((a,b) => {
        const [af] = a.split('-').map(x=>x.trim());
        const [bf] = b.split('-').map(x=>x.trim());
        return parseTime12ToMinutes_student(af) - parseTime12ToMinutes_student(bf);
    });
    return arr;
}

// ==================== Counselors' Schedules Calendar (Schedule Page) ====================
function initializeCounselorsCalendarDrawer() {
    const grid = document.getElementById('counselorsCalendarGrid');
    const monthLabel = document.getElementById('counselorsCurrentMonth');
    const prevBtn = document.getElementById('counselorsPrevMonth');
    const nextBtn = document.getElementById('counselorsNextMonth');
    if (!grid || !monthLabel) return;

    let calDate = new Date();
    let monthStatsCache = {}; // key: YYYY-MM -> stats object

    function monthName(idx){ return ['January','February','March','April','May','June','July','August','September','October','November','December'][idx]; }
    function sameDay(a,b){ return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate(); }
    function iso(date){ return date.toISOString().split('T')[0]; }

    async function fetchMonthStats(year, monthIndex){
        const key = year + '-' + String(monthIndex+1).padStart(2,'0');
        if (monthStatsCache[key]) return monthStatsCache[key];
        try {
            const url = new URL((window.BASE_URL || '/') + 'student/calendar/daily-stats');
            url.searchParams.append('year', String(year));
            url.searchParams.append('month', String(monthIndex+1));
            const res = await fetch(url.toString(), { method:'GET', credentials:'include', headers:{ 'Accept':'application/json' }});
            if (!res.ok) throw new Error('Failed to load calendar stats');
            const data = await res.json();
            if (data && data.status === 'success' && data.stats){
                monthStatsCache[key] = data.stats;
                return data.stats;
            }
        } catch(e){ /* noop: fallback to no stats */ }
        monthStatsCache[key] = {};
        return {};
    }

    async function render(){
        grid.innerHTML = '';
        monthLabel.textContent = monthName(calDate.getMonth()) + ' ' + calDate.getFullYear();

        // Fetch stats for this month
        const stats = await fetchMonthStats(calDate.getFullYear(), calDate.getMonth());

        const headers = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        headers.forEach(h => { const el=document.createElement('div'); el.className='calendar-day-header'; el.textContent=h; grid.appendChild(el); });

        const first = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
        const last = new Date(calDate.getFullYear(), calDate.getMonth()+1, 0);
        for (let i=0;i<first.getDay();i++){ const pad=document.createElement('div'); pad.className='calendar-day other-month'; grid.appendChild(pad); }

        for (let d=1; d<=last.getDate(); d++){
            const cell = document.createElement('div');
            cell.className = 'calendar-day';
            const thisDate = new Date(calDate.getFullYear(), calDate.getMonth(), d);
            const y = thisDate.getFullYear();
            const m = String(thisDate.getMonth()+1).padStart(2,'0');
            const dd = String(d).padStart(2,'0');
            const isoDate = y + '-' + m + '-' + dd;

            // Layout container
            cell.style.display = 'flex';
            cell.style.flexDirection = 'column';
            cell.style.alignItems = 'center';
            cell.style.justifyContent = 'flex-start';
            cell.style.position = 'relative'; // enable absolute overlay for badge

            // Day number label
            const dayNum = document.createElement('div');
            dayNum.textContent = String(d);
            dayNum.className = 'day-number';
            dayNum.style.marginTop = '4px';
            cell.appendChild(dayNum);

            // Apply today highlight
            if (sameDay(thisDate, new Date())) cell.classList.add('today');

            // Stats badge and fully booked label (both appear below the day number)
            const st = stats[isoDate];
            if (st && typeof st.count === 'number' && st.count > 0){
                const badge = document.createElement('span');
                badge.className = 'appt-badge';
                badge.textContent = String(st.count);
                badge.title = 'Approved appointments';
                // Overlay above the day number (top-right)
                badge.style.position = 'absolute';
                badge.style.top = '4px';
                badge.style.right = '6px';
                badge.style.minWidth = '18px';
                badge.style.height = '18px';
                badge.style.borderRadius = '9px';
                badge.style.backgroundColor = '#0d6efd';
                badge.style.color = '#fff';
                badge.style.fontSize = '11px';
                badge.style.lineHeight = '18px';
                badge.style.textAlign = 'center';
                badge.style.pointerEvents = 'none';
                cell.appendChild(badge);
            }

            if (st && st.fullyBooked === true){
                // Red highlight only when fully booked
                cell.classList.add('fully-booked');
                cell.style.backgroundColor = '#fde2e1';
                cell.style.borderColor = '#f8b4b4';
                // Add a small "Fully booked" label under the badge/number
                const fb = document.createElement('div');
                fb.textContent = 'Fully booked';
                fb.style.marginTop = '4px';
                fb.style.fontSize = '11px';
                fb.style.color = '#b91c1c';
                cell.appendChild(fb);
            }

            cell.addEventListener('click', () => openCounselorsBubbleSchedule(thisDate, cell));
            grid.appendChild(cell);
        }
    }

    async function openCounselorsBubbleSchedule(dateObj, anchorEl){
        closeBubble();
        const bubble = document.createElement('div');
        bubble.className = 'counselors-bubble';
        bubble.innerHTML = '<div class="bubble-header"><i class="fas fa-user-md me-2"></i>Available Counselors</div><div class="bubble-body">Loading...</div>';
        document.body.appendChild(bubble);
        const rect = anchorEl.getBoundingClientRect();
        const top = window.scrollY + rect.top - bubble.offsetHeight - 8;
        const left = window.scrollX + rect.left + (rect.width/2) - 160;
        bubble.style.top = Math.max(10, top) + 'px';
        bubble.style.left = Math.max(10, left) + 'px';

        const dayOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][dateObj.getDay()];
        const url = new URL((window.BASE_URL || '/') + 'student/get-counselors-by-availability');
        url.searchParams.append('date', iso(dateObj));
        url.searchParams.append('day', dayOfWeek);
        url.searchParams.append('time', '00:00-23:59');
        url.searchParams.append('from', '00:00');
        url.searchParams.append('to', '23:59');
        url.searchParams.append('timeMode', 'overlap');

        try {
            const res = await fetch(url.toString(), { method:'GET', credentials:'include', headers:{ 'Accept':'application/json' } });
            if (!res.ok) throw new Error('Availability request failed: ' + res.status);
            const data = await res.json();
            const body = bubble.querySelector('.bubble-body');
            if (data.status === 'success' && Array.isArray(data.counselors) && data.counselors.length > 0){
                const list = document.createElement('div');
                list.className = 'counselors-list';
                for (const c of data.counselors){
                    const item = document.createElement('div');
                    item.className = 'counselor-item';
                    item.innerHTML = `<div class=\"c-name\"><i class=\"fas fa-user me-2\"></i>${c.name}</div><div class=\"c-slots\">Loading slots...</div>`;
                    list.appendChild(item);
                    try {
                        const availRes = await fetch((window.BASE_URL || '/') + 'counselor/profile/availability?counselorId=' + encodeURIComponent(c.counselor_id));
                        const availData = await availRes.json();
                        const slotsDiv = item.querySelector('.c-slots');
                        const rows = (availData && availData.availability && availData.availability[dayOfWeek]) ? availData.availability[dayOfWeek] : [];
                        const slotStrings = Array.isArray(rows) ? rows.map(r => r && r.time_scheduled).filter(Boolean) : [];
                        if (slotStrings.length > 0){
                            const unique = Array.from(new Set(slotStrings));
                            const formattedSlots = formatTimeSlotsForBadges(unique);
                            slotsDiv.innerHTML = formattedSlots.map(s => `<span class=\"slot-badge\">${s}</span>`).join(' ');
                        } else {
                            slotsDiv.textContent = 'Available (no specific time slots posted)';
                        }
                    } catch(e){
                        item.querySelector('.c-slots').textContent = 'Available';
                    }
                }
                body.innerHTML = '';
                body.appendChild(list);
            } else {
                body.innerHTML = '<div class="text-muted small">No counselors available on this date.</div>';
            }
        } catch (e){
            bubble.querySelector('.bubble-body').innerHTML = '<div class="text-danger small">Failed to load counselors.</div>';
        }

        setTimeout(() => {
            function outside(ev){ if (!bubble.contains(ev.target)){ closeBubble(); document.removeEventListener('mousedown', outside); window.removeEventListener('resize', closeBubble); window.removeEventListener('scroll', closeBubble, true);} }
            document.addEventListener('mousedown', outside);
            window.addEventListener('resize', closeBubble);
            window.addEventListener('scroll', closeBubble, true);
        }, 0);
    }

    function closeBubble(){ const b=document.querySelector('.counselors-bubble'); if (b) b.remove(); }

    if (prevBtn) prevBtn.addEventListener('click', () => { calDate.setMonth(calDate.getMonth()-1); render(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { calDate.setMonth(calDate.getMonth()+1); render(); });
    render();
}

// Normalize human readable time (e.g., "8:00 AM - 9:00 AM") to 24-hour "HH:MM-HH:MM"
function normalizePreferredTimeTo24hRange(rangeStr) {
    if (!rangeStr || typeof rangeStr !== 'string') return null;

    // Example inputs:
    // "8:00 AM - 9:00 AM"
    // "1:00 PM - 2:00 PM"
    const parts = rangeStr.split('-').map(function (p) { return p.trim(); });
    if (parts.length !== 2) return null;

    function to24h(t) {
        // t like "8:00 AM" or "12:30 PM"
        const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return null;
        let hour = parseInt(match[1], 10);
        const minute = match[2];
        const ampm = match[3].toUpperCase();

        if (ampm === 'AM') {
            if (hour === 12) hour = 0;
        } else {
            if (hour !== 12) hour += 12;
        }
        return String(hour).padStart(2, '0') + ':' + minute;
    }

    const start = to24h(parts[0]);
    const end = to24h(parts[1]);
    if (!start || !end) return null;
    return start + '-' + end;
}

// Extract start and end in 24-hour format from human-friendly range
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
        if (ampm === 'AM') {
            if (hour === 12) hour = 0;
        } else {
            if (hour !== 12) hour += 12;
        }
        return String(hour).padStart(2, '0') + ':' + minute;
    }

    const start = to24h(parts[0]);
    const end = to24h(parts[1]);
    if (!start || !end) return null;
    return { start: start, end: end };
}

// Setup form submission handling
function setupFormSubmission() {
    const appointmentForm = document.getElementById('consultationForm');
    const scheduleBtn = document.getElementById('scheduleAppointmentBtn');
    const submitText = document.getElementById('submitText');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const formMessage = document.getElementById('formMessage');

    if (appointmentForm && scheduleBtn) {
        // Ensure button starts in normal state (not loading)
        initializeButtonState();

        appointmentForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form
            if (!validateForm()) {
                return;
            }

            // Check for counselor conflicts and group slot availability before submission
            const consultationType = document.getElementById('consultationType').value;
            const date = document.getElementById('preferredDate').value;
            const time = document.getElementById('preferredTime').value;
            
            // For group consultation, check slot availability first
            if (consultationType === 'Group Consultation') {
                const counselorId = document.getElementById('counselorPreference').value;
                checkGroupSlotAvailability(date, time, counselorId).then(availabilityInfo => {
                    if (!availabilityInfo || !availabilityInfo.isAvailable) {
                        showMessage('error', 'Group consultation slots are full for this time slot (maximum 5 participants). Please select a different time or date.');
                        toggleLoadingState(false);
                        return;
                    }
                    proceedWithSubmission();
                }).catch(error => {
                    console.error('Error checking group slots:', error);
                    showMessage('error', 'Error checking group slot availability. Please try again.');
                    toggleLoadingState(false);
                });
            } else {
                // For individual consultation, use existing conflict check
                checkCounselorConflicts().then(hasConflict => {
                    if (hasConflict) {
                        toggleLoadingState(false);
                        return; // Conflict modal will be shown, don't proceed with submission
                    }
                    proceedWithSubmission();
                }).catch(error => {
                    console.error('Error checking conflicts:', error);
                    showMessage('error', 'Error checking counselor availability. Please try again.');
                    toggleLoadingState(false);
                });
            }
            
            function proceedWithSubmission() {
                // Show loading state
                toggleLoadingState(true);

                // Create form data object
                const formData = new FormData(appointmentForm);

                // Send AJAX request
                fetch((window.BASE_URL || '/') + 'student/appointment/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(formData)
            })
            .then(response => {
                // If response is not ok, convert it to JSON anyway to get the error message
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Show confirmation popup immediately
                    showAppointmentConfirmation();

                    // Reset form
                    appointmentForm.reset();
                    setMinimumAppointmentDate(); // Reset the date input to tomorrow

                    // Show success message in the form
                    showMessage('success', data.message);

                    setTimeout(() => {
                        window.location.href = (window.BASE_URL || '/') + 'student/dashboard';
                    }, 1500);
                } else {
                    showMessage('error', data.message || 'An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('error', error.message || 'A server error occurred. Please try again later.');
            })
            .finally(() => {
                // Always hide loading state, regardless of success or error
                toggleLoadingState(false);
            });
            }
        });
    }
}

// Show appointment confirmation popup
function showAppointmentConfirmation() {
    // Remove any existing overlay first
    const existingOverlay = document.querySelector('.confirmation-overlay');
    if (existingOverlay) {
        document.body.removeChild(existingOverlay);
    }

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'confirmation-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';

    // Create popup
    const popup = document.createElement('div');
    popup.className = 'bg-white rounded-lg p-8 max-w-md text-center relative';
    popup.style.width = '320px';
    popup.style.borderRadius = '8px';
    popup.style.padding = '24px';

    // Popup content
    popup.innerHTML = `
        <div class="mb-4">
            <div class="text-green-500 mb-4">
                <i class="fas fa-check-circle text-4xl"></i>
            </div>
            <h3 class="text-xl font-semibold mb-3">Booking Successful!</h3>
            <p class="text-gray-800 mb-4" style="font-size: 14px; line-height: 1.5;">Your booking entry has been passed to the Admin. Please wait for confirmation before proceeding. Thank you for your patience!</p>
            <button id="confirmOkBtn" class="px-6 py-2 bg-cyan-400 text-black rounded hover:bg-cyan-500 transition" style="background-color: #22d3ee; color: black; padding: 8px 24px; border-radius: 4px; border: none; font-weight: normal; cursor: pointer;">Close</button>
        </div>
    `;

    // Add popup to overlay
    overlay.appendChild(popup);

    // Add overlay to document body
    document.body.appendChild(overlay);

    // Add event listener to OK button
    const okButton = document.getElementById('confirmOkBtn');
    if (okButton) {
        okButton.addEventListener('click', function () {
            // Remove popup
            document.body.removeChild(overlay);
        });
    }

    // Prevent clicking outside from closing the popup
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            e.stopPropagation();
        }
    });
}

// Validate the form
function validateForm() {
    const preferredDate = document.getElementById('preferredDate').value;
    const preferredTime = document.getElementById('preferredTime').value;
    const consultationType = document.getElementById('consultationType').value;
    const methodType = document.getElementById('methodType').value;
    const purpose = document.getElementById('purpose').value;

    if (!preferredDate) {
        showMessage('error', 'Please select a preferred date.');
        return false;
    }

    if (!preferredTime) {
        showMessage('error', 'Please select a preferred time.');
        return false;
    }

    if (!consultationType) {
        showMessage('error', 'Please select a consultation type.');
        return false;
    }

    if (!methodType) {
        showMessage('error', 'Please select a method type.');
        return false;
    }

    if (!purpose) {
        showMessage('error', 'Please select the purpose of your consultation.');
        return false;
    }

    // Check if date is in the future
    const selectedDate = new Date(preferredDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate <= today) {
        showMessage('error', 'Please select a future date for your appointment.');
        return false;
    }

    // Validate acknowledgment checkboxes
    const consentRead = document.getElementById('consentRead');
    const consentAccept = document.getElementById('consentAccept');
    const acknowledgmentError = document.getElementById('acknowledgmentError');

    if (!consentRead || !consentAccept) {
        console.error('Acknowledgment checkboxes not found');
        return false;
    }

    if (!consentRead.checked || !consentAccept.checked) {
        // Show acknowledgment error message
        if (acknowledgmentError) {
            acknowledgmentError.classList.remove('hidden');
        }
        showMessage('error', 'Please acknowledge both consent statements to proceed with your appointment booking.');
        return false;
    } else {
        // Hide acknowledgment error message if both are checked
        if (acknowledgmentError) {
            acknowledgmentError.classList.add('hidden');
        }
    }

    return true;
}

// Show message to the user
function showMessage(type, message) {
    const formMessage = document.getElementById('formMessage');

    if (formMessage) {
        formMessage.textContent = message;
        formMessage.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');

        if (type === 'error') {
            formMessage.classList.add('bg-red-100', 'text-red-800');
        } else {
            formMessage.classList.add('bg-green-100', 'text-green-800');
        }

        // Scroll to message
        formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Initialize button state to ensure it starts in normal state
function initializeButtonState() {
    const submitText = document.getElementById('submitText');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const scheduleBtn = document.getElementById('scheduleAppointmentBtn');

    if (submitText && loadingIndicator && scheduleBtn) {
        // Force normal state: show submit text, hide loading indicator, enable button
        submitText.classList.remove('hidden');
        loadingIndicator.classList.add('hidden');
        scheduleBtn.disabled = false;
    }
}

// Toggle loading state of the submit button
function toggleLoadingState(isLoading) {
    const submitText = document.getElementById('submitText');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const scheduleBtn = document.getElementById('scheduleAppointmentBtn');

    if (submitText && loadingIndicator && scheduleBtn) {
        if (isLoading) {
            submitText.classList.add('hidden');
            loadingIndicator.classList.remove('hidden');
            scheduleBtn.disabled = true;
        } else {
            submitText.classList.remove('hidden');
            loadingIndicator.classList.add('hidden');
            scheduleBtn.disabled = false;
        }
    }
}

// Check for message in URL parameters
function checkForUrlMessage() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const type = urlParams.get('type') || 'error';

    if (message) {
        showMessage(type, decodeURIComponent(message));
    }
}

// Helper function to scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Check for counselor conflicts before scheduling appointment
async function checkCounselorConflicts() {
    try {
        const counselorId = document.getElementById('counselorPreference').value;
        const date = document.getElementById('preferredDate').value;
        const time = document.getElementById('preferredTime').value;

        if (!counselorId || counselorId === 'No preference' || !date || !time) {
            return false; // No specific counselor selected, no conflict check needed
        }

        const response = await fetch((window.BASE_URL || '/') + 'student/check-counselor-conflicts?' + 
            new URLSearchParams({
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
            showConflictModal(data.message, data.conflictType);
            return true; // Has conflict
        }

        return false; // No conflict
    } catch (error) {
        console.error('Error checking counselor conflicts:', error);
        showMessage('error', 'Error checking counselor availability. Please try again.');
        return false;
    }
}

// Show conflict modal
function showConflictModal(message, conflictType) {
    // Remove any existing conflict modal
    const existingModal = document.querySelector('.conflict-modal');
    if (existingModal) {
        document.body.removeChild(existingModal);
    }

    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'conflict-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center';
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
            <button id="conflictModalOk" type="button" style="
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
    const okButton = document.getElementById('conflictModalOk');
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
        if (e.key === 'Escape' && document.querySelector('.conflict-modal')) {
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

// Setup consultation type handling
function setupConsultationTypeHandling() {
    const consultationTypeSelect = document.getElementById('consultationType');
    const consultationTypeHelp = document.getElementById('consultationTypeHelp');
    const preferredDateInput = document.getElementById('preferredDate');
    const preferredTimeSelect = document.getElementById('preferredTime');
    const counselorSelect = document.getElementById('counselorPreference');

    if (!consultationTypeSelect || !consultationTypeHelp) return;

    function updateHelpText() {
        const selectedType = consultationTypeSelect.value;
        if (selectedType === 'Group Consultation') {
            consultationTypeHelp.textContent = 'Group consultation allows up to 5 students per time slot.';
            consultationTypeHelp.style.color = '#2563EB';
            // Check slot availability when date/time changes for group consultation
            checkGroupSlotAvailabilityForCurrentSelection();
        } else if (selectedType === 'Individual Consultation') {
            consultationTypeHelp.textContent = 'One-on-one consultation with the counselor.';
            consultationTypeHelp.style.color = '#6B7280';
        } else {
            consultationTypeHelp.textContent = '';
        }
    }

    function checkGroupSlotAvailabilityForCurrentSelection() {
        const date = preferredDateInput.value;
        const time = preferredTimeSelect.value;
        const counselorId = counselorSelect.value;

        if (consultationTypeSelect.value === 'Group Consultation' && date && time) {
            checkGroupSlotAvailability(date, time, counselorId).then(hasSlots => {
                if (hasSlots !== null) {
                    updateSlotAvailabilityDisplay(hasSlots);
                }
            });
        } else {
            clearSlotAvailabilityDisplay();
        }
    }

    consultationTypeSelect.addEventListener('change', () => {
        updateHelpText();
        // Refresh time slots when consultation type changes (different logic for individual vs group)
        const selectedDate = preferredDateInput ? preferredDateInput.value : '';
        if (selectedDate) {
            refreshTimeSlotsForDate(selectedDate);
        }
        checkGroupSlotAvailabilityForCurrentSelection();
    });

    if (preferredDateInput) {
        preferredDateInput.addEventListener('change', checkGroupSlotAvailabilityForCurrentSelection);
    }
    if (preferredTimeSelect) {
        preferredTimeSelect.addEventListener('change', checkGroupSlotAvailabilityForCurrentSelection);
    }
    if (counselorSelect) {
        counselorSelect.addEventListener('change', checkGroupSlotAvailabilityForCurrentSelection);
    }

    // Initial help text update
    updateHelpText();
}

// Check group slot availability
async function checkGroupSlotAvailability(date, time, counselorId = '') {
    try {
        const url = new URL((window.BASE_URL || '/') + 'student/appointments/check-group-slots');
        url.searchParams.append('date', date);
        url.searchParams.append('time', time);
        if (counselorId && counselorId !== 'No preference') {
            url.searchParams.append('counselor_id', counselorId);
        }

        const response = await fetch(url.toString(), {
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
            return {
                isAvailable: data.isAvailable,
                bookedSlots: data.bookedSlots,
                availableSlots: data.availableSlots
            };
        }

        return null;
    } catch (error) {
        SecureLogger.info('Error checking group slot availability:', error.message);
        return null;
    }
}

// Update slot availability display
function updateSlotAvailabilityDisplay(availabilityInfo) {
    const consultationTypeHelp = document.getElementById('consultationTypeHelp');
    if (!consultationTypeHelp || !availabilityInfo) return;

    if (availabilityInfo.availableSlots > 0) {
        consultationTypeHelp.innerHTML = `Group consultation: <strong>${availabilityInfo.availableSlots}</strong> slot(s) available (${availabilityInfo.bookedSlots}/5 booked).`;
        consultationTypeHelp.style.color = '#059669';
    } else {
        consultationTypeHelp.innerHTML = `Group consultation: <strong>No slots available</strong> (${availabilityInfo.bookedSlots}/5 booked). Please choose another time.`;
        consultationTypeHelp.style.color = '#DC2626';
    }
}

// Clear slot availability display
function clearSlotAvailabilityDisplay() {
    const consultationTypeHelp = document.getElementById('consultationTypeHelp');
    if (!consultationTypeHelp) return;

    const consultationTypeSelect = document.getElementById('consultationType');
    if (consultationTypeSelect && consultationTypeSelect.value === 'Group Consultation') {
        consultationTypeHelp.textContent = 'Group consultation allows up to 5 students per time slot.';
        consultationTypeHelp.style.color = '#2563EB';
    } else if (consultationTypeSelect && consultationTypeSelect.value === 'Individual Consultation') {
        consultationTypeHelp.textContent = 'One-on-one consultation with the counselor.';
        consultationTypeHelp.style.color = '#6B7280';
    } else {
        consultationTypeHelp.textContent = '';
    }
}

// Setup acknowledgment validation
function setupAcknowledgmentValidation() {
    const consentRead = document.getElementById('consentRead');
    const consentAccept = document.getElementById('consentAccept');
    const acknowledgmentError = document.getElementById('acknowledgmentError');

    if (!consentRead || !consentAccept || !acknowledgmentError) {
        console.error('Acknowledgment elements not found');
        return;
    }

    // Function to check both checkboxes and hide/show error message
    function checkAcknowledgmentStatus() {
        if (consentRead.checked && consentAccept.checked) {
            acknowledgmentError.classList.add('hidden');
        } else {
            acknowledgmentError.classList.remove('hidden');
        }
    }

    // Add event listeners to both checkboxes
    consentRead.addEventListener('change', checkAcknowledgmentStatus);
    consentAccept.addEventListener('change', checkAcknowledgmentStatus);

    // Initial check
    checkAcknowledgmentStatus();
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
