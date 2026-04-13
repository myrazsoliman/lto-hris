<?php
// Simple logout endpoint
require_once 'includes/auth.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

// Show a one-time logout success modal on the landing page.
setcookie('lto_hris_logout_success', '1', [
    'expires' => time() + 15,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

logout_user();
header('Location: index.php?logout_success=1');
exit;
