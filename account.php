<?php
$pageTitle = 'My Account';
$activePage = 'account.php';
require_once 'includes/auth.php';
require_login();

// Get current user info
$currentUser = current_user();
$error = '';
$success = '';

// Handle success messages from redirect
if (isset($_GET['success']) && $_GET['success'] === 'email') {
    $success = 'Email updated successfully!';
} elseif (isset($_GET['success']) && $_GET['success'] === 'password') {
    $success = 'Password updated successfully!';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } elseif (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_email') {
            $newEmail = $_POST['new_email'] ?? '';
            $confirmEmail = $_POST['confirm_email'] ?? '';
            $currentPassword = $_POST['current_password'] ?? '';
            
            if (empty($newEmail) || empty($confirmEmail) || empty($currentPassword)) {
                $error = 'All fields are required.';
            } elseif ($newEmail !== $confirmEmail) {
                $error = 'Email addresses do not match.';
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Verify current password
                $user = fetch_user_record($currentUser['email']);
                if ($user && password_verify($currentPassword, $user['password'])) {
                    // Check if email already exists
                    if (normalize_identifier($newEmail) !== normalize_identifier($currentUser['email']) && user_exists($newEmail)) {
                        $error = 'This email address is already in use.';
                    } else {
                        // Update email
                        $stmt = db()->prepare('UPDATE users SET email = ? WHERE id = ?');
                        $stmt->execute([normalize_identifier($newEmail), $currentUser['id']]);
                        
                        // Update session
                        $_SESSION['user']['email'] = $newEmail;
                        
                        // Redirect to prevent form resubmission
                        header('Location: account.php?success=email');
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
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = 'All fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                // Verify current password
                $user = fetch_user_record($currentUser['email']);
                if ($user && password_verify($currentPassword, $user['password'])) {
                    // Check password policy
                    $passwordErrors = password_policy_errors($newPassword, '', $currentUser['email'] ?? '');
                    if (!empty($passwordErrors)) {
                        $error = implode(' ', $passwordErrors);
                    } else {
                        // Update password
                        $stmt = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $currentUser['id']]);
                        
                        // Redirect to prevent form resubmission
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
?>

<section class="hero modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge" style="background: linear-gradient(135deg, #8b0000, #dc143c); padding: 16px; border-radius: 12px; color: white; display: flex; align-items: center; justify-content: center; width: 60px; height: 60px;">
                <i class="fas fa-user-shield" style="font-size: 32px;"></i>
            </div>
            <div>
                <h2 style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0 0 8px 0; line-height: 1.2;">My Account</h2>
                <p style="color: var(--muted); font-size: 15px; margin: 0;">Manage your superadmin account settings</p>
            </div>
        </div>

        <p style="color: var(--muted); line-height: 1.8; margin: 24px 0 28px 0; max-width: 650px; font-size: 15px;">
            Update your email address and password to maintain secure access to the LTO HRIS system.
        </p>
    </div>
</section>

<section class="activities-section">
    <div class="section-title">
        <h3><i class="fas fa-cogs"></i> Account Settings</h3>
        <p>Manage your superadmin account credentials</p>
    </div>

    <div class="activities-container">
        <div class="admin-grid">
            <!-- Account Information -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #8b0000, #dc143c);">
                    <i class="fas fa-info-circle"></i>
                    <h4>Account Information</h4>
                </div>
                <div class="admin-card-body">
                    <div class="account-info">
                        <div class="info-item">
                            <label>Account Type:</label>
                            <span class="status active">Super Administrator</span>
                        </div>
                        <div class="info-item">
                            <label>Display Name:</label>
                            <span><?php echo htmlspecialchars($currentUser['display_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email Address:</label>
                            <span><?php echo htmlspecialchars($currentUser['email']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Email -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-envelope"></i>
                    <h4>Update Email Address</h4>
                </div>
                <div class="admin-card-body">
                    <?php if ($error && strpos($_POST['action'] ?? '', 'email') !== false): ?>
                        <div class="alert" style="background: var(--danger-bg); color: var(--danger-text); padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--danger-text);">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success && strpos($success, 'Email') !== false): ?>
                        <div class="alert" style="background: var(--success-bg); color: var(--success-text); padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--success-text);">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="form-grid">
                        <input type="hidden" name="action" value="update_email">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="form-group">
                            <label for="new_email">New Email Address</label>
                            <input type="email" id="new_email" name="new_email" required 
                                   placeholder="Enter new email address">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_email">Confirm Email Address</label>
                            <input type="email" id="confirm_email" name="confirm_email" required 
                                   placeholder="Confirm new email address">
                        </div>
                        
                        <div class="form-group">
                            <label for="email_current_password">Current Password</label>
                            <input type="password" id="email_current_password" name="current_password" required 
                                   placeholder="Enter current password for verification">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Update Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Update Password -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-lock"></i>
                    <h4>Update Password</h4>
                </div>
                <div class="admin-card-body">
                    <?php if ($error && strpos($_POST['action'] ?? '', 'password') !== false): ?>
                        <div class="alert" style="background: var(--danger-bg); color: var(--danger-text); padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--danger-text);">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success && strpos($success, 'Password') !== false): ?>
                        <div class="alert" style="background: var(--success-bg); color: var(--success-text); padding: 12px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--success-text);">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="form-grid">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required 
                                   placeholder="Enter current password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required 
                                   placeholder="Enter new password (min 8 characters)">
                            <small style="color: var(--muted); font-size: 12px; margin-top: 4px; display: block;">
                                Password must include uppercase, lowercase, and numbers.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm new password">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="admin-card">
                <div class="admin-card-header" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security Tips</h4>
                </div>
                <div class="admin-card-body">
                    <div class="security-tips">
                        <div class="tip-item">
                            <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                            <span>Use a strong, unique password for your superadmin account</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                            <span>Change your password regularly (every 90 days)</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                            <span>Never share your credentials with anyone</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                            <span>Always logout when finished using the system</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle" style="color: var(--success-text);"></i>
                            <span>Keep your email account secure with 2FA</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Admin Card Styles */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 32px;
    max-width: 1400px;
    margin: 0 auto;
}

.admin-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.admin-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 28px;
    color: white;
}

.admin-card-header i {
    font-size: 24px;
}

.admin-card-header h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.admin-card-body {
    padding: 36px;
}

.account-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--surface-soft);
    border-radius: 8px;
    border: 1px solid var(--border);
}

.info-item label {
    font-weight: 600;
    color: var(--text);
    margin: 0;
    font-size: 14px;
}

.info-item span {
    font-size: 14px;
    color: var(--muted);
}

.security-tips {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tip-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--surface-soft);
    border-radius: 8px;
    border-left: 4px solid var(--success-text);
}

.tip-item span {
    font-size: 14px;
    color: var(--text);
    line-height: 1.4;
}

/* Activities Container */
.activities-container {
    display: flex;
    gap: 24px;
}

/* Section Title */
.section-title {
    margin-bottom: 24px;
}

.section-title h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title p {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .admin-grid {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 28px;
        max-width: 1200px;
    }
}

@media (max-width: 1024px) {
    .admin-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 24px;
    }
}

@media (max-width: 768px) {
    .admin-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .activities-container {
        flex-direction: column;
    }
    
    .admin-card-header {
        padding: 20px;
    }
    
    .admin-card-body {
        padding: 24px;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .info-item span {
        align-self: flex-end;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
