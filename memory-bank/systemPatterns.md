## Resend Reset Code Modal Pattern

**Purpose**: Professional modal interface for resending password reset codes with improved user experience

**Modal Structure**:
- **HTML**: Bootstrap 5 modal with proper accessibility attributes
- **Form Elements**: Label, input field, validation messages, action buttons
- **Design**: Consistent with other system modals (login, signup, contact, resend verification)
- **Responsive**: Mobile-friendly layout with proper spacing

**User Experience Features**:
- **Input Validation**: Real-time validation for email/user ID format
- **Loading States**: Professional spinner animations during API calls
- **Error Handling**: Clear error messages with proper styling
- **Keyboard Support**: Enter key submission, proper focus management
- **Modal Management**: Clean open/close behavior with input clearing
- **Integration**: Seamlessly integrated into existing forgot password flow

**Technical Implementation**:
- **JavaScript**: Type-safe implementation with clear variable naming
- **Validation**: Email regex and user ID format checking
- **API Integration**: Uses existing `forgot-password/send-code` endpoint
- **State Management**: Proper button state handling and cleanup
- **Event Handling**: Comprehensive event listeners for all interactions
- **Data Format**: JSON format matching backend expectations

**Files Modified**:
- `app/Views/landing.php` - Added resend reset code modal HTML and resend link
- `public/js/landing.js` - Enhanced with resend reset code functionality
- `public/css/landing.css` - Added professional styling

**Code Quality Standards**:
- **Type Safety**: Clear, descriptive variable names and function organization
- **Error Handling**: Comprehensive error handling without ambiguous patterns
- **Maintainability**: Clean code structure following MVC architecture
- **Accessibility**: Proper ARIA labels and keyboard navigation support
- **Performance**: Efficient DOM manipulation and event handling
- **Backward Compatibility**: Maintains existing forgot password functionality

## Resend Verification Modal Pattern

**Purpose**: Professional modal interface for resending verification emails with improved user experience

**Modal Structure**:
- **HTML**: Bootstrap 5 modal with proper accessibility attributes
- **Form Elements**: Label, input field, validation messages, action buttons
- **Design**: Consistent with other system modals (login, signup, contact)
- **Responsive**: Mobile-friendly layout with proper spacing

**User Experience Features**:
- **Input Validation**: Real-time validation for email/user ID format
- **Loading States**: Professional spinner animations during API calls
- **Error Handling**: Clear error messages with proper styling
- **Keyboard Support**: Enter key submission, proper focus management
- **Modal Management**: Clean open/close behavior with input clearing

**Technical Implementation**:
- **JavaScript**: Type-safe implementation with clear variable naming
- **Validation**: Email regex and user ID format checking
- **API Integration**: Existing `resend-verification-email` endpoint
- **State Management**: Proper button state handling and cleanup
- **Event Handling**: Comprehensive event listeners for all interactions

**Files Modified**:
- `app/Views/auth/verification_prompt.php` - Added resend verification modal HTML
- `public/js/auth/verification_prompt.js` - Enhanced with modal functionality
- `public/css/auth/verification_prompt.css` - Added professional styling

**Code Quality Standards**:
- **Type Safety**: Clear, descriptive variable names and function organization
- **Error Handling**: Comprehensive error handling without ambiguous patterns
- **Maintainability**: Clean code structure following MVC architecture
- **Accessibility**: Proper ARIA labels and keyboard navigation support
- **Performance**: Efficient DOM manipulation and event handling

## Automatic Email Notifications for Appointment Actions

**Email Notification System:**
- **Service Architecture**: Dedicated `AppointmentEmailService` class handles all appointment-related email notifications
- **PHPMailer Integration**: Uses PHPMailer with Gmail SMTP for reliable email delivery
- **Type-Safe Implementation**: Comprehensive error handling and logging for email operations
- **Counselor Email Lookup**: Automatic counselor email retrieval using `counselors.counselor_id = users.user_id` relationship

**Email Notification Triggers:**
- **Appointment Booking**: When student books new appointment with counselor preference
- **Appointment Editing**: When student updates pending appointment details
- **Appointment Cancellation**: When student cancels pending appointment with reason
- **Appointment Approval**: When counselor approves student's appointment
- **Appointment Rejection**: When counselor rejects student's appointment with reason
- **Appointment Cancellation by Counselor**: When counselor cancels approved appointment with reason
- **Conditional Sending**: Email sent only when counselor preference is selected (not "No preference")

**Email Content Features:**
- **Professional HTML Templates**: Responsive email design with system branding
- **Complete Appointment Details**: Date, time, consultation type, purpose, description
- **Student Information**: Name, student ID, email address
- **Counselor Information**: Name, email, counselor ID (for counselor action emails)
- **Action-Specific Templates**: Different templates for booking, editing, cancellation, approval, rejection notifications
- **Visual Design**: Color-coded sections with icons and professional styling
- **Cancellation Details**: Special cancellation reason section with warning styling
- **Rejection Details**: Special rejection reason section with warning styling
- **Approval Confirmation**: Success-themed templates with confirmation messaging

**Technical Implementation:**
- **Non-Blocking Operations**: Email sending doesn't interfere with appointment operations
- **Comprehensive Logging**: Detailed logging for debugging email delivery issues
- **Error Handling**: Graceful handling of email failures with proper error messages
- **Database Integration**: Seamless integration with existing appointment and user data
- **Manila Timezone Support**: All appointment updates use Asia/Manila timezone with format 'Y-m-d H:i:s'
- **Timezone Testing**: Built-in timezone verification functionality for debugging
- **Follow-up Session Management**: Complete CRUD operations for follow-up sessions
- **Conflict Detection**: Prevents scheduling conflicts between follow-up sessions
- **Status-Based UI**: Edit functionality only available for pending sessions

