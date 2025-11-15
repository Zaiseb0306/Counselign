function resolveImageUrl(path) {
  if (!path) return (window.BASE_URL || "/") + "Photos/profile.png";
  const trimmed = String(path).trim();
  if (/^https?:\/\//i.test(trimmed)) return trimmed;
  if (trimmed.startsWith("/")) return trimmed;
  return (window.BASE_URL || "/") + trimmed;
}

// Calculate online status based on last_activity, last_login, and logout_time
function calculateOnlineStatus(lastActivity, lastLogin, logoutTime) {
  // Get all available times
  const activityTime = lastActivity ? new Date(lastActivity) : null;
  const loginTime = lastLogin ? new Date(lastLogin) : null;
  const logoutTimeDate = logoutTime ? new Date(logoutTime) : null;

  // Check if logout_time equals last_activity (exact match)
  if (
    logoutTimeDate &&
    activityTime &&
    logoutTimeDate.getTime() === activityTime.getTime()
  ) {
    return {
      status: "offline",
      text: "Offline",
      class: "status-offline",
    };
  }

  // Find the most recent time between last_activity and last_login
  let mostRecentTime = null;

  if (activityTime && loginTime) {
    // Use the more recent of the two
    mostRecentTime = activityTime > loginTime ? activityTime : loginTime;
  } else if (activityTime) {
    mostRecentTime = activityTime;
  } else if (loginTime) {
    mostRecentTime = loginTime;
  }

  if (!mostRecentTime) {
    return {
      status: "offline",
      text: "Offline",
      class: "status-offline",
    };
  }

  const now = new Date();
  const diffInMinutes = Math.floor((now - mostRecentTime) / (1000 * 60));

  if (diffInMinutes <= 5) {
    return {
      status: "online",
      text: "Online",
      class: "status-online",
    };
  } else if (diffInMinutes <= 60) {
    return {
      status: "active",
      text: `Last active ${diffInMinutes}m ago`,
      class: "status-active-recent",
    };
  } else {
    return {
      status: "offline",
      text: "Offline",
      class: "status-offline",
    };
  }
}
document.addEventListener("DOMContentLoaded", function () {
  // Initialize lastMessageId and isTyping
  let lastMessageId = 0;
  let isTyping = false;

  // Get references to all necessary elements
  const header = document.querySelector("header");
  const homeLink = document.querySelector("nav ul li:first-child a");
  const appointmentBtn = document.getElementById("appointmentBtn");
  const appointmentForm = document.getElementById("appointmentForm");
  const cancelAppointmentBtn = document.getElementById("cancelAppointmentBtn");
  const welcomeSection = document.querySelector(".content-panel h3");
  const welcomeQuote = document.querySelector(".content-panel p");
  const openChatBtn = document.getElementById("openChatBtn");
  const chatPopup = document.getElementById("chatPopup");
  const closeChat = document.getElementById("closeChat");
  const main = document.querySelector("main");
  const notificationIcon = document.getElementById("notificationIcon");
  const notificationsDropdown = document.getElementById(
    "notificationsDropdown"
  );
  const notificationBadge = document.getElementById("notificationBadge");
  const profileAvatar = document.querySelector(".profile-avatar");
  const navbarDrawerToggler = document.getElementById("navbarDrawerToggler");
  const navbarDrawer = document.getElementById("navbarDrawer");
  const navbarDrawerClose = document.getElementById("navbarDrawerClose");
  const navbarOverlay = document.getElementById("navbarOverlay");

  // Make header sticky on scroll
  if (header) {
    window.addEventListener("scroll", function () {
      if (window.scrollY > 0) {
        header.classList.add("sticky-header");
      } else {
        header.classList.remove("sticky-header");
      }
    });
  }

  // Remove the placeholder div code completely, as it's causing the jumping

  // Initially hide the appointment form
  if (appointmentForm) {
    appointmentForm.style.display = "none";
  }

  // Initially hide the chat popup
  if (chatPopup) {
    chatPopup.style.display = "none";
  }

  // Click animations for chat and notifications
  const openChatBtnEl = document.getElementById("openChatBtn");
  if (openChatBtnEl) {
    openChatBtnEl.addEventListener("click", function () {
      openChatBtnEl.classList.remove("chat-click");
      void openChatBtnEl.offsetWidth;
      openChatBtnEl.classList.add("chat-click");
    });
  }
  if (notificationIcon) {
    notificationIcon.addEventListener("click", function (e) {
      notificationIcon.classList.remove("bell-click");
      void notificationIcon.offsetWidth;
      notificationIcon.classList.add("bell-click");
    });
  }

  // Home link - make it functional to navigate to user_dashboard.html
  if (homeLink) {
    homeLink.addEventListener("click", function (e) {
      // Check if we're already on the dashboard page
      if (
        window.location.pathname.includes("user_dashboard.html") ||
        window.location.pathname.endsWith("/") ||
        window.location.pathname === ""
      ) {
        // If we're on dashboard, just reset the view
        e.preventDefault();

        // Reset dashboard view
        if (appointmentForm) appointmentForm.style.display = "none";
        if (welcomeSection) welcomeSection.style.display = "block";
        if (welcomeQuote) welcomeQuote.style.display = "block";
        if (chatPopup) chatPopup.style.display = "none";

        // Scroll to top
        window.scrollTo(0, 0);
      }
    });
  }

  // Appointment button - show form and hide welcome
  if (appointmentBtn) {
    appointmentBtn.addEventListener("click", function () {
      if (appointmentForm) {
        appointmentForm.style.display = "block";

        // Hide welcome section
        if (welcomeSection) welcomeSection.style.display = "none";
        if (welcomeQuote) welcomeQuote.style.display = "none";

        // Scroll to appointment form
        appointmentForm.scrollIntoView({ behavior: "smooth" });
      }
    });
  }

  // Cancel button - hide form and show welcome
  if (cancelAppointmentBtn) {
    cancelAppointmentBtn.addEventListener("click", function () {
      if (appointmentForm) {
        appointmentForm.style.display = "none";

        // Show welcome section again
        if (welcomeSection) welcomeSection.style.display = "block";
        if (welcomeQuote) welcomeQuote.style.display = "block";
      }
    });
  }

  // Chat functionality
  let messageUpdateInterval = null;
  let userId = null; // Will store the user's ID
  let selectedStudentId = null; // Will store the selected student ID
  let selectedStudentName = null; // Will store the selected student name

  // Student selection modal elements
  const studentSelectionModal = document.getElementById(
    "studentSelectionModal"
  );
  const closeStudentSelection = document.getElementById(
    "closeStudentSelection"
  );
  const studentSearchInput = document.getElementById("studentSearchInput");
  const studentList = document.getElementById("studentList");
  const messagesCard = document.getElementById("messagesCard");

  // Notification handling
  function initializeNotifications() {
    const notificationIcon = document.getElementById("notificationIcon");
    const notificationsDropdown = document.getElementById(
      "notificationsDropdown"
    );
    const notificationBadge = document.getElementById("notificationBadge");

    if (notificationIcon && notificationsDropdown) {
      notificationsDropdown.style.display = "none";

      notificationIcon.addEventListener("click", function (e) {
        e.stopPropagation();
        if (
          notificationsDropdown.style.display === "none" ||
          !notificationsDropdown.style.display
        ) {
          const iconRect = notificationIcon.getBoundingClientRect();
          const dropdownWidth = Math.min(320, window.innerWidth - 20);
          let right = window.innerWidth - iconRect.right;
          if (right + dropdownWidth > window.innerWidth) {
            right = 10;
          }
          notificationsDropdown.style.top =
            Math.min(
              iconRect.bottom + window.scrollY + 10,
              window.scrollY +
                window.innerHeight -
                notificationsDropdown.offsetHeight -
                10
            ) + "px";
          notificationsDropdown.style.right = right + "px";
          notificationsDropdown.style.display = "block";
          loadNotifications();
        } else {
          notificationsDropdown.style.display = "none";
        }
      });

      document.addEventListener("click", function (e) {
        if (
          notificationsDropdown.style.display === "block" &&
          !notificationsDropdown.contains(e.target) &&
          e.target !== notificationIcon
        ) {
          notificationsDropdown.style.display = "none";
        }
      });

      notificationsDropdown.addEventListener("click", function (e) {
        e.stopPropagation();
      });

      window.addEventListener("resize", function () {
        if (notificationsDropdown.style.display === "block") {
          const iconRect = notificationIcon.getBoundingClientRect();
          const dropdownWidth = Math.min(320, window.innerWidth - 20);
          let right = window.innerWidth - iconRect.right;
          if (right + dropdownWidth > window.innerWidth) {
            right = 10;
          }
          notificationsDropdown.style.right = right + "px";
          notificationsDropdown.style.width = dropdownWidth + "px";
        }
      });
    }
  }

  function updateNotificationCounter(count) {
    const notificationBadge = document.getElementById("notificationBadge");
    if (notificationBadge) {
      if (count > 0) {
        notificationBadge.textContent = count;
        notificationBadge.style.display = "inline-block";
        notificationBadge.classList.remove("hidden");
      } else {
        notificationBadge.textContent = "";
        notificationBadge.style.display = "none";
        notificationBadge.classList.add("hidden");
      }
    }
  }

  function fetchNotificationCount() {
    // Align the badge count with the currently displayable items from the same source
    fetch(window.BASE_URL + "counselor/notifications")
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          const notifications = Array.isArray(data.notifications)
            ? data.notifications
            : [];
          updateNotificationCounter(notifications.length);
        }
      })
      .catch((error) => {
        console.error("Error fetching notification count:", error);
      });
  }

  function loadNotifications() {
    fetch(window.BASE_URL + "counselor/notifications")
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          const notifications = Array.isArray(data.notifications)
            ? data.notifications
            : [];
          // Apply client-side expiration filtering
          const filteredNotifications =
            filterExpiredNotifications(notifications);
          renderNotifications(filteredNotifications);
          updateNotificationCounter(filteredNotifications.length);
        } else {
          showEmptyNotifications("Failed to load notifications");
        }
      })
      .catch((error) => {
        showEmptyNotifications("Unable to connect to server");
      });
  }

  // Filter out expired notifications on the client side
  function filterExpiredNotifications(notifications) {
    const now = new Date();
    return notifications.filter((notification) => {
      // Check if event has passed
      if (notification.type === "event" && notification.event_date) {
        const eventDate = new Date(notification.event_date);
        return eventDate > now;
      }
      // Check if appointment has passed (keep for 7 days after appointment date)
      if (
        notification.type === "appointment" &&
        notification.appointment_date
      ) {
        const appointmentDate = new Date(notification.appointment_date);
        const sevenDaysAfter = new Date(
          appointmentDate.getTime() + 7 * 24 * 60 * 60 * 1000
        );
        return sevenDaysAfter > now;
      }
      // Keep all other notification types (announcements, messages)
      return true;
    });
  }

  function renderNotifications(notifications = []) {
    const notificationsContainer = document.querySelector(
      ".notifications-list"
    );
    if (!notificationsContainer) return;
    if (!notifications || notifications.length === 0) {
      showEmptyNotifications("No notifications");
      return;
    }
    notificationsContainer.innerHTML = "";
    notifications.forEach((notification) => {
      if (!notification) return;
      const notificationItem = document.createElement("div");
      notificationItem.className = "notification-item";
      if (!notification.is_read) {
        notificationItem.classList.add("unread");
      }
      const notifDate = new Date(notification.created_at);
      const formattedDate =
        notifDate.toLocaleDateString() + " " + notifDate.toLocaleTimeString();
      // Show mark as read button for all notification types if not already read
      // For events and announcements, check if they're already marked as read
      let showMarkReadBtn = false;
      if (notification.is_read === 0 || notification.is_read === false || notification.is_read === '0') {
        showMarkReadBtn = true;
      } else if (notification.type === 'event' || notification.type === 'announcement') {
        // Events and announcements don't have is_read in the notification object
        // They're considered unread if they appear in the list
        showMarkReadBtn = true;
      }
      
      const markReadBtn = showMarkReadBtn ? `
                <button class="btn btn-sm btn-outline-primary mark-read-btn" data-notification-id="${notification.id || ''}" data-type="${notification.type || ''}" data-related-id="${notification.related_id || ''}" title="Mark as read">
                    <i class="fas fa-check"></i>
                </button>
            ` : '';
      
      notificationItem.innerHTML = `
                <div class="notification-header">
                    <h4>${notification.title || "Notification"}</h4>
                    <div class="notification-actions">
                        <span class="notification-time">${formattedDate}</span>
                        ${markReadBtn}
                    </div>
                </div>
                <p>${notification.message || ""}</p>
            `;
      
      // Add click handler for mark as read button
      const markReadButton = notificationItem.querySelector('.mark-read-btn');
      if (markReadButton) {
        markReadButton.addEventListener('click', function(e) {
          e.stopPropagation();
          e.preventDefault();
          const notificationId = markReadButton.dataset.notificationId || notification.id || null;
          const notificationType = markReadButton.dataset.type || notification.type || null;
          const relatedId = markReadButton.dataset.relatedId || notification.related_id || null;
          
          markNotificationAsRead(notificationId, notificationType, relatedId);
          notificationItem.classList.remove('unread');
          notification.is_read = 1;
          markReadButton.remove();
          fetchNotificationCount();
          loadNotifications(); // Reload to update the list
        });
      }
      
      notificationItem.addEventListener("click", function () {
        // Hide notifications dropdown first
        const notificationsDropdown = document.getElementById(
          "notificationsDropdown"
        );
        if (notificationsDropdown) {
          notificationsDropdown.style.display = "none";
        }

        if (!notification.is_read) {
          markNotificationAsRead(notification.id);
          notificationItem.classList.remove("unread");
          notification.is_read = true;
          fetchNotificationCount();
        }
        // Handle navigation based on notification type
        if (notification.type === "appointment") {
          // Reuse student modal behavior for appointment details; fetch counselor appointments too
          showCounselorAppointmentDetailsModal(notification.related_id);
        } else if (
          notification.type === "event" ||
          notification.type === "announcement"
        ) {
          window.location.href = window.BASE_URL + "counselor/announcements";
        } else if (notification.type === "message") {
          // Open chat popup
          const openChatBtn = document.getElementById("openChatBtn");
          if (openChatBtn) openChatBtn.click();
        }
      });
      notificationsContainer.appendChild(notificationItem);
    });
  }

  // Counselor Appointment Details Modal (mirrors student modal but adds a Go To Appointments button)
  function showCounselorAppointmentDetailsModal(appointmentId) {
    const notificationsDropdown = document.getElementById(
      "notificationsDropdown"
    );
    if (
      notificationsDropdown &&
      notificationsDropdown.style.display === "block"
    ) {
      notificationsDropdown.style.display = "none";
    }
    fetch(window.BASE_URL + "counselor/appointments/getAppointments")
      .then((response) => response.json())
      .then((data) => {
        const body = document.getElementById("appointmentDetailsBody");
        if (!body) return;
        if (data && data.appointments) {
          const appointment = (data.appointments || []).find(
            (app) => String(app.id) === String(appointmentId)
          );
          if (appointment) {
            const getStatusBadge = (status) => {
              const statusLower = String(status || "").toLowerCase();
              let badgeClass = "bg-secondary";
              if (statusLower === "rejected") badgeClass = "bg-danger";
              else if (statusLower === "pending") badgeClass = "bg-warning";
              else if (statusLower === "completed") badgeClass = "bg-primary";
              else if (statusLower === "approved") badgeClass = "bg-success";
              else if (statusLower === "cancelled") badgeClass = "bg-secondary";
              return `<span class="badge ${badgeClass}">${status}</span>`;
            };
            body.innerHTML = `
                        <strong>Date:</strong> ${appointment.preferred_date}<br>
                        <strong>Time:</strong> ${appointment.preferred_time}<br>
                        <strong>Status:</strong> ${getStatusBadge(
                          appointment.status
                        )}<br>
                        <strong>Student:</strong> ${
                          appointment.student_name || appointment.username || appointment.student_id
                        }<br>
                        <strong>Method:</strong> ${
                          appointment.method_type || "N/A"
                        }<br>
                        <strong>Purpose:</strong> ${
                          appointment.purpose || "N/A"
                        }<br>
                        <strong>Description:</strong> ${
                          appointment.description || ""
                        }<br>
                    `;
            const modalEl = document.getElementById("appointmentDetailsModal");
            if (modalEl) {
              const footer = modalEl.querySelector(".modal-footer");
              if (
                footer &&
                !footer.querySelector("#goToCounselorAppointments")
              ) {
                const btn = document.createElement("a");
                btn.id = "goToCounselorAppointments";
                btn.className = "btn btn-primary";
                btn.href = (window.BASE_URL || "/") + "counselor/appointments";
                btn.textContent = "Go to Appointments";
                footer.appendChild(btn);
              }
              const modal = new bootstrap.Modal(modalEl);
              modal.show();
            }
          } else {
            body.innerHTML = "Appointment not found.";
          }
        } else {
          if (body) body.innerHTML = "Failed to load appointment details.";
        }
      })
      .catch(() => {
        const body = document.getElementById("appointmentDetailsBody");
        if (body) body.innerHTML = "Error loading appointment details.";
      });
  }

  function markNotificationAsRead(notificationId, notificationType, relatedId) {
    const payload = {};
    if (notificationId) {
      payload.notification_id = notificationId;
    } else if (notificationType && relatedId) {
      payload.type = notificationType;
      payload.related_id = relatedId;
    } else {
      console.error('Invalid parameters for markNotificationAsRead');
      return;
    }
    
    fetch(window.BASE_URL + "counselor/notifications/mark-read", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status !== "success") {
          console.error("Error marking notification as read:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error marking notification as read:", error);
      });
  }
  
  // Add mark all as read functionality
