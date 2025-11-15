<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <title>Schedule Appointment - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/student/student_schedule_appointment.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/student/header.css') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <div class="logo-title-container">
                        <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="University Logo" class="logo" />
                        <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    </div>
                    <button class="custom-navbar-toggler d-lg-none align-items-center" type="button" id="navbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-gear"></i></span>
                    </button>
                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <ul class="navbar-nav nav-links ms-auto">
                            <li><a href="<?= base_url('student/my-appointments') ?>"><i class="fas fa-list-alt"></i> My Appointments History</a></li>
                            <li><a href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Navbar Drawer for Small Screens -->
    <div class="navbar-drawer d-lg-none" id="navbarDrawer">
        <div class="drawer-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #060E57;">
            <h5 class="m-0">Student Menu</h5>
            <button class="btn-close btn-close-white" id="navbarDrawerClose" aria-label="Close"></button>
        </div>
        <ul class="navbar-nav nav-links p-3">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/my-appointments') ?>"><i class="fas fa-list-alt"></i> My Appointments History</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
        </ul>
    </div>

    <!-- Overlay for Navbar Drawer -->
    <div class="navbar-overlay d-lg-none" id="navbarOverlay"></div>

    <main>
        <!-- Main Container with Side-by-Side Layout -->
        <div class="schedule-container">
            <!-- Left Side: Appointment Form -->
            <div class="appointment-form-container">
                <div class="appointment-form" id="appointmentForm">
                    <h4>Schedule an Appointment</h4>

                    <div id="formMessage" class="hidden"></div>

                    <form id="consultationForm" action="/Counselign/includes/save_appointment.php" method="post">

                        <div class="form-group">
                            <label for="consultationType">
                                Consultation Type <span class="required-asterisk">*</span>
                            </label>
                            <select id="consultationType" name="consultationType" class="form-control" required>
                                <option value="">Select consultation type</option>
                                <option value="Individual Consultation">Individual Consultation</option>
                                <option value="Group Consultation">Group Consultation</option>
                            </select>
                            <small id="consultationTypeHelp" class="form-text text-muted"></small>
                        </div>

                        <!-- Date & Time Section -->
                        <div class="form-group">
                            <label for="preferredDate">
                                Preferred Date <span class="required-asterisk">*</span>
                            </label>
                            <input id="preferredDate" name="preferredDate" type="date" class="form-control"
                                min="" value="" required>
                            <small>Select a date at least one day in the future</small>
                        </div>

                        <div class="form-group">
                            <label for="preferredTime">
                                Preferred Time <span class="required-asterisk">*</span>
                            </label>
                            <select id="preferredTime" name="preferredTime" class="form-control" required>
                                <option value="">Select a time slot</option>
                                <option value="8:00 AM - 8:30 AM">8:00 AM - 8:30 AM</option>
                                <option value="8:30 AM - 9:00 AM">8:30 AM - 9:00 AM</option>
                                <option value="9:00 AM - 9:30 AM">9:00 AM - 9:30 AM</option>
                                <option value="9:30 AM - 10:00 AM">9:30 AM - 10:00 AM</option>
                                <option value="10:00 AM - 10:30 AM">10:00 AM - 10:30 AM</option>
                                <option value="10:30 AM - 11:00 AM">10:30 AM - 11:00 AM</option>
                                <option value="11:00 AM - 11:30 AM">11:00 AM - 11:30 AM</option>
                                <option value="1:00 PM - 1:30 PM">1:00 PM - 1:30 PM</option>
                                <option value="1:30 PM - 2:00 PM">1:30 PM - 2:00 PM</option>
                                <option value="2:00 PM - 2:30 PM">2:00 PM - 2:30 PM</option>
                                <option value="2:30 PM - 3:00 PM">2:30 PM - 3:00 PM</option>
                                <option value="3:00 PM - 3:30 PM">3:00 PM - 3:30 PM</option>
                                <option value="3:30 PM - 4:00 PM">3:30 PM - 4:00 PM</option>
                                <option value="4:00 PM - 4:30 PM">4:00 PM - 4:30 PM</option>
                                <option value="4:30 PM - 5:00 PM">4:30 PM - 5:00 PM</option>
                            </select>
                        </div>

                        <!-- Counselor & Consultation Type Section -->
                        <div class="form-group">
                            <label for="counselorPreference">
                                Counselor Preference <span class="required-asterisk">*</span>
                            </label>
                            <select id="counselorPreference" name="counselorPreference" class="form-control" required>
                                <option value="">Select a counselor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="methodType">
                                Method Type <span class="required-asterisk">*</span>
                            </label>
                            <select id="methodType" name="methodType" class="form-control" required>
                                <option value="">Select a method type</option>
                                <option>In-person</option>
                                <option>Online (Video)</option>
                                <option>Online (Audio only)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="purpose">
                                Purpose of Consultation <span class="required-asterisk">*</span>
                            </label>
                            <select id="purpose" name="purpose" class="form-control" required>
                                <option value="">Select the purpose of your consultation</option>
                                <option value="Counseling">Counseling</option>
                                <option value="Psycho-Social Support">Psycho-Social Support</option>
                                <option value="Initial Interview">Initial Interview</option>
                            </select>
                        </div>

                        <!-- Brief Description -->
                        <div class="form-group full-width">
                            <label for="briefDescription">Brief Description (Optional)</label>
                            <textarea id="briefDescription" name="description" class="form-control" rows="3"
                                placeholder="Briefly describe what you'd like to discuss..."></textarea>
                        </div>

                        <!-- Counseling Informed Consent Accordion -->
                        <div class="consent-accordion-container">
                            <div class="accordion" id="counselingConsentAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="consentHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#consentCollapse" aria-expanded="false" aria-controls="consentCollapse">
                                            <i class="fas fa-file-contract me-2"></i>
                                            Counseling Informed Consent Form
                                        </button>
                                    </h2>
                                    <div id="consentCollapse" class="accordion-collapse collapse" aria-labelledby="consentHeading"
                                        data-bs-parent="#counselingConsentAccordion">
                                        <div class="accordion-body">
                                            <div class="consent-content">
                                                <div class="consent-intro">
                                                    <p class="consent-description">
                                                        This form intends to ask the consent of the client to undergo counseling session(s) with a Registered Guidance Counselor (RGC) from Guidance and Counseling Services. This also stipulates the nature and scope of counseling, key elements of confidentiality, and the rights and responsibilities of the client and counselor.
                                                    </p>
                                                    <p class="consent-note">
                                                        <strong>Please feel free to speak with the counselor for any clarifications regarding this form.</strong>
                                                    </p>
                                                </div>

                                                <div class="consent-section">
                                                    <h4 class="consent-section-title">
                                                        <i class="fas fa-gavel me-2"></i>
                                                        THE RIGHT OF INFORMED CONSENT
                                                    </h4>
                                                    <p>The clients have the right to decide whether to enter into a counseling relationship with the specific counselor and must be told what to expect (Villar, 2009).</p>
                                                </div>

                                                <div class="consent-section">
                                                    <h4 class="consent-section-title">
                                                        <i class="fas fa-handshake me-2"></i>
                                                        COUNSELING
                                                    </h4>
                                                    <p>It is a collaborative effort between the counselor and client. Professional counselors help clients identify goals and potential solutions to problems which cause emotional turmoil; seek to improve communication and coping skills; strengthen self-esteem; and promote behavior change and optimal mental health (American Counseling Association, 2021).</p>
                                                </div>

                                                <div class="consent-section">
                                                    <h4 class="consent-section-title">
                                                        <i class="fas fa-list-alt me-2"></i>
                                                        TERMS AND CONDITIONS:
                                                    </h4>
                                                    <ul class="consent-terms-list">
                                                        <li>The client will share information about his or her problems or issues to the counselor that may have affected certain areas of his or her life.</li>
                                                        <li>The client may ask questions before, during, and after the counseling session (s) if there are things unclear to her or him.</li>
                                                        <li>The counselor will guide the session and may ask probing questions to better understand the:
                                                            <ul class="consent-sub-list">
                                                                <li>specific or various concerns of the client re: personal, academic, emotional, psychological, occupational, spiritual, etc.</li>
                                                            </ul>
                                                        </li>
                                                        <li>Both the client and the counselor have the right not to continue the counseling sessions without any impediment unless required by specific authority.</li>
                                                        <li>In case of termination, both client and counselor have the responsibility to notify each party on the reason for dismissing the sessions for record purposes.</li>
                                                        <li>Virtual/electronic counseling sessions may experience privacy and other technical glitches.</li>
                                                        <li>All information provided in this form and during counseling will be kept strictly confidential except for reasons cited in the dimensions of confidentiality.</li>
                                                    </ul>
                                                </div>

                                                <div class="consent-section">
                                                    <h4 class="consent-section-title">
                                                        <i class="fas fa-shield-alt me-2"></i>
                                                        DIMENSIONS OF CONFIDENTIALITY
                                                    </h4>
                                                    <p>The clients have a right to know that the counselor may be discussing certain details of the relationship with a supervisor or a colleague. Moreover, there are times when confidential information must be divulged and there are exemptions.</p>

                                                    <p>Arthur and Swanson (1993) note exemptions cited by Bisell and Royce (1992) to the ethical principle of confidentiality:</p>

                                                    <ul class="consent-exemptions-list">
                                                        <li><strong>The client is a danger to self or others.</strong> The law places physical safety above considerations of confidentiality or the right of privacy. Protection of the person takes precedence and includes the duty to warn.</li>
                                                        <li><strong>The client requests the release of information.</strong> Privacy belongs to the client and may be waived. The counselor should release information as requested by the client.</li>
                                                        <li><strong>A court orders release of information.</strong> The responsibility under the law for the counselor to maintain confidentiality is overridden when the court determines that the information is needed to serve the cause of justice.</li>
                                                        <li><strong>The counselor is receiving systematic clinical supervision.</strong> The client gives up the right to confidentiality when it is known that session material will be used during supervision.</li>
                                                        <li><strong>Clients are below the age of 18.</strong> Parents or guardians have the legal right to communication between the minor and the counselor.</li>
                                                        <li><strong>The counselor has reason to suspect child abuse.</strong> All states now legally require the reporting of suspected abuse.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Acknowledgment Checkboxes -->
                        <div class="acknowledgment-section">
                            <h5 class="acknowledgment-title">
                                <i class="fas fa-check-circle me-2"></i>
                                ACKNOWLEDGEMENT
                            </h5>
                            <div class="acknowledgment-checkboxes">
                                <div class="form-check acknowledgment-checkbox">
                                    <input class="form-check-input me-2" type="checkbox" id="consentRead" name="consentRead" required>
                                    <label class="form-check-label" for="consentRead">
                                        I have read and reviewed the content of this Counseling Informed Consent.
                                    </label>
                                </div>
                                <div class="form-check acknowledgment-checkbox">
                                    <input class="form-check-input me-2" type="checkbox" id="consentAccept" name="consentAccept" required>
                                    <label class="form-check-label" for="consentAccept">
                                        I accept this agreement and consent to counseling.
                                    </label>
                                </div>
                            </div>
                            <div id="acknowledgmentError" class="acknowledgment-error hidden">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span>Please acknowledge both statements above to proceed.</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="button-group">
                            <button type="submit" id="scheduleAppointmentBtn">
                                <span id="submitText">Schedule Appointment</span>
                                <span id="loadingIndicator" class="hidden">
                                    <i class="fas fa-spinner"></i> Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Side: Counselor's Schedule -->
            <div class="counselors-schedule-sidebar">
                <div class="schedule-sidebar-header">
                    <h3>
                        <i class="fas fa-calendar-alt me-2"></i>
                        Counselor's Schedule
                    </h3>
                </div>

                <div class="schedule-sidebar-content">
                    <!-- Calendar Section -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button id="counselorsPrevMonth" class="calendar-nav-btn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h4 id="counselorsCurrentMonth" class="calendar-month"></h4>
                            <button id="counselorsNextMonth" class="calendar-nav-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="calendar-grid" id="counselorsCalendarGrid">
                            <!-- Calendar will be dynamically generated here -->
                        </div>
                    </div>

                    <!-- Counselor Schedules Display Section -->
                    <div class="counselor-schedules-section">
                        <div class="section-header">
                            <h4><i class="fas fa-user-md me-2"></i>Available Counselors</h4>
                            <p class="text-muted">View counselors and their time slots by day</p>
                        </div>

                        <div class="schedules-container" id="counselorSchedulesContainer">
                            <div class="loading-schedules">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading counselor schedules...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <?php echo view('modals/student_dashboard_modals'); ?>
    <script src="<?= base_url('js/modals/student_dashboard_modals.js') ?>"></script>
    <script src="<?= base_url('js/utils/timeFormatter.js') ?>"></script>
    <script src="<?= base_url('js/student/student_header_drawer.js') ?>"></script>
    <script src="<?= base_url('js/student/student_schedule_appointment.js') ?>" defer></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set the base URL for JavaScript
        window.BASE_URL = '<?= base_url() ?>';
    </script>
</body>

</html>