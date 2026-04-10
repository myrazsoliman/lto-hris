<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$context = isset($_GET['context']) ? (string) $_GET['context'] : '';
if ($context !== 'login_modal') {
    http_response_code(404);
    exit;
}

function generate_login_captcha_code()
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $alphabetLength = strlen($alphabet) - 1;
    $captchaCode = '';

    for ($i = 0; $i < 5; $i++) {
        $captchaCode .= $alphabet[random_int(0, $alphabetLength)];
    }

    $_SESSION['login_modal_captcha_expected'] = $captchaCode;
    $_SESSION['login_modal_captcha_nonce'] = bin2hex(random_bytes(8));

    return $captchaCode;
}

$needsRegeneration = ($_GET['regen'] ?? '') === '1';
$captchaCode = (string) ($_SESSION['login_modal_captcha_expected'] ?? '');
if ($needsRegeneration || $captchaCode === '') {
    $captchaCode = generate_login_captcha_code();
}

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$safeCaptchaCode = htmlspecialchars($captchaCode, ENT_QUOTES, 'UTF-8');
$textX = [18, 52, 86, 120, 154];
$textY = [46, 44, 47, 45, 46];
$textRotate = [-8, 6, -4, 7, -6];
$textColor = ['#223447', '#1d364b', '#2f2b1f', '#152a3d', '#2c2c2c'];
?>
<svg xmlns="http://www.w3.org/2000/svg" width="190" height="62" viewBox="0 0 190 62" role="img" aria-label="Security captcha">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f6f8fb" />
      <stop offset="100%" stop-color="#edf2f8" />
    </linearGradient>
  </defs>
  <rect x="0" y="0" width="190" height="62" fill="url(#bg)" rx="6" />
  <g opacity="0.42">
    <?php for ($i = 0; $i < 44; $i++): ?>
      <circle cx="<?php echo random_int(1, 188); ?>" cy="<?php echo random_int(1, 60); ?>" r="<?php echo random_int(1, 2); ?>" fill="#b9c2cf" />
    <?php endfor; ?>
  </g>
  <g opacity="0.55">
    <?php for ($i = 0; $i < 6; $i++): ?>
      <line
        x1="<?php echo random_int(2, 44); ?>"
        y1="<?php echo random_int(4, 58); ?>"
        x2="<?php echo random_int(144, 188); ?>"
        y2="<?php echo random_int(4, 58); ?>"
        stroke="#9ca7b8"
        stroke-width="<?php echo random_int(1, 2); ?>"
      />
    <?php endfor; ?>
  </g>
  <g font-family="Georgia, 'Times New Roman', serif" font-size="46" font-weight="700">
    <?php foreach (str_split($safeCaptchaCode) as $idx => $char): ?>
      <text
        x="<?php echo $textX[$idx] ?? 18; ?>"
        y="<?php echo $textY[$idx] ?? 46; ?>"
        fill="<?php echo $textColor[$idx] ?? '#1e2f45'; ?>"
        transform="rotate(<?php echo $textRotate[$idx] ?? 0; ?> <?php echo $textX[$idx] ?? 18; ?> <?php echo $textY[$idx] ?? 46; ?>)"
      ><?php echo $char; ?></text>
    <?php endforeach; ?>
  </g>
</svg>
