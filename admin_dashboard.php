<?php
// admin_dashboard.php - Updated for PHP 5.6
// Removed inline CSS, using app.css
// Using functions from functions.php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

include __DIR__ . '/header.php';

// Fetch statistics
$stats = array();

// Total bookings
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings");
$row = mysqli_fetch_assoc($result);
$stats['total_bookings'] = $row['total'];

// Active bookings (paid and not checked out)
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings WHERE status = 'paid' AND checkout_status != 'checked_out'");
$row = mysqli_fetch_assoc($result);
$stats['active_bookings'] = $row['total'];

// Total customers
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM customers");
$row = mysqli_fetch_assoc($result);
$stats['total_customers'] = $row['total'];

// Available rooms
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
$row = mysqli_fetch_assoc($result);
$stats['available_rooms'] = $row['total'];

// Total revenue
$result = mysqli_query($conn, "SELECT SUM(amount) as total FROM bookings WHERE status = 'paid'");
$row = mysqli_fetch_assoc($result);
$stats['revenue'] = isset($row['total']) ? (float)$row['total'] : 0;

// Total buildings
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM buildings");
$row = mysqli_fetch_assoc($result);
$stats['total_buildings'] = $row['total'];

// Building types stats
$result = mysqli_query($conn, "SELECT building_type, COUNT(*) as count, AVG(base_price) as avg_price FROM buildings GROUP BY building_type");
$building_types = array();
while ($row = mysqli_fetch_assoc($result)) {
    $building_types[$row['building_type']] = array(
        'count' => $row['count'],
        'avg_price' => (float)$row['avg_price']
    );
}

// Top 5 customers by spend
$top_customers_sql = "SELECT CONCAT(c.firstname,' ',c.lastname) AS name, COALESCE(SUM(i.amount),0) AS spent
                      FROM customers c
                      JOIN bookings b ON c.id = b.customer_id
                      JOIN invoices i ON b.id = i.booking_id AND i.paid = 1
                      GROUP BY c.id ORDER BY spent DESC LIMIT 5";
$top_customers_result = mysqli_query($conn, $top_customers_sql);

// Recent bookings - ADDED keycard_issued and actual_checkin_datetime
$recent_bookings_sql = "SELECT b.*, c.firstname, c.lastname, r.room_number, r.building_id, 
                               i.id as invoice_id, b.keycard_issued, b.actual_checkin_datetime
                        FROM bookings b 
                        JOIN customers c ON b.customer_id = c.id 
                        JOIN rooms r ON b.room_id = r.id
                        LEFT JOIN invoices i ON b.id = i.booking_id
                        ORDER BY b.created_at DESC 
                        LIMIT 10";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_sql);

// Rooms needing checkout today - ONLY FOR GUESTS WHO ARE CHECKED IN
$today = date('Y-m-d');
$checkout_today_sql = "SELECT b.*, c.firstname, c.lastname, r.room_number, bld.name as building_name,
                              b.keycard_issued, b.actual_checkin_datetime
                       FROM bookings b
                       JOIN customers c ON b.customer_id = c.id
                       JOIN rooms r ON b.room_id = r.id
                       JOIN buildings bld ON r.building_id = bld.id
                       WHERE b.status = 'paid' 
                       AND b.checkout_date = '$today'
                       AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
                       AND b.keycard_issued = 1
                       AND b.actual_checkin_datetime IS NOT NULL
                       ORDER BY b.checkout_date ASC
                       LIMIT 5";
