<?php
require_once 'includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'lto-pila-office.php');
$publicNavItems = [
    ['label' => 'Home', 'href' => 'index.php', 'active' => $currentPage === 'index.php', 'caret' => false],
            [
        'label' => 'About Us',
        'href' => '#',
        'active' => in_array($currentPage, [
            'news-and-updates.php',
            'careers.php',
            'lto-accredited.php',
            'otdc-it-provider.php',
            'medical-clinics.php',
            'medical-it-providers.php',
            'lecturers.php',
            'driving-schools.php',
            'driving-school-instructors.php',
            'drivers-education-center.php',
            'affiliates.php',
            'ltms-portal.php',
            'cde-program.php',
            'cde-online-exam.php',
            'citisend.php',
            'resources.php',
            'downloadable-forms.php',
            'hrds-forms.php',
            'mission-and-vision.php',
            'mandate-and-functions.php',
            'historical-background.php',
            'road-safety-action-plan.php',
            'lto-pila-office.php'
        ], true),
        'caret' => true,
        'children' => [
            ['label' => 'News and Updates', 'href' => 'news-and-updates.php', 'active' => $currentPage === 'news-and-updates.php'],
            ['label' => 'Careers', 'href' => 'careers.php', 'active' => $currentPage === 'careers.php'],
            [
                'label' => 'LTO Accredited',
                'href' => 'lto-accredited.php',
                'active' => in_array($currentPage, ['lto-accredited.php', 'otdc-it-provider.php', 'medical-clinics.php', 'medical-it-providers.php', 'lecturers.php', 'driving-schools.php', 'driving-school-instructors.php', 'drivers-education-center.php'], true),
                'children' => [
                    ['label' => 'OTDC IT Provider', 'href' => 'otdc-it-provider.php', 'active' => $currentPage === 'otdc-it-provider.php'],
                    ['label' => 'Medical Clinics', 'href' => 'medical-clinics.php', 'active' => $currentPage === 'medical-clinics.php'],
                    ['label' => 'Medical IT Providers', 'href' => 'medical-it-providers.php', 'active' => $currentPage === 'medical-it-providers.php'],
                    ['label' => 'Lecturers', 'href' => 'lecturers.php', 'active' => $currentPage === 'lecturers.php'],
                    ['label' => 'Driving Schools', 'href' => 'driving-schools.php', 'active' => $currentPage === 'driving-schools.php'],
                    ['label' => 'Driving School Instructors', 'href' => 'driving-school-instructors.php', 'active' => $currentPage === 'driving-school-instructors.php'],
                    ['label' => 'Drivers Education Center', 'href' => 'drivers-education-center.php', 'active' => $currentPage === 'drivers-education-center.php'],
                ],
            ],
            [
                'label' => 'Affiliates',
                'href' => 'affiliates.php',
                'active' => in_array($currentPage, ['affiliates.php', 'ltms-portal.php', 'cde-program.php', 'cde-online-exam.php', 'citisend.php'], true),
                'children' => [
                    ['label' => 'LTMS Portal', 'href' => 'ltms-portal.php', 'active' => $currentPage === 'ltms-portal.php'],
                    ['label' => 'CDE Program', 'href' => 'cde-program.php', 'active' => $currentPage === 'cde-program.php'],
                    ['label' => 'CDE Online Exam', 'href' => 'cde-online-exam.php', 'active' => $currentPage === 'cde-online-exam.php'],
                    ['label' => 'CitiSend', 'href' => 'citisend.php', 'active' => $currentPage === 'citisend.php'],
                ],
            ],
            [
                'label' => 'Resources',
                'href' => 'resources.php',
                'active' => in_array($currentPage, ['resources.php', 'downloadable-forms.php', 'hrds-forms.php'], true),
                'children' => [
                    ['label' => 'Downloadable Forms', 'href' => 'downloadable-forms.php', 'active' => $currentPage === 'downloadable-forms.php'],
                    ['label' => 'HRDS Forms', 'href' => 'hrds-forms.php', 'active' => $currentPage === 'hrds-forms.php'],
                ],
            ],
            ['label' => 'Mission and Vision', 'href' => 'mission-and-vision.php', 'active' => $currentPage === 'mission-and-vision.php'],
            ['label' => 'Mandate and Functions', 'href' => 'mandate-and-functions.php', 'active' => $currentPage === 'mandate-and-functions.php'],
            ['label' => 'Historical Background', 'href' => 'historical-background.php', 'active' => $currentPage === 'historical-background.php'],
            ['label' => 'Road Safety Action Plan', 'href' => 'road-safety-action-plan.php', 'active' => $currentPage === 'road-safety-action-plan.php'],
            ['label' => 'Data Privacy Notice', 'href' => 'data-privacy-notice.php', 'active' => $currentPage === 'data-privacy-notice.php'],
            ['label' => 'LTO Pila Office', 'href' => 'lto-pila-office.php', 'active' => $currentPage === 'lto-pila-office.php'],
        ],
    ],
    ['label' => 'Services', 'href' => 'employee-services.php', 'active' => $currentPage === 'employee-services.php', 'caret' => false],
    ['label' => 'Policies', 'href' => 'data-privacy-notice.php', 'active' => $currentPage === 'data-privacy-notice.php', 'caret' => false],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>LTO Pila Office | LTO HRIS</title>
    
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
<link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
</head>
<body class="landing-page public-page-<?php echo htmlspecialchars(pathinfo($currentPage, PATHINFO_FILENAME)); ?>">
    <div class="gov-topbar">
        <div class="gov-inner">
            <div class="gov-left">LTO-HRIS</div>
            <nav class="gov-nav">
                <?php foreach ($publicNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <div class="gov-nav-item has-dropdown">
                            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo trim(($item['active'] ? 'active ' : '') . ($item['caret'] ? 'has-caret' : '')); ?>">
                                <?php echo htmlspecialchars($item['label']); ?>
                            </a>
                            <div class="gov-dropdown">
                                <?php foreach ($item['children'] as $child): ?>
                                    <?php if (!empty($child['children'])): ?>
                                        <div class="gov-dropdown-item has-submenu">
                                            <a href="<?php echo htmlspecialchars($child['href'] ?? '#'); ?>" class="<?php echo !empty($child['active']) ? 'active' : ''; ?>">
                                                <span><?php echo htmlspecialchars($child['label']); ?></span>
                                                <span class="gov-submenu-caret" aria-hidden="true"></span>
                                            </a>
                                            <div class="gov-submenu">
                                                <?php foreach ($child['children'] as $grandChild): ?>
                                                    <a href="<?php echo htmlspecialchars($grandChild['href'] ?? '#'); ?>" class="<?php echo !empty($grandChild['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($grandChild['label']); ?></a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($child['href'] ?? '#'); ?>" class="<?php echo !empty($child['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo trim(($item['active'] ? 'active ' : '') . ($item['caret'] ? 'has-caret' : '')); ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <a href="#" class="gov-contact-link has-caret">Contact Us</a>
            <div class="gov-search"><input placeholder="Search..." aria-label="Search"></div>
            <div class="gov-actions">
                <a class="btn btn-login js-login-trigger" href="login.php">Login</a>
                <a class="btn btn-register js-register-trigger" href="register.php">Register</a>
            </div>
            <button class="gov-menu-toggle" type="button" aria-expanded="false" aria-controls="govMobilePanel" aria-label="Open navigation menu">
                <span class="gov-menu-toggle-label">Menu</span>
                <span class="gov-menu-toggle-box" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
            <div class="gov-mobile-search"><input placeholder="Search..." aria-label="Search"></div>
        </div>
        <div class="gov-mobile-panel" id="govMobilePanel" hidden>
            <nav class="gov-mobile-nav" aria-label="Mobile navigation">
                <?php foreach ($publicNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <div class="gov-mobile-group">
                            <button class="gov-mobile-parent" type="button" data-submenu="<?php echo htmlspecialchars($item['label']); ?>"><span><?php echo htmlspecialchars($item['label']); ?></span></button>
                            <div class="gov-mobile-submenu-view" data-submenu-panel="<?php echo htmlspecialchars($item['label']); ?>" hidden>
                                <button class="gov-mobile-back" type="button"><span class="gov-mobile-back-icon" aria-hidden="true"></span><span>Back</span></button>
                                <div class="gov-mobile-submenu-title"><?php echo htmlspecialchars($item['label']); ?></div>
                                <div class="gov-mobile-submenu">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <?php if (!empty($child['children'])): ?>
                                            <div class="gov-mobile-submenu-group">
                                                <a href="<?php echo htmlspecialchars($child['href'] ?? '#'); ?>" class="gov-mobile-submenu-group-title-link <?php echo !empty($child['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                                                <?php foreach ($child['children'] as $grandChild): ?>
                                                    <a href="<?php echo htmlspecialchars($grandChild['href'] ?? '#'); ?>" class="<?php echo !empty($grandChild['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($grandChild['label']); ?></a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($child['href'] ?? '#'); ?>" class="<?php echo !empty($child['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo !empty($item['active']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a href="#">Contact Us</a>
            </nav>
        </div>
    </div>

    <header class="site-header">
        <div class="container header-wrap">
            <div class="brand-group">
                <div class="left-logos">
                    <img src="assets/img/bph.png" alt="seal">
                    <img src="assets/img/lto_logo.png" alt="LTO logo">
                </div>
                <div class="center-brand">
                    <div class="govtext">Republic of the Philippines<br><strong>DEPARTMENT OF TRANSPORTATION</strong></div>
                    <h1>LAND TRANSPORTATION OFFICE</h1>
                </div>
            </div>
            <div class="right-info">
                <div class="time">Philippine Standard Time:<br><span id="pstClock"></span></div>
                <div class="icons">
                    <img src="assets/img/fip.png" alt="Freedom of Information">
                    <img src="assets/img/pts.png" alt="Philippine Transparency Seal">
                </div>
            </div>
        </div>
    </header>

    <section class="public-page"><div class="public-wrap"><div class="public-hero"><span class="public-kicker">District Profile</span><h2 class="public-title">LTO Pila Office</h2><p class="public-summary">Official district office information, service commitment, and public contact references.</p><div class="pub-layout"><article class="pub-panel"><h3>Office Profile</h3><ul class="public-list"><li>Address: Brgy. Sta. Clara Sur, Pila, Laguna</li><li>Office Contact: +63 49 250-1712</li><li>Hours: Monday to Friday, 8:00 AM to 5:00 PM</li></ul></article><article class="pub-panel"><h3>Service Commitment</h3><p>Provide timely, professional, and transparent front-line support aligned with LTO standards.</p></article></div></div></div></section>

    <?php include __DIR__ . '/includes/public-footer.php'; ?>

    <script>
        (function() {
            const topbar = document.querySelector('.gov-topbar');
            if (topbar) {
                const toggle = topbar.querySelector('.gov-menu-toggle');
                const panel = topbar.querySelector('.gov-mobile-panel');
                if (toggle && panel) {
                    let closeTimer = null;
                    const submenuPanels = Array.from(panel.querySelectorAll('[data-submenu-panel]'));
                    const submenuTriggers = Array.from(panel.querySelectorAll('[data-submenu]'));
                    const closeSubmenus = () => { submenuPanels.forEach((submenu) => { submenu.hidden = true; }); panel.classList.remove('submenu-open'); };
                    const closeMenu = () => {
                        if (!topbar.classList.contains('menu-open') && !topbar.classList.contains('menu-closing')) return;
                        closeSubmenus();
                        toggle.setAttribute('aria-expanded', 'false');
                        topbar.classList.remove('menu-open');
                        topbar.classList.add('menu-closing');
                        if (closeTimer) clearTimeout(closeTimer);
                        closeTimer = window.setTimeout(() => { topbar.classList.remove('menu-closing'); panel.hidden = true; closeTimer = null; }, 460);
                    };
                    const openMenu = () => {
                        if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
                        topbar.classList.remove('menu-closing');
                        panel.hidden = false;
                        topbar.classList.add('menu-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    };
                    toggle.addEventListener('click', () => topbar.classList.contains('menu-open') ? closeMenu() : openMenu());
                    document.addEventListener('click', (event) => { if (!topbar.classList.contains('menu-open')) return; if (topbar.contains(event.target)) return; closeMenu(); });
                    window.addEventListener('resize', () => { if (window.innerWidth > 1180) closeMenu(); });
                    panel.querySelectorAll('a').forEach((link) => { link.addEventListener('click', closeMenu); });
                    submenuTriggers.forEach((trigger) => {
                        trigger.addEventListener('click', () => {
                            const target = trigger.getAttribute('data-submenu');
                            closeSubmenus();
                            const submenu = panel.querySelector(`[data-submenu-panel="${target}"]`);
                            if (!submenu) return;
                            submenu.hidden = false;
                            panel.classList.add('submenu-open');
                        });
                    });
                    panel.querySelectorAll('.gov-mobile-back').forEach((button) => { button.addEventListener('click', closeSubmenus); });
                }
            }
        }());

        (function() {
            const clockEl = document.getElementById('pstClock');
            if (!clockEl) return;
            const formatter = new Intl.DateTimeFormat('en-PH', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila'
            });
            const updateClock = () => { clockEl.textContent = formatter.format(new Date()); };
            updateClock();
            setInterval(updateClock, 1000);
        }());
    </script>
</body>
</html>















