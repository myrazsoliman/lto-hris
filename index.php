<?php
require_once 'includes/auth.php';
require_once __DIR__ . '/includes/login-captcha.php';

$loginError = '';
$loginEmail = '';
$showLoginModal = isset($_GET['login']) || isset($_GET['registration_success']);
$registerError = '';
$registerSuccess = '';
$registrationSuccess = isset($_GET['registration_success']);
$showLoginCaptcha = true;
$loginCaptchaInput = '';
$loginCaptchaNonce = '';
$registerFirstName = '';
$registerMiddleName = '';
$registerLastName = '';
$registerEmail = '';
$registerVerificationCode = '';
$showRegisterVerificationStep = isset($_SESSION['pending_register_verification']);
$showRegisterModal = isset($_GET['register']);
$forgotError = '';
$forgotSuccess = '';
$forgotEmail = '';
$showForgotModal = isset($_GET['forgot']);
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
    $loginCaptchaInput = isset($_POST['captcha_answer']) ? trim((string) $_POST['captcha_answer']) : '';
    $captchaRequired = true;

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $loginError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('login')) {
        $loginError = 'Too many login attempts. Please wait a few minutes before trying again.';
    } elseif ($loginEmail === '' || $password === '') {
        $loginError = 'Enter your email and password.';
    } else {
        $canProceedLogin = true;

        if ($captchaRequired) {
            if (!login_captcha_verify_answer($loginCaptchaInput)) {
                register_failed_attempt('login');
                $loginError = 'Security verification failed. Please answer the CAPTCHA correctly.';
                $canProceedLogin = false;
            }
        }

        if ($canProceedLogin) {
            $user = authenticate_user($loginEmail, $password);

            if ($user) {
                clear_failed_attempts('login');
                unset($_SESSION['login_modal_captcha_expected'], $_SESSION['login_modal_captcha_nonce']);
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
    }

    // Always re-issue a fresh CAPTCHA after a POST so the next attempt has a new image.
    $loginCaptchaNonce = login_captcha_issue_challenge();
    $showLoginModal = true;
}

if ($showLoginCaptcha) {
    $loginCaptchaNonce = login_captcha_ensure_nonce();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'register_modal') {
    $registerFirstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $registerMiddleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $registerLastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $registerEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
    $registerVerificationCode = isset($_POST['register_verification_code']) ? preg_replace('/\D+/', '', (string) $_POST['register_verification_code']) : '';
    $password = isset($_POST['register_password']) ? (string) $_POST['register_password'] : '';
    $confirmPassword = isset($_POST['register_confirm_password']) ? (string) $_POST['register_confirm_password'] : '';
    $registerVerificationAction = isset($_POST['register_verification_action']) ? trim((string) $_POST['register_verification_action']) : 'send_code';
    $registerHoneypot = isset($_POST['website']) ? trim($_POST['website']) : '';
    $pendingRegister = $_SESSION['pending_register_verification'] ?? null;

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $registerError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('register')) {
        $registerError = 'Too many registration attempts. Please wait a few minutes before trying again.';
    } else {
        if ($registerVerificationAction === 'verify_code') {
            if (!is_array($pendingRegister)) {
                $registerError = 'No pending email verification found. Please request a new verification code.';
            } elseif (($pendingRegister['expires_at'] ?? 0) < time()) {
                unset($_SESSION['pending_register_verification']);
                $registerError = 'Verification code has expired. Please request a new code.';
            } elseif ($registerVerificationCode === '' || strlen($registerVerificationCode) !== 6) {
                $registerError = 'Enter the 6-digit verification code sent to your email.';
            } elseif (!password_verify($registerVerificationCode, (string) ($pendingRegister['code_hash'] ?? ''))) {
                $registerError = 'Invalid verification code. Please try again.';
            } elseif (user_exists((string) ($pendingRegister['email'] ?? ''))) {
                unset($_SESSION['pending_register_verification']);
                $registerError = 'An account with this email already exists.';
            } else {
                $userId = create_user_directly_with_hash(
                    (string) ($pendingRegister['first_name'] ?? ''),
                    (string) ($pendingRegister['middle_name'] ?? ''),
                    (string) ($pendingRegister['last_name'] ?? ''),
                    (string) ($pendingRegister['email'] ?? ''),
                    (string) ($pendingRegister['password_hash'] ?? '')
                );

                if ($userId) {
                    unset($_SESSION['pending_register_verification']);
                    clear_failed_attempts('register');
                    regenerate_csrf_token();
                    header('Location: index.php?login=1&registration_success=1');
                    exit;
                }

                $registerError = 'Registration failed. Please try again.';
            }
        } elseif ($registerVerificationAction === 'resend_code') {
            if (!is_array($pendingRegister)) {
                $registerError = 'No pending email verification found. Please register again.';
            } else {
                $lastSentAt = (int) ($pendingRegister['sent_at'] ?? 0);
                if ($lastSentAt > 0 && (time() - $lastSentAt) < 30) {
                    $registerError = 'Please wait a few seconds before requesting another code.';
                } else {
                    $verificationCode = (string) random_int(100000, 999999);
                    $pendingRegister['code_hash'] = password_hash($verificationCode, PASSWORD_DEFAULT);
                    $pendingRegister['expires_at'] = time() + 600;
                    $pendingRegister['sent_at'] = time();
                    $_SESSION['pending_register_verification'] = $pendingRegister;

                    $subject = 'LTO HRIS Email Verification Code';
                    $message = "Your LTO HRIS registration verification code is: {$verificationCode}\n\nThis code expires in 10 minutes.";
                    $sent = send_email_message((string) $pendingRegister['email'], $subject, $message);

                    if ($sent) {
                        $registerSuccess = 'A new verification code has been sent to your email.';
                    } else {
                        $registerError = 'Unable to send verification code right now. Please try again later.';
                    }
                }
            }
        } else {
            if ($registerFirstName === '' || $registerLastName === '' || $registerEmail === '' || $password === '' || $confirmPassword === '') {
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
                    $verificationCode = (string) random_int(100000, 999999);
                    $_SESSION['pending_register_verification'] = [
                        'first_name' => $registerFirstName,
                        'middle_name' => $registerMiddleName,
                        'last_name' => $registerLastName,
                        'email' => normalize_identifier($registerEmail),
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'code_hash' => password_hash($verificationCode, PASSWORD_DEFAULT),
                        'expires_at' => time() + 600,
                        'sent_at' => time(),
                    ];

                    $subject = 'LTO HRIS Email Verification Code';
                    $message = "Your LTO HRIS registration verification code is: {$verificationCode}\n\nThis code expires in 10 minutes.";
                    $sent = send_email_message($registerEmail, $subject, $message);

                    if ($sent) {
                        $registerSuccess = 'A verification code has been sent to your email. Enter the code to complete registration.';
                    } else {
                        unset($_SESSION['pending_register_verification']);
                        $registerError = 'Unable to send verification code right now. Please try again later.';
                    }
                }
            }
        }
    }

    if ($registerError !== '') {
        register_failed_attempt('register');
    }

    $showRegisterModal = true;
}

