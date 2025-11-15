# Follow-Up Session System - Complete Documentation

## Overview

The Follow-Up Session System allows counselors to create, manage, and track follow-up appointments for students after an initial appointment has been completed. This system provides a complete CRUD (Create, Read, Update, Delete) interface with email notifications, conflict detection, and status management.

---

## System Architecture

### Frontend Components
- **View**: `app/Views/counselor/follow_up.php` - Main HTML structure with modals
- **JavaScript**: `public/js/counselor/follow_up.js` - Client-side logic and API interactions
- **CSS**: `public/css/counselor/follow_up.css` - Styling

### Backend Components
- **Controller**: `app/Controllers/Counselor/FollowUp.php` - API endpoints and business logic
- **Model**: `app/Models/FollowUpAppointmentModel.php` - Database operations
- **Services**: `app/Services/AppointmentEmailService.php` - Email notifications

---

## Complete User Journey

### 1. Page Load → Display Completed Appointments

**Frontend Flow:**
```
DOMContentLoaded Event
    ↓
loadCompletedAppointments() [Line 47]
    ↓
GET /counselor/follow-up/completed-appointments
    ↓
Backend: getCompletedAppointments() [Controller Line 30]
    ↓
displayCompletedAppointments() [Line 81]
```

**Process:**
1. Page loads → `DOMContentLoaded` event fires
2. `loadCompletedAppointments()` is called automatically
3. Fetches all completed appointments for the logged-in counselor
4. Backend queries database with follow-up counts and pending indicators
5. Results displayed as cards with appointment details

---

## Frontend JavaScript Functions (Detailed)

### Initialization Functions

#### `initStickyHeader()` [Line 838]
- **Purpose**: Makes header sticky on scroll
- **Features**: 
  - Adds shadow effect when scrolled
  - Manages modal backdrop cleanup
  - Prevents multiple backdrops when modals stack

#### `setupModalEventListeners()` [Line 538]
- **Purpose**: Sets up event handlers for all modal interactions
- **Key Handlers**:
  - Create new follow-up button click
  - Save follow-up button click
  - Update follow-up button click
  - Date change for availability loading

#### `initializeSearch()` [Line 890]
- **Purpose**: Implements search functionality with debouncing
- **Features**:
  - 300ms debounce to reduce API calls
  - Shows/hides clear button based on input
  - Calls `loadCompletedAppointments()` with search term

---

### Data Loading Functions

#### `loadCompletedAppointments(searchTerm = '')` [Line 47]
- **Purpose**: Fetches and displays completed appointments
- **API Endpoint**: `GET /counselor/follow-up/completed-appointments?search={term}`
- **Process**:
  1. Builds URL with optional search parameter
  2. Makes GET request with credentials
  3. Parses JSON response
  4. Calls `displayCompletedAppointments()` with results
  5. Handles errors with user-friendly messages

#### `displayCompletedAppointments(appointments, searchTerm)` [Line 81]
- **Purpose**: Renders appointment cards in the grid
- **Features**:
  - Shows/hides empty state messages
  - Displays follow-up count badges
  - Shows pending follow-up indicators
  - Renders appointment details (date, time, type, purpose, reason, description)
  - Creates "Follow-up Sessions" button for each appointment

#### `openFollowUpSessionsModal(parentAppointmentId, studentId)` [Line 166]
- **Purpose**: Opens modal showing all follow-up sessions for an appointment
- **Process**:
  1. Sets global variables: `currentParentAppointmentId`, `currentStudentId`
  2. Fetches follow-up sessions: `GET /counselor/follow-up/sessions?parent_appointment_id={id}`
  3. Updates modal header with student name
  4. Calls `displayFollowUpSessions()` to render sessions
  5. Shows Bootstrap modal

#### `displayFollowUpSessions(sessions)` [Line 220]
- **Purpose**: Renders follow-up session cards in the modal
- **Features**:
  - Sorts sessions: pending first, then by sequence number
  - Shows/hides "Create New Follow-up" button (disabled if pending exists)
  - Displays session details (sequence, status, date, time, type, description, reason)
  - Adds action buttons for pending sessions:
    - "Mark as Completed"
    - "Edit"
    - "Cancel"

