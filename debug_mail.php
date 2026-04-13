<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';

if (!AUTH_DEV_MODE) {
    http_response_code(404);
    echo "Not found\n";
    exit;
}

$to = isset($_GET['to']) ? trim((string) $_GET['to']) : '';
if ($to === '') {
    $to = 'test@example.com';
}

echo "=== DEBUGGING EMAIL SENDING ===\n\n";
echo "To: {$to}\n";
echo "SMTP_HOST: " . (SMTP_HOST !== '' ? SMTP_HOST : '(empty)') . "\n";
echo "SMTP_PORT: " . (int) SMTP_PORT . "\n";
echo "SMTP_SECURE: " . (SMTP_SECURE !== '' ? SMTP_SECURE : '(none)') . "\n";
echo "SMTP_USER: " . (SMTP_USER !== '' ? SMTP_USER : '(empty)') . "\n";
echo "SMTP_FROM_EMAIL: " . (SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : '(empty)') . "\n";
echo "SMTP_FROM_NAME: " . (SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : '(empty)') . "\n\n";

$subject = 'LTO HRIS Mail Test';
$text = "This is a test message from LTO HRIS.\n\nIf you received this, SMTP is configured correctly.";
$html = build_verification_email_html('SMTP Test', '123456', 5, 'If you can read this nicely, HTML email is working.');
$ok = send_email_message_html($to, $subject, $text, $html);

echo $ok ? "Result: SENT\n" : "Result: FAILED\n";
if (!$ok) {
    echo "Last error: " . last_mail_error() . "\n";
}
