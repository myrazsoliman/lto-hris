<?php
// Generate filled PDF with employee data
require_once 'includes/auth.php';
require_login();

// Check if user is allowed to generate PDFs
require_roles(['employee', 'hr_officer', 'admin', 'superadmin']);

require_once 'includes/db.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Security token expired');
}

$template_id = $_POST['template_id'] ?? null;
$form_type = $_POST['form_type'] ?? null;
$employee_data_json = $_POST['employee_data'] ?? null;

if (!$template_id || !$form_type || !$employee_data_json) {
    http_response_code(400);
    die('Missing required parameters');
}

// Decode employee data
$employee_data = json_decode($employee_data_json, true);
if (!$employee_data) {
    http_response_code(400);
    die('Invalid employee data');
}

// Get template information
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM form_templates WHERE id = ? AND is_active = TRUE");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    http_response_code(404);
    die('Template not found');
}

// Check if template is a PDF
$file_extension = strtolower(pathinfo($template['file_path'], PATHINFO_EXTENSION));
if ($file_extension !== 'pdf') {
    http_response_code(400);
    die('Template is not a PDF file');
}

// Generate filled PDF
try {
    $filled_pdf_path = generateFilledPDF($template, $employee_data, $form_type);

    if ($filled_pdf_path && file_exists($filled_pdf_path)) {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filled_pdf_path) . '"');
        header('Content-Length: ' . filesize($filled_pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Output the PDF
        readfile($filled_pdf_path);

        // Clean up the temporary file after download
        register_shutdown_function(function() use ($filled_pdf_path) {
            if (file_exists($filled_pdf_path)) {
                unlink($filled_pdf_path);
            }
        });

        exit;
    } else {
        http_response_code(500);
        die('Failed to generate filled PDF');
    }
} catch (Exception $e) {
    error_log("PDF generation error: " . $e->getMessage());
    http_response_code(500);
    die('Error generating PDF: ' . $e->getMessage());
}

/**
 * Generate filled PDF using FPDI/FPDF
 */
function generateFilledPDF($template, $employee_data, $form_type) {
    // Check if FPDI is available
    if (!class_exists('setasign\Fpdi\Fpdi')) {
        // Fallback: create a simple PDF with employee data
        return generateSimpleFilledPDF($template, $employee_data, $form_type);
    }

    try {
        // Use FPDI to import and fill the PDF
        $pdf = new setasign\Fpdi\Fpdi();

        // Get the number of pages
        $pageCount = $pdf->setSourceFile($template['file_path']);

        // Import each page
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            // Add text overlays based on form type
            addEmployeeDataToPDF($pdf, $employee_data, $form_type, $pageNo);
        }

        // Generate filename
        $filename = $form_type . '_filled_' . date('Y-m-d_H-i-s') . '.pdf';
        $output_path = sys_get_temp_dir() . '/' . $filename;

        // Save the filled PDF
        $pdf->Output($output_path, 'F');

        return $output_path;

    } catch (Exception $e) {
        error_log("FPDI PDF generation failed: " . $e->getMessage());
        // Fallback to simple PDF generation
        return generateSimpleFilledPDF($template, $employee_data, $form_type);
    }
}

/**
 * Add employee data as text overlays on PDF
 */
