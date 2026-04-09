<?php
require_once 'includes/auth.php';

$loginError = '';
$loginEmail = '';
$showLoginModal = isset($_GET['login']) || isset($_GET['registration_success']);
$registerError = '';
$registerSuccess = '';
$registrationSuccess = isset($_GET['registration_success']);
$registerFirstName = '';
$registerMiddleName = '';
$registerLastName = '';
$registerEmail = '';
$showRegisterModal = isset($_GET['register']);
$csrfToken = csrf_token();
$show2faModal = isset($_GET['2fa']) || isset($_SESSION['pending_2fa']);
$twoFaError = '';
$twoFaSuccess = '';
$twoFaCodePrefill = '';

function redirect_to_dashboard_by_roles($roles)
{
    $normalizedRoles = array_map(
        static fn($role) => strtolower(trim((string) $role)),
        (array) $roles
    );

    if (in_array('superadmin', $normalizedRoles, true)) {
        header('Location: superadmin-dashboard.php');
        exit;
    }

    if (in_array('admin', $normalizedRoles, true) || in_array('hr_officer', $normalizedRoles, true)) {
        header('Location: admin-dashboard.php');
        exit;
    }

    header('Location: employee-dashboard.php');
    exit;
}

if (isset($_GET['cancel_2fa'])) {
    unset($_SESSION['pending_2fa']);
    unset($_SESSION['flash_2fa_code']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'verify_2fa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $twoFaError = 'Your session has expired. Please try again.';
    } else {
        $code = trim((string) ($_POST['otp_code'] ?? ''));
        $twoFaCodePrefill = preg_replace('/\D+/', '', $code);
        [$ok, $pendingUser] = verify_2fa_code($code);
        if ($ok && is_array($pendingUser)) {
            unset($_SESSION['pending_2fa']);
            unset($_SESSION['flash_2fa_code']);
            regenerate_csrf_token();

            $remember = ($_POST['remember_device'] ?? '') === '1';
            if ($remember) {
                remember_trusted_device($pendingUser['id'] ?? 0, 'Trusted device');
            }
            login_user($pendingUser);
            redirect_to_dashboard_by_roles($pendingUser['roles'] ?? []);
        }

        $twoFaError = (string) ($pendingUser ?: 'Invalid verification code.');
    }

    $show2faModal = true;
}

