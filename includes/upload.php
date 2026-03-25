<?php
// File upload helper

const NEWS_BANNER_WIDTH = 2048;
const NEWS_BANNER_HEIGHT = 574;

function ensure_upload_dirs()
{
    $base = __DIR__ . '/../uploads';
    $dirs = ['csc', 'pds', 'saln', 'news'];
    foreach ($dirs as $d) {
        $path = $base . '/' . $d;
        if (!is_dir($path)) mkdir($path, 0777, true);
    }
}

function get_uploaded_image_size($tmpPath)
{
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false) {
        return [false, 'Invalid image file.'];
    }

    return [true, ['width' => $imageInfo[0], 'height' => $imageInfo[1], 'mime' => $imageInfo['mime'] ?? '']];
}

function handle_file_upload($fieldName, $category = 'csc')
{
    ensure_upload_dirs();
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return [false, 'No file uploaded or upload error.'];
    }

    $file = $_FILES[$fieldName];
    $allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if ($file['size'] > $maxSize) return [false, 'File exceeds maximum size (10MB).'];
    if (!in_array($file['type'], $allowed)) return [false, 'Unsupported file type.'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe = bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/' . basename($category);
    $dest = $dir . '/' . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [false, 'Failed to move uploaded file.'];
    }

    // return web-accessible path
    $rel = 'uploads/' . basename($category) . '/' . $safe;
    return [true, $rel];
}

function handle_news_banner_upload($fieldName)
{
    ensure_upload_dirs();
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return [false, 'No banner image uploaded or upload error.'];
    }

    $file = $_FILES[$fieldName];
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        return [false, 'Banner image exceeds maximum size (10MB).'];
    }

    [$validImage, $imageData] = get_uploaded_image_size($file['tmp_name']);
    if (!$validImage) {
        return [false, $imageData];
    }

    $allowedImageMimes = ['image/jpeg', 'image/png'];
    if (!in_array($imageData['mime'], $allowedImageMimes, true)) {
        return [false, 'Banner image must be a JPG or PNG file.'];
    }

    if ($imageData['width'] !== NEWS_BANNER_WIDTH || $imageData['height'] !== NEWS_BANNER_HEIGHT) {
        return [
            false,
            'Banner image must be exactly ' . NEWS_BANNER_WIDTH . 'x' . NEWS_BANNER_HEIGHT . ' pixels.'
        ];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }

    if (!in_array($ext, ['jpg', 'png'], true)) {
        $ext = $imageData['mime'] === 'image/png' ? 'png' : 'jpg';
    }

    $safe = 'news_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/news';
    $dest = $dir . '/' . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [false, 'Failed to move uploaded banner image.'];
    }

    return [true, 'uploads/news/' . $safe];
}
