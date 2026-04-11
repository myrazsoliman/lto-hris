<?php
$pageTitle = 'Activity Logs';
$activePage = 'activity-logs.php';
require_once 'includes/auth.php';
require_roles(['superadmin', 'admin', 'hr_officer']);
ensure_auth_activity_table();
require_once 'includes/header.php';

$q = trim((string) ($_GET['q'] ?? ''));
$event = trim((string) ($_GET['event'] ?? ''));
$dateFrom = trim((string) ($_GET['from'] ?? ''));
$dateTo = trim((string) ($_GET['to'] ?? ''));
$pageRequest = max(1, (int) ($_GET['page'] ?? 1));
$requestedPerPage = (int) ($_GET['per_page'] ?? 10);
$perPage = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

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

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$error = '';
$rows = [];
$total = 0;
$totalPages = 1;
$page = $pageRequest;
$offset = 0;
$events = [];
$wasPerPageCapped = false;
$summary = [
    'last_7d' => 0,
    'login_success' => 0,
    'login_failed' => 0,
    '2fa_sent' => 0,
];

try {
    $events = db()->query('SELECT DISTINCT event FROM auth_activity ORDER BY event')->fetchAll(PDO::FETCH_COLUMN);

    $summary['last_7d'] = (int) db()->query("SELECT COUNT(*) FROM auth_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stmt = db()->prepare("SELECT COUNT(*) FROM auth_activity WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND event = ?");
    foreach (['login_success', 'login_failed', '2fa_sent'] as $ev) {
        $stmt->execute([$ev]);
        $summary[$ev] = (int) $stmt->fetchColumn();
    }

    $countStmt = db()->prepare(
        "SELECT COUNT(*)
         FROM auth_activity a
         LEFT JOIN users u ON u.id = a.user_id
         {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // If the selected per-page is larger than what exists, cap to the nearest valid size.
    // This prevents "Show 50" from staying selected when there are only 1-10 rows.
    if ($total > 0 && $total < $perPage) {
        $wasPerPageCapped = true;
        $perPage = $total >= 25 ? 25 : 10;
    }

    $totalPages = max(1, (int) ceil($total / $perPage));
    $totalPages = min($totalPages, 200);
    $page = min($pageRequest, $totalPages);
    $offset = ($page - 1) * $perPage;

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
         {$whereSql}
         ORDER BY a.created_at DESC
         LIMIT :limit OFFSET :offset";
    $stmt = db()->prepare($sql);
    foreach ($params as $idx => $value) {
        $stmt->bindValue($idx + 1, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Unable to load activity logs. Check database connection and schema.';
}

function activity_event_label($event)
{
    $event = (string) $event;
    return ucwords(str_replace('_', ' ', $event));
}

function activity_badge_variant($event)
{
    $event = (string) $event;
    if (str_contains($event, 'success')) {
        return 'success';
    }
    if (str_contains($event, 'failed') || str_contains($event, 'error')) {
        return 'danger';
    }
    if (str_contains($event, '2fa')) {
        return 'info';
    }
    if (str_contains($event, 'logout')) {
        return 'muted';
    }
    return 'default';
}

function activity_ip_label($ip)
{
    $ip = trim((string) $ip);
    if ($ip === '::1' || $ip === '127.0.0.1') {
        return 'Localhost';
    }
    return $ip !== '' ? $ip : '-';
}
?>

<div class="activity-logs-page">
    <section class="hero modern-hero activity-logs-hero">
        <div class="hero-content">
            <div class="hero-header">
                <div class="header-badge" aria-hidden="true">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div>
                    <h2>System Activity Logs</h2>
                    <p class="text-muted small-text">Audit sign-ins, security actions, and system access.</p>
                </div>
            </div>

            <div class="log-kpis" aria-label="Activity summary">
                <div class="kpi">
                    <div class="kpi-label">Last 7 days</div>
                    <div class="kpi-value"><?php echo (int) $summary['last_7d']; ?></div>
                    <div class="kpi-sub">Total events</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">Logins</div>
                    <div class="kpi-value"><?php echo (int) $summary['login_success']; ?></div>
                    <div class="kpi-sub">Successful</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">Logins</div>
                    <div class="kpi-value"><?php echo (int) $summary['login_failed']; ?></div>
                    <div class="kpi-sub">Failed</div>
                </div>
                <div class="kpi">
                    <div class="kpi-label">2FA</div>
                    <div class="kpi-value"><?php echo (int) $summary['2fa_sent']; ?></div>
                    <div class="kpi-sub">Codes sent</div>
                </div>
            </div>
        </div>
    </section>

    <section class="card activity-logs-card">
        <div class="section-head activity-logs-head">
            <div>
                <span class="tag">Audit</span>
                <h3>Browse Logs</h3>
                <p class="section-head-desc">Filter by user, event type, or date range.</p>
            </div>
        </div>
        <div class="section-divider" role="presentation"></div>

        <form class="dt-controls" method="get" action="activity-logs.php" autocomplete="off" id="activityLogsControls">
            <div class="dt-toolbar" role="group" aria-label="Table controls">
                <div class="dt-length">
                    <label for="per_page">Show</label>
                    <select id="per_page" name="per_page" aria-label="Rows per page">
                        <?php foreach ([10, 25, 50] as $n): ?>
                            <?php
                                $disabled = $total > 0 && $n !== 10 && $total < $n;
                            ?>
                            <option value="<?php echo (int) $n; ?>"<?php echo $perPage === (int) $n ? ' selected' : ''; ?><?php echo $disabled ? ' disabled' : ''; ?>>
                                <?php echo (int) $n; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span>entries</span>
                    <span class="dt-length-note<?php echo ($wasPerPageCapped && $total > 0 && $requestedPerPage > $total) ? ' is-visible' : ''; ?>" id="dtLengthNote" role="status" aria-live="polite">
                        <?php if ($wasPerPageCapped && $total > 0 && $requestedPerPage > $total): ?>
                            Only <?php echo (int) $total; ?> entries available right now.
                        <?php endif; ?>
                    </span>
                </div>

                <div class="dt-search">
                    <label for="q">Search:</label>
                    <input id="q" name="q" type="search" value="<?php echo htmlspecialchars($q); ?>" placeholder="" inputmode="search">
                </div>
            </div>

            <details class="dt-advanced">
                <summary>Advanced filters</summary>
                <div class="dt-advanced-grid">
                    <div class="dt-field">
                        <label for="event">Event</label>
                        <select id="event" name="event">
                            <option value="">All events</option>
                            <?php foreach ($events as $ev): ?>
                                <option value="<?php echo htmlspecialchars((string) $ev); ?>"<?php echo $event === (string) $ev ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars(activity_event_label((string) $ev)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="dt-field">
                        <label for="from">From</label>
                        <input id="from" name="from" type="date" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="dt-field">
                        <label for="to">To</label>
                        <input id="to" name="to" type="date" value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                </div>
                <div class="dt-advanced-actions">
                    <a class="btn btn-outline btn-small" href="activity-logs.php">Reset</a>
                </div>
            </details>
        </form>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" style="margin-top: 14px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="log-table-wrap" id="logTableWrap" data-last-id="<?php echo !empty($rows) ? (int) ($rows[0]['id'] ?? 0) : 0; ?>" data-total="<?php echo (int) $total; ?>">
            <table class="dt-table log-table">
                <thead>
                    <tr>
                        <th style="width: 170px;">Time</th>
                        <th>User</th>
                        <th style="width: 200px;">Event</th>
                        <th style="width: 140px;">IP</th>
                        <th>Device</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="table-empty">No activity logs found for the current filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $fullName = trim((string) (($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
                                $email = (string) ($r['email'] ?? '');
                                $identifier = (string) ($r['identifier'] ?? '');
                                $displayUser = $fullName !== '' ? $fullName : ($email !== '' ? $email : ($identifier !== '' ? $identifier : 'Unknown'));
                                $badge = activity_badge_variant((string) ($r['event'] ?? ''));
                                $time = (string) ($r['created_at'] ?? '');
                                $timeLabel = $time !== '' ? date('M j, Y g:i A', strtotime($time)) : '-';
                            ?>
                            <tr>
                                <td class="log-time">
                                    <div class="time-main"><?php echo htmlspecialchars($timeLabel); ?></div>
                                    <div class="time-sub"><?php echo htmlspecialchars((string) ($r['id'] ?? '')); ?></div>
                                </td>
                                <td class="log-user">
                                    <div class="user-main"><?php echo htmlspecialchars($displayUser); ?></div>
                                    <?php if ($email !== '' && $fullName !== ''): ?>
                                        <div class="user-sub"><?php echo htmlspecialchars($email); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="event-badge event-badge--<?php echo htmlspecialchars($badge); ?>">
                                        <?php echo htmlspecialchars(activity_event_label((string) ($r['event'] ?? ''))); ?>
                                    </span>
                                </td>
                                <td class="log-ip">
                                    <span class="ip-main"><?php echo htmlspecialchars(activity_ip_label((string) ($r['ip_address'] ?? ''))); ?></span>
                                    <?php $rawIp = trim((string) ($r['ip_address'] ?? '')); ?>
                                    <?php if ($rawIp === '::1' || $rawIp === '127.0.0.1'): ?>
                                        <span class="ip-sub"><?php echo htmlspecialchars($rawIp); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="log-device" title="<?php echo htmlspecialchars((string) ($r['user_agent'] ?? '')); ?>">
                                    <?php
                                        $ua = trim((string) ($r['user_agent'] ?? ''));
                                        echo htmlspecialchars($ua !== '' ? $ua : '-');
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="log-pagination" role="navigation" aria-label="Pagination">
            <div class="pagination-meta" id="logPaginationMeta">
                <?php if ($total <= 0): ?>
                    Showing 0 to 0 of 0 entries
                <?php else: ?>
                    Showing <?php echo (int) min($total, $offset + 1); ?> to <?php echo (int) min($total, $offset + $perPage); ?> of <?php echo (int) $total; ?> entries
                <?php endif; ?>
            </div>
            <div class="pds-template-pagination">
                <?php
                    $base = $_GET;
                    unset($base['page']);
                    $base['per_page'] = $perPage;
                    $baseQuery = http_build_query($base);
                    $baseHref = 'activity-logs.php' . ($baseQuery !== '' ? ('?' . $baseQuery . '&') : '?');

                    $prevPage = max(1, $page - 1);
                    $nextPage = min($totalPages, $page + 1);

                    // Show only 2 page numbers: 1-2, then 2-3, etc.
                    $windowStart = $totalPages <= 1 ? 1 : min($page, $totalPages - 1);
                    $windowEnd = min($totalPages, $windowStart + 1);
                ?>

                <a class="pds-page-link<?php echo $page <= 1 ? ' is-disabled' : ''; ?>" data-preserve-scroll="1" href="<?php echo $page <= 1 ? '#' : htmlspecialchars($baseHref . 'page=' . $prevPage); ?>">Previous</a>

                <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="pds-page-link is-active" aria-current="page"><?php echo (int) $i; ?></span>
                    <?php else: ?>
                        <a class="pds-page-link" data-preserve-scroll="1" href="<?php echo htmlspecialchars($baseHref . 'page=' . $i); ?>"><?php echo (int) $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <a class="pds-page-link<?php echo $page >= $totalPages ? ' is-disabled' : ''; ?>" data-preserve-scroll="1" href="<?php echo $page >= $totalPages ? '#' : htmlspecialchars($baseHref . 'page=' . $nextPage); ?>">Next</a>
            </div>
        </div>
    </section>
</div>

<style>
.activity-logs-page .activity-logs-hero {
    margin-bottom: 18px;
}

.activity-logs-page .hero-header {
    display: flex;
    align-items: center;
    gap: 14px;
}

.activity-logs-page .header-badge {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, #0f4c81, #2a6eab);
    box-shadow: 0 10px 22px rgba(15, 76, 129, 0.24);
}

.activity-logs-page .header-badge i {
    font-size: 22px;
}

.activity-logs-page .hero-content h2 {
    margin: 0 0 6px;
    letter-spacing: -0.02em;
}

.activity-logs-page .log-kpis {
    margin-top: 18px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.activity-logs-page .kpi {
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(15, 76, 129, 0.12);
    border-radius: 14px;
    padding: 12px 12px 10px;
    box-shadow: 0 8px 18px rgba(20, 42, 68, 0.08);
}

.activity-logs-page .kpi-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #5d7088;
}

.activity-logs-page .kpi-value {
    font-size: 22px;
    font-weight: 800;
    color: #0f3156;
    margin-top: 4px;
}

.activity-logs-page .kpi-sub {
    margin-top: 2px;
    font-size: 12px;
    color: #6f86a3;
    font-weight: 600;
}

.activity-logs-page .log-filters {
    padding: 14px 16px 6px;
    border-radius: 14px;
    border: 1px solid #d6e2f0;
    background: linear-gradient(180deg, #ffffff, #f8fbff);
}

.activity-logs-page .filter-grid {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1fr 1fr 0.8fr;
    gap: 12px;
}

.activity-logs-page .filter-field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #607892;
    margin-bottom: 6px;
}

.activity-logs-page .filter-field input,
.activity-logs-page .filter-field select {
    width: 100%;
    height: 40px;
    border-radius: 10px;
    border: 1px solid #c9d9ea;
    padding: 0 12px;
    background: #fff;
}

.activity-logs-page .filter-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.activity-logs-page .dt-controls {
    margin-top: 0;
}

.activity-logs-page .dt-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px 10px 0 0;
    border: 1px solid #dee2e6;
    border-bottom: none;
    background: #fff;
}

.activity-logs-page .dt-length,
.activity-logs-page .dt-search {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4f6178;
    font-size: 13px;
    font-weight: 600;
}

.activity-logs-page .dt-length-note {
    display: none;
    margin-left: 8px;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid #ffe6b5;
    background: #fff6de;
    color: #7b5a16;
    font-weight: 700;
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
}

.activity-logs-page .dt-length-note.is-visible {
    display: inline-flex;
    align-items: center;
}

.activity-logs-page .dt-length select {
    height: 32px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0 8px;
    background: #fff;
}

.activity-logs-page .dt-search input {
    height: 32px;
    width: 220px;
    max-width: 100%;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0 10px;
    background: #fff;
}

.activity-logs-page .dt-advanced {
    border: 1px solid #dee2e6;
    border-top: none;
    border-bottom: none;
    background: #fff;
    padding: 10px 14px 12px;
}

.activity-logs-page .dt-advanced > summary {
    cursor: pointer;
    user-select: none;
    color: #0f4c81;
    font-weight: 800;
    font-size: 13px;
    list-style: none;
}

.activity-logs-page .dt-advanced > summary::-webkit-details-marker {
    display: none;
}

.activity-logs-page .dt-advanced > summary::after {
    content: "▾";
    display: inline-block;
    margin-left: 8px;
    color: #6f86a3;
    font-weight: 900;
    transform: translateY(-1px);
}

.activity-logs-page .dt-advanced[open] > summary::after {
    content: "▴";
}

.activity-logs-page .dt-advanced-grid {
    margin-top: 12px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.activity-logs-page .dt-field label {
    display: block;
    font-size: 11px;
    color: #4f6178;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-weight: 800;
    margin-bottom: 6px;
}

.activity-logs-page .dt-field input,
.activity-logs-page .dt-field select {
    width: 100%;
    height: 34px;
    border-radius: 4px;
    border: 1px solid #ced4da;
    padding: 0 10px;
    background: #fff;
}

.activity-logs-page .dt-advanced-actions {
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;
}

.activity-logs-page .log-table-wrap {
    margin-top: 0;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    background: #fff;
}

.activity-logs-page .dt-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.activity-logs-page .dt-table thead th {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-right: 1px solid #e9ecef;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 800;
    color: #2c3e50;
}

.activity-logs-page .dt-table thead th:last-child {
    border-right: none;
}

.activity-logs-page .dt-table tbody td {
    border-top: 1px solid #e9ecef;
    padding: 14px;
    vertical-align: middle;
    background: #fff;
}

.activity-logs-page .dt-table tbody tr:nth-child(even) td {
    background: #f9f9f9;
}

.activity-logs-page .dt-table tbody tr:hover td {
    background: #f1f6ff;
}

.activity-logs-page .table-empty {
    padding: 18px;
    color: #6f86a3;
    text-align: center;
}

.activity-logs-page .log-time .time-main {
    font-weight: 700;
    color: #1e334b;
}

.activity-logs-page .log-time .time-sub {
    font-size: 12px;
    color: #6f86a3;
    font-weight: 700;
}

.activity-logs-page .log-user .user-main {
    font-weight: 700;
    color: #1e334b;
}

.activity-logs-page .log-user .user-sub {
    font-size: 12px;
    color: #6f86a3;
    font-weight: 600;
}

.activity-logs-page .log-device {
    max-width: 520px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #384e66;
}

.activity-logs-page .log-ip .ip-main {
    font-weight: 700;
    color: #1e334b;
}

.activity-logs-page .log-ip .ip-sub {
    display: block;
    font-size: 12px;
    color: #6f86a3;
    font-weight: 700;
    margin-top: 2px;
}

.activity-logs-page .event-badge {
    display: inline-flex;
    align-items: center;
    height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.02em;
    border: 1px solid transparent;
}

.activity-logs-page .event-badge--success { background: #def5ed; border-color: #bfe8dd; color: #167b5c; }
.activity-logs-page .event-badge--danger { background: #fde8e8; border-color: #f4caca; color: #a43434; }
.activity-logs-page .event-badge--info { background: #e3f2fd; border-color: #c9e1f7; color: #1f6fb7; }
.activity-logs-page .event-badge--muted { background: #eef2f6; border-color: #d8e1ea; color: #556b81; }
.activity-logs-page .event-badge--default { background: #fff4e4; border-color: #f0ddbf; color: #8e5308; }

.activity-logs-page .log-pagination {
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 0;
    border: 0;
    background: transparent;
    border-radius: 0;
}

.activity-logs-page .pagination-meta {
    color: #6f86a3;
    font-weight: 600;
}

.activity-logs-page .pds-template-pagination {
    display: flex;
    align-items: center;
    gap: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.activity-logs-page .pds-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 0;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #0f4c81;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    margin-left: -1px;
}

.activity-logs-page .pds-page-link:hover {
    background: #e9ecef;
    border-color: #dee2e6;
    z-index: 1;
}

.activity-logs-page .pds-page-link.is-active {
    background: #0f4c81;
    border-color: #0f4c81;
    color: #fff;
    z-index: 2;
}

.activity-logs-page .pds-page-link.is-disabled {
    pointer-events: none;
    opacity: 0.45;
}

.activity-logs-page .pds-page-ellipsis {
    color: #6a7d94;
    font-weight: 800;
    padding-inline: 2px;
}

.activity-logs-page .pds-page-link:first-child {
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
    margin-left: 0;
}

.activity-logs-page .pds-page-link:last-child {
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}

@media (max-width: 1100px) {
    .activity-logs-page .dt-advanced-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 900px) {
    .activity-logs-page .log-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .activity-logs-page .log-pagination {
        flex-direction: column;
        align-items: flex-start;
    }

    .activity-logs-page .log-device {
        max-width: 260px;
    }
}
</style>

<script>
(function() {
    const wrap = document.getElementById('logTableWrap');
    const body = document.getElementById('logTableBody');
    if (!wrap || !body) return;

    const url = new URL(window.location.href);
    const pollMs = 5000;
    let lastId = parseInt(wrap.dataset.lastId || '0', 10) || 0;
    let inFlight = false;
    const perSel = document.getElementById('per_page');
    const meta = document.getElementById('logPaginationMeta');
    const note = document.getElementById('dtLengthNote');

    function setMeta(total) {
        if (!meta) return;
        const t = Number.isFinite(total) ? total : 0;
        if (t <= 0) {
            meta.textContent = 'Showing 0 to 0 of 0 entries';
            return;
        }
        const per = (() => {
            const n = parseInt((perSel && perSel.value) ? perSel.value : '10', 10);
            return Number.isFinite(n) ? n : 10;
        })();
        const end = Math.min(t, per);
        meta.textContent = `Showing 1 to ${end} of ${t} entries`;
    }

    function updatePerOptions(total) {
        if (!perSel) return;
        const t = Number.isFinite(total) ? total : 0;
        Array.from(perSel.options || []).forEach(opt => {
            const v = parseInt(opt.value || '0', 10) || 0;
            if (v === 10) {
                opt.disabled = false;
                return;
            }
            opt.disabled = t > 0 ? (t < v) : true;
        });
        // Clear the note when there are enough rows again (we only show it on user action).
        if (note && t >= 25) {
            note.classList.remove('is-visible');
        }
    }

    function eventLabel(ev) {
        return (ev || '').toString().replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function badgeVariant(ev) {
        ev = (ev || '').toString();
        if (ev.includes('success')) return 'success';
        if (ev.includes('failed') || ev.includes('error')) return 'danger';
        if (ev.includes('2fa')) return 'info';
        if (ev.includes('logout')) return 'muted';
        return 'default';
    }

    function ipLabel(ip) {
        ip = (ip || '').toString().trim();
        if (ip === '::1' || ip === '127.0.0.1') return {main: 'Localhost', sub: ip};
        return {main: ip || '-', sub: ''};
    }

    function fmtTime(ts) {
        if (!ts) return '-';
        const d = new Date(ts.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return ts;
        return d.toLocaleString(undefined, {year:'numeric', month:'short', day:'numeric', hour:'numeric', minute:'2-digit'});
    }

    function buildRow(r) {
        const tr = document.createElement('tr');
        const name = ((r.first_name || '') + ' ' + (r.last_name || '')).trim();
        const email = (r.email || '').toString();
        const identifier = (r.identifier || '').toString();
        const displayUser = name || email || identifier || 'Unknown';
        const badge = badgeVariant(r.event);
        const ip = ipLabel(r.ip_address);
        const timeLabel = fmtTime(r.created_at);
        const ua = (r.user_agent || '').toString().trim();

        tr.innerHTML = `
            <td class="log-time">
                <div class="time-main"></div>
                <div class="time-sub"></div>
            </td>
            <td class="log-user">
                <div class="user-main"></div>
                ${email && name ? `<div class="user-sub"></div>` : ''}
            </td>
            <td><span class="event-badge event-badge--${badge}"></span></td>
            <td class="log-ip">
                <span class="ip-main"></span>
                ${ip.sub ? `<span class="ip-sub"></span>` : ''}
            </td>
            <td class="log-device"></td>
        `;

        tr.querySelector('.time-main').textContent = timeLabel;
        tr.querySelector('.time-sub').textContent = String(r.id || '');
        tr.querySelector('.user-main').textContent = displayUser;
        if (email && name) {
            tr.querySelector('.user-sub').textContent = email;
        }
        tr.querySelector('.event-badge').textContent = eventLabel(r.event);
        tr.querySelector('.ip-main').textContent = ip.main;
        if (ip.sub) tr.querySelector('.ip-sub').textContent = ip.sub;
        const dev = tr.querySelector('.log-device');
        dev.textContent = ua || '-';
        dev.title = ua || '';

        return tr;
    }

    function currentPerPage() {
        const sel = document.getElementById('per_page');
        const n = parseInt((sel && sel.value) ? sel.value : '10', 10);
        return Number.isFinite(n) ? n : 10;
    }

    function trimRows() {
        const maxRows = currentPerPage();
        const rows = Array.from(body.querySelectorAll('tr'));
        // Keep the empty state row.
        if (rows.length === 1 && rows[0].querySelector('.table-empty')) return;
        while (body.children.length > maxRows) {
            body.removeChild(body.lastElementChild);
        }
    }

    async function poll() {
        if (inFlight) return;
        inFlight = true;
        try {
            const feed = new URL('activity-logs-feed.php', window.location.href);
            ['q','event','from','to'].forEach(k => {
                if (url.searchParams.has(k)) feed.searchParams.set(k, url.searchParams.get(k));
            });
            feed.searchParams.set('after_id', String(lastId));
            feed.searchParams.set('limit', '20');

            const res = await fetch(feed.toString(), {credentials: 'same-origin', cache: 'no-store'});
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.ok || !Array.isArray(data.rows)) return;

            if (typeof data.total === 'number' && Number.isFinite(data.total)) {
                const t = Math.max(0, Math.floor(data.total));
                wrap.dataset.total = String(t);
                updatePerOptions(t);
                setMeta(t);
            }

            if (data.rows.length > 0) {
                // Remove empty state row if present.
                const empty = body.querySelector('.table-empty');
                if (empty) empty.closest('tr').remove();

                // Prepend newest at top, keep table sorted by id desc.
                for (let i = data.rows.length - 1; i >= 0; i--) {
                    const r = data.rows[i];
                    if (!r || !r.id) continue;
                    const row = buildRow(r);
                    body.insertBefore(row, body.firstChild);
                    lastId = Math.max(lastId, parseInt(r.id, 10) || 0);
                }
                wrap.dataset.lastId = String(lastId);
                trimRows();
            }
        } catch (e) {
            // silent
        } finally {
            inFlight = false;
        }
    }

    // Only run live polling on the first page so pagination stays deterministic.
    if ((url.searchParams.get('page') || '1') === '1') {
        setInterval(poll, pollMs);
    }
})();

(function() {
    const KEY = 'activity_logs_scroll_y';

    // Save scroll position only for pagination navigation.
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a[data-preserve-scroll="1"]');
        if (!a) return;
        const href = a.getAttribute('href') || '';
        if (href === '' || href === '#') return;
        try {
            sessionStorage.setItem(KEY, String(window.scrollY || 0));
        } catch (err) {
            // ignore
        }
    });

    function restore() {
        let y = null;
        try {
            y = sessionStorage.getItem(KEY);
            sessionStorage.removeItem(KEY);
        } catch (err) {
            y = null;
        }
        if (y === null) return;
        const n = parseInt(y, 10);
        if (!Number.isFinite(n)) return;

        // Override browser default top-of-page behavior after navigation.
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        requestAnimationFrame(() => window.scrollTo(0, n));
        setTimeout(() => window.scrollTo(0, n), 0);
        setTimeout(() => window.scrollTo(0, n), 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restore, { once: true });
    } else {
        restore();
    }
}());

(function () {
    const form = document.getElementById('activityLogsControls');
    if (!form) return;

    let t = null;
    function submitSoon(delayMs) {
        if (t) clearTimeout(t);
        t = setTimeout(() => form.submit(), delayMs);
    }

    const q = document.getElementById('q');
    const per = document.getElementById('per_page');
    const ev = document.getElementById('event');
    const from = document.getElementById('from');
    const to = document.getElementById('to');

    const total = (() => {
        const wrap = document.getElementById('logTableWrap');
        const n = wrap ? parseInt(wrap.dataset.total || '0', 10) : 0;
        return Number.isFinite(n) ? n : 0;
    })();

    const note = document.getElementById('dtLengthNote');
    function setNote(msg) {
        if (!note) return;
        const text = (msg || '').toString().trim();
        note.textContent = text;
        note.classList.toggle('is-visible', text !== '');
    }

    function bestAllowedPerPage(totalCount) {
        const t = Number.isFinite(totalCount) ? totalCount : 0;
        if (t >= 25) return 25;
        return 10;
    }

    function updatePerPageOptions(totalCount, showNoteOnCap) {
        if (!per) return false;
        const t = Number.isFinite(totalCount) ? totalCount : 0;
        // Disable invalid options dynamically (and keep the UI in sync with realtime changes).
        Array.from(per.options || []).forEach(opt => {
            const v = parseInt(opt.value || '0', 10) || 0;
            if (v === 10) {
                opt.disabled = false;
                return;
            }
            opt.disabled = t > 0 ? (t < v) : true;
        });

        const cur = parseInt(per.value || '10', 10) || 10;
        const curOpt = per.options ? per.options[per.selectedIndex] : null;
        const invalid = (t > 0 && cur > t) || (curOpt && curOpt.disabled);
        if (!invalid) return false;

        const next = bestAllowedPerPage(t);
        per.value = String(next);
        if (showNoteOnCap && t > 0) {
            setNote(`Only ${t} entries available right now.`);
        }
        return true;
    }

    if (q) q.addEventListener('input', () => submitSoon(450));
    if (q) q.addEventListener('input', () => setNote(''));

    if (per) {
        // Initial sync in case the server-side HTML was cached or the dataset changes.
        updatePerPageOptions(total, false);

        per.addEventListener('change', () => {
            const wrap = document.getElementById('logTableWrap');
            const liveTotal = wrap ? (parseInt(wrap.dataset.total || '0', 10) || 0) : total;
            const didCap = updatePerPageOptions(liveTotal, true);
            if (!didCap) setNote('');
            form.submit();
        });
    }
    if (ev) ev.addEventListener('change', () => form.submit());
    if (from) from.addEventListener('change', () => form.submit());
    if (to) to.addEventListener('change', () => form.submit());
}());
</script>

<?php require_once 'includes/footer.php'; ?>
