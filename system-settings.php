<?php
$pageTitle = 'System Settings';
$activePage = 'system-settings.php';
require_once 'includes/auth.php';
require_roles(['superadmin']);
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Configuration</span>
            <h3>System Settings</h3>
        </div>
    </div>

    <p class="text-muted" style="line-height: 1.8;">
        Coming soon: configure policies, permissions, and enabled modules.
    </p>
</section>

<?php require_once 'includes/footer.php'; ?>