**Controller Integration:**
- **Student Appointment Controller**: Enhanced `save()`, `update()`, and `cancel()` methods
- **Counselor Appointment Controller**: Enhanced `updateAppointmentStatus()` method
- **Counselor Follow-up Controller**: Enhanced with edit functionality and email notifications
- **Helper Methods**: 
  - `sendAppointmentNotificationToCounselor()` for booking/editing notifications
  - `sendAppointmentCancellationNotificationToCounselor()` for cancellation notifications
  - `sendAppointmentNotificationToStudent()` for counselor action notifications
- **Data Flow**: Automatic student/counselor information retrieval for email content
- **Loading States**: Professional loading animations for all modal interactions

## Follow-up Session Management Pattern

**Purpose**: Comprehensive follow-up session management with edit functionality and email notifications

**Triggers**:
- **Create**: Counselor creates new follow-up session for completed appointment
- **Edit**: Counselor modifies pending follow-up session details
- **Complete**: Counselor marks follow-up session as completed
- **Cancel**: Counselor cancels follow-up session with reason

**Email Notifications**:
- **Created**: Student receives notification when follow-up session is created
- **Edited**: Student receives notification when follow-up session is modified
- **Completed**: Student receives notification when follow-up session is completed
- **Cancelled**: Student receives notification when follow-up session is cancelled

**Email Content**:
- **Session Details**: Date, time, consultation type, description, reason
- **Counselor Information**: Name, email, counselor ID
- **Professional Templates**: HTML and text versions with proper branding
- **Color Coding**: Blue (created), Yellow (edited), Green (completed), Red (cancelled)

**Technical Implementation**:
- **Edit Functionality**: Only available for pending sessions
- **Conflict Detection**: Prevents scheduling conflicts between follow-up sessions
- **Manila Timezone**: All updates use Asia/Manila timezone with proper format
- **Form Validation**: Comprehensive validation for all edit operations
- **Activity Tracking**: Updates counselor and student activity logs
- **Status Management**: Smart button states based on session status

**Controller Integration**:
- **Follow-up Controller**: Enhanced with `editFollowUp()` method
- **Email Service**: New methods for follow-up email notifications
- **Email Templates**: Dedicated templates for follow-up actions
- **Helper Methods**: 
  - `sendFollowUpNotificationToStudent()` for all follow-up actions
  - `getManilaDateTime()` for timezone handling
  - `getStudentEmail()` for student information retrieval

**Files Created/Modified:**
- `app/Services/AppointmentEmailService.php` - Enhanced email service class with counselor methods
- `app/Services/CounselorEmailTemplates.php` - New template class for counselor email notifications
- `app/Controllers/Student/Appointment.php` - Enhanced with email notifications
- `app/Controllers/Counselor/Appointments.php` - Enhanced with email notifications and loading states
- `public/js/student/my_appointments.js` - Enhanced with loading animations
- `public/js/counselor/appointments.js` - Enhanced with loading animations
- `app/Config/Routes.php` - Added email testing route

## Student Schedule Appointment Counseling Consent Accordion

**Counseling Consent Accordion System:**
- **Accordion Structure**: Bootstrap 5 accordion with royal blue gradient theme, positioned below description input
- **Comprehensive Content**: Complete informed consent form with all required legal sections
- **Professional Design**: Royal blue gradient theme matching system design with proper typography
- **Always Available**: Accordion is always visible and accessible, not hidden or conditional
- **User Actions**: 
  - Collapsible design doesn't interfere with form completion
  - Clear visual hierarchy with icons and proper formatting
  - Easy to read with proper spacing and typography
- **Content Sections**:
  - Introduction and purpose statement
  - The Right of Informed Consent
  - Counseling definition and scope
  - Terms and Conditions (7 detailed points)
  - Dimensions of Confidentiality (6 exemption categories)
- **Responsive Design**: Mobile-friendly layout that adapts to different screen sizes

**Implementation Files:**
- **HTML**: `app/Views/student/student_schedule_appointment.php` - Accordion HTML structure with Bootstrap 5 classes
- **CSS**: `public/css/student/student_schedule_appointment.css` - Comprehensive styling with responsive design

**Key Features:**
- **Professional Design**: Royal blue gradient theme with smooth animations
- **Comprehensive Content**: Complete informed consent form with all legal requirements
- **User-Friendly Interface**: Collapsible design that doesn't interfere with form completion
- **Responsive Design**: Mobile-friendly layout with adaptive sizing
- **Accessibility**: Keyboard accessible accordion with screen reader friendly structure
- **Type Safety**: Proper HTML structure with Bootstrap 5 classes and clean CSS
- **Visual Hierarchy**: Clear section organization with icons and proper formatting

## Counselor Time Format Standardization

**12-Hour Format Implementation:**
- **Database Storage**: `time_scheduled` field in `counselor_availability` table now stores 12-hour format with meridian labels
- **Format Examples**: "9:00 AM-11:30 AM", "1:30 PM-3:00 PM", "7:00 AM-5:00 PM"
- **Input Handling**: Counselor profile time selects populate with 12-hour format options
- **Display Consistency**: All availability displays show 12-hour format across admin, counselor, and student views
- **Backward Compatibility**: System handles both 12-hour and 24-hour formats for existing data

**Updated Controllers:**
- `App\Controllers\Counselor\Availability`: Handles 12-hour format validation and storage
- `App\Controllers\Student\Appointment`: Enhanced time parsing for counselor availability checking
- `App\Controllers\Admin\AdminsManagement`: Updated time slot availability checking for 12-hour format

**Updated Frontend:**
- `public/js/counselor/counselor_profile.js`: Modified time handling functions for 12-hour format
- `public/js/utils/timeFormatter.js`: Already supports 12-hour format conversion (existing)

## Counselor Follow-up Sessions (API)

Routes (under `counselor` group):

