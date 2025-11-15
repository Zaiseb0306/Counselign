// Resolve image URL: if already absolute or starts with '/', return as-is; else prepend BASE_URL
function resolveImageUrl(path) {
    if (!path) return (window.BASE_URL || '/') + 'Photos/profile.png';
    const trimmed = String(path).trim();
    if (/^https?:\/\//i.test(trimmed)) return trimmed;
    if (trimmed.startsWith('/')) return trimmed; 
    return (window.BASE_URL || '/') + trimmed;
}

// Function to change profile picture
let images = ["default.jpg", "image2.jpg", "image3.jpg"];
let currentIndex = 0;

function changeImage() {
    currentIndex = (currentIndex + 1) % images.length;
    document.getElementById("profile-img").src = images[currentIndex];
}

// Function to handle logout action
function handleLogout() {
    if (typeof window.confirmLogout === "function") {
        window.confirmLogout();
    } else {
        // Fallback (should rarely occur)
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = (window.BASE_URL || "/") + "auth/logout";
        }
    }
}

// Function to validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Function to save profile changes
function saveProfileChanges() {
    // Get the values from the modal inputs
    const newUsername = document.getElementById('update-username').value.trim();
    const newEmail = document.getElementById('update-email').value.trim();

    SecureLogger.info('Saving profile changes:', { newUsername, newEmail });

    // Validate inputs
    if (!newUsername) {
        openAlertModal('Please enter a username', 'warning');
        return;
    }

    if (!newEmail) {
        openAlertModal('Please enter an email address', 'warning');
        return;
    }

    if (!validateEmail(newEmail)) {
        openAlertModal('Please enter a valid email address', 'warning');
        return;
    }

    // Create a FormData object to send the data
    const formData = new FormData();
    formData.append('username', newUsername);
    formData.append('email', newEmail);

    // First update text fields
    fetch(window.BASE_URL + 'counselor/profile/update', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(async data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to update profile');
        }

        // If there is a selected picture, upload it next
        const fileInput = document.getElementById('update-picture');
        const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (file) {
            const picForm = new FormData();
            picForm.append('profile_picture', file);
            const resp = await fetch(window.BASE_URL + 'counselor/profile/picture', {
                method: 'POST',
                body: picForm,
                credentials: 'include'
            });
            const picData = await resp.json();
            if (!picData.success) {
                throw new Error(picData.message || 'Failed to upload picture');
            }
            // Update on-page avatar
            const imgEl = document.getElementById('profile-img');
            if (imgEl && picData.picture_url) {
                const newUrl = resolveImageUrl(picData.picture_url) + '?t=' + Date.now();
                imgEl.src = newUrl;
                try { localStorage.setItem('counselor_profile_picture', newUrl); } catch (e) {}
            }
        }

        // Update the display values
        document.getElementById('display-username').value = newUsername;
        document.getElementById('display-email').value = newEmail;

        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('updateProfileModal'));
        modal.hide();

        // Show success message
        openAlertModal('Profile updated successfully!', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        openAlertModal(error.message || 'Failed to update profile. Please try again later.', 'error');
    });
}

