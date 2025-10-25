// Function to resolve image URL (helper function)
function resolveImageUrl(path) {
    if (!path) return (window.BASE_URL || '/') + 'Photos/profile.png';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) return (window.BASE_URL || '/') + path.substring(1);
    return (window.BASE_URL || '/') + path;
}


// Function to validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Function to validate course and year format
function validateCourseYear(courseYear) {
    const re = /^[A-Za-z\s]+-\s*\d+(st|nd|rd|th)\s*Year$/;
    return re.test(courseYear);
}

// Function to save profile changes
function saveProfileChanges() {
    // Get the values from the modal inputs
    const newUsername = document.getElementById('update-username').value.trim();
    const newEmail = document.getElementById('update-email').value.trim();

    console.log('Saving profile changes:', { newUsername, newEmail });

    // Validate inputs
    if (!newUsername) {
        openAlertModal('Please enter a username', 'warning');
        return;
    }

    if (!newEmail) {
        openAlertModal('Please enter an email address', 'warning');
        return;
    }

    if (!validateEmail(newEmail)) {
        openAlertModal('Please enter a valid email address', 'warning');
        return;
    }


    // Create a FormData object to send the data
    const formData = new FormData();
    formData.append('username', newUsername);
    formData.append('email', newEmail);

    // Send the data to the server
    fetch(window.BASE_URL + 'student/profile/update', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(async data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to update profile');
        }

        // If there is a selected picture, upload it next
        const fileInput = document.getElementById('update-picture');
        const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (file) {
            const picForm = new FormData();
            picForm.append('profile_picture', file);
            const resp = await fetch(window.BASE_URL + 'student/profile/picture', {
                method: 'POST',
                body: picForm,
                credentials: 'include'
            });
            const picData = await resp.json();
            if (!picData.success) {
                throw new Error(picData.message || 'Failed to upload picture');
            }
            // Update on-page avatar
            const imgEl = document.getElementById('profile-img');
            if (imgEl && picData.picture_url) {
                const newUrl = resolveImageUrl(picData.picture_url) + '?t=' + Date.now();
                imgEl.src = newUrl;
                try { localStorage.setItem('student_profile_picture', newUrl); } catch (e) {}
            }
        }

        // Update the display values
        document.getElementById('display-username').textContent = newUsername;
        document.getElementById('display-email').textContent = newEmail;    

        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('updateProfileModal'));
        modal.hide();

        // Show success message
        openAlertModal('Profile updated successfully!', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        openAlertModal('Failed to update profile. Please try again later.', 'error');
    });
}

