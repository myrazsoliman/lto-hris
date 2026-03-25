<?php
$pageTitle = 'Reports and Compliance';
$activePage = 'reports.php';
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Reports</span>
            <h3>Generated Reports</h3>
        </div>
        <a href="#" class="btn btn-primary">Create Report</a>
    </div>

    <div class="report-grid">
        <?php foreach ($reports as $report): ?>
            <article class="report-card">
                <h4><?php echo htmlspecialchars($report['title']); ?></h4>
                <p class="text-muted">File Type: <?php echo htmlspecialchars($report['type']); ?></p>
                <p class="text-muted">Last Updated: <?php echo htmlspecialchars($report['updated']); ?></p>
                <div class="report-actions">
                    <a href="#" class="btn btn-outline btn-small">View</a>
                    <a href="#" class="btn btn-primary btn-small">Download</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>