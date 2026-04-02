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
                    <div class="brand-icon">L</div>
                    <div>
                        <h1><?php echo htmlspecialchars($systemName); ?></h1>
                        <p><?php echo htmlspecialchars($systemOffice); ?></p>
                    </div>
                </div>

                <nav class="nav">
                    <?php foreach ($navItems as $file => $label): ?>
                        <a
                            href="<?php echo $file; ?>"
                            class="nav-link <?php echo $activePage === $file ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

        </aside>

        <main class="main">
            <header class="topbar">
                <div class="topbar-left">
                    <img src="assets/img/lto_logo.png" alt="LTO logo" style="height:40px;display:block">
                    <div class="topbar-title">
                        <span class="eyebrow">Human Resource Management System</span>
                        <h2><?php echo htmlspecialchars($pageTitle); ?></h2>
                    </div>
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
                                <a href="account.php">My Profile</a>
                                <a href="help.php">Help</a>
                                <a href="logout.php" class="danger">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Secure Login</a>
                        <a href="index.php" class="btn btn-primary">Dashboard</a>
                    <?php endif; ?>
                </div>
            </header>
