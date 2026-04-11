<?php
$pageTitle = 'PDS Template Upload';
$activePage = 'pds-template-admin.php';

require_once 'includes/auth.php';
require_roles(['hr_officer', 'admin', 'superadmin']);
require_once 'includes/data.php';

$templateRelativePath = 'uploads/form_templates/PH GSIS CS 212 2017-2026.pdf';
$templateAbsolutePath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'form_templates' . DIRECTORY_SEPARATOR . 'PH GSIS CS 212 2017-2026.pdf';
$archiveDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'form_templates' . DIRECTORY_SEPARATOR . 'archive';
$templateMetaPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'form_templates' . DIRECTORY_SEPARATOR . 'pds_template_meta.json';
$historyMetaPath = $archiveDir . DIRECTORY_SEPARATOR . 'pds_template_history_meta.json';

$error = '';
$success = '';
$canViewVersionHistory = has_role(['admin', 'superadmin']);
$historyPageRequest = isset($_POST['history_page']) ? (int) $_POST['history_page'] : (isset($_GET['history_page']) ? (int) $_GET['history_page'] : 1);
if ($historyPageRequest < 1) {
    $historyPageRequest = 1;
}
$showDeleteSuccessModal = isset($_GET['deleted']) && (int) $_GET['deleted'] === 1;
$deletedVersionLabel = isset($_GET['deleted_name']) ? trim((string) $_GET['deleted_name']) : '';

function pds_template_upload_error_message($code)
{
    $code = (int) $code;
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return 'Upload failed: file is too large for current server limits.';
    }
    if ($code === UPLOAD_ERR_PARTIAL) {
        return 'Upload failed: file was only partially uploaded. Please try again.';
    }
    if ($code === UPLOAD_ERR_NO_FILE) {
        return 'Please choose a PDF file to upload.';
    }
    if ($code === UPLOAD_ERR_NO_TMP_DIR) {
        return 'Upload failed: temporary upload directory is missing on server.';
    }
    if ($code === UPLOAD_ERR_CANT_WRITE) {
        return 'Upload failed: server cannot write uploaded file.';
    }
    if ($code === UPLOAD_ERR_EXTENSION) {
        return 'Upload blocked by a server extension.';
    }
    if ($code !== UPLOAD_ERR_OK) {
        return 'Upload failed with error code ' . $code . '.';
    }
    return '';
}

