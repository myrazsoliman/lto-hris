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
                        $verifyPath = 'account.php?verify_email_change=' . urlencode($token);
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $verifyLink = $scheme . '://' . $host . '/' . ltrim($verifyPath, '/');

                        if (AUTH_DEV_MODE) {
                            $_SESSION['flash_email_change_link'] = $verifyLink;
                        }

                        $subject = 'Confirm your LTO HRIS email change';
                        $message = "A request was made to change your account email.\n\n";
                        $message .= "Verify this change using the link below (valid for 24 hours):\n{$verifyLink}\n\n";
                        $message .= "If you did not request this, you may ignore this email.";
                        send_email_message($currentUser['email'] ?? '', $subject, $message);

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

$devEmailLink = $_SESSION['flash_email_change_link'] ?? '';
unset($_SESSION['flash_email_change_link']);

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
?>

<section class="card account-page-head">
    <div class="account-page-head-inner">
        <div>
            <span class="tag">Official Account Record</span>
            <h3>Profile Management</h3>
            <p class="text-muted small-text account-subtitle">
                Maintain your official login credentials and review recent account access.
            </p>
        </div>
        <div class="account-office-block">
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
        <section class="card account-panel">
            <div class="section-head">
                <div>
                    <span class="tag">Credentials</span>
                    <h3>Change Email</h3>
                </div>
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
                    <div>
                        <?php echo htmlspecialchars($success); ?>
                        <?php if (AUTH_DEV_MODE && $devEmailLink !== ''): ?>
                            <div class="small-text" style="margin-top:8px">
                                Dev link: <a href="<?php echo htmlspecialchars($devEmailLink); ?>"><?php echo htmlspecialchars($devEmailLink); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="form-grid" data-confirm="Send email verification link for this change?">
                <input type="hidden" name="action" value="update_email">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="new_email">New Email Address</label>
                    <input type="email" id="new_email" name="new_email" required placeholder="Enter new email address">
                </div>

                <div class="form-group">
                    <label for="confirm_email">Confirm New Email</label>
                    <input type="email" id="confirm_email" name="confirm_email" required placeholder="Re-enter new email address">
                </div>

                <div class="form-group">
                    <label for="email_current_password">Current Password</label>
                    <div class="password-field">
                        <input type="password" id="email_current_password" name="current_password" required placeholder="Enter current password to confirm" data-caps-indicator="caps_email_current_password">
                        <button type="button" class="password-toggle" data-password-toggle="email_current_password" aria-pressed="false">Show</button>
                    </div>
                    <div class="caps-indicator" id="caps_email_current_password" hidden>Caps Lock is on.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i> Update Email
                    </button>
                </div>
            </form>
        </section>

        <section class="card account-panel">
            <div class="section-head">
                <div>
                    <span class="tag">Credentials</span>
                    <h3>Change Password</h3>
                </div>
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

            <form method="post" class="form-grid" data-confirm="Update your password now?">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-field">
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password" data-caps-indicator="caps_current_password">
                        <button type="button" class="password-toggle" data-password-toggle="current_password" aria-pressed="false">Show</button>
                    </div>
                    <div class="caps-indicator" id="caps_current_password" hidden>Caps Lock is on.</div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new password" data-caps-indicator="caps_new_password" data-strength-meter="passwordStrength">
                        <button type="button" class="password-toggle" data-password-toggle="new_password" aria-pressed="false">Show</button>
                    </div>
                    <div class="caps-indicator" id="caps_new_password" hidden>Caps Lock is on.</div>
                    <div class="strength-meter" id="passwordStrength">
                        <div class="strength-track"><div class="strength-fill"></div></div>
                        <div class="strength-row">
                            <span>Password strength</span>
                            <span class="strength-label">Weak</span>
                        </div>
                    </div>
                    <small class="account-helper">Use at least 10 characters and include mixed case + numbers.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password" data-caps-indicator="caps_confirm_password">
                        <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-pressed="false">Show</button>
                    </div>
                    <div class="caps-indicator" id="caps_confirm_password" hidden>Caps Lock is on.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Update Password
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
