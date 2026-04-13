<?php
require_once 'includes/auth.php';
require_once 'includes/data.php';
require_once __DIR__ . '/includes/login-captcha.php';

$loginError = '';
$loginUsername = '';
$showLoginModal = isset($_GET['login']);
$showLoginCaptcha = true;
$loginCaptchaInput = '';
$loginCaptchaNonce = '';
$registerError = '';
$registerSuccess = '';
$registerFullName = '';
$registerEmail = '';
$registerUsername = '';
$showRegisterModal = isset($_GET['register']);
$csrfToken = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login_modal') {
    $loginUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $loginCaptchaInput = isset($_POST['captcha_answer']) ? trim((string) $_POST['captcha_answer']) : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $loginError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('login')) {
        $loginError = 'Too many login attempts. Please wait a few minutes before trying again.';
    } elseif ($loginUsername === '' || $password === '') {
        $loginError = 'Enter your username and password.';
    } elseif (!login_captcha_verify_answer($loginCaptchaInput)) {
        register_failed_attempt('login');
        $loginError = 'Security verification failed. Please answer the CAPTCHA correctly.';
    } else {
        $user = authenticate_user($loginUsername, $password);

        if ($user) {
            clear_failed_attempts('login');
            regenerate_csrf_token();
            login_user($user);
            header('Location: index.php');
            exit;
        }

        register_failed_attempt('login');
        $loginError = 'Invalid credentials or unauthorized account.';
    }

    // Always re-issue a fresh CAPTCHA after a POST so the next attempt has a new image.
    $loginCaptchaNonce = login_captcha_issue_challenge();
    $showLoginModal = true;
}

if ($showLoginCaptcha) {
    $loginCaptchaNonce = login_captcha_ensure_nonce();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register_modal') {
    $registerFullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $registerEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
    $registerUsername = isset($_POST['register_username']) ? trim($_POST['register_username']) : '';
    $password = isset($_POST['register_password']) ? (string) $_POST['register_password'] : '';
    $registerHoneypot = isset($_POST['website']) ? trim($_POST['website']) : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $registerError = 'Your session has expired. Please try again.';
    } elseif ($registerHoneypot !== '') {
        $registerError = 'Unable to submit registration request.';
    } elseif (is_rate_limited('register')) {
        $registerError = 'Too many registration attempts. Please wait a few minutes before trying again.';
    } elseif ($registerFullName === '' || $registerEmail === '' || $registerUsername === '' || $password === '') {
        $registerError = 'Please complete all registration fields.';
    } elseif (!filter_var($registerEmail, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{4,32}$/', $registerUsername)) {
        $registerError = 'Username must be 4 to 32 characters and use only letters, numbers, dots, underscores, or hyphens.';
    } else {
        $policyErrors = password_policy_errors($password, $registerUsername, $registerEmail);

        if ($policyErrors) {
            $registerError = $policyErrors[0];
        } elseif (fetch_user_record($registerEmail) || account_request_exists($registerEmail, $registerUsername)) {
            $registerError = 'An account or pending request already exists for this email or username.';
        } else {
            create_account_request($registerFullName, $registerEmail, $registerUsername, $password);
            clear_failed_attempts('register');
            regenerate_csrf_token();
            $registerSuccess = 'Registration request submitted for administrator review.';
            $registerFullName = '';
            $registerEmail = '';
            $registerUsername = '';
        }
    }

    if ($registerError !== '') {
        register_failed_attempt('register');
    } else {
        $showRegisterModal = true;
    }

    if ($registerError !== '' || $registerSuccess !== '') {
        $showRegisterModal = true;
    }
}

$pageTitle = 'Transparency Seal';
$agencyDepartment = 'DEPARTMENT OF TRANSPORTATION';
$agencyName = 'LAND TRANSPORTATION OFFICE';
$sealLabel = $pageTitle;
$publicDisclosureLabel = 'Public Disclosure';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'transparency-seal.php');
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