// Function to load current profile data
function loadProfileData() {
    console.log('Loading profile data...');
    
    // Show loading state
    document.querySelectorAll('.form-value').forEach(el => {
        el.textContent = 'Loading...';
    });

    fetch(window.BASE_URL + 'student/profile/get', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                // Session expired or unauthorized
                window.location.href = window.BASE_URL + 'student/dashboard';
                return;
            }
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Profile data:', data);
        if (data.success) {
            // Update display values
            document.getElementById('display-userid').textContent = data.user_id || 'N/A';
            document.getElementById('display-username').textContent = data.username || 'N/A';
            document.getElementById('display-email').textContent = data.email || 'N/A';
            document.getElementById('personalEmail').value = data.email || 'N/A';

            
            // Update profile picture if available
            if (data.profile_picture) {
                const imgEl = document.getElementById('profile-img');
                if (imgEl) {
                    const newUrl = resolveImageUrl(data.profile_picture) + '?t=' + Date.now();
                    imgEl.src = newUrl;
                    try { localStorage.setItem('student_profile_picture', newUrl); } catch (e) {}
                }
            }

            // Update modal input values
            document.getElementById('update-username').value = data.username || '';
            document.getElementById('update-email').value = data.email || '';

        } else {
            throw new Error(data.message || 'Failed to load profile data');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error state in the UI
        document.querySelectorAll('.form-value').forEach(el => {
            el.textContent = 'Error loading data';
        });
        
        if (error.message === 'User not logged in') {
            setTimeout(() => {
                window.location.href = window.BASE_URL + 'student/dashboard';
            }, 1500);
        } else {
            openAlertModal('Failed to load profile data. Please try again later.', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const navbarDrawerToggler = document.getElementById('navbarDrawerToggler');
    const navbarDrawer = document.getElementById('navbarDrawer');
    const navbarDrawerClose = document.getElementById('navbarDrawerClose');
    const navbarOverlay = document.getElementById('navbarOverlay');

    console.log("DOM loaded, setting up profile functionality");
    
    // Load profile data when page loads
    loadProfileData();
    
    // Load PDS data when page loads
    loadPDSData();
    
    // Initialize conditional logic
    handleCivilStatusChange();
    handlePWDChange();
    
    // Add image preview functionality
    const pictureInput = document.getElementById('update-picture');
    if (pictureInput) {
        pictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('update-picture-preview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Drawer toggle bindings (match landing behavior)
    

    // Drawer open/close behavior (always enabled)
    function openDrawer() {
        if (navbarDrawer) navbarDrawer.classList.add('show');
        if (navbarOverlay) navbarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        if (navbarDrawerToggler) navbarDrawerToggler.classList.add('active');
    }

    if (navbarDrawerToggler) {
        navbarDrawerToggler.addEventListener('click', openDrawer);
    }

    function closeDrawer() {
        if (navbarDrawer) navbarDrawer.classList.remove('show');
        if (navbarOverlay) navbarOverlay.classList.remove('show');
        document.body.style.overflow = '';
        if (navbarDrawerToggler) navbarDrawerToggler.classList.remove('active');
    }
      
      
    if (navbarDrawerClose) {
        navbarDrawerClose.addEventListener('click', closeDrawer);
    }
    
    if (navbarOverlay) {
        navbarOverlay.addEventListener('click', closeDrawer);
    }

    

    // One-shot click animation for drawer items
    document.querySelectorAll('#navbarDrawer .nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            link.classList.remove('drawer-item-click');
            void link.offsetWidth;
            link.classList.add('drawer-item-click');
        });
    });

    // Initialize PDS inputs as disabled (except tab togglers)
    function setPdsEnabled(enabled) {
        const container = document.querySelector('.pds-container');
        if (!container) return;
        const interactiveSelectors = [
            'input', 'select', 'textarea', 'button.form-check-input'
        ];
        container.querySelectorAll(interactiveSelectors.join(',')).forEach(function(el){
            if (el.hasAttribute('data-bs-toggle')) return;
            if (el.closest('.nav-tabs')) return;
            if (el.id === 'pdsEditToggleBtn' || el.id === 'pdsSaveBtn') return;
            el.disabled = !enabled;
        });
        const saveBtn = document.getElementById('pdsSaveBtn');
        if (saveBtn) saveBtn.disabled = !enabled;
        const editBtn = document.getElementById('pdsEditToggleBtn');
        if (editBtn) {
            if (enabled) {
                editBtn.setAttribute('aria-pressed', 'true');
                editBtn.innerHTML = '<i class="fas fa-unlock"></i> Disable Editing';
            } else {
                editBtn.setAttribute('aria-pressed', 'false');
                editBtn.innerHTML = '<i class="fas fa-lock"></i> Enable Editing';
            }
        }
    }

    // disabled by default
    setPdsEnabled(false);

    const editBtn = document.getElementById('pdsEditToggleBtn');
    if (editBtn) {
        editBtn.addEventListener('click', function(){
            const isEnabled = editBtn.getAttribute('aria-pressed') === 'true';
            setPdsEnabled(!isEnabled);
        });
    }

    const saveBtn = document.getElementById('pdsSaveBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function(){
            const payload = new FormData();
            const getVal = id => {
                const el = document.getElementById(id);
                return el ? el.value : '';
            };
            payload.append('course', getVal('courseSelect'));
            payload.append('yearLevel', getVal('yearSelect'));
            payload.append('academicStatus', getVal('academicStatusSelect'));
            payload.append('lastName', getVal('lastName'));
            payload.append('firstName', getVal('firstName'));
            payload.append('middleName', getVal('middleName'));
            payload.append('dateOfBirth', getVal('dateOfBirth'));
            payload.append('age', getVal('age'));
            payload.append('sex', (document.getElementById('sexSelect')||{}).value || '');
            payload.append('civilStatus', (document.getElementById('civilStatusSelect')||{}).value || '');
            payload.append('contactNumber', getVal('contactNumber'));
            payload.append('fbAccountName', getVal('fbAccountName'));
            payload.append('permanentZone', getVal('permanentAddressZone'));
            payload.append('permanentBarangay', getVal('permanentAddressBarangay'));
            payload.append('permanentCity', getVal('permanentAddressCity'));
            payload.append('permanentProvince', getVal('permanentAddressProvince'));
            payload.append('presentZone', getVal('presentAddressZone'));
            payload.append('presentBarangay', getVal('presentAddressBarangay'));
            payload.append('presentCity', getVal('presentAddressCity'));
            payload.append('presentProvince', getVal('presentAddressProvince'));
            payload.append('fatherName', getVal('fatherName'));
            payload.append('fatherOccupation', getVal('fatherOccupation'));
            payload.append('motherName', getVal('motherName'));
            payload.append('motherOccupation', getVal('motherOccupation'));
            payload.append('spouse', getVal('spouse'));
            payload.append('guardianContactNumber', getVal('guardianContactNumber'));

            const getRadio = name => {
                const el = document.querySelector(`input[name="${name}"]:checked`);
                return el ? el.value : '';
            };
            payload.append('soloParent', getRadio('soloParent'));
            payload.append('indigenous', getRadio('indigenous'));
            payload.append('breastFeeding', getRadio('breastFeeding'));
            payload.append('pwd', getRadio('pwd'));
            payload.append('pwdSpecify', getVal('pwdSpecify'));
            payload.append('residence', getRadio('residence'));
            payload.append('resOtherText', getVal('resOtherText'));
            payload.append('consentAgree', (document.getElementById('consentAgree')||{}).checked ? '1' : '0');
            
            // Add PWD proof file if selected
            const pwdProofFile = document.getElementById('pwdProof');
            if (pwdProofFile && pwdProofFile.files && pwdProofFile.files[0]) {
                payload.append('pwdProof', pwdProofFile.files[0]);
            }

            // Handle services needed
            const servicesNeeded = [];
            const serviceCheckboxes = [
                { id: 'svcCounseling', type: 'counseling' },
                { id: 'svcInsurance', type: 'insurance' },
                { id: 'svcSpecialLanes', type: 'special_lanes' },
                { id: 'svcSafeLearning', type: 'safe_learning' },
                { id: 'svcEqualAccess', type: 'equal_access' }
            ];
            
            serviceCheckboxes.forEach(service => {
                const checkbox = document.getElementById(service.id);
                if (checkbox && checkbox.checked) {
                    servicesNeeded.push({ type: service.type, other: null });
                }
            });
            
            const svcOther = getVal('svcOther');
            if (svcOther) {
                servicesNeeded.push({ type: 'other', other: svcOther });
            }
            
            payload.append('services_needed', JSON.stringify(servicesNeeded));

            // Handle services availed
            const servicesAvailed = [];
            const availedCheckboxes = [
                { id: 'availedCounseling', type: 'counseling' },
                { id: 'availedInsurance', type: 'insurance' },
                { id: 'availedSpecialLanes', type: 'special_lanes' },
                { id: 'availedSafeLearning', type: 'safe_learning' },
                { id: 'availedEqualAccess', type: 'equal_access' }
            ];
            
            availedCheckboxes.forEach(service => {
                const checkbox = document.getElementById(service.id);
                if (checkbox && checkbox.checked) {
                    servicesAvailed.push({ type: service.type, other: null });
                }
            });
            
            const availedOther = getVal('availedOther');
            if (availedOther) {
                servicesAvailed.push({ type: 'other', other: availedOther });
            }
            
            payload.append('services_availed', JSON.stringify(servicesAvailed));

            // Debug: Log the data being sent
            console.log('PDS Save - Sending data:');
            for (let [key, value] of payload.entries()) {
                console.log(key + ':', value);
            }

            fetch((window.BASE_URL||'') + 'student/pds/save', {
                method: 'POST',
                body: payload,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    openAlertModal('Personal Data Sheet saved successfully!', 'success');
                    setPdsEnabled(false);
                } else {
                    openAlertModal((data && data.message) || 'Failed to save Personal Data Sheet', 'error');
                }
            })
            .catch(() => {
                openAlertModal('Failed to save Personal Data Sheet. Please try again later.', 'error');
            });
        });
    }
});

