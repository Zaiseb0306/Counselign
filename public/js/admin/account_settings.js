// Debug function to log events
function debug(message) {
    SecureLogger.info(`[DEBUG] ${message}`);
}

// Modal functions for editing fields and changing password
function editField(field) {
    SecureLogger.info(`editField called with field: ${field}`);

    // Check if a modal is already open
    if (document.querySelector('.modal-overlay')) {
        return; // Don't open another modal if one is already open
    }

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';

    let fieldLabel = field === 'email' ? 'Email' : 'Username';
    let fieldType = field === 'email' ? 'email' : 'text';
    let currentValue = '';

    // Get current value
    if (field === 'email') {
        currentValue = document.querySelector('.info-item:nth-of-type(1) .info-value').textContent;
    } else if (field === 'username') {
        currentValue = document.querySelector('.info-item:nth-of-type(2) .info-value').textContent;
    }

    SecureLogger.info(`Current value for ${field}: ${currentValue}`);

    modal.innerHTML = `
        <div class="modal-container">
            <div class="modal-header">
                <h3>Change ${fieldLabel}</h3>
            </div>
            <div class="modal-body">
                <form id="edit-${field}-form">
                <div class="form-group">
                    <label>Current ${fieldLabel}:</label>
                    <input type="text" disabled value="${currentValue}">
                </div>
                <div class="form-group">
                    <label>New ${fieldLabel}:</label>
                        <input type="${fieldType}" id="new-${field}" value="${currentValue}" required>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                        <button type="submit" class="submit-btn">Save Changes</button>
                </div>
                </form>
            </div>
        </div>
    `;

    // Append the modal to the body
    document.body.appendChild(modal);

    // Add animation class after a small delay to trigger the transition
    setTimeout(() => {
        modal.classList.add('active');
        // Do not shift profile container on desktop anymore
        const pc = document.querySelector('.profile-container');
        pc && pc.classList.remove('modal-open');
        positionModal();
    }, 50);

    // Add form submission handler
    const form = modal.querySelector(`#edit-${field}-form`);
    form.onsubmit = function (e) {
        e.preventDefault();
        const newValue = document.getElementById(`new-${field}`).value;

        // Validate email if updating email
        if (field === 'email' && !validateEmail(newValue)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('field', field);
        formData.append('value', newValue);

        // Send update request
        fetch((window.BASE_URL || '/') + 'admin/profile/update', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update displayed value
                    if (field === 'email') {
                        document.querySelector('.info-item:nth-of-type(1) .info-value').textContent = newValue;
                    } else if (field === 'username') {
                        document.querySelector('.info-item:nth-of-type(2) .info-value').textContent = newValue;
                    }
                    showNotification(data.message, 'success');
                    closeModal();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating the profile', 'error');
            });
    };
}

function changePassword() {
    // Check if a modal is already open
    if (document.querySelector('.modal-overlay')) {
        return;
    }

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';

    modal.innerHTML = `
        <div class="modal-container">
            <div class="modal-header">
                <h3>Change Password</h3>
            </div>
            <div class="modal-body">
                <form id="change-password-form">
                <div class="form-group">
                    <label>Current Password:</label>
                        <input type="password" id="current-password" required>
                </div>
                <div class="form-group">
                    <label>New Password:</label>
                        <input type="password" id="new-password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password:</label>
                        <input type="password" id="confirm-password" required>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                        <button type="submit" class="submit-btn">Update Password</button>
                </div>
                </form>
            </div>
        </div>
    `;

    // Append the modal to the body
    document.body.appendChild(modal);

    // Add animation class
    setTimeout(() => {
        modal.classList.add('active');
        const pc = document.querySelector('.profile-container');
        pc && pc.classList.remove('modal-open');
        positionModal();
    }, 50);

    // Add form submission handler
    const form = modal.querySelector('#change-password-form');
    form.onsubmit = function (e) {
        e.preventDefault();
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        // Validate passwords
        if (newPassword !== confirmPassword) {
            showNotification('New passwords do not match!', 'error');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);

        // Send update request
        fetch((window.BASE_URL || '/') + 'update-password', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating the password', 'error');
            });
    };
}

