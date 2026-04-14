<?php
require_once __DIR__ . '/includes/auth.php';

$token = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
$token = preg_replace('/[^a-f0-9]/i', '', $token);
$error = '';
$success = '';
$notice = '';
$currentUser = current_user();

if ($token === '' || strlen($token) < 32) {
    $error = 'Invalid or expired email confirmation link.';
} elseif (!is_logged_in()) {
    $_SESSION['pending_email_change_token'] = $token;
    $notice = 'Please sign in to confirm your email change request.';
} else {
    [$ok, $result] = verify_email_change_request($currentUser['id'] ?? 0, $token);
    if ($ok) {
        $_SESSION['user']['email'] = (string) $result;
        $success = 'Your email address has been updated successfully.';
        log_auth_event('email_change_verified', $currentUser['id'] ?? null, (string) $result);
    } else {
        $error = (string) $result;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm Email Change | LTO HRIS</title>
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
    <style>
        html,body{height:100%;overflow:hidden}
        :root{--confirm-pad:clamp(12px,3.8vw,22px)}
        body{margin:0;padding-top:0 !important;background:#f4f7fb;overflow:hidden}
        .gov-topbar{display:none !important}
        .confirm-center{
            height:100vh;
            height:100dvh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:var(--confirm-pad);
            box-sizing:border-box;
            background:
                radial-gradient(circle at 20% 18%, rgba(58,106,168,.10), transparent 28%),
                radial-gradient(circle at 78% 82%, rgba(240,161,40,.08), transparent 26%),
                linear-gradient(180deg,#f6f9fd 0%,#edf3fb 100%);
        }
        .confirm-shell{
            width:min(100%,600px);
            background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
            border:1px solid rgba(208,221,238,.98);
            border-radius:32px;
            box-shadow:0 34px 88px rgba(12,30,54,.22);
            padding:32px 32px 28px;
            text-align:center;
        }
        .confirm-head{
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:8px;
        }
        .confirm-badge{
            width:74px;
            height:74px;
            border-radius:24px;
            display:grid;
            place-items:center;
            box-shadow:inset 0 1px 0 rgba(255,255,255,.8),0 14px 28px rgba(24,55,97,.14);
        }
        .confirm-badge svg{width:28px;height:28px}
        .confirm-badge-success{
            background:radial-gradient(circle at 30% 30%, rgba(132,195,65,.26), transparent 60%), linear-gradient(145deg,#effcf4,#e3f8ea);
            border:1px solid rgba(120,199,146,.42);
            color:#1f7a3a;
        }
        .confirm-badge-error{
            background:radial-gradient(circle at 30% 30%, rgba(231,92,92,.18), transparent 60%), linear-gradient(145deg,#fff0f0,#fee3e3);
            border:1px solid rgba(210,95,95,.34);
            color:#b63636;
        }
        .confirm-badge-notice{
            background:radial-gradient(circle at 30% 30%, rgba(53,120,198,.18), transparent 60%), linear-gradient(145deg,#f2f7ff,#e6effd);
            border:1px solid rgba(116,154,215,.34);
            color:#214a82;
        }
        .confirm-kicker{
            margin:2px 0 0;
            font-size:12px;
            letter-spacing:.18em;
            text-transform:uppercase;
            font-weight:900;
            color:#57708e;
        }
        .confirm-title{
            margin:0;
            font-size:42px;
            line-height:1.08;
            letter-spacing:-.02em;
            color:#142b46;
        }
        .confirm-copy{
            margin:4px 0 0;
            font-size:16px;
            line-height:1.55;
            color:#5a6e88;
        }
        .confirm-subcopy{
            margin:6px 0 0;
            font-size:13px;
            line-height:1.6;
            color:#7286a3;
        }
        .confirm-actions{
            margin-top:24px;
            display:flex;
            justify-content:center;
            gap:12px;
            flex-wrap:wrap;
        }
        .confirm-actions a{text-decoration:none}
        .confirm-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:220px;
            min-height:56px;
            padding:0 22px;
            border-radius:18px;
            font-weight:700;
            letter-spacing:.06em;
            text-transform:uppercase;
            transition:transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
        }
        .confirm-btn:hover{
            transform:translateY(-1px);
        }
        .confirm-btn-primary{
            background:linear-gradient(135deg,#1f5ca8,#2a6bc0);
            color:#fff;
            box-shadow:0 16px 32px rgba(15,76,129,.22);
        }
        .confirm-btn-secondary{
            background:#fff;
            color:#1d3858;
            border:2px solid #0f4c81;
            box-shadow:0 0 0 1px rgba(15,76,129,.22);
        }
        .confirm-btn-secondary:hover{
            background:#edf3fb;
            border-color:#0b3458;
            box-shadow:0 0 0 1px rgba(11,52,88,.28);
        }
        @media (max-width:560px){
            .confirm-shell{width:min(100%,560px);padding:30px 22px 24px;border-radius:24px}
            .confirm-title{font-size:40px}
            .confirm-btn{min-width:100%}
            .confirm-actions{flex-direction:column}
        }
    </style>
</head>
<body>
    <div class="confirm-center">
        <div class="confirm-shell">
            <?php if ($success !== ''): ?>
                <div class="confirm-head">
                    <div class="confirm-badge confirm-badge-success" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M6 12.5 10 16l8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="confirm-kicker">Email Verification</p>
                    <h1 class="confirm-title">Email Confirmed</h1>
                    <p class="confirm-copy"><?php echo htmlspecialchars($success); ?></p>
                    <p class="confirm-subcopy">You can continue managing your account details securely.</p>
                </div>
                <div class="confirm-actions">
                    <a class="confirm-btn confirm-btn-primary" href="account.php">Go to My Account</a>
                </div>
            <?php elseif ($notice !== ''): ?>
                <div class="confirm-head">
                    <div class="confirm-badge confirm-badge-notice" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm0 5.2a1.2 1.2 0 1 1-1.2 1.2A1.2 1.2 0 0 1 12 8.2Zm1.3 8.6h-2.6v-1.8h.7v-3.1h-.7V10h2.6v5h.7Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <p class="confirm-kicker">Account Security</p>
                    <h1 class="confirm-title">Login Required</h1>
                    <p class="confirm-copy"><?php echo htmlspecialchars($notice); ?></p>
                    <p class="confirm-subcopy">After signing in, we will continue the email confirmation automatically.</p>
                </div>
                <div class="confirm-actions">
                    <a class="confirm-btn confirm-btn-primary" href="index.php?login=1">Sign In</a>
                    <a class="confirm-btn confirm-btn-secondary" href="index.php">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="confirm-head">
                    <div class="confirm-badge confirm-badge-error" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 8v4.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <circle cx="12" cy="16.7" r="1.1" fill="currentColor"/>
                            <path d="M10.3 4.7 3.2 17a2 2 0 0 0 1.7 3h14.2a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="confirm-kicker">Email Verification</p>
                    <h1 class="confirm-title">Unable to Confirm</h1>
                    <p class="confirm-copy"><?php echo htmlspecialchars($error !== '' ? $error : 'The email confirmation link could not be processed.'); ?></p>
                    <p class="confirm-subcopy">Request a new email change link if this one has already expired.</p>
                </div>
                <div class="confirm-actions">
                    <a class="confirm-btn confirm-btn-primary" href="<?php echo is_logged_in() ? 'account.php' : 'index.php?login=1'; ?>">
                        <?php echo is_logged_in() ? 'Back to My Account' : 'Sign In'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