// Function to load PDS data
function loadPDSData() {
    console.log('Loading PDS data...');
    
    fetch(window.BASE_URL + 'student/pds/load', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('PDS data:', data);
        if (data.success && data.data) {
            populatePDSForm(data.data);
        } else {
            console.log('No PDS data found or failed to load');
        }
    })
    .catch(error => {
        console.error('Error loading PDS data:', error);
    });
}

// Function to populate PDS form with data
function populatePDSForm(pdsData) {
    // Debug: Log the received PDS data
    console.log('PDS Data received:', pdsData);
    console.log('User email:', pdsData.user_email);
    
    // Academic Information
    if (pdsData.academic) {
        setValue('courseSelect', pdsData.academic.course);
        setValue('yearSelect', pdsData.academic.year_level);
        setValue('academicStatusSelect', pdsData.academic.academic_status);
    }

    // Personal Information
    if (pdsData.personal) {
        setValue('lastName', pdsData.personal.last_name);
        setValue('firstName', pdsData.personal.first_name);
        setValue('middleName', pdsData.personal.middle_name);
        setValue('dateOfBirth', pdsData.personal.date_of_birth);
        setValue('age', pdsData.personal.age);
        setValue('sexSelect', pdsData.personal.sex);
        setValue('civilStatusSelect', pdsData.personal.civil_status);
        setValue('contactNumber', pdsData.personal.contact_number);
        setValue('fbAccountName', pdsData.personal.fb_account_name);
    }

    // Address Information
    if (pdsData.address) {
        setValue('permanentAddressZone', pdsData.address.permanent_zone);
        setValue('permanentAddressBarangay', pdsData.address.permanent_barangay);
        setValue('permanentAddressCity', pdsData.address.permanent_city);
        setValue('permanentAddressProvince', pdsData.address.permanent_province);
        setValue('presentAddressZone', pdsData.address.present_zone);
        setValue('presentAddressBarangay', pdsData.address.present_barangay);
        setValue('presentAddressCity', pdsData.address.present_city);
        setValue('presentAddressProvince', pdsData.address.present_province);
    }

    // Family Information
    if (pdsData.family) {
        setValue('fatherName', pdsData.family.father_name);
        setValue('fatherOccupation', pdsData.family.father_occupation);
        setValue('motherName', pdsData.family.mother_name);
        setValue('motherOccupation', pdsData.family.mother_occupation);
        setValue('spouse', pdsData.family.spouse);
        setValue('guardianContactNumber', pdsData.family.guardian_contact_number);
    }

    // Special Circumstances
    if (pdsData.circumstances) {
        setRadioValue('soloParent', pdsData.circumstances.is_solo_parent);
        setRadioValue('indigenous', pdsData.circumstances.is_indigenous);
        setRadioValue('breastFeeding', pdsData.circumstances.is_breastfeeding);
        setRadioValue('pwd', pdsData.circumstances.is_pwd);
        setValue('pwdSpecify', pdsData.circumstances.pwd_disability_type);
    }

    // Services Needed
    if (pdsData.services_needed && Array.isArray(pdsData.services_needed)) {
        populateServicesCheckboxes(pdsData.services_needed, 'svc');
    }

    // Services Availed
    if (pdsData.services_availed && Array.isArray(pdsData.services_availed)) {
        populateServicesCheckboxes(pdsData.services_availed, 'availed');
    }

    // Residence Information
    if (pdsData.residence) {
        setRadioValue('residence', pdsData.residence.residence_type);
        setValue('resOtherText', pdsData.residence.residence_other_specify);
        setCheckboxValue('consentAgree', pdsData.residence.has_consent);
    }

    // Handle PWD proof preview and display
    if (pdsData.circumstances && pdsData.circumstances.pwd_proof_file && pdsData.circumstances.pwd_proof_file !== 'N/A') {
        showPwdProofPreview(pdsData.circumstances.pwd_proof_file);
        showPwdProofDisplayBox(pdsData.circumstances.pwd_proof_file);
        console.log('PWD Proof file found on page load:', pdsData.circumstances.pwd_proof_file);
    } else {
        console.log('No PWD Proof file found on page load');
    }

    // Trigger conditional logic after populating
    handleCivilStatusChange();
    handlePWDChange();
}

