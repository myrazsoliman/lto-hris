<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (!defined('AUTH_BYPASS_LOGIN')) {
    define('AUTH_BYPASS_LOGIN', false);
}

if (!defined('AUTH_DEV_MODE')) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $addr = $_SERVER['SERVER_ADDR'] ?? '';
    $dev = $host === 'localhost' || str_starts_with($host, '127.0.0.1')
        || $addr === '127.0.0.1' || $addr === '::1';
    define('AUTH_DEV_MODE', $dev);
}

if (!defined('AUTH_2FA_ENABLED')) {
    define('AUTH_2FA_ENABLED', true);
}

if (!defined('TRUSTED_DEVICE_COOKIE')) {
    define('TRUSTED_DEVICE_COOKIE', 'lto_hris_trusted_device');
}

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function ensure_auth_schema()
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    db()->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            middle_name VARCHAR(80) NULL,
            last_name VARCHAR(80) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    db()->exec(
        'CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(60) NOT NULL UNIQUE,
            description VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    db()->exec(
        'CREATE TABLE IF NOT EXISTS user_roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            role_id INT UNSIGNED NOT NULL,
            UNIQUE KEY uniq_user_role (user_id, role_id),
            INDEX idx_role (role_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    ensure_auth_activity_table();
    ensure_email_change_requests_table();
    ensure_trusted_devices_table();
    ensure_base_roles_exist();
}

function client_ip()
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    $isTrustedProxy = TRUST_PROXY_HEADERS
        && $remote !== ''
        && is_array(TRUSTED_PROXY_IPS)
        && in_array($remote, TRUSTED_PROXY_IPS, true);

    if ($isTrustedProxy) {
        $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            // XFF can be a comma-separated list. We want the left-most client IP.
            $parts = array_values(array_filter(array_map('trim', explode(',', $xff)), 'strlen'));
            if (!empty($parts)) {
                $candidate = $parts[0];
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        $realIp = (string) ($_SERVER['HTTP_X_REAL_IP'] ?? '');
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }
    }

    return $remote;
}

function ensure_auth_activity_table()
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS auth_activity (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            identifier VARCHAR(160) NULL,
            event VARCHAR(60) NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_event_time (user_id, event, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function log_auth_event($event, $userId = null, $identifier = null)
{
    try {
        ensure_auth_activity_table();
        $stmt = db()->prepare(
            'INSERT INTO auth_activity (user_id, identifier, event, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId ? (int) $userId : null,
            $identifier ? (string) $identifier : null,
            (string) $event,
            client_ip(),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        // Avoid breaking auth flow when logging fails.
    }
}

function ensure_email_change_requests_table()
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS email_change_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            new_email VARCHAR(150) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_token (token_hash),
            INDEX idx_user_expires (user_id, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function ensure_trusted_devices_table()
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS trusted_devices (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            label VARCHAR(120) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            last_used_at DATETIME NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_token (user_id, token_hash),
            INDEX idx_user_expires (user_id, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function trusted_device_cookie_value()
{
    $v = $_COOKIE[TRUSTED_DEVICE_COOKIE] ?? '';
    return is_string($v) ? trim($v) : '';
}

function is_trusted_device($userId)
{
    if (AUTH_BYPASS_LOGIN) {
        return true;
    }

    $token = trusted_device_cookie_value();
    if ($token === '' || strlen($token) < 32) {
        return false;
    }

    try {
        ensure_trusted_devices_table();
        $hash = hash('sha256', $token);
        $stmt = db()->prepare(
            'SELECT id, expires_at
             FROM trusted_devices
             WHERE user_id = ? AND token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([(int) $userId, $hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        $now = new DateTimeImmutable('now');
        $expires = new DateTimeImmutable($row['expires_at']);
        if ($expires < $now) {
            db()->prepare('DELETE FROM trusted_devices WHERE id = ?')->execute([(int) $row['id']]);
            return false;
        }

        db()->prepare('UPDATE trusted_devices SET last_used_at = NOW(), ip_address = ? WHERE id = ?')
            ->execute([client_ip(), (int) $row['id']]);

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function remember_trusted_device($userId, $label = '')
{
    ensure_trusted_devices_table();

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

    $stmt = db()->prepare(
        'INSERT IGNORE INTO trusted_devices (user_id, token_hash, label, ip_address, user_agent, last_used_at, expires_at)
         VALUES (?, ?, ?, ?, ?, NOW(), ?)'
    );
    $stmt->execute([
        (int) $userId,
        $hash,
        $label !== '' ? substr($label, 0, 120) : null,
        client_ip(),
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        $expiresAt,
    ]);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    setcookie(TRUSTED_DEVICE_COOKIE, $token, [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return true;
}

function clear_trusted_device_cookie()
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    setcookie(TRUSTED_DEVICE_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function send_email_message($to, $subject, $message)
{
    $to = (string) $to;
    $subject = (string) $subject;
    $message = (string) $message;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    if (SMTP_FROM_EMAIL !== '') {
        $fromName = SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'LTO HRIS';
        $headers .= 'From: ' . $fromName . ' <' . SMTP_FROM_EMAIL . ">\r\n";
    }

    $sent = false;

    // Prefer PHPMailer when available (composer vendor/autoload.php).
    $autoloadCandidates = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php',
    ];
    foreach ($autoloadCandidates as $autoloadPath) {
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
            break;
        }
    }

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        $sent = phpmailer_send_mail($to, $subject, $message);
    }

    if (!$sent && SMTP_HOST !== '') {
        $sent = smtp_send_mail($to, $subject, $message);
    }

    try {
        if (!$sent) {
            $sent = @mail($to, $subject, $message, $headers);
        }
    } catch (Throwable $e) {
        $sent = false;
    }

    if (!$sent) {
        error_log('[LTO HRIS] Email not sent (check SMTP). To=' . $to . ' Subject=' . $subject);
    }

    return $sent;
}

function phpmailer_send_mail($to, $subject, $body)
{
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST !== '' ? SMTP_HOST : 'smtp.gmail.com';
        $mail->Port = SMTP_PORT ?: 587;
        $mail->SMTPAuth = SMTP_USER !== '';

        $secure = strtolower((string) SMTP_SECURE);
        if ($secure === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls' || $secure === '') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        if (SMTP_USER !== '') {
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
        }

        $fromEmail = SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : (SMTP_USER !== '' ? SMTP_USER : 'noreply@localhost');
        $fromName = SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'LTO HRIS';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress((string) $to);
        $mail->Subject = (string) $subject;
        $mail->Body = (string) $body;
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';

        return $mail->send();
    } catch (Throwable $e) {
        return false;
    }
}

function format_user_agent($ua)
{
    $ua = (string) $ua;
    if ($ua === '') {
        return 'Unknown device';
    }

    $os = 'Unknown OS';
    if (stripos($ua, 'Windows NT') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Mac OS X') !== false) $os = 'macOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    $browser = 'Browser';
    if (stripos($ua, 'Edg/') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) $browser = 'Chrome';
    elseif (stripos($ua, 'Firefox/') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) $browser = 'Safari';

    return $browser . ' on ' . $os;
}

function smtp_send_mail($to, $subject, $body)
{
    $host = SMTP_HOST;
    $port = SMTP_PORT ?: 587;
    $secure = strtolower((string) SMTP_SECURE);
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $from = SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : ($user !== '' ? $user : 'noreply@localhost');
    $fromName = SMTP_FROM_NAME !== '' ? SMTP_FROM_NAME : 'LTO HRIS';

    $target = ($secure === 'ssl') ? ('ssl://' . $host) : $host;
    $fp = @fsockopen($target, $port, $errno, $errstr, 10);
    if (!$fp) {
        return false;
    }

    $read = function() use ($fp) {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 515);
            if ($line === false) break;
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $data;
    };

    $send = function($cmd) use ($fp) {
        fwrite($fp, $cmd . "\r\n");
    };

    $expectOk = function($resp) {
        $code = (int) substr($resp, 0, 3);
        return $code >= 200 && $code < 400;
    };

    $greet = $read();
    if (!$expectOk($greet)) {
        fclose($fp);
        return false;
    }

    $send('EHLO lto-hris');
    $ehlo = $read();
    if (!$expectOk($ehlo)) {
        $send('HELO lto-hris');
        $helo = $read();
        if (!$expectOk($helo)) {
            fclose($fp);
            return false;
        }
    }

    if ($secure === 'tls' && stripos($ehlo, 'STARTTLS') !== false) {
        $send('STARTTLS');
        $resp = $read();
        if ($expectOk($resp)) {
            @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO lto-hris');
            $ehlo = $read();
        }
    }

    if ($user !== '') {
        $send('AUTH LOGIN');
        $resp = $read();
        if (substr($resp, 0, 3) !== '334') {
            fclose($fp);
            return false;
        }
        $send(base64_encode($user));
        $resp = $read();
        if (substr($resp, 0, 3) !== '334') {
            fclose($fp);
            return false;
        }
        $send(base64_encode($pass));
        $resp = $read();
        if (!$expectOk($resp)) {
            fclose($fp);
            return false;
        }
    }

    $send('MAIL FROM:<' . $from . '>');
    if (!$expectOk($read())) {
        fclose($fp);
        return false;
    }

    $send('RCPT TO:<' . $to . '>');
    if (!$expectOk($read())) {
        fclose($fp);
        return false;
    }

    $send('DATA');
    $resp = $read();
    if (substr($resp, 0, 3) !== '354') {
        fclose($fp);
        return false;
    }

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    $data = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", (string) $body) . "\r\n.";
    $send($data);

    if (!$expectOk($read())) {
        fclose($fp);
        return false;
    }

    $send('QUIT');
    fclose($fp);
    return true;
}

function create_email_change_request($userId, $newEmail)
{
    ensure_email_change_requests_table();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');

    db()->prepare('DELETE FROM email_change_requests WHERE user_id = ?')->execute([(int) $userId]);

    $stmt = db()->prepare(
        'INSERT INTO email_change_requests (user_id, new_email, token_hash, expires_at)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([(int) $userId, normalize_identifier($newEmail), $tokenHash, $expiresAt]);

    return $token;
}

function verify_email_change_request($userId, $token)
{
    ensure_email_change_requests_table();

    $tokenHash = hash('sha256', (string) $token);
    $stmt = db()->prepare(
        'SELECT id, new_email, expires_at
         FROM email_change_requests
         WHERE user_id = ? AND token_hash = ?
         LIMIT 1'
    );
    $stmt->execute([(int) $userId, $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        return [false, 'Invalid or expired verification link.'];
    }

    $now = new DateTimeImmutable('now');
    $expires = new DateTimeImmutable($row['expires_at']);
    if ($expires < $now) {
        db()->prepare('DELETE FROM email_change_requests WHERE id = ?')->execute([(int) $row['id']]);
        return [false, 'Verification link has expired.'];
    }

    $newEmail = (string) $row['new_email'];
    if (user_exists($newEmail)) {
        return [false, 'This email address is already in use.'];
    }

    $update = db()->prepare('UPDATE users SET email = ? WHERE id = ?');
    $update->execute([normalize_identifier($newEmail), (int) $userId]);

    db()->prepare('DELETE FROM email_change_requests WHERE id = ?')->execute([(int) $row['id']]);

    return [true, $newEmail];
}

function requires_2fa($roles)
{
    if (!AUTH_2FA_ENABLED) {
        return false;
    }

    $roles = (array) $roles;
    return in_array('superadmin', $roles, true) || in_array('admin', $roles, true);
}

function start_2fa_challenge($user)
{
    $code = (string) random_int(100000, 999999);
    $_SESSION['pending_2fa'] = [
        'user' => [
            'id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'roles' => $user['roles'] ?? [],
        ],
        'code_hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + 300,
    ];

    if (AUTH_DEV_MODE) {
        $_SESSION['flash_2fa_code'] = $code;
    }

    $email = $user['email'] ?? '';
    $subject = 'Your LTO HRIS verification code';
    $message = "Your one-time verification code is: {$code}\n\nThis code expires in 5 minutes.";
    send_email_message($email, $subject, $message);

    log_auth_event('2fa_sent', $user['id'] ?? null, $email);
}

function verify_2fa_code($code)
{
    $pending = $_SESSION['pending_2fa'] ?? null;
    if (!$pending || !is_array($pending)) {
        return [false, 'No verification is pending.'];
    }

    if (($pending['expires_at'] ?? 0) < time()) {
        unset($_SESSION['pending_2fa']);
        return [false, 'Verification code expired. Please login again.'];
    }

    $hash = $pending['code_hash'] ?? '';
    if (!is_string($hash) || !password_verify((string) $code, $hash)) {
        return [false, 'Invalid verification code.'];
    }

    return [true, $pending['user'] ?? null];
}

function ensure_base_roles_exist()
{
    db()->exec(
        "CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(60) NOT NULL UNIQUE,
            description VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $roles = [
        ['superadmin', 'Super administrator with full system control'],
        ['admin', 'Administrator managing employee records and approvals'],
        ['hr_officer', 'HR officer with personnel management permissions'],
        ['employee', 'Regular employee with self-service access'],
    ];

    $stmt = db()->prepare('INSERT IGNORE INTO roles (name, description) VALUES (?, ?)');
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
}

ensure_auth_schema();

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function regenerate_csrf_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function normalize_identifier($value)
{
    return strtolower(trim((string) $value));
}

function auth_table_has_column($table, $column)
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    $cache[$key] = (int) $stmt->fetchColumn() > 0;
    return $cache[$key];
}

function session_attempt_bucket($bucket)
{
    if (!isset($_SESSION['auth_limits'][$bucket])) {
        $_SESSION['auth_limits'][$bucket] = [
            'count' => 0,
            'locked_until' => 0,
        ];
    }

    return $_SESSION['auth_limits'][$bucket];
}

function is_rate_limited($bucket)
{
    $state = session_attempt_bucket($bucket);
    return $state['locked_until'] > time();
}

function register_failed_attempt($bucket, $lockSeconds = 180, $maxAttempts = 8)
{
    $state = session_attempt_bucket($bucket);
    $state['count']++;

    if ($state['count'] >= $maxAttempts) {
        $state['locked_until'] = time() + $lockSeconds;
        $state['count'] = 0;
    }

    $_SESSION['auth_limits'][$bucket] = $state;
}

function clear_failed_attempts($bucket)
{
    unset($_SESSION['auth_limits'][$bucket]);
}

function current_user()
{
    if (AUTH_BYPASS_LOGIN && !isset($_SESSION['user'])) {
        return [
            'id' => 0,
            'email' => 'test@lto.local',
            'display_name' => 'User',
            'first_name' => 'User',
            'last_name' => '',
            'roles' => ['superadmin', 'admin', 'hr_officer', 'employee'],
        ];
    }

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    static $rolesRefreshed = false;
    if (!$rolesRefreshed && !AUTH_BYPASS_LOGIN) {
        $rolesRefreshed = true;
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if ($userId > 0) {
            try {
                $freshRoles = fetch_user_roles($userId);
                $_SESSION['user']['roles'] = $freshRoles;
            } catch (Throwable $e) {
                // Ignore role refresh failures (e.g., during initial schema setup)
            }
        }
    }

    return $_SESSION['user'];
}

function is_logged_in()
{
    if (AUTH_BYPASS_LOGIN) {
        return true;
    }

    return current_user() !== null;
}

function fetch_user_record($identifier)
{
    $identifier = normalize_identifier($identifier);
    if ($identifier === '') {
        return null;
    }

    $params = [$identifier];
    $sql = 'SELECT id, email, password, first_name, last_name, created_at FROM users WHERE LOWER(email) = ?';

    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetch_user_roles($userId)
{
    $stmt = db()->prepare(
        'SELECT r.name
         FROM roles r
         INNER JOIN user_roles ur ON ur.role_id = r.id
         WHERE ur.user_id = ?'
    );
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function authenticate_user($identifier, $password)
{
    $user = fetch_user_record($identifier);
    if (!$user || !is_string($password) || $password === '') {
        return null;
    }

    if (!password_verify($password, $user['password'])) {
        return null;
    }

    $user['roles'] = fetch_user_roles($user['id']);
    return $user;
}

function login_user($user)
{
    if (!is_array($user)) {
        $user = [
            'id' => null,
            'email' => null,
            'first_name' => (string) $user,
            'middle_name' => '',
            'last_name' => '',
            'roles' => ['hr_officer'],
        ];
    }

    session_regenerate_id(true);

    $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($displayName === '') {
        $displayName = $user['email'] ?? 'User';
    }

    $_SESSION['user'] = [
        'id' => $user['id'] ?? null,
        'email' => $user['email'] ?? null,
        'display_name' => $displayName,
        'roles' => $user['roles'] ?? [],
    ];

    log_auth_event('login_success', $_SESSION['user']['id'] ?? null, $_SESSION['user']['email'] ?? null);
}

function logout_user()
{
    $u = current_user();
    $uid = $u['id'] ?? null;
    $identifier = $u['email'] ?? null;
    log_auth_event('logout', $uid, $identifier);

    unset($_SESSION['user']);
    unset($_SESSION['auth_limits']);
    unset($_SESSION['pending_2fa']);

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function require_login()
{
    if (AUTH_BYPASS_LOGIN) {
        return;
    }

    if (!is_logged_in()) {
        header('Location: index.php?login=1');
        exit;
    }
}

function get_user_roles($user = null)
{
    $u = $user ?: current_user();
    return isset($u['roles']) ? $u['roles'] : [];
}

function has_role($roles)
{
    $userRoles = get_user_roles();
    foreach ((array) $roles as $role) {
        if (in_array($role, $userRoles, true)) {
            return true;
        }
    }
    return false;
}

function require_roles($roles)
{
    if (AUTH_BYPASS_LOGIN) {
        return;
    }

    if (!is_logged_in()) {
        header('Location: index.php?login=1');
        exit;
    }

    if (!has_role($roles)) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }
}

function password_policy_errors($password, $identifier = '', $email = '')
{
    $password = (string) $password;
    $errors = [];
    $categories = 0;

    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    if (preg_match('/[a-z]/', $password)) {
        $categories++;
    }
    if (preg_match('/[A-Z]/', $password)) {
        $categories++;
    }
    if (preg_match('/\d/', $password)) {
        $categories++;
    }
    if (preg_match('/[^a-zA-Z\d]/', $password)) {
        $categories++;
    }
    if ($categories < 3) {
        $errors[] = 'Password must include at least 3 of these: lowercase letters, uppercase letters, numbers, or special characters.';
    }

    $lowerPassword = strtolower($password);
    $samples = array_filter([
        strtolower((string) $email),
    ]);

    foreach ($samples as $sample) {
        if ($sample !== '' && strpos($lowerPassword, $sample) !== false) {
            $errors[] = 'Password must not contain your email.';
            break;
        }
    }

    return $errors;
}

function ensure_account_requests_table()
{
    ensure_auth_schema();
    db()->exec(
        'CREATE TABLE IF NOT EXISTS account_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(160) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            requested_username VARCHAR(80) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT "pending_review",
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function account_request_exists($email, $username = '')
{
    ensure_account_requests_table();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM account_requests
         WHERE LOWER(email) = ?'
    );
    $stmt->execute([
        normalize_identifier($email),
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function create_account_request($fullName, $email, $username = '', $password)
{
    ensure_account_requests_table();

    $stmt = db()->prepare(
        'INSERT INTO account_requests (full_name, email, requested_username, password_hash)
         VALUES (?, ?, ?, ?)'
    );

    $stmt->execute([
        trim($fullName),
        normalize_identifier($email),
        $username ?: null,
        password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function ensure_employee_role_exists()
{
    ensure_auth_schema();
    $stmt = db()->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
    $stmt->execute(['employee']);
    $role = $stmt->fetch();
    
    if (!$role) {
        // Insert employee role if it doesn't exist
        $insertStmt = db()->prepare('INSERT INTO roles (name, description) VALUES (?, ?)');
        $insertStmt->execute(['employee', 'Regular employee with self-service access']);
        return db()->lastInsertId();
    }
    
    return $role['id'];
}

function create_user_directly($firstName, $middleName, $lastName, $email, $password)
{
    ensure_auth_schema();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    return create_user_directly_with_hash($firstName, $middleName, $lastName, $email, $passwordHash);
}

function create_user_directly_with_hash($firstName, $middleName, $lastName, $email, $passwordHash)
{
    ensure_auth_schema();

    $hasMiddleName = auth_table_has_column('users', 'middle_name');
    if ($hasMiddleName) {
        $stmt = db()->prepare(
            'INSERT INTO users (first_name, middle_name, last_name, email, password, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            trim($firstName),
            trim($middleName) !== '' ? trim($middleName) : null,
            trim($lastName),
            normalize_identifier($email),
            (string) $passwordHash,
        ]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO users (first_name, last_name, email, password, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            trim($firstName),
            trim($lastName),
            normalize_identifier($email),
            (string) $passwordHash,
        ]);
    }

    $userId = db()->lastInsertId();
    
    // Ensure employee role exists and assign it to new user
    $employeeRoleId = ensure_employee_role_exists();
    $userRoleStmt = db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
    $userRoleStmt->execute([$userId, $employeeRoleId]);
    
    return $userId;
}

function user_exists($email)
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM users WHERE LOWER(email) = ?'
    );
    $stmt->execute([normalize_identifier($email)]);
    
    return (int) $stmt->fetchColumn() > 0;
}

function lto_email_verification_source_available()
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    if (!auth_table_has_column('pds_personal_info', 'email_address')) {
        $available = false;
        return $available;
    }

    $stmt = db()->query(
        "SELECT COUNT(*)
         FROM pds_personal_info
         WHERE email_address IS NOT NULL
           AND TRIM(email_address) <> ''"
    );

    $available = (int) $stmt->fetchColumn() > 0;
    return $available;
}

function email_exists_in_lto_db($email)
{
    if (!lto_email_verification_source_available()) {
        return null;
    }

    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM pds_personal_info
         WHERE LOWER(TRIM(email_address)) = ?"
    );
    $stmt->execute([normalize_identifier($email)]);

    return (int) $stmt->fetchColumn() > 0;
}
