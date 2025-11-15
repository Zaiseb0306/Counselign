<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');
$routes->setDefaultController('Auth');
$routes->post('auth/login', 'Auth::login');
$routes->match(['GET', 'POST'], 'auth/signup', 'Auth::signup');
$routes->post('auth/verify-admin', 'Auth::verifyAdmin');
$routes->get('auth/logout', 'Logout::index');
$routes->get('test/logout', 'TestActivity::testLogout');
$routes->get('test/login', 'TestActivity::testLogin');

$routes->post('update-password', 'UpdatePassword::index');

$routes->post('email/sendContactEmail', 'EmailController::sendContactEmail');

$routes->get('photo/(:segment)', 'Photo::show/$1');
$routes->get('photo/(:segment)/(:any)', 'Photo::show/$1/$2');

$routes->post('forgot-password/send-code', 'ForgotPassword::sendCode');
$routes->post('forgot-password/resend-code', 'ForgotPassword::resendCode');
$routes->post('forgot-password/verify-code', 'ForgotPassword::verifyCode');
$routes->post('forgot-password/set-password', 'ForgotPassword::setPassword');

// Counselor basic info route (for landing page signup)
$routes->post('counselor/save-basic-info', 'Counselor::saveBasicInfo');

// Account verification routes
$routes->get('verify-account/prompt', 'Auth::verificationPrompt');
$routes->match(['GET', 'POST'], 'verify-account/(:segment)', 'Auth::verifyAccount/$1');
$routes->post('verify-account', 'Auth::verifyAccount');
$routes->post('resend-verification-email', 'Auth::resendVerificationEmail');

$routes->get('/services', 'Services::index');

// Debug route (remove in production)
$routes->get('/debug/session', 'Debug::session');

// Admin routes
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('dashboard/data', 'Dashboard::getAdminData');
    $routes->get('appointments', 'Appointments::index');
    $routes->get('appointments/view-all', 'Appointments::viewAll');
    $routes->get('appointments/scheduled', 'Appointments::scheduled');
    $routes->get('appointments/scheduled/get', 'Appointments::getScheduledAppointments');
    $routes->get('appointments/latest', 'Appointments::getLatest');
    $routes->get('appointments/getAll', 'Appointments::getAppointments');
    $routes->get('appointments/getAppointments', 'Appointments::getAppointments');
    $routes->post('appointments/updateStatus', 'Appointments::updateStatus');
    $routes->post('appointments/updateAppointmentStatus', 'Appointments::updateAppointmentStatus');
    $routes->post('appointments/track-export', 'Appointments::trackExport');
    $routes->get('follow-up-sessions', 'FollowUpSessions::index');
    $routes->get('follow-up-sessions/completed-appointments', 'FollowUpSessions::getAllCompletedAppointments');
    $routes->get('follow-up-sessions/sessions', 'FollowUpSessions::getFollowUpSessions');
    $routes->match(['GET', 'POST'], 'message/operations', 'Message::operations');
    $routes->get('messages', 'Message::index');
    $routes->get('session/check', 'SessionCheck::index');
    $routes->get('announcements', 'Announcements::index');
    $routes->get('announcements/api', 'AnnouncementsApi::index');
    $routes->post('announcements/api', 'AnnouncementsApi::create');
    $routes->put('announcements/api/(:num)', 'AnnouncementsApi::update/$1');
    $routes->delete('announcements/api/(:num)', 'AnnouncementsApi::delete/$1');
    $routes->get('events/api', 'EventsApi::index');
    $routes->post('events/api', 'EventsApi::create');
    $routes->put('events/api/(:num)', 'EventsApi::update/$1');
    $routes->delete('events/api/(:num)', 'EventsApi::delete/$1');
    $routes->get('admins-management', 'AdminsManagement::index');
    $routes->get('admins-management/schedules', 'AdminsManagement::getCounselorSchedules');
    $routes->get('admins-management/counselors-by-time', 'AdminsManagement::getCounselorsByTimeSlot');
    $routes->get('view-users', 'AdminsManagement::viewUsers');
    $routes->get('account-settings', 'AdminsManagement::accountSettings');
    $routes->post('profile/update', 'AdminProfileApi::updateProfile');
    $routes->post('profile/picture', 'AdminProfileApi::updateProfilePicture');
    $routes->post('admin/profile/password', 'AdminProfileApi::updatePassword');
    $routes->get('users/api', 'UsersApi::getAllUsers');
    $routes->get('users/pds/(:num)', 'UsersApi::getStudentPDSData/$1');
    $routes->get('counselors/api', 'CounselorsApi::index');
    $routes->post('counselors/api', 'CounselorsApi::save');
    $routes->delete('counselors/api', 'CounselorsApi::delete');
    $routes->post('counselors/approve', 'CounselorsApi::approve');
    $routes->post('counselors/reject', 'CounselorsApi::reject');
    $routes->get('counselor-info', 'CounselorInfo::index');
    $routes->post('counselor-info/create-announcement', 'CounselorInfo::createAnnouncement');
    $routes->get('counselor-info/get-announcements', 'CounselorInfo::getAnnouncements');
    $routes->delete('counselor-info/delete-announcement/(:num)', 'CounselorInfo::deleteAnnouncement/$1');
    $routes->get('counselor-info/schedule', 'CounselorInfo::getCounselorSchedule');
    $routes->get('appointments/get_all_appointments', 'GetAllAppointments::index');
    $routes->get('history-reports', 'HistoryReports::index');
    $routes->get('history-reports/data', 'HistoryReports::getHistoryData');
    $routes->get('history-reports/historical-data', 'HistoryReports::getHistoricalData');
    
    // Filter data endpoints
    $routes->get('filter-data/counselors', 'FilterData::getCounselors');
    $routes->get('filter-data/students', 'FilterData::getStudents');
    $routes->get('filter-data/courses', 'FilterData::getCourses');
    $routes->get('filter-data/year-levels', 'FilterData::getYearLevels');
    $routes->get('filter-data/student-academic-map', 'FilterData::getStudentAcademicMap');
});

