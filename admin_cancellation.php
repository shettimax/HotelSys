<?php
// admin_cancellation.php - Cancel booking & optionally issue refund via checkouts table
// WITH SweetAlert2 CONFIRMATION - FIXED: Single trigger

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$booking_id) {
    header('Location: admin_booking_management.php');
    exit;
}

$sql = "
    SELECT b.*, 
           i.id AS invoice_id, 
           i.paid AS invoice_paid, 
           i.amount AS invoice_amount,
           CONCAT(c.firstname,' ',c.lastname) AS customer_name,
           r.room_number, 
           bu.name AS building_name,
           c.email as customer_email,
           c.phone as customer_phone
    FROM bookings b
    LEFT JOIN invoices i ON b.id = i.booking_id
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bu ON r.building_id = bu.id
    WHERE b.id = $booking_id
      AND b.status != 'cancelled'
      AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
";
$result = mysqli_query($conn, $sql);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header('Location: admin_booking_management.php?alert=Invalid+booking');
    exit;
}

if ($booking['status'] == 'cancelled') {
    header('Location: admin_booking_management.php?alert=Booking+already+cancelled');
    exit;
}

$error = '';
$success = false;

$is_paid = (int)$booking['invoice_paid'] === 1;
$max_refund = $is_paid ? $booking['invoice_amount'] : 0;

$is_checked_in = ($booking['actual_checkin_datetime'] !== null && $booking['keycard_issued'] == 1);

$default_refund = 0;
$cancellation_fee = 0;
$cancellation_reason = '';

