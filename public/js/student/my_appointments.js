let pendingSaveContext = null;
let pendingCancelContext = null;
let pendingDeleteId = null;

// ---- Availability helpers (must be global for use outside DOMContentLoaded) ----
function getDayOfWeek(dateString) {
  const date = new Date(dateString);
  const days = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ];
  return days[date.getDay()];
}

function normalizePreferredTimeTo24hRange(rangeStr) {
  if (!rangeStr || typeof rangeStr !== "string") return null;
  const parts = rangeStr.split("-").map(function (p) {
    return p.trim();
  });
  if (parts.length !== 2) return null;
  function to24h(t) {
    const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let hour = parseInt(match[1], 10);
    const minute = match[2];
    const ampm = match[3].toUpperCase();
    if (ampm === "AM") {
      if (hour === 12) hour = 0;
    } else {
      if (hour !== 12) hour += 12;
    }
    return String(hour).padStart(2, "0") + ":" + minute;
  }
  const start = to24h(parts[0]);
  const end = to24h(parts[1]);
  if (!start || !end) return null;
  return start + "-" + end;
}

function extractStartEnd24h(rangeStr) {
  if (!rangeStr || typeof rangeStr !== "string") return null;
  const parts = rangeStr.split("-").map(function (p) {
    return p.trim();
  });
  if (parts.length !== 2) return null;
  function to24h(t) {
    const match = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let hour = parseInt(match[1], 10);
    const minute = match[2];
    const ampm = match[3].toUpperCase();
    if (ampm === "AM") {
      if (hour === 12) hour = 0;
    } else {
      if (hour !== 12) hour += 12;
    }
    return String(hour).padStart(2, "0") + ":" + minute;
  }
  const start = to24h(parts[0]);
  const end = to24h(parts[1]);
  if (!start || !end) return null;
  return { start: start, end: end };
}

