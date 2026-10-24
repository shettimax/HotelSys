<?php
// header.php - Clean version with no inline CSS/JS
// REARRANGED NAVIGATION: OVERVIEW | FRONT DESK | GUESTS | ROOMS | FINANCE | SETTINGS
// NOTIFICATIONS: ALL CATEGORIES with proper exclusions

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/signature.php';
$config = include __DIR__ . '/config.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_user_id = get_current_admin_id();

// ============================================================
// NOTIFICATIONS - ALL CATEGORIES
// ============================================================
$notification_types = 0;
$notifications = array();
$badge_color = 'info';

if (is_admin_logged_in()) {
    
    // ============================================================
    // 1. CHECKOUT CATEGORY
    // ============================================================
    $checkout_items = array();
    $has_checkout_issue = false;
    
    $pending_sql = "SELECT COUNT(*) as count FROM bookings b 
                    WHERE b.status = 'paid' 
                    AND b.keycard_issued = 1 
                    AND b.actual_checkin_datetime IS NOT NULL 
                    AND (b.checkout_status IS NULL OR b.checkout_status = 'not_checked_out')
                    AND b.checkout_date = CURDATE() 
                    AND b.checkout_time > CURTIME() 
                    AND TIMESTAMPDIFF(HOUR, NOW(), CONCAT(b.checkout_date, ' ', b.checkout_time)) <= 12";
    $pending_result = mysqli_query($conn, $pending_sql);
    if ($pending_result) {
        $pending_row = mysqli_fetch_assoc($pending_result);
        $pending_count = $pending_row['count'];
        if ($pending_count > 0) {
            $has_checkout_issue = true;
            $badge_color = 'warning';
            $checkout_items[] = array(
                'type' => 'pending',
                'icon' => 'fa-clock',
                'count' => $pending_count,
                'text' => $pending_count . ' guest(s) pending checkout',
                'link' => 'admin_checkout_management.php?filter=pending'
            );
        }
    }
    
    $overdue_sql = "SELECT COUNT(*) as count FROM bookings b 
                    WHERE b.status = 'paid' 
                    AND b.keycard_issued = 1 
                    AND b.actual_checkin_datetime IS NOT NULL 
                    AND (b.checkout_status IS NULL OR b.checkout_status = 'not_checked_out')
                    AND (
                        b.checkout_date < CURDATE() 
                        OR (b.checkout_date = CURDATE() AND b.checkout_time <= CURTIME())
                    )";
    $overdue_result = mysqli_query($conn, $overdue_sql);
    if ($overdue_result) {
        $overdue_row = mysqli_fetch_assoc($overdue_result);
        $overdue_count = $overdue_row['count'];
        if ($overdue_count > 0) {
            $has_checkout_issue = true;
            $badge_color = 'danger';
            $checkout_items[] = array(
                'type' => 'overdue',
                'icon' => 'fa-exclamation-triangle',
                'count' => $overdue_count,
                'text' => $overdue_count . ' guest(s) OVERDUE checkout!',
                'link' => 'admin_checkout_management.php?filter=overdue'
            );
        }
    }
    
    if ($has_checkout_issue) {
        $notification_types++;
        $notifications[] = array(
            'category' => 'Checkout',
            'icon' => 'fa-clock',
            'items' => $checkout_items
        );
    }
    
    // ============================================================
    // 2. REFUNDS CATEGORY
    // ============================================================
    $refund_items = array();
    $has_refund_issue = false;
    
    $pending_refund_sql = "SELECT COUNT(*) as count FROM checkouts ch
                           WHERE ch.refund_amount > 0 
                           AND NOT EXISTS(
                               SELECT 1 FROM payments p 
                               WHERE p.invoice_id = ch.invoice_id 
                               AND p.amount < 0 
                               AND ABS(p.amount) = ch.refund_amount
                           )";
    $pending_refund_result = mysqli_query($conn, $pending_refund_sql);
    if ($pending_refund_result) {
        $pending_refund_row = mysqli_fetch_assoc($pending_refund_result);
        $pending_refund_count = $pending_refund_row['count'];
        if ($pending_refund_count > 0) {
            $has_refund_issue = true;
            $badge_color = 'warning';
            $refund_items[] = array(
                'type' => 'pending_refund',
                'icon' => 'fa-money-bill-wave',
                'count' => $pending_refund_count,
                'text' => $pending_refund_count . ' pending refund(s)',
                'link' => 'admin_refunds.php?filter=pending'
            );
        }
    }
    
    if ($has_refund_issue) {
        $notification_types++;
        $notifications[] = array(
            'category' => 'Refunds',
            'icon' => 'fa-money-bill-wave',
            'items' => $refund_items
        );
    }
    
    // ============================================================
    // 3. INVOICES CATEGORY
    // ============================================================
    $unpaid_sql = "SELECT COUNT(*) as count FROM invoices i 
                   JOIN bookings b ON i.booking_id = b.id 
                   WHERE i.paid = 0 
                   AND b.status != 'cancelled' 
                   AND b.status != 'pending'";
    $unpaid_result = mysqli_query($conn, $unpaid_sql);
    if ($unpaid_result) {
        $unpaid_row = mysqli_fetch_assoc($unpaid_result);
        $unpaid_count = $unpaid_row['count'];
        if ($unpaid_count > 0) {
            $notification_types++;
            if ($badge_color !== 'danger' && $badge_color !== 'warning') {
                $badge_color = 'info';
            }
            $notifications[] = array(
                'category' => 'Invoices',
                'icon' => 'fa-file-invoice',
                'items' => array(
                    array(
                        'type' => 'unpaid',
                        'icon' => 'fa-file-invoice',
                        'count' => $unpaid_count,
                        'text' => $unpaid_count . ' unpaid invoice(s)',
                        'link' => 'admin_invoice_management.php?status=unpaid'
                    )
                )
            );
        }
    }
    
    // ============================================================
    // 4. DISPUTES CATEGORY
    // ============================================================
    $disputes_sql = "SELECT COUNT(*) as count FROM disputes WHERE status = 'open'";
    $disputes_result = mysqli_query($conn, $disputes_sql);
    if ($disputes_result) {
        $disputes_row = mysqli_fetch_assoc($disputes_result);
        $dispute_count = $disputes_row['count'];
        if ($dispute_count > 0) {
            $notification_types++;
            if ($badge_color !== 'danger') {
                $badge_color = 'warning';
            }
            $notifications[] = array(
                'category' => 'Disputes',
                'icon' => 'fa-exclamation-circle',
                'items' => array(
                    array(
                        'type' => 'open',
                        'icon' => 'fa-exclamation-circle',
                        'count' => $dispute_count,
                        'text' => $dispute_count . ' open dispute(s)',
                        'link' => 'admin_dispute.php?status=open'
                    )
                )
            );
        }
    }
    
    // ============================================================
    // 5. PAYMENTS CATEGORY
    // ============================================================
    $payment_items = array();
    $has_payment_issue = false;
    
    $failed_payment_sql = "SELECT COUNT(*) as count FROM payments p
                           JOIN invoices i ON p.invoice_id = i.id
                           JOIN bookings b ON i.booking_id = b.id
                           WHERE p.status = 'failed' 
                           AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                           AND b.status != 'cancelled'";
    $failed_payment_result = mysqli_query($conn, $failed_payment_sql);
    if ($failed_payment_result) {
        $failed_payment_row = mysqli_fetch_assoc($failed_payment_result);
        $failed_payment_count = $failed_payment_row['count'];
        if ($failed_payment_count > 0) {
            $has_payment_issue = true;
            $badge_color = 'danger';
            $payment_items[] = array(
                'type' => 'failed',
                'icon' => 'fa-times-circle',
                'count' => $failed_payment_count,
                'text' => $failed_payment_count . ' failed payment(s)',
                'link' => 'admin_invoice_management.php'
            );
        }
    }
    
    $initiated_payment_sql = "SELECT COUNT(*) as count FROM payments p
                              JOIN invoices i ON p.invoice_id = i.id
                              JOIN bookings b ON i.booking_id = b.id
                              WHERE p.status = 'initiated' 
                              AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                              AND b.status != 'cancelled'";
    $initiated_payment_result = mysqli_query($conn, $initiated_payment_sql);
    if ($initiated_payment_result) {
        $initiated_payment_row = mysqli_fetch_assoc($initiated_payment_result);
        $initiated_payment_count = $initiated_payment_row['count'];
        if ($initiated_payment_count > 0) {
            $has_payment_issue = true;
            if ($badge_color !== 'danger') {
                $badge_color = 'warning';
            }
            $payment_items[] = array(
                'type' => 'initiated',
                'icon' => 'fa-hourglass-half',
                'count' => $initiated_payment_count,
                'text' => $initiated_payment_count . ' pending payment(s)',
                'link' => 'admin_invoice_management.php'
            );
        }
    }
    
    if ($has_payment_issue) {
        $notification_types++;
        $notifications[] = array(
            'category' => 'Payments',
            'icon' => 'fa-credit-card',
            'items' => $payment_items
        );
    }
    
    // ============================================================
    // 6. BOOKINGS CATEGORY
    // ============================================================
    $booking_items = array();
    $has_booking_issue = false;
    
    $pending_booking_sql = "SELECT COUNT(*) as count FROM bookings 
                            WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $pending_booking_result = mysqli_query($conn, $pending_booking_sql);
    if ($pending_booking_result) {
        $pending_booking_row = mysqli_fetch_assoc($pending_booking_result);
        $pending_booking_count = $pending_booking_row['count'];
        if ($pending_booking_count > 0) {
            $has_booking_issue = true;
            $badge_color = 'warning';
            $booking_items[] = array(
                'type' => 'pending',
                'icon' => 'fa-calendar-check',
                'count' => $pending_booking_count,
                'text' => $pending_booking_count . ' pending booking(s)',
                'link' => 'admin_booking_management.php?filter=pending'
            );
        }
    }
    
    if ($has_booking_issue) {
        $notification_types++;
        $notifications[] = array(
            'category' => 'Bookings',
            'icon' => 'fa-calendar-alt',
            'items' => $booking_items
        );
    }
    
    // ============================================================
    // 7. STAFF ATTENDANCE CATEGORY
    // ============================================================
    $staff_items = array();
    $has_staff_issue = false;
    
    $staff_clocked_sql = "SELECT COUNT(DISTINCT staff_id) as count FROM staff_attendance 
                          WHERE clock_in IS NOT NULL AND clock_out IS NULL 
                          AND date = CURDATE()";
    $staff_clocked_result = mysqli_query($conn, $staff_clocked_sql);
    if ($staff_clocked_result) {
        $staff_clocked_row = mysqli_fetch_assoc($staff_clocked_result);
        $staff_clocked_count = $staff_clocked_row['count'];
        if ($staff_clocked_count > 0) {
            $has_staff_issue = true;
            $badge_color = 'warning';
            $staff_items[] = array(
                'type' => 'clocked_in',
                'icon' => 'fa-user-clock',
                'count' => $staff_clocked_count,
                'text' => $staff_clocked_count . ' staff clocked in',
                'link' => 'admin_attendance_clock.php'
            );
        }
    }
    
    if ($has_staff_issue) {
        $notification_types++;
        $notifications[] = array(
            'category' => 'Staff',
            'icon' => 'fa-users',
            'items' => $staff_items
        );
    }
    
    // ============================================================
    // 8. MAINTENANCE CATEGORY
    // ============================================================
    $maintenance_sql = "SELECT COUNT(*) as count FROM rooms WHERE status = 'maintenance'";
    $maintenance_result = mysqli_query($conn, $maintenance_sql);
    if ($maintenance_result) {
        $maintenance_row = mysqli_fetch_assoc($maintenance_result);
        $maintenance_count = $maintenance_row['count'];
        if ($maintenance_count > 0) {
            $notification_types++;
            if ($badge_color !== 'danger' && $badge_color !== 'warning') {
                $badge_color = 'info';
            }
            $notifications[] = array(
                'category' => 'Maintenance',
                'icon' => 'fa-tools',
                'items' => array(
                    array(
                        'type' => 'maintenance',
                        'icon' => 'fa-tools',
                        'count' => $maintenance_count,
                        'text' => $maintenance_count . ' room(s) under maintenance',
                        'link' => 'admin_manage_rooms.php?search=maintenance'
                    )
                )
            );
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotel Management System<?php echo is_admin_logged_in() ? ' - Admin Panel' : ''; ?></title>
    <link rel="icon" href="img/favicon.png" type="image/x-icon">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="css/bootstrap-3.4.1.min.css">
    <link rel="stylesheet" href="css/font-awesome-6.5.0.min.css">
    <link rel="stylesheet" href="css/sweetalert2-11.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/functions.css">
    
    <!-- ============================================================
         NAVIGATION STYLES - In HEAD where they belong
         ============================================================ -->
    <style>
    /* Admin Navbar */
    .admin-nav {
        background: #0a0a0a !important;
        border-bottom: 2px solid #00ff99 !important;
        box-shadow: 0 4px 30px rgba(0, 255, 153, 0.1);
        min-height: 55px;
        z-index: 1030 !important;
    }

    .admin-nav .navbar-brand {
        padding: 12px 15px;
        height: auto;
    }

    .admin-nav .navbar-brand .brand-text {
        color: #00ff99;
        font-weight: bold;
        font-size: 16px;
        letter-spacing: 1px;
        margin-left: 5px;
        text-shadow: 0 0 20px rgba(0, 255, 153, 0.3);
    }

    .admin-nav .navbar-brand img {
        display: inline-block;
        vertical-align: middle;
    }

    .admin-nav .navbar-nav > li > a {
        color: #aaa !important;
        font-size: 13px;
        padding: 18px 14px;
        transition: all 0.3s ease;
        letter-spacing: 0.3px;
    }

    .admin-nav .navbar-nav > li > a:hover,
    .admin-nav .navbar-nav > li > a:focus {
        color: #00ff99 !important;
        background: rgba(0, 255, 153, 0.05) !important;
    }

    .admin-nav .navbar-nav > li.active > a {
        color: #00ff99 !important;
        background: rgba(0, 255, 153, 0.08) !important;
        border-bottom: 2px solid #00ff99;
    }

    .admin-nav .navbar-nav > li > a .nav-label {
        margin-left: 4px;
    }

    .admin-nav .dropdown-menu {
        background: #111 !important;
        border: 1px solid #333 !important;
        border-radius: 4px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        padding: 5px 0;
        min-width: 200px;
    }

    .admin-nav .dropdown-menu > li > a {
        color: #ccc !important;
        padding: 8px 18px;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .admin-nav .dropdown-menu > li > a:hover,
    .admin-nav .dropdown-menu > li > a:focus {
        background: rgba(0, 255, 153, 0.1) !important;
        color: #00ff99 !important;
    }

    .admin-nav .dropdown-menu > li > a i {
        width: 20px;
        color: #00ff99;
    }

    .admin-nav .dropdown-menu .divider {
        background: #222 !important;
    }

    .admin-nav .dropdown-header {
        color: #666 !important;
        font-size: 11px;
        padding: 8px 18px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Notification styles */
    .notification-category {
        padding: 8px 15px 4px 15px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #666;
        background: rgba(0,0,0,0.2);
    }

    .notification-category .category-label i {
        margin-right: 6px;
        color: #00ff99;
    }

    .notifications-dropdown > a {
        position: relative;
        padding: 18px 12px !important;
    }

    .notifications-dropdown > a .notification-badge {
        position: absolute;
        top: 8px;
        right: 4px;
        color: #fff !important;
        font-size: 9px;
        padding: 2px 6px;
        border-radius: 50%;
        border: 2px solid #0a0a0a;
        min-width: 18px;
        text-align: center;
        animation: bellPulse 1.5s ease-in-out infinite;
    }

    .notification-badge.badge-danger {
        background: #ff0000 !important;
    }

    .notification-badge.badge-warning {
        background: #ffcc00 !important;
        color: #000 !important;
    }

    .notification-badge.badge-info {
        background: #00aaff !important;
    }

    @keyframes bellPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }

    .notifications-dropdown > a .fa-bell {
        font-size: 16px;
        color: #aaa;
        transition: all 0.3s ease;
    }

    .notifications-dropdown > a:hover .fa-bell {
        color: #00ff99;
    }

    .notifications-menu {
        min-width: 320px !important;
        max-height: 400px;
        overflow-y: auto;
    }

    .notifications-menu .dropdown-header {
        background: rgba(0, 255, 153, 0.05);
        border-bottom: 1px solid #222;
    }

    .notifications-menu .dropdown-header .badge {
        background: #00ff99;
        color: #000;
        font-weight: bold;
    }

    .notification-item {
        display: flex !important;
        align-items: center;
        padding: 10px 15px !important;
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }

    .notification-item:hover {
        border-left-color: #00ff99 !important;
    }

    .notification-item .notification-icon {
        flex: 0 0 30px;
        font-size: 16px;
        color: #00ff99;
    }

    .notification-item .notification-text {
        flex: 1;
        font-size: 12px;
    }

    .notification-item .notification-count {
        margin-right: 8px;
        font-weight: bold;
    }

    .notification-item.notification-danger {
        border-left-color: #ff0000;
    }
    .notification-item.notification-danger .notification-icon {
        color: #ff0000;
    }

    .notification-item.notification-warning {
        border-left-color: #ffcc00;
    }
    .notification-item.notification-warning .notification-icon {
        color: #ffcc00;
    }

    .notification-item.notification-info {
        border-left-color: #00aaff;
    }
    .notification-item.notification-info .notification-icon {
        color: #00aaff;
    }

    .notification-item.notification-success {
        border-left-color: #00ff99;
    }
    .notification-item.notification-success .notification-icon {
        color: #00ff99;
    }

    .notifications-menu .dropdown-footer {
        background: rgba(0, 0, 0, 0.3);
        padding: 8px 0;
        border-top: 1px solid #222;
    }

    .notifications-menu .dropdown-footer a {
        color: #666 !important;
        font-size: 12px;
    }

    .notifications-menu .dropdown-footer a:hover {
        color: #00ff99 !important;
        text-decoration: none;
    }

    /* User dropdown */
    .user-dropdown > a {
        padding: 18px 12px !important;
    }

    .user-dropdown > a .fa-user-circle {
        font-size: 18px;
        color: #00ff99;
        margin-right: 5px;
    }

    .user-dropdown .dropdown-header {
        background: rgba(0, 255, 153, 0.05);
        border-bottom: 1px solid #222;
    }

    .user-dropdown .dropdown-header small {
        color: #666 !important;
    }

    .user-dropdown .dropdown-menu > li > a i {
        color: #00ff99;
        width: 20px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .admin-nav .navbar-nav > li > a {
            padding: 12px 15px;
        }
        
        .admin-nav .navbar-nav > li > a .nav-label {
            display: inline-block;
        }
        
        .admin-nav .navbar-brand .brand-text {
            display: none;
        }
        
        .notifications-menu {
            min-width: 280px !important;
            max-width: 300px;
        }
    }

    @media (max-width: 991px) {
        .admin-nav .navbar-nav > li > a .nav-label {
            display: inline-block;
        }
        
        .admin-nav .navbar-nav {
            margin: 0;
        }
    }

    /* Scrollbar */
    .notifications-menu::-webkit-scrollbar {
        width: 4px;
    }

    .notifications-menu::-webkit-scrollbar-track {
        background: #111;
    }

    .notifications-menu::-webkit-scrollbar-thumb {
        background: #00ff99;
        border-radius: 4px;
    }

    .notifications-menu::-webkit-scrollbar-thumb:hover {
        background: #00cc77;
    }

    /* Dropdown hover */
    @media (min-width: 992px) {
        .admin-nav .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .admin-nav .dropdown .dropdown-menu {
            margin-top: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    }
    </style>
</head>

<body class="<?php echo is_admin_logged_in() ? 'admin-body' : 'public-body'; ?>">

<?php if (!is_admin_logged_in()): ?>
<!-- Public Navigation -->
<nav class="navbar navbar-default public-nav">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#public-navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo htmlspecialchars($config['base_url'] . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-hotel"></i> HotelSys
            </a>
        </div>
        <div class="collapse navbar-collapse" id="public-navbar">
            <ul class="nav navbar-nav">
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($config['base_url'] . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">Home</a>
                </li>
                <li class="<?php echo ($current_page == 'index.php#reservation') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($config['base_url'] . '/index.php#reservation', ENT_QUOTES, 'UTF-8'); ?>">Make Reservation</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($config['base_url'] . '/invoice.php', ENT_QUOTES, 'UTF-8'); ?>">View Invoice</a>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="<?php echo ($current_page == 'admin_login.php') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($config['base_url'] . '/admin_login.php', ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-sign-in-alt"></i> Admin Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php else: ?>
<!-- Admin Dashboard Navigation -->
<nav class="navbar navbar-default navbar-fixed-top admin-nav">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#admin-navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="img/logox.png" height="30" alt="Hotel Logo" class="brand-logo">
                <span class="brand-text hidden-xs"></span>
            </a>
        </div>

        <div class="collapse navbar-collapse" id="admin-navbar">
            <ul class="nav navbar-nav">
                <!-- OVERVIEW -->
                <li class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                    <a href="admin_dashboard.php" title="Dashboard - View hotel statistics and overview">
                        <i class="fas fa-chart-pie"></i> <span class="nav-label">Overview</span>
                    </a>
                </li>

                <!-- FRONT DESK -->
                <?php if (has_permission('front_desk') || has_permission('manage_bookings')): ?>
                <li class="dropdown <?php echo in_array($current_page, array('admin_index.php', 'admin_booking_management.php', 'admin_checkin.php', 'admin_checkout_management.php', 'admin_extend_stay.php', 'admin_cancellation.php')) ? 'active' : ''; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" 
                       title="Front Desk Operations - Reservations, Check-in, Checkout">
                        <i class="fas fa-concierge-bell"></i> <span class="nav-label">Front Desk</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (has_permission('front_desk') || has_permission('manage_bookings')): ?>
                        <li>
                            <a href="admin_index.php" title="View room grid and create new reservations">
                                <i class="fas fa-calendar-plus"></i> New Reservation
                            </a>
                        </li>
                        <li>
                            <a href="admin_booking_management.php" title="View and manage all bookings">
                                <i class="fas fa-list-alt"></i> Manage Bookings
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('checkin_guest') || has_permission('front_desk')): ?>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_checkin.php" title="Check in arriving guests">
                                <i class="fas fa-sign-in-alt"></i> Guest Check-in
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('checkout_guest') || has_permission('front_desk')): ?>
                        <li>
                            <a href="admin_checkout_management.php" title="Check out departing guests">
                                <i class="fas fa-sign-out-alt"></i> Guest Checkout
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('extend_stay') || has_permission('front_desk')): ?>
                        <li>
                            <a href="admin_extend_stay.php" title="Extend guest stays">
                                <i class="fas fa-clock"></i> Extend Stay
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- GUESTS -->
                <?php if (has_permission('manage_customers') || has_permission('manage_staff') || has_permission('view_attendance')): ?>
                <li class="dropdown <?php echo in_array($current_page, array('admin_customers.php', 'admin_staff.php', 'admin_attendance_clock.php')) ? 'active' : ''; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="Manage guests and staff">
                        <i class="fas fa-users"></i> <span class="nav-label">Guests</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (has_permission('manage_customers')): ?>
                        <li>
                            <a href="admin_customers.php" title="Manage customer profiles">
                                <i class="fas fa-address-book"></i> Customers
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('manage_staff') || has_permission('view_attendance')): ?>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_staff.php" title="Manage hotel staff">
                                <i class="fas fa-user-tie"></i> Staff Management
                            </a>
                        </li>
                        <li>
                            <a href="admin_attendance_clock.php" title="Staff clock in/out">
                                <i class="fas fa-clock"></i> Staff Attendance
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- ROOMS -->
                <?php if (has_permission('manage_rooms') || has_permission('manage_buildings')): ?>
                <li class="dropdown <?php echo in_array($current_page, array('admin_manage_rooms.php', 'admin_manage_blocks.php')) ? 'active' : ''; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="Manage rooms and buildings">
                        <i class="fas fa-building"></i> <span class="nav-label">Rooms</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (has_permission('manage_rooms')): ?>
                        <li>
                            <a href="admin_manage_rooms.php" title="Add, edit, or delete rooms">
                                <i class="fas fa-door-closed"></i> Manage Rooms
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('manage_buildings')): ?>
                        <li>
                            <a href="admin_manage_blocks.php" title="Manage building properties">
                                <i class="fas fa-building"></i> Manage Buildings
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- FINANCE -->
                <?php if (has_permission('finance') || has_permission('mark_paid') || has_permission('process_refunds') || has_permission('manage_disputes')): ?>
                <li class="dropdown <?php echo in_array($current_page, array('admin_invoice_management.php', 'admin_refunds.php', 'admin_dispute.php', 'admin_resolve_dispute.php')) ? 'active' : ''; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="Financial management">
                        <i class="fas fa-coins"></i> <span class="nav-label">Finance</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (has_permission('finance') || has_permission('mark_paid')): ?>
                        <li>
                            <a href="admin_invoice_management.php" title="View and manage invoices">
                                <i class="fas fa-file-invoice"></i> Invoices
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('process_refunds') || has_permission('finance')): ?>
                        <li>
                            <a href="admin_refunds.php" title="Process refunds">
                                <i class="fas fa-money-bill-wave"></i> Refunds
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('manage_disputes') || has_permission('finance')): ?>
                        <li>
                            <a href="admin_dispute.php" title="Handle customer disputes">
                                <i class="fas fa-exclamation-triangle"></i> Disputes
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- SETTINGS -->
                <?php if (has_permission('manage_users') || has_permission('view_logs') || has_permission('view_reports') || has_permission('manage_settings') || get_current_role_name() === 'super_admin'): ?>
                <li class="dropdown <?php echo in_array($current_page, array('admin_users.php', 'admin_logs.php', 'admin_reports.php', 'admin_settings.php', 'admin_roles.php')) ? 'active' : ''; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="System administration">
                        <i class="fas fa-cogs"></i> <span class="nav-label">Settings</span> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (has_permission('manage_users')): ?>
                        <li>
                            <a href="admin_users.php" title="Manage admin users">
                                <i class="fas fa-user-shield"></i> Admin Users
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('view_logs')): ?>
                        <li>
                            <a href="admin_logs.php" title="View activity logs">
                                <i class="fas fa-clipboard-list"></i> Activity Logs
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('view_reports')): ?>
                        <li>
                            <a href="admin_reports.php" title="Generate reports">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (has_permission('manage_settings') || get_current_role_name() === 'super_admin'): ?>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_settings.php" title="Hotel branding settings">
                                <i class="fas fa-sliders-h"></i> Hotel Settings
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Roles & Permissions - Super Admin only -->
                        <?php if (get_current_role_name() === 'super_admin'): ?>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_roles.php" title="Manage roles and permissions">
                                <i class="fas fa-lock"></i> <strong>Roles & Permissions</strong>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <!-- RIGHT SIDE - Notifications & User -->
            <ul class="nav navbar-nav navbar-right">
                <!-- Notification Bell -->
                <li class="dropdown notifications-dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="Notifications - Alerts and updates">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_types > 0): ?>
                        <span class="badge badge-<?php echo h($badge_color); ?> notification-badge">
                            <?php echo h($notification_types); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu notifications-menu">
                        <li class="dropdown-header">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($notification_types > 0): ?>
                            <span class="badge pull-right"><?php echo h($notification_types); ?></span>
                            <?php endif; ?>
                        </li>
                        <li role="separator" class="divider"></li>
                        
                        <?php if (empty($notifications)): ?>
                        <li class="text-center text-muted" style="padding:15px 10px;">
                            <i class="fas fa-check-circle" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                            All clear! No pending notifications.
                        </li>
                        <?php else: ?>
                            <?php foreach ($notifications as $category): ?>
                            <li class="notification-category">
                                <span class="category-label">
                                    <i class="fas <?php echo h($category['icon']); ?>"></i>
                                    <?php echo h($category['category']); ?>
                                </span>
                            </li>
                            <?php foreach ($category['items'] as $item): ?>
                            <li>
                                <a href="<?php echo h($item['link']); ?>" class="notification-item notification-<?php echo h($item['type']); ?>">
                                    <span class="notification-icon">
                                        <i class="fas <?php echo h($item['icon']); ?>"></i>
                                    </span>
                                    <span class="notification-text">
                                        <span class="notification-count badge badge-<?php echo $item['type'] == 'overdue' ? 'danger' : ($item['type'] == 'pending' ? 'warning' : 'info'); ?>">
                                            <?php echo h($item['count']); ?>
                                        </span>
                                        <?php echo h($item['text']); ?>
                                    </span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <li role="separator" class="divider"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <li class="dropdown-footer text-center">
                            <a href="admin_dashboard.php" class="text-muted">
                                <i class="fas fa-arrow-right"></i> View Dashboard
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- User Dropdown -->
                <li class="dropdown user-dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown"
                       title="Account settings">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars(get_current_admin_username(), ENT_QUOTES, 'UTF-8'); ?>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars(get_current_admin_fullname(), ENT_QUOTES, 'UTF-8'); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(get_current_admin_username(), ENT_QUOTES, 'UTF-8'); ?></small>
                            <br><small class="text-muted"><?php echo get_role_display(get_current_role_id()); ?></small>
                        </li>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_users.php?view=<?php echo h($current_user_id); ?>" title="View your profile">
                                <i class="fas fa-id-card"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a href="admin_settings.php" title="Hotel settings">
                                <i class="fas fa-cog"></i> Hotel Settings
                            </a>
                        </li>
                        <?php if (get_current_role_name() === 'super_admin'): ?>
                        <li>
                            <a href="admin_roles.php" title="Manage roles and permissions">
                                <i class="fas fa-lock"></i> Roles & Permissions
                            </a>
                        </li>
                        <?php endif; ?>
                        <li role="separator" class="divider"></li>
                        <li>
                            <a href="admin_logout.php" title="Logout from admin panel">
                                <i class="fas fa-sign-out-alt"></i> <strong>Logout</strong>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- Add spacing for fixed navbar -->
<div style="height: 60px;"></div>
<?php endif; ?>

<!-- Main Content Container -->
<div class="container main-container">
    <div class="content-wrapper">

<script>
// Dropdown hover support for desktop
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth >= 992) {
        var dropdowns = document.querySelectorAll('.admin-nav .dropdown');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('mouseenter', function() {
                var toggle = this.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.click();
                }
            });
            dropdown.addEventListener('mouseleave', function() {
                var menu = this.querySelector('.dropdown-menu');
                if (menu && menu.classList.contains('show')) {
                    var toggle = this.querySelector('.dropdown-toggle');
                    if (toggle) {
                        toggle.click();
                    }
                }
            });
        });
    }
});
</script>