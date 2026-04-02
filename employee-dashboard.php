<?php
$pageTitle = 'Employee Dashboard';
$activePage = 'employee-dashboard.php';

require_once 'includes/auth.php';
require_login();
require_roles(['employee']);

require_once 'includes/notifications.php';
require_once 'includes/header.php';

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$userName = $currentUser['display_name'] ?? 'Employee';

$unreadCount = $userId > 0 ? get_unread_notification_count($userId) : 0;
$recentNotifications = $userId > 0 ? get_notifications_for_user($userId, 6) : [];

function employee_activity_variant($type)
{
    $type = (string) $type;
    if ($type === 'security') {
        return 'urgent';
    }
    if ($type === 'leave_request') {
        return 'success';
    }
    if ($type === 'account') {
        return 'warning';
    }
    return 'info';
}
?>

<section class="modern-hero">
    <div class="hero-content">
        <div class="hero-header">
            <div class="header-badge header-badge--gov" aria-hidden="true">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <span class="eyebrow">Employee Portal</span>
                <h3>Welcome, <?php echo htmlspecialchars($userName); ?></h3>
                <p class="text-muted small-text">Self-service access to your HR records and requests.</p>
            </div>
        </div>

        <div class="quick-actions" aria-label="Quick actions">
            <a href="account.php" class="quick-action-card quick-action-primary">
                <div class="action-icon"><i class="fa-regular fa-user"></i></div>
                <div class="action-content">
                    <h4>My Account</h4>
                    <p>Profile & security settings</p>
                </div>
                <i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i>
            </a>
            <a href="pds.php" class="quick-action-card quick-action-blue">
                <div class="action-icon"><i class="fa-solid fa-id-card"></i></div>
                <div class="action-content">
                    <h4>My PDS</h4>
                    <p>Review and update information</p>
                </div>
                <i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i>
            </a>
            <a href="leave-request.php" class="quick-action-card quick-action-success">
                <div class="action-icon"><i class="fa-regular fa-calendar-check"></i></div>
                <div class="action-content">
                    <h4>Leave Request</h4>
                    <p>File and track status</p>
                </div>
                <i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i>
            </a>
            <a href="documents.php" class="quick-action-card quick-action-purple">
                <div class="action-icon"><i class="fa-regular fa-folder-open"></i></div>
                <div class="action-content">
                    <h4>My Documents</h4>
                    <p>Upload and manage files</p>
                </div>
                <i class="fa-solid fa-chevron-right action-arrow" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <div class="hero-panel modern-panel">
        <div class="stat-widget stat-widget--primary">
            <div class="stat-header stat-header--primary">
                <span class="stat-icon"><i class="fa-regular fa-bell"></i></span>
                <h4>Unread Notifications</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--primary"><?php echo (int) $unreadCount; ?></p>
                <p class="stat-label">Items requiring your attention</p>
            </div>
        </div>

        <div class="stat-widget stat-widget--info">
            <div class="stat-header stat-header--info">
                <span class="stat-icon"><i class="fa-regular fa-id-card"></i></span>
                <h4>Profile</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--primary">Active</p>
                <p class="stat-label">Account is in good standing</p>
            </div>
        </div>

        <div class="stat-widget stat-widget--success">
            <div class="stat-header stat-header--success">
                <span class="stat-icon"><i class="fa-regular fa-calendar-check"></i></span>
                <h4>Requests</h4>
            </div>
            <div class="stat-body">
                <p class="stat-number stat-number--success">Ready</p>
                <p class="stat-label">File leave and track approvals</p>
            </div>
        </div>
    </div>
</section>

