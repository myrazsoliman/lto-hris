<?php
require_once 'db.php';

/**
 * Get active template for a specific form type
 */
function getActiveTemplate($form_type) {
    global $pdo;
    
    $sql = "SELECT * FROM form_templates WHERE form_type = ? AND is_active = TRUE ORDER BY uploaded_at DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$form_type]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all templates for a form type
 */
function getAllTemplates($form_type) {
    global $pdo;
    
    $sql = "SELECT * FROM form_templates WHERE form_type = ? ORDER BY uploaded_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$form_type]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generate form with auto-fill data using uploaded template
 */
function generateFormWithAutofill($form_type, $employee_data = []) {
    $template = getActiveTemplate($form_type);
    
    if (!$template) {
        return '<p>No active template found for ' . strtoupper($form_type) . ' forms.</p>';
    }
    
    $form_html = '';
    
    if ($form_type === 'saln') {
        $form_html = generateSALNFromTemplate($template, $employee_data);
    } elseif ($form_type === 'csc') {
        $form_html = generateCSCFromTemplate($template, $employee_data);
    }
    
    return $form_html;
}

/**
 * Extract and display content from uploaded template files
 */
function extractTemplateContent($file_path, $template_name, $employee_data) {
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $content = '';
    
    try {
        if ($file_extension === 'pdf') {
            // Try to extract text from PDF
            $content = extractPDFContent($file_path);
        } elseif (in_array($file_extension, ['doc', 'docx'])) {
            // Try to extract text from Word document
            $content = extractWordContent($file_path);
        }
        
        // If extraction successful, display with auto-fill
        if ($content) {
            return displayExtractedContent($content, $template_name, $employee_data, $file_extension);
        }
    } catch (Exception $e) {
        error_log("Template extraction error: " . $e->getMessage());
    }
    
    // Fallback to iframe if extraction fails
    return displayTemplateFallback($file_path, $template_name, $employee_data, $file_extension);
}

/**
 * Extract text content from PDF file
 */
function extractPDFContent($file_path) {
    try {
        // Check if PDF text extraction library is available
        if (extension_loaded('pdfparser')) {
            // Use PDFParser if available
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
            return $pdf->getText();
        } elseif (function_exists('shell_exec')) {
            // Try using pdftotext command line tool
            $text = shell_exec("pdftotext '" . escapeshellarg($file_path) . "' - 2>/dev/null");
            if ($text && trim($text) !== '') {
                return $text;
            }
        }
        
        // Try basic file reading as last resort
        if (file_exists($file_path)) {
            $file_content = file_get_contents($file_path);
            if ($file_content && strlen($file_content) > 100) {
                // Try to extract readable text between PDF objects
                preg_match_all('/BT\s*(.*?)\s*ET/s', $file_content, $matches);
                if (!empty($matches[1])) {
                    return implode(' ', $matches[1]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("PDF extraction failed: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Extract text content from Word document
 */
function extractWordContent($file_path) {
    try {
        if (extension_loaded('phpoffice/phpword')) {
            // Use PHPWord if available
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
            $content = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $content .= $element->getText() . "\n";
                    }
                }
            }
            
            return $content;
        } elseif (function_exists('shell_exec')) {
            // Try using antiword command line tool
            $text = shell_exec("antiword '" . escapeshellarg($file_path) . "' 2>/dev/null");
            if ($text && trim($text) !== '') {
                return $text;
            }
            
            // Try using pandoc
            $text = shell_exec("pandoc '" . escapeshellarg($file_path) . "' -t plain 2>/dev/null");
            if ($text && trim($text) !== '') {
                return $text;
            }
        }
    } catch (Exception $e) {
        error_log("Word extraction failed: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Display extracted content with auto-fill
 */
function displayExtractedContent($content, $template_name, $employee_data, $file_extension) {
    ob_start();
    ?>
    <div class="extracted-template-container">
        <div class="template-header">
            <h3>Statement of Assets, Liabilities and Net Worth (SALN)</h3>
            <p class="template-info">
                Template: <?php echo htmlspecialchars($template_name); ?> | 
                Format: <?php echo strtoupper($file_extension); ?> | 
                Auto-extracted Content
            </p>
        </div>
        
        <!-- Employee Information (Auto-filled) -->
        <div class="employee-info-section">
            <h4>Employee Information (Auto-filled)</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($employee_data['full_name'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Position:</label>
                    <span><?php echo htmlspecialchars($employee_data['position'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Department:</label>
                    <span><?php echo htmlspecialchars($employee_data['department'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Employee Number:</label>
                    <span><?php echo htmlspecialchars($employee_data['employee_number'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Extracted Template Content -->
        <div class="template-content-section">
            <div class="content-toolbar">
                <div class="toolbar-left">
                    <span class="content-label">📄 Extracted Template Content:</span>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-sm btn-outline" onclick="downloadOriginalTemplate()">
                        <i class="fas fa-download"></i> Download Original
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="fillExtractedForm()">
                        <i class="fas fa-edit"></i> Fill Form
                    </button>
                </div>
            </div>
            
            <div class="extracted-content">
                <div class="content-display">
                    <?php
                    // Process and display extracted content
                    $processed_content = processExtractedContent($content, $employee_data);
                    echo $processed_content;
                    ?>
                </div>
                
                <!-- Editable Form Overlay -->
                <div class="editable-form-overlay" id="editableFormOverlay" style="display: none;">
                    <div class="overlay-header">
                        <h4>Fill SALN Information</h4>
                        <button class="close-overlay" onclick="closeEditableForm()">×</button>
                    </div>
                    <div class="overlay-content">
                        <?php echo generateEditableForm($content, $employee_data); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="instructions-section">
            <h4>How to Use This Form</h4>
            <ul>
                <li>📋 The template content above was automatically extracted from your uploaded file.</li>
                <li>👤 Your personal information has been auto-filled from employee records.</li>
                <li>✏️ Click "Fill Form" to enter your information in an editable format.</li>
                <li>💾 Click "Download Original" to get the exact template file.</li>
                <li>🖨️ Use the print function to generate a printable version.</li>
            </ul>
        </div>
    </div>
    
    <script>
    function downloadOriginalTemplate() {
        window.open('<?php echo htmlspecialchars($GLOBALS['template_file_path'] ?? ''); ?>', '_blank');
    }
    
    function fillExtractedForm() {
        document.getElementById('editableFormOverlay').style.display = 'block';
    }
    
    function closeEditableForm() {
        document.getElementById('editableFormOverlay').style.display = 'none';
    }
    
    function saveFormData() {
        // Save form data implementation
        console.log('Saving form data...');
    }
    
    function generateFilledPDF() {
        // Generate filled PDF implementation
        console.log('Generating filled PDF...');
    }
    </script>
    
    <style>
    .extracted-template-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .template-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2c3e50;
    }
    
    .template-header h3 {
        color: #2c3e50;
        font-size: 28px;
        margin: 0 0 10px 0;
    }
    
    .template-info {
        color: #7f8c8d;
        font-size: 14px;
        margin: 0;
    }
    
    .employee-info-section {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        border-left: 4px solid #1976d2;
    }
    
    .employee-info-section h4 {
        color: #1976d2;
        margin: 0 0 15px 0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-item label {
        font-weight: 600;
        color: #1565c0;
        margin-bottom: 5px;
    }
    
    .info-item span {
        color: #2c3e50;
        font-size: 15px;
        font-weight: 500;
    }
    
    .template-content-section {
        margin-bottom: 30px;
    }
    
    .content-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        border-bottom: none;
    }
    
    .content-label {
        font-weight: 600;
        color: #495057;
        font-size: 16px;
    }
    
    .toolbar-right {
        display: flex;
        gap: 10px;
    }
    
    .extracted-content {
        border: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px;
        background: white;
    }
    
    .content-display {
        padding: 30px;
        min-height: 400px;
        font-family: 'Times New Roman', serif;
        line-height: 1.6;
        white-space: pre-wrap;
        overflow-wrap: break-word;
    }
    
    .editable-form-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 1000;
        display: none;
        overflow-y: auto;
    }
    
    .overlay-content {
        background: white;
        margin: 20px auto;
        max-width: 900px;
        border-radius: 8px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .overlay-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }
    
    .close-overlay {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6c757d;
    }
    
    .instructions-section {
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #f39c12;
    }
    
    .instructions-section h4 {
        color: #e67e22;
        margin: 0 0 15px 0;
    }
    
    .instructions-section ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .instructions-section li {
        margin-bottom: 10px;
        color: #424242;
        font-size: 14px;
    }
    
    .content-line {
        padding: 2px 0;
        line-height: 1.6;
        border-bottom: 1px solid transparent;
        transition: border-color 0.3s ease;
    }
    
    .content-line:hover {
        border-bottom-color: #e9ecef;
        background: #f8f9fa;
    }
    
    .form-field-line {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 8px 12px;
        margin: 5px 0;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .auto-filled-line {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 1px solid #c3e6cb;
        padding: 8px 12px;
        margin: 5px 0;
        border-radius: 4px;
        font-weight: 600;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .auto-filled-line::before {
        content: "✅ ";
        color: #28a745;
        font-weight: bold;
        margin-right: 5px;
    }
    
    .content-display {
        padding: 30px;
        min-height: 400px;
        font-family: 'Times New Roman', serif;
        line-height: 1.6;
        white-space: pre-wrap;
        overflow-wrap: break-word;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    
    .content-field {
        background: #e3f2fd;
        border: 1px solid #ffeaa7;
        padding: 4px 8px;
        margin: 2px 0;
        border-radius: 4px;
        font-weight: 500;
        color: #856404;
    }
    
    @media (max-width: 768px) {
        .content-toolbar {
            flex-direction: column;
            gap: 10px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .overlay-content {
            margin: 10px;
            max-width: none;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
/**
 * Process extracted content for display with smart auto-fill
 */
function processExtractedContent($content, $employee_data) {
    // Clean up the extracted content
    $content = trim($content);
    
    // Enhanced employee data replacements with more patterns
    $replacements = [
        // Standard placeholders
        '{{FULL_NAME}}' => $employee_data['full_name'] ?? '',
        '{{POSITION}}' => $employee_data['position'] ?? '',
        '{{DEPARTMENT}}' => $employee_data['department'] ?? '',
        '{{EMPLOYEE_NUMBER}}' => $employee_data['employee_number'] ?? '',
        '[FULL_NAME]' => $employee_data['full_name'] ?? '',
        '[POSITION]' => $employee_data['position'] ?? '',
        '[DEPARTMENT]' => $employee_data['department'] ?? '',
        '[EMPLOYEE_NUMBER]' => $employee_data['employee_number'] ?? '',
        
        // Filipino language placeholders
        '{{PANGALAN}}' => $employee_data['full_name'] ?? '',
        '{{PUWESTO}}' => $employee_data['position'] ?? '',
        '{{DEPARTAMENTO}}' => $employee_data['department'] ?? '',
        '{{EMPLOYEE_NO}}' => $employee_data['employee_number'] ?? '',
        '[PANGALAN]' => $employee_data['full_name'] ?? '',
        '[PUWESTO]' => $employee_data['position'] ?? '',
        '[DEPARTAMENTO]' => $employee_data['department'] ?? '',
        '[EMPLOYEE_NO]' => $employee_data['employee_number'] ?? '',
        
        // Common field labels
        'Name:' => 'Name: ' . ($employee_data['full_name'] ?? ''),
        'Position:' => 'Position: ' . ($employee_data['position'] ?? ''),
        'Department:' => 'Department: ' . ($employee_data['department'] ?? ''),
        'Employee No.:' => 'Employee No.: ' . ($employee_data['employee_number'] ?? ''),
        'Pangalan:' => 'Pangalan: ' . ($employee_data['full_name'] ?? ''),
        'Pwesto:' => 'Pwesto: ' . ($employee_data['position'] ?? ''),
        'Departamento:' => 'Departamento: ' . ($employee_data['department'] ?? ''),
        'Employee No.:' => 'Employee No.: ' . ($employee_data['employee_number'] ?? ''),
    ];
    
    foreach ($replacements as $placeholder => $value) {
        $content = str_replace($placeholder, $value, $content);
    }
    
    // Smart field detection and auto-fill
    $content = detectAndFillFormFields($content, $employee_data);
    
    // Format content for display with auto-filled fields
    $lines = explode("\n", $content);
    $formatted_content = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Check if this line contains form fields or labels
            if (isFormLine($line)) {
                $formatted_content .= '<div class="form-field-line">' . htmlspecialchars($line) . '</div>';
            } elseif (isEmployeeInfoLine($line)) {
                // Auto-fill employee information lines
                $filled_line = autoFillEmployeeLine($line, $employee_data);
                $formatted_content .= '<div class="auto-filled-line">' . htmlspecialchars($filled_line) . '</div>';
            } else {
                $formatted_content .= '<div class="content-line">' . htmlspecialchars($line) . '</div>';
            }
        } else {
            $formatted_content .= '<br>';
        }
    }
    
    return $formatted_content;
}

/**
 * Detect and fill form fields in template content
 */
function detectAndFillFormFields($content, $employee_data) {
    // Pattern to detect form fields and labels
    $patterns = [
        '/Name\s*[:\-]\s*(.+?)/i',
        '/Position\s*[:\-]\s*(.+?)/i', 
        '/Department\s*[:\-]\s*(.+?)/i',
        '/Employee\s*(?:No\.?|Number)\s*[:\-]\s*(.+?)/i',
        '/Pangalan\s*[:\-]\s*(.+?)/i',
        '/Pwesto\s*[:\-]\s*(.+?)/i',
        '/Departamento\s*[:\-]\s*(.+?)/i',
    ];
    
    $replacements = [
        'Name:' => 'Name: ' . ($employee_data['full_name'] ?? ''),
        'Position:' => 'Position: ' . ($employee_data['position'] ?? ''),
        'Department:' => 'Department: ' . ($employee_data['department'] ?? ''),
        'Employee No.:' => 'Employee No.: ' . ($employee_data['employee_number'] ?? ''),
        'Employee Number:' => 'Employee Number: ' . ($employee_data['employee_number'] ?? ''),
        'Pangalan:' => 'Pangalan: ' . ($employee_data['full_name'] ?? ''),
        'Pwesto:' => 'Pwesto: ' . ($employee_data['position'] ?? ''),
        'Departamento:' => 'Departamento: ' . ($employee_data['department'] ?? ''),
    ];
    
    foreach ($patterns as $pattern) {
        $content = preg_replace_callback($pattern, function($matches) use ($replacements) {
            $full_match = $matches[0];
            $label_part = $matches[1] ?? '';
            
            // Find the corresponding replacement
            foreach ($replacements as $key => $value) {
                if (stripos($full_match, $key) !== false) {
                    return $value;
                }
            }
            
            return $full_match;
        }, $content);
    }
    
    return $content;
}

/**
 * Check if line contains form fields
 */
function isFormLine($line) {
    $form_indicators = [
        '_____', '=====', '______',
        '[', ']', '{', '}',
        '□', '☐', '◻', '▭',
        'Signature:', 'Date:', 'Year:', 'Amount:',
        'Cost:', 'Value:', 'Balance:'
    ];
    
    foreach ($form_indicators as $indicator) {
        if (strpos($line, $indicator) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if line contains employee information
 */
function isEmployeeInfoLine($line) {
    $employee_patterns = [
        '/name\s*[:\-]/i',
        '/position\s*[:\-]/i',
        '/department\s*[:\-]/i',
        '/employee\s*(?:no\.?|number)\s*[:\-]/i',
        '/pangalan\s*[:\-]/i',
        '/pwesto\s*[:\-]/i',
        '/departamento\s*[:\-]/i',
    ];
    
    foreach ($employee_patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Auto-fill employee information line
 */
function autoFillEmployeeLine($line, $employee_data) {
    // Extract the label and preserve format
    if (preg_match('/^(.*?)(\s*[:\-]\s*)(.*)$/', $line, $matches)) {
        $label = trim($matches[1]);
        $separator = $matches[2];
        
        // Determine which employee field to use
        $value = '';
        if (preg_match('/name|pangalan/i', $label)) {
            $value = $employee_data['full_name'] ?? '';
        } elseif (preg_match('/position|pwesto/i', $label)) {
            $value = $employee_data['position'] ?? '';
        } elseif (preg_match('/department|departamento/i', $label)) {
            $value = $employee_data['department'] ?? '';
        } elseif (preg_match('/employee\s*(?:no\.?|number)/i', $label)) {
            $value = $employee_data['employee_number'] ?? '';
        }
        
        return $label . $separator . $value;
    }
    
    return $line;
}

/**
 * Generate editable form based on extracted content
 */
function generateEditableForm($content, $employee_data) {
    ob_start();
    ?>
    <div class="editable-form-content">
        <h4>SALN Information Entry</h4>
        
        <div class="form-section">
            <h5>Real Properties</h5>
            <table class="editable-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Year Acquired</th>
                        <th>Acquisition Cost</th>
                        <th>Current FMV</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="real_prop_desc[]" placeholder="Property description"></td>
                        <td><input type="text" name="real_prop_loc[]" placeholder="Location"></td>
                        <td><input type="number" name="real_prop_year[]" placeholder="Year"></td>
                        <td><input type="number" step="0.01" name="real_prop_cost[]" placeholder="0.00"></td>
                        <td><input type="number" step="0.01" name="real_prop_fmv[]" placeholder="0.00"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline" onclick="addEditableRow('real_prop')">+ Add Property</button>
        </div>
        
        <div class="form-section">
            <h5>Personal Properties</h5>
            <table class="editable-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Year Acquired</th>
                        <th>Acquisition Cost</th>
                        <th>Current FMV</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="personal_prop_desc[]" placeholder="Property description"></td>
                        <td><input type="number" name="personal_prop_year[]" placeholder="Year"></td>
                        <td><input type="number" step="0.01" name="personal_prop_cost[]" placeholder="0.00"></td>
                        <td><input type="number" step="0.01" name="personal_prop_fmv[]" placeholder="0.00"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline" onclick="addEditableRow('personal_prop')">+ Add Property</button>
        </div>
        
        <div class="form-section">
            <h5>Liabilities</h5>
            <table class="editable-table">
                <thead>
                    <tr>
                        <th>Creditor</th>
                        <th>Nature</th>
                        <th>Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="creditor[]" placeholder="Creditor name"></td>
                        <td><input type="text" name="liability_nature[]" placeholder="Nature of liability"></td>
                        <td><input type="number" step="0.01" name="liability_balance[]" placeholder="0.00"></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline" onclick="addEditableRow('liability')">+ Add Liability</button>
        </div>
        
        <div class="form-section">
            <h5>Net Worth Calculation</h5>
            <div class="calculation-grid">
                <div class="calc-item">
                    <label>Total Assets:</label>
                    <input type="number" id="editableTotalAssets" readonly class="calc-field">
                </div>
                <div class="calc-item">
                    <label>Total Liabilities:</label>
                    <input type="number" id="editableTotalLiabilities" readonly class="calc-field">
                </div>
                <div class="calc-item">
                    <label><strong>Net Worth:</strong></label>
                    <input type="number" id="editableNetWorth" readonly class="calc-field">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-primary" onclick="saveFormData()">
                <i class="fas fa-save"></i> Save Information
            </button>
            <button type="button" class="btn btn-outline" onclick="generateFilledPDF()">
                <i class="fas fa-file-pdf"></i> Generate Filled PDF
            </button>
        </div>
    </div>
    
    <style>
    .editable-form-content {
        padding: 20px;
    }
    
    .editable-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .editable-table th,
    .editable-table td {
        border: 1px solid #dee2e6;
        padding: 10px;
        text-align: left;
    }
    
    .editable-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .editable-table input {
        width: 100%;
        border: 1px solid #ced4da;
        padding: 8px;
        border-radius: 4px;
    }
    
    .editable-table input:focus {
        outline: 2px solid #007bff;
        outline-offset: -2px;
    }
    
    .calculation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    
    .calc-item {
        display: flex;
        flex-direction: column;
    }
    
    .calc-item label {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .calc-field {
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #f8f9fa;
        font-weight: 600;
    }
    </style>
    
    <script>
    function addEditableRow(type) {
        console.log('Adding editable row for:', type);
        // Implementation for adding editable rows
    }
    
    // Auto-calculation for editable form
    document.addEventListener('DOMContentLoaded', function() {
        const editableInputs = document.querySelectorAll('.editable-form-content input[type="number"]');
        editableInputs.forEach(input => {
            input.addEventListener('input', calculateEditableNetWorth);
        });
    });
    
    function calculateEditableNetWorth() {
        let totalAssets = 0;
        let totalLiabilities = 0;
        
        // Calculate from editable form fields
        document.querySelectorAll('input[name*="fmv"]').forEach(input => {
            totalAssets += parseFloat(input.value) || 0;
        });
        
        document.querySelectorAll('input[name="liability_balance[]"]').forEach(input => {
            totalLiabilities += parseFloat(input.value) || 0;
        });
        
        const totalAssetsField = document.getElementById('editableTotalAssets');
        const totalLiabilitiesField = document.getElementById('editableTotalLiabilities');
        const netWorthField = document.getElementById('editableNetWorth');
        
        if (totalAssetsField) totalAssetsField.value = totalAssets.toFixed(2);
        if (totalLiabilitiesField) totalLiabilitiesField.value = totalLiabilities.toFixed(2);
        if (netWorthField) netWorthField.value = (totalAssets - totalLiabilities).toFixed(2);
    }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Fallback display when extraction fails
 */
function displayTemplateFallback($file_path, $template_name, $employee_data, $file_extension) {
    ob_start();
    ?>
    <div class="fallback-template-container">
        <div class="template-header">
            <h3>Statement of Assets, Liabilities and Net Worth (SALN)</h3>
            <p class="template-info">
                Template: <?php echo htmlspecialchars($template_name); ?> | 
                Format: <?php echo strtoupper($file_extension); ?> | 
                Original File Display
            </p>
        </div>
        
        <!-- Employee Information (Auto-filled) -->
        <div class="employee-info-section">
            <h4>Employee Information (Auto-filled)</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($employee_data['full_name'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Position:</label>
                    <span><?php echo htmlspecialchars($employee_data['position'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Department:</label>
                    <span><?php echo htmlspecialchars($employee_data['department'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Employee Number:</label>
                    <span><?php echo htmlspecialchars($employee_data['employee_number'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Template Display -->
        <div class="template-display">
            <div class="template-toolbar">
                <div class="toolbar-left">
                    <span class="template-label">📄 Original Template File:</span>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-sm btn-outline" onclick="downloadTemplate()">
                        <i class="fas fa-download"></i> Download Template
                    </button>
                </div>
            </div>
            
            <div class="template-viewer">
                <?php if ($file_extension === 'pdf'): ?>
                    <iframe src="<?php echo htmlspecialchars($file_path); ?>" 
                            width="100%" 
                            height="800px"
                            style="border: 1px solid #ddd; border-radius: 8px;">
                    </iframe>
                <?php else: ?>
                    <div class="word-document-container">
                        <div class="doc-preview">
                            <i class="fas fa-file-word" style="font-size: 48px; color: #2b579a;"></i>
                            <h4>Microsoft Word Template</h4>
                            <p>This SALN template is in Microsoft Word format.</p>
                            <div class="doc-actions">
                                <button class="btn btn-primary" onclick="downloadTemplate()">
                                    <i class="fas fa-download"></i> Download Template
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function downloadTemplate() {
        window.open('<?php echo htmlspecialchars($file_path); ?>', '_blank');
    }
    </script>
    
    <style>
    .fallback-template-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .template-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2c3e50;
    }
    
    .template-header h3 {
        color: #2c3e50;
        font-size: 28px;
        margin: 0 0 10px 0;
    }
    
    .template-info {
        color: #7f8c8d;
        font-size: 14px;
        margin: 0;
    }
    
    .employee-info-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .employee-info-section h4 {
        color: #2c3e50;
        margin: 0 0 15px 0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-item label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .info-item span {
        color: #2c3e50;
        font-size: 15px;
    }
    
    .template-display {
        margin-bottom: 30px;
    }
    
    .template-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #e9ecef;
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        border-bottom: none;
    }
    
    .template-label {
        font-weight: 600;
        color: #495057;
    }
    
    .toolbar-right {
        display: flex;
        gap: 10px;
    }
    
    .template-viewer {
        border: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px;
        background: white;
    }
    
    .word-document-container {
        padding: 40px;
        text-align: center;
    }
    
    .doc-preview h4 {
        color: #2c3e50;
        margin: 20px 0 10px 0;
    }
    
    .doc-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate SALN form from uploaded template with auto-fill
 */
function generateSALNFromTemplate($template, $employee_data) {
    // Store template path globally for JavaScript access
    $GLOBALS['template_file_path'] = $template['file_path'];
    
    // Try to extract and display content from the uploaded template
    return extractTemplateContent($template['file_path'], $template['template_name'], $employee_data);
}

/**
 * Generate CSC form from uploaded template with auto-fill
 */
function generateCSCFromTemplate($template, $employee_data) {
    ob_start();
    ?>
    <div class="csc-template-container">
        <div class="template-header">
            <h3>CSC Form</h3>
            <p class="template-info">Template: <?php echo htmlspecialchars($template['template_name']); ?> (Version: <?php echo htmlspecialchars($template['version']); ?>)</p>
        </div>
        
        <!-- Employee Information (Auto-filled) -->
        <div class="employee-info-section">
            <h4>Employee Information</h4>
            <div class="info-grid">
                <div class="info-item">
                    <label>Full Name:</label>
                    <span><?php echo htmlspecialchars($employee_data['full_name'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Position:</label>
                    <span><?php echo htmlspecialchars($employee_data['position'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Department:</label>
                    <span><?php echo htmlspecialchars($employee_data['department'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <label>Employee Number:</label>
                    <span><?php echo htmlspecialchars($employee_data['employee_number'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Template Display -->
        <div class="template-display">
            <div class="template-toolbar">
                <div class="toolbar-left">
                    <span class="template-label">Official CSC Form Template:</span>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-sm btn-outline" onclick="downloadTemplate()">
                        <i class="fas fa-download"></i> Download Template
                    </button>
                </div>
            </div>
            
            <div class="template-viewer">
                <?php
                $file_extension = strtolower(pathinfo($template['file_path'], PATHINFO_EXTENSION));
                
                if ($file_extension === 'pdf') {
                    ?>
                    <iframe src="<?php echo htmlspecialchars($template['file_path']); ?>" 
                            width="100%" 
                            height="800px"
                            style="border: 1px solid #ddd; border-radius: 8px;">
                    </iframe>
                    <?php
                } elseif (in_array($file_extension, ['doc', 'docx'])) {
                    ?>
                    <div class="word-document-container">
                        <div class="doc-preview">
                            <i class="fas fa-file-word" style="font-size: 48px; color: #2b579a;"></i>
                            <h4>Microsoft Word Template</h4>
                            <p>This CSC form template is in Microsoft Word format.</p>
                            <div class="doc-actions">
                                <button class="btn btn-primary" onclick="downloadTemplate()">
                                    <i class="fas fa-download"></i> Download Template
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
    function downloadTemplate() {
        window.open('<?php echo htmlspecialchars($template['file_path']); ?>', '_blank');
    }
    </script>
    
    <style>
    .csc-template-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .template-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2c3e50;
    }
    
    .template-header h3 {
        color: #2c3e50;
        font-size: 28px;
        margin: 0 0 10px 0;
    }
    
    .template-info {
        color: #7f8c8d;
        font-size: 14px;
        margin: 0;
    }
    
    .employee-info-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .employee-info-section h4 {
        color: #2c3e50;
        margin: 0 0 15px 0;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-item label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .info-item span {
        color: #2c3e50;
        font-size: 15px;
    }
    
    .template-display {
        margin-bottom: 30px;
    }
    
    .template-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #e9ecef;
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        border-bottom: none;
    }
    
    .template-label {
        font-weight: 600;
        color: #495057;
    }
    
    .toolbar-right {
        display: flex;
        gap: 10px;
    }
    
    .template-viewer {
        border: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px;
        background: white;
    }
    
    .word-document-container {
        padding: 40px;
        text-align: center;
    }
    
    .doc-preview h4 {
        color: #2c3e50;
        margin: 20px 0 10px 0;
    }
    
    .doc-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    </style>
    <?php
    return ob_get_clean();
}
?>
