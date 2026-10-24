<?php
// staff_dashboard.php - Shows a logged-in staff member their own
// booking/sales performance (bookings.staff_id tracks who made each booking).

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

require_staff_login();

$staff_id = get_current_staff_id();
$staff_name = get_current_staff_name();

$staff_row = null;
$res = mysqli_query($conn, "SELECT * FROM staff WHERE id = " . (int)$staff_id . " LIMIT 1");
if ($res) {
    $staff_row = mysqli_fetch_assoc($res);
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date_esc = mysqli_real_escape_string($conn, $start_date);
$end_date_esc = mysqli_real_escape_string($conn, $end_date);

$has_tracking = db_column_exists($conn, 'bookings', 'staff_id');

$summary = array('total_bookings' => 0, 'total_sales' => 0, 'avg_value' => 0, 'paid_bookings' => 0);
$recent_bookings = array();

if ($has_tracking) {
    $summary_sql = "
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status != 'cancelled' THEN amount ELSE 0 END) as total_sales,
            AVG(CASE WHEN status != 'cancelled' THEN amount ELSE NULL END) as avg_value,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_bookings
        FROM bookings
        WHERE staff_id = " . (int)$staff_id . "
          AND checkin_date BETWEEN '$start_date_esc' AND '$end_date_esc'
    ";
    $summary_result = mysqli_query($conn, $summary_sql);
    if ($summary_result && ($row = mysqli_fetch_assoc($summary_result))) {
        $summary = array(
            'total_bookings' => (int)$row['total_bookings'],
            'total_sales' => (float)$row['total_sales'],
            'avg_value' => (float)$row['avg_value'],
            'paid_bookings' => (int)$row['paid_bookings'],
        );
    }

    $list_sql = "
        SELECT b.id, b.checkin_date, b.checkout_date, b.amount, b.status,
               CONCAT(c.firstname, ' ', c.lastname) as guest_name,
               r.room_number, bu.name as building_name
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE b.staff_id = " . (int)$staff_id . "
          AND b.checkin_date BETWEEN '$start_date_esc' AND '$end_date_esc'
        ORDER BY b.checkin_date DESC
        LIMIT 100
    ";
    $list_result = mysqli_query($conn, $list_sql);
    if ($list_result) {
        while ($row = mysqli_fetch_assoc($list_result)) {
            $recent_bookings[] = $row;
        }
    }
}

function sd_badge($status) {
    $map = array(
        'pending'    => 'warning',
        'confirmed'  => 'info',
        'paid'       => 'success',
        'cancelled'  => 'danger',
        'completed'  => 'secondary'
    );
    $class = isset($map[$status]) ? $map[$status] : 'secondary';
    return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst($status)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Dashboard</title>
<style>
    body { background:#0a0a0a; color:#d0d0d0; font-family:'Courier New', monospace; margin:0; padding:20px; }
    .wrap { max-width:1000px; margin:0 auto; }
    .topbar { display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #00ff99; padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
    .topbar h1 { color:#00ff99; font-size:18px; text-transform:uppercase; letter-spacing:2px; margin:0; }
    .topbar .who { font-size:12px; color:#888; }
    .logout { color:#ff6666; text-decoration:none; font-size:12px; border:1px solid #ff6666; padding:6px 14px; border-radius:4px; }
    .logout:hover { background:#ff6666; color:#000; }
    .cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom:25px; }
    .card { border:1px solid #222; border-radius:8px; padding:18px; background:#0d0d0d; }
    .card .label { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
    .card .value { font-size:22px; color:#00ff99; font-weight:bold; }
    form.filter { display:flex; gap:10px; align-items:end; margin-bottom:20px; flex-wrap:wrap; }
    form.filter label { display:block; font-size:11px; color:#888; text-transform:uppercase; margin-bottom:5px; }
    form.filter input { background:#111; border:1px solid #333; color:#eee; padding:8px 10px; border-radius:4px; font-family:'Courier New', monospace; }
    form.filter button { background:#00ff99; color:#000; border:none; padding:8px 18px; border-radius:4px; font-weight:bold; cursor:pointer; text-transform:uppercase; font-size:12px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:9px 10px; border-bottom:1px solid #222; text-align:left; }
    th { color:#888; text-transform:uppercase; font-size:11px; letter-spacing:1px; }
    .badge { display:inline-block; padding:2px 10px; border-radius:10px; font-size:11px; font-weight:bold; text-transform:uppercase; }
    .badge.warning { background:#ffcc00; color:#000; }
    .badge.info { background:#3399ff; color:#000; }
    .badge.success { background:#00ff99; color:#000; }
    .badge.danger { background:#ff5555; color:#000; }
    .badge.secondary { background:#555; color:#fff; }
    .empty { text-align:center; color:#666; padding:30px; }
    .notice { background:#1a1500; border:1px solid #ffcc00; color:#ffcc66; padding:14px; border-radius:6px; margin-bottom:20px; font-size:13px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <div>
            <h1>Staff Dashboard</h1>
            <div class="who">
                <?php echo htmlspecialchars($staff_name); ?>
                <?php if ($staff_row): ?> &bull; <?php echo htmlspecialchars($staff_row['pnumber']); ?> &bull; <?php echo htmlspecialchars($staff_row['department']); ?><?php endif; ?>
            </div>
        </div>
        <a href="staff_logout.php" class="logout">Logout</a>
    </div>

    <?php if (!$has_tracking): ?>
        <div class="notice">Sales tracking isn't set up yet - ask an administrator to run setup.php.</div>
    <?php else: ?>

    <form method="get" class="filter">
        <div>
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div>
            <label>End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <button type="submit">Filter</button>
    </form>

    <div class="cards">
        <div class="card">
            <div class="label">Bookings Made</div>
            <div class="value"><?php echo $summary['total_bookings']; ?></div>
        </div>
        <div class="card">
            <div class="label">Total Sales</div>
            <div class="value">&#8358;<?php echo number_format($summary['total_sales'], 2); ?></div>
        </div>
        <div class="card">
            <div class="label">Avg Booking Value</div>
            <div class="value">&#8358;<?php echo number_format($summary['avg_value'], 2); ?></div>
        </div>
        <div class="card">
            <div class="label">Paid Bookings</div>
            <div class="value"><?php echo $summary['paid_bookings']; ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_bookings)): ?>
            <tr><td colspan="6" class="empty">No bookings in this date range.</td></tr>
            <?php else: ?>
            <?php foreach ($recent_bookings as $b): ?>
            <tr>
                <td><?php echo date('M d, Y', strtotime($b['checkin_date'])); ?></td>
                <td><?php echo date('M d, Y', strtotime($b['checkout_date'])); ?></td>
                <td><?php echo htmlspecialchars($b['guest_name']); ?></td>
                <td><?php echo htmlspecialchars($b['building_name'] . ' - ' . $b['room_number']); ?></td>
                <td>&#8358;<?php echo number_format($b['amount'], 2); ?></td>
                <td><?php echo sd_badge($b['status']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>
</body>
</html>
