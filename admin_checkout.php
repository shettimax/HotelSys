<?php
// admin_checkout.php - Updated for PHP 5.6
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Check for success message ON THIS PAGE
$show_success = false;
$success_message = '';
$success_booking_id = 0;

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $show_success = true;
    $success_message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Checkout completed successfully!';
    $success_booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
}

// Initialize booking variable
$booking = null;
$balance = 0;
$additional_charges = 0;
$charges_reason = '';
$refund_amount = 0;
$refund_reason = '';
$error = '';
$show_confirmation = false; // Add flag for inline confirmation

// If NOT showing success, we need booking/invoice ID
if (!$show_success) {
    if (!$booking_id && !$invoice_id) {
        header('Location: admin_checkout_management.php');
        exit;
    }
    
    // Fetch booking data
    $sql = "SELECT b.*, i.id as invoice_id, i.invoice_number, i.amount as invoice_amount,
                   CONCAT(c.firstname,' ',c.lastname) as customer_name, c.phone,
                   r.room_number, r.price_per_night, bu.name as building_name,
                   (SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = i.id AND status='completed') as total_paid
            FROM bookings b
            LEFT JOIN invoices i ON b.id = i.booking_id
            JOIN customers c ON b.customer_id = c.id
            JOIN rooms r ON b.room_id = r.id
            JOIN buildings bu ON r.building_id = bu.id
            " . ($booking_id ? "WHERE b.id = $booking_id" : "WHERE i.id = $invoice_id") . "
            LIMIT 1";
    
    $result = mysqli_query($conn, $sql);
    $booking = mysqli_fetch_assoc($result);

    if (!$booking) {
        header('Location: admin_checkout_management.php?alert=' . urlencode('Booking not found') . '&type=error');
        exit;
    }

    // BONUS SAFETY CHECK: Verify guest has checked in
    if ($booking['keycard_issued'] != 1 || $booking['actual_checkin_datetime'] == NULL) {
        header('Location: admin_checkout_management.php?alert=' . urlencode('Guest has not checked in yet. Cannot process checkout.') . '&type=warning');
        exit;
    }

    // Check if already checked out
    if ($booking['checkout_status'] == 'checked_out') {
        header('Location: admin_checkout_management.php?alert=' . urlencode('Guest is already checked out') . '&type=warning');
        exit;
    }
    
    // Calculate balance
    $balance = $booking['invoice_amount'] - $booking['total_paid'];

    // Calculate late/early charges
    $scheduled_datetime = new DateTime($booking['checkout_date'] . ' 12:00:00');
    $now = new DateTime();
    
    // Grace period: 1 hour
    $grace_period = new DateInterval('PT1H');
    $scheduled_with_grace = clone $scheduled_datetime;
    $scheduled_with_grace->add($grace_period);
    
    if ($now > $scheduled_with_grace) {
        // Late checkout - charge full days
        $interval = $now->diff($scheduled_datetime);
        $days_late = $interval->days;
        if ($interval->invert == 1 && $interval->days == 0) {
            $days_late = 1;
        }
        $additional_charges = $days_late * $booking['price_per_night'];
        $charges_reason = "Late checkout: $days_late day(s) past scheduled date (12:00 PM)";
    } elseif ($now < $scheduled_datetime) {
        // Early checkout - possible refund
        $interval = $scheduled_datetime->diff($now);
        $days_early = $interval->days;
        
        if ($days_early >= 1) {
            $refund_amount = $days_early * $booking['price_per_night'] * 0.5;
            $refund_reason = "Early checkout: $days_early day(s) unused (50% refund)";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['show_confirmation'])) {
        // Show confirmation form
        $show_confirmation = true;
    } elseif (isset($_POST['process_checkout'])) {
        // Process the actual checkout
        $room_condition = mysqli_real_escape_string($conn, $_POST['room_condition']);
        $additional_charges = (float)$_POST['additional_charges'];
        $charges_desc = mysqli_real_escape_string($conn, $_POST['charges_description']);
        $refund_amount = (float)$_POST['refund_amount'];
        $refund_desc = mysqli_real_escape_string($conn, $_POST['refund_description']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        $admin_id = get_current_admin_id() ?: 1;
        
        // Validate balance
        if ($balance > 0 && $additional_charges == 0) {
            $error = "Guest has outstanding balance of " . formatPrice($balance) . 
                     ". Please add charges or collect payment first.";
        }
        
        if (!$error) {
            mysqli_autocommit($conn, false);
            
            try {
                // Record checkout
                $checkout_sql = "INSERT INTO checkouts
                    (booking_id, invoice_id, customer_id, room_id,
                     actual_checkout_date, checkout_time,
                     checked_out_by, room_condition, additional_charges, charges_description,
                     refund_amount, refund_description, notes, status)
                    VALUES (
                     {$booking['id']}, {$booking['invoice_id']}, {$booking['customer_id']}, {$booking['room_id']},
                     CURDATE(), CURTIME(),
                     $admin_id, '$room_condition', $additional_charges, '$charges_desc',
                     $refund_amount, '$refund_desc', '$notes', 'completed'
                    )";
                
                if (!mysqli_query($conn, $checkout_sql)) {
                    throw new Exception('Failed to record checkout: ' . mysqli_error($conn));
                }
                
                // Update booking status
                if (!mysqli_query($conn, "UPDATE bookings SET checkout_status = 'checked_out' WHERE id = {$booking['id']}")) {
                    throw new Exception('Failed to update booking status: ' . mysqli_error($conn));
                }
                
                // Process additional charges
                if ($additional_charges > 0) {
                    $txn_ref = 'LATE' . time();
                    $payment_sql = "INSERT INTO payments (invoice_id, txn_ref, amount, method, status) 
                                   VALUES ({$booking['invoice_id']}, '$txn_ref', $additional_charges, 'late_checkout', 'completed')";
                    
                    if (!mysqli_query($conn, $payment_sql)) {
                        throw new Exception('Failed to record payment: ' . mysqli_error($conn));
                    }
                    
                    if (!mysqli_query($conn, "UPDATE invoices SET amount = amount + $additional_charges WHERE id = {$booking['invoice_id']}")) {
                        throw new Exception('Failed to update invoice amount: ' . mysqli_error($conn));
                    }
                }
                
                // Log admin activity
                log_admin_activity('Guest Checkout', "Guest {$booking['customer_name']} checked out from room {$booking['room_number']}");
                
                mysqli_commit($conn);
                
                // Show success on THIS PAGE (without "Last modified") then auto-redirect
                $success_msg = "{$booking['customer_name']} checked out from Room {$booking['room_number']}!";
                header('Location: admin_checkout.php?success=1&message=' . urlencode($success_msg) . '&booking_id=' . $booking['id']);
                exit;
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                mysqli_autocommit($conn, true);
                $error = 'Checkout failed: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/header.php';

// Prepare back URL for edit link
$back_url = $booking_id ? "booking_id=$booking_id" : "invoice_id=$invoice_id";
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <?php if ($show_success): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success alert-dismissible" 
                     style="border:2px solid #00ff99;background-color:#001a0000;color:#00ff99;font-family:monospace;">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4>
                        <span class="glyphicon glyphicon-ok-circle"></span>
                        Success!
                    </h4>
                    <div class="terminal-feed">
                        <?php echo h($success_message); ?>
                        <br>
                        <small class="text-muted">Redirecting to booking details in <span id="countdown">3</span> seconds...</small>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Auto-redirect after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var countdown = 3;
            var countdownElement = document.getElementById('countdown');
            var countdownInterval = setInterval(function() {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'admin_view_booking.php?booking_id=<?php echo h($success_booking_id); ?>';
                }
            }, 1000);
        });
        </script>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-error alert-dismissible" 
                     style="border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4>
                        <span class="glyphicon glyphicon-exclamation-sign"></span>
                        Error!
                    </h4>
                    <div class="terminal-feed">
                        <?php echo h($error); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$show_success && $booking): ?>
        <!-- Guest & Booking Details (ONLY SHOW ON INITIAL FORM) -->
        <?php if (!$show_confirmation): ?>
        <div class="panel panel-default hacker-panel">
            <div class="panel-heading hacker-heading">
                <h4 class="panel-title"><i class="fas fa-calendar-alt"></i> Guest & Booking Details</h4>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-dark table-bordered checkout-details-table">
                        <tbody>
                            <tr>
                                <td width="30%"><strong>Guest Name:</strong></td>
                                <td width="70%"><?php echo h($booking['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo h($booking['phone']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Room:</strong></td>
                                <td><?php echo h($booking['room_number'] . ' - ' . $booking['building_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Invoice:</strong></td>
                                <td><span class="badge hacker-badge-info"><?php echo h($booking['invoice_number']); ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Check-in Date:</strong></td>
                                <td><?php echo date('F j, Y', strtotime($booking['checkin_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Actual Check-in:</strong></td>
                                <td>
                                    <?php if ($booking['actual_checkin_datetime']): ?>
                                        <?php echo date('F j, Y h:i A', strtotime($booking['actual_checkin_datetime'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not checked in yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Scheduled Check-out:</strong></td>
                                <td><?php echo date('F j, Y', strtotime($booking['checkout_date'])); ?> 12:00 PM</td>
                            </tr>
                            <tr>
                                <td><strong>Nights:</strong></td>
                                <td><?php echo h($booking['nights']); ?> night(s)</td>
                            </tr>
                            <tr>
                                <td><strong>Price per night:</strong></td>
                                <td>₦<?php echo number_format($booking['price_per_night'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td><?php echo formatPrice($booking['invoice_amount']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Paid:</strong></td>
                                <td><?php echo formatPrice($booking['total_paid']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Balance:</strong></td>
                                <td>
                                    <?php if ($balance > 0): ?>
                                        <span class="text-danger"><strong><?php echo formatPrice($balance); ?> DUE</strong></span>
                                    <?php else: ?>
                                        <span class="text-success"><strong>PAID IN FULL</strong></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                        
                <?php if ($balance > 0): ?>
                    <div class="alert alert-warning mt-3">
                        <h6><i class="fas fa-exclamation-triangle"></i> Outstanding Balance</h6>
                        <p class="mb-2">Guest has outstanding balance of <?php echo formatPrice($balance); ?></p>
                        <div>
                            <a href="admin_collect_payment.php?invoice_id=<?php echo h($booking['invoice_id']); ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                               class="btn hacker-btn hacker-btn-danger btn-sm">
                                <i class="fas fa-credit-card"></i> Collect Payment
                            </a>
                            <button type="button" class="btn hacker-btn hacker-btn-warning btn-sm" onclick="addBalanceAsCharge();">
                                <i class="fas fa-plus"></i> Add as Additional Charges
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                        
                <?php if ($additional_charges > 0): ?>
                    <div class="alert alert-danger mt-3">
                        <h6><i class="fas fa-clock"></i> Late Checkout Detected</h6>
                        <p class="mb-0">Auto-charging <?php echo formatPrice($additional_charges); ?> for late checkout</p>
                    </div>
                <?php elseif ($refund_amount > 0): ?>
                    <div class="alert alert-success mt-3">
                        <h6><i class="fas fa-clock"></i> Early Checkout Detected</h6>
                        <p class="mb-0">Suggested refund: <?php echo formatPrice($refund_amount); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$show_confirmation): ?>
        <!-- Checkout Form (Initial) -->
        <div class="panel panel-default hacker-panel mt-3">
            <div class="panel-heading hacker-heading">
                <h4 class="panel-title"><i class="fas fa-cogs"></i> Checkout Details</h4>
            </div>
            <div class="panel-body">
                <form method="post" id="checkoutForm">
                    <input type="hidden" name="show_confirmation" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label"><i class="fas fa-home"></i> Room Condition</label>
                                <select name="room_condition" class="form-control hacker-input" required>
                                    <option value="good" <?php echo isset($_POST['room_condition']) && $_POST['room_condition'] == 'good' ? 'selected' : ''; ?>>Good / Clean</option>
                                    <option value="needs_cleaning" <?php echo isset($_POST['room_condition']) && $_POST['room_condition'] == 'needs_cleaning' ? 'selected' : ''; ?>>Needs Cleaning</option>
                                    <option value="damaged" <?php echo isset($_POST['room_condition']) && $_POST['room_condition'] == 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="very_dirty" <?php echo isset($_POST['room_condition']) && $_POST['room_condition'] == 'very_dirty' ? 'selected' : ''; ?>>Very Dirty</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label"><i class="fas fa-plus-circle"></i> Additional Charges</label>
                                <input type="number" id="additional_charges_input" name="additional_charges" class="form-control hacker-input" 
                                       value="<?php echo isset($_POST['additional_charges']) ? $_POST['additional_charges'] : number_format($additional_charges, 2); ?>" 
                                       step="0.01" min="0">
                                <textarea id="charges_description" name="charges_description" class="form-control hacker-input mt-2" rows="2" 
                                          placeholder="Reason for charges..."><?php echo isset($_POST['charges_description']) ? h($_POST['charges_description']) : h($charges_reason); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label"><i class="fas fa-money-bill-wave"></i> Refund Amount</label>
                                <input type="number" name="refund_amount" class="form-control hacker-input" 
                                       value="<?php echo isset($_POST['refund_amount']) ? $_POST['refund_amount'] : number_format($refund_amount, 2); ?>" 
                                       step="0.01" min="0">
                                <textarea name="refund_description" class="form-control hacker-input mt-2" rows="2" 
                                          placeholder="Reason for refund..."><?php echo isset($_POST['refund_description']) ? h($_POST['refund_description']) : h($refund_reason); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                                <textarea name="notes" class="form-control hacker-input" rows="3" placeholder="Any additional comments..."><?php echo isset($_POST['notes']) ? h($_POST['notes']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="admin_checkout_management.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                <i class="fas fa-times"></i> CANCEL
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block" onclick="return validateCheckoutForm();">
                                <i class="fas fa-sign-out-alt"></i> REVIEW & CONFIRM
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Confirmation Section (Inline) - SIMPLIFIED -->
        <div class="panel panel-default hacker-panel border-warning">
            <div class="panel-heading hacker-heading bg-warning">
                <h4 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Confirm Checkout</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-warning">
                    <h5 class="text-center mb-3"><i class="fas fa-question-circle"></i> Please review checkout details before proceeding</h5>
                    
                    <div class="confirmation-details">
                        <table class="table table-dark table-bordered">
                            <tr>
                                <td width="40%"><strong>Guest:</strong></td>
                                <td width="60%"><?php echo h($booking['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Room:</strong></td>
                                <td><?php echo h($booking['room_number'] . ' (' . $booking['building_name'] . ')'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Room Condition:</strong></td>
                                <td>
                                    <?php 
                                    $room_condition_label = '';
                                    switch($_POST['room_condition']) {
                                        case 'good': $room_condition_label = '<span class="badge badge-success">Good / Clean</span>'; break;
                                        case 'needs_cleaning': $room_condition_label = '<span class="badge badge-warning">Needs Cleaning</span>'; break;
                                        case 'damaged': $room_condition_label = '<span class="badge badge-danger">Damaged</span>'; break;
                                        case 'very_dirty': $room_condition_label = '<span class="badge badge-danger">Very Dirty</span>'; break;
                                    }
                                    echo $room_condition_label;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Additional Charges:</strong></td>
                                <td>
                                    <?php 
                                    $additional = (float)$_POST['additional_charges'];
                                    if ($additional > 0): 
                                    ?>
                                        <span class="text-danger">+ <?php echo formatPrice($additional); ?></span>
                                        <br><small><?php echo h($_POST['charges_description'] ?: 'No description'); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Refund Amount:</strong></td>
                                <td>
                                    <?php 
                                    $refund = (float)$_POST['refund_amount'];
                                    if ($refund > 0): 
                                    ?>
                                        <span class="text-success">- <?php echo formatPrice($refund); ?></span>
                                        <br><small><?php echo h($_POST['refund_description'] ?: 'No description'); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($_POST['notes'])): ?>
                            <tr>
                                <td><strong>Notes:</strong></td>
                                <td><em><?php echo h($_POST['notes']); ?></em></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="bg-dark">
                                <td><strong>Final Balance:</strong></td>
                                <td>
                                    <?php 
                                    $final_balance = $balance + $additional - $refund;
                                    if ($final_balance > 0): 
                                    ?>
                                        <span class="text-danger"><strong><?php echo formatPrice($final_balance); ?> DUE</strong></span>
                                    <?php elseif ($final_balance < 0): ?>
                                        <span class="text-success"><strong><?php echo formatPrice(abs($final_balance)); ?> REFUND</strong></span>
                                    <?php else: ?>
                                        <span class="text-success"><strong>SETTLED</strong></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="alert alert-danger mt-3">
                        <h6><i class="fas fa-exclamation-triangle"></i> Important</h6>
                        <p class="mb-2">This action <strong>CANNOT BE UNDONE</strong>. Once confirmed, the room will become available for new bookings immediately.</p>
                    </div>
                    
                    <form method="post">
                        <!-- Pass all form data as hidden fields -->
                        <input type="hidden" name="process_checkout" value="1">
                        <input type="hidden" name="room_condition" value="<?php echo h($_POST['room_condition']); ?>">
                        <input type="hidden" name="additional_charges" value="<?php echo h($_POST['additional_charges']); ?>">
                        <input type="hidden" name="charges_description" value="<?php echo h($_POST['charges_description']); ?>">
                        <input type="hidden" name="refund_amount" value="<?php echo h($_POST['refund_amount']); ?>">
                        <input type="hidden" name="refund_description" value="<?php echo h($_POST['refund_description']); ?>">
                        <input type="hidden" name="notes" value="<?php echo h($_POST['notes']); ?>">
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <a href="admin_checkout.php?<?php echo h($back_url); ?>" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                    <i class="fas fa-edit"></i> EDIT DETAILS
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                    <i class="fas fa-check"></i> CONFIRM CHECKOUT
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function addBalanceAsCharge() {
    var balance = <?php echo h($balance); ?>;
    document.getElementById('additional_charges_input').value = balance.toFixed(2);
    document.getElementById('charges_description').value = 'Outstanding balance collection';
}

function validateCheckoutForm() {
    var balance = <?php echo h($balance); ?>;
    var additionalCharges = parseFloat(document.getElementById('additional_charges_input').value) || 0;
    
    if (balance > 0 && additionalCharges === 0) {
        alert('Warning: Guest has outstanding balance of <?php echo formatPrice($balance); ?>\n\nPlease add late charges or collect payment before proceeding.');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Clean up success URL parameters
    if (window.history.replaceState && (window.location.search.includes('success=') || window.location.search.includes('message='))) {
        var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        
        var bookingIdParam = new URLSearchParams(window.location.search).get('booking_id');
        var invoiceIdParam = new URLSearchParams(window.location.search).get('invoice_id');
        
        var params = [];
        if (bookingIdParam) params.push('booking_id=' + bookingIdParam);
        if (invoiceIdParam) params.push('invoice_id=' + invoiceIdParam);
        
        if (params.length > 0) {
            cleanUrl += '?' + params.join('&');
        }
        
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    // Initialize additional charges input
    var additionalChargesInput = document.getElementById('additional_charges_input');
    if (additionalChargesInput) {
        additionalChargesInput.addEventListener('input', function() {
            var value = parseFloat(this.value) || 0;
            if (value > 0) {
                var chargesDesc = document.getElementById('charges_description');
                if (chargesDesc && !chargesDesc.value) {
                    chargesDesc.value = 'Additional charges applied';
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>