// Function to handle civil status change (show/hide spouse field)
function handleCivilStatusChange() {
    const civilStatusSelect = document.getElementById('civilStatusSelect');
    const spouseField = document.getElementById('spouse');
    const spouseLabel = document.querySelector('label[for="spouse"]');
    const spouseContainer = spouseField ? spouseField.closest('.col-md-8') : null;
    
    console.log('Civil Status Elements:', {
        civilStatusSelect: !!civilStatusSelect,
        spouseField: !!spouseField,
        spouseLabel: !!spouseLabel,
        spouseContainer: !!spouseContainer
    });
    
    if (civilStatusSelect && spouseField && spouseLabel && spouseContainer) {
        const toggleSpouseField = () => {
            const isMarried = civilStatusSelect.value === 'Married';
            console.log('Civil Status Changed:', civilStatusSelect.value, 'Is Married:', isMarried);
            
            // Show/hide spouse field and label
            if (isMarried) {
                spouseField.style.display = 'block';
                spouseLabel.style.display = 'block';
                spouseContainer.style.display = 'block';
                console.log('Spouse field shown');
            } else {
                spouseField.style.display = 'none';
                spouseLabel.style.display = 'none';
                spouseContainer.style.display = 'none';
                spouseField.value = 'N/A';
                console.log('Spouse field hidden, value set to N/A');
            }
        };
        
        // Set initial state
        toggleSpouseField();
        
        // Add event listener
        civilStatusSelect.addEventListener('change', toggleSpouseField);
    } else {
        console.error('Civil Status elements not found:', {
            civilStatusSelect: !!civilStatusSelect,
            spouseField: !!spouseField,
            spouseLabel: !!spouseLabel,
            spouseContainer: !!spouseContainer
        });
    }
}