- `GET counselor/follow-up` → `FollowUp::index` (view)
- `GET counselor/follow-up/completed-appointments` → `FollowUp::getCompletedAppointments` (query: `search` - optional)
- `GET counselor/follow-up/sessions` → `FollowUp::getFollowUpSessions` (query: `parent_appointment_id`)
- `GET counselor/follow-up/availability` → `FollowUp::getCounselorAvailability` (query: `date`)
- `POST counselor/follow-up/create` → `FollowUp::createFollowUp`
- `POST counselor/follow-up/complete` → `FollowUp::completeFollowUp` (form: `id`)
- `POST counselor/follow-up/cancel` → `FollowUp::cancelFollowUp` (form: `id`, `reason`)

Controller: `App\Controllers\Counselor\FollowUp`

**Search Functionality:**
- Search parameter: `?search=<term>` in `getCompletedAppointments`
- Searches across: student_id, username, email, first_name, last_name, preferred_date, preferred_time, consultation_type, purpose, reason
- Returns filtered appointments with search_term in response

**Display Enhancement:**
- **Purpose Field**: Displays appointment purpose with bullseye icon (conditional rendering)
- **Reason Field**: Displays appointment reason with clipboard-list icon (conditional rendering)  
- **Description Field**: Displays appointment description with file-text icon (conditional rendering)
- **Data Source**: Controllers return `appointments.*` with all appointment fields including purpose, reason, and description
- **Frontend**: Enhanced `public/js/counselor/follow_up.js` to display these fields in appointment cards

## Admin Follow-up Sessions (API)

Routes (under `admin` group):

- `GET admin/follow-up-sessions` → `FollowUpSessions::index` (view)
- `GET admin/follow-up-sessions/completed-appointments` → `FollowUpSessions::getAllCompletedAppointments` (query: `search` - optional)
- `GET admin/follow-up-sessions/sessions` → `FollowUpSessions::getFollowUpSessions` (query: `parent_appointment_id`)

Controller: `App\Controllers\Admin\FollowUpSessions`

**Search Functionality:**
- Search parameter: `?search=<term>` in `getAllCompletedAppointments`
- Searches across: student_id, username, email, first_name, last_name, preferred_date, preferred_time, consultation_type, purpose, reason, counselor_name
- Returns filtered appointments with search_term in response

**Key Differences from Counselor Version:**
- Admin shows ALL completed appointments (no counselor filtering)
- Read-only view - no create/cancel/complete functionality
- Same UI/UX as counselor version but without action buttons
- Admin can view follow-up sessions for any completed appointment
- Admin search includes counselor name field

## Student Follow-up Sessions (API)

Routes (under `student` group):

- `GET student/follow-up-sessions` → `FollowUpSessions::index` (view)
- `GET student/follow-up-sessions/completed-appointments` → `FollowUpSessions::getCompletedAppointments` (query: `search` - optional)
- `GET student/follow-up-sessions/sessions` → `FollowUpSessions::getFollowUpSessions` (query: `parent_appointment_id`)

Controller: `App\Controllers\Student\FollowUpSessions`

**Search Functionality:**
- Search parameter: `?search=<term>` in `getCompletedAppointments`
- Searches across: student_id, username, email, first_name, last_name, preferred_date, preferred_time, consultation_type, purpose, reason, counselor_name, counselor_first_name, counselor_last_name
- Returns filtered appointments with search_term in response

**Key Features:**
- Student shows only their own completed appointments (student_id filtering)
- Read-only view - no create/cancel/complete functionality
- Same UI/UX as counselor version but without action buttons
- Security validation ensures students can only access their own appointments
- Student search includes counselor name fields

**Display Enhancement:**
- **Purpose Field**: Displays appointment purpose with bullseye icon (conditional rendering)
- **Reason Field**: Displays appointment reason with clipboard-list icon (conditional rendering)  
- **Description Field**: Displays appointment description with file-text icon (conditional rendering)
- **Counselor Name**: Displays counselor name with user-md icon (conditional rendering)
- **Data Source**: Controllers return `appointments.*` with all appointment fields including purpose, reason, and description
- **Frontend**: Enhanced `public/js/student/follow_up_sessions.js` to display these fields in appointment cards

Models:
- `AppointmentModel` – base appointments
- `FollowUpAppointmentModel` – follow-up sessions, fields include `status`, `reason`, `follow_up_sequence`, `parent_appointment_id`

UI Logic (in `public/js/counselor/follow_up.js`):
- In sessions modal:
  - Hide/disable `Create New Follow-up` if any sessions exist; show/enable only when none exist
  - `Create Next Follow-up` enabled only on the last session when that last session is `completed`
  - Add `Mark as Completed` and `Cancel` buttons beside `Create Next Follow-up`
  - `Mark as Completed` disabled when the session is already `completed`
  - `Cancel` enabled only when the session is `pending`; opens a modal requiring a reason

Views:
- `app/Views/counselor/follow_up.php`
  - Added `cancelFollowUpModal` with reason textarea and CSRF hidden field

## Notifications Endpoints

Student
- GET `student/notifications` → `App/Controllers/Student/Notifications::index`
- GET `student/notifications/unread-count` → `...::getUnreadCount`
- POST `student/notifications/mark-read` → `...::markAsRead`

Counselor
- GET `counselor/notifications` → `App/Controllers/Counselor/Notifications::index`
- GET `counselor/notifications/unread-count` → `...::getUnreadCount`
- POST `counselor/notifications/mark-read` → `...::markAsRead`

Notifications Model
- `NotificationsModel::getRecentNotifications(userId, lastActiveTime)` aggregates events, announcements, appointments (by `student_id`), and messages (student↔counselor).
- `getUnreadCount(userId)` mirrors same sources since last activity.
## Counselor Routes (Appointments & Reports)