$sealSections = [
    [
        'title' => "I. AGENCY'S MANDATE, VISION, MISSION AND LIST OF OFFICIALS",
        'href' => 'https://lto.gov.ph/transparency-seal/',
    ],
    [
        'title' => 'II. ANNUAL FINANCIAL REPORTS',
        'href' => 'https://lto.gov.ph/transparency-seal/',
    ],
    [
        'title' => 'III. DBM APPROVED BUDGET AND TARGETS',
        'href' => 'https://www.dbm.gov.ph/index.php/about-the-transparency-seal',
    ],
    [
        'title' => 'IV. PROJECTS, PROGRAMS AND ACTIVITIES, BENEFICIARIES, AND STATUS OF IMPLEMENTATION',
        'href' => '#branch-notes',
    ],
    [
        'title' => 'V. ANNUAL PROCUREMENT PLAN',
        'href' => 'https://lto.gov.ph/transparency-seal/',
    ],
    [
        'title' => 'VI. PROCUREMENT MONITORING REPORT',
        'href' => 'https://lto.gov.ph/transparency-seal/',
    ],
    [
        'title' => 'VII. CITIZEN\'S CHARTER',
        'href' => 'https://lto.gov.ph/transparency-seal/',
    ],
    [
        'title' => 'VIII. SALN AVAILABILITY POLICY',
        'href' => '#saln-policy',
    ],
];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($systemName); ?></title>
    
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
<link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
</head>

