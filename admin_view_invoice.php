<?php
// admin_view_invoice.php - FOCUSED ON FINANCE
// Shows invoice details, payment summary, and payment history only
// Clean status alert in header card
// FIXED: Keycard print functionality

require_once __DIR__.'/database.php';
require_once __DIR__.'/session.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

if (empty($_GET['invoice_id'])) {
    header('Location: admin_invoice_management.php');
    exit;
}

$invoice_id = (int)$_GET['invoice_id'];

$sql = "
    SELECT
        i.*,
        i.paid as invoice_paid,
        i.paid_at,
        bk.keycard_issued,
        bk.actual_checkin_datetime,
        bk.checkout_status,
        CONCAT(c.firstname, ' ', c.lastname) as customer_name,
        c.firstname,
        c.lastname,
        c.email as customer_email,
        c.phone as customer_phone,
        r.room_number,
        b.name as building_name,
        bk.id as booking_id,
        bk.checkin_date,
        bk.checkout_date,
        bk.amount as booking_amount,
        bk.status as booking_status,
        bk.nights,
        bk.created_at as booking_created,
        ch.checkout_date as checkout_timestamp,
        ch.status as checkout_status,
        (SELECT COUNT(*) FROM payments p WHERE p.invoice_id = i.id AND p.status = 'completed') as completed_payments,
        (SELECT COUNT(*) FROM payments p WHERE p.invoice_id = i.id) as total_payments,
        (SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id AND p.status = 'completed') as total_paid_amount
    FROM invoices i
    JOIN bookings bk ON i.booking_id = bk.id
    JOIN customers c ON bk.customer_id = c.id
    JOIN rooms r ON bk.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    LEFT JOIN checkouts ch ON bk.id = ch.booking_id
    WHERE i.id = $invoice_id
    LIMIT 1
";

$result = mysqli_query($conn, $sql);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    header('Location: admin_invoice_management.php?alert=' . urlencode('Invoice not found!') . '&type=error');
    exit;
}

// Payment history
$payments_sql = "SELECT * FROM payments WHERE invoice_id = $invoice_id ORDER BY created_at DESC";
$payments_result = mysqli_query($conn, $payments_sql);
$payments = mysqli_fetch_all($payments_result, MYSQLI_ASSOC);

// Balance
$balance = $invoice['amount'] - ($invoice['total_paid_amount'] ?: 0);

// Check-in/out status
$is_checked_in = (!empty($invoice['keycard_issued']) && $invoice['keycard_issued'] == 1);
$is_checkout_completed = (
    $invoice['checkout_status'] === 'completed' || 
    $invoice['checkout_status'] === 'checked_out' ||
    (isset($invoice['checkout_status']) && $invoice['checkout_status'] == 'checked_out')
);

// Format dates
$checkinDate = date('M d, Y', strtotime($invoice['checkin_date']));
$checkoutDate = date('M d, Y', strtotime($invoice['checkout_date']));
$issuedDate = date('M d, Y h:i A', strtotime($invoice['issued_at']));
$paidDate = $invoice['paid_at'] ? date('M d, Y h:i:s A', strtotime($invoice['paid_at'])) : null;

$actual_checkout_display = null;
if ($is_checkout_completed && !empty($invoice['checkout_timestamp'])) {
    $actual_checkout_display = date('M d, Y h:i A', strtotime($invoice['checkout_timestamp']));
}