function addEmployeeDataToPDF($pdf, $employee_data, $form_type, $pageNo) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);

    // Common fields for all forms
    $common_fields = [
        'full_name' => $employee_data['full_name'] ?? '',
        'position' => $employee_data['position'] ?? '',
        'department' => $employee_data['department'] ?? '',
        'employee_number' => $employee_data['employee_number'] ?? '',
    ];

    if ($form_type === 'saln') {
        // SALN specific field positions (these would need to be calibrated for actual PDF)
        $saln_positions = [
            'name' => [50, 100],
            'position' => [50, 120],
            'department' => [50, 140],
            'employee_no' => [150, 100],
            'spouse_name' => [50, 200],
            'monthly_salary' => [150, 140],
        ];

        // Add text at specific positions
        if ($pageNo === 1) {
            $pdf->SetXY($saln_positions['name'][0], $saln_positions['name'][1]);
            $pdf->Write(0, $common_fields['full_name']);

            $pdf->SetXY($saln_positions['position'][0], $saln_positions['position'][1]);
            $pdf->Write(0, $common_fields['position']);

            $pdf->SetXY($saln_positions['department'][0], $saln_positions['department'][1]);
            $pdf->Write(0, $common_fields['department']);

            $pdf->SetXY($saln_positions['employee_no'][0], $saln_positions['employee_no'][1]);
            $pdf->Write(0, $common_fields['employee_number']);

            if (!empty($employee_data['spouse_name'])) {
                $pdf->SetXY($saln_positions['spouse_name'][0], $saln_positions['spouse_name'][1]);
                $pdf->Write(0, $employee_data['spouse_name']);
            }

            if (!empty($employee_data['monthly_salary'])) {
                $pdf->SetXY($saln_positions['monthly_salary'][0], $saln_positions['monthly_salary'][1]);
                $pdf->Write(0, '₱' . number_format($employee_data['monthly_salary'], 2));
            }
        }
    } elseif ($form_type === 'csc') {
        // CSC form specific positions
        $csc_positions = [
            'name' => [50, 100],
            'position' => [50, 120],
            'department' => [50, 140],
            'employee_no' => [150, 100],
            'birthdate' => [50, 160],
            'civil_status' => [150, 160],
        ];

        if ($pageNo === 1) {
            $pdf->SetXY($csc_positions['name'][0], $csc_positions['name'][1]);
            $pdf->Write(0, $common_fields['full_name']);

            $pdf->SetXY($csc_positions['position'][0], $csc_positions['position'][1]);
            $pdf->Write(0, $common_fields['position']);

            $pdf->SetXY($csc_positions['department'][0], $csc_positions['department'][1]);
            $pdf->Write(0, $common_fields['department']);

            $pdf->SetXY($csc_positions['employee_no'][0], $csc_positions['employee_no'][1]);
            $pdf->Write(0, $common_fields['employee_number']);

            if (!empty($employee_data['birthdate'])) {
                $pdf->SetXY($csc_positions['birthdate'][0], $csc_positions['birthdate'][1]);
                $pdf->Write(0, date('m/d/Y', strtotime($employee_data['birthdate'])));
            }

            if (!empty($employee_data['civil_status'])) {
                $pdf->SetXY($csc_positions['civil_status'][0], $csc_positions['civil_status'][1]);
                $pdf->Write(0, $employee_data['civil_status']);
            }
        }
    }
}

/**
 * Fallback: Generate a simple PDF with employee data
 */
function generateSimpleFilledPDF($template, $employee_data, $form_type) {
    // Check if FPDF is available
    if (!class_exists('FPDF')) {
        // If no PDF library is available, return the original template
        return $template['file_path'];
    }

    try {
        $pdf = new FPDF();
        $pdf->AddPage();

        // Set up the PDF
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, strtoupper($form_type) . ' FORM - AUTO-FILLED', 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Employee Information', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', '', 10);

        // Employee details
        $fields = [
            'Full Name' => $employee_data['full_name'] ?? 'N/A',
            'Position' => $employee_data['position'] ?? 'N/A',
            'Department' => $employee_data['department'] ?? 'N/A',
            'Employee Number' => $employee_data['employee_number'] ?? 'N/A',
            'Date of Birth' => $employee_data['birthdate'] ?? 'N/A',
            'Civil Status' => $employee_data['civil_status'] ?? 'N/A',
            'Monthly Salary' => $employee_data['monthly_salary'] ? '₱' . number_format($employee_data['monthly_salary'], 2) : 'N/A',
            'Spouse Name' => $employee_data['spouse_name'] ?? 'N/A',
        ];

        foreach ($fields as $label => $value) {
            $pdf->Cell(50, 8, $label . ':', 0, 0);
            $pdf->Cell(0, 8, $value, 0, 1);
        }

        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, 'Note: This is an auto-generated filled form. Please verify all information before submission.', 0, 1);

        // Generate filename
        $filename = $form_type . '_filled_' . date('Y-m-d_H-i-s') . '.pdf';
        $output_path = sys_get_temp_dir() . '/' . $filename;

        // Save the PDF
        $pdf->Output($output_path, 'F');

        return $output_path;

    } catch (Exception $e) {
        error_log("Simple PDF generation failed: " . $e->getMessage());
        // Return original template as last resort
        return $template['file_path'];
    }
}
?>