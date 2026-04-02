<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "Checking roles in database...\n";

// Check all roles
$stmt = db()->prepare('SELECT * FROM roles');
$stmt->execute();
$roles = $stmt->fetchAll();

echo "Roles found:\n";
foreach ($roles as $role) {
    echo "- {$role['name']}: {$role['description']}\n";
}

// Check if employee role exists
$stmt = db()->prepare('SELECT * FROM roles WHERE name = ?');
$stmt->execute(['employee']);
$role = $stmt->fetch();

if ($role) {
    echo "\nEmployee role found: ID {$role['id']}\n";
} else {
    echo "\nEmployee role NOT found - inserting...\n";
    
    // Insert employee role
    $stmt = db()->prepare('INSERT INTO roles (name, description) VALUES (?, ?)');
    $stmt->execute(['employee', 'Regular employee with self-service access']);
    echo "Employee role inserted\n";
}

// Check a test user's roles
$stmt = db()->prepare('SELECT u.email, r.name as role_name FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id ORDER BY u.id, r.name');
$stmt->execute();
$userRoles = $stmt->fetchAll();

echo "\nUser roles:\n";
foreach ($userRoles as $userRole) {
    echo "- {$userRole['email']}: {$userRole['role_name']}\n";
}
?>