#### `loadCounselorAvailability(date)` [Line 465]
- **Purpose**: Fetches available time slots for a specific date
- **API Endpoint**: `GET /counselor/follow-up/availability?date={date}`
- **Process**:
  1. Fetches counselor's availability for the day
  2. Fetches already-booked times for the date
  3. Calls `populateTimeOptions()` to filter available slots

#### `populateTimeOptions(timeSlots, bookedTimes)` [Line 510]
- **Purpose**: Populates time dropdown with available 30-minute slots
- **Features**:
  - Generates 30-minute increment ranges from availability slots
  - Filters out booked times
  - Uses `generateHalfHourRangeLabelsFromSlots()` helper

---

### Create Follow-Up Functions

#### `openCreateFollowUpModal()` [Line 437]
- **Purpose**: Opens modal for creating a new follow-up session
- **Process**:
  1. Sets parent appointment ID and student ID in hidden fields
  2. Resets form
  3. Sets minimum date to tomorrow
  4. Clears time options
  5. Loads availability for tomorrow
  6. Shows Bootstrap modal

#### `saveFollowUp()` [Line 586]
- **Purpose**: Submits form data to create a new follow-up session
- **API Endpoint**: `POST /counselor/follow-up/create`
- **Process**:
  1. Validates required fields
  2. Shows loading state on button
  3. Sends POST request with form data (FormData → URLSearchParams)
  4. Handles success:
     - Shows success message
     - Closes create modal
     - Refreshes completed appointments list
     - Opens follow-up sessions modal (with 300ms delay)
  5. Handles errors with specific messages

---

### Update Follow-Up Functions

#### `openEditFollowUpModal(sessionId)` [Line 929]
- **Purpose**: Opens modal for editing a pending follow-up session
- **Process**:
  1. Extracts session data from the displayed card
  2. Parses date, time, type, description, reason from DOM
  3. Sets form values
  4. Sets minimum date to today
  5. Populates time dropdown (read-only, shows current value)
  6. Shows Bootstrap modal

#### `updateFollowUp()` [Line 1075]
- **Purpose**: Submits form data to update a follow-up session
- **API Endpoint**: `POST /counselor/follow-up/edit`
- **Process**:
  1. Manually includes preferred_time (field is disabled)
  2. Validates required fields
  3. Shows loading state
  4. Sends POST request with form data
  5. Handles success:
     - Shows success message
     - Closes edit modal
     - Refreshes completed appointments list
     - Refreshes follow-up sessions modal

---

### Status Management Functions

#### `markFollowUpCompleted(id)` [Line 311]
- **Purpose**: Marks a pending follow-up session as completed
- **API Endpoint**: `POST /counselor/follow-up/complete`
- **Process**:
  1. Finds clicked button
  2. Shows loading state
  3. Sends POST request with session ID
  4. Handles success:
     - Shows success message
     - Refreshes follow-up sessions modal
     - Refreshes completed appointments list
  5. Hides loading state

#### `openCancelFollowUpModal(id)` [Line 365]
- **Purpose**: Opens modal for cancelling a follow-up session
- **Process**:
  1. Sets session ID in hidden field
  2. Clears reason textarea
  3. Shows Bootstrap modal

#### `confirmCancelFollowUp()` [Line 377]
- **Purpose**: Submits cancellation with reason
- **API Endpoint**: `POST /counselor/follow-up/cancel`
- **Process**:
  1. Validates reason is provided
  2. Shows loading state
  3. Sends POST request with ID and reason
  4. Handles success:
     - Shows success message
     - Closes cancel modal
     - Refreshes follow-up sessions modal
     - Refreshes completed appointments list

---

### Utility Functions

#### Time Formatting Functions

**`parseTime12ToMinutes(timeStr)` [Line 711]**
- Converts 12-hour format ("9:00 AM") to minutes since midnight
- Handles AM/PM conversion
- Returns null if invalid format

