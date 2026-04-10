<?php
$pageTitle = 'Processing SALN Submission';
require_once 'includes/auth.php';
require_login();

// Check if user is allowed to submit SALN
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
                // Prepare SALN data
                $saln_data = [
                    'employee_id' => $employee_id,
                    'template_id' => $template_id,
                    'year' => date('Y'),
                    'declarant_name' => $_POST['declarant_name'] ?? '',
                    'position' => $_POST['position'] ?? '',
                    'department' => $_POST['department'] ?? '',
                    'annual_salary' => $_POST['annual_salary'] ?? 0,
                    'spouse_name' => $_POST['spouse_name'] ?? '',
                    'as_of_date' => $_POST['as_of_date'] ?? date('Y-12-31'),
                    'signature' => $_POST['signature'] ?? '',
                    'date_signed' => $_POST['date_signed'] ?? date('Y-m-d'),
                    'status' => 'Submitted'
                ];
                
                // Insert SALN record
                $sql = "INSERT INTO saln_submissions 
                        (employee_id, year, file_path, status, submitted_at) 
                        VALUES (?, ?, '', ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$employee_id, $saln_data['year'], $saln_data['status']]);
                $saln_id = $pdo->lastInsertId();
                
                // Store detailed SALN data in a JSON format (you might want to create separate tables for this)
                $detailed_data = [
                    'personal_info' => $saln_data,
                    'real_properties' => [],
                    'personal_properties' => [],
                    'liabilities' => []
                ];
                
                // Process real properties
                if (isset($_POST['real_property_description'])) {
                    foreach ($_POST['real_property_description'] as $key => $description) {
                        if (!empty($description)) {
                            $detailed_data['real_properties'][] = [
                                'description' => $description,
                                'kind' => $_POST['real_property_kind'][$key] ?? '',
                                'location' => $_POST['real_property_location'][$key] ?? '',
                                'value' => $_POST['real_property_value'][$key] ?? 0,
                                'year' => $_POST['real_property_year'][$key] ?? '',
                                'mode' => $_POST['real_property_mode'][$key] ?? ''
                            ];
                        }
                    }
                }
                
                // Process personal properties
                if (isset($_POST['personal_property_description'])) {
                    foreach ($_POST['personal_property_description'] as $key => $description) {
                        if (!empty($description)) {
                            $detailed_data['personal_properties'][] = [
                                'description' => $description,
                                'year' => $_POST['personal_property_year'][$key] ?? '',
                                'cost' => $_POST['personal_property_cost'][$key] ?? 0
                            ];
                        }
                    }
                }
                
                // Process liabilities
                if (isset($_POST['liability_nature'])) {
                    foreach ($_POST['liability_nature'] as $key => $nature) {
                        if (!empty($nature)) {
                            $detailed_data['liabilities'][] = [
                                'nature' => $nature,
                                'creditor' => $_POST['liability_creditor'][$key] ?? '',
                                'balance' => $_POST['liability_balance'][$key] ?? 0
                            ];
                        }
                    }
                }
                
                // Update the file_path with JSON data (or you could store this in a separate table)
                $json_data = json_encode($detailed_data);
                $update_sql = "UPDATE saln_submissions SET file_path = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$json_data, $saln_id]);
                
                // Log the activity
                // audit_log($currentUser['id'], 'SALN submitted', "SALN submitted for year {$saln_data['year']}");
                
                $success = 'SALN submitted successfully!';
                
                // Redirect to avoid form resubmission
                header('Location: saln.php?success=submitted');
                exit;
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                error_log("SALN submission error: " . $e->getMessage());
            }
        }
    }
}

// If we get here, there was an error or direct access
if (!empty($error)) {
    $_SESSION['error'] = $error;
    header('Location: saln.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="alert alert-info">
    <h4>Processing SALN Submission</h4>
    <p>If you are seeing this page, there may have been an issue processing your SALN submission.</p>
    <a href="saln.php" class="btn btn-primary">Return to SALN</a>
</div>

<?php require_once 'includes/footer.php'; ?>