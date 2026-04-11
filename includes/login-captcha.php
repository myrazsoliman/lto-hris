<?php
// Login modal CAPTCHA helpers.
// Stores the expected code + nonce in the session (same keys used by captcha-image.php).

function login_captcha_issue_challenge(): string
{
    // 5-char CAPTCHA with exactly 2 digits (avoid ambiguous chars like I, l, O, o, 0, 1),
    // and no repeated letters/digits (case-insensitive for letters).
    $letterBases = str_split('ABCDEFGHJKLMNPQRSTUVWXYZ'); // no I/O
    $digits = str_split('23456789'); // no 0/1

    // Pick 3 unique base letters.
    shuffle($letterBases);
    $pickedLetters = array_slice($letterBases, 0, 3);

    // Force at least 1 upper + 1 lower in the letters.
    $letters = [
        strtoupper($pickedLetters[0]),
        strtolower($pickedLetters[1]),
        (random_int(0, 1) === 1) ? strtoupper($pickedLetters[2]) : strtolower($pickedLetters[2]),
    ];

    // Pick 2 unique digits.
    shuffle($digits);
    $pickedDigits = array_slice($digits, 0, 2);

    // Combine and shuffle (still unique by construction).
    $captchaChars = array_merge($letters, $pickedDigits);
    shuffle($captchaChars);
    $captchaCode = implode('', $captchaChars);

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
    // Case-sensitive verification (the code contains both upper + lower case).
    $ok = hash_equals($expected, $normalized);
    if ($ok) {
        unset($_SESSION['login_modal_captcha_expected'], $_SESSION['login_modal_captcha_nonce']);
    }
    return $ok;
}
