<?php

// SMTP settings (recommended for verification-code emails).
// For Gmail, use an App Password and set SMTP_HOST to smtp.gmail.com, SMTP_PORT to 587, SMTP_SECURE to tls.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls', 'ssl', or '' (none)
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@example.com');
define('SMTP_FROM_NAME', 'LTO-HRIS');
