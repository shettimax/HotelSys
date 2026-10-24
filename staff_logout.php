<?php
// staff_logout.php - Ends a staff session (separate from admin session)

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (is_staff_logged_in()) {
    staff_logout();
}

header('Location: admin_login.php');
exit;