$twoFaCodePrefill = substr(preg_replace('/\D+/', '', $twoFaCodePrefill), 0, 6);
$twoFaDigits = str_split($twoFaCodePrefill);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'resend_2fa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $twoFaError = 'Your session has expired. Please try again.';
    } else {
        $pending = $_SESSION['pending_2fa']['user'] ?? null;
        if (is_array($pending)) {
            start_2fa_challenge($pending);
            $twoFaSuccess = 'A new verification code was sent.';
        } else {
            $twoFaError = 'No verification is pending.';
        }
    }
    $show2faModal = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'login_modal') {
    $loginEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $loginError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('login')) {
        $loginError = 'Too many login attempts. Please wait a few minutes before trying again.';
    } elseif ($loginEmail === '' || $password === '') {
        $loginError = 'Enter your email and password.';
    } else {
        $user = authenticate_user($loginEmail, $password);

        if ($user) {
            clear_failed_attempts('login');
            regenerate_csrf_token();

            $roles = $user['roles'] ?? [];
            login_user($user);
            redirect_to_dashboard_by_roles($roles);
        }

        register_failed_attempt('login');
        log_auth_event('login_failed', null, $loginEmail);
        
        // Check if the email exists to provide more specific error messages
        $existingUser = fetch_user_record($loginEmail);
        if ($existingUser) {
            $loginError = 'The password you entered is incorrect. Please try again.';
        } else {
            $loginError = 'No account found with this email address. Please check your email or register for a new account.';
        }
    }

    $showLoginModal = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register_modal') {
    $registerFirstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $registerMiddleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $registerLastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $registerEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['register_password']) ? (string) $_POST['register_password'] : '';
    $confirmPassword = isset($_POST['register_confirm_password']) ? (string) $_POST['register_confirm_password'] : '';
    $registerHoneypot = isset($_POST['website']) ? trim($_POST['website']) : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $registerError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('register')) {
        $registerError = 'Too many registration attempts. Please wait a few minutes before trying again.';
    } elseif ($registerFirstName === '' || $registerLastName === '' || $registerEmail === '' || $password === '' || $confirmPassword === '') {
        $registerError = 'Please complete all required registration fields.';
    } elseif (!filter_var($registerEmail, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Password and confirm password do not match.';
    } else {
        $policyErrors = password_policy_errors($password, '', $registerEmail);

        if ($policyErrors) {
            $registerError = $policyErrors[0];
        } elseif (user_exists($registerEmail)) {
            $registerError = 'An account with this email already exists.';
        } else {
            // Create user directly
            $userId = create_user_directly($registerFirstName, $registerMiddleName, $registerLastName, $registerEmail, $password);
            
            if ($userId) {
                clear_failed_attempts('register');
                regenerate_csrf_token();

                // After successful registration, proceed directly to verification modal.
                $newUser = fetch_user_record($registerEmail);
                if ($newUser && is_array($newUser)) {
                    start_2fa_challenge($newUser);
                    header('Location: index.php?2fa=1');
                } else {
                    header('Location: index.php?login=1&registration_success=1');
                }
                exit;
            } else {
                $registerError = 'Registration failed. Please try again.';
            }
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

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$publicNavItems = [
    ['label' => 'Home', 'href' => 'index.php', 'active' => $currentPage === 'index.php', 'caret' => false],
    [
        'label' => 'About Us',
        'href' => '#',
        'active' => false,
        'caret' => true,
        'children' => [
            ['label' => 'LTO Pila Office'],
            ['label' => 'Employee Services'],
            ['label' => 'Forms and Downloads'],
            ['label' => 'Data Privacy Notice'],
        ],
    ],
    ['label' => 'Announcements', 'href' => '#', 'active' => false, 'caret' => false],
    ['label' => 'Transparency Seal', 'href' => 'transparency-seal.php', 'active' => $currentPage === 'transparency-seal.php', 'caret' => false],
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
    <style>
        #twoFaModal .verification-form .verification-otp-row {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
        }
        #twoFaModal .verification-form .verification-otp-row > .verification-otp-digit {
            display: inline-block !important;
            flex: 0 0 42px !important;
            width: 42px !important;
            min-width: 42px !important;
            max-width: 42px !important;
            height: 58px !important;
            min-height: 58px !important;
            max-height: 58px !important;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 8px !important;
            text-align: center !important;
            font-size: 36px !important;
        }
        #twoFaModal .verification-form .verification-remember-device {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        #twoFaModal .verification-form .verification-remember-checkbox[type="checkbox"] {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
</head>

<body class="landing-page<?php echo $show2faModal ? ' login-modal-open' : ''; ?>">

    <div class="gov-topbar">
        <div class="gov-inner">
            <div class="gov-left">LTO-HRIS</div>
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

                        <?php if ($registrationSuccess): ?>
                            <div class="alert alert-success">
                                Registration successful! You can now login with your email and password.
                            </div>
                        <?php endif; ?>

                        <div class="login-modal-role-band">Authorized Access Only</div>

                        <form class="login-form-modern login-form-modal" method="post" action="index.php" novalidate>
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
                                <input type="email" id="loginModalEmail" name="email" placeholder=" " value="<?php echo htmlspecialchars($loginEmail ?? ''); ?>" autocomplete="email" spellcheck="false" maxlength="150" required>
                                <label class="login-floating-label" for="loginModalEmail">Email</label>
                            </div>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <input type="password" id="loginModalPassword" name="password" placeholder=" " autocomplete="current-password" maxlength="128" required>
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
                                        <path d="M2.6 3.9 20.1 21.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M10.6 6.3A11.2 11.2 0 0 1 12 6.2c5.3 0 9.6 3.3 10.8 5.8-.5 1.1-1.5 2.6-3 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7.2 7.4C4.8 8.5 3 10.4 1.9 12c1.3 2.7 5.5 5.8 10.1 5.8 1.6 0 3.2-.4 4.6-1.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.9 9.1a4 4 0 0 1 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                            <div class="login-modal-actions-row login-modal-actions-row-split">
                                <a href="#" class="login-forgot-link">Forgot password?</a>
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

    <div class="login-modal verification-modal<?php echo $show2faModal ? ' is-open' : ''; ?>" id="twoFaModal" aria-hidden="<?php echo $show2faModal ? 'false' : 'true'; ?>">
        <a class="login-modal-backdrop" href="index.php?cancel_2fa=1" aria-label="Cancel verification"></a>
        <div class="login-modal-dialog verification-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="twoFaModalTitle">
            <div class="login-card-modern login-card-modal login-card-modal-split verification-card-modal">
                <div class="login-modal-visual verification-modal-visual">
                    <div class="login-modal-visual-overlay"></div>
                    <div class="login-modal-visual-content verification-modal-visual-content">
                        <img src="assets/img/lto_logo.png" alt="Land Transportation Office seal" class="login-modal-visual-logo">
                        <h2 class="login-modal-visual-title">LTO - HRIS</h2>
                        <p class="login-modal-visual-subtitle">Land Transportation Office</p>
                        <span class="login-modal-visual-kicker">Secure Verification Required</span>
                    </div>
                </div>

                <div class="login-modal-panel verification-modal-panel">
                    <div class="login-modal-header login-modal-header-plain verification-modal-header-plain">
                        <div class="login-modal-header-copy">
                            <div class="login-modal-title-row">
                                <h2>Secure <span>Sign-In</span></h2>
                            </div>
                            <p class="login-modal-subtitle">Enter your 6-digit authentication code to continue.</p>
                        </div>
                        <a class="login-modal-close" href="index.php?cancel_2fa=1" aria-label="Cancel verification">
                            <span aria-hidden="true">&times;</span>
                        </a>
                    </div>

                    <div class="login-modal-body login-modal-body-plain verification-modal-body">
                        <?php if ($twoFaError !== ''): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($twoFaError); ?></div>
                        <?php endif; ?>

                        <?php if ($twoFaSuccess !== ''): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($twoFaSuccess); ?></div>
                        <?php endif; ?>

                        <div class="login-modal-role-band verification-modal-role-band">Two-Factor Authentication</div>

                        <form class="login-form-modern login-form-modal verification-form" method="post" action="index.php?2fa=1" novalidate data-verification-form>
                            <input type="hidden" id="verificationAction" name="form_action" value="<?php echo strlen($twoFaCodePrefill) === 6 ? 'verify_2fa' : 'resend_2fa'; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" id="otpCode" name="otp_code" value="<?php echo htmlspecialchars($twoFaCodePrefill); ?>">

                            <div class="verification-hero-icon<?php echo strlen($twoFaCodePrefill) === 6 ? ' is-ready' : ''; ?>" id="verificationHeroIcon" aria-hidden="true">
                                <img src="assets/img/email-sent.png" alt="Email verification sent" class="verification-hero-image">
                            </div>

                            <div class="verification-otp-row" role="group" aria-label="6-digit verification code">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" value="<?php echo htmlspecialchars($twoFaDigits[0] ?? ''); ?>" aria-label="Verification digit 1">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" value="<?php echo htmlspecialchars($twoFaDigits[1] ?? ''); ?>" aria-label="Verification digit 2">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" value="<?php echo htmlspecialchars($twoFaDigits[2] ?? ''); ?>" aria-label="Verification digit 3">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" value="<?php echo htmlspecialchars($twoFaDigits[3] ?? ''); ?>" aria-label="Verification digit 4">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" value="<?php echo htmlspecialchars($twoFaDigits[4] ?? ''); ?>" aria-label="Verification digit 5">
                                <input class="verification-otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" value="<?php echo htmlspecialchars($twoFaDigits[5] ?? ''); ?>" aria-label="Verification digit 6">
                                <span class="verification-otp-ready<?php echo strlen($twoFaCodePrefill) === 6 ? ' is-visible' : ''; ?>" id="verificationReadyMark" aria-hidden="true">&#10003;</span>
                            </div>

                            <h3 id="twoFaModalTitle" class="verification-title">Verification Code</h3>
                            <p class="verification-description">Enter the 6-digit code we sent to your email to continue sign in.</p>

                            <label class="verification-remember-device" for="rememberDevice">
                                <input class="verification-remember-checkbox" id="rememberDevice" type="checkbox" name="remember_device" value="1" <?php echo (($_POST['remember_device'] ?? '') === '1') ? 'checked' : ''; ?>>
                                <span class="verification-remember-text">Remember this device for 30 days</span>
                            </label>

                            <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split verification-primary-btn" id="verificationPrimaryButton">
                                <span><?php echo strlen($twoFaCodePrefill) === 6 ? 'Confirm Code' : 'Resend'; ?></span>
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

                        <form class="login-form-modern login-form-modal register-form-modal" method="post" action="index.php">
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
                                <input type="text" id="registerModalFirstName" name="first_name" placeholder=" " value="<?php echo htmlspecialchars($registerFirstName ?? ''); ?>" autocomplete="given-name" maxlength="80" required>
                                <label class="login-floating-label" for="registerModalFirstName">First Name</label>
                            </div>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                        <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <input type="text" id="registerModalMiddleName" name="middle_name" placeholder=" " value="<?php echo htmlspecialchars($registerMiddleName ?? ''); ?>" autocomplete="additional-name" maxlength="80">
                                <label class="login-floating-label" for="registerModalMiddleName">Middle Name</label>
                            </div>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" />
                                        <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <input type="text" id="registerModalLastName" name="last_name" placeholder=" " value="<?php echo htmlspecialchars($registerLastName ?? ''); ?>" autocomplete="family-name" maxlength="80" required>
                                <label class="login-floating-label" for="registerModalLastName">Last Name</label>
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


                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <input type="password" id="registerModalPassword" name="register_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" required>
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
                                        <path d="M2.6 3.9 20.1 21.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M10.6 6.3A11.2 11.2 0 0 1 12 6.2c5.3 0 9.6 3.3 10.8 5.8-.5 1.1-1.5 2.6-3 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7.2 7.4C4.8 8.5 3 10.4 1.9 12c1.3 2.7 5.5 5.8 10.1 5.8 1.6 0 3.2-.4 4.6-1.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.9 9.1a4 4 0 0 1 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <input type="password" id="registerModalConfirmPassword" name="register_confirm_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" required>
                                <label class="login-floating-label" for="registerModalConfirmPassword">Confirm Password</label>
                            </div>
                            <button type="button" class="login-password-toggle" id="registerConfirmPasswordToggle" aria-label="Show password" aria-pressed="false">
                                <span class="login-password-icon login-password-icon-show" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="login-password-icon login-password-icon-hide" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M2.6 3.9 20.1 21.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M10.6 6.3A11.2 11.2 0 0 1 12 6.2c5.3 0 9.6 3.3 10.8 5.8-.5 1.1-1.5 2.6-3 3.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7.2 7.4C4.8 8.5 3 10.4 1.9 12c1.3 2.7 5.5 5.8 10.1 5.8 1.6 0 3.2-.4 4.6-1.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.9 9.1a4 4 0 0 1 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        <div class="password-validation-indicator is-hidden" id="passwordValidationIndicator">
                            <div class="password-strength-progress" aria-hidden="true">
                                <span class="password-strength-progress-bar" id="passwordStrengthProgressBar"></span>
                            </div>
                            <div class="password-strength-alert is-hidden" id="passwordStrengthAlert" data-strength="empty">Password strength: weak.</div>
                            <div class="password-validation-title">Your password must contain:</div>
                            <div class="password-validation-list">
                                <div class="password-validation-item" data-validation="length">
                                    <span class="validation-icon validation-icon-invalid">✕</span>
                                    <span class="validation-icon validation-icon-valid">✓</span>
                                    <span class="validation-text">At least 10 characters</span>
                                </div>
                                <div class="password-validation-item" data-validation="lowercase">
                                    <span class="validation-icon validation-icon-invalid">✕</span>
                                    <span class="validation-icon validation-icon-valid">✓</span>
                                    <span class="validation-text">Lower case letters (a-z)</span>
                                </div>
                                <div class="password-validation-item" data-validation="uppercase">
                                    <span class="validation-icon validation-icon-invalid">✕</span>
                                    <span class="validation-icon validation-icon-valid">✓</span>
                                    <span class="validation-text">Upper case letters (A-Z)</span>
                                </div>
                                <div class="password-validation-item" data-validation="number">
                                    <span class="validation-icon validation-icon-invalid">✕</span>
                                    <span class="validation-icon validation-icon-valid">✓</span>
                                    <span class="validation-text">Numbers (0-9)</span>
                                </div>
                                <div class="password-validation-item" data-validation="special">
                                    <span class="validation-icon validation-icon-invalid">✕</span>
                                    <span class="validation-icon validation-icon-valid">✓</span>
                                    <span class="validation-text">Special characters (e.g. !@#$%^&amp;*)</span>
                                </div>
                            </div>
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
                },
                {
                    modal: document.getElementById('registerModal'),
                    triggerSelector: '.js-register-trigger',
                    closeSelector: '[data-close-register-modal]',
                    passwordFieldId: '#registerModalConfirmPassword',
                    passwordToggleId: '#registerConfirmPasswordToggle'
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
            const modal = document.getElementById('twoFaModal');
            if (!modal) return;

            const form = modal.querySelector('[data-verification-form]');
            const actionInput = modal.querySelector('#verificationAction');
            const codeInput = modal.querySelector('#otpCode');
            const primaryButton = modal.querySelector('#verificationPrimaryButton');
            const heroIcon = modal.querySelector('#verificationHeroIcon');
            const readyMark = modal.querySelector('#verificationReadyMark');
            const digitInputs = Array.from(modal.querySelectorAll('.verification-otp-digit'));

            if (!form || !actionInput || !codeInput || !primaryButton || !heroIcon || !readyMark || digitInputs.length !== 6) return;

            const digitsOnly = (value) => (value || '').replace(/\D+/g, '').slice(0, 6);

            const setState = () => {
                const value = digitsOnly(digitInputs.map((input) => input.value).join(''));
                codeInput.value = value;

                const complete = value.length === 6;
                actionInput.value = complete ? 'verify_2fa' : 'resend_2fa';
                primaryButton.classList.toggle('is-ready', complete);
                heroIcon.classList.toggle('is-ready', complete);
                readyMark.classList.toggle('is-visible', complete);
                primaryButton.querySelector('span').textContent = complete ? 'Confirm Code' : 'Resend';
            };

            const fillInputs = (value) => {
                const chars = digitsOnly(value).split('');
                digitInputs.forEach((input, index) => {
                    input.value = chars[index] || '';
                });
                setState();
            };

            fillInputs(codeInput.value);

            digitInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    const value = digitsOnly(input.value);

                    if (value.length > 1) {
                        fillInputs(value + digitInputs.slice(index + 1).map((node) => node.value).join(''));
                        const nextFocus = Math.min(index + value.length, digitInputs.length - 1);
                        digitInputs[nextFocus].focus();
                        return;
                    }

                    input.value = value;
                    if (value && index < digitInputs.length - 1) {
                        digitInputs[index + 1].focus();
                    }
                    setState();
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace') {
                        event.preventDefault();
                        if (input.value) {
                            input.value = '';
                        } else if (index > 0) {
                            const prev = digitInputs[index - 1];
                            prev.value = '';
                            prev.focus();
                        }
                        setState();
                        return;
                    }
                    if (event.key === 'Delete') {
                        event.preventDefault();
                        input.value = '';
                        setState();
                        return;
                    }
                    if (event.key === 'ArrowLeft' && index > 0) {
                        event.preventDefault();
                        digitInputs[index - 1].focus();
                    }
                    if (event.key === 'ArrowRight' && index < digitInputs.length - 1) {
                        event.preventDefault();
                        digitInputs[index + 1].focus();
                    }
                });

                input.addEventListener('paste', (event) => {
                    const pasted = digitsOnly(event.clipboardData ? event.clipboardData.getData('text') : '');
                    if (!pasted) return;
                    event.preventDefault();
                    fillInputs(pasted);
                    const focusIndex = Math.min(pasted.length, digitInputs.length - 1);
                    digitInputs[focusIndex].focus();
                });
            });

            form.addEventListener('submit', () => {
                setState();
            });
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

        (function() {
            // Password Validation Functionality
            const passwordField = document.getElementById('registerModalPassword');
            const validationIndicator = document.getElementById('passwordValidationIndicator');
            const strengthAlert = document.getElementById('passwordStrengthAlert');
            const progressBar = document.getElementById('passwordStrengthProgressBar');
            const confirmPasswordField = document.getElementById('registerModalConfirmPassword');
            const registerForm = passwordField ? passwordField.closest('form') : null;
            
            if (!passwordField || !validationIndicator || !strengthAlert || !progressBar) return;

            function validatePassword(password) {
                const categoryCount = [
                    /[a-z]/.test(password),
                    /[A-Z]/.test(password),
                    /\d/.test(password),
                    /[^a-zA-Z\d]/.test(password)
                ].filter(Boolean).length;

                const validations = {
                    lowercase: /[a-z]/.test(password),
                    uppercase: /[A-Z]/.test(password),
                    number: /\d/.test(password),
                    special: /[^a-zA-Z\d]/.test(password),
                    length: password.length >= 10,
                    categories: categoryCount >= 3,
                    categoryCount
                };

                validations.score = ['length', 'uppercase', 'lowercase', 'number', 'special']
                    .filter((key) => validations[key]).length;

                return validations;
            }

            function getPasswordStrength(password, validations) {
                if (!password) {
                    return {
                        level: 'empty',
                        message: 'Password strength: weak.'
                    };
                }

                if (validations.length && validations.categories) {
                    return {
                        level: 'strong',
                        message: 'Password strength: strong.'
                    };
                }

                if (validations.score >= 3 || validations.categoryCount >= 2) {
                    return {
                        level: 'medium',
                        message: 'Password strength: medium.'
                    };
                }

                return {
                    level: 'weak',
                    message: 'Password strength: weak.'
                };
            }

            function updateValidationIndicator(validations) {
                Object.keys(validations).forEach(key => {
                    const item = validationIndicator.querySelector(`[data-validation="${key}"]`);
                    if (item) {
                        if (validations[key]) {
                            item.classList.add('is-valid');
                        } else {
                            item.classList.remove('is-valid');
                        }
                    }
                });
            }

            function updateStrengthAlert(strength) {
                strengthAlert.dataset.strength = strength.level;
                strengthAlert.textContent = strength.message;
                strengthAlert.classList.toggle('is-hidden', strength.level === 'empty');
            }

            function updateProgressBar(validations) {
                const progress = (validations.score / 5) * 100;
                const strength = getPasswordStrength(passwordField.value, validations);
                progressBar.style.width = `${progress}%`;
                progressBar.dataset.strength = strength.level;
            }

            function updateValidationVisibility(password) {
                validationIndicator.classList.toggle('is-hidden', !password);
            }

            function checkPasswordStrength() {
                const password = passwordField.value;
                const validations = validatePassword(password);
                updateValidationVisibility(password);
                updateValidationIndicator(validations);
                updateProgressBar(validations);

                if (!password) {
                    passwordField.setCustomValidity('');
                } else if (!validations.length || !validations.categories) {
                    passwordField.setCustomValidity('Use at least 10 characters and meet at least 3 of these: lowercase, uppercase, number, special character.');
                } else {
                    passwordField.setCustomValidity('');
                }

                if (confirmPasswordField) {
                    if (confirmPasswordField.value && confirmPasswordField.value !== password) {
                        confirmPasswordField.setCustomValidity('Passwords do not match.');
                        updateStrengthAlert({
                            level: 'weak',
                            message: 'Passwords do not match.'
                        });
                    } else {
                        confirmPasswordField.setCustomValidity('');
                        updateStrengthAlert(getPasswordStrength(password, validations));
                    }
                } else {
                    updateStrengthAlert(getPasswordStrength(password, validations));
                }
            }

            function validateConfirmPassword() {
                if (!confirmPasswordField) return;

                if (confirmPasswordField.value && confirmPasswordField.value !== passwordField.value) {
                    confirmPasswordField.setCustomValidity('Passwords do not match.');
                    updateStrengthAlert({
                        level: 'weak',
                        message: 'Passwords do not match.'
                    });
                } else {
                    confirmPasswordField.setCustomValidity('');
                    checkPasswordStrength();
                }
            }

            // Add event listeners
            passwordField.addEventListener('input', checkPasswordStrength);
            if (confirmPasswordField) {
                confirmPasswordField.addEventListener('input', validateConfirmPassword);
            }
            if (registerForm) {
                registerForm.addEventListener('submit', () => {
                    checkPasswordStrength();
                    validateConfirmPassword();
                });
            }
            
            // Initial check
            checkPasswordStrength();
        }());

        (function() {
            // Login Form Custom Validation
            const loginForm = document.querySelector('form[action="index.php"][novalidate]');
            
            if (!loginForm) return;

            loginForm.addEventListener('submit', function(e) {
                const emailField = document.getElementById('loginModalEmail');
                const passwordField = document.getElementById('loginModalPassword');
                
                // Clear any previous custom validation messages
                const existingAlert = loginForm.querySelector('.alert-error');
                if (existingAlert) {
                    existingAlert.remove();
                }

                // Custom validation
                let isValid = true;
                let errorMessage = '';

                if (!emailField.value.trim()) {
                    errorMessage = 'Please enter your email address.';
                    isValid = false;
                } else if (!emailField.validity.valid) {
                    errorMessage = 'Please enter a valid email address.';
                    isValid = false;
                } else if (!passwordField.value.trim()) {
                    errorMessage = 'Please enter your password.';
                    isValid = false;
                } else if (passwordField.value.length < 8) {
                    errorMessage = 'Password must be at least 8 characters long.';
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    
                    // Create and show professional error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-error';
                    errorDiv.textContent = errorMessage;
                    
                    // Insert error message at the top of the form
                    const formBody = loginForm.querySelector('.login-modal-body');
                    if (formBody) {
                        formBody.insertBefore(errorDiv, formBody.firstChild);
                    }
                    
                    // Focus on the first invalid field
                    if (!emailField.value.trim() || !emailField.validity.valid) {
                        emailField.focus();
                    } else {
                        passwordField.focus();
                    }
                }
            });
        }());
    </script>

</body>

</html>