document.addEventListener("DOMContentLoaded", function () {
  // Initialize variables
  let allAppointments = [];
  const searchInput = document.getElementById("searchInput");
  const dateFilter = document.getElementById("dateFilter");
  const loadingSpinner = document.querySelector(".loading-spinner");
  const emptyState = document.querySelector(".empty-state");
  let counselorsCache = null;

  // Fetch appointments when the page loads
  fetchAppointments();

  // Add event listeners
  if (searchInput) searchInput.addEventListener("input", filterAppointments);
  if (dateFilter) dateFilter.addEventListener("change", filterAppointments);

  // Setup counselor schedules in drawer
  setupCounselorSchedulesInDrawer();
  // ==================== Counselors' Schedules Calendar ====================
  (function initializeCounselorsCalendar() {
    const grid = document.getElementById("counselorsCalendarGrid");
    const monthLabel = document.getElementById("counselorsCurrentMonth");
    const prevBtn = document.getElementById("counselorsPrevMonth");
    const nextBtn = document.getElementById("counselorsNextMonth");
    if (!grid || !monthLabel) return;

    let calDate = new Date();
    let monthStatsCache = {}; // key: YYYY-MM -> stats object

    function monthName(idx) {
      return [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ][idx];
    }
    function sameDay(a, b) {
      return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
      );
    }
    function iso(date) {
      return date.toISOString().split("T")[0];
    }

    async function fetchMonthStats(year, monthIndex) {
      const key = year + "-" + String(monthIndex + 1).padStart(2, "0");
      if (monthStatsCache[key]) return monthStatsCache[key];
      try {
        const url = new URL(
          (window.BASE_URL || "/") + "student/calendar/daily-stats"
        );
        url.searchParams.append("year", String(year));
        url.searchParams.append("month", String(monthIndex + 1));
        const res = await fetch(url.toString(), {
          method: "GET",
          credentials: "include",
          headers: { Accept: "application/json" },
        });
        if (!res.ok) throw new Error("Failed to load calendar stats");
        const data = await res.json();
        if (data && data.status === "success" && data.stats) {
          monthStatsCache[key] = data.stats;
          return data.stats;
        }
      } catch (e) {
        /* noop: fallback to no stats */
      }
      monthStatsCache[key] = {};
      return {};
    }

    async function render() {
      grid.innerHTML = "";
      monthLabel.textContent =
        monthName(calDate.getMonth()) + " " + calDate.getFullYear();

      // Fetch stats for this month
      const stats = await fetchMonthStats(
        calDate.getFullYear(),
        calDate.getMonth()
      );

      const headers = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
      headers.forEach((h) => {
        const el = document.createElement("div");
        el.className = "calendar-day-header";
        el.textContent = h;
        grid.appendChild(el);
      });

      const first = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
      const last = new Date(calDate.getFullYear(), calDate.getMonth() + 1, 0);
      for (let i = 0; i < first.getDay(); i++) {
        const pad = document.createElement("div");
        pad.className = "calendar-day other-month";
        grid.appendChild(pad);
      }

      for (let d = 1; d <= last.getDate(); d++) {
        const cell = document.createElement("div");
        cell.className = "calendar-day";
        const thisDate = new Date(calDate.getFullYear(), calDate.getMonth(), d);
        const y = thisDate.getFullYear();
        const m = String(thisDate.getMonth() + 1).padStart(2, "0");
        const dd = String(d).padStart(2, "0");
        const isoDate = y + "-" + m + "-" + dd;

        // Layout container
        cell.style.display = "flex";
        cell.style.flexDirection = "column";
        cell.style.alignItems = "center";
        cell.style.justifyContent = "flex-start";
        cell.style.position = "relative"; // enable absolute overlay for badge

        // Day number label
        const dayNum = document.createElement("div");
        dayNum.textContent = String(d);
        dayNum.className = "day-number";
        dayNum.style.marginTop = "4px";
        cell.appendChild(dayNum);

        // Apply today highlight
        if (sameDay(thisDate, new Date())) cell.classList.add("today");

        // Stats badge and fully booked label (both appear below the day number)
        const st = stats[isoDate];
        if (st && typeof st.count === "number" && st.count > 0) {
          const badge = document.createElement("span");
          badge.className = "appt-badge";
          badge.textContent = String(st.count);
          badge.title = "Approved appointments";
          // Overlay above the day number (top-right)
          badge.style.position = "absolute";
          badge.style.top = "4px";
          badge.style.right = "6px";
          badge.style.minWidth = "18px";
          badge.style.height = "18px";
          badge.style.borderRadius = "9px";
          badge.style.backgroundColor = "#0d6efd";
          badge.style.color = "#fff";
          badge.style.fontSize = "11px";
          badge.style.lineHeight = "18px";
          badge.style.textAlign = "center";
          badge.style.pointerEvents = "none";
          cell.appendChild(badge);
        }

        if (st && st.fullyBooked === true) {
          // Red highlight only when fully booked
          cell.classList.add("fully-booked");
          cell.style.backgroundColor = "#fde2e1";
          cell.style.borderColor = "#f8b4b4";
          // Add a small "Fully booked" label under the badge/number
          const fb = document.createElement("div");
          fb.textContent = "Fully booked";
          fb.style.marginTop = "4px";
          fb.style.fontSize = "11px";
          fb.style.color = "#b91c1c";
          cell.appendChild(fb);
        }

        grid.appendChild(cell);
      }
    }

    if (prevBtn)
      prevBtn.addEventListener("click", () => {
        calDate.setMonth(calDate.getMonth() - 1);
        render();
      });
    if (nextBtn)
      nextBtn.addEventListener("click", () => {
        calDate.setMonth(calDate.getMonth() + 1);
        render();
      });
    render();
  })();

  // Add event listeners for tab changes
  document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
    tab.addEventListener("shown.bs.tab", handleTabChange);
  });

  // Initialize modals
  const editModal = new bootstrap.Modal(
    document.getElementById("editAppointmentModal")
  );
  const cancelModal = new bootstrap.Modal(
    document.getElementById("cancelAppointmentModal")
  );

  // Add event listeners for modal buttons
  document.getElementById("saveEditBtn").addEventListener("click", saveEdit);
  document
    .getElementById("confirmCancelBtn")
    .addEventListener("click", confirmCancel);

  async function fetchAppointments() {
    try {
      showLoading();
      const response = await fetch(
        (window.BASE_URL || "/") + "student/appointments/get-my-appointments",
        {
          method: "GET",
          credentials: "include",
          headers: {
            "Cache-Control": "no-cache",
            Pragma: "no-cache",
          },
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        allAppointments = data.appointments;
        updateInitialDisplay();

        if (allAppointments.length === 0) {
          showEmptyState();
        } else {
          hideEmptyState();
        }
      } else {
        showError(data.message || "Failed to fetch appointments");
      }
    } catch (error) {
      console.error("Error fetching appointments:", error);
      showError("An error occurred while fetching appointments");
    } finally {
      hideLoading();
    }
  }

  function updateInitialDisplay() {
    // Display approved appointments first
    const approvedAppointments = allAppointments.filter(
      (app) => app.status && app.status.toUpperCase() === "APPROVED"
    );
    displayApprovedAppointments(approvedAppointments);

    // Display pending appointments
    const pendingAppointments = allAppointments.filter(
      (app) => app.status && app.status.toUpperCase() === "PENDING"
    );
    displayPendingAppointments(pendingAppointments);

    // Display all appointments
    displayAppointments(allAppointments, "allAppointmentsTable");

    // Display appointments for each status tab (approved tab removed - using card display instead)

    const rejectedAppointments = allAppointments.filter(
      (app) => app.status && app.status.toUpperCase() === "REJECTED"
    );
    displayAppointments(rejectedAppointments, "rejectedAppointmentsTable");

    const completedAppointments = allAppointments.filter(
      (app) => app.status && app.status.toUpperCase() === "COMPLETED"
    );
    displayAppointments(completedAppointments, "completedAppointmentsTable");

    const cancelledAppointments = allAppointments.filter(
      (app) => app.status && app.status.toUpperCase() === "CANCELLED"
    );
    displayAppointments(cancelledAppointments, "cancelledAppointmentsTable");
  }

  async function fetchCounselors() {
    if (counselorsCache) return counselorsCache;
    try {
      const response = await fetch(
        (window.BASE_URL || "/") + "student/get-counselors"
      );
      const data = await response.json();
      if (data.status === "success" && Array.isArray(data.counselors)) {
        counselorsCache = data.counselors;
        return counselorsCache;
      } else {
        return [];
      }
    } catch (error) {
      console.error("Error fetching counselors:", error);
      return [];
    }
  }

  async function displayApprovedAppointments(appointments) {
    const container = document.getElementById("approvedAppointmentsContainer");
    if (!container) return;
    container.innerHTML = "";

    if (!appointments || appointments.length === 0) {
      container.innerHTML = `
                <div class="no-approved-appointments">
                    <i class="fas fa-calendar-check"></i>
                    <h4>No Approved Appointments</h4>
                    <p>You don't have any approved appointments at the moment.</p>
                </div>
            `;
      return;
    }

    // Display the first approved appointment (most recent)
    const appointment = appointments[0];
    const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
    const qrCodeData = JSON.stringify({
      appointmentId: appointment.id,
      studentId: appointment.student_id,
      date: appointment.preferred_date,
      time: appointment.preferred_time,
      counselor: appointment.counselor_name,
      type: appointment.consultation_type,
      purpose: appointment.purpose,
      ticketId: ticketId,
    });

    const ticketHtml = await generateAppointmentTicket(appointment);
    container.innerHTML = ticketHtml;

    // Generate QR code after DOM is updated - wait longer to ensure QRCode library is loaded
    setTimeout(() => {
      generateQRCode(appointment.id, qrCodeData);
    }, 500);

    // Add event listener for download button
    const downloadBtn = container.querySelector(".download-ticket-btn");
    if (downloadBtn) {
      downloadBtn.addEventListener("click", () =>
        downloadAppointmentTicket(appointment)
      );
    }
  }

  async function displayPendingAppointments(appointments) {
    const container = document.getElementById(
      "pendingAppointmentsFormsContainer"
    );
    if (!container) return;
    container.innerHTML = "";

    if (!appointments || appointments.length === 0) {
      container.innerHTML =
        '<div class="alert alert-info text-center">No pending appointments</div>';
      return;
    }

    // Fetch counselors once
    const counselors = await fetchCounselors();

    appointments.forEach((appointment) => {
      const form = document.createElement("form");
      form.className =
        "pending-appointment-form mb-2 p-3 border rounded shadow-sm";
      // Build counselor options
      let counselorOptions = '<option value="">Select a counselor</option>';
      counselorOptions += `<option value="No preference"${
        appointment.counselor_preference === "No preference" ? " selected" : ""
      }>No preference</option>`;
      counselors.forEach((counselor) => {
        const selected =
          appointment.counselor_preference == counselor.counselor_id
            ? " selected"
            : "";
        counselorOptions += `<option value="${counselor.counselor_id}"${selected}>${counselor.name}</option>`;
      });
      form.innerHTML = `
                <div class="row g-3 align-items-center">
                    <div class="row g-3 align-items-center mt-1"><div class="col-md-4">
                        <label class="form-label mb-1">Consultation Type</label>
                        <select class="form-control" name="consultation_type" disabled>
                            <option value="">Select consultation type</option>
                            <option value="Individual Consultation"${
                              appointment.consultation_type ===
                              "Individual Consultation"
                                ? " selected"
                                : ""
                            }>Individual Consultation</option>
                            <option value="Group Consultation"${
                              appointment.consultation_type ===
                              "Group Consultation"
                                ? " selected"
                                : ""
                            }>Group Consultation</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Preferred Date</label>
                        <input type="date" class="form-control" name="preferred_date" value="${
                          appointment.preferred_date
                        }" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Preferred Time</label>
                        <select class="form-control" name="preferred_time" disabled>
                            <option value="">Select a time slot</option>
                            <option value="${
                              appointment.preferred_time
                            }" selected>${appointment.preferred_time}</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 align-items-center mt-1">

                    <div class="col-md-4">
                        <label class="form-label mb-1">Counselor Preference</label>
                        <select class="form-control" name="counselor_preference" disabled>${counselorOptions}</select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label mb-1">Method Type</label>
                        <select class="form-control" name="method_type" disabled>
                            <option value="">Select a method type</option>
                            <option value="In-person"${
                              appointment.method_type === "In-person"
                                ? " selected"
                                : ""
                            }>In-person</option>
                            <option value="Online (Video)"${
                              appointment.method_type === "Online (Video)"
                                ? " selected"
                                : ""
                            }>Online (Video)</option>
                            <option value="Online (Audio only)"${
                              appointment.method_type === "Online (Audio only)"
                                ? " selected"
                                : ""
                            }>Online (Audio only)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Purpose</label>
                        <select class="form-control" name="purpose" disabled>
                            <option value="">Select purpose...</option>
                            <option value="Counseling"${
                              appointment.purpose === "Counseling"
                                ? " selected"
                                : ""
                            }>Counseling</option>
                            <option value="Psycho-Social Support"${
                              appointment.purpose === "Psycho-Social Support"
                                ? " selected"
                                : ""
                            }>Psycho-Social Support</option>
                            <option value="Initial Interview"${
                              appointment.purpose === "Initial Interview"
                                ? " selected"
                                : ""
                            }>Initial Interview</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label class="form-label mb-1">Brief Description(Optional)</label>
                        <textarea class="form-control" name="description" rows="2" disabled>${
                          appointment.description || ""
                        }</textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-secondary btn-sm me-2 enable-edit-btn">Enable Edit</button>
                        <button type="button" class="btn btn-primary btn-sm me-2 save-changes-btn" disabled>
                            <i class="fas fa-edit"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-danger btn-sm cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            `;
      setTimeout(() => {
        const enableBtn = form.querySelector(".enable-edit-btn");
        const saveBtn = form.querySelector(".save-changes-btn");
        const deleteBtn = form.querySelector(".delete-btn");
        const cancelBtn = form.querySelector(".cancel-btn");
        const inputs = form.querySelectorAll("input, select, textarea");
        const dateInput = form.querySelector('[name="preferred_date"]');
        const timeSelect = form.querySelector('[name="preferred_time"]');
        const counselorSelect = form.querySelector(
          '[name="counselor_preference"]'
        );
        const consultationTypeSelect = form.querySelector(
          '[name="consultation_type"]'
        );

        enableBtn.addEventListener("click", async function () {
          const editing = enableBtn.dataset.editing === "true";
          if (!editing) {
            inputs.forEach((input) => {
              // Keep counselor preference disabled - it cannot be changed for pending appointments
              if (input.name === "counselor_preference") {
                input.disabled = true;
                input.readOnly = true;
              } else if (input.name !== undefined && input.name !== "") {
                input.disabled = false;
                input.readOnly = false;
              }
            });
            saveBtn.disabled = false;
            enableBtn.textContent = "Cancel Edit";
            enableBtn.dataset.editing = "true";

            // When entering edit mode, load time slots for the selected counselor only
            if (
              dateInput &&
              timeSelect &&
              counselorSelect &&
              consultationTypeSelect
            ) {
              const currentDate = dateInput.value;
              // Use the original appointment's counselor preference (cannot be changed)
              const originalCounselor =
                appointment.counselor_preference || counselorSelect.value;
              const currentConsultationType =
                appointment.consultation_type || consultationTypeSelect.value;
              const currentTime = appointment.preferred_time;

              // Load time slots with 30-minute intervals for the selected counselor only
              await refreshPendingAppointmentTimeSlots(
                form,
                currentDate,
                currentTime,
                originalCounselor,
                currentConsultationType
              );

              // Add event listeners for dynamic updates (date and consultation type only)
              dateInput.addEventListener("change", async function () {
                const date = dateInput.value;
                const consultationType = consultationTypeSelect.value;
                const time = timeSelect.value;
                // Always use original counselor - cannot change
                await refreshPendingAppointmentTimeSlots(
                  form,
                  date,
                  time,
                  originalCounselor,
                  consultationType
                );
              });

              consultationTypeSelect.addEventListener(
                "change",
                async function () {
                  const date = dateInput.value;
                  const consultationType = consultationTypeSelect.value;
                  const time = timeSelect.value;
                  // Always use original counselor - cannot change
                  await refreshPendingAppointmentTimeSlots(
                    form,
                    date,
                    time,
                    originalCounselor,
                    consultationType
                  );
                }
              );
            }
          } else {
            inputs.forEach((input) => {
              if (input.name !== undefined && input.name !== "") {
                input.disabled = true;
                input.readOnly = true;
              }
            });
            saveBtn.disabled = true;
            enableBtn.textContent = "Enable Edit";
            enableBtn.dataset.editing = "false";
          }
        });
        saveBtn.addEventListener("click", function () {
          pendingSaveContext = { appointmentId: appointment.id, form };
          const saveModal = new bootstrap.Modal(
            document.getElementById("saveChangesModal")
          );
          saveModal.show();
        });

        cancelBtn.addEventListener("click", function () {
          pendingCancelContext = { appointmentId: appointment.id };
          document.getElementById("cancellationReason").value = "";
          const cancelModal = new bootstrap.Modal(
            document.getElementById("cancellationReasonModal")
          );
          cancelModal.show();
        });
      }, 0);
      container.appendChild(form);
    });
  }

  function displayAppointments(appointments, targetTableId) {
    const tableBody = document.getElementById(targetTableId);
    if (!tableBody) return;

    tableBody.innerHTML = "";

    // Determine if this table should show the reason column
    const showReason = [
      "allAppointmentsTable",
      "rejectedAppointmentsTable",
      "cancelledAppointmentsTable",
    ].includes(targetTableId);

    if (!appointments || appointments.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="${
        showReason ? 8 : 7
      }" class="text-center">No appointments found</td></tr>`;
      return;
    }

    appointments.forEach((appointment) => {
      const row = document.createElement("tr");
      row.innerHTML = `
                <td>${formatDate(appointment.preferred_date)}</td>
                <td>${appointment.preferred_time}</td>
                <td>${appointment.consultation_type || "N/A"}</td>
                <td>${appointment.method_type || "N/A"}</td>
                <td>${appointment.purpose || "N/A"}</td>
                <td>${appointment.counselor_name || "Not assigned"}</td>
                <td><span class="badge badge-${getStatusClass(
                  appointment.status
                )}">${appointment.status || "PENDING"}</span></td>
                ${
                  showReason
                    ? `<td>${appointment.reason ? appointment.reason : ""}</td>`
                    : ""
                }
            `;
      tableBody.appendChild(row);
    });
  }

  // ---- Availability helpers are now defined globally above DOMContentLoaded ----

  async function updateCounselorOptionsForForm(
    preferredDate,
    preferredTime,
    counselorSelect,
    currentValue
  ) {
    if (!counselorSelect) return;

    counselorSelect.disabled = true;
    counselorSelect.innerHTML =
      '<option value="">Loading available counselors...</option>';

    if (!preferredDate || !preferredTime) {
      counselorSelect.innerHTML =
        '<option value="">Select a counselor</option>';
      counselorSelect.disabled = false;
      return;
    }

    const dayOfWeek = getDayOfWeek(preferredDate);
    const normalizedTimeRange = normalizePreferredTimeTo24hRange(preferredTime);
    const timeBounds = extractStartEnd24h(preferredTime);

    const url = new URL(
      (window.BASE_URL || "/") + "student/get-counselors-by-availability"
    );
    url.searchParams.append("date", preferredDate);
    url.searchParams.append("day", dayOfWeek);
    url.searchParams.append("time", normalizedTimeRange || preferredTime);
    if (timeBounds) {
      url.searchParams.append("from", timeBounds.start);
      url.searchParams.append("to", timeBounds.end);
      url.searchParams.append("timeMode", "overlap");
    }

    try {
      const response = await fetch(url.toString(), {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json", "Cache-Control": "no-cache" },
      });
      if (!response.ok) throw new Error("Network error");
      const data = await response.json();
      counselorSelect.innerHTML =
        '<option value="">Select a counselor</option>';
      counselorSelect.insertAdjacentHTML(
        "beforeend",
        `<option value="No preference">No preference</option>`
      );
      if (data.status === "success" && Array.isArray(data.counselors)) {
        data.counselors.forEach((c) => {
          const opt = document.createElement("option");
          opt.value = c.counselor_id;
          opt.textContent = c.name;
          counselorSelect.appendChild(opt);
        });
        if (currentValue) {
          counselorSelect.value = currentValue;
        }
        if (data.counselors.length === 0) {
          const opt = document.createElement("option");
          opt.value = "";
          opt.textContent =
            "No counselors available for the selected date/time.";
          opt.disabled = true;
          counselorSelect.appendChild(opt);
        }
      } else {
        const opt = document.createElement("option");
        opt.value = "";
        opt.textContent = "Error loading counselors";
        opt.disabled = true;
        counselorSelect.appendChild(opt);
      }
    } catch (e) {
      SecureLogger.info("Error loading counselors by availability:", e);
      counselorSelect.innerHTML =
        '<option value="">Error loading counselors</option>';
    } finally {
      counselorSelect.disabled = false;
    }
  }

  function handleTabChange(event) {
    const targetTabId = event.target
      .getAttribute("data-bs-target")
      .replace("#", "");
    let status;
    let targetTableId;

    switch (targetTabId) {
      case "rejected":
        status = "REJECTED";
        targetTableId = "rejectedAppointmentsTable";
        break;
      case "completed":
        status = "COMPLETED";
        targetTableId = "completedAppointmentsTable";
        break;
      case "cancelled":
        status = "CANCELLED";
        targetTableId = "cancelledAppointmentsTable";
        break;
      case "all":
      default:
        status = "all";
        targetTableId = "allAppointmentsTable";
    }

    const filteredAppointments =
      status === "all"
        ? allAppointments
        : allAppointments.filter(
            (app) => app.status && app.status.toUpperCase() === status
          );

    displayAppointments(filteredAppointments, targetTableId);
  }

  function filterAppointments() {
    const searchTerm = searchInput.value.toLowerCase();
    const dateValue = dateFilter.value;

    let filtered = allAppointments;

    if (searchTerm) {
      filtered = filtered.filter((appointment) =>
        Object.values(appointment).some((value) =>
          String(value).toLowerCase().includes(searchTerm)
        )
      );
    }

    if (dateValue) {
      filtered = filtered.filter((appointment) =>
        appointment.preferred_date.startsWith(dateValue)
      );
    }

    const activeTab = document.querySelector(".nav-link.active");
    if (activeTab) {
      const tabId = activeTab.getAttribute("data-bs-target").replace("#", "");
      let status;
      let targetTableId;

      switch (tabId) {
        case "rejected":
          status = "REJECTED";
          targetTableId = "rejectedAppointmentsTable";
          break;
        case "completed":
          status = "COMPLETED";
          targetTableId = "completedAppointmentsTable";
          break;
        case "cancelled":
          status = "CANCELLED";
          targetTableId = "cancelledAppointmentsTable";
          break;
        case "all":
        default:
          status = "all";
          targetTableId = "allAppointmentsTable";
      }

      if (status !== "all") {
        filtered = filtered.filter(
          (app) => app.status && app.status.toUpperCase() === status
        );
      }

      displayAppointments(filtered, targetTableId);
    } else {
      displayAppointments(filtered, "allAppointmentsTable");
    }
  }

  // Edit appointment
  window.editAppointment = async function (appointmentId) {
    const appointment = allAppointments.find((app) => app.id === appointmentId);
    if (!appointment) return;

    document.getElementById("editAppointmentId").value = appointment.id;
    document.getElementById("editDate").value = appointment.preferred_date;
    document.getElementById("editConsultationType").value =
      appointment.consultation_type || "";
    document.getElementById("editMethodType").value =
      appointment.method_type || "";
    document.getElementById("editPurpose").value = appointment.purpose || "";
    document.getElementById("editDescription").value =
      appointment.description || "";
    document.getElementById("editCounselorPreference").value =
      appointment.counselor_preference || "";

    // Setup consultation type help text
    const consultationTypeHelp = document.getElementById(
      "editConsultationTypeHelp"
    );
    if (consultationTypeHelp) {
      if (appointment.consultation_type === "Group Consultation") {
        consultationTypeHelp.textContent =
          "Group consultation allows up to 5 students per time slot.";
        consultationTypeHelp.style.color = "#2563EB";
      } else {
        consultationTypeHelp.textContent =
          "One-on-one consultation with the counselor.";
        consultationTypeHelp.style.color = "#6c757d";
      }
    }

    // Load counselors for the counselor preference dropdown
    await loadCounselorsForEditModal(appointment.counselor_preference || "");

    // Load time slots based on selected date and counselor
    await refreshEditModalTimeSlots(
      appointment.preferred_date,
      appointment.preferred_time,
      appointment.counselor_preference || "",
      appointment.consultation_type || ""
    );

    // Setup event listeners for dynamic updates
    setupEditModalEventListeners();

    editModal.show();
  };

  async function saveEdit() {
    const appointmentId = document.getElementById("editAppointmentId").value;
    const date = document.getElementById("editDate").value;
    const time = document.getElementById("editTime").value;
    const consultationType = document.getElementById(
      "editConsultationType"
    ).value;
    const methodType = document.getElementById("editMethodType").value;
    const purpose = document.getElementById("editPurpose").value;
    const counselorPreference = document.getElementById(
      "editCounselorPreference"
    ).value;
    const description = document.getElementById("editDescription").value;

    try {
      const response = await fetch(
        (window.BASE_URL || "/") + "student/appointments/update",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            appointment_id: appointmentId,
            preferred_date: date,
            preferred_time: time,
            consultation_type: consultationType,
            method_type: methodType,
            purpose: purpose,
            counselor_preference: counselorPreference,
            description: description,
          }),
        }
      );

      const data = await response.json();

      if (data.success) {
        editModal.hide();
        fetchAppointments(); // Refresh the appointments list
        showSuccess("Appointment updated successfully");
      } else {
        showError(data.message || "Failed to update appointment");
      }
    } catch (error) {
      console.error("Error updating appointment:", error);
      showError("An error occurred while updating the appointment");
    }
  }

  // Delete appointment
  window.deleteAppointment = async function (appointmentId) {
    if (!confirm("Are you sure you want to delete this appointment?")) return;

    try {
      const response = await fetch(
        (window.BASE_URL || "/") +
          `student/appointments/delete/${appointmentId}`,
        {
          method: "DELETE",
        }
      );

      const data = await response.json();

      if (data.success) {
        fetchAppointments(); // Refresh the appointments list
        showSuccess("Appointment deleted successfully");
      } else {
        showError(data.message || "Failed to delete appointment");
      }
    } catch (error) {
      console.error("Error deleting appointment:", error);
      showError("An error occurred while deleting the appointment");
    }
  };

  // Cancel appointment
  window.cancelAppointment = function (appointmentId) {
    document.getElementById("cancelAppointmentId").value = appointmentId;
    document.getElementById("cancelReason").value = "";
    cancelModal.show();
  };

  async function confirmCancel() {
    const appointmentId = document.getElementById("cancelAppointmentId").value;
    const reason = document.getElementById("cancelReason").value;

    if (!reason.trim()) {
      showError("Please provide a reason for cancellation");
      return;
    }

    try {
      const response = await fetch(
        (window.BASE_URL || "/") + "student/appointments/cancel",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            appointment_id: appointmentId,
            reason: reason,
          }),
        }
      );

      const data = await response.json();

      if (data.success) {
        cancelModal.hide();
        fetchAppointments(); // Refresh the appointments list
        showSuccess("Appointment cancelled successfully");
      } else {
        showError(data.message || "Failed to cancel appointment");
      }
    } catch (error) {
      console.error("Error cancelling appointment:", error);
      showError("An error occurred while cancelling the appointment");
    }
  }

  async function updatePendingAppointment(appointmentId, form) {
    const preferred_date = form.querySelector('[name="preferred_date"]').value;
    const preferred_time = form.querySelector('[name="preferred_time"]').value;
    const consultation_type = form.querySelector(
      '[name="consultation_type"]'
    ).value;
    const method_type = form.querySelector('[name="method_type"]').value;
    const purpose = form.querySelector('[name="purpose"]').value;
    const counselor_preference = form.querySelector(
      '[name="counselor_preference"]'
    ).value;
    const description = form.querySelector('[name="description"]').value;

    // Check for counselor conflicts before updating
    const hasConflict = await checkEditConflicts(
      appointmentId,
      counselor_preference,
      preferred_date,
      preferred_time
    );
    if (hasConflict) {
      throw new Error("Counselor conflict detected"); // Throw error to trigger catch block
    }

    try {
      const response = await fetch(
        (window.BASE_URL || "/") + "student/appointments/update",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            appointment_id: appointmentId,
            preferred_date,
            preferred_time,
            consultation_type,
            method_type,
            purpose,
            counselor_preference,
            description,
            status: "pending", // always keep as pending
          }),
        }
      );
      const data = await response.json();
      if (data.success) {
        showSuccess("Appointment updated successfully.");
        fetchAppointments();
      } else {
        throw new Error(data.message || "Failed to update appointment.");
      }
    } catch (error) {
      showError(
        error.message || "An error occurred while updating the appointment."
      );
      throw error; // Re-throw to trigger catch block in modal handler
    }
  }

  async function deleteAppointment(appointmentId) {
    try {
      const response = await fetch(
        (window.BASE_URL || "/") +
          `student/appointments/delete/${appointmentId}`,
        {
          method: "DELETE",
        }
      );
      const data = await response.json();
      if (data.success) {
        showSuccess("Appointment deleted successfully.");
        fetchAppointments();
      } else {
        showError(data.message || "Failed to delete appointment.");
      }
    } catch (error) {
      showError("An error occurred while deleting the appointment.");
    }
  }

  async function cancelPendingAppointment(appointmentId, reason) {
    try {
      const response = await fetch(
        (window.BASE_URL || "/") + "student/appointments/cancel",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            appointment_id: appointmentId,
            reason: reason,
          }),
        }
      );
      const data = await response.json();
      if (data.success) {
        showSuccess("Appointment cancelled successfully.");
        fetchAppointments();
      } else {
        throw new Error(data.message || "Failed to cancel appointment.");
      }
    } catch (error) {
      showError(
        error.message || "An error occurred while cancelling the appointment."
      );
      throw error; // Re-throw to trigger catch block in modal handler
    }
  }

  // Utility functions
  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
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

  function formatTime(timeString) {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function getStatusClass(status) {
    if (!status) return "pending";
    switch (status.toUpperCase()) {
      case "APPROVED":
        return "approved";
      case "REJECTED":
        return "rejected";
      case "COMPLETED":
        return "completed";
      case "CANCELLED":
        return "cancelled";
      case "PENDING":
      default:
        return "pending";
    }
  }

  function showLoading() {
    if (loadingSpinner) loadingSpinner.style.display = "flex";
  }

  function hideLoading() {
    if (loadingSpinner) loadingSpinner.style.display = "none";
  }

  function showEmptyState() {
    if (emptyState) emptyState.style.display = "block";
  }

  function hideEmptyState() {
    if (emptyState) emptyState.style.display = "none";
  }

  function showError(message) {
    // You can implement a toast or alert system here
    alert(message);
  }

  function showSuccess(message) {
    // Update modal message and show success modal
    const successModalMessage = document.getElementById("successModalMessage");
    if (successModalMessage) {
      successModalMessage.textContent = message;
    }

    // Show the modal using Bootstrap
    const successModal = new bootstrap.Modal(
      document.getElementById("successModal")
    );
    successModal.show();
  }

  // Generate appointment ticket HTML
  async function generateAppointmentTicket(appointment) {
    const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
    const qrCodeData = JSON.stringify({
      appointmentId: appointment.id,
      studentId: appointment.student_id,
      date: appointment.preferred_date,
      time: appointment.preferred_time,
      counselor: appointment.counselor_name,
      type: appointment.consultation_type,
      purpose: appointment.purpose,
      ticketId: ticketId,
    });

    return `
            <div class="approved-appointment-ticket">
                <div class="ticket-header">
                    <div class="ticket-title-container">
                        <img src="${
                          window.BASE_URL || "/"
                        }Photos/ticket_logo_green.png" alt="Counselign Logo" class="ticket-logo">
                        <h3 class="ticket-title">Appointment Ticket</h3>
                    </div>
                    <span class="ticket-status">Approved</span>
                </div>
                
                <div class="ticket-details">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Date</div>
                            <p class="detail-value">${formatDate(
                              appointment.preferred_date
                            )}</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-clock detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Time</div>
                            <p class="detail-value">${
                              appointment.preferred_time
                            }</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-user-md detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Counselor</div>
                            <p class="detail-value">${
                              appointment.counselor_name || "Not assigned"
                            }</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-comments detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Consultation Type</div>
                            <p class="detail-value">${
                              appointment.consultation_type || "Not specified"
                            }</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-laptop detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Method Type</div>
                            <p class="detail-value">${
                              appointment.method_type || "Not specified"
                            }</p>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-bullseye detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Purpose</div>
                            <p class="detail-value">${
                              appointment.purpose || "Not specified"
                            }</p>
                        </div>
                    </div>
                </div>
                
                <div class="ticket-footer">
                    <div class="qr-code-container">
                        <div class="qr-code" id="qr-code-${appointment.id}">
                            <div>Generating QR Code...</div>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #6c757d; margin-bottom: 5px;">Ticket ID:</div>
                            <div style="font-weight: 600; color: #28a745;">${ticketId}</div>
                        </div>
                    </div>
                    
                    <button class="download-ticket-btn" data-appointment-id="${
                      appointment.id
                    }">
                        <i class="fas fa-download"></i>
                        Download Ticket
                    </button>
                </div>
            </div>
        `;
  }

  // Generate QR code for appointment
  function generateQRCode(appointmentId, qrCodeData) {
    const qrContainer = document.getElementById(`qr-code-${appointmentId}`);
    if (!qrContainer) {
      console.error("QR container not found:", `qr-code-${appointmentId}`);
      return;
    }

    // Wait for qrcode library to load
    const checkQRCodeLibrary = () => {
      if (typeof qrcode !== "undefined") {
        try {
          // Clear the container
          qrContainer.innerHTML = "";

          // Generate QR code using qrcode-generator (SVG with margin creates proper quiet zone)
          const qr = qrcode(0, "M");
          qr.addData(qrCodeData);
          qr.make();

          // Use SVG tag with explicit cell size and margin (quiet zone). Default color is black-on-white.
          const cellSize = 4; // px per module (scales the final size)
          const margin = 4; // modules of quiet zone
          const svgMarkup = qr.createSvgTag(cellSize, margin);

          // Inject SVG
          qrContainer.innerHTML = svgMarkup;

          // Ensure SVG fits inside its box
          const svgEl = qrContainer.querySelector("svg");
          if (svgEl) {
            svgEl.style.width = "100%";
            svgEl.style.height = "100%";
            svgEl.setAttribute("preserveAspectRatio", "xMidYMid meet");
            // Remove any hardcoded width/height that could overflow
            svgEl.removeAttribute("width");
            svgEl.removeAttribute("height");
          }
          SecureLogger.info("QR Code (SVG) generated successfully");
        } catch (error) {
          console.error("QR Code generation error:", error);
          qrContainer.innerHTML = `
                        <div style="font-size: 10px; color: #dc3545; text-align: center;">
                            <div style="font-weight: bold;">QR</div>
                            <div>Error</div>
                        </div>
                    `;
        }
      } else {
        // Fallback if qrcode library is not loaded
        console.warn("qrcode library not available, showing placeholder");
        qrContainer.innerHTML = `
                    <div style="font-size: 8px; color: #28a745; text-align: center; line-height: 1.2;">
                        <div style="font-weight: bold; margin-bottom: 2px;">QR CODE</div>
                        <div style="font-size: 6px;">Appointment</div>
                        <div style="font-size: 6px;">ID: ${appointmentId}</div>
                    </div>
                `;
      }
    };

    // Check immediately and also after a delay to ensure library is loaded
    checkQRCodeLibrary();

    // If library is not ready, wait a bit more
    if (typeof qrcode === "undefined") {
      setTimeout(checkQRCodeLibrary, 1000);
    }
  }

  // Download appointment ticket as PDF
  async function downloadAppointmentTicket(appointment) {
    try {
      // Track download activity
      try {
        await fetch(
          (window.BASE_URL || "/") + "student/appointments/track-download",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "include",
            body: JSON.stringify({
              appointment_id: appointment.id,
            }),
          }
        );
      } catch (error) {
        SecureLogger.info("Activity tracking failed:", error);
        // Continue with download even if tracking fails
      }

      const ticketId = `TICKET-${appointment.id}-${Date.now()}`;
      const qrCodeData = JSON.stringify({
        appointmentId: appointment.id,
        studentId: appointment.student_id,
        date: appointment.preferred_date,
        time: appointment.preferred_time,
        counselor: appointment.counselor_name,
        type: appointment.consultation_type,
        purpose: appointment.purpose,
        ticketId: ticketId,
      });

      // Check if jsPDF is available
      if (typeof window.jspdf === "undefined") {
        showError(
          "PDF library not loaded. Please refresh the page and try again."
        );
        return;
      }

      // Create new PDF document (A4 size: 210mm x 297mm)
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({
        orientation: "portrait",
        unit: "mm",
        format: "a4",
      });

      // Set up colors (RGB values for jsPDF)
      const greenColor = [40, 167, 69]; // #28a745
      const lightGrayColor = [248, 249, 250]; // #f8f9fa
      const darkGrayColor = [108, 117, 125]; // #6c757d
      const borderGrayColor = [233, 236, 239]; // #e9ecef

      // Page dimensions
      const pageWidth = 210;
      const pageHeight = 297;
      const margin = 20;
      const contentWidth = pageWidth - margin * 2;

      // Start Y position
      let currentY = margin;

      // Helper function to add text with styling
      function addText(x, y, text, options = {}) {
        const {
          fontSize = 12,
          fontStyle = "normal",
          color = [0, 0, 0],
          align = "left",
        } = options;

        doc.setFontSize(fontSize);
        doc.setFont("helvetica", fontStyle);
        doc.setTextColor(color[0], color[1], color[2]);
        doc.text(text, x, y);
      }

      // Helper function to add rectangle
      function addRect(x, y, width, height, options = {}) {
        const {
          fillColor = null,
          strokeColor = [0, 0, 0],
          lineWidth = 0.5,
        } = options;

        if (fillColor) {
          doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
        }
        doc.setDrawColor(strokeColor[0], strokeColor[1], strokeColor[2]);
        doc.setLineWidth(lineWidth);

        if (fillColor) {
          doc.rect(x, y, width, height, "FD");
        } else {
          doc.rect(x, y, width, height, "S");
        }
      }

      // Helper function to add rounded rectangle
      function addRoundedRect(x, y, width, height, radius, options = {}) {
        const {
          fillColor = null,
          strokeColor = [0, 0, 0],
          lineWidth = 0.5,
        } = options;

        if (fillColor) {
          doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
        }
        doc.setDrawColor(strokeColor[0], strokeColor[1], strokeColor[2]);
        doc.setLineWidth(lineWidth);

        // Create rounded rectangle using lines and arcs
        const path = `M ${x + radius} ${y} L ${x + width - radius} ${y} Q ${
          x + width
        } ${y} ${x + width} ${y + radius} L ${x + width} ${
          y + height - radius
        } Q ${x + width} ${y + height} ${x + width - radius} ${y + height} L ${
          x + radius
        } ${y + height} Q ${x} ${y + height} ${x} ${
          y + height - radius
        } L ${x} ${y + radius} Q ${x} ${y} ${x + radius} ${y} Z`;

        if (fillColor) {
          doc.setFillColor(fillColor[0], fillColor[1], fillColor[2]);
          doc.path(path, "FD");
        } else {
          doc.path(path, "S");
        }
      }

      // Main ticket border (2mm thick green border)
      addRoundedRect(margin, currentY, contentWidth, 80, 2, {
        fillColor: [255, 255, 255],
        strokeColor: greenColor,
        lineWidth: 2,
      });

      // Header section
      const headerY = currentY + 5;

      // Logo (40x40px = ~11x11mm)
      try {
        // Try to load and embed the logo image
        const logoUrl = `${window.BASE_URL || "/"}Photos/ticket_logo_green.png`;
        const logoResponse = await fetch(logoUrl);
        if (logoResponse.ok) {
          const logoBlob = await logoResponse.blob();
          const logoBase64 = await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(logoBlob);
          });

          // Add logo image to PDF
          doc.addImage(logoBase64, "PNG", margin + 5, headerY, 11, 9);
        } else {
          throw new Error("Logo not found");
        }
      } catch (error) {
        console.warn("Could not load logo, using placeholder:", error);
        // Fallback to placeholder
        addRect(margin + 5, headerY, 11, 11, {
          fillColor: lightGrayColor,
          strokeColor: greenColor,
          lineWidth: 1,
        });
        addText(margin + 5.5, headerY + 7, "LOGO", {
          fontSize: 6,
          color: greenColor,
          align: "center",
        });
      }

      // Title
      addText(margin + 20, headerY + 7, "Appointment Ticket", {
        fontSize: 16,
        fontStyle: "bold",
        color: greenColor,
      });

      // Status badge
      const statusText = "APPROVED";
      const statusWidth = 25;
      const statusX = margin + contentWidth - statusWidth - 5;
      addRoundedRect(statusX, headerY, statusWidth, 8, 4, {
        fillColor: greenColor,
        strokeColor: greenColor,
      });
      addText(statusX + statusWidth / 2, headerY + 5.5, statusText, {
        fontSize: 8,
        fontStyle: "bold",
        color: [255, 255, 255],
        align: "center",
      });

      // Header bottom border
      addRect(margin + 5, headerY + 12, contentWidth - 10, 0.5, {
        strokeColor: borderGrayColor,
        lineWidth: 0.5,
      });

      currentY = headerY + 20;

      // Details section (2x3 grid)
      const detailBoxWidth = (contentWidth - 20) / 2;
      const detailBoxHeight = 15;
      const detailSpacing = 5;

      // Row 1
      // Date box
      addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
        fillColor: lightGrayColor,
        strokeColor: [255, 255, 255],
        lineWidth: 0,
      });
      addRect(margin + 5, currentY, 1, detailBoxHeight, {
        fillColor: greenColor,
        strokeColor: greenColor,
      });
      addText(margin + 8, currentY + 4, "DATE", {
        fontSize: 6,
        color: darkGrayColor,
      });
      addText(
        margin + 8,
        currentY + 8,
        formatDate(appointment.preferred_date),
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      // Time box
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        detailBoxWidth,
        detailBoxHeight,
        {
          fillColor: lightGrayColor,
          strokeColor: [255, 255, 255],
          lineWidth: 0,
        }
      );
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        1,
        detailBoxHeight,
        {
          fillColor: greenColor,
          strokeColor: greenColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 4,
        "TIME",
        {
          fontSize: 6,
          color: darkGrayColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 8,
        appointment.preferred_time,
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      currentY += detailBoxHeight + detailSpacing;

      // Row 2
      // Counselor box
      addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
        fillColor: lightGrayColor,
        strokeColor: [255, 255, 255],
        lineWidth: 0,
      });
      addRect(margin + 5, currentY, 1, detailBoxHeight, {
        fillColor: greenColor,
        strokeColor: greenColor,
      });
      addText(margin + 8, currentY + 4, "COUNSELOR", {
        fontSize: 6,
        color: darkGrayColor,
      });
      addText(
        margin + 8,
        currentY + 8,
        appointment.counselor_name || "Not assigned",
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      // Consultation Type box
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        detailBoxWidth,
        detailBoxHeight,
        {
          fillColor: lightGrayColor,
          strokeColor: [255, 255, 255],
          lineWidth: 0,
        }
      );
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        1,
        detailBoxHeight,
        {
          fillColor: greenColor,
          strokeColor: greenColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 4,
        "CONSULTATION TYPE",
        {
          fontSize: 6,
          color: darkGrayColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 8,
        appointment.consultation_type || "Not specified",
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      currentY += detailBoxHeight + detailSpacing;

      // Row 3
      // Method Type box
      addRect(margin + 5, currentY, detailBoxWidth, detailBoxHeight, {
        fillColor: lightGrayColor,
        strokeColor: [255, 255, 255],
        lineWidth: 0,
      });
      addRect(margin + 5, currentY, 1, detailBoxHeight, {
        fillColor: greenColor,
        strokeColor: greenColor,
      });
      addText(margin + 8, currentY + 4, "METHOD TYPE", {
        fontSize: 6,
        color: darkGrayColor,
      });
      addText(
        margin + 8,
        currentY + 8,
        appointment.method_type || "Not specified",
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      // Purpose box (right side)
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        detailBoxWidth,
        detailBoxHeight,
        {
          fillColor: lightGrayColor,
          strokeColor: [255, 255, 255],
          lineWidth: 0,
        }
      );
      addRect(
        margin + 5 + detailBoxWidth + detailSpacing,
        currentY,
        1,
        detailBoxHeight,
        {
          fillColor: greenColor,
          strokeColor: greenColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 4,
        "PURPOSE",
        {
          fontSize: 6,
          color: darkGrayColor,
        }
      );
      addText(
        margin + 8 + detailBoxWidth + detailSpacing,
        currentY + 8,
        appointment.purpose || "Not specified",
        {
          fontSize: 10,
          fontStyle: "bold",
          color: [33, 37, 41],
        }
      );

      currentY += detailBoxHeight + 10;

      // Footer section
      addRect(margin + 5, currentY, contentWidth - 10, 0.5, {
        strokeColor: borderGrayColor,
        lineWidth: 0.5,
      });

      currentY += 5;

      // Footer content
      const footerLeftWidth = contentWidth - 35; // Leave space for QR code

      // Ticket ID
      addText(margin + 5, currentY, `Ticket ID: ${ticketId}`, {
        fontSize: 8,
        color: darkGrayColor,
      });

      // Instructions
      addText(
        margin + 5,
        currentY + 4,
        "Please bring this ticket to your appointment",
        {
          fontSize: 8,
          color: darkGrayColor,
        }
      );

      // Generated date
      addText(
        margin + 5,
        currentY + 8,
        `Generated on: ${new Date().toLocaleString()}`,
        {
          fontSize: 8,
          color: darkGrayColor,
        }
      );

      // QR Code (30x30mm area) with quiet zone inside
      const qrCodeSize = 30;
      const qrCodeX = margin + contentWidth - qrCodeSize - 5;
      const qrCodeY = currentY;

      // QR Code border
      addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
        fillColor: lightGrayColor,
        strokeColor: greenColor,
        lineWidth: 1,
      });

      // Generate QR Code
      if (typeof qrcode !== "undefined") {
        try {
          const qr = qrcode(0, "M");
          qr.addData(qrCodeData);
          qr.make();

          const qrSize = qr.getModuleCount();
          // Add a white quiet zone around modules for reliable scanning
          const quietZone = 2; // mm of quiet zone inside the 30mm box
          const drawableSize = Math.max(0, qrCodeSize - quietZone * 2);
          const cellSize = drawableSize / qrSize;

          // Draw QR code with improved rendering
          // First, fill the entire QR area with white background
          addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
            fillColor: [255, 255, 255],
            strokeColor: [255, 255, 255],
          });

          // Then draw only the dark modules
          for (let row = 0; row < qrSize; row++) {
            for (let col = 0; col < qrSize; col++) {
              if (qr.isDark(row, col)) {
                const x = qrCodeX + quietZone + col * cellSize;
                const y = qrCodeY + quietZone + row * cellSize;

                // Draw each dark cell as a filled rectangle
                doc.setFillColor(0, 0, 0); // black modules
                doc.rect(x, y, cellSize, cellSize, "F");
              }
            }
          }

          // Add a subtle border around the QR code
          addRect(qrCodeX, qrCodeY, qrCodeSize, qrCodeSize, {
            fillColor: null,
            strokeColor: greenColor,
            lineWidth: 0.5,
          });
        } catch (error) {
          console.error("QR Code generation error:", error);
          // Fallback text
          addText(
            qrCodeX + qrCodeSize / 2,
            qrCodeY + qrCodeSize / 2,
            "QR CODE",
            {
              fontSize: 8,
              fontStyle: "bold",
              color: greenColor,
              align: "center",
            }
          );
        }
      } else {
        // Fallback text
        addText(qrCodeX + qrCodeSize / 2, qrCodeY + qrCodeSize / 2, "QR CODE", {
          fontSize: 8,
          fontStyle: "bold",
          color: greenColor,
          align: "center",
        });
      }

      // Download the PDF
      const fileName = `Appointment_Ticket_${appointment.id}_${ticketId}.pdf`;
      doc.save(fileName);

      showSuccess("Appointment ticket downloaded as PDF successfully");
    } catch (error) {
      console.error("Error downloading ticket:", error);
      showError("Failed to download ticket. Please try again.");
    }
  }

 // Save Changes Modal
