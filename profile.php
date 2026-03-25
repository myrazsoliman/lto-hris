<?php
require 'includes/header.php';
require_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$employee = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only HR or admin may create or update employee records
    require_roles(['admin', 'hr_officer']);
    $employee_number = $_POST['employee_number'] ?? null;
    $first_name = $_POST['first_name'] ?? null;
    $last_name = $_POST['last_name'] ?? null;
    $department = $_POST['department'] ?? null;
    $position = $_POST['position'] ?? null;
    $status = $_POST['status'] ?? 'Active';

    if ($id) {
        db()->prepare('UPDATE employees SET employee_number = ?, first_name = ?, last_name = ?, department = ?, position = ?, status = ? WHERE id = ?')
            ->execute([$employee_number, $first_name, $last_name, $department, $position, $status, $id]);
    } else {
        db()->prepare('INSERT INTO employees (employee_number, first_name, last_name, department, position, status) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$employee_number, $first_name, $last_name, $department, $position, $status]);
    }
    header('Location: employees.php');
    exit;
}
?>

<main class="container small">
    <div class="card">
        <h2>Employee Profile</h2>
        <form class="form" method="post">
            <label>Employee Number
                <input type="text" name="employee_number" value="<?= htmlspecialchars($employee['employee_number'] ?? '') ?>">
            </label>
            <label>First Name
                <input type="text" name="first_name" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>">
            </label>
            <label>Last Name
                <input type="text" name="last_name" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>">
            </label>
            <label>Department
                <input type="text" name="department" value="<?= htmlspecialchars($employee['department'] ?? '') ?>">
            </label>
            <label>Position
                <input type="text" name="position" value="<?= htmlspecialchars($employee['position'] ?? '') ?>">
            </label>
            <label>Status
                <select name="status">
                    <option value="Active" <?= (isset($employee['status']) && $employee['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
                    <option value="Probationary" <?= (isset($employee['status']) && $employee['status'] === 'Probationary') ? 'selected' : '' ?>>Probationary</option>
                    <option value="On Leave" <?= (isset($employee['status']) && $employee['status'] === 'On Leave') ? 'selected' : '' ?>>On Leave</option>
                </select>
            </label>
            <div class="form-row">
                <button class="btn">Save</button>
                <a class="btn ghost" href="employees.php">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require 'includes/footer.php'; ?>