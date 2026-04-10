<?php
$pageTitle = 'Personal Data Sheet (CSC Form No. 212)';
$activePage = 'pds.php';
require_once 'includes/auth.php';
require_once 'includes/data.php';
require_once 'includes/pds-document-render.php';
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);

// Resolve employee ID from current user by default.
$currentUser = current_user();
$userRoles = get_user_roles($currentUser);
$isEmployeeRole = in_array('employee', $userRoles, true);
$defaultEmployeeId = $isEmployeeRole
    ? ensure_employee_record_for_user((int) ($currentUser['id'] ?? 0))
    : resolve_employee_id_for_user((int) ($currentUser['id'] ?? 0));
$requestedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
$employeeId = $isEmployeeRole
    ? (int) $defaultEmployeeId
    : ($requestedEmployeeId > 0 ? $requestedEmployeeId : (int) $defaultEmployeeId);
$currentYear = date('Y');

// Check if PDS exists for current year
$pdsRecord = get_pds_record($employeeId, $currentYear);
$pdsExists = (bool) $pdsRecord;
$pdsContext = $pdsExists ? pds_document_get_context($employeeId, $currentYear) : null;
$generatedPdfPath = $pdsExists ? ($pdsRecord['generated_pdf_path'] ?? '') : '';
$pdsStatus = $pdsExists ? ($pdsRecord['status'] ?? 'draft') : 'draft';
$submittedNow = isset($_GET['submitted']) && (int) $_GET['submitted'] === 1;
$hasViewableDocument = ($pdsExists && in_array($pdsStatus, ['submitted', 'for_review', 'approved'], true)) || $submittedNow || !empty($generatedPdfPath);
$templateRootDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'form_templates';
$templateMetaPath = $templateRootDir . DIRECTORY_SEPARATOR . 'pds_template_meta.json';
$defaultTemplatePath = 'uploads/form_templates/PH GSIS CS 212 2017-2026.pdf';
$officialTemplatePath = $defaultTemplatePath;
$officialTemplateFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $officialTemplatePath);
$officialTemplateDisplayName = 'PH GSIS CS 212 2017-2026.pdf';

if (!is_file($officialTemplateFile) && is_dir($templateRootDir)) {
    $candidates = glob($templateRootDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
    $bestPath = '';
    $bestTime = 0;
    foreach ($candidates as $candidatePath) {
        $base = strtolower((string) basename($candidatePath));
        if (strpos($base, 'saln') !== false) {
            continue;
        }
        if (!preg_match('/(212|pds|cs form|gsis)/i', $base)) {
            continue;
        }
        $mtime = (int) @filemtime($candidatePath);
        if ($mtime > $bestTime) {
            $bestTime = $mtime;
            $bestPath = $candidatePath;
        }
    }

    if ($bestPath !== '' && is_file($bestPath)) {
        $relative = str_replace('\\', '/', str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $bestPath));
        $officialTemplatePath = $relative;
        $officialTemplateFile = $bestPath;
        $officialTemplateDisplayName = basename($bestPath);
    }
}

if (is_file($templateMetaPath)) {
    $metaRaw = (string) @file_get_contents($templateMetaPath);
    $metaData = json_decode($metaRaw, true);
    if (is_array($metaData) && !empty($metaData['original_name']) && is_string($metaData['original_name'])) {
        $officialTemplateDisplayName = basename($metaData['original_name']);
    }
}

$templateAvailable = is_file($officialTemplateFile);
$officialTemplateVersion = $templateAvailable ? (string) filemtime($officialTemplateFile) : (string) time();
$officialTemplateHref = $templateAvailable
    ? ($officialTemplatePath . '?v=' . rawurlencode($officialTemplateVersion))
    : '#';
$documentPreviewHref = $officialTemplateHref;
$documentPreviewLabel = 'Open Official PDF';
$uploadError = '';
$uploadSuccess = '';

if (isset($_GET['uploaded']) && (int) $_GET['uploaded'] === 1) {
    $uploadSuccess = 'Filled PDS PDF uploaded successfully.';
}

$generatedPdfFile = '';
if (!empty($generatedPdfPath)) {
    $generatedPdfRelative = ltrim(str_replace('\\', '/', (string) $generatedPdfPath), '/');
    $generatedPdfCandidate = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $generatedPdfRelative);
    if (is_file($generatedPdfCandidate)) {
        $generatedPdfFile = $generatedPdfCandidate;
    }
}

