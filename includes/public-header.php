<?php
if (!isset($currentPage) || $currentPage === '') {
    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
}
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
            <input placeholder="Search..." aria-label="Search">
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
                <p class="office-address">Brgy. Sta. Clara Sur, Pila, Laguna, Philippines</p>
            </div>
        </div>
        <div class="right-info">
            <div class="time">Philippine Standard Time:<br><span id="pstClock"></span></div>
        </div>
    </div>
</header>
