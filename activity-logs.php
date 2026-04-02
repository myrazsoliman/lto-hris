<?php
$pageTitle = 'Activity Logs';
$activePage = 'activity-logs.php';
require_once 'includes/auth.php';
require_roles(['superadmin']);
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Audit</span>
            <h3>System Activity Logs</h3>
        </div>
    </div>

    <p class="text-muted" style="line-height: 1.8;">
        Coming soon: view sign-ins, changes to records, approvals, and configuration updates.
    </p>
</section>

<?php require_once 'includes/footer.php'; ?>

