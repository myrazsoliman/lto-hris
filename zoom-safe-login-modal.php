<?php
declare(strict_types=1);

// Demo/default values. Replace these with your actual PHP variables when integrating.
$loginError = $loginError ?? '';
$loginEmail = $loginEmail ?? '';
$csrfToken = $csrfToken ?? '';
$loginCaptchaNonce = $loginCaptchaNonce ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTO HRIS Login</title>
    <style>
        :root {
            --surface: #f2f6fb;
            --surface-panel: #ffffff;
            --surface-field: #f8fbff;
            --border-soft: #d6e1f1;
            --border-strong: #b8cbe6;
            --text-main: #173456;
            --text-muted: #60748e;
            --brand-900: #143b75;
            --brand-700: #2458a8;
            --brand-500: #6e97cf;
            --accent: #f2c24f;
            --shadow-modal: 0 1.5rem 4rem rgba(18, 42, 78, 0.18);
            --shadow-soft: 0 1rem 2.5rem rgba(24, 51, 90, 0.1);
            --radius-xl: 2rem;
            --radius-lg: 1.25rem;
            --radius-md: 0.95rem;
            --page-pad: clamp(1rem, 2vw, 1.5rem);
            --panel-pad: clamp(1.25rem, 2vw, 2rem);
            --field-pad-x: 1rem;
            --field-pad-y: 0.95rem;
            --modal-max: 72rem;
            --visual-min: 18rem;
            --visual-max: 24rem;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            font-size: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            min-width: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            background:
                linear-gradient(rgba(11, 29, 55, 0.74), rgba(11, 29, 55, 0.74)),
                linear-gradient(135deg, #17345e 0%, #274f8d 45%, #8db0da 100%);
            overflow-x: hidden;
        }

        .page-shell {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: var(--page-pad);
        }

        /* Main modal shell. Centered, flexible, and capped for zoom safety. */
        .auth-modal {
            width: 100%;
            max-width: var(--modal-max);
            max-height: min(100dvh - (var(--page-pad) * 2), 48rem);
            display: grid;
            grid-template-columns: minmax(var(--visual-min), var(--visual-max)) minmax(0, 1fr);
            background: var(--surface-panel);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-modal);
            overflow: hidden;
            isolation: isolate;
        }

        .auth-visual {
            position: relative;
            display: grid;
            place-items: center;
            padding: clamp(1.5rem, 2vw, 2rem);
            color: #fff;
            text-align: center;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.16), transparent 32%),
                radial-gradient(circle at bottom right, rgba(255, 255, 255, 0.12), transparent 30%),
                linear-gradient(160deg, var(--brand-900) 0%, var(--brand-700) 48%, var(--brand-500) 100%);
        }

        .auth-visual::before {
            content: "";
            position: absolute;
            inset: 8% 6%;
            border-radius: 50%;
            border: 0.12rem solid rgba(255, 255, 255, 0.09);
            pointer-events: none;
        }

        .auth-visual-content {
            position: relative;
            z-index: 1;
            width: min(100%, 18rem);
            display: grid;
            gap: 1rem;
            justify-items: center;
        }

        .auth-logo {
            display: block;
            width: min(100%, 11rem);
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 1rem 1.8rem rgba(11, 24, 52, 0.28));
        }

        .auth-visual-title {
            margin: 0;
            font-family: "Oswald", "Arial Narrow", Arial, sans-serif;
            font-size: clamp(2rem, 2.2vw, 3rem);
            line-height: 0.98;
            letter-spacing: 0.04em;
            text-wrap: balance;
        }

        .auth-visual-subtitle {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.4;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.92);
            text-wrap: balance;
        }

        .auth-visual-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1rem;
            border-radius: 999rem;
            border: 0.08rem solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            text-wrap: balance;
        }

        /* Right panel keeps header visible and allows safe scrolling when height is tight. */
        .auth-panel {
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--surface-panel);
            overflow: hidden;
        }

        .auth-panel-header {
            position: relative;
            flex: 0 0 auto;
            padding: clamp(1.25rem, 2vw, 1.75rem) clamp(1.25rem, 2vw, 2rem) 1.125rem;
            background: linear-gradient(160deg, var(--brand-900) 0%, var(--brand-700) 48%, var(--brand-500) 100%);
            color: #fff;
        }

        .auth-close {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border: 0;
            border-radius: 999rem;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 1.7rem;
            line-height: 1;
            cursor: pointer;
        }

        .auth-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .auth-heading {
            margin: 0;
            padding-right: 3rem;
            font-size: clamp(2rem, 3vw, 3.5rem);
            line-height: 1.02;
            font-weight: 800;
            letter-spacing: -0.04em;
            text-wrap: balance;
        }

        .auth-heading span {
            color: var(--accent);
        }

        .auth-subheading {
            margin: 0.75rem 0 0;
            max-width: 22ch;
            font-size: 1rem;
            line-height: 1.4;
            color: rgba(255, 255, 255, 0.92);
            text-wrap: balance;
        }

        .auth-panel-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: var(--panel-pad);
            background: var(--surface-panel);
        }

        .auth-panel-body-inner {
            width: min(100%, 31rem);
            margin-inline: auto;
        }

        .auth-banner {
            margin: 0 0 1.25rem;
            padding-bottom: 0.8rem;
            border-bottom: 0.08rem solid var(--border-soft);
            font-size: 0.86rem;
            font-weight: 800;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            text-align: center;
            color: var(--text-muted);
        }

        .alert {
            margin: 0 0 1rem;
            padding: 0.9rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.94rem;
            line-height: 1.45;
        }

        .alert-error {
            background: #fff1f1;
            border: 0.08rem solid #efc8c8;
            color: #8a2f2f;
        }

        .auth-form {
            display: grid;
            gap: 1rem;
        }

        .field-group {
            display: grid;
            gap: 0.45rem;
        }

        .field-label {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .field-shell {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: var(--field-pad-y) var(--field-pad-x);
            border: 0.08rem solid var(--border-soft);
            border-radius: var(--radius-lg);
            background: linear-gradient(180deg, #fbfdff, var(--surface-field));
            box-shadow: inset 0 0.08rem 0 rgba(255, 255, 255, 0.85);
        }

        .field-shell:focus-within {
            border-color: var(--brand-700);
            box-shadow: 0 0 0 0.2rem rgba(36, 88, 168, 0.12);
        }

        .field-icon {
            width: 1.4rem;
            height: 1.4rem;
            color: var(--brand-700);
            flex: 0 0 auto;
        }

        .field-input {
            min-width: 0;
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-main);
            font: inherit;
            font-size: 1rem;
            line-height: 1.3;
        }

        .field-input::placeholder {
            color: #8899af;
        }

        .field-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border: 0;
            border-radius: 999rem;
            background: transparent;
            color: #b47a14;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .auth-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .auth-link {
            color: #1b4f98;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .captcha-block {
            display: grid;
            gap: 0.75rem;
            padding-top: 0.25rem;
        }

        .captcha-label {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .captcha-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 0.75rem;
            align-items: stretch;
        }

        .captcha-preview {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .captcha-image-frame {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem;
            border: 0.08rem solid var(--border-soft);
            border-radius: var(--radius-md);
            background: #f3f7fc;
        }

        .captcha-image {
            display: block;
            max-width: 100%;
            width: 100%;
            height: auto;
        }

        .captcha-refresh {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            border: 0.08rem solid var(--border-strong);
            border-radius: 0.8rem;
            background: #fff;
            cursor: pointer;
        }

        .captcha-refresh img {
            max-width: 100%;
            height: auto;
        }

        .auth-submit {
            width: 100%;
            border: 0;
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, var(--brand-900), var(--brand-700));
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: var(--shadow-soft);
        }

        .auth-submit:hover {
            filter: saturate(1.05);
        }

        /* Tablet and high-zoom transition point. */
        @media (max-width: 64rem) {
            .auth-modal {
                grid-template-columns: minmax(16rem, 20rem) minmax(0, 1fr);
            }

            .auth-panel-header {
                padding-right: 3.75rem;
            }

            .captcha-row {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile and extreme zoom: stack vertically and keep everything readable. */
        @media (max-width: 48rem) {
            .page-shell {
                align-items: start;
            }

            .auth-modal {
                max-height: calc(100dvh - (var(--page-pad) * 2));
                grid-template-columns: 1fr;
            }

            .auth-visual {
                padding-block: 1.5rem;
            }

            .auth-visual-content {
                width: min(100%, 22rem);
            }

            .auth-logo {
                width: min(100%, 8rem);
            }

            .auth-panel-header {
                padding: 1rem 3.5rem 0.95rem 1rem;
            }

            .auth-heading {
                font-size: clamp(1.65rem, 7vw, 2.35rem);
                padding-right: 0;
            }

            .auth-subheading {
                max-width: none;
                font-size: 0.94rem;
            }

            .auth-panel-body {
                padding: 1rem;
            }

            .auth-panel-body-inner {
                width: 100%;
            }
        }

        @media (max-width: 30rem) {
            .page-shell {
                padding: 0.75rem;
            }

            .auth-modal {
                border-radius: 1.35rem;
            }

            .auth-visual,
            .auth-panel-header,
            .auth-panel-body {
                padding-inline: 0.9rem;
            }

            .field-shell {
                gap: 0.6rem;
                padding-inline: 0.85rem;
            }

            .field-input {
                font-size: 0.98rem;
            }

            .auth-submit {
                letter-spacing: 0.12em;
            }
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <!-- Semantic dialog container. Replace with your modal open/close logic as needed. -->
        <section class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
            <aside class="auth-visual" aria-label="LTO HRIS system information">
                <div class="auth-visual-content">
                    <img src="assets/img/lto_logo.png" alt="Land Transportation Office seal" class="auth-logo">
                    <h2 class="auth-visual-title">LTO - HRIS</h2>
                    <p class="auth-visual-subtitle">Land Transportation Office</p>
                    <span class="auth-visual-badge">Official Personnel Access Portal</span>
                </div>
            </aside>

            <div class="auth-panel">
                <header class="auth-panel-header">
                    <!-- Replace this with your actual close trigger in production. -->
                    <button type="button" class="auth-close" aria-label="Close login modal">&times;</button>
                    <h1 class="auth-heading" id="loginModalTitle">Welcome to <span>LTO HRIS</span></h1>
                    <p class="auth-subheading">Land Transportation Office Human Resource Information System</p>
                </header>

                <!-- Body scrolls safely if the viewport gets short due to browser zoom. -->
                <div class="auth-panel-body">
                    <div class="auth-panel-body-inner">
                        <?php if ($loginError !== ''): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($loginError); ?></div>
                        <?php endif; ?>

                        <p class="auth-banner">Authorized Access Only</p>

                        <form class="auth-form" method="post" action="index.php" novalidate>
                            <input type="hidden" name="form_action" value="login_modal">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                            <div class="field-group">
                                <label class="field-label" for="loginModalEmail">Email</label>
                                <div class="field-shell">
                                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor"></path>
                                        <path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"></path>
                                    </svg>
                                    <input
                                        class="field-input"
                                        type="email"
                                        id="loginModalEmail"
                                        name="email"
                                        value="<?php echo htmlspecialchars($loginEmail); ?>"
                                        placeholder="Enter your email"
                                        autocomplete="email"
                                        spellcheck="false"
                                        maxlength="150"
                                        required>
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="loginModalPassword">Password</label>
                                <div class="field-shell">
                                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor"></path>
                                    </svg>
                                    <input
                                        class="field-input"
                                        type="password"
                                        id="loginModalPassword"
                                        name="password"
                                        placeholder="Enter your password"
                                        autocomplete="current-password"
                                        maxlength="128"
                                        required>
                                    <button type="button" class="field-action" aria-label="Show password">◉</button>
                                </div>
                            </div>

                            <div class="auth-row">
                                <a class="auth-link" href="index.php?forgot=1">Forgot password?</a>
                            </div>

                            <div class="captcha-block">
                                <p class="captcha-label">What code is in the image?</p>
                                <div class="captcha-row">
                                    <div class="captcha-preview">
                                        <div class="captcha-image-frame">
                                            <img
                                                class="captcha-image"
                                                src="captcha-image.php?context=login_modal&amp;v=<?php echo urlencode($loginCaptchaNonce); ?>"
                                                alt="CAPTCHA image">
                                        </div>
                                        <button class="captcha-refresh" type="button" aria-label="Refresh captcha">
                                            <img src="assets/img/captcha-refresh.png" alt="" width="18" height="18">
                                        </button>
                                    </div>

                                    <div class="field-group">
                                        <label class="field-label" for="loginModalCaptcha">Security Code</label>
                                        <div class="field-shell">
                                            <img class="field-icon" src="assets/img/captcha-logo.png" alt="" width="24" height="24" aria-hidden="true">
                                            <input
                                                class="field-input"
                                                type="text"
                                                id="loginModalCaptcha"
                                                name="captcha_answer"
                                                placeholder="Enter the CAPTCHA"
                                                autocomplete="off"
                                                autocapitalize="off"
                                                spellcheck="false"
                                                maxlength="5"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="auth-submit">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
