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
        <a href="contact-us.php" class="gov-contact-link <?php echo $currentPage === 'contact-us.php' ? 'active' : ''; ?>">Contact Us</a>
        <div class="gov-search">
            <input placeholder="Search..." aria-label="Search">
        </div>
        <div class="gov-actions">
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
