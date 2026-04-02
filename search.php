<?php
$pageTitle = 'Search';
$activePage = '';

require_once 'includes/auth.php';
require_login();
require_once 'includes/data.php';
require_once 'includes/notifications.php';
require_once 'includes/header.php';

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$roles = get_user_roles($user);

$q = trim((string) ($_GET['q'] ?? ''));
$qLower = strtolower($q);

$navItems = get_nav_items($roles);
$pageResults = [];
foreach ($navItems as $file => $label) {
    $hay = strtolower($label . ' ' . $file);
    if ($q === '' || strpos($hay, $qLower) !== false) {
        $pageResults[] = ['href' => $file, 'label' => $label];
    }
}

$notificationResults = [];
if ($userId > 0) {
    $all = get_notifications_for_user($userId, 50);
    foreach ($all as $item) {
        $hay = strtolower(((string) ($item['title'] ?? '')) . ' ' . ((string) ($item['body'] ?? '')) . ' ' . ((string) ($item['type'] ?? '')));
        if ($q === '' || strpos($hay, $qLower) !== false) {
            $notificationResults[] = $item;
        }
    }
}
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Search</span>
            <h3><?php echo $q !== '' ? 'Results for “' . htmlspecialchars($q) . '”' : 'Search'; ?></h3>
            <p class="section-head-desc">Find pages and your recent notifications.</p>
        </div>
        <?php if ($q !== ''): ?>
            <div class="section-head-meta">
                <span class="section-count"><?php echo (int) count($pageResults); ?> pages</span>
                <span class="section-count"><?php echo (int) count($notificationResults); ?> notifications</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="section-divider" role="presentation"></div>

    <div class="search-layout">
        <div class="search-block">
            <h4 class="search-title">Pages</h4>
            <?php if (empty($pageResults)): ?>
                <div class="notification-empty">No matching pages.</div>
            <?php else: ?>
                <div class="search-links">
                    <?php foreach ($pageResults as $p): ?>
                        <a class="search-link" href="<?php echo htmlspecialchars($p['href']); ?>">
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($p['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="search-block">
            <h4 class="search-title">Notifications</h4>
            <div class="notification-list notification-list--page">
                <?php if (empty($notificationResults)): ?>
                    <div class="notification-empty">No matching notifications.</div>
                <?php else: ?>
                    <?php foreach ($notificationResults as $item): ?>
                        <a class="notification-item<?php echo (int) $item['is_read'] === 0 ? ' is-unread' : ''; ?>" href="<?php echo htmlspecialchars($item['link'] ?: 'notification-center.php'); ?>">
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

            <div style="margin-top: 12px;">
                <a class="btn btn-outline btn-small" href="notification-center.php">Open Notification Center</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