// Function to handle PWD change (show/hide PWD fields)
function handlePWDChange() {
    const pwdRadios = document.querySelectorAll('input[name="pwd"]');
    const pwdSpecifyField = document.getElementById('pwdSpecify');
    const pwdProofField = document.getElementById('pwdProof');
    const pwdSpecifyLabel = document.querySelector('label[for="pwdSpecify"]');
    const pwdProofLabel = document.querySelector('label[for="pwdProof"]');
    
    if (pwdRadios.length > 0 && pwdSpecifyField && pwdProofField) {
        const togglePWDFields = () => {
            const selectedPWD = document.querySelector('input[name="pwd"]:checked');
            const isPWD = selectedPWD && (selectedPWD.value === 'Yes' || selectedPWD.value === 'Other');
            
            // Show/hide PWD fields
            if (isPWD) {
                pwdSpecifyField.style.display = 'block';
                pwdProofField.style.display = 'block';
                if (pwdSpecifyLabel) pwdSpecifyLabel.style.display = 'block';
                if (pwdProofLabel) pwdProofLabel.style.display = 'block';
                pwdSpecifyField.closest('.col-md-12').style.display = 'block';
                pwdProofField.closest('.col-md-12').style.display = 'block';
            } else {
                pwdSpecifyField.style.display = 'none';
                pwdProofField.style.display = 'none';
                if (pwdSpecifyLabel) pwdSpecifyLabel.style.display = 'none';
                if (pwdProofLabel) pwdProofLabel.style.display = 'none';
                pwdSpecifyField.closest('.col-md-12').style.display = 'none';
                pwdProofField.closest('.col-md-12').style.display = 'none';
                pwdSpecifyField.value = 'N/A';
                pwdProofField.value = '';
            }
        };
        
        // Set initial state
        togglePWDFields();
        
        // Add event listeners
        pwdRadios.forEach(radio => {
            radio.addEventListener('change', togglePWDFields);
        });
    }
}

// Helper function to set input values
function setValue(id, value) {
    const element = document.getElementById(id);
    console.log(`setValue called for ${id}:`, value, 'Element found:', !!element);
    if (element && value && value !== 'N/A') {
        element.value = value;
        console.log(`Value set for ${id}:`, element.value);
    } else if (element && id === 'personalEmail') {
        // Special case for personalEmail - always set the value even if empty
        element.value = value || '';
        console.log(`PersonalEmail value set to:`, element.value);
    }
}

// Helper function to set radio button values
function setRadioValue(name, value) {
    const element = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (element) {
        element.checked = true;
    }
}