function updateProfilePicture() {
    // Check if a modal is already open
    if (document.querySelector('.modal-overlay')) {
        return;
    }

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';

    modal.innerHTML = `
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Profile Picture</h3>
            </div>
            <div class="modal-body">
                <form id="update-profile-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Select a new profile picture:</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <small>Maximum file size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                        <button type="submit" class="submit-btn">Upload Picture</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Append the modal to the body
    document.body.appendChild(modal);

    // Add animation class
    setTimeout(() => {
        modal.classList.add('active');
        const pc = document.querySelector('.profile-container');
        pc && pc.classList.remove('modal-open');
        positionModal();
    }, 50);

    // Add form submission handler
    const form = modal.querySelector('#update-profile-form');
    form.onsubmit = function (e) {
        e.preventDefault();
        const fileInput = document.getElementById('profile_picture');
        const file = fileInput.files[0];

        if (!file) {
            showNotification('Please select a file', 'error');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('File is too large. Maximum size is 5MB', 'error');
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Only JPG, PNG & GIF files are allowed.', 'error');
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('profile_picture', file);

        // Show loading state
        const submitBtn = form.querySelector('.submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';

        // Send update request to the new endpoint
        fetch((window.BASE_URL || '/') + 'admin/profile/picture', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update profile picture using the correct selector
                    const profileImg = document.getElementById('profile-avatar');
                    if (profileImg) {
                        const newImageUrl = data.picture_url + '?t=' + new Date().getTime(); // Add timestamp to prevent caching
                        profileImg.src = newImageUrl;
                        // Store the updated profile picture URL in sessionStorage
                        sessionStorage.setItem('adminProfilePicture', newImageUrl);
                        // Broadcast the profile picture update
                        broadcastProfileUpdate(newImageUrl);
                        showNotification(data.message, 'success');
                        closeModal();
                        loadAdminData();
                    } else {
                        throw new Error('Profile image element not found');
                    }
                } else {
                    showNotification(data.message || 'Failed to update profile picture', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating the profile picture', 'error');
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload Picture';
            });
    };
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.classList.remove('active');
        document.querySelector('.profile-container').classList.remove('modal-open');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

function positionModal() {
    const modal = document.querySelector('.modal-overlay');
    if (!modal) return;
    // Remove any legacy inline positioning so CSS flex centering can take over
    modal.style.top = '';
    modal.style.right = '';
    modal.style.bottom = '';
    modal.style.left = '';
    modal.style.transform = '';
}

// Add window resize handler
window.addEventListener('resize', positionModal);

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Add active class after a small delay to trigger animation
    setTimeout(() => {
        notification.classList.add('active');
    }, 10);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('active');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Load admin data function
function loadAdminData() {
    debug('Loading admin data...');

    fetch((window.BASE_URL || '/') + 'admin/dashboard/data', {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => {
            if (!response.ok) {
                if (response.status === 403) {
                    window.location.href = (window.BASE_URL || '/') + 'auth/logout';
                    return;
                }
                throw new Error('Network response was not ok');
            }

            // First get the text response
            return response.text().then(text => {
                // Try to parse as JSON, if it fails, log the raw response
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Raw Response:', text);
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Update profile information
                document.getElementById('admin-id').textContent = data.data.user_id;
                document.getElementById('admin-email').textContent = data.data.email;
                document.getElementById('admin-username').textContent = data.data.username;
                document.getElementById('profile-avatar').src = data.data.profile_picture;
            } else {
                throw new Error(data.message || 'Failed to load admin data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load admin data. Please try again later.', 'error');
        });
}

// Add this new function after the updateProfilePicture function
function broadcastProfileUpdate(newImageUrl) {
    // Store in localStorage to persist across pages
    localStorage.setItem('adminProfilePicture', newImageUrl);
    localStorage.setItem('adminProfileUpdateTime', new Date().getTime());

    // Broadcast the update to other open tabs/windows
    window.dispatchEvent(new StorageEvent('storage', {
        key: 'adminProfilePicture',
        newValue: newImageUrl
    }));
}

// Add profile picture update listener
function initProfilePictureListener() {
    // Listen for profile picture updates from other tabs/windows
    window.addEventListener('storage', (event) => {
        if (event.key === 'adminProfilePicture') {
            updateAllProfilePictures(event.newValue);
        }
    });

    // Check for existing profile picture update
    const storedPicture = localStorage.getItem('adminProfilePicture');
    if (storedPicture) {
        updateAllProfilePictures(storedPicture);
    }
}

function updateAllProfilePictures(newImageUrl) {
    // Update all profile pictures on the current page
    const profilePictures = document.querySelectorAll('.profile-avatar, .admin-avatar, .message-avatar');
    profilePictures.forEach(img => {
        if (img.classList.contains('admin-avatar')) {
            img.src = newImageUrl;
        }
    });
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    debug('DOM Content Loaded');

    // Initialize profile picture listener
    initProfilePictureListener();

    // Load admin data
    loadAdminData();

    // Make functions available globally
    window.editField = editField;
    window.changePassword = changePassword;
    window.closeModal = closeModal;
    window.showNotification = showNotification;
    window.validateEmail = validateEmail;
    window.updateProfilePicture = updateProfilePicture;

    // Add event listeners to buttons
    const editButtons = document.querySelectorAll('.edit-btn');
    debug(`Found ${editButtons.length} edit buttons`);

    editButtons.forEach((button, index) => {
        button.addEventListener('click', function () {
            debug(`Edit button ${index + 1} clicked`);
            const parentItem = this.closest('.info-item');
            const label = parentItem.querySelector('.info-label').textContent.trim();
            let field = '';

            if (label.includes('Email')) {
                field = 'email';
            } else if (label.includes('Username')) {
                field = 'username';
            }

            if (field) {
                editField(field);
            }
        });
    });

    const passwordButton = document.querySelector('.change-password-btn');
    if (passwordButton) {
        debug('Password button found');
        passwordButton.addEventListener('click', function () {
            debug('Password button clicked');
            changePassword();
        });
    } else {
        debug('Password button not found');
    }
});