**`formatMinutesTo12h(totalMinutes)` [Line 723]**
- Converts minutes since midnight to 12-hour format
- Handles 12:00 AM/PM edge cases
- Returns formatted string (e.g., "9:00 AM")

**`generateHalfHourTimesFromSlots(timeSlots)` [Line 733]**
- Generates individual 30-minute time points from availability ranges
- Handles both range formats ("9:00 AM - 11:00 AM") and single times
- Returns sorted array of time strings

**`generateHalfHourRangeLabelsFromSlots(timeSlots)` [Line 770]**
- Generates 30-minute range labels (e.g., "10:00 AM - 10:30 AM")
- Used for dropdown options
- Returns sorted array of range strings

#### UI Helper Functions

**`formatDate(dateString)` [Line 701]**
- Formats date string to readable format (e.g., "January 15, 2025")
- Uses `toLocaleDateString()` with US locale

**`showButtonLoading(button, loadingText)` [Line 677]**
- Shows spinner and loading text on button
- Disables button during operation

**`hideButtonLoading(button, originalText)` [Line 682]**
- Restores button to original state
- Determines appropriate icon based on button type
- Re-enables button

**`showError(message)` [Line 809]**
- Displays error message in Bootstrap modal
- Falls back to alert if modal not found

**`showSuccess(message)` [Line 823]**
- Displays success message in Bootstrap modal
- Falls back to alert if modal not found

**`scrollToTop()` [Line 879]**
- Smoothly scrolls page to top
- Used after form submissions

---

## Backend PHP Controller Methods (Detailed)

### View Rendering

#### `index()` [Line 17]
- **Purpose**: Renders the main follow-up page
- **Process**:
  1. Checks authentication (logged in, role = counselor)
  2. Redirects to home if unauthorized
  3. Returns view: `counselor/follow_up`

---

### Data Retrieval Methods

#### `getCompletedAppointments()` [Line 30]
- **Purpose**: Returns JSON list of completed appointments with follow-up counts
- **Query Parameters**: `search` (optional)
- **Process**:
  1. Validates authentication
  2. Gets counselor ID from session
  3. Normalizes counselor ID (VARCHAR(10) constraint)
  4. Builds query with:
     - Joins: `users`, `student_personal_info`
     - Aggregates: `follow_up_count`, `pending_follow_up_count`, `next_pending_date`
     - Filters: `counselor_preference = counselor_id`, `status = 'completed'`
  5. Adds search functionality (searches across multiple fields)
  6. Orders by: pending count (DESC), next pending date (ASC), appointment date (DESC)
  7. Returns JSON response

**Search Fields:**
- student_id, username, email, first_name, last_name
- preferred_date, preferred_time, method_type
- purpose, reason

#### `getFollowUpSessions()` [Line 115]
- **Purpose**: Returns JSON list of follow-up sessions for a parent appointment
- **Query Parameters**: `parent_appointment_id` (required)
- **Process**:
  1. Validates authentication
  2. Gets parent appointment ID from query
  3. Calls `FollowUpAppointmentModel::getFollowUpChain()`
  4. Returns JSON response with sessions

#### `getCounselorAvailability(date)` [Line 148]
- **Purpose**: Returns available time slots for a specific date
- **Query Parameters**: `date` (required, format: YYYY-MM-DD)
- **Process**:
  1. Validates authentication
  2. Gets date from query
  3. Determines day of week from date
  4. Fetches counselor availability grouped by day
  5. Extracts time slots for the specific day
  6. Returns JSON response with day and time slots

#### `getBookedTimesForDate()` [Line 196]
- **Purpose**: Returns already-booked time ranges for a date
- **Query Parameters**: `date` (required)
- **Process**:
  1. Validates authentication and date format
  2. Gets counselor ID from session
  3. Queries `appointments` table for approved appointments on date
  4. Queries `follow_up_appointments` table for pending/approved follow-ups on date
  5. Merges and deduplicates time ranges
  6. Returns JSON response with booked times

---

### CRUD Operations