const confirmSaveBtn = document.getElementById("confirmSaveChangesBtn");
if (confirmSaveBtn) {
  confirmSaveBtn.addEventListener("click", async function () {
    if (pendingSaveContext) {
      // Show loading state
      showButtonLoading(confirmSaveBtn, "Saving Changes...");

      try {
        await updatePendingAppointment(
          pendingSaveContext.appointmentId,
          pendingSaveContext.form
        );
        
        // Hide modal after successful update
        const modalElement = document.getElementById("saveChangesModal");
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
          modalInstance.hide();
        }
        
        // Reset context
        pendingSaveContext = null;
        
        // Reset button state after a short delay (to ensure modal closes smoothly)
        setTimeout(() => {
          hideButtonLoading(confirmSaveBtn, "Save Changes");
        }, 300);
        
      } catch (error) {
        // Reset button state on error
        hideButtonLoading(confirmSaveBtn, "Save Changes");
        console.error("Error saving changes:", error);
      }
    }
  });
}

// Reset button state when modal is closed/hidden (handles X button, backdrop clicks, etc.)
const saveChangesModal = document.getElementById("saveChangesModal");
if (saveChangesModal) {
  saveChangesModal.addEventListener('hidden.bs.modal', function () {
    const confirmBtn = document.getElementById("confirmSaveChangesBtn");
    if (confirmBtn) {
      // Reset button to original state whenever modal is hidden
      hideButtonLoading(confirmBtn, "Save Changes");
    }
  });
}

  // Cancel Modal
  const confirmCancelBtn = document.getElementById("confirmCancellationBtn");
  if (confirmCancelBtn) {
    confirmCancelBtn.addEventListener("click", function () {
      if (pendingCancelContext) {
        const reason = document.getElementById("cancellationReason").value;
        if (reason && reason.trim() !== "") {
          // Show loading state
          showButtonLoading(confirmCancelBtn, "Cancelling...");

          cancelPendingAppointment(pendingCancelContext.appointmentId, reason)
            .then(() => {
              // Hide modal after successful cancellation
              bootstrap.Modal.getInstance(
                document.getElementById("cancellationReasonModal")
              ).hide();
              pendingCancelContext = null;
            })
            .catch(() => {
              // Reset button state on error
              hideButtonLoading(confirmCancelBtn, "Confirm Cancellation");
            });
        } else {
          showError("Cancellation reason is required.");
        }
      }
    });
  }

  // Delete Modal
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", function () {
      if (pendingDeleteId) {
        deleteAppointment(pendingDeleteId);
        bootstrap.Modal.getInstance(
          document.getElementById("deleteConfirmationModal")
        ).hide();
        pendingDeleteId = null;
      }
    });
  }
});

