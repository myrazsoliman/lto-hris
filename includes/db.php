<?php
$dbHost = '127.0.0.1';
$dbName = 'lto_hris';
$dbUser = 'root';
$dbPass = '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In production, don't echo errors. Log them instead.
    die('Database connection failed: ' . $e->getMessage());
}

// Simple helper
function db()
{
    global $pdo;
    return $pdo;
}

function ensure_form_templates_table()
{
    $sql = "CREATE TABLE IF NOT EXISTS form_templates (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        form_type VARCHAR(30) NOT NULL,
        template_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(1024) NOT NULL,
        version VARCHAR(50) NOT NULL,
        uploaded_by INT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_form_type_uploaded_at (form_type, uploaded_at),
        INDEX idx_form_type_active (form_type, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    db()->exec($sql);
}
