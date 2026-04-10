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
 * Generate PDF form with auto-fill functionality
 */
function generatePDFFormWithAutofill($template, $employee_data, $form_type) {
    ob_start();
    ?>
    <div class="pdf-template-container">
        <div class="template-header">
            <h3><?php echo strtoupper($form_type); ?> Form</h3>
            <p class="template-info">Template: <?php echo htmlspecialchars($template['template_name']); ?> (Version: <?php echo htmlspecialchars($template['version']); ?>)</p>
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
        
        <!-- PDF Preview and Auto-Fill Options -->
        <div class="pdf-section">
            <div class="pdf-toolbar">
                <div class="toolbar-left">
                    <span class="pdf-label">📄 PDF Template Preview:</span>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-sm btn-outline" onclick="downloadOriginalPDF()">
                        <i class="fas fa-download"></i> Download Original
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="generateFilledPDF()">
                        <i class="fas fa-magic"></i> Auto-Fill PDF
                    </button>
                </div>
            </div>
            
            <div class="pdf-viewer">
                <iframe src="<?php echo htmlspecialchars($template['file_path']); ?>" 
                        width="100%" 
                        height="600px"
                        style="border: 1px solid #ddd; border-radius: 0 0 8px 8px;">
                    <p>Your browser does not support iframes. 
                       <a href="<?php echo htmlspecialchars($template['file_path']); ?>" target="_blank">Click here to view the PDF</a>
                    </p>
                </iframe>
            </div>
        </div>
        
        <!-- Auto-Fill Data Preview -->
        <div class="autofill-preview">
            <h4>Auto-Fill Data Preview</h4>
            <p>The following information will be automatically filled into the PDF:</p>
            
            <div class="data-preview-grid">
                <div class="data-item">
                    <label>Name:</label>
                    <span><?php echo htmlspecialchars($employee_data['full_name'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Position:</label>
                    <span><?php echo htmlspecialchars($employee_data['position'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Department:</label>
                    <span><?php echo htmlspecialchars($employee_data['department'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Employee Number:</label>
                    <span><?php echo htmlspecialchars($employee_data['employee_number'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Date of Birth:</label>
                    <span><?php echo htmlspecialchars($employee_data['birthdate'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Civil Status:</label>
                    <span><?php echo htmlspecialchars($employee_data['civil_status'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Monthly Salary:</label>
                    <span><?php echo htmlspecialchars($employee_data['monthly_salary'] ?? 'Not available'); ?></span>
                </div>
                <div class="data-item">
                    <label>Spouse Name:</label>
                    <span><?php echo htmlspecialchars($employee_data['spouse_name'] ?? 'Not available'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="instructions-section">
            <h4>How to Use PDF Auto-Fill</h4>
            <ul>
                <li>📋 The PDF template is displayed above for your reference.</li>
                <li>👤 Your personal information has been retrieved from your PDS records.</li>
                <li>✨ Click "Auto-Fill PDF" to generate a filled version of the form.</li>
                <li>💾 The filled PDF will be automatically downloaded to your device.</li>
                <li>📝 You can then print or save the filled PDF as needed.</li>
                <li>🔄 If you need to update your information, please contact HR.</li>
            </ul>
        </div>
        
        <!-- Hidden form for PDF generation -->
        <form id="pdfFillForm" method="POST" action="generate_filled_pdf.php" target="_blank" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
            <input type="hidden" name="form_type" value="<?php echo $form_type; ?>">
            <input type="hidden" name="employee_data" value="<?php echo htmlspecialchars(json_encode($employee_data)); ?>">
        </form>
    </div>

    <script>
    function downloadOriginalPDF() {
        window.open('<?php echo htmlspecialchars($template['file_path']); ?>', '_blank');
    }
    
    function generateFilledPDF() {
        // Show loading state
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        button.disabled = true;
        
        // Submit the hidden form to generate filled PDF
        const form = document.getElementById('pdfFillForm');
        form.submit();
        
        // Reset button after a delay (since we can't detect when download completes)
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 3000);
    }
    </script>

    <style>
    .pdf-template-container {
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
    
    .pdf-section {
        margin-bottom: 30px;
    }
    
    .pdf-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 8px 8px 0 0;
        border: 1px solid #dee2e6;
        border-bottom: none;
    }
    
    .pdf-label {
        font-weight: 600;
        color: #495057;
        font-size: 16px;
    }
    
    .toolbar-right {
        display: flex;
        gap: 10px;
    }
    
    .pdf-viewer {
        border: 1px solid #dee2e6;
        border-radius: 0 0 8px 8px;
        background: white;
        overflow: hidden;
    }
    
    .pdf-viewer iframe {
        display: block;
        width: 100%;
        border: none;
    }
    
    .autofill-preview {
        background: linear-gradient(135deg, #fff3e0, #ffe0b2);
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        border-left: 4px solid #f39c12;
    }
    
    .autofill-preview h4 {
        color: #e67e22;
        margin: 0 0 15px 0;
    }
    
    .data-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
    }
    
    .data-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: white;
        border-radius: 6px;
        border: 1px solid #ffeaa7;
    }
    
    .data-item label {
        font-weight: 600;
        color: #856404;
    }
    
    .data-item span {
        color: #2c3e50;
        font-weight: 500;
    }
    
    .instructions-section {
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #8e24aa;
    }
    
    .instructions-section h4 {
        color: #6a1b9a;
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
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid #007bff;
        color: #007bff;
    }
    
    .btn:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .pdf-toolbar {
            flex-direction: column;
            gap: 10px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .data-preview-grid {
            grid-template-columns: 1fr;
        }
        
        .pdf-viewer iframe {
            height: 400px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate HTML SALN form with auto-fill
 */
function generateHTMLSALNForm($template, $employee_data) {
        <div class="template-header">
            <h3>Statement of Assets, Liabilities and Net Worth (SALN)</h3>
            <p class="template-info">Template: <?php echo htmlspecialchars($template['template_name']); ?> (Version: <?php echo htmlspecialchars($template['version']); ?>)</p>
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
        
        <!-- SALN Form -->
        <form id="salnForm" method="POST" action="process_saln.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
            
            <!-- Personal Information Section -->
            <div class="form-section">
                <h4>1. Personal Information</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="declarant_name">Name of Declarant</label>
                        <input type="text" id="declarant_name" name="declarant_name" 
                               value="<?php echo htmlspecialchars($employee_data['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position/Title</label>
                        <input type="text" id="position" name="position" 
                               value="<?php echo htmlspecialchars($employee_data['position'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department">Department/Agency/Office</label>
                        <input type="text" id="department" name="department" 
                               value="<?php echo htmlspecialchars($employee_data['department'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="annual_salary">Annual Salary</label>
                        <input type="number" id="annual_salary" name="annual_salary" step="0.01" 
                               value="<?php echo htmlspecialchars($employee_data['monthly_salary'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="spouse_name">Name of Spouse</label>
                        <input type="text" id="spouse_name" name="spouse_name" 
                               value="<?php echo htmlspecialchars($employee_data['spouse_name'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Assets Section -->
            <div class="form-section">
                <h4>2. Assets</h4>
                
                <!-- Real Properties -->
                <div class="sub-section">
                    <h5>a. Real Properties</h5>
                    <div id="real-properties-container">
                        <div class="property-row">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="real_property_description[]" placeholder="e.g., Residential Lot">
                                </div>
                                <div class="form-group">
                                    <label>Kind</label>
                                    <input type="text" name="real_property_kind[]" placeholder="e.g., Land">
                                </div>
                                <div class="form-group">
                                    <label>Exact Location</label>
                                    <input type="text" name="real_property_location[]" placeholder="Address">
                                </div>
                                <div class="form-group">
                                    <label>Assessed Value</label>
                                    <input type="number" name="real_property_value[]" step="0.01" class="asset-value">
                                </div>
                                <div class="form-group">
                                    <label>Year Acquired</label>
                                    <input type="number" name="real_property_year[]" min="1900" max="<?php echo date('Y'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Mode of Acquisition</label>
                                    <select name="real_property_mode[]">
                                        <option value="">Select</option>
                                        <option value="Purchase">Purchase</option>
                                        <option value="Inheritance">Inheritance</option>
                                        <option value="Gift">Gift</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addRealProperty()">+ Add Real Property</button>
                </div>
                
                <!-- Personal Properties -->
                <div class="sub-section">
                    <h5>b. Personal Properties</h5>
                    <div id="personal-properties-container">
                        <div class="property-row">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="personal_property_description[]" placeholder="e.g., Car, Jewelry">
                                </div>
                                <div class="form-group">
                                    <label>Year Acquired</label>
                                    <input type="number" name="personal_property_year[]" min="1900" max="<?php echo date('Y'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Acquisition Cost</label>
                                    <input type="number" name="personal_property_cost[]" step="0.01" class="asset-value">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addPersonalProperty()">+ Add Personal Property</button>
                </div>
            </div>
            
            <!-- Liabilities Section -->
            <div class="form-section">
                <h4>3. Liabilities</h4>
                <div id="liabilities-container">
                    <div class="liability-row">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nature</label>
                                <input type="text" name="liability_nature[]" placeholder="e.g., Loan, Mortgage">
                            </div>
                            <div class="form-group">
                                <label>Name of Creditor</label>
                                <input type="text" name="liability_creditor[]" placeholder="Bank/Individual name">
                            </div>
                            <div class="form-group">
                                <label>Outstanding Balance</label>
                                <input type="number" name="liability_balance[]" step="0.01" class="liability-value">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="addLiability()">+ Add Liability</button>
            </div>
            
            <!-- Net Worth Calculation -->
            <div class="form-section">
                <h4>4. Net Worth</h4>
                <div class="net-worth-summary">
                    <div class="summary-row">
                        <label>Total Assets:</label>
                        <span id="totalAssets">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <label>Total Liabilities:</label>
                        <span id="totalLiabilities">₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <label>Net Worth:</label>
                        <span id="netWorth">₱0.00</span>
                    </div>
                </div>
            </div>
            
            <!-- Declaration and Signature -->
            <div class="form-section">
                <h4>5. Declaration</h4>
                <div class="declaration-section">
                    <p>I declare under oath that this Statement of Assets, Liabilities and Net Worth is a full, true and correct statement of all my assets, liabilities and net worth as of <input type="date" name="as_of_date" value="<?php echo date('Y-12-31'); ?>" required>, including those of my spouse and unmarried children under eighteen (18) years of age living in my household.</p>
                    
                    <div class="signature-section">
                        <div class="form-group">
                            <label for="signature">Signature</label>
                            <input type="text" id="signature" name="signature" placeholder="Type your full name as signature" required>
                        </div>
                        <div class="form-group">
                            <label for="date_signed">Date Signed</label>
                            <input type="date" id="date_signed" name="date_signed" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save SALN
                </button>
                <button type="button" class="btn btn-success" onclick="printForm()">
                    <i class="fas fa-print"></i> Print Form
                </button>
            </div>
        </form>
    </div>

    <script>
    // Auto-calculate net worth
    function calculateNetWorth() {
        let totalAssets = 0;
        let totalLiabilities = 0;
        
        // Calculate total assets
        document.querySelectorAll('.asset-value').forEach(input => {
            totalAssets += parseFloat(input.value) || 0;
        });
        
        // Calculate total liabilities
        document.querySelectorAll('.liability-value').forEach(input => {
            totalLiabilities += parseFloat(input.value) || 0;
        });
        
        // Update display
        document.getElementById('totalAssets').textContent = '₱' + totalAssets.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalLiabilities').textContent = '₱' + totalLiabilities.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('netWorth').textContent = '₱' + (totalAssets - totalLiabilities).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Add real property row
    function addRealProperty() {
        const container = document.getElementById('real-properties-container');
        const newRow = container.querySelector('.property-row').cloneNode(true);
        // Clear input values
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
        container.appendChild(newRow);
    }

    // Add personal property row
    function addPersonalProperty() {
        const container = document.getElementById('personal-properties-container');
        const newRow = container.querySelector('.property-row').cloneNode(true);
        // Clear input values
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(newRow);
    }

    // Add liability row
    function addLiability() {
        const container = document.getElementById('liabilities-container');
        const newRow = container.querySelector('.liability-row').cloneNode(true);
        // Clear input values
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        container.appendChild(newRow);
    }

    function downloadTemplate() {
        window.open('<?php echo htmlspecialchars($template['file_path']); ?>', '_blank');
    }

    function printForm() {
        window.print();
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Calculate net worth on input changes
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('asset-value') || e.target.classList.contains('liability-value')) {
                calculateNetWorth();
            }
        });
        
        // Initial calculation
        calculateNetWorth();
    });
    </script>

    <style>
    .saln-template-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .form-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .form-section h4 {
        color: #495057;
        margin-bottom: 15px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 5px;
    }
    
    .sub-section h5 {
        color: #6c757d;
        margin: 15px 0 10px 0;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
    }
    
    .form-group input, .form-group select {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .property-row, .liability-row {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .net-worth-summary {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 20px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .summary-row.total {
        font-weight: bold;
        font-size: 18px;
        border-bottom: none;
        border-top: 2px solid #007bff;
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .declaration-section {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 20px;
    }
    
    .declaration-section p {
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .signature-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid #007bff;
        color: #007bff;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Generate HTML CSC form with auto-fill
 */
function generateHTMLCSCForm($template, $employee_data) {
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
        
        <!-- CSC Form -->
        <form id="cscForm" method="POST" action="process_csc.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
            
            <!-- Form Selection -->
            <div class="form-section">
                <h4>1. Select CSC Form Type</h4>
                <div class="form-group">
                    <label for="csc_form_type">CSC Form Type</label>
                    <select id="csc_form_type" name="csc_form_type" required onchange="updateFormFields()">
                        <option value="">Select Form Type</option>
                        <option value="csc_form_1">CSC Form 1 - Application for Leave</option>
                        <option value="csc_form_2">CSC Form 2 - Daily Time Record</option>
                        <option value="csc_form_6">CSC Form 6 - Personal Data Sheet</option>
                        <option value="csc_form_48">CSC Form 48 - Application for Vacation Leave</option>
                        <option value="csc_form_212">CSC Form 212 - Personal Data Sheet Update</option>
                        <option value="other">Other CSC Form</option>
                    </select>
                </div>
                <div class="form-group" id="other_form_type" style="display: none;">
                    <label for="other_form_name">Specify Form Name</label>
                    <input type="text" id="other_form_name" name="other_form_name" placeholder="e.g., CSC Form 33">
                </div>
            </div>
            
            <!-- Personal Information Section -->
            <div class="form-section">
                <h4>2. Personal Information</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($employee_data['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="position_title">Position/Title</label>
                        <input type="text" id="position_title" name="position_title" 
                               value="<?php echo htmlspecialchars($employee_data['position'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department_office">Department/Office</label>
                        <input type="text" id="department_office" name="department_office" 
                               value="<?php echo htmlspecialchars($employee_data['department'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="employee_no">Employee Number</label>
                        <input type="text" id="employee_no" name="employee_no" 
                               value="<?php echo htmlspecialchars($employee_data['employee_number'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo htmlspecialchars($employee_data['birthdate'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="place_of_birth">Place of Birth</label>
                        <input type="text" id="place_of_birth" name="place_of_birth" 
                               value="<?php echo htmlspecialchars($employee_data['place_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="civil_status">Civil Status</label>
                        <select id="civil_status" name="civil_status">
                            <option value="">Select</option>
                            <option value="Single" <?php echo ($employee_data['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($employee_data['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Widowed" <?php echo ($employee_data['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                            <option value="Separated" <?php echo ($employee_data['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                            <option value="Divorced" <?php echo ($employee_data['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="citizenship">Citizenship</label>
                        <input type="text" id="citizenship" name="citizenship" 
                               value="<?php echo htmlspecialchars($employee_data['citizenship'] ?? 'Filipino'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="form-section">
                <h4>3. Contact Information</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="residential_address">Residential Address</label>
                        <textarea id="residential_address" name="residential_address" rows="3"><?php echo htmlspecialchars($employee_data['residential_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="permanent_address">Permanent Address</label>
                        <textarea id="permanent_address" name="permanent_address" rows="3"><?php echo htmlspecialchars($employee_data['permanent_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Telephone Number</label>
                        <input type="tel" id="telephone" name="telephone" 
                               value="<?php echo htmlspecialchars($employee_data['residential_telephone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="tel" id="mobile" name="mobile" 
                               value="<?php echo htmlspecialchars($employee_data['mobile'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($employee_data['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Purpose/Reason Section -->
            <div class="form-section">
                <h4>4. Purpose/Reason for Filing</h4>
                <div class="form-group">
                    <label for="purpose">Purpose/Reason</label>
                    <textarea id="purpose" name="purpose" rows="4" placeholder="State the purpose or reason for filing this CSC form..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="details">Additional Details</label>
                    <textarea id="details" name="details" rows="3" placeholder="Provide any additional information or details..."></textarea>
                </div>
            </div>
            
            <!-- Supporting Documents -->
            <div class="form-section">
                <h4>5. Supporting Documents</h4>
                <div class="form-group">
                    <label for="attachments">Attach Supporting Documents (if any)</label>
                    <input type="file" id="attachments" name="attachments[]" multiple 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="form-help">You can select multiple files. Supported formats: PDF, Word documents, images</small>
                </div>
            </div>
            
            <!-- Declaration and Signature -->
            <div class="form-section">
                <h4>6. Declaration and Signature</h4>
                <div class="declaration-section">
                    <p>I hereby certify that the information provided above is true and correct to the best of my knowledge and belief.</p>
                    
                    <div class="signature-section">
                        <div class="form-group">
                            <label for="signature">Signature</label>
                            <input type="text" id="signature" name="signature" 
                                   placeholder="Type your full name as signature" required>
                        </div>
                        <div class="form-group">
                            <label for="date_signed">Date Signed</label>
                            <input type="date" id="date_signed" name="date_signed" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">
                    <i class="fas fa-download"></i> Download Template
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save CSC Form
                </button>
                <button type="button" class="btn btn-success" onclick="printForm()">
                    <i class="fas fa-print"></i> Print Form
                </button>
            </div>
        </form>
    </div>

    <script>
    function updateFormFields() {
        const formType = document.getElementById('csc_form_type').value;
        const otherFormType = document.getElementById('other_form_type');
        
        if (formType === 'other') {
            otherFormType.style.display = 'block';
            document.getElementById('other_form_name').required = true;
        } else {
            otherFormType.style.display = 'none';
            document.getElementById('other_form_name').required = false;
        }
    }

    function downloadTemplate() {
        window.open('<?php echo htmlspecialchars($template['file_path']); ?>', '_blank');
    }

    function printForm() {
        window.print();
    }

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        const cscForm = document.getElementById('cscForm');
        if (cscForm) {
            cscForm.addEventListener('submit', function(e) {
                // Additional validation can be added here
                const formType = document.getElementById('csc_form_type').value;
                if (!formType) {
                    e.preventDefault();
                    alert('Please select a CSC form type.');
                    return false;
                }
            });
        }
    });
    </script>

    <style>
    .csc-template-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .form-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .form-section h4 {
        color: #495057;
        margin-bottom: 15px;
        border-bottom: 2px solid #28a745;
        padding-bottom: 5px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
    }
    
    .form-group input, .form-group select, .form-group textarea {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-help {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
    
    .declaration-section {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 20px;
    }
    
    .declaration-section p {
        line-height: 1.6;
        margin-bottom: 20px;
    }
    
    .signature-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #28a745;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-success {
        background: #007bff;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    </style>
    <?php
    return ob_get_clean();
}
    
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
