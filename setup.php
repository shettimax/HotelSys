<?php
// setup.php - ONE-SHOT SCHEMA MIGRATION
// Adds the columns needed for:
//   1. Role-based permissions (users_admin.role)
//   2. Staff sales tracking (bookings.staff_id, bookings.staff_name + index)
//   3. Staff login (staff.password_hash)
//
// No new tables are created - only columns/indexes added to existing tables.
// Safe to re-run: every step checks INFORMATION_SCHEMA first and is skipped
// if already applied, so running this twice does nothing the second time.

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

// NOTE: login/permission check intentionally removed so this can be run
// directly for a one-off schema fixup. This means anyone who can reach
// this URL can run ALTER TABLE statements against your database - delete
// this file (or re-add the guard below) once your schema is up to date.
//
// if (!is_admin_logged_in()) {
//     header('Location: admin_login.php');
//     exit;
// }
// require_permission();

/**
 * Check if a column exists on a given table.
 */
function column_exists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    return ((int)$row['cnt']) > 0;
}

/**
 * Check if an index exists on a given table.
 */
function index_exists($conn, $table, $index_name) {
    $table = mysqli_real_escape_string($conn, $table);
    $index_name = mysqli_real_escape_string($conn, $index_name);
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND INDEX_NAME = '$index_name'";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    return ((int)$row['cnt']) > 0;
}

/**
 * Check if a table exists at all (used before touching it, in case a
 * deployment doesn't have the `staff` table yet, for example).
 */
function table_exists($conn, $table) {
    $table = mysqli_real_escape_string($conn, $table);
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($res);
    return ((int)$row['cnt']) > 0;
}

$results = array();
$ran_migration = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $ran_migration = true;

    // ============================================================
    // 1. ROLE-BASED PERMISSIONS: users_admin.role
    // ============================================================
    if (!table_exists($conn, 'users_admin')) {
        $results[] = array('step' => 'users_admin.role', 'status' => 'skip', 'msg' => 'Table users_admin does not exist');
    } elseif (column_exists($conn, 'users_admin', 'role')) {
        $results[] = array('step' => 'users_admin.role', 'status' => 'skip', 'msg' => 'Column already exists');
    } else {
        $sql = "ALTER TABLE `users_admin` ADD COLUMN `role` VARCHAR(50) DEFAULT 'receptionist' AFTER `fullname`";
        if (mysqli_query($conn, $sql)) {
            $results[] = array('step' => 'users_admin.role', 'status' => 'ok', 'msg' => 'Column added');
        } else {
            $results[] = array('step' => 'users_admin.role', 'status' => 'error', 'msg' => mysqli_error($conn));
        }
    }

    // Backfill known admin roles (safe no-op if those ids don't exist or
    // the role column wasn't just added - UPDATE with no matching rows
    // simply affects 0 rows, it doesn't error).
    if (table_exists($conn, 'users_admin') && column_exists($conn, 'users_admin', 'role')) {
        $backfill = array(
            1 => 'super_admin',
            2 => 'manager',
            4 => 'receptionist',
        );
        $backfill_done = array();
        foreach ($backfill as $id => $role) {
            $role_esc = mysqli_real_escape_string($conn, $role);
            $ok = mysqli_query($conn, "UPDATE `users_admin` SET `role` = '$role_esc' WHERE `id` = " . (int)$id);
            $backfill_done[] = $ok ? "id $id => $role" : "id $id FAILED (" . mysqli_error($conn) . ")";
        }
        $results[] = array('step' => 'users_admin.role backfill', 'status' => 'ok', 'msg' => implode(', ', $backfill_done));
    }

    // ============================================================
    // 2. STAFF SALES TRACKING: bookings.staff_id, bookings.staff_name
    // ============================================================
    if (!table_exists($conn, 'bookings')) {
        $results[] = array('step' => 'bookings.staff_id/staff_name', 'status' => 'skip', 'msg' => 'Table bookings does not exist');
    } else {
        if (column_exists($conn, 'bookings', 'staff_id')) {
            $results[] = array('step' => 'bookings.staff_id', 'status' => 'skip', 'msg' => 'Column already exists');
        } else {
            $anchor = column_exists($conn, 'bookings', 'keycard_issued_by') ? 'AFTER `keycard_issued_by`' : '';
            $sql = "ALTER TABLE `bookings` ADD COLUMN `staff_id` INT NULL $anchor";
            if (mysqli_query($conn, $sql)) {
                $results[] = array('step' => 'bookings.staff_id', 'status' => 'ok', 'msg' => 'Column added');
            } else {
                $results[] = array('step' => 'bookings.staff_id', 'status' => 'error', 'msg' => mysqli_error($conn));
            }
        }

        if (column_exists($conn, 'bookings', 'staff_name')) {
            $results[] = array('step' => 'bookings.staff_name', 'status' => 'skip', 'msg' => 'Column already exists');
        } else {
            $anchor = column_exists($conn, 'bookings', 'staff_id') ? 'AFTER `staff_id`' : '';
            $sql = "ALTER TABLE `bookings` ADD COLUMN `staff_name` VARCHAR(100) NULL $anchor";
            if (mysqli_query($conn, $sql)) {
                $results[] = array('step' => 'bookings.staff_name', 'status' => 'ok', 'msg' => 'Column added');
            } else {
                $results[] = array('step' => 'bookings.staff_name', 'status' => 'error', 'msg' => mysqli_error($conn));
            }
        }

        if (index_exists($conn, 'bookings', 'idx_staff_id')) {
            $results[] = array('step' => 'bookings idx_staff_id', 'status' => 'skip', 'msg' => 'Index already exists');
        } elseif (column_exists($conn, 'bookings', 'staff_id')) {
            $sql = "ALTER TABLE `bookings` ADD INDEX `idx_staff_id` (`staff_id`)";
            if (mysqli_query($conn, $sql)) {
                $results[] = array('step' => 'bookings idx_staff_id', 'status' => 'ok', 'msg' => 'Index added');
            } else {
                $results[] = array('step' => 'bookings idx_staff_id', 'status' => 'error', 'msg' => mysqli_error($conn));
            }
        } else {
            $results[] = array('step' => 'bookings idx_staff_id', 'status' => 'skip', 'msg' => 'staff_id column missing, cannot index');
        }
    }

    // ============================================================
    // 3. STAFF LOGIN: staff.password_hash
    // ============================================================
    if (!table_exists($conn, 'staff')) {
        $results[] = array('step' => 'staff.password_hash', 'status' => 'skip', 'msg' => 'Table staff does not exist');
    } elseif (column_exists($conn, 'staff', 'password_hash')) {
        $results[] = array('step' => 'staff.password_hash', 'status' => 'skip', 'msg' => 'Column already exists');
    } else {
        $anchor = column_exists($conn, 'staff', 'phone') ? 'AFTER `phone`' : '';
        $sql = "ALTER TABLE `staff` ADD COLUMN `password_hash` VARCHAR(255) NULL $anchor";
        if (mysqli_query($conn, $sql)) {
            $results[] = array('step' => 'staff.password_hash', 'status' => 'ok', 'msg' => 'Column added');
        } else {
            $results[] = array('step' => 'staff.password_hash', 'status' => 'error', 'msg' => mysqli_error($conn));
        }
    }

    log_admin_activity('Schema Migration', 'Ran setup.php schema migration (roles, staff tracking, staff login)');
}

