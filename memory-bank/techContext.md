## Tech Context

### Stack
- PHP 8.x (via XAMPP on Windows)
- CodeIgniter 4
- Composer for dependencies
- MySQL/MariaDB

### Local development
- Path: `C:\xampp\htdocs\3RD\Counselign`
- Web root: `public/`
- CLI: `php spark` (when available)
- Dependencies: `composer install`

### Environment & config
- App config in `app/Config/*.php` (e.g., `App.php`, `Database.php`, `Routes.php`)
- Base URL, database DSN, email settings via environment or config files

### Email Service Configuration
**ENHANCED FEATURE**: Centralized email configuration with debugging capabilities for appointment notifications.

#### Email Service Architecture:
- **Centralized Configuration**: Both `AppointmentEmailService` and `EmailController` use `Config\Email` settings
- **PHPMailer Integration**: Uses PHPMailer library for reliable email delivery
- **Gmail SMTP Configuration**: All settings loaded from centralized config
  - Host: `$emailConfig->SMTPHost` (smtp.gmail.com)
  - Port: `$emailConfig->SMTPPort` (587)
  - Authentication: `$emailConfig->SMTPUser` and `$emailConfig->SMTPPass`
  - Encryption: `$emailConfig->SMTPCrypto` (tls)
  - Timeout: `$emailConfig->SMTPTimeout`
  - Keep Alive: `$emailConfig->SMTPKeepAlive`

#### Email Notification Features:
- **Automatic Counselor Notifications**: Emails sent to counselors when students book or edit appointments
- **Professional HTML Templates**: Responsive email design with system branding
- **Complete Appointment Details**: Includes all appointment and student information
- **Enhanced Error Handling**: Comprehensive logging and graceful failure handling
- **Non-Blocking Operations**: Email sending doesn't interfere with appointment operations
- **Debugging Tools**: Built-in email testing capabilities

#### Database Integration:
- **Counselor Email Lookup**: Uses `counselors.counselor_id = users.user_id` relationship
- **Student Information Retrieval**: Automatic student data fetching for email content
- **Type-Safe Implementation**: Proper error handling and data validation

#### Testing and Debugging:
- **Test Method**: `testEmailConfiguration()` in AppointmentEmailService
- **Test Endpoint**: `POST student/appointments/test-email`
- **Comprehensive Testing**: Config loading, SMTP connection, and email sending tests
- **Detailed Logging**: Step-by-step logging for debugging email issues

### CORS Configuration
**PROBLEM SOLVED**: CORS (Cross-Origin Resource Sharing) policy errors when accessing from different origins.

