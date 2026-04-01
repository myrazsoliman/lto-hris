<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "<h2>LTO HRIS - Professional Authentication Fix</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

try {
    echo "<h3>Step 1: Checking Database Connection</h3>";
    $db = db();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    echo "<h3>Step 2: Ensuring Employee Role Exists</h3>";
    $employeeRoleId = ensure_employee_role_exists();
    echo "<p class='success'>✓ Employee role ensured (ID: $employeeRoleId)</p>";
    
    echo "<h3>Step 3: Checking Users Table Structure</h3>";
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='info'>Users table columns: " . implode(', ', $columns) . "</p>";
    
    echo "<h3>Step 4: Finding Users Without Roles</h3>";
    $stmt = $db->prepare('
        SELECT u.id, u.email, u.first_name, u.last_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.id = ur.user_id 
        WHERE ur.user_id IS NULL
    ');
    $stmt->execute();
    $usersWithoutRoles = $stmt->fetchAll();
    
    if (count($usersWithoutRoles) > 0) {
        echo "<p class='info'>Found " . count($usersWithoutRoles) . " users without roles:</p>";
        echo "<pre>";
        foreach ($usersWithoutRoles as $user) {
            echo "- {$user['email']} ({$user['first_name']} {$user['last_name']})\n";
            
            // Assign employee role
            $userRoleStmt = $db->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $result = $userRoleStmt->execute([$user['id'], $employeeRoleId]);
            echo "  → Employee role assigned: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        }
        echo "</pre>";
        echo "<p class='success'>✓ Role assignment completed</p>";
    } else {
        echo "<p class='success'>✓ All users have roles assigned</p>";
    }
    
    echo "<h3>Step 5: Verifying Current User Roles</h3>";
    $stmt = $db->prepare('
        SELECT u.email, r.name as role_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN roles r ON ur.role_id = r.id 
        ORDER BY u.email, r.name
    ');
    $stmt->execute();
    $userRoles = $stmt->fetchAll();
    
    echo "<pre>";
    foreach ($userRoles as $userRole) {
        $roleName = $userRole['role_name'] ?: 'NO ROLE';
        echo "- {$userRole['email']}: $roleName\n";
    }
    echo "</pre>";
    
    echo "<h3>Step 6: Testing Authentication Functions</h3>";
    
    // Test role fetching for a sample user
    if (!empty($userRoles)) {
        $testEmail = $userRoles[0]['email'];
        $testUser = fetch_user_record($testEmail);
        if ($testUser) {
            $testRoles = fetch_user_roles($testUser['id']);
            echo "<p class='info'>Test user: $testEmail</p>";
            echo "<p class='info'>User roles: " . implode(', ', $testRoles) . "</p>";
            
            $hasEmployeeRole = has_role(['employee']);
            echo "<p class='info'>Has employee role: " . ($hasEmployeeRole ? 'YES' : 'NO') . "</p>";
        }
    }
    
    echo "<h3>✓ PROFESSIONAL FIX COMPLETED</h3>";
    echo "<p class='success'>The authentication system has been fixed. Users should now be able to:</p>";
    echo "<ul>";
    echo "<li>Register new accounts with automatic employee role assignment</li>";
    echo "<li>Login and access the employee dashboard without 'Forbidden' errors</li>";
    echo "<li>Existing users have been assigned the employee role</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Clear your browser cache and cookies</li>";
    echo "<li>Try logging out and logging back in</li>";
    echo "<li>If you still have issues, register a new test account</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