if ($is_paid) {
    $checkin_date = new DateTime($booking['checkin_date']);
    $today = new DateTime('today');
    $days_until_checkin = $today->diff($checkin_date)->days;
    
    if ($today > $checkin_date) {
        $days_until_checkin = -$days_until_checkin;
    }
    
    if ($is_checked_in) {
        $cancellation_fee = $booking['invoice_amount'] * 0.5;
        $default_refund = $booking['invoice_amount'] - $cancellation_fee;
        $cancellation_reason = "50% penalty (already checked in)";
    } elseif ($days_until_checkin <= 0) {
        $cancellation_fee = $booking['invoice_amount'] * 0.75;
        $default_refund = $booking['invoice_amount'] * 0.25;
        $cancellation_reason = "75% penalty (check-in today or past)";
    } elseif ($days_until_checkin <= 1) {
        $cancellation_fee = $booking['invoice_amount'] * 0.75;
        $default_refund = $booking['invoice_amount'] * 0.25;
        $cancellation_reason = "75% penalty (<24h notice)";
    } elseif ($days_until_checkin <= 3) {
        $cancellation_fee = $booking['invoice_amount'] * 0.5;
        $default_refund = $booking['invoice_amount'] * 0.5;
        $cancellation_reason = "50% penalty (1-3 days notice)";
    } else {
        $cancellation_fee = 0;
        $default_refund = $booking['invoice_amount'];
        $cancellation_reason = "Full refund (>3 days notice)";
    }
} else {
    $default_refund = 0;
    $cancellation_reason = "No payment made";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    if (empty($reason)) {
        $error = "Reason for cancellation is required.";
    } elseif (strlen($reason) < 5) {
        $error = "Reason must be at least 5 characters.";
    } elseif (strlen($reason) > 500) {
        $error = "Reason cannot exceed 500 characters.";
    }
    
    if ($is_paid) {
        $refund_amount = isset($_POST['refund_amount']) ? (float)$_POST['refund_amount'] : $default_refund;
        if ($refund_amount < 0) {
            $error = "Refund amount cannot be negative.";
        } elseif ($refund_amount > $booking['invoice_amount']) {
            $error = "Invalid refund amount. Must be between 0 and " . number_format($booking['invoice_amount'], 2);
        }
    } else {
        $refund_amount = 0.00;
    }
    
    if (strlen($notes) > 1000) {
        $error = "Notes cannot exceed 1000 characters.";
    }
    
    if (!$is_paid && isset($_POST['refund_amount']) && (float)$_POST['refund_amount'] > 0) {
        $error = "Cannot issue refund for unpaid booking.";
    }

    if (!$error) {
        mysqli_autocommit($conn, false);
        
        $admin_id = get_current_admin_id();
        if (!$admin_id) {
            $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
        }

        $update_booking = mysqli_query($conn, "UPDATE bookings SET status = 'cancelled', checkout_status = 'cancelled' WHERE id = $booking_id");
        
        if (!$update_booking) {
            $error = "Failed to update booking status: " . mysqli_error($conn);
            mysqli_rollback($conn);
        } else {
            $insert = mysqli_query($conn, "
                INSERT INTO checkouts 
                (booking_id, invoice_id, customer_id, room_id, 
                 actual_checkout_date, checkout_time, checked_out_by,
                 refund_amount, refund_description, notes, status)
                VALUES 
                (
                $booking_id,
                " . ($booking['invoice_id'] ?: "NULL") . ",
                {$booking['customer_id']}, 
                {$booking['room_id']},
                CURDATE(), 
                CURTIME(), 
                $admin_id,
                $refund_amount,
                " . ($reason ? "'" . mysqli_real_escape_string($conn, $reason) . "'" : "NULL") . ",
                " . ($notes ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL") . ",
                'cancelled'
                )
            ");

            if ($insert && mysqli_affected_rows($conn) > 0) {
                $checkout_id = mysqli_insert_id($conn);
                
                mysqli_query($conn, "UPDATE bookings SET checkout_id = $checkout_id WHERE id = $booking_id");
                
                $log_success = log_admin_activity(
                    'Booking Cancelled',
                    "Booking #$booking_id cancelled for " . $booking['customer_name'] . 
                    " (Room " . $booking['room_number'] . ")" . 
                    ($refund_amount > 0 ? " with refund of ₦" . number_format($refund_amount, 2) : "") . 
                    ". Reason: " . substr($reason, 0, 100),
                    $admin_id
                );
                
                if (!$log_success) {
                    $error = "Failed to log admin activity";
                    mysqli_rollback($conn);
                } else {
                    mysqli_commit($conn);
                    $success = true;
                }
            } else {
                $error = "Failed to record cancellation. ";
                if (mysqli_error($conn)) {
                    $error .= "Error: " . mysqli_error($conn);
                } else {
                    $error .= "No rows were inserted into checkouts table.";
                }
                mysqli_rollback($conn);
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4><i class="fa fa-times-circle"></i> Cancel Booking</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <h4><i class="fa fa-check-circle"></i> Booking Cancelled Successfully!</h4>
                                <div class="table-responsive mt-3">
                                    <table class="table table-dark table-bordered">
                                        <tbody>
                                            <tr>
                                                <td width="30%"><strong>Booking ID:</strong></td>
                                                <td width="70%"><span class="text-success">#<?php echo h($booking_id); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Guest:</strong></td>
                                                <td><span class="text-success"><?php echo h($booking['customer_name']); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Room:</strong></td>
                                                <td><span class="text-success"><?php echo h($booking['room_number']); ?></span></td>
                                            </tr>
                                            <?php if ($is_paid && $refund_amount > 0): ?>
                                                <tr>
                                                    <td><strong>Refund Issued:</strong></td>
                                                    <td><span class="text-success">₦<?php echo number_format($refund_amount, 2); ?></span></td>
                                                </tr>
                                            <?php elseif ($is_paid && $refund_amount == 0): ?>
                                                <tr>
                                                    <td><strong>Refund:</strong></td>
                                                    <td><span class="text-danger">₦0.00 (penalty applied)</span></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td><strong>Refund:</strong></td>
                                                    <td><span class="text-muted">Not applicable (unpaid booking)</span></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning mt-3">
                                    <i class="fa fa-info-circle"></i> This booking is now marked as <strong>CANCELLED</strong> in the system.
                                </div>
                                <a href="admin_booking_management.php" class="btn btn-primary mt-3">
                                    <i class="fa fa-home"></i> Back to Bookings
                                </a>
                            </div>

                        <?php else: ?>

                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> Booking Details</h5>
                                <div class="table-responsive mt-3">
                                    <table class="table table-dark table-bordered checkout-details-table">
                                        <tbody>
                                            <tr>
                                                <td width="30%"><strong>Guest Name:</strong></td>
                                                <td width="70%"><?php echo h($booking['customer_name']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Room:</strong></td>
                                                <td><?php echo h($booking['room_number'] . ' (' . $booking['building_name'] . ')'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check-in:</strong></td>
                                                <td><?php echo date('M j, Y', strtotime($booking['checkin_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check-out:</strong></td>
                                                <td><?php echo date('M j, Y', strtotime($booking['checkout_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Amount:</strong></td>
                                                <td>₦<?php echo number_format($booking['invoice_amount'], 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Payment Status:</strong></td>
                                                <td>
                                                    <?php echo $is_paid ? '<span class="badge badge-success">PAID</span>' : '<span class="badge badge-warning">UNPAID</span>'; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check-in Status:</strong></td>
                                                <td>
                                                    <?php echo $is_checked_in ? '<span class="badge badge-success">CHECKED IN</span>' : '<span class="badge badge-info">NOT CHECKED IN</span>'; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if ($is_paid): ?>
                                    <div class="alert <?php echo $default_refund > 0 ? 'alert-success' : 'alert-warning'; ?> mt-3">
                                        <h6><i class="fa fa-exclamation-circle"></i> Cancellation Policy Applied</h6>
                                        <p class="mb-1"><strong>Policy:</strong> <?php echo h($cancellation_reason); ?></p>
                                        <p class="mb-0"><strong>Suggested Refund:</strong> ₦<?php echo number_format($default_refund, 2); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        <h6><i class="fa fa-warning"></i> No Refund</h6>
                                        <p class="mb-0">Booking was not paid.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo h($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="cancelForm">
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5><i class="fa fa-cogs"></i> Cancellation Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label><i class="fa fa-money-bill-wave"></i> Refund Amount (₦)</label>
                                            <input 
                                                type="number" 
                                                name="refund_amount" 
                                                class="form-control"
                                                value="<?php echo number_format($default_refund, 2); ?>"
                                                step="0.01"
                                                min="0"
                                                max="<?php echo h($max_refund); ?>"
                                                <?php echo $is_paid ? "" : "readonly disabled"; ?>
                                            >
                                            <?php if (!$is_paid): ?>
                                                <small class="text-warning">Booking was not paid. Refund not applicable.</small>
                                            <?php else: ?>
                                                <small class="text-muted">Adjust refund amount as needed. Max: ₦<?php echo number_format($max_refund, 2); ?></small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label><i class="fa fa-comment"></i> Reason for Cancellation</label>
                                            <textarea name="reason" class="form-control" rows="3" required placeholder="Enter reason for cancellation..."></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label><i class="fa fa-sticky-note"></i> Notes (Optional)</label>
                                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                                        </div>

                                        <div class="alert alert-danger">
                                            <h6><i class="fa fa-warning"></i> Warning</h6>
                                            <p class="mb-0">This action cannot be undone. All associated data will be marked as cancelled.</p>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <a href="admin_booking_management.php" class="btn btn-secondary btn-lg btn-block">
                                                    <i class="fa fa-times"></i> CANCEL
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="button" class="btn btn-danger btn-lg btn-block" 
                                                    onclick="showConfirmModalForm('Are you sure you want to cancel booking <strong>#<?php echo h($booking_id); ?></strong> for <?php echo h($booking['customer_name']); ?>?', 'cancelForm', 'This action cannot be undone. The room will become available.', 'btn-danger', 'fa-times-circle', '#ff0000', 'Cancel Booking');">
                                                    <i class="fa fa-check"></i> CONFIRM CANCELLATION
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa fa-question-circle"></i> Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6><i class="fa fa-clock"></i> Cancellation Policy:</h6>
                            <ul class="mb-0">
                                <li>More than 3 days notice: Full refund</li>
                                <li>1-3 days notice: 50% refund</li>
                                <li>Less than 24 hours: 25% refund</li>
                                <li>Already checked in: 50% penalty</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-secondary">
                            <h6><i class="fa fa-lightbulb"></i> Quick Tips:</h6>
                            <ul class="mb-0">
                                <li>Always check if guest is checked in</li>
                                <li>Verify payment status before refund</li>
                                <li>Document reason for cancellation</li>
                                <li>Check for special requests or notes</li>
                            </ul>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fa fa-exclamation-triangle"></i> Important Notes</h5>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Cancellation cannot be reversed</li>
                                    <li>Room becomes available immediately</li>
                                    <li>Refunds processed to original payment</li>
                                    <li>Email notification sent to guest</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>