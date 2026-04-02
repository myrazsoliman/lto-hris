<?php

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

