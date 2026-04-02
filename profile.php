<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// Redirect superadmin to account management page
$user = current_user();
if (has_role('superadmin')) {
    header('Location: account.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$employee = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM employees WHERE id = ?');
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
}

$error = '';
$success = '';

// Handle success messages from redirect
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $success = 'Employee information updated successfully!';
} elseif (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = 'Employee created successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only HR or admin may create or update employee records
    require_roles(['admin', 'hr_officer']);
    
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } else {
        $employee_number = $_POST['employee_number'] ?? null;
        $first_name = $_POST['first_name'] ?? null;
        $last_name = $_POST['last_name'] ?? null;
        $department = $_POST['department'] ?? null;
        $position = $_POST['position'] ?? null;
        $status = $_POST['status'] ?? 'Active';

        if ($id) {
            db()->prepare('UPDATE employees SET employee_number = ?, first_name = ?, last_name = ?, department = ?, position = ?, status = ? WHERE id = ?')
                ->execute([$employee_number, $first_name, $last_name, $department, $position, $status, $id]);
            $successParam = 'updated';
        } else {
            db()->prepare('INSERT INTO employees (employee_number, first_name, last_name, department, position, status) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$employee_number, $first_name, $last_name, $department, $position, $status]);
            $successParam = 'created';
        }
        
        // Redirect to prevent form resubmission
        header('Location: employees.php?success=' . $successParam);
        exit;
    }
}

$pageTitle = 'My Profile';
$activePage = 'profile.php';
require 'includes/header.php';
?>

<main class="container small">
    <div class="card">
        <h2>Employee Profile</h2>
        <form class="form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
