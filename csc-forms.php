<?php
$pageTitle = 'CSC Forms Module';
$activePage = 'csc-forms.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/template-helper.php';
require_once 'includes/data.php';

// Get employee data for auto-fill
$employee_data = [];
if (isset($_SESSION['user']['id'])) {
    $employee_data = getEmployeeData($_SESSION['user']['id']);
}
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">CSC Forms</span>
            <h3>Forms Tracking</h3>
        </div>
        <button class="btn btn-primary" onclick="toggleForm()">Generate Form</button>
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

<!-- Dynamic CSC Form Section -->
<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">CSC Form Generator</span>
            <h3>Create New CSC Form</h3>
        </div>
    </div>

    <div id="cscFormContainer" style="display: none;">
        <?php 
        // Generate form with auto-filled employee data
        echo generateFormWithAutofill('csc', $employee_data); 
        ?>
    </div>
</section>

<script>
function toggleForm() {
    const container = document.getElementById('cscFormContainer');
    const button = event.target;
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        button.textContent = 'Hide Form';
        button.classList.remove('btn-primary');
        button.classList.add('btn-secondary');
    } else {
        container.style.display = 'none';
        button.textContent = 'Generate Form';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-primary');
    }
}

function downloadForm() {
    // Implementation for PDF download
    alert('PDF download functionality will be implemented with a PDF library like DOMPDF or TCPDF');
}
</script>

<?php require_once 'includes/footer.php'; ?>