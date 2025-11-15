document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const usersTableBody = document.getElementById('usersTableBody');
    const noUsersMessage = document.getElementById('noUsersFound');
    const totalUsersElement = document.getElementById('totalUsers');
    const activeUsersElement = document.getElementById('activeUsers');
    
    let allUsers = [];

    // Add event listeners
    if (searchInput) searchInput.addEventListener('input', filterUsers);
    if (statusFilter) statusFilter.addEventListener('change', filterUsers);

    // Fetch users when the page loads
    fetchUsers();

    async function fetchUsers() {
        try {
            const response = await fetch((window.BASE_URL || '/') + 'admin/users/api', {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                allUsers = data.users;
                updateUserStats(data.activeCount);
                displayUsers(allUsers);
            } else {
                showError(data.message || 'Failed to fetch users');
            }
        } catch (error) {
            console.error('Error fetching users:', error);
            showError('An error occurred while fetching users');
        }
    }

    function updateUserStats(activeCount) {
        if (totalUsersElement) {
            totalUsersElement.textContent = allUsers.length;
        }
        
        if (activeUsersElement) {
            activeUsersElement.textContent = activeCount || 0;
        }
    }

    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;

        let filtered = allUsers.filter(user => {
            // Get course information from student data
            const courseInfo = user.student_data?.academic_info?.course && user.student_data?.academic_info?.year_level 
                ? `${user.student_data.academic_info.course}-${user.student_data.academic_info.year_level}`
                : 'Not specified';
            
            const searchFields = [
                user.user_id,
                user.username,
                user.email,
                courseInfo
            ];
            
            const matchesSearch = searchFields.some(field => 
                String(field).toLowerCase().includes(searchTerm)
            );
            
            let matchesStatus = true;
            if (statusValue === 'active') {
                matchesStatus = user.is_online;
            } else if (statusValue === 'inactive') {
                matchesStatus = !user.is_online;
            }
            
            return matchesSearch && matchesStatus;
        });

        displayUsers(filtered);
    }

    function displayUsers(users) {
        if (!usersTableBody) return;
        
        usersTableBody.innerHTML = '';
        
        if (!users || users.length === 0) {
            noUsersMessage.style.display = 'block';
            return;
        }

        noUsersMessage.style.display = 'none';
        
        users.forEach(user => {
            const row = document.createElement('tr');
            let activityStatus;
            
            // Get course information from student data
            const courseInfo = user.student_data?.academic_info?.course && user.student_data?.academic_info?.year_level 
                ? `${user.student_data.academic_info.course}-${user.student_data.academic_info.year_level}`
                : 'Not specified';
            
            if (user.is_online) {
                activityStatus = `<span class="badge bg-success">Active</span>`;
            } else {
                activityStatus = `<span class="text-muted">${user.activity_status}</span>`;
            }
            
            const fullName = (user.student_data && user.student_data.personal_info && (user.student_data.personal_info.last_name || user.student_data.personal_info.first_name))
                ? `${user.student_data.personal_info.last_name || ''}, ${user.student_data.personal_info.first_name || ''}`.trim().replace(/^,\s*|\s*,\s*$/g, '')
                : '';

            row.innerHTML = `
                <td>
                    <button class="btn btn-primary action-btn" onclick="viewUserDetails('${user.user_id}')">
                        <i class="fas fa-eye"></i><span class="btn-text">PDS</span>
                    </button>
                </td>
                <td>${user.user_id || ''}</td>
                <td>${fullName || ''}</td>
                <td>${user.username || ''}</td>
                <td>${user.email || ''}</td>
                <td>${courseInfo}</td>
                <td>${formatDate(user.created_at)}</td>
                <td>${activityStatus}</td>
            `;
            usersTableBody.appendChild(row);
        });
    }

    // PDS Modal functionality
    let currentStudentId = null;

    // Function to view student PDS data
    window.viewUserDetails = function(userId) {
        currentStudentId = userId;
        showPDSModal(userId);
    };

    // Function to show PDS modal and load data
    function showPDSModal(userId) {
        const modal = new bootstrap.Modal(document.getElementById('studentPDSModal'));
        
        // Show loading state
        showPDSLoadingState();
        
        // Show modal
        modal.show();
        
        // Load student PDS data
        loadStudentPDSData(userId);
    }

    // Function to show loading state
    function showPDSLoadingState() {
        document.getElementById('pdsLoadingState').style.display = 'block';
        document.getElementById('pdsErrorState').style.display = 'none';
        document.getElementById('pdsContent').style.display = 'none';
    }

    // Function to show error state
    function showPDSErrorState(message) {
        document.getElementById('pdsLoadingState').style.display = 'none';
        document.getElementById('pdsErrorState').style.display = 'block';
        document.getElementById('pdsContent').style.display = 'none';
        document.getElementById('pdsErrorMessage').textContent = message;
    }

    // Function to show PDS content
    function showPDSContent() {
        document.getElementById('pdsLoadingState').style.display = 'none';
        document.getElementById('pdsErrorState').style.display = 'none';
        document.getElementById('pdsContent').style.display = 'block';
    }

    // Function to load student PDS data
    async function loadStudentPDSData(userId) {
        try {
            const response = await fetch((window.BASE_URL || '/') + `admin/users/pds/${userId}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                populatePDSModal(data.user_info, data.pds_data);
                showPDSContent();
            } else {
                showPDSErrorState(data.message || 'Failed to load student PDS data');
            }
        } catch (error) {
            console.error('Error loading student PDS data:', error);
            showPDSErrorState('An error occurred while loading student data');
        }
    }

    // Function to populate PDS modal with data
    function populatePDSModal(userInfo, pdsData) {
        // Update student header information
        document.getElementById('pdsStudentId').textContent = userInfo.user_id;
        document.getElementById('pdsStudentEmail').textContent = userInfo.email;
        
        // Update profile picture
        const profilePicture = document.getElementById('pdsProfilePicture');
        if (userInfo.profile_picture) {
            profilePicture.src = resolveImageUrl(userInfo.profile_picture);
        } else {
            profilePicture.src = (window.BASE_URL || '/') + 'Photos/profile.png';
        }

        // Update student name
        const fullName = formatStudentName(pdsData.personal_info);
        document.getElementById('pdsStudentName').textContent = fullName || userInfo.username;

        // ========================================
        // PERSONAL BACKGROUND TAB
        // ========================================
        
        // Academic Information
        if (pdsData.academic_info) {
            setPDSValue('pdsCourse', pdsData.academic_info.course);
            setPDSValue('pdsYearLevel', pdsData.academic_info.year_level);
            setPDSValue('pdsAcademicStatus', pdsData.academic_info.academic_status);
            setPDSValue('pdsSchoolLastAttended', pdsData.academic_info.school_last_attended);
            setPDSValue('pdsLocationOfSchool', pdsData.academic_info.location_of_school);
            setPDSValue('pdsPreviousCourseGrade', pdsData.academic_info.previous_course_grade);
        }

        // Personal Information
        if (pdsData.personal_info) {
            setPDSValue('pdsLastName', pdsData.personal_info.last_name);
            setPDSValue('pdsFirstName', pdsData.personal_info.first_name);
            setPDSValue('pdsMiddleName', pdsData.personal_info.middle_name);
            setPDSValue('pdsDateOfBirth', formatDate(pdsData.personal_info.date_of_birth));
            setPDSValue('pdsAge', pdsData.personal_info.age);
            setPDSValue('pdsSex', pdsData.personal_info.sex);
            setPDSValue('pdsPlaceOfBirth', pdsData.personal_info.place_of_birth);
            setPDSValue('pdsCivilStatus', pdsData.personal_info.civil_status);
            setPDSValue('pdsReligion', pdsData.personal_info.religion);
            setPDSValue('pdsContactNumber', pdsData.personal_info.contact_number);
            setPDSValue('pdsFbAccount', pdsData.personal_info.fb_account_name);
            setPDSValue('pdsPersonalEmail', userInfo.email);
        }

        // Address Information
        if (pdsData.address_info) {
            setPDSValue('pdsPermanentZone', pdsData.address_info.permanent_zone);
            setPDSValue('pdsPermanentBarangay', pdsData.address_info.permanent_barangay);
            setPDSValue('pdsPermanentCity', pdsData.address_info.permanent_city);
            setPDSValue('pdsPermanentProvince', pdsData.address_info.permanent_province);
            setPDSValue('pdsPresentZone', pdsData.address_info.present_zone);
            setPDSValue('pdsPresentBarangay', pdsData.address_info.present_barangay);
            setPDSValue('pdsPresentCity', pdsData.address_info.present_city);
            setPDSValue('pdsPresentProvince', pdsData.address_info.present_province);
        }

        // ========================================
        // FAMILY BACKGROUND TAB
        // ========================================
        
        if (pdsData.family_info) {
            // Father's Information
            setPDSValue('pdsFatherName', pdsData.family_info.father_name);
            setPDSValue('pdsFatherOccupation', pdsData.family_info.father_occupation);
            setPDSValue('pdsFatherEducation', pdsData.family_info.father_educational_attainment);
            setPDSValue('pdsFatherAge', pdsData.family_info.father_age);
            setPDSValue('pdsFatherContact', pdsData.family_info.father_contact_number);
            
            // Mother's Information
            setPDSValue('pdsMotherName', pdsData.family_info.mother_name);
            setPDSValue('pdsMotherOccupation', pdsData.family_info.mother_occupation);
            setPDSValue('pdsMotherEducation', pdsData.family_info.mother_educational_attainment);
            setPDSValue('pdsMotherAge', pdsData.family_info.mother_age);
            setPDSValue('pdsMotherContact', pdsData.family_info.mother_contact_number);
            
            // Parents' Contact Information
            setPDSValue('pdsParentsAddress', pdsData.family_info.parents_permanent_address);
            setPDSValue('pdsParentsContact', pdsData.family_info.parents_contact_number);
            
            // Spouse Information
            setPDSValue('pdsSpouse', pdsData.family_info.spouse);
            setPDSValue('pdsSpouseOccupation', pdsData.family_info.spouse_occupation);
            setPDSValue('pdsSpouseEducation', pdsData.family_info.spouse_educational_attainment);
            
            // Guardian Information
            setPDSValue('pdsGuardianName', pdsData.family_info.guardian_name);
            setPDSValue('pdsGuardianAge', pdsData.family_info.guardian_age);
            setPDSValue('pdsGuardianOccupation', pdsData.family_info.guardian_occupation);
            setPDSValue('pdsGuardianContact', pdsData.family_info.guardian_contact_number);
        }

        // ========================================
        // OTHER INFORMATION TAB
        // ========================================
        
        // Course Choice & Family Description
        if (pdsData.other_info) {
            setPDSValue('pdsCourseReason', pdsData.other_info.course_choice_reason);
            
            // Format family description
            if (pdsData.other_info.family_description && Array.isArray(pdsData.other_info.family_description)) {
                const familyDesc = formatFamilyDescription(pdsData.other_info.family_description, pdsData.other_info.family_description_other);
                setPDSValue('pdsFamilyDescription', familyDesc);
            }
            
            setPDSValue('pdsLivingCondition', formatLivingCondition(pdsData.other_info.living_condition));
            
            // Format health condition
            const healthCondition = pdsData.other_info.physical_health_condition === 'Yes' 
                ? `Yes - ${pdsData.other_info.physical_health_condition_specify || 'Not specified'}` 
                : pdsData.other_info.physical_health_condition;
            setPDSValue('pdsHealthCondition', healthCondition);
            
            setPDSValue('pdsPsychTreatment', pdsData.other_info.psych_treatment);
        }

        // Special Circumstances
        if (pdsData.special_circumstances) {
            setPDSValue('pdsSoloParent', pdsData.special_circumstances.is_solo_parent);
            setPDSValue('pdsIndigenous', pdsData.special_circumstances.is_indigenous);
            setPDSValue('pdsBreastfeeding', pdsData.special_circumstances.is_breastfeeding);
            setPDSValue('pdsPWD', pdsData.special_circumstances.is_pwd);
            
            // Handle PWD details
            const isPWD = pdsData.special_circumstances.is_pwd === 'Yes' || pdsData.special_circumstances.is_pwd === 'Other';
            const pwdDetailsSection = document.getElementById('pwdDetailsSection');
            const pwdProofSection = document.getElementById('pwdProofSection');
            
            if (isPWD) {
                pwdDetailsSection.style.display = 'block';
                pwdProofSection.style.display = 'block';
                setPDSValue('pdsPWDType', pdsData.special_circumstances.pwd_disability_type);
                displayPWDProofFile(pdsData.special_circumstances.pwd_proof_file);
            } else {
                pwdDetailsSection.style.display = 'none';
                pwdProofSection.style.display = 'none';
            }
        }

        // Services Information
        if (pdsData.services_needed && Array.isArray(pdsData.services_needed)) {
            const servicesNeeded = formatServicesList(pdsData.services_needed);
            setPDSValue('pdsServicesNeeded', servicesNeeded);
        }
        
        if (pdsData.services_availed && Array.isArray(pdsData.services_availed)) {
            const servicesAvailed = formatServicesList(pdsData.services_availed);
            setPDSValue('pdsServicesAvailed', servicesAvailed);
        }

        // GCS Activities
        if (pdsData.gcs_activities && Array.isArray(pdsData.gcs_activities)) {
            const gcsActivities = formatGCSActivities(pdsData.gcs_activities);
            setPDSValue('pdsGCSActivities', gcsActivities);
        }

        // Residence Information
        if (pdsData.residence_info) {
            setPDSValue('pdsResidence', pdsData.residence_info.residence_type);
            setPDSValue('pdsConsent', pdsData.residence_info.has_consent ? 'Yes' : 'No');
        }

        // ========================================
        // AWARDS TAB
        // ========================================
        
        if (pdsData.awards && Array.isArray(pdsData.awards) && pdsData.awards.length > 0) {
            populateAwards(pdsData.awards);
        } else {
            const awardsContainer = document.getElementById('pdsAwardsContainer');
            awardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="info-item">
                        <div class="info-value text-muted">No awards recorded</div>
                    </div>
                </div>
            `;
        }
    }

    // Helper function to format family description
    function formatFamilyDescription(descriptions, otherText) {
        if (!descriptions || descriptions.length === 0) {
            return 'Not specified';
        }
        
        const descMap = {
            'harmonious': 'Harmonious',
            'conflict': 'Conflict',
            'separated_parents': 'Separated Parents',
            'parents_working_abroad': 'Parents Working Abroad'
        };
        
        const formatted = descriptions.map(desc => descMap[desc] || desc);
        
        if (otherText && otherText.trim() !== '') {
            formatted.push(`Other: ${otherText}`);
        }
        
        return formatted.join(', ');
    }

    // Helper function to format living condition
    function formatLivingCondition(condition) {
        if (!condition) return 'Not specified';
        
        const conditionMap = {
            'good_environment': 'Good environment for learning',
            'not_good_environment': 'Not-so-good environment for learning'
        };
        
        return conditionMap[condition] || condition;
    }

    // Helper function to format GCS activities
    function formatGCSActivities(activities) {
        if (!activities || activities.length === 0) {
            return 'None specified';
        }
        
        const activityMap = {
            'adjustment': 'Adjustment',
            'building_self_confidence': 'Building Self-Confidence',
            'developing_communication_skills': 'Developing Communication Skills',
            'study_habits': 'Study Habits',
            'time_management': 'Time Management',
            'tutorial_with_peers': 'Tutorial with Peers'
        };
        
        const formatted = activities.map(activity => {
            if (activity.type === 'other') {
                return activity.other || 'Other';
            }
            
            if (activity.type === 'tutorial_with_peers' && activity.tutorial_subjects) {
                return `Tutorial with Peers (${activity.tutorial_subjects})`;
            }
            
            return activityMap[activity.type] || activity.type;
        });
        
        return formatted.join(', ');
    }

    // Helper function to populate awards
    function populateAwards(awards) {
        const awardsContainer = document.getElementById('pdsAwardsContainer');
        awardsContainer.innerHTML = '';
        
        awards.forEach((award, index) => {
            const awardHtml = `
                <div class="col-12 mb-3">
                    <h6 class="text-secondary mb-2">Award ${index + 1}</h6>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="info-item">
                                <label class="info-label">Name of Award</label>
                                <div class="info-value">${award.award_name || 'Not specified'}</div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="info-item">
                                <label class="info-label">School/Organization</label>
                                <div class="info-value">${award.school_organization || 'Not specified'}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-item">
                                <label class="info-label">Year</label>
                                <div class="info-value">${award.year_received || 'Not specified'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            awardsContainer.insertAdjacentHTML('beforeend', awardHtml);
        });
    }
    function setPDSValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Handle different types of empty/null values
            if (value === null || value === undefined || value === '' || value === 'N/A') {
                element.textContent = 'Not specified';
            } else {
                element.textContent = value;
            }
        }
    }

    // Helper function to format student name
    function formatStudentName(personalInfo) {
        if (!personalInfo) return null;
        
        const lastName = personalInfo.last_name || '';
        const firstName = personalInfo.first_name || '';
        
        if (lastName && firstName) {
            return `${lastName}, ${firstName}`;
        } else if (firstName) {
            return firstName;
        } else if (lastName) {
            return lastName;
        }
        
        return null;
    }

    // Helper function to format services list
    function formatServicesList(services) {
        if (!services || services.length === 0) {
            return 'Not specified';
        }
        
        const serviceNames = services.map(service => {
            if (service.type === 'other') {
                return service.other || 'Other';
            }
            
            const serviceMap = {
                'counseling': 'Counseling',
                'insurance': 'Insurance',
                'special_lanes': 'Special Lanes for PWD/Pregnant/Elderly',
                'safe_learning': 'Safe Learning Environment',
                'equal_access': 'Equal Access to Quality Education'
            };
            
            return serviceMap[service.type] || service.type;
        });
        
        return serviceNames.join(', ');
    }

    // Helper function to resolve image URL
    function resolveImageUrl(path) {
        if (!path) return (window.BASE_URL || '/') + 'Photos/profile.png';
        if (path.startsWith('http')) return path;
        if (path.startsWith('/')) return (window.BASE_URL || '/') + path.substring(1);
        return (window.BASE_URL || '/') + path;
    }

    // Function to retry loading PDS data
    window.retryLoadPDS = function() {
        if (currentStudentId) {
            showPDSModal(currentStudentId);
        }
    };

    // Function to display PWD proof file
    function displayPWDProofFile(filePath) {
        const placeholder = document.getElementById('pdsPWDProofPlaceholder');
        const fileDisplay = document.getElementById('pdsPWDProofFile');
        
        if (!filePath || filePath === 'N/A' || filePath.trim() === '') {
            placeholder.style.display = 'block';
            fileDisplay.style.display = 'none';
            return;
        }
        
        const fileName = filePath.split('/').pop();
        const fileExtension = fileName.split('.').pop().toLowerCase();
        
        // Update file name
        document.getElementById('pwdProofFileName').textContent = fileName;
        
        // Update file type
        const fileTypeMap = {
            'pdf': 'PDF Document',
            'doc': 'Word Document',
            'docx': 'Word Document',
            'jpg': 'Image File',
            'jpeg': 'Image File',
            'png': 'Image File',
            'gif': 'Image File',
            'xls': 'Excel Spreadsheet',
            'xlsx': 'Excel Spreadsheet'
        };
        document.getElementById('pwdProofFileType').textContent = fileTypeMap[fileExtension] || 'Document File';
        
        // Update file icon
        const iconElement = document.getElementById('pwdProofIcon').querySelector('i');
        iconElement.className = getFileIcon(fileExtension);
        
        // Update download link
        const downloadBtn = document.getElementById('downloadPwdProofBtn');
        downloadBtn.href = resolveImageUrl(filePath);
        downloadBtn.download = fileName;
        
        // Show file display and hide placeholder
        placeholder.style.display = 'none';
        fileDisplay.style.display = 'flex';
        
        // Add event listeners
        setupPWDProofEventListeners(filePath, fileExtension);
    }

    // Function to get appropriate file icon
    function getFileIcon(extension) {
        const iconMap = {
            'pdf': 'fas fa-file-pdf fa-3x text-danger',
            'doc': 'fas fa-file-word fa-3x text-primary',
            'docx': 'fas fa-file-word fa-3x text-primary',
            'jpg': 'fas fa-file-image fa-3x text-success',
            'jpeg': 'fas fa-file-image fa-3x text-success',
            'png': 'fas fa-file-image fa-3x text-success',
            'gif': 'fas fa-file-image fa-3x text-success',
            'xls': 'fas fa-file-excel fa-3x text-success',
            'xlsx': 'fas fa-file-excel fa-3x text-success'
        };
        return iconMap[extension] || 'fas fa-file-alt fa-3x text-muted';
    }

    // Function to setup PWD proof event listeners
    function setupPWDProofEventListeners(filePath, fileExtension) {
        const viewBtn = document.getElementById('viewPwdProofBtn');
        const downloadBtn = document.getElementById('downloadPwdProofBtn');
        
        // Remove existing event listeners
        viewBtn.replaceWith(viewBtn.cloneNode(true));
        downloadBtn.replaceWith(downloadBtn.cloneNode(true));
        
        // Get new references
        const newViewBtn = document.getElementById('viewPwdProofBtn');
        const newDownloadBtn = document.getElementById('downloadPwdProofBtn');
        
        // Add view event listener
        newViewBtn.addEventListener('click', function() {
            previewPWDProofFile(filePath, fileExtension);
        });
        
        // Add download event listener
        newDownloadBtn.addEventListener('click', function() {
            SecureLogger.info('PWD Proof file download initiated:', filePath);
        });
    }

    // Function to preview PWD proof file
    function previewPWDProofFile(filePath, fileExtension) {
        const modalContent = document.getElementById('pwdProofPreviewContent');
        modalContent.innerHTML = '';
        
        const fullPath = resolveImageUrl(filePath);
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Image preview
            const img = document.createElement('img');
            img.src = fullPath;
            img.alt = 'PWD Proof Image';
            img.onerror = function() {
                modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading image. File may not exist or be corrupted.</div>';
            };
            modalContent.appendChild(img);
        } else if (fileExtension === 'pdf') {
            // PDF preview
            const embed = document.createElement('embed');
            embed.src = fullPath;
            embed.type = 'application/pdf';
            embed.onerror = function() {
                modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading PDF. File may not exist or be corrupted.</div>';
            };
            modalContent.appendChild(embed);
        } else {
            // Other file types - show download link
            const downloadLink = document.createElement('a');
            downloadLink.href = fullPath;
            downloadLink.className = 'btn btn-primary btn-lg';
            downloadLink.download = filePath.split('/').pop();
            downloadLink.innerHTML = '<i class="fas fa-download me-2"></i>Download File';
            modalContent.appendChild(downloadLink);
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('pwdProofPreviewModal'));
        modal.show();
    }

    function formatDate(dateString) {
        if (!dateString) return 'Not specified';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Not specified';
            
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            console.error('Date formatting error:', e);
            return 'Not specified';
        }
    }

    function showError(message) {
        console.error(message);
        alert(message);
    }


    // Update user activity status periodically
    function updateUserActivity() {
        fetch('/UGCSystem/includes/update_user_status.php', {
            method: 'POST',
            credentials: 'include'
        });
    }

    // Update activity status every 4 minutes
    setInterval(updateUserActivity, 240000);

    // Update user list more frequently to show real-time status
    setInterval(fetchUsers, 10000); // Update every 10 seconds

    // Update status filter options
    if (statusFilter) {
        statusFilter.innerHTML = `
            <option value="all">All Student Users</option>
            <option value="active">Active Student Users</option>
            <option value="inactive">Inactive Student Users</option>
        `;
    }
}); 