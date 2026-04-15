<?php
require_once 'includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'contact-us.php');
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
<title>Contact Us | LTO HRIS</title>
    
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
<link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
</head>
<body class="landing-page public-page-<?php echo htmlspecialchars(pathinfo($currentPage, PATHINFO_FILENAME)); ?>">
    <?php include __DIR__ . '/includes/public-header.php'; ?>

    <section class="public-page contact-gov-page">
        <div class="public-wrap">
            <div class="public-hero contact-gov-hero">
                <span class="public-kicker">Official Public Assistance</span>
                <h2 class="public-title">Contact Us</h2>
                <p class="public-summary">For official public assistance, inquiry routing, and service coordination, please contact the LTO Pila District Office through the channels below.</p>

                <div class="contact-gov-grid contact-gov-grid-main">
                    <section class="contact-gov-section contact-gov-info">
                        <h3>Contact Us</h3>
                        <p class="contact-gov-intro">Feel free to use the form or send us an official email. You may also contact the district office by phone during office hours.</p>
                        <ul class="contact-gov-contact-list">
                            <li>
                                <span class="contact-gov-icon contact-gov-icon-phone" aria-hidden="true"></span>
                                <span><a href="tel:+63492501712">+63 49 250-1712</a></span>
                            </li>
                            <li>
                                <span class="contact-gov-icon contact-gov-icon-mail" aria-hidden="true"></span>
                                <span><a href="mailto:lto.pila@lto.gov.ph">lto.pila@lto.gov.ph</a></span>
                            </li>
                            <li>
                                <span class="contact-gov-icon contact-gov-icon-pin" aria-hidden="true"></span>
                                <span>Brgy. Sta. Clara Sur, Pila, Laguna, Philippines</span>
                            </li>
                            <li>
                                <span class="contact-gov-icon contact-gov-icon-time" aria-hidden="true"></span>
                                <span>Monday to Friday, 8:00 AM to 5:00 PM</span>
                            </li>
                        </ul>
                        <div class="contact-gov-mini-block">
                            <h4>Expected Response</h4>
                            <ul class="contact-gov-required-list">
                                <li>Initial acknowledgement is typically issued within 1-2 working days, subject to request volume and validation.</li>
                            </ul>
                        </div>
                        <div class="contact-gov-mini-block">
                            <h4>Required Details Before Sending</h4>
                            <ul class="contact-gov-required-list">
                                <li>Full Name</li>
                                <li>Subject of Inquiry</li>
                                <li>Reference Number (for follow-up requests)</li>
                            </ul>
                        </div>
                        <p class="contact-gov-note">Submitted contact details and inquiry records are handled through official channels under applicable data privacy and policies.</p>
                    </section>

                    <section class="contact-gov-section contact-gov-form-section">
                        <h3>Public Inquiry Form</h3>
                        <form class="contact-form" action="#" method="post" novalidate>
                            <div class="contact-form-grid">
                                <label>
                                    <span>First Name *</span>
                                    <input type="text" placeholder="Juan">
                                </label>
                                <label>
                                    <span>Last Name *</span>
                                    <input type="text" placeholder="Dela Cruz">
                                </label>
                            </div>
                            <label>
                                <span>Email Address *</span>
                                <input type="email" placeholder="name@example.com">
                            </label>
                            <label>
                                <span>Phone (Optional)</span>
                                <input type="text" placeholder="09XX-XXX-XXXX">
                            </label>
                            <label>
                                <span>Subject *</span>
                                <input type="text" placeholder="Inquiry subject">
                            </label>
                            <label>
                                <span>Message *</span>
                                <textarea rows="5" placeholder="Type your concern here..."></textarea>
                            </label>
                            <p class="contact-form-note">Submitted inquiries are processed through official LTO channels in accordance with applicable data privacy and records policies.</p>
                            <button type="button" class="contact-submit-btn">Submit Official Inquiry</button>
                        </form>
                    </section>
                </div>

                <div class="contact-gov-grid">
                    <section class="contact-gov-section contact-gov-wide">
                        <h3>Service Channels and Response Timeline</h3>
                        <div class="contact-gov-bottom-grid">
                            <ul class="contact-channel-list contact-channel-list-enhanced">
                                <li class="channel-item channel-item-general"><strong>General Inquiries:</strong> Please use the Office Contact Directory for official communication.</li>
                                <li class="channel-item channel-item-followup"><strong>Document Follow-Up:</strong> Provide your reference number and transaction details when calling.</li>
                                <li class="channel-item channel-item-onsite"><strong>On-site Assistance:</strong> Walk-in concerns are accommodated during posted office hours.</li>
                            </ul>
                            <div class="contact-timeline-cards">
                                <div class="contact-timeline-card timeline-item timeline-item-ack">
                                    <h4>1-2 Working Days</h4>
                                    <p>Email acknowledgment and initial routing confirmation.</p>
                                </div>
                                <div class="contact-timeline-card timeline-item timeline-item-review">
                                    <h4>3-5 Working Days</h4>
                                    <p>Preliminary review and coordination by concerned office unit.</p>
                                </div>
                                <div class="contact-timeline-card timeline-item timeline-item-resolution">
                                    <h4>As Needed</h4>
                                    <p>Final resolution timeline based on case requirements and validation.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
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




















