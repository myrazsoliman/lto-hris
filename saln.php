<?php
$pageTitle = 'SALN Module';
$activePage = 'saln.php';
require_once 'includes/header.php';
?>

<section class="grid two-col">
    <article class="card">
        <div class="section-head">
            <div>
                <span class="tag">SALN Monitoring</span>
                <h3>Annual Filing Status</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salnRecords as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['employee']); ?></td>
                            <td><?php echo htmlspecialchars($record['year']); ?></td>
                            <td>
                                <span class="status <?php echo strtolower(str_replace(' ', '-', $record['status'])); ?>">
                                    <?php echo htmlspecialchars($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="section-head">
            <div>
                <span class="tag">Compliance</span>
                <h3>Module Overview</h3>
            </div>
        </div>
        <p class="text-muted">
            This module helps HR staff track annual SALN filing compliance and identify employees with
            complete, pending, or missing submissions.
        </p>
    </article>
</section>

<?php require_once 'includes/footer.php'; ?>