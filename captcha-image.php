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
    // 5-char CAPTCHA with exactly 2 digits (avoid ambiguous chars like I, l, O, o, 0, 1),
    // and no repeated letters/digits (case-insensitive for letters).
    $letterBases = str_split('ABCDEFGHJKLMNPQRSTUVWXYZ'); // base letters (no I/O)
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
$captchaChars = str_split($captchaCode);
$captchaLen = max(1, count($captchaChars));

$logoDataUri = '';
$logoPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'captcha-logo.png';
if (is_file($logoPath)) {
    $logoBytes = @file_get_contents($logoPath);
    if ($logoBytes !== false && $logoBytes !== '') {
        $logoDataUri = 'data:image/png;base64,' . base64_encode($logoBytes);
    }
}

// Center the characters horizontally in the 190px wide canvas.
$canvasW = 190;
$canvasH = 62;
$centerX = (int) round($canvasW / 2);
$charSpacing = 32; // close to the old spacing, but centered
$firstX = (int) round($centerX - (($captchaLen - 1) / 2) * $charSpacing);
$baseY = 44; // slightly higher baseline to avoid descenders being cropped

$textRotate = [-10, 8, -6, 9, -8];
$textColor = ['#13273a', '#1d364b', '#2f2b1f', '#152a3d', '#2c2c2c'];

// Visual hardening against simple OCR (still user-readable).
$noiseSeed = random_int(1, 9999);
$bfX = random_int(10, 20) / 1000; // 0.010 - 0.020 (less distortion, clearer)
$bfY = random_int(14, 28) / 1000; // 0.014 - 0.028
$warpScale = random_int(8, 11); // wavy but less likely to clip
$blurStd = random_int(32, 58) / 100; // 0.32 - 0.58 (keep readable even with stronger warp)
$waveAmp = random_int(4, 7); // px (kept readable; avoids vertical cropping)
$waveCycles = random_int(1, 2);
$wavePhase = random_int(0, 628) / 100; // 0..6.28
$fontFamilies = [
    "Georgia, 'Times New Roman', serif",
    "Cambria, 'Times New Roman', serif",
    "Trebuchet MS, Arial, sans-serif",
    "Verdana, Arial, sans-serif",
];

// Precompute glyph geometry once so we can reuse it for:
// 1) the rendered text and
// 2) masks that apply grain specifically on/around letters.
$glyphs = [];
foreach ($captchaChars as $idx => $rawChar) {
    $baseX = $firstX + ($idx * $charSpacing);
    $x = $baseX + random_int(-3, 3);
    $pos = ($captchaLen <= 1) ? 0.0 : ($idx / ($captchaLen - 1));
    $angle = ($pos * (2 * pi()) * $waveCycles) + $wavePhase;
    $waveOffset = (int) round(sin($angle) * $waveAmp);
    $y = $baseY + random_int(-1, 1) + $waveOffset;
    $rot = (int) (($textRotate[$idx] ?? 0) + random_int(-4, 4));
    $fontSize = random_int(40, 46);
    $family = $fontFamilies[random_int(0, count($fontFamilies) - 1)];
    $skx = random_int(-4, 4);
    $sky = random_int(-2, 2);
    $weight = random_int(650, 800);
    $color = (string) ($textColor[$idx] ?? '#1e2f45');

    $glyphs[] = [
        'char' => (string) $rawChar,
        'x' => (int) $x,
        'y' => (int) $y,
        'rot' => (int) $rot,
        'size' => (int) $fontSize,
        'family' => (string) $family,
        'skx' => (int) $skx,
        'sky' => (int) $sky,
        'weight' => (int) $weight,
        'color' => $color,
    ];
}

