<?php
// Centralized favicon links (used by both authenticated and public pages).
// Note: Browsers control favicon display size in the tab; supplying multiple sizes improves sharpness.
// Uses APP_BASE_URL when configured so URLs work even when the app is deployed under a subpath.

function __favicon_href(string $path): string
{
    if (defined('APP_BASE_URL') && APP_BASE_URL !== '') {
        return rtrim(APP_BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    return ltrim($path, '/');
}

function __favicon_url(string $path, ?int $version = null): string
{
    $href = __favicon_href($path);
    $v = $version ?? 0;
    if ($v > 0) {
        $href .= (str_contains($href, '?') ? '&' : '?') . 'v=' . $v;
    }
    return $href;
}

$__favicon_version = @filemtime(__DIR__ . '/../assets/img/favicon-32x32.png');
$__favicon_version = is_int($__favicon_version) ? $__favicon_version : null;
?>
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars(__favicon_url('assets/img/favicon-16x16.png', $__favicon_version), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars(__favicon_url('assets/img/favicon-32x32.png', $__favicon_version), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="icon" type="image/png" sizes="48x48" href="<?php echo htmlspecialchars(__favicon_url('assets/img/favicon-48x48.png', $__favicon_version), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="shortcut icon" type="image/png" href="<?php echo htmlspecialchars(__favicon_url('assets/img/favicon-32x32.png', $__favicon_version), ENT_QUOTES, 'UTF-8'); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars(__favicon_url('assets/img/apple-touch-icon.png', $__favicon_version), ENT_QUOTES, 'UTF-8'); ?>">
