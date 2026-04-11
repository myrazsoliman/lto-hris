<?php
$pageTitle = 'My Documents';
$activePage = 'documents.php';
require_once 'includes/auth.php';
require_roles(['employee']);
require_once 'includes/data.php';
require_once 'includes/upload.php';
require_once 'includes/notifications.php';

$currentUser = current_user();
$userName = $currentUser['display_name'] ?? 'Employee';

// Handle file upload form submission
$uploadMessage = '';
$uploadStatus = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $uploadMessage = 'Security token expired. Please try again.';
        $uploadStatus = 'error';
    } else {
        $category = $_POST['docCategory'] ?? '';
        $docName = $_POST['docName'] ?? '';
        $description = $_POST['docDescription'] ?? '';
        
        // Validate required fields
        if (empty($category) || empty($docName)) {
            $uploadMessage = 'Please fill in all required fields.';
            $uploadStatus = 'error';
        } else {
            // Handle file upload
            [$success, $result] = handle_file_upload('docFile', $category);
            
            if ($success) {
                // Here you would typically save the document info to database
                $uploadMessage = "Document '{$docName}' uploaded successfully!";
                $uploadStatus = 'success';
                create_notification(
                    (int) ($currentUser['id'] ?? 0),
                    'document_upload',
                    'Document uploaded',
                    $docName . ' was uploaded successfully.',
                    'documents.php'
                );
                
                // Clear the form data
                $_POST = [];
                
                // Redirect to prevent form resubmission
                header('Location: documents.php?upload_success=1');
                exit;
            } else {
                $uploadMessage = 'Upload failed: ' . $result;
                $uploadStatus = 'error';
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['upload_success']) && $_GET['upload_success'] == '1') {
    $uploadMessage = 'Document uploaded successfully!';
    $uploadStatus = 'success';
}

require_once 'includes/header.php';
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #e74c3c, #c0392b); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-folder-open" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">My Documents</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Manage your personal files</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Upload, view, and manage your personal documents including certificates, clearances, and other required files. Keep your records up to date for smooth HR processing.
        </p>

        <div class="quick-actions">
            <button class="quick-action-card quick-action-red" onclick="document.getElementById('uploadModal').style.display='block'">
                <div class="action-icon" style="background: #e74c3c; color: white;"><i class="fas fa-upload"></i></div>
                <div class="action-content">
                    <h4>Upload Document</h4>
                    <p>Add new files</p>
                </div>
                <div class="action-arrow">→</div>
            </button>
            <a href="#" class="quick-action-card quick-action-blue">
                <div class="action-icon" style="background: #2196f3; color: white;"><i class="fas fa-download"></i></div>
                <div class="action-content">
                    <h4>Download All</h4>
                    <p>Get zip archive</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#" class="quick-action-card quick-action-green">
                <div class="action-icon" style="background: #27ae60; color: white;"><i class="fas fa-share"></i></div>
                <div class="action-content">
                    <h4>Share Documents</h4>
                    <p>Generate links</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
            <a href="#" class="quick-action-card quick-action-orange">
                <div class="action-icon" style="background: #f39c12; color: white;"><i class="fas fa-history"></i></div>
                <div class="action-content">
                    <h4>Document History</h4>
                    <p>View changes</p>
                </div>
                <div class="action-arrow">→</div>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget" style="border-top: 4px solid #e74c3c;">
            <div class="stat-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <span class="stat-icon"><i class="fas fa-file"></i></span>
                <h4>Total Documents</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #e74c3c;">24</p>
                <p class="stat-label">Files uploaded</p>
                <div style="margin-top: 12px; background: #ffebee; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 80%; height: 100%; background: linear-gradient(90deg, #e74c3c, #ff6b6b); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #2196f3;">
            <div class="stat-header" style="background: linear-gradient(135deg, #2196f3, #1976d2);">
                <span class="stat-icon"><i class="fas fa-hdd"></i></span>
                <h4>Storage Used</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #2196f3;">156 MB</p>
                <p class="stat-label">Out of 500 MB limit</p>
                <div style="margin-top: 12px; background: #e3f2fd; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 31%; height: 100%; background: linear-gradient(90deg, #2196f3, #00bcd4); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #27ae60;">
            <div class="stat-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                <h4>Verified</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #27ae60;">18</p>
                <p class="stat-label">Documents verified</p>
                <div style="margin-top: 12px; background: #e8f5e9; height: 4px; border-radius: 2px; overflow: hidden;">
                    <div style="width: 75%; height: 100%; background: linear-gradient(90deg, #27ae60, #2ecc71); border-radius: 2px;"></div>
                </div>
            </div>
        </div>

        <div class="stat-widget" style="border-top: 4px solid #f39c12;">
            <div class="stat-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <span class="stat-icon"><i class="fas fa-clock"></i></span>
                <h4>Pending</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number" style="color: #f39c12;">3</p>
                <p class="stat-label">Awaiting verification</p>
                <div style="margin-top: 12px; display: flex; gap: 6px;">
                    <span style="background: #fef5e7; color: #e67e22; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">In Review</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DOCUMENT CATEGORIES -->
<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-folder"></i> Document Categories</h3>
        <p>Browse your documents by category</p>
    </div>

    <div class="document-categories">
        <div class="category-card">
            <div class="category-header" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-user-graduate"></i>
                <h4>Educational Documents</h4>
            </div>
            <div class="category-content">
                <div class="category-stats">
                    <span class="doc-count">8 files</span>
                    <span class="doc-size">45 MB</span>
                </div>
                <div class="recent-docs">
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>College Diploma.pdf</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Transcript of Records.pdf</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm">View All</button>
            </div>
        </div>

        <div class="category-card">
            <div class="category-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-id-card"></i>
                <h4>Identification Documents</h4>
            </div>
            <div class="category-content">
                <div class="category-stats">
                    <span class="doc-count">6 files</span>
                    <span class="doc-size">28 MB</span>
                </div>
                <div class="recent-docs">
                    <div class="doc-item">
                        <i class="fas fa-file-image"></i>
                        <span>Valid ID.jpg</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                    <div class="doc-item">
                        <i class="fas fa-file-image"></i>
                        <span>Birth Certificate.jpg</span>
                        <span class="doc-status pending">Pending</span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm">View All</button>
            </div>
        </div>

        <div class="category-card">
            <div class="category-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                <i class="fas fa-heartbeat"></i>
                <h4>Medical Documents</h4>
            </div>
            <div class="category-content">
                <div class="category-stats">
                    <span class="doc-count">4 files</span>
                    <span class="doc-size">15 MB</span>
                </div>
                <div class="recent-docs">
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Medical Certificate 2025.pdf</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Lab Results.pdf</span>
                        <span class="doc-status pending">Pending</span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm">View All</button>
            </div>
        </div>

        <div class="category-card">
            <div class="category-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <i class="fas fa-certificate"></i>
                <h4>Certificates & Training</h4>
            </div>
            <div class="category-content">
                <div class="category-stats">
                    <span class="doc-count">6 files</span>
                    <span class="doc-size">38 MB</span>
                </div>
                <div class="recent-docs">
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>CSC Certificate.pdf</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                    <div class="doc-item">
                        <i class="fas fa-file-pdf"></i>
                        <span>Training Certificate 2024.pdf</span>
                        <span class="doc-status verified">Verified</span>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm">View All</button>
            </div>
        </div>
    </div>
</section>

<!-- RECENT DOCUMENTS -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Recent Uploads</span>
            <h3>Recently Uploaded Documents</h3>
        </div>
        <div class="section-actions">
            <button class="btn btn-outline btn-sm">View All</button>
        </div>
    </div>

    <div class="documents-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Upload Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="doc-name">
                            <i class="fas fa-file-pdf"></i>
                            <span>Medical Certificate 2025.pdf</span>
                        </div>
                    </td>
                    <td>Medical Documents</td>
                    <td>2.4 MB</td>
                    <td>March 20, 2026</td>
                    <td><span class="status-badge verified">Verified</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                        <button class="btn btn-sm btn-outline">Download</button>
                        <button class="btn btn-sm btn-outline">Share</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="doc-name">
                            <i class="fas fa-file-image"></i>
                            <span>Valid ID.jpg</span>
                        </div>
                    </td>
                    <td>Identification Documents</td>
                    <td>1.8 MB</td>
                    <td>March 18, 2026</td>
                    <td><span class="status-badge verified">Verified</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                        <button class="btn btn-sm btn-outline">Download</button>
                        <button class="btn btn-sm btn-outline">Share</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="doc-name">
                            <i class="fas fa-file-pdf"></i>
                            <span>Training Certificate 2024.pdf</span>
                        </div>
                    </td>
                    <td>Certificates & Training</td>
                    <td>3.2 MB</td>
                    <td>March 15, 2026</td>
                    <td><span class="status-badge pending">Pending</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                        <button class="btn btn-sm btn-outline">Download</button>
                        <button class="btn btn-sm btn-warning">Update</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="doc-name">
                            <i class="fas fa-file-pdf"></i>
                            <span>Birth Certificate.jpg</span>
                        </div>
                    </td>
                    <td>Identification Documents</td>
                    <td>1.5 MB</td>
                    <td>March 10, 2026</td>
                    <td><span class="status-badge pending">In Review</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline">View</button>
                        <button class="btn btn-sm btn-outline">Download</button>
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- UPLOAD MODAL -->
<div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload Document</h3>
            <button class="modal-close" onclick="document.getElementById('uploadModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body">
            <?php if (!empty($uploadMessage)): ?>
                <div class="alert alert-<?= $uploadStatus === 'success' ? 'success' : 'error' ?>" style="padding: 12px; margin-bottom: 20px; border-radius: 6px; background: <?= $uploadStatus === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $uploadStatus === 'success' ? '#155724' : '#721c24' ?>; border: 1px solid <?= $uploadStatus === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
                    <?= htmlspecialchars($uploadMessage) ?>
                </div>
            <?php endif; ?>
            
            <form class="upload-form" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="action" value="upload_document">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="form-group">
                    <label for="docCategory">Document Category</label>
                    <select id="docCategory" name="docCategory" required>
                        <option value="">Select category</option>
                        <option value="educational">Educational Documents</option>
                        <option value="identification">Identification Documents</option>
                        <option value="medical">Medical Documents</option>
                        <option value="certificates">Certificates & Training</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="docName">Document Name</label>
                    <input type="text" id="docName" name="docName" placeholder="Enter document name" required>
                </div>
                <div class="form-group">
                    <label for="docFile">Choose File</label>
                    <input type="file" id="docFile" name="docFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
                </div>
                <div class="form-group">
                    <label for="docDescription">Description</label>
                    <textarea id="docDescription" name="docDescription" placeholder="Brief description of the document"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="uploadBtn">Upload Document</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('uploadModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --docs-surface: #ffffff;
    --docs-bg: #f4f8fc;
    --docs-text: #14324f;
    --docs-muted: #617993;
    --docs-border: #d9e4f1;
    --docs-shadow: 0 14px 30px rgba(17, 43, 70, 0.10);
    --docs-blue-700: #1f4d85;
    --docs-blue-600: #2c6cb0;
    --docs-cyan-600: #2f7dbf;
    --docs-green-600: #1f9f7b;
    --docs-amber-600: #c98522;
}

section.hero.modern-hero {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 30px;
    border: 1px solid var(--docs-border);
    background:
        radial-gradient(600px 220px at 90% -20%, rgba(44, 108, 176, 0.08), transparent 70%),
        radial-gradient(500px 220px at -10% 10%, rgba(31, 77, 133, 0.08), transparent 68%),
        linear-gradient(180deg, #fbfdff 0%, #f1f6fc 100%);
    box-shadow: var(--docs-shadow);
}

section.hero.modern-hero .hero-header {
    display: flex;
    align-items: center;
    gap: 16px;
}

section.hero.modern-hero .header-badge {
    width: 62px !important;
    height: 62px !important;
    border-radius: 16px !important;
    padding: 0 !important;
    background: linear-gradient(135deg, var(--docs-blue-700), var(--docs-blue-600)) !important;
    box-shadow: 0 10px 22px rgba(31, 77, 133, 0.22);
}

section.hero.modern-hero .header-badge i {
    font-size: 28px !important;
}

section.hero.modern-hero h2 {
    margin: 0 0 6px !important;
    font-size: 34px !important;
    letter-spacing: -0.02em;
    color: var(--docs-text) !important;
}

section.hero.modern-hero .hero-header p {
    margin: 0 !important;
    color: var(--docs-muted) !important;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
    font-size: 13px !important;
}

section.hero.modern-hero .hero-content > p {
    max-width: 700px !important;
    margin: 22px 0 28px !important;
    color: #48627f !important;
    line-height: 1.75 !important;
}

section.hero.modern-hero .quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 14px;
}

section.hero.modern-hero .quick-action-card {
    border: 1px solid var(--docs-border);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(17, 43, 70, 0.08);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

section.hero.modern-hero .quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(17, 43, 70, 0.14);
}

section.hero.modern-hero .quick-action-card .action-icon {
    color: #fff !important;
}

section.hero.modern-hero .quick-action-card:nth-child(1) .action-icon {
    background: linear-gradient(135deg, var(--docs-blue-700), var(--docs-blue-600)) !important;
}

section.hero.modern-hero .quick-action-card:nth-child(2) .action-icon {
    background: linear-gradient(135deg, #24659c, var(--docs-cyan-600)) !important;
}

section.hero.modern-hero .quick-action-card:nth-child(3) .action-icon {
    background: linear-gradient(135deg, #197f63, var(--docs-green-600)) !important;
}

section.hero.modern-hero .quick-action-card:nth-child(4) .action-icon {
    background: linear-gradient(135deg, #b6781f, var(--docs-amber-600)) !important;
}

section.hero.modern-hero .quick-action-card .action-arrow {
    font-size: 0 !important;
    color: transparent !important;
}

section.hero.modern-hero .quick-action-card .action-arrow::before {
    content: "\f061";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 15px;
    color: #6982a0;
}

section.hero.modern-hero .hero-panel.modern-panel {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

section.hero.modern-hero .stat-widget {
    border-radius: 14px;
    border: 1px solid var(--docs-border);
    border-top-width: 3px !important;
    box-shadow: 0 10px 22px rgba(17, 43, 70, 0.08);
    overflow: hidden;
}

section.hero.modern-hero .stat-widget:nth-child(1) { border-top-color: var(--docs-blue-700) !important; }
section.hero.modern-hero .stat-widget:nth-child(2) { border-top-color: var(--docs-cyan-600) !important; }
section.hero.modern-hero .stat-widget:nth-child(3) { border-top-color: var(--docs-green-600) !important; }
section.hero.modern-hero .stat-widget:nth-child(4) { border-top-color: var(--docs-amber-600) !important; }

section.hero.modern-hero .stat-widget:nth-child(1) .stat-header { background: linear-gradient(135deg, var(--docs-blue-700), var(--docs-blue-600)) !important; }
section.hero.modern-hero .stat-widget:nth-child(2) .stat-header { background: linear-gradient(135deg, #24659c, var(--docs-cyan-600)) !important; }
section.hero.modern-hero .stat-widget:nth-child(3) .stat-header { background: linear-gradient(135deg, #197f63, var(--docs-green-600)) !important; }
section.hero.modern-hero .stat-widget:nth-child(4) .stat-header { background: linear-gradient(135deg, #b6781f, var(--docs-amber-600)) !important; }

section.hero.modern-hero .stat-widget:nth-child(1) .stat-number { color: var(--docs-blue-700) !important; }
section.hero.modern-hero .stat-widget:nth-child(2) .stat-number { color: var(--docs-cyan-600) !important; }
section.hero.modern-hero .stat-widget:nth-child(3) .stat-number { color: var(--docs-green-600) !important; }
section.hero.modern-hero .stat-widget:nth-child(4) .stat-number { color: var(--docs-amber-600) !important; }

section.hero.modern-hero .stat-widget:nth-child(1) .stat-body > div:first-of-type { background: #e8eff8 !important; border-radius: 999px !important; height: 5px !important; }
section.hero.modern-hero .stat-widget:nth-child(2) .stat-body > div:first-of-type { background: #e4f0fa !important; border-radius: 999px !important; height: 5px !important; }
section.hero.modern-hero .stat-widget:nth-child(3) .stat-body > div:first-of-type { background: #e2f3ee !important; border-radius: 999px !important; height: 5px !important; }

section.hero.modern-hero .stat-widget:nth-child(1) .stat-body > div:first-of-type > div { background: linear-gradient(90deg, var(--docs-blue-700), var(--docs-blue-600)) !important; border-radius: 999px !important; }
section.hero.modern-hero .stat-widget:nth-child(2) .stat-body > div:first-of-type > div { background: linear-gradient(90deg, #24659c, var(--docs-cyan-600)) !important; border-radius: 999px !important; }
section.hero.modern-hero .stat-widget:nth-child(3) .stat-body > div:first-of-type > div { background: linear-gradient(90deg, #197f63, var(--docs-green-600)) !important; border-radius: 999px !important; }

section.hero.modern-hero .stat-widget:nth-child(4) .stat-body > div {
    margin-top: 12px !important;
}

section.hero.modern-hero .stat-widget:nth-child(4) .stat-body > div span {
    background: #fff2de !important;
    color: #946116 !important;
    font-weight: 700 !important;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.section-title h3 {
    color: var(--docs-text);
}

.section-title p {
    color: var(--docs-muted);
}

.document-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 18px;
    margin-top: 20px;
}

.category-card {
    background: var(--docs-surface);
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--docs-border);
    box-shadow: 0 10px 25px rgba(17, 43, 70, 0.08);
}

.category-header {
    padding: 20px;
    color: white;
    display: flex;
    align-items: center;
    gap: 15px;
}

.category-card:nth-child(1) .category-header { background: linear-gradient(135deg, var(--docs-blue-700), var(--docs-blue-600)) !important; }
.category-card:nth-child(2) .category-header { background: linear-gradient(135deg, #24659c, var(--docs-cyan-600)) !important; }
.category-card:nth-child(3) .category-header { background: linear-gradient(135deg, #197f63, var(--docs-green-600)) !important; }
.category-card:nth-child(4) .category-header { background: linear-gradient(135deg, #b6781f, var(--docs-amber-600)) !important; }

.category-header i {
    font-size: 24px;
}

.category-header h4 {
    margin: 0;
    font-size: 18px;
}

.category-content {
    padding: 20px;
}

.category-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--docs-muted);
}

.recent-docs {
    margin-bottom: 15px;
}

.doc-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #e3edf7;
}

.doc-item:last-child {
    border-bottom: none;
}

.doc-item i {
    color: #2f7dbf;
    width: 20px;
}

.doc-status {
    margin-left: auto;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.03em;
}

.doc-status.verified {
    background: #def5ed;
    color: #167b5c;
}

.doc-status.pending {
    background: #fff3dd;
    color: #9a6213;
}

.documents-table {
    overflow-x: auto;
}

.doc-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.doc-name i {
    color: var(--docs-blue-700);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 14px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid var(--docs-border);
    box-shadow: 0 24px 40px rgba(17, 43, 70, 0.20);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e3edf7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--docs-text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--docs-muted);
}

.modal-body {
    padding: 20px;
}

.upload-form .form-group {
    margin-bottom: 20px;
}

.upload-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--docs-text);
}

.upload-form input,
.upload-form select,
.upload-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #c9d9ea;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.upload-form input:focus,
.upload-form select:focus,
.upload-form textarea:focus {
    outline: none;
    border-color: var(--docs-blue-600);
    box-shadow: 0 0 0 3px rgba(44, 108, 176, 0.14);
}

.upload-form small {
    display: block;
    margin-top: 5px;
    color: var(--docs-muted);
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

@media (max-width: 1024px) {
    section.hero.modern-hero .hero-panel.modern-panel {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    section.hero.modern-hero {
        padding: 20px;
    }

    section.hero.modern-hero h2 {
        font-size: 28px !important;
    }

    section.hero.modern-hero .hero-header {
        align-items: flex-start;
    }

    section.hero.modern-hero .quick-actions,
    .document-categories {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadModal = document.getElementById('uploadModal');
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            // Prevent multiple submissions
            if (uploadBtn.disabled) {
                e.preventDefault();
                return false;
            }
            
            // Disable button and show loading state
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';
            
            // Allow form to submit normally
            return true;
        });
    }
    
    // Close modal on successful upload (if URL parameter exists)
    if (window.location.search.includes('upload_success=1')) {
        // Clear any existing modal state
        if (uploadModal) {
            uploadModal.style.display = 'none';
        }
        
        // Clear URL parameter without page reload
        const url = new URL(window.location);
        url.searchParams.delete('upload_success');
        window.history.replaceState({}, '', url);
    }
    
    // Reset form when modal is opened
    const uploadModalTriggers = document.querySelectorAll('[onclick*="uploadModal"]');
    uploadModalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            if (uploadForm) {
                uploadForm.reset();
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload Document';
                
                // Remove any existing alerts
                const alerts = uploadModal.querySelectorAll('.alert');
                alerts.forEach(alert => alert.remove());
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