// Check for counselor conflicts before editing appointment
async function checkEditConflicts(appointmentId, counselorId, date, time) {
  try {
    if (!counselorId || counselorId === "No preference" || !date || !time) {
      return false; // No specific counselor selected, no conflict check needed
    }

    const response = await fetch(
      (window.BASE_URL || "/") +
        "student/check-edit-conflicts?" +
        new URLSearchParams({
          appointment_id: appointmentId,
          counselor_id: counselorId,
          date: date,
          time: time,
        }),
      {
        method: "GET",
        credentials: "include",
        headers: {
          Accept: "application/json",
          "Cache-Control": "no-cache",
        },
      }
    );

    if (!response.ok) {
      throw new Error("Failed to check counselor availability");
    }

    const data = await response.json();

    if (data.status === "success" && data.hasConflict) {
      showEditConflictModal(data.message, data.conflictType);
      return true; // Has conflict
    }

    return false; // No conflict
  } catch (error) {
    console.error("Error checking edit conflicts:", error);
    showError("Error checking counselor availability. Please try again.");
    return false;
  }
}

// Show edit conflict modal
function showEditConflictModal(message, conflictType) {
  // Remove any existing conflict modal
  const existingModal = document.querySelector(".edit-conflict-modal");
  if (existingModal) {
    document.body.removeChild(existingModal);
  }

  // Create modal overlay
  const modal = document.createElement("div");
  modal.className =
    "edit-conflict-modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center";
  modal.style.zIndex = "9999";
  modal.style.position = "fixed";
  modal.style.top = "0";
  modal.style.left = "0";
  modal.style.width = "100vw";
  modal.style.height = "100vh";
  modal.style.display = "flex";
  modal.style.alignItems = "center";
  modal.style.justifyContent = "center";

  // Create modal content
  const modalContent = document.createElement("div");
  modalContent.className =
    "bg-white rounded-lg p-6 max-w-md mx-4 text-center relative";
  modalContent.style.width = "400px";
  modalContent.style.borderRadius = "8px";
  modalContent.style.maxWidth = "90vw";
  modalContent.style.maxHeight = "90vh";
  modalContent.style.overflow = "auto";

  // Modal content HTML
  modalContent.innerHTML = `
        <div class="mb-4">
            <div class="text-red-500 mb-4">
                <i class="fas fa-exclamation-triangle text-4xl"></i>
            </div>
            <h3 class="text-xl font-semibold mb-3 text-gray-800">Counselor Not Available</h3>
            <p class="text-gray-700 mb-4 text-sm leading-relaxed">${message}</p>
            <p class="text-gray-600 mb-4 text-xs">Please choose a different time slot or select another counselor.</p>
        </div>
        <div class="flex justify-center mb-2">
            <button id="editConflictModalOk" type="button" style="
                background: linear-gradient(90deg, #2563EB 0%, #1D4ED8 100%);
                color: #FFFFFF;
                font-weight: 600;
                padding: 12px 24px;
                border-radius: 10px;
                border: none;
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                cursor: pointer;
                outline: none;
            ">
                OK, I Understand
            </button>
        </div>
    `;

  modal.appendChild(modalContent);
  document.body.appendChild(modal);

  // Add event listener for OK button
  const okButton = document.getElementById("editConflictModalOk");
  if (okButton) {
    okButton.addEventListener("click", function () {
      document.body.removeChild(modal);
    });
  }

  // Add event listener for clicking outside modal
  modal.addEventListener("click", function (e) {
    if (e.target === modal) {
      document.body.removeChild(modal);
    }
  });

  // Add event listener for ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && document.querySelector(".edit-conflict-modal")) {
      document.body.removeChild(modal);
    }
  });
}

