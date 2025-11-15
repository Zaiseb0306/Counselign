document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const updateBtn = document.querySelector('.update-btn');
    const modalOverlay = document.getElementById('updateModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelUpdateBtn = document.getElementById('cancelUpdate');
    const updateForm = document.getElementById('updateCounselorForm');
    const mainContent = document.querySelector('.counselor-form-container');
    const addCounselorBtn = document.querySelector('.add-counselor');
    
    // Information display elements
    const counselorIdDisplay = document.getElementById('counselorId');
    const specializationDisplay = document.getElementById('specialization');
    const nameDisplay = document.getElementById('name');
    const degreeDisplay = document.getElementById('degree');
    const emailDisplay = document.getElementById('email');
    const contactNumberDisplay = document.getElementById('contactNumber');
    const licenseNumberDisplay = document.getElementById('licenseNumber');
    const addressDisplay = document.getElementById('address');
    const startTimeDisplay = document.getElementById('startTime');
    const endTimeDisplay = document.getElementById('endTime');
    const mainProfileImage = document.getElementById('main-profile-image');
    
    // Modal form inputs
    const modalCounselorId = document.getElementById('modal-counselorId');
    const modalSpecialization = document.getElementById('modal-specialization');
    const modalName = document.getElementById('modal-name');
    const modalDegree = document.getElementById('modal-degree');
    const modalEmail = document.getElementById('modal-email');
    const modalContactNumber = document.getElementById('modal-contactNumber');
    const modalLicenseNumber = document.getElementById('modal-licenseNumber');
    const modalAddress = document.getElementById('modal-address');
    const modalStartTime = document.getElementById('modal-startTime');
    const modalEndTime = document.getElementById('modal-endTime');
    
    // Add profile picture elements
    let currentProfilePicture = '';
    let counselors = [];
    let isEditMode = false;
    
    // Initial setup - hide the add button until we know if we have counselors
    if (addCounselorBtn) {
        addCounselorBtn.style.display = 'none';
    }
    
    // Function to show empty state
    function showEmptyState() {
        mainContent.innerHTML = `
            <div class="text-center" style="padding: 40px;">
                <img src="${(window.BASE_URL || '/') + 'Photos/profile.png'}" alt="Empty State" style="width: 150px; height: 150px; opacity: 0.5; margin-bottom: 20px;">
                <h3 style="color: #666; margin-bottom: 15px;">No Counselors Registered Yet</h3>
                <p style="color: #888; margin-bottom: 25px;">Counselors Registered in the system will have their information displayed here.</p>
                <button class="btn-add-first-counselor" style="display: inline-flex; padding: 12px 24px; gap: 8px; background-color: #060E57; color: white; border: none; border-radius: 8px; cursor: pointer;" hidden>
                    <i class="fas fa-plus"></i> Add New Counselor
                </button>
            </div>
        `;

        // Add click event to the new add counselor button
        const newAddButton = mainContent.querySelector('.btn-add-first-counselor');
        if (newAddButton) {
            newAddButton.addEventListener('click', function() {
                isEditMode = false;
                openAddCounselorModal();
            });
        }
    }
    
    // Load counselors from database
    function loadCounselors() {
        SecureLogger.info('Loading counselors...');
        
        // Create a timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        fetch((window.BASE_URL || '/') + `admin/counselors/api?t=${timestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    SecureLogger.info('Raw server response:', text); // Add this for debugging
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        console.error('Raw response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                SecureLogger.info('Received counselors data:', data);
                counselors = data.data || [];
                
                // Update add button visibility
                if (addCounselorBtn) {
                    addCounselorBtn.style.display = counselors.length > 0 ? 'flex' : 'none';
                }
                
                const counselorList = document.querySelector('.counselor-list');
                
                if (!counselors.length) {
                    counselorList.innerHTML = '';
                    showEmptyState();
                    if (updateBtn) updateBtn.style.display = 'none';
                    return;
                }

                // Separate pending vs verified
                const pending = counselors.filter(c => String(c.is_verified || 0) === '0');
                const verified = counselors.filter(c => String(c.is_verified || 0) === '1');

                const renderItem = (counselor, index, isPending) => `
                    <div class="counselor-item ${index === 0 && pending.length > 0 ? 'active' : ''} ${isPending ? 'pending' : ''}" data-id="${counselor.counselor_id}" data-index="${counselors.indexOf(counselor)}" style="${isPending ? 'border-left:4px solid #f1c40f;' : ''}">
                        <img src="${counselor.profile_picture ? (window.BASE_URL || '/') + 'Photos/profile_pictures/' + counselor.profile_picture.split('/').pop() : (window.BASE_URL || '/') + 'Photos/profile.png'}" alt="${counselor.name}" class="counselor-avatar" onerror="this.onerror=null;this.src='${(window.BASE_URL || '/') + 'Photos/profile.png'}'">
                        <div class="counselor-info">
                            <h3 style="display:flex;align-items:center;gap:6px;">${counselor.name}${isPending ? '<span title="Pending verification" style="display:inline-block;width:8px;height:8px;background:#f1c40f;border-radius:50%;"></span>' : ''}</h3>
                            <p>${counselor.counselor_id}</p>
                        </div>
                    </div>`;

                let html = '';
                if (pending.length) {
                    html += '<div class="counselor-section-title" style="padding:6px 10px;color:#aa8400;font-weight:600;">Pending Verification</div>';
                    html += pending.map((c, i) => renderItem(c, i, true)).join('');
                }
                if (verified.length) {
                    html += '<div class="counselor-section-title" style="padding:6px 10px;color:#555;font-weight:600;margin-top:6px;">Verified</div>';
                    html += verified.map((c) => renderItem(c, 0, false)).join('');
                }
                counselorList.innerHTML = html;

                // Show the main content with the first pending else first verified
                const initial = pending[0] || verified[0] || null;
                displayCounselorInfo(initial);
                
                // Show update button
                if (updateBtn) updateBtn.style.display = 'block';
                
                // Add click events to counselor items
                document.querySelectorAll('.counselor-item').forEach((button) => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        document.querySelectorAll('.counselor-item').forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        displayCounselorInfo(counselors[index]);
                    });
                });
            })
            .catch(error => {
                console.error('Error loading counselors:', error);
                showNotification('Failed to load counselors: ' + error.message, 'error');
                showEmptyState();
            });
    }
    
    function handleSubmit(e) {
        e.preventDefault();
        SecureLogger.info('Form submission started');

        // Create FormData object from the form
        const formData = new FormData();

        // Get counselorId from the input field
        const counselorId = document.getElementById('modal-counselorId').value;
        if (!counselorId) {
            showNotification('Counselor ID is required', 'error');
            return;
        }

        // Get selected days
        const selectedDays = Array.from(document.querySelectorAll('input[name="days"]:checked'))
            .map(checkbox => checkbox.value);
        
        // Format time values
        const startTime = document.getElementById('modal-startTime').value;
        const endTime = document.getElementById('modal-endTime').value;
        const timeScheduled = startTime && endTime ? `${startTime} - ${endTime}` : '';

        // Add all required fields
        formData.append('counselorId', counselorId);
        formData.append('name', document.getElementById('modal-name').value || '');
        formData.append('specialization', document.getElementById('modal-specialization').value || '');
        formData.append('degree', document.getElementById('modal-degree').value || '');
        formData.append('email', document.getElementById('modal-email').value || '');
        formData.append('contactNumber', document.getElementById('modal-contactNumber').value || '');
        formData.append('licenseNumber', document.getElementById('modal-licenseNumber').value || '');
        formData.append('address', document.getElementById('modal-address').value || '');
        formData.append('startTime', startTime);
        formData.append('endTime', endTime);
        formData.append('timeScheduled', timeScheduled);
        formData.append('availableDays', JSON.stringify(selectedDays));

        // Log form data for debugging
        SecureLogger.info('Form data being sent:', {
            counselorId,
            startTime,
            endTime,
            timeScheduled,
            days: selectedDays
        });

        // Add remove profile flag if set
        const removeProfileFlag = document.getElementById('profile-remove-flag');
        if (removeProfileFlag && removeProfileFlag.value === '1') {
            formData.append('remove_profile', '1');
        }

        // Add profile picture if changed
        const fileInput = document.getElementById('profile-upload');
        if (fileInput && fileInput.files[0]) {
            formData.append('profile_picture', fileInput.files[0]);
        }

        // Log the data being sent
        SecureLogger.info('Sending form data:', Object.fromEntries(formData));

        // Show loading indicator
        showNotification('Processing...', 'info');

        // Send to server
        fetch((window.BASE_URL || '/') + 'admin/counselors/api', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            SecureLogger.info('Raw response:', response);
            return response.text().then(text => {
                SecureLogger.info('Response text:', text);
                if (!response.ok) {
                    throw new Error(text || `HTTP error! status: ${response.status}`);
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            SecureLogger.info('Server response:', data);
            if (data.success) {
                showNotification(data.message || 'Counselor updated successfully', 'success');
        closeModal();
                
                // Wait a moment before reloading the counselors to ensure database update is complete
                setTimeout(() => {
                    loadCounselors();
                }, 500);
            } else {
                throw new Error(data.message || 'Error updating counselor');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification(error.message || 'An error occurred while processing your request', 'error');
        });
    }
    
    function displayCounselorInfo(counselor) {
        if (!counselor) {
            clearCounselorDisplay();
            return;
        }

        SecureLogger.info('Displaying counselor info:', counselor); // Add this for debugging

        // Update main content profile section
        mainContent.innerHTML = `
        <div class="counselor-details">
            <div class="profile-image-container text-center">
                <img src="${counselor.profile_picture ? (window.BASE_URL || '/') + 'Photos/profile_pictures/' + counselor.profile_picture.split('/').pop() : (window.BASE_URL || '/') + 'Photos/profile.png'}" alt="Counselor Profile" class="profile-image" id="main-profile-image" onerror="this.onerror=null;this.src='${(window.BASE_URL || '/') + 'Photos/profile.png'}'">
            </div>

            <div class="details-right">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Counselor's ID:</label>
                        <div class="info-display" id="counselorId">${counselor.counselor_id || ''}</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Name:</label>
                        <div class="info-display" id="name">${counselor.name || ''}</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Degree:</label>
                        <div class="info-display" id="degree">${counselor.degree || ''}</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <div class="info-display" id="email">${counselor.email || ''}</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number:</label>
                        <div class="info-display" id="contactNumber">${counselor.contact_number || ''}</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address:</label>
                        <div class="info-display" id="address">${counselor.address || ''}</div>
                    </div>
                </div>
            </div>
        </div>

            <div class="form-group">
                    <label>Availability:</label>
                    <div id="availabilityCards" class="availability-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,2fr));gap:8px;">Loading...</div>
            </div>

            ${String(counselor.is_verified || 0) === '0' ? `
            <div class="actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px;">
                <button id="approveCounselorBtn" class="btn btn-success" style="padding:8px 12px;"><i class="fas fa-check"></i> Approve</button>
                <button id="rejectCounselorBtn" class="btn btn-danger" style="padding:8px 12px;"><i class="fas fa-times"></i> Reject</button>
            </div>` : ''}
        `;

        // Load counselor schedule
        loadCounselorSchedule(counselor.counselor_id);

        // Approve/Reject handlers when pending
        if (String(counselor.is_verified || 0) === '0') {
            const approveBtn = document.getElementById('approveCounselorBtn');
            const rejectBtn = document.getElementById('rejectCounselorBtn');
            if (approveBtn) {
                approveBtn.addEventListener('click', () => approveCounselor(counselor.counselor_id, approveBtn));
            }
            if (rejectBtn) {
                rejectBtn.addEventListener('click', () => openRejectModal(counselor));
            }
        }

        // Re-attach event listeners
        const newUpdateBtn = document.querySelector('.update-btn');
        if (newUpdateBtn) {
            newUpdateBtn.addEventListener('click', () => {
                openModal(counselor);
            });
        }

        const deleteBtn = document.querySelector('.delete-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                confirmDeleteCounselor(counselor);
            });
        }

        // Store current profile picture
        currentProfilePicture = counselor.profile_picture || '';
    }
    
    function showNotification(message, type = 'success') {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(note => note.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('active'), 10);
        
        setTimeout(() => {
            notification.classList.remove('active');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    function generateCounselorId() {
        const date = new Date();
        const year = date.getFullYear();
        const randomNum = Math.floor(Math.random() * 9000) + 1000;
        return `COUN-${year}-${randomNum}`;
    }
    
    function openAddCounselorModal() {
        // Clear the form inputs
        modalCounselorId.value = generateCounselorId();
        modalCounselorId.readOnly = true;
        modalSpecialization.value = '';
        modalName.value = '';
        modalDegree.value = '';
        modalEmail.value = '';
        modalContactNumber.value = '';
        modalLicenseNumber.value = '';
        modalAddress.value = '';
        modalStartTime.value = '';
        modalEndTime.value = '';
        currentProfilePicture = '';
        
        // Change modal title and button text
        document.querySelector('.modal-header h3').textContent = 'Add New Counselor';
        document.querySelector('.confirm-btn').textContent = 'Add Counselor';
        
        openModal();
    }
    
    function openModal(counselor) {
        modalOverlay.style.display = 'flex';
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Set default values for counselor if not provided (for add new counselor case)
        const defaultCounselor = {
            counselor_id: generateCounselorId(),
            specialization: '',
            name: '',
            degree: '',
            email: '',
            contact_number: '',
            license_number: '',
            address: '',
            start_time: '09:00',
            end_time: '17:00',
            profile_picture: '',
            days: []
        };
        
        // Use provided counselor data or defaults
        const counselorData = counselor || defaultCounselor;
        isEditMode = !!counselor; // Set edit mode based on whether counselor data was provided

        // Ensure time values are in proper format (HH:mm)
        const formatTime = (timeStr) => {
            if (!timeStr) return '';
            if (/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(timeStr)) return timeStr;
            try {
                const [hours, minutes] = timeStr.split(':');
                return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
            } catch (e) {
                return '';
            }
        };

        let startTime = '';
        let endTime = '';
        if (counselorData.time_scheduled && counselorData.time_scheduled.includes('-')) {
            const parts = counselorData.time_scheduled.split('-').map(s => s.trim());
            startTime = parts[0] || '';
            endTime = parts[1] || '';
        }
        
        // Update modal form layout
        const modalContent = document.querySelector('.modal-body');
        modalContent.innerHTML = `
            <form id="updateCounselorForm">
                <div id="profile-upload-section" class="mb-3 text-center">
                    <div class="profile-upload-container" style="margin-bottom: 15px;">
                        <div class="profile-preview" style="display: flex; justify-content: center;">
                            <div style="position: relative; width: 100px; height: 100px;">
                                <img id="profile-preview" src="${counselorData.profile_picture ? (window.BASE_URL || '/') + 'Photos/profile_pictures/' + counselorData.profile_picture.split('/').pop() : (window.BASE_URL || '/') + 'Photos/profile.png'}" 
                                    alt="Profile Preview" 
                                    style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 2px solid #e0e0e0;">
                            </div>
                        </div>
                        <div class="profile-upload-controls mt-2">
                            <input type="file" id="profile-upload" accept="image/*" style="display: none;">
                            <input type="hidden" id="profile-remove-flag" name="remove_profile" value="0">
                            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        style="padding: 4px 12px; font-size: 12px;"
                                        onclick="document.getElementById('profile-upload').click()">
                                    Upload Photo
                                </button>
                                <button type="button" id="remove-photo-btn" class="btn btn-sm btn-outline-danger" 
                                        style="padding: 4px 12px; font-size: 12px;"
                                        onclick="removeProfilePhoto()">
                                    Remove Photo
                                </button>
                            </div>
                            <small class="d-block text-muted mt-1" style="font-size: 11px;">Max: 5MB (JPG, PNG)</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Counselor's ID:</label>
                    <input type="text" id="modal-counselorId" class="form-control" value="${counselorData.counselor_id}" ${isEditMode ? 'readonly' : ''}>
                </div>
                
                <div class="form-group">
                    <label>Specialization:</label>
                    <input type="text" id="modal-specialization" class="form-control" value="${counselorData.specialization}">
                </div>
                
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" id="modal-name" class="form-control" value="${counselorData.name}">
                </div>
                
                <div class="form-group">
                    <label>Degree:</label>
                    <input type="text" id="modal-degree" class="form-control" value="${counselorData.degree}">
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="modal-email" class="form-control" value="${counselorData.email}">
                </div>
                
                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="text" id="modal-contactNumber" class="form-control" value="${counselorData.contact_number}">
                </div>
                
                <div class="form-group">
                    <label>License Number:</label>
                    <input type="text" id="modal-licenseNumber" class="form-control" value="${counselorData.license_number}">
                </div>
                
                <div class="form-group">
                    <label>Address:</label>
                    <input type="text" id="modal-address" class="form-control" value="${counselorData.address}">
                </div>

                <div class="form-group">
                    <label>Time Scheduled:</label>
                    <div class="schedule-time-container" style="display: flex; align-items: center; gap: 10px;">
                        <input type="time" id="modal-startTime" class="form-control" value="${startTime}" style="width: 150px; flex: 1;" required>
                        <span class="time-separator" style="margin: 0 5px;">-</span>
                        <input type="time" id="modal-endTime" class="form-control" value="${endTime}" style="width: 150px; flex: 1;" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Available Days:</label>
                    <div class="days-selection" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="days" value="Monday" ${counselorData.available_days?.includes('Monday') ? 'checked' : ''}>
                            Monday
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="days" value="Tuesday" ${counselorData.available_days?.includes('Tuesday') ? 'checked' : ''}>
                            Tuesday
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="days" value="Wednesday" ${counselorData.available_days?.includes('Wednesday') ? 'checked' : ''}>
                            Wednesday
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="days" value="Thursday" ${counselorData.available_days?.includes('Thursday') ? 'checked' : ''}>
                            Thursday
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="days" value="Friday" ${counselorData.available_days?.includes('Friday') ? 'checked' : ''}>
                            Friday
                        </label>
                    </div>
                </div>

                <div class="modal-footer" style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn btn-secondary" id="cancelUpdate" style="margin-right: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary confirm-btn">
                        ${isEditMode ? 'Update Counselor' : 'Save Counselor'}
                    </button>
                </div>
            </form>
        `;

        // Update modal title
        const modalTitle = document.querySelector('.modal-header h3');
        if (modalTitle) {
            modalTitle.textContent = isEditMode ? 'Update Counselor Information' : 'Add New Counselor';
        }

        // Add event listener for file input
        document.getElementById('profile-upload').addEventListener('change', handleProfilePictureChange);

        // Add custom styles to modal
        const modalContainer = document.querySelector('.modal-container');
        if (modalContainer) {
            modalContainer.style.maxWidth = '400px';
            modalContainer.style.margin = '0 auto';
            modalContainer.style.backgroundColor = '#fff';
            modalContainer.style.borderRadius = '8px';
        }

        // Style form groups
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            group.style.marginBottom = '15px';
            const label = group.querySelector('label');
            const input = group.querySelector('input');
            if (label) {
                label.style.fontSize = '14px';
                label.style.marginBottom = '5px';
                label.style.color = '#333';
                label.style.fontWeight = '500';
            }
            if (input) {
                input.style.fontSize = '14px';
                input.style.padding = '8px 12px';
                input.style.borderRadius = '4px';
                input.style.border = '1px solid #ced4da';
            }
        });

        // Re-attach event listeners
        const form = document.getElementById('updateCounselorForm');
        if (form) {
            form.addEventListener('submit', handleSubmit);
        }

        const cancelBtn = document.getElementById('cancelUpdate');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }
    }
    
    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
        
        // Remove the profile upload section so it can be recreated fresh next time
        const profileSection = document.getElementById('profile-upload-section');
        if (profileSection) {
            profileSection.remove();
        }
    }
    
    function handleProfilePictureChange(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('File is too large. Maximum size is 5MB', 'error');
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Only JPG and PNG files are allowed.', 'error');
            return;
        }
        
        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profile-preview');
            preview.src = e.target.result;
            currentProfilePicture = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    // Add this function to create and display a confirmation modal
    function confirmDeleteCounselor(counselor) {
        // Create confirmation modal
        const confirmationModal = document.createElement('div');
        confirmationModal.className = 'confirmation-modal-overlay';
        confirmationModal.style.position = 'fixed';
        confirmationModal.style.top = '0';
        confirmationModal.style.left = '0';
        confirmationModal.style.width = '100%';
        confirmationModal.style.height = '100%';
        confirmationModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        confirmationModal.style.display = 'flex';
        confirmationModal.style.justifyContent = 'center';
        confirmationModal.style.alignItems = 'center';
        confirmationModal.style.zIndex = '1000';

        confirmationModal.innerHTML = `
            <div class="confirmation-modal" style="background-color: white; padding: 20px; border-radius: 8px; width: 400px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                <h3 style="margin-top: 0; color: #dc3545; font-size: 18px;">Confirm Deletion</h3>
                <p style="margin-bottom: 20px;">Are you sure you want to delete counselor ${counselor.name}? This action cannot be undone.</p>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button class="cancel-delete-btn" style="padding: 8px 12px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button class="confirm-delete-btn" style="padding: 8px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Delete</button>
                </div>
            </div>
        `;

        document.body.appendChild(confirmationModal);

        // Add event listeners for the buttons
        const cancelDeleteBtn = confirmationModal.querySelector('.cancel-delete-btn');
        const confirmDeleteBtn = confirmationModal.querySelector('.confirm-delete-btn');

        cancelDeleteBtn.addEventListener('click', () => {
            document.body.removeChild(confirmationModal);
        });

        confirmDeleteBtn.addEventListener('click', () => {
            deleteCounselor(counselor.counselor_id);
            document.body.removeChild(confirmationModal);
        });
    }

    // Function to delete a counselor
    function deleteCounselor(counselorId) {
        fetch((window.BASE_URL || '/') + 'admin/counselors/api', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ counselor_id: counselorId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showNotification('Counselor deleted successfully', 'success');
                
                // Remove from the list
                const deletedCounselorElement = document.querySelector(`[data-id="${counselorId}"]`);
                if (deletedCounselorElement) {
                    deletedCounselorElement.remove();
                }
                
                // Remove from the array
                const index = counselors.findIndex(c => c.counselor_id === counselorId);
                if (index !== -1) {
                    counselors.splice(index, 1);
                }
                
                // If the currently displayed counselor was deleted, clear the display
                const currentDisplayedId = document.getElementById('counselorId').textContent;
                if (currentDisplayedId === counselorId) {
                    clearCounselorDisplay();
                }
                
                // If there are remaining counselors, display the first one
                if (counselors.length > 0) {
                    displayCounselorInfo(counselors[0]);
                }
                // Refresh the page to ensure UI is fully updated
                setTimeout(() => { location.reload(); }, 500);
            } else {
                showNotification('Error deleting counselor: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting counselor', 'error');
        });
    }

    // Function to clear the counselor display
    function clearCounselorDisplay() {
        const counselorIdElement = document.getElementById('counselorId');
        const nameElement = document.getElementById('name');
        const degreeElement = document.getElementById('degree');
        const emailElement = document.getElementById('email');
        const contactNumberElement = document.getElementById('contactNumber');
        const addressElement = document.getElementById('address');
        const timeScheduledElement = document.getElementById('timeScheduled');
        const availableDaysElement = document.getElementById('availableDays');
        
        if (counselorIdElement) counselorIdElement.textContent = '-';
        if (nameElement) nameElement.textContent = '-';
        if (degreeElement) degreeElement.textContent = '-';
        if (emailElement) emailElement.textContent = '-';
        if (contactNumberElement) contactNumberElement.textContent = '-';
        if (addressElement) addressElement.textContent = '-';
        if (timeScheduledElement) timeScheduledElement.textContent = '-';
        if (availableDaysElement) availableDaysElement.textContent = '-';
        
        // Clear profile picture
        const profileImg = document.getElementById('main-profile-image');
        if (profileImg) {
            profileImg.src = (window.BASE_URL || '/') + 'Photos/profile.png';
        }
    }

    // Update the updateCounselorsList function to include delete buttons
    function updateCounselorsList() {
        const counselorsList = document.getElementById('counselorsList');
        counselorsList.innerHTML = '';
        
        counselors.forEach(counselor => {
            const button = document.createElement('div');
            button.className = 'counselor-button';
            
            button.innerHTML = `
                <button class="btn w-100 text-start mb-2" data-id="${counselor.counselor_id}">
                    ${counselor.name}
                </button>
                <button class="btn btn-danger btn-sm delete-counselor no-border" data-id="${counselor.counselor_id}" title="Delete counselor" style="border: none; outline: none;">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            counselorsList.appendChild(button);
            
            // Add click event for the counselor button
            const counselorBtn = button.querySelector('.btn:not(.delete-counselor)');
            counselorBtn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const selectedCounselor = counselors.find(c => c.counselor_id === id);
                displayCounselorInfo(selectedCounselor);
            });
            
            // Add click event for the delete button
            const deleteBtn = button.querySelector('.delete-counselor');
            deleteBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering the counselor button
                const id = this.getAttribute('data-id');
                deleteCounselor(id);
            });
        });
    }
    
    // Initialize
    loadCounselors();
    
    // Event Listeners
    if (updateForm) updateForm.addEventListener('submit', handleSubmit);
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelUpdateBtn) cancelUpdateBtn.addEventListener('click', closeModal);
    
    // Handle add counselor button
    if (addCounselorBtn) {
        addCounselorBtn.addEventListener('click', function() {
            isEditMode = false;
            openAddCounselorModal();
        });
    }

    // Event listener for the delete button
    const deleteButton = document.getElementById('deleteButton');
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
        const counselorId = document.getElementById('counselorId').textContent;
        if (counselorId && counselorId !== '-') {
            deleteCounselor(counselorId);
        } else {
            showNotification('No counselor selected to delete', 'error');
        }
    });
    }

    // Update the removeProfilePhoto function to be globally accessible
    window.removeProfilePhoto = function() {
        const preview = document.getElementById('profile-preview');
        preview.src = (window.BASE_URL || '/') + 'Photos/profile.png';
        currentProfilePicture = '';
        
        // Set the remove flag to true
        document.getElementById('profile-remove-flag').value = '1';
        
        // Reset the file input
        const fileInput = document.getElementById('profile-upload');
        if (fileInput) {
            fileInput.value = '';
        }
        
        showNotification('Profile photo will be removed upon saving', 'info');
    };

    /**
     * Load counselor's availability schedule and display it
     * @param {string} counselorId - The counselor ID to load schedule for
     */
    function loadCounselorSchedule(counselorId) {
        if (!counselorId) {
            console.warn('No counselor ID provided for schedule loading');
            displayScheduleError('No counselor selected');
            return;
        }

        const url = (window.BASE_URL || '/') + `admin/counselor-info/schedule?counselor_id=${counselorId}&_=${Date.now()}`;
        
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
                displayScheduleError('No Available Schedule Defined yet');
            }
        })
        .catch(error => {
            console.error('Error loading counselor schedule:', error);
            displayScheduleError('No Available Schedule Defined yet');
        });
    }

    /**
     * Display the counselor's schedule in the info display
     * @param {Array} schedule - Array of schedule objects with day and time properties
     */
    function displayCounselorSchedule(schedule) {
        const container = document.getElementById('availabilityCards');
        if (!container) return;

        if (!schedule || schedule.length === 0) {
            container.textContent = 'No Availability Scheduled Yet.';
            return;
        }

        // Group schedule by day with multiple time slots
        const grouped = {};
        schedule.forEach(item => {
            const day = item.day;
            const time = (item.time || '').trim();
            if (!grouped[day]) grouped[day] = [];
            if (time && !grouped[day].includes(time)) grouped[day].push(time);
        });

        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sortedDays = Object.keys(grouped).sort((a, b) => dayOrder.indexOf(a) - dayOrder.indexOf(b));

        container.innerHTML = '';
        sortedDays.forEach(day => {
            const times = grouped[day];
            const formattedTimes = times.length ? formatTimeSlotsForBadges(times).join(', ') : 'All day';
            const card = document.createElement('div');
            card.className = 'availability-card';
            card.style.cssText = 'border:1px solid #e0e0e0;border-radius:6px;padding:6px 10px;background:#fff;';
            card.textContent = `${day}: ${formattedTimes}`;
            container.appendChild(card);
        });
    }

    /**
     * Display schedule error message
     * @param {string} message - Error message to display
     */
    function displayScheduleError(message) {
        const container = document.getElementById('availabilityCards');
        if (container) container.textContent = message;
    }

    // Approve counselor
    function approveCounselor(counselorId, buttonEl) {
        // Loading state
        const originalHtml = buttonEl ? buttonEl.innerHTML : '';
        if (buttonEl) {
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
        }

        fetch((window.BASE_URL || '/') + 'admin/counselors/approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ counselor_id: counselorId })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Approval failed');
            showNotification('Counselor approved successfully', 'success');
            setTimeout(() => loadCounselors(), 300);
        })
        .catch(err => {
            console.error(err);
            showNotification(err.message || 'Approval error', 'error');
        })
        .finally(() => {
            if (buttonEl) {
                buttonEl.disabled = false;
                buttonEl.innerHTML = originalHtml;
            }
        });
    }

    // Reject counselor with reason
    function openRejectModal(counselor) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;';
        overlay.innerHTML = `
            <div style="background:#fff;border-radius:8px;max-width:420px;width:100%;padding:16px;">
                <h4 style="margin:0 0 8px 0;">Reject Counselor</h4>
                <p style="margin:0 0 8px 0;">Provide a reason to include in the email.</p>
                <textarea id="rejectReason" rows="4" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;"></textarea>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button id="rejectCancel" class="btn btn-secondary">Cancel</button>
                    <button id="rejectConfirm" class="btn btn-danger">Reject</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        const cancelBtn = overlay.querySelector('#rejectCancel');
        const confirmBtn = overlay.querySelector('#rejectConfirm');
        cancelBtn.onclick = () => document.body.removeChild(overlay);
        confirmBtn.onclick = () => {
            const reason = overlay.querySelector('#rejectReason').value.trim();
            if (!reason) { showNotification('Rejection reason is required', 'error'); return; }
            const originalHtml = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
            fetch((window.BASE_URL || '/') + 'admin/counselors/reject', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ counselor_id: counselor.counselor_id, reason })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Rejection failed');
                document.body.removeChild(overlay);
                showNotification('Counselor rejected and removed', 'success');
                setTimeout(() => loadCounselors(), 300);
            })
            .catch(err => {
                console.error(err);
                showNotification(err.message || 'Rejection error', 'error');
            })
            .finally(() => {
                // If overlay still exists (i.e., on error), restore button states
                if (document.body.contains(overlay)) {
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                    confirmBtn.innerHTML = originalHtml;
                }
            });
        };
    }
});
