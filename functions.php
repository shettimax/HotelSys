<?php
// functions.php – fully compatible with PHP 5.6
// Contains ALL reusable functions including authentication
// UPDATED: Self-access permission system - users can view/edit their own data

// Start session first
if (session_id() == '' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone from config
$config_path = __DIR__ . '/config.php';
if (file_exists($config_path)) {
    $config = include $config_path;
    if (isset($config['timezone']) && $config['timezone']) {
        date_default_timezone_set($config['timezone']);
    } else {
        date_default_timezone_set('Africa/Lagos');
    }
} else {
    date_default_timezone_set('Africa/Lagos');
}

// Include database connection
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

// ------------------------------------------------------------------
// Security & Output Functions
// ------------------------------------------------------------------

function h($string) { 
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); 
}

function format_currency($amount) {
    return '₦' . number_format((float)$amount, 2);
}

// Alias for formatPrice for backward compatibility
function formatPrice($amount) {
    return format_currency($amount);
}

function generate_invoice_number($conn) {
    do {
        $num = 'INV' . time() . rand(100, 999);
        $sql = "SELECT 1 FROM invoices WHERE invoice_number = '$num' LIMIT 1";
        $res = mysqli_query($conn, $sql);
    } while (mysqli_fetch_assoc($res));
    return $num;
}

// ------------------------------------------------------------------
// Admin Activity Logging Function
// ------------------------------------------------------------------

function log_admin_activity($action, $description = '') {
    global $conn;

    if (!is_admin_logged_in()) {
        return false;
    }

    $admin_id = (int)get_current_admin_id();
    $admin_name = get_current_admin_username();

    if (!$admin_id || !$admin_name) {
        return false;
    }
    
    // Escape strings for safety
    $admin_name_esc = mysqli_real_escape_string($conn, $admin_name);
    $action_esc = mysqli_real_escape_string($conn, $action);
    $desc_esc = mysqli_real_escape_string($conn, $description);
    $ip = isset($_SERVER['REMOTE_ADDR']) ? mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']) : 'unknown';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT']) : 'unknown';
    
    $sql = "INSERT INTO admin_logs 
            (admin_id, admin_name, action, description, ip_address, user_agent, created_at)
            VALUES ($admin_id, '$admin_name_esc', '$action_esc', '$desc_esc', '$ip', '$ua', NOW())";
    
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        error_log("log_admin_activity() - Query failed: " . mysqli_error($conn));
    }

    return $result;
}

// ------------------------------------------------------------------
// Authentication Functions
// ------------------------------------------------------------------

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function get_current_admin_id() {
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

function get_current_admin_username() {
    return isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : null;
}

function get_current_admin_fullname() {
    return isset($_SESSION['admin_fullname']) ? $_SESSION['admin_fullname'] : get_current_admin_username();
}

function admin_login($username, $password, $conn) {
    $username = mysqli_real_escape_string($conn, trim($username));
    $sql = "SELECT id, username, password, fullname FROM users_admin WHERE username = '$username' LIMIT 1";
    $res = mysqli_query($conn, $sql);
    
    if ($user = mysqli_fetch_assoc($res)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_id'] = (int)$user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_fullname'] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];
            return true;
        }
    }
    return false;
}

function admin_logout() {
    unset($_SESSION['admin_logged']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_fullname']);
    session_destroy();
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Location: admin_login.php');
        exit;
    }
}

// ------------------------------------------------------------------
// Staff Login
// ------------------------------------------------------------------

function is_staff_logged_in() {
    return isset($_SESSION['staff_logged']) && $_SESSION['staff_logged'] === true;
}

function get_current_staff_id() {
    return isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : null;
}

function get_current_staff_name() {
    return isset($_SESSION['staff_name']) ? $_SESSION['staff_name'] : null;
}

function staff_login($pnumber, $password, $conn) {
    if (!db_column_exists($conn, 'staff', 'password_hash')) {
        return false;
    }
    $pnumber = mysqli_real_escape_string($conn, trim($pnumber));
    $sql = "SELECT id, pnumber, name, password_hash, status FROM staff WHERE pnumber = '$pnumber' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($staff = mysqli_fetch_assoc($res)) {
        if ($staff['status'] !== 'active') {
            return false;
        }
        if (!empty($staff['password_hash']) && password_verify($password, $staff['password_hash'])) {
            $_SESSION['staff_logged'] = true;
            $_SESSION['staff_id'] = (int)$staff['id'];
            $_SESSION['staff_pnumber'] = $staff['pnumber'];
            $_SESSION['staff_name'] = $staff['name'];
            return true;
        }
    }
    return false;
}

function staff_logout() {
    unset($_SESSION['staff_logged']);
    unset($_SESSION['staff_id']);
    unset($_SESSION['staff_pnumber']);
    unset($_SESSION['staff_name']);
}

