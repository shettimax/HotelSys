<?php
// session.php - Minimal session handling
// Updated for PHP 5.6
// NO duplicate functions here!

// Start session only if not already started
if (session_id() == '' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token functions
function get_csrf_token() {
    // Generate token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    return $_SESSION['csrf_token'];
}

// Flash message functions
function set_flash_message($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = array();
    }
    $_SESSION['flash_messages'][] = array(
        'type' => $type,
        'message' => $message
    );
}

function get_flash_messages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return array();
}

// Session cleanup
function session_cleanup() {
    // Remove old flash messages
    if (isset($_SESSION['flash_messages']) && count($_SESSION['flash_messages']) > 10) {
        array_shift($_SESSION['flash_messages']);
    }
}
?>