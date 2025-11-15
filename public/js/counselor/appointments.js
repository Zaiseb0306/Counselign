// Duplicated from admin with counselor endpoints
document.addEventListener('DOMContentLoaded', function() {
    const appointmentDetailsModal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
    const loadingIndicator = document.getElementById('loadingIndicator');
    const noAppointmentsMessage = document.getElementById('noAppointmentsMessage');
    const appointmentsList = document.getElementById('appointmentsList');
    const statusFilter = document.getElementById('statusFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');

    const pendingCountEl = document.getElementById('pendingCount');
    const approvedCountEl = document.getElementById('approvedCount');
    const completedCountEl = document.getElementById('completedCount');
    const rejectedCountEl = document.getElementById('rejectedCount');
    const cancelledCountEl = document.getElementById('cancelledCount');

    let appointments = [];
    let currentAppointmentId = null;

    function loadAppointments() {
        const timestamp = new Date().getTime();
        fetch((window.BASE_URL || '/') + `counselor/appointments/getAll?_=${timestamp}`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
        })
        .then(r => { if (!r.ok) throw new Error('Failed'); return r.json(); })
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message || 'Failed');
            appointments = Array.isArray(data.appointments) ? data.appointments : [];
            updateStatusCounts(appointments);
            displayAppointments(appointments);
        })
        .catch(err => {
            if (noAppointmentsMessage) {
                noAppointmentsMessage.textContent = err.message || 'Failed to load appointments';
                noAppointmentsMessage.classList.remove('d-none');
            }
            if (appointmentsList) appointmentsList.classList.add('d-none');
        })
        .finally(() => { if (loadingIndicator) loadingIndicator.classList.add('d-none'); });
    }

    function updateStatusCounts(data) {
        const counts = { pending: 0, approved: 0, completed: 0, rejected: 0, cancelled: 0 };
        data.forEach(a => { if (counts.hasOwnProperty(a.status)) counts[a.status]++; });
        pendingCountEl.textContent = counts.pending;
        approvedCountEl.textContent = counts.approved;
        completedCountEl.textContent = counts.completed;
        rejectedCountEl.textContent = counts.rejected;
        cancelledCountEl.textContent = counts.cancelled;
    }

    function isDateInRange(dateString, rangeType) {
        if (rangeType === 'all') return true;
        const today = new Date(); today.setHours(0,0,0,0);
        const d = new Date(dateString); d.setHours(0,0,0,0);
        const diff = Math.floor((d.getTime()-today.getTime())/(1000*3600*24));
        switch(rangeType){
            case 'today': return diff === 0;
            case 'thisWeek':
                const monday = new Date(today); monday.setDate(today.getDate() - (today.getDay()===0?6:today.getDay()-1));
                const sunday = new Date(monday); sunday.setDate(monday.getDate()+6); sunday.setHours(23,59,59,999);
                return d>=monday && d<=sunday;
            case 'nextWeek':
                const nextStart = new Date(today); nextStart.setDate(today.getDate() - (today.getDay()===0?6:today.getDay()-1) + 7);
                const nextEnd = new Date(nextStart); nextEnd.setDate(nextStart.getDate()+6); nextEnd.setHours(23,59,59,999);
                return d>=nextStart && d<=nextEnd;
            case 'nextMonth':
                const ns = new Date(today.getFullYear(), today.getMonth()+1, 1);
                const ne = new Date(today.getFullYear(), today.getMonth()+2, 0, 23,59,59,999);
                return d>=ns && d<=ne;
            case 'past': return d < today;
            default: return true;
        }
    }

    function displayAppointments(data) {
        const selectedStatus = statusFilter.value;
        const selectedDateRange = dateRangeFilter.value;
        const filtered = data.filter(app => {
            const statusMatch = selectedStatus === 'all' || app.status === selectedStatus;
            const dateMatch = isDateInRange(app.preferred_date, selectedDateRange);
            return statusMatch && dateMatch;
        });
        if (filtered.length === 0) { showNoAppointmentsMessage(); return; }
        appointmentsList.innerHTML = '';
        filtered.sort((a,b)=>{
            const at = a.status==='pending'? new Date(a.created_at).getTime(): new Date(a.updated_at||a.created_at).getTime();
            const bt = b.status==='pending'? new Date(b.created_at).getTime(): new Date(b.updated_at||b.created_at).getTime();
            return bt-at;
        }).forEach(app=>appointmentsList.appendChild(createAppointmentCard(app)));
        appointmentsList.classList.remove('d-none');
        noAppointmentsMessage.classList.add('d-none');
    }

    function createAppointmentCard(appointment){
        const card = document.createElement('div');
        card.className = 'appointment-card';
        card.dataset.id = appointment.id;
        card.classList.add(`status-${appointment.status}`);
        const timeLabel = appointment.status==='pending' ? 'Received: ' : 'Updated: ';
        const ts = appointment.status==='pending' ? appointment.created_at : (appointment.updated_at||appointment.created_at);
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <p class="student-id mb-0">Student ID: ${appointment.student_id}</p>
                <span class="badge ${getStatusBadgeClass(appointment.status)}">${capitalizeFirstLetter(appointment.status)}</span>
            </div>
            <p class="date-time mb-1">Appointment Date: ${formatDate(appointment.preferred_date)}</p>
            <p class="date-time mb-0">Time: ${appointment.preferred_time}</p>
            <p class="timestamp text-muted mt-2 mb-0" style="font-size: 0.8rem;">${timeLabel}${formatDateTime(ts)}</p>
            <hr class="my-2">
            <button class="btn btn-sm btn-outline-primary view-details-btn w-100" data-id="${appointment.id}">View Details</button>
        `;
        card.querySelector('.view-details-btn').addEventListener('click', function(){ openAppointmentDetails(appointment); });
        return card;
    }

    function showNoAppointmentsMessage(){
        appointmentsList.classList.add('d-none');
        noAppointmentsMessage.classList.remove('d-none');
        const selectedStatus = statusFilter.value; const selectedDateRange = dateRangeFilter.value;
        let message = 'No appointments found';
        if (selectedStatus !== 'all' || selectedDateRange !== 'all') { message += ' with the selected filters'; }
        noAppointmentsMessage.querySelector('p').textContent = message + '.';
    }

    function openAppointmentDetails(appointment){
        const modal = document.getElementById('appointmentDetailsModal');
        modal.querySelector('.modal-title').textContent = `Appointment Details - ${appointment.student_id || 'N/A'}`;
        
        const set = (sel, val) => { const el = modal.querySelector(sel); if (el) el.textContent = val || 'N/A'; };
        set('#modalStudentId', appointment.student_id);
        set('#modalEmail', appointment.user_email);
        set('#modalStudentName', appointment.student_name || appointment.username);
        set('#modalDate', formatDate(appointment.preferred_date));
        set('#modalTime', appointment.preferred_time);
        
        // Consultation Type: Individual Consultation or Group Consultation
        const consultationType = appointment.consultation_type;
        set('#modalConsultationType', (consultationType && consultationType.trim() !== '') ? consultationType : null);
        
        // Method Type: In-person, Online (Video), Online (Audio only)
        const methodType = appointment.method_type;
        set('#modalMethodType', (methodType && methodType.trim() !== '') ? methodType : null);
        
        // Purpose: Counseling, Psycho-Social Support, Initial Interview
        const purpose = appointment.purpose;
        set('#modalPurpose', (purpose && purpose.trim() !== '') ? purpose : null);
        set('#modalCounselorPreference', appointment.counselor_name || 'No Preference');
        const modalStatus = modal.querySelector('#modalStatus');
        if (modalStatus) { modalStatus.textContent = capitalizeFirstLetter(appointment.status); modalStatus.className = `badge ${getStatusBadgeClass(appointment.status)}`; }
        set('#modalCreated', formatDateTime(appointment.created_at));
        set('#modalUpdated', formatDateTime(appointment.updated_at));
        const modalDescription = modal.querySelector('#modalDescription'); if (modalDescription) modalDescription.textContent = appointment.description || 'No description provided.';
        const reasonContainer = modal.querySelector('#modalReasonContainer'); const reasonEl = modal.querySelector('#modalReason');
        if (reasonContainer && reasonEl) { if (['cancelled','rejected'].includes(appointment.status)) { reasonContainer.style.display='block'; reasonEl.textContent = appointment.reason || 'No reason provided.'; } else { reasonContainer.style.display='none'; } }
        const idEl = modal.querySelector('#modalAppointmentId'); if (idEl) idEl.value = appointment.id;
        updateModalButtons(modal, appointment.status);
        (bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)).show();
        currentAppointmentId = appointment.id;
    }

    function updateModalButtons(modal, status){
        const modalFooter = modal.querySelector('.modal-footer');
        if (status === 'pending'){
            modalFooter.innerHTML = '';
            modalFooter.className = 'modal-footer';
            const container = document.createElement('div'); container.className = 'container-fluid px-4';
            const row = document.createElement('div'); row.className = 'row justify-content-center align-items-center';
            const leftCol = document.createElement('div'); leftCol.className = 'col-3 text-end pe-2';
            const rejectBtn = document.createElement('button'); rejectBtn.type='button'; rejectBtn.className='btn btn-danger'; rejectBtn.id='rejectAppointmentBtn'; rejectBtn.innerHTML='<i class="fas fa-times me-1"></i> Reject'; leftCol.appendChild(rejectBtn);
            const centerCol=document.createElement('div'); centerCol.className='col-2 text-center px-0'; const closeBtn=document.createElement('button'); closeBtn.type='button'; closeBtn.className='btn btn-secondary'; closeBtn.setAttribute('data-bs-dismiss','modal'); closeBtn.textContent='Close'; centerCol.appendChild(closeBtn);
            const rightCol=document.createElement('div'); rightCol.className='col-3 text-start ps-2'; const approveBtn=document.createElement('button'); approveBtn.type='button'; approveBtn.className='btn btn-primary'; approveBtn.id='approveAppointmentBtn'; approveBtn.innerHTML='<i class="fas fa-check me-1"></i> Approve'; rightCol.appendChild(approveBtn);
            row.appendChild(leftCol); row.appendChild(centerCol); row.appendChild(rightCol); container.appendChild(row); modalFooter.appendChild(container);
        } else {
            modalFooter.innerHTML = '';
            modalFooter.className = 'modal-footer d-flex justify-content-between align-items-center';
            let statusClass, statusIcon, statusText;
            switch(status){
                case 'approved': statusClass='bg-success'; statusIcon='check'; statusText='Approved'; break;
                case 'rejected': statusClass='bg-danger'; statusIcon='times'; statusText='Rejected'; break;
                case 'completed': statusClass='bg-primary'; statusIcon='check-double'; statusText='Completed'; break;
                default: statusClass='bg-secondary'; statusIcon='info-circle'; statusText='Cancelled';
            }
            const statusIndicator=document.createElement('div'); statusIndicator.className=`status-indicator d-inline-flex align-items-center ${statusClass} text-white px-3 py-2 rounded`; statusIndicator.innerHTML=`<i class="fas fa-${statusIcon} me-2"></i><span>This appointment has been ${statusText.toLowerCase()}</span>`;
            const closeButton=document.createElement('button'); closeButton.type='button'; closeButton.className='btn btn-secondary ms-3'; closeButton.setAttribute('data-bs-dismiss','modal'); closeButton.textContent='Close';
            modalFooter.appendChild(statusIndicator); modalFooter.appendChild(closeButton);
        }
    }

    async function updateAppointmentStatus(appointmentId, newStatus, rejectionReason = null){
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('status', newStatus);
        if (newStatus === 'rejected' && rejectionReason) formData.append('rejection_reason', rejectionReason);
        
        try {
            const response = await fetch((window.BASE_URL || '/') + 'counselor/appointments/updateAppointmentStatus', {
                method: 'POST', body: formData, credentials: 'include', headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error('Server error');
            
            const data = await response.json();
            
            if (data.status === 'success') {
                window.location.reload();
            } else {
                throw new Error(data.message || 'Failed to update');
            }
        } catch (error) {
            alert(error.message || 'Error');
            throw error; // Re-throw to trigger catch in calling functions
        }
    }

    // Loading button utility functions
    function showButtonLoading(button, loadingText) {
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
    }

    function hideButtonLoading(button, originalText) {
        button.disabled = false;
        button.innerHTML = `<i class="fas fa-check me-1"></i>${originalText}`;
    }

    function formatDate(d){ return new Date(d).toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'}); }
    function formatDateTime(dt){ return new Date(dt).toLocaleString(undefined,{year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'}); }
    function capitalizeFirstLetter(s){ return s.charAt(0).toUpperCase()+s.slice(1); }
    function getStatusBadgeClass(status){ switch(status){ case 'pending': return 'bg-warning text-dark'; case 'approved': return 'bg-success'; case 'rejected': return 'bg-danger'; case 'completed': return 'bg-info'; case 'cancelled': return 'bg-secondary'; default: return 'bg-secondary'; } }

    // Filter event listeners
    document.getElementById('dateRangeFilter').addEventListener('change', function(){ displayAppointments(appointments); });
    document.getElementById('statusFilter').addEventListener('change', function(){ displayAppointments(appointments); });

    // Appointment details modal event delegation for approve/reject buttons
    document.getElementById('appointmentDetailsModal').addEventListener('click', function(event){
        const target = event.target;
        if (target.id === 'approveAppointmentBtn' || target.closest('#approveAppointmentBtn')) {
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            document.getElementById('confirmationModalTitle').textContent = 'Confirm Approval';
            document.getElementById('confirmationModalBody').innerHTML = '<p>Are you sure you want to approve this appointment?</p><p class="text-muted">This action will notify the student via email.</p>';
            const confirmBtn = document.getElementById('confirmActionBtn');
            confirmBtn.className = 'btn btn-success';
            confirmBtn.innerHTML = '<i class="fas fa-check me-1"></i>Confirm';
            document.getElementById('confirmationModal').dataset.action = 'approve';
            confirmationModal.show();
        } else if (target.id === 'rejectAppointmentBtn' || target.closest('#rejectAppointmentBtn')) {
            const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal')); 
            if (appointmentModal) appointmentModal.hide();
            setTimeout(()=>{ 
                new bootstrap.Modal(document.getElementById('rejectionReasonModal'), { backdrop:'static', keyboard:false }).show(); 
            }, 300);
        }
    });

    // SINGLE event listener for confirmation action button
    document.getElementById('confirmActionBtn').addEventListener('click', function(){
        const action = document.getElementById('confirmationModal').dataset.action;
        const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        const confirmBtn = document.getElementById('confirmActionBtn');
        
        // Prevent double-clicking
        if (confirmBtn.disabled) return;
        
        // Show loading state
        showButtonLoading(confirmBtn, 'Processing...');
        
        if (action === 'approve') { 
            updateAppointmentStatus(currentAppointmentId, 'approved')
                .then(() => {
                    if (confirmationModal) confirmationModal.hide();
                })
                .catch(() => {
                    hideButtonLoading(confirmBtn, 'Confirm');
                });
        }
        else if (action === 'reject') { 
            const reason = document.getElementById('confirmationModal').dataset.reason; 
            updateAppointmentStatus(currentAppointmentId, 'rejected', reason)
                .then(() => {
                    if (confirmationModal) confirmationModal.hide();
                })
                .catch(() => {
                    hideButtonLoading(confirmBtn, 'Confirm');
                });
        }
    });

    // Rejection reason confirmation handler
    document.getElementById('confirmRejectionBtn').addEventListener('click', function(){
        const rejectionReason = document.getElementById('rejectionReason').value.trim();
        if (!rejectionReason) { 
            alert('Please provide a reason for rejection.'); 
            return; 
        }
        
        const confirmRejectionBtn = document.getElementById('confirmRejectionBtn');
        showButtonLoading(confirmRejectionBtn, 'Processing...');
        
        // Setup confirmation modal for rejection
        const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        document.querySelector('#confirmationModal .modal-header').className = 'modal-header bg-danger text-white';
        document.getElementById('confirmationModalTitle').textContent = 'Confirm Rejection';
        document.getElementById('confirmationModalBody').innerHTML = `<p>Are you sure you want to reject this appointment?</p><p class="text-muted">Reason: ${rejectionReason}</p><p class="text-muted">This action will notify the student via email.</p>`;
        const confirmBtn = document.getElementById('confirmActionBtn');
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fas fa-times me-1"></i>Confirm Rejection';
        document.getElementById('confirmationModal').dataset.action = 'reject';
        document.getElementById('confirmationModal').dataset.reason = rejectionReason;
        
        // Hide rejection reason modal and show confirmation modal
        const rejectionModal = bootstrap.Modal.getInstance(document.getElementById('rejectionReasonModal')); 
        if (rejectionModal) rejectionModal.hide();
        confirmationModal.show();
        
        // Reset button state
        hideButtonLoading(confirmRejectionBtn, 'Confirm Rejection');
    });

    // Reset rejection reason when modal is hidden
    document.getElementById('rejectionReasonModal').addEventListener('hidden.bs.modal', function(){ 
        document.getElementById('rejectionReason').value = ''; 
    });

    // Load appointments on page load
    loadAppointments();
});