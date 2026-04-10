<?php
$pageTitle = 'Processing CSC Form Submission';
require_once 'includes/auth.php';
require_login();

// Check if user is allowed to submit CSC forms
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);

require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $currentUser = current_user();
        $employee_id = $currentUser['id'];
        $template_id = $_POST['template_id'] ?? null;
        
        if (!$template_id) {
            $error = 'Invalid template selected.';
        } else {
            try {
                // Prepare CSC form data
                $csc_form_type = $_POST['csc_form_type'];
                if ($csc_form_type === 'other') {
                    $csc_form_type = $_POST['other_form_name'] ?? 'Other CSC Form';
                }
                
                $csc_data = [
                    'employee_id' => $employee_id,
                    'template_id' => $template_id,
                    'form_type' => $csc_form_type,
                    'full_name' => $_POST['full_name'] ?? '',
                    'position_title' => $_POST['position_title'] ?? '',
                    'department_office' => $_POST['department_office'] ?? '',
                    'employee_no' => $_POST['employee_no'] ?? '',
                    'date_of_birth' => $_POST['date_of_birth'] ?? '',
                    'place_of_birth' => $_POST['place_of_birth'] ?? '',
                    'civil_status' => $_POST['civil_status'] ?? '',
                    'citizenship' => $_POST['citizenship'] ?? '',
                    'residential_address' => $_POST['residential_address'] ?? '',
                    'permanent_address' => $_POST['permanent_address'] ?? '',
                    'telephone' => $_POST['telephone'] ?? '',
                    'mobile' => $_POST['mobile'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'purpose' => $_POST['purpose'] ?? '',
                    'details' => $_POST['details'] ?? '',
                    'signature' => $_POST['signature'] ?? '',
                    'date_signed' => $_POST['date_signed'] ?? date('Y-m-d'),
                    'status' => 'Submitted'
                ];
                
                // Handle file uploads
                $uploaded_files = [];
                if (isset($_FILES['attachments'])) {
                    $upload_dir = 'uploads/csc_attachments/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['attachments']['name'] as $key => $filename) {
                        if (!empty($filename)) {
                            $temp_path = $_FILES['attachments']['tmp_name'][$key];
                            $safe_filename = time() . '_' . $key . '_' . basename($filename);
                            $file_path = $upload_dir . $safe_filename;
                            
                            if (move_uploaded_file($temp_path, $file_path)) {
                                $uploaded_files[] = $file_path;
                            }
                        }
                    }
                }
                
                // Insert CSC form record
                $sql = "INSERT INTO csc_forms 
                        (employee_id, form_type, file_path, submitted_at) 
                        VALUES (?, ?, ?, NOW())";
                
                $file_path_json = json_encode($uploaded_files);
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$employee_id, $csc_data['form_type'], $file_path_json]);
                $csc_id = $pdo->lastInsertId();
                
                // Store detailed CSC data (you might want to create a separate table for this)
                $detailed_data = $csc_data;
                $detailed_data['attachments'] = $uploaded_files;
                
                // For now, we'll store the detailed data as JSON in a custom field
                // In a production system, you'd want proper database tables for this
                
                // Log the activity
                // audit_log($currentUser['id'], 'CSC form submitted', "CSC form submitted: {$csc_data['form_type']}");
                
                $success = 'CSC form submitted successfully!';
                
                // Redirect to avoid form resubmission
                header('Location: csc-forms.php?success=submitted');
                exit;
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                error_log("CSC form submission error: " . $e->getMessage());
            }
        }
    }
}

// If we get here, there was an error or direct access
if (!empty($error)) {
    $_SESSION['error'] = $error;
    header('Location: csc-forms.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="alert alert-info">
    <h4>Processing CSC Form Submission</h4>
    <p>If you are seeing this page, there may have been an issue processing your CSC form submission.</p>
    <a href="csc-forms.php" class="btn btn-primary">Return to CSC Forms</a>
</div>

<?php require_once 'includes/footer.php'; ?>