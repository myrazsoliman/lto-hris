<?php
require_once 'includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'forms-and-downloads.php');
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
    [
        'label' => 'Policies',
        'href' => '#',
        'active' => in_array(
            $currentPage,
            ['data-privacy-notice.php', 'terms-of-use.php', 'security.php', 'records-retention.php'],
            true
        ),
        'caret' => true,
        'children' => [
            ['label' => 'Data Privacy', 'href' => 'data-privacy-notice.php', 'active' => $currentPage === 'data-privacy-notice.php'],
            ['label' => 'Terms of Use', 'href' => 'terms-of-use.php', 'active' => $currentPage === 'terms-of-use.php'],
            ['label' => 'Security', 'href' => 'security.php', 'active' => $currentPage === 'security.php'],
            ['label' => 'Records Retention', 'href' => 'records-retention.php', 'active' => $currentPage === 'records-retention.php'],
        ],
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forms and Downloads | LTO HRIS</title>
    
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
<link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
</head>
<body class="landing-page">
    <?php include __DIR__ . '/includes/public-header.php'; ?>

    <section class="privacy-notice-section container">
        <div class="privacy-notice-wrap">
            <h2>Forms and Downloads</h2>
            <div class="privacy-block">
                <h3>Available Resources</h3>
                <p>This section provides quick access to common HR forms and downloadable templates used in employee records, personnel updates, and internal administrative transactions.</p>
            </div>
            <div class="privacy-block">
                <h3>Form Access Guidelines</h3>
                <p>Always use the latest official form versions and submit complete requirements to avoid delays in validation and approval.</p>
            </div>
            <div class="privacy-block">
                <h3>Quick Links</h3>
                <ul class="flat-links">
                    <li><a href="csc-forms.php">CSC Forms</a></li>
                    <li><a href="form-templates.php">Form Templates</a></li>
                    <li><a href="pds-document.php">PDS Document</a></li>
                    <li><a href="leave-request.php">Leave Request</a></li>
                </ul>
            </div>
        </div>
    </section>

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
                    const nestedGroups = Array.from(panel.querySelectorAll('.gov-mobile-submenu-group'));
                    const collapseNestedGroups = () => {
                        nestedGroups.forEach((group) => {
                            group.classList.remove('is-open');
                            const toggle = group.querySelector('.gov-mobile-submenu-toggle');
                            if (toggle) toggle.setAttribute('aria-expanded', 'false');
                            group.querySelectorAll(':scope > a:not(.gov-mobile-submenu-group-title-link)').forEach((link) => { link.hidden = true; });
                        });
                    };
                    const initializeNestedGroups = () => {
                        nestedGroups.forEach((group) => {
                            const titleLink = group.querySelector(':scope > .gov-mobile-submenu-group-title-link');
                            const childLinks = Array.from(group.querySelectorAll(':scope > a:not(.gov-mobile-submenu-group-title-link)'));
                            if (!titleLink || !childLinks.length) return;
                            group.classList.add('is-collapsible');
                            if (!group.querySelector('.gov-mobile-submenu-toggle')) {
                                const toggleBtn = document.createElement('button');
                                toggleBtn.type = 'button';
                                toggleBtn.className = 'gov-mobile-submenu-toggle';
                                toggleBtn.setAttribute('aria-expanded', 'false');
                                toggleBtn.setAttribute('aria-label', 'Show submenu for ' + (titleLink.textContent || 'group'));
                                titleLink.insertAdjacentElement('afterend', toggleBtn);
                                toggleBtn.addEventListener('click', () => {
                                    const willOpen = !group.classList.contains('is-open');
                                    nestedGroups.forEach((sibling) => {
                                        if (sibling === group) return;
                                        sibling.classList.remove('is-open');
                                        const siblingToggle = sibling.querySelector('.gov-mobile-submenu-toggle');
                                        if (siblingToggle) siblingToggle.setAttribute('aria-expanded', 'false');
                                        sibling.querySelectorAll(':scope > a:not(.gov-mobile-submenu-group-title-link)').forEach((link) => { link.hidden = true; });
                                    });
                                    group.classList.toggle('is-open', willOpen);
                                    toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                                    childLinks.forEach((link) => { link.hidden = !willOpen; });
                                });
                            }
                            childLinks.forEach((link) => { link.hidden = true; });
                        });
                    };
                    const closeSubmenus = () => { submenuPanels.forEach((submenu) => { submenu.hidden = true; }); panel.classList.remove('submenu-open'); collapseNestedGroups(); };
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
                    initializeNestedGroups();
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