#### `createFollowUp()` [Line 258]
- **Purpose**: Creates a new follow-up appointment
- **HTTP Method**: POST
- **Form Data**:
  - `parent_appointment_id` (required)
  - `student_id` (required)
  - `preferred_date` (required)
  - `preferred_time` (required)
  - `consultation_type` (required)
  - `description` (optional)
  - `reason` (optional)

**Process**:
1. Validates authentication
2. Gets and normalizes counselor ID from session
3. Retrieves form data
4. Validates required fields
5. Checks for time slot conflicts using `hasCounselorFollowUpConflict()`
6. Gets next sequence number using `getNextSequence()`
7. Sets Manila timezone
8. Prepares data array with status = 'pending'
9. Inserts into database
10. Sends email notification (`sendFollowUpNotificationToStudent()`)
11. Updates activity logs (counselor and student)
12. Restores original timezone
13. Returns JSON response

**Conflict Detection:**
- Checks against other follow-up sessions and regular appointments
- Prevents double-booking on same date/time

#### `editFollowUp()` [Line 545]
- **Purpose**: Updates a pending follow-up session
- **HTTP Method**: POST
- **Form Data**:
  - `id` (required)
  - `preferred_date` (required)
  - `preferred_time` (required)
  - `consultation_type` (required)
  - `description` (optional)
  - `reason` (optional)

**Process**:
1. Validates authentication
2. Gets session ID and form data
3. Finds follow-up session in database
4. Validates ownership (counselor owns the session)
5. Validates status (only pending can be edited)
6. Validates required fields
7. Checks for conflicts (excluding current session)
8. Sets Manila timezone
9. Updates database
10. Sends email notification (action: 'edited')
11. Updates activity logs
12. Restores timezone
13. Returns JSON response

#### `completeFollowUp()` [Line 406]
- **Purpose**: Marks a follow-up session as completed
- **HTTP Method**: POST
- **Form Data**: `id` (required)

**Process**:
1. Validates authentication
2. Gets session ID
3. Finds session in database
4. Validates ownership
5. Checks if already completed (idempotent)
6. Sets Manila timezone
7. Updates status to 'completed'
8. Sends email notification (action: 'completed')
9. Updates activity logs
10. Restores timezone
11. Returns JSON response

#### `cancelFollowUp()` [Line 473]
- **Purpose**: Cancels a follow-up session with reason
- **HTTP Method**: POST
- **Form Data**:
  - `id` (required)
  - `reason` (required)

**Process**:
1. Validates authentication
2. Gets session ID and reason
3. Validates reason is provided
4. Finds session in database
5. Validates ownership
6. Prevents cancelling completed sessions
7. Sets Manila timezone
8. Updates status to 'cancelled' and saves reason
9. Sends email notification (action: 'cancelled')
10. Updates activity logs
11. Restores timezone
12. Returns JSON response

---

### Helper Methods

#### `sendFollowUpNotificationToStudent(followUpData, actionType)` [Line 691]
- **Purpose**: Sends email notification to student about follow-up action
- **Parameters**:
  - `followUpData`: Array with follow-up session data
  - `actionType`: 'created', 'edited', 'completed', or 'cancelled'

**Process**:
1. Queries database for counselor information (name, email)
2. Gets student email using `getStudentEmail()`
3. Instantiates `AppointmentEmailService`
4. Calls appropriate email method based on action type:
   - `sendFollowUpCreatedNotification()`
   - `sendFollowUpEditedNotification()`
   - `sendFollowUpCompletedNotification()`
   - `sendFollowUpCancelledNotification()`
5. Logs success/failure

#### `getStudentEmail(studentId)` [Line 747]
- **Purpose**: Retrieves student email from users table
- **Returns**: Email string or null

#### `setManilaTimezone()` [Line 668]
- **Purpose**: Sets timezone to Asia/Manila for database operations
- **Returns**: Original timezone string

#### `restoreTimezone(originalTimezone)` [Line 680]
- **Purpose**: Restores original timezone after operations

#### `getManilaDateTime()` [Line 647]
- **Purpose**: Returns current datetime in Manila timezone
- **Format**: 'Y-m-d H:i:s'

---

## Model Methods (FollowUpAppointmentModel)

