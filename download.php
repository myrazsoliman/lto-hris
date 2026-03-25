<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$map = [
    'csc' => ['table' => 'csc_forms', 'file' => 'file_path'],
    'pds' => ['table' => 'pds', 'file' => 'file_path'],
    'saln' => ['table' => 'saln_submissions', 'file' => 'file_path'],
];

if (!isset($map[$type]) || !$id) {
    http_response_code(400);
    echo 'Invalid download request.';
    exit;
}

// Only HR staff or admin can download sensitive files
if (!has_role(['admin', 'hr_officer'])) {
    http_response_code(403);
    echo 'Forbidden.';
    exit;
}

$info = $map[$type];
$stmt = db()->prepare('SELECT * FROM ' . $info['table'] . ' WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$rel = $row[$info['file']];
$full = realpath(__DIR__ . '/' . $rel);
$uploadsBase = realpath(__DIR__ . '/uploads');
if (!$full || strpos($full, $uploadsBase) !== 0 || !is_file($full)) {
    http_response_code(404);
    echo 'File not available.';
    exit;
}

$filename = basename($full);
$mime = mime_content_type($full) ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