// Setup counselor schedules to load when calendar drawer is opened
function setupCounselorSchedulesInDrawer() {
  const toggleBtn = document.getElementById("counselorsCalendarToggleBtn");
  const drawer = document.getElementById("counselorsCalendarDrawer");

  if (!toggleBtn || !drawer) return;

  let schedulesLoaded = false;

  // Add event listener to load schedules when drawer is opened
  toggleBtn.addEventListener("click", async () => {
    if (!schedulesLoaded) {
      await loadCounselorSchedules();
      schedulesLoaded = true;
    }
  });
}

// Load and display counselor schedules
async function loadCounselorSchedules() {
  const container = document.getElementById("counselorSchedulesContainer");
  if (!container) return;

  try {
    const response = await fetch(
      (window.BASE_URL || "/") + "student/get-counselor-schedules",
      {
        method: "GET",
        credentials: "include",
        headers: {
          Accept: "application/json",
          "Cache-Control": "no-cache",
        },
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.status === "success" && data.schedules) {
      displayCounselorSchedules(data.schedules);
    } else {
      showCounselorSchedulesError("Failed to load counselor schedules");
    }
  } catch (error) {
    console.error("Error loading counselor schedules:", error);
    showCounselorSchedulesError(
      "An error occurred while loading counselor schedules"
    );
  }
}

// Display counselor schedules in the UI
function displayCounselorSchedules(schedules) {
  const container = document.getElementById("counselorSchedulesContainer");
  if (!container) return;

  const daysOfWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];

  // Create schedules grid
  const schedulesGrid = document.createElement("div");
  schedulesGrid.className = "schedules-grid";

  daysOfWeek.forEach((day) => {
    const dayCard = createDayScheduleCard(day, schedules[day] || []);
    schedulesGrid.appendChild(dayCard);
  });

  // Clear loading state and add schedules
  container.innerHTML = "";
  container.appendChild(schedulesGrid);
}

