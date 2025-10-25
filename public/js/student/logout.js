document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('studentLogoutModal')) {
      const modalHtml = `
<div class="modal fade" id="studentLogoutModal" tabindex="-1" aria-labelledby="studentLogoutModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="studentLogoutModalLabel">Confirm Logout</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      Are you sure you want to log out?
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-danger" id="studentLogoutConfirmBtn">
        <i class="fas fa-sign-out-alt me-1"></i> Log Out
      </button>
    </div>
  </div>
</div>
</div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      const confirmBtn = document.getElementById('studentLogoutConfirmBtn');
      if (confirmBtn) {
          confirmBtn.addEventListener('click', function () {
              window.location.href = (window.BASE_URL || '/') + 'auth/logout';
          });
      }
  }
});

// Expose a consistent function for use in links/buttons
window.confirmLogout = function confirmLogout() {
  const el = document.getElementById('studentLogoutModal');
  if (!el) return;
  const instance = bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: false });
  instance.show();
};