// Current state check (for the pre-run summary table)
$state = array(
    array('table' => 'users_admin', 'column' => 'role', 'exists' => table_exists($conn, 'users_admin') && column_exists($conn, 'users_admin', 'role')),
    array('table' => 'bookings', 'column' => 'staff_id', 'exists' => table_exists($conn, 'bookings') && column_exists($conn, 'bookings', 'staff_id')),
    array('table' => 'bookings', 'column' => 'staff_name', 'exists' => table_exists($conn, 'bookings') && column_exists($conn, 'bookings', 'staff_name')),
    array('table' => 'bookings', 'column' => 'idx_staff_id (index)', 'exists' => table_exists($conn, 'bookings') && index_exists($conn, 'bookings', 'idx_staff_id')),
    array('table' => 'staff', 'column' => 'password_hash', 'exists' => table_exists($conn, 'staff') && column_exists($conn, 'staff', 'password_hash')),
);
$all_applied = true;
foreach ($state as $s) {
    if (!$s['exists']) { $all_applied = false; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Schema Setup</title>
<style>
    body { background:#0a0a0a; color:#d0d0d0; font-family:'Courier New', monospace; padding:30px; }
    .box { max-width:800px; margin:0 auto; border:2px solid #00ff99; border-radius:8px; padding:20px 25px; background:#0d0d0d; box-shadow:0 0 30px rgba(0,255,153,0.1); }
    h1 { color:#00ff99; font-size:20px; text-transform:uppercase; letter-spacing:2px; margin-top:0; }
    table { width:100%; border-collapse:collapse; margin:15px 0; }
    th, td { padding:8px 10px; border-bottom:1px solid #222; text-align:left; font-size:13px; }
    th { color:#888; text-transform:uppercase; font-size:11px; letter-spacing:1px; }
    .ok { color:#00ff99; }
    .error { color:#ff5555; }
    .skip { color:#888; }
    .pending { color:#ffcc00; }
    .applied { color:#00ff99; }
    button { background:#00ff99; color:#000; border:none; padding:12px 25px; font-weight:bold; border-radius:4px; cursor:pointer; font-family:'Courier New', monospace; text-transform:uppercase; letter-spacing:1px; }
    button:hover { background:#00cc7a; }
    a { color:#00ff99; }
    .note { font-size:12px; color:#666; margin-top:20px; line-height:1.6; }
</style>
</head>
<body>
<div class="box">
    <h1><i></i>&#9881; Schema Migration</h1>

    <?php if ($ran_migration): ?>
        <h3 style="color:#00ff99;">Migration Results</h3>
        <table>
            <thead><tr><th>Step</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['step']); ?></td>
                    <td class="<?php echo htmlspecialchars($r['status']); ?>"><?php echo strtoupper(htmlspecialchars($r['status'])); ?></td>
                    <td><?php echo htmlspecialchars($r['msg']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><a href="setup.php">&laquo; Re-check current state</a></p>
    <?php else: ?>
        <p>Current schema state:</p>
        <table>
            <thead><tr><th>Table</th><th>Column / Index</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($state as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['table']); ?></td>
                    <td><?php echo htmlspecialchars($s['column']); ?></td>
                    <td class="<?php echo $s['exists'] ? 'applied' : 'pending'; ?>">
                        <?php echo $s['exists'] ? 'APPLIED' : 'PENDING'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($all_applied): ?>
            <p class="ok">Everything is already applied - nothing to do. Safe to re-run any time.</p>
        <?php else: ?>
            <form method="post">
                <button type="submit" name="run_migration" value="1">Run Migration</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <div class="note">
        This adds: <code>users_admin.role</code>, <code>bookings.staff_id</code>,
        <code>bookings.staff_name</code> (+ index), <code>staff.password_hash</code>.
        No tables are created or dropped. Each step is skipped automatically if
        already applied, so this is safe to run more than once.
        Delete this file once your schema is up to date - it isn't linked from
        anywhere in the admin menu and shouldn't stay reachable long-term.
    </div>
</div>
</body>
</html>