// Helper function to set checkbox values
function setCheckboxValue(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.checked = value === 1 || value === true || value === '1';
    }
}

// Helper function to populate services checkboxes
function populateServicesCheckboxes(services, prefix) {
    const serviceMap = {
        'counseling': 'Counseling',
        'insurance': 'Insurance',
        'special_lanes': 'SpecialLanes',
        'safe_learning': 'SafeLearning',
        'equal_access': 'EqualAccess'
    };

    services.forEach(service => {
        if (service.type === 'other') {
            setValue(prefix + 'Other', service.other);
        } else {
            const checkboxId = prefix + serviceMap[service.type];
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
}

// Function to toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    // The DOM structure is: <div class="password-input-group"><input><i class="toggle-password"></i></div>
    // So the correct container is the parent element, not nextElementSibling.
    const container = input ? input.parentElement : null;

    if (!input || !container) return;

    // Find whatever element is currently acting as the toggle (either <i> or <img>)
    let toggleEl = container.querySelector('.toggle-password');

    if (input.type === 'password') {
        // We are about to SHOW the password. Keep the "hide" icon as Photos/eye.png
        input.type = 'text';

        // If it's already an <img>, just swap src; otherwise replace the <i> with <img>
        if (toggleEl && toggleEl.tagName.toLowerCase() === 'img') {
            toggleEl.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
            toggleEl.alt = 'Hide password';
        } else if (toggleEl) {
            const img = document.createElement('img');
            img.src = (window.BASE_URL || '/') + 'Photos/close_eye.png';
            img.alt = 'Hide password';
            img.className = 'toggle-password custom-hide-icon';
            img.style.width = '30px';
            img.style.height = '30px';
            img.style.cursor = 'pointer';
            img.onclick = () => togglePassword(inputId);
            toggleEl.replaceWith(img);
        }
    } else {
        // We are about to HIDE the password. Restore the Font Awesome eye (show icon)
        input.type = 'password';

        if (toggleEl && toggleEl.tagName.toLowerCase() === 'img') {
            const icon = document.createElement('i');
            icon.className = 'fas fa-eye toggle-password';
            icon.style.cursor = 'pointer';
            icon.onclick = () => togglePassword(inputId);
            toggleEl.replaceWith(icon);
        } else if (toggleEl) {
            // Ensure proper classes if an <i> already exists
            toggleEl.classList.remove('fa-eye-slash');
            toggleEl.classList.add('fa-eye');
        }
    }
}

// Function to change password
function changePassword() {
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    // Validate inputs
    if (!currentPassword || !newPassword || !confirmPassword) {
        openAlertModal('Please fill in all password fields', 'warning');
        return;
    }

    if (newPassword !== confirmPassword) {
        openAlertModal('New passwords do not match', 'warning');
        return;
    }

    if (newPassword.length < 8) {
        openAlertModal('New password must be at least 8 characters long', 'warning');
        return;
    }

    // Create FormData object
    const formData = new FormData();
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    // Send request to server
    fetch(window.BASE_URL + 'update-password', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success logic
            openAlertModal('Password updated successfully!', 'success');
            document.getElementById('changePasswordForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
        } else {
            openAlertModal(data.message || 'Failed to update password', 'error');
        }
    })
    .catch(error => {
        openAlertModal('Failed to update password. Please try again later.', 'error');
    });
}

// PWD Proof Display Box Functions
function showPwdProofDisplayBox(filePath) {
    const displayBox = document.getElementById('pwdProofDisplayBox');
    const fileContent = document.getElementById('pwdProofFileContent');
    const fileName = document.getElementById('pwdProofFileName');
    const fileSize = document.getElementById('pwdProofFileSize');
    const downloadBtn = document.getElementById('downloadPwdProofBtn');
    
    console.log('showPwdProofDisplayBox called with:', filePath);
    
    if (filePath && filePath !== 'N/A' && filePath.trim() !== '') {
        const fileNameOnly = filePath.split('/').pop();
        const fileExtension = fileNameOnly.split('.').pop().toLowerCase();
        
        // Set file name
        fileName.textContent = fileNameOnly;
        
        // Set download link
        downloadBtn.href = window.BASE_URL + filePath;
        downloadBtn.download = fileNameOnly;
        
        // Clear previous content
        fileContent.innerHTML = '';
        
        // Display file preview based on type
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Image thumbnail
            const img = document.createElement('img');
            img.src = window.BASE_URL + filePath;
            img.className = 'img-fluid rounded';
            img.style.maxHeight = '120px';
            img.style.maxWidth = '100%';
            img.style.objectFit = 'cover';
            img.alt = 'PWD Proof Image';
            img.onerror = function() {
                fileContent.innerHTML = '<div class="text-center"><i class="fas fa-image fa-3x text-muted"></i></div>';
            };
            fileContent.appendChild(img);
            fileSize.textContent = 'Image File';
        } else if (fileExtension === 'pdf') {
            // PDF icon
            fileContent.innerHTML = '<div class="text-center"><i class="fas fa-file-pdf fa-3x text-danger"></i></div>';
            fileSize.textContent = 'PDF Document';
        } else if (['doc', 'docx'].includes(fileExtension)) {
            // Word document icon
            fileContent.innerHTML = '<div class="text-center"><i class="fas fa-file-word fa-3x text-primary"></i></div>';
            fileSize.textContent = 'Word Document';
        } else if (['xls', 'xlsx'].includes(fileExtension)) {
            // Excel icon
            fileContent.innerHTML = '<div class="text-center"><i class="fas fa-file-excel fa-3x text-success"></i></div>';
            fileSize.textContent = 'Excel Spreadsheet';
        } else {
            // Generic file icon
            fileContent.innerHTML = '<div class="text-center"><i class="fas fa-file fa-3x text-muted"></i></div>';
            fileSize.textContent = 'Document File';
        }
        
        // Show the display box
        displayBox.style.display = 'block';
        
        console.log('PWD Proof display box shown for file:', fileNameOnly);
    } else {
        displayBox.style.display = 'none';
        console.log('PWD Proof display box hidden - no valid file path');
    }
}

