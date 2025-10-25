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
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pds-acad" type="button"><i class="fas fa-graduation-cap me-2"></i>A. Academic</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pds-personal" type="button"><i class="fas fa-user me-2"></i>B. Personal</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pds-other-info" type="button"><i class="fas fa-info-circle me-2"></i>C. Other Information</button></li>
                        </ul>

                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content p-3 pds-scroll">
                            <div class="tab-pane fade show active" id="pds-acad">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Course</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <select class="form-select" id="courseSelect">
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
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Year Level</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <select class="form-select" id="yearSelect">
                                                <option value="I">I</option>
                                                <option value="II">II</option>
                                                <option value="III">III</option>
                                                <option value="IV">IV</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Academic Status</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <select class="form-select" id="academicStatusSelect">
                                                <option value="Continuing/Old">Continuing/Old</option>
                                                <option value="Returnee">Returnee</option>
                                                <option value="Shiftee">Shiftee</option>
                                                <option value="New Student">New Student</option>
                                                <option value="Transferee">Transferee</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pds-personal">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label">Last name</label><input class="form-control" type="text" id="lastName"></div>
                                    <div class="col-md-3"><label class="form-label">First name</label><input class="form-control" type="text" id="firstName"></div>
                                    <div class="col-md-3"><label class="form-label">Middle name</label><input class="form-control" type="text" id="middleName"></div>
                                    <div class="col-md-3"><label class="form-label">Date of Birth</label><input class="form-control" type="date" id="dateOfBirth"></div>
                                    <div class="col-md-2"><label class="form-label">Age</label><input class="form-control" type="number" id="age"></div>
                                    <div class="col-md-2"><label class="form-label">Sex</label>
                                        <div class="d-flex gap-1">
                                            <select class="form-select" id="sexSelect">
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Civil Status</label>
                                        <select class="form-select" id="civilStatusSelect">
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Legally Separated">Legally Separated</option>
                                            <option value="Annulled">Annulled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Contact Number</label><input class="form-control" type="tel" id="contactNumber" placeholder="09XXXXXXXXX"></div>


                                    <div class="col-12">
                                        <h6>Social</h6>
                                    </div>
                                    <div class="col-md-6"><label class="form-label">FB Account Name</label><input class="form-control" type="text" id="fbAccountName"></div>
                                    <div class="col-md-6"><label class="form-label">Email Address</label><input class="form-control" id="personalEmail" placeholder="name@example.com" readonly></div>

                                    <div class="col-12">
                                        <h6>Permanent Home Address</h6>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Zone</label><input class="form-control" type="text" id="permanentAddressZone"></div>
                                    <div class="col-md-3"><label class="form-label">Barangay</label><input class="form-control" type="text" id="permanentAddressBarangay"></div>
                                    <div class="col-md-3"><label class="form-label">City</label><input class="form-control" type="text" id="permanentAddressCity"></div>
                                    <div class="col-md-3"><label class="form-label">Province</label><input class="form-control" type="text" id="permanentAddressProvince"></div>

                                    <div class="col-12">
                                        <h6>Present Address</h6>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Zone</label><input class="form-control" type="text" id="presentAddressZone"></div>
                                    <div class="col-md-3"><label class="form-label">Barangay</label><input class="form-control" type="text" id="presentAddressBarangay"></div>
                                    <div class="col-md-3"><label class="form-label">City</label><input class="form-control" type="text" id="presentAddressCity"></div>
                                    <div class="col-md-3"><label class="form-label">Province</label><input class="form-control" type="text" id="presentAddressProvince"></div>

                                    <div class="col-12">
                                        <h6 class="mt-3">Parents / Guardian</h6>
                                    </div>
                                    <div class="col-md-8"><label class="form-label">Father's Name</label><input class="form-control" type="text" id="fatherName"></div>
                                    <div class="col-md-4"><label class="form-label">Father's Occupation</label><input class="form-control" type="text" id="fatherOccupation"></div>
                                    <div class="col-md-8"><label class="form-label">Mother's Name</label><input class="form-control" type="text" id="motherName"></div>
                                    <div class="col-md-4"><label class="form-label">Mother's Occupation</label><input class="form-control" type="text" id="motherOccupation"></div>
                                    <div class="col-md-8"><label class="form-label" for="spouse">Spouse</label><input class="form-control" type="text" id="spouse"></div>
                                    <div class="col-md-4"><label class="form-label">Parent/Guardian/Spouse Contact Number</label><input class="form-control" type="tel" id="guardianContactNumber" placeholder="09XXXXXXXXX"></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="pds-other-info">
                                <div class="row g-3">


                                    <div class="col-12">
                                        <h6 class="mt-3 mb-2">Special Circumstances</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label d-flex">Are you a solo parent? <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="soloParent" id="soloParentYes" value="Yes"><label class="form-check-label" for="soloParentYes">Yes</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="soloParent" id="soloParentNo" value="No"><label class="form-check-label" for="soloParentNo">No</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label d-flex">Member of indigenous people? <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-2">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="indigenous" id="indigenousYes" value="Yes"><label class="form-check-label" for="indigenousYes">Yes</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="indigenous" id="indigenousNo" value="No"><label class="form-check-label" for="indigenousNo">No</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label d-flex">Are you a breast-feeding mother? <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="breastFeeding" id="bfYes" value="Yes"><label class="form-check-label" for="bfYes">Yes</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="breastFeeding" id="bfNo" value="No"><label class="form-check-label" for="bfNo">No</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="breastFeeding" id="bfNA" value="N/A"><label class="form-check-label" for="bfNA">N/A (for Male)</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label d-flex">Are you a person with disability? <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check"><input class="form-check-input" type="radio" name="pwd" id="pwdYes" value="Yes"><label class="form-check-label" for="pwdYes">Yes</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="pwd" id="pwdNo" value="No"><label class="form-check-label" for="pwdNo">No</label></div>
                                            <div class="form-check"><input class="form-check-input" type="radio" name="pwd" id="pwdOther" value="Other"><label class="form-check-label" for="pwdOther">Other</label></div>
                                        </div>
                                    </div>
                                    <div class="col-md-12"><label class="form-label">Specify disability (put N/A if not applicable)</label><input class="form-control" type="text" id="pwdSpecify" placeholder="N/A"></div>
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

                                        <!-- PWD Proof File Display Box -->
                                        <div id="pwdProofDisplayBox" class="mt-3" style="display: none;">
                                            <div class="card border-0 shadow-sm" style="max-width: 300px;">
                                                <div class="card-body p-3 text-center">
                                                    <div id="pwdProofFileContent" class="mb-2">
                                                        <!-- File content will be displayed here -->
                                                    </div>
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

                                    <div class="col-12">
                                        <h6 class="mt-3 mb-2">Services Needed <small class="text-muted">(check all that apply)</small></h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="svcCounseling"><label class="form-check-label" for="svcCounseling">counseling</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="svcInsurance"><label class="form-check-label" for="svcInsurance">insurance</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="svcSpecialLanes"><label class="form-check-label" for="svcSpecialLanes">special lanes for PWD/pregnant/elderly in all office</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="svcSafeLearning"><label class="form-check-label" for="svcSafeLearning">safe learning environment, free from any form of discrimination</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="svcEqualAccess"><label class="form-check-label" for="svcEqualAccess">equal access to quality education</label></div>
                                        <div class="mt-2"><input class="form-control" type="text" id="svcOther" placeholder="Other (specify)"></div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mt-3 mb-2">Services Availed in the University</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="availedCounseling"><label class="form-check-label" for="availedCounseling">counseling</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="availedInsurance"><label class="form-check-label" for="availedInsurance">insurance</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="availedSpecialLanes"><label class="form-check-label" for="availedSpecialLanes">special lanes for PWD/pregnant/elderly in all office</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="availedSafeLearning"><label class="form-check-label" for="availedSafeLearning">safe learning environment, free from any form of discrimination</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" id="availedEqualAccess"><label class="form-check-label" for="availedEqualAccess">equal access to quality education</label></div>
                                        <div class="mt-2"><input class="form-control" type="text" id="availedOther" placeholder="Other (specify)"></div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mt-3 mb-2">Current Residence</h6>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check"><input class="form-check-input" type="radio" name="residence" id="resHome" value="at home"><label class="form-check-label" for="resHome">at home</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="residence" id="resBoarding" value="boarding house"><label class="form-check-label" for="resBoarding">boarding house</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="residence" id="resDorm" value="USTP-Claveria Dormitory"><label class="form-check-label" for="resDorm">USTP-Claveria Dormitory</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="residence" id="resRelatives" value="relatives"><label class="form-check-label" for="resRelatives">relatives</label></div>
                                        <div class="form-check"><input class="form-check-input" type="radio" name="residence" id="resFriends" value="friends"><label class="form-check-label" for="resFriends">friends</label></div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <div class="form-check m-0"><input class="form-check-input" type="radio" name="residence" id="resOther" value="Other"></div><input class="form-control" type="text" id="resOtherText" placeholder="Other (specify)">
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="consentAgree">
                                            <label class="form-check-label" for="consentAgree">I voluntarily give my consent to participate in this survey.</label>
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

    <script src="<?= base_url('js/student/student_profile.js') ?>"></script>
    <script src="<?= base_url('js/student/logout.js') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>";
    </script>
</body>

</html>