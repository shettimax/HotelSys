<?php
// compatibility.php - Updated for PHP 5.6
// No changes needed

// This file is kept for backward compatibility but session handling is now in functions.php

// Only define these if they haven't been defined elsewhere
if (!defined('PHP_SESSION_NONE')) {
    define('PHP_SESSION_NONE', 0);
}

if (!defined('PHP_SESSION_ACTIVE')) {
    define('PHP_SESSION_ACTIVE', 1);
}

if (!defined('PHP_SESSION_DISABLED')) {
    define('PHP_SESSION_DISABLED', 2);
}

// Keep the function for backward compatibility
function session_safe_start() {
    if (session_id() == '' && session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
?>