if ($generatedPdfFile !== '') {
    $generatedPdfVersion = (string) filemtime($generatedPdfFile);
    $documentPreviewHref = ltrim(str_replace('\\', '/', (string) $generatedPdfPath), '/') . '?v=' . rawurlencode($generatedPdfVersion);
    $documentPreviewLabel = 'Open Filled PDF';
} else {
    $renderedDocVersion = $pdsExists ? (string) strtotime((string) ($pdsRecord['updated_at'] ?? 'now')) : (string) time();
    $documentPreviewHref = 'pds-document.php?employee_id=' . (int) $employeeId . '&year=' . (int) $currentYear . '&v=' . rawurlencode($renderedDocVersion);
    $documentPreviewLabel = 'Open Filled Document';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_filled_template'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $uploadError = 'Security token expired. Please refresh and try again.';
    } else {
        $upload = $_FILES['filled_pds_pdf'] ?? null;
        $uploadErrorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadErrorCode !== UPLOAD_ERR_OK) {
            $uploadError = 'Please select a valid filled PDF to upload.';
        } else {
            $originalName = (string) ($upload['name'] ?? '');
            $tmpPath = (string) ($upload['tmp_name'] ?? '');
            $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
            $mime = '';

            if (is_uploaded_file($tmpPath) && function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = (string) finfo_file($finfo, $tmpPath);
                    finfo_close($finfo);
                }
            }

            $isPdfByExtension = ($extension === 'pdf');
            $isPdfByMime = in_array($mime, ['application/pdf', 'application/x-pdf', 'application/octet-stream', ''], true);

            if (!is_uploaded_file($tmpPath) || (!$isPdfByExtension && !$isPdfByMime)) {
                $uploadError = 'Only PDF files are allowed.';
            } else {
                $pdsRecordForUpload = get_pds_record($employeeId, $currentYear);
                if (!$pdsRecordForUpload) {
                    $createdId = create_pds_record($employeeId, $currentYear);
                    if ($createdId) {
                        $pdsRecordForUpload = get_pds_record($employeeId, $currentYear);
                    }
                }

                if (!$pdsRecordForUpload) {
                    $uploadError = 'Unable to prepare PDS record for upload.';
                } else {
                    $filledDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pds' . DIRECTORY_SEPARATOR . 'filled';
                    if (!is_dir($filledDir)) {
                        @mkdir($filledDir, 0777, true);
                    }

                    if (!is_dir($filledDir) || !is_writable($filledDir)) {
                        $uploadError = 'Upload directory is not writable.';
                    } else {
                        $fileName = 'pds_filled_emp' . (int) $employeeId . '_' . (int) $currentYear . '_' . date('Ymd_His') . '.pdf';
                        $targetAbsolute = $filledDir . DIRECTORY_SEPARATOR . $fileName;
                        $targetRelative = 'uploads/pds/filled/' . $fileName;

                        if (!move_uploaded_file($tmpPath, $targetAbsolute)) {
                            $uploadError = 'Failed to save uploaded filled PDF.';
                        } else {
                            $stmt = db()->prepare("UPDATE pds_records SET generated_pdf_path = ?, status = 'submitted', updated_at = NOW() WHERE id = ?");
                            $stmt->execute([$targetRelative, (int) $pdsRecordForUpload['id']]);
                            header('Location: pds.php?uploaded=1');
                            exit;
                        }
                    }
                }
            }
        }
    }
}
$initialPdsFormState = [
    'fields' => [
        'surname' => $pdsContext['personal']['surname'] ?? '',
        'first_name' => $pdsContext['personal']['first_name'] ?? '',
        'middle_name' => $pdsContext['personal']['middle_name'] ?? '',
        'name_extension' => $pdsContext['personal']['name_extension'] ?? '',
        'date_of_birth' => $pdsContext['personal']['date_of_birth'] ?? '',
        'place_of_birth' => $pdsContext['personal']['place_of_birth'] ?? '',
        'sex' => $pdsContext['personal']['sex'] ?? '',
        'civil_status' => $pdsContext['personal']['civil_status'] ?? '',
        'citizenship' => $pdsContext['personal']['citizenship'] ?? '',
        'height_m' => $pdsContext['personal']['height_m'] ?? '',
        'weight_kg' => $pdsContext['personal']['weight_kg'] ?? '',
        'blood_type' => $pdsContext['personal']['blood_type'] ?? '',
        'gsis_id' => $pdsContext['personal']['gsis_id'] ?? '',
        'pagibig_id' => $pdsContext['personal']['pagibig_id'] ?? '',
        'philhealth_id' => $pdsContext['personal']['philhealth_id'] ?? '',
        'sss_id' => $pdsContext['personal']['sss_id'] ?? '',
        'tin_id' => $pdsContext['personal']['tin_id'] ?? '',
        'agency_employee_no' => $pdsContext['personal']['agency_employee_no'] ?? '',
        'residential_address' => $pdsContext['personal']['residential_address'] ?? '',
        'residential_zip_code' => $pdsContext['personal']['residential_zip_code'] ?? '',
        'residential_telephone' => $pdsContext['personal']['residential_telephone'] ?? '',
        'permanent_address' => $pdsContext['personal']['permanent_address'] ?? '',
        'permanent_zip_code' => $pdsContext['personal']['permanent_zip_code'] ?? '',
        'permanent_telephone' => $pdsContext['personal']['permanent_telephone'] ?? '',
        'email_address' => $pdsContext['personal']['email_address'] ?? '',
        'mobile_number' => $pdsContext['personal']['mobile_number'] ?? '',
        'spouse_surname' => $pdsContext['family']['spouse_surname'] ?? '',
        'spouse_first_name' => $pdsContext['family']['spouse_first_name'] ?? '',
        'spouse_middle_name' => $pdsContext['family']['spouse_middle_name'] ?? '',
        'spouse_name_extension' => $pdsContext['family']['spouse_name_extension'] ?? '',
        'spouse_occupation' => $pdsContext['family']['spouse_occupation'] ?? '',
        'spouse_employer_business_name' => $pdsContext['family']['spouse_employer_business_name'] ?? '',
        'spouse_business_address' => $pdsContext['family']['spouse_business_address'] ?? '',
        'spouse_telephone' => $pdsContext['family']['spouse_telephone'] ?? '',
        'father_surname' => $pdsContext['family']['father_surname'] ?? '',
        'father_first_name' => $pdsContext['family']['father_first_name'] ?? '',
        'father_middle_name' => $pdsContext['family']['father_middle_name'] ?? '',
        'father_name_extension' => $pdsContext['family']['father_name_extension'] ?? '',
        'mother_maiden_surname' => $pdsContext['family']['mother_maiden_surname'] ?? '',
        'mother_first_name' => $pdsContext['family']['mother_first_name'] ?? '',
        'mother_middle_name' => $pdsContext['family']['mother_middle_name'] ?? '',
        'special_skills' => $pdsContext['otherInfo']['special_skills_hobbies'] ?? '',
        'non_academic_distinctions' => $pdsContext['otherInfo']['non_academic_distinctions_recognitions'] ?? '',
        'membership_organizations' => $pdsContext['otherInfo']['membership_association_organizations'] ?? '',
        'q34_related' => $pdsContext['questions']['q34_related_by_blood_marriage'] ?? '',
        'q34_details' => $pdsContext['questions']['q34_relationship_details'] ?? '',
        'q35_guilty' => $pdsContext['questions']['q35_guilty_administrative_offense'] ?? '',
        'q35_details' => $pdsContext['questions']['q35_offense_details'] ?? '',
        'q36_charged' => $pdsContext['questions']['q36_criminally_charged'] ?? '',
        'q36_details' => $pdsContext['questions']['q36_case_details'] ?? '',
        'q37_convicted' => $pdsContext['questions']['q37_convicted_final_judgment'] ?? '',
        'q37_details' => $pdsContext['questions']['q37_case_details'] ?? '',
        'q38_separated' => $pdsContext['questions']['q38_separated_service'] ?? '',
        'q38_details' => $pdsContext['questions']['q38_reason_details'] ?? '',
        'q39_immigrant' => $pdsContext['questions']['q39_immigrant_status'] ?? '',
        'q39_details' => $pdsContext['questions']['q39_country_details'] ?? '',
        'q40_indigenous' => $pdsContext['questions']['q40_indigenous_member'] ?? '',
        'q40_details' => $pdsContext['questions']['q40_group_details'] ?? '',
        'id_type' => $pdsContext['governmentId']['id_type'] ?? '',
        'id_number' => $pdsContext['governmentId']['id_number'] ?? '',
        'id_date_issued' => $pdsContext['governmentId']['date_issued'] ?? '',
        'id_issuing_authority' => $pdsContext['governmentId']['issuing_authority'] ?? '',
        'date_signed' => $pdsContext['signature']['date_signed'] ?? '',
    ],
    'children' => array_map(static function ($child) {
        return [
            'children_name[]' => $child['child_name'] ?? '',
            'children_birthdate[]' => $child['date_of_birth'] ?? '',
        ];
    }, $pdsContext['children'] ?? []),
    'education' => array_map(static function ($row) {
        return [
            'education_level[]' => $row['level'] ?? '',
            'school_name[]' => $row['school_name'] ?? '',
            'degree_course[]' => $row['degree_course'] ?? '',
            'education_from[]' => $row['period_from'] ?? '',
            'education_to[]' => $row['period_to'] ?? '',
            'highest_level[]' => $row['highest_level_units_earned'] ?? '',
            'year_graduated[]' => $row['year_graduated'] ?? '',
            'scholarship_honors[]' => $row['scholarship_academic_honors'] ?? '',
        ];
    }, $pdsContext['education'] ?? []),
    'eligibility' => array_map(static function ($row) {
        return [
            'career_service[]' => $row['career_service'] ?? '',
            'eligibility_rating[]' => $row['rating'] ?? '',
            'exam_date[]' => $row['date_of_examination_conferment'] ?? '',
            'exam_place[]' => $row['place_of_examination_conferment'] ?? '',
            'license_number[]' => $row['license_number'] ?? '',
            'license_release_date[]' => $row['date_of_release'] ?? '',
        ];
    }, $pdsContext['eligibility'] ?? []),
    'workExperience' => array_map(static function ($row) {
        return [
            'work_from[]' => $row['inclusive_dates_from'] ?? '',
            'work_to[]' => $row['inclusive_dates_to'] ?? '',
            'position_title[]' => $row['position_title'] ?? '',
            'department[]' => $row['department_agency_office'] ?? '',
            'monthly_salary[]' => $row['monthly_salary'] ?? '',
            'salary_grade[]' => $row['salary_grade'] ?? '',
            'step_increment[]' => $row['step_increment'] ?? '',
            'appointment_status[]' => $row['status_of_appointment'] ?? '',
            'government_service[]' => $row['government_service'] ?? '',
        ];
    }, $pdsContext['workExperience'] ?? []),
    'voluntaryWork' => array_map(static function ($row) {
        return [
            'org_name_address[]' => $row['name_organization_address'] ?? '',
            'voluntary_from[]' => $row['inclusive_dates_from'] ?? '',
            'voluntary_to[]' => $row['inclusive_dates_to'] ?? '',
            'voluntary_hours[]' => $row['number_of_hours'] ?? '',
            'voluntary_position[]' => $row['position_nature_of_work'] ?? '',
        ];
    }, $pdsContext['voluntaryWork'] ?? []),
    'training' => array_map(static function ($row) {
        return [
            'training_title[]' => $row['title_of_learning_development_programs'] ?? '',
            'training_from[]' => $row['inclusive_dates_from'] ?? '',
            'training_to[]' => $row['inclusive_dates_to'] ?? '',
            'training_hours[]' => $row['number_of_hours'] ?? '',
            'training_type[]' => $row['type_of_ld'] ?? '',
            'training_sponsored[]' => $row['sponsored_by'] ?? '',
        ];
    }, $pdsContext['training'] ?? []),
    'references' => array_reduce($pdsContext['references'] ?? [], static function ($carry, $row) {
        $referenceNumber = (int) ($row['reference_number'] ?? 0);
        if ($referenceNumber > 0) {
            $carry["ref{$referenceNumber}_name"] = $row['name'] ?? '';
            $carry["ref{$referenceNumber}_address"] = $row['address'] ?? '';
            $carry["ref{$referenceNumber}_tel"] = $row['telephone'] ?? '';
        }
        return $carry;
    }, []),
    'uploads' => [
        'applicant_photo_preview' => $pdsContext['signature']['applicant_photo'] ?? '',
        'applicant_signature_preview' => $pdsContext['signature']['applicant_signature'] ?? '',
        'thumbmark_preview' => $pdsContext['signature']['thumbmark'] ?? '',
    ],
];
require_once 'includes/header.php';
?>
<section class="card pds-container">
    <div class="pds-header">
        <div class="pds-header-main">
            <div class="pds-header-brand">
                <div class="pds-header-badge">PDS</div>
                <div class="pds-header-agency">
                    <span class="pds-eyebrow">Republic of the Philippines</span>
                    <strong>Land Transportation Office Human Resource Information System</strong>
                    <span class="pds-agency-subline">Human Resource Management Division</span>
                </div>
            </div>
            <div class="pds-header-copy">
                <div class="pds-form-tag">Government Personnel Record</div>
                <h1>PERSONAL DATA SHEET</h1>
                <p>CSC Form No. 212 (Revised 2017)</p>
            </div>
        </div>
        <div class="pds-header-panel" aria-label="PDS filing overview">
            <div class="pds-header-panel-title">Filing Overview</div>
            <div class="pds-header-meta">
                <span class="pds-meta-pill"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Filing year: <?php echo (int) $currentYear; ?></span>
                <span class="pds-meta-pill"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Employee ID: <?php echo (int) $employeeId; ?></span>
                <span class="pds-meta-pill"><i class="fa-solid fa-asterisk" aria-hidden="true"></i> Required fields marked *</span>
                <span class="pds-meta-pill"><i class="fa-solid fa-file-shield" aria-hidden="true"></i> Status: <?php echo $pdsExists ? 'On file' : 'For completion'; ?></span>
            </div>
        </div>
    </div>

    <div class="pds-top-grid">
        <div class="pds-warning-bar">
            <div class="pds-info-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
            <div class="pds-info-copy">
                <strong>Warning</strong>
                <span>Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.</span>
            </div>
        </div>
        <div class="pds-document-note">
            <div class="pds-info-icon"><i class="fa-solid fa-book-open-reader" aria-hidden="true"></i></div>
            <div class="pds-info-copy">
                <div class="pds-document-note-title">Official form guide</div>
                <?php if ($templateAvailable): ?>
                    <p>Review the CSC Form 212 guide before completing this form. <a href="<?php echo htmlspecialchars($officialTemplateHref); ?>" target="_blank" rel="noopener">Open blank CSC PDF</a></p>
                <?php else: ?>
                    <p>The admin-uploaded PDS template is not available yet. Please contact HR/Admin to upload one.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($hasViewableDocument): ?>
        <div class="pds-document-actions" id="pdsDocumentActions" tabindex="-1">
            <div class="pds-document-actions-copy">
                <div class="pds-document-actions-title">Submitted PDS Document Ready</div>
                <p>Your accomplished PDS is now available for viewing and printing.</p>
            </div>
            <div class="pds-document-actions-buttons">
                <a href="<?php echo htmlspecialchars($documentPreviewHref); ?>" class="btn btn-primary" target="_blank" rel="noopener" id="pdsViewDocumentBtn">View Document</a>
                <?php if (!empty($generatedPdfPath)): ?>
                    <a href="<?php echo htmlspecialchars($generatedPdfPath); ?>" class="btn btn-outline" target="_blank" rel="noopener">Print PDF</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <section class="card" style="margin-top: 14px; border: 1px solid #d7e0ea; background: linear-gradient(180deg, #ffffff, #f8fbff);">
        <div style="padding: 16px 18px; border-bottom: 1px solid #dbe5ef;">
            <h3 style="margin: 0; font-size: 18px; color: #183247;">Fill and Submit Using the Template</h3>
            <p style="margin: 8px 0 0; color: #5f7286;">Open the admin template, fill it out, then upload your completed PDF here.</p>
        </div>
        <div style="padding: 16px 18px;">
            <?php if ($uploadError !== ''): ?>
                <div class="alert alert-danger" style="margin-bottom: 12px;"><?php echo htmlspecialchars($uploadError); ?></div>
            <?php endif; ?>
            <?php if ($uploadSuccess !== ''): ?>
                <div class="alert alert-success" style="margin-bottom: 12px;"><?php echo htmlspecialchars($uploadSuccess); ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="upload_filled_template" value="1">
                <input type="file" name="filled_pds_pdf" accept=".pdf,application/pdf" required>
                <button type="submit" class="btn btn-primary">Submit Filled PDF</button>
                <?php if (!empty($generatedPdfPath)): ?>
                    <a href="<?php echo htmlspecialchars($generatedPdfPath); ?>" class="btn btn-outline" target="_blank" rel="noopener">Preview / Print Filled PDF</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <section class="card" style="margin-top: 18px; border: 1px solid #d7e0ea; overflow: hidden;">
        <div style="padding: 18px 20px; border-bottom: 1px solid #d7e0ea; background: linear-gradient(135deg, #f7fafc, #eef4f8); display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div>
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #0f4c81;">Template Preview</div>
                <h3 style="margin: 6px 0 0; font-size: 20px; color: #183247;">Admin Uploaded PDS Template Preview</h3>
                <p style="margin: 6px 0 0; color: #5f7286;">The admin-uploaded PDF template is displayed below. Current template: <strong><?php echo htmlspecialchars($officialTemplateDisplayName); ?></strong>.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if ($templateAvailable): ?>
                    <a href="<?php echo htmlspecialchars($officialTemplateHref); ?>" class="btn btn-outline" target="_blank" rel="noopener">Open Admin Template</a>
                    <a href="<?php echo htmlspecialchars($documentPreviewHref); ?>" class="btn btn-primary" target="_blank" rel="noopener"><?php echo htmlspecialchars($documentPreviewLabel); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div style="background: #eef3f8;">
            <div style="background: #eef3f8;">
                <?php if ($templateAvailable): ?>
                    <iframe
                        id="pdsTemplateIframe"
                        src="<?php echo htmlspecialchars($officialTemplateHref); ?>#page=1&toolbar=0&navpanes=0&scrollbar=1"
                        title="<?php echo htmlspecialchars($officialTemplateDisplayName); ?>"
                        style="width: 100%; height: min(1700px, calc(100vh - 90px)); min-height: 1200px; border: 0; background: #eef3f8;"
                    ></iframe>
                <?php else: ?>
                    <div style="padding: 20px; color: #4f6782; font-weight: 600;">
                        No active PDS template available. Ask admin to upload a template in <code>PDS Template</code>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php $showInlinePdsForm = false; ?>
    <?php if ($showInlinePdsForm): ?>
    <form id="pdsForm" method="POST" action="pds_process.php" enctype="multipart/form-data">
        <input type="hidden" name="employee_id" value="<?php echo $employeeId; ?>">
        <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
        <input type="hidden" name="action" id="pdsAction" value="submit">
        
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
                <h5 class="pds-subsection-title pds-subsection-tight">Spouse (If married)</h5>
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

                <h5 class="pds-subsection-title">Parents</h5>
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
                        <label>Signature Image</label>
                        <div class="pds-signature-box pds-upload-box">
                            <label class="pds-upload-button" for="applicant_signature">Upload Signature</label>
                            <input type="file" name="applicant_signature" id="applicant_signature" class="pds-file-input" accept="image/png,image/jpeg">
                            <div class="pds-upload-preview" id="applicant_signature_preview" hidden></div>
                            <p class="pds-signature-placeholder">PNG or JPG only</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Thumbmark Image</label>
                        <div class="pds-signature-box pds-thumbmark-box pds-upload-box">
                            <label class="pds-upload-button" for="thumbmark">Upload Thumbmark</label>
                            <input type="file" name="thumbmark" id="thumbmark" class="pds-file-input" accept="image/png,image/jpeg">
                            <div class="pds-upload-preview" id="thumbmark_preview" hidden></div>
                            <p class="pds-signature-placeholder">PNG or JPG only</p>
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
                <a href="<?php echo htmlspecialchars($documentPreviewHref); ?>" class="btn btn-outline" target="_blank" rel="noopener">Preview Document</a>
                <?php if (!empty($generatedPdfPath)): ?>
                    <a href="<?php echo htmlspecialchars($generatedPdfPath); ?>" class="btn btn-outline" target="_blank" rel="noopener">Open Saved PDF</a>
                <?php endif; ?>
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
const existingPdsData = <?php echo json_encode($initialPdsFormState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const templatePdfBaseHref = <?php echo json_encode($templateAvailable ? $officialTemplateHref : '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

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

function syncTemplatePreviewPage(pageNum) {
    const iframe = document.getElementById('pdsTemplateIframe');
    if (!iframe || !templatePdfBaseHref) return;

    const safePage = Math.max(1, Math.min(totalPages, Number(pageNum) || 1));
    iframe.src = `${templatePdfBaseHref}#page=${safePage}&toolbar=0&navpanes=0&scrollbar=1`;
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
    syncTemplatePreviewPage(pageNum);
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
    const form = document.getElementById('pdsForm');
    const actionInput = document.getElementById('pdsAction');
    if (!form || !actionInput) return;

    actionInput.value = 'save_draft';
    form.submit();
}

function bindImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];

        if (!file) {
            preview.hidden = true;
            preview.innerHTML = '';
            return;
        }

        if (!file.type.startsWith('image/')) {
            preview.hidden = false;
            preview.innerHTML = '<span class="pds-upload-error">Please choose a valid image file.</span>';
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            preview.hidden = false;
            preview.innerHTML = `<img src="${event.target.result}" alt="Upload preview">`;
        };
        reader.readAsDataURL(file);
    });
}