<body class="landing-page">
    <div class="gov-topbar">
        <div class="gov-inner">
            <div class="gov-left">LTO HRIS</div>
            <nav class="gov-nav">
                <?php foreach ($publicNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <div class="gov-nav-item has-dropdown">
                            <a
                                href="<?php echo htmlspecialchars($item['href']); ?>"
                                class="<?php echo trim(($item['active'] ? 'active ' : '') . ($item['caret'] ? 'has-caret' : '')); ?>">
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
                        <a
                            href="<?php echo htmlspecialchars($item['href']); ?>"
                            class="<?php echo trim(($item['active'] ? 'active ' : '') . ($item['caret'] ? 'has-caret' : '')); ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <a href="contact-us.php" class="gov-contact-link <?php echo $currentPage === 'contact-us.php' ? 'active' : ''; ?>">Contact Us</a>
            <div class="gov-search">
                <input placeholder="Search..." aria-label="Search">
            </div>
            <div class="gov-actions">
                <a class="btn btn-login js-login-trigger" href="login.php">Login</a>
                <a class="btn btn-register js-register-trigger" href="register.php">Register</a>
            </div>
            <button class="gov-menu-toggle" type="button" aria-expanded="false" aria-controls="govMobilePanel" aria-label="Open navigation menu">
                <span class="gov-menu-toggle-label">Menu</span>
                <span class="gov-menu-toggle-box" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
            <div class="gov-mobile-search">
                <input placeholder="Search..." aria-label="Search">
            </div>
        </div>
        <div class="gov-mobile-panel" id="govMobilePanel" hidden>
            <nav class="gov-mobile-nav" aria-label="Mobile navigation">
                <?php foreach ($publicNavItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <div class="gov-mobile-group">
                            <button class="gov-mobile-parent" type="button" data-submenu="<?php echo htmlspecialchars($item['label']); ?>">
                                <span><?php echo htmlspecialchars($item['label']); ?></span>
                            </button>
                            <div class="gov-mobile-submenu-view" data-submenu-panel="<?php echo htmlspecialchars($item['label']); ?>" hidden>
                                <button class="gov-mobile-back" type="button">
                                    <span class="gov-mobile-back-icon" aria-hidden="true"></span>
                                    <span>Back</span>
                                </button>
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
                        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo !empty($item['active']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a href="contact-us.php" class="<?php echo $currentPage === 'contact-us.php' ? 'active' : ''; ?>">Contact Us</a>
            </nav>
            <div class="gov-mobile-actions">
                <a class="btn btn-login js-login-trigger" href="login.php">Login</a>
                <a class="btn btn-register js-register-trigger" href="register.php">Register</a>
            </div>
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
                    <div class="govtext">Republic of the Philippines<br><strong><?php echo htmlspecialchars($agencyDepartment); ?></strong></div>
                    <h1><?php echo htmlspecialchars($agencyName); ?></h1>
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

    <section class="transparency-page">
        <div class="container transparency-page-inner">
            <div class="transparency-hero-card">
                <div class="transparency-seal-mark">
                    <img src="assets/img/pts.png" alt="Transparency Seal">
                    <div class="transparency-seal-label"><?php echo htmlspecialchars($sealLabel); ?></div>
                </div>

                <div class="transparency-copy">
                    <span class="transparency-kicker"><?php echo htmlspecialchars($publicDisclosureLabel); ?></span>
                    <h2>Symbolism</h2>
                    <p>A pearl buried inside a tightly-shut shell is practically worthless. Government information is a pearl, meant to be shared with the public in order to maximize its inherent value.</p>
                    <p>The Transparency Seal, depicted by a pearl shining out of an open shell, is a symbol of a policy shift towards openness in access to government information. On the one hand, it hopes to inspire Filipinos in the civil service to be more open to citizen engagement; on the other, to invite the Filipino citizenry to exercise their right to participate in governance.</p>
                    <p>This initiative is envisioned as a step in the right direction towards solidifying the position of the Philippines as the Pearl of the Orient, a shining example for democratic virtue in the region.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="transparency-section-list container">
        <div class="transparency-inline-links">
            <?php foreach ($sealSections as $section): ?>
                <a href="<?php echo htmlspecialchars($section['href']); ?>" class="transparency-inline-link">
                    <span class="transparency-inline-plus" aria-hidden="true">+</span>
                    <span class="transparency-inline-text"><?php echo htmlspecialchars($section['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="transparency-design-panel-wrap container">
        <div class="transparency-design-panel">
            <section class="transparency-section transparency-section-panel" id="branch-notes">
                <div class="transparency-note transparency-note-plain">
                    <h3>Branch-Level Publication Note</h3>
                    <p>The Pila District Office should publish only records it actually owns or officially issues at branch level, such as local office profile details, local notices, district accomplishments, and branch-issued public advisories. Consolidated finance and procurement records may still point to official LTO regional or central disclosures when those records are not separately issued for the branch.</p>
                </div>
            </section>

            <section class="transparency-section transparency-note-wrap transparency-section-panel" id="saln-policy">
                <div class="transparency-note transparency-note-plain">
                    <h3>SALN and HRIS Data Notice</h3>
                    <p>Personnel files such as PDS, employee master records, leave records, payroll records, and private HR documents must remain within the secured HRIS environment and should not be published under the Transparency Seal. SALN access should be handled through the applicable request and disclosure policy, not through direct public posting of employee files.</p>
                </div>
            </section>
        </div>
    </section>

    <div class="login-modal<?php echo $showLoginModal ? ' is-open' : ''; ?>" id="loginModal" aria-hidden="<?php echo $showLoginModal ? 'false' : 'true'; ?>">
        <div class="login-modal-backdrop" data-close-login-modal></div>
        <div class="login-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
            <div class="login-card-modern login-card-modal login-card-modal-split">
                <div class="login-modal-visual">
                    <div class="login-modal-visual-overlay"></div>
                    <div class="login-modal-visual-content">
                        <img src="assets/img/lto_logo.png" alt="Land Transportation Office seal" class="login-modal-visual-logo">
                        <h2 class="login-modal-visual-title">LTO - HRIS</h2>
                        <p class="login-modal-visual-subtitle">Land Transportation Office</p>
                        <span class="login-modal-visual-kicker">Official Personnel Access Portal</span>
                    </div>
                </div>

                <div class="login-modal-panel">
                    <div class="login-modal-header login-modal-header-plain">
                        <div class="login-modal-header-copy">
                            <div class="login-modal-title-row">
                                <h2 id="loginModalTitle">Welcome to <span>LTO HRIS</span></h2>
                            </div>
                            <p class="login-modal-subtitle">Land Transportation Office<br>Human Resource Information System</p>
                        </div>
                        <button class="login-modal-close" type="button" data-close-login-modal aria-label="Close login modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="login-modal-body login-modal-body-plain">
                        <?php if ($loginError !== ''): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($loginError); ?></div>
                        <?php endif; ?>

                        <div class="login-modal-role-band">Authorized Access Only</div>

                        <form class="login-form-modern login-form-modal" method="post" action="transparency-seal.php">
                            <input type="hidden" name="form_action" value="login_modal">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                            <div class="login-input-wrap login-input-wrap-modal">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                            <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <input type="text" id="loginModalUsername" name="username" placeholder=" " value="<?php echo htmlspecialchars($loginUsername); ?>" autocomplete="username" spellcheck="false" maxlength="80" required>
                                    <label class="login-floating-label" for="loginModalUsername">Username</label>
                                </div>
                            </div>

                            <div class="login-input-wrap login-input-wrap-modal">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <input type="password" id="loginModalPassword" name="password" placeholder=" " autocomplete="current-password" minlength="12" maxlength="128" required>
                                    <label class="login-floating-label" for="loginModalPassword">Password</label>
                                </div>
                                <button type="button" class="login-password-toggle" id="loginPasswordToggle" aria-label="Show password" aria-pressed="false">
                                    <span class="login-password-icon login-password-icon-show" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <span class="login-password-icon login-password-icon-hide" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M3 4.3 19.7 21l1.4-1.4L4.4 2.9 3 4.3Zm9 3.7c5.5 0 9.9 3.6 11 7-.4 1.3-1.4 2.7-2.7 4l-1.4-1.4c.8-.8 1.4-1.7 1.8-2.6-1.1-2.4-4.4-5-8.7-5-.9 0-1.7.1-2.5.3L8 8.9c1.2-.6 2.6-.9 4-.9Zm0 3a4 4 0 0 1 4 4c0 .7-.2 1.4-.5 2l-5.5-5.5c.6-.3 1.3-.5 2-.5Zm-9 4c.9 2.7 4.1 5.6 8.5 6.1l-1.9-1.9c-2.8-.6-4.9-2.4-5.9-4.2.4-.9 1.1-1.8 2-2.6L4.2 11C3.6 11.6 3.2 12.3 3 13Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            <div class="login-forgot-under-password">
                                <a href="#" class="login-forgot-link">Forgot password?</a>
                            </div>

                            <div class="login-captcha-block">
                                <div class="login-captcha-hint-row">
                                    <div class="login-captcha-hint">What code is in the image?</div>
                                </div>
                                <div class="login-captcha-row">
                                    <div class="login-captcha-image-wrap">
                                        <div class="login-captcha-image-frame">
                                            <img
                                                src="captcha-image.php?context=login_modal&amp;v=<?php echo urlencode($loginCaptchaNonce); ?>"
                                                alt="CAPTCHA image"
                                                class="login-captcha-image"
                                                id="loginCaptchaImage"
                                            >
                                        </div>
                                        <button type="button" class="login-captcha-refresh" id="loginCaptchaRefresh" aria-label="Refresh captcha">
                                            <img src="assets/img/captcha-refresh.png" alt="" width="18" height="18" loading="lazy" decoding="async">
                                        </button>
                                    </div>

                                    <div class="login-input-wrap login-input-wrap-modal login-captcha-input-wrap">
                                        <div class="login-input-shell">
                                            <span class="login-input-icon" aria-hidden="true">
                                                <img src="assets/img/captcha-logo.png" alt="" width="26" height="26" loading="lazy" decoding="async">
                                            </span>
                                            <input type="text" id="loginModalCaptcha" name="captcha_answer" placeholder=" " value="<?php echo htmlspecialchars($loginCaptchaInput); ?>" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="5" required>
                                            <label class="login-floating-label" for="loginModalCaptcha">Enter CAPTCHA</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split">
                                <span>Login</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="login-modal register-modal<?php echo $showRegisterModal ? ' is-open' : ''; ?>" id="registerModal" aria-hidden="<?php echo $showRegisterModal ? 'false' : 'true'; ?>">
        <div class="login-modal-backdrop" data-close-register-modal></div>
        <div class="login-modal-dialog register-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="registerModalTitle">
            <div class="login-card-modern login-card-modal login-card-modal-split register-card-modal">
                <div class="login-modal-visual register-modal-visual">
                    <div class="login-modal-visual-overlay"></div>
                    <div class="login-modal-visual-content register-modal-visual-content">
                        <img src="assets/img/lto_logo.png" alt="Land Transportation Office seal" class="login-modal-visual-logo">
                        <h2 class="login-modal-visual-title">LTO - HRIS</h2>
                        <p class="login-modal-visual-subtitle">Land Transportation Office</p>
                        <span class="login-modal-visual-kicker">Official Personnel Access Portal</span>
                    </div>
                </div>

                <div class="login-modal-panel register-modal-panel">
                    <div class="login-modal-header login-modal-header-plain register-modal-header-plain">
                        <div class="login-modal-header-copy">
                            <div class="login-modal-title-row">
                                <h2 id="registerModalTitle">Create <span>Account</span></h2>
                            </div>
                            <p class="login-modal-subtitle">Land Transportation Office<br>Human Resource Information System</p>
                        </div>
                        <button class="login-modal-close" type="button" data-close-register-modal aria-label="Close register modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="login-modal-body login-modal-body-plain register-modal-body">
                        <?php if ($registerError !== ''): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($registerError); ?></div>
                        <?php endif; ?>

                        <?php if ($registerSuccess !== ''): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($registerSuccess); ?></div>
                        <?php endif; ?>

                        <form class="login-form-modern login-form-modal register-form-modal" method="post" action="transparency-seal.php">
                            <input type="hidden" name="form_action" value="register_modal">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <div class="register-honeypot" aria-hidden="true">
                                <label for="registerWebsite">Website</label>
                                <input type="text" id="registerWebsite" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                            <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                        </svg>
                                    </span>
                                    <input type="text" id="registerModalFullName" name="full_name" placeholder=" " value="<?php echo htmlspecialchars($registerFullName); ?>" autocomplete="name" maxlength="160" required>
                                    <label class="login-floating-label" for="registerModalFullName">Full Name</label>
                                </div>
                            </div>

                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                            <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <input type="email" id="registerModalEmail" name="email" placeholder=" " value="<?php echo htmlspecialchars($registerEmail); ?>" autocomplete="email" maxlength="150" required>
                                    <label class="login-floating-label" for="registerModalEmail">Email</label>
                                </div>
                            </div>

                            <div class="register-form-grid">
                                <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                    <div class="login-input-shell">
                                        <span class="login-input-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                                <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                        <input type="text" id="registerModalUsername" name="register_username" placeholder=" " value="<?php echo htmlspecialchars($registerUsername); ?>" autocomplete="username" spellcheck="false" minlength="4" maxlength="32" pattern="[A-Za-z0-9._-]{4,32}" required>
                                        <label class="login-floating-label" for="registerModalUsername">Username</label>
                                    </div>
                                </div>

                                <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                    <div class="login-input-shell">
                                        <span class="login-input-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                        <input type="password" id="registerModalPassword" name="register_password" placeholder=" " autocomplete="new-password" minlength="12" maxlength="128" required>
                                        <label class="login-floating-label" for="registerModalPassword">Password</label>
                                    </div>
                                    <button type="button" class="login-password-toggle" id="registerPasswordToggle" aria-label="Show password" aria-pressed="false">
                                        <span class="login-password-icon login-password-icon-show" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                        <span class="login-password-icon login-password-icon-hide" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M3 4.3 19.7 21l1.4-1.4L4.4 2.9 3 4.3Zm9 3.7c5.5 0 9.9 3.6 11 7-.4 1.3-1.4 2.7-2.7 4l-1.4-1.4c.8-.8 1.4-1.7 1.8-2.6-1.1-2.4-4.4-5-8.7-5-.9 0-1.7.1-2.5.3L8 8.9c1.2-.6 2.6-.9 4-.9Zm0 3a4 4 0 0 1 4 4c0 .7-.2 1.4-.5 2l-5.5-5.5c.6-.3 1.3-.5 2-.5Zm-9 4c.9 2.7 4.1 5.6 8.5 6.1l-1.9-1.9c-2.8-.6-4.9-2.4-5.9-4.2.4-.9 1.1-1.8 2-2.6L4.2 11C3.6 11.6 3.2 12.3 3 13Z" fill="currentColor" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="register-login-row">
                                <span>Already have an account?</span>
                                <a href="login.php" class="register-login-link js-login-trigger">Login</a>
                            </div>

                            <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split register-submit-btn-modal">
                                <span>Register</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

                    const closeSubmenus = () => {
                        submenuPanels.forEach((submenu) => {
                            submenu.hidden = true;
                        });
                        panel.classList.remove('submenu-open');
                    };

                    const closeMenu = () => {
                        if (!topbar.classList.contains('menu-open') && !topbar.classList.contains('menu-closing')) return;
                        closeSubmenus();
                        toggle.setAttribute('aria-expanded', 'false');
                        topbar.classList.remove('menu-open');
                        topbar.classList.add('menu-closing');
                        if (closeTimer) clearTimeout(closeTimer);
                        closeTimer = window.setTimeout(() => {
                            topbar.classList.remove('menu-closing');
                            panel.hidden = true;
                            closeTimer = null;
                        }, 460);
                    };

                    const openMenu = () => {
                        if (closeTimer) {
                            clearTimeout(closeTimer);
                            closeTimer = null;
                        }
                        topbar.classList.remove('menu-closing');
                        panel.hidden = false;
                        topbar.classList.add('menu-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    };

                    toggle.addEventListener('click', () => {
                        if (topbar.classList.contains('menu-open')) {
                            closeMenu();
                        } else {
                            openMenu();
                        }
                    });

                    document.addEventListener('click', (event) => {
                        if (!topbar.classList.contains('menu-open')) return;
                        if (topbar.contains(event.target)) return;
                        closeMenu();
                    });

                    window.addEventListener('resize', () => {
                        if (window.innerWidth > 1180) {
                            closeMenu();
                        }
                    });

                    panel.querySelectorAll('a').forEach((link) => {
                        link.addEventListener('click', closeMenu);
                    });

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

                    panel.querySelectorAll('.gov-mobile-back').forEach((button) => {
                        button.addEventListener('click', closeSubmenus);
                    });
                }
            }
        }());

        (function() {
            const clockEl = document.getElementById('pstClock');
            if (!clockEl) return;

            const formatter = new Intl.DateTimeFormat('en-PH', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            });

            const updateClock = () => {
                clockEl.textContent = formatter.format(new Date());
            };

            updateClock();
            setInterval(updateClock, 1000);
        }());

        (function() {
            const captchaRefreshBtn = document.getElementById('loginCaptchaRefresh');
            const captchaImage = document.getElementById('loginCaptchaImage');
            const captchaField = document.getElementById('loginModalCaptcha');
            if (!captchaRefreshBtn || !captchaImage) return;

            if (captchaField) {
                captchaField.addEventListener('input', function() {
                    this.value = this.value.replace(/[^A-Za-z0-9]/g, '').slice(0, 5);
                });
            }

            captchaRefreshBtn.addEventListener('click', function() {
                captchaImage.src = 'captcha-image.php?context=login_modal&regen=1&t=' + Date.now();
                if (captchaField) {
                    captchaField.value = '';
                    captchaField.focus();
                }
            });
        }());
    </script>
</body>

</html>