// Create a day schedule card
function createDayScheduleCard(day, counselors) {
  const dayCard = document.createElement("div");
  dayCard.className = "day-schedule-card";

  // Create day header with gradient background
  const dayHeader = document.createElement("div");
  dayHeader.className = `day-header ${day.toLowerCase()}`;
  dayHeader.textContent = day;

  // Create counselors list container
  const counselorsList = document.createElement("div");
  counselorsList.className = "counselors-list";

  if (counselors && counselors.length > 0) {
    counselors.forEach((counselor) => {
      const counselorItem = createCounselorItem(counselor);
      counselorsList.appendChild(counselorItem);
    });
  } else {
    const noCounselorsDiv = document.createElement("div");
    noCounselorsDiv.className = "no-counselors";
    noCounselorsDiv.innerHTML = `
            <i class="fas fa-user-slash"></i>
            <h4>No Counselors Available</h4>
            <p>No counselors are scheduled for ${day}</p>
        `;
    counselorsList.appendChild(noCounselorsDiv);
  }

  dayCard.appendChild(dayHeader);
  dayCard.appendChild(counselorsList);

  return dayCard;
}

// Create a counselor item
function createCounselorItem(counselor) {
  const counselorItem = document.createElement("div");
  counselorItem.className = "counselor-item";

  const counselorName = document.createElement("div");
  counselorName.className = "counselor-name";
  counselorName.innerHTML = `
        <i class="fas fa-user-md"></i>
        ${counselor.counselor_name}
    `;

  const timeSlots = document.createElement("div");
  timeSlots.className = "time-slots";

  if (counselor.time_scheduled) {
    // Parse time_scheduled string and create badges
    const timeSlotsArray = counselor.time_scheduled
      .split(",")
      .map((slot) => slot.trim())
      .filter((slot) => slot);

    timeSlotsArray.forEach((slot) => {
      const timeBadge = document.createElement("span");
      timeBadge.className = "time-slot-badge";
      timeBadge.textContent = formatTimeSlot(slot);
      timeSlots.appendChild(timeBadge);
    });
  } else {
    const noTimeSlot = document.createElement("span");
    noTimeSlot.className = "time-slot-badge";
    noTimeSlot.textContent = "Available";
    timeSlots.appendChild(noTimeSlot);
  }

  counselorItem.appendChild(counselorName);
  counselorItem.appendChild(timeSlots);

  return counselorItem;
}