function showExistingImagePreview(previewId, imagePath) {
    const preview = document.getElementById(previewId);
    if (!preview || !imagePath) return;

    preview.hidden = false;
    preview.innerHTML = `<img src="${imagePath}" alt="Saved upload preview">`;
}

function setFieldValue(name, value) {
    const fields = document.querySelectorAll(`[name="${name}"]`);
    if (!fields.length) return;

    fields.forEach((field) => {
        if (field.type === 'radio') {
            field.checked = field.value === String(value);
            return;
        }

        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
            return;
        }

        field.value = value ?? '';
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function previewDisplayValue(value, fallback = 'Not provided') {
    const normalized = String(value ?? '').trim();
    return normalized === '' ? fallback : escapeHtml(normalized);
}

function getInputValue(name) {
    const field = document.querySelector(`[name="${name}"]`);
    return field ? field.value : '';
}

function getRadioValue(name) {
    const selected = document.querySelector(`[name="${name}"]:checked`);
    return selected ? selected.value : '';
}

function collectRows(fieldNames) {
    const rowCount = Math.max(
        0,
        ...fieldNames.map((name) => document.querySelectorAll(`[name="${name}"]`).length)
    );

    const rows = [];
    for (let i = 0; i < rowCount; i += 1) {
        const row = {};
        let hasValue = false;

        fieldNames.forEach((name) => {
            const field = document.querySelectorAll(`[name="${name}"]`)[i];
            const value = field ? String(field.value ?? '').trim() : '';
            row[name] = value;
            if (value !== '') {
                hasValue = true;
            }
        });

        if (hasValue) {
            rows.push(row);
        }
    }

    return rows;
}

function renderPreviewField(label, value) {
    return `
        <div style="border: 1px solid #dbe5ef; background: #fff; border-radius: 12px; padding: 12px 14px;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">${escapeHtml(label)}</div>
            <div style="margin-top: 6px; font-size: 14px; line-height: 1.5; color: #183247; font-weight: 600;">${previewDisplayValue(value)}</div>
        </div>
    `;
}

function renderPreviewSection(title, fieldsHtml) {
    return `
        <section style="margin-bottom: 18px; border: 1px solid #dbe5ef; border-radius: 16px; overflow: hidden; background: #fff;">
            <div style="padding: 11px 14px; background: linear-gradient(90deg, #0f4c81, #0b3558); color: #fff; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;">${escapeHtml(title)}</div>
            <div style="padding: 14px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">${fieldsHtml}</div>
        </section>
    `;
}

function renderPreviewList(title, itemsHtml, emptyMessage = 'No entries yet.') {
    return `
        <section style="margin-bottom: 18px; border: 1px solid #dbe5ef; border-radius: 16px; overflow: hidden; background: #fff;">
            <div style="padding: 11px 14px; background: linear-gradient(90deg, #0f4c81, #0b3558); color: #fff; font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;">${escapeHtml(title)}</div>
            <div style="padding: 14px; display: grid; gap: 12px;">${itemsHtml || `<div style="padding: 14px; border: 1px dashed #cbd6e2; border-radius: 12px; color: #607286; background: #f8fbfd;">${escapeHtml(emptyMessage)}</div>`}</div>
        </section>
    `;
}

function renderLivePdsPreview() {
    const target = document.getElementById('pdsLivePreviewContent');
    if (!target) return;

    const childrenRows = collectRows(['children_name[]', 'children_birthdate[]']);
    const educationRows = collectRows(['education_level[]', 'school_name[]', 'degree_course[]', 'education_from[]', 'education_to[]', 'highest_level[]', 'year_graduated[]', 'scholarship_honors[]']);
    const eligibilityRows = collectRows(['career_service[]', 'eligibility_rating[]', 'exam_date[]', 'exam_place[]', 'license_number[]', 'license_release_date[]']);
    const workRows = collectRows(['work_from[]', 'work_to[]', 'position_title[]', 'department[]', 'monthly_salary[]', 'salary_grade[]', 'step_increment[]', 'appointment_status[]', 'government_service[]']);
    const voluntaryRows = collectRows(['org_name_address[]', 'voluntary_from[]', 'voluntary_to[]', 'voluntary_hours[]', 'voluntary_position[]']);
    const trainingRows = collectRows(['training_title[]', 'training_from[]', 'training_to[]', 'training_hours[]', 'training_type[]', 'training_sponsored[]']);

    const personalHtml = [
        renderPreviewField('Surname', getInputValue('surname')),
        renderPreviewField('First Name', getInputValue('first_name')),
        renderPreviewField('Middle Name', getInputValue('middle_name')),
        renderPreviewField('Name Extension', getInputValue('name_extension')),
        renderPreviewField('Date of Birth', getInputValue('date_of_birth')),
        renderPreviewField('Place of Birth', getInputValue('place_of_birth')),
        renderPreviewField('Sex', getInputValue('sex')),
        renderPreviewField('Civil Status', getInputValue('civil_status')),
        renderPreviewField('Citizenship', getInputValue('citizenship')),
        renderPreviewField('Height (m)', getInputValue('height_m')),
        renderPreviewField('Weight (kg)', getInputValue('weight_kg')),
        renderPreviewField('Blood Type', getInputValue('blood_type')),
        renderPreviewField('GSIS ID No.', getInputValue('gsis_id')),
        renderPreviewField('PAG-IBIG ID No.', getInputValue('pagibig_id')),
        renderPreviewField('PhilHealth No.', getInputValue('philhealth_id')),
        renderPreviewField('SSS No.', getInputValue('sss_id')),
        renderPreviewField('TIN No.', getInputValue('tin_id')),
        renderPreviewField('Agency Employee No.', getInputValue('agency_employee_no')),
        renderPreviewField('Residential Address', getInputValue('residential_address')),
        renderPreviewField('Residential Zip Code', getInputValue('residential_zip_code')),
        renderPreviewField('Residential Telephone', getInputValue('residential_telephone')),
        renderPreviewField('Permanent Address', getInputValue('permanent_address')),
        renderPreviewField('Permanent Zip Code', getInputValue('permanent_zip_code')),
        renderPreviewField('Permanent Telephone', getInputValue('permanent_telephone')),
        renderPreviewField('Email Address', getInputValue('email_address')),
        renderPreviewField('Mobile Number', getInputValue('mobile_number'))
    ].join('');

    const familyHtml = [
        renderPreviewField('Spouse Surname', getInputValue('spouse_surname')),
        renderPreviewField('Spouse First Name', getInputValue('spouse_first_name')),
        renderPreviewField('Spouse Middle Name', getInputValue('spouse_middle_name')),
        renderPreviewField('Spouse Extension', getInputValue('spouse_name_extension')),
        renderPreviewField('Spouse Occupation', getInputValue('spouse_occupation')),
        renderPreviewField('Employer / Business Name', getInputValue('spouse_employer_business_name')),
        renderPreviewField('Business Address', getInputValue('spouse_business_address')),
        renderPreviewField('Spouse Telephone', getInputValue('spouse_telephone')),
        renderPreviewField("Father's Surname", getInputValue('father_surname')),
        renderPreviewField("Father's First Name", getInputValue('father_first_name')),
        renderPreviewField("Father's Middle Name", getInputValue('father_middle_name')),
        renderPreviewField("Father's Name Extension", getInputValue('father_name_extension')),
        renderPreviewField("Mother's Maiden Surname", getInputValue('mother_maiden_surname')),
        renderPreviewField("Mother's First Name", getInputValue('mother_first_name')),
        renderPreviewField("Mother's Middle Name", getInputValue('mother_middle_name'))
    ].join('');

    const childrenHtml = childrenRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Child ${index + 1}</div>
            <div style="margin-top: 6px; color: #183247; font-weight: 700;">${previewDisplayValue(row['children_name[]'])}</div>
            <div style="margin-top: 4px; color: #607286;">Date of Birth: ${previewDisplayValue(row['children_birthdate[]'])}</div>
        </div>
    `).join('');

    const educationHtml = educationRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Education ${index + 1}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('Level', row['education_level[]'])}
                ${renderPreviewField('School Name', row['school_name[]'])}
                ${renderPreviewField('Degree / Course', row['degree_course[]'])}
                ${renderPreviewField('From', row['education_from[]'])}
                ${renderPreviewField('To', row['education_to[]'])}
                ${renderPreviewField('Highest Level / Units Earned', row['highest_level[]'])}
                ${renderPreviewField('Year Graduated', row['year_graduated[]'])}
                ${renderPreviewField('Scholarship / Honors', row['scholarship_honors[]'])}
            </div>
        </div>
    `).join('');

    const eligibilityHtml = eligibilityRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Eligibility ${index + 1}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('Career Service', row['career_service[]'])}
                ${renderPreviewField('Rating', row['eligibility_rating[]'])}
                ${renderPreviewField('Date of Exam / Conferment', row['exam_date[]'])}
                ${renderPreviewField('Place of Exam / Conferment', row['exam_place[]'])}
                ${renderPreviewField('License Number', row['license_number[]'])}
                ${renderPreviewField('Date of Release', row['license_release_date[]'])}
            </div>
        </div>
    `).join('');

    const workHtml = workRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Work Experience ${index + 1}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('From', row['work_from[]'])}
                ${renderPreviewField('To', row['work_to[]'])}
                ${renderPreviewField('Position Title', row['position_title[]'])}
                ${renderPreviewField('Department / Agency / Office', row['department[]'])}
                ${renderPreviewField('Monthly Salary', row['monthly_salary[]'])}
                ${renderPreviewField('Salary Grade', row['salary_grade[]'])}
                ${renderPreviewField('Step', row['step_increment[]'])}
                ${renderPreviewField('Appointment Status', row['appointment_status[]'])}
                ${renderPreviewField("Gov't Service", row['government_service[]'])}
            </div>
        </div>
    `).join('');

    const voluntaryHtml = voluntaryRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Voluntary Work ${index + 1}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('Organization / Address', row['org_name_address[]'])}
                ${renderPreviewField('From', row['voluntary_from[]'])}
                ${renderPreviewField('To', row['voluntary_to[]'])}
                ${renderPreviewField('Number of Hours', row['voluntary_hours[]'])}
                ${renderPreviewField('Position / Nature of Work', row['voluntary_position[]'])}
            </div>
        </div>
    `).join('');

    const trainingHtml = trainingRows.map((row, index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Training ${index + 1}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('Program Title', row['training_title[]'])}
                ${renderPreviewField('From', row['training_from[]'])}
                ${renderPreviewField('To', row['training_to[]'])}
                ${renderPreviewField('Number of Hours', row['training_hours[]'])}
                ${renderPreviewField('Type', row['training_type[]'])}
                ${renderPreviewField('Sponsored By', row['training_sponsored[]'])}
            </div>
        </div>
    `).join('');

    const questionsHtml = [
        renderPreviewField('Q34 Related by Consanguinity / Affinity', getRadioValue('q34_related')),
        renderPreviewField('Q34 Details', getInputValue('q34_details')),
        renderPreviewField('Q35 Guilty of Administrative Offense', getRadioValue('q35_guilty')),
        renderPreviewField('Q35 Details', getInputValue('q35_details')),
        renderPreviewField('Q36 Criminally Charged', getRadioValue('q36_charged')),
        renderPreviewField('Q36 Details', getInputValue('q36_details')),
        renderPreviewField('Q37 Convicted', getRadioValue('q37_convicted')),
        renderPreviewField('Q37 Details', getInputValue('q37_details')),
        renderPreviewField('Q38 Separated from Service', getRadioValue('q38_separated')),
        renderPreviewField('Q38 Details', getInputValue('q38_details')),
        renderPreviewField('Q39 Foreign Citizenship / Dual Citizenship', getRadioValue('q39_immigrant')),
        renderPreviewField('Q39 Details', getInputValue('q39_details')),
        renderPreviewField('Q40 Indigenous Group Member', getRadioValue('q40_indigenous')),
        renderPreviewField('Q40 Details', getInputValue('q40_details'))
    ].join('');

    const referencesHtml = [1, 2, 3].map((index) => `
        <div style="border: 1px solid #dbe5ef; border-radius: 12px; padding: 12px 14px; background: #fff;">
            <div style="font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #718499;">Reference ${index}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 10px;">
                ${renderPreviewField('Name', getInputValue(`ref${index}_name`))}
                ${renderPreviewField('Address', getInputValue(`ref${index}_address`))}
                ${renderPreviewField('Telephone', getInputValue(`ref${index}_tel`))}
            </div>
        </div>
    `).join('');

    const governmentHtml = [
        renderPreviewField('ID Type', getInputValue('id_type')),
        renderPreviewField('ID Number', getInputValue('id_number')),
        renderPreviewField('Date Issued', getInputValue('id_date_issued')),
        renderPreviewField('Issuing Authority', getInputValue('id_issuing_authority')),
        renderPreviewField('Date Signed', getInputValue('date_signed'))
    ].join('');

    target.innerHTML = [
        renderPreviewSection('I. Personal Information', personalHtml),
        renderPreviewSection('II. Family Background', familyHtml),
        renderPreviewList('Children', childrenHtml),
        renderPreviewList('III. Educational Background', educationHtml),
        renderPreviewList('IV. Civil Service Eligibility', eligibilityHtml),
        renderPreviewList('V. Work Experience', workHtml),
        renderPreviewList('VI. Voluntary Work', voluntaryHtml),
        renderPreviewList('VII. Learning and Development', trainingHtml),
        renderPreviewSection('VIII. Other Information', [
            renderPreviewField('Special Skills and Hobbies', getInputValue('special_skills')),
            renderPreviewField('Non-Academic Distinctions / Recognitions', getInputValue('non_academic_distinctions')),
            renderPreviewField('Membership in Associations / Organizations', getInputValue('membership_organizations'))
        ].join('')),
        renderPreviewSection('IX. Questions', questionsHtml),
        renderPreviewList('X. References', referencesHtml),
        renderPreviewSection('XI. Government Issued ID / Signature Date', governmentHtml)
    ].join('');
}