- Base group: `counselor` → namespace `App\Controllers\Counselor`
- New routes added (duplicates of admin):
  - `GET counselor/appointments` → `Counselor\Appointments::index` → view `counselor/appointments`
  - `GET counselor/appointments/view-all` → `Counselor\Appointments::viewAll` → view `counselor/view_all_appointments`
  - `GET counselor/appointments/scheduled` → `Counselor\Appointments::scheduled` → view `counselor/scheduled_appointments`
  - `GET counselor/appointments/scheduled/get` → `Counselor\Appointments::getScheduledAppointments` (JSON)
  - `GET counselor/appointments/getAll` and `GET counselor/appointments/getAppointments` → `Counselor\Appointments::getAppointments` (JSON)
  - `POST counselor/appointments/updateStatus` → `Counselor\Appointments::updateStatus`
  - `POST counselor/appointments/updateAppointmentStatus` → `Counselor\Appointments::updateAppointmentStatus`
  - `GET counselor/appointments/schedule` → `Counselor\Appointments::getCounselorSchedule` (JSON - returns counselor's availability schedule)
  - `GET counselor/appointments/get_all_appointments` → `Counselor\GetAllAppointments::index` (JSON for charts and listings - FIXED: now includes all appointment statuses)
  - `GET counselor/history-reports` → `Counselor\HistoryReports::index` → view `counselor/history_reports`
  - `GET counselor/history-reports/data` → `Counselor\HistoryReports::getHistoryData`
  - `GET counselor/history-reports/historical-data` → `Counselor\HistoryReports::getHistoricalData`

Views & Assets:
- Views duplicated under `app/Views/counselor/` for `appointments.php`, `scheduled_appointments.php`, `view_all_appointments.php`, `history_reports.php`
- JS duplicated to `public/js/counselor/` with counselor endpoints
- CSS duplicated via import wrappers in `public/css/counselor/`

Rejection Flow (Counselor Appointments):
- Pending appointment actions in `appointmentDetailsModal` now follow a strict modal sequence:
  1. Click `#rejectAppointmentBtn` → open `#rejectionReasonModal` (backdrop: static, keyboard: false).
  2. Enter reason → click `#confirmRejectionBtn` → open `#confirmationModal` with action `reject` and carry `data-reason`.
  3. Click `#confirmActionBtn` → call `updateAppointmentStatus(id, 'rejected', reason)`.
- Direct auto-reject from footer click is removed to prevent bypassing reason capture.

Admin Scheduled Appointments Calendar:
- Updated `app/Views/admin/scheduled_appointments.php` to use a two-column layout `csq-layout` with a right sidebar mini-calendar identical to the counselor view.
- Enhanced `public/js/admin/scheduled_appointments.js` to render a mini-calendar using the same logic as counselor, counting only approved appointments per day from `admin/appointments/scheduled/get`.
- Styling is shared via `public/css/admin/scheduled_appointments.css` which already contains `.csq-*` and calendar classes.

Access Control:
- All counselor controllers enforce `session('role') === 'counselor'`

## System Patterns

### Architecture
- Framework: CodeIgniter 4 (MVC)
- Entrypoint: `public/index.php`
- Routing: `app/Config/Routes.php`
- Controllers: `app/Controllers/**` (Admin, User, Auth, etc.)
- Models: `app/Models/**` (e.g., `UserModel`, `AnnouncementModel`)
- Views: `app/Views/**` (admin, user, landing)
- Assets: `public/css/**`, `public/js/**`, `public/Photos/**`
- Database: configured via `app/Config/Database.php`; sample `ugcsystem.sql`
- Sessions/Logs: `writable/session/`, `writable/logs/`

### Domain modules (observed from codebase)
- Authentication: `app/Controllers/Auth.php`, `Logout.php`, `UpdatePassword.php`, `ForgotPassword.php`
- Admin area: `app/Controllers/Admin/**` (dashboard, users, counselors, announcements, events, appointments, history reports, messaging)
- User area: `app/Views/user/**`, `public/js/user/**`
- Announcements/Events: controllers + models; list/detail views
- Appointments: admin and user flows; debug logs under `writable/appointments_debug.log`

### Patterns & conventions
- Controllers return views or JSON for API-style endpoints under Admin
- Models encapsulate DB access; validation via CI4 Validation config
- Flash/session usage for auth and feedback
- Server-rendered views with progressive enhancement via vanilla JS
- **CRITICAL**: Always separate HTML, CSS, and JavaScript into their respective files and folders:
  - HTML structure goes in PHP view files (`app/Views/`)
  - CSS styles go in dedicated CSS files (`public/css/`)
  - JavaScript functions go in dedicated JS files (`public/js/`)
  - Never mix HTML, CSS, and JS in the same file
  - This promotes consistency, maintainability, and proper MVC architecture

### Security & middleware
- Filters configured in `app/Config/Filters.php` for auth/CSRF as applicable
- Content Security Policy in `app/Config/ContentSecurityPolicy.php`

### Error handling & logging
- CI4 logger per `app/Config/Logger.php`; logs in `writable/logs/*`
- Debugbar artifacts under `writable/debugbar/`

## Admin APIs (Announcements & Events)

- Announcements
  - GET `admin/announcements/api` → list announcements
  - POST `admin/announcements/api` → create announcement
  - PUT `admin/announcements/api/(:num)` → update announcement by id
  - DELETE `admin/announcements/api/(:num)` → delete announcement by id

- Events
  - GET `admin/events/api` → list events
  - POST `admin/events/api` → create event
  - PUT `admin/events/api/(:num)` → update event by id
  - DELETE `admin/events/api/(:num)` → delete event by id

- Counselor Info
  - GET `admin/counselor-info` → view counselor information page
  - GET `admin/counselor-info/schedule` → get counselor schedule by counselor_id (JSON - returns counselor's availability schedule)

- Users Management (Enhanced)
  - GET `admin/users/api` → `Admin\UsersApi::getAllUsers` (JSON - returns comprehensive student data)
  - GET `admin/users/pds/(:num)` → `Admin\UsersApi::getStudentPDSData` (JSON - returns individual student PDS data by user ID)

- Admin Management (Enhanced)
  - GET `admin/admins-management` → `Admin\AdminsManagement::index` (view - counselor schedule management page)
  - GET `admin/admins-management/schedules` → `Admin\AdminsManagement::getCounselorSchedules` (JSON - returns counselor schedules organized by day)
    - **Response Structure**: Returns counselor availability data organized by day (Monday-Friday)
    - **Data Processing**: Fetches from `counselor_availability` table and groups by available days
    - **Counselor Information**: Includes counselor_id, name, degree, time_slots for each available day
    - **Error Handling**: Comprehensive error handling with proper authentication checks
    - **Type Safety**: Proper data validation and null checks for robust API responses
  - GET `admin/admins-management/counselors-by-time` → `Admin\AdminsManagement::getCounselorsByTimeSlot` (JSON - returns counselors available at specific time/day)
    - **Parameters**: `day` (Monday-Friday) and `time` (HH:MM format) query parameters
    - **Time Matching**: Handles various time slot formats (single time, ranges, comma-separated)
    - **Response Structure**: Returns array of available counselors with detailed information
    - **Counselor Details**: Includes counselor_id, name, degree, email, contact_number, time_slots
    - **Validation**: Strict parameter validation with proper error messages
    - **Type Safety**: Comprehensive error handling and data validation for time-specific queries
    - **Enhanced Response Structure**: Includes complete student information from all related models
    - **Student Data Sections**:
      - `academic_info`: Course, year level, academic status from `StudentAcademicInfoModel`
      - `personal_info`: Name, birth date, age, sex, civil status, contact, Facebook from `StudentPersonalInfoModel`
      - `address_info`: Permanent and present address details from `StudentAddressInfoModel`
      - `family_info`: Parents' names and occupations, spouse, guardian contact from `StudentFamilyInfoModel`
      - `residence_info`: Residence type and consent information from `StudentResidenceInfoModel`
      - `special_circumstances`: Solo parent, indigenous, breastfeeding, PWD status and proof from `StudentSpecialCircumstancesModel`
      - `services_needed`: Array of services requested from `StudentServicesNeededModel`
      - `services_availed`: Array of services used from `StudentServicesAvailedModel`
    - **Error Handling**: Robust error handling with fallback empty structures for missing data
    - **Type Safety**: Comprehensive exception handling and data validation



### System Flowchart

```mermaid
flowchart TD
    %% Actors
    A[Student/User]:::actor
    B[Counselor]:::actor
    C[Admin]:::actor

    %% Entry & Routing
    subgraph Web[Web Layer]
        W1[public/index.php] --> W2[app/Config/Routes.php]
    end

    %% Controllers (selected)
    subgraph Cntr[Controllers]
        direction LR
        CA[Auth]:::ctrl
        CL[Logout]:::ctrl
        CFP[ForgotPassword]:::ctrl
        CUP[UpdatePassword]:::ctrl
        CPH[Photo]:::ctrl
        CSV[Services]:::ctrl

        subgraph AdminCtrls[Admin Namespace]
            direction LR
            AD[Dashboard]:::ctrl
            AA[Announcements / AnnouncementsApi]:::ctrl
            AE[EventsApi]:::ctrl
            AP[Appointments]:::ctrl
            AG[GetAllAppointments]:::ctrl
            AM[AdminsManagement / UsersApi]:::ctrl
            ACN[CounselorsApi]:::ctrl
            ACI[CounselorInfo]:::ctrl
            AHR[HistoryReports]:::ctrl
            AMS[Message]:::ctrl
            ASC[SessionCheck]:::ctrl
            APR[AdminProfileApi]:::ctrl
        end

        subgraph UserCtrls[User Namespace]
            direction LR
            UD[Dashboard]:::ctrl
            UAN[Announcements]:::ctrl
            UEV[Events]:::ctrl
            UAP[Appointment]:::ctrl
            UMS[Message]:::ctrl
            UPR[Profile]:::ctrl
            UNF[Notifications]:::ctrl
        end

        subgraph CounselorCtrls[Counselor Namespace]
            direction LR
            CD[Dashboard]:::ctrl
            CPR[Profile]:::ctrl
        end
    end

    %% Models
    subgraph Mdl[Models]
        UM[UserModel]:::model
        ANM[AnnouncementModel]:::model
        NTM[NotificationsModel]:::model
        %% (Other appointment/event models inferred in controllers)
    end

    %% Views
    subgraph Vw[Views]
        direction LR
        VAD[admin/dashboard.php]
        VAA[admin/announcements.php]
        VAP[admin/appointments.php]
        VAS[admin/scheduled_appointments.php]
        VVU[admin/view_users.php]
        VAH[admin/history_reports.php]
        VAC[admin/counselor_info.php]
        VAM[admin/messages.php]
        VADM[admin/admins_management.php]
        VAAS[admin/account_settings.php]
        VAAp[admin/view_all_appointments.php]

        VUD[user/dashboard.php]
        VUA[user/user_announcements.php]
        VUM[user/my_appointments.php]
        VUS[user/user_schedule_appointment.php]
        VUP[user/user_profile.php]

        VCD[counselor/dashboard.php]
        VCP[counselor/counselor_profile.php]
    end

    %% Data & Infra
    subgraph Data[Data & Infra]
        DB[(MySQL/MariaDB)]:::db
        SES[writable/session/*]:::infra
        LOG[writable/logs/*]:::infra
        PHO[public/Photos/*]:::infra
        CFG[app/Config/*]:::infra
    end

    %% Assets
    subgraph Assets
        JS[public/js/**]
        CSS[public/css/**]
    end

    %% Flows
    A -->|Login/Signup| W1 --> W2 --> CA
    CA -->|authenticate| UM --> DB
    CA -->|redirect| UD
    A -->|Frontend Signup Request| JS --> CA
    CA -->|validate & create user| UM --> DB
    CA -->|redirect| UD
    A -->|Password Reset| CFP
    CFP --> UM --> DB
    A -->|Update Password| CUP --> UM --> DB
    A -->|View Photo| CPH --> PHO
    A -->|View Services| W1 --> W2 --> CSV
    A -->|Logout| CPH

    %% Responsive Navigation Flow
    A[User on small screen] -->|Clicks Navbar Toggler| JS[public/js/landing.js] --> ND[Navbar Drawer (HTML)]
    ND -->|Displays Navigation Links|
    ND -->|Overlay covers content|
    JS -->|Handles Drawer Open/Close|
    JS -->|Manages Modal Opening from Drawer|
    ND -->|User interacts with navigation/modals|
    A -->|Open Contact Modal| JS
    A -->|Dynamic Text Styling| CSS[public/css/landing.css] & CSS[public/css/services.css]

    %% User area
    A -->|Open Dashboard| UD --> VUD
    A -->|View Announcements| UAN --> ANM --> DB
    UAN --> VUA
    A -->|View Events| UEV --> DB
    A -->|Schedule Appointment| UAP --> VUS
    UAP -->|get counselors| DB
    UAP -->|save appointment| DB
    A -->|My Appointments| UAP --> VUM --> DB
    A -->|Profile Get/Update| UPR --> UM --> DB
    A -->|Notifications| UNF --> NTM --> DB

    %% Counselor area
    B -->|Open Dashboard| CD --> VCD
    B -->|Profile Get/Update| CPR --> UM --> DB
    CPR --> VCP

    %% Admin area
    C -->|Dashboard| AD --> VAD --> DB
    C -->|Manage Appointments| AP --> VAP --> DB
    AP --> VAS
    C -->|View All Appointments| AG --> VAAp --> DB
    C -->|Announcements CRUD| AA --> ANM --> DB
    C -->|Events CRUD| AE --> DB
    C -->|Users & Admins| AM --> UM --> DB
    C -->|Counselors| ACN --> DB
    C -->|Counselor Info| ACI --> VAC --> ANM --> DB
    C -->|History Reports| AHR --> VAH --> DB
    C -->|Messages Ops| AMS --> LOG
    C -->|Session Check| ASC --> SES
    C -->|Profile Update| APR --> UM --> DB

    %% Rendering
    AdminCtrls --> Vw
    UserCtrls --> Vw
    CounselorCtrls --> Vw
    JS -. progressive enhancement .-> Vw
    CSS -. styles .-> Vw

    %% Config / Infra links
    Cntr -. uses .-> CFG
    Cntr -. logs .-> LOG
    Cntr -. sessions .-> SES

    %% Styles
    classDef actor fill:#eef,stroke:#446;
    classDef ctrl fill:#efe,stroke:#484;
    classDef model fill:#ffe,stroke:#884;
    classDef db fill:#fef,stroke:#848;
    classDef infra fill:#eee,stroke:#777;
```

### Responsive Navigation Pattern (Header)
- On screens larger than 992px, the full navigation bar (`.navbar-collapse`) is displayed, and the custom toggle button (`.custom-navbar-toggler`) is hidden.
- On screens 991.98px and smaller, the full navigation bar is hidden, and the custom toggle button is displayed on the rightmost side of the header.
- The custom toggle button, when clicked, opens a side drawer (`.navbar-drawer`) containing the navigation links with vertically centered icons and text. An overlay (`.navbar-overlay`) is also activated to dim the background and prevent interaction with the main content.
- JavaScript functions (`toggleDrawer()`, `closeDrawer()`) in `public/js/landing.js` and `public/js/services.js` manage the opening and closing of this drawer and handle modal interactions from within the drawer.
- The "Back" button in `app/Views/services_page.php` has been moved outside of the collapsible navbar to ensure it is always visible, regardless of screen size.
- The heading hierarchy in `app/Views/services_page.php` has been revised to ensure semantic correctness, with all h1-h6 tags being lessened by one level and associated styles updated.
- The header of the services page has been adjusted to match the height of the landing page header through CSS modifications in `public/css/services.css`, ensuring a consistent visual experience.
- Team member names in the footer of `app/Views/landing.php` and `app/Views/services_page.php` are now clickable hyperlinks.
- All client-side fetch calls and redirects in `public/js/landing.js`, `public/js/user/user_dashboard.js`, and `public/js/user/user_profile.js` now utilize `window.BASE_URL` for dynamic path resolution, improving maintainability. The direct path to `admin_notifications.php` was also converted to a more appropriate `admin/notify` route.
- The client-side login redirection logic in `public/js/landing.js` and `public/js/user/user_dashboard.js` has been updated to prevent double `base_url()` concatenation by conditionally prepending `window.BASE_URL` only to relative paths.

### Services Page Responsive Design Pattern
- **Mobile-First Approach**: Implemented comprehensive responsive design with 5 breakpoints (1200px+, 992-1199px, 768-991px, 480-767px, <480px).
- **Grid Layouts**: 
  - Service cards: 3 columns (desktop) → 2 columns (tablet) → 1 column (mobile)
  - Support programs: 4 columns (desktop) → 3 columns (large tablet) → 2 columns (small tablet) → 1 column (mobile)
- **Typography Scaling**: Responsive font sizes for all headings and text elements that scale appropriately across screen sizes.
- **Content Adaptation**: Responsive padding, margins, and spacing adjustments for main content, cards, and CTA section.
- **Mobile Navigation**: Drawer functionality with JavaScript controls for opening/closing, overlay management, and keyboard navigation (ESC key).
- **Landscape Support**: Special responsive rules for mobile landscape orientation with 2-column layouts.
- **Code Structure**: Replaced inline styles with CSS classes for better maintainability and responsive control.

### Modal System Pattern
- **Landing Page Modals**: Uses Bootstrap 5 modals for login, signup, forgot password, contact, terms, and verification prompts.
- **User Dashboard Modals**: Consistent modal system implemented with separated concerns:
  - **HTML Structure**: `app/Views/modals/user_dashboard_modals.php` contains modal HTML components:
    - `confirmationModal`: For user confirmations (e.g., logout) with Cancel/Confirm buttons
    - `alertModal`: For error/success/warning messages with appropriate icons and styling
    - `noticeModal`: For informational notices with bell icon
  - **JavaScript Functions**: `public/js/modals/user_dashboard_modals.js` contains modal logic:
    - `openConfirmationModal(message, onConfirm)`: Shows confirmation dialog with callback
    - `openAlertModal(message, type)`: Shows alert with type-specific styling (success/error/warning/info)
    - `openNoticeModal(message, type)`: Shows notice with type-specific styling
- **Error Handling**: All `window.alert()` and `window.confirm()` calls replaced with modal functions for consistent UX
- **Styling**: Modals use Bootstrap 5 classes with custom icons (Font Awesome) and color-coded styling based on message type
- **Code Structure**: JavaScript functions separated from PHP modal file for consistent MVC architecture

### Counselor Dashboard Logout Pattern
- `app/Views/counselor/dashboard.php` includes shared modal markup: `confirmationModal`, `alertModal`, `noticeModal`
- Script order: Bootstrap bundle → `public/js/modals/student_dashboard_modals.js` → `public/js/counselor/counselor_dashboard.js`
- `public/js/counselor/counselor_dashboard.js` `handleLogout()` uses `openConfirmationModal` to confirm and then calls `auth/logout`

### Student Calendar System Pattern
Inline mini-calendar embedded on the page (drawer removed).

- Layout: Two-column top row (Announcements | Calendar), full-width "Upcoming Events" list below
- Navigation: Previous/Next month buttons with month/year title
- Calendar Grid: 7 columns, smaller cells; each day shows bold date number plus overlaid first event title
- Badges: Top-right numeric badge shows count of events for that day
- Visibility: Title overlay uses accessible contrast (blue text) that doesn’t overpower bold date
- Today: Outlined highlight ring; hover scales cell subtly
- Date Details Modal: Shows events then announcements for selected date (unchanged)
- Tooltips: Hover shows total count breakdown
- Data: `student/announcements/all` and `student/events/all`

### Counselor Calendar System Pattern
Inline mini-calendar embedded on the page (drawer removed).

- Layout: Two-column top row (Announcements | Calendar), full-width "Upcoming Events" list below
- Calendar Grid: 7 columns, smaller cells with bold dates and overlaid first event title
- Badges: Top-right numeric badge shows count of events for that day
- Tooltips: Rich tooltip lists items; click opens modal with full details
- Data: `counselor/announcements/all` and `counselor/events/all`

### Student Approved Appointment Ticket Pattern
- **Approved Appointment Section**: Green-themed section above pending appointments
  - **Section Header**: Green title with check-circle icon and "Approved Appointment" text
  - **Empty State**: Shows "No Approved Appointments" message when none exist
  - **Ticket Display**: Shows most recent approved appointment in formal ticket format
- **Formal Ticket Design**: Professional ticket layout with multiple sections
  - **Ticket Header**: Logo + Title "Appointment Ticket" with "Approved" status badge
    - **Counselign Logo**: 40px logo positioned in top-left of header
    - **Responsive Logo**: Scales down to 35px (tablet) and 30px (mobile)
    - **Title Container**: Flexbox layout with logo and title alignment
  - **Details Grid**: 4-column responsive grid showing appointment details:
    - Date with calendar icon
    - Time with clock icon  
    - Counselor with user-md icon
    - Consultation type with comments icon
  - **Ticket Footer**: Functional QR code and download button
- **QR Code Implementation**: Functional QR codes using QRCode.js library
  - **QR Code Library**: CDN-loaded QRCode.js for canvas-based QR generation
  - **QR Code Container**: Responsive sizing (80px → 70px → 60px → 50px)
  - **QR Code Data**: JSON containing appointment details and unique ticket ID
  - **QR Code Styling**: Green-themed colors matching ticket design
  - **Error Handling**: Fallback display for library loading failures
  - **Print Integration**: QR codes generated in print window for downloadable tickets
- **Download Functionality**: Enhanced print-ready ticket generation
  - **Download Button**: Green gradient button with download icon
  - **Print Window**: Opens new window with formatted ticket including logo and QR code
  - **Print Styles**: Optimized CSS for print media with logo container styling
  - **QR Code Generation**: Separate QR code generation in print window
  - **Ticket Content**: Includes logo, all appointment details, and scannable QR code
- **Responsive Design**: Comprehensive mobile-friendly ticket layout
  - **Breakpoint System**: 992px, 768px, 576px responsive breakpoints
  - **Tablet Layout**: Single column grid, centered elements, stacked footer
  - **Mobile Layout**: Vertical detail items, centered icons, full-width button
  - **Small Mobile**: Compact spacing, reduced font sizes, optimized touch targets
  - **Typography**: Responsive font scaling across all screen sizes
- **JavaScript Architecture**:
  - **Display Function**: `displayApprovedAppointments()` renders approved appointments
  - **Ticket Generation**: `generateAppointmentTicket()` creates formal ticket HTML with logo
  - **QR Code Generation**: `generateQRCode()` handles QR code creation with error handling
  - **Download Handler**: `downloadAppointmentTicket()` manages print functionality with QR codes
  - **Print Content**: `generateTicketPrintContent()` formats ticket for printing with logo
  - **Library Integration**: QRCode.js integration with proper error handling and fallbacks

### Authentication Flow

```

```

### Routes & Controllers (Auth-related)

- `/` → `Auth::index` (landing)
- `POST /auth/login` → `Auth::login`
- `POST /auth/signup` → `Auth::signup`
- `POST /auth/verify-admin` → `Auth::verifyAdmin`
- `GET /counselor/dashboard` → `Counselor\\Dashboard::index`
- `GET /counselor/profile` → `Counselor\\Profile::profile`
- `GET /counselor/profile/get` → `Counselor\\Profile::getProfile`
- `POST /counselor/profile/update` → `Counselor\\Profile::updateProfile`
- `POST /counselor/profile/picture` → `Counselor\\Profile::updateProfilePicture`
- `GET /student/profile` → `Student\\Profile::profile`
- `GET /student/profile/get` → `Student\\Profile::getProfile`
- `POST /student/profile/update` → `Student\\Profile::updateProfile`
- `POST /student/profile/picture` → `Student\\Profile::updateProfilePicture`
- `GET /student/pds/load` → `Student\\PDS::loadPDS`
- `POST /student/pds/save` → `Student\\PDS::savePDS`
- `GET /student/get-counselors` → `Student\\Appointment::getCounselors` (returns `counselor_id`, `name`, optional `specialization`, and optional `profile_picture` from `users` join when column exists)
- `GET /student/get-counselors-by-availability` → `Student\\Appointment::getCounselorsByAvailability`
- `GET /student/get-counselor-schedules` → `Student\\Appointment::getCounselorSchedules` (returns counselor schedules organized by day with time slots)
- `GET /student/check-appointment-eligibility` → `Student\\Appointment::checkAppointmentEligibility` (returns JSON: `{ status, hasPending, hasApproved, hasPendingFollowUp, allowed }`)
- `GET /student/check-counselor-conflicts` → `Student\\Appointment::checkCounselorConflicts` (returns JSON: `{ status, hasConflict, conflictType, message }`)
- `GET /student/check-edit-conflicts` → `Student\\Appointment::checkEditConflicts` (returns JSON: `{ status, hasConflict, conflictType, message }`)
- `POST /forgot-password/send-code` → `ForgotPassword::sendCode`
- `POST /forgot-password/set-password` → `ForgotPassword::setPassword`
- `POST /contact/send` → `EmailController::sendContactEmail`

### Messaging System Routes

- `GET /student/message/operations?action=get_messages&user_id={counselor_id}` → `Student\\Message::operations` (get conversation with specific counselor)
- `POST /student/message/operations` → `Student\\Message::operations` (send message to counselor)
- `GET /counselor/message/operations?action=get_students` → `Counselor\\Message::operations` (get list of students for counselor)
- `GET /counselor/message/operations?action=get_messages&user_id={student_id}` → `Counselor\\Message::operations` (get conversation with specific student)
- `GET /counselor/message/operations?action=get_conversations` → `Counselor\\Message::operations` (get all conversations for messages page with proper sender/receiver indication)
- `GET /counselor/message/operations?action=get_dashboard_messages&limit=2` → `Counselor\\Message::operations` (get latest 2 messages sent TO counselor for dashboard)
- `POST /counselor/message/operations` → `Counselor\\Message::operations` (send message to student)

### Follow-up Appointments System Routes

- `GET /counselor/follow-up` → `Counselor\\FollowUp::index` (main follow-up appointments page)
- `GET /counselor/follow-up/completed-appointments` → `Counselor\\FollowUp::getCompletedAppointments` (get completed appointments for logged-in counselor)
- `GET /counselor/follow-up/sessions?parent_appointment_id={id}` → `Counselor\\FollowUp::getFollowUpSessions` (get follow-up sessions for specific parent appointment)
- `GET /counselor/follow-up/availability?date={date}` → `Counselor\\FollowUp::getCounselorAvailability` (get counselor availability for specific date)
- `POST /counselor/follow-up/create` → `Counselor\\FollowUp::createFollowUp` (create new follow-up appointment)

### Validation & Filters (Auth-related)

- Server-side Validation (CI4):
  - Signup:
    - `userId`: required, regex_match[/^\\d{10}$/] (exactly 10 digits)
    - `email`: required, valid_email, is_unique[users.email]
    - `password`: required, min_length[8], complexity regex, matches[confirmPassword]
  - Login:
    - `user_id`: required, regex_match[/^\\d{10}$/] (exactly 10 digits)
    - `password`: required, min_length[8], complexity regex
    - Redirects based on role: user → `user/dashboard`, counselor → `counselor/dashboard`, admin → `user/dashboard`
  - Admin Verification:
    - `verify-admin`: requires `user_id`, `password`; verifies admin role and hashed password; sets session and redirects to `admin/dashboard` on success; if unverified, returns prompt redirect
  - Forgot Password:
    - `sendCode`: `input` required (valid email format OR exactly 10-digit `user_id` resolving to email)
    - `setPassword`: password required, min_length[8], complexity regex
  - Contact Form:
    - name, email (valid), subject, message are required

### Profile Email Validation Pattern

**Enhanced Email Validation Across All Profiles:**
- **Consistent Error Messages**: All profile update endpoints now return the same specific error message for duplicate emails: "This email is already registered to another account"
- **Performance Optimization**: Duplicate email checking only occurs when the email is actually being changed, avoiding unnecessary database queries
- **Type Safety**: Clear, descriptive variable names and proper error handling without ambiguous code patterns

**Implementation Details:**
- **Counselor Profile**: `app/Controllers/Counselor/Profile.php` - `updateProfile()` method
- **Student Profile**: `app/Controllers/Student/Profile.php` - `updateProfile()` method  
- **Admin Profile**: `app/Controllers/Admin/AdminProfileApi.php` - `updateProfile()` method

**Validation Flow:**
1. Validate email format using `filter_var($email, FILTER_VALIDATE_EMAIL)`
2. Check if email is being changed by comparing with current user email
3. If email is changing, query database for existing email with different user_id
4. Return specific error message if duplicate found
5. Proceed with update using `skipValidation(true)` to avoid model-level unique checks