### Query Methods

#### `getFollowUpChain(parentAppointmentId)` [Line 116]
- Returns all follow-up sessions for a parent appointment
- Ordered by `follow_up_sequence` ASC

#### `getNextSequence(parentAppointmentId)` [Line 173]
- Returns the next sequence number for a parent appointment
- Calculates: MAX(sequence) + 1

#### `hasPendingFollowUp(studentId)` [Line 219]
- Checks if student has any pending follow-up sessions

---

### Conflict Detection Methods

#### `hasCounselorFollowUpConflict(counselorId, date, time, excludeFollowUpId)` [Line 235]
- **Purpose**: Checks if time slot conflicts with existing follow-up sessions
- **Parameters**:
  - `counselorId`: Counselor ID
  - `date`: Date in YYYY-MM-DD format
  - `time`: Time range string (e.g., "10:00 AM - 10:30 AM")
  - `excludeFollowUpId`: Optional ID to exclude from check (for updates)

**Process**:
1. Queries follow-up sessions for counselor on date
2. Filters by status = 'pending'
3. Excludes specified ID if provided
4. Checks each session for time overlap using `timeRangesOverlap()`
5. Returns true if conflict found

#### `timeRangesOverlap(timeRange1, timeRange2)` [Line 298]
- **Purpose**: Determines if two time ranges overlap
- **Supports Formats**:
  - 12-hour: "9:00 AM - 10:00 AM"
  - 24-hour: "09:00-10:00"
- **Process**:
  1. Converts both ranges to minutes using `convertToMinutesRange()`
  2. Checks if ranges overlap (one starts before other ends)
  3. Falls back to exact string match if conversion fails

#### `convertToMinutesRange(timeRange)` [Line 319]
- **Purpose**: Converts time range string to minutes since midnight
- **Returns**: Array with 'start' and 'end' keys, or null if invalid

---

## Data Flow Diagrams

### Creating a Follow-Up Session

```
User clicks "Follow-up Sessions" button
    ↓
openFollowUpSessionsModal() called
    ↓
GET /counselor/follow-up/sessions?parent_appointment_id={id}
    ↓
Backend: getFollowUpSessions()
    ↓
displayFollowUpSessions() renders sessions
    ↓
User clicks "Create New Follow-up" button
    ↓
openCreateFollowUpModal() called
    ↓
Form displayed with tomorrow's date
    ↓
loadCounselorAvailability() fetches available slots
    ↓
populateTimeOptions() fills time dropdown
    ↓
User fills form and clicks "Create Follow-up"
    ↓
saveFollowUp() validates and submits
    ↓
POST /counselor/follow-up/create
    ↓
Backend: createFollowUp()
    ↓
1. Validates data
2. Checks conflicts
3. Gets next sequence
4. Inserts into database
5. Sends email notification
6. Updates activity logs
    ↓
Returns success response
    ↓
Frontend refreshes modals and lists
```

### Editing a Follow-Up Session

```
User clicks "Edit" button on pending session
    ↓
openEditFollowUpModal() extracts data from card
    ↓
Form populated with current values
    ↓
User modifies fields (date/time are read-only)
    ↓
User clicks "Update Follow-up"
    ↓
updateFollowUp() validates and submits
    ↓
POST /counselor/follow-up/edit
    ↓
Backend: editFollowUp()
    ↓
1. Validates ownership
2. Validates status (must be pending)
3. Checks conflicts (excluding current session)
4. Updates database
5. Sends email notification
6. Updates activity logs
    ↓
Returns success response
    ↓
Frontend refreshes modals and lists
```

### Completing a Follow-Up Session

```
User clicks "Mark as Completed" button
    ↓
markFollowUpCompleted() called with session ID
    ↓
POST /counselor/follow-up/complete
    ↓
Backend: completeFollowUp()
    ↓
1. Validates ownership
2. Updates status to 'completed'
3. Sends email notification
4. Updates activity logs
    ↓
Returns success response
    ↓
Frontend refreshes modals and lists
```

### Cancelling a Follow-Up Session