// Student routes
$routes->group('student', ['namespace' => 'App\Controllers\Student'], function($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('dashboard/get-profile-data', 'Dashboard::getProfileData');
    $routes->get('session/check', 'SessionCheck::index');
    $routes->match(['GET', 'POST'], 'message/operations', 'Message::operations');
    $routes->get('messages', 'Message::index');
    $routes->get('profile/get', 'Profile::getProfile');
    $routes->get('announcements', 'Announcements::index');
    $routes->get('announcements/all', 'Announcements::getAll');
    $routes->get('events/all', 'Events::getAll');
    $routes->get('schedule-appointment', 'Appointment::schedule');
    $routes->get('get-counselors', 'Appointment::getCounselors');
    $routes->get('get-counselors-by-availability', 'Appointment::getCounselorsByAvailability');
    $routes->get('check-pending-appointment', 'Appointment::checkPendingAppointment');
    $routes->get('check-appointment-eligibility', 'Appointment::checkAppointmentEligibility');
    $routes->get('check-counselor-conflicts', 'Appointment::checkCounselorConflicts');
    $routes->get('check-edit-conflicts', 'Appointment::checkEditConflicts');
    $routes->get('get-counselor-schedules', 'Appointment::getCounselorSchedules');
    // Calendar daily stats (approved counts and fully booked flags)
    $routes->get('calendar/daily-stats', 'Appointment::getCalendarDailyStats');
    $routes->get('profile', 'Profile::profile');
    $routes->post('profile/update', 'Profile::updateProfile');
    $routes->match(['POST','OPTIONS'], 'profile/picture', 'Profile::updateProfilePicture');
    $routes->post('appointment/save', 'Appointment::save');
    $routes->get('my-appointments', 'Appointment::viewAppointments');
    
    // Add notification routes
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/unread-count', 'Notifications::getUnreadCount');
    $routes->post('notifications/mark-read', 'Notifications::markAsRead');

    $routes->get('appointments/get-my-appointments', 'Appointment::getMyAppointments');
    $routes->get('appointments/booked-times', 'Appointment::getBookedTimesForDate');
    $routes->get('appointments/check-group-slots', 'Appointment::checkGroupSlotAvailability');
    $routes->post('appointments/update', 'Appointment::update');
    $routes->delete('appointments/delete/(:num)', 'Appointment::delete/$1');
    $routes->get('follow-up-sessions', 'FollowUpSessions::index');
    $routes->get('follow-up-sessions/completed-appointments', 'FollowUpSessions::getCompletedAppointments');
    $routes->get('follow-up-sessions/sessions', 'FollowUpSessions::getFollowUpSessions');
    $routes->post('appointments/cancel', 'Appointment::cancel');
    $routes->post('appointments/track-download', 'Appointment::trackDownload');
    $routes->post('appointments/test-email', 'Appointment::testEmailService');
    $routes->get('appointments/check-group-slots', 'Appointment::checkGroupSlots');



// Follow-up routes
    
    // PDS (Personal Data Sheet) routes
    $routes->get('pds/load', 'PDS::loadPDS');
    $routes->post('pds/save', 'PDS::savePDS');
    
    // Follow-up Sessions routes
    $routes->get('follow-up-sessions', 'FollowUpSessions::index');
    $routes->get('follow-up-sessions/completed-appointments', 'FollowUpSessions::getCompletedAppointments');
    $routes->get('follow-up-sessions/sessions', 'FollowUpSessions::getFollowUpSessions');
});

