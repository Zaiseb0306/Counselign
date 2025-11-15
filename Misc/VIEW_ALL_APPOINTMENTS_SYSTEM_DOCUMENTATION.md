# View All Appointments System - Complete Documentation

## Overview

The View All Appointments System provides counselors with comprehensive appointment management, data visualization through charts, and full export capabilities (PDF and Excel). This system combines appointment listing, statistical analysis, and reporting in a single interface.

---

## System Architecture

### Frontend Components
- **View**: `app/Views/counselor/view_all_appointments.php` - HTML structure with tabs, charts, and tables
- **JavaScript**: `public/js/counselor/view_all_appointments.js` - Client-side logic, chart rendering, and export functionality
- **CSS**: `public/css/counselor/view_all_appointments.css` - Styling

### Backend Components
- **Controller**: `app/Controllers/Counselor/GetAllAppointments.php` - API endpoint for appointment data
- **Filter Data Endpoint**: `app/Controllers/Counselor/FilterData.php` - Student and academic data for filters

---

## Complete User Journey

### 1. Page Load → Data Fetching & Initialization

**Frontend Flow:**
```
DOMContentLoaded Event
    ↓
initializeCharts() [Line 23]
updateReports() [Line 144]
fetchAppointments() [Line 687]
loadFilterData() [Line 831]
```

**Process:**
1. Page loads → Multiple `DOMContentLoaded` listeners fire
2. Charts initialized (Chart.js)
3. Reports fetched with default time range (weekly)
4. All appointments fetched for table display
5. Filter data loaded (students, academic map)

---

## Frontend JavaScript Functions (Detailed)

### Chart Initialization Functions

#### `initializeCharts()` [Line 23]
- **Purpose**: Initializes Chart.js instances for trend and pie charts
- **Charts Created**:
  1. **Trend Chart** (Line Chart):
     - 5 datasets: Completed, Approved, Rejected, Pending, Cancelled
     - Responsive with 400px height
     - Interactive tooltips
     - Y-axis starts at 0 with step size 1
     - X-axis labels rotated 45 degrees
  2. **Pie Chart** (Doughnut Chart):
     - 5 status categories
     - 65% cutout (doughnut style)
     - Percentage tooltips
     - Bottom legend

#### `updateReports()` [Line 144]
- **Purpose**: Fetches appointment statistics based on selected time range
- **Process**:
  1. Gets selected time range (daily/weekly/monthly)
  2. Shows loading state on stat cards
  3. Fetches: `GET /counselor/appointments/get_all_appointments?timeRange={range}`
  4. Calls `updateCharts()`, `updateStatistics()`, `updateCounselorName()`
  5. Saves to history via `saveToHistory()`
  6. Handles errors and resets stats on failure

#### `updateCharts(data)` [Line 176]
- **Purpose**: Updates both trend and pie charts with new data
- **Time Range Handling**:
  - **Daily**: Labels formatted as "Mon, Jan 15" from `weekInfo.weekDays`
  - **Weekly**: Labels as date ranges "Jan 15 - Jan 21" from `weekRanges`
  - **Monthly**: Labels as month names (January-December)
- **Data Mapping**:
  - Uses `monthlyXXX` arrays for monthly view
  - Uses regular arrays (`completed`, `approved`, etc.) for daily/weekly
  - Updates chart title with time range and month/year context
- **Y-Axis Configuration**:
  - Monthly: max 100, step 20
  - Daily: max 8, step 2
  - Weekly: max 40, step 10
  - Default: step 1
- **Pie Chart Update**:
  - Uses `totalXXX` aggregate counts
  - Calculates percentages for tooltips

#### `updateStatistics(data)` [Line 374]
- **Purpose**: Updates stat cards with total counts
- **Updates**: `completedCount`, `approvedCount`, `rejectedCount`, `pendingCount`, `cancelledCount`
- **Data Source**: `totalCompleted`, `totalApproved`, etc. from API

#### `updateCounselorName(data)` [Line 382]
- **Purpose**: Displays logged-in counselor name in page header
- **Data Source**: `data.counselorName` from API

---