function renderTemplateLine(label, value, width = '1fr') {
    return `
        <div style="display: grid; grid-template-columns: 140px minmax(0, ${width}); border-bottom: 1px solid #2f2f2f;">
            <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; font-weight: 700; background: #f2f2f2; text-transform: uppercase;">${escapeHtml(label)}</div>
            <div style="padding: 6px 8px; font-size: 13px; min-height: 30px;">${previewDisplayValue(value, '&nbsp;')}</div>
        </div>
    `;
}

function renderTemplatePair(leftLabel, leftValue, rightLabel, rightValue) {
    return `
        <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #2f2f2f;">
            <div style="display: grid; grid-template-columns: 140px minmax(0, 1fr); border-right: 1px solid #2f2f2f;">
                <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; font-weight: 700; background: #f2f2f2; text-transform: uppercase;">${escapeHtml(leftLabel)}</div>
                <div style="padding: 6px 8px; font-size: 13px; min-height: 30px;">${previewDisplayValue(leftValue, '&nbsp;')}</div>
            </div>
            <div style="display: grid; grid-template-columns: 140px minmax(0, 1fr);">
                <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; font-weight: 700; background: #f2f2f2; text-transform: uppercase;">${escapeHtml(rightLabel)}</div>
                <div style="padding: 6px 8px; font-size: 13px; min-height: 30px;">${previewDisplayValue(rightValue, '&nbsp;')}</div>
            </div>
        </div>
    `;
}