const markAllReadBtn = document.getElementById('markAllReadBtn');
if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        e.preventDefault();
        
        // Get all notifications currently displayed in the modal
        const notificationsList = document.querySelector('.notifications-list');
        if (!notificationsList) return;
        
        const notificationItems = notificationsList.querySelectorAll('.notification-item');
        
        // Collect all notifications that need to be marked as read
        const notificationsToMark = [];
        
        notificationItems.forEach(item => {
            const markReadBtn = item.querySelector('.mark-read-btn');
            if (markReadBtn) {
                const notificationId = markReadBtn.dataset.notificationId || null;
                const notificationType = markReadBtn.dataset.type || null;
                const relatedId = markReadBtn.dataset.relatedId || null;
                
                if (notificationId || (notificationType && relatedId)) {
                    notificationsToMark.push({
                        notification_id: notificationId,
                        type: notificationType,
                        related_id: relatedId
                    });
                }
            }
        });
        
        // If no notifications to mark, just return
        if (notificationsToMark.length === 0) {
            return;
        }
        
        // Mark all notifications as read using the bulk endpoint
        fetch(window.BASE_URL + 'counselor/notifications/mark-read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mark_all: true })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Now mark each individual notification based on its type
                const markPromises = notificationsToMark.map(notif => {
                    const payload = {};
                    
                    // Handle different notification types
                    if (notif.notification_id) {
                        payload.notification_id = notif.notification_id;
                    } else if (notif.type && notif.related_id) {
                        payload.type = notif.type;
                        payload.related_id = notif.related_id;
                    } else {
                        return Promise.resolve();
                    }
                    
                    return fetch(window.BASE_URL + 'counselor/notifications/mark-read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                });
                
                // Wait for all individual marks to complete
                return Promise.all(markPromises);
            } else {
                console.error('Error marking all notifications as read:', data.message);
                throw new Error(data.message);
            }
        })
        .then(() => {
            // Remove all mark-as-read buttons and unread classes
            notificationItems.forEach(item => {
                item.classList.remove('unread');
                const markReadBtn = item.querySelector('.mark-read-btn');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            });
            
            // Reload notifications to get fresh data
            loadNotifications();
            fetchNotificationCount();
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    });
}

  function showEmptyNotifications(message) {
    const notificationsContainer = document.querySelector(
      ".notifications-list"
    );
    if (notificationsContainer) {
      notificationsContainer.innerHTML = `<div class="empty-notifications"><p>${message}</p></div>`;
    }
  }

  // Real-time polling for notifications
  function startNotificationPolling() {
    fetchNotificationCount();
    setInterval(() => {
      fetchNotificationCount();
      loadNotifications();
    }, 10000); // every 10 seconds
  }

  // Modify fetchUserIdAndInitialize to also initialize notifications
  function fetchUserIdAndInitialize() {
    fetch(window.BASE_URL + "counselor/profile/get")
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.user_id) {
          userId = data.user_id;
          initializeChat();
          initializeNotifications();
          startNotificationPolling();
          // Update profile picture on dashboard
          const img = document.getElementById("profile-img");
          if (img) {
            const src = resolveImageUrl(data.profile_picture);
            img.src = src;
            try {
              localStorage.setItem("counselor_profile_picture", src);
            } catch (e) {}
          }

          // Update the user ID in the welcome message only if no display name exists
          const userIdSpan = document.querySelector(".text-primary i");
          const userDisplaySpan = document.getElementById("user-id-display");
          if (userIdSpan && !userDisplaySpan) {
            // Only update if there's no hidden user-id-display element (meaning no name was found)
            userIdSpan.textContent = data.user_id;
          }
        } else {
          console.error("Failed to get user ID");
        }
      })
      .catch((error) => {
        console.error("Error fetching user profile:", error);
      });
  }

  function initializeChat() {
    const messageForm = document.getElementById("messageForm");
    const messageInput = document.getElementById("messageInput");
    const messagesContainer = document.getElementById("messagesContainer");
    const chatPopup = document.getElementById("chatPopup");
    const openChatBtn = document.getElementById("openChatBtn");
    const closeChat = document.getElementById("closeChat");

    // Add console logs for debugging
    SecureLogger.info("Chat elements:", {
      messageForm,
      messageInput,
      messagesContainer,
      chatPopup,
      openChatBtn,
      closeChat,
    });

    if (openChatBtn && chatPopup) {
      openChatBtn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        SecureLogger.info("Chat button clicked");
        chatPopup.style.display = "block";
        chatPopup.classList.add("visible");
        openChatBtn.classList.add("active"); // Add active class when chat is opened
        loadMessages();
        startMessagePolling();
      });
    }

    if (closeChat && chatPopup) {
      closeChat.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        chatPopup.classList.remove("visible");
        openChatBtn.classList.remove("active"); // Remove active class when chat is closed
        setTimeout(() => {
          chatPopup.style.display = "none";
        }, 300); // Match the transition duration
        stopMessagePolling();
      });
    }

    // Close chat when clicking outside
    document.addEventListener("click", function (e) {
      if (
        chatPopup &&
        chatPopup.style.display === "block" &&
        !chatPopup.contains(e.target) &&
        e.target !== openChatBtn
      ) {
        closeChat.click();
      }
    });

    // Prevent chat from closing when clicking inside
    if (chatPopup) {
      chatPopup.addEventListener("click", function (e) {
        e.stopPropagation();
      });
    }

    // Handle message submission
    if (messageForm) {
      messageForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message) {
          sendMessage(e);
        }
      });
    }

    // Handle enter key for sending message
    if (messageInput) {
      messageInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          const message = messageInput.value.trim();
          if (message) {
            sendMessage(e);
          }
        }
      });
    }
  }

  function startMessagePolling() {
    loadMessages(); // Initial load
    messageUpdateInterval = setInterval(loadMessages, 5000); // Poll every 5 seconds
  }

  function stopMessagePolling() {
    if (messageUpdateInterval) {
      clearInterval(messageUpdateInterval);
      messageUpdateInterval = null;
    }
  }

  function loadMessages() {
    if (!userId) {
      console.error("User ID not available");
      return;
    }

    fetch(window.BASE_URL + "counselor/message/operations?action=get_messages")
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          displayMessages(data.messages || []);
        } else {
          console.error("Failed to load messages:", data.message);
          showSystemMessage("Unable to load messages. Please try again later.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showSystemMessage(
          "Error loading messages. Please check your connection."
        );
      });
  }

  function displayMessages(messages) {
    const container = document.getElementById("messagesContainer");
    if (!container) return;

    // If no messages, show welcome message
    if (!messages || messages.length === 0) {
      container.innerHTML = `
                <div class="system-message">
                    Welcome! Send a message to get started.
                </div>
            `;
      return;
    }

    // Sort messages by created_at
    messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

    // Track which messages are new
    const newMessages = messages.filter(
      (msg) => msg.message_id > lastMessageId
    );

    // Update lastMessageId
    if (messages.length > 0) {
      lastMessageId = Math.max(...messages.map((m) => m.message_id));
    }

    // Only update DOM if there are new messages
    if (newMessages.length > 0) {
      // Append new messages
      newMessages.forEach((message) => {
        const messageElement = createMessageElement(message);
        container.appendChild(messageElement);

        // Trigger fade-in animation
        requestAnimationFrame(() => {
          messageElement.style.opacity = "1";
          messageElement.style.transform = "translateY(0)";
        });
      });

      // Scroll to bottom smoothly
      scrollToBottom();
    }
  }

  function createMessageElement(message) {
    const div = document.createElement("div");
    div.className = `message-bubble ${
      message.sender_id === userId ? "sent" : "received"
    }`;
    div.style.opacity = "0";
    div.style.transform = "translateY(10px)";

    const messageText = document.createElement("div");
    messageText.className = "message-text";
    messageText.textContent = message.message_text;

    const timeDiv = document.createElement("div");
    timeDiv.className = "message-time";
    timeDiv.textContent = formatMessageTime(message.created_at);

    div.appendChild(messageText);
    div.appendChild(timeDiv);

    return div;
  }

  function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    // If less than 24 hours ago, show time
    if (diff < 24 * 60 * 60 * 1000) {
      return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      });
    }
    // If this year, show date and time
    if (date.getFullYear() === now.getFullYear()) {
      return (
        date.toLocaleDateString([], { month: "short", day: "numeric" }) +
        " " +
        date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
      );
    }
    // If different year, show full date
    return date.toLocaleDateString([], {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  function scrollToBottom() {
    const chatBody = document.querySelector(".chat-body");
    if (chatBody) {
      chatBody.scrollTop = chatBody.scrollHeight;
    }
  }

  function showTypingIndicator() {
    if (isTyping) return;

    const container = document.getElementById("messagesContainer");
    if (!container) return;

    isTyping = true;
    const indicator = document.createElement("div");
    indicator.className = "typing-indicator";
    indicator.id = "typingIndicator";
    indicator.innerHTML = `
            <span></span>
            <span></span>
            <span></span>
        `;

    container.appendChild(indicator);
    scrollToBottom();
  }

  function hideTypingIndicator() {
    const indicator = document.getElementById("typingIndicator");
    if (indicator) {
      indicator.remove();
    }
    isTyping = false;
  }

  function sendMessage(event) {
    event.preventDefault();

    if (!userId) {
      console.error("User ID not available");
      showSystemMessage("Unable to send message. Please try again.");
      return;
    }

    const messageInput = document.querySelector(".message-input");
    const message = messageInput.value.trim();

    if (!message) return;

    const sendButton = document.querySelector(".send-button");
    sendButton.disabled = true;

    showTypingIndicator();

    const formData = new FormData();
    formData.append("action", "send_message");
    formData.append("receiver_id", "admin123");
    formData.append("message", message);

    fetch(window.BASE_URL + "user/message/operations", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          messageInput.value = "";
          loadMessages(); // Refresh messages
          // Show system message immediately
          showSystemMessage(
            "Message sent successfully. Please wait for a counselor to respond."
          );
        } else {
          console.error("Failed to send message:", data.message);
          showSystemMessage(
            data.message || "Failed to send message. Please try again."
          );
        }
      })
      .catch((error) => {
        console.error("Error sending message:", error);
        showSystemMessage(
          "An error occurred while sending the message. Please try again."
        );
      })
      .finally(() => {
        sendButton.disabled = false;
        hideTypingIndicator();
      });
  }

  function notifyAdmin(message) {
    // Send notification to admin page
    const notification = {
      type: "new_message",
      user_id: userId,
      message: message,
      timestamp: new Date().toISOString(),
    };

    // Store notification in database for admin
    fetch(window.BASE_URL + "admin/notify", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(notification),
    })
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          console.error("Failed to notify admin:", data.message);
        }
      })
      .catch((error) => {
        console.error("Error notifying admin:", error);
      });
  }

  function showSystemMessage(message) {
    const container = document.getElementById("messagesContainer");
    if (!container) return;

    const systemMessage = document.createElement("div");
    systemMessage.className = "system-message";
    systemMessage.textContent = message;

    container.appendChild(systemMessage);
    scrollToBottom();

    // Keep system message visible for 10 seconds before fading out
    setTimeout(() => {
      systemMessage.style.opacity = "0";
      setTimeout(() => {
        if (systemMessage.parentNode === container) {
          container.removeChild(systemMessage);
        }
      }, 300);
    }, 10000);
  }

  function showAppointmentDetailsModal(appointmentId) {
    // Hide notifications dropdown if open
    const notificationsDropdown = document.getElementById(
      "notificationsDropdown"
    );
    if (
      notificationsDropdown &&
      notificationsDropdown.style.display === "block"
    ) {
      notificationsDropdown.style.display = "none";
    }
    fetch(window.BASE_URL + "user/appointments/get-my-appointments")
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.appointments) {
          const appointment = data.appointments.find(
            (app) => app.id == appointmentId
          );
          if (appointment) {
            // Function to get status badge HTML
            const getStatusBadge = (status) => {
              const statusLower = status.toLowerCase();
              let badgeClass = "";
              switch (statusLower) {
                case "pending":
                  badgeClass = "bg-warning";
                  break;
                case "rejected":
                  badgeClass = "bg-danger";
                  break;
                case "completed":
                  badgeClass = "bg-primary";
                  break;
                case "approved":
                  badgeClass = "bg-success";
                  break;
                case "cancelled":
                  badgeClass = "bg-secondary";
                  break;
                default:
                  badgeClass = "bg-secondary";
              }
              return `<span class="badge ${badgeClass}">${status}</span>`;
            };

            document.getElementById("appointmentDetailsBody").innerHTML = `
                            <strong>Date:</strong> ${
                              appointment.preferred_date
                            }<br>
                            <strong>Time:</strong> ${
                              appointment.preferred_time
                            }<br>
                            <strong>Status:</strong> ${getStatusBadge(
                              appointment.status
                            )}<br>
                            <strong>Counselor Preference:</strong> ${
                              appointment.counselor_preference
                            }<br>
                            <strong>Consultation Type:</strong> ${
                              appointment.consultation_type || ""
                            }<br>
                            <strong>Description:</strong> ${
                              appointment.description || ""
                            }<br>
                            <strong>Reason:</strong> ${
                              appointment.reason || ""
                            }<br>

                            
                        `;
            // Show the modal (Bootstrap 5)
            const modal = new bootstrap.Modal(
              document.getElementById("appointmentDetailsModal")
            );
            modal.show();
          } else {
            document.getElementById("appointmentDetailsBody").innerHTML =
              "Appointment not found.";
          }
        } else {
          document.getElementById("appointmentDetailsBody").innerHTML =
            "Failed to load appointment details.";
        }
      })
      .catch(() => {
        document.getElementById("appointmentDetailsBody").innerHTML =
          "Error loading appointment details.";
      });
  }

  // Messages card click handler
  if (messagesCard) {
    messagesCard.addEventListener("click", function () {
      // Navigate to counselor messages page
      window.location.href = window.BASE_URL + "counselor/messages";
    });
  }

  // Auto-refresh notifications every 30 seconds
  let notificationRefreshInterval = null;

  function startNotificationAutoRefresh() {
    // Clear existing interval if any
    if (notificationRefreshInterval) {
      clearInterval(notificationRefreshInterval);
    }

    // Refresh notifications every 30 seconds
    notificationRefreshInterval = setInterval(() => {
      // Only refresh if notifications dropdown is open
      const notificationsDropdown = document.getElementById(
        "notificationsDropdown"
      );
      if (
        notificationsDropdown &&
        notificationsDropdown.style.display === "block"
      ) {
        loadNotifications();
      }
    }, 30000);
  }

  function stopNotificationAutoRefresh() {
    if (notificationRefreshInterval) {
      clearInterval(notificationRefreshInterval);
      notificationRefreshInterval = null;
    }
  }

  // Start the initialization process
  fetchUserIdAndInitialize();

  // Start auto-refresh for notifications
  startNotificationAutoRefresh();

  /**
   * Fetch and display recent pending appointments for the counselor
   * This function loads the 2 most recent pending appointments where
   * counselor_preference matches the logged-in counselor
   */
  function loadRecentPendingAppointments() {
    const container = document.getElementById("appointments-container");
    if (!container) {
      console.warn("Appointments container not found");
      return;
    }

    fetch(window.BASE_URL + "counselor/dashboard/recent-pending-appointments")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.status === "success") {
          displayRecentAppointments(data.appointments || []);
        } else {
          console.error(
            "Failed to load appointments:",
            data.message || "Unknown error"
          );
          displayRecentAppointments([]);
        }
      })
      .catch((error) => {
        console.error("Error fetching appointments:", error);
        // Display empty state instead of breaking
        displayRecentAppointments([]);
      });
  }

  /**
   * Display recent appointments in the dashboard
   * @param {Array} appointments - Array of appointment objects
   */
  function displayRecentAppointments(appointments) {
    const container = document.getElementById("appointments-container");
    if (!container) return;

    // Find the appointments content area (the div with gap-3 class)
    const appointmentsContent = container.querySelector(
      ".d-flex.flex-column.gap-3"
    );
    if (!appointmentsContent) {
      console.warn("Appointments content area not found");
      return;
    }

    // Clear existing placeholder content
    appointmentsContent.innerHTML = "";

    if (!appointments || appointments.length === 0) {
      // Show "no appointments" message
      appointmentsContent.innerHTML = `
            <div class="p-3 bg-light rounded shadow-sm text-center">
                <p class="text-muted mb-0">No pending appointments at the moment</p>
            </div>
        `;
      return;
    }

    // Display each appointment
    appointments.forEach((appointment) => {
      const appointmentCard = document.createElement("div");
      appointmentCard.className = "p-3 bg-light rounded shadow-sm";

      // Format the date safely
      let formattedDate = "N/A";
      try {
        const appointmentDate = new Date(appointment.preferred_date);
        if (!isNaN(appointmentDate.getTime())) {
          formattedDate = appointmentDate.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
          });
        }
      } catch (e) {
        console.error("Error formatting date:", e);
      }

      appointmentCard.innerHTML = `
            <p class="text-body-secondary mb-1"><strong>Student:</strong> ${escapeHtml(
              appointment.student_name || appointment.username || appointment.student_id || "N/A"
            )}</p>
            <p class="text-body-secondary mb-1"><strong>Date:</strong> ${formattedDate}</p>
            <p class="text-body-secondary mb-1"><strong>Time:</strong> ${escapeHtml(
              appointment.preferred_time || "N/A"
            )}</p>
            <p class="text-body-secondary mb-1"><strong>Method:</strong> ${escapeHtml(
              appointment.method_type || "N/A"
            )}</p>
            <p class="text-body-secondary mb-0"><strong>Purpose:</strong> ${escapeHtml(
              appointment.purpose || "N/A"
            )}</p>
        `;

      appointmentsContent.appendChild(appointmentCard);
    });
  }

  // Load appointments when page loads
  loadRecentPendingAppointments();

  // Refresh appointments every 30 seconds
  setInterval(loadRecentPendingAppointments, 30000);

  /**
   * Fetch and display the latest 2 student conversations for the counselor
   * Populates the Messages card on the dashboard
   */
  function loadRecentMessages() {
    const card = document.getElementById("messagesCard");
    if (!card) return;

    fetch(
      (window.BASE_URL || "/") +
        "counselor/message/operations?action=get_dashboard_messages&limit=2",
      {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
      }
    )
      .then((r) => r.json())
      .then((data) => {
        if (data && data.success && Array.isArray(data.conversations)) {
          displayRecentMessages(data.conversations);
        } else {
          displayRecentMessages([]);
        }
      })
      .catch(() => displayRecentMessages([]));
  }

  function displayRecentMessages(conversations) {
    const card = document.getElementById("messagesCard");
    if (!card) return;

    const content = card.querySelector(".d-flex.flex-column");
    if (!content) return;

    content.innerHTML = "";

    if (!conversations || conversations.length === 0) {
      const empty = document.createElement("div");
      empty.className = "p-3 bg-light rounded shadow-sm text-center";
      empty.innerHTML =
        '<p class="text-muted mb-0">No recent student messages</p>';
      content.appendChild(empty);
      return;
    }

    conversations.slice(0, 2).forEach((conv) => {
      const lastTime = conv.last_message_time
        ? formatDashboardTime(conv.last_message_time)
        : "";
      const statusInfo = calculateOnlineStatus(
        conv.last_activity,
        conv.last_login,
        conv.logout_time
      );

      const preview = document.createElement("div");
      preview.className =
        "p-3 bg-light rounded shadow-sm d-flex align-items-start gap-2 dashboard-message-card";

      const avatar = document.createElement("img");
      avatar.alt = "Student avatar";
      avatar.className = "rounded-circle";
      avatar.style.width = "36px";
      avatar.style.height = "36px";
      avatar.style.objectFit = "cover";
      avatar.src = resolveImageUrl(
        conv.other_profile_picture || "Photos/profile.png"
      );

      const info = document.createElement("div");
      info.style.flex = "1";
      info.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-start justify-content-left">
                    <strong class="text-body-secondary me-1">Student:</strong>
                    <span class="text-body-secondary">${escapeHtml(
                      conv.other_username || conv.other_user_id || ""
                    )}</span>
                </div>
                <span class="dashboard-status-indicator ${statusInfo.class}">${
        statusInfo.text
      }</span>
            </div>
            <p class="text-body-secondary mb-1"><strong>Last:</strong> ${escapeHtml(
              conv.last_message || ""
            )}</p>
            <p class="small text-secondary mb-0"><strong>Received on:</strong> ${lastTime}</p>
        `;

      preview.appendChild(avatar);
      preview.appendChild(info);
      content.appendChild(preview);
    });
  }

  /**
   * Formats a timestamp for dashboard display with dynamic relative time
   * @param {string|Date|number} ts - Timestamp to format
   * @returns {string} Formatted time string
   */
  function formatDashboardTime(ts) {
    if (!ts) return "";
    
    const messageDate = new Date(ts);
    if (Number.isNaN(messageDate.getTime())) return "";
    
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const messageDay = new Date(
      messageDate.getFullYear(),
      messageDate.getMonth(),
      messageDate.getDate()
    );
    
    // Format time in 12-hour format with AM/PM
    const hours = messageDate.getHours();
    const minutes = messageDate.getMinutes();
    const ampm = hours >= 12 ? "PM" : "AM";
    const displayHours = hours % 12 || 12;
    const displayMinutes = minutes.toString().padStart(2, "0");
    const timeString = `${displayHours}:${displayMinutes} ${ampm}`;
    
    // Calculate difference in days
    const diffInMs = today - messageDay;
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    
    // Today: show only time
    if (diffInDays === 0) {
      return timeString;
    }
    
    // Yesterday: "Yesterday at {time}"
    if (diffInDays === 1) {
      return `Yesterday at ${timeString}`;
    }
    
    // 1-6 days ago: "{n} days ago {time}"
    if (diffInDays >= 2 && diffInDays <= 6) {
      return `${diffInDays} days ago ${timeString}`;
    }
    
    // 1 week ago (7 days): "1 week ago {time}"
    if (diffInDays === 7) {
      return `1 week ago ${timeString}`;
    }
    
    // More than 1 week ago: "Nov 1, 9:30 PM" format
    const monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    const month = monthNames[messageDate.getMonth()];
    const day = messageDate.getDate();
    return `${month} ${day}, ${timeString}`;
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // Load recent messages when page loads and refresh every 30s
  loadRecentMessages();
  setInterval(loadRecentMessages, 30000);

  // Drawer open/close behavior (always enabled)
  function openDrawer() {
    if (navbarDrawer) navbarDrawer.classList.add("show");
    if (navbarOverlay) navbarOverlay.classList.add("show");
    document.body.style.overflow = "hidden";
    if (navbarDrawerToggler) navbarDrawerToggler.classList.add("active");
  }

  function closeDrawer() {
    if (navbarDrawer) navbarDrawer.classList.remove("show");
    if (navbarOverlay) navbarOverlay.classList.remove("show");
    document.body.style.overflow = "";
    if (navbarDrawerToggler) navbarDrawerToggler.classList.remove("active");
  }

  if (navbarDrawerToggler) {
    navbarDrawerToggler.addEventListener("click", openDrawer);
  }
  if (navbarDrawerClose) {
    navbarDrawerClose.addEventListener("click", closeDrawer);
  }
  if (navbarOverlay) {
    navbarOverlay.addEventListener("click", closeDrawer);
  }

  // Logout from drawer
  const logoutFromDrawer = document.getElementById("logoutFromDrawer");
  if (logoutFromDrawer) {
    logoutFromDrawer.addEventListener("click", function (e) {
      e.preventDefault();
      closeDrawer();
      setTimeout(() => handleLogout(), 150);
    });
  }

  // One-shot click animation for drawer items
  document.querySelectorAll("#navbarDrawer .nav-link").forEach(function (link) {
    link.addEventListener("click", function () {
      link.classList.remove("drawer-item-click");
      void link.offsetWidth;
      link.classList.add("drawer-item-click");
    });
  });
});

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