### Appointment Table Functions

#### `fetchAppointments()` [Line 687]
- **Purpose**: Fetches all appointments for table display
- **API Endpoint**: `GET /counselor/appointments/get_all_appointments`
- **Process**:
  1. Shows loading spinner
  2. Fetches appointments with cache control headers
  3. Parses JSON response
  4. Stores in `allAppointments` global variable
  5. Calls `updateInitialDisplay()` to populate all tabs
  6. Handles empty state
  7. Logs debugging info using SecureLogger

#### `updateInitialDisplay()` [Line 658]
- **Purpose**: Pre-populates all tab tables with filtered appointments
- **Process**:
  1. Displays all appointments in "All" tab
  2. Filters by status for each tab:
     - Approved (status = 'APPROVED')
     - Rejected (status = 'REJECTED')
     - Completed (status = 'COMPLETED')
     - Cancelled (status = 'CANCELLED')
     - Follow-up (record_kind = 'follow_up' AND status IN ['PENDING','COMPLETED','CANCELLED'])
  3. Calls `displayAppointments()` for each tab

#### `displayAppointments(appointments, targetTableId)` [Line 560]
- **Purpose**: Renders appointment rows in specified table
- **Parameters**:
  - `appointments`: Array of appointment objects
  - `targetTableId`: ID of target table body element
- **Process**:
  1. Clears table body
  2. Shows empty message if no appointments
  3. Determines if "Reason" column should be shown (all, rejected, cancelled tabs)
  4. Sorts appointments by date/time (oldest first)
  5. Renders each appointment as table row with:
     - User ID, Student Name, Date, Time
     - Method Type, Consultation Type, Session Type
     - Purpose, Status badge, Reason (conditional)

#### `filterAppointments()` [Line 745]
- **Purpose**: Filters displayed appointments based on search and date inputs
- **Process**:
  1. Gets search term (lowercase) and date filter value
  2. Filters `allAppointments` array:
     - Search: checks all appointment values for substring match
     - Date: filters by `appointed_date` starts with date value
  3. Gets active tab and applies status filter
  4. Calls `displayAppointments()` with filtered results

#### `handleTabChange(event)` [Line 610]
- **Purpose**: Handles tab switching and filters appointments accordingly
- **Process**:
  1. Gets target tab ID from event
  2. Maps tab ID to status filter and table ID
  3. Filters appointments based on tab:
     - All: no filter
     - Follow-up: `record_kind === 'follow_up'` AND status in ['PENDING','COMPLETED','CANCELLED']
     - Others: status matches tab (e.g., 'APPROVED')
  4. Calls `displayAppointments()` with filtered results

---

### Export Functions

