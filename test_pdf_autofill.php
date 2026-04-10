<?php
// Test PDF auto-fill functionality
require_once 'includes/db.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

echo "<h2>PDF Auto-Fill Test</h2>";

// Test 1: Check if PDF functions exist
echo "<h3>1. Function Availability</h3>";
$functions = [
    'generatePDFFormWithAutofill',
    'generateHTMLSALNForm',
    'generateHTMLCSCForm',
    'generateFilledPDF',
    'addEmployeeDataToPDF',
    'generateSimpleFilledPDF'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✅ Function {$func} exists<br>";
    } else {
        echo "❌ Function {$func} missing<br>";
    }
}

// Test 2: Test PDF form generation
echo "<h3>2. PDF Form Generation Test</h3>";
$mock_template = [
    'id' => 1,
    'template_name' => 'Test SALN Template',
    'file_path' => 'uploads/form_templates/test.pdf',
    'version' => '1.0'
];

$mock_employee_data = [
    'full_name' => 'Juan Dela Cruz',
    'position' => 'Software Developer',
    'department' => 'ICT Department',
    'employee_number' => 'EMP001',
    'birthdate' => '1990-01-01',
    'civil_status' => 'Married',
    'monthly_salary' => 50000.00,
    'spouse_name' => 'Maria Dela Cruz'
];

try {
    $pdf_form_html = generatePDFFormWithAutofill($mock_template, $mock_employee_data, 'saln');
    if ($pdf_form_html && strpos($pdf_form_html, 'pdf-template-container') !== false) {
        echo "✅ PDF form generation successful<br>";
        echo "📏 Generated HTML length: " . strlen($pdf_form_html) . " characters<br>";
    } else {
        echo "❌ PDF form generation failed<br>";
    }
} catch (Exception $e) {
    echo "❌ PDF form generation error: " . $e->getMessage() . "<br>";
}

// Test 3: Test SALN form routing
echo "<h3>3. SALN Form Routing Test</h3>";
try {
    // Test PDF routing
    $pdf_template = $mock_template;
    $pdf_template['file_path'] = 'test.pdf';
    $saln_pdf_result = generateSALNFromTemplate($pdf_template, $mock_employee_data);
    if (strpos($saln_pdf_result, 'pdf-template-container') !== false) {
        echo "✅ SALN PDF routing works<br>";
    } else {
        echo "❌ SALN PDF routing failed<br>";
    }

    // Test HTML routing
    $html_template = $mock_template;
    $html_template['file_path'] = 'test.docx';
    $saln_html_result = generateSALNFromTemplate($html_template, $mock_employee_data);
    if (strpos($saln_html_result, 'saln-template-container') !== false) {
        echo "✅ SALN HTML routing works<br>";
    } else {
        echo "❌ SALN HTML routing failed<br>";
    }
} catch (Exception $e) {
    echo "❌ SALN routing error: " . $e->getMessage() . "<br>";
}

// Test 4: Test CSC form routing
echo "<h3>4. CSC Form Routing Test</h3>";
try {
    // Test PDF routing
    $pdf_template = $mock_template;
    $pdf_template['file_path'] = 'test.pdf';
    $csc_pdf_result = generateCSCFromTemplate($pdf_template, $mock_employee_data);
    if (strpos($csc_pdf_result, 'pdf-template-container') !== false) {
        echo "✅ CSC PDF routing works<br>";
    } else {
        echo "❌ CSC PDF routing failed<br>";
    }

    // Test HTML routing
    $html_template = $mock_template;
    $html_template['file_path'] = 'test.docx';
    $csc_html_result = generateCSCFromTemplate($html_template, $mock_employee_data);
    if (strpos($csc_html_result, 'csc-template-container') !== false) {
        echo "✅ CSC HTML routing works<br>";
    } else {
        echo "❌ CSC HTML routing failed<br>";
    }
} catch (Exception $e) {
    echo "❌ CSC routing error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If all tests passed, the PDF auto-fill functionality should work correctly.</p>";
?>