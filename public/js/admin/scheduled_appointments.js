document.addEventListener("DOMContentLoaded", function () {
  // Initialize Bootstrap components
  const statusToast = new bootstrap.Toast(
    document.getElementById("statusToast")
  );

  // Get DOM elements
  const loadingIndicator = document.getElementById("loading-indicator");
  const emptyMessage = document.getElementById("empty-message");
  const appointmentsTableContainer = document.getElementById(
    "appointments-table-container"
  );
  const appointmentsBody = document.getElementById("appointments-body");

  // Store original appointments data for search filtering
  let originalAppointments = [];
  let filteredAppointments = [];

  // Make header sticky on scroll
  initStickyHeader();

  // Initialize search functionality
  initSearchFunctionality();

  // Load appointments on page load
  loadAppointments();

  // Listen for storage events to refresh when appointments are updated from another page
  window.addEventListener("storage", function (e) {
    if (e.key === "scheduledAppointments") {
      // Reload appointments when storage changes
      loadAppointments();
      showToast("Info", "Appointments list has been updated with new data");
    }
  });

  /**
   * Initialize sticky header behavior
   */
  function initStickyHeader() {
    const header = document.querySelector("header");
    const main = document.querySelector("main");

    if (header && main) {
      // Store the original header height
      const headerHeight = header.offsetHeight;

      // Create a placeholder div to prevent content jump
      const placeholder = document.createElement("div");
      placeholder.style.display = "none";
      placeholder.style.height = headerHeight + "px";
      document.body.insertBefore(placeholder, main);

      window.onscroll = function () {
        if (window.pageYOffset > 10) {
          header.classList.add("sticky-header");
          placeholder.style.display = "block";
        } else {
          header.classList.remove("sticky-header");
          placeholder.style.display = "none";
        }
      };

      // Update placeholder height when window is resized
      window.addEventListener("resize", function () {
        const newHeaderHeight = header.offsetHeight;
        placeholder.style.height = newHeaderHeight + "px";
      });
    }
  }

  /**
   * Initialize search functionality for appointments table
   */
  function initSearchFunctionality() {
    const searchInput = document.getElementById("appointmentsSearchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");

    if (!searchInput) {
      return;
    }

    // Search input event listener
    searchInput.addEventListener("input", function () {
      const searchQuery = this.value.trim().toLowerCase();
      filterAppointmentsTable(searchQuery);

      // Show/hide clear button
      if (searchQuery.length > 0) {
        clearSearchBtn.style.display = "block";
      } else {
        clearSearchBtn.style.display = "none";
      }
    });

    // Clear search button event listener
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener("click", function () {
        searchInput.value = "";
        clearSearchBtn.style.display = "none";
        filterAppointmentsTable("");
      });
    }
  }

  /**
   * Filter appointments table based on search query
   * @param {string} searchQuery - The search query string
   */
  function filterAppointmentsTable(searchQuery) {
    const tableRows = appointmentsBody.querySelectorAll("tr");

    if (!searchQuery || searchQuery.length === 0) {
      // Show all rows if search is empty
      tableRows.forEach((row) => {
        row.style.display = "";
      });

      // Show/hide empty message based on original data
      if (originalAppointments.length === 0) {
        emptyMessage.classList.remove("d-none");
        appointmentsTableContainer.classList.add("d-none");
      } else {
        emptyMessage.classList.add("d-none");
        appointmentsTableContainer.classList.remove("d-none");
      }
      return;
    }

    let visibleRowCount = 0;

    // Filter rows based on search query
    tableRows.forEach((row) => {
      const cells = row.querySelectorAll("td");
      let rowText = "";

      // Collect all text content from table cells
      cells.forEach((cell) => {
        if (cell) {
          rowText += " " + cell.textContent.trim().toLowerCase();
        }
      });

      // Check if search query matches any cell content
      if (rowText.includes(searchQuery)) {
        row.style.display = "";
        visibleRowCount++;
      } else {
        row.style.display = "none";
      }
    });

    // Show/hide empty message based on filtered results
    if (visibleRowCount === 0 && originalAppointments.length > 0) {
      emptyMessage.textContent =
        "No appointments match your search criteria.";
      emptyMessage.classList.remove("d-none");
    } else if (visibleRowCount > 0) {
      emptyMessage.classList.add("d-none");
      appointmentsTableContainer.classList.remove("d-none");
    }
  }

  /**
   * Load appointments from the server
   */
  function loadAppointments() {
    SecureLogger.info("Starting to load appointments...");

    loadingIndicator.classList.remove("d-none");
    appointmentsTableContainer.classList.add("d-none");
    emptyMessage.classList.add("d-none");

    const timestamp = new Date().getTime();
    const url =
      (window.BASE_URL || "/") +
      `admin/appointments/scheduled/get?_=${timestamp}`;

    fetch(url, {
      method: "GET",
      credentials: "include",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Cache-Control": "no-cache",
      },
    })
      .then((response) => {
        if (!response.ok) {
          if (response.status === 401) {
            throw new Error("Session expired - Please log in again");
          }
          throw new Error(`Network response was not ok: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        SecureLogger.info("Received data:", data);

        if (data.status === "success") {
          if (
            Array.isArray(data.appointments) &&
            data.appointments.length > 0
          ) {
            originalAppointments = data.appointments;
            filteredAppointments = data.appointments;
            displayAppointments(data.appointments);
            appointmentsTableContainer.classList.remove("d-none");
            emptyMessage.classList.add("d-none");
          } else {
            originalAppointments = [];
            filteredAppointments = [];
            emptyMessage.textContent =
              data.message || "No approved appointments found";
            emptyMessage.classList.remove("d-none");
            appointmentsTableContainer.classList.add("d-none");
          }
        } else {
          if (data.message && data.message.includes("session")) {
            window.location.href = (window.BASE_URL || "/") + "auth/logout";
          } else {
            throw new Error(data.message || "Failed to load appointments");
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        originalAppointments = [];
        filteredAppointments = [];
        if (
          error.message.includes("session") ||
          error.message.includes("log in")
        ) {
          window.location.href = (window.BASE_URL || "/") + "auth/logout";
        } else {
          emptyMessage.textContent = error.message;
          emptyMessage.classList.remove("d-none");
          appointmentsTableContainer.classList.add("d-none");
        }
      })
      .finally(() => {
        loadingIndicator.classList.add("d-none");
      });
  }

  /**
   * Display appointments in the table
   */
  function displayAppointments(appointments) {
    SecureLogger.info("Starting to display appointments..."); // Debug log

    // Clear the table
    appointmentsBody.innerHTML = "";

    // Check if there are appointments
    if (!appointments || appointments.length === 0) {
      SecureLogger.info("No appointments to display"); // Debug log
      emptyMessage.classList.remove("d-none");
      appointmentsTableContainer.classList.add("d-none");
      return;
    }

    // Store current search query to reapply after rendering
    const searchInput = document.getElementById("appointmentsSearchInput");
    const currentSearchQuery = searchInput
      ? searchInput.value.trim().toLowerCase()
      : "";

    SecureLogger.info("Processing appointments:", appointments); // Debug log

    // Add appointments to the table
    appointments.forEach((appointment, index) => {
      SecureLogger.info(`Processing appointment ${index + 1}:`, appointment); // Debug log

      const row = document.createElement("tr");
      row.dataset.id = appointment.id;

      // Add appropriate class based on status
      if (appointment.status === "COMPLETED") {
        row.classList.add("table-success");
      } else {
        const appointmentDate = new Date(
          appointment.appointed_date || appointment.preferred_date
        );
        if (isToday(appointmentDate)) {
          row.classList.add("table-primary");
        }
      }

      // Format date
      const appointmentDate = new Date(
        appointment.appointed_date || appointment.preferred_date
      );
      const formattedDate = appointmentDate.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });

      // Format time
      const formattedTime = formatTime(
        appointment.time || appointment.preferred_time
      );

      // Create table row with exact column order matching your HTML
      row.innerHTML = `
                <td>${appointment.student_id || "N/A"}</td>
                <td>${appointment.student_name || "N/A"}</td>
                <td>${formattedDate || "Invalid Date"}</td>
                <td>${formattedTime || "N/A"}</td>
                <td>${appointment.consultation_type || "Individual Consultation"}</td>
                <td>${appointment.purpose || "N/A"}</td>
                <td>${appointment.counselorPreference || "No Preference"}</td>
                <td class="text-center">
                    ${
                      appointment.status === "COMPLETED"
                        ? '<span class="badge bg-success">Completed</span>'
                        : appointment.status === "CANCELLED"
                        ? '<span class="badge bg-danger">Cancelled</span>'
                        : '<span class="badge bg-primary">Approved</span>'
                    }
                </td>
            `;

      appointmentsBody.appendChild(row);
    });

    // Add event listeners to Mark Complete and Cancel buttons
    document.querySelectorAll(".mark-complete-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const appointmentId = this.getAttribute("data-id");
        updateAppointmentStatus(appointmentId, "COMPLETED");
      });
    });

    document.querySelectorAll(".cancel-appointment-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const appointmentId = this.getAttribute("data-id");
        // Show cancellation reason modal with backdrop: 'static' and keyboard: false
        const cancellationModal = new bootstrap.Modal(
          document.getElementById("cancellationReasonModal"),
          {
            backdrop: "static",
            keyboard: false,
          }
        );
        cancellationModal.show();

        // Store the appointment ID for later use
        document.getElementById(
          "cancellationReasonModal"
        ).dataset.appointmentId = appointmentId;
      });
    });

    // Add event listener for cancellation confirmation
    document
      .getElementById("confirmCancellationBtn")
      .addEventListener("click", function () {
        const cancellationReason = document
          .getElementById("cancellationReason")
          .value.trim();
        if (!cancellationReason) {
          showToast("Error", "Please provide a reason for cancellation.");
          return;
        }

        // Get the appointment ID from the modal's dataset
        const appointmentId = document.getElementById("cancellationReasonModal")
          .dataset.appointmentId;

        // Disable the confirm button and show loading state
        const confirmBtn = document.getElementById("confirmCancellationBtn");
        const originalBtnText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        // Create form data
        const formData = new FormData();
        formData.append("appointment_id", appointmentId);
        formData.append("status", "CANCELLED");
        formData.append("rejection_reason", cancellationReason); // Using the same field name as rejection

        // Send request to update appointment status
        fetch(
          (window.BASE_URL || "/") +
            "admin/appointments/updateAppointmentStatus",
          {
            method: "POST",
            body: formData,
            credentials: "include",
            headers: {
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
          .then((response) => {
            if (!response.ok) {
              if (response.status === 401) {
                window.location.href = (window.BASE_URL || "/") + "";
                throw new Error(
                  "Your session has expired. Please log in again."
                );
              }
              throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
          })
          .then((data) => {
            if (data.status === "success") {
              // Close the cancellation modal
              const cancellationModal = bootstrap.Modal.getInstance(
                document.getElementById("cancellationReasonModal")
              );
              if (cancellationModal) {
                cancellationModal.hide();
              }

              // Show success message
              showToast(
                "Success",
                "Appointment cancelled successfully! An email notification has been sent to the user."
              );

              // Reload appointments to reflect changes
              loadAppointments();
            } else {
              throw new Error(data.message || "Failed to cancel appointment");
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showToast(
              "Error",
              error.message ||
                "An error occurred while cancelling the appointment."
            );
          })
          .finally(() => {
            // Re-enable the confirm button and restore original text
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalBtnText;
          });
      });

    // Reset cancellation reason when modal is closed
    document
      .getElementById("cancellationReasonModal")
      .addEventListener("hidden.bs.modal", function () {
        document.getElementById("cancellationReason").value = "";
        delete this.dataset.appointmentId;
      });

    // Show the table and hide empty message
    appointmentsTableContainer.classList.remove("d-none");
    emptyMessage.classList.add("d-none");
    
    // Reapply search filter if there's an active search query
    if (currentSearchQuery && currentSearchQuery.length > 0) {
      filterAppointmentsTable(currentSearchQuery);
    }
    
    // Update calendar with appointments
    updateCalendarWithAppointments(appointments);
  }

  /**
   * Check if a date is today
   */
  function isToday(date) {
    const today = new Date();
    return (
      date.getDate() === today.getDate() &&
      date.getMonth() === today.getMonth() &&
      date.getFullYear() === today.getFullYear()
    );
  }

  /**
   * Format time string (HH:MM) to AM/PM format
   */
  function formatTime(timeString) {
    if (!timeString) return "N/A";

    // If the time already contains AM or PM, just return it as is
    if (timeString.includes("AM") || timeString.includes("PM")) {
      return timeString;
    }

    // Check if it's a time range (contains a hyphen)
    if (timeString.includes("-")) {
      const [startTime, endTime] = timeString
        .split("-")
        .map((time) => time.trim());
      const [startHours, startMinutes] = startTime.split(":");
      const [endHours, endMinutes] = endTime.split(":");

      const startHour = parseInt(startHours, 10);
      const endHour = parseInt(endHours, 10);

      const startAmPm = startHour >= 12 ? "PM" : "AM";
      const endAmPm = endHour >= 12 ? "PM" : "AM";

      const formattedStartHour = startHour % 12 || 12;
      const formattedEndHour = endHour % 12 || 12;

      return `${formattedStartHour}:${
        startMinutes || "00"
      } ${startAmPm} - ${formattedEndHour}:${endMinutes || "00"} ${endAmPm}`;
    }

    return formatSingleTime(timeString);
  }

  function formatSingleTime(time) {
    if (!time) return "N/A";

    // Check if already in 12-hour format (contains AM/PM)
    if (time.includes('AM') || time.includes('PM')) {
      return time;
    }

    // Convert from 24-hour format to 12-hour format
    const [hours, minutes] = time.split(":");
    const hour = parseInt(hours, 10);
    const ampm = hour >= 12 ? "PM" : "AM";
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${minutes || "00"} ${ampm}`;
  }

  /**
   * Update appointment status in the database
   */
  function updateAppointmentStatus(appointmentId, newStatus) {
    SecureLogger.info(`Updating appointment ${appointmentId} status to ${newStatus}`);

    // Disable the buttons to prevent multiple submissions
    const buttons = document.querySelectorAll(
      `.mark-complete-btn[data-id="${appointmentId}"], .cancel-appointment-btn[data-id="${appointmentId}"]`
    );
    buttons.forEach((button) => {
      button.disabled = true;
      button.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    });

    // Create form data
    const formData = new FormData();
    formData.append("appointment_id", appointmentId);
    formData.append("status", newStatus);

    // Send request to update status
    fetch(
      (window.BASE_URL || "/") + "admin/appointments/updateAppointmentStatus",
      {
        method: "POST",
        body: formData,
        credentials: "include",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      }
    )
      .then((response) => {
        if (!response.ok) {
          if (response.status === 401) {
            window.location.href = (window.BASE_URL || "/") + "";
            throw new Error("Session expired - Please log in again");
          }
          throw new Error(`Server error: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        SecureLogger.info("Response:", data);

        if (data.status === "success") {
          // Update the row in the table
          const row = document.querySelector(`tr[data-id="${appointmentId}"]`);
          if (row) {
            // Add appropriate class based on status
            row.classList.remove("table-primary", "table-success");
            if (newStatus === "COMPLETED") {
              row.classList.add("table-success");
            } else if (newStatus === "CANCELLED") {
              row.classList.add("table-danger");
            }

            // Update status cell
            const statusCell = row.querySelector("td:nth-last-child(2)");
            if (statusCell) {
              statusCell.innerHTML =
                newStatus === "COMPLETED"
                  ? '<span class="badge bg-success">Completed</span>'
                  : '<span class="badge bg-danger">Cancelled</span>';
            }

            // Update action cell
            const actionCell = row.querySelector("td:last-child");
            if (actionCell) {
              actionCell.innerHTML =
                '<span class="text-muted">No actions available</span>';
            }
          }

          // Show success message
          showToast(
            "Success",
            `Appointment ${newStatus.toLowerCase()} successfully`
          );
        } else {
          if (data.message && data.message.includes("session")) {
            window.location.href = "../index.php";
          } else {
            showToast(
              "Error",
              data.message || "Failed to update appointment status"
            );
            // Re-enable the buttons
            buttons.forEach((button) => {
              button.disabled = false;
              button.innerHTML =
                newStatus === "COMPLETED"
                  ? '<i class="fas fa-check me-1"></i>Mark Complete'
                  : '<i class="fas fa-times me-1"></i>Cancel';
            });
          }
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast(
          "Error",
          error.message || "Failed to update appointment status"
        );
        // Re-enable the buttons
        buttons.forEach((button) => {
          button.disabled = false;
          button.innerHTML =
            newStatus === "COMPLETED"
              ? '<i class="fas fa-check me-1"></i>Mark Complete'
              : '<i class="fas fa-times me-1"></i>Cancel';
        });
      });
  }

  /**
   * Show toast notification
   */
  function showToast(title, message) {
    const toastTitle = document.querySelector(
      "#statusToast .toast-header strong"
    );
    const toastBody = document.querySelector("#statusToast .toast-body");
    const toastTime = document.querySelector(
      "#statusToast .toast-header small"
    );

    if (toastTitle) toastTitle.textContent = title;
    if (toastBody) toastBody.textContent = message;
    if (toastTime) toastTime.textContent = "Just now";

    const toast = bootstrap.Toast.getInstance(
      document.getElementById("statusToast")
    );
    if (toast) {
      toast.show();
    } else {
      const newToast = new bootstrap.Toast(
        document.getElementById("statusToast")
      );
      newToast.show();
    }
  }
});

class AppointmentCalendar {
  constructor() {
    this.currentDate = new Date();
    this.appointments = [];
    this.init();
  }

  init() {
    this.createCalendarHTML();
    this.renderCalendar();
    this.attachEventListeners();
  }

  createCalendarHTML() {
    // Admin page already has sidebar calendar markup (mini-calendar-card)
    // No dynamic HTML injection needed; just ensure elements exist
    return;
  }

  attachEventListeners() {
    document.getElementById("prevMonth")?.addEventListener("click", () => {
      this.currentDate.setMonth(this.currentDate.getMonth() - 1);
      this.renderCalendar();
    });

    document.getElementById("nextMonth")?.addEventListener("click", () => {
      this.currentDate.setMonth(this.currentDate.getMonth() + 1);
      this.renderCalendar();
    });
  }

  setAppointments(appointments) {
    this.appointments = appointments;
    this.renderCalendar();
  }

  getAppointmentCountForDate(date) {
    // Compare using local YYYY-MM-DD to avoid timezone shifts
    const toYmd = (d) => {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, "0");
      const da = String(d.getDate()).padStart(2, "0");
      return `${y}-${m}-${da}`;
    };
    const dateStr = toYmd(date);

    return this.appointments.filter((apt) => {
      const status = (apt.status || "").toString().toLowerCase();
      const isApproved = status === "approved" || status === "scheduled" || status === "approved\n";
      const raw = apt.appointed_date || apt.preferred_date || apt.appointedDate || apt.preferredDate;
      if (!raw || !isApproved) return false;
      const d = new Date(raw);
      if (isNaN(d.getTime())) return false;
      return toYmd(d) === dateStr;
    }).length;
  }

  renderCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const monthNames = [
      "January","February","March","April","May","June",
      "July","August","September","October","November","December"
    ];

    const monthYearEl = document.getElementById("monthYear");
    if (monthYearEl) monthYearEl.textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    const calendarDays = document.getElementById("calendarDays");
    if (!calendarDays) return;
    calendarDays.innerHTML = "";

    for (let i = 0; i < firstDay; i++) {
      const emptyDay = document.createElement("div");
      emptyDay.className = "calendar-day empty";
      calendarDays.appendChild(emptyDay);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement("div");
      dayElement.className = "calendar-day";

      const currentLoopDate = new Date(year, month, day);
      const appointmentCount = this.getAppointmentCountForDate(currentLoopDate);

      if (
        day === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear()
      ) {
        dayElement.classList.add("today");
      }

      if (appointmentCount > 0) {
        dayElement.classList.add("has-appointment");
        dayElement.innerHTML = `
            <span class="day-number">${day}</span>
            <span class="appointment-badge">${appointmentCount}</span>
        `;
        dayElement.title = `${appointmentCount} appointment${appointmentCount > 1 ? "s" : ""}`;
      } else {
        dayElement.innerHTML = `<span class="day-number">${day}</span>`;
      }

      calendarDays.appendChild(dayElement);
    }
  }
}

let appointmentCalendar;

function updateCalendarWithAppointments(appointments) {
  if (!appointmentCalendar) {
    appointmentCalendar = new AppointmentCalendar();
  }
  appointmentCalendar.setAppointments(appointments);
}

// Ensure calendar initialized once DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  appointmentCalendar = new AppointmentCalendar();
});