#### `exportToPDF(filters = {})` [Line 992]
- **Purpose**: Generates PDF export with filtered appointment data
- **Libraries Used**: jsPDF, jsPDF-AutoTable
- **Process**:
  1. Validates jsPDF library availability
  2. Dynamically loads AutoTable plugin if needed
  3. Gets active tab and filters appointments accordingly
  4. Applies enhanced filters (date range, student, course, year level)
  5. Sorts appointments by date/time (oldest first)
  6. Loads logo image asynchronously
  7. Creates PDF document:
     - Adds logo (20x15mm at position 12,10)
     - Adds header text "Counselign: USTP Guidance Counseling Sanctuary"
     - Adds horizontal line
     - Centers report title
  8. Defines table:
     - Headers: User ID, Full Name, Date, Time, Method Type, Consultation Type, Session, Purpose, Counselor, Status
     - Data mapped from appointments
     - Column widths optimized for A4
  9. Generates table with AutoTable:
     - Start Y: 40mm
     - Margins: 12mm all sides
     - Font size: 7pt
     - Header: Blue background (#003366), white text
     - Alternating row colors
  10. Adds footer on each page:
      - Confidential document notice
      - Prepared by text
      - Generated date/time with PST timezone
      - Page numbers
      - Filter summary
  11. Saves file: `{report_title}_{date}.pdf`

#### `exportToExcel(filters = {})` [Line 1217]
- **Purpose**: Generates Excel export with filtered appointment data
- **Library Used**: SheetJS (XLSX)
- **Process**:
  1. Gets active tab and filters appointments
  2. Applies enhanced filters
  3. Sorts appointments by date/time
  4. Determines if "Reason for Status" column needed
  5. Builds Excel data array:
     - Row 1: Report title (merged across columns)
     - Row 2: Filter summary
     - Row 3: Empty row
     - Row 4: Headers
     - Subsequent rows: Appointment data
  6. Creates workbook and worksheet:
     - Sets column widths (optimized for content)
     - Merges title row
     - Applies styles:
       - Title: Bold, 14pt, centered
       - Headers: Bold, centered
       - Status column: Bold, centered
  7. Saves file: `{report_title}_{date}.xlsx`

#### `applyFilters()` [Line 864]
- **Purpose**: Validates and applies export filters before exporting
- **Process**:
  1. Gets filter values (dates, student, course, year level)
  2. Validates date range (start ≤ end)
  3. Ensures academic map is loaded if course/year filters used
  4. Hides export modal
  5. Calls appropriate export function (PDF or Excel) based on modal attribute

#### `applyEnhancedFilters(appointments, filters, reportTitle)` [Line 916]
- **Purpose**: Applies multiple filters to appointment array
- **Filters Applied**:
  1. **Date Range**: Filters by `appointed_date` between start and end
  2. **Student**: Filters by `student_id` or `user_id` matching filter
  3. **Course**: Uses `__studentAcademicMap` to filter by course
  4. **Year Level**: Uses `__studentAcademicMap` to filter by year level
- **Returns**: Object with filtered appointments and updated report title

#### `buildFilterSummary(filters)` [Line 970]
- **Purpose**: Creates human-readable filter summary for export footers
- **Includes**:
  - Active tab status (All, Approved, Rejected, etc.)
  - Date range (if specified)
  - Student name (if filtered)
  - Course (if filtered)
  - Year level (if filtered)
- **Format**: "Status: X | Start: Date | End: Date | Student: Name | Course: X | Year: Y"

---

### Filter Data Functions

#### `loadFilterData()` [Line 831]
- **Purpose**: Loads student list and academic map for export filters
- **Process**:
  1. Fetches students: `GET /counselor/filter-data/students`
     - Populates `exportStudentFilter` dropdown
  2. Fetches academic map: `GET /counselor/filter-data/student-academic-map`
     - Stores in `window.__studentAcademicMap` for course/year filtering

#### `clearDateRange()` [Line 818]
- **Purpose**: Clears date range inputs in export modal

#### `clearAllFilters()` [Line 823]
- **Purpose**: Clears all filter inputs (dates, student, course, year level)

---

### History Management Functions

#### `saveToHistory(reportData)` [Line 453]
- **Purpose**: Saves report to localStorage for history tracking
- **Process**:
  1. Gets existing history from localStorage
  2. Creates new report record with:
     - Unique ID (timestamp)
     - Date generated (ISO string)
     - Report type (time range)
     - Total appointments count
     - Full report data
  3. Adds to history (prepended)
  4. Limits to last 50 reports
  5. Saves back to localStorage

#### `viewHistory()` [Line 408]
- **Purpose**: Displays report history modal
- **Process**:
  1. Gets history from localStorage
  2. Clears table body
  3. Shows "No history" if empty
  4. Sorts by date (newest first)
  5. Populates table with:
     - Date generated
     - Report type
     - Total appointments
     - View/Delete buttons
  6. Shows Bootstrap modal

#### `viewReport(reportId)` [Line 478]
- **Purpose**: Restores a historical report view
- **Process**:
  1. Finds report in history by ID
  2. Calls `updateCharts()` and `updateStatistics()` with historical data
  3. Closes history modal

#### `deleteReport(reportId)` [Line 494]
- **Purpose**: Deletes a report from history
- **Process**:
  1. Confirms deletion with user
  2. Filters report from history array
  3. Saves updated history to localStorage
  4. Refreshes history view

---

### Utility Functions

#### `formatDate(dateString)` [Line 1420]
- **Purpose**: Formats date string to locale date format
- **Returns**: Formatted date (e.g., "1/15/2025")

#### `formatTime(timeString)` [Line 1424]
- **Purpose**: Formats time string to 12-hour format
- **Returns**: Formatted time (e.g., "2:30 PM")

#### `getStatusClass(status)` [Line 1431]
- **Purpose**: Returns CSS class for status badge
- **Mapping**:
  - APPROVED → 'approved'
  - REJECTED → 'rejected'
  - COMPLETED → 'completed'
  - CANCELLED → 'cancelled'
  - PENDING → 'pending'
  - Default → 'pending'

#### `formatReason(reason)` [Line 1477]
- **Purpose**: Formats reason text with line break after first colon
- **Example**: "Reason: This is a reason" → "Reason:<br>This is a reason"

#### `formatDateForTitle(dateString)` [Line 1485]
- **Purpose**: Formats date for export titles
- **Returns**: "Jan 15, 2025" format

#### `showLoading()` [Line 1449]
- **Purpose**: Shows loading spinner, hides table

#### `hideLoading()` [Line 1455]
- **Purpose**: Hides loading spinner, shows table

#### `showEmptyState()` [Line 1461]
- **Purpose**: Shows empty state message, hides table

#### `hideEmptyState()` [Line 1467]
- **Purpose**: Hides empty state message, shows table

#### `showError(message)` [Line 1472]
- **Purpose**: Displays error alert (placeholder for toast system)

#### `resetStatistics()` [Line 389]
- **Purpose**: Resets all stat cards to 0 and clears charts
- **Used**: Error handling when API fails

---

### Event Listeners Setup [Line 505-1493]

**Initialization:**
- Time range dropdown change → `updateReports()`
- Window resize → Chart resize
- Search input → `filterAppointments()`
- Date filter → `filterAppointments()`
- Tab changes → `handleTabChange()`
- Export buttons → Opens export modal
- Export modal actions → Filter management and export

---

## Backend PHP Controller (GetAllAppointments.php)

### `index()` Method [Line 11]

**Purpose**: Returns JSON with appointment data, statistics, and chart datasets

**Process Flow:**

1. **Authentication Check** [Line 41-55]
   - Validates logged in status
   - Validates counselor role
   - Gets counselor ID from session

2. **Get Counselor Name** [Line 58-60]
   - Fetches counselor record from database
   - Extracts name for display

3. **Get Time Range** [Line 62]
   - Defaults to 'weekly' if not provided
   - Options: 'daily', 'weekly', 'monthly'

4. **Query Base Appointments** [Line 68-88]
   ```sql
   SELECT appointments.*, student_name, counselor_name
   FROM appointments
   LEFT JOIN counselors ON counselor_preference = counselor_id
   LEFT JOIN student_personal_info ON student_id = user_id
   WHERE counselor_preference = {counselor_id} OR counselor_preference IS NULL
   ORDER BY preferred_date ASC, preferred_time ASC
   ```
   - Filters by logged-in counselor
   - Includes NULL counselor preference appointments

5. **Query Follow-up Sessions** [Line 91-110]
   ```sql
   SELECT follow-up sessions with mapped fields
   FROM follow_up_appointments
   WHERE counselor_id = {counselor_id}
   AND status IN ('pending','completed','cancelled')
   ```
   - Maps fields to match base appointment structure
   - Adds `appointment_type` = 'Follow-up Session'
   - Adds `record_kind` = 'follow_up'

6. **Normalize Appointments** [Line 113-117]
   - Adds `appointment_type` = 'First Session'
   - Adds `record_kind` = 'appointment'

7. **Merge Lists** [Line 120]
   - Combines base appointments and follow-ups
   - Single unified array for frontend

8. **Build Date Filter** [Line 140-162]
   - **Daily**: Current week (Monday to Sunday)
   - **Weekly**: Last 5 weeks (Monday to Sunday)
   - **Monthly**: Current year

9. **Query Chart Data** [Line 164-186]
   - Applies date filter to base appointments
   - Includes follow-up sessions in chart data
   - Normalizes follow-up status to uppercase

10. **Generate Statistics** [Line 188-276]
    - **Daily/Weekly**: Creates date buckets
      - Daily: One bucket per day
      - Weekly: One bucket per week (Monday start)
    - **Monthly**: Uses month numbers (1-12)
    - Counts appointments by status for each bucket
    - Calculates totals across all buckets
    - Calculates monthly aggregates

11. **Build Response** [Line 251-277]
    - `labels`: Array of date labels
    - `completed`, `approved`, etc.: Arrays of counts per label
    - `totalCompleted`, etc.: Aggregate totals
    - `monthlyCompleted`, etc.: Monthly arrays (12 elements)
    - `appointments`: Full appointment list for tables
    - `counselorName`: Counselor name for display
    - `weekInfo` / `weekRanges`: Date metadata for daily/weekly views

**Response Structure:**
```json
{
  "success": true,
  "appointments": [...],
  "labels": ["2025-01-15", "2025-01-16", ...],
  "completed": [2, 3, 1, ...],
  "approved": [5, 4, 6, ...],
  "rejected": [0, 1, 0, ...],
  "pending": [1, 2, 1, ...],
  "cancelled": [0, 0, 1, ...],
  "totalCompleted": 15,
  "totalApproved": 20,
  "totalRejected": 3,
  "totalPending": 8,
  "totalCancelled": 2,
  "monthlyCompleted": [2, 3, 1, ...],
  "monthlyApproved": [5, 4, 6, ...],
  "counselorName": "Dr. John Doe",
  "weekInfo": {...},
  "weekRanges": [...]
}
```

---

## Data Visualization Process

### Chart Rendering Flow

```
1. User selects time range (daily/weekly/monthly)
   ↓
2. updateReports() fetches data from API
   ↓
3. Backend processes appointments and calculates statistics
   ↓
4. Response includes:
   - labels: Date labels for X-axis
   - completed/approved/rejected/pending/cancelled: Count arrays
   - totalXXX: Aggregate counts for pie chart
   ↓
5. updateCharts() processes data:
   - Formats labels based on time range
   - Maps data to chart datasets
   - Configures Y-axis limits
   - Updates chart title
   ↓
6. Chart.js renders:
   - Trend chart: Line chart with 5 datasets
   - Pie chart: Doughnut chart with percentages
```

### Label Formatting

**Daily:**
- Input: `weekInfo.weekDays` array
- Format: "Mon, Jan 15"
- Title: "Appointment Trends - Daily Report (January 2025)"

**Weekly:**
- Input: `weekRanges` array
- Format: "Jan 15 - Jan 21"
- Title: "Appointment Trends - Weekly Report (January 2025)"

**Monthly:**
- Input: Month numbers (1-12)
- Format: "January", "February", etc.
- Title: "Appointment Trends - Monthly Report"

### Y-Axis Configuration

**Monthly:**
- Max: 100
- Step: 20
- Reason: Higher volume expected

**Daily:**
- Max: 8
- Step: 2
- Reason: Lower daily counts

**Weekly:**
- Max: 40
- Step: 10
- Reason: Weekly aggregation

---

## Export Functionality

### PDF Export Process

```
1. User clicks "Export PDF" button
   ↓
2. Export modal opens with filters
   ↓
3. User applies filters (optional):
   - Date range
   - Student
   - Course
   - Year level
   ↓
4. applyFilters() validates and calls exportToPDF()
   ↓
5. exportToPDF() process:
   a. Gets active tab (determines status filter)
   b. Filters appointments by tab status
   c. Applies enhanced filters
   d. Sorts by date/time
   e. Loads logo image
   f. Creates PDF document
   g. Adds header with logo
   h. Generates table with AutoTable
   i. Adds footer on each page
   j. Saves file
```

**PDF Features:**
- Logo at top-left (20x15mm)
- Header text: "Counselign: USTP Guidance Counseling Sanctuary"
- Centered report title
- Professional table with:
  - Blue header (#003366)
  - White header text
  - Alternating row colors
  - Optimized column widths
- Footer on each page:
  - Confidential notice
  - Prepared by text
  - Generated date/time with PST
  - Page numbers
  - Filter summary

### Excel Export Process

```
1. User clicks "Export Excel" button
   ↓
2. Export modal opens with filters
   ↓
3. User applies filters (optional)
   ↓
4. applyFilters() validates and calls exportToExcel()
   ↓
5. exportToExcel() process:
   a. Gets active tab (determines status filter)
   b. Filters appointments by tab status
   c. Applies enhanced filters
   d. Sorts by date/time
   e. Builds Excel data array:
      - Row 1: Title (merged)
      - Row 2: Filter summary
      - Row 3: Empty row
      - Row 4: Headers
      - Rows 5+: Data
   f. Creates workbook/worksheet
   g. Sets column widths
   h. Applies styles (bold, centered)
   i. Saves file
```

**Excel Features:**
- Merged title row
- Filter summary row
- Bold, centered headers
- Bold, centered status column
- Optimized column widths
- Conditional "Reason" column (rejected/cancelled/all tabs)

### Enhanced Filtering

**Date Range Filter:**
- Filters by `appointed_date` between start and end
- Handles partial ranges (start only or end only)

**Student Filter:**
- Direct match on `student_id` or `user_id`

**Course Filter:**
- Uses `__studentAcademicMap` to match course
- Map structure: `{student_id: {course: 'BSIT', year_level: 'III'}}`

**Year Level Filter:**
- Uses `__studentAcademicMap` to match year level

**Combined Filters:**
- All filters applied sequentially (AND logic)
- Filters are cumulative

---

## Complete Data Flow Diagrams

### Page Load & Initialization

```
Browser loads page
    ↓
DOMContentLoaded fires
    ↓
initializeCharts() creates Chart.js instances
    ↓
updateReports() fetches statistics
    ↓
GET /counselor/appointments/get_all_appointments?timeRange=weekly
    ↓
Backend: GetAllAppointments::index()
    ↓
Queries appointments + follow-ups
Calculates statistics
Returns JSON
    ↓
Frontend: updateCharts(data)
Frontend: updateStatistics(data)
Frontend: updateCounselorName(data)
    ↓
Charts rendered, stats displayed
    ↓
fetchAppointments() fetches full list
    ↓
GET /counselor/appointments/get_all_appointments
    ↓
Backend returns appointments array
    ↓
Frontend: updateInitialDisplay()
    ↓
All tabs populated with filtered data
```

### Time Range Change

```
User selects new time range (daily/weekly/monthly)
    ↓
timeRange change event fires
    ↓
updateReports() called
    ↓
GET /counselor/appointments/get_all_appointments?timeRange={new}
    ↓
Backend processes with new date filter
    ↓
Returns new statistics
    ↓
updateCharts() formats labels based on range
    ↓
Charts update with new data
```

### Tab Switching

```
User clicks tab (All/Approved/Rejected/etc.)
    ↓
Tab change event fires
    ↓
handleTabChange(event) called
    ↓
Determines status filter from tab ID
    ↓
Filters allAppointments array
    ↓
displayAppointments(filtered, targetTableId)
    ↓
Table updated with filtered rows
```

### Search & Filter

```
User types in search box
    ↓
input event fires (debounced)
    ↓
filterAppointments() called
    ↓
Filters allAppointments:
  - Search: checks all fields
  - Date: filters by date
  - Tab: applies status filter
    ↓
displayAppointments(filtered, activeTableId)
    ↓
Table updated
```

### PDF Export

```
User clicks "Export PDF"
    ↓
Export modal opens
    ↓
User applies filters (optional)
    ↓
User clicks "Apply Filters & Export"
    ↓
applyFilters() validates
    ↓
exportToPDF(filters) called
    ↓
1. Gets active tab appointments
2. Applies enhanced filters
3. Sorts by date/time
4. Loads logo
5. Creates PDF with jsPDF
6. Generates table with AutoTable
7. Adds headers/footers
8. Saves file
```

### Excel Export

```
User clicks "Export Excel"
    ↓
Export modal opens
    ↓
User applies filters (optional)
    ↓
User clicks "Apply Filters & Export"
    ↓
applyFilters() validates
    ↓
exportToExcel(filters) called
    ↓
1. Gets active tab appointments
2. Applies enhanced filters
3. Sorts by date/time
4. Builds Excel data array
5. Creates workbook with XLSX
6. Applies styles
6. Saves file
```

---

## Key Features

### 1. Dual Data Sources
- **Base Appointments**: Regular appointments from `appointments` table
- **Follow-up Sessions**: From `follow_up_appointments` table
- Unified display with `record_kind` field to distinguish

### 2. Multi-Time Range Support
- **Daily**: Current week view
- **Weekly**: Last 5 weeks view
- **Monthly**: Yearly view with monthly buckets

### 3. Interactive Charts
- **Trend Chart**: Line chart showing status trends over time
- **Pie Chart**: Doughnut chart showing status distribution
- **Responsive**: Charts resize on window resize

### 4. Tabbed Interface
- **All**: All appointments
- **Follow-up**: Follow-up sessions only
- **Approved**: Approved appointments
- **Rejected**: Rejected appointments
- **Completed**: Completed appointments
- **Cancelled**: Cancelled appointments

### 5. Advanced Filtering
- **Search**: Text search across all fields
- **Date**: Month/year filter
- **Export Filters**: Date range, student, course, year level

### 6. Export Capabilities
- **PDF**: Professional reports with logo, headers, footers
- **Excel**: Structured spreadsheets with styles
- **Filtered**: Exports respect active tab and filters

### 7. History Management
- Saves reports to localStorage
- View historical reports
- Delete old reports
- Limited to 50 reports

### 8. Real-time Updates
- Charts update on time range change
- Tables update on search/filter
- Statistics update automatically

---

## Database Schema Integration

### Appointments Table
- `student_id`: Links to users
- `counselor_preference`: Links to counselors
- `preferred_date`: Date for filtering
- `preferred_time`: Time display
- `method_type`: Consultation method
- `purpose`: Appointment purpose
- `status`: Appointment status
- `reason`: Status reason

### Follow-up Appointments Table
- `student_id`: Links to users
- `counselor_id`: Links to counselors
- `parent_appointment_id`: Links to base appointment
- `preferred_date`: Date for filtering
- `preferred_time`: Time display
- `consultation_type`: Mapped to purpose
- `status`: Follow-up status
- `reason`: Status reason

### Student Personal Info Table
- `student_id`: Links to users
- `first_name`, `last_name`: Combined for display

### Counselors Table
- `counselor_id`: Links to users
- `name`: Counselor name for display

---

## Security Features

1. **Authentication**: All endpoints require logged-in counselor
2. **Authorization**: Counselors only see their own appointments
3. **CSRF Protection**: Forms include CSRF tokens
4. **Input Validation**: All inputs validated on backend
5. **SQL Injection**: Uses parameterized queries
6. **XSS Protection**: Output escaped in views

---

## Performance Optimizations

1. **Caching Headers**: No-cache headers prevent stale data
2. **Debounced Search**: 300ms debounce reduces API calls
3. **Lazy Loading**: Charts initialized once, data updated
4. **Client-side Filtering**: Table filtering done in-memory
5. **Pre-filtered Tabs**: All tabs populated on initial load
6. **LocalStorage History**: History stored client-side

---

## Error Handling

### Frontend
- Try-catch blocks around API calls
- Loading states during operations
- Empty state messages
- Error alerts for failures
- Statistics reset on error

### Backend
- Exception handling with try-catch
- Detailed error messages
- Logging for debugging
- Graceful failure responses

---

## API Endpoints Summary

| Method | Endpoint | Purpose | Parameters |
|--------|----------|---------|------------|
| GET | `/counselor/appointments/get_all_appointments` | Get all appointments | `timeRange` (optional) |
| GET | `/counselor/filter-data/students` | Get student list | None |
| GET | `/counselor/filter-data/student-academic-map` | Get academic map | None |

---

This documentation provides a complete overview of the View All Appointments System, covering all functions, data flows, visualization processes, and export functionalities.

