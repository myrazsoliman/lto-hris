<?php
require_once 'includes/auth.php';

function redirect_to_dashboard()
{
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }

    $user = current_user();
    $roles = $user['roles'] ?? [];

    // Priority-based role routing
    if (in_array('superadmin', $roles)) {
        header('Location: superadmin-dashboard.php');
    } elseif (in_array('admin', $roles)) {
        header('Location: admin-dashboard.php');
    } elseif (in_array('hr_officer', $roles)) {
        header('Location: admin-dashboard.php');
    } elseif (in_array('employee', $roles)) {
        header('Location: employee-dashboard.php');
    } else {
        // Default fallback
        header('Location: employee-dashboard.php');
    }
    exit;
}

// Handle direct dashboard access requests
if (isset($_GET['redirect_to_dashboard'])) {
    redirect_to_dashboard();
}
?>