function pds_template_load_json_array($path)
{
    if (!is_file($path)) {
        return [];
    }
    $raw = (string) @file_get_contents($path);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function pds_template_save_json_array($path, array $payload)
{
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function pds_template_is_fillable_pdf($path)
{
    if (!is_file($path)) {
        return false;
    }

    $content = @file_get_contents($path);
    if (!is_string($content) || $content === '') {
        return false;
    }

    // Basic AcroForm detection for fillable PDFs.
    $hasAcroForm = stripos($content, '/AcroForm') !== false;
    $hasFields = stripos($content, '/Fields') !== false;
    $hasWidget = stripos($content, '/Widget') !== false;
    $hasFieldType = preg_match('/\/FT\s*\/(Tx|Btn|Ch|Sig)/i', $content) === 1;

    return $hasAcroForm && ($hasFields || $hasWidget || $hasFieldType);
}

$currentTemplateMeta = pds_template_load_json_array($templateMetaPath);
$currentTemplateOriginalName = 'PH GSIS CS 212 2017-2026.pdf';
if (!empty($currentTemplateMeta['original_name']) && is_string($currentTemplateMeta['original_name'])) {
    $currentTemplateOriginalName = basename($currentTemplateMeta['original_name']);
}
$historyMeta = pds_template_load_json_array($historyMetaPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_history_file'])) {
    if (!$canViewVersionHistory) {
        $error = 'You are not authorized to delete template history.';
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $deleteFileName = basename((string) ($_POST['delete_history_file'] ?? ''));
        $deletePath = $archiveDir . DIRECTORY_SEPARATOR . $deleteFileName;

        if ($deleteFileName === '' || !is_file($deletePath)) {
            $error = 'Selected history file was not found.';
        } elseif (@unlink($deletePath)) {
            $deletedNameForUi = $deleteFileName;
            if (!empty($historyMeta[$deleteFileName]['original_name']) && is_string($historyMeta[$deleteFileName]['original_name'])) {
                $deletedNameForUi = basename($historyMeta[$deleteFileName]['original_name']);
            }
            if (isset($historyMeta[$deleteFileName])) {
                unset($historyMeta[$deleteFileName]);
                pds_template_save_json_array($historyMetaPath, $historyMeta);
            }
            header('Location: pds-template-admin.php?history_page=' . $historyPageRequest . '&deleted=1&deleted_name=' . rawurlencode($deletedNameForUi) . '#templateHistory');
            exit;
        } else {
            $error = 'Unable to delete history file. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pds_template_file'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $upload = $_FILES['pds_template_file'];
        $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($uploadError !== UPLOAD_ERR_OK) {
            $error = pds_template_upload_error_message($uploadError);
        } else {
            $originalName = (string) ($upload['name'] ?? '');
            $safeOriginalName = basename($originalName);
            $tmpPath = (string) ($upload['tmp_name'] ?? '');
            $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
            $mime = '';

            if (is_uploaded_file($tmpPath) && function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = (string) finfo_file($finfo, $tmpPath);
                    finfo_close($finfo);
                }
            }

            $isPdfByExtension = ($extension === 'pdf');
            $isPdfByMime = in_array($mime, ['application/pdf', 'application/x-pdf', 'application/octet-stream'], true);

            if (!is_uploaded_file($tmpPath)) {
                $error = 'Upload validation failed. Please try selecting the file again.';
            } elseif (!$isPdfByExtension && !$isPdfByMime) {
                $error = 'Only PDF files are allowed for the PDS template.';
            } else {
                $templateDir = dirname($templateAbsolutePath);
                if (!is_dir($templateDir)) {
                    @mkdir($templateDir, 0777, true);
                }

                if (!is_dir($templateDir)) {
                    $error = 'Unable to create template directory.';
                } elseif (!is_writable($templateDir)) {
                    $error = 'Template directory is not writable by the server.';
                } else {
                    $stagedPath = $templateDir . DIRECTORY_SEPARATOR . '__incoming_pds_template_' . date('Ymd_His') . '.pdf';

                    if (!move_uploaded_file($tmpPath, $stagedPath)) {
                        $error = 'Failed to move uploaded file to server storage.';
                    } else {
                        if (!pds_template_is_fillable_pdf($stagedPath)) {
                            @unlink($stagedPath);
                            $error = 'Only fillable PDF templates are allowed. Upload a PDF with editable form fields (AcroForm).';
                        }
                    }

                    if ($error === '' && is_file($stagedPath)) {
                        if (is_file($templateAbsolutePath)) {
                            if (!is_dir($archiveDir)) {
                                @mkdir($archiveDir, 0777, true);
                            }
                            if (is_dir($archiveDir)) {
                                $backupBase = (string) pathinfo($currentTemplateOriginalName, PATHINFO_FILENAME);
                                $backupBase = preg_replace('/[^A-Za-z0-9 _-]/', '', $backupBase);
                                $backupBase = trim((string) $backupBase);
                                if ($backupBase === '') {
                                    $backupBase = 'PDS_Template';
                                }

                                $backupFileName = $backupBase . '_' . date('Ymd_His') . '.pdf';
                                $backupPath = $archiveDir . DIRECTORY_SEPARATOR . $backupFileName;
                                if (@copy($templateAbsolutePath, $backupPath)) {
                                    $historyMeta[$backupFileName] = [
                                        'original_name' => $currentTemplateOriginalName,
                                        'uploaded_at' => date('c'),
                                    ];
                                    pds_template_save_json_array($historyMetaPath, $historyMeta);
                                }
                            }
                        }

                        $replaced = false;

                        if (is_file($templateAbsolutePath)) {
                            if (@unlink($templateAbsolutePath)) {
                                $replaced = @rename($stagedPath, $templateAbsolutePath);
                            } else {
                                $error = 'Cannot replace existing template file (it may be locked or read-only).';
                            }
                        } else {
                            $replaced = @rename($stagedPath, $templateAbsolutePath);
                        }

                        if (!$replaced && $error === '') {
                            if (@copy($stagedPath, $templateAbsolutePath)) {
                                @unlink($stagedPath);
                                $replaced = true;
                            } else {
                                $error = 'Failed to save new template file. Please try again.';
                            }
                        }

                        if ($replaced) {
                            try {
                                if (isset($pdo) && $pdo instanceof PDO) {
                                    $stmt = $pdo->prepare("
                                        UPDATE pds_records
                                        SET generated_pdf_path = NULL
                                        WHERE generated_pdf_path IS NOT NULL
                                          AND generated_pdf_path <> ''
                                    ");
                                    $stmt->execute();
                                }
                            } catch (Throwable $e) {
                                // Keep upload success even if invalidation fails.
                            }

                            $metaPayload = [
                                'original_name' => $safeOriginalName !== '' ? $safeOriginalName : 'PH GSIS CS 212 2017-2026.pdf',
                                'updated_at' => date('c'),
                                'is_fillable' => true,
                            ];
                            @file_put_contents($templateMetaPath, json_encode($metaPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                            clearstatcache(true, $templateAbsolutePath);
                            clearstatcache(true, $templateMetaPath);
                            $success = 'Fillable PDS template updated successfully.';
                        } elseif (is_file($stagedPath)) {
                            @unlink($stagedPath);
                        }
                    }
                }
            }
        }
    }
}

$templateExists = is_file($templateAbsolutePath);
$templateSize = $templateExists ? (int) filesize($templateAbsolutePath) : 0;
$templateUpdatedAt = $templateExists ? (int) filemtime($templateAbsolutePath) : 0;
$templateViewHref = $templateRelativePath . ($templateExists ? ('?v=' . rawurlencode((string) $templateUpdatedAt)) : '');
$templateDisplayName = 'PH GSIS CS 212 2017-2026.pdf';

$currentTemplateMetaDisplay = pds_template_load_json_array($templateMetaPath);
if (!empty($currentTemplateMetaDisplay['original_name']) && is_string($currentTemplateMetaDisplay['original_name'])) {
    $templateDisplayName = basename($currentTemplateMetaDisplay['original_name']);
}
$templateHistory = [];
$historyPerPage = isset($_GET['history_per_page']) ? (int) $_GET['history_per_page'] : 10;
$historyPerPage = in_array($historyPerPage, [10, 25, 50], true) ? $historyPerPage : 10;
$historyQuery = trim((string) ($_GET['history_q'] ?? ''));
$historyPage = 1;
$historyTotalPages = 1;
$historyTotalCount = 0;
$templateHistoryVisible = [];

if ($canViewVersionHistory && is_dir($archiveDir)) {
    $historyFiles = glob($archiveDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
    foreach ($historyFiles as $historyPath) {
        if (!is_file($historyPath)) {
            continue;
        }

        $historyFileName = basename($historyPath);
        $displayName = $historyFileName;
        if (!empty($historyMeta[$historyFileName]['original_name']) && is_string($historyMeta[$historyFileName]['original_name'])) {
            $displayName = basename($historyMeta[$historyFileName]['original_name']);
        } elseif (preg_match('/^(.*)_\d{8}_\d{6}\.pdf$/', $historyFileName, $matches)) {
            $displayName = trim(str_replace('_', ' ', (string) ($matches[1] ?? '')));
            if ($displayName !== '') {
                $displayName .= '.pdf';
            } else {
                $displayName = $historyFileName;
            }
        }

        $templateHistory[] = [
            'name' => $historyFileName,
            'display_name' => $displayName,
            'size' => (int) filesize($historyPath),
            'updated_at' => !empty($historyMeta[$historyFileName]['uploaded_at'])
                ? (int) strtotime((string) $historyMeta[$historyFileName]['uploaded_at'])
                : (int) filemtime($historyPath),
            'href' => 'uploads/form_templates/archive/' . rawurlencode($historyFileName),
        ];
    }

    usort($templateHistory, static function ($a, $b) {
        return ($b['updated_at'] ?? 0) <=> ($a['updated_at'] ?? 0);
    });

    if ($historyQuery !== '') {
        $needle = strtolower($historyQuery);
        $templateHistory = array_values(array_filter($templateHistory, static function ($row) use ($needle) {
            $display = strtolower((string) ($row['display_name'] ?? ''));
            $name = strtolower((string) ($row['name'] ?? ''));
            return str_contains($display, $needle) || str_contains($name, $needle);
        }));
    }

    $historyTotalCount = count($templateHistory);
    if ($historyTotalCount > 0) {
        $historyTotalPages = max(1, (int) ceil($historyTotalCount / $historyPerPage));
    }
    $requestedPage = $historyPageRequest;
    if ($requestedPage < 1) {
        $requestedPage = 1;
    }
    if ($requestedPage > $historyTotalPages) {
        $requestedPage = $historyTotalPages;
    }
    $historyPage = $requestedPage;
    $historyOffset = ($historyPage - 1) * $historyPerPage;
    $templateHistoryVisible = array_slice($templateHistory, $historyOffset, $historyPerPage);
}

if (isset($_GET['history_json']) && (int) $_GET['history_json'] === 1) {
    header('Content-Type: application/json; charset=utf-8');
    $signaturePayload = array_map(static function ($row) {
        return [
            'name' => (string) ($row['name'] ?? ''),
            'updated_at' => (int) ($row['updated_at'] ?? 0),
            'size' => (int) ($row['size'] ?? 0),
        ];
    }, $templateHistoryVisible);
    $historySignature = hash('sha256', json_encode($signaturePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo json_encode([
        'ok' => true,
        'current_template' => [
            'exists' => $templateExists,
            'size_bytes' => $templateSize,
            'updated_at' => $templateUpdatedAt,
            'updated_at_formatted' => $templateUpdatedAt > 0 ? date('F j, Y g:i:s A', $templateUpdatedAt) : '',
            'display_name' => $templateDisplayName,
        ],
        'history_total_count' => $historyTotalCount,
        'history_page' => $historyPage,
        'history_per_page' => $historyPerPage,
        'history_total_pages' => $historyTotalPages,
        'history_query' => $historyQuery,
        'history_signature' => $historySignature,
        'history' => array_map(static function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'size_bytes' => (int) ($row['size'] ?? 0),
                'updated_at' => (int) ($row['updated_at'] ?? 0),
            ];
        }, $templateHistoryVisible),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once 'includes/header.php';
?>

<section class="card pds-template-page">
    <div class="section-head">
        <div>
            <span class="tag">PDS Template</span>
            <h3>Upload Official CSC Form 212 PDF</h3>
            <p class="pds-template-subtitle">Manage the file used by employee PDS preview, download, and PDF generation.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger pds-template-alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success pds-template-alert">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="pds-template-admin-grid">
        <div class="pds-template-admin-card">
            <h4>Current Employee PDS Template</h4>
            <?php if ($templateExists): ?>
                <div class="pds-template-meta-list">
                    <div class="pds-template-meta-row"><span>File</span><strong><?php echo htmlspecialchars($templateDisplayName); ?></strong></div>
                    <div class="pds-template-meta-row"><span>Size</span><strong id="currentTemplateSize"><?php echo number_format($templateSize / 1024, 2); ?> KB</strong></div>
                    <div class="pds-template-meta-row"><span>Last updated</span><strong id="currentTemplateUpdated"><?php echo date('F j, Y g:i:s A', $templateUpdatedAt); ?></strong></div>
                </div>
                <a href="<?php echo htmlspecialchars($templateViewHref); ?>" class="btn btn-outline" target="_blank" rel="noopener">
                    <i class="fa-regular fa-file-pdf" aria-hidden="true"></i> View Current Template
                </a>
            <?php else: ?>
                <p class="pds-template-empty">No template found yet. Upload a PDF to activate employee PDS template preview and download.</p>
            <?php endif; ?>
        </div>

        <div class="pds-template-admin-card">
            <h4>Upload New Template</h4>
            <p class="pds-template-help">This will replace the template used by employees in <code>pds.php</code>. Only fillable PDF files (with form fields) are accepted.</p>
            <form method="post" enctype="multipart/form-data" class="pds-template-upload-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

                <div class="form-group">
                    <label for="pds_template_file">Choose PDF Template</label>
                    <div class="pds-template-file-wrap">
                        <input
                            type="file"
                            id="pds_template_file"
                            name="pds_template_file"
                            accept=".pdf,application/pdf"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload" aria-hidden="true"></i> Upload PDS Template
                </button>
            </form>
        </div>
    </div>

    <?php if ($canViewVersionHistory): ?>
        <div class="pds-template-history" id="templateHistory">
            <div class="pds-template-history-head">
                <h4>Template Version History</h4>
                <p>Only visible to admin roles.</p>
            </div>

            <form class="pds-dt-controls" method="get" action="pds-template-admin.php" autocomplete="off" id="pdsHistoryControls">
                <div class="pds-dt-toolbar" role="group" aria-label="Template history controls">
                    <div class="pds-dt-length">
                        <label for="history_per_page">Show</label>
                        <select id="history_per_page" name="history_per_page" aria-label="Rows per page">
                            <?php foreach ([10, 25, 50] as $n): ?>
                                <option value="<?php echo (int) $n; ?>"<?php echo $historyPerPage === (int) $n ? ' selected' : ''; ?>><?php echo (int) $n; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span>entries</span>
                    </div>

                    <div class="pds-dt-search">
                        <label for="history_q">Search:</label>
                        <input id="history_q" name="history_q" type="search" value="<?php echo htmlspecialchars($historyQuery); ?>" inputmode="search">
                    </div>
                </div>

                <input type="hidden" id="history_page" name="history_page" value="<?php echo (int) $historyPage; ?>">
            </form>

            <?php if (!empty($templateHistoryVisible)): ?>
                <div class="pds-template-history-table-wrap">
                    <table class="pds-template-history-table">
                        <thead>
                            <tr>
                                <th>Version File</th>
                                <th>Uploaded Date</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templateHistoryVisible as $version): ?>
                                <tr data-history-name="<?php echo htmlspecialchars($version['name']); ?>">
                                    <td><?php echo htmlspecialchars($version['display_name'] ?? $version['name']); ?></td>
                                    <td>
                                        <span class="pds-live-time" data-timestamp="<?php echo (int) $version['updated_at']; ?>">
                                            <?php echo date('F j, Y g:i:s A', (int) $version['updated_at']); ?>
                                        </span>
                                    </td>
                                    <td class="pds-history-size"><?php echo number_format(((int) $version['size']) / 1024, 2); ?> KB</td>
                                    <td>
                                        <div class="pds-template-history-actions">
                                            <a href="<?php echo htmlspecialchars($version['href']); ?>" class="pds-template-view-btn" target="_blank" rel="noopener" aria-label="View version" title="View version">
                                                <i class="fa-solid fa-eye pds-template-eye-icon" aria-hidden="true"></i>
                                            </a>
                                            <form method="post" class="pds-template-history-delete-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                <input type="hidden" name="delete_history_file" value="<?php echo htmlspecialchars($version['name']); ?>">
                                                <input type="hidden" name="history_page" value="<?php echo (int) $historyPage; ?>">
                                                <button type="button" class="pds-template-delete-btn" data-delete-trigger="true" aria-label="Delete version" title="Delete version">
                                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="pds-template-history-empty">No archived template versions yet.</div>
            <?php endif; ?>
        </div>

        <?php if (!empty($templateHistoryVisible)): ?>
            <div class="pds-history-pagination">
                <div class="pds-history-pagination-meta">
                    <?php if ($historyTotalCount <= 0): ?>
                        Showing 0 to 0 of 0 entries
                    <?php else: ?>
                        <?php
                            $historyStart = min($historyTotalCount, $historyOffset + 1);
                            $historyEnd = min($historyTotalCount, $historyOffset + count($templateHistoryVisible));
                        ?>
                        Showing <?php echo (int) $historyStart; ?> to <?php echo (int) $historyEnd; ?> of <?php echo (int) $historyTotalCount; ?> entries
                    <?php endif; ?>
                </div>
                <div class="pds-template-pagination" role="navigation" aria-label="Template history pagination">
                    <?php
                    $prevPage = max(1, $historyPage - 1);
                    $nextPage = min($historyTotalPages, $historyPage + 1);
                    $pageWindowSize = 10;
                    $windowStart = (int) (floor(($historyPage - 1) / $pageWindowSize) * $pageWindowSize) + 1;
                    $windowEnd = min($historyTotalPages, $windowStart + $pageWindowSize - 1);

                    $historyBase = [
                        'history_q' => $historyQuery,
                        'history_per_page' => $historyPerPage,
                    ];
                    $historyBaseQuery = http_build_query(array_filter($historyBase, static fn($v) => $v !== '' && $v !== null));
                    $historyBaseHref = 'pds-template-admin.php' . ($historyBaseQuery !== '' ? ('?' . $historyBaseQuery . '&') : '?');
                    ?>
                    <a class="pds-page-link<?php echo $historyPage <= 1 ? ' is-disabled' : ''; ?>" data-preserve-scroll="1" href="<?php echo $historyPage <= 1 ? '#' : htmlspecialchars($historyBaseHref . 'history_page=' . $prevPage . '#templateHistory'); ?>">Previous</a>
                    <?php if ($windowStart > 1): ?>
                        <a class="pds-page-link" data-preserve-scroll="1" href="<?php echo htmlspecialchars($historyBaseHref . 'history_page=1#templateHistory'); ?>">1</a>
                        <span class="pds-page-ellipsis">...</span>
                    <?php endif; ?>
                    <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                        <a class="pds-page-link<?php echo $i === $historyPage ? ' is-active' : ''; ?>" data-preserve-scroll="1" href="<?php echo htmlspecialchars($historyBaseHref . 'history_page=' . $i . '#templateHistory'); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($windowEnd < $historyTotalPages): ?>
                        <span class="pds-page-ellipsis">...</span>
                        <a class="pds-page-link" data-preserve-scroll="1" href="<?php echo htmlspecialchars($historyBaseHref . 'history_page=' . $historyTotalPages . '#templateHistory'); ?>"><?php echo $historyTotalPages; ?></a>
                    <?php endif; ?>
                    <a class="pds-page-link<?php echo $historyPage >= $historyTotalPages ? ' is-disabled' : ''; ?>" data-preserve-scroll="1" href="<?php echo $historyPage >= $historyTotalPages ? '#' : htmlspecialchars($historyBaseHref . 'history_page=' . $nextPage . '#templateHistory'); ?>">Next</a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="confirm-modal confirm-modal--menu" id="historyDeleteConfirmModal" aria-hidden="true">
        <div class="confirm-modal-backdrop" data-delete-cancel></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="historyDeleteConfirmTitle">
            <div class="confirm-modal-head">
                <div class="confirm-modal-copy">
                    <h2 id="historyDeleteConfirmTitle">Delete template version?</h2>
                    <p class="confirm-modal-text" id="historyDeleteConfirmText">This action cannot be undone.</p>
                </div>
            </div>
            <div class="confirm-modal-divider" aria-hidden="true"></div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-outline confirm-btn-cancel" id="historyDeleteNo" data-delete-cancel>Cancel</button>
                <button type="button" class="btn btn-danger confirm-btn-logout" id="historyDeleteYes">Delete</button>
            </div>
        </div>
    </div>

    <div class="confirm-modal confirm-modal--menu" id="historyDeleteSuccessModal" aria-hidden="true">
        <div class="confirm-modal-backdrop" data-delete-success-close></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="historyDeleteSuccessTitle">
            <div class="confirm-modal-head">
                <div class="confirm-modal-copy">
                    <h2 id="historyDeleteSuccessTitle">Version Deleted</h2>
                    <p class="confirm-modal-text">
                        <?php if ($deletedVersionLabel !== ''): ?>
                            "<?php echo htmlspecialchars($deletedVersionLabel); ?>" was removed from template history.
                        <?php else: ?>
                            The selected template version was removed from history.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="confirm-modal-divider" aria-hidden="true"></div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-primary" id="historyDeleteSuccessOk" data-delete-success-close>OK</button>
            </div>
        </div>
    </div>

    <div class="confirm-modal confirm-modal--menu" id="historyEntriesModal" aria-hidden="true">
        <div class="confirm-modal-backdrop" data-entries-close></div>
        <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="historyEntriesTitle">
            <div class="confirm-modal-head">
                <div class="confirm-modal-copy">
                    <h2 id="historyEntriesTitle">Entries Not Available</h2>
                    <p class="confirm-modal-text" id="historyEntriesText">Not enough entries to show that many rows.</p>
                </div>
            </div>
            <div class="confirm-modal-divider" aria-hidden="true"></div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-primary" id="historyEntriesOk" data-entries-close>OK</button>
            </div>
        </div>
    </div>
</section>

<style>
.pds-template-page {
    border: 1px solid rgba(214, 226, 239, 0.95);
    box-shadow: 0 16px 34px rgba(15, 35, 60, 0.08);
}

.pds-template-subtitle {
    margin-top: 8px;
    color: #5d728b;
    font-size: 14px;
}

.pds-template-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 10px;
}

.pds-template-admin-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.pds-template-admin-card {
    background: linear-gradient(180deg, #ffffff, #f8fbff);
    border: 1px solid #d3e1f0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 22px rgba(15, 35, 60, 0.06);
}

.pds-template-admin-card h4 {
    margin: 0 0 12px;
    font-size: 18px;
    color: #163654;
}

.pds-template-admin-card p {
    margin: 0 0 10px;
    color: #4d6178;
}

.pds-template-meta-list {
    display: grid;
    gap: 8px;
    margin-bottom: 14px;
}

.pds-template-meta-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    font-size: 13px;
    padding: 8px 10px;
    border-radius: 10px;
    background: rgba(15, 76, 129, 0.05);
}

.pds-template-meta-row span {
    color: #5d728b;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 11px;
}

.pds-template-meta-row strong {
    color: #1c3f63;
    text-align: right;
}

.pds-template-empty {
    padding: 12px;
    border-radius: 10px;
    background: #f7fbff;
    border: 1px dashed #bfd2e6;
}

.pds-template-help {
    margin-bottom: 14px;
}

.pds-template-upload-form {
    display: grid;
    gap: 12px;
}

.pds-template-upload-form .form-group {
    margin: 0;
}

.pds-template-upload-form label {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #1f4368;
    font-weight: 800;
}

.pds-template-file-wrap {
    border: 1px solid #b9cee5;
    border-radius: 12px;
    background: linear-gradient(180deg, #ffffff, #f4f9ff);
    padding: 12px;
}

.pds-template-file-wrap input[type="file"] {
    width: 100%;
    color: #2c4968;
}

.pds-template-upload-form .btn {
    justify-self: start;
    min-height: 46px;
    border-radius: 12px;
    font-weight: 800;
    padding-inline: 20px;
}

.pds-template-history {
    margin-top: 20px;
    border: 1px solid #dee2e6;
    border-radius: 0;
    background: #fff;
    overflow: visible;
}

.pds-template-history-head {
    padding: 16px 18px;
    border-bottom: 1px solid #dee2e6;
    background: #fff;
}

.pds-template-history-head h4 {
    margin: 0;
    color: #163654;
}

.pds-template-history-head p {
    margin: 6px 0 0;
    color: #5d728b;
    font-size: 13px;
}

.pds-dt-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid #dde7f3;
    background: #fff;
}

.pds-dt-length,
.pds-dt-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4f6178;
    font-size: 13px;
    font-weight: 600;
}

.pds-dt-length select {
    height: 32px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0 8px;
    background: #fff;
}

.pds-dt-search input {
    height: 32px;
    width: 220px;
    max-width: 100%;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0 10px;
    background: #fff;
}

.pds-template-history-table-wrap {
    overflow-x: auto;
}

.pds-template-history-table {
    width: 100%;
    border-collapse: collapse;
}

.pds-template-history-table th,
.pds-template-history-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e7edf6;
    text-align: left;
    font-size: 13px;
}

.pds-template-history-table th {
    font-size: 14px;
    font-weight: 800;
    color: #2c3e50;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-right: 1px solid #e9ecef;
}

.pds-template-history-table th:last-child {
    border-right: none;
}

.pds-template-history-table td {
    border-top: 1px solid #e9ecef;
    border-bottom: none;
    background: #fff;
}

.pds-template-history-table tr:nth-child(even) td {
    background: #f9f9f9;
}

.pds-template-history-table tr:hover td {
    background: #f1f6ff;
}

.pds-template-history-delete-form {
    margin: 0;
}

.pds-template-history-actions {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.pds-template-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    opacity: 0.9;
}

.pds-template-view-btn:hover {
    opacity: 1;
}

.pds-template-eye-icon {
    font-size: 15px;
    color: #111;
    line-height: 1;
}

.pds-template-delete-btn {
    border: 0;
    background: transparent;
    color: #c13535;
    cursor: pointer;
    padding: 2px;
    line-height: 1;
    font-size: 14px;
    opacity: 0.9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pds-template-delete-btn:hover {
    opacity: 1;
    color: #a62222;
}

.pds-template-delete-btn:focus-visible {
    outline: 2px solid rgba(193, 53, 53, 0.28);
    outline-offset: 2px;
    border-radius: 4px;
}

.pds-template-history-empty {
    padding: 16px 18px;
    color: #5d728b;
}

.pds-history-pagination {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
}

.pds-history-pagination-meta {
    color: #6f86a3;
    font-weight: 600;
}

.pds-template-pagination {
    display: flex;
    align-items: center;
    gap: 0;
    justify-content: flex-end;
    padding: 0;
    border-top: 0;
    background: transparent;
    flex-wrap: wrap;
}

.pds-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 0;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #0f4c81;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    margin-left: -1px;
}

.pds-page-link:hover {
    background: #e9ecef;
    border-color: #dee2e6;
    z-index: 1;
}

.pds-page-link.is-active {
    background: #0f4c81;
    border-color: #0f4c81;
    color: #fff;
    z-index: 2;
}

.pds-page-link.is-disabled {
    opacity: 0.45;
    pointer-events: none;
}

.pds-page-ellipsis {
    color: #6a7d94;
    font-weight: 800;
    padding-inline: 2px;
}

.pds-page-link:first-child {
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
    margin-left: 0;
}

.pds-page-link:last-child {
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}

@media (max-width: 900px) {
    .pds-template-admin-grid {
        grid-template-columns: 1fr;
    }

    .pds-history-pagination {
        flex-direction: column;
        align-items: flex-start;
    }

    .pds-dt-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .pds-template-meta-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .pds-template-meta-row strong {
        text-align: left;
    }
}
</style>

<script>
(function () {
    if (window.__pdsTemplateDeleteConfirmBound) return;
    window.__pdsTemplateDeleteConfirmBound = true;

    function bindDeleteConfirm() {
        var modal = document.getElementById('historyDeleteConfirmModal');
        if (!modal) return;

        var cancelTargets = modal.querySelectorAll('[data-delete-cancel]');
        var yesBtn = document.getElementById('historyDeleteYes');
        var noBtn = document.getElementById('historyDeleteNo');
        var confirmText = document.getElementById('historyDeleteConfirmText');
        var pendingForm = null;
        var lastFocus = null;

        function setOpen(next) {
            var isOpen = !!next;
            modal.classList.toggle('is-open', isOpen);
            modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            document.body.classList.toggle('modal-open', isOpen);
            if (isOpen && noBtn) {
                noBtn.focus();
            } else if (!isOpen && lastFocus) {
                lastFocus.focus();
            }
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-delete-trigger="true"]');
            if (!trigger) return;
            e.preventDefault();
            pendingForm = trigger.closest('form');
            lastFocus = trigger;

            if (confirmText && pendingForm) {
                var row = pendingForm.closest('tr');
                var nameCell = row ? row.querySelector('td') : null;
                var fileName = nameCell ? nameCell.textContent.trim() : 'this template version';
                confirmText.textContent = 'Delete "' + fileName + '" from version history? This action cannot be undone.';
            }

            setOpen(true);
        });

        cancelTargets.forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                pendingForm = null;
                setOpen(false);
            });
        });

        if (yesBtn) {
            yesBtn.addEventListener('click', function () {
                if (pendingForm) {
                    pendingForm.submit();
                } else {
                    setOpen(false);
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                pendingForm = null;
                setOpen(false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindDeleteConfirm, { once: true });
    } else {
        bindDeleteConfirm();
    }
}());

(function () {
    if (window.__pdsTemplateDeleteSuccessModalBound) return;
    window.__pdsTemplateDeleteSuccessModalBound = true;

    var shouldOpen = <?php echo $showDeleteSuccessModal ? 'true' : 'false'; ?>;
    if (!shouldOpen) return;

    function bindDeleteSuccessModal() {
        var modal = document.getElementById('historyDeleteSuccessModal');
        if (!modal) return;
        var closeTargets = modal.querySelectorAll('[data-delete-success-close]');
        var okBtn = document.getElementById('historyDeleteSuccessOk');

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        if (okBtn) okBtn.focus();

        closeTargets.forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                closeModal();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindDeleteSuccessModal, { once: true });
    } else {
        bindDeleteSuccessModal();
    }
}());

(function () {
    if (window.__pdsLiveTimeBound) return;
    window.__pdsLiveTimeBound = true;

    function formatAbsolute(date) {
        var month = date.toLocaleString(undefined, { month: 'long' });
        var day = date.getDate();
        var year = date.getFullYear();
        var time = date.toLocaleTimeString(undefined, {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        return month + ' ' + day + ', ' + year + ' ' + time;
    }

    function renderLiveTimes() {
        document.querySelectorAll('.pds-live-time[data-timestamp]').forEach(function (el) {
            var ts = parseInt(el.getAttribute('data-timestamp'), 10);
            if (!ts || Number.isNaN(ts)) return;
            var dt = new Date(ts * 1000);
            el.textContent = formatAbsolute(dt);
        });
    }
    window.__pdsRenderLiveTimes = renderLiveTimes;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            renderLiveTimes();
            setInterval(renderLiveTimes, 1000);
        }, { once: true });
    } else {
        renderLiveTimes();
        setInterval(renderLiveTimes, 1000);
    }
}());

(function () {
    if (window.__pdsLiveSizeBound) return;
    window.__pdsLiveSizeBound = true;

    var lastSignature = '';
    var lastTotal = <?php echo (int) $historyTotalCount; ?>;
    var pendingPerPage = null;
    var modalOpen = false;

    function formatKb(bytes) {
        var value = Number(bytes || 0) / 1024;
        return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' KB';
    }

    function openEntriesModal(message) {
        var modal = document.getElementById('historyEntriesModal');
        var text = document.getElementById('historyEntriesText');
        if (!modal) return;
        if (text) text.textContent = message || 'Not enough entries to show that many rows.';
        modalOpen = true;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        var ok = document.getElementById('historyEntriesOk');
        if (ok) ok.focus();
    }

    function closeEntriesModal() {
        var modal = document.getElementById('historyEntriesModal');
        if (!modal) return;
        modalOpen = false;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    function bindEntriesModal() {
        var modal = document.getElementById('historyEntriesModal');
        if (!modal) return;
        var okBtn = document.getElementById('historyEntriesOk');
        var closeTargets = modal.querySelectorAll('[data-entries-close]');

        if (okBtn) {
            okBtn.addEventListener('click', function (e) {
                e.preventDefault();
                closeEntriesModal();

                var select = document.getElementById('history_per_page');
                var page = document.getElementById('history_page');
                var form = document.getElementById('pdsHistoryControls');
                if (!select || !form || pendingPerPage === null) return;

                select.value = String(pendingPerPage);
                pendingPerPage = null;
                if (page) page.value = '1';
                form.submit();
            });
        }

        closeTargets.forEach(function (el) {
            if (okBtn && el === okBtn) return;
            el.addEventListener('click', function (e) {
                e.preventDefault();
                pendingPerPage = null;
                closeEntriesModal();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                pendingPerPage = null;
                closeEntriesModal();
            }
        });
    }

    function bestPerPage(total) {
        if (total >= 50) return 50;
        if (total >= 25) return 25;
        return 10;
    }

    function enforcePerPage(total) {
        var select = document.getElementById('history_per_page');
        var page = document.getElementById('history_page');
        var form = document.getElementById('pdsHistoryControls');
        if (!select || !form) return;

        var selected = parseInt(select.value || '10', 10) || 10;

        // If user tries 50 but there aren't 50 items, show modal and downgrade.
        if (selected === 50 && total < 50) {
            if (modalOpen) return;
            var next = bestPerPage(total);
            pendingPerPage = next;
            // Keep current selection until user confirms, so modal doesn't flash on navigation.
            openEntriesModal('Only ' + total + ' entries available. Switch to ' + next + ' entries?');
            return;
        }
    }

    // Immediately enforce on load (covers existing low counts).
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindEntriesModal();
            enforcePerPage(lastTotal);
        }, { once: true });
    } else {
        bindEntriesModal();
        enforcePerPage(lastTotal);
    }

    function refreshTemplateData() {
        // Keep polling with current URL params so results match filters/per-page/page.
        var url = new URL(window.location.href);
        url.searchParams.set('history_json', '1');
        fetch(url.toString(), { cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || !data.ok) return;

                if (data.current_template) {
                    var currentSize = document.getElementById('currentTemplateSize');
                    var currentUpdated = document.getElementById('currentTemplateUpdated');

                    if (currentSize && typeof data.current_template.size_bytes === 'number') {
                        currentSize.textContent = formatKb(data.current_template.size_bytes);
                    }
                    if (currentUpdated && data.current_template.updated_at_formatted) {
                        currentUpdated.textContent = data.current_template.updated_at_formatted;
                    }
                }

                if (typeof data.history_total_count === 'number') {
                    lastTotal = data.history_total_count;
                    enforcePerPage(lastTotal);
                }

                if (data.history_signature && lastSignature && data.history_signature !== lastSignature) {
                    // New versions added/removed: reload so the table rows and actions are correct.
                    try {
                        sessionStorage.setItem('pds_template_history_scroll_y', String(window.scrollY || 0));
                    } catch (err) {}
                    window.location.reload();
                    return;
                }
                if (data.history_signature && !lastSignature) {
                    lastSignature = data.history_signature;
                }

                if (Array.isArray(data.history)) {
                    var historyMap = {};
                    data.history.forEach(function (item) {
                        if (item && item.name) historyMap[item.name] = item;
                    });

                    document.querySelectorAll('tr[data-history-name]').forEach(function (row) {
                        var name = row.getAttribute('data-history-name');
                        if (!name || !historyMap[name]) return;

                        var sizeCell = row.querySelector('.pds-history-size');
                        var timeCell = row.querySelector('.pds-live-time');
                        var item = historyMap[name];

                        if (sizeCell && typeof item.size_bytes === 'number') {
                            sizeCell.textContent = formatKb(item.size_bytes);
                        }
                        if (timeCell && typeof item.updated_at === 'number' && item.updated_at > 0) {
                            timeCell.setAttribute('data-timestamp', String(item.updated_at));
                        }
                    });

                    if (typeof window.__pdsRenderLiveTimes === 'function') {
                        window.__pdsRenderLiveTimes();
                    }
                }
            })
            .catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            refreshTemplateData();
            setInterval(refreshTemplateData, 5000);
        }, { once: true });
    } else {
        refreshTemplateData();
        setInterval(refreshTemplateData, 5000);
    }
}());

