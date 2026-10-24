<?php
// admin_view_booking.php - TIDIED VERSION
// Clean header: just booking ID + status badges + clean status alert
// All details in their proper sections below

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

if (empty($_GET['booking_id'])) {
    header('Location: admin_booking_management.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];

$sql = "
    SELECT
        b.*,
        b.keycard_issued,
        b.actual_checkin_datetime,
        i.id as invoice_id,
        i.invoice_number,
        i.paid as invoice_paid,
        i.amount as invoice_amount,
        CONCAT(c.firstname, ' ', c.lastname) as customer_name,
        c.firstname,
        c.lastname,
        c.email as customer_email,
        c.phone as customer_phone,
        c.gender,
        c.address,
        c.has_car,
        c.id_type,
        c.id_number,
        c.notes as customer_notes,
        r.room_number,
        r.capacity,
        r.price_per_night,
        bu.name as building_name,
        bu.building_type,
        bu.base_price,
        ch.checkout_date as checkout_timestamp,
        ch.room_condition,
        ch.additional_charges,
        ch.charges_description,
        ch.refund_amount,
        ch.refund_description,
        ch.notes as checkout_notes,
        ch.status as checkout_status,
        ch.checked_out_by as checked_out_by_admin,
        admin_checkout.fullname as checked_out_by_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bu ON r.building_id = bu.id
    LEFT JOIN invoices i ON b.id = i.booking_id
    LEFT JOIN checkouts ch ON b.id = ch.booking_id
    LEFT JOIN users_admin admin_checkout ON ch.checked_out_by = admin_checkout.id
    WHERE b.id = $booking_id
    LIMIT 1
";

$result = mysqli_query($conn, $sql);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header('Location: admin_booking_management.php?alert=' . urlencode('Booking not found!') . '&type=error');
    exit;
}

// Calculate timeline
$today = new DateTime();
$checkin_date = new DateTime($booking['checkin_date']);
$checkout_date = new DateTime($booking['checkout_date']);

$days_to_checkin = $today->diff($checkin_date)->days;
$days_to_checkout = $today->diff($checkout_date)->days;

$is_checkin_today = ($today->format('Y-m-d') == $checkin_date->format('Y-m-d'));
$is_checkout_today = ($today->format('Y-m-d') == $checkout_date->format('Y-m-d'));

if ($today < $checkin_date) {
    $timeline_status = 'upcoming';
} elseif ($today >= $checkin_date && $today <= $checkout_date) {
    $timeline_status = 'active';
} else {
    $timeline_status = 'past';
}

$is_checkout_completed = (
    $booking['checkout_status'] === 'completed' || 
    $booking['checkout_status'] === 'checked_out' ||
    (isset($booking['checkout_status']) && $booking['checkout_status'] == 'checked_out')
);

$is_checked_in = (!empty($booking['keycard_issued']) && $booking['keycard_issued'] == 1);
$is_currently_staying = ($is_checked_in && !$is_checkout_completed);
$is_overdue = ($timeline_status == 'past' && $is_currently_staying && !$is_checkout_completed);
$can_checkout = ($booking['status'] == 'paid' && $is_currently_staying && $today->format('Y-m-d') >= $checkout_date->format('Y-m-d'));

$actual_checkout_display = null;
if ($is_checkout_completed && !empty($booking['checkout_timestamp'])) {
    $actual_checkout_display = date('M d, Y h:i A', strtotime($booking['checkout_timestamp']));
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <!-- ============================================================
             HEADER CARD - Booking ID + Status Badges + Clean Status Alert
             ============================================================ -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="text-terminal"><i class="fas fa-calendar-check"></i> BOOKING #<?php echo h($booking_id); ?></h4>
                        <p class="text-muted mb-0">
                            Created: <?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="mb-2">
                            <span class="badge badge-<?php
                                echo $timeline_status == 'upcoming' ? 'warning' :
                                     ($timeline_status == 'active' ? 'success' : 'secondary');
                            ?>" style="font-size:1.1em;padding:10px 15px;">
                                <?php echo strtoupper($timeline_status); ?> STAY
                            </span>
                            
                            <span class="badge badge-<?php
                                echo $booking['status'] == 'paid' ? 'success' :
                                     ($booking['status'] == 'confirmed' ? 'info' :
                                     ($booking['status'] == 'pending' ? 'warning' : 'danger'));
                            ?>" style="font-size:1.1em;padding:10px 15px;">
                                <?php echo strtoupper($booking['status']); ?>
                            </span>
                            
                            <?php if ($is_checked_in && !$is_checkout_completed): ?>
                            <span class="badge badge-success" style="font-size:1.1em;padding:10px 15px;">
                                <i class="fas fa-key"></i> CHECKED IN
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($is_checkout_completed): ?>
                            <span class="badge badge-secondary" style="font-size:1.1em;padding:10px 15px;">
                                <i class="fas fa-check"></i> CHECKED OUT
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($is_overdue): ?>
                            <span class="badge badge-danger" style="font-size:1.1em;padding:10px 15px;animation: blinker 1s linear infinite;">
                                <i class="fas fa-exclamation-triangle"></i> OVERDUE
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2">
                            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            
                            <?php if ($booking['invoice_id']): ?>
                            <a href="admin_view_invoice.php?invoice_id=<?php echo h($booking['invoice_id']); ?>"
                               class="btn btn-info btn-sm ml-1">
                                <i class="fas fa-file-invoice"></i> Invoice
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] == 'paid' && !$is_checked_in && !$is_checkout_completed && ($timeline_status == 'upcoming' || $timeline_status == 'active')): ?>
                            <?php if ($booking['invoice_number']): ?>
                            <a href="admin_checkin.php?invoice=<?php echo urlencode($booking['invoice_number']); ?>"
                               class="btn btn-primary btn-sm ml-1">
                                <i class="fas fa-sign-in-alt"></i> Check-in
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($can_checkout): ?>
                            <a href="admin_checkout.php?booking_id=<?php echo h($booking_id); ?>"
                               class="btn btn-<?php echo $is_overdue ? 'danger' : 'success'; ?> btn-sm ml-1">
                                <i class="fas fa-sign-out-alt"></i> Checkout <?php echo $is_overdue ? '(Overdue)' : 'Now'; ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================================
                 CLEAN STATUS ALERT - Inside the header card
                 ============================================================ -->
            <div class="card-body" style="padding-top:0;padding-bottom:0;">
                <?php if ($is_checkout_completed): ?>
                <div class="alert alert-secondary" style="margin-bottom:15px;margin-top:15px;border:1px solid #555;">
                    <i class="fas fa-check-circle"></i>
                    <strong>Guest has checked out.</strong>
                    <?php if ($actual_checkout_display): ?>
                    Checked out on <?php echo h($actual_checkout_display); ?>.
                    <?php endif; ?>
                </div>
                <?php elseif ($is_overdue): ?>
                <div class="alert alert-danger" style="margin-bottom:15px;margin-top:15px;border:2px solid #ff0000;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>OVERDUE CHECKOUT!</strong>
                    Guest checked in but has not checked out. Scheduled checkout was <strong><?php echo date('F d, Y', strtotime($booking['checkout_date'])); ?></strong>.
                    <a href="admin_checkout.php?booking_id=<?php echo h($booking_id); ?>" class="btn btn-danger btn-sm ml-2">
                        <i class="fas fa-sign-out-alt"></i> Checkout Now
                    </a>
                </div>
                <?php elseif ($is_checked_in && $is_checkout_today): ?>
                <div class="alert alert-warning" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-clock"></i>
                    <strong>Checkout scheduled for today.</strong>
                    <a href="admin_checkout.php?booking_id=<?php echo h($booking_id); ?>" class="btn btn-warning btn-sm ml-2">
                        <i class="fas fa-sign-out-alt"></i> Checkout Now
                    </a>
                </div>
                <?php elseif ($is_checked_in && $timeline_status == 'active'): ?>
                <div class="alert alert-success" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-check-circle"></i>
                    <strong>Guest is currently checked in.</strong>
                    Checkout on <strong><?php echo date('F d, Y', strtotime($booking['checkout_date'])); ?></strong>.
                    <?php if (!empty($booking['actual_checkin_datetime'])): ?>
                    <br><small>Checked in: <?php echo date('F d, Y h:i A', strtotime($booking['actual_checkin_datetime'])); ?></small>
                    <?php endif; ?>
                </div>
                <?php elseif ($timeline_status == 'active' && !$is_checked_in): ?>
                <div class="alert alert-info" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-calendar-check"></i>
                    Stay in progress but guest has not checked in yet.
                    <?php if ($is_checkin_today): ?>
                    <span class="badge badge-info ml-2">Expected Today</span>
                    <?php endif; ?>
                </div>
                <?php elseif ($timeline_status == 'upcoming'): ?>
                <div class="alert alert-info" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Upcoming stay.</strong>
                    Check-in on <strong><?php echo date('F d, Y', strtotime($booking['checkin_date'])); ?></strong>.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================
             MAIN CONTENT - Two columns
             ============================================================ -->
        <div class="row mt-3">
            <!-- LEFT: Stay Timeline + Guest + Room -->
            <div class="col-md-8">
                <!-- Stay Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-timeline"></i> Stay Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <!-- CHECK-IN -->
                            <div class="col-md-4">
                                <div class="bg-terminal p-3 rounded border border-<?php echo $timeline_status == 'upcoming' ? 'warning' : 'success'; ?>">
                                    <div class="text-light small">CHECK-IN</div>
                                    <div class="text-large font-weight-bold"><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></div>
                                    <div class="text-muted small">
                                        <?php
                                        if ($is_checkout_completed) {
                                            echo "Completed ✓";
                                        } elseif ($is_checked_in && !empty($booking['actual_checkin_datetime'])) {
                                            echo "Checked in: " . date('h:i A', strtotime($booking['actual_checkin_datetime']));
                                        } elseif ($timeline_status == 'upcoming') {
                                            echo "In $days_to_checkin day(s)";
                                        } elseif ($is_checkin_today) {
                                            echo "DUE TODAY";
                                        } else {
                                            echo "ACTIVE";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DURATION -->
                            <div class="col-md-4">
                                <div class="bg-terminal p-3 rounded border border-info">
                                    <div class="text-light small">DURATION</div>
                                    <div class="text-info large font-weight-bold"><?php echo h($booking['nights']); ?> Night(s)</div>
                                </div>
                            </div>
                            
                            <!-- CHECK-OUT -->
                            <div class="col-md-4">
                                <div class="bg-terminal p-3 rounded border border-<?php 
                                    if ($is_checkout_completed) {
                                        echo 'secondary';
                                    } elseif ($is_overdue) {
                                        echo 'danger';
                                    } elseif ($is_checkout_today) {
                                        echo 'warning';
                                    } else {
                                        echo 'danger';
                                    }
                                ?>">
                                    <div class="text-light small">CHECK-OUT</div>
                                    <div class="text-large font-weight-bold <?php 
                                        if ($is_checkout_completed) {
                                            echo 'text-secondary';
                                        } elseif ($is_overdue) {
                                            echo 'text-danger';
                                        }
                                    ?>">
                                        <?php 
                                        if ($is_checkout_completed && !empty($booking['checkout_timestamp'])) {
                                            echo date('M d, Y', strtotime($booking['checkout_timestamp']));
                                        } else {
                                            echo date('M d, Y', strtotime($booking['checkout_date']));
                                        }
                                        ?>
                                        <?php if ($is_checkout_completed): ?>
                                        <span style="font-size:12px;color:#28a745;">✓</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php
                                        if ($is_checkout_completed) {
                                            if (!empty($booking['checkout_timestamp'])) {
                                                echo "Checked out: " . date('h:i A', strtotime($booking['checkout_timestamp']));
                                            } else {
                                                echo "CHECKED OUT ✓";
                                            }
                                        } elseif ($is_overdue) {
                                            echo '<span style="color:#ff0000;font-weight:bold;">OVERDUE</span>';
                                        } elseif ($is_checkout_today && $is_currently_staying) {
                                            echo "DUE TODAY";
                                        } elseif ($is_currently_staying) {
                                            echo "Active Stay";
                                        } elseif (!$is_checked_in && $timeline_status == 'active') {
                                            echo "Awaiting Check-in";
                                        } elseif ($timeline_status == 'upcoming') {
                                            echo "In $days_to_checkout day(s)";
                                        } else {
                                            echo "Scheduled";
                                        }
                                        ?>
                                    </div>
                                    <?php if ($is_checkout_completed && !empty($booking['checkout_timestamp'])): ?>
                                    <div class="text-muted small mt-1" style="font-size:10px;color:#666;">
                                        <span style="text-decoration:line-through;color:#666;">
                                            Scheduled: <?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guest Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-user"></i> Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Name:</strong> <span class="text-green"><?php echo h($booking['customer_name']); ?></span></p>
                                <p class="mb-2"><strong>Email:</strong> <?php echo h($booking['customer_email']); ?></p>
                                <p class="mb-2"><strong>Phone:</strong> <?php echo h($booking['customer_phone']); ?></p>
                                <p class="mb-2"><strong>Gender:</strong> <?php echo ucfirst(h($booking['gender'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Has Car:</strong> <?php echo $booking['has_car'] ? 'Yes' : 'No'; ?></p>
                                <?php if ($booking['id_type'] && $booking['id_number']): ?>
                                <p class="mb-2"><strong>ID:</strong>
                                    <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', h($booking['id_type']))); ?></span>
                                    <?php echo h($booking['id_number']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($booking['address']): ?>
                                <p class="mb-2"><strong>Address:</strong><br><?php echo nl2br(h($booking['address'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($booking['customer_notes']): ?>
                        <div class="row mt-2">
                            <div class="col-12">
                                <p class="mb-1"><strong>Notes:</strong></p>
                                <div class="bg-dark p-2 rounded border border-terminal">
                                    <?php echo nl2br(h($booking['customer_notes'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Room Information -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-bed"></i> Room Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="bg-terminal p-3 rounded border border-primary">
                                    <div class="text-light small">ROOM</div>
                                    <div class="text-primary" style="font-size:24px;font-weight:bold;">
                                        <?php echo h($booking['room_number']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <span class="badge badge-info"><?php echo h($booking['capacity']); ?> person(s)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="bg-terminal p-3 rounded border border-success">
                                    <div class="text-light small">BUILDING</div>
                                    <div class="text-success" style="font-size:18px;font-weight:bold;">
                                        <?php echo h($booking['building_name']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <span class="badge badge-<?php 
                                            echo $booking['building_type'] == 'vip' ? 'warning' : 
                                                 ($booking['building_type'] == 'premium' ? 'success' : 'info'); 
                                        ?>">
                                            <?php echo strtoupper(h($booking['building_type'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="bg-terminal p-3 rounded border border-warning">
                                    <div class="text-light small">RATE</div>
                                    <div class="text-warning" style="font-size:18px;font-weight:bold;">
                                        ₦<?php echo number_format($booking['price_per_night'], 2); ?>
                                    </div>
                                    <div class="text-muted small">Per night</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Actions + Quick Info -->
            <div class="col-md-4">
                <!-- Financial Snapshot -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-coins"></i> Financial Snapshot</h5>
                    </div>
                    <div class="card-body bg-terminal">
                        <?php if ($booking['invoice_id']): ?>
                        <div class="mb-2">
                            <div class="text-light small">Invoice</div>
                            <div class="text-right">
                                <a href="admin_view_invoice.php?invoice_id=<?php echo h($booking['invoice_id']); ?>" 
                                   class="text-info">
                                    <?php echo h($booking['invoice_number']); ?>
                                </a>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="text-light small">Total Amount</div>
                            <div class="text-right text-green" style="font-size:18px;font-weight:bold;">
                                ₦<?php echo number_format($booking['amount'], 2); ?>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="text-light small">Status</div>
                            <div class="text-right">
                                <span class="badge badge-<?php echo $booking['invoice_paid'] ? 'success' : 'danger'; ?>">
                                    <?php echo $booking['invoice_paid'] ? 'PAID' : 'UNPAID'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <a href="admin_view_invoice.php?invoice_id=<?php echo h($booking['invoice_id']); ?>" 
                               class="btn btn-info btn-sm btn-block">
                                <i class="fas fa-file-invoice"></i> View Full Invoice
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-warning text-center">
                            <i class="fas fa-exclamation-circle"></i> No invoice generated
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Checkout Details (if checked out) -->
                <?php if ($is_checkout_completed && !empty($booking['checkout_status'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-sign-out-alt"></i> Checkout Details</h5>
                    </div>
                    <div class="card-body bg-terminal">
                        <div class="mb-2">
                            <div class="text-light small">Checked out</div>
                            <div class="text-right text-success">
                                <?php echo !empty($booking['checkout_timestamp']) ? date('M d, Y h:i A', strtotime($booking['checkout_timestamp'])) : '—'; ?>
                            </div>
                        </div>
                        <?php if ($booking['room_condition']): ?>
                        <div class="mb-2">
                            <div class="text-light small">Room Condition</div>
                            <div class="text-right">
                                <span class="badge badge-<?php echo $booking['room_condition'] == 'good' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['room_condition'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($booking['checked_out_by_name']): ?>
                        <div class="mb-2">
                            <div class="text-light small">Checked out by</div>
                            <div class="text-right"><?php echo h($booking['checked_out_by_name']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($booking['checkout_notes']): ?>
                        <div class="mb-2">
                            <div class="text-light small">Notes</div>
                            <div class="text-right small"><?php echo nl2br(h($booking['checkout_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-bolt"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if (!$booking['invoice_id'] && $booking['status'] != 'cancelled'): ?>
                            <a href="admin_create_invoice.php?booking_id=<?php echo h($booking_id); ?>"
                               class="list-group-item list-group-item-action" style="background:#1a1a1a;color:#00ff99;border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-plus text-warning"></i> Create Invoice
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['invoice_id'] && !$booking['invoice_paid'] && $booking['status'] != 'cancelled'): ?>
                            <a href="admin_mark_paid.php?invoice_id=<?php echo h($booking['invoice_id']); ?>"
                               class="list-group-item list-group-item-action" style="background:#1a1a1a;color:#00ff99;border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-check text-success"></i> Mark Invoice Paid
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] == 'paid' && !$is_checked_in && !$is_checkout_completed && ($timeline_status == 'upcoming' || $timeline_status == 'active')): ?>
                            <?php if ($booking['invoice_number']): ?>
                            <a href="admin_checkin.php?invoice=<?php echo urlencode($booking['invoice_number']); ?>"
                               class="list-group-item list-group-item-action" style="background:#1a1a1a;color:#00ff99;border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-sign-in-alt text-primary"></i> Check In Guest
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($can_checkout): ?>
                            <a href="admin_checkout.php?booking_id=<?php echo h($booking_id); ?>"
                               class="list-group-item list-group-item-action <?php echo $is_overdue ? 'bg-danger text-white' : 'bg-success text-white'; ?>" style="border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-sign-out-alt"></i> <?php echo $is_overdue ? 'Checkout (Overdue)' : 'Checkout Now'; ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] == 'paid' && $is_currently_staying && $timeline_status == 'active' && $booking['checkout_date'] >= date('Y-m-d') && !$is_checkout_completed): ?>
                            <a href="admin_extend_stay.php?booking=<?php echo h($booking_id); ?>"
                               class="list-group-item list-group-item-action" style="background:#1a1a1a;color:#00ff99;border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-clock text-primary"></i> Extend Stay
                            </a>
                            <?php endif; ?>
                            
                            <a href="admin_booking_management.php" 
                               class="list-group-item list-group-item-action" style="background:#1a1a1a;color:#00ff99;border:1px solid #444;margin-bottom:5px;">
                                <i class="fas fa-list"></i> All Bookings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes blinker {
    0% { opacity: 1; }
    50% { opacity: 0.3; }
    100% { opacity: 1; }
}
</style>

<?php include __DIR__.'/footer.php'; ?>