function renderTemplateListRows(title, rows, formatter) {
    const content = rows.length
        ? rows.map(formatter).join('')
        : `<div style="padding: 10px 12px; font-size: 12px; color: #4f6275;">No entries yet.</div>`;

    return `
        <section style="border: 2px solid #2f2f2f; background: #fff; margin-top: 14px;">
            <div style="padding: 7px 10px; background: #d9d9d9; border-bottom: 2px solid #2f2f2f; font-size: 12px; font-weight: 800; text-transform: uppercase;">${escapeHtml(title)}</div>
            ${content}
        </section>
    `;
}

function renderLiveTemplatePreview() {
    const target = document.getElementById('pdsTemplateLiveView');
    if (!target) return;

    const childrenRows = collectRows(['children_name[]', 'children_birthdate[]']);
    const educationRows = collectRows(['education_level[]', 'school_name[]', 'degree_course[]', 'education_from[]', 'education_to[]']);
    const workRows = collectRows(['work_from[]', 'work_to[]', 'position_title[]', 'department[]']);

    target.innerHTML = `
        <div style="background: #ffffff; border: 3px solid #222; box-shadow: 0 10px 24px rgba(15,76,129,.08);">
            <div style="padding: 8px 10px; border-bottom: 2px solid #222;">
                <div style="font-size: 10px; font-weight: 800;">CS Form No. 212</div>
                <div style="font-size: 10px; font-style: italic; font-weight: 700;">Revised 2017</div>
                <div style="margin-top: 4px; font-size: 14px; font-weight: 900; text-align: center; letter-spacing: .04em;">PERSONAL DATA SHEET</div>
            </div>

            <div style="padding: 8px 10px; font-size: 10px; line-height: 1.4; border-bottom: 2px solid #222; background: #fafafa;">
                Live template preview from current user input.
            </div>

            <section style="border-bottom: 2px solid #222;">
                <div style="padding: 6px 10px; background: #d9d9d9; border-bottom: 2px solid #222; font-size: 12px; font-weight: 800; text-transform: uppercase;">I. Personal Information</div>
                ${renderTemplatePair('Surname', getInputValue('surname'), 'First Name', getInputValue('first_name'))}
                ${renderTemplatePair('Middle Name', getInputValue('middle_name'), 'Name Extension', getInputValue('name_extension'))}
                ${renderTemplatePair('Date of Birth', getInputValue('date_of_birth'), 'Place of Birth', getInputValue('place_of_birth'))}
                ${renderTemplatePair('Sex', getInputValue('sex'), 'Civil Status', getInputValue('civil_status'))}
                ${renderTemplatePair('Citizenship', getInputValue('citizenship'), 'Blood Type', getInputValue('blood_type'))}
                ${renderTemplatePair('Height (m)', getInputValue('height_m'), 'Weight (kg)', getInputValue('weight_kg'))}
                ${renderTemplatePair('GSIS ID No.', getInputValue('gsis_id'), 'PAG-IBIG ID No.', getInputValue('pagibig_id'))}
                ${renderTemplatePair('PhilHealth No.', getInputValue('philhealth_id'), 'SSS No.', getInputValue('sss_id'))}
                ${renderTemplatePair('TIN No.', getInputValue('tin_id'), 'Agency Employee No.', getInputValue('agency_employee_no'))}
                ${renderTemplateLine('Residential Address', getInputValue('residential_address'))}
                ${renderTemplatePair('Residential Zip Code', getInputValue('residential_zip_code'), 'Residential Telephone', getInputValue('residential_telephone'))}
                ${renderTemplateLine('Permanent Address', getInputValue('permanent_address'))}
                ${renderTemplatePair('Permanent Zip Code', getInputValue('permanent_zip_code'), 'Permanent Telephone', getInputValue('permanent_telephone'))}
                ${renderTemplatePair('Mobile No.', getInputValue('mobile_number'), 'Email Address', getInputValue('email_address'))}
            </section>

            <section style="border-bottom: 2px solid #222;">
                <div style="padding: 6px 10px; background: #d9d9d9; border-bottom: 2px solid #222; font-size: 12px; font-weight: 800; text-transform: uppercase;">II. Family Background</div>
                ${renderTemplatePair('Spouse Surname', getInputValue('spouse_surname'), 'Spouse First Name', getInputValue('spouse_first_name'))}
                ${renderTemplatePair('Spouse Middle Name', getInputValue('spouse_middle_name'), 'Spouse Extension', getInputValue('spouse_name_extension'))}
                ${renderTemplatePair('Occupation', getInputValue('spouse_occupation'), 'Employer / Business', getInputValue('spouse_employer_business_name'))}
                ${renderTemplatePair('Business Address', getInputValue('spouse_business_address'), 'Telephone No.', getInputValue('spouse_telephone'))}
                ${renderTemplatePair("Father's Surname", getInputValue('father_surname'), "Father's First Name", getInputValue('father_first_name'))}
                ${renderTemplatePair("Father's Middle Name", getInputValue('father_middle_name'), "Father's Extension", getInputValue('father_name_extension'))}
                ${renderTemplatePair("Mother's Maiden Surname", getInputValue('mother_maiden_surname'), "Mother's First Name", getInputValue('mother_first_name'))}
                ${renderTemplateLine("Mother's Middle Name", getInputValue('mother_middle_name'))}
            </section>

            ${renderTemplateListRows('Children', childrenRows, (row, index) => `
                <div style="display: grid; grid-template-columns: 50px 1fr 180px; border-bottom: 1px solid #2f2f2f;">
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; background: #f8f8f8;">${index + 1}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 13px;">${previewDisplayValue(row['children_name[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; font-size: 13px;">${previewDisplayValue(row['children_birthdate[]'], '&nbsp;')}</div>
                </div>
            `)}

            ${renderTemplateListRows('Educational Background', educationRows, (row, index) => `
                <div style="display: grid; grid-template-columns: 40px 120px 1fr 1fr 110px 110px; border-bottom: 1px solid #2f2f2f;">
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; background: #f8f8f8;">${index + 1}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['education_level[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['school_name[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['degree_course[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['education_from[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; font-size: 12px;">${previewDisplayValue(row['education_to[]'], '&nbsp;')}</div>
                </div>
            `)}

            ${renderTemplateListRows('Work Experience', workRows, (row, index) => `
                <div style="display: grid; grid-template-columns: 40px 110px 110px 1fr 1fr; border-bottom: 1px solid #2f2f2f;">
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 11px; background: #f8f8f8;">${index + 1}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['work_from[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['work_to[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; border-right: 1px solid #2f2f2f; font-size: 12px;">${previewDisplayValue(row['position_title[]'], '&nbsp;')}</div>
                    <div style="padding: 6px 8px; font-size: 12px;">${previewDisplayValue(row['department[]'], '&nbsp;')}</div>
                </div>
            `)}
        </div>
    `;
}

