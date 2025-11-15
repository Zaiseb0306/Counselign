document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    const appointmentDetailsModal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
    
    // Get DOM elements
    const loadingIndicator = document.getElementById('loadingIndicator');
    const noAppointmentsMessage = document.getElementById('noAppointmentsMessage');
    const appointmentsList = document.getElementById('appointmentsList');
    const statusFilter = document.getElementById('statusFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    
    // Status count elements
    const pendingCountEl = document.getElementById('pendingCount');
    const approvedCountEl = document.getElementById('approvedCount');
    const completedCountEl = document.getElementById('completedCount');
    const rejectedCountEl = document.getElementById('rejectedCount');
    const cancelledCountEl = document.getElementById('cancelledCount');
    
    // Global variable to store appointments data
    let appointments = [];
    // Current selected appointment ID
    let currentAppointmentId = null;
    
    // Load appointments data
    function loadAppointments() {
        SecureLogger.info('Loading appointments...');
        
        // Show loading indicator and hide appointments list
        if (loadingIndicator) {
            loadingIndicator.classList.remove('d-none');
        }
        if (appointmentsList) {
            appointmentsList.classList.add('d-none');
        }
        if (noAppointmentsMessage) {
            noAppointmentsMessage.classList.add('d-none');
        }

        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        fetch((window.BASE_URL || '/') + `admin/appointments/getAll?_=${timestamp}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .then(response => {
            SecureLogger.info('Response status:', response.status);
            if (response.status === 401) {
                // Session expired or unauthorized
                window.location.href = '/Counselign/auth';
                throw new Error('Session expired - Please log in again');
            }
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            SecureLogger.info('Received data:', data);
            
            if (data.status === 'error') {
                if (data.message && data.message.toLowerCase().includes('session')) {
                    window.location.href = '/Counselign/auth';
                    throw new Error(data.message);
                }
                throw new Error(data.message || 'Failed to load appointments');
            }

            // Store appointments data globally
            appointments = Array.isArray(data.appointments) ? data.appointments : [];
            
            // Update status counts
            updateStatusCounts(appointments);
            
            // Display appointments
            displayAppointments(appointments);
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'Failed to load appointments';
            
            // Check if error is session-related
            if (errorMessage.toLowerCase().includes('session') || 
                errorMessage.toLowerCase().includes('unauthorized') || 
                errorMessage.toLowerCase().includes('log in')) {
                window.location.href = '/Counselign/auth';
            } else {
                showToast('Error', errorMessage);
                if (noAppointmentsMessage) {
                    noAppointmentsMessage.textContent = errorMessage;
                    noAppointmentsMessage.classList.remove('d-none');
                }
                if (appointmentsList) {
                    appointmentsList.classList.add('d-none');
                }
            }
        })
        .finally(() => {
            // Hide loading indicator
            if (loadingIndicator) {
                loadingIndicator.classList.add('d-none');
            }
        });
    }
    
    // Reset status count displays
    function resetStatusCounts() {
        pendingCountEl.textContent = '-';
        approvedCountEl.textContent = '-';
        completedCountEl.textContent = '-';
        rejectedCountEl.textContent = '-';
        cancelledCountEl.textContent = '-';
    }
    
    // Update status count cards
    function updateStatusCounts(appointmentsData) {
        // Count appointments by status
        const counts = {
            pending: 0,
            approved: 0,
            completed: 0,
            rejected: 0,
            cancelled: 0
        };
        
        // Count each status
        appointmentsData.forEach(appointment => {
            if (counts.hasOwnProperty(appointment.status)) {
                counts[appointment.status]++;
            }
        });
        
        // Update the count displays with animation
        animateCount(pendingCountEl, counts.pending);
        animateCount(approvedCountEl, counts.approved);
        animateCount(completedCountEl, counts.completed);
        animateCount(rejectedCountEl, counts.rejected);
        animateCount(cancelledCountEl, counts.cancelled);
    }
    
    // Animate counting for better visual effect
    function animateCount(element, targetCount) {
        const duration = 1000; // Animation duration in ms
        const frameDuration = 1000/60; // 60fps
        const totalFrames = Math.round(duration / frameDuration);
        let frame = 0;
        
        const currentValue = 0;
        const increment = Math.max(1, Math.floor(targetCount / totalFrames));
        
        const counter = setInterval(() => {
            frame++;
            const progress = Math.min(frame / totalFrames, 1);
            const currentCount = Math.floor(progress * targetCount);
            
            element.textContent = currentCount;
            
            if (frame === totalFrames) {
                clearInterval(counter);
                element.textContent = targetCount;
            }
        }, frameDuration);
    }
    
    // Helper function to get Monday of current week
    function getMondayOfCurrentWeek() {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
        return new Date(today.setDate(diff));
    }
    
    // Check if a date is within the selected date range
    function isDateInRange(dateString, rangeType) {
        if (rangeType === 'all') {
            return true;
        }
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const appointmentDate = new Date(dateString);
        appointmentDate.setHours(0, 0, 0, 0);
        
        const timeDiff = appointmentDate.getTime() - today.getTime();
        const dayDiff = Math.floor(timeDiff / (1000 * 3600 * 24));
        
        switch (rangeType) {
            case 'today':
                return dayDiff === 0;
            case 'tomorrow':
                return dayDiff === 1;
            case 'week':
                // Get Monday of current week
                const monday = getMondayOfCurrentWeek();
                monday.setHours(0, 0, 0, 0);
                
                // Get end of week (Sunday)
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                sunday.setHours(23, 59, 59, 999);
                
                return appointmentDate >= monday && appointmentDate <= sunday;
            case 'month':
                const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                const daysLeftInMonth = endOfMonth.getDate() - today.getDate();
                return dayDiff >= 0 && dayDiff <= daysLeftInMonth;
            default:
                return true;
        }
    }
    
    // Display appointments based on filters
    function displayAppointments(appointmentsData) {
        const selectedStatus = statusFilter.value;
        const selectedDateRange = dateRangeFilter.value;
        
        // Filter appointments based on selected filters
        const filteredAppointments = appointmentsData.filter(app => {
            const statusMatch = selectedStatus === 'all' || app.status === selectedStatus;
            const dateMatch = isDateInRange(app.preferred_date, selectedDateRange);
            return statusMatch && dateMatch;
        });
        
        // Show no appointments message if no appointments found
        if (filteredAppointments.length === 0) {
            showNoAppointmentsMessage();
            return;
        }
        
        // Sort appointments based on status and timestamp
        // For pending appointments, sort by created_at (when received)
        // For approved/rejected/completed, sort by updated_at (when status was changed)
        const sortedAppointments = filteredAppointments.sort((a, b) => {
            // Determine which timestamp to use based on status
            const aTimestamp = a.status === 'pending' ? 
                new Date(a.created_at).getTime() : 
                new Date(a.updated_at || a.created_at).getTime();
            
            const bTimestamp = b.status === 'pending' ? 
                new Date(b.created_at).getTime() : 
                new Date(b.updated_at || b.created_at).getTime();
            
            // Sort in descending order (latest first)
            return bTimestamp - aTimestamp;
        });
        
        // Clear appointments list
        appointmentsList.innerHTML = '';
        
        // Create appointment cards for sorted appointments
        sortedAppointments.forEach(appointment => {
            const card = createAppointmentCard(appointment);
            appointmentsList.appendChild(card);
        });
        
        // Show appointments list
        appointmentsList.classList.remove('d-none');
        noAppointmentsMessage.classList.add('d-none');
    }
    
    // Create an appointment card element
    function createAppointmentCard(appointment) {
        const card = document.createElement('div');
        card.className = 'appointment-card';
        card.dataset.id = appointment.id;
        
        // Add status badge class
        card.classList.add(`status-${appointment.status}`);
        
        // Determine which timestamp to display based on status
        const timeLabel = appointment.status === 'pending' ? 'Received: ' : 'Updated: ';
        const timestamp = appointment.status === 'pending' ? 
            appointment.created_at : 
            (appointment.updated_at || appointment.created_at);
        
        // Create card content
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <p class="student-id mb-0">Student ID: ${appointment.student_id}</p>
                <span class="badge ${getStatusBadgeClass(appointment.status)}">${capitalizeFirstLetter(appointment.status)}</span>
            </div>
            <p class="date-time mb-1">Appointment Date: ${formatDate(appointment.preferred_date)}</p>
            <p class="date-time mb-0">Time: ${appointment.preferred_time}</p>
            <p class="timestamp text-muted mt-2 mb-0" style="font-size: 0.8rem;">${timeLabel}${formatDateTime(timestamp)}</p>
            <hr class="my-2">
            <button class="btn btn-sm btn-outline-primary view-details-btn w-100" data-id="${appointment.id}">
                View Details
            </button>
        `;
        
        // Add event listener to view details button
        card.querySelector('.view-details-btn').addEventListener('click', function() {
            openAppointmentDetails(appointment);
        });
        
        return card;
    }
    
    // Show no appointments message
    function showNoAppointmentsMessage() {
        appointmentsList.classList.add('d-none');
        noAppointmentsMessage.classList.remove('d-none');
        
        // Update message based on filter
        const selectedStatus = statusFilter.value;
        const selectedDateRange = dateRangeFilter.value;
        
        let message = 'No appointments found';
        
        if (selectedStatus !== 'all' || selectedDateRange !== 'all') {
            message += ' with the selected filters';
        }
        
        noAppointmentsMessage.querySelector('p').textContent = message + '.';
    }
    
    // Open appointment details modal
    function openAppointmentDetails(appointment) {
        const modal = document.getElementById('appointmentDetailsModal');
        if (!modal) return;

        // Update modal title
        modal.querySelector('.modal-title').textContent = `Appointment Details - ${appointment.student_id || 'N/A'}`;
        
        // Update modal content with existing elements
        const modalStudentId = modal.querySelector('#modalStudentId');
        if (modalStudentId) modalStudentId.textContent = appointment.student_id || 'N/A';
        
        const modalEmail = modal.querySelector('#modalEmail');
        if (modalEmail) modalEmail.textContent = appointment.user_email || 'N/A';
        const modalStudentName = modal.querySelector('#modalStudentName');
        if (modalStudentName) modalStudentName.textContent = appointment.student_name || appointment.username || 'N/A';
        
        const modalDate = modal.querySelector('#modalDate');
        if (modalDate) modalDate.textContent = formatDate(appointment.preferred_date) || 'N/A';
        
        const modalTime = modal.querySelector('#modalTime');
        if (modalTime) modalTime.textContent = appointment.preferred_time || 'N/A';
        
        // Consultation Type: Individual Consultation or Group Consultation
        const modalConsultationType = modal.querySelector('#modalConsultationType');
        if (modalConsultationType) {
            const consultationType = appointment.consultation_type;
            modalConsultationType.textContent = (consultationType && consultationType.trim() !== '') ? consultationType : 'N/A';
        }
        
        // Method Type: In-person, Online (Video), Online (Audio only)
        const modalMethodType = modal.querySelector('#modalMethodType');
        if (modalMethodType) {
            const methodType = appointment.method_type;
            modalMethodType.textContent = (methodType && methodType.trim() !== '') ? methodType : 'N/A';
        }
        
        // Purpose: Counseling, Psycho-Social Support, Initial Interview
        const modalPurpose = modal.querySelector('#modalPurpose');
        if (modalPurpose) {
            const purpose = appointment.purpose;
            modalPurpose.textContent = (purpose && purpose.trim() !== '') ? purpose : 'N/A';
        }
        
        const modalCounselorPreference = modal.querySelector('#modalCounselorPreference');
        if (modalCounselorPreference) modalCounselorPreference.textContent = appointment.counselor_name || 'No Preference';
        
        const modalStatus = modal.querySelector('#modalStatus');
        if (modalStatus) {
            modalStatus.textContent = capitalizeFirstLetter(appointment.status);
            modalStatus.className = `badge ${getStatusBadgeClass(appointment.status)}`;
        }
        
        const modalCreated = modal.querySelector('#modalCreated');
        if (modalCreated) modalCreated.textContent = formatDateTime(appointment.created_at) || 'N/A';
        
        const modalUpdated = modal.querySelector('#modalUpdated');
        if (modalUpdated) modalUpdated.textContent = formatDateTime(appointment.updated_at) || 'N/A';
        
        const modalDescription = modal.querySelector('#modalDescription');
        if (modalDescription) modalDescription.textContent = appointment.description || 'No description provided.';
        
        // Handle reason display for cancelled and rejected statuses
        const modalReasonContainer = modal.querySelector('#modalReasonContainer');
        const modalReason = modal.querySelector('#modalReason');
        
        if (modalReasonContainer && modalReason) {
            if (appointment.status === 'cancelled' || appointment.status === 'rejected') {
                modalReasonContainer.style.display = 'block';
                modalReason.textContent = appointment.reason || 'No reason provided.';
            } else {
                modalReasonContainer.style.display = 'none';
            }
        }
        
        const modalAppointmentId = modal.querySelector('#modalAppointmentId');
        if (modalAppointmentId) modalAppointmentId.value = appointment.id;
        
        // Update action buttons based on status
        updateModalButtons(modal, appointment.status);
        
        // Show the modal
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (!modalInstance) {
            new bootstrap.Modal(modal).show();
        } else {
            modalInstance.show();
        }
        
        // Store current appointment ID
        currentAppointmentId = appointment.id;
    }
    
    // Update modal buttons based on status
    function updateModalButtons(modal, status) {
        const modalFooter = modal.querySelector('.modal-footer');
        
        if (status === 'pending') {
            // For pending status, center the close button with approve/reject on sides
            if (modalFooter) {
                modalFooter.innerHTML = '';
                modalFooter.className = 'modal-footer';
                
                // Create a container div for the buttons
                const buttonsContainer = document.createElement('div');
                buttonsContainer.className = 'container-fluid px-4';
                
                // Create a row with three columns
                const row = document.createElement('div');
                row.className = 'row justify-content-center align-items-center';
                
                // Left column (Reject button)
                const leftCol = document.createElement('div');
                leftCol.className = 'col-3 text-end pe-2';
                const rejectButton = document.createElement('button');
                rejectButton.type = 'button';
                rejectButton.hidden = true;
                rejectButton.className = 'btn btn-danger';
                rejectButton.id = 'rejectAppointmentBtn';
                rejectButton.innerHTML = '<i class="fas fa-times me-1"></i> Reject';
                leftCol.appendChild(rejectButton);
                
                // Center column (Close button)
                const centerCol = document.createElement('div');
                centerCol.className = 'col-2 text-center px-0';
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn btn-secondary';
                closeButton.setAttribute('data-bs-dismiss', 'modal');
                closeButton.textContent = 'Close';
                centerCol.appendChild(closeButton);
                
                // Right column (Approve button)
                const rightCol = document.createElement('div');
                rightCol.className = 'col-3 text-start ps-2';
                const approveButton = document.createElement('button');
                approveButton.type = 'button';
                approveButton.hidden = true;
                approveButton.className = 'btn btn-primary';
                approveButton.id = 'approveAppointmentBtn';
                approveButton.innerHTML = '<i class="fas fa-check me-1"></i> Approve';
                rightCol.appendChild(approveButton);
                
                // Add columns to row
                row.appendChild(leftCol);
                row.appendChild(centerCol);
                row.appendChild(rightCol);
                
                // Add row to container
                buttonsContainer.appendChild(row);
                
                // Add container to footer
                modalFooter.appendChild(buttonsContainer);
                
                // Add event listeners using event delegation
                modalFooter.addEventListener('click', function(event) {
                    const target = event.target;
                    
                    if (target.id === 'rejectAppointmentBtn' || target.closest('#rejectAppointmentBtn')) {
                        updateAppointmentStatus(currentAppointmentId, 'rejected');
                    } else if (target.id === 'approveAppointmentBtn' || target.closest('#approveAppointmentBtn')) {
                        updateAppointmentStatus(currentAppointmentId, 'approved');
                    }
                });
            }
        } else {
            // For non-pending statuses, show status indicator and close button in a row
            if (modalFooter) {
                modalFooter.innerHTML = '';
                modalFooter.className = 'modal-footer d-flex justify-content-between align-items-center';
                
                // Create status indicator based on the status
                let statusClass, statusIcon, statusText;
                
                switch(status) {
                    case 'approved':
                        statusClass = 'bg-success';
                        statusIcon = 'check';
                        statusText = 'Approved';
                        break;
                    case 'rejected':
                        statusClass = 'bg-danger';
                        statusIcon = 'times';
                        statusText = 'Rejected';
                        break;
                    case 'completed':
                        statusClass = 'bg-primary';
                        statusIcon = 'check-double';
                        statusText = 'Completed';
                        break;
                    default:
                        statusClass = 'bg-secondary';
                        statusIcon = 'info-circle';
                        statusText = 'Cancelled';
                }
                
                // Create status indicator element
                const statusIndicator = document.createElement('div');
                statusIndicator.className = `status-indicator d-inline-flex align-items-center ${statusClass} text-white px-3 py-2 rounded`;
                statusIndicator.innerHTML = `
                    <i class="fas fa-${statusIcon} me-2"></i>
                    <span>This appointment has been ${statusText.toLowerCase()}</span>
                `;
                
                // Create close button
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn btn-secondary ms-3';
                closeButton.setAttribute('data-bs-dismiss', 'modal');
                closeButton.textContent = 'Close';
                
                // Add elements to footer
                modalFooter.appendChild(statusIndicator);
                modalFooter.appendChild(closeButton);
            }
        }
    }
    
    // Update appointment status
    function updateAppointmentStatus(appointmentId, newStatus, rejectionReason = null) {
        SecureLogger.info('Updating appointment status:', { appointmentId, newStatus, rejectionReason });
        
        // Get the current buttons from the modal
        const currentApproveBtn = document.getElementById('approveAppointmentBtn');
        const currentRejectBtn = document.getElementById('rejectAppointmentBtn');
        
        // Disable buttons if they exist
        if (currentApproveBtn) currentApproveBtn.disabled = true;
        if (currentRejectBtn) currentRejectBtn.disabled = true;
        
        // Show loading state
        if (newStatus === 'approved' && currentApproveBtn) {
            currentApproveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        } else if (newStatus === 'rejected' && currentRejectBtn) {
            currentRejectBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('status', newStatus);
        
        // If rejecting, add the rejection reason
        if (newStatus === 'rejected' && rejectionReason) {
            formData.append('rejection_reason', rejectionReason);
        }
        
        SecureLogger.info('Sending request to update status...');
        
        // Send request to update appointment status
        fetch((window.BASE_URL || '/') + 'admin/appointments/updateAppointmentStatus', {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            SecureLogger.info('Response received:', response);
            if (!response.ok) {
                if (response.status === 401) {
                    window.location.href = 'auth/logout';
                    throw new Error('Your session has expired. Please log in again.');
                }
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            SecureLogger.info('Response data:', data);
            if (data.status === 'success') {
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                const successTitle = document.getElementById('successModalTitle');
                const successBody = document.getElementById('successModalBody');
                const modalHeader = document.querySelector('#successModal .modal-header');
                const closeBtn = modalHeader.querySelector('.btn-close');
                const okBtn = document.querySelector('#successModal .modal-footer .btn');
                
                // Set colors based on action
                if (newStatus === 'approved') {
                    modalHeader.className = 'modal-header bg-success text-white';
                    closeBtn.className = 'btn-close btn-close-white';
                    okBtn.className = 'btn btn-success';
                } else {
                    modalHeader.className = 'modal-header bg-danger text-white';
                    closeBtn.className = 'btn-close btn-close-white';
                    okBtn.className = 'btn btn-danger';
                }
                
                successTitle.textContent = newStatus === 'approved' ? 'Appointment Approved' : 'Appointment Rejected';
                successBody.innerHTML = `
                    <p>The appointment has been ${newStatus === 'approved' ? 'approved' : 'rejected'} successfully.</p>
                    <p class="text-muted">An email notification has been sent to the student.</p>
                `;
                
                // Add event listener for modal close
                const successModalElement = document.getElementById('successModal');
                successModalElement.addEventListener('hidden.bs.modal', function () {
                    // Reload the page after modal is closed
                    window.location.reload();
                });
                
                successModal.show();
                
                // Update local data
                const appointmentIndex = appointments.findIndex(app => app.id === appointmentId);
                if (appointmentIndex !== -1) {
                    appointments[appointmentIndex].status = newStatus;
                    
                    // If approved, save to localStorage for scheduled appointments
                    if (newStatus === 'approved') {
                        saveToScheduledAppointments(appointments[appointmentIndex]);
                    }
                    
                    // Remove appointment card from view if it's now rejected or approved
                    const card = document.querySelector(`.appointment-card[data-id="${appointmentId}"]`);
                    if (card && (newStatus === 'rejected' || newStatus === 'approved')) {
                        card.classList.add('fade-out');
                        setTimeout(() => {
                            card.remove();
                            
                            // Check if there are no more cards
                            if (appointmentsList.children.length === 0) {
                                showNoAppointmentsMessage();
                            }
                        }, 500);
                    }
                    
                    // Update status counts
                    updateStatusCounts(appointments);
                }
                
                // Close appointment details modal
                const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
                if (appointmentModal) {
                    appointmentModal.hide();
                }
            } else {
                // Show error message
                showToast(data.message || 'Failed to update appointment status.', 'error');
                if (data.message && data.message.includes('session')) {
                    setTimeout(() => {
                        window.location.href = 'auth/logout';
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'An error occurred. Please try again.';
            showToast(errorMessage, 'error');
            
            if (errorMessage.toLowerCase().includes('session') || 
                errorMessage.toLowerCase().includes('log in') || 
                errorMessage.toLowerCase().includes('unauthorized')) {
                SecureLogger.info('Session error detected, redirecting to login...');
                showToast('Your session has expired. Redirecting to login...', 'error');
                setTimeout(() => {
                    window.location.href = 'auth/logout';
                }, 2000);
            }
        })
        .finally(() => {
            // Re-enable buttons and restore text if they still exist
            if (currentApproveBtn) {
                currentApproveBtn.disabled = false;
                currentApproveBtn.innerHTML = '<i class="fas fa-check me-1"></i> Approve';
            }
            if (currentRejectBtn) {
                currentRejectBtn.disabled = false;
                currentRejectBtn.innerHTML = '<i class="fas fa-times me-1"></i> Reject';
            }
        });
    }
    
    /**
     * Save approved appointment to localStorage for scheduled appointments page
     */
    function saveToScheduledAppointments(appointment) {
        try {
            SecureLogger.info('Saving appointment to scheduled appointments:', appointment);
            
        // Get existing scheduled appointments from localStorage
            let scheduledAppointments = [];
            try {
                const stored = localStorage.getItem('scheduledAppointments');
                scheduledAppointments = stored ? JSON.parse(stored) : [];
                if (!Array.isArray(scheduledAppointments)) {
                    scheduledAppointments = [];
                }
            } catch (e) {
                console.error('Error parsing stored appointments:', e);
                scheduledAppointments = [];
            }
            
            // Format the appointment data
        const scheduledAppointment = {
            id: appointment.id,
                student_id: appointment.student_id || '',
                name: appointment.username || `Student ${appointment.student_id}`,
                role: appointment.method_type || 'Online Consultation',
                appointed_date: appointment.preferred_date,
                time: appointment.preferred_time || '',
                appointed_counselor: appointment.counselorPreference || 'Any Available',
                status: 'APPROVED',
                action: 'pending',
                timestamp: new Date().toISOString()
            };
            
            SecureLogger.info('Formatted appointment:', scheduledAppointment);
            
            // Remove any existing entry for this appointment
            scheduledAppointments = scheduledAppointments.filter(app => app.id !== scheduledAppointment.id);
            
            // Add the new appointment
            scheduledAppointments.push(scheduledAppointment);
            
            // Sort by date and time
            scheduledAppointments.sort((a, b) => {
                const dateA = new Date(a.appointed_date + ' ' + a.time);
                const dateB = new Date(b.appointed_date + ' ' + b.time);
                return dateA - dateB;
            });
            
            // Save to localStorage
            try {
                localStorage.setItem('scheduledAppointments', JSON.stringify(scheduledAppointments));
                SecureLogger.info('Successfully saved to localStorage');
                
                // Also save to sessionStorage as backup
                sessionStorage.setItem('scheduledAppointments', JSON.stringify(scheduledAppointments));
                
                // Dispatch a custom event to notify other pages
                const event = new CustomEvent('scheduledAppointmentsUpdated', {
                    detail: { appointments: scheduledAppointments }
                });
                window.dispatchEvent(event);
                
                showToast('Appointment successfully added to scheduled consultations!', 'success');
            } catch (e) {
                console.error('Error saving to storage:', e);
                throw new Error('Failed to save appointment data');
            }
        } catch (error) {
            console.error('Error in saveToScheduledAppointments:', error);
            showToast('Error saving appointment data. Please try again.', 'error');
        }
    }
    
    // Create a toast notification
    function showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        // Add to container
        toastContainer.appendChild(toastEl);
        
        // Initialize and show toast
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        // Remove toast after it's hidden
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
    
    // Helper functions
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }
    
    function formatDateTime(dateTimeString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateTimeString).toLocaleString(undefined, options);
    }
    
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'pending':
                return 'bg-warning text-dark';
            case 'approved':
                return 'bg-success';
            case 'rejected':
                return 'bg-danger';
            case 'completed':
                return 'bg-info';
            case 'cancelled':
                return 'bg-secondary';
            default:
                return 'bg-secondary';
        }
    }
    
    // Filter functions
    function filterAppointments() {
        const dateFilter = document.getElementById('dateRangeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const now = new Date();
        
        return appointments.filter(appointment => {
            const appointmentDate = new Date(appointment.preferred_date);
            const meetsDateFilter = (() => {
                switch(dateFilter) {
                    case 'all':
                        return true;
                    case 'today':
                        return isSameDay(appointmentDate, now);
                    case 'past':
                        return appointmentDate < now;
                    case 'upcoming':
                        return appointmentDate > now;
                    case 'thisWeek':
                        return isThisWeek(appointmentDate);
                    case 'nextWeek':
                        return isNextWeek(appointmentDate);
                    case 'nextMonth':
                        return isNextMonth(appointmentDate);
                    default:
                        return true;
                }
            })();

            const meetsStatusFilter = statusFilter === 'all' || 
                                   appointment.status.toLowerCase() === statusFilter.toLowerCase();

            return meetsDateFilter && meetsStatusFilter;
        });
    }

    // Helper functions for date filtering
    function isSameDay(date1, date2) {
        return date1.getDate() === date2.getDate() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getFullYear() === date2.getFullYear();
    }

    function isThisWeek(date) {
        const now = new Date();
        
        // Get Monday of current week
        const weekStart = getMondayOfCurrentWeek();
        weekStart.setHours(0, 0, 0, 0);
        
        // Get end of week (Sunday)
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);
        weekEnd.setHours(23, 59, 59, 999);
        
        // Create a new date object for comparison and set time to midnight
        const compareDate = new Date(date);
        compareDate.setHours(0, 0, 0, 0);
        
        return compareDate >= weekStart && compareDate <= weekEnd;
    }

    function isNextWeek(date) {
        const now = new Date();
        const nextWeekStart = new Date(now);
        nextWeekStart.setDate(now.getDate() - now.getDay() + 7); // Start of next week
        const nextWeekEnd = new Date(nextWeekStart);
        nextWeekEnd.setDate(nextWeekStart.getDate() + 6); // End of next week
        
        return date >= nextWeekStart && date <= nextWeekEnd;
    }

    function isNextMonth(date) {
        const now = new Date();
        const nextMonthStart = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        const nextMonthEnd = new Date(now.getFullYear(), now.getMonth() + 2, 0);
        
        return date >= nextMonthStart && date <= nextMonthEnd;
    }

    // Add event listeners for filters
    document.getElementById('dateRangeFilter').addEventListener('change', function() {
        const filteredAppointments = filterAppointments();
        displayAppointments(filteredAppointments);
    });

    document.getElementById('statusFilter').addEventListener('change', function() {
        const filteredAppointments = filterAppointments();
        displayAppointments(filteredAppointments);
    });

    // Add event delegation for modal buttons
    document.getElementById('appointmentDetailsModal').addEventListener('click', function(event) {
        const target = event.target;
        
        // Check if the clicked element is one of our action buttons
        if (target.id === 'approveAppointmentBtn' || target.closest('#approveAppointmentBtn')) {
            SecureLogger.info('Approve button clicked');
            if (currentAppointmentId) {
                // Show confirmation modal for approval
                const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
                const modalTitle = document.getElementById('confirmationModalTitle');
                const modalBody = document.getElementById('confirmationModalBody');
                const confirmBtn = document.getElementById('confirmActionBtn');
                const modalHeader = document.querySelector('#confirmationModal .modal-header');
                
                // Set green background for approval
                modalHeader.className = 'modal-header bg-success text-white';
                modalTitle.textContent = 'Confirm Approval';
                modalBody.innerHTML = `
                    <p>Are you sure you want to approve this appointment?</p>
                    <p class="text-muted">This action will notify the student via email.</p>
                `;
                confirmBtn.className = 'btn btn-success';
                confirmBtn.innerHTML = '<i class="fas fa-check me-1"></i> Confirm Approval';
                
                // Store the action type in the modal's dataset
                document.getElementById('confirmationModal').dataset.action = 'approve';
                
                confirmationModal.show();
            }
        } else if (target.id === 'rejectAppointmentBtn' || target.closest('#rejectAppointmentBtn')) {
            SecureLogger.info('Reject button clicked');
            if (currentAppointmentId) {
                // Close the appointment details modal first
                const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
                if (appointmentModal) {
                    appointmentModal.hide();
                }
                
                // Wait for the appointment modal to close before showing rejection modal
                setTimeout(() => {
                    // Show rejection reason modal with backdrop: 'static' and keyboard: false
                    const rejectionModal = new bootstrap.Modal(document.getElementById('rejectionReasonModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    rejectionModal.show();
                }, 300); // Wait for the fade-out animation to complete
            }
        } 
    });

    // Add event listener for confirmation modal
    document.getElementById('confirmActionBtn').addEventListener('click', function() {
        const action = document.getElementById('confirmationModal').dataset.action;
        const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        
        if (action === 'approve') {
            updateAppointmentStatus(currentAppointmentId, 'approved');
        }
        
        // Hide the confirmation modal
        if (confirmationModal) {
            confirmationModal.hide();
        }
    });

    // Add event listener for rejection confirmation
    document.getElementById('confirmRejectionBtn').addEventListener('click', function() {
        const rejectionReason = document.getElementById('rejectionReason').value.trim();
        if (!rejectionReason) {
            showToast('Please provide a reason for rejection.', 'error');
            return;
        }
        
        // Show confirmation modal for rejection
        const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        const modalTitle = document.getElementById('confirmationModalTitle');
        const modalBody = document.getElementById('confirmationModalBody');
        const confirmBtn = document.getElementById('confirmActionBtn');
        const modalHeader = document.querySelector('#confirmationModal .modal-header');
        const closeBtn = modalHeader.querySelector('.btn-close');
        
        // Set red background for rejection and update close button
        modalHeader.className = 'modal-header bg-danger text-white';
        closeBtn.className = 'btn-close btn-close-white';
        modalTitle.textContent = 'Confirm Rejection';
        modalBody.innerHTML = `
            <p>Are you sure you want to reject this appointment?</p>
            <p class="text-muted">Reason: ${rejectionReason}</p>
            <p class="text-muted">This action will notify the student via email.</p>
        `;
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fas fa-times me-1"></i> Confirm Rejection';
        
        // Store the action type and reason in the modal's dataset
        document.getElementById('confirmationModal').dataset.action = 'reject';
        document.getElementById('confirmationModal').dataset.reason = rejectionReason;
        
        // Hide the rejection reason modal
        const rejectionModal = bootstrap.Modal.getInstance(document.getElementById('rejectionReasonModal'));
        if (rejectionModal) {
            rejectionModal.hide();
        }
        
        // Show the confirmation modal
        confirmationModal.show();
    });

    // Update the confirmation modal event listener to handle both approve and reject
    document.getElementById('confirmActionBtn').addEventListener('click', function() {
        const action = document.getElementById('confirmationModal').dataset.action;
        const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        
        if (action === 'approve') {
            updateAppointmentStatus(currentAppointmentId, 'approved');
        } else if (action === 'reject') {
            const rejectionReason = document.getElementById('confirmationModal').dataset.reason;
            updateAppointmentStatus(currentAppointmentId, 'rejected', rejectionReason);
        }
        
        // Hide the confirmation modal
        if (confirmationModal) {
            confirmationModal.hide();
        }
    });

    // Reset rejection reason when modal is closed
    document.getElementById('rejectionReasonModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('rejectionReason').value = '';
    });
    
    // Add CSS for fade-out animation
    const style = document.createElement('style');
    style.textContent = `
        .fade-out {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
    `;
    document.head.appendChild(style);
    
    // Initial load
    loadAppointments();
}); 