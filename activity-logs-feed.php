<?php
require_once __DIR__ . '/includes/auth.php';
require_roles(['superadmin', 'admin', 'hr_officer']);
ensure_auth_activity_table();

header('Content-Type: application/json; charset=utf-8');

$afterId = max(0, (int) ($_GET['after_id'] ?? 0));
$limit = (int) ($_GET['limit'] ?? 20);
$limit = $limit > 0 ? min($limit, 50) : 20;

$q = trim((string) ($_GET['q'] ?? ''));
$event = trim((string) ($_GET['event'] ?? ''));
$dateFrom = trim((string) ($_GET['from'] ?? ''));
$dateTo = trim((string) ($_GET['to'] ?? ''));

$params = [];
$where = [];

if ($event !== '') {
    $where[] = 'a.event = ?';
    $params[] = $event;
}

if ($q !== '') {
    $where[] = '('
        . 'a.identifier LIKE ? OR '
        . 'a.ip_address LIKE ? OR '
        . 'a.user_agent LIKE ? OR '
        . 'u.email LIKE ? OR '
        . "CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) LIKE ?"
        . ')';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($dateFrom !== '') {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
    if ($dt) {
        $where[] = 'a.created_at >= ?';
        $params[] = $dt->format('Y-m-d 00:00:00');
    }
}

if ($dateTo !== '') {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);
    if ($dt) {
        $where[] = 'a.created_at <= ?';
        $params[] = $dt->format('Y-m-d 23:59:59');
    }
}

$baseWhereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    // Total for current filters (ignore after_id so the UI can stay dynamic/realtime).
    $countSql =
        "SELECT COUNT(*)
         FROM auth_activity a
         LEFT JOIN users u ON u.id = a.user_id
         {$baseWhereSql}";
    $countStmt = db()->prepare($countSql);
    foreach ($params as $idx => $value) {
        $countStmt->bindValue($idx + 1, $value);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    // Row feed: apply after_id constraint on top of the base filters.
    $feedWhere = $where;
    $feedParams = $params;
    if ($afterId > 0) {
        $feedWhere[] = 'a.id > ?';
        $feedParams[] = $afterId;
    }
    $feedWhereSql = $feedWhere ? ('WHERE ' . implode(' AND ', $feedWhere)) : '';

    $sql =
        "SELECT
            a.id,
            a.user_id,
            a.identifier,
            a.event,
            a.ip_address,
            a.user_agent,
            a.created_at,
            u.first_name,
            u.last_name,
            u.email
         FROM auth_activity a
         LEFT JOIN users u ON u.id = a.user_id
         {$feedWhereSql}
         ORDER BY a.id DESC
         LIMIT :limit";

    $stmt = db()->prepare($sql);
    foreach ($feedParams as $idx => $value) {
        $stmt->bindValue($idx + 1, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reverse so the client can append/prepend in chronological order.
    $rows = array_reverse($rows);

    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'total' => $total,
        'now' => date('c'),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Failed to load feed.',
    ], JSON_UNESCAPED_SLASHES);
}
