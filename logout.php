<?php
// Simple logout endpoint
require_once 'includes/auth.php';
logout_user();
header('Location: index.php');
exit;