```
User clicks "Cancel" button
    ↓
openCancelFollowUpModal() opens modal
    ↓
User enters cancellation reason
    ↓
User clicks "Confirm Cancellation"
    ↓
confirmCancelFollowUp() validates reason
    ↓
POST /counselor/follow-up/cancel
    ↓
Backend: cancelFollowUp()
    ↓
1. Validates ownership
2. Validates reason provided
3. Prevents cancelling completed sessions
4. Updates status to 'cancelled' with reason
5. Sends email notification
6. Updates activity logs
    ↓
Returns success response
    ↓
Frontend refreshes modals and lists
```

---

## Key Features

### 1. Conflict Detection
- Prevents double-booking by checking time overlaps
- Considers both follow-up sessions and regular appointments
- Excludes current session when editing

### 2. Sequence Management
- Automatically assigns sequence numbers (1, 2, 3...)
- Maintains order within each parent appointment

### 3. Status Management
- **pending**: Newly created, can be edited/cancelled/completed
- **completed**: Cannot be edited or cancelled
- **cancelled**: Cannot be edited or completed

### 4. Email Notifications
- Sent to student for all actions (created, edited, completed, cancelled)
- Includes session details and counselor information
- Uses professional HTML templates

### 5. Activity Tracking
- Updates counselor and student activity logs
- Tracks all follow-up actions for audit trail

### 6. Timezone Handling
- All operations use Asia/Manila timezone
- Timezone is set before operations and restored after

### 7. Search Functionality
- Real-time search with 300ms debounce
- Searches across multiple fields
- Shows/hides clear button based on input

### 8. Responsive UI
- Modal-based interface
- Loading states on all buttons
- Success/error feedback via modals
- Empty state messages

---

## Security Features

1. **Authentication**: All endpoints require logged-in counselor
2. **Authorization**: Counselors can only manage their own follow-ups
3. **Validation**: All inputs validated on both client and server
4. **CSRF Protection**: Forms include CSRF tokens
5. **Session Management**: Uses CodeIgniter session handling

---

## Error Handling

### Frontend
- Try-catch blocks around all API calls
- User-friendly error messages via modals
- Fallback to alerts if modals unavailable
- Console logging for debugging

### Backend
- Comprehensive validation
- Detailed error messages
- Logging for all operations
- Graceful failure handling
- Timezone restoration in finally blocks

---

## Database Schema

### `follow_up_appointments` Table
- `id` (PK, auto-increment)
- `counselor_id` (VARCHAR(10), FK to counselors)
- `student_id` (VARCHAR(100), FK to users)
- `parent_appointment_id` (INT, FK to appointments)
- `preferred_date` (DATE)
- `preferred_time` (VARCHAR(50))
- `consultation_type` (VARCHAR(50))
- `follow_up_sequence` (INT)
- `description` (TEXT, nullable)
- `reason` (TEXT, nullable)
- `status` (ENUM: pending, approved, rejected, completed, cancelled)
- `created_at` (DATETIME)
- `updated_at` (DATETIME)

---

## API Endpoints Summary

| Method | Endpoint | Purpose | Parameters |
|--------|----------|---------|------------|
| GET | `/counselor/follow-up` | View page | None |
| GET | `/counselor/follow-up/completed-appointments` | Get completed appointments | `search` (optional) |
| GET | `/counselor/follow-up/sessions` | Get follow-up sessions | `parent_appointment_id` (required) |
| GET | `/counselor/follow-up/availability` | Get counselor availability | `date` (required) |
| GET | `/counselor/follow-up/booked-times` | Get booked times | `date` (required) |
| POST | `/counselor/follow-up/create` | Create follow-up | Form data (see createFollowUp) |
| POST | `/counselor/follow-up/edit` | Edit follow-up | Form data (see editFollowUp) |
| POST | `/counselor/follow-up/complete` | Complete follow-up | `id` (required) |
| POST | `/counselor/follow-up/cancel` | Cancel follow-up | `id`, `reason` (required) |

---

This documentation provides a complete overview of the Follow-Up Session System, covering all functions, data flows, and implementation details.

