<?php
$pageTitle = 'Admin Accounts';
$activePage = 'admin-accounts.php';
require_once 'includes/auth.php';
require_roles(['superadmin']);
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">System</span>
            <h3>Admin Account Management</h3>
        </div>
    </div>

    <p class="text-muted" style="line-height: 1.8;">
        Coming soon: create and manage admin accounts, assign roles, and reset credentials.
    </p>
</section>

<?php require_once 'includes/footer.php'; ?>

