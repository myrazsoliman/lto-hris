<?php
$pageTitle = 'CSC Forms Module';
$activePage = 'csc-forms.php';
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">CSC Forms</span>
            <h3>Forms Tracking</h3>
        </div>
        <a href="#" class="btn btn-primary">Generate Form</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Form</th>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cscForms as $form): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($form['form']); ?></td>
                        <td><?php echo htmlspecialchars($form['employee']); ?></td>
                        <td><?php echo htmlspecialchars($form['date']); ?></td>
                        <td>
                            <span class="status <?php echo strtolower(str_replace(' ', '-', $form['status'])); ?>">
                                <?php echo htmlspecialchars($form['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>