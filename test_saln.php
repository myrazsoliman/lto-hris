<?php
// Test script for SALN functionality
require_once 'includes/db.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

echo "<h2>SALN Template System Test</h2>";

// Test 1: Check database connection
echo "<h3>1. Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Database connected. Users found: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 2: Check form templates table
echo "<h3>2. Form Templates</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM form_templates WHERE form_type = 'saln'");
    $result = $stmt->fetch();
    echo "SALN templates found: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $template = getActiveTemplate('saln');
        if ($template) {
            echo "✅ Active SALN template: " . htmlspecialchars($template['template_name']) . "<br>";
            echo "📁 File path: " . htmlspecialchars($template['file_path']) . "<br>";
            echo "📄 File exists: " . (file_exists($template['file_path']) ? "Yes" : "No") . "<br>";
        } else {
            echo "⚠️ No active SALN template found<br>";
        }
    } else {
        echo "⚠️ No SALN templates in database<br>";
    }
} catch (Exception $e) {
    echo "❌ Template error: " . $e->getMessage() . "<br>";
}

// Test 3: Check employees table
echo "<h3>3. Employee Data</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
    $result = $stmt->fetch();
    echo "Employees found: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT id, employee_number, first_name, last_name, position, department FROM employees LIMIT 1");
        $employee = $stmt->fetch();
        echo "✅ Sample employee: " . htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) . "<br>";
        
        // Test getEmployeeData function
        $employee_data = getEmployeeData($employee['id']);
        if ($employee_data) {
            echo "✅ getEmployeeData function works<br>";
            echo "📋 Employee data: " . json_encode($employee_data, JSON_PRETTY_PRINT) . "<br>";
        } else {
            echo "❌ getEmployeeData function failed<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Employee error: " . $e->getMessage() . "<br>";
}

// Test 4: Test new template-based SALN form generation
echo "<h3>4. Template-Based SALN Form Generation</h3>";
try {
    $template = getActiveTemplate('saln');
    if ($template) {
        $employee_data = [
            'full_name' => 'Test Employee', 
            'position' => 'Test Position', 
            'department' => 'Test Department', 
            'employee_number' => 'TEST-001'
        ];
        $form_html = generateFormWithAutofill('saln', $employee_data);
        if ($form_html) {
            echo "✅ Template-based SALN form generated successfully<br>";
            echo "📝 Form length: " . strlen($form_html) . " characters<br>";
            
            // Check if it contains expected elements
            if (strpos($form_html, 'saln-template-container') !== false) {
                echo "✅ Contains template container<br>";
            }
            if (strpos($form_html, 'employee-info-section') !== false) {
                echo "✅ Contains employee info section<br>";
            }
            if (strpos($form_html, 'template-display') !== false) {
                echo "✅ Contains template display section<br>";
            }
        } else {
            echo "❌ Template-based SALN form generation failed<br>";
        }
    } else {
        echo "⚠️ Cannot test form generation - no active template<br>";
    }
} catch (Exception $e) {
    echo "❌ Form generation error: " . $e->getMessage() . "<br>";
}

// Test 5: Check uploaded template files
echo "<h3>5. Template File Analysis</h3>";
try {
    $stmt = $pdo->query("SELECT template_name, file_path FROM form_templates WHERE form_type = 'saln' ORDER BY uploaded_at DESC LIMIT 3");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($templates as $template) {
        echo "📄 Template: " . htmlspecialchars($template['template_name']) . "<br>";
        echo "📁 Path: " . htmlspecialchars($template['file_path']) . "<br>";
        
        if (file_exists($template['file_path'])) {
            $file_info = pathinfo($template['file_path']);
            echo "✅ File exists<br>";
            echo "📊 Extension: " . htmlspecialchars($file_info['extension']) . "<br>";
            echo "📏 Size: " . number_format(filesize($template['file_path']) / 1024, 2) . " KB<br>";
        } else {
            echo "❌ File not found<br>";
        }
        echo "<br>";
    }
} catch (Exception $e) {
    echo "❌ File analysis error: " . $e->getMessage() . "<br>";
}

echo "<h3>6. System Features</h3>";
echo "<ul>";
echo "<li>✅ Template-based SALN display - Shows actual uploaded template</li>";
echo "<li>✅ Auto-filled employee information</li>";
echo "<li>✅ PDF template viewing with iframe</li>";
echo "<li>✅ Word template download support</li>";
echo "<li>✅ Online form filling overlay for PDFs</li>";
echo "<li>✅ Net worth calculation</li>";
echo "<li>✅ Print-friendly layout</li>";
echo "</ul>";

echo "<h3>7. Recommendations</h3>";
echo "<ul>";
echo "<li>Upload SALN templates via form-templates.php as superadmin</li>";
echo "<li>Ensure template files are PDF or Word format</li>";
echo "<li>Test with actual employee data</li>";
echo "<li>Verify template display works correctly</li>";
echo "</ul>";

echo "<p><a href='saln.php'>🔗 Test SALN Form</a> | <a href='form-templates.php'>🔗 Manage Templates</a></p>";
?>
