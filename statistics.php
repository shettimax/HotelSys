<?php
// statistics.php - COMPLETE STATISTICS DASHBOARD (December 12, 2025)
require_once __DIR__.'/database.php';
// silence
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}

include __DIR__.'/header.php';

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ==================== REVENUE & BOOKINGS ====================
$rev_sql = "SELECT COALESCE(SUM(i.amount),0) AS rev FROM invoices i 
            JOIN bookings b ON i.booking_id = b.id 
            WHERE i.paid = 1 AND DATE_FORMAT(i.paid_at,'%Y-%m') = '$month'";
$revenue = mysqli_fetch_assoc(mysqli_query($conn, $rev_sql))['rev'];

$bookings_sql = "SELECT COUNT(*) AS total FROM bookings WHERE DATE_FORMAT(created_at,'%Y-%m') = '$month'";
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, $bookings_sql))['total'];

// ==================== OCCUPANCY ====================
$occupied_sql = "SELECT COUNT(DISTINCT room_id) AS occ FROM bookings 
                 WHERE status = 'paid' AND keycard_issued = 1 
                 AND (checkout_status IS NULL OR checkout_status != 'checked_out')";
$occupied = mysqli_fetch_assoc(mysqli_query($conn, $occupied_sql))['occ'];

$total_rooms_sql = "SELECT COUNT(*) AS total FROM rooms WHERE status != 'maintenance'";
$total_rooms = mysqli_fetch_assoc(mysqli_query($conn, $total_rooms_sql))['total'];
$occupancy_rate = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100, 1) : 0;

// ==================== CUSTOMERS ====================
$total_customers_sql = "SELECT COUNT(*) AS total FROM customers";
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, $total_customers_sql))['total'];

$new_customers_sql = "SELECT COUNT(*) AS total FROM customers WHERE DATE_FORMAT(created_at,'%Y-%m') = '$month'";
$new_customers_this_month = mysqli_fetch_assoc(mysqli_query($conn, $new_customers_sql))['total'];

// ==================== CANCELLATIONS & REFUNDS ====================
$cancelled_sql = "SELECT COUNT(*) AS total, COALESCE(SUM(i.amount),0) AS lost 
                  FROM bookings b 
                  LEFT JOIN invoices i ON b.id = i.booking_id 
                  WHERE b.status = 'cancelled' AND DATE_FORMAT(b.cancelled_at,'%Y-%m') = '$month'";
$cancelled_data = mysqli_fetch_assoc(mysqli_query($conn, $cancelled_sql));
$cancelled_count = $cancelled_data['total'];
$cancelled_revenue_lost = $cancelled_data['lost'];

$refunded_sql = "SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE amount < 0 AND DATE_FORMAT(created_at,'%Y-%m') = '$month'";
$refunded_amount = mysqli_fetch_assoc(mysqli_query($conn, $refunded_sql))['total'];

// ==================== NO-SHOWS (Paid but never checked in) ====================
$noshow_sql = "SELECT COUNT(*) AS total FROM bookings b
               JOIN invoices i ON b.id = i.booking_id
               WHERE i.paid = 1 AND b.keycard_issued = 0 AND b.checkin_date < CURDATE()";
$no_shows = mysqli_fetch_assoc(mysqli_query($conn, $noshow_sql))['total'];

// ==================== TOP 5 CUSTOMERS BY SPEND (ALL TIME) ====================
$top_customers_sql = "SELECT CONCAT(c.firstname,' ',c.lastname) AS name, COALESCE(SUM(i.amount),0) AS spent
                      FROM customers c
                      JOIN bookings b ON c.id = b.customer_id
                      JOIN invoices i ON b.id = i.booking_id AND i.paid = 1
                      GROUP BY c.id ORDER BY spent DESC LIMIT 5";
$top_customers_result = mysqli_query($conn, $top_customers_sql);

// ==================== MONTHLY REVENUE CHART (LAST 12 MONTHS) ====================
$chart_sql = "SELECT DATE_FORMAT(i.paid_at,'%Y-%m') AS m, COALESCE(SUM(i.amount),0) AS rev
              FROM invoices i WHERE i.paid = 1 AND i.paid_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
              GROUP BY m ORDER BY m";
$chart_result = mysqli_query($conn, $chart_sql);

$labels = []; $data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $labels[] = date('M Y', strtotime($row['m'].'-01'));
    $data[] = (float)$row['rev'];
}
?>

<div class="container mt-4">
    <div class="page-header">
        <h2 class="text-olive"><i class="fa fa-chart-pie"></i> Complete Statistics Dashboard</h2>
        <p class="lead">All performance metrics • <?php echo date('F Y', strtotime($month.'-01')); ?></p>
    </div>

    <!-- Month Filter -->
    <div class="row mb-4">
        <div class="col-md-3">
            <form method="get" class="form-inline">
                <div class="input-group">
                    <input type="month" name="month" class="form-control" value="<?php echo h($month); ?>">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Apply</button>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- First Row - Main KPIs -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-money fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">₦<?php echo number_format($revenue); ?></div>
                            <div>Revenue This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-calendar-check-o fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($total_bookings); ?></div>
                            <div>Total Bookings</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-bed fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($occupancy_rate); ?>%</div>
                            <div>Current Occupancy</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-ban fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($cancelled_count); ?></div>
                            <div>Cancellations (₦<?php echo number_format($cancelled_revenue_lost); ?> lost)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-users fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($total_customers); ?></div>
                            <div>Total Customers (+<?php echo h($new_customers_this_month); ?> new)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-reply fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">₦<?php echo number_format(abs($refunded_amount)); ?></div>
                            <div>Refunds Issued</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-ghost fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($no_shows); ?></div>
                            <div>No-Shows (Paid, Never Arrived)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3"><i class="fa fa-home fa-5x"></i></div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo h($occupied); ?>/<?php echo h($total_rooms); ?></div>
                            <div>Rooms Occupied Now</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Top Lists -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-line-chart"></i> Revenue Trend (Last 12 Months)
                </div>
                <div class="panel-body">
                    <canvas id="revenueChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-trophy"></i> Top 5 Customers (All Time)
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tbody>
                            <?php while ($cust = mysqli_fetch_assoc($top_customers_result)): ?>
                            <tr>
                                <td><?php echo h($cust['name']); ?></td>
                                <td class="text-right">₦<?php echo number_format($cust['spent']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php mysqli_data_seek($top_customers_result, 0); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="terminal-feed mt-4">
        <h4 class="text-olive">Quick Navigation</h4>
        <div class="row">
            <div class="col-md-2"><a href="admin_index.php" class="btn btn-primary btn-block">Dashboard</a></div>
            <div class="col-md-2"><a href="admin_booking_management.php" class="btn btn-info btn-block">Bookings</a></div>
            <div class="col-md-2"><a href="admin_invoice_management.php" class="btn btn-success btn-block">Invoices</a></div>
            <div class="col-md-2"><a href="admin_checkout_management.php" class="btn btn-warning btn-block">Checkouts</a></div>
            <div class="col-md-2"><a href="admin_customers.php" class="btn btn-default btn-block">Customers</a></div>
            <div class="col-md-2"><a href="admin_refunds.php" class="btn btn-danger btn-block">Refunds</a></div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Revenue (₦)',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#00ff99',
                backgroundColor: 'rgba(0, 255, 153, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#00ff99',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#00ff99' } } },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#ccc', callback: v => '₦' + v.toLocaleString() } },
                x: { ticks: { color: '#ccc' } }
            }
        }
    });
});
</script>

<?php include __DIR__.'/footer.php'; ?>