// Counselor routes
$routes->group('counselor', ['namespace' => 'App\Controllers\Counselor'], function($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('dashboard/recent-pending-appointments', 'Dashboard::getRecentPendingAppointments');
    $routes->get('session/check', 'SessionCheck::index');
    $routes->get('profile/get', 'Profile::getProfile');
    $routes->get('profile', 'Profile::profile');
    $routes->post('profile/update', 'Profile::updateProfile');
    $routes->match(['POST','OPTIONS'], 'profile/picture', 'Profile::updateProfilePicture');
    $routes->match(['GET'], 'profile/counselor-info', 'Profile::getProfile');
    $routes->match(['POST','OPTIONS'], 'profile/counselor-info', 'Profile::updatePersonalInfo');

    // Availability management
    $routes->get('profile/availability', 'Availability::get');
    $routes->match(['POST','OPTIONS'], 'profile/availability', 'Availability::save');
    $routes->match(['DELETE','OPTIONS'], 'profile/availability', 'Availability::delete');

    // Appointments routes (duplicates of admin for counselors)
    $routes->get('appointments', 'Appointments::index');
    $routes->get('appointments/view-all', 'Appointments::viewAll');
    $routes->get('appointments/scheduled', 'Appointments::scheduled');
    $routes->get('appointments/scheduled/get', 'Appointments::getScheduledAppointments');
    $routes->get('appointments/getAll', 'Appointments::getAppointments');
    $routes->get('appointments/getAppointments', 'Appointments::getAppointments');
    $routes->post('appointments/updateStatus', 'Appointments::updateStatus');
    $routes->post('appointments/updateAppointmentStatus', 'Appointments::updateAppointmentStatus');
    $routes->post('appointments/track-export', 'Appointments::trackExport');
    $routes->get('appointments/schedule', 'Appointments::getCounselorSchedule');
    $routes->get('follow-up', 'Appointments::followUp');

    // Reports endpoints used by view-all and charts
    $routes->get('appointments/get_all_appointments', 'GetAllAppointments::index');

    // History reports (duplicate)
    $routes->get('history-reports', 'HistoryReports::index');
    $routes->get('history-reports/data', 'HistoryReports::getHistoryData');
    $routes->get('history-reports/historical-data', 'HistoryReports::getHistoricalData');

    // Messages routes (duplicate of admin for counselors)
    $routes->get('messages', 'Message::index');
    $routes->match(['GET', 'POST'], 'message/operations', 'Message::operations');

    // Announcements routes (duplicate of user for counselors)
    $routes->get('announcements', 'Announcements::index');
    $routes->get('announcements/all', 'Announcements::getAll');
    $routes->get('events/all', 'Events::getAll');

    // Notifications routes for counselors
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/unread-count', 'Notifications::getUnreadCount');
    $routes->post('notifications/mark-read', 'Notifications::markAsRead');

    // Follow-up appointments routes
    $routes->get('follow-up', 'FollowUp::index');
    $routes->get('follow-up/completed-appointments', 'FollowUp::getCompletedAppointments');
    $routes->get('follow-up/sessions', 'FollowUp::getFollowUpSessions');
    $routes->get('follow-up/availability', 'FollowUp::getCounselorAvailability');
    $routes->get('follow-up/booked-times', 'FollowUp::getBookedTimesForDate');
    $routes->post('follow-up/create', 'FollowUp::createFollowUp');
    $routes->post('follow-up/edit', 'FollowUp::editFollowUp');
    $routes->post('follow-up/complete', 'FollowUp::completeFollowUp');
    $routes->post('follow-up/cancel', 'FollowUp::cancelFollowUp');
    
    // Filter data endpoints for counselor
    $routes->get('filter-data/students', 'FilterData::getStudents');
    $routes->get('filter-data/courses', 'FilterData::getCourses');
    $routes->get('filter-data/year-levels', 'FilterData::getYearLevels');
    $routes->get('filter-data/student-academic-map', 'FilterData::getStudentAcademicMap');
    // Counselor timezone test route
    $routes->get('appointments/test-timezone', 'Appointments::testManilaTimezone');
});


