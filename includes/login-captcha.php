<?php
// Login modal CAPTCHA helpers.
// Stores the expected code + nonce in the session (same keys used by captcha-image.php).

function login_captcha_issue_challenge(): string
{
    // Mixed-case CAPTCHA (avoid ambiguous chars like I, l, O, o).
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $captchaCode = '';

    // Ensure at least 1 uppercase + 1 lowercase in a 5-char code.
    $captchaCode .= $upper[random_int(0, strlen($upper) - 1)];
    $captchaCode .= $lower[random_int(0, strlen($lower) - 1)];

    $all = $upper . $lower;
    for ($i = 0; $i < 3; $i++) {
        $captchaCode .= $all[random_int(0, strlen($all) - 1)];
    }

    // Shuffle.
    $captchaCode = str_shuffle($captchaCode);

    $_SESSION['login_modal_captcha_expected'] = $captchaCode;
    $_SESSION['login_modal_captcha_nonce'] = bin2hex(random_bytes(8));

    return (string) $_SESSION['login_modal_captcha_nonce'];
}

function login_captcha_ensure_nonce(): string
{
    if ((string) ($_SESSION['login_modal_captcha_expected'] ?? '') === '') {
        return login_captcha_issue_challenge();
    }
    $nonce = (string) ($_SESSION['login_modal_captcha_nonce'] ?? '');
    if ($nonce === '') {
        return login_captcha_issue_challenge();
    }
    return $nonce;
}

function login_captcha_verify_answer(string $input): bool
{
    $expected = (string) ($_SESSION['login_modal_captcha_expected'] ?? '');
    $normalized = trim($input);
    if ($normalized === '' || $expected === '') {
        return false;
    }
    // Case-sensitive verification (because the code contains upper + lower case).
    $ok = hash_equals($expected, $normalized);
    if ($ok) {
        unset($_SESSION['login_modal_captcha_expected'], $_SESSION['login_modal_captcha_nonce']);
    }
    return $ok;
}