function require_staff_login() {
    if (!is_staff_logged_in()) {
        header('Location: admin_login.php');
        exit;
    }
}

// ------------------------------------------------------------------
// DATABASE-DRIVEN PERMISSION SYSTEM
// ------------------------------------------------------------------

/**
 * Check if a column exists in a table
 */
function db_column_exists($conn, $table, $column) {
    static $cache = array();
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $table_esc = mysqli_real_escape_string($conn, $table);
    $column_esc = mysqli_real_escape_string($conn, $column);
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_esc' AND COLUMN_NAME = '$column_esc'";
    $res = mysqli_query($conn, $sql);
    $exists = false;
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $exists = ((int)$row['cnt']) > 0;
    }
    $cache[$key] = $exists;
    return $exists;
}

/**
 * Check if roles/permissions tables exist (migration check)
 */
function permission_tables_exist($conn) {
    $tables = ['roles', 'permissions', 'role_permissions', 'page_permissions'];
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $res = mysqli_query($conn, $sql);
        if (mysqli_num_rows($res) == 0) {
            return false;
        }
    }
    return true;
}

/**
 * Get current user's role ID (legacy mode fallback)
 */
function get_current_role_id() {
    global $conn;
    
    // If permission tables don't exist, use legacy role column
    if (!permission_tables_exist($conn)) {
        return get_legacy_role_id();
    }
    
    $admin_id = get_current_admin_id();
    if (!$admin_id) return null;
    
    // Check if users_admin has role_id column
    if (db_column_exists($conn, 'users_admin', 'role_id')) {
        $sql = "SELECT role_id FROM users_admin WHERE id = " . (int)$admin_id;
        $res = mysqli_query($conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            return (int)$row['role_id'];
        }
    }
    
    // Fallback: get role from legacy column
    return get_legacy_role_id();
}

/**
 * Legacy role lookup (for backward compatibility)
 */
function get_legacy_role_id() {
    global $conn;
    $admin_id = get_current_admin_id();
    if (!$admin_id) return null;
    
    // Get role name from legacy column
    if (db_column_exists($conn, 'users_admin', 'role')) {
        $sql = "SELECT role FROM users_admin WHERE id = " . (int)$admin_id;
        $res = mysqli_query($conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $role_name = $row['role'];
            // Map legacy role to role_id
            $sql2 = "SELECT id FROM roles WHERE name = '$role_name'";
            $res2 = mysqli_query($conn, $sql2);
            if ($res2 && ($row2 = mysqli_fetch_assoc($res2))) {
                return (int)$row2['id'];
            }
        }
    }
    
    // Default: super_admin (id 1)
    return 1;
}

/**
 * Get current user's role name
 */
function get_current_role_name() {
    global $conn;
    
    // If permission tables don't exist, use legacy role column
    if (!permission_tables_exist($conn)) {
        return get_legacy_role_name();
    }
    
    $role_id = get_current_role_id();
    if (!$role_id) return null;
    
    $sql = "SELECT name FROM roles WHERE id = " . (int)$role_id;
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return $row['name'];
    }
    return null;
}

/**
 * Legacy role name lookup
 */
function get_legacy_role_name() {
    global $conn;
    $admin_id = get_current_admin_id();
    if (!$admin_id) return 'super_admin';
    
    if (db_column_exists($conn, 'users_admin', 'role')) {
        $sql = "SELECT role FROM users_admin WHERE id = " . (int)$admin_id;
        $res = mysqli_query($conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res)) && !empty($row['role'])) {
            return $row['role'];
        }
    }
    return 'super_admin';
}

/**
 * Get all permissions for a role
 */
function get_role_permissions($role_id) {
    global $conn;
    
    // If permission tables don't exist, use legacy mapping
    if (!permission_tables_exist($conn)) {
        return get_legacy_role_permissions($role_id);
    }
    
    $role_id = (int)$role_id;
    $permissions = [];
    
    $sql = "SELECT p.name, p.display_name, p.category 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = $role_id";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $permissions[] = $row['name'];
    }
    return $permissions;
}

/**
 * Legacy permission mapping (hardcoded fallback)
 */
function get_legacy_role_permissions($role_id) {
    // Map role ID to role name
    global $conn;
    $sql = "SELECT name FROM roles WHERE id = " . (int)$role_id;
    $res = mysqli_query($conn, $sql);
    $role_name = $res ? mysqli_fetch_assoc($res)['name'] : 'receptionist';
    
    $legacy_map = [
        'super_admin' => ['*'], // Wildcard = all permissions
        'manager' => ['view_dashboard', 'front_desk', 'finance', 'view_logs', 'view_reports', 'manage_rooms', 'manage_staff', 'manage_users'],
        'receptionist' => ['view_dashboard', 'front_desk', 'finance'],
        'security' => ['view_dashboard', 'front_desk'],
        'housekeeping' => ['view_dashboard', 'manage_rooms']
    ];
    
    return isset($legacy_map[$role_name]) ? $legacy_map[$role_name] : ['view_dashboard'];
}

