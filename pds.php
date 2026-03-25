<?php
$pageTitle = 'Personnel Data Sheet Module';
$activePage = 'pds.php';
require_once 'includes/header.php';
?>

<section class="grid two-col">
    <article class="card">
        <div class="section-head">
            <div>
                <span class="tag">PDS Module</span>
                <h3>Personnel Data Sheet Records</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Last Updated</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pdsRecords as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['employee']); ?></td>
                            <td><?php echo htmlspecialchars($record['last_updated']); ?></td>
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
                <span class="tag">Description</span>
                <h3>Module Purpose</h3>
            </div>
        </div>
        <p class="text-muted">
            The PDS module centralizes employee personal and professional information. It helps HR personnel
            maintain complete records and quickly retrieve data when needed for documentation and reporting.
        </p>
    </article>
</section>

<?php require_once 'includes/footer.php'; ?>