// PWD Proof Preview Functions
function showPwdProofPreview(filePath) {
    const previewButton = document.getElementById('previewPwdProof');
    const previewDiv = document.getElementById('pwdProofPreview');
    const fileNameSpan = document.getElementById('currentPwdProofName');
    
    console.log('showPwdProofPreview called with:', filePath);
    
    if (filePath && filePath !== 'N/A' && filePath.trim() !== '') {
        const fileName = filePath.split('/').pop();
        fileNameSpan.textContent = fileName;
        previewDiv.style.display = 'block';
        previewButton.style.display = 'inline-block';
        
        // Store the file path for preview
        previewButton.setAttribute('data-file-path', filePath);
        previewButton.removeAttribute('data-new-file');
        
        console.log('PWD Proof preview button shown for file:', fileName);
    } else {
        previewDiv.style.display = 'none';
        previewButton.style.display = 'none';
        console.log('PWD Proof preview button hidden - no valid file path');
    }
}

function previewPwdProofFile() {
    const previewButton = document.getElementById('previewPwdProof');
    const filePath = previewButton.getAttribute('data-file-path');
    const isNewFile = previewButton.getAttribute('data-new-file') === 'true';
    const pwdProofInput = document.getElementById('pwdProof');
    
    const modalContent = document.getElementById('pwdProofContent');
    modalContent.innerHTML = '';
    
    if (isNewFile && pwdProofInput.files && pwdProofInput.files[0]) {
        // Preview newly selected file
        const file = pwdProofInput.files[0];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Image preview
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-fluid rounded shadow-sm';
            img.style.maxHeight = '80vh';
            img.style.maxWidth = '100%';
            img.alt = 'PWD Proof Image';
            img.style.objectFit = 'contain';
            modalContent.appendChild(img);
        } else if (fileExtension === 'pdf') {
            // PDF preview
            const embed = document.createElement('embed');
            embed.src = URL.createObjectURL(file);
            embed.type = 'application/pdf';
            embed.style.width = '100%';
            embed.style.height = '80vh';
            embed.style.border = '1px solid #dee2e6';
            embed.style.borderRadius = '0.375rem';
            modalContent.appendChild(embed);
        } else {
            // Other file types - show file info
            const fileInfo = document.createElement('div');
            fileInfo.innerHTML = `
                <p><strong>File Name:</strong> ${file.name}</p>
                <p><strong>File Size:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                <p><strong>File Type:</strong> ${file.type}</p>
                <p class="text-muted">This file type cannot be previewed. It will be uploaded when you save the form.</p>
            `;
            modalContent.appendChild(fileInfo);
        }
    } else if (filePath && filePath !== 'N/A') {
        // Preview existing file from server
        const fileExtension = filePath.split('.').pop().toLowerCase();
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            // Image preview
            const img = document.createElement('img');
            img.src = window.BASE_URL + filePath;
            img.className = 'img-fluid rounded shadow-sm';
            img.style.maxHeight = '80vh';
            img.style.maxWidth = '100%';
            img.alt = 'PWD Proof Image';
            img.style.objectFit = 'contain';
            img.onerror = function() {
                modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading image. File may not exist or be corrupted.</div>';
            };
            modalContent.appendChild(img);
        } else if (fileExtension === 'pdf') {
            // PDF preview
            const embed = document.createElement('embed');
            embed.src = window.BASE_URL + filePath;
            embed.type = 'application/pdf';
            embed.style.width = '100%';
            embed.style.height = '80vh';
            embed.style.border = '1px solid #dee2e6';
            embed.style.borderRadius = '0.375rem';
            embed.onerror = function() {
                modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading PDF. File may not exist or be corrupted.</div>';
            };
            modalContent.appendChild(embed);
        } else {
            // Other file types - show download link
            const downloadLink = document.createElement('a');
            downloadLink.href = window.BASE_URL + filePath;
            downloadLink.className = 'btn btn-primary';
            downloadLink.download = filePath.split('/').pop();
            downloadLink.innerHTML = '<i class="fas fa-download"></i> Download File';
            modalContent.appendChild(downloadLink);
        }
    } else {
        modalContent.innerHTML = '<p class="text-muted">No file to preview.</p>';
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('pwdProofModal'));
    modal.show();
}

