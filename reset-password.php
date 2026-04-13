<?php
require_once __DIR__ . '/includes/auth.php';

$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
$token = preg_replace('/[^a-f0-9]/i', '', $token);
$error = '';
$success = '';
$newPassword = '';
$confirmPassword = '';
$csrfToken = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim((string) $_POST['token']) : $token;
    $token = preg_replace('/[^a-f0-9]/i', '', $token);
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($token === '' || strlen($token) < 32) {
        $error = 'Invalid or expired reset link.';
    } elseif ($newPassword === '' || $confirmPassword === '') {
        $error = 'Please enter your new password.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password and confirm password do not match.';
    } else {
        [$ok, $msg] = reset_password_with_token($token, $newPassword);
        if ($ok) {
            header('Location: index.php?login=1&password_reset=1');
            exit;
        }
        $error = (string) $msg;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | LTO HRIS</title>
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
    <style>
        html,body{height:100%;overflow:hidden}
        :root{--reset-pad:clamp(12px,3.8vw,22px)}
        body{padding-top:0 !important}
        .gov-topbar{display:none !important}
        .reset-center{height:100vh;height:100dvh;display:flex;align-items:center;justify-content:center;padding:var(--reset-pad);box-sizing:border-box;overflow:hidden}
        .reset-card{width:min(100%,560px);background:#ffffff;border:1px solid #dbe6f6;border-radius:22px;box-shadow:0 18px 50px rgba(12,30,54,.12);padding:26px 26px 22px;transform:translateY(-40px)}
        .reset-card .login-input-icon{color:#1f4f8f}
        @media (max-width:560px){
            .reset-card{padding:18px 16px 16px;border-radius:20px;transform:translateY(-12px)}
            .reset-card .reset-logo{width:38px !important;height:38px !important}
            .reset-card .reset-title{margin:10px 0 6px !important;font-size:26px !important}
            .reset-card .reset-lead{margin:0 0 14px !important;font-size:14px !important}
            .reset-card .login-input-shell{min-height:68px !important;padding:16px 18px !important;border-radius:16px !important}
            .reset-card .login-input-wrap-modal input{font-size:16px !important}
            .reset-card .login-password-toggle{width:40px !important;height:40px !important}
            .reset-card .login-submit-btn-modal{min-height:54px !important;border-radius:16px !important}
        }
        @media (max-height:720px){.reset-card{transform:none}}
    </style>
</head>
<body style="margin:0;background:#f4f7fb;overflow:hidden;padding-top:0;">
    <div class="reset-center">
        <div class="reset-card">
            <h1 class="reset-title" style="margin:4px 0 6px;font-size:28px;letter-spacing:-.02em;color:#142b46;">Reset Password</h1>
            <p class="reset-lead" style="margin:0 0 16px;color:#5a6e88;line-height:1.55;">Create a new password for your account.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" style="margin-bottom:14px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="reset-password.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="login-input-wrap login-input-wrap-modal" style="margin-bottom:10px;">
                    <div class="login-input-shell">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                            </svg>
                        </span>
                        <input type="password" id="resetNewPassword" name="new_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" required>
                        <label class="login-floating-label" for="resetNewPassword">New Password</label>
                    </div>
                    <button type="button" class="login-password-toggle" data-password-toggle="resetNewPassword" aria-label="Show password" aria-pressed="false">
                        <span class="login-password-icon login-password-icon-show" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" />
                            </svg>
                        </span>
                        <span class="login-password-icon login-password-icon-hide" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M2.6 3.9 20.1 21.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M10.6 6.3A11.2 11.2 0 0 1 12 6.2c5.3 0 9.6 3.3 10.8 5.8-.5 1.1-1.5 2.6-3 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7.2 7.4C4.8 8.5 3 10.4 1.9 12c1.3 2.7 5.5 5.8 10.1 5.8 1.6 0 3.2-.4 4.6-1.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.9 9.1a4 4 0 0 1 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="login-input-wrap login-input-wrap-modal" style="margin-bottom:14px;">
                    <div class="login-input-shell">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                            </svg>
                        </span>
                        <input type="password" id="resetConfirmPassword" name="confirm_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" required>
                        <label class="login-floating-label" for="resetConfirmPassword">Confirm Password</label>
                    </div>
                    <button type="button" class="login-password-toggle" data-password-toggle="resetConfirmPassword" aria-label="Show password" aria-pressed="false">
                        <span class="login-password-icon login-password-icon-show" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" />
                            </svg>
                        </span>
                        <span class="login-password-icon login-password-icon-hide" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M2.6 3.9 20.1 21.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M10.6 6.3A11.2 11.2 0 0 1 12 6.2c5.3 0 9.6 3.3 10.8 5.8-.5 1.1-1.5 2.6-3 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7.2 7.4C4.8 8.5 3 10.4 1.9 12c1.3 2.7 5.5 5.8 10.1 5.8 1.6 0 3.2-.4 4.6-1.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.9 9.1a4 4 0 0 1 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </button>
                </div>

                <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split" style="margin-top:0;">
                    <span>Update Password</span>
                </button>
            </form>

            <div style="margin-top:14px;text-align:center;">
                <a href="index.php?login=1" style="color:#184d97;font-weight:700;text-decoration:none;">Back to login</a>
            </div>
        </div>
    </div>
    <script>
        (function() {
            const toggles = Array.from(document.querySelectorAll('[data-password-toggle]'));
            if (!toggles.length) return;

            toggles.forEach((btn) => {
                const targetId = btn.getAttribute('data-password-toggle') || '';
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) return;

                btn.addEventListener('click', () => {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    btn.classList.toggle('is-active', isPassword);
                    btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    input.focus();
                });
            });
        }());
    </script>
</body>
</html>