function appendDynamicItem(containerId, templateHtml, values) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const item = document.createElement('div');
    item.className = 'dynamic-item';
    item.innerHTML = templateHtml;
    container.appendChild(item);

    Object.entries(values || {}).forEach(([name, value]) => {
        const field = item.querySelector(`[name="${name}"]`);
        if (field) {
            field.value = value ?? '';
        }
    });
}

function childTemplate() {
    return `
        <input type="text" placeholder="Child's Full Name" name="children_name[]">
        <input type="date" placeholder="Date of Birth" name="children_birthdate[]">
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
}

function educationTemplate() {
    return `
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
}

function eligibilityTemplate() {
    return `
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
}

function workExperienceTemplate() {
    return `
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
}

function voluntaryWorkTemplate() {
    return `
        <input type="text" placeholder="Name & Address of Organization" name="org_name_address[]" required>
        <input type="date" placeholder="From" name="voluntary_from[]" required>
        <input type="date" placeholder="To" name="voluntary_to[]" required>
        <input type="number" placeholder="Number of Hours" name="voluntary_hours[]">
        <input type="text" placeholder="Position/Nature of Work" name="voluntary_position[]" required>
        <button type="button" class="btn btn-outline btn-small pds-remove" onclick="removeDynamicItem(this)">Remove</button>
    `;
}