// Event listeners for PWD proof preview
document.addEventListener('DOMContentLoaded', function() {
    const previewButton = document.getElementById('previewPwdProof');
    if (previewButton) {
        previewButton.addEventListener('click', previewPwdProofFile);
    }
    
    // Handle file input change to show preview for new files
    const pwdProofInput = document.getElementById('pwdProof');
    if (pwdProofInput) {
        pwdProofInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileName = file.name;
                const previewDiv = document.getElementById('pwdProofPreview');
                const fileNameSpan = document.getElementById('currentPwdProofName');
                const previewButton = document.getElementById('previewPwdProof');
                
                fileNameSpan.textContent = fileName + ' (new file)';
                previewDiv.style.display = 'block';
                previewButton.style.display = 'inline-block';
                
                // For new files, we'll preview the selected file directly
                previewButton.setAttribute('data-file-path', '');
                previewButton.setAttribute('data-new-file', 'true');
            }
        });
    }
    
    // Initialize preview button responsiveness
    initializePreviewResponsiveness();
    
    // Initialize PWD proof display box event listeners
    initializePwdProofDisplayBox();
});

// Function to handle responsive behavior for preview button
function initializePreviewResponsiveness() {
    const previewButton = document.getElementById('previewPwdProof');
    if (!previewButton) return;
    
    // Handle window resize to adjust button text display
    window.addEventListener('resize', function() {
        const isMediumScreen = window.innerWidth < 768; // md breakpoint
        const buttonText = previewButton.querySelector('span');
        
        if (buttonText) {
            if (isMediumScreen) {
                buttonText.classList.add('d-none');
            } else {
                buttonText.classList.remove('d-none');
            }
        }
    });
    
    // Initial check
    const isMediumScreen = window.innerWidth < 768;
    const buttonText = previewButton.querySelector('span');
    
    if (buttonText && isMediumScreen) {
        buttonText.classList.add('d-none');
    }
}

// Function to initialize PWD proof display box event listeners
function initializePwdProofDisplayBox() {
    const viewBtn = document.getElementById('viewPwdProofBtn');
    const downloadBtn = document.getElementById('downloadPwdProofBtn');
    
    if (viewBtn) {
        viewBtn.addEventListener('click', function() {
            // Use the same preview function as the preview button
            previewPwdProofFile();
        });
    }
    
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            // Let the default download behavior handle the file download
            console.log('PWD Proof file download initiated');
        });
    }
}