if (isset($_SESSION['pending_register_verification']) && is_array($_SESSION['pending_register_verification'])) {
    $pendingRegister = $_SESSION['pending_register_verification'];
    if ($registerFirstName === '') {
        $registerFirstName = (string) ($pendingRegister['first_name'] ?? '');
    }
    if ($registerMiddleName === '') {
        $registerMiddleName = (string) ($pendingRegister['middle_name'] ?? '');
    }
    if ($registerLastName === '') {
        $registerLastName = (string) ($pendingRegister['last_name'] ?? '');
    }
    if ($registerEmail === '') {
        $registerEmail = (string) ($pendingRegister['email'] ?? '');
    }
    $showRegisterVerificationStep = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'forgot_password_modal') {
    $forgotEmail = isset($_POST['forgot_email']) ? trim((string) $_POST['forgot_email']) : '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $forgotError = 'Your session has expired. Please try again.';
    } elseif (is_rate_limited('forgot_password')) {
        $forgotError = 'Too many requests. Please wait a few minutes before trying again.';
    } elseif ($forgotEmail === '' || !filter_var($forgotEmail, FILTER_VALIDATE_EMAIL)) {
        $forgotError = 'Please enter a valid email address.';
    } else {
        $existingUser = fetch_user_record($forgotEmail);
        if ($existingUser) {
            $subject = 'LTO HRIS Password Assistance';
            $message = "A password assistance request was submitted for your LTO HRIS account.\n\n"
                . "If you made this request, please contact your HRIS administrator to reset your password.\n\n"
                . "If you did not make this request, you can safely ignore this email.\n\n"
                . "LTO HRIS";
            send_email_message($forgotEmail, $subject, $message);
        }

        clear_failed_attempts('forgot_password');
        $forgotSuccess = 'If an account exists for this email, password assistance instructions have been sent.';
    }

    if ($forgotError !== '') {
        register_failed_attempt('forgot_password');
    }

    $showForgotModal = true;
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
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
    <title>LTO-HRIS</title>
    
    <?php require_once __DIR__ . '/includes/favicon-links.php'; ?>
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