function trainingTemplate() {
    return `
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
}

function renderDynamicSection(containerId, items, templateFactory, emptyItem = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';
    const source = Array.isArray(items) && items.length ? items : [emptyItem];
    source.forEach((item) => appendDynamicItem(containerId, templateFactory(), item));
}

function hydratePdsForm() {
    const flatFields = {
        ...(existingPdsData.fields || {}),
        ...(existingPdsData.references || {}),
    };

    Object.entries(flatFields).forEach(([name, value]) => {
        setFieldValue(name, value);
    });

    renderDynamicSection('childrenList', existingPdsData.children, childTemplate, {});
    renderDynamicSection('educationList', existingPdsData.education, educationTemplate, {});
    renderDynamicSection('eligibilityList', existingPdsData.eligibility, eligibilityTemplate, {});
    renderDynamicSection('workExperienceList', existingPdsData.workExperience, workExperienceTemplate, {});
    renderDynamicSection('voluntaryWorkList', existingPdsData.voluntaryWork, voluntaryWorkTemplate, {});
    renderDynamicSection('trainingList', existingPdsData.training, trainingTemplate, {});

    Object.entries(existingPdsData.uploads || {}).forEach(([previewId, imagePath]) => {
        showExistingImagePreview(previewId, imagePath);
    });
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
    appendDynamicItem('childrenList', childTemplate(), {});
}

function addEducation() {
    appendDynamicItem('educationList', educationTemplate(), {});
}

function addEligibility() {
    appendDynamicItem('eligibilityList', eligibilityTemplate(), {});
}

function addWorkExperience() {
    appendDynamicItem('workExperienceList', workExperienceTemplate(), {});
}

function addVoluntaryWork() {
    appendDynamicItem('voluntaryWorkList', voluntaryWorkTemplate(), {});
}

function addTraining() {
    appendDynamicItem('trainingList', trainingTemplate(), {});
}

// Initialize
hydratePdsForm();
bindImagePreview('applicant_signature', 'applicant_signature_preview');
bindImagePreview('thumbmark', 'thumbmark_preview');
renderLiveTemplatePreview();
syncTemplatePreviewPage(currentPage);
document.getElementById('pdsForm')?.addEventListener('input', () => {
    renderLiveTemplatePreview();
});
document.getElementById('pdsForm')?.addEventListener('change', () => {
    renderLiveTemplatePreview();
});
document.getElementById('pdsForm')?.addEventListener('submit', () => {
    const actionInput = document.getElementById('pdsAction');
    if (actionInput && actionInput.value !== 'save_draft') {
        actionInput.value = 'submit';
    }
});
updateButtons();

const shouldFocusDocumentPanel = <?php echo $submittedNow ? 'true' : 'false'; ?>;
if (shouldFocusDocumentPanel) {
    window.addEventListener('load', () => {
        const panel = document.getElementById('pdsDocumentActions');
        const primaryAction = document.getElementById('pdsViewDocumentBtn');
        if (!panel) return;

        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => {
            if (primaryAction) {
                primaryAction.focus({ preventScroll: true });
            } else {
                panel.focus({ preventScroll: true });
            }
        }, 350);
    });
}
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