// Format time slot for display
function formatTimeSlot(timeSlot) {
  if (!timeSlot) return "Available";

  // If it's already in 12-hour format, return as is
  if (timeSlot.includes("AM") || timeSlot.includes("PM")) {
    return timeSlot;
  }

  // If it's in 24-hour format, convert to 12-hour
  if (timeSlot.includes(":")) {
    const [hour, minute] = timeSlot.split(":");
    const hourNum = parseInt(hour);
    const ampm = hourNum >= 12 ? "PM" : "AM";
    const displayHour =
      hourNum === 0 ? 12 : hourNum > 12 ? hourNum - 12 : hourNum;
    return `${displayHour}:${minute} ${ampm}`;
  }

  return timeSlot;
}

// Refresh time slots for pending appointment form
async function refreshPendingAppointmentTimeSlots(
  form,
  dateStr,
  selectedTime,
  counselorId,
  consultationType
) {
  const timeSelect = form.querySelector('[name="preferred_time"]');
  if (!timeSelect || !dateStr) return;

  timeSelect.disabled = true;
  const originalValue = timeSelect.value;
  timeSelect.innerHTML = '<option value="">Loading time slots...</option>';

  try {
    const dayOfWeek = getDayOfWeek(dateStr);
    // For pending appointments, we only get slots for the specific counselor (cannot change counselor)
    const currentCounselorId = counselorId;

    if (!currentCounselorId || currentCounselorId === "No preference") {
      timeSelect.innerHTML = '<option value="">No counselor selected</option>';
      timeSelect.disabled = false;
      return;
    }

    let availableRanges = [];

    // Get time slots ONLY for the selected counselor
    try {
      const availRes = await fetch(
        (window.BASE_URL || "/") +
          "counselor/profile/availability?counselorId=" +
          encodeURIComponent(currentCounselorId)
      );
      const availData = await availRes.json();
      const daySchedule =
        availData && availData.availability && availData.availability[dayOfWeek]
          ? availData.availability[dayOfWeek]
          : [];
      const slotStrings = Array.isArray(daySchedule)
        ? daySchedule.map((r) => r && r.time_scheduled).filter(Boolean)
        : [];
      const ranges = generateHalfHourRangeUnion(slotStrings);
      availableRanges = ranges;
      availableRanges.sort();
    } catch (e) {
      console.error("Error fetching counselor availability:", e);
      timeSelect.innerHTML =
        '<option value="">Error loading counselor availability</option>';
      timeSelect.disabled = false;
      return;
    }

    // Get booked times for the date
    const bookedUrl = new URL(
      (window.BASE_URL || "/") + "student/appointments/booked-times"
    );
    bookedUrl.searchParams.append("date", dateStr);
    if (currentCounselorId && currentCounselorId !== "No preference") {
      bookedUrl.searchParams.append("counselor_id", currentCounselorId);
    }
    if (consultationType) {
      bookedUrl.searchParams.append("consultation_type", consultationType);
    }

    const bookedRes = await fetch(bookedUrl.toString(), {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
    });

    let booked = [];
    if (bookedRes.ok) {
      const bookedData = await bookedRes.json();
      if (
        bookedData &&
        bookedData.status === "success" &&
        Array.isArray(bookedData.booked)
      ) {
        booked = bookedData.booked;
      }
    }

    // Build options
    const fragment = document.createDocumentFragment();
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select a time slot";
    fragment.appendChild(placeholder);

    const bookedSet = new Set(booked);
    let availableCount = 0;
    for (const slot of availableRanges) {
      if (!bookedSet.has(slot)) {
        const opt = document.createElement("option");
        opt.value = slot;
        opt.textContent = slot;
        if (
          (selectedTime && slot === selectedTime) ||
          (originalValue && slot === originalValue)
        ) {
          opt.selected = true;
        }
        fragment.appendChild(opt);
        availableCount++;
      }
    }

    timeSelect.innerHTML = "";
    timeSelect.appendChild(fragment);

    if (availableCount === 0) {
      const none = document.createElement("option");
      none.value = "";
      none.textContent = "No available time slots for this date";
      none.disabled = true;
      timeSelect.appendChild(none);
    } else {
      // Restore selected time if it's still available
      if (
        (selectedTime &&
          timeSelect.querySelector(`option[value="${selectedTime}"]`)) ||
        (originalValue &&
          timeSelect.querySelector(`option[value="${originalValue}"]`))
      ) {
        timeSelect.value = selectedTime || originalValue;
      }
    }
  } catch (error) {
    console.error("Error refreshing time slots:", error);
    timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
    if (selectedTime) {
      const opt = document.createElement("option");
      opt.value = selectedTime;
      opt.textContent = selectedTime;
      opt.selected = true;
      timeSelect.appendChild(opt);
    }
  } finally {
    timeSelect.disabled = false;
  }
}

