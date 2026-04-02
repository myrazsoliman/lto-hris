<?php
if (!isset($pageTitle)) {
    $pageTitle = 'LTO HRIS';
}
if (!isset($activePage)) {
    $activePage = '';
}
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/auth.php';

// Get role-based navigation items
$user = current_user();
$userRoles = $user ? $user['roles'] : [];
$navItems = get_nav_items($userRoles);
$profileName = $user['display_name'] ?? 'User';

$navIconMap = [
    'Dashboard' => 'fa-solid fa-house',
    'My Account' => 'fa-solid fa-user',
    'My Profile' => 'fa-solid fa-user',
    'Admin Accounts' => 'fa-solid fa-users-gear',
    'Employees' => 'fa-solid fa-users',
    'User Management' => 'fa-solid fa-user-shield',
    'PDS' => 'fa-solid fa-id-card',
    'My PDS' => 'fa-solid fa-id-card',
    'System PDS' => 'fa-solid fa-address-card',
    'CSC Forms' => 'fa-solid fa-file-lines',
    'Forms' => 'fa-solid fa-file-lines',
    'SALN' => 'fa-solid fa-scale-balanced',
    'My SALN' => 'fa-solid fa-scale-balanced',
    'SALN Monitoring' => 'fa-solid fa-scale-balanced',
    'Reports' => 'fa-solid fa-chart-column',
    'System Reports' => 'fa-solid fa-chart-column',
    'Form Templates' => 'fa-solid fa-layer-group',
    'System Settings' => 'fa-solid fa-gear',
    'Activity Logs' => 'fa-solid fa-clipboard-list',
    'Leave Request' => 'fa-solid fa-calendar-check',
    'Leave Requests' => 'fa-solid fa-calendar-check',
    'Employee Files' => 'fa-solid fa-folder-open',
    'My Documents' => 'fa-solid fa-folder-open'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($systemName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <div class="app">
        <aside class="sidebar">
            <div>
                <div class="brand">
                    <div class="brand-icon">
                        <img src="assets/img/lto_logo.png" alt="LTO logo">
                    </div>
                    <div>
                        <h1><?php echo htmlspecialchars($systemName); ?></h1>
                        <p><?php echo htmlspecialchars($systemOffice); ?></p>
                    </div>
                </div>

                <nav class="nav">
                    <?php foreach ($navItems as $file => $label): ?>
                        <?php $iconClass = $navIconMap[$label] ?? 'fa-regular fa-circle'; ?>
                        <a
                            href="<?php echo $file; ?>"
                            class="nav-link <?php echo $activePage === $file ? 'active' : ''; ?>">
                            <i class="<?php echo htmlspecialchars($iconClass); ?>" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

        </aside>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <form class="topbar-search" role="search" action="#" method="get" autocomplete="off">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <label class="sr-only" for="topbarSearch">Search</label>
                        <input id="topbarSearch" name="q" type="search" placeholder="Search menu..." aria-label="Search menu">
                    </form>
                    <span class="sr-only"><?php echo htmlspecialchars($pageTitle); ?></span>
                </div>
                <div class="topbar-actions">
                    <?php if (is_logged_in()): ?>
                        <button type="button" class="topbar-mini-icon" aria-label="Notifications">
                            <i class="far fa-bell" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="topbar-mini-icon" aria-label="Messages">
                            <i class="far fa-envelope" aria-hidden="true"></i>
                        </button>
                        <div class="profile-menu">
                            <button type="button" class="profile-summary" aria-label="Open profile menu">
                                <span class="profile-avatar" aria-hidden="true">P</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="profile-dropdown">
                                <a href="account.php">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                    <span>My Profile</span>
                                </a>
                                <a href="help.php">
                                    <i class="fa-regular fa-circle-question" aria-hidden="true"></i>
                                    <span>Help</span>
                                </a>
                                <a href="logout.php" class="danger">
                                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Secure Login</a>
                        <a href="index.php" class="btn btn-primary">Dashboard</a>
                    <?php endif; ?>
                </div>
            </header>
