<?php
// config/permissions.php - Centralized page permissions
// Define which permission is required for each admin page
// If not listed, page is accessible to all logged-in admins

return [
    'admin_users.php' => 'manage_users',                    // Only super admins
    'admin_logs.php' => 'view_logs',                        // Auditors +
    'admin_reports.php' => 'view_reports',                  // Auditors +
    'admin_manage_rooms.php' => 'manage_rooms',             // Admin +
    'admin_manage_blocks.php' => 'manage_rooms',            // Same as rooms
    'admin_mark_paid.php' => 'mark_paid',                    // Receptionist +
    'admin_checkout.php' => 'checkout_guest',               // Receptionist +
    'admin_cancellation.php' => 'cancel_booking',           // Receptionist +
    'admin_resolve_dispute.php' => 'resolve_dispute',       // Receptionist +
    'admin_extend_stay.php' => 'extend_stay',               // Receptionist +
    'admin_refunds.php' => 'view_reports',                  // Same tier as reports
    'admin_dispute.php' => 'resolve_dispute',               // Same tier as resolve
    'admin_staff.php' => 'manage_staff',                    // Manager +
    'admin_settings.php' => 'manage_settings',              // Super admin only
    'setup.php' => 'manage_settings',                       // Super admin only
    // Add more as needed
    // Pages not listed = accessible to all roles
];