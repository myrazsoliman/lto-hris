<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/data.php';
require_once 'includes/pds-document-render.php';
require_once 'includes/upload.php';

// Check if user is authenticated
if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pds.php');
    exit();
}

// Get form data
$employeeId = (int)$_POST['employee_id'];
$year = (int)$_POST['year'];
$action = $_POST['action'] ?? 'submit'; // Can be 'submit' or 'save_draft'

// Validate employee access
$userRoles = get_user_roles($_SESSION['user_id']);
$currentUserEmployeeId = in_array('employee', $userRoles, true)
    ? ensure_employee_record_for_user((int) ($_SESSION['user']['id'] ?? 0))
    : resolve_employee_id_for_user((int) ($_SESSION['user']['id'] ?? 0));

// Employees can only edit their own PDS, HR/Admin can edit any
if (in_array('employee', $userRoles) && $currentUserEmployeeId != $employeeId) {
    $_SESSION['error'] = 'You are not authorized to edit this PDS.';
    header('Location: pds.php');
    exit();
}

try {
    ensure_pds_signature_columns();
    ensure_pds_record_pdf_column();

    // Get or create PDS record
    $pdsRecord = get_pds_record($employeeId, $year);
    
    if (!$pdsRecord) {
        $pdsId = create_pds_record($employeeId, $year);
        if (!$pdsId) {
            throw new Exception('Failed to create PDS record');
        }
    } else {
        $pdsId = $pdsRecord['id'];
    }
    
    // Process personal information
    $personalData = [
        'surname' => $_POST['surname'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'name_extension' => $_POST['name_extension'] ?? '',
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'place_of_birth' => $_POST['place_of_birth'] ?? '',
        'sex' => $_POST['sex'] ?? '',
        'civil_status' => $_POST['civil_status'] ?? '',
        'citizenship' => $_POST['citizenship'] ?? '',
        'height_m' => !empty($_POST['height_m']) ? (float)$_POST['height_m'] : null,
        'weight_kg' => !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null,
        'blood_type' => $_POST['blood_type'] ?? '',
        'gsis_id' => $_POST['gsis_id'] ?? '',
        'pagibig_id' => $_POST['pagibig_id'] ?? '',
        'philhealth_id' => $_POST['philhealth_id'] ?? '',
        'sss_id' => $_POST['sss_id'] ?? '',
        'tin_id' => $_POST['tin_id'] ?? '',
        'agency_employee_no' => $_POST['agency_employee_no'] ?? '',
        'residential_address' => $_POST['residential_address'] ?? '',
        'residential_zip_code' => $_POST['residential_zip_code'] ?? '',
        'residential_telephone' => $_POST['residential_telephone'] ?? '',
        'permanent_address' => $_POST['permanent_address'] ?? '',
        'permanent_zip_code' => $_POST['permanent_zip_code'] ?? '',
        'permanent_telephone' => $_POST['permanent_telephone'] ?? '',
        'email_address' => $_POST['email_address'] ?? '',
        'mobile_number' => $_POST['mobile_number'] ?? ''
    ];
    
    if (!update_pds_personal_info($pdsId, $personalData)) {
        throw new Exception('Failed to update personal information');
    }
    
    // Process family background
    $familyData = [
        'spouse_surname' => $_POST['spouse_surname'] ?? '',
        'spouse_first_name' => $_POST['spouse_first_name'] ?? '',
        'spouse_middle_name' => $_POST['spouse_middle_name'] ?? '',
        'spouse_name_extension' => $_POST['spouse_name_extension'] ?? '',
        'spouse_occupation' => $_POST['spouse_occupation'] ?? '',
        'spouse_employer_business_name' => $_POST['spouse_employer_business_name'] ?? '',
        'spouse_business_address' => $_POST['spouse_business_address'] ?? '',
        'spouse_telephone' => $_POST['spouse_telephone'] ?? '',
        'father_surname' => $_POST['father_surname'] ?? '',
        'father_first_name' => $_POST['father_first_name'] ?? '',
        'father_middle_name' => $_POST['father_middle_name'] ?? '',
        'father_name_extension' => $_POST['father_name_extension'] ?? '',
        'mother_maiden_surname' => $_POST['mother_maiden_surname'] ?? '',
        'mother_first_name' => $_POST['mother_first_name'] ?? '',
        'mother_middle_name' => $_POST['mother_middle_name'] ?? ''
    ];
    
    if (!update_pds_family_background($pdsId, $familyData)) {
        throw new Exception('Failed to update family background');
    }
    
    // Process children
    $children = [];
    if (isset($_POST['children_name']) && is_array($_POST['children_name'])) {
        foreach ($_POST['children_name'] as $index => $name) {
            if (!empty(trim($name))) {
                $children[] = [
                    'name' => trim($name),
                    'birthdate' => $_POST['children_birthdate'][$index] ?? null
                ];
            }
        }
    }
    
    if (!update_pds_children($pdsId, $children)) {
        throw new Exception('Failed to update children information');
    }
    
    // Process education
    // Delete existing education records
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM pds_education WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if (isset($_POST['education_level']) && is_array($_POST['education_level'])) {
        foreach ($_POST['education_level'] as $index => $level) {
            if (!empty(trim($level))) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_education (
                        pds_id, level, school_name, degree_course, period_from, period_to,
                        highest_level_units_earned, year_graduated, scholarship_academic_honors
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pdsId,
                    $level,
                    $_POST['school_name'][$index] ?? '',
                    $_POST['degree_course'][$index] ?? '',
                    $_POST['education_from'][$index] ?? null,
                    $_POST['education_to'][$index] ?? null,
                    $_POST['highest_level'][$index] ?? '',
                    $_POST['year_graduated'][$index] ?? null,
                    $_POST['scholarship_honors'][$index] ?? ''
                ]);
            }
        }
    }
    
    // Process civil service eligibility
    $stmt = $pdo->prepare("DELETE FROM pds_civil_service_eligibility WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if (isset($_POST['career_service']) && is_array($_POST['career_service'])) {
        foreach ($_POST['career_service'] as $index => $service) {
            if (!empty(trim($service))) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_civil_service_eligibility (
                        pds_id, career_service, rating, date_of_examination_conferment,
                        place_of_examination_conferment, license_number, date_of_release
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pdsId,
                    $service,
                    $_POST['eligibility_rating'][$index] ?? '',
                    $_POST['exam_date'][$index] ?? null,
                    $_POST['exam_place'][$index] ?? '',
                    $_POST['license_number'][$index] ?? '',
                    $_POST['license_release_date'][$index] ?? null
                ]);
            }
        }
    }
    
    // Process work experience
    $stmt = $pdo->prepare("DELETE FROM pds_work_experience WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if (isset($_POST['work_from']) && is_array($_POST['work_from'])) {
        foreach ($_POST['work_from'] as $index => $from) {
            if (!empty(trim($from))) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_work_experience (
                        pds_id, inclusive_dates_from, inclusive_dates_to, position_title,
                        department_agency_office, monthly_salary, salary_grade, step_increment,
                        status_of_appointment, government_service
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pdsId,
                    $from,
                    $_POST['work_to'][$index] ?? null,
                    $_POST['position_title'][$index] ?? '',
                    $_POST['department'][$index] ?? '',
                    $_POST['monthly_salary'][$index] ?? null,
                    $_POST['salary_grade'][$index] ?? '',
                    $_POST['step_increment'][$index] ?? '',
                    $_POST['appointment_status'][$index] ?? '',
                    $_POST['government_service'][$index] ?? ''
                ]);
            }
        }
    }
    
    // Process voluntary work
    $stmt = $pdo->prepare("DELETE FROM pds_voluntary_work WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if (isset($_POST['voluntary_from']) && is_array($_POST['voluntary_from'])) {
        foreach ($_POST['voluntary_from'] as $index => $from) {
            if (!empty(trim($from))) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_voluntary_work (
                        pds_id, name_organization_address, inclusive_dates_from,
                        inclusive_dates_to, number_of_hours, position_nature_of_work
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pdsId,
                    $_POST['org_name_address'][$index] ?? '',
                    $from,
                    $_POST['voluntary_to'][$index] ?? '',
                    $_POST['voluntary_hours'][$index] ?? null,
                    $_POST['voluntary_position'][$index] ?? ''
                ]);
            }
        }
    }
    
    // Process training programs
    $stmt = $pdo->prepare("DELETE FROM pds_training_programs WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if (isset($_POST['training_from']) && is_array($_POST['training_from'])) {
        foreach ($_POST['training_from'] as $index => $from) {
            if (!empty(trim($from))) {
                $stmt = $pdo->prepare("
                    INSERT INTO pds_training_programs (
                        pds_id, title_of_learning_development_programs, inclusive_dates_from,
                        inclusive_dates_to, number_of_hours, type_of_ld, sponsored_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $pdsId,
                    $_POST['training_title'][$index] ?? '',
                    $from,
                    $_POST['training_to'][$index] ?? '',
                    $_POST['training_hours'][$index] ?? null,
                    $_POST['training_type'][$index] ?? '',
                    $_POST['training_sponsored'][$index] ?? ''
                ]);
            }
        }
    }
    
    // Process other information
    $otherInfoData = [
        'special_skills_hobbies' => $_POST['special_skills'] ?? '',
        'non_academic_distinctions_recognitions' => $_POST['non_academic_distinctions'] ?? '',
        'membership_association_organizations' => $_POST['membership_organizations'] ?? ''
    ];
    
    $stmt = $pdo->prepare("SELECT id FROM pds_other_information WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if ($stmt->fetch()) {
        $fields = [];
        $values = [];
        foreach ($otherInfoData as $key => $value) {
            if (!empty($value)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        if (!empty($fields)) {
            $values[] = $pdsId;
            $sql = "UPDATE pds_other_information SET " . implode(', ', $fields) . " WHERE pds_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
    } else {
        $fields = array_keys($otherInfoData);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $values = array_values($otherInfoData);
        $values[] = $pdsId;
        
        $sql = "INSERT INTO pds_other_information (" . implode(', ', $fields) . ", pds_id) VALUES ($placeholders, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
    
    // Process questions
    $questionsData = [
        'q34_related_by_blood_marriage' => $_POST['q34_related'] ?? '',
        'q34_relationship_details' => $_POST['q34_details'] ?? '',
        'q35_guilty_administrative_offense' => $_POST['q35_guilty'] ?? '',
        'q35_offense_details' => $_POST['q35_details'] ?? '',
        'q36_criminally_charged' => $_POST['q36_charged'] ?? '',
        'q36_case_details' => $_POST['q36_details'] ?? '',
        'q37_convicted_final_judgment' => $_POST['q37_convicted'] ?? '',
        'q37_case_details' => $_POST['q37_details'] ?? '',
        'q38_separated_service' => $_POST['q38_separated'] ?? '',
        'q38_reason_details' => $_POST['q38_details'] ?? '',
        'q39_immigrant_status' => $_POST['q39_immigrant'] ?? '',
        'q39_country_details' => $_POST['q39_details'] ?? '',
        'q40_indigenous_member' => $_POST['q40_indigenous'] ?? '',
        'q40_group_details' => $_POST['q40_details'] ?? ''
    ];
    
    $stmt = $pdo->prepare("SELECT id FROM pds_questions WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if ($stmt->fetch()) {
        $fields = [];
        $values = [];
        foreach ($questionsData as $key => $value) {
            if (!empty($value)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        if (!empty($fields)) {
            $values[] = $pdsId;
            $sql = "UPDATE pds_questions SET " . implode(', ', $fields) . " WHERE pds_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
    } else {
        $fields = array_keys($questionsData);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $values = array_values($questionsData);
        $values[] = $pdsId;
        
        $sql = "INSERT INTO pds_questions (" . implode(', ', $fields) . ", pds_id) VALUES ($placeholders, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
    
    // Process references
    for ($i = 1; $i <= 3; $i++) {
        $refData = [
            'name' => $_POST["ref{$i}_name"] ?? '',
            'address' => $_POST["ref{$i}_address"] ?? '',
            'telephone' => $_POST["ref{$i}_tel"] ?? ''
        ];
        
        $stmt = $pdo->prepare("
            UPDATE pds_references 
            SET name = ?, address = ?, telephone = ? 
            WHERE pds_id = ? AND reference_number = ?
        ");
        $stmt->execute([
            $refData['name'],
            $refData['address'],
            $refData['telephone'],
            $pdsId,
            $i
        ]);
    }
    
    // Process government ID
    $idData = [
        'id_type' => $_POST['id_type'] ?? '',
        'id_number' => $_POST['id_number'] ?? '',
        'date_issued' => $_POST['id_date_issued'] ?? null,
        'issuing_authority' => $_POST['id_issuing_authority'] ?? ''
    ];
    
    $stmt = $pdo->prepare("SELECT id FROM pds_government_id WHERE pds_id = ?");
    $stmt->execute([$pdsId]);
    
    if ($stmt->fetch()) {
        $fields = [];
        $values = [];
        foreach ($idData as $key => $value) {
            if (!empty($value)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        if (!empty($fields)) {
            $values[] = $pdsId;
            $sql = "UPDATE pds_government_id SET " . implode(', ', $fields) . " WHERE pds_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
        }
    } else {
        $fields = array_keys($idData);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $values = array_values($idData);
        $values[] = $pdsId;
        
        $sql = "INSERT INTO pds_government_id (" . implode(', ', $fields) . ", pds_id) VALUES ($placeholders, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
    
    // Process photo, signature, and thumbmark images
    $signatureRecord = get_pds_signature($pdsId);
    $signatureData = [
        'applicant_photo' => is_array($signatureRecord) ? ($signatureRecord['applicant_photo'] ?? null) : null,
        'applicant_signature' => is_array($signatureRecord) ? ($signatureRecord['applicant_signature'] ?? null) : null,
        'thumbmark' => is_array($signatureRecord) ? ($signatureRecord['thumbmark'] ?? null) : null,
        'date_signed' => $_POST['date_signed'] ?? null,
    ];

    $uploadFields = [
        'applicant_photo' => 'Applicant photo',
        'applicant_signature' => 'Signature image',
        'thumbmark' => 'Thumbmark image',
    ];

    foreach ($uploadFields as $fieldName => $label) {
        if (!empty($_FILES[$fieldName]) && ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$uploaded, $uploadResult] = handle_image_upload($fieldName, 'pds');
            if (!$uploaded) {
                throw new Exception($label . ' upload failed: ' . $uploadResult);
            }
            $signatureData[$fieldName] = $uploadResult;
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM pds_signature WHERE pds_id = ?");
    $stmt->execute([$pdsId]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("
            UPDATE pds_signature
            SET applicant_photo = ?, applicant_signature = ?, thumbmark = ?, date_signed = ?
            WHERE pds_id = ?
        ");
        $stmt->execute([
            $signatureData['applicant_photo'],
            $signatureData['applicant_signature'],
            $signatureData['thumbmark'],
            $signatureData['date_signed'],
            $pdsId,
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO pds_signature (pds_id, applicant_photo, applicant_signature, thumbmark, date_signed)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pdsId,
            $signatureData['applicant_photo'],
            $signatureData['applicant_signature'],
            $signatureData['thumbmark'],
            $signatureData['date_signed'],
        ]);
    }
    
    // Update PDS status
    $newStatus = ($action === 'submit') ? 'submitted' : 'draft';
    if (!update_pds_status($pdsId, $newStatus)) {
        throw new Exception('Failed to update PDS status');
    }

    if ($action === 'submit') {
        $generatedPdfPath = generate_pds_pdf($employeeId, $year);
        if ($generatedPdfPath !== null) {
            $stmt = $pdo->prepare("UPDATE pds_records SET generated_pdf_path = ? WHERE id = ?");
            $stmt->execute([$generatedPdfPath, $pdsId]);
        }
    }
    
    // Set success message
    if ($action === 'submit') {
        $_SESSION['success'] = 'PDS submitted successfully! Your PDS is now under review.';
    } else {
        $_SESSION['success'] = 'PDS saved as draft successfully!';
    }
    
    // Redirect based on user role
    if (in_array('employee', $userRoles)) {
        $redirectUrl = 'pds.php';
        if ($action === 'submit') {
            $redirectUrl .= '?submitted=1';
        } elseif ($action === 'save_draft') {
            $redirectUrl .= '?draft_saved=1';
        }
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: employees.php?view=employee&id=' . $employeeId);
    }
    
} catch (Exception $e) {
    error_log("PDS Processing Error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while processing your PDS: ' . $e->getMessage();
    header('Location: pds.php');
}

function ensure_pds_signature_columns()
{
    global $pdo;

    $requiredColumns = [
        'applicant_photo' => 'ALTER TABLE pds_signature ADD COLUMN applicant_photo TEXT NULL AFTER pds_id',
        'applicant_signature' => 'ALTER TABLE pds_signature ADD COLUMN applicant_signature TEXT NULL AFTER applicant_photo',
        'thumbmark' => 'ALTER TABLE pds_signature ADD COLUMN thumbmark TEXT NULL AFTER applicant_signature',
    ];

    foreach ($requiredColumns as $column => $sql) {
        if (!auth_table_has_column('pds_signature', $column)) {
            $pdo->exec($sql);
        }
    }
}

function ensure_pds_record_pdf_column()
{
    global $pdo;

    if (!auth_table_has_column('pds_records', 'generated_pdf_path')) {
        $pdo->exec('ALTER TABLE pds_records ADD COLUMN generated_pdf_path VARCHAR(1024) NULL AFTER status');
    }
}

function generate_pds_pdf($employeeId, $year)
{
    $context = pds_document_get_context($employeeId, $year);
    if ($context === null) {
        throw new Exception('Unable to build PDS document context for PDF generation.');
    }

    ensure_pds_filled_dir();
    $timestamp = date('Ymd_His');
    $baseName = 'pds_' . (int) $employeeId . '_' . (int) $year . '_' . $timestamp;
    $filledDir = __DIR__ . '/uploads/pds/filled';
    $pdfPath = $filledDir . '/' . $baseName . '.pdf';

    $html = pds_document_render_html($context, true, true);
    $htmlPath = $filledDir . '/' . $baseName . '.html';
    file_put_contents($htmlPath, $html);

    try {
        $browserPath = find_pdf_browser_path();
        if ($browserPath !== null) {
            $command = escapeshellarg($browserPath)
                . ' --headless=new --disable-gpu --allow-file-access-from-files'
                . ' --print-to-pdf=' . escapeshellarg($pdfPath)
                . ' ' . escapeshellarg(path_to_file_url($htmlPath));

            exec($command, $output, $exitCode);

            if ($exitCode === 0 && is_file($pdfPath) && filesize($pdfPath) > 0) {
                return 'uploads/pds/filled/' . basename($pdfPath);
            }
        }

        if (fill_exact_pds_template_pdf($context, $pdfPath)) {
            return 'uploads/pds/filled/' . basename($pdfPath);
        }
    } finally {
        if (is_file($htmlPath)) {
            @unlink($htmlPath);
        }
    }

    throw new Exception('Unable to generate PDS PDF.');
}

function ensure_pds_filled_dir()
{
    ensure_upload_dirs();
    $dir = __DIR__ . '/uploads/pds/filled';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function find_pdf_browser_path()
{
    $paths = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function path_to_file_url($path)
{
    $normalized = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path) ?: $path);
    return 'file:///' . str_replace(' ', '%20', ltrim($normalized, '/'));
}

function fill_exact_pds_template_pdf(array $context, $outputPdfPath)
{
    $pdftkPath = find_pdftk_path();
    $templatePath = find_pds_template_pdf();

    if ($pdftkPath === null || $templatePath === null) {
        return false;
    }

    $fdfPath = preg_replace('/\.pdf$/i', '.fdf', $outputPdfPath);
    $fields = build_pds_template_field_values($context);
    file_put_contents($fdfPath, build_fdf_content($fields));

    try {
        $command = escapeshellarg($pdftkPath)
            . ' ' . escapeshellarg($templatePath)
            . ' fill_form ' . escapeshellarg($fdfPath)
            . ' output ' . escapeshellarg($outputPdfPath)
            . ' need_appearances flatten';

        exec($command, $output, $exitCode);
        return $exitCode === 0 && is_file($outputPdfPath) && filesize($outputPdfPath) > 0;
    } finally {
        if (is_file($fdfPath)) {
            @unlink($fdfPath);
        }
    }
}

function find_pdftk_path()
{
    $paths = [
        'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe',
        'C:\\Program Files\\PDFtk Server\\bin\\pdftk.exe',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function find_pds_template_pdf()
{
    $paths = [
        __DIR__ . '\\uploads\\form_templates\\PH GSIS CS 212 2017-2026.pdf',
        __DIR__ . '\\assets\\img\\PH GSIS CS 212 2017-2026.pdf',
        'C:\\Users\\Myra\\Downloads\\PH GSIS CS 212 2017-2026.pdf',
        __DIR__ . '\\uploads\\pds\\PH GSIS CS 212 2017-2026.pdf',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function build_pds_template_field_values(array $context)
{
    $personal = $context['personal'] ?? [];
    $family = $context['family'] ?? [];
    $children = $context['children'] ?? [];
    $signature = $context['signature'] ?? [];

    $fields = [
        'Text_2' => $personal['surname'] ?? '',
        'Text_3' => $personal['first_name'] ?? '',
        'Text_4' => $personal['middle_name'] ?? '',
        'Date_1' => format_pdf_date($personal['date_of_birth'] ?? ''),
        'Text_5' => $personal['place_of_birth'] ?? '',
        'Text_6' => $personal['gsis_id'] ?? '',
        'Text_7' => $personal['pagibig_id'] ?? '',
        'Text_8' => $personal['philhealth_id'] ?? '',
        'Text_9' => $personal['sss_id'] ?? '',
        'Text_10' => $personal['tin_id'] ?? '',
        'Text_11' => $personal['agency_employee_no'] ?? '',
        'Text_12' => $personal['citizenship'] ?? '',
        'Text_13' => $personal['height_m'] ?? '',
        'Text_14' => $personal['weight_kg'] ?? '',
        'Text_15' => $personal['blood_type'] ?? '',
        'Text_17' => $personal['residential_address'] ?? '',
        'Zip_Code_1' => $personal['residential_zip_code'] ?? '',
        'US_Phone_Number_1' => $personal['residential_telephone'] ?? '',
        'Text_23' => $personal['permanent_address'] ?? '',
        'Zip_Code_2' => $personal['permanent_zip_code'] ?? '',
        'US_Phone_Number_2' => $personal['permanent_telephone'] ?? '',
        'Email_1' => $personal['email_address'] ?? '',
        'Text_29' => $personal['mobile_number'] ?? '',
        'Text_30' => $family['spouse_surname'] ?? '',
        'Text_31' => $family['spouse_first_name'] ?? '',
        'Text_32' => $family['spouse_middle_name'] ?? '',
        'Text_33' => $family['spouse_name_extension'] ?? '',
        'Text_34' => $family['spouse_occupation'] ?? '',
        'US_Phone_Number_3' => $family['spouse_telephone'] ?? '',
        'Text_41' => $family['father_surname'] ?? '',
        'Text_42' => $family['father_first_name'] ?? '',
        'Text_43' => $family['father_middle_name'] ?? '',
        'Text_44' => $family['father_name_extension'] ?? '',
        'Text_45' => $family['mother_maiden_surname'] ?? '',
        'Text_46' => $family['mother_first_name'] ?? '',
        'Text_47' => $family['mother_middle_name'] ?? '',
        'Date_151' => format_pdf_date($signature['date_signed'] ?? ''),
    ];

    $childFieldMap = [
        ['Text_35', 'Date_2'],
        ['Text_36', 'Date_3'],
        ['Text_37', 'Date_4'],
        ['Text_38', 'Date_5'],
        ['Text_39', 'Date_6'],
        ['Text_40', 'Date_7'],
    ];

    foreach ($childFieldMap as $index => [$nameField, $dateField]) {
        $child = $children[$index] ?? [];
        $fields[$nameField] = $child['child_name'] ?? '';
        $fields[$dateField] = format_pdf_date($child['date_of_birth'] ?? '');
    }

    $sex = strtolower(trim((string) ($personal['sex'] ?? '')));
    if ($sex === 'male') {
        $fields['Checkbox_1'] = 'Checkbox_1';
    } elseif ($sex === 'female') {
        $fields['Checkbox_2'] = 'Checkbox_2';
    }

    $civilStatus = strtolower(trim((string) ($personal['civil_status'] ?? '')));
    $civilMap = [
        'single' => 'Checkbox_3',
        'married' => 'Checkbox_4',
        'widowed' => 'Checkbox_5',
        'separated' => 'Checkbox_6',
        'divorced' => 'Checkbox_7',
        'common law' => 'Checkbox_7',
    ];
    if (isset($civilMap[$civilStatus])) {
        $fields[$civilMap[$civilStatus]] = $civilMap[$civilStatus];
    }

    $citizenship = strtolower(trim((string) ($personal['citizenship'] ?? '')));
    if ($citizenship !== '') {
        $fields['Checkbox_8'] = 'Checkbox_8';
        if (strpos($citizenship, 'natural') !== false) {
            $fields['Checkbox_9'] = 'Checkbox_9';
        }
    }

    return $fields;
}

function format_pdf_date($value)
{
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('m/d/Y', $timestamp) : (string) $value;
}

function build_fdf_content(array $fields)
{
    $lines = ["%FDF-1.2", "1 0 obj", "<<", "/FDF << /Fields ["];
    foreach ($fields as $name => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $lines[] = "<< /T (" . fdf_escape($name) . ") /V (" . fdf_escape((string) $value) . ") >>";
    }
    $lines[] = "] >>";
    $lines[] = ">>";
    $lines[] = "endobj";
    $lines[] = "trailer";
    $lines[] = "<< /Root 1 0 R >>";
    $lines[] = "%%EOF";
    return implode("\n", $lines);
}

function fdf_escape($value)
{
    return str_replace(
        ["\\", "(", ")", "\r", "\n"],
        ["\\\\", "\\(", "\\)", '', "\\n"],
        (string) $value
    );
}
?>
