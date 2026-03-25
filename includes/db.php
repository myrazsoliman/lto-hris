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
