<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';

echo "=== DEBUGGING EMPLOYEE DASHBOARD ACCESS ===\n";

// Check if user is logged in
if (!is_logged_in()) {
    echo "User is NOT logged in\n";
    exit;
} else {
    echo "User is logged in\n";
}

// Get current user information
$currentUser = current_user();
echo "Current user data: " . json_encode($currentUser) . "\n";

// Get user roles
$userRoles = get_user_roles();
echo "User roles: " . json_encode($userRoles) . "\n";

// Check if user has employee role
$hasEmployeeRole = has_role(['employee']);
echo "Has employee role: " . ($hasEmployeeRole ? 'YES' : 'NO') . "\n";

// Check if user passes the role check
$passesRoleCheck = false;
foreach (['employee', 'hr_officer', 'admin', 'superadmin'] as $role) {
    if (has_role([$role])) {
        $passesRoleCheck = true;
        echo "User has role: $role\n";
        break;
    }
}

echo "Passes role check: " . ($passesRoleCheck ? 'YES' : 'NO') . "\n";

if ($passesRoleCheck) {
    echo "✓ User should be able to access employee dashboard\n";
} else {
    echo "✗ User will get FORBIDDEN error\n";
}
?>
