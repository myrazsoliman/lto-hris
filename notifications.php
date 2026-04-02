<?php
require_once __DIR__ . '/includes/notifications.php';

require_login();

header('Content-Type: application/json; charset=UTF-8');

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $ids = [];
    if (is_array($payload) && isset($payload['ids']) && is_array($payload['ids'])) {
        $ids = $payload['ids'];
    }

    mark_notifications_read($userId, $ids);
    echo json_encode(['ok' => true]);
    exit;
}

$items = get_notifications_for_user($userId, 12);
$count = get_unread_notification_count($userId);

$responseItems = array_map(static function ($item) {
    return [
        'id' => (int) $item['id'],
        'type' => (string) $item['type'],
        'icon' => notification_type_icon((string) $item['type']),
        'title' => (string) $item['title'],
        'body' => (string) ($item['body'] ?? ''),
        'link' => (string) ($item['link'] ?? ''),
        'is_read' => (bool) $item['is_read'],
        'created_at' => (string) $item['created_at'],
    ];
}, $items);

echo json_encode([
    'ok' => true,
    'unread_count' => $count,
    'items' => $responseItems,
]);

