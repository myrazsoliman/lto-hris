<?php
require_once 'includes/auth.php';

$loginError = '';
$loginUsername = '';
$showLoginModal = isset($_GET['login']);
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

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $loginError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('login')) {
        $loginError = 'Too many login attempts. Please wait a few minutes before trying again.';
    } elseif ($loginUsername === '' || $password === '') {
        $loginError = 'Enter your username and password.';
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

    $showLoginModal = true;
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

// LTO-like homepage (structure matching provided image)
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$publicNavItems = [
    ['label' => 'Home', 'href' => 'index.php', 'active' => $currentPage === 'index.php', 'caret' => false],
    [
        'label' => 'About LTO',
        'href' => '#',
        'active' => false,
        'caret' => true,
        'children' => [
            ['label' => 'News and Updates'],
            ['label' => 'Mandate and Functions'],
            ['label' => 'Employee Services'],
            ['label' => 'Forms and Downloads'],
            ['label' => 'Data Privacy Notice'],
        ],
    ],
    ['label' => 'Issuances', 'href' => '#', 'active' => false, 'caret' => false],
    ['label' => 'Transparency Seal', 'href' => '#', 'active' => false, 'caret' => true],
];
$contactLink = ['label' => 'Contact Us', 'href' => '#', 'active' => false, 'caret' => true];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>LTO-HRIS</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/lto-style.css">
</head>

<body class="landing-page">

    <div class="gov-topbar">
        <div class="gov-inner">
            <div class="gov-left">GOVPH</div>
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
                                    <a href="#"><?php echo htmlspecialchars($child['label']); ?></a>
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
            <a
                href="<?php echo htmlspecialchars($contactLink['href']); ?>"
                class="<?php echo trim('gov-contact-link ' . ($contactLink['active'] ? 'active ' : '') . ($contactLink['caret'] ? 'has-caret' : '')); ?>">
                <?php echo htmlspecialchars($contactLink['label']); ?>
            </a>
            <div class="gov-search">
                <input placeholder="Search...">
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
                                        <a href="#"><?php echo htmlspecialchars($child['label']); ?></a>
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
                <a href="<?php echo htmlspecialchars($contactLink['href']); ?>"><?php echo htmlspecialchars($contactLink['label']); ?></a>
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

    <section class="news-updates">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">Latest Bulletin</span>
            </div>
            <div class="banner-carousel" id="bannerCarousel">
                <div class="banner-track">
                    <div class="banner-slide">
                        <img src="assets/img/s1.png" alt="LTO HRIS dashboard banner">
                    </div>
                    <div class="banner-slide">
                        <img src="assets/img/s2.png" alt="LTO HRIS digital records banner">
                    </div>
                    <div class="banner-slide">
                        <img src="assets/img/s3.png" alt="HRIS Employee Services banner">
                    </div>
                    <div class="banner-slide">
                        <img src="assets/img/s4.png" alt="HRIS Workforce Development banner">
                    </div>
                </div>

                <div class="banner-controls">
                    <div class="slide-counter"><span class="current">1</span> / <span class="total">4</span></div>
                    <button class="banner-pause" type="button" aria-label="Pause carousel">||</button>
                </div>

                <div class="banner-arrows" aria-hidden="false">
                    <button class="banner-arrow banner-arrow-prev" type="button" aria-label="Previous slide">
                        <span class="banner-arrow-icon" aria-hidden="true"></span>
                    </button>
                    <button class="banner-arrow banner-arrow-next" type="button" aria-label="Next slide">
                        <span class="banner-arrow-icon" aria-hidden="true"></span>
                    </button>
                </div>

                <div class="banner-dots">
                    <ul>
                        <li class="on" data-index="0"></li>
                        <li data-index="1"></li>
                        <li data-index="2"></li>
                        <li data-index="3"></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="vision-mission">
        <div class="vm-inner">
            <div class="vm-col">
                <h2>Vision</h2>
                <p>The Land Transportation Office envisions itself as a modern, efficient, and citizen-centered government agency that delivers safe, reliable, accessible, and sustainable land transportation services for all Filipinos, driven by professional excellence, digital innovation, transparent governance, and a deep commitment to public trust and national development.</p>
            </div>
            <div class="vm-col">
                <h2>Mission</h2>
                <p>To deliver responsive and innovative land transportation services through competent and values-driven personnel, streamlined and transparent processes, and the effective use of information systems and modern technology, while promoting road safety, operational accountability, organizational integrity, and greater convenience in serving employees and the riding public.</p>
            </div>
        </div>
    </section>

    <section class="hris-actions container">
        <div class="section-heading section-heading-compact">
            <span class="section-kicker">Quick Access</span>
        </div>
        <div class="hris-actions-grid">
            <a href="login.php" class="hris-action-card js-login-trigger">
                <span class="hris-action-eyebrow">Employee Workflow</span>
                <div class="hris-action-icon" aria-hidden="true">
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H15l5 5v8.5A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                        <path d="M15 4v5h5" stroke="currentColor" stroke-width="1.8" />
                        <path d="M8 13h8M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </div>
                <h3>Application</h3>
                <p>Submit HR requests, apply for available opportunities, and start your workflow in one secure portal.</p>
                <span class="hris-action-cta">Open service</span>
            </a>

            <a href="login.php" class="hris-action-card js-login-trigger">
                <span class="hris-action-eyebrow">Account Details</span>
                <div class="hris-action-icon" aria-hidden="true">
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.8" />
                        <circle cx="9" cy="11" r="2.2" stroke="currentColor" stroke-width="1.8" />
                        <path d="M6 17c.9-1.9 2.4-2.9 4.5-2.9S14.1 15.1 15 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M15.5 10h3M15.5 13h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </div>
                <h3>My Profile</h3>
                <p>Review personal information, employment details, and profile records.</p>
                <span class="hris-action-cta">Manage profile</span>
            </a>

            <a href="login.php" class="hris-action-card js-login-trigger">
                <span class="hris-action-eyebrow">Document Hub</span>
                <div class="hris-action-icon" aria-hidden="true">
                    <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.5 8.5A2.5 2.5 0 0 1 6 6h4l1.7 2H18a2.5 2.5 0 0 1 2.5 2.5v6A2.5 2.5 0 0 1 18 19H6A2.5 2.5 0 0 1 3.5 16.5v-8Z" stroke="currentColor" stroke-width="1.8" />
                        <path d="M7.5 12h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M7.5 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </div>
                <h3>My Files</h3>
                <p>Upload, manage, and track supporting documents, forms, and personnel requirements in one organized space.</p>
                <span class="hris-action-cta">View files</span>
            </a>
        </div>
    </section>

    <section class="privacy-notice-section container">
        <div class="privacy-notice-wrap">
            <h2>Privacy Notice for Employees and Job Applicants</h2>

            <div class="privacy-block">
                <h3>Introduction</h3>
                <p>In compliance with Republic Act No. 10173, or the Data Privacy Act of 2012, its Implementing Rules and Regulations, and other relevant issuances of the National Privacy Commission, the Land Transportation Office Human Resource Information System adopts this Privacy Notice to inform employees, job applicants, and authorized users how personal data is collected, used, stored, shared, and protected within the system.</p>

                <p>The LTO respects and upholds the right to data privacy of all personnel and applicants. Personal information collected through the HRIS is processed in accordance with the principles of transparency, legitimate purpose, and proportionality. This includes information submitted for recruitment, personnel administration, records management, leave processing, compliance monitoring, and other lawful human resource functions.</p>

                <p>In this notice, the terms “data” and “information” may be used interchangeably. References to “personal data” include personal information, sensitive personal information, and other information protected under applicable laws and regulations. This notice is intended to explain, in clear and practical language, how LTO HRIS handles your information in support of efficient, accountable, and secure HR service delivery.</p>
            </div>

            <div class="privacy-block">
                <h3>Information We Collect, Acquire, or Generate</h3>
                <p>The LTO HRIS may collect, receive, or generate personal data necessary for recruitment, employment administration, and organizational recordkeeping. This may include your full name, contact details, date of birth, civil status, government-issued identifiers, educational background, employment history, attendance records, leave data, payroll-related information, training records, performance evaluations, submitted forms, uploaded supporting documents, and system-generated logs associated with your use of the platform.</p>
            </div>

            <div class="privacy-block">
                <h3>How We Share, Disclose, or Transfer Your Information</h3>
                <p>Your information may be shared only when necessary for lawful HR functions, internal administrative processing, compliance with legal obligations, or coordination with authorized government offices, auditors, service providers, and oversight bodies. Any disclosure or transfer of personal data shall be limited to what is relevant and necessary, subject to appropriate safeguards, confidentiality controls, and applicable data privacy laws and regulations.</p>
            </div>

            <div class="privacy-block">
                <h3>How We Store and Retain Your Information</h3>
                <p>LTO HRIS stores personal data in secured digital environments protected by access controls, role-based permissions, system monitoring, and administrative safeguards designed to prevent unauthorized access, alteration, disclosure, or loss. Personal data shall be retained only for as long as necessary to fulfill legitimate HR, legal, regulatory, audit, and operational requirements, after which records shall be archived, anonymized, or securely disposed of in accordance with approved records management and retention policies.</p>
            </div>

            <div class="privacy-block">
                <h3>Your Rights with Respect to Your Personal Data</h3>
                <p>Subject to the Data Privacy Act of 2012 and other applicable regulations, you may have the right to be informed, to access your personal data, to object to certain processing activities, to request correction of inaccurate or incomplete records, to request erasure or blocking when allowed by law, and to seek redress for privacy-related concerns. Requests involving your personal data shall be evaluated in accordance with lawful procedures, operational requirements, and the rights and obligations of the Land Transportation Office as a government agency.</p>
            </div>

            <div class="privacy-block">
                <h3>Changing This Privacy Notice</h3>
                <p>The LTO may update or revise this Privacy Notice from time to time to reflect changes in laws, regulations, policies, business processes, or system functionalities. Any updated version shall take effect upon posting within the HRIS portal or through other official communication channels. Users are encouraged to review this notice periodically to remain informed about how their personal data is handled.</p>
            </div>
        </div>
    </section>

    <div class="login-modal<?php echo $showLoginModal ? ' is-open' : ''; ?>" id="loginModal" aria-hidden="<?php echo $showLoginModal ? 'false' : 'true'; ?>">
        <div class="login-modal-backdrop" data-close-login-modal></div>
        <div class="login-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
            <div class="login-card-modern login-card-modal">
                <div class="login-modal-header">
                    <div class="login-modal-header-copy">
                        <h2 id="loginModalTitle">Login</h2>
                    </div>
                    <button class="login-modal-close" type="button" data-close-login-modal aria-label="Close login modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="login-modal-body">
                    <?php if ($loginError !== ''): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>

                    <form class="login-form-modern login-form-modal" method="post" action="index.php">
                        <input type="hidden" name="form_action" value="login_modal">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="login-input-wrap login-input-wrap-modal">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                    <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                </svg>
                            </span>
                            <div class="login-input-shell">
                                <input type="text" id="loginModalUsername" name="username" placeholder=" " value="<?php echo htmlspecialchars($loginUsername); ?>" autocomplete="username" spellcheck="false" maxlength="80" required>
                                <label class="login-floating-label" for="loginModalUsername">Username</label>
                            </div>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                </svg>
                            </span>
                            <div class="login-input-shell">
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

                        <div class="login-modal-actions-row">
                            <a href="#" class="login-forgot-link">Forgot password?</a>
                        </div>

                        <button type="submit" class="login-submit-btn login-submit-btn-modal">
                            <span>Login</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="login-modal register-modal<?php echo $showRegisterModal ? ' is-open' : ''; ?>" id="registerModal" aria-hidden="<?php echo $showRegisterModal ? 'false' : 'true'; ?>">
        <div class="login-modal-backdrop" data-close-register-modal></div>
        <div class="login-modal-dialog register-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="registerModalTitle">
            <div class="login-card-modern login-card-modal register-card-modal">
                <div class="login-modal-header register-modal-header">
                    <div class="login-modal-header-copy">
                        <h2 id="registerModalTitle">Register</h2>
                    </div>
                    <button class="login-modal-close" type="button" data-close-register-modal aria-label="Close register modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="login-modal-body register-modal-body">
                    <?php if ($registerError !== ''): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($registerError); ?></div>
                    <?php endif; ?>

                    <?php if ($registerSuccess !== ''): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($registerSuccess); ?></div>
                    <?php endif; ?>

                    <form class="login-form-modern login-form-modal register-form-modal" method="post" action="index.php">
                        <input type="hidden" name="form_action" value="register_modal">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="register-honeypot" aria-hidden="true">
                            <label for="registerWebsite">Website</label>
                            <input type="text" id="registerWebsite" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                    <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                </svg>
                            </span>
                            <div class="login-input-shell">
                                <input type="text" id="registerModalFullName" name="full_name" placeholder=" " value="<?php echo htmlspecialchars($registerFullName); ?>" autocomplete="name" maxlength="160" required>
                                <label class="login-floating-label" for="registerModalFullName">Full Name</label>
                            </div>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                    <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="login-input-shell">
                                <input type="email" id="registerModalEmail" name="email" placeholder=" " value="<?php echo htmlspecialchars($registerEmail); ?>" autocomplete="email" maxlength="150" required>
                                <label class="login-floating-label" for="registerModalEmail">Official Email</label>
                            </div>
                        </div>

                        <div class="register-form-grid">
                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                        <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <div class="login-input-shell">
                                    <input type="text" id="registerModalUsername" name="register_username" placeholder=" " value="<?php echo htmlspecialchars($registerUsername); ?>" autocomplete="username" spellcheck="false" minlength="4" maxlength="32" pattern="[A-Za-z0-9._-]{4,32}" required>
                                    <label class="login-floating-label" for="registerModalUsername">Username</label>
                                </div>
                            </div>

                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <div class="login-input-shell">
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

                        <div class="login-modal-actions-row register-modal-actions-row">
                            <span class="register-modal-note">Account registration requests are subject to verification, evaluation, and approval by the authorized office prior to account activation.</span>
                        </div>

                        <button type="submit" class="login-submit-btn login-submit-btn-modal register-submit-btn-modal">
                            <span>Register</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer gov-footer">
        <div class="container footer-inner">
            <div class="footer-seal">
                <img src="assets/img/rph.png" alt="Republic of the Philippines">
            </div>
            <div class="footer-col">
                <h4>LTO - PILA DISTRICT OFFICE</h4>
                <p>Land Transportation Office (LTO)</p>
                <p>Brgy. Sta. Clara Sur, Pila, Laguna</p>
                <p><a href="tel:+63492501712">+63 49 250-1712</a></p>
            </div>
            <div class="footer-col">
                <h4>About GOVPH</h4>
                <p>Learn more about the Philippine government, its structure, how government works and the people behind it.</p>
                <ul class="flat-links">
                    <li><a href="https://portal.gov.ph/" target="_blank" rel="noopener noreferrer">GOV.PH</a></li>
                    <li><a href="https://open.gov.ph/" target="_blank" rel="noopener noreferrer">Open Data Portal</a></li>
                    <li><a href="https://www.officialgazette.gov.ph/" target="_blank" rel="noopener noreferrer">Official Gazette</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Government Links</h4>
                <ul class="gov-links">
                    <li><a href="https://op-proper.gov.ph/" target="_blank" rel="noopener noreferrer">Office of the President</a></li>
                    <li><a href="https://ovp.gov.ph/" target="_blank" rel="noopener noreferrer">Office of the Vice President</a></li>
                    <li><a href="https://senate.gov.ph/" target="_blank" rel="noopener noreferrer">Senate of the Philippines</a></li>
                    <li><a href="https://www.congress.gov.ph/" target="_blank" rel="noopener noreferrer">House of Representatives</a></li>
                    <li><a href="https://sc.judiciary.gov.ph/" target="_blank" rel="noopener noreferrer">Supreme Court</a></li>
                    <li><a href="https://ca.judiciary.gov.ph/" target="_blank" rel="noopener noreferrer">Court of Appeals</a></li>
                    <li><a href="https://sb.judiciary.gov.ph/" target="_blank" rel="noopener noreferrer">Sandiganbayan</a></li>
                </ul>
            </div>
        </div>
    </footer>

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
            const modalConfigs = [{
                    modal: document.getElementById('loginModal'),
                    triggerSelector: '.js-login-trigger',
                    closeSelector: '[data-close-login-modal]',
                    passwordFieldId: '#loginModalPassword',
                    passwordToggleId: '#loginPasswordToggle'
                },
                {
                    modal: document.getElementById('registerModal'),
                    triggerSelector: '.js-register-trigger',
                    closeSelector: '[data-close-register-modal]',
                    passwordFieldId: '#registerModalPassword',
                    passwordToggleId: '#registerPasswordToggle'
                }
            ].filter((config) => config.modal);

            if (!modalConfigs.length) return;

            const anyModalOpen = () => modalConfigs.some((config) => config.modal.classList.contains('is-open'));

            const syncBodyState = () => {
                document.body.classList.toggle('login-modal-open', anyModalOpen());
            };

            const closeAllModals = () => {
                modalConfigs.forEach((config) => {
                    config.modal.classList.remove('is-open');
                    config.modal.setAttribute('aria-hidden', 'true');
                });
                syncBodyState();
            };

            modalConfigs.forEach((config) => {
                const {
                    modal,
                    triggerSelector,
                    closeSelector,
                    passwordFieldId,
                    passwordToggleId
                } = config;
                const openTriggers = document.querySelectorAll(triggerSelector);
                const closeTriggers = modal.querySelectorAll(closeSelector);
                const passwordField = modal.querySelector(passwordFieldId);
                const passwordToggle = modal.querySelector(passwordToggleId);

                const openModal = () => {
                    closeAllModals();
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    syncBodyState();
                };

                const closeModal = () => {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    syncBodyState();
                };

                openTriggers.forEach((trigger) => {
                    trigger.addEventListener('click', (event) => {
                        event.preventDefault();
                        openModal();
                    });
                });

                closeTriggers.forEach((trigger) => {
                    trigger.addEventListener('click', closeModal);
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal || event.target.classList.contains('login-modal-backdrop')) {
                        closeModal();
                    }
                });

                if (passwordToggle && passwordField) {
                    passwordToggle.addEventListener('click', () => {
                        const reveal = passwordField.type === 'password';
                        passwordField.type = reveal ? 'text' : 'password';
                        passwordToggle.classList.toggle('is-active', reveal);
                        passwordToggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                        passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
                    });
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && anyModalOpen()) {
                    closeAllModals();
                }
            });

            syncBodyState();
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
            const el = document.getElementById('bannerCarousel');
            if (!el) return;

            const track = el.querySelector('.banner-track');
            const slides = Array.from(el.querySelectorAll('.banner-slide'));
            const dots = Array.from(el.querySelectorAll('.banner-dots li'));
            const curSpan = el.querySelector('.slide-counter .current');
            const totalSpan = el.querySelector('.slide-counter .total');
            const pauseBtn = el.querySelector('.banner-pause');
            const prevBtn = el.querySelector('.banner-arrow-prev');
            const nextBtn = el.querySelector('.banner-arrow-next');
            const basePixelRatio = window.devicePixelRatio || 1;

            let idx = 0;
            let timer = null;
            let paused = false;
            const interval = 4000;

            if (totalSpan) {
                totalSpan.textContent = String(slides.length);
            }

            const hideDuplicateCounters = () => {
                el.querySelectorAll('*').forEach((node) => {
                    if (node.closest('.slide-counter')) return;
                    const text = (node.textContent || '').trim();
                    if (/^\d+\s*of\s*\d+$/i.test(text) || /of\s*9/i.test(text)) {
                        node.style.display = 'none';
                    }
                });

                const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
                const textNodes = [];
                let currentNode;
                while ((currentNode = walker.nextNode())) {
                    textNodes.push(currentNode);
                }

                textNodes.forEach((textNode) => {
                    const parent = textNode.parentElement;
                    if (!parent || parent.closest('.slide-counter')) return;
                    const original = textNode.nodeValue || '';
                    const cleaned = original.replace(/\b\d+\s*of\s*9\b/gi, '').replace(/\b\d+\s*of\s*\d+\b/gi, '');
                    if (cleaned !== original) {
                        textNode.nodeValue = cleaned.trim() ? cleaned : '';
                    }
                });
            };

            const update = () => {
                track.style.transform = `translateX(-${idx * 100}%)`;
                if (curSpan) curSpan.textContent = String(idx + 1);
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('on', dotIndex === idx);
                });
            };

            const updateControlScale = () => {
                const viewportScale = window.visualViewport && typeof window.visualViewport.scale === 'number' ?
                    window.visualViewport.scale :
                    1;
                const ratioScale = (window.devicePixelRatio || 1) / basePixelRatio;
                const zoomLevel = Math.max(viewportScale, ratioScale, 1);
                const controlScale = Math.max(0.7, Math.min(1, 1 / zoomLevel));

                el.style.setProperty('--banner-control-scale', controlScale.toFixed(3));
            };

            const start = () => {
                if (timer) clearInterval(timer);
                timer = setInterval(() => {
                    idx = (idx + 1) % slides.length;
                    update();
                }, interval);
            };

            const stop = () => {
                if (!timer) return;
                clearInterval(timer);
                timer = null;
            };

            const goTo = (nextIndex) => {
                idx = (nextIndex + slides.length) % slides.length;
                update();
                stop();
                if (!paused) start();
            };

            dots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    goTo(Number(dot.dataset.index || 0));
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    goTo(idx - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    goTo(idx + 1);
                });
            }

            if (pauseBtn) {
                pauseBtn.addEventListener('click', () => {
                    paused = !paused;
                    if (paused) {
                        stop();
                        pauseBtn.textContent = '\u25BA';
                    } else {
                        start();
                        pauseBtn.textContent = '||';
                    }
                });
            }

            el.addEventListener('mouseenter', stop);
            el.addEventListener('mouseleave', () => {
                if (!paused) start();
            });

            hideDuplicateCounters();
            const observer = new MutationObserver(() => hideDuplicateCounters());
            observer.observe(el, {
                childList: true,
                subtree: true,
                characterData: true
            });

            updateControlScale();
            window.addEventListener('resize', updateControlScale);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', updateControlScale);
            }

            update();
            start();
        }());
    </script>

</body>

</html>