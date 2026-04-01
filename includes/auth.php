<?php
require_once __DIR__ . '/db.php';

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
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in()
{
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
}

function logout_user()
{
    unset($_SESSION['user']);
    unset($_SESSION['auth_limits']);

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
    if (!is_logged_in()) {
        header('Location: /login.php');
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

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must include at least one number.';
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
    $stmt = db()->prepare(
        'INSERT INTO users (first_name, last_name, email, password, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );

    $stmt->execute([
        trim($firstName),
        trim($lastName),
        normalize_identifier($email),
        password_hash($password, PASSWORD_DEFAULT),
    ]);

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
