<?php
$pageTitle = 'My Account';
$activePage = 'account.php';
require_once 'includes/auth.php';
require_login();
require_once 'includes/notifications.php';

$currentUser = current_user();
$error = '';
$success = '';

if (isset($_GET['verify_email_change'])) {
    $token = (string) $_GET['verify_email_change'];
    [$ok, $result] = verify_email_change_request($currentUser['id'] ?? 0, $token);
    if ($ok) {
        $_SESSION['user']['email'] = (string) $result;
        header('Location: account.php?success=email');
        exit;
    }
    $error = (string) $result;
}

if (isset($_GET['success']) && $_GET['success'] === 'email') {
    $success = 'Email updated successfully!';
} elseif (isset($_GET['success']) && $_GET['success'] === 'email_pending') {
    $success = 'Email change requested. Please verify using the link sent to your email.';
} elseif (isset($_GET['success']) && $_GET['success'] === 'password') {
    $success = 'Password updated successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_email') {
            $newEmail = $_POST['new_email'] ?? '';
            $confirmEmail = $_POST['confirm_email'] ?? '';
            $currentPassword = $_POST['current_password'] ?? '';

            if ($newEmail === '' || $confirmEmail === '' || $currentPassword === '') {
                $error = 'All fields are required.';
            } elseif ($newEmail !== $confirmEmail) {
                $error = 'Email addresses do not match.';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $user = fetch_user_record($currentUser['email'] ?? '');
                if ($user && password_verify($currentPassword, $user['password'])) {
                    if (
                        normalize_identifier($newEmail) !== normalize_identifier($currentUser['email'] ?? '')
                        && user_exists($newEmail)
                    ) {
                        $error = 'This email address is already in use.';
                    } else {
                        $token = create_email_change_request((int) $currentUser['id'], $newEmail);
                        $verifyLink = build_email_change_verification_url($token);

                        $subject = 'Confirm your LTO HRIS email change';
                        $message = "A request was made to change your account email.\n\n";
                        $message .= "Verify this change using the link below.\n";
                        $message .= $verifyLink . "\n\n";
                        $message .= "This link expires in 24 hours.\n\n";
                        $message .= "If you did not request this, you may ignore this email.";
                        $html = build_email_change_email_html($verifyLink, 24);
                        send_email_message_html($currentUser['email'] ?? '', $subject, $message, $html);

                        log_auth_event('email_change_requested', $currentUser['id'] ?? null, $currentUser['email'] ?? null);
                        create_notification(
                            (int) $currentUser['id'],
                            'account',
                            'Email change requested',
                            'A verification link was sent to confirm your new email address.',
                            'account.php'
                        );

                        header('Location: account.php?success=email_pending');
                        exit;
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        } elseif ($_POST['action'] === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $error = 'All fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 10) {
                $error = 'Password must be at least 10 characters long.';
            } else {
                $user = fetch_user_record($currentUser['email'] ?? '');
                if ($user && password_verify($currentPassword, $user['password'])) {
                    $passwordErrors = password_policy_errors($newPassword, '', $currentUser['email'] ?? '');
                    if (!empty($passwordErrors)) {
                        $error = implode(' ', $passwordErrors);
                    } else {
                        $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $currentUser['id']]);
                        create_notification(
                            (int) $currentUser['id'],
                            'security',
                            'Password updated',
                            'Your account password was changed successfully.',
                            'account.php'
                        );

                        header('Location: account.php?success=password');
                        exit;
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        }
    }
}

require_once 'includes/header.php';

$recentLogins = [];
try {
    $stmt = db()->prepare(
        'SELECT ip_address, created_at
         FROM auth_activity
         WHERE user_id = ? AND event = ?
         ORDER BY created_at DESC
         LIMIT 5'
    );
    $stmt->execute([(int) ($currentUser['id'] ?? 0), 'login_success']);
    $recentLogins = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $recentLogins = [];
}
$lastLogin = null;
try {
    $stmt = db()->prepare(
        'SELECT ip_address, user_agent, created_at
         FROM auth_activity
         WHERE user_id = ? AND event = ?
         ORDER BY created_at DESC
         LIMIT 1'
    );
    $stmt->execute([(int) ($currentUser['id'] ?? 0), 'login_success']);
    $lastLogin = $stmt->fetch() ?: null;
} catch (Throwable $e) {
    $lastLogin = null;
}

$roles = $currentUser['roles'] ?? [];
$primaryRole = 'employee';
foreach (['superadmin', 'admin', 'hr_officer', 'employee'] as $role) {
    if (in_array($role, $roles, true)) {
        $primaryRole = $role;
        break;
    }
}

$roleLabels = [
    'superadmin' => 'Super Administrator',
    'admin' => 'Administrator',
    'hr_officer' => 'HR Officer',
    'employee' => 'Employee',
];
$roleLabel = $roleLabels[$primaryRole] ?? 'User';

$displayName = (string) ($currentUser['display_name'] ?? 'User');
$initial = strtoupper(substr(trim($displayName), 0, 1)) ?: 'U';
$requiresTwoFactor = requires_2fa($roles);
$recentLoginCount = count($recentLogins);
?>

<section class="card account-page-head">
    <div class="account-page-head-inner">
        <div class="account-page-head-copy">
            <span class="tag">Official Account Record</span>
            <h3>Profile Management</h3>
            <p class="text-muted small-text account-subtitle">
                Maintain your official login credentials and review recent account access.
            </p>
            <div class="account-head-badges">
                <span class="account-head-badge">
                    <i class="fa-solid fa-id-badge" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($roleLabel); ?>
                </span>
                <span class="account-head-badge">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <?php echo $requiresTwoFactor ? '2FA required' : '2FA optional'; ?>
                </span>
                <span class="account-head-badge">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    <?php echo htmlspecialchars((string) $recentLoginCount); ?> recent sign-ins
                </span>
            </div>
        </div>
        <div class="account-office-block">
            <div class="account-office-icon" aria-hidden="true">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <span class="account-office-label">Office</span>
            <strong><?php echo htmlspecialchars($systemOffice ?? 'Land Transportation Office'); ?></strong>
        </div>
    </div>
</section>

<div class="account-layout">
    <div class="account-col">
        <section class="card account-summary">
            <div class="account-summary-head">
                <div class="account-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
                <div class="account-summary-meta">
                    <div class="account-name"><?php echo htmlspecialchars($displayName); ?></div>
                    <div class="role-badge"><?php echo htmlspecialchars($roleLabel); ?></div>
                    <div class="account-summary-note">Authorized system user</div>
                </div>
            </div>

            <div class="account-summary-strip">
                <div class="account-summary-stat">
                    <span class="account-summary-stat-label">Protection</span>
                    <strong><?php echo $requiresTwoFactor ? 'Enhanced' : 'Standard'; ?></strong>
                </div>
                <div class="account-summary-stat">
                    <span class="account-summary-stat-label">Recent logins</span>
                    <strong><?php echo htmlspecialchars((string) $recentLoginCount); ?></strong>
                </div>
            </div>

            <div class="account-meta">
                <div class="account-meta-row">
                    <span class="account-meta-key">Email</span>
                    <span class="account-meta-value"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                </div>
                <?php if (is_array($lastLogin) && !empty($lastLogin['created_at'])): ?>
                    <div class="account-meta-row">
                        <span class="account-meta-key">Last login</span>
                        <span class="account-meta-value">
                            <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($lastLogin['created_at']))); ?>
                        </span>
                    </div>
                    <div class="account-meta-row">
                        <span class="account-meta-key">Device</span>
                        <span class="account-meta-value">
                            <?php echo htmlspecialchars(format_user_agent((string) ($lastLogin['user_agent'] ?? ''))); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="account-meta-row">
                    <span class="account-meta-key">Status</span>
                    <span class="account-meta-value account-meta-value--active">Active</span>
                </div>
            </div>
        </section>

        <?php if (!empty($recentLogins)): ?>
            <section class="card account-tips">
                <div class="section-head">
                    <div>
                        <span class="tag">Security</span>
                        <h3>Recent Sign-ins</h3>
                        <p class="section-head-desc">Review the latest successful account access activity.</p>
                    </div>
                </div>

                <ul class="account-tip-list">
                    <?php foreach ($recentLogins as $row): ?>
                        <li>
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                            <span>
                                <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))); ?>
                                <?php if (!empty($row['ip_address']) && $row['ip_address'] !== '::1' && $row['ip_address'] !== '127.0.0.1'): ?>
                                    &middot; <?php echo htmlspecialchars($row['ip_address']); ?>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>

    <div class="account-col">
        <section class="card account-panel account-panel--auth">
            <div class="account-auth-shell">
                <div class="account-auth-header">
                    <span class="login-modal-panel-kicker">Credentials</span>
                    <h3 class="account-auth-title">Change Email</h3>
                    <p class="account-auth-subtitle">Update your account email using the same secure verification flow used during sign in.</p>
                </div>

                <?php if ($error && ($_POST['action'] ?? '') === 'update_email'): ?>
                    <div class="notice notice--danger">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success && strpos($success, 'Email') !== false): ?>
                    <div class="notice notice--success">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" class="login-form-modern account-auth-form" data-confirm="Send email verification link for this change?">
                    <input type="hidden" name="action" value="update_email">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                    <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <input type="email" id="new_email" name="new_email" required placeholder=" " autocomplete="email">
                            <label class="login-floating-label" for="new_email">New Email Address</label>
                        </div>
                    </div>

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                    <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <input type="email" id="confirm_email" name="confirm_email" required placeholder=" " autocomplete="email">
                            <label class="login-floating-label" for="confirm_email">Confirm New Email</label>
                        </div>
                    </div>

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap account-auth-input-wrap--password">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                </svg>
                            </span>
                            <input type="password" id="email_current_password" name="current_password" required placeholder=" " autocomplete="current-password" data-caps-indicator="caps_email_current_password">
                            <label class="login-floating-label" for="email_current_password">Current Password</label>
                            <button type="button" class="login-password-toggle account-auth-password-toggle" data-password-toggle="email_current_password" data-toggle-mode="icon" aria-label="Show password" aria-pressed="false">
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
                        <div class="caps-indicator" id="caps_email_current_password" hidden>Caps Lock is on.</div>
                    </div>

                    <div class="form-actions account-auth-actions">
                        <button type="submit" class="login-submit-btn account-auth-submit">
                            <span>Update Email</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card account-panel account-panel--auth">
            <div class="account-auth-shell">
                <div class="account-auth-header">
                    <span class="login-modal-panel-kicker">Credentials</span>
                    <h3 class="account-auth-title">Change Password</h3>
                    <p class="account-auth-subtitle">Use a stronger password and keep your account access protected.</p>
                </div>

                <?php if ($error && ($_POST['action'] ?? '') === 'update_password'): ?>
                    <div class="notice notice--danger">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success && strpos($success, 'Password') !== false): ?>
                    <div class="notice notice--success">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" class="login-form-modern account-auth-form" data-confirm="Update your password now?">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap account-auth-input-wrap--password">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                </svg>
                            </span>
                            <input type="password" id="current_password" name="current_password" required placeholder=" " autocomplete="current-password" data-caps-indicator="caps_current_password">
                            <label class="login-floating-label" for="current_password">Current Password</label>
                            <button type="button" class="login-password-toggle account-auth-password-toggle" data-password-toggle="current_password" data-toggle-mode="icon" aria-label="Show password" aria-pressed="false">
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
                        <div class="caps-indicator" id="caps_current_password" hidden>Caps Lock is on.</div>
                    </div>

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap account-auth-input-wrap--password">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                </svg>
                            </span>
                            <input type="password" id="new_password" name="new_password" required placeholder=" " autocomplete="new-password" data-caps-indicator="caps_new_password" data-strength-meter="passwordStrength">
                            <label class="login-floating-label" for="new_password">New Password</label>
                            <button type="button" class="login-password-toggle account-auth-password-toggle" data-password-toggle="new_password" data-toggle-mode="icon" aria-label="Show password" aria-pressed="false">
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
                        <div class="caps-indicator" id="caps_new_password" hidden>Caps Lock is on.</div>
                    </div>

                    <div class="login-input-wrap login-input-wrap-modal register-input-wrap account-auth-input-wrap account-auth-input-wrap--password">
                        <div class="login-input-shell">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                </svg>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder=" " autocomplete="new-password" data-caps-indicator="caps_confirm_password">
                            <label class="login-floating-label" for="confirm_password">Confirm New Password</label>
                            <button type="button" class="login-password-toggle account-auth-password-toggle" data-password-toggle="confirm_password" data-toggle-mode="icon" aria-label="Show password" aria-pressed="false">
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
                        <div class="caps-indicator" id="caps_confirm_password" hidden>Caps Lock is on.</div>
                    </div>

                    <div class="password-validation-indicator account-password-validation-indicator is-hidden" id="accountPasswordValidationIndicator">
                        <div class="password-strength-progress" aria-hidden="true">
                            <span class="password-strength-progress-bar" id="accountPasswordStrengthProgressBar"></span>
                        </div>
                        <div class="password-strength-alert is-hidden" id="accountPasswordStrengthAlert" data-strength="empty">Password strength: weak.</div>
                        <div class="password-validation-title">Your password must contain:</div>
                        <div class="password-validation-list">
                            <div class="password-validation-item" data-validation="length">
                                <span class="validation-icon validation-icon-invalid">&#10005;</span>
                                <span class="validation-icon validation-icon-valid">&#10003;</span>
                                <span class="validation-text">At least 10 characters</span>
                            </div>
                            <div class="password-validation-item" data-validation="lowercase">
                                <span class="validation-icon validation-icon-invalid">&#10005;</span>
                                <span class="validation-icon validation-icon-valid">&#10003;</span>
                                <span class="validation-text">Lower case letters (a-z)</span>
                            </div>
                            <div class="password-validation-item" data-validation="uppercase">
                                <span class="validation-icon validation-icon-invalid">&#10005;</span>
                                <span class="validation-icon validation-icon-valid">&#10003;</span>
                                <span class="validation-text">Upper case letters (A-Z)</span>
                            </div>
                            <div class="password-validation-item" data-validation="number">
                                <span class="validation-icon validation-icon-invalid">&#10005;</span>
                                <span class="validation-icon validation-icon-valid">&#10003;</span>
                                <span class="validation-text">Numbers (0-9)</span>
                            </div>
                            <div class="password-validation-item" data-validation="special">
                                <span class="validation-icon validation-icon-invalid">&#10005;</span>
                                <span class="validation-icon validation-icon-valid">&#10003;</span>
                                <span class="validation-text">Special characters (e.g. !@#$%^&amp;*)</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions account-auth-actions">
                        <button type="submit" class="login-submit-btn account-auth-submit">
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<script>
    (function() {
        const passwordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const validationIndicator = document.getElementById('accountPasswordValidationIndicator');
        const strengthAlert = document.getElementById('accountPasswordStrengthAlert');
        const progressBar = document.getElementById('accountPasswordStrengthProgressBar');
        const accountForm = passwordField ? passwordField.closest('form') : null;

        if (!passwordField || !confirmPasswordField || !validationIndicator || !strengthAlert || !progressBar) {
            return;
        }

        function validatePassword(password) {
            const categoryCount = [
                /[a-z]/.test(password),
                /[A-Z]/.test(password),
                /\d/.test(password),
                /[^a-zA-Z\d]/.test(password)
            ].filter(Boolean).length;

            const validations = {
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[^a-zA-Z\d]/.test(password),
                length: password.length >= 10,
                categories: categoryCount >= 3,
                categoryCount
            };

            validations.score = ['length', 'uppercase', 'lowercase', 'number', 'special']
                .filter((key) => validations[key]).length;

            return validations;
        }

        function getPasswordStrength(password, validations) {
            if (!password) {
                return { level: 'empty', message: 'Password strength: weak.' };
            }

            if (validations.length && validations.categories) {
                return { level: 'strong', message: 'Password strength: strong.' };
            }

            if (validations.score >= 3 || validations.categoryCount >= 2) {
                return { level: 'medium', message: 'Password strength: medium.' };
            }

            return { level: 'weak', message: 'Password strength: weak.' };
        }

        function updateValidationIndicator(validations) {
            Object.keys(validations).forEach((key) => {
                const item = validationIndicator.querySelector('[data-validation="' + key + '"]');
                if (!item) return;
                item.classList.toggle('is-valid', !!validations[key]);
            });
        }

        function updateStrengthAlert(strength) {
            strengthAlert.dataset.strength = strength.level;
            strengthAlert.textContent = strength.message;
            strengthAlert.classList.toggle('is-hidden', strength.level === 'empty');
        }

        function updateProgressBar(validations) {
            const progress = (validations.score / 5) * 100;
            const strength = getPasswordStrength(passwordField.value, validations);
            progressBar.style.width = progress + '%';
            progressBar.dataset.strength = strength.level;
        }

        function updateValidationVisibility(password) {
            validationIndicator.classList.toggle('is-hidden', !password);
        }

        function checkPasswordStrength() {
            const password = passwordField.value;
            const validations = validatePassword(password);
            updateValidationVisibility(password);
            updateValidationIndicator(validations);
            updateProgressBar(validations);

            if (!password) {
                passwordField.setCustomValidity('');
            } else if (!validations.length || !validations.categories) {
                passwordField.setCustomValidity('Use at least 10 characters and meet at least 3 of these: lowercase, uppercase, number, special character.');
            } else {
                passwordField.setCustomValidity('');
            }

            if (confirmPasswordField.value && confirmPasswordField.value !== password) {
                confirmPasswordField.setCustomValidity('Passwords do not match.');
                updateStrengthAlert({ level: 'weak', message: 'Passwords do not match.' });
            } else {
                confirmPasswordField.setCustomValidity('');
                updateStrengthAlert(getPasswordStrength(password, validations));
            }
        }

        function validateConfirmPassword() {
            if (confirmPasswordField.value && confirmPasswordField.value !== passwordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match.');
                updateStrengthAlert({ level: 'weak', message: 'Passwords do not match.' });
            } else {
                confirmPasswordField.setCustomValidity('');
                checkPasswordStrength();
            }
        }

        passwordField.addEventListener('input', checkPasswordStrength);
        confirmPasswordField.addEventListener('input', validateConfirmPassword);

        if (accountForm) {
            accountForm.addEventListener('submit', function() {
                checkPasswordStrength();
                validateConfirmPassword();
            });
        }

        checkPasswordStrength();
    }());
</script>

<?php require_once 'includes/footer.php'; ?>
