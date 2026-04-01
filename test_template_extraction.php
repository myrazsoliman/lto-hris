<?php
// Test template extraction functionality
require_once 'includes/db.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

echo "<h2>📄 Template Extraction Test</h2>";

// Test 1: Check if we have templates
echo "<h3>1. Available Templates</h3>";
try {
    $stmt = $pdo->query("SELECT template_name, file_path FROM form_templates WHERE form_type = 'saln' ORDER BY uploaded_at DESC LIMIT 3");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($templates)) {
        foreach ($templates as $template) {
            echo "📋 Template: " . htmlspecialchars($template['template_name']) . "<br>";
            echo "📁 Path: " . htmlspecialchars($template['file_path']) . "<br>";
            echo "📄 Exists: " . (file_exists($template['file_path']) ? "✅ Yes" : "❌ No") . "<br>";
            
            if (file_exists($template['file_path'])) {
                $file_info = pathinfo($template['file_path']);
                $size = filesize($template['file_path']);
                echo "📊 Size: " . number_format($size / 1024, 2) . " KB<br>";
                echo "🏷️ Extension: " . htmlspecialchars($file_info['extension']) . "<br>";
                
                // Test extraction
                $employee_data = [
                    'full_name' => 'Test Employee',
                    'position' => 'Test Position', 
                    'department' => 'Test Department',
                    'employee_number' => 'TEST-001'
                ];
                
                echo "<h4>🔍 Testing Extraction:</h4>";
                
                // Test PDF extraction
                if ($file_info['extension'] === 'pdf') {
                    echo "📄 Testing PDF extraction...<br>";
                    $extracted = extractPDFContent($template['file_path']);
                    if ($extracted) {
                        echo "✅ PDF extraction successful!<br>";
                        echo "📝 Extracted length: " . strlen($extracted) . " characters<br>";
                        echo "📋 Preview: " . substr(htmlspecialchars($extracted), 0, 200) . "...<br>";
                    } else {
                        echo "⚠️ PDF extraction failed - will use iframe fallback<br>";
                    }
                }
                
                // Test Word extraction
                elseif (in_array($file_info['extension'], ['doc', 'docx'])) {
                    echo "📄 Testing Word extraction...<br>";
                    $extracted = extractWordContent($template['file_path']);
                    if ($extracted) {
                        echo "✅ Word extraction successful!<br>";
                        echo "📝 Extracted length: " . strlen($extracted) . " characters<br>";
                        echo "📋 Preview: " . substr(htmlspecialchars($extracted), 0, 200) . "...<br>";
                    } else {
                        echo "⚠️ Word extraction failed - will use download fallback<br>";
                    }
                }
            }
            echo "<hr>";
        }
    } else {
        echo "❌ No SALN templates found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Test full form generation
echo "<h3>2. Full Form Generation Test</h3>";
try {
    $template = getActiveTemplate('saln');
    if ($template) {
        $employee_data = [
            'full_name' => 'Juan Dela Cruz',
            'position' => 'Administrative Officer III',
            'department' => 'HR Unit',
            'employee_number' => 'EMP-2026-001'
        ];
        
        echo "📋 Using template: " . htmlspecialchars($template['template_name']) . "<br>";
        
        $form_html = generateFormWithAutofill('saln', $employee_data);
        
        if ($form_html) {
            echo "✅ Form generation successful!<br>";
            echo "📝 HTML length: " . strlen($form_html) . " characters<br>";
            
            // Check for expected elements
            $checks = [
                'extracted-template-container' => 'Template container',
                'employee-info-section' => 'Employee info section',
                'template-content-section' => 'Template content section',
                'content-display' => 'Content display area',
                'Juan Dela Cruz' => 'Employee name auto-fill'
            ];
            
            echo "<h4>🔍 Content Verification:</h4>";
            foreach ($checks as $check => $description) {
                $found = strpos($form_html, $check) !== false;
                echo $found ? "✅ {$description}" : "❌ {$description}";
                echo "<br>";
            }
        } else {
            echo "❌ Form generation failed<br>";
        }
    } else {
        echo "⚠️ No active template found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. System Features</h3>";
echo "<ul>";
echo "<li>🔍 <strong>Auto Template Reading</strong> - System automatically reads PDF/Word content</li>";
echo "<li>👤 <strong>Auto-Fill</strong> - Employee data automatically inserted</li>";
echo "<li>📄 <strong>Content Display</strong> - Shows extracted template content</li>";
echo "<li>✏️ <strong>Editable Overlay</strong> - Click to fill form online</li>";
echo "<li>📥 <strong>Fallback Options</strong> - Download original if extraction fails</li>";
echo "<li>🖨️ <strong>Print Ready</strong> - Optimized for printing</li>";
echo "</ul>";

echo "<h3>4. How to Use</h3>";
echo "<ol>";
echo "<li>Upload SALN template (PDF/Word) via form-templates.php</li>";
echo "<li>System will automatically read and display content</li>";
echo "<li>Employee info is auto-filled from database</li>";
echo "<li>Users can fill form online or download original</li>";
echo "</ol>";

echo "<p><a href='saln.php'>🔗 Test SALN Form</a> | <a href='form-templates.php'>🔗 Manage Templates</a></p>";
?>
