<?php

// Optional per-environment overrides. Create `includes/config.local.php` (not committed)
// to define constants (SMTP_*, APP_BASE_URL, etc.) for your server.
$localConfigCandidates = [
    __DIR__ . '/config.local.php',
];
foreach ($localConfigCandidates as $localConfigPath) {
    if (is_file($localConfigPath)) {
        require_once $localConfigPath;
        break;
    }
}

if (!defined('APP_BASE_URL')) {
    // Example: https://hris.lto.gov.ph or http://localhost/LTO%20HRIS
    define('APP_BASE_URL', (string) (getenv('APP_BASE_URL') ?: ''));
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', (string) (getenv('SMTP_HOST') ?: ''));
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
}
if (!defined('SMTP_SECURE')) {
    // 'tls', 'ssl', or '' (none)
    define('SMTP_SECURE', (string) (getenv('SMTP_SECURE') ?: 'tls'));
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', (string) (getenv('SMTP_USER') ?: ''));
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', (string) (getenv('SMTP_PASS') ?: ''));
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', (string) (getenv('SMTP_FROM_EMAIL') ?: ''));
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', (string) (getenv('SMTP_FROM_NAME') ?: 'LTO HRIS'));
}

if (!defined('PASSWORD_RESET_EXPIRES_MINUTES')) {
    define('PASSWORD_RESET_EXPIRES_MINUTES', (int) (getenv('PASSWORD_RESET_EXPIRES_MINUTES') ?: 5));
}

// Reverse proxy support (only enable when you control/ trust the proxy).
// Example env:
// TRUST_PROXY_HEADERS=1
// TRUSTED_PROXY_IPS=127.0.0.1,::1
if (!defined('TRUST_PROXY_HEADERS')) {
    define('TRUST_PROXY_HEADERS', (bool) (getenv('TRUST_PROXY_HEADERS') ?: false));
}
if (!defined('TRUSTED_PROXY_IPS')) {
    $raw = (string) (getenv('TRUSTED_PROXY_IPS') ?: '');
    $ips = array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
    define('TRUSTED_PROXY_IPS', $ips);
}
