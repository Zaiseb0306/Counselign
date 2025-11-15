<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="University Guidance Counseling Services - User Profile Page" />
    <meta name="keywords"
        content="counseling, guidance, university, support, mental health, student wellness, profile" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>User Profile - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?= base_url('css/student/student_profile.css') ?>" rel="stylesheet" />
    <link rel="stylesheet" href="<?= base_url('css/student/header.css') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <div class="logo-title-container">
                        <img src="<?= base_url('Photos/counselign_logo.png') ?>" alt="Counselign logo" class="logo" />
                        <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    </div>
                    <button class="custom-navbar-toggler d-lg-none align-items-center" type="button" id="navbarDrawerToggler">
                        <span class="navbar-toggler-icon"><i class="fas fa-gear"></i></span>
                    </button>
                    <nav class="navbar navbar-expand-lg navbar-dark">
                        <ul class="navbar-nav nav-links ms-auto">
                            <li><a href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
                            <li><a onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
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
            <li class="nav-item"><a class="nav-link" href="<?= base_url('student/dashboard') ?>"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <!-- Overlay for Navbar Drawer -->
    <div class="navbar-overlay d-lg-none" id="navbarOverlay"></div>

    <main>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img id="profile-img" src="<?= base_url('Photos/profile.png') ?>" alt="User Avatar" />
                </div>



                <!-- Enhanced form with title and better styling -->
                <div class="profile-form">
                    <div class="user-name">Account ID:<span class="user-id" id="display-userid"></span></div>

                    <div class="form-group">
                        <label class="form-label">Username:</label>
                        <div class="form-value" id="display-username"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email:</label>
                        <div class="form-value" id="display-email"></div>
                    </div>

                    <!-- Enhanced button group -->
                    <div class="btn-group">
                        <button class="btn btn-password" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <button class="btn btn-update" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                            <i class="fas fa-edit"></i> Update Profile
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-details">
                <div class="pds-header-row">
                    <h4>Personal Data Sheet</h4>
                    <div class="pds-actions">
                        <button type="button" id="pdsEditToggleBtn" class="btn btn-secondary btn-compact" aria-pressed="false">
                            <i class="fas fa-lock"></i> Enable Editing
                        </button>
                        <button type="button" id="pdsSaveBtn" class="btn btn-primary btn-compact" disabled>
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
                <!-- PDS CONTAINER (responsive + inner scrolling) -->
                <div class="pds-container card shadow-sm">
                    <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pds-personal-bg" type="button">
                                    <i class="fas fa-user me-2"></i> Personal Background
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pds-family-bg" type="button">
                                    <i class="fas fa-users me-2"></i> Family Background
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pds-other-info" type="button">
                                    <i class="fas fa-info-circle me-2"></i> Other Information
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pds-awards" type="button">
                                    <i class="fas fa-trophy me-2"></i> Awards
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <div class="tab-content p-3 pds-scroll">

                            <!-- ================================================ -->
                            <!-- TAB 1: PERSONAL BACKGROUND -->
                            <!-- ================================================ -->
                            <div class="tab-pane fade show active" id="pds-personal-bg">
                                <div class="row g-3">
                                    <!-- Academic Information -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Course/Track <span class="text-danger">*</span></label>
                                        <select class="form-select" id="courseSelect">
                                            <option value="">Select Course</option>
                                            <option value="BSIT">BSIT</option>
                                            <option value="BSABE">BSABE</option>
                                            <option value="BSEnE">BSEnE</option>
                                            <option value="BSHM">BSHM</option>
                                            <option value="BFPT">BFPT</option>
                                            <option value="BSA">BSA</option>
                                            <option value="BTHM">BTHM</option>
                                            <option value="BSSW">BSSW</option>
                                            <option value="BSAF">BSAF</option>
                                            <option value="BTLED">BTLED</option>
                                            <option value="DAT-BAT">DAT-BAT</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Grade/Year Level <span class="text-danger">*</span></label>
                                        <select class="form-select" id="yearSelect">
                                            <option value="">Select Year</option>
                                            <option value="I">I</option>
                                            <option value="II">II</option>
                                            <option value="III">III</option>
                                            <option value="IV">IV</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Academic Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="academicStatusSelect">
                                            <option value="">Select Status</option>
                                            <option value="Continuing/Old">Continuing/Old</option>
                                            <option value="Returnee">Returnee</option>
                                            <option value="Shiftee">Shiftee</option>
                                            <option value="New Student">New Student</option>
                                            <option value="Transferee">Transferee</option>
                                        </select>
                                    </div>

                                    <!-- NEW FIELDS -->
                                    <div class="col-md-4">
                                        <label class="form-label">School Last Attended</label>
                                        <input class="form-control" type="text" id="schoolLastAttended" placeholder="Enter school name">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Location of School</label>
                                        <input class="form-control" type="text" id="locationOfSchool" placeholder="City/Municipality">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Previous Course/Grade</label>
                                        <input class="form-control" type="text" id="previousCourseGrade" placeholder="e.g., Grade 12, STEM">
                                    </div>

                                    <!-- Personal Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-id-card me-2"></i>Personal Information</h6>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" id="lastName" placeholder="Enter last name">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" id="firstName" placeholder="Enter first name">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input class="form-control" type="text" id="middleName" placeholder="Enter middle name">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Sex <span class="text-danger">*</span></label>
                                        <select class="form-select" id="sexSelect">
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                        <input class="form-control" type="date" id="dateOfBirth">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Age</label>
                                        <input class="form-control" type="number" id="age" placeholder="Age">
                                    </div>

                                    <!-- NEW FIELD -->
                                    <div class="col-md-4">
                                        <label class="form-label">Place of Birth</label>
                                        <input class="form-control" type="text" id="placeOfBirth" placeholder="City/Municipality, Province">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="civilStatusSelect">
                                            <option value="">Select</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Legally Separated">Legally Separated</option>
                                            <option value="Annulled">Annulled</option>
                                        </select>
                                    </div>

                                    <!-- NEW FIELD -->
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <input class="form-control" type="text" id="religion" placeholder="Enter religion">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Contact Number</label>
                                        <input class="form-control" type="tel" id="contactNumber" placeholder="09XXXXXXXXX">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">E-mail Address</label>
                                        <input class="form-control" id="personalEmail" placeholder="name@example.com" readonly>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">FB Account Name</label>
                                        <input class="form-control" type="text" id="fbAccountName" placeholder="Facebook name">
                                    </div>

                                    <!-- Address Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-map-marker-alt me-2"></i>Permanent Home Address</h6>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Street/Zone</label>
                                        <input class="form-control" type="text" id="permanentAddressZone" placeholder="Zone">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Barangay</label>
                                        <input class="form-control" type="text" id="permanentAddressBarangay" placeholder="Barangay">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">City</label>
                                        <input class="form-control" type="text" id="permanentAddressCity" placeholder="City">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Province</label>
                                        <input class="form-control" type="text" id="permanentAddressProvince" placeholder="Province">
                                    </div>

                                    <div class="col-12 mt-3">
                                        <h6 class="text-primary mb-3"><i class="fas fa-home me-2"></i>Present Address</h6>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Street/Zone</label>
                                        <input class="form-control" type="text" id="presentAddressZone" placeholder="Zone">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Barangay</label>
                                        <input class="form-control" type="text" id="presentAddressBarangay" placeholder="Barangay">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">City</label>
                                        <input class="form-control" type="text" id="presentAddressCity" placeholder="City">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Province</label>
                                        <input class="form-control" type="text" id="presentAddressProvince" placeholder="Province">
                                    </div>
                                </div>
                            </div>

                            <!-- ================================================ -->
                            <!-- TAB 2: FAMILY BACKGROUND -->
                            <!-- ================================================ -->
                            <div class="tab-pane fade" id="pds-family-bg">
                                <div class="row g-3">
                                    <!-- Father Information -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="fas fa-male me-2"></i>Father's Information</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Father's Name</label>
                                        <input class="form-control" type="text" id="fatherName" placeholder="Full name">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Father's Occupation</label>
                                        <input class="form-control" type="text" id="fatherOccupation" placeholder="Occupation">
                                    </div>

                                    <!-- NEW FIELDS -->
                                    <div class="col-md-4">
                                        <label class="form-label">Father's Educational Attainment</label>
                                        <input class="form-control" type="text" id="fatherEducationalAttainment" placeholder="e.g., College Graduate">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Father's Age</label>
                                        <input class="form-control" type="number" id="fatherAge" placeholder="Age" min="18" max="120">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Father's Contact No.</label>
                                        <input class="form-control" type="tel" id="fatherContactNumber" placeholder="09XXXXXXXXX">
                                    </div>

                                    <!-- Mother Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-female me-2"></i>Mother's Information</h6>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Mother's Name</label>
                                        <input class="form-control" type="text" id="motherName" placeholder="Full name">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Mother's Occupation</label>
                                        <input class="form-control" type="text" id="motherOccupation" placeholder="Occupation">
                                    </div>

                                    <!-- NEW FIELDS -->
                                    <div class="col-md-4">
                                        <label class="form-label">Mother's Educational Attainment</label>
                                        <input class="form-control" type="text" id="motherEducationalAttainment" placeholder="e.g., High School Graduate">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Mother's Age</label>
                                        <input class="form-control" type="number" id="motherAge" placeholder="Age" min="18" max="120">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Mother's Contact No.</label>
                                        <input class="form-control" type="tel" id="motherContactNumber" placeholder="09XXXXXXXXX">
                                    </div>

                                    <!-- NEW FIELDS - Parents Address and Contact -->
                                    <div class="col-12 mt-3">
                                        <h6 class="text-primary mb-3"><i class="fas fa-address-book me-2"></i>Parents' Contact Information</h6>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Parents' Permanent Address</label>
                                        <textarea class="form-control" id="parentsPermanentAddress" rows="2" placeholder="Complete address"></textarea>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Parents' Contact No.</label>
                                        <input class="form-control" type="tel" id="parentsContactNumber" placeholder="09XXXXXXXXX">
                                    </div>

                                    <!-- Spouse Information (if married) -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-ring me-2"></i>Spouse Information (If Married)</h6>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Husband/Wife Name</label>
                                        <input class="form-control" type="text" id="spouse" placeholder="Full name">
                                    </div>

                                    <!-- NEW FIELDS -->
                                    <div class="col-md-4">
                                        <label class="form-label">Spouse's Occupation</label>
                                        <input class="form-control" type="text" id="spouseOccupation" placeholder="Occupation">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Spouse's Educational Attainment</label>
                                        <input class="form-control" type="text" id="spouseEducationalAttainment" placeholder="e.g., College Level">
                                    </div>

                                    <!-- Guardian Information -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-user-shield me-2"></i>Guardian Information (If Applicable)</h6>
                                    </div>

                                    <!-- NEW FIELDS -->
                                    <div class="col-md-4">
                                        <label class="form-label">Name of Guardian</label>
                                        <input class="form-control" type="text" id="guardianName" placeholder="Full name">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Guardian's Age</label>
                                        <input class="form-control" type="number" id="guardianAge" placeholder="Age" min="18" max="120">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Guardian's Occupation</label>
                                        <input class="form-control" type="text" id="guardianOccupation" placeholder="Occupation">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Guardian's Contact No.</label>
                                        <input class="form-control" type="tel" id="guardianContactNumber" placeholder="09XXXXXXXXX">
                                    </div>
                                </div>
                            </div>

                            <!-- ================================================ -->
                            <!-- TAB 3: OTHER INFORMATION -->
                            <!-- ================================================ -->
                            <div class="tab-pane fade" id="pds-other-info">
                                <div class="row g-3">
                                    <!-- Course Choice -->
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="fas fa-question-circle me-2"></i>Course Selection</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Why did you choose this course/program?</label>
                                        <textarea class="form-control" id="courseChoiceReason" rows="3" placeholder="Explain your reason for choosing this course..."></textarea>
                                    </div>

                                    <!-- Family Description -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-home me-2"></i>Family Description</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label d-block mb-2">Check all that apply:</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="familyDescHarmonious" value="harmonious">
                                                    <label class="form-check-label" for="familyDescHarmonious">Harmonious</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="familyDescConflict" value="conflict">
                                                    <label class="form-check-label" for="familyDescConflict">Conflict</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="familyDescSeparated_parents" value="separated_parents">
                                                    <label class="form-check-label" for="familyDescSeparated_parents">Separated Parents</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="familyDescParents_working_abroad" value="parents_working_abroad">
                                                    <label class="form-check-label" for="familyDescParents_working_abroad">Parents Working Abroad</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input class="form-control" type="text" id="familyDescriptionOther" placeholder="Others (Specify)">
                                        </div>
                                    </div>

                                    <!-- Living Arrangement (from residence) -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-building me-2"></i>Living Arrangement</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="residence" id="resHome" value="at home">
                                            <label class="form-check-label" for="resHome">At home</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="residence" id="resBoarding" value="boarding house">
                                            <label class="form-check-label" for="resBoarding">Boarding house</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="residence" id="resDorm" value="USTP-Claveria Dormitory">
                                            <label class="form-check-label" for="resDorm">USTP-Claveria Dormitory</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="residence" id="resRelatives" value="relatives">
                                            <label class="form-check-label" for="resRelatives">Relatives</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="residence" id="resFriends" value="friends">
                                            <label class="form-check-label" for="resFriends">Friends</label>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="radio" name="residence" id="resOther" value="other">
                                            </div>
                                            <input class="form-control" type="text" id="resOtherText" placeholder="Others (Specify)">
                                        </div>
                                    </div>

                                    <!-- Living Condition -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-couch me-2"></i>Living Condition</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="livingCondition" id="livingCondGood" value="good_environment">
                                            <label class="form-check-label" for="livingCondGood">Good environment for learning</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="livingCondition" id="livingCondNotGood" value="not_good_environment">
                                            <label class="form-check-label" for="livingCondNotGood">Not-so-good environment for learning</label>
                                        </div>
                                    </div>

                                    <!-- Physical/Health Condition -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-heartbeat me-2"></i>Physical/Health Condition</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Do you have any physical/health condition?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="physicalHealthCondition" id="healthNo" value="No">
                                                <label class="form-check-label" for="healthNo">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="physicalHealthCondition" id="healthYes" value="Yes">
                                                <label class="form-check-label" for="healthYes">Yes</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">If yes, please specify:</label>
                                        <textarea class="form-control" id="physicalHealthConditionSpecify" rows="2" placeholder="Describe your health condition"></textarea>
                                    </div>

                                    <!-- Psychological Treatment -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-brain me-2"></i>Psychological Treatment</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Have you undergone psychological treatment?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="psychTreatment" id="psychNo" value="No">
                                                <label class="form-check-label" for="psychNo">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="psychTreatment" id="psychYes" value="Yes">
                                                <label class="form-check-label" for="psychYes">Yes</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Special Circumstances -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-star me-2"></i>Special Circumstances</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Are you a solo parent?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="soloParent" id="soloParentYes" value="Yes">
                                                <label class="form-check-label" for="soloParentYes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="soloParent" id="soloParentNo" value="No">
                                                <label class="form-check-label" for="soloParentNo">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Member of indigenous people?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="indigenous" id="indigenousYes" value="Yes">
                                                <label class="form-check-label" for="indigenousYes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="indigenous" id="indigenousNo" value="No">
                                                <label class="form-check-label" for="indigenousNo">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Are you a breast-feeding mother?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="breastFeeding" id="bfYes" value="Yes">
                                                <label class="form-check-label" for="bfYes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="breastFeeding" id="bfNo" value="No">
                                                <label class="form-check-label" for="bfNo">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="breastFeeding" id="bfNA" value="N/A">
                                                <label class="form-check-label" for="bfNA">N/A (for Male)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Are you a person with disability?</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pwd" id="pwdYes" value="Yes">
                                                <label class="form-check-label" for="pwdYes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pwd" id="pwdNo" value="No">
                                                <label class="form-check-label" for="pwdNo">No</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="pwd" id="pwdOther" value="Other">
                                                <label class="form-check-label" for="pwdOther">Other</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Specify disability (put N/A if not applicable)</label>
                                        <input class="form-control" type="text" id="pwdSpecify" placeholder="N/A">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Attach PWD ID / Proof of Disability</label>
                                        <div class="input-group">
                                            <input class="form-control" type="file" id="pwdProof" accept="image/*,application/pdf" style="flex: 1 1 auto;">
                                            <button class="btn btn-secondary" type="button" id="previewPwdProof" style="display: none; flex: 0 0 auto; width: auto;">
                                                <i class="fas fa-eye"></i> <span class="d-none d-sm-inline ms-1">Preview</span>
                                            </button>
                                        </div>
                                        <div id="pwdProofPreview" class="mt-2" style="display: none;">
                                            <small class="text-muted">Current file: <span id="currentPwdProofName"></span></small>
                                        </div>

                                        <div id="pwdProofDisplayBox" class="mt-3" style="display: none;">
                                            <div class="card border-0 shadow-sm" style="max-width: 300px;">
                                                <div class="card-body p-3 text-center">
                                                    <div id="pwdProofFileContent" class="mb-2"></div>
                                                    <h6 class="card-title mb-1" id="pwdProofFileName">File Name</h6>
                                                    <small class="text-muted" id="pwdProofFileSize">File Size</small>
                                                    <div class="mt-2">
                                                        <button class="btn btn-outline-primary btn-sm me-2" id="viewPwdProofBtn">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <a href="#" class="btn btn-outline-secondary btn-sm" id="downloadPwdProofBtn" download>
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Services Needed -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-hands-helping me-2"></i>Services Needed</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label d-block mb-2">Check all that apply:</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="svcCounseling">
                                            <label class="form-check-label" for="svcCounseling">Counseling</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="svcInsurance">
                                            <label class="form-check-label" for="svcInsurance">Insurance</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="svcSpecialLanes">
                                            <label class="form-check-label" for="svcSpecialLanes">Special lanes for PWD/pregnant/elderly in all office</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="svcSafeLearning">
                                            <label class="form-check-label" for="svcSafeLearning">Safe learning environment, free from any form of discrimination</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="svcEqualAccess">
                                            <label class="form-check-label" for="svcEqualAccess">Equal access to quality education</label>
                                        </div>
                                        <div class="mt-2">
                                            <input class="form-control" type="text" id="svcOther" placeholder="Other (specify)">
                                        </div>
                                    </div>

                                    <!-- Services Availed -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-check-circle me-2"></i>Services Availed in the University</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="availedCounseling">
                                            <label class="form-check-label" for="availedCounseling">Counseling</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="availedInsurance">
                                            <label class="form-check-label" for="availedInsurance">Insurance</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="availedSpecialLanes">
                                            <label class="form-check-label" for="availedSpecialLanes">Special lanes for PWD/pregnant/elderly in all office</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="availedSafeLearning">
                                            <label class="form-check-label" for="availedSafeLearning">Safe learning environment, free from any form of discrimination</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="availedEqualAccess">
                                            <label class="form-check-label" for="availedEqualAccess">Equal access to quality education</label>
                                        </div>
                                        <div class="mt-2">
                                            <input class="form-control" type="text" id="availedOther" placeholder="Other (specify)">
                                        </div>
                                    </div>

                                    <!-- GCS Seminars/Activities -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-primary mb-3"><i class="fas fa-chalkboard-teacher me-2"></i>GCS Seminars/Activities to Avail</h6>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label d-block mb-2">Check all that apply:</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsAdjustment">
                                            <label class="form-check-label" for="gcsAdjustment">Adjustment</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsSelfConfidence">
                                            <label class="form-check-label" for="gcsSelfConfidence">Building Self-Confidence</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsCommunication">
                                            <label class="form-check-label" for="gcsCommunication">Developing Communication Skills</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsStudyHabits">
                                            <label class="form-check-label" for="gcsStudyHabits">Study Habits</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsTimeManagement">
                                            <label class="form-check-label" for="gcsTimeManagement">Time Management</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="gcsTutorial">
                                            <label class="form-check-label" for="gcsTutorial">Tutorial with Peers</label>
                                        </div>
                                        <div class="mt-2">
                                            <input class="form-control" type="text" id="tutorialSubjects" placeholder="Specify subject/s (if Tutorial with Peers)">
                                        </div>
                                        <div class="mt-2">
                                            <input class="form-control" type="text" id="gcsOther" placeholder="Others (specify)">
                                        </div>
                                    </div>

                                    <!-- Consent -->
                                    <div class="col-12 mt-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="consentAgree">
                                            <label class="form-check-label" for="consentAgree">
                                                <strong>I voluntarily give my consent to participate in this survey.</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ================================================ -->
                            <!-- TAB 4: AWARDS AND RECOGNITION -->
                            <!-- ================================================ -->
                            <div class="tab-pane fade" id="pds-awards">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3"><i class="fas fa-trophy me-2"></i>Awards and Recognition</h6>
                                        <p class="text-muted small">List up to 3 awards or recognitions you have received</p>
                                    </div>

                                    <!-- Award 1 -->
                                    <div class="col-12 mt-3">
                                        <h6 class="text-secondary mb-2">Award 1</h6>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">Name of Award</label>
                                        <input class="form-control" type="text" id="awardName1" placeholder="e.g., Academic Excellence Award">
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">School/Organization</label>
                                        <input class="form-control" type="text" id="awardSchoolOrg1" placeholder="e.g., USTP Claveria">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Year</label>
                                        <input class="form-control" type="text" id="awardYear1" placeholder="2024" maxlength="4">
                                    </div>

                                    <!-- Award 2 -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-secondary mb-2">Award 2</h6>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">Name of Award</label>
                                        <input class="form-control" type="text" id="awardName2" placeholder="e.g., Leadership Award">
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">School/Organization</label>
                                        <input class="form-control" type="text" id="awardSchoolOrg2" placeholder="e.g., Student Council">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Year</label>
                                        <input class="form-control" type="text" id="awardYear2" placeholder="2023" maxlength="4">
                                    </div>

                                    <!-- Award 3 -->
                                    <div class="col-12 mt-4">
                                        <h6 class="text-secondary mb-2">Award 3</h6>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">Name of Award</label>
                                        <input class="form-control" type="text" id="awardName3" placeholder="e.g., Best Capstone Project">
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">School/Organization</label>
                                        <input class="form-control" type="text" id="awardSchoolOrg3" placeholder="e.g., IT Department">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Year</label>
                                        <input class="form-control" type="text" id="awardYear3" placeholder="2022" maxlength="4">
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Note:</strong> Leave fields blank if you have fewer than 3 awards. Only filled awards will be saved.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProfileForm">
                        <div class="mb-3">
                            <label for="update-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="update-username">
                        </div>
                        <div class="mb-3">
                            <label for="update-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="update-email">
                        </div>
                        <div class="mb-3">
                            <label for="update-picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="update-picture" accept="image/*">
                            <div class="mt-2 text-center">
                                <img id="update-picture-preview" src="<?= base_url('Photos/profile.png') ?>" alt="Preview" style="max-width:120px; max-height:120px; border-radius:50%; object-fit:cover; display:none;" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProfileChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="password-field mb-4">
                            <label for="current-password" class="form-label">Current Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="current-password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('current-password')"></i>
                            </div>
                        </div>
                        <div class="password-field mb-4">
                            <label for="new-password" class="form-label">New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="new-password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('new-password')"></i>
                            </div>
                        </div>
                        <div class="password-field mb-4">
                            <label for="confirm-password" class="form-label">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="confirm-password" required>
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm-password')"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="changePassword()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PWD Proof Preview Modal -->
    <div class="modal fade" id="pwdProofModal" tabindex="-1" aria-labelledby="pwdProofModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pwdProofModalLabel">
                        <i class="fas fa-file-alt me-2"></i>PWD Proof Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="pwdProofContent" class="text-center">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <?php echo view('modals/student_dashboard_modals'); ?>
    <script src="<?= base_url('js/modals/student_dashboard_modals.js') ?>"></script>
    <script src="<?= base_url('js/utils/secureLogger.js') ?>"></script>
    <script src="<?= base_url('js/student/student_profile.js') ?>"></script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
</body>

</html>