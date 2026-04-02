<?php
$pageTitle = 'Employee Records';
$activePage = 'employees.php';
require_once 'includes/auth.php';
require_roles(['admin', 'hr_officer', 'superadmin']);
require_once 'includes/header.php';
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Personnel</span>
            <h3>Employee Directory</h3>
        </div>
        <a href="#" class="btn btn-primary">Add Employee</a>
    </div>

    <div class="toolbar">
        <input type="text" class="search-input" placeholder="Search employee...">
        <select class="select-input">
            <option>All Departments</option>
            <option>HR Unit</option>
            <option>Admin</option>
            <option>Records</option>
            <option>ICT</option>
            <option>Operations</option>
        </select>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['id']); ?></td>
                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                        <td>
                            <span class="status <?php echo strtolower(str_replace(' ', '-', $employee['status'])); ?>">
                                <?php echo htmlspecialchars($employee['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
