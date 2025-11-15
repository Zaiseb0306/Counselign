// Add click event to open conversation
messageContent.addEventListener('click', function () {
    const userId = this.dataset.userId;
    SecureLogger.info('Message clicked, redirecting to conversation with user:', userId);
    // Store the user ID for highlighting the student card
    localStorage.setItem('highlightStudentCard', userId);
    // Redirect to messages page
    window.location.href = (window.BASE_URL || '/') + 'admin/messages';
}); 