// Function to load current profile data
function loadProfileData() {
    SecureLogger.info('Loading profile data...');
    
    // Show loading state
    document.getElementById('display-username').value = 'Loading...';
    document.getElementById('display-email').value = 'Loading...';

    fetch(window.BASE_URL + 'counselor/profile/get', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        SecureLogger.info('Response status:', response.status);
        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                // Session expired or unauthorized
                window.location.href = window.BASE_URL + 'counselor/dashboard';
                return;
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        SecureLogger.info('Profile data:', data);
        if (data.success) {
            // Update display values
            document.getElementById('display-userid').textContent = data.user_id || 'N/A';
            document.getElementById('display-username').value = data.username || 'N/A';
            document.getElementById('display-email').value = data.email || 'N/A';

            // Set current profile picture
            const imgEl = document.getElementById('profile-img');
            const previewEl = document.getElementById('update-picture-preview');
            const src = resolveImageUrl(data.profile_picture);
            if (imgEl) imgEl.src = src;
            if (previewEl) {
                previewEl.src = src;
                previewEl.style.display = 'block';
            }

            // Update modal input values
            document.getElementById('update-username').value = data.username || '';
            document.getElementById('update-email').value = data.email || '';

            // Populate Personal Information section with default values for first-time users
            const c = (data && data.counselor) ? data.counselor : null;
            const setVal = (id, v, defaultValue = 'N/A') => { const el = document.getElementById(id); if (el) el.value = v || defaultValue; };
            const setText = (id, v, defaultValue = 'N/A') => { const el = document.getElementById(id); if (el) el.textContent = v || defaultValue; };
            const setValInput = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
            setValInput('pi-counselor-id-input', data.user_id || '');
            setVal('pi-fullname', c ? c.name : '', 'N/A');
            setVal('pi-birthdate', c ? (c.birthdate || '') : '', '');
            setVal('pi-address', c ? c.address : '', 'N/A');
            setVal('pi-degree', c ? c.degree : '', 'N/A');
            setVal('pi-email', c ? c.email : data.email || '', 'N/A');
            // Note: specialization field might not exist in all database versions
            const specializationDisplayEl = document.getElementById('pi-specialization');
            if (specializationDisplayEl) {
                setVal('pi-specialization', c ? c.specialization : '', 'N/A');
            }
            setVal('pi-contact', c ? c.contact_number : '', 'N/A');
            setVal('pi-sex', c ? c.sex : '', '');
            setVal('pi-civil', c ? c.civil_status : '', '');

            // Initialize modal fields with default values for first-time users
            const setModal = (id, v, defaultValue = 'N/A') => { 
                const el = document.getElementById(id); 
                if (el) el.value = v || defaultValue; 
            };
            setModal('upi-fullname', c ? c.name : '', 'N/A');
            setModal('upi-birthdate', c ? (c.birthdate || '') : '', '');
            setModal('upi-address', c ? c.address : '', 'N/A');
            setModal('upi-degree', c ? c.degree : '', 'N/A');
            setModal('upi-email', c ? c.email : data.email || '', 'N/A');
            // Note: specialization field might not exist in all database versions
            const specializationEl = document.getElementById('upi-specialization');
            if (specializationEl) {
                setModal('upi-specialization', c ? c.specialization : '', 'N/A');
            }
            setModal('upi-contact', c ? c.contact_number : '', 'N/A');
            setModal('upi-sex', c ? c.sex : '', '');
            setModal('upi-civil', c ? c.civil_status : '', '');
        } else {
            throw new Error(data.message || 'Failed to load profile data');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error state in the UI
        document.getElementById('display-username').value = 'Error loading data';
        document.getElementById('display-email').value = 'Error loading data';
        
        if (error.message === 'User not logged in') {
            setTimeout(() => {
                window.location.href = window.BASE_URL + 'counselor/dashboard';
            }, 1500);
        } else {
            openAlertModal('Failed to load profile data. Please try again later.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    SecureLogger.info("DOM loaded, setting up profile functionality");
    
    // Load profile data when page loads
    loadProfileData();

    // Initialize availability UI
    initAvailabilityUi();

    // Sync right profile container height to left
    function syncRightHeight() {
        const left = document.querySelector('.col-lg-5 .profile-container');
        const rightDetails = document.querySelector('.col-lg-7 .profile-container .scroll-inner');
        const right = document.querySelector('.col-lg-7 .profile-container');
        if (!left || !right || !rightDetails) return;
        const leftRect = left.getBoundingClientRect();
        // Set right container height equal to left (minus border differences)
        right.style.minHeight = leftRect.height + 'px';
        // Ensure inner scroll area fits within right container
        const header = right.querySelector('.profile-header');
        const details = right.querySelector('.profile-details');
        // Only adjust max-height for inner scroll to avoid horizontal scroll
        const paddingTop = 0;
        const desired = leftRect.height - paddingTop;
        rightDetails.style.maxHeight = Math.max(200, desired - 0) + 'px';
    }

    syncRightHeight();
    window.addEventListener('resize', () => { syncRightHeight(); });
    // Re-sync after images load (e.g., profile picture)
    const img = document.getElementById('profile-img');
    if (img) { img.addEventListener('load', syncRightHeight); }
    
    // Get the logout button
    const logoutBtn = document.querySelector('.btn-logout');

    // Add click event listener to logout button
    if (logoutBtn) {
        SecureLogger.info("Logout button found, adding event listener");
        logoutBtn.addEventListener('click', handleLogout);
    } else {
        SecureLogger.info("Logout button not found!");
    }
    
    // Drawer toggle bindings (match landing behavior)
    const navbarDrawerToggler = document.getElementById('navbarDrawerToggler');
    const navbarDrawer = document.getElementById('navbarDrawer');
    const navbarDrawerClose = document.getElementById('navbarDrawerClose');
    const navbarOverlay = document.getElementById('navbarOverlay');

    function openDrawer() {
        if (navbarDrawer && navbarOverlay) {
            navbarDrawer.classList.add('show');
            navbarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (navbarDrawer && navbarOverlay) {
            navbarDrawer.classList.remove('show');
            navbarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (navbarDrawerToggler) navbarDrawerToggler.addEventListener('click', openDrawer);
    if (navbarDrawerClose) navbarDrawerClose.addEventListener('click', closeDrawer);
    if (navbarOverlay) navbarOverlay.addEventListener('click', closeDrawer);

    // Logout from drawer
    const logoutFromDrawer = document.getElementById('logoutFromDrawer');
    if (logoutFromDrawer) {
        logoutFromDrawer.addEventListener('click', function() {
            closeDrawer();
            setTimeout(handleLogout, 200);
        });
    }
});

// Function to toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    // The DOM structure is: <div class="password-input-group"><input><i class="toggle-password"></i></div>
    // So the correct container is the parent element, not nextElementSibling.
    const container = input ? input.parentElement : null;

    if (!input || !container) return;

    // Find whatever element is currently acting as the toggle (either <i> or <img>)
    let toggleEl = container.querySelector('.toggle-password');

    if (input.type === 'password') {
        // We are about to SHOW the password. Keep the "hide" icon as Photos/eye.png
        input.type = 'text';

        // If it's already an <img>, just swap src; otherwise replace the <i> with <img>
        if (toggleEl && toggleEl.tagName.toLowerCase() === 'img') {
            toggleEl.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
            toggleEl.alt = 'Hide password';
        } else if (toggleEl) {
            const img = document.createElement('img');
            img.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
            img.alt = 'Hide password';
            img.className = 'toggle-password custom-hide-icon';
            img.style.width = '30px';
            img.style.height = '30px';
            img.style.cursor = 'pointer';
            img.onclick = () => togglePassword(inputId);
            toggleEl.replaceWith(img);
        }
    } else {
        // We are about to HIDE the password. Restore the Font Awesome eye (show icon)
        input.type = 'password';

        if (toggleEl && toggleEl.tagName.toLowerCase() === 'img') {
            const icon = document.createElement('i');
            icon.className = 'fas fa-eye toggle-password';
            icon.style.cursor = 'pointer';
            icon.onclick = () => togglePassword(inputId);
            toggleEl.replaceWith(icon);
        } else if (toggleEl) {
            // Ensure proper classes if an <i> already exists
            toggleEl.classList.remove('fa-eye-slash');
            toggleEl.classList.add('fa-eye');
        }
    }
}

// Preview selected profile picture in modal
document.addEventListener('change', function(e) {
    const target = e.target;
    if (target && target.id === 'update-picture' && target.files && target.files[0]) {
        const file = target.files[0];
        const reader = new FileReader();
        reader.onload = function(ev) {
            const preview = document.getElementById('update-picture-preview');
            if (preview) {
                preview.src = ev.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
});

// Function to change password
function changePassword() {
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    // Validate inputs
    if (!currentPassword || !newPassword || !confirmPassword) {
        openAlertModal('Please fill in all password fields', 'warning');
        return;
    }

    if (newPassword !== confirmPassword) {
        openAlertModal('New passwords do not match', 'warning');
        return;
    }

    if (newPassword.length < 8) {
        openAlertModal('New password must be at least 8 characters long', 'warning');
        return;
    }

    // Create FormData object
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    // Send request to server
    fetch(window.BASE_URL + 'update-password', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success logic
            openAlertModal('Password updated successfully!', 'success');
            document.getElementById('changePasswordForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
        } else {
            openAlertModal(data.message || 'Failed to update password', 'error');
        }
    })
    .catch(error => {
        openAlertModal('Failed to update password. Please try again later.', 'error');
    });
}

// Save Personal Info changes
function savePersonalInfoChanges() {
    const form = new FormData();
    // Get values and use 'N/A' as default for empty fields (except birthdate and select fields)
    const getValueOrDefault = (fieldId, defaultValue = 'N/A') => {
        const value = document.getElementById(fieldId)?.value?.trim() || '';
        return value === '' ? defaultValue : value;
    };
    
    form.append('fullname', getValueOrDefault('upi-fullname'));
    form.append('birthdate', getValueOrDefault('upi-birthdate', ''));
    form.append('address', getValueOrDefault('upi-address'));
    form.append('degree', getValueOrDefault('upi-degree'));
    form.append('email', getValueOrDefault('upi-email'));
    form.append('contact', getValueOrDefault('upi-contact'));
    form.append('sex', getValueOrDefault('upi-sex', ''));
    form.append('civil_status', getValueOrDefault('upi-civil', ''));

    fetch((window.BASE_URL || '/') + 'counselor/profile/counselor-info', {
        method: 'POST',
        body: form,
        credentials: 'include'
    })
    .then(r => r.json())
    .then(d => {
        if (!d || !d.success) throw new Error(d?.message || 'Failed to save');
        // Refresh display from server to reflect saved data
        return fetch((window.BASE_URL || '/') + 'counselor/profile/get', { credentials: 'include' });
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error('Failed to reload');

        const c = data.counselor || {};
        const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || '—'; };
        setText('pi-counselor-id', data.user_id || '—');
        setVal('pi-fullname', c.name);
        setVal('pi-birthdate', c.birthdate);
        setVal('pi-address', c.address);
        setVal('pi-degree', c.degree);
        setVal('pi-email', c.email || data.email);
        setVal('pi-contact', c.contact_number);
        setVal('pi-sex', c.sex);
        setVal('pi-civil', c.civil_status);

        const modal = bootstrap.Modal.getInstance(document.getElementById('updatePersonalInfoModal'));
        if (modal) modal.hide();
        openAlertModal('Personal information updated successfully!', 'success');
    })
    .catch(err => {
        openAlertModal(err.message || 'Failed to update personal information.', 'error');
    });
}

// ===== Availability Management =====
function initAvailabilityUi() {
    // Populate time selects with 30-min intervals from 07:00 AM to 05:30 PM in 12-hour format
    // Exclude 12:00 PM and 12:30 PM from the time options
    const timeFrom = document.getElementById('time-from');
    const timeTo = document.getElementById('time-to');
    if (timeFrom && timeTo) {
        const options = [];
        for (let h = 7; h <= 17; h++) {
            for (let m = 0; m < 60; m += 30) {
                const h24 = h;
                const m24 = m;
                const ampm = h24 >= 12 ? 'PM' : 'AM';
                const hour12 = ((h24 + 11) % 12) + 1; // 0->12, 13->1
                const val = `${hour12}:${String(m24).padStart(2,'0')} ${ampm}`;
                
                // Skip 12:00 PM and 12:30 PM times
                if (val !== '12:00 PM' && val !== '12:30 PM') {
                    options.push(val);
                }
            }
        }
        options.forEach(v => {
            const o1 = document.createElement('option'); o1.value = v; o1.textContent = v; timeFrom.appendChild(o1);
            const o2 = document.createElement('option'); o2.value = v; o2.textContent = v; timeTo.appendChild(o2);
        });
    }

    const addBtn = document.getElementById('add-time-slot');
    const daysContainer = document.getElementById('availability-days');
    if (addBtn && daysContainer) {
        addBtn.addEventListener('click', () => {
            const from = document.getElementById('time-from')?.value;
            const to = document.getElementById('time-to')?.value;
            if (!from || !to) { openAlertModal('Select both From and To time.', 'warning'); return; }
            // Use timeToMinutes() for proper time comparison instead of string comparison
            if (timeToMinutes(from) >= timeToMinutes(to)) { openAlertModal('From must be earlier than To.', 'warning'); return; }
            const days = getSelectedDays();
            if (days.length === 0) { openAlertModal('Select at least one day.', 'warning'); return; }
            days.forEach(day => addRangeForDay(day, { from, to }));
            renderAvailabilityChips();
        });
    }

    const saveBtn = document.getElementById('save-availability');
    if (saveBtn) { saveBtn.addEventListener('click', () => { if (availabilityEditMode) { saveAvailability(); } }); }

    const editBtn = document.getElementById('edit-availability');
    if (editBtn) { editBtn.addEventListener('click', enterAvailabilityEditMode); }
    const cancelBtn = document.getElementById('cancel-availability');
    if (cancelBtn) { cancelBtn.addEventListener('click', () => { loadAvailabilityFromServer(); setAvailabilityDisplayMode(true); }); }

    // initialize display mode by default
    setAvailabilityDisplayMode(true);

    // Initial load
    loadAvailabilityFromServer();
}

// ----- Availability state and helpers -----
const DAYS_ORDER = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
let availabilityState = { rangesByDay: { Monday: [], Tuesday: [], Wednesday: [], Thursday: [], Friday: [] } };
let availabilityEditMode = false;

// Convert 12-hour format time to minutes for comparison
function timeToMinutes(t) { 
    if (!t) return 0;
    // Handle 12-hour format: "1:30 PM" or "12:00 AM"
    const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return 0;
    
    let hours = parseInt(match[1]);
    const minutes = parseInt(match[2]);
    const ampm = match[3].toUpperCase();
    
    // Convert to 24-hour format for calculation
    if (ampm === 'PM' && hours !== 12) {
        hours += 12;
    } else if (ampm === 'AM' && hours === 12) {
        hours = 0;
    }
    
    return hours * 60 + minutes;
}

// Convert minutes back to 12-hour format
function minutesToTime(m) { 
    const hours = Math.floor(m / 60);
    const minutes = m % 60;
    
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = ((hours + 11) % 12) + 1; // 0->12, 13->1
    
    return `${hour12}:${String(minutes).padStart(2,'0')} ${ampm}`;
}

function normalizeRange(range) {
    const fromM = timeToMinutes(range.from);
    const toM = timeToMinutes(range.to);
    return fromM < toM ? { from: minutesToTime(fromM), to: minutesToTime(toM) } : null;
}

function mergeRanges(ranges) {
    if (!ranges.length) return [];
    const sorted = ranges.slice().sort((a,b) => timeToMinutes(a.from) - timeToMinutes(b.from));
    const merged = [sorted[0]];
    for (let i = 1; i < sorted.length; i++) {
        const prev = merged[merged.length - 1];
        const cur = sorted[i];
        if (timeToMinutes(cur.from) <= timeToMinutes(prev.to)) {
            if (timeToMinutes(cur.to) > timeToMinutes(prev.to)) prev.to = cur.to;
        } else {
            merged.push({ ...cur });
        }
    }
    return merged;
}

function addRangeForDay(day, range) {
    const norm = normalizeRange(range);
    if (!norm) return;
    const arr = availabilityState.rangesByDay[day] || [];
    arr.push(norm);
    availabilityState.rangesByDay[day] = mergeRanges(arr);
}

function removeRangeForDay(day, index) {
    const arr = availabilityState.rangesByDay[day] || [];
    if (index >= 0 && index < arr.length) {
        arr.splice(index, 1);
        availabilityState.rangesByDay[day] = arr;
    }
}

function expandRangesToTimes(ranges) {
    const times = [];
    ranges.forEach(r => {
        for (let t = timeToMinutes(r.from); t < timeToMinutes(r.to); t += 30) {
            times.push(minutesToTime(t));
        }
    });
    return Array.from(new Set(times));
}

function compactTimesToRanges(times) {
    const t = (times || []).slice().sort();
    const ranges = [];
    let i = 0;
    while (i < t.length) {
        const start = t[i];
        let prev = start; i++;
        while (i < t.length) {
            const cur = t[i];
            if (timeToMinutes(cur) - timeToMinutes(prev) === 30) { prev = cur; i++; } else { break; }
        }
        const end = minutesToTime(timeToMinutes(prev) + 30);
        ranges.push({ from: start, to: end });
    }
    return ranges;
}

function renderAvailabilityChips() {
    const host = document.getElementById('time-slots-list');
    if (!host) return;
    host.innerHTML = '';
    DAYS_ORDER.forEach(day => {
        const ranges = availabilityState.rangesByDay[day] || [];
        if (!ranges.length) return;
        const section = document.createElement('div');
        section.className = 'day-section';
        const title = document.createElement('div');
        title.className = 'day-section-title';
        title.textContent = day;
        section.appendChild(title);
        const wrap = document.createElement('div');
        ranges.forEach((r, idx) => {
            const chip = document.createElement('span');
            chip.className = 'slot-chip';
            // Display time range in 12-hour format (already in correct format)
            const timeRange = `${r.from} - ${r.to}`;
            chip.textContent = timeRange;
            if (availabilityEditMode) {
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'chip-remove';
                rm.textContent = '×';
                rm.onclick = () => {
                    // Optimistic removal in UI, then delete on server
                    const removed = { from: r.from, to: r.to };
                    removeRangeForDay(day, idx);
                    renderAvailabilityChips();
                    fetch((window.BASE_URL || '/') + 'counselor/profile/availability', {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ day: day, from: removed.from, to: removed.to })
                    })
                    .then(res => res.json())
                    .then(d => {
                        if (!d || !d.success) {
                            openAlertModal((d && d.message) || 'Failed to delete slot', 'error');
                            loadAvailabilityFromServer();
                        }
                    })
                    .catch(() => {
                        openAlertModal('Failed to delete slot', 'error');
                        loadAvailabilityFromServer();
                    });
                };
                chip.appendChild(rm);
            }
            wrap.appendChild(chip);
        });
        section.appendChild(wrap);
        host.appendChild(section);
    });
}

function getSelectedDays() {
    const days = [];
    ['Monday','Tuesday','Wednesday','Thursday','Friday'].forEach(d => {
        const cb = document.getElementById('day-' + d);
        if (cb && cb.checked) days.push(d);
    });
    return days;
}

function loadAvailabilityFromServer() {
    fetch((window.BASE_URL || '/') + 'counselor/profile/availability', { credentials: 'include' })
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.message || 'Failed to load availability');
            // Set checkboxes based on received days
            ['Monday','Tuesday','Wednesday','Thursday','Friday'].forEach(day => {
                const cb = document.getElementById('day-' + day);
                if (cb) cb.checked = !!(d.availability && d.availability[day] && d.availability[day].length);
            });
            // Build state rangesByDay reading consolidated ranges in 12-hour format from server
            const state = { Monday: [], Tuesday: [], Wednesday: [], Thursday: [], Friday: [] };
            (Object.keys(d.availability || {})).forEach(day => {
                const rows = (d.availability[day] || []);
                const ranges = [];
                rows.forEach(row => {
                    const ts = row.time_scheduled;
                    if (!ts) return;
                    // Handle 12-hour format ranges: "1:30 PM-3:00 PM" or "9:00 AM-11:30 AM"
                    const m = String(ts).match(/^(.+?)-(.+)$/);
                    if (m) {
                        ranges.push({ from: m[1].trim(), to: m[2].trim() });
                    }
                });
                state[day] = ranges;
            });
            availabilityState.rangesByDay = state;
            renderAvailabilityChips();
        })
        .catch(err => {
            console.error(err);
        });
}

function saveAvailability() {
    // Save based on days that actually have time ranges added, not on checkbox state
    const daysToSave = [];
    const timesByDay = {};
    
    DAYS_ORDER.forEach(day => {
        const ranges = availabilityState.rangesByDay[day] || [];
        if (ranges.length > 0) {
            daysToSave.push(day);
            // Convert ranges to 12-hour format time strings for server
            const timeStrings = ranges.map(range => `${range.from}-${range.to}`);
            timesByDay[day] = timeStrings;
        }
    });
    
    if (daysToSave.length === 0) { 
        openAlertModal('Please add at least one time slot before saving.', 'warning'); 
        return; 
    }

    fetch((window.BASE_URL || '/') + 'counselor/profile/availability', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ days: daysToSave, timesByDay })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) throw new Error(d.message || 'Failed to save availability');
        openAlertModal('Availability saved successfully!', 'success');
        // Reload to reflect server state, then return to display mode
        loadAvailabilityFromServer();
        setAvailabilityDisplayMode(true);
    })
    .catch(err => {
        openAlertModal(err.message || 'Failed to save availability', 'error');
    });
}

// ===== Display vs Edit Mode Toggle =====
function setAvailabilityDisplayMode(displayMode) {
    availabilityEditMode = !displayMode;
    const editFields = document.getElementById('availability-edit-fields');
    const addBtn = document.getElementById('add-time-slot');
    const saveBtn = document.getElementById('save-availability');
    const editBtn = document.getElementById('edit-availability');
    const cancelBtn = document.getElementById('cancel-availability');

    if (editFields) editFields.style.display = displayMode ? 'none' : '';
    if (addBtn) addBtn.style.display = displayMode ? 'none' : '';
    if (saveBtn) saveBtn.style.display = displayMode ? 'none' : '';
    if (editBtn) editBtn.style.display = displayMode ? '' : 'none';
    if (cancelBtn) cancelBtn.style.display = displayMode ? 'none' : '';

    // Prevent interacting with checkboxes and selects in display mode
    const controls = [];
    document.querySelectorAll('#availability-days input[type="checkbox"], #availability-times select').forEach(el => controls.push(el));
    controls.forEach(el => { el.disabled = displayMode; });

    // Re-render chips so remove buttons appear only in edit mode
    renderAvailabilityChips();
}

function enterAvailabilityEditMode() {
    setAvailabilityDisplayMode(false);
}
