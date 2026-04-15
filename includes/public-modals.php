<?php
if (!isset($showLoginModal)) $showLoginModal = false;
if (!isset($showRegisterModal)) $showRegisterModal = false;
if (!isset($loginError)) $loginError = '';
if (!isset($loginUsername)) $loginUsername = '';
if (!isset($registerError)) $registerError = '';
if (!isset($registerSuccess)) $registerSuccess = '';
if (!isset($registerFullName)) $registerFullName = '';
if (!isset($registerEmail)) $registerEmail = '';
if (!isset($registerUsername)) $registerUsername = '';
if (!isset($csrfToken)) $csrfToken = '';
if (!isset($modalFormAction) || $modalFormAction === '') $modalFormAction = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
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
                            <p class="login-modal-subtitle"><span class="agency-line">Land Transportation Office</span><span class="system-line">Human Resource Information System</span></p>
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
                    <form class="login-form-modern login-form-modal" method="post" action="<?php echo htmlspecialchars($modalFormAction); ?>">
                        <input type="hidden" name="form_action" value="login_modal">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="login-input-wrap login-input-wrap-modal">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" /><path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" /></svg></span>
                                <input type="text" id="loginModalUsername" name="username" placeholder=" " value="<?php echo htmlspecialchars($loginUsername); ?>" autocomplete="username" spellcheck="false" maxlength="80" required>
                                <label class="login-floating-label" for="loginModalUsername">Username</label>
                            </div>
                        </div>
                        <div class="login-input-wrap login-input-wrap-modal">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" /></svg></span>
                                <input type="password" id="loginModalPassword" name="password" placeholder=" " autocomplete="current-password" minlength="12" maxlength="128" required>
                                <label class="login-floating-label" for="loginModalPassword">Password</label>
                            </div>
                            <button type="button" class="login-password-toggle" id="loginPasswordToggle" aria-label="Show password" aria-pressed="false">
                                <span class="login-password-icon login-password-icon-show" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" /></svg></span>
                                <span class="login-password-icon login-password-icon-hide" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4.3 19.7 21l1.4-1.4L4.4 2.9 3 4.3Zm9 3.7c5.5 0 9.9 3.6 11 7-.4 1.3-1.4 2.7-2.7 4l-1.4-1.4c.8-.8 1.4-1.7 1.8-2.6-1.1-2.4-4.4-5-8.7-5-.9 0-1.7.1-2.5.3L8 8.9c1.2-.6 2.6-.9 4-.9Zm0 3a4 4 0 0 1 4 4c0 .7-.2 1.4-.5 2l-5.5-5.5c.6-.3 1.3-.5 2-.5Zm-9 4c.9 2.7 4.1 5.6 8.5 6.1l-1.9-1.9c-2.8-.6-4.9-2.4-5.9-4.2.4-.9 1.1-1.8 2-2.6L4.2 11C3.6 11.6 3.2 12.3 3 13Z" fill="currentColor" /></svg></span>
                            </button>
                        </div>
                        <div class="login-modal-actions-row login-modal-actions-row-split">
                            <a href="#" class="login-forgot-link">Forgot password?</a>
                        </div>
                        <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split"><span>Login</span></button>
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
                            <p class="login-modal-subtitle"><span class="agency-line">Land Transportation Office</span><span class="system-line">Human Resource Information System</span></p>
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
                    <form class="login-form-modern login-form-modal register-form-modal" method="post" action="<?php echo htmlspecialchars($modalFormAction); ?>">
                        <input type="hidden" name="form_action" value="register_modal">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="register-honeypot" aria-hidden="true">
                            <label for="registerWebsite">Website</label>
                            <input type="text" id="registerWebsite" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" /><path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" /></svg></span>
                                <input type="text" id="registerModalFullName" name="full_name" placeholder=" " value="<?php echo htmlspecialchars($registerFullName); ?>" autocomplete="name" maxlength="160" required>
                                <label class="login-floating-label" for="registerModalFullName">Full Name</label>
                            </div>
                        </div>
                        <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                            <div class="login-input-shell">
                                <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.8" /><path d="m6 8 6 4 6-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                                <input type="email" id="registerModalEmail" name="email" placeholder=" " value="<?php echo htmlspecialchars($registerEmail); ?>" autocomplete="email" maxlength="150" required>
                                <label class="login-floating-label" for="registerModalEmail">Email</label>
                            </div>
                        </div>
                        <div class="register-form-grid">
                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z" fill="currentColor" /><path d="M12 14c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor" /></svg></span>
                                    <input type="text" id="registerModalUsername" name="register_username" placeholder=" " value="<?php echo htmlspecialchars($registerUsername); ?>" autocomplete="username" spellcheck="false" minlength="4" maxlength="32" pattern="[A-Za-z0-9._-]{4,32}" required>
                                    <label class="login-floating-label" for="registerModalUsername">Username</label>
                                </div>
                            </div>
                            <div class="login-input-wrap login-input-wrap-modal register-input-wrap">
                                <div class="login-input-shell">
                                    <span class="login-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 0V7a2 2 0 1 1 4 0v2h-4Z" fill="currentColor" /></svg></span>
                                    <input type="password" id="registerModalPassword" name="register_password" placeholder=" " autocomplete="new-password" minlength="12" maxlength="128" required>
                                    <label class="login-floating-label" for="registerModalPassword">Password</label>
                                </div>
                                <button type="button" class="login-password-toggle" id="registerPasswordToggle" aria-label="Show password" aria-pressed="false">
                                    <span class="login-password-icon login-password-icon-show" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 5C6.5 5 2.1 8.6 1 12c1.1 3.4 5.5 7 11 7s9.9-3.6 11-7c-1.1-3.4-5.5-7-11-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" fill="currentColor" /></svg></span>
                                    <span class="login-password-icon login-password-icon-hide" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4.3 19.7 21l1.4-1.4L4.4 2.9 3 4.3Zm9 3.7c5.5 0 9.9 3.6 11 7-.4 1.3-1.4 2.7-2.7 4l-1.4-1.4c.8-.8 1.4-1.7 1.8-2.6-1.1-2.4-4.4-5-8.7-5-.9 0-1.7.1-2.5.3L8 8.9c1.2-.6 2.6-.9 4-.9Zm0 3a4 4 0 0 1 4 4c0 .7-.2 1.4-.5 2l-5.5-5.5c.6-.3 1.3-.5 2-.5Zm-9 4c.9 2.7 4.1 5.6 8.5 6.1l-1.9-1.9c-2.8-.6-4.9-2.4-5.9-4.2.4-.9 1.1-1.8 2-2.6L4.2 11C3.6 11.6 3.2 12.3 3 13Z" fill="currentColor" /></svg></span>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="login-submit-btn login-submit-btn-modal login-submit-btn-modal-split register-submit-btn-modal"><span>Register</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
