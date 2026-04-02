<?php
$pageTitle = 'Form Templates Management';
$activePage = 'form-templates.php';
require_once 'includes/auth.php';
require_roles(['superadmin']);
require_once 'includes/db.php';

$error = '';
$success = '';

// Handle success messages from redirect
if (isset($_GET['success']) && $_GET['success'] === 'uploaded') {
    $success = "Template uploaded successfully!";
}

// Handle form upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $form_type = $_POST['form_type'];
        $template_name = $_POST['template_name'];
        $version = $_POST['version'];
        
        // Get user ID from session (using correct session structure from auth.php)
        $user_id = $_SESSION['user']['id'] ?? null;
        
        if (!$user_id) {
            $error = "User not authenticated. Please log in again.";
        } else {
            // Validate file
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($_FILES['template_file']['type'], $allowed_types)) {
                $error = "Only PDF and Word documents are allowed.";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/form_templates/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $filename = $form_type . '_' . $version . '_' . time() . '_' . basename($_FILES['template_file']['name']);
                $filepath = $upload_dir . $filename;
                
                // Upload file
                if (move_uploaded_file($_FILES['template_file']['tmp_name'], $filepath)) {
                    try {
                        // Deactivate old templates of same type
                        $deactivate_sql = "UPDATE form_templates SET is_active = FALSE WHERE form_type = ?";
                        $deactivate_stmt = $pdo->prepare($deactivate_sql);
                        $deactivate_stmt->execute([$form_type]);
                        
                        // Insert new template
                        $sql = "INSERT INTO form_templates (form_type, template_name, file_path, version, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$form_type, $template_name, $filepath, $version, $user_id]);
                        
                        // Redirect to prevent form resubmission
                        header('Location: form-templates.php?success=uploaded');
                        exit;
                    } catch (PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                        // Clean up uploaded file if database insert failed
                        if (file_exists($filepath)) {
                            unlink($filepath);
                        }
                    }
                } else {
                    $error = "Failed to upload file.";
                }
            }
        }
    }
}

// Get current templates
$saln_templates = $pdo->prepare("SELECT * FROM form_templates WHERE form_type = 'saln' ORDER BY uploaded_at DESC");
$saln_templates->execute();

$csc_templates = $pdo->prepare("SELECT * FROM form_templates WHERE form_type = 'csc' ORDER BY uploaded_at DESC");
$csc_templates->execute();
?>

<?php require_once 'includes/header.php'; ?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Form Templates</span>
            <h3>Manage SALN & CSC Form Templates</h3>
        </div>
    </div>

    <?php if (!empty($error) && strlen(trim($error)) > 0): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success) && strlen(trim($success)) > 0): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; color: #155724; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="upload-section" style="margin-bottom: 30px;">
        <h4>Upload New Template</h4>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label for="form_type">Form Type</label>
                <select name="form_type" id="form_type" required>
                    <option value="">Select Form Type</option>
                    <option value="saln">SALN Form</option>
                    <option value="csc">CSC Form</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="template_name">Template Name</label>
                <input type="text" name="template_name" id="template_name" required 
                       placeholder="e.g., SALN 2024 Format">
            </div>
            
            <div class="form-group">
                <label for="version">Version</label>
                <input type="text" name="version" id="version" required 
                       placeholder="e.g., 2024.1">
            </div>
            
            <div class="form-group">
                <label for="template_file">Template File</label>
                <input type="file" name="template_file" id="template_file" required 
                       accept=".pdf,.doc,.docx">
            </div>
            
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Template
                </button>
            </div>
        </form>
    </div>

    <!-- Current Templates -->
    <div class="templates-grid">
        <div class="template-section">
            <div class="section-header">
                <h4><i class="fas fa-file-alt"></i> SALN Templates</h4>
                <button class="btn btn-sm btn-info" onclick="viewActiveForm('saln')">
                    <i class="fas fa-eye"></i> View Active Form
                </button>
            </div>
            <div class="template-list">
                <?php 
                $saln_templates->execute(); // Reset pointer
                $has_saln_templates = false;
                $active_found = false;
                while ($template = $saln_templates->fetch()): 
                    $has_saln_templates = true;
                    $is_active = $template['is_active'];
                    if ($is_active) $active_found = true;
                ?>
                    <div class="template-item <?php echo $is_active ? 'active' : 'inactive'; ?>">
                        <div class="template-info">
                            <h5>
                                <?php echo htmlspecialchars($template['template_name']); ?>
                                <?php if ($is_active): ?>
                                    <span class="current-badge">Currently Active</span>
                                <?php endif; ?>
                            </h5>
                            <p class="template-meta">
                                Version: <?php echo htmlspecialchars($template['version']); ?> | 
                                Uploaded: <?php echo date('M d, Y', strtotime($template['uploaded_at'])); ?>
                            </p>
                        </div>
                        <div class="template-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewTemplate('<?php echo htmlspecialchars($template['file_path']); ?>', '<?php echo htmlspecialchars($template['template_name']); ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if (!$is_active): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="activateTemplate(<?php echo $template['id']; ?>, 'saln')" title="Activate this template">
                                    <i class="fas fa-check"></i> Use This
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if (!$has_saln_templates): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-upload"></i>
                        <p>No SALN templates uploaded yet</p>
                        <button class="btn btn-outline" onclick="scrollToUpload()">
                            <i class="fas fa-plus"></i> Upload First Template
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="template-section">
            <div class="section-header">
                <h4><i class="fas fa-file-contract"></i> CSC Templates</h4>
                <button class="btn btn-sm btn-info" onclick="viewActiveForm('csc')">
                    <i class="fas fa-eye"></i> View Active Form
                </button>
            </div>
            <div class="template-list">
                <?php 
                $csc_templates->execute(); // Reset pointer
                $has_csc_templates = false;
                $active_found = false;
                while ($template = $csc_templates->fetch()): 
                    $has_csc_templates = true;
                    $is_active = $template['is_active'];
                    if ($is_active) $active_found = true;
                ?>
                    <div class="template-item <?php echo $is_active ? 'active' : 'inactive'; ?>">
                        <div class="template-info">
                            <h5>
                                <?php echo htmlspecialchars($template['template_name']); ?>
                                <?php if ($is_active): ?>
                                    <span class="current-badge">Currently Active</span>
                                <?php endif; ?>
                            </h5>
                            <p class="template-meta">
                                Version: <?php echo htmlspecialchars($template['version']); ?> | 
                                Uploaded: <?php echo date('M d, Y', strtotime($template['uploaded_at'])); ?>
                            </p>
                        </div>
                        <div class="template-actions">
                            <button class="btn btn-sm btn-primary" onclick="viewTemplate('<?php echo htmlspecialchars($template['file_path']); ?>', '<?php echo htmlspecialchars($template['template_name']); ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if (!$is_active): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="activateTemplate(<?php echo $template['id']; ?>, 'csc')" title="Activate this template">
                                    <i class="fas fa-check"></i> Use This
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if (!$has_csc_templates): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-upload"></i>
                        <p>No CSC templates uploaded yet</p>
                        <button class="btn btn-outline" onclick="scrollToUpload()">
                            <i class="fas fa-plus"></i> Upload First Template
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Template Viewer Modal -->
    <div id="templateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Template Viewer</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="templateViewer">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading template...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button id="downloadBtn" class="btn btn-primary" style="display:none;">
                    <i class="fas fa-download"></i> Download
                </button>
            </div>
        </div>
    </div>