<section class="activities-section">
    <div class="section-title">
        <h3><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Recent Updates</h3>
        <p>Latest notifications and reminders.</p>
    </div>

    <div class="activities-container">
        <div class="activity-timeline">
            <?php if (empty($recentNotifications)): ?>
                <div class="notification-empty">No notifications available.</div>
            <?php else: ?>
                <?php foreach ($recentNotifications as $n): ?>
                    <?php $variant = employee_activity_variant((string) $n['type']); ?>
                    <a class="activity-item activity-item--<?php echo htmlspecialchars($variant); ?>" href="<?php echo htmlspecialchars($n['link'] ?: 'notification-center.php'); ?>">
                        <span class="activity-icon activity-icon--<?php echo htmlspecialchars($variant); ?>">
                            <i class="<?php echo htmlspecialchars(notification_type_icon((string) $n['type'])); ?>" aria-hidden="true"></i>
                        </span>
                        <span class="activity-content">
                            <h4><?php echo htmlspecialchars((string) $n['title']); ?></h4>
                            <?php if (!empty($n['body'])): ?>
                                <p><?php echo htmlspecialchars((string) $n['body']); ?></p>
                            <?php else: ?>
                                <p class="text-muted">Open to view details.</p>
                            <?php endif; ?>
                            <span class="activity-meta">
                                <span class="activity-badge <?php echo htmlspecialchars($variant); ?>"><?php echo strtoupper(str_replace('_', ' ', (string) $n['type'])); ?></span>
                                <span class="activity-time"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime((string) $n['created_at']))); ?></span>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="card card-compact">
                <div class="section-head" style="margin-bottom: 10px;">
                    <div>
                        <span class="tag">Notifications</span>
                        <h3 style="font-size: 18px;">View all updates</h3>
                    </div>
                </div>
                <a class="btn btn-primary" href="notification-center.php">Open Notification Center</a>
            </div>
        </div>

        <div class="activities-sidebar">
            <div class="sidebar-card">
                <h4><i class="fa-solid fa-list-check" aria-hidden="true"></i> Quick Reminders</h4>
                <p class="text-muted small-text">Keep your records updated and review pending items.</p>
                <div class="tools-mini">
                    <a class="tools-mini-link" href="account.php"><i class="fa-regular fa-user" aria-hidden="true"></i> Update profile details</a>
                    <a class="tools-mini-link" href="documents.php"><i class="fa-regular fa-folder-open" aria-hidden="true"></i> Upload supporting files</a>
                    <a class="tools-mini-link" href="leave-request.php"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> File leave request</a>
                </div>
            </div>

            <div class="sidebar-card">
                <h4><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Announcements</h4>
                <div class="announcement-item">
                    <span class="announcement-date"><?php echo htmlspecialchars(date('M d, Y', strtotime('-2 days'))); ?></span>
                    <span class="announcement-title">Office memo updates</span>
                    <span class="announcement-desc">Check announcements regularly for schedules, advisories, and reminders.</span>
                </div>
                <div class="announcement-item">
                    <span class="announcement-date"><?php echo htmlspecialchars(date('M d, Y', strtotime('-8 days'))); ?></span>
                    <span class="announcement-title">System maintenance window</span>
                    <span class="announcement-desc">Scheduled updates may affect access outside office hours.</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Tools</span>
            <h3>Self-Service Tools</h3>
            <p class="section-head-desc">Common actions for employees.</p>
        </div>
    </div>
    <div class="section-divider" role="presentation"></div>

    <div class="tools-grid">
        <a href="csc-forms.php" class="tool-card">
            <span class="tool-icon tool-icon--primary"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
            <h4>Forms</h4>
            <p>Access CSC forms and references.</p>
            <span class="tool-cta">Open</span>
        </a>
        <a href="saln.php" class="tool-card">
            <span class="tool-icon tool-icon--warning"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></span>
            <h4>SALN</h4>
            <p>View guidance and submit annually.</p>
            <span class="tool-cta">Open</span>
        </a>
        <a href="documents.php" class="tool-card">
            <span class="tool-icon tool-icon--info"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></span>
            <h4>Documents</h4>
            <p>Upload and manage your files.</p>
            <span class="tool-cta">Open</span>
        </a>
        <a href="help.php" class="tool-card">
            <span class="tool-icon tool-icon--success"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
            <h4>Help</h4>
            <p>Get support and FAQs.</p>
            <span class="tool-cta">Open</span>
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

