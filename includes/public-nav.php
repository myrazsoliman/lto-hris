<?php
if (!isset($currentPage) || $currentPage === '') {
    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
}

if (!isset($loginHref) || $loginHref === '') {
    $loginHref = 'login.php';
}

if (!isset($registerHref) || $registerHref === '') {
    $registerHref = 'register.php';
}

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

$contactLink = ['label' => 'Contact Us', 'href' => 'contact-us.php', 'active' => $currentPage === 'contact-us.php', 'caret' => false];
?>
<div class="gov-topbar">
    <div class="gov-inner">
        <div class="gov-left">
            <a href="index.php" class="gov-left-title">LTO-HRIS</a>
            <span class="gov-left-subtitle">Human Resources Information System</span>
        </div>
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
            <input placeholder="Search..." aria-label="Search">
        </div>
        <div class="gov-actions">
            <a class="btn btn-login js-login-trigger" href="<?php echo htmlspecialchars($loginHref); ?>">Login</a>
            <a class="btn btn-register js-register-trigger" href="<?php echo htmlspecialchars($registerHref); ?>">Register</a>
        </div>
        <button class="gov-menu-toggle" type="button" aria-expanded="false" aria-controls="govMobilePanel" aria-label="Open navigation menu">
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
            <a class="btn btn-login js-login-trigger" href="<?php echo htmlspecialchars($loginHref); ?>">Login</a>
            <a class="btn btn-register js-register-trigger" href="<?php echo htmlspecialchars($registerHref); ?>">Register</a>
        </div>
    </div>
</div>