/**
 * Check if current user has a specific permission
 */
function has_permission($permission_name) {
    global $conn;
    
    // Get current user's role
    $role_id = get_current_role_id();
    if (!$role_id) return false;
    
    // Super admin has everything
    $role_name = get_current_role_name();
    if ($role_name === 'super_admin') return true;
    
    // If permission tables don't exist, use legacy mapping
    if (!permission_tables_exist($conn)) {
        $permissions = get_legacy_role_permissions($role_id);
        return in_array($permission_name, $permissions) || in_array('*', $permissions);
    }
    
    // Check if role has this permission (cached)
    static $permission_cache = array();
    $cache_key = $role_id . '_' . $permission_name;
    if (isset($permission_cache[$cache_key])) {
        return $permission_cache[$cache_key];
    }
    
    $permission_name = mysqli_real_escape_string($conn, $permission_name);
    $sql = "SELECT 1 FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = $role_id AND p.name = '$permission_name'";
    $res = mysqli_query($conn, $sql);
    $result = mysqli_num_rows($res) > 0;
    
    $permission_cache[$cache_key] = $result;
    return $result;
}

/**
 * Check if current user can access a page
 */
function can_access_page($page) {
    global $conn;
    
    // If permission tables don't exist, use legacy page map
    if (!permission_tables_exist($conn)) {
        return can_access_page_legacy($page);
    }
    
    $page = mysqli_real_escape_string($conn, $page);
    $sql = "SELECT p.name FROM page_permissions pp
            JOIN permissions p ON pp.permission_id = p.id
            WHERE pp.page = '$page'";
    $res = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($res) === 0) {
        return true; // No permission required for this page
    }
    
    $row = mysqli_fetch_assoc($res);
    return has_permission($row['name']);
}

/**
 * Legacy page permission check (hardcoded fallback)
 */
function can_access_page_legacy($page) {
    $legacy_map = [
        'admin_users.php' => 'manage_users',
        'admin_logs.php' => 'view_logs',
        'admin_reports.php' => 'view_reports',
        'admin_settings.php' => 'manage_settings',
        'admin_manage_rooms.php' => 'manage_rooms',
        'admin_manage_blocks.php' => 'manage_rooms',
        'admin_staff.php' => 'manage_staff',
        'admin_checkin.php' => 'front_desk',
        'admin_checkout.php' => 'front_desk',
        'admin_cancellation.php' => 'front_desk',
        'admin_extend_stay.php' => 'front_desk',
        'admin_mark_paid.php' => 'finance',
        'admin_refunds.php' => 'finance',
        'admin_dispute.php' => 'finance',
        'admin_resolve_dispute.php' => 'finance'
    ];
    
    $required = isset($legacy_map[$page]) ? $legacy_map[$page] : 'view_dashboard';
    return has_permission($required);
}

// ============================================================
// SELF-ACCESS PERMISSION SYSTEM
// ============================================================

/**
 * Check if user is accessing their own data
 * Returns true if self-access, false otherwise
 * This allows users to view/edit their own profiles, bookings, etc.
 * without needing special permissions
 */
