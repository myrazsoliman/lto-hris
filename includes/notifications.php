<?php
require_once __DIR__ . '/auth.php';

function ensure_notifications_table()
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(180) NOT NULL,
            body TEXT NULL,
            link VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME NULL,
            INDEX idx_user_read_created (user_id, is_read, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function create_notification($userId, $type, $title, $body = '', $link = '')
{
    ensure_notifications_table();
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, type, title, body, link)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $userId,
        (string) $type,
        substr((string) $title, 0, 180),
        (string) $body,
        $link !== '' ? substr((string) $link, 0, 255) : null,
    ]);
}

function create_notification_for_roles($roles, $type, $title, $body = '', $link = '')
{
    $roles = array_values(array_unique(array_filter((array) $roles, 'is_string')));
    if (empty($roles)) {
        return;
    }

    ensure_notifications_table();

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql = "SELECT DISTINCT u.id
            FROM users u
            INNER JOIN user_roles ur ON ur.user_id = u.id
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE r.name IN ($placeholders)";
    $stmt = db()->prepare($sql);
    $stmt->execute($roles);
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($userIds as $userId) {
        create_notification((int) $userId, $type, $title, $body, $link);
    }
}

function get_notifications_for_user($userId, $limit = 10)
{
    ensure_notifications_table();
    $limit = max(1, min(50, (int) $limit));
    $stmt = db()->prepare(
        "SELECT id, type, title, body, link, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT $limit"
    );
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll() ?: [];
}

function get_unread_notification_count($userId)
{
    ensure_notifications_table();
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM notifications
         WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([(int) $userId]);
    return (int) $stmt->fetchColumn();
}

function mark_notifications_read($userId, $ids = [])
{
    ensure_notifications_table();
    $userId = (int) $userId;
    $ids = array_values(array_filter(array_map('intval', (array) $ids)));

    if (empty($ids)) {
        $stmt = db()->prepare(
            'UPDATE notifications
             SET is_read = 1, read_at = NOW()
             WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$userId], $ids);
    $stmt = db()->prepare(
        "UPDATE notifications
         SET is_read = 1, read_at = NOW()
         WHERE user_id = ? AND id IN ($placeholders)"
    );
    $stmt->execute($params);
}

function notification_type_icon($type)
{
    $map = [
        'leave_request' => 'fa-regular fa-calendar-check',
        'document_upload' => 'fa-regular fa-folder-open',
        'account' => 'fa-regular fa-user',
        'security' => 'fa-solid fa-shield-halved',
        'system' => 'fa-regular fa-bell',
    ];

    return $map[$type] ?? 'fa-regular fa-bell';
}