$app_config = include __DIR__ . '/config.php';
$hotel = isset($app_config['hotel']) ? $app_config['hotel'] : array();

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <!-- ============================================================
             HEADER CARD - Invoice # + Status Badges + Clean Status Alert
             ============================================================ -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="text-terminal"><i class="fas fa-file-invoice"></i> INVOICE #<?php echo h($invoice['invoice_number']); ?></h4>
                        <p class="text-muted mb-0">
                            Issued: <?php echo h($issuedDate); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="mb-2">
                            <?php if ($invoice['booking_status'] == 'cancelled'): ?>
                            <span class="badge badge-danger" style="font-size:1.3em;padding:10px 20px;">
                                <i class="fas fa-ban"></i> CANCELLED
                            </span>
                            <?php else: ?>
                            <span class="badge badge-<?php echo $invoice['invoice_paid'] ? 'success' : 'danger'; ?>"
                                  style="font-size:1.3em;padding:10px 20px;">
                                <?php echo $invoice['invoice_paid'] ? '<i class="fas fa-check"></i> PAID' : '<i class="fas fa-clock"></i> PENDING'; ?>
                            </span>
                            <?php endif; ?>

                            <?php if ($is_checked_in && !$is_checkout_completed): ?>
                            <span class="badge badge-success ml-2" style="font-size:1.1em;padding:10px 15px;">
                                <i class="fas fa-key"></i> CHECKED IN
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($is_checkout_completed): ?>
                            <span class="badge badge-secondary ml-2" style="font-size:1.1em;padding:10px 15px;">
                                <i class="fas fa-sign-out-alt"></i> CHECKED OUT
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-2">
                            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>

                            <a href="admin_view_booking.php?booking_id=<?php echo h($invoice['booking_id']); ?>"
                               class="btn btn-info btn-sm ml-1">
                                <i class="fas fa-eye"></i> View Booking
                            </a>

                            <?php if (!$invoice['invoice_paid'] && $invoice['booking_status'] != 'cancelled'): ?>
                            <a href="admin_mark_paid.php?invoice_id=<?php echo h($invoice_id); ?>"
                               class="btn btn-success btn-sm ml-1">
                                <i class="fas fa-check-circle"></i> Mark Paid
                            </a>
                            <?php endif; ?>

                            <?php if ($invoice['booking_status'] != 'cancelled'): ?>
                            <button class="btn btn-info btn-sm ml-1 btn-print-receipt">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <?php endif; ?>

                            <!-- FIXED: Keycard print button - Only show if checked in and not checked out -->
                            <?php if ($is_checked_in && $invoice['booking_status'] == 'paid' && !$is_checkout_completed): ?>
                            <button class="btn btn-warning btn-sm ml-1 btn-print-keycard">
                                <i class="fas fa-credit-card"></i> Print Keycard
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================================
                 CLEAN STATUS ALERT - Inside the header card
                 ============================================================ -->
            <div class="card-body" style="padding-top:0;padding-bottom:0;">
                <?php if ($invoice['booking_status'] == 'cancelled'): ?>
                <div class="alert alert-danger" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-ban"></i> <strong>This booking has been cancelled.</strong> No further actions allowed.
                </div>
                <?php elseif ($is_checkout_completed): ?>
                <div class="alert alert-secondary" style="margin-bottom:15px;margin-top:15px;border:1px solid #555;">
                    <i class="fas fa-check-circle"></i>
                    <strong>Guest has checked out.</strong>
                    <?php if ($actual_checkout_display): ?>
                    Checked out on <?php echo h($actual_checkout_display); ?>.
                    <?php endif; ?>
                </div>
                <?php elseif ($is_checked_in): ?>
                <div class="alert alert-success" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-key"></i>
                    <strong>Guest is currently checked in.</strong>
                    <?php if (!empty($invoice['actual_checkin_datetime'])): ?>
                    Checked in: <?php echo date('M d, Y h:i A', strtotime($invoice['actual_checkin_datetime'])); ?>
                    <?php endif; ?>
                </div>
                <?php elseif ($invoice['booking_status'] == 'paid' || $invoice['booking_status'] == 'confirmed'): ?>
                <div class="alert alert-info" style="margin-bottom:15px;margin-top:15px;">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Upcoming booking.</strong>
                    Check-in on <strong><?php echo h($checkinDate); ?></strong>.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================
             MAIN CONTENT - Two columns
             ============================================================ -->
        <div class="row mt-3">
            <!-- LEFT: Booking Reference -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-calendar-alt"></i> Booking Reference</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-dark table-bordered">
                            <tr>
                                <td width="40%"><strong>Guest</strong></td>
                                <td width="60%"><?php echo h($invoice['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Room</strong></td>
                                <td><?php echo h($invoice['room_number'] . ' (' . $invoice['building_name'] . ')'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Stay</strong></td>
                                <td>
                                    <?php echo h($checkinDate); ?> → <?php echo h($checkoutDate); ?>
                                    <?php if ($is_checkout_completed && $actual_checkout_display): ?>
                                    <br><small class="text-muted">Actual checkout: <?php echo h($actual_checkout_display); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Nights</strong></td>
                                <td><?php echo h($invoice['nights']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Booking Status</strong></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $invoice['booking_status'] == 'paid' ? 'success' : 
                                        ($invoice['booking_status'] == 'confirmed' ? 'warning' : 
                                        ($invoice['booking_status'] == 'cancelled' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo strtoupper(h($invoice['booking_status'])); ?>
                                    </span>
                                    <?php if ($is_checkout_completed): ?>
                                    <span class="badge badge-secondary">CHECKED OUT</span>
                                    <?php elseif ($is_checked_in): ?>
                                    <span class="badge badge-success">CHECKED IN</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        <div class="text-center mt-2">
                            <a href="admin_view_booking.php?booking_id=<?php echo h($invoice['booking_id']); ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Full Booking Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Financial Summary -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-terminal"><i class="fas fa-money-bill-wave"></i> Payment Summary</h5>
                    </div>
                    <div class="card-body bg-terminal">
                        <div class="mb-2">
                            <div class="text-light" style="font-size:14px;">Room Rate</div>
                            <div class="text-right">
                                ₦<?php echo number_format($invoice['price_per_night'], 2); ?> × <?php echo h($invoice['nights']); ?> nights
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="text-light" style="font-size:14px;">Total Amount</div>
                            <div class="text-right text-green" style="font-size:20px;font-weight:bold;">
                                ₦<?php echo number_format($invoice['amount'], 2); ?>
                            </div>
                        </div>
                        
                        <hr class="border-terminal">
                        
                        <div class="mb-2">
                            <div class="text-light" style="font-size:14px;">Amount Paid</div>
                            <div class="text-right <?php echo $invoice['total_paid_amount'] >= $invoice['amount'] ? 'text-success' : 'text-warning'; ?>" style="font-size:16px;">
                                ₦<?php echo number_format($invoice['total_paid_amount'] ?: 0, 2); ?>
                                <?php if ($invoice['total_paid_amount'] > 0): ?>
                                <br><small class="text-muted">(<?php echo h($invoice['completed_payments']); ?> payment<?php echo $invoice['completed_payments'] != 1 ? 's' : ''; ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-2" style="border-top:2px solid #00ff99;padding-top:10px;">
                            <div class="text-light" style="font-size:14px;"><strong>Balance Due</strong></div>
                            <div class="text-right <?php echo $balance <= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size:20px;font-weight:bold;">
                                ₦<?php echo number_format($balance, 2); ?>
                                <?php if ($balance <= 0): ?>
                                <span class="badge badge-success ml-2">FULLY PAID</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($balance > 0 && $invoice['booking_status'] != 'cancelled'): ?>
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle"></i>
                            <strong>Payment Required:</strong> ₦<?php echo number_format($balance, 2); ?> outstanding.
                            <a href="admin_mark_paid.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-success btn-sm ml-2">
                                <i class="fas fa-check"></i> Mark Paid
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             PAYMENT HISTORY - Full width
             ============================================================ -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="text-terminal"><i class="fas fa-history"></i> Payment History</h5>
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dark table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($payment['created_at'])); ?></td>
                                <td><small><?php echo h($payment['txn_ref'] ?: 'N/A'); ?></small></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo strtoupper(h($payment['method'])); ?>
                                    </span>
                                </td>
                                <td class="text-green">₦<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $payment['status'] == 'completed' ? 'success' : ($payment['status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                        <?php echo strtoupper(h($payment['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4">
                    <p class="text-muted">No payment records found.</p>
                    <?php if (!$invoice['invoice_paid'] && $balance > 0 && $invoice['booking_status'] != 'cancelled'): ?>
                    <a href="admin_mark_paid.php?invoice_id=<?php echo h($invoice_id); ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-check-circle"></i> Mark as Paid
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================
             QUICK ACTIONS
             ============================================================ -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="text-terminal"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="admin_view_booking.php?booking_id=<?php echo h($invoice['booking_id']); ?>" 
                           class="btn btn-primary btn-block">
                            <i class="fas fa-eye"></i> View Booking
                        </a>
                    </div>
                    
                    <?php if (!$invoice['invoice_paid'] && $invoice['booking_status'] != 'cancelled'): ?>
                    <div class="col-md-3">
                        <a href="admin_mark_paid.php?invoice_id=<?php echo h($invoice_id); ?>" 
                           class="btn btn-success btn-block">
                            <i class="fas fa-check-circle"></i> Mark Paid
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <a href="admin_invoice_management.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-list"></i> All Invoices
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// NOTE: printReceipt()/printKeycard() are provided by the single shared
// implementation in js/print.js (loaded via footer.php). This page only
// needs to expose the data those functions read from window.* below —
// previously this file also defined its own local printReceipt()/
// printKeycard(), which silently shadowed print.js's versions and caused
// print buttons to fire two conflicting implementations per click.
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($invoice['booking_status'] != 'cancelled'): ?>
    window.invoicePrintData = {
        invoiceNumber: "<?php echo h($invoice['invoice_number']); ?>",
        guestName: "<?php echo h($invoice['customer_name']); ?>",
        roomNumber: "<?php echo h($invoice['room_number']); ?>",
        buildingName: "<?php echo h($invoice['building_name']); ?>",
        checkinDate: "<?php echo h($checkinDate); ?>",
        checkoutDate: "<?php echo h($checkoutDate); ?>",
        actualCheckout: "<?php echo $actual_checkout_display ? h($actual_checkout_display) : ''; ?>",
        nights: "<?php echo h($invoice['nights']); ?>",
        invoicePaid: <?php echo $invoice['invoice_paid'] ? 'true' : 'false'; ?>,
        totalAmount: "₦<?php echo number_format($invoice['amount'], 2); ?>",
        amountPaid: "₦<?php echo number_format($invoice['total_paid_amount'] ?: 0, 2); ?>",
        balanceDue: "₦<?php echo number_format($balance, 2); ?>",
        issuedDate: "<?php echo h($issuedDate); ?>",
        paidDate: "<?php echo $paidDate ? $paidDate : 'Not Paid'; ?>",
        paymentStatus: "<?php echo $invoice['invoice_paid'] ? 'PAID' : 'NOT PAID'; ?>",
        bookingStatus: "<?php echo h($invoice['booking_status']); ?>",
        hotelName: "<?php echo h(isset($hotel['name']) ? $hotel['name'] : 'Hotel Management System'); ?>",
        hotelTagline: "<?php echo h(isset($hotel['tagline']) ? $hotel['tagline'] : ''); ?>",
        hotelAddress: "<?php echo h(trim((isset($hotel['address_line1']) ? $hotel['address_line1'] : '') . ' ' . (isset($hotel['address_line2']) ? $hotel['address_line2'] : ''))); ?>"
    };
    <?php endif; ?>
    
    // FIXED: Keycard data only after check-in
    <?php if ($is_checked_in && $invoice['booking_status'] == 'paid' && !$is_checkout_completed): ?>
    window.keycardData = {
        invoiceNumber: "<?php echo h($invoice['invoice_number']); ?>",
        guestName: "<?php echo h($invoice['customer_name']); ?>",
        roomNumber: "<?php echo h($invoice['room_number']); ?>",
        buildingName: "<?php echo h($invoice['building_name']); ?>",
        buildingType: "<?php echo strtoupper(h($invoice['building_type'])); ?>",
        checkinDate: "<?php echo date('M j, Y', strtotime($invoice['checkin_date'])); ?>",
        checkinTime: "<?php echo date('h:i A', strtotime($invoice['actual_checkin_datetime'])); ?>",
        checkoutDate: "<?php echo date('M j, Y', strtotime($invoice['checkout_date'])); ?>",
        issuedDate: "<?php echo date('M j, Y H:i'); ?>",
        nights: "<?php echo h($invoice['nights']); ?>",
        hotelName: "<?php echo h(isset($hotel['name']) ? $hotel['name'] : 'Hotel Management System'); ?>",
        hotelTagline: "<?php echo h(isset($hotel['tagline']) ? $hotel['tagline'] : ''); ?>"
    };
    <?php endif; ?>
    
    // Initialize print buttons if the print.js library is loaded
    if (typeof initPrintButtons !== 'undefined') {
        setTimeout(initPrintButtons, 500);
    }
});
</script>

<?php include __DIR__.'/footer.php'; ?>