</section>

<style>
.upload-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.templates-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.template-section h4 {
    margin-bottom: 15px;
    color: #333;
}

.template-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.template-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
}

.template-item.active {
    border-left: 4px solid #28a745;
}

.template-item.inactive {
    border-left: 4px solid #6c757d;
    opacity: 0.7;
}

.template-info h5 {
    margin: 0 0 5px 0;
    color: #333;
}

.template-meta {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.template-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>

<script>
// JavaScript functions for template viewing
function viewTemplate(filePath, templateName) {
    console.log('Viewing template:', { filePath, templateName });
    
    // Check if modal exists
    const modal = document.getElementById('templateModal');
    if (!modal) {
        alert('Modal not found. Please refresh the page and try again.');
        return;
    }
    
    document.getElementById('modalTitle').textContent = templateName;
    document.getElementById('templateViewer').innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading template...</p>
        </div>
    `;
    modal.style.display = 'block';
    document.getElementById('downloadBtn').style.display = 'inline-block';
    document.getElementById('downloadBtn').onclick = () => {
        console.log('Downloading file:', filePath);
        window.open(filePath, '_blank');
    };
    
    // Determine file type and display accordingly
    const fileExtension = filePath.split('.').pop().toLowerCase();
    console.log('File extension:', fileExtension);
    
    try {
        if (fileExtension === 'pdf') {
            document.getElementById('templateViewer').innerHTML = `
                <iframe src="${filePath}" width="100%" height="600px" style="border: none;"></iframe>
            `;
        } else if (fileExtension === 'doc' || fileExtension === 'docx') {
            document.getElementById('templateViewer').innerHTML = `
                <div class="document-preview">
                    <i class="fas fa-file-word" style="font-size: 48px; color: #2b579a; margin-bottom: 20px;"></i>
                    <h4>Word Document</h4>
                    <p>This is a Microsoft Word document.</p>
                    <p>Click the Download button to view or edit this template.</p>
                </div>
            `;
        } else {
            document.getElementById('templateViewer').innerHTML = `
                <div class="document-preview">
                    <i class="fas fa-file" style="font-size: 48px; color: #666; margin-bottom: 20px;"></i>
                    <h4>Document File</h4>
                    <p>Template file ready for download.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error displaying template:', error);
        document.getElementById('templateViewer').innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 20px;"></i>
                <h4>Error Loading Template</h4>
                <p>There was an error loading the template. Please try downloading it instead.</p>
            </div>
        `;
    }
}

function viewActiveForm(formType) {
    // Find the active template for the given form type using a more compatible approach
    let templateItems;
    
    if (formType.toLowerCase() === 'saln') {
        // Look for SALN templates (first section)
        templateItems = document.querySelectorAll('.template-section:first-child .template-item.active');
    } else if (formType.toLowerCase() === 'csc') {
        // Look for CSC templates (second section)
        templateItems = document.querySelectorAll('.template-section:last-child .template-item.active');
    } else {
        // Fallback: search all sections
        templateItems = document.querySelectorAll('.template-item.active');
    }
    
    if (templateItems.length > 0) {
        const activeItem = templateItems[0];
        const viewButton = activeItem.querySelector('button[onclick*="viewTemplate"]');
        if (viewButton) {
            viewButton.click();
        } else {
            alert('View button not found for active template.');
        }
    } else {
        alert(`No active ${formType.toUpperCase()} template found. Please upload and activate a template first.`);
    }
}

function activateTemplate(templateId, formType) {
    if (confirm('Are you sure you want to activate this template? This will deactivate the current active template.')) {
        // Create form data for AJAX request
        const formData = new FormData();
        formData.append('action', 'activate_template');
        formData.append('template_id', templateId);
        formData.append('form_type', formType);
        
        fetch('form-templates.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to show updated status
            } else {
                alert('Error activating template: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error activating template. Please try again.');
        });
    }
}

function closeModal() {
    const modal = document.getElementById('templateModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function testModal() {
    console.log('Testing modal...');
    viewTemplate('test.pdf', 'Test Template');
}

// Auto-test modal on page load (remove comment to test)
// window.addEventListener('load', testModal);

// Test function to verify all functions are loaded
function testFunctions() {
    console.log('Testing functions...');
    console.log('viewTemplate:', typeof viewTemplate);
    console.log('viewActiveForm:', typeof viewActiveForm);
    console.log('activateTemplate:', typeof activateTemplate);
    console.log('closeModal:', typeof closeModal);
}

// Run test on page load (for debugging)
window.addEventListener('load', function() {
    console.log('Page loaded, functions available');
    // testFunctions(); // Uncomment to test
});

function scrollToUpload() {
    document.querySelector('.upload-section').scrollIntoView({ behavior: 'smooth' });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('templateModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Handle AJAX activation request
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate_template') {
    $template_id = $_POST['template_id'];
    $form_type = $_POST['form_type'];
    
    try {
        // Deactivate all templates of this type
        $deactivate_sql = "UPDATE form_templates SET is_active = FALSE WHERE form_type = ?";
        $deactivate_stmt = $pdo->prepare($deactivate_sql);
        $deactivate_stmt->execute([$form_type]);
        
        // Activate selected template
        $activate_sql = "UPDATE form_templates SET is_active = TRUE WHERE id = ?";
        $activate_stmt = $pdo->prepare($activate_sql);
        $activate_stmt->execute([$template_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Template activated successfully']);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
</script>

<style>
.upload-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.templates-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.section-header h4 {
    margin: 0;
    color: #333;
}

.template-section h4 {
    margin-bottom: 15px;
    color: #333;
}

.template-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.template-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
}

.template-item.active {
    border-left: 4px solid #28a745;
}

.template-item.inactive {
    border-left: 4px solid #6c757d;
    opacity: 0.7;
}

.template-info h5 {
    margin: 0 0 5px 0;
    color: #333;
}

.template-meta {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.template-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ccc;
}

.empty-state p {
    margin: 0 0 20px 0;
    font-size: 16px;
}

.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: none;
    border-radius: 8px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    color: #333;
}

.modal-body {
    padding: 0;
    max-height: 70vh;
    overflow: auto;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
    color: #666;
}

.loading-spinner i {
    font-size: 24px;
    margin-bottom: 10px;
}

.document-preview {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.document-preview h4 {
    margin: 20px 0 10px 0;
    color: #333;
}

.error-message {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.error-message h4 {
    margin: 20px 0 10px 0;
    color: #dc3545;
}

@media (max-width: 768px) {
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .template-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .template-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .modal-content {
        width: 95%;
        margin: 2% auto;
    }
}
</style>

<style>
/* Additional styles for better template display */
.template-info h5 {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.current-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Make inactive templates less prominent but still accessible */
.template-item.inactive {
    opacity: 0.6;
    background: #f8f9fa;
}

.template-item.inactive:hover {
    opacity: 0.8;
    background: #e9ecef;
}

/* Better button styling for inactive templates */
.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
    background: transparent;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
}
</style>

<script>
// Clear URL parameters after displaying success messages
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have a success parameter and clear it
    if (window.location.search.includes('success=uploaded')) {
        // Clear the URL parameter without page reload
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
        
        // Auto-hide success message after 5 seconds
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.transition = 'opacity 0.3s ease';
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 300);
            }, 5000);
        }
    }
    
    // Verify functions are loaded
    console.log('Template management script loaded');
    console.log('viewTemplate function available:', typeof viewTemplate !== 'undefined');

    // Add a simple inline test
    if (typeof viewTemplate === 'function') {
        console.log('✅ viewTemplate function is available');
    } else {
        console.error('❌ viewTemplate function is NOT available');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
