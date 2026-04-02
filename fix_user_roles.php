<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "=== FIXING EXISTING USER ROLES ===\n";

// Ensure employee role exists
$employeeRoleId = ensure_employee_role_exists();
echo "Employee role ID: $employeeRoleId\n";

// Find users without any roles
$stmt = db()->prepare('
    SELECT u.id, u.email, u.first_name, u.last_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    WHERE ur.user_id IS NULL
');
$stmt->execute();
$usersWithoutRoles = $stmt->fetchAll();

echo "Found " . count($usersWithoutRoles) . " users without roles:\n";

foreach ($usersWithoutRoles as $user) {
    echo "- Assigning employee role to: {$user['email']} ({$user['first_name']} {$user['last_name']})\n";
    
    // Assign employee role
    $userRoleStmt = db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
    $userRoleStmt->execute([$user['id'], $employeeRoleId]);
}

echo "Role assignment completed.\n";

// Verify the fix
$stmt = db()->prepare('
    SELECT u.email, r.name as role_name 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    ORDER BY u.email, r.name
');
$stmt->execute();
$userRoles = $stmt->fetchAll();

echo "\nCurrent user roles:\n";
foreach ($userRoles as $userRole) {
    echo "- {$userRole['email']}: {$userRole['role_name']}\n";
}
?>
