<?php
require_once 'includes/auth.php';

// Clear all failed login attempts
clear_failed_attempts('login');

// Also clear any registration attempts just in case
clear_failed_attempts('register');

echo "<h2>Login Attempts Reset</h2>";
echo "<p>All failed login attempts have been cleared. You can now try logging in again.</p>";
echo "<p><a href='index.php?login=1'>Click here to login</a></p>";
echo "<p><small>This page resets the rate limiting counter. You can bookmark this for future use if you get locked out again.</small></p>";

// Auto-redirect after 3 seconds
echo "<script>
setTimeout(function() {
    window.location.href = 'index.php?login=1';
}, 3000);
</script>";
?>
