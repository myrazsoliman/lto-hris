<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/notifications.php';

header('Content-Type: application/json; charset=UTF-8');

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$roles = get_user_roles($user);

$q = trim((string) ($_GET['q'] ?? ''));
$qLower = strtolower($q);

if ($q === '') {
    echo json_encode(['ok' => true, 'q' => $q, 'pages' => [], 'notifications' => []]);
    exit;
}

// Pages (based on role navigation)
$navItems = get_nav_items($roles);
$pages = [];
foreach ($navItems as $file => $label) {
    $hay = strtolower($label . ' ' . $file);
    if (strpos($hay, $qLower) !== false) {
        $pages[] = ['href' => $file, 'label' => $label];
    }
}

// Notifications (user-specific)
$notifs = [];
if ($userId > 0) {
    $items = get_notifications_for_user($userId, 50);
    foreach ($items as $item) {
        $hay = strtolower(((string) ($item['title'] ?? '')) . ' ' . ((string) ($item['body'] ?? '')) . ' ' . ((string) ($item['type'] ?? '')));
        if (strpos($hay, $qLower) !== false) {
            $notifs[] = [
                'id' => (int) $item['id'],
                'type' => (string) $item['type'],
                'icon' => notification_type_icon((string) $item['type']),
                'title' => (string) $item['title'],
                'body' => (string) ($item['body'] ?? ''),
                'link' => (string) ($item['link'] ?? ''),
                'created_at' => (string) $item['created_at'],
            ];
        }
    }
}

echo json_encode([
    'ok' => true,
    'q' => $q,
    'pages' => array_slice($pages, 0, 6),
    'notifications' => array_slice($notifs, 0, 6),
]);

