<?php
if (!isset($pageTitle)) {
    $pageTitle = 'LTO HRIS';
}
if (!isset($activePage)) {
    $activePage = '';
}
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';

// Get role-based navigation items
$user = current_user();
$userRoles = $user ? $user['roles'] : [];
$navItems = get_nav_items($userRoles);
$profileName = $user['display_name'] ?? 'User';
$profileInitial = strtoupper(substr(trim((string) $profileName), 0, 1)) ?: 'U';
$profilePhoto = trim((string) ($user['profile_photo_path'] ?? ''));
$profilePhotoUrl = $profilePhoto !== '' ? str_replace(' ', '%20', str_replace('\\', '/', $profilePhoto)) : '';
$userIdForNotifications = (int) ($user['id'] ?? 0);
if ($user) {
    ensure_demo_notification_for_user($userIdForNotifications);
}
$notificationCount = $user ? get_unread_notification_count($userIdForNotifications) : 0;
$showLoginSuccessModal = !empty($_SESSION['flash_login_success']) && $user;
if ($showLoginSuccessModal) {
    unset($_SESSION['flash_login_success']);
}

$navIconMap = [
    'Dashboard' => 'fa-solid fa-house',
    'My Account' => 'fa-solid fa-user',
    'My Profile' => 'fa-solid fa-user',
    'Admin Accounts' => 'fa-solid fa-users-gear',
    'Employees' => 'fa-solid fa-users',
    'User Management' => 'fa-solid fa-user-shield',
    'PDS' => 'fa-solid fa-id-card',
    'My PDS' => 'fa-solid fa-id-card',
    'PDS Template' => 'fa-solid fa-file-arrow-up',
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
    'My Documents' => 'fa-solid fa-folder-open'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($systemName); ?></title>
    <?php require_once __DIR__ . '/favicon-links.php'; ?>
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
                    <form class="topbar-search" role="search" action="search.php" method="get" autocomplete="off">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <label class="sr-only" for="topbarSearch">Search</label>
                        <input id="topbarSearch" name="q" type="text" inputmode="search" placeholder="Search..." aria-label="Search" autocapitalize="off" spellcheck="false">
                        <div class="topbar-search-dropdown" id="topbarSearchDropdown" hidden>
                            <div class="topbar-search-dropdown-head">
                                <strong>Search results</strong>
                                <span class="topbar-search-dropdown-hint">Press Enter for full results</span>
                            </div>
                            <div class="topbar-search-dropdown-body" id="topbarSearchResults">
                                <div class="notification-empty">Type to search…</div>
                            </div>
                        </div>
                    </form>
                    <span class="sr-only"><?php echo htmlspecialchars($pageTitle); ?></span>
                </div>
                <div class="topbar-actions">
                    <?php if (is_logged_in()): ?>
                        <div class="notification-menu" id="notificationMenu">
                        <button type="button" class="topbar-mini-icon topbar-mini-icon--notif" id="notifBtn" aria-label="Notifications" aria-expanded="false" aria-controls="notificationDropdown">
                            <i class="far fa-bell" aria-hidden="true"></i>
                            <span class="notification-badge<?php echo $notificationCount > 0 ? '' : ' is-hidden'; ?>" id="notifCount"><?php echo (int) min($notificationCount, 99); ?></span>
                        </button>
                        <div class="notification-dropdown" id="notificationDropdown" hidden>
                            <div class="notification-dropdown-head">
                                <div>
                                    <strong>Notifications</strong>
                                    <p id="notificationSummary">
                                        <?php echo $notificationCount > 0 ? 'You have ' . (int) $notificationCount . ' new notifications.' : 'No new notifications.'; ?>
                                    </p>
                                </div>
                                <button type="button" class="notification-mark-read" id="markNotificationsRead">Mark all as read</button>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="notification-empty">Loading notifications...</div>
                            </div>
                            <a href="notification-center.php" class="notification-footer-link">See all notifications</a>
                        </div>
                        </div>
                        <div class="profile-menu">
                            <button type="button" class="profile-summary" aria-label="Open profile menu">
                                <span class="profile-avatar<?php echo $profilePhotoUrl !== '' ? ' has-photo' : ''; ?>" aria-hidden="true">
                                    <?php if ($profilePhotoUrl !== ''): ?>
                                        <img src="<?php echo htmlspecialchars($profilePhotoUrl); ?>" alt="<?php echo htmlspecialchars($profileName); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($profileInitial); ?>
                                    <?php endif; ?>
                                </span>
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
                                <a href="logout.php" class="danger" data-logout-trigger="true">
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

            <?php if (is_logged_in()): ?>
                <div class="confirm-modal confirm-modal--menu" id="logoutConfirmModal" aria-hidden="true">
                    <div class="confirm-modal-backdrop" data-logout-cancel></div>
                    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
                        <div class="confirm-modal-head">
                            <span class="confirm-modal-icon" aria-hidden="true">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </span>
                            <div class="confirm-modal-copy">
                                <h2 id="logoutConfirmTitle">Are you sure you want to logout?</h2>
                                <p class="confirm-modal-text">You will need to sign in again to access your account.</p>
                            </div>
                        </div>
                        <div class="confirm-modal-divider" aria-hidden="true"></div>
                        <div class="confirm-modal-actions">
                            <button type="button" class="btn btn-outline confirm-btn-cancel" id="logoutConfirmNo" data-logout-cancel>Stay Signed In</button>
                            <a href="logout.php" class="btn btn-danger confirm-btn-logout" id="logoutConfirmYes">Logout</a>
                        </div>
                    </div>
                </div>

                <?php if ($showLoginSuccessModal): ?>
                    <div class="confirm-modal confirm-modal--menu confirm-modal--success" id="loginSuccessModal" aria-hidden="true">
                        <div class="confirm-modal-backdrop" data-login-success-close></div>
                        <div class="confirm-modal-dialog login-success-dialog" role="dialog" aria-modal="true" aria-labelledby="loginSuccessTitle">
                            <div class="login-success-head">
                                <span class="confirm-modal-icon login-success-icon" aria-hidden="true">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <h2 id="loginSuccessTitle" class="login-success-title">Login Successful</h2>
                                <p class="confirm-modal-text login-success-text">Welcome back, <?php echo htmlspecialchars($profileName); ?>.</p>
                                <p class="confirm-modal-text login-success-subtext">Redirecting you to your dashboard…</p>
                            </div>
                            <div class="confirm-modal-actions login-success-actions">
                                <button type="button" class="btn btn-primary login-success-ok" data-login-success-close>OK</button>
                            </div>
                        </div>
                    </div>
                    <script>
                        (function() {
                            if (window.__loginSuccessModalBound) return;
                            window.__loginSuccessModalBound = true;

                            var modal = document.getElementById('loginSuccessModal');
                            if (!modal) return;

                            var closeTargets = modal.querySelectorAll('[data-login-success-close]');

                            function setOpen(next) {
                                var isOpen = !!next;
                                modal.classList.toggle('is-open', isOpen);
                                modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                                document.body.classList.toggle('modal-open', isOpen);
                            }

                            setOpen(true);
                            window.setTimeout(function() {
                                setOpen(false);
                            }, 3000);

                            closeTargets.forEach(function(el) {
                                el.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    setOpen(false);
                                });
                            });

                            document.addEventListener('keydown', function(e) {
                                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                                    setOpen(false);
                                }
                            });
                        }());
                    </script>
                <?php endif; ?>
                <script>
                    (function() {
                        if (window.__logoutConfirmInlineBound) return;
                        window.__logoutConfirmInlineBound = true;

                        function bindLogoutConfirm() {
                            var modal = document.getElementById('logoutConfirmModal');
                            if (!modal) return;

                            var cancelTargets = modal.querySelectorAll('[data-logout-cancel]');
                            var yesLink = document.getElementById('logoutConfirmYes');
                            var noBtn = document.getElementById('logoutConfirmNo');
                            var lastFocus = null;

                            function setOpen(next) {
                                var isOpen = !!next;
                                modal.classList.toggle('is-open', isOpen);
                                modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                                document.body.classList.toggle('modal-open', isOpen);
                                if (isOpen && noBtn) {
                                    noBtn.focus();
                                } else if (!isOpen && lastFocus) {
                                    lastFocus.focus();
                                }
                            }

                            document.addEventListener('click', function(e) {
                                var trigger = e.target.closest('[data-logout-trigger="true"], a[href="logout.php"]');
                                if (!trigger || trigger.id === 'logoutConfirmYes') return;
                                e.preventDefault();
                                lastFocus = trigger;
                                setOpen(true);
                            });

                            cancelTargets.forEach(function(el) {
                                el.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    setOpen(false);
                                });
                            });

                            document.addEventListener('keydown', function(e) {
                                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                                    setOpen(false);
                                }
                            });

                            if (yesLink) {
                                yesLink.addEventListener('click', function() {
                                    setOpen(false);
                                });
                            }
                        }

                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', bindLogoutConfirm, { once: true });
                        } else {
                            bindLogoutConfirm();
                        }
                    }());
                </script>
            <?php endif; ?>