#### Configuration Files Updated:
- **`app/Config/Cors.php`**: Comprehensive CORS settings configured
  - `allowedOrigins`: localhost, 127.0.0.1 with various ports
  - `allowedOriginsPatterns`: Regex patterns for local network IPs (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
  - `supportsCredentials`: true (for session/auth support)
  - `allowedHeaders`: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-TOKEN, etc.
  - `allowedMethods`: GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD
  - `exposedHeaders`: X-CSRF-TOKEN, Content-Length, Content-Type

- **`app/Config/Filters.php`**: CORS filter enabled globally
  - Added 'cors' to globals.before array for all requests

- **`app/Config/App.php`**: Dynamic hostname support
  - Added `allowedHostnames` array with localhost, IP patterns
  - Created `getBaseURL()` method for dynamic base URL generation

#### Custom Helper Created:
- **`app/Helpers/url_helper.php`**: Dynamic URL generation functions
  - `dynamic_base_url()`: Auto-detects host and protocol
  - `dynamic_site_url()`: Site URL with dynamic base
  - `api_url()`: API endpoint URL generation
- **`app/Config/Autoload.php`**: Registered custom URL helper

#### Benefits:
- **Multi-Origin Support**: Works with localhost, 127.0.0.1, and any local network IP
- **Automatic Detection**: No manual base URL changes needed when switching between localhost/IP
- **Security**: Properly configured CORS headers with credential support
- **Flexibility**: Supports both HTTP and HTTPS protocols with various ports

### Build/test
- PHPUnit tests under `tests/`
- Run: `vendor\bin\phpunit.bat` or `vendor/bin/phpunit`

### Constraints
- Shared hosting-style deployment
- Windows file permissions and path separators

### Frontend Development Standards
**CRITICAL RULE**: Always separate HTML, CSS, and JavaScript into their respective files and folders:

#### File Organization:
- **HTML Structure**: `app/Views/` - PHP view files containing only HTML structure
- **CSS Styles**: `public/css/` - Dedicated CSS files for styling
- **JavaScript Logic**: `public/js/` - Dedicated JS files for functionality

#### Examples of Proper Structure:
```
app/Views/user/dashboard.php          → HTML structure only
public/css/user/user_dashboard.css    → CSS styles only  
public/js/user/user_dashboard.js      → JavaScript functions only
```

#### What NOT to do:
- ❌ Mix HTML, CSS, and JS in the same file
- ❌ Put JavaScript functions inside PHP view files
- ❌ Put CSS styles inline in HTML
- ❌ Put HTML structure inside JavaScript files

#### Benefits:
- **Consistency**: Uniform code organization across the project
- **Maintainability**: Easier to find and modify specific code
- **MVC Architecture**: Proper separation of concerns
- **Team Collaboration**: Clear file responsibilities
- **Debugging**: Easier to isolate and fix issues

### PDS (Personal Data Sheet) System

#### Architecture:
- **Controller**: `app/Controllers/Student/PDS.php` - Handles PDS data loading and saving
- **Models**: Uses existing student models for data persistence
- **Routes**: `student/pds/load` and `student/pds/save` endpoints
- **Frontend**: Enhanced `public/js/student/student_profile.js` with PDS functionality

#### Features:
- **Data Loading**: Automatically loads existing PDS data when page loads
- **Data Saving**: Saves complete PDS data with validation
- **Conditional Logic**: Spouse field shows only when Married, PWD fields show only when PWD is Yes
- **File Upload**: PWD proof file upload (PDF, images, videos, documents)
- **Services Handling**: Multiple selection for services needed and services availed
- **Validation**: Comprehensive server-side validation with proper error messages
- **Partial Updates**: Handles partial updates with N/A defaults for empty fields

#### Data Flow:
1. Page loads → `loadPDSData()` → `student/pds/load` → Populate form
2. User edits → Conditional logic updates UI
3. User saves → `student/pds/save` → Validate → Save to database
4. Success/Error feedback via modal system

### Enhanced Admin Users API

#### Architecture:
- **Controller**: `app/Controllers/Admin/UsersApi.php` - Enhanced to retrieve comprehensive student data
- **Models**: Uses all student-related models for complete data aggregation
- **Route**: `GET admin/users/api` → `Admin\UsersApi::getAllUsers`
- **Response**: JSON with complete student information from all related tables

#### Features:
- **Comprehensive Data Retrieval**: Aggregates data from all student models:
  - `StudentAcademicInfoModel`: Course, year level, academic status
  - `StudentPersonalInfoModel`: Personal information (name, birth date, contact, etc.)
  - `StudentAddressInfoModel`: Permanent and present address details
  - `StudentFamilyInfoModel`: Family information (parents, spouse, guardian)
  - `StudentResidenceInfoModel`: Residence type and consent information
  - `StudentSpecialCircumstancesModel`: Special circumstances (PWD, solo parent, etc.)
  - `StudentServicesNeededModel`: Services requested by student
  - `StudentServicesAvailedModel`: Services used by student
- **Error Handling**: Robust error handling with fallback empty structures
- **Type Safety**: Comprehensive exception handling and data validation
- **Performance**: Efficient data retrieval with proper model instantiation

#### Data Structure:
```json
{
  "success": true,
  "users": [
    {
      "user_id": "1234567890",
      "username": "student_username",
      "email": "student@email.com",
      "course_and_year": "BSIT-3",
      "created_at": "2024-01-01T00:00:00",
      "activity_status": "Currently Active",
      "student_data": {
        "academic_info": { "course": "BSIT", "year_level": "3", "academic_status": "Regular" },
        "personal_info": { "last_name": "Doe", "first_name": "John", ... },
        "address_info": { "permanent_zone": "Zone 1", ... },
        "family_info": { "father_name": "Father Name", ... },
        "residence_info": { "residence_type": "Dormitory", ... },
        "special_circumstances": { "is_pwd": false, ... },
        "services_needed": [{"type": "Academic", "other": null}],
        "services_availed": [{"type": "Career", "other": null}]
      }
    }
  ],
  "activeCount": 5
}
```

#### Implementation Details:
- **Service Method**: `getComprehensiveStudentData()` aggregates data from all models
- **Fallback Handling**: Returns empty structures for missing data to maintain API consistency
- **Logging**: Comprehensive error logging for debugging data retrieval issues
- **Session Management**: Maintains existing user activity tracking functionality