function is_self_access($page) {
    global $conn;
    
    $current_user_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
    $current_staff_id = isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : 0;
    
    // If not logged in as admin or staff, no self access
    if ($current_user_id == 0 && $current_staff_id == 0) {
        return false;
    }
    
    // ============================================================
    // CASE 1: admin_users.php - viewing/editing own profile
    // ============================================================
    if ($page === 'admin_users.php') {
        $view = isset($_GET['view']) ? (int)$_GET['view'] : 0;
        $edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
        if (($view > 0 && $view === $current_user_id) || 
            ($edit > 0 && $edit === $current_user_id)) {
            return true;
        }
    }
    
    // ============================================================
    // CASE 2: admin_staff.php - viewing/editing own staff profile
    // ============================================================
    if ($page === 'admin_staff.php') {
        $view = isset($_GET['view']) ? (int)$_GET['view'] : 0;
        $edit = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
        if ($current_staff_id > 0 && 
            (($view > 0 && $view === $current_staff_id) || 
             ($edit > 0 && $edit === $current_staff_id))) {
            return true;
        }
    }
    
    // ============================================================
    // CASE 3: admin_view_booking.php - viewing own booking
    // ============================================================
    if ($page === 'admin_view_booking.php') {
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        if ($booking_id > 0) {
            // Check if booking belongs to this admin (as customer)
            if ($current_user_id > 0) {
                $sql = "SELECT customer_id FROM bookings WHERE id = $booking_id";
                $res = mysqli_query($conn, $sql);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    if ($row['customer_id'] == $current_user_id) {
                        return true;
                    }
                }
            }
            // Check if staff is assigned to this booking
            if ($current_staff_id > 0) {
                $sql = "SELECT id FROM bookings WHERE id = $booking_id AND staff_id = $current_staff_id";
                $res = mysqli_query($conn, $sql);
                if ($res && mysqli_num_rows($res) > 0) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 4: admin_view_invoice.php - viewing own invoice
    // ============================================================
    if ($page === 'admin_view_invoice.php') {
        $invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
        if ($invoice_id > 0 && $current_user_id > 0) {
            $sql = "SELECT b.customer_id FROM invoices i 
                    JOIN bookings b ON i.booking_id = b.id 
                    WHERE i.id = $invoice_id";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['customer_id'] == $current_user_id) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 5: admin_attendance_clock.php - viewing own attendance
    // ============================================================
    if ($page === 'admin_attendance_clock.php') {
        $pnumber = isset($_GET['pnumber']) ? $_GET['pnumber'] : '';
        if ($current_staff_id > 0 && !empty($pnumber)) {
            $sql = "SELECT pnumber FROM staff WHERE id = $current_staff_id";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['pnumber'] === $pnumber) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 6: admin_cancellation.php - cancelling own booking
    // ============================================================
    if ($page === 'admin_cancellation.php') {
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        if ($booking_id > 0 && $current_user_id > 0) {
            $sql = "SELECT customer_id FROM bookings WHERE id = $booking_id";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['customer_id'] == $current_user_id) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 7: admin_extend_stay.php - extending own booking
    // ============================================================
    if ($page === 'admin_extend_stay.php') {
        $booking_id = isset($_GET['booking']) ? (int)$_GET['booking'] : 0;
        if ($booking_id > 0 && $current_user_id > 0) {
            $sql = "SELECT customer_id FROM bookings WHERE id = $booking_id";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['customer_id'] == $current_user_id) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 8: admin_customers.php - viewing self as customer
    // ============================================================
    if ($page === 'admin_customers.php') {
        $view = isset($_GET['view']) ? (int)$_GET['view'] : 0;
        // If customer ID matches current user (if customers are linked to users)
        if ($view > 0 && $current_user_id > 0 && $view == $current_user_id) {
            return true;
        }
    }
    
    // ============================================================
    // CASE 9: admin_checkout.php - checking out own room
    // ============================================================
    if ($page === 'admin_checkout.php') {
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        if ($booking_id > 0 && $current_user_id > 0) {
            $sql = "SELECT customer_id FROM bookings WHERE id = $booking_id";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['customer_id'] == $current_user_id) {
                    return true;
                }
            }
        }
    }
    
    // ============================================================
    // CASE 10: admin_checkin.php - checking in own booking
    // ============================================================
    if ($page === 'admin_checkin.php') {
        $invoice = isset($_GET['invoice']) ? $_GET['invoice'] : '';
        if (!empty($invoice) && $current_user_id > 0) {
            $sql = "SELECT b.customer_id FROM invoices i 
                    JOIN bookings b ON i.booking_id = b.id 
                    WHERE i.invoice_number = '$invoice'";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                if ($row['customer_id'] == $current_user_id) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Enforce page permission - redirect with flash message
 * SELF-ACCESS: Users can always view/edit their own data
 */
function require_permission() {
    $page = basename($_SERVER['PHP_SELF']);
    
    // Allow login/logout/setup
    if (in_array($page, ['admin_login.php', 'admin_logout.php', 'setup.php', 'admin_roles.php'])) {
        return;
    }
    
    // ============================================================
    // CHECK SELF-ACCESS FIRST - Users can view/edit their own data
    // ============================================================
    if (is_self_access($page)) {
        return; // Always allow self access
    }
    
    // Normal permission check
    if (!can_access_page($page)) {
        set_flash_message('danger', 'You do not have permission to access that page.');
        header('Location: admin_dashboard.php');
        exit;
    }
}

/**
 * Get all available roles (for admin UI)
 */
function get_all_roles() {
    global $conn;
    $roles = [];
    
    // If roles table doesn't exist, return default roles
    $sql = "SHOW TABLES LIKE 'roles'";
    $res = mysqli_query($conn, $sql);
    if (mysqli_num_rows($res) == 0) {
        return [
            ['id' => 1, 'name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Full access', 'is_system' => 1],
            ['id' => 2, 'name' => 'manager', 'display_name' => 'Manager', 'description' => 'Operations management', 'is_system' => 1],
            ['id' => 3, 'name' => 'receptionist', 'display_name' => 'Receptionist', 'description' => 'Front desk', 'is_system' => 1],
            ['id' => 4, 'name' => 'security', 'display_name' => 'Security', 'description' => 'Security staff', 'is_system' => 1]
        ];
    }
    
    $sql = "SELECT id, name, display_name, description, is_system FROM roles ORDER BY name";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $roles[] = $row;
    }
    return $roles;
}

/**
 * Get all permissions (for admin UI)
 */
function get_all_permissions() {
    global $conn;
    $permissions = [];
    
    // If permissions table doesn't exist, return default permissions
    $sql = "SHOW TABLES LIKE 'permissions'";
    $res = mysqli_query($conn, $sql);
    if (mysqli_num_rows($res) == 0) {
        return [
            ['id' => 1, 'name' => 'manage_users', 'display_name' => 'Manage Users', 'category' => 'System'],
            ['id' => 2, 'name' => 'view_logs', 'display_name' => 'View Logs', 'category' => 'System'],
            ['id' => 3, 'name' => 'front_desk', 'display_name' => 'Front Desk', 'category' => 'Operations'],
            ['id' => 4, 'name' => 'finance', 'display_name' => 'Finance', 'category' => 'Financial'],
            ['id' => 5, 'name' => 'manage_rooms', 'display_name' => 'Manage Rooms', 'category' => 'Property']
        ];
    }
    
    $sql = "SELECT id, name, display_name, category, description FROM permissions ORDER BY category, name";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $permissions[] = $row;
    }
    return $permissions;
}

/**
 * Assign permission to role
 */
function assign_permission_to_role($role_id, $permission_id) {
    global $conn;
    $role_id = (int)$role_id;
    $permission_id = (int)$permission_id;
    
    $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) 
            VALUES ($role_id, $permission_id)";
    return mysqli_query($conn, $sql);
}

/**
 * Remove permission from role
 */
function remove_permission_from_role($role_id, $permission_id) {
    global $conn;
    $role_id = (int)$role_id;
    $permission_id = (int)$permission_id;
    
    $sql = "DELETE FROM role_permissions 
            WHERE role_id = $role_id AND permission_id = $permission_id";
    return mysqli_query($conn, $sql);
}

// ------------------------------------------------------------------
// Room Availability & Booking Functions
// ------------------------------------------------------------------

function room_is_available_datetime($conn, $room_id, $checkin_datetime, $checkout_datetime) {
    $room_id = (int)$room_id;
    $checkin_datetime = mysqli_real_escape_string($conn, $checkin_datetime);
    $checkout_datetime = mysqli_real_escape_string($conn, $checkout_datetime);

    $sql = "SELECT 1 FROM bookings 
            WHERE room_id = $room_id 
              AND status IN ('pending', 'confirmed', 'paid')
              AND (checkout_status IS NULL OR checkout_status != 'checked_out')
              AND NOT (
                CONCAT(checkout_date, ' ', checkout_time) <= '$checkin_datetime'
                OR CONCAT(checkin_date, ' ', checkin_time) >= '$checkout_datetime'
              )
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    return !mysqli_fetch_assoc($result);
}

/**
 * Check if room is currently occupied (guest has checked in and hasn't checked out)
 * Uses same-day checkout logic
 */
function is_room_occupied_today($conn, $room_id) {
    $current_date = date('Y-m-d');
    $room_id = (int)$room_id;
    
    $sql = "
        SELECT 1 FROM bookings b
        WHERE b.room_id = $room_id
          AND b.status IN ('confirmed','paid')
          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
          -- Guest has ALREADY checked in
          AND b.checkin_date <= '$current_date'
          -- Guest hasn't checked out yet (considering same-day checkout)
          AND (
            b.checkout_date > '$current_date'
            OR (b.checkout_date = '$current_date' AND b.checkout_time > '12:00:00')
          )
        LIMIT 1
    ";
    
    $result = mysqli_query($conn, $sql);
    if (!$result) return false;
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Get current room status (occupied/available) using same-day checkout logic
 */
function get_room_current_status($conn, $room_id, $today = null) {
    if ($today === null) $today = date('Y-m-d');
    
    $room_id = (int)$room_id;
    $today   = mysqli_real_escape_string($conn, $today);

    $sql = "
        SELECT 1 FROM bookings b
        WHERE b.room_id = $room_id
          AND b.status IN ('confirmed','paid')
          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
          -- Guest has ALREADY checked in
          AND b.checkin_date <= '$today'
          -- Guest hasn't checked out yet
          AND b.checkout_date > '$today'
          AND NOT (b.checkout_date = '$today' AND b.checkout_time <= '12:00:00')
        LIMIT 1
    ";

    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0 ? 'occupied' : 'available';
}

function get_available_rooms($conn, $checkin_date, $checkout_date) {
    $checkin_date = mysqli_real_escape_string($conn, $checkin_date);
    $checkout_date = mysqli_real_escape_string($conn, $checkout_date);
    
    $sql = "SELECT r.*, b.name as building_name, b.building_type, b.base_price
            FROM rooms r
            JOIN buildings b ON r.building_id = b.id
            WHERE r.id NOT IN (
                SELECT DISTINCT room_id 
                FROM bookings 
                WHERE status IN ('confirmed', 'paid')
                  AND (checkout_status IS NULL OR checkout_status != 'checked_out')
                  AND NOT (
                    checkout_date <= '$checkin_date'
                    OR checkin_date >= '$checkout_date'
                  )
            )
            ORDER BY b.building_type, r.room_number";
    
    return mysqli_query($conn, $sql);
}

function get_building_price($conn, $building_id) {
    $building_id = (int)$building_id;
    $res = mysqli_query($conn, "SELECT base_price FROM buildings WHERE id = $building_id LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    return $row ? (float)$row['base_price'] : 0;
}

/**
 * Check if a room is available for booking using same-day checkout logic
 * Rooms become available after 12:00 PM on checkout day
 * Allows bookings that checkout by noon on same day as next booking's checkin
 */
function is_room_available_same_day_checkout($conn, $room_id, $checkin_date, $checkout_date) {
    $room_id = (int)$room_id;
    $ci = mysqli_real_escape_string($conn, $checkin_date);
    $co = mysqli_real_escape_string($conn, $checkout_date);
    
    $sql = "
        SELECT 1 FROM bookings b
        WHERE b.room_id = $room_id
          AND b.status IN ('confirmed','paid')
          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
          AND (
            -- Standard overlap: existing booking overlaps with our dates
            (b.checkin_date < '$co' AND b.checkout_date > '$ci')
            OR
            -- We want to checkout on same day as existing booking's checkin
            -- Only problematic if existing guest checks out AFTER noon
            ('$co' = b.checkin_date AND b.checkout_time > '12:00:00')
            OR
            -- We want to checkin on same day as existing booking's checkout
            -- Only problematic if existing guest checks out AFTER noon
            ('$ci' = b.checkout_date AND b.checkout_time > '12:00:00')
          )
        LIMIT 1
    ";
    
    $result = mysqli_query($conn, $sql);
    if (!$result) return false;
    
    return mysqli_num_rows($result) == 0;
}

// ------------------------------------------------------------------
// Date & Time Calculation Functions
// ------------------------------------------------------------------

function calculate_nights($checkin_date, $checkout_date) {
    $in = strtotime($checkin_date);
    $out = strtotime($checkout_date);
    if ($out === false || $in === false) {
        return 0;
    }
    return max(0, floor(($out - $in) / 86400));
}

function validate_stay_duration($checkin_datetime, $checkout_datetime) {
    $in = strtotime($checkin_datetime);
    $out = strtotime($checkout_datetime);

    if (!$in || !$out) {
        return array('valid' => false, 'error' => 'Invalid date/time');
    }
    
    if ($out <= $in) {
        return array('valid' => false, 'error' => 'Check-out must be after check-in');
    }

    $hours = ($out - $in) / 3600;
    if ($hours < 1) {
        return array('valid' => false, 'error' => 'Minimum stay is 1 hour');
    }

    $days = $hours / 24;
    if ($days > 30) {
        return array('valid' => false, 'error' => 'Maximum stay is 30 days');
    }

    return array('valid' => true, 'nights' => ceil($days));
}

function get_effective_checkin_time($checkin_date) {
    $today = date('Y-m-d');
    if ($checkin_date === $today) {
        $now = date('H:i:00');
        return ($now > '14:00:00') ? $now : '14:00:00';
    }
    return '14:00:00';
}

function calculate_nights_display($checkin, $checkout, $price, $nights_id, $total_id) {
    $checkin_time = strtotime($checkin);
    $checkout_time = strtotime($checkout);
    $nights = max(0, floor(($checkout_time - $checkin_time) / 86400));
    $total = $nights * $price;
    echo "<script>
        document.getElementById('{$nights_id}').value = '{$nights}';
        document.getElementById('{$total_id}').value = '₦' + {$total}.toFixed(2);
    </script>";
}

// ------------------------------------------------------------------
// Status Badge Functions
// ------------------------------------------------------------------

function get_booking_status_badge($status) {
    $map = array(
        'pending'    => 'warning',
        'confirmed'  => 'info',
        'paid'       => 'success',
        'cancelled'  => 'danger',
        'completed'  => 'secondary'
    );
    return isset($map[$status]) ? $map[$status] : 'secondary';
}

function get_checkout_status_badge($status) {
    $map = array(
        'not_checked_out' => 'warning',
        'checked_out'     => 'success',
        'pending'         => 'info'
    );
    return isset($map[$status]) ? $map[$status] : 'secondary';
}

// ------------------------------------------------------------------
// Utility Functions
// ------------------------------------------------------------------

function redirect_with_message($location, $message, $type = 'info') {
    header('Location: ' . $location . '?alert=' . urlencode($message) . '&type=' . $type);
    exit;
}

// ------------------------------------------------------------------
// Database Helper Functions
// ------------------------------------------------------------------

function db_query($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Database error: " . mysqli_error($conn));
        return false;
    }
    return $result;
}

function db_fetch_single($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function db_fetch_all($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function db_escape($conn, $string) {
    return mysqli_real_escape_string($conn, $string);
}

function db_last_insert_id($conn) {
    return mysqli_insert_id($conn);
}

// ============================================================
// EXCEL EXPORT HELPER - Shared across all admin pages
// ============================================================

function export_to_excel($data, $headers, $filename, $title = 'Report') {
    // Load hotel config for branding
    $config_path = __DIR__ . '/config.php';
    $config = include $config_path;
    $hotel = isset($config['hotel']) ? $config['hotel'] : array();
    $hotel_name = isset($hotel['name']) ? $hotel['name'] : 'Hotel Management System';
    $hotel_tagline = isset($hotel['tagline']) ? $hotel['tagline'] : '';
    $hotel_address = '';
    if (isset($hotel['address_line1']) && !empty($hotel['address_line1'])) {
        $hotel_address = $hotel['address_line1'];
        if (isset($hotel['address_line2']) && !empty($hotel['address_line2'])) {
            $hotel_address .= ' ' . $hotel['address_line2'];
        }
    }
    $hotel_phone = isset($hotel['phone']) ? $hotel['phone'] : '';
    $hotel_email = isset($hotel['email']) ? $hotel['email'] : '';

    // Get current admin user
    $admin_user = get_current_admin_fullname();
    if (empty($admin_user)) {
        $admin_user = 'System';
    }

    // Calculate column widths
    $col_widths = array();
    $col_count = count($headers);
    if ($col_count == 0) $col_count = 1;

    foreach ($headers as $idx => $header) {
        $max_len = strlen($header);
        foreach ($data as $row) {
            $val = isset($row[$header]) ? (string)$row[$header] : '';
            $len = strlen($val);
            if ($len > $max_len) $max_len = $len;
        }
        $col_widths[] = min(max($max_len + 4, 12), 50);
    }

    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $excel = '<?xml version="1.0" encoding="UTF-8"?>
    <?mso-application progid="Excel.Sheet"?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
              xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Alignment ss:Vertical="Bottom"/>
            <Borders/>
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Interior/>
            <NumberFormat/>
            <Protection/>
        </Style>

        <Style ss:ID="HotelName">
            <Font ss:FontName="Arial" ss:Size="18" ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#1a1a1a" ss:Pattern="Solid"/>
            <Font ss:Color="#FFFFFF"/>
        </Style>

        <Style ss:ID="Tagline">
            <Font ss:FontName="Arial" ss:Size="11" ss:Italic="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#555555"/>
        </Style>

        <Style ss:ID="Address">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#666666"/>
        </Style>

        <Style ss:ID="Contact">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#666666"/>
        </Style>

        <Style ss:ID="Separator">
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
            </Borders>
        </Style>

        <Style ss:ID="ReportTitle">
            <Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#E8E8E8" ss:Pattern="Solid"/>
        </Style>

        <Style ss:ID="ReportMeta">
            <Font ss:FontName="Arial" ss:Size="10"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Font ss:Color="#888888"/>
        </Style>

        <Style ss:ID="HeaderRow">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Interior ss:Color="#1a1a1a" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>

        <Style ss:ID="DataRow">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>

        <Style ss:ID="DataRowAlt">
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Interior ss:Color="#F5F5F5" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>

        <Style ss:ID="Footer">
            <Font ss:FontName="Arial" ss:Size="9" ss:Italic="1" ss:Color="#999999"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>

        <Style ss:ID="FooterUser">
            <Font ss:FontName="Arial" ss:Size="9" ss:Italic="1" ss:Color="#666666"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
        </Style>
    </Styles>
    <Worksheet ss:Name="' . h($title) . '">
        <Table>';

    // Set column widths
    foreach ($col_widths as $idx => $width) {
        $excel .= '
            <Column ss:Width="' . ($width * 7) . '"/>';
    }

    $col_span = max($col_count, 1);

    // Hotel Header
    $excel .= '
            <Row>
                <Cell ss:StyleID="HotelName" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_name) . '</Data></Cell>
            </Row>';

    if (!empty($hotel_tagline)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Tagline" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_tagline) . '</Data></Cell>
            </Row>';
    }

    if (!empty($hotel_address)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Address" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_address) . '</Data></Cell>
            </Row>';
    }

    $contact_parts = array();
    if (!empty($hotel_phone)) $contact_parts[] = 'Tel: ' . h($hotel_phone);
    if (!empty($hotel_email)) $contact_parts[] = 'Email: ' . h($hotel_email);
    if (!empty($contact_parts)) {
        $excel .= '
            <Row>
                <Cell ss:StyleID="Contact" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . implode(' | ', $contact_parts) . '</Data></Cell>
            </Row>';
    }

    // Separator
    $excel .= '
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>';

    // Report Title
    $excel .= '
            <Row>
                <Cell ss:StyleID="ReportTitle" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . strtoupper(h($title)) . '</Data></Cell>
            </Row>';

    // Report Meta
    $excel .= '
            <Row>
                <Cell ss:StyleID="ReportMeta" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">Generated: ' . date('Y-m-d H:i:s') . ' by ' . h($admin_user) . '</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>';

    // Data Table Headers
    if (!empty($headers) && !empty($data)) {
        $excel .= '
            <Row>';
        foreach ($headers as $header) {
            $excel .= '
                <Cell ss:StyleID="HeaderRow"><Data ss:Type="String">' . h($header) . '</Data></Cell>';
        }
        $excel .= '
            </Row>';

        // Data Rows
        $row_num = 0;
        foreach ($data as $row) {
            $row_style = ($row_num % 2 == 0) ? 'DataRow' : 'DataRowAlt';
            $excel .= '
            <Row>';
            foreach ($headers as $header) {
                $val = isset($row[$header]) ? $row[$header] : '';
                if (strlen($val) > 50) {
                    $val = substr($val, 0, 47) . '...';
                }
                $excel .= '
                <Cell ss:StyleID="' . $row_style . '"><Data ss:Type="String">' . h($val) . '</Data></Cell>';
            }
            $excel .= '
            </Row>';
            $row_num++;
        }
    } else {
        $excel .= '
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '" ss:StyleID="DataRow"><Data ss:Type="String">No data found</Data></Cell>
            </Row>';
    }

    // Footer
    $excel .= '
            <Row>
                <Cell ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Separator" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String"></Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Footer" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">--- End of Report ---</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="FooterUser" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">Generated by ' . h($admin_user) . ' on ' . date('Y-m-d H:i:s') . '</Data></Cell>
            </Row>
            <Row>
                <Cell ss:StyleID="Footer" ss:MergeAcross="' . ($col_span - 1) . '"><Data ss:Type="String">' . h($hotel_name) . ' Management System</Data></Cell>
            </Row>';

    $excel .= '
        </Table>
        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <PageSetup>
                <PageMargins x:Bottom="0.75" x:Left="0.7" x:Right="0.7" x:Top="0.75"/>
            </PageSetup>
            <Print>
                <ValidPrinterInfo/>
                <PaperSizeIndex>9</PaperSizeIndex>
                <HorizontalResolution>600</HorizontalResolution>
                <VerticalResolution>600</VerticalResolution>
            </Print>
            <Selected/>
            <Panes>
                <Pane>
                    <Number>3</Number>
                    <ActiveRow>6</ActiveRow>
                    <ActiveCol>0</ActiveCol>
                </Pane>
            </Panes>
            <ProtectObjects>False</ProtectObjects>
            <ProtectScenarios>False</ProtectScenarios>
        </WorksheetOptions>
    </Worksheet>
</Workbook>';

    echo $excel;
    exit;
}

// ============================================================
// ROLE DISPLAY HELPER (Legacy support)
// ============================================================

/**
 * Styled badge markup for displaying a role in a table (e.g. admin_users.php).
 * Works with both legacy and new role systems.
 */
function get_role_display($role) {
    // If numeric role ID passed, get role name
    if (is_numeric($role)) {
        global $conn;
        $sql = "SELECT display_name FROM roles WHERE id = " . (int)$role;
        $res = mysqli_query($conn, $sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $role = $row['display_name'];
        } else {
            $role = 'Unknown';
        }
    }
    
    $labels = array(
        'super_admin'  => '<span class="badge badge-danger">Super Admin</span>',
        'manager'      => '<span class="badge badge-warning">Manager</span>',
        'receptionist' => '<span class="badge badge-info">Receptionist</span>',
        'security'     => '<span class="badge badge-secondary">Security</span>',
        'housekeeping' => '<span class="badge badge-primary">Housekeeping</span>',
        'accountant'   => '<span class="badge badge-success">Accountant</span>'
    );
    
    if (isset($labels[$role])) {
        return $labels[$role];
    }
    return '<span class="badge badge-secondary">' . h(ucfirst((string)$role)) . '</span>';
}

// ============================================================
// LEGACY ROLE FUNCTIONS (Keep for backward compatibility)
// ============================================================

/**
 * Get current admin role (legacy) - returns role name string
 * Used by older code that expects a string
 */
function get_current_admin_role() {
    $role_name = get_current_role_name();
    return $role_name ?: 'receptionist';
}

/**
 * Get all roles (legacy format)
 */
function get_all_roles_legacy() {
    $roles = get_all_roles();
    $result = array();
    foreach ($roles as $role) {
        $result[$role['name']] = $role['display_name'];
    }
    return $result;
}
?>