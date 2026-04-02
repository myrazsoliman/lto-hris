<?php
$pageTitle = 'Notifications';
$activePage = '';
require_once 'includes/auth.php';
require_login();
require_once 'includes/notifications.php';
require_once 'includes/header.php';

$user = current_user();
$items = get_notifications_for_user((int) ($user['id'] ?? 0), 50);
mark_notifications_read((int) ($user['id'] ?? 0));
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Updates</span>
            <h3>All Notifications</h3>
        </div>
    </div>

    <div class="notification-list notification-list--page">
        <?php if (empty($items)): ?>
            <div class="notification-empty">No notifications available.</div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <a class="notification-item<?php echo (int) $item['is_read'] === 0 ? ' is-unread' : ''; ?>" href="<?php echo htmlspecialchars($item['link'] ?: '#'); ?>">
                    <span class="notification-item-icon"><i class="<?php echo htmlspecialchars(notification_type_icon((string) $item['type'])); ?>" aria-hidden="true"></i></span>
                    <span class="notification-item-body">
                        <div class="notification-item-title"><?php echo htmlspecialchars((string) $item['title']); ?></div>
                        <?php if (!empty($item['body'])): ?>
                            <div class="notification-item-text"><?php echo htmlspecialchars((string) $item['body']); ?></div>
                        <?php endif; ?>
                        <div class="notification-item-time"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime((string) $item['created_at']))); ?></div>
                    </span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