// Keep the whole CAPTCHA text visually centered even with per-character jitter/waves.
// (Recenter by average glyph position; avoids "drifting" left/right/up/down between regenerations.)
if (count($glyphs) > 0) {
    $sumX = 0;
    $sumY = 0;
    foreach ($glyphs as $g) {
        $sumX += (int) $g['x'];
        $sumY += (int) $g['y'];
    }

    $meanX = $sumX / count($glyphs);
    $meanY = $sumY / count($glyphs);
    $shiftX = $centerX - $meanX;
    $shiftY = $baseY - $meanY;

    foreach ($glyphs as &$g) {
        $g['x'] = (int) round($g['x'] + $shiftX);
        $g['y'] = (int) round($g['y'] + $shiftY);
    }
    unset($g);
}

// Clamp glyphs inside the visible canvas so letters don't get cropped,
// even with rotation/skew/wave baseline.
if (count($glyphs) > 0) {
    $pad = 8;
    foreach ($glyphs as &$g) {
        $size = (int) $g['size'];

        // Rough text extents for typical fonts: ascent ~0.86em, descent ~0.18em.
        // The SVG `y` is the baseline.
        $minBaseline = $pad + (int) round($size * 0.86);
        $maxBaseline = $canvasH - $pad - (int) round($size * 0.18);

        // Extra horizontal padding (rotation can push edges).
        $hPad = $pad + (int) round($size * 0.35);
        $minX = $hPad;
        $maxX = $canvasW - $hPad;

        $g['x'] = (int) max($minX, min($maxX, (int) $g['x']));
        $g['y'] = (int) max($minBaseline, min($maxBaseline, (int) $g['y']));
    }
    unset($g);
}
?>
<svg xmlns="http://www.w3.org/2000/svg" width="190" height="62" viewBox="0 0 190 62" role="img" aria-label="Security captcha">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f6f8fb" />
      <stop offset="100%" stop-color="#edf2f8" />
    </linearGradient>

    <!-- Warp filter to distort glyph shapes slightly (anti-OCR). -->
    <filter id="warp" x="-12%" y="-22%" width="124%" height="144%">
      <feTurbulence type="fractalNoise" baseFrequency="<?php echo htmlspecialchars((string) $bfX); ?> <?php echo htmlspecialchars((string) $bfY); ?>" numOctaves="2" seed="<?php echo (int) $noiseSeed; ?>" result="noise" />
      <feDisplacementMap in="SourceGraphic" in2="noise" scale="<?php echo (int) $warpScale; ?>" xChannelSelector="R" yChannelSelector="G" result="warped1" />
      <!-- Second displacement pass for a less uniform warp (kept subtle). -->
      <feTurbulence type="turbulence" baseFrequency="<?php echo htmlspecialchars((string) ($bfX * 0.92)); ?> <?php echo htmlspecialchars((string) ($bfY * 0.86)); ?>" numOctaves="1" seed="<?php echo (int) ($noiseSeed + 17); ?>" result="noise2" />
      <feDisplacementMap in="warped1" in2="noise2" scale="<?php echo (int) max(2, $warpScale - 4); ?>" xChannelSelector="B" yChannelSelector="R" result="warped2" />
      <!-- Blur to reduce crisp edges (anti-OCR). -->
      <feGaussianBlur in="warped2" stdDeviation="<?php echo htmlspecialchars((string) $blurStd); ?>" />
    </filter>

    <!-- Glassy overlay distortion to add "design" + break clean edges. -->
    <filter id="glass" x="-10%" y="-20%" width="120%" height="140%">
      <feTurbulence type="turbulence" baseFrequency="<?php echo htmlspecialchars((string) ($bfX * 0.62)); ?> <?php echo htmlspecialchars((string) ($bfY * 0.62)); ?>" numOctaves="2" seed="<?php echo (int) ($noiseSeed + 101); ?>" result="gNoise" />
      <feDisplacementMap in="SourceGraphic" in2="gNoise" scale="<?php echo (int) random_int(7, 11); ?>" xChannelSelector="R" yChannelSelector="G" />
    </filter>

    <!-- Second glass pass to add more distortion texture (kept subtle overall). -->
    <filter id="glass2" x="-10%" y="-20%" width="120%" height="140%">
      <feTurbulence type="turbulence" baseFrequency="<?php echo htmlspecialchars((string) ($bfX * 0.78)); ?> <?php echo htmlspecialchars((string) ($bfY * 0.78)); ?>" numOctaves="2" seed="<?php echo (int) ($noiseSeed + 505); ?>" result="gNoise2" />
      <feDisplacementMap in="SourceGraphic" in2="gNoise2" scale="<?php echo (int) random_int(10, 15); ?>" xChannelSelector="B" yChannelSelector="R" />
    </filter>

    <!-- Grain overlay (fine noise) -->
    <filter id="grain" x="-10%" y="-20%" width="120%" height="140%">
      <feTurbulence type="fractalNoise" baseFrequency="<?php echo htmlspecialchars((string) ($bfX * 2.1)); ?> <?php echo htmlspecialchars((string) ($bfY * 2.1)); ?>" numOctaves="2" seed="<?php echo (int) ($noiseSeed + 303); ?>" result="grainNoise"/>
      <feColorMatrix type="matrix" values="
        0.33 0.33 0.33 0 0
        0.33 0.33 0.33 0 0
        0.33 0.33 0.33 0 0
        0    0    0    1 0" in="grainNoise" result="grainGray"/>
      <feComponentTransfer in="grainGray">
        <feFuncA type="table" tableValues="0 0.45"/>
      </feComponentTransfer>
    </filter>

    <!-- Masks to apply grain on top of letters and around them (halo). -->
    <mask id="captchaTextMask" maskUnits="userSpaceOnUse" x="0" y="0" width="190" height="62">
      <rect x="0" y="0" width="190" height="62" fill="#000"/>
      <g fill="#fff">
        <?php foreach ($glyphs as $g): ?>
          <text
            x="<?php echo (int) $g['x']; ?>"
            y="<?php echo (int) $g['y']; ?>"
            font-family="<?php echo htmlspecialchars((string) $g['family'], ENT_QUOTES, 'UTF-8'); ?>"
            font-size="<?php echo (int) $g['size']; ?>"
            font-weight="<?php echo (int) $g['weight']; ?>"
            text-anchor="middle"
            transform="rotate(<?php echo (int) $g['rot']; ?> <?php echo (int) $g['x']; ?> <?php echo (int) $g['y']; ?>) skewX(<?php echo (int) $g['skx']; ?>) skewY(<?php echo (int) $g['sky']; ?>)"
          ><?php echo htmlspecialchars((string) $g['char'], ENT_QUOTES, 'UTF-8'); ?></text>
        <?php endforeach; ?>
      </g>
    </mask>

    <mask id="captchaHaloMask" maskUnits="userSpaceOnUse" x="0" y="0" width="190" height="62">
      <rect x="0" y="0" width="190" height="62" fill="#000"/>
      <g fill="#fff" stroke="#fff" stroke-width="6" paint-order="stroke">
        <?php foreach ($glyphs as $g): ?>
          <text
            x="<?php echo (int) $g['x']; ?>"
            y="<?php echo (int) $g['y']; ?>"
            font-family="<?php echo htmlspecialchars((string) $g['family'], ENT_QUOTES, 'UTF-8'); ?>"
            font-size="<?php echo (int) $g['size']; ?>"
            font-weight="<?php echo (int) $g['weight']; ?>"
            text-anchor="middle"
            transform="rotate(<?php echo (int) $g['rot']; ?> <?php echo (int) $g['x']; ?> <?php echo (int) $g['y']; ?>) skewX(<?php echo (int) $g['skx']; ?>) skewY(<?php echo (int) $g['sky']; ?>)"
          ><?php echo htmlspecialchars((string) $g['char'], ENT_QUOTES, 'UTF-8'); ?></text>
        <?php endforeach; ?>
      </g>
    </mask>
  </defs>
  <rect x="0" y="0" width="190" height="62" fill="url(#bg)" rx="6" />

  <?php if ($logoDataUri !== ''): ?>
    <!-- Logo watermark (embedded data URI so it loads inside <img>-rendered SVG). -->
    <image
      href="<?php echo htmlspecialchars($logoDataUri, ENT_QUOTES, 'UTF-8'); ?>"
      x="10"
      y="8"
      width="22"
      height="22"
      opacity="0.16"
      filter="url(#glass2)"
      preserveAspectRatio="xMidYMid meet"
    />
  <?php endif; ?>

  <!-- Distortion overlay (subtle) -->
  <rect x="0" y="0" width="190" height="62" rx="6" fill="#ffffff" opacity="0.08" filter="url(#glass)"/>
  <rect x="0" y="0" width="190" height="62" rx="6" fill="#ffffff" opacity="0.06" filter="url(#glass2)"/>
  <!-- Grain overlay -->
  <rect x="0" y="0" width="190" height="62" rx="6" fill="#000000" opacity="0.18" filter="url(#grain)"/>
  <g opacity="0.50">
    <?php for ($i = 0; $i < 84; $i++): ?>
      <circle cx="<?php echo random_int(1, 188); ?>" cy="<?php echo random_int(1, 60); ?>" r="<?php echo random_int(1, 2); ?>" fill="#b3bdcb" />
    <?php endfor; ?>
  </g>
  <g opacity="0.62">
    <?php for ($i = 0; $i < 10; $i++): ?>
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

  <!-- Primary text (warped + per-character jitter). -->
  <g filter="url(#warp)">
    <?php foreach ($glyphs as $g): ?>
      <text
        x="<?php echo (int) $g['x']; ?>"
        y="<?php echo (int) $g['y']; ?>"
        fill="<?php echo htmlspecialchars((string) $g['color'], ENT_QUOTES, 'UTF-8'); ?>"
        font-family="<?php echo htmlspecialchars((string) $g['family'], ENT_QUOTES, 'UTF-8'); ?>"
        font-size="<?php echo (int) $g['size']; ?>"
        font-weight="<?php echo (int) $g['weight']; ?>"
        text-anchor="middle"
        transform="rotate(<?php echo (int) $g['rot']; ?> <?php echo (int) $g['x']; ?> <?php echo (int) $g['y']; ?>) skewX(<?php echo (int) $g['skx']; ?>) skewY(<?php echo (int) $g['sky']; ?>)"
      ><?php echo htmlspecialchars((string) $g['char'], ENT_QUOTES, 'UTF-8'); ?></text>
    <?php endforeach; ?>
  </g>

  <!-- Targeted grain: on top of letters + around letters (halo) -->
  <rect x="0" y="0" width="190" height="62" rx="6" fill="#000000" opacity="0.26" filter="url(#grain)" mask="url(#captchaHaloMask)"/>
  <rect x="0" y="0" width="190" height="62" rx="6" fill="#000000" opacity="0.32" filter="url(#grain)" mask="url(#captchaTextMask)"/>

  <!-- Foreground scratches above text (more OCR resistance). -->
  <g opacity="0.55">
    <?php for ($i = 0; $i < 4; $i++): ?>
      <path
        d="M<?php echo random_int(0, 18); ?> <?php echo random_int(8, 54); ?> C<?php echo random_int(30, 60); ?> <?php echo random_int(0, 62); ?>, <?php echo random_int(90, 120); ?> <?php echo random_int(0, 62); ?>, <?php echo random_int(172, 190); ?> <?php echo random_int(8, 54); ?>"
        fill="none"
        stroke="<?php echo ['#6f7c8f', '#7d8aa0', '#8a97ad'][random_int(0, 2)]; ?>"
        stroke-width="<?php echo random_int(1, 2); ?>"
        stroke-linecap="round"
      />
    <?php endfor; ?>
  </g>
</svg>