$checkout_today_result = mysqli_query($conn, $checkout_today_sql);
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo h($stats['total_bookings']); ?></h3>
                        <p class="card-text"><i class="fa fa-list-alt"></i> Bookings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo h($stats['active_bookings']); ?></h3>
                        <p class="card-text"><i class="fa fa-check-circle"></i> Active Stays</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo h($stats['total_customers']); ?></h3>
                        <p class="card-text"><i class="fa fa-users"></i> Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo h($stats['available_rooms']); ?></h3>
                        <p class="card-text"><i class="fa fa-bed"></i> Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo h($stats['total_buildings']); ?></h3>
                        <p class="card-text"><i class="fa fa-building"></i> Buildings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title"><?php echo formatPrice($stats['revenue']); ?></h3>
                        <p class="card-text"><i class="fa fa-money-bill-wave"></i> Revenue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column: Recent Bookings -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-clock"></i> Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 1;
                                    if (mysqli_num_rows($recent_bookings_result) > 0):
                                        mysqli_data_seek($recent_bookings_result, 0);
                                        while ($booking = mysqli_fetch_assoc($recent_bookings_result)): 
                                            $status_badge = get_booking_status_badge($booking['status']);
                                            
                                            // FIXED: Only allow extend stay for checked-in guests
                                            $guest_checked_in = (
                                                $booking['keycard_issued'] == 1 && 
                                                !empty($booking['actual_checkin_datetime'])
                                            );
                                            $can_extend_stay = (
                                                $booking['status'] == 'paid' && 
                                                $booking['checkout_date'] >= date('Y-m-d') &&
                                                $guest_checked_in &&
                                                $booking['checkout_status'] != 'checked_out'
                                            );
                                            
                                            $can_mark_paid = ($booking['status'] !== 'paid' && $booking['status'] !== 'cancelled' && isset($booking['invoice_id']) && $booking['invoice_id']);
                                    ?>
                                    <tr>
                                        <td><span class="badge badge-secondary"><?php echo $counter++; ?></span></td>
                                        <td><strong><?php echo h($booking['firstname'] . ' ' . $booking['lastname']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo h($booking['room_number']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                                        <td><strong class="text-success"><?php echo formatPrice($booking['amount']); ?></strong></td>
                                        <td><span class="badge badge-<?php echo h($status_badge); ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (isset($booking['invoice_id']) && $booking['invoice_id']): ?>
                                                    <a href="admin_view_booking.php?booking_id=<?php echo h($booking['id']); ?>" class="btn btn-info" title="View Booking">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <?php if ($can_mark_paid): ?>
                                                        <a href="admin_mark_paid.php?invoice_id=<?php echo h($booking['invoice_id']); ?>" class="btn btn-success" title="Mark Paid">
                                                            <i class="fa fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a href="admin_view_booking.php?booking_id=<?php echo h($booking['id']); ?>" class="btn btn-info" title="View Booking">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($can_extend_stay): ?>
                                                    <a href="admin_extend_stay.php?booking=<?php echo h($booking['id']); ?>" class="btn btn-primary" title="Extend Stay">
                                                        <i class="fa fa-clock"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fa fa-info-circle"></i> No recent bookings found.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Top Customers and Building Stats -->
            <div class="col-md-4">
                <!-- Top 5 Customers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-trophy"></i> Top 5 Customers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Customer</th>
                                        <th class="text-right">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    if (mysqli_num_rows($top_customers_result) > 0):
                                        mysqli_data_seek($top_customers_result, 0);
                                        while ($customer = mysqli_fetch_assoc($top_customers_result)): 
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank == 1): ?>
                                                <span class="badge badge-warning">🥇</span>
                                            <?php elseif ($rank == 2): ?>
                                                <span class="badge badge-secondary">🥈</span>
                                            <?php elseif ($rank == 3): ?>
                                                <span class="badge badge-warning">🥉</span>
                                            <?php else: ?>
                                                <span class="badge badge-dark"><?php echo h($rank); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo h($customer['name']); ?></td>
                                        <td class="text-right"><strong class="text-success"><?php echo formatPrice($customer['spent']); ?></strong></td>
                                    </tr>
                                    <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No customer data available</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Checkouts Today - ONLY FOR GUESTS WHO ARE CHECKED IN -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-sign-out-alt"></i> Checkouts Today (Checked In)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($checkout_today_result) > 0): ?>
                                        <?php while ($checkout = mysqli_fetch_assoc($checkout_today_result)): ?>
                                        <tr>
                                            <td><?php echo h($checkout['firstname'] . ' ' . $checkout['lastname']); ?></td>
                                            <td>
                                                <?php echo h($checkout['room_number']); ?>
                                                <?php if ($checkout['keycard_issued'] == 1 && !empty($checkout['actual_checkin_datetime'])): ?>
                                                    <br><small class="text-success"><i class="fa fa-key"></i> Checked In</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="admin_checkout.php?booking_id=<?php echo h($checkout['id']); ?>" class="btn btn-danger btn-sm">
                                                    <i class="fa fa-sign-out-alt"></i> Checkout
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                No checked-in guests scheduled for checkout today
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Building Types -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-building"></i> Building Types</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Count</th>
                                        <th class="text-right">Avg. Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($building_types as $type => $data): ?>
                                    <tr>
                                        <td><?php echo ucfirst($type); ?></td>
                                        <td><span class="badge badge-info"><?php echo h($data['count']); ?></span></td>
                                        <td class="text-right text-success"><?php echo formatPrice($data['avg_price']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-link"></i> Quick Navigation</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_index.php" class="btn btn-success btn-block">
                            <i class="fa fa-calendar-plus"></i> New Booking
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_manage_rooms.php" class="btn btn-info btn-block">
                            <i class="fa fa-door-closed"></i> Manage Rooms
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_manage_blocks.php" class="btn btn-warning btn-block">
                            <i class="fa fa-building"></i> Buildings
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_customers.php" class="btn btn-primary btn-block">
                            <i class="fa fa-users"></i> Customers
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_checkout_management.php" class="btn btn-default btn-block">
                            <i class="fa fa-sign-out-alt"></i> Checkouts
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <a href="admin_invoice_management.php" class="btn btn-danger btn-block">
                            <i class="fa fa-file-invoice-dollar"></i> Invoices
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>