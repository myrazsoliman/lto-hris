<?php
$pageTitle = 'Personal Data Sheet (CSC Form No. 212)';
$activePage = 'pds.php';
require_once 'includes/auth.php';
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);
require_once 'includes/header.php';

// Get current employee ID (for demo purposes, using session or parameter)
$employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 1;
$currentYear = date('Y');

// Check if PDS exists for current year
$pdsExists = false; // This would be checked from database
?>
<section class="card pds-container">
    <div class="pds-header">
        <div class="pds-header-badge">PDS</div>
        <div>
            <h1>PERSONAL DATA SHEET</h1>
            <p>CS Form No. 212 (Revised 2017) • <?php echo (int) $currentYear; ?></p>
            <p class="pds-header-note">Print legibly. Tick appropriate boxes and indicate N/A if not applicable. Do not abbreviate.</p>
            <div class="pds-header-meta" aria-label="PDS metadata">
                <span class="pds-meta-pill"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Filing year: <?php echo (int) $currentYear; ?></span>
                <span class="pds-meta-pill"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Employee ID: <?php echo (int) $employeeId; ?></span>
                <span class="pds-meta-pill"><i class="fa-solid fa-asterisk" aria-hidden="true"></i> Required fields marked *</span>
            </div>
        </div>
    </div>
    <div class="pds-warning-bar">
        <strong>WARNING:</strong>
        <span>Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.</span>
    </div>
    <div class="pds-document-note">
        <div class="pds-document-note-title">Official form guide</div>
        <p>Read the attached guide to filling out the Personal Data Sheet before accomplishing the form. This page mirrors the CSC Form 212 structure so the employee can complete it as a fill-out document.</p>
    </div>
    <div class="pds-form-intro">
        <div class="form-group">
            <label>1. CS ID NO. <span class="pds-csc-note">(Do not fill up. For CSC use only)</span></label>
            <input type="text" name="cs_id_no_display" form="pdsForm" readonly placeholder="Reserved for CSC use only">
        </div>
        <div class="pds-photo-box" aria-label="Passport photo placeholder">
            <div class="pds-photo-box-title">Photo</div>
            <p>Insert 4.5 cm x 3.5 cm passport-size photograph with name tag if required by your office process.</p>
        </div>
    </div>

    <div class="pds-nav" role="tablist" aria-label="PDS pages">
        <button type="button" class="pds-nav-item active" data-page="1" id="pdsTab1" aria-controls="page1" aria-selected="true">
            <span class="pds-step-num" aria-hidden="true">1</span>
            <span class="pds-step-text"><strong>Page 1</strong><span>Personal &amp; Family</span></span>
        </button>
        <button type="button" class="pds-nav-item" data-page="2" id="pdsTab2" aria-controls="page2" aria-selected="false">
            <span class="pds-step-num" aria-hidden="true">2</span>
            <span class="pds-step-text"><strong>Page 2</strong><span>Education &amp; Eligibility</span></span>
        </button>
        <button type="button" class="pds-nav-item" data-page="3" id="pdsTab3" aria-controls="page3" aria-selected="false">
            <span class="pds-step-num" aria-hidden="true">3</span>
            <span class="pds-step-text"><strong>Page 3</strong><span>Work &amp; Training</span></span>
        </button>
        <button type="button" class="pds-nav-item" data-page="4" id="pdsTab4" aria-controls="page4" aria-selected="false">
            <span class="pds-step-num" aria-hidden="true">4</span>
            <span class="pds-step-text"><strong>Page 4</strong><span>Legal &amp; References</span></span>
        </button>
        <div class="pds-progress" aria-hidden="true"><div id="pdsProgressBar"></div></div>
    </div>

    <form id="pdsForm" method="POST" action="pds_process.php">
        <input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
        <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
        <div id="pdsAlert" class="pds-alert" role="alert" hidden>
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <div>Please complete the required fields on this page before continuing.</div>
        </div>
        
        <!-- PAGE 1: Personal & Family Background -->
        <div id="page1" class="pds-page active">
            <div class="form-section">
                <h4>I. PERSONAL INFORMATION</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">2. Surname</label>
                        <input type="text" name="surname" required>
                    </div>
                    <div class="form-group">
                        <label class="required">First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label>Name Extension (JR., SR., III)</label>
                        <input type="text" name="name_extension">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required">3. Date of Birth (mm/dd/yyyy)</label>
                        <input type="date" name="date_of_birth" required>
                    </div>
                    <div class="form-group full-width">
                        <label class="required">4. Place of Birth</label>
                        <input type="text" name="place_of_birth" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required">5. Sex</label>
                        <select name="sex" required>
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">6. Civil Status</label>
                        <select name="civil_status" required>
                            <option value="">Select...</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Common Law">Common Law</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">16. Citizenship</label>
                        <input type="text" name="citizenship" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>8. Height (m)</label>
                        <input type="number" step="0.01" name="height_m" placeholder="1.65">
                    </div>
                    <div class="form-group">
                        <label>9. Weight (kg)</label>
                        <input type="number" step="0.1" name="weight_kg" placeholder="65.5">
                    </div>
                    <div class="form-group">
                        <label>18. Blood Type</label>
                        <select name="blood_type">
                            <option value="">Select...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>7. GSIS ID No.</label>
                        <input type="text" name="gsis_id">
                    </div>
                    <div class="form-group">
                        <label>10. PAG-IBIG ID No.</label>
                        <input type="text" name="pagibig_id">
                    </div>
                    <div class="form-group">
                        <label>11. PHILHEALTH No.</label>
                        <input type="text" name="philhealth_id">
                    </div>
                    <div class="form-group">
                        <label>12. SSS No.</label>
                        <input type="text" name="sss_id">
                    </div>
                    <div class="form-group">
                        <label>13. TIN No.</label>
                        <input type="text" name="tin_id">
                    </div>
                    <div class="form-group">
                        <label>14. Agency Employee No.</label>
                        <input type="text" name="agency_employee_no">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>17. RESIDENTIAL ADDRESS</h4>
                <div class="form-group full-width">
                    <label class="required">Address</label>
                    <textarea name="residential_address" rows="2" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" name="residential_zip_code">
                    </div>
                    <div class="form-group">
                        <label>Telephone No.</label>
                        <input type="text" name="residential_telephone">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>19. PERMANENT ADDRESS</h4>
                <div class="form-group full-width">
                    <label class="required">Address</label>
                    <textarea name="permanent_address" rows="2" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" name="permanent_zip_code">
                    </div>
                    <div class="form-group">
                        <label>Telephone No.</label>
                        <input type="text" name="permanent_telephone">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>20-21. CONTACT INFORMATION</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">21. E-mail Address (if any)</label>
                        <input type="email" name="email_address" required>
                    </div>
                    <div class="form-group">
                        <label class="required">20. Mobile No.</label>
                        <input type="text" name="mobile_number" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>II. FAMILY BACKGROUND</h4>
                <h5 style="margin-top: 0; color: #666;">Spouse (If married)</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Surname</label>
                        <input type="text" name="spouse_surname">
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="spouse_first_name">
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="spouse_middle_name">
                    </div>
                    <div class="form-group">
                        <label>Name Extension</label>
                        <input type="text" name="spouse_name_extension">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Occupation</label>
                        <input type="text" name="spouse_occupation">
                    </div>
                    <div class="form-group">
                        <label>Employer/Business Name</label>
                        <input type="text" name="spouse_employer_business_name">
                    </div>
                    <div class="form-group full-width">
                        <label>Business Address</label>
                        <input type="text" name="spouse_business_address">
                    </div>
                    <div class="form-group">
                        <label>Telephone No.</label>
                        <input type="text" name="spouse_telephone">
                    </div>
                </div>

                <h5 style="color: #666;">Parents</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Father's Surname</label>
                        <input type="text" name="father_surname">
                    </div>
                    <div class="form-group">
                        <label>Father's First Name</label>
                        <input type="text" name="father_first_name">
                    </div>
                    <div class="form-group">
                        <label>Father's Middle Name</label>
                        <input type="text" name="father_middle_name">
                    </div>
                    <div class="form-group">
                        <label>Father's Name Extension</label>
                        <input type="text" name="father_name_extension">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mother's Maiden Surname</label>
                        <input type="text" name="mother_maiden_surname">
                    </div>
                    <div class="form-group">
                        <label>Mother's First Name</label>
                        <input type="text" name="mother_first_name">
                    </div>
                    <div class="form-group">
                        <label>Mother's Middle Name</label>
                        <input type="text" name="mother_middle_name">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>CHILDREN (Write names in chronological order)</h4>
                <div id="childrenList" class="dynamic-list">
                    <div class="dynamic-item">
                        <input type="text" placeholder="Child's Full Name" name="children_name[]">
                        <input type="date" placeholder="Date of Birth" name="children_birthdate[]">
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addChild()">Add Child</button>
            </div>
        </div>

        <!-- PAGE 2: Education & Eligibility -->
        <div id="page2" class="pds-page">
            <div class="form-section">
                <h4>III. EDUCATIONAL BACKGROUND</h4>
                <div id="educationList" class="dynamic-list">
                    <div class="dynamic-item">
                        <select name="education_level[]" required>
                            <option value="">Select Level...</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="College">College</option>
                            <option value="Vocational/Trade Course">Vocational/Trade Course</option>
                            <option value="Graduate Studies">Graduate Studies</option>
                        </select>
                        <input type="text" placeholder="School Name" name="school_name[]" required>
                        <input type="text" placeholder="Degree/Course" name="degree_course[]">
                        <input type="date" placeholder="From" name="education_from[]">
                        <input type="date" placeholder="To" name="education_to[]">
                        <input type="text" placeholder="Highest Level/Units Earned" name="highest_level[]">
                        <input type="number" placeholder="Year Graduated" name="year_graduated[]">
                        <input type="text" placeholder="Scholarship/Academic Honors" name="scholarship_honors[]">
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addEducation()">Add Education</button>
            </div>

            <div class="form-section">
                <h4>IV. CIVIL SERVICE ELIGIBILITY</h4>
                <div id="eligibilityList" class="dynamic-list">
                    <div class="dynamic-item">
                        <select name="career_service[]" required>
                            <option value="">Select...</option>
                            <option value="Professional">Professional</option>
                            <option value="Sub-Professional">Sub-Professional</option>
                            <option value="Bar">Bar</option>
                            <option value="Board">Board</option>
                            <option value="Others">Others</option>
                        </select>
                        <input type="text" placeholder="Rating" name="eligibility_rating[]">
                        <input type="date" placeholder="Date of Exam/Conferment" name="exam_date[]">
                        <input type="text" placeholder="Place of Exam/Conferment" name="exam_place[]">
                        <input type="text" placeholder="License Number" name="license_number[]">
                        <input type="date" placeholder="Date of Release" name="license_release_date[]">
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addEligibility()">Add Eligibility</button>
            </div>
        </div>

        <!-- PAGE 3: Work Experience & Training -->
        <div id="page3" class="pds-page">
            <div class="form-section">
                <h4>V. WORK EXPERIENCE<br><small>(Start from present/recent work proceeding backward)</small></h4>
                <div id="workExperienceList" class="dynamic-list">
                    <div class="dynamic-item">
                        <input type="date" placeholder="From" name="work_from[]" required>
                        <input type="date" placeholder="To (leave blank if present)" name="work_to[]">
                        <input type="text" placeholder="Position Title" name="position_title[]" required>
                        <input type="text" placeholder="Department/Agency/Office" name="department[]" required>
                        <input type="number" step="0.01" placeholder="Monthly Salary" name="monthly_salary[]">
                        <input type="text" placeholder="Salary Grade" name="salary_grade[]">
                        <input type="text" placeholder="Step" name="step_increment[]">
                        <select name="appointment_status[]" required>
                            <option value="">Status...</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Temporary">Temporary</option>
                            <option value="Coterminous">Coterminous</option>
                            <option value="Casual">Casual</option>
                            <option value="Substitute">Substitute</option>
                            <option value="Others">Others</option>
                        </select>
                        <select name="government_service[]" required>
                            <option value="">Gov't Service?</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addWorkExperience()">Add Work Experience</button>
            </div>

            <div class="form-section">
                <h4>VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC/NGO/PEOPLE'S ORGANIZATION</h4>
                <div id="voluntaryWorkList" class="dynamic-list">
                    <div class="dynamic-item">
                        <input type="text" placeholder="Name & Address of Organization" name="org_name_address[]" required>
                        <input type="date" placeholder="From" name="voluntary_from[]" required>
                        <input type="date" placeholder="To" name="voluntary_to[]" required>
                        <input type="number" placeholder="Number of Hours" name="voluntary_hours[]">
                        <input type="text" placeholder="Position/Nature of Work" name="voluntary_position[]" required>
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addVoluntaryWork()">Add Voluntary Work</button>
            </div>

            <div class="form-section">
                <h4>VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</h4>
                <div id="trainingList" class="dynamic-list">
                    <div class="dynamic-item">
                        <input type="text" placeholder="Title of L&D Program" name="training_title[]" required>
                        <input type="date" placeholder="From" name="training_from[]" required>
                        <input type="date" placeholder="To" name="training_to[]" required>
                        <input type="number" placeholder="Number of Hours" name="training_hours[]">
                        <select name="training_type[]" required>
                            <option value="">Type...</option>
                            <option value="Managerial">Managerial</option>
                            <option value="Supervisory">Supervisory</option>
                            <option value="Technical">Technical</option>
                            <option value="Others">Others</option>
                        </select>
                        <input type="text" placeholder="Sponsored By" name="training_sponsored[]" required>
                        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-small pds-add" onclick="addTraining()">Add Training</button>
            </div>

            <div class="form-section">
                <h4>VIII. OTHER INFORMATION</h4>
                <div class="form-group full-width">
                    <label>SPECIAL SKILLS AND HOBBIES</label>
                    <textarea name="special_skills" rows="3" placeholder="e.g., Computer programming, public speaking, photography, etc."></textarea>
                </div>
                <div class="form-group full-width">
                    <label>NON-ACADEMIC DISTINCTIONS / RECOGNITIONS</label>
                    <textarea name="non_academic_distinctions" rows="3" placeholder="e.g., Awards, citations, etc."></textarea>
                </div>
                <div class="form-group full-width">
                    <label>MEMBERSHIP IN ASSOCIATIONS / ORGANIZATIONS</label>
                    <textarea name="membership_organizations" rows="3" placeholder="e.g., Professional associations, civic organizations, etc."></textarea>
                </div>
            </div>
        </div>

        <!-- PAGE 4: Legal & References -->
        <div id="page4" class="pds-page">
            <div class="form-section">
                <h4>ANSWER THE FOLLOWING QUESTIONS</h4>
                
                <div class="question-group">
                    <label>34. Are you related by consanguinity or affinity to the appointing authority or to any public official working in the same agency where you will be assigned?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q34_related" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q34_related" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q34_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>35. Have you ever been found guilty of any administrative offense?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q35_guilty" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q35_guilty" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q35_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>36. Have you ever been criminally charged in any court?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q36_charged" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q36_charged" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q36_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>37. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q37_convicted" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q37_convicted" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q37_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>38. Have you ever been separated from the service in any of the following modes: resignation, abandonment, failure to rejoin, non-reinstatement after expiration of term, dismissal, termination, drop from the rolls, retirement in absentia, or from contractual job due to unsatisfactory performance?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q38_separated" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q38_separated" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q38_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>39. Have you ever been a citizen or a holder of dual citizenship of any foreign country?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q39_immigrant" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q39_immigrant" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q39_details" rows="2" placeholder="If Yes, give details..."></textarea>
                </div>

                <div class="question-group">
                    <label>40. Are you a member of any indigenous group?</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="q40_indigenous" value="Yes" required>
                            <label>Yes</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="q40_indigenous" value="No" required>
                            <label>No</label>
                        </div>
                    </div>
                    <textarea name="q40_details" rows="2" placeholder="If Yes, specify..."></textarea>
                </div>
            </div>

            <div class="form-section">
                <h4>REFERENCES (Not related by blood or marriage)</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Reference 1 - Name</label>
                        <input type="text" name="ref1_name" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 1 - Address</label>
                        <input type="text" name="ref1_address" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 1 - Telephone</label>
                        <input type="text" name="ref1_tel">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Reference 2 - Name</label>
                        <input type="text" name="ref2_name" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 2 - Address</label>
                        <input type="text" name="ref2_address" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 2 - Telephone</label>
                        <input type="text" name="ref2_tel">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Reference 3 - Name</label>
                        <input type="text" name="ref3_name" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 3 - Address</label>
                        <input type="text" name="ref3_address" required>
                    </div>
                    <div class="form-group">
                        <label>Reference 3 - Telephone</label>
                        <input type="text" name="ref3_tel">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4>GOVERNMENT ISSUED ID</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>ID Type (e.g., Passport, Driver's License)</label>
                        <input type="text" name="id_type" required>
                    </div>
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" name="id_number" required>
                    </div>
                    <div class="form-group">
                        <label>Date Issued</label>
                        <input type="date" name="id_date_issued">
                    </div>
                    <div class="form-group">
                        <label>Issuing Authority</label>
                        <input type="text" name="id_issuing_authority">
                    </div>
                </div>
            </div>

            <div class="signature-area">
                <h5>SIGNATURE AND THUMBMARK</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Signature (Please sign in the space provided)</label>
                        <div style="border: 2px solid #ccc; height: 100px; background: white; cursor: crosshair;" id="signaturePad">
                            <p style="text-align: center; line-height: 100px; color: #999;">Click to sign</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Thumbmark</label>
                        <div style="border: 2px solid #ccc; height: 100px; width: 100px; background: white; cursor: crosshair;" id="thumbmarkPad">
                            <p style="text-align: center; line-height: 100px; color: #999;">Click</p>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Signed</label>
                        <input type="date" name="date_signed" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="pds-actions">
            <div class="pds-actions-left">
                <div class="pds-step-indicator" id="pdsStepIndicator" aria-live="polite">Step 1 of 4</div>
            </div>
            <div class="pds-actions-right">
                <button type="button" class="btn btn-outline" onclick="previousPage()" id="prevBtn" style="display: none;">Previous</button>
                <button type="button" class="btn btn-primary" onclick="nextPage()" id="nextBtn">Next</button>
                <button type="button" class="btn btn-outline" onclick="saveDraft()" id="saveBtn">Save as Draft</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">Submit PDS</button>
            </div>
        </div>
    </form>
</section>

<script>
let currentPage = 1;
const totalPages = 4;

function setAlertVisible(isVisible) {
    const alertEl = document.getElementById('pdsAlert');
    if (!alertEl) return;
    alertEl.hidden = !isVisible;
}

function updateProgress() {
    const progressBar = document.getElementById('pdsProgressBar');
    if (progressBar) {
        const pct = Math.max(0, Math.min(100, Math.round((currentPage / totalPages) * 100)));
        progressBar.style.width = pct + '%';
    }

    const indicator = document.getElementById('pdsStepIndicator');
    if (indicator) {
        indicator.textContent = `Step ${currentPage} of ${totalPages}`;
    }
}

function showPage(pageNum) {
    setAlertVisible(false);

    // Hide all pages
    document.querySelectorAll('.pds-page').forEach(page => {
        page.classList.remove('active');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.pds-nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected page
    document.getElementById('page' + pageNum).classList.add('active');
    
    // Activate corresponding nav item
    document.querySelectorAll('.pds-nav-item').forEach((item, idx) => {
        const active = (idx === (pageNum - 1));
        item.classList.toggle('active', active);
        item.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    
    currentPage = pageNum;
    updateButtons();
    updateProgress();
}

function nextPage() {
    if (currentPage < totalPages) {
        if (validateCurrentPage()) {
            showPage(currentPage + 1);
        }
    }
}

function previousPage() {
    if (currentPage > 1) {
        showPage(currentPage - 1);
    }
}

function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.style.display = currentPage === 1 ? 'none' : 'inline-block';
    
    if (currentPage === totalPages) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }

    updateProgress();
}

function validateCurrentPage() {
    const currentPageElement = document.getElementById('page' + currentPage);
    const invalidField = currentPageElement ? currentPageElement.querySelector(':invalid') : null;
    if (!invalidField) {
        setAlertVisible(false);
        return true;
    }

    setAlertVisible(true);
    invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (typeof invalidField.reportValidity === 'function') {
        invalidField.reportValidity();
    } else {
        invalidField.focus();
    }
    return false;
}

function saveDraft() {
    // Show saving message
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;
    
    // Simulate save (in real implementation, this would submit to server)
    setTimeout(() => {
        saveBtn.textContent = 'Draft Saved!';
        setTimeout(() => {
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        }, 2000);
    }, 1000);
}

// Bind step navigation
document.querySelectorAll('.pds-nav-item[data-page]').forEach(btn => {
    btn.addEventListener('click', () => {
        const pageNum = Number(btn.getAttribute('data-page') || '0');
        if (!pageNum || pageNum === currentPage) return;
        if (pageNum > currentPage && !validateCurrentPage()) return;
        showPage(pageNum);
    });
});

// Dynamic list functions
function removeDynamicItem(button) {
    if (confirm('Remove this item?')) {
        button.parentElement.remove();
    }
}

function addChild() {
    const container = document.getElementById('childrenList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <input type="text" placeholder="Child's Full Name" name="children_name[]">
        <input type="date" placeholder="Date of Birth" name="children_birthdate[]">
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

function addEducation() {
    const container = document.getElementById('educationList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <select name="education_level[]" required>
            <option value="">Select Level...</option>
            <option value="Elementary">Elementary</option>
            <option value="High School">High School</option>
            <option value="College">College</option>
            <option value="Vocational/Trade Course">Vocational/Trade Course</option>
            <option value="Graduate Studies">Graduate Studies</option>
        </select>
        <input type="text" placeholder="School Name" name="school_name[]" required>
        <input type="text" placeholder="Degree/Course" name="degree_course[]">
        <input type="date" placeholder="From" name="education_from[]">
        <input type="date" placeholder="To" name="education_to[]">
        <input type="text" placeholder="Highest Level/Units Earned" name="highest_level[]">
        <input type="number" placeholder="Year Graduated" name="year_graduated[]">
        <input type="text" placeholder="Scholarship/Academic Honors" name="scholarship_honors[]">
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

function addEligibility() {
    const container = document.getElementById('eligibilityList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <select name="career_service[]" required>
            <option value="">Select...</option>
            <option value="Professional">Professional</option>
            <option value="Sub-Professional">Sub-Professional</option>
            <option value="Bar">Bar</option>
            <option value="Board">Board</option>
            <option value="Others">Others</option>
        </select>
        <input type="text" placeholder="Rating" name="eligibility_rating[]">
        <input type="date" placeholder="Date of Exam/Conferment" name="exam_date[]">
        <input type="text" placeholder="Place of Exam/Conferment" name="exam_place[]">
        <input type="text" placeholder="License Number" name="license_number[]">
        <input type="date" placeholder="Date of Release" name="license_release_date[]">
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

function addWorkExperience() {
    const container = document.getElementById('workExperienceList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <input type="date" placeholder="From" name="work_from[]" required>
        <input type="date" placeholder="To (leave blank if present)" name="work_to[]">
        <input type="text" placeholder="Position Title" name="position_title[]" required>
        <input type="text" placeholder="Department/Agency/Office" name="department[]" required>
        <input type="number" step="0.01" placeholder="Monthly Salary" name="monthly_salary[]">
        <input type="text" placeholder="Salary Grade" name="salary_grade[]">
        <input type="text" placeholder="Step" name="step_increment[]">
        <select name="appointment_status[]" required>
            <option value="">Status...</option>
            <option value="Permanent">Permanent</option>
            <option value="Temporary">Temporary</option>
            <option value="Coterminous">Coterminous</option>
            <option value="Casual">Casual</option>
            <option value="Substitute">Substitute</option>
            <option value="Others">Others</option>
        </select>
        <select name="government_service[]" required>
            <option value="">Gov't Service?</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

function addVoluntaryWork() {
    const container = document.getElementById('voluntaryWorkList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <input type="text" placeholder="Name & Address of Organization" name="org_name_address[]" required>
        <input type="date" placeholder="From" name="voluntary_from[]" required>
        <input type="date" placeholder="To" name="voluntary_to[]" required>
        <input type="number" placeholder="Number of Hours" name="voluntary_hours[]">
        <input type="text" placeholder="Position/Nature of Work" name="voluntary_position[]" required>
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

function addTraining() {
    const container = document.getElementById('trainingList');
    const newItem = document.createElement('div');
    newItem.className = 'dynamic-item';
    newItem.innerHTML = `
        <input type="text" placeholder="Title of L&D Program" name="training_title[]" required>
        <input type="date" placeholder="From" name="training_from[]" required>
        <input type="date" placeholder="To" name="training_to[]" required>
        <input type="number" placeholder="Number of Hours" name="training_hours[]">
        <select name="training_type[]" required>
            <option value="">Type...</option>
            <option value="Managerial">Managerial</option>
            <option value="Supervisory">Supervisory</option>
            <option value="Technical">Technical</option>
            <option value="Others">Others</option>
        </select>
        <input type="text" placeholder="Sponsored By" name="training_sponsored[]" required>
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
}

// Initialize
updateButtons();
</script>

<?php require_once 'includes/footer.php'; ?>

