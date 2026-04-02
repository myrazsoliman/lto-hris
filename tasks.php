<?php
$pageTitle = 'Tasks';
$activePage = '';

require_once 'includes/auth.php';
require_login();
require_roles(['employee']);

require_once 'includes/header.php';

$tasks = [
    [
        'icon' => 'fa-regular fa-user',
        'title' => 'Review profile details',
        'desc' => 'Update contact info and review security settings.',
        'href' => 'account.php',
    ],
    [
        'icon' => 'fa-solid fa-id-card',
        'title' => 'Update Personnel Data Sheet (PDS)',
        'desc' => 'Verify information and keep your PDS current.',
        'href' => 'pds.php',
    ],
    [
        'icon' => 'fa-regular fa-folder-open',
        'title' => 'Upload supporting files',
        'desc' => 'Upload requirements and supporting documents.',
        'href' => 'documents.php',
    ],
    [
        'icon' => 'fa-regular fa-calendar-check',
        'title' => 'File leave request',
        'desc' => 'Submit leave requests and track status.',
        'href' => 'leave-request.php',
    ],
    [
        'icon' => 'fa-solid fa-scale-balanced',
        'title' => 'Review SALN requirements',
        'desc' => 'Check guidance and ensure annual compliance.',
        'href' => 'saln.php',
    ],
];
?>

<section class="card">
    <div class="section-head">
        <div>
            <span class="tag">Checklist</span>
            <h3>Employee Tasks</h3>
            <p class="section-head-desc">Quick checklist to keep your HR records updated.</p>
        </div>
    </div>
    <div class="section-divider" role="presentation"></div>

    <div class="checklist">
        <?php foreach ($tasks as $index => $task): ?>
            <label class="checklist-item">
                <span class="checklist-check">
                    <input type="checkbox" name="task_<?php echo (int) $index; ?>">
                    <span class="checklist-box" aria-hidden="true"></span>
                </span>
                <span class="checklist-body">
                    <span class="checklist-title">
                        <i class="<?php echo htmlspecialchars($task['icon']); ?>" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($task['title']); ?>
                    </span>
                    <span class="checklist-desc"><?php echo htmlspecialchars($task['desc']); ?></span>
                </span>
                <span class="checklist-cta">
                    <a class="btn btn-outline btn-small" href="<?php echo htmlspecialchars($task['href']); ?>">Open</a>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