(function () {
    const KEY = 'pds_template_history_scroll_y';

    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[data-preserve-scroll="1"]');
        if (!a) return;
        const href = a.getAttribute('href') || '';
        if (href === '' || href === '#') return;
        try {
            sessionStorage.setItem(KEY, String(window.scrollY || 0));
        } catch (err) {
            // ignore
        }
    });

    function restore() {
        let y = null;
        try {
            y = sessionStorage.getItem(KEY);
            sessionStorage.removeItem(KEY);
        } catch (err) {
            y = null;
        }
        if (y === null) return;
        const n = parseInt(y, 10);
        if (!Number.isFinite(n)) return;

        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        requestAnimationFrame(() => window.scrollTo(0, n));
        setTimeout(() => window.scrollTo(0, n), 0);
        setTimeout(() => window.scrollTo(0, n), 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restore, { once: true });
    } else {
        restore();
    }
}());

(function () {
    const form = document.getElementById('pdsHistoryControls');
    if (!form) return;
    const page = document.getElementById('history_page');
    const q = document.getElementById('history_q');
    const per = document.getElementById('history_per_page');

    let t = null;
    function submitSoon(delayMs) {
        if (page) page.value = '1';
        if (t) clearTimeout(t);
        t = setTimeout(() => form.submit(), delayMs);
    }

    if (q) q.addEventListener('input', () => submitSoon(450));
    if (per) per.addEventListener('change', () => {
        if (page) page.value = '1';
        form.submit();
    });
}());
</script>

<?php require_once 'includes/footer.php'; ?>