// Show error message for counselor schedules
function showCounselorSchedulesError(message) {
  const container = document.getElementById("counselorSchedulesContainer");
  if (!container) return;

  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <h4>Error Loading Schedules</h4>
        <p>${message}</p>
    `;

  container.innerHTML = "";
  container.appendChild(errorDiv);
}

// ===== Edit Modal Functions =====
// Load counselors for edit modal
async function loadCounselorsForEditModal(selectedCounselorId) {
  const counselorSelect = document.getElementById("editCounselorPreference");
  if (!counselorSelect) return;

  counselorSelect.disabled = true;
  counselorSelect.innerHTML = '<option value="">Loading counselors...</option>';

  try {
    const counselors = await fetchCounselors();
    counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
    counselorSelect.insertAdjacentHTML(
      "beforeend",
      '<option value="No preference">No preference</option>'
    );

    counselors.forEach((counselor) => {
      const option = document.createElement("option");
      option.value = counselor.counselor_id;
      option.textContent = counselor.name;
      if (
        selectedCounselorId &&
        counselor.counselor_id == selectedCounselorId
      ) {
        option.selected = true;
      }
      counselorSelect.appendChild(option);
    });

    if (selectedCounselorId) {
      counselorSelect.value = selectedCounselorId;
    }
  } catch (error) {
    console.error("Error loading counselors:", error);
    counselorSelect.innerHTML =
      '<option value="">Error loading counselors</option>';
  } finally {
    counselorSelect.disabled = false;
  }
}

// Refresh time slots in edit modal based on date, counselor, and consultation type
async function refreshEditModalTimeSlots(
  dateStr,
  selectedTime,
  counselorId,
  consultationType
) {
  const timeSelect = document.getElementById("editTime");
  if (!timeSelect || !dateStr) return;

  timeSelect.disabled = true;
  timeSelect.innerHTML = '<option value="">Loading time slots...</option>';

  try {
    const dayOfWeek = getDayOfWeek(dateStr);
    const counselorSelect = document.getElementById("editCounselorPreference");
    const currentCounselorId =
      counselorId || (counselorSelect ? counselorSelect.value : "");

    // Build URL to get available counselors for the date
    const url = new URL(
      (window.BASE_URL || "/") + "student/get-counselors-by-availability"
    );
    url.searchParams.append("date", dateStr);
    url.searchParams.append("day", dayOfWeek);
    url.searchParams.append("time", "00:00-23:59");
    url.searchParams.append("from", "00:00");
    url.searchParams.append("to", "23:59");
    url.searchParams.append("timeMode", "overlap");

    const response = await fetch(url.toString(), {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
    });

    let availableRanges = [];
    if (response.ok) {
      const data = await response.json();
      if (data.status === "success" && Array.isArray(data.counselors)) {
        // Filter to selected counselor or get all
        const relevantCounselors =
          currentCounselorId && currentCounselorId !== "No preference"
            ? data.counselors.filter(
                (c) => c.counselor_id == currentCounselorId
              )
            : data.counselors;

        // Get counselor schedules and generate 30-min slots
        for (const counselor of relevantCounselors) {
          try {
            const availRes = await fetch(
              (window.BASE_URL || "/") +
                "counselor/profile/availability?counselorId=" +
                encodeURIComponent(counselor.counselor_id)
            );
            const availData = await availRes.json();
            const daySchedule =
              availData &&
              availData.availability &&
              availData.availability[dayOfWeek]
                ? availData.availability[dayOfWeek]
                : [];
            const slotStrings = Array.isArray(daySchedule)
              ? daySchedule.map((r) => r && r.time_scheduled).filter(Boolean)
              : [];
            const ranges = generateHalfHourRangeUnion(slotStrings);
            availableRanges = availableRanges.concat(ranges);
          } catch (e) {
            console.error("Error fetching counselor availability:", e);
          }
        }
        // Remove duplicates
        availableRanges = Array.from(new Set(availableRanges));
        availableRanges.sort();
      }
    }

    // Get booked times for the date
    const bookedUrl = new URL(
      (window.BASE_URL || "/") + "student/appointments/booked-times"
    );
    bookedUrl.searchParams.append("date", dateStr);
    if (currentCounselorId && currentCounselorId !== "No preference") {
      bookedUrl.searchParams.append("counselor_id", currentCounselorId);
    }
    if (consultationType) {
      bookedUrl.searchParams.append("consultation_type", consultationType);
    }

    const bookedRes = await fetch(bookedUrl.toString(), {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json" },
    });

    let booked = [];
    if (bookedRes.ok) {
      const bookedData = await bookedRes.json();
      if (
        bookedData &&
        bookedData.status === "success" &&
        Array.isArray(bookedData.booked)
      ) {
        booked = bookedData.booked;
      }
    }

    // Build options
    const fragment = document.createDocumentFragment();
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select a time slot";
    fragment.appendChild(placeholder);

    const bookedSet = new Set(booked);
    let availableCount = 0;
    for (const slot of availableRanges) {
      if (!bookedSet.has(slot)) {
        const opt = document.createElement("option");
        opt.value = slot;
        opt.textContent = slot;
        if (selectedTime && slot === selectedTime) {
          opt.selected = true;
        }
        fragment.appendChild(opt);
        availableCount++;
      }
    }

    timeSelect.innerHTML = "";
    timeSelect.appendChild(fragment);

    if (availableCount === 0) {
      const none = document.createElement("option");
      none.value = "";
      none.textContent = "No available time slots for this date";
      none.disabled = true;
      timeSelect.appendChild(none);
    }

    // Restore selected time if it's still available
    if (
      selectedTime &&
      timeSelect.querySelector(`option[value="${selectedTime}"]`)
    ) {
      timeSelect.value = selectedTime;
    }
  } catch (error) {
    console.error("Error refreshing time slots:", error);
    timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
  } finally {
    timeSelect.disabled = false;
  }
}

// Setup event listeners for edit modal
function setupEditModalEventListeners() {
  // Remove existing listeners to avoid duplicates
  const dateInput = document.getElementById("editDate");
  const timeSelect = document.getElementById("editTime");
  const counselorSelect = document.getElementById("editCounselorPreference");
  const consultationTypeSelect = document.getElementById(
    "editConsultationType"
  );
  const consultationTypeHelp = document.getElementById(
    "editConsultationTypeHelp"
  );

  // Clone and replace to remove all event listeners
  if (dateInput) {
    const newDateInput = dateInput.cloneNode(true);
    dateInput.parentNode.replaceChild(newDateInput, dateInput);
    newDateInput.addEventListener("change", async function () {
      const date = newDateInput.value;
      const counselor = counselorSelect ? counselorSelect.value : "";
      const consultationType = consultationTypeSelect
        ? consultationTypeSelect.value
        : "";
      const currentTime = timeSelect ? timeSelect.value : "";
      if (date) {
        await refreshEditModalTimeSlots(
          date,
          currentTime,
          counselor,
          consultationType
        );
        // Also update counselor options based on availability
        if (counselorSelect) {
          await updateCounselorOptionsForEditModal(
            date,
            timeSelect ? timeSelect.value : "",
            counselor
          );
        }
      }
    });
  }

  if (counselorSelect) {
    const newCounselorSelect = counselorSelect.cloneNode(true);
    counselorSelect.parentNode.replaceChild(
      newCounselorSelect,
      counselorSelect
    );
    newCounselorSelect.addEventListener("change", async function () {
      const date = dateInput ? dateInput.value : "";
      const counselor = newCounselorSelect.value;
      const consultationType = consultationTypeSelect
        ? consultationTypeSelect.value
        : "";
      const currentTime = timeSelect ? timeSelect.value : "";
      if (date) {
        await refreshEditModalTimeSlots(
          date,
          currentTime,
          counselor,
          consultationType
        );
      }
    });
  }

  if (consultationTypeSelect) {
    const newConsultationTypeSelect = consultationTypeSelect.cloneNode(true);
    consultationTypeSelect.parentNode.replaceChild(
      newConsultationTypeSelect,
      consultationTypeSelect
    );
    newConsultationTypeSelect.addEventListener("change", function () {
      const selectedType = newConsultationTypeSelect.value;
      if (consultationTypeHelp) {
        if (selectedType === "Group Consultation") {
          consultationTypeHelp.textContent =
            "Group consultation allows up to 5 students per time slot.";
          consultationTypeHelp.style.color = "#2563EB";
        } else if (selectedType === "Individual Consultation") {
          consultationTypeHelp.textContent =
            "One-on-one consultation with the counselor.";
          consultationTypeHelp.style.color = "#6c757d";
        } else {
          consultationTypeHelp.textContent = "";
        }
      }
      // Refresh time slots when consultation type changes
      const date = dateInput ? dateInput.value : "";
      const counselor = counselorSelect ? counselorSelect.value : "";
      const currentTime = timeSelect ? timeSelect.value : "";
      if (date) {
        refreshEditModalTimeSlots(date, currentTime, counselor, selectedType);
      }
    });
  }
}

// Update counselor options for edit modal based on availability
async function updateCounselorOptionsForEditModal(
  preferredDate,
  preferredTime,
  currentValue
) {
  const counselorSelect = document.getElementById("editCounselorPreference");
  if (!counselorSelect || !preferredDate) return;

  counselorSelect.disabled = true;
  const originalValue = counselorSelect.value;

  try {
    const dayOfWeek = getDayOfWeek(preferredDate);
    const normalizedTimeRange = normalizePreferredTimeTo24hRange(preferredTime);
    const timeBounds = extractStartEnd24h(preferredTime);

    const url = new URL(
      (window.BASE_URL || "/") + "student/get-counselors-by-availability"
    );
    url.searchParams.append("date", preferredDate);
    url.searchParams.append("day", dayOfWeek);
    if (preferredTime) {
      url.searchParams.append("time", normalizedTimeRange || preferredTime);
      if (timeBounds) {
        url.searchParams.append("from", timeBounds.start);
        url.searchParams.append("to", timeBounds.end);
        url.searchParams.append("timeMode", "overlap");
      }
    } else {
      url.searchParams.append("time", "00:00-23:59");
      url.searchParams.append("from", "00:00");
      url.searchParams.append("to", "23:59");
      url.searchParams.append("timeMode", "overlap");
    }

    const response = await fetch(url.toString(), {
      method: "GET",
      credentials: "include",
      headers: { Accept: "application/json", "Cache-Control": "no-cache" },
    });

    if (!response.ok) throw new Error("Network error");
    const data = await response.json();

    counselorSelect.innerHTML = '<option value="">Select a counselor</option>';
    counselorSelect.insertAdjacentHTML(
      "beforeend",
      '<option value="No preference">No preference</option>'
    );

    if (data.status === "success" && Array.isArray(data.counselors)) {
      data.counselors.forEach((c) => {
        const opt = document.createElement("option");
        opt.value = c.counselor_id;
        opt.textContent = c.name;
        counselorSelect.appendChild(opt);
      });

      if (currentValue || originalValue) {
        counselorSelect.value = currentValue || originalValue;
      }

      if (data.counselors.length === 0) {
        const opt = document.createElement("option");
        opt.value = "";
        opt.textContent = "No counselors available for the selected date/time.";
        opt.disabled = true;
        counselorSelect.appendChild(opt);
      }
    }
  } catch (e) {
    console.error("Error loading counselors by availability:", e);
    counselorSelect.innerHTML =
      '<option value="">Error loading counselors</option>';
  } finally {
    counselorSelect.disabled = false;
  }
}

// Helper function to generate half-hour ranges from time slots (copied from student_schedule_appointment.js)
function generateHalfHourRangeUnion(slotStrings) {
  const set = new Set();
  slotStrings.forEach((s) => {
    const str = String(s).trim();
    if (!str) return;
    if (str.includes("-")) {
      const parts = str.split("-");
      if (parts.length !== 2) return;
      const start = parseTime12ToMinutes_student(parts[0].trim());
      const end = parseTime12ToMinutes_student(parts[1].trim());
      if (start === null || end === null || end <= start) return;
      for (let t = start; t + 30 <= end; t += 30) {
        const from = formatMinutesTo12h_student(t);
        const to = formatMinutesTo12h_student(t + 30);
        set.add(`${from} - ${to}`);
      }
    }
  });
  const arr = Array.from(set);
  arr.sort((a, b) => {
    const [af] = a.split("-").map((x) => x.trim());
    const [bf] = b.split("-").map((x) => x.trim());
    return parseTime12ToMinutes_student(af) - parseTime12ToMinutes_student(bf);
  });
  return arr;
}

function parseTime12ToMinutes_student(t) {
  if (!t) return null;
  const m = String(t)
    .trim()
    .match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
  if (!m) return null;
  let h = parseInt(m[1], 10);
  const min = parseInt(m[2], 10);
  const ampm = m[3].toUpperCase();
  if (h === 12) h = 0;
  if (ampm === "PM") h += 12;
  return h * 60 + min;
}

function formatMinutesTo12h_student(total) {
  let minutes = total % 60;
  let h24 = Math.floor(total / 60) % 24;
  const ampm = h24 >= 12 ? "PM" : "AM";
  let h12 = h24 % 12;
  if (h12 === 0) h12 = 12;
  const mm = String(minutes).padStart(2, "0");
  return `${h12}:${mm} ${ampm}`;
}
