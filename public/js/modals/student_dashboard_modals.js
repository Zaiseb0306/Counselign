// Modal utility functions for user dashboard
function openConfirmationModal(message, onConfirm = null) {
    document.getElementById('confirmationMessageContent').textContent = message;
    
    // Get the modal element
    const modalElement = document.getElementById('confirmationModal');
    
    // Use Bootstrap's getOrCreateInstance to avoid disposal issues
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    
    // Add event listener for confirm button
    const confirmBtn = document.getElementById('confirmationConfirmBtn');
    
    // Remove any existing event listeners by cloning the button
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.onclick = function() {
        modalInstance.hide();
        if (onConfirm) {
            setTimeout(() => {
                onConfirm();
            }, 150);
        }
    };
    
    modalInstance.show();
}

function openAlertModal(message, type = 'info') {
    document.getElementById('alertMessageContent').textContent = message;
    
    // Get the modal element
    const modalElement = document.getElementById('alertModal');
    
    // Use Bootstrap's getOrCreateInstance to avoid disposal issues
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    
    // Update icon based on type
    const alertIcon = document.getElementById('alertIcon');
    const iconElement = alertIcon.querySelector('i');
    
    switch(type) {
        case 'success':
            iconElement.className = 'fas fa-check-circle text-success';
            document.getElementById('alertModalLabel').textContent = 'Success';
            break;
        case 'error':
            iconElement.className = 'fas fa-exclamation-circle text-danger';
            document.getElementById('alertModalLabel').textContent = 'Error';
            break;
        case 'warning':
            iconElement.className = 'fas fa-exclamation-triangle text-warning';
            document.getElementById('alertModalLabel').textContent = 'Warning';
            break;
        default:
            iconElement.className = 'fas fa-info-circle text-primary';
            document.getElementById('alertModalLabel').textContent = 'Information';
    }
    
    modalInstance.show();
}

function openNoticeModal(message, type = 'info') {
    document.getElementById('noticeMessageContent').textContent = message;
    
    // Get the modal element
    const modalElement = document.getElementById('noticeModal');
    
    // Use Bootstrap's getOrCreateInstance to avoid disposal issues
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    
    // Update icon based on type
    const noticeIcon = document.getElementById('noticeIcon');
    const iconElement = noticeIcon.querySelector('i');
    
    switch(type) {
        case 'success':
            iconElement.className = 'fas fa-check-circle text-success';
            document.getElementById('noticeModalLabel').textContent = 'Success';
            break;
        case 'error':
            iconElement.className = 'fas fa-exclamation-circle text-danger';
            document.getElementById('noticeModalLabel').textContent = 'Error';
            break;
        case 'warning':
            iconElement.className = 'fas fa-exclamation-triangle text-warning';
            document.getElementById('noticeModalLabel').textContent = 'Warning';
            break;
        default:
            iconElement.className = 'fas fa-bell text-warning';
            document.getElementById('noticeModalLabel').textContent = 'Notice';
    }
    
    modalInstance.show();
}

// Global functions for backward compatibility
window.openConfirmationModal = openConfirmationModal;
window.openAlertModal = openAlertModal;
window.openNoticeModal = openNoticeModal;
