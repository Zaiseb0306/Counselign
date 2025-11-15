// Function to get admin data from the server
function fetchAdminData() {
    return fetch((window.BASE_URL || '/') + 'admin/dashboard/data', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.profile_picture) {
            localStorage.setItem('adminProfilePicture', data.data.profile_picture);
            return data.data.profile_picture;
        }
        throw new Error('Failed to fetch admin data');
    });
}

// Function to update admin profile pictures on the current page
function updateAdminProfilePictures() {
    SecureLogger.info('Updating profile pictures...');
    const storedPicture = localStorage.getItem('adminProfilePicture');
    
    // If we have a stored picture, use it immediately
    if (storedPicture) {
        SecureLogger.info('Using stored picture:', storedPicture);
        updatePicturesWithUrl(storedPicture);
    }
    
    // Always fetch fresh data from server
    fetchAdminData()
        .then(profilePicture => {
            SecureLogger.info('Fetched new picture:', profilePicture);
            updatePicturesWithUrl(profilePicture);
        })
        .catch(error => {
            console.error('Error fetching admin data:', error);
        });
}

// Function to update all profile picture elements with a URL
function updatePicturesWithUrl(pictureUrl) {
    if (!pictureUrl) return;
    
    // Add cache-busting parameter
    const timestamp = new Date().getTime();
    const random = Math.random();
    const urlWithCache = `${pictureUrl}?t=${timestamp}&r=${random}`;
    
    // Update all possible profile picture elements
    const selectors = [
        '.profile-avatar img',
        '.admin-avatar',
        '.message-avatar',
        '.profile-img',
        '#profile-img',
        'img.admin-avatar'
    ];
    
    selectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(img => {
            SecureLogger.info('Updating element:', selector, img);
            img.src = urlWithCache;
        });
    });
}

// Function to force reload profile pictures
function forceReloadProfilePictures() {
    updateAdminProfilePictures();
}

// Listen for profile picture updates from other tabs/windows
window.addEventListener('storage', (event) => {
    if (event.key === 'adminProfilePicture') {
        SecureLogger.info('Storage event detected:', event.newValue);
        updatePicturesWithUrl(event.newValue);
    }
});

// Initialize when the page loads
document.addEventListener('DOMContentLoaded', () => {
    SecureLogger.info('DOM loaded, initializing profile sync...');
    
    // Initial update
    updateAdminProfilePictures();
    
    // Set up periodic updates
    setInterval(forceReloadProfilePictures, 30000); // Check every 30 seconds
}); 