<body class="landing-page<?php echo ($showLoginModal || $showRegisterModal || $showForgotModal || $show2faModal) ? ' login-modal-open' : ''; ?>">

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
                        <div class="login-forgot-under-password">
                            <a href="#" class="login-forgot-link js-forgot-trigger">Forgot password?</a>
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

    <div class="login-modal forgot-modal<?php echo $showForgotModal ? ' is-open' : ''; ?>" id="forgotPasswordModal" aria-hidden="<?php echo $showForgotModal ? 'false' : 'true'; ?>">
        <div class="login-modal-backdrop" data-close-forgot-modal></div>
        <div class="login-modal-dialog forgot-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordModalTitle">
            <div class="login-card-modern login-card-modal login-card-modal-split forgot-card-modal">
                <div class="login-modal-visual">
                    <div class="login-modal-visual-overlay"></div>
                    <div class="login-modal-visual-content">
                        <img src="assets/img/lto_logo.png" alt="Land Transportation Office seal" class="login-modal-visual-logo">
                        <h2 class="login-modal-visual-title">LTO - HRIS</h2>
                        <p class="login-modal-visual-subtitle">Land Transportation Office</p>
                        <span class="login-modal-visual-kicker">Account Recovery Portal</span>
                    </div>
                </div>

                <div class="login-modal-panel forgot-modal-panel">
                    <div class="login-modal-header login-modal-header-plain">
                        <div class="login-modal-header-copy">
                            <div class="login-modal-title-row">
                                <h2 id="forgotPasswordModalTitle">Forgot <span>Password</span></h2>
                            </div>
                            <p class="login-modal-subtitle">Enter your email to reset your password.</p>
                        </div>
                        <button class="login-modal-close" type="button" data-close-forgot-modal aria-label="Close forgot password modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="login-modal-body login-modal-body-plain">
                        <?php if ($forgotError !== ''): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($forgotError); ?></div>
                        <?php endif; ?>

                        <?php if ($forgotSuccess !== ''): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($forgotSuccess); ?></div>
                        <?php endif; ?>

                        <div class="login-modal-role-band">Password Recovery</div>

                        <form class="login-form-modern login-form-modal" method="post" action="index.php" novalidate>
                            <input type="hidden" name="form_action" value="forgot_password_modal">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                            <div class="login-input-wrap login-input-wrap-modal">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                            <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <input type="email" id="forgotPasswordEmail" name="forgot_email" placeholder=" " value="<?php echo htmlspecialchars($forgotEmail); ?>" autocomplete="email" maxlength="150" required>
                                    <label class="login-floating-label" for="forgotPasswordEmail">Email</label>
                                </div>
                            </div>

                            <div class="login-modal-actions-row login-modal-actions-row-split">
                                <a href="#" class="login-forgot-link js-login-trigger">Back to Login</a>
                            </div>

                            <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split">
                                <span>Send Email</span>
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
                        <input type="hidden" name="register_verification_action" id="registerVerificationAction" value="<?php echo $showRegisterVerificationStep ? 'verify_code' : 'send_code'; ?>">
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
                                <input type="text" id="registerModalFirstName" name="first_name" placeholder=" " value="<?php echo htmlspecialchars($registerFirstName ?? ''); ?>" autocomplete="given-name" maxlength="80" <?php echo $showRegisterVerificationStep ? 'readonly' : ''; ?> required>
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
                                <input type="text" id="registerModalMiddleName" name="middle_name" placeholder=" " value="<?php echo htmlspecialchars($registerMiddleName ?? ''); ?>" autocomplete="additional-name" maxlength="80" <?php echo $showRegisterVerificationStep ? 'readonly' : ''; ?>>
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
                                <input type="text" id="registerModalLastName" name="last_name" placeholder=" " value="<?php echo htmlspecialchars($registerLastName ?? ''); ?>" autocomplete="family-name" maxlength="80" <?php echo $showRegisterVerificationStep ? 'readonly' : ''; ?> required>
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
                                <input type="email" id="registerModalEmail" name="email" placeholder=" " value="<?php echo htmlspecialchars($registerEmail); ?>" autocomplete="email" maxlength="150" <?php echo $showRegisterVerificationStep ? 'readonly' : ''; ?> required>
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
                                <input type="password" id="registerModalPassword" name="register_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" <?php echo $showRegisterVerificationStep ? '' : 'required'; ?> <?php echo $showRegisterVerificationStep ? 'disabled' : ''; ?>>
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
                                <input type="password" id="registerModalConfirmPassword" name="register_confirm_password" placeholder=" " autocomplete="new-password" minlength="10" maxlength="128" <?php echo $showRegisterVerificationStep ? '' : 'required'; ?> <?php echo $showRegisterVerificationStep ? 'disabled' : ''; ?>>
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

                        <div class="register-login-row">
                            <span>Already have an account?</span>
                            <a href="login.php" class="register-login-link js-login-trigger">Login</a>
                        </div>

                        <?php if ($showRegisterVerificationStep): ?>
                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" />
                                        <path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <input type="text" id="registerVerificationCode" name="register_verification_code" placeholder=" " value="<?php echo htmlspecialchars($registerVerificationCode); ?>" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                                <label class="login-floating-label" for="registerVerificationCode">Email Verification Code</label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="password-validation-indicator<?php echo $showRegisterVerificationStep ? ' is-hidden' : ' is-hidden'; ?>" id="passwordValidationIndicator">
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

                            <?php if ($showRegisterVerificationStep): ?>
                                <div class="register-verify-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split register-submit-btn-modal" onclick="document.getElementById('registerVerificationAction').value='verify_code';">
                                        <span>Verify and Create Account</span>
                                    </button>
                                    <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split register-submit-btn-modal" style="background:#f1f5fb;color:#1f4f8f;border-color:#b8c9e6;" onclick="document.getElementById('registerVerificationAction').value='resend_code';">
                                        <span>Resend Code</span>
                                    </button>
                                </div>
                            <?php else: ?>
                                <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split register-submit-btn-modal" onclick="document.getElementById('registerVerificationAction').value='send_code';">
                                    <span>Send Verification Code</span>
                                </button>
                            <?php endif; ?>
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
                            collapseNestedGroups();
                            submenu.hidden = false;
                            panel.classList.add('submenu-open');
                        });
                    });

                    panel.querySelectorAll('.gov-mobile-back').forEach((button) => {
                        button.addEventListener('click', closeSubmenus);
                    });
                    initializeNestedGroups();
                }
            }
        }());

        (function() {
            const STORAGE_KEY = 'ltohris.open_modal';
            const getStoredModalId = () => {
                try {
                    return (sessionStorage.getItem(STORAGE_KEY) || '').toString();
                } catch (err) {
                    return '';
                }
            };
            const setStoredModalId = (id) => {
                try {
                    sessionStorage.setItem(STORAGE_KEY, (id || '').toString());
                } catch (err) {
                    // ignore
                }
            };
            const clearStoredModalId = () => {
                try {
                    sessionStorage.removeItem(STORAGE_KEY);
                } catch (err) {
                    // ignore
                }
            };

            const modalConfigs = [{
                    modal: document.getElementById('loginModal'),
                    triggerSelector: '.js-login-trigger',
                    closeSelector: '[data-close-login-modal]',
                    passwordFieldId: '#loginModalPassword',
                    passwordToggleId: '#loginPasswordToggle'
                },
                {
                    modal: document.getElementById('forgotPasswordModal'),
                    triggerSelector: '.js-forgot-trigger',
                    closeSelector: '[data-close-forgot-modal]'
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

            const verificationModal = document.getElementById('twoFaModal');
            const anyModalOpen = () => modalConfigs.some((config) => config.modal.classList.contains('is-open')) ||
                (verificationModal && verificationModal.classList.contains('is-open'));

            const openHandlers = {};

            const syncBodyState = () => {
                document.body.classList.toggle('login-modal-open', anyModalOpen());
            };

            const closeAllModals = () => {
                clearStoredModalId();
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
                const passwordField = passwordFieldId ? modal.querySelector(passwordFieldId) : null;
                const passwordToggle = passwordToggleId ? modal.querySelector(passwordToggleId) : null;

                const openModal = () => {
                    closeAllModals();
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    setStoredModalId(modal.id || '');
                    syncBodyState();
                };

                const closeModal = () => {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    if ((modal.id || '') !== '' && getStoredModalId() === (modal.id || '')) {
                        clearStoredModalId();
                    }
                    syncBodyState();
                };

                if (modal.id) {
                    openHandlers[modal.id] = openModal;
                }

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

            // If PHP rendered a modal open (e.g., on validation errors), remember it for refresh.
            const serverOpen = modalConfigs.find((config) => config.modal.classList.contains('is-open'));
            if (serverOpen && serverOpen.modal.id) {
                setStoredModalId(serverOpen.modal.id);
            } else if (verificationModal && verificationModal.classList.contains('is-open') && verificationModal.id) {
                setStoredModalId(verificationModal.id);
            } else {
                // Restore last open modal after refresh (client-side only modals).
                const restoreId = getStoredModalId();
                if (!anyModalOpen() && restoreId && openHandlers[restoreId]) {
                    openHandlers[restoreId]();
                } else if (restoreId && !openHandlers[restoreId]) {
                    clearStoredModalId();
                }
            }

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
                const captchaField = document.getElementById('loginModalCaptcha');
                
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
                } else if (captchaField && !captchaField.value.trim()) {
                    errorMessage = 'Please answer the security check.';
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
                    } else if (!passwordField.value.trim() || passwordField.value.length < 8) {
                        passwordField.focus();
                    } else if (captchaField) {
                        captchaField.focus();
                    }
                }
            });

            const captchaField = document.getElementById('loginModalCaptcha');
                if (captchaField) {
                    captchaField.addEventListener('input', function() {
                        this.value = this.value.replace(/[^A-Za-z0-9]/g, '').slice(0, 5);
                    });
                }

            const captchaRefreshBtn = document.getElementById('loginCaptchaRefresh');
            const captchaImage = document.getElementById('loginCaptchaImage');
            if (captchaRefreshBtn && captchaImage) {
                captchaRefreshBtn.addEventListener('click', function() {
                    captchaImage.src = 'captcha-image.php?context=login_modal&regen=1&t=' + Date.now();
                    if (captchaField) {
                        captchaField.value = '';
                        captchaField.focus();
                    }
                });
            }
        }());
    </script>

</body>

</html>











