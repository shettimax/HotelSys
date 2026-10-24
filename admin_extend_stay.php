<?php
// admin_extend_stay.php - WITH HACKER PANEL STYLING
require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// Fix Timezone Issue
if (function_exists('date_default_timezone_get')) {
    $current_tz = date_default_timezone_get();
    if ($current_tz == 'UTC' || $current_tz == 'GMT') {
        date_default_timezone_set('Africa/Lagos');
    }
}

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$checkout_time = '12:00:00';
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Today's checkouts
$expired_result = mysqli_query($conn, "
    SELECT b.*, c.firstname, c.lastname, r.room_number, r.price_per_night,
           b.created_at as booking_time,
           b.checkout_status,
           CASE
               WHEN DATE(b.created_at) = CURDATE() AND b.checkout_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               THEN DATE_ADD(DATE(b.created_at), INTERVAL 1 DAY) + INTERVAL 12 HOUR
               ELSE DATE(b.checkout_date) + INTERVAL 12 HOUR
           END as actual_checkout_datetime
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.status = 'paid'
      AND b.status != 'cancelled'
      AND b.actual_checkin_datetime IS NOT NULL
      AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
      AND (
        b.checkout_date = '$today'
        OR (DATE(b.created_at) = '$today' AND b.checkout_date = '$tomorrow')
      )
    ORDER BY actual_checkout_datetime ASC
");

$invoice_number = '';
$customer_info = null;
$booking_info = null;
$additional_nights = 1;
$clash_message = '';
$extensions = [];
$show_confirmation = false;

// Handle invoice lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lookup_invoice'])) {
        $invoice_number = mysqli_real_escape_string($conn, trim($_POST['invoice_number']));
       
        $result = mysqli_query($conn, "
            SELECT i.*, b.*, c.firstname, c.lastname, c.email, c.phone,
                   r.room_number, r.price_per_night, r.id as room_id,
                   b.checkout_status, i.id as invoice_id
            FROM invoices i
            JOIN bookings b ON i.booking_id = b.id
            JOIN customers c ON b.customer_id = c.id
            JOIN rooms r ON b.room_id = r.id
            WHERE i.invoice_number = '$invoice_number'
              AND b.status = 'paid'
              AND b.status != 'cancelled'
              AND b.actual_checkin_datetime IS NOT NULL
              AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
              AND b.checkout_date >= CURDATE()
        ");
       
        if (mysqli_num_rows($result) > 0) {
            $booking_info = mysqli_fetch_assoc($result);
            $customer_info = array(
                'firstname' => $booking_info['firstname'],
                'lastname' => $booking_info['lastname'],
                'email' => $booking_info['email'],
                'phone' => $booking_info['phone']
            );
           
            $extensions_sql = "SELECT * FROM stay_extensions WHERE booking_id = {$booking_info['id']} ORDER BY extended_at DESC";
            $extensions_result = mysqli_query($conn, $extensions_sql);
            $extensions = mysqli_fetch_all($extensions_result, MYSQLI_ASSOC);
        } else {
            $clash_message = 'Invoice not found, booking is cancelled, guest not checked in, or not eligible for extension';
        }
    }
    
    // Handle show confirmation
    elseif (isset($_POST['show_confirmation'])) {
        $show_confirmation = true;
    }
    
    // Handle stay extension
    elseif (isset($_POST['extend_stay'])) {
        $booking_id = (int)$_POST['booking_id'];
        $additional_nights = (int)$_POST['additional_nights'];
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $admin_id = get_current_admin_id() ?: 0;

        if ($additional_nights < 1 || $additional_nights > 30) {
            $clash_message = 'Additional nights must be between 1 and 30';
        } else {
            $result = mysqli_query($conn, "
                SELECT b.*, r.price_per_night, r.id as room_id, i.id as invoice_id,
                       c.firstname, c.lastname, r.room_number
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN customers c ON b.customer_id = c.id
                LEFT JOIN invoices i ON b.id = i.booking_id
                WHERE b.id = $booking_id
                  AND b.status = 'paid'
                  AND b.status != 'cancelled'
                  AND b.actual_checkin_datetime IS NOT NULL
                  AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
            ");
           
            if ($result && mysqli_num_rows($result) > 0) {
                $booking = mysqli_fetch_assoc($result);
                
                if ($booking['status'] == 'cancelled') {
                    $clash_message = 'Cannot extend a cancelled booking!';
                } else {
                    $current_checkout = $booking['checkout_date'];
                    $new_checkout = date('Y-m-d', strtotime($current_checkout . " + $additional_nights days"));
                    $additional_amount = $additional_nights * $booking['price_per_night'];

                    $clash_check = mysqli_query($conn, "
                        SELECT b.checkin_date, b.checkout_date, c.firstname, c.lastname, r.room_number
                        FROM bookings b
                        JOIN customers c ON b.customer_id = c.id
                        JOIN rooms r ON b.room_id = r.id
                        WHERE b.room_id = {$booking['room_id']}
                          AND b.status IN ('confirmed', 'paid')
                          AND b.status != 'cancelled'
                          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
                          AND b.id != $booking_id
                          AND NOT (b.checkout_date <= '$current_checkout' OR b.checkin_date >= '$new_checkout')
                        LIMIT 1
                    ");

                    if (mysqli_num_rows($clash_check) > 0) {
                        $clash = mysqli_fetch_assoc($clash_check);
                        $clash_message = "Room booked by {$clash['firstname']} {$clash['lastname']} from {$clash['checkin_date']} to {$clash['checkout_date']}";
                    } else {
                        $max_nights = 30;
                        $total_nights_after = $booking['nights'] + $additional_nights;
                        if ($total_nights_after > $max_nights) {
                            $clash_message = "Maximum total stay is {$max_nights} nights. Current: {$booking['nights']}";
                        } else {
                            mysqli_autocommit($conn, false);
                            $ok = true;

                            // 1. Record payment
                            $txn_ref = 'EXT' . time() . rand(100,999);
                            $ok = $ok && mysqli_query($conn, "INSERT INTO payments 
                                (invoice_id, txn_ref, amount, method, status) 
                                VALUES ({$booking['invoice_id']}, '$txn_ref', $additional_amount, '$payment_method', 'completed')");

                            // 2. Update invoice
                            $ok = $ok && mysqli_query($conn, "UPDATE invoices SET amount = amount + $additional_amount WHERE id = {$booking['invoice_id']}");

                            // 3. Update booking
                            $ok = $ok && mysqli_query($conn, "
                                UPDATE bookings
                                SET checkout_date = '$new_checkout',
                                    nights = nights + $additional_nights,
                                    amount = amount + $additional_amount
                                WHERE id = $booking_id
                            ");

                            // 4. Log extension
                            $ok = $ok && mysqli_query($conn, "
                                INSERT INTO stay_extensions
                                (booking_id, original_checkout, new_checkout, additional_nights, additional_amount, extended_by)
                                VALUES
                                ($booking_id, '$current_checkout', '$new_checkout', $additional_nights, $additional_amount, $admin_id)
                            ");

                            // 5. Log admin activity - MOVED INSIDE TRANSACTION
                            if ($ok && function_exists('log_admin_activity')) {
                                $log_ok = log_admin_activity('Extend Stay', "Booking ID {$booking_id} extended by {$additional_nights} night(s). Guest: {$booking['firstname']} {$booking['lastname']}, Room: {$booking['room_number']}, Amount: ₦" . number_format($additional_amount, 2));
                                $ok = $ok && $log_ok;
                            }

                            if ($ok) {
                                mysqli_commit($conn);
                                
                                // Refresh the page to show success
                                header('Location: admin_extend_stay.php?success=' . urlencode("Extended by $additional_nights night(s)! ₦" . number_format($additional_amount, 2) . " paid."));
                                exit;
                            } else {
                                mysqli_rollback($conn);
                                $clash_message = 'Extension failed: ' . mysqli_error($conn);
                            }
                        }
                    }
                }
            } else {
                $clash_message = 'Booking not found, cancelled, guest not checked in, or already checked out';
            }
        }
    }
}

// Check for success message
$success_message = '';
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
}

// Quick extension by booking ID
if (isset($_GET['booking'])) {
    $booking_id = (int)$_GET['booking'];
    $result = mysqli_query($conn, "
        SELECT b.*, r.price_per_night, r.id as room_id, c.firstname, c.lastname, c.email, c.phone, r.room_number
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN customers c ON b.customer_id = c.id
        WHERE b.id = $booking_id
          AND b.status = 'paid'
          AND b.status != 'cancelled'
          AND b.actual_checkin_datetime IS NOT NULL
          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
          AND b.checkout_date >= CURDATE()
    ");
   
    if (mysqli_num_rows($result) > 0) {
        $booking_info = mysqli_fetch_assoc($result);
        $customer_info = array(
            'firstname' => $booking_info['firstname'],
            'lastname' => $booking_info['lastname'],
            'email' => $booking_info['email'],
            'phone' => $booking_info['phone']
        );
        $invoice_number = 'BOOKING_' . $booking_info['id'];
       
        $extensions_sql = "SELECT * FROM stay_extensions WHERE booking_id = $booking_id ORDER BY extended_at DESC";
        $extensions_result = mysqli_query($conn, $extensions_sql);
        $extensions = mysqli_fetch_all($extensions_result, MYSQLI_ASSOC);
    } else {
        $clash_message = 'Booking not found, cancelled, guest not checked in, or not eligible for extension';
    }
}

// Countdown logic
$checkouts_data = [];
$active_checkouts = 0;
if (mysqli_num_rows($expired_result) > 0) {
    mysqli_data_seek($expired_result, 0);
    while ($row = mysqli_fetch_assoc($expired_result)) {
        $actualCheckout = ($row['checkout_date'] == $tomorrow && date('Y-m-d', strtotime($row['booking_time'])) == $today)
            ? $tomorrow . ' 12:00:00'
            : $row['checkout_date'] . ' 12:00:00';
        
        $checkoutTime = strtotime($actualCheckout);
        $timeRemaining = $checkoutTime - time();
        
        if ($timeRemaining > 0) {
            $checkouts_data[] = array_merge($row, [
                'actual_checkout' => $actualCheckout,
                'time_remaining' => $timeRemaining,
                'hours_remaining' => floor($timeRemaining / 3600)
            ]);
            $active_checkouts++;
        }
    }
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-6">
                <!-- Lookup Booking Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-search"></i> Lookup Booking</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" class="mb-4">
                            <input type="hidden" name="lookup_invoice" value="1">
                            <div class="form-group">
                                <label class="hacker-label">Enter Invoice Number</label>
                                <div class="input-group hacker-input-group">
                                    <input type="text" name="invoice_number" class="form-control hacker-input"
                                           value="<?php echo h($invoice_number); ?>" placeholder="e.g., INV..." required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn hacker-btn hacker-btn-info">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                       
                        <?php if ($clash_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo h($clash_message); ?>
                            </div>
                        <?php endif; ?>
                       
                        <?php if ($success_message): ?>
                            <div class="alert alert-success text-center">
                                <h4><i class="fas fa-check-circle"></i> Extension Complete!</h4>
                                <p><?php echo h($success_message); ?></p>
                                <a href="admin_extend_stay.php" class="btn hacker-btn hacker-btn-primary btn-lg">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                       
                        <?php if ($customer_info && $booking_info && !$success_message && !$show_confirmation): ?>
                        <div class="alert hacker-alert-info">
                            <h5><i class="fas fa-user-check"></i> Customer Found</h5>
                            <p><strong>Name:</strong> <?php echo h($customer_info['firstname'] . ' ' . $customer_info['lastname']); ?></p>
                            <p><strong>Room:</strong> <span class="badge hacker-badge-secondary"><?php echo h($booking_info['room_number']); ?></span></p>
                            <p><strong>Current Checkout:</strong> <?php echo date('M j, Y', strtotime($booking_info['checkout_date'])); ?></p>
                            <p><strong>Price/Night:</strong> ₦<?php echo number_format($booking_info['price_per_night'], 2); ?></p>
                            <p class="mt-3">
                                <a href="admin_view_invoice.php?invoice_id=<?php echo h($booking_info['invoice_id']); ?>" class="btn hacker-btn hacker-btn-info btn-sm" title="View Invoice">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                <a href="admin_view_booking.php?booking_id=<?php echo h($booking_info['id']); ?>" class="btn hacker-btn hacker-btn-primary btn-sm" title="View Booking">
                                    <i class="fas fa-calendar-alt"></i>
                                </a>
                            </p>
                        </div>
                       
                        <form method="post" id="extendStayForm">
                            <input type="hidden" name="show_confirmation" value="1">
                            <input type="hidden" name="booking_id" value="<?php echo h($booking_info['id']); ?>">
                           
                            <h6 class="mt-3 text-green"><i class="fas fa-calendar-plus"></i> Extension Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Additional Nights</label>
                                        <input type="number" name="additional_nights" class="form-control hacker-input" 
                                               min="1" max="30" value="1" required 
                                               onchange="updateExtensionFields()" onkeyup="updateExtensionFields()">
                                        <small class="text-muted">1-30 nights</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">New Checkout Date</label>
                                        <input type="text" name="new_checkout_date" class="form-control hacker-input" readonly>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Additional Amount</label>
                                        <input type="text" name="additional_amount_display" class="form-control hacker-input" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Payment Method</label>
                                        <select name="payment_method" class="form-control hacker-input" required>
                                            <option value="cash">Cash</option>
                                            <option value="transfer">Bank Transfer</option>
                                            <option value="pos">POS/Card</option>
                                            <option value="manual">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                           
                            <!-- Review and Cancel buttons -->
                            <div class="row no-gutters mt-3">
                                <div class="col-6 pr-1">
                                    <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                        <i class="fas fa-calendar-check"></i>
                                    </button>
                                </div>
                                <div class="col-6 pl-1">
                                    <a href="admin_extend_stay.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <small class="text-muted d-block mt-2 text-center">
                                <i class="fas fa-info-circle"></i> This will charge the guest and update their booking.
                            </small>
                        </form>
                        <?php elseif ($show_confirmation && $customer_info && $booking_info): ?>
                        <!-- Confirmation Panel (Inline) -->
                        <div class="panel panel-default hacker-panel border-warning">
                            <div class="panel-heading hacker-heading bg-warning">
                                <h4 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Confirm Extension</h4>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-warning">
                                    <h5 class="text-center mb-3"><i class="fas fa-question-circle"></i> Please review extension details</h5>
                                    
                                    <div class="confirmation-details">
                                        <table class="table table-dark table-bordered">
                                            <tr>
                                                <td width="40%"><strong>Guest:</strong></td>
                                                <td width="60%"><?php echo h($customer_info['firstname'] . ' ' . $customer_info['lastname']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Room:</strong></td>
                                                <td><?php echo h($booking_info['room_number']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Current Checkout:</strong></td>
                                                <td><?php echo h($booking_info['checkout_date']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Additional Nights:</strong></td>
                                                <td><?php echo h($_POST['additional_nights']); ?> night(s)</td>
                                            </tr>
                                            <tr>
                                                <td><strong>New Checkout Date:</strong></td>
                                                <td>
                                                    <?php 
                                                    $current = $booking_info['checkout_date'];
                                                    $additional_nights = (int)$_POST['additional_nights'];
                                                    $d = new DateTime($current);
                                                    $d->modify("+{$additional_nights} days");
                                                    $new_date = $d->format('Y-m-d');
                                                    echo $new_date;
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Additional Amount:</strong></td>
                                                <td>
                                                    <?php 
                                                    $additional_amount = $additional_nights * $booking_info['price_per_night'];
                                                    echo '₦' . number_format($additional_amount, 2);
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Payment Method:</strong></td>
                                                <td><?php echo ucfirst(h($_POST['payment_method'])); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="alert alert-danger mt-3">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Important</h6>
                                        <p class="mb-2">This action will charge the guest and update their booking.</p>
                                    </div>
                                    
                                    <form method="post">
                                        <!-- Pass all form data as hidden fields -->
                                        <input type="hidden" name="extend_stay" value="1">
                                        <input type="hidden" name="booking_id" value="<?php echo h($booking_info['id']); ?>">
                                        <input type="hidden" name="additional_nights" value="<?php echo h($_POST['additional_nights']); ?>">
                                        <input type="hidden" name="payment_method" value="<?php echo h($_POST['payment_method']); ?>">
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <a href="admin_extend_stay.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
           
            <div class="col-md-6">
                <!-- Active Checkouts Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title">
                            <i class="fas fa-clock"></i> Active Checkouts
                            <small class="text-muted">(<span id="active-count"><?php echo h($active_checkouts); ?></span> rooms)</small>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($active_checkouts > 0): ?>
                            <div id="checkouts-grid" class="checkin-grid">
                                <?php foreach ($checkouts_data as $index => $row): ?>
                                    <div id="room-<?php echo h($index); ?>" class="checkin-card">
                                        <div class="room-number">Room <?php echo h($row['room_number']); ?></div>
                                        <div id="countdown-<?php echo h($index); ?>" class="countdown-container">
                                            <span class="badge hacker-badge-success countdown-time">Calculating...</span>
                                        </div>
                                        <div class="mt-2">
                                            <!-- ICON-ONLY ACTION BUTTONS -->
                                            <div class="d-flex justify-content-between mt-1">
                                                <a href="admin_checkout.php?booking_id=<?php echo h($row['id']); ?>"
                                                   class="btn hacker-btn hacker-btn-danger btn-xs"
                                                   title="Checkout Guest">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </a>
                                                
                                                <a href="admin_extend_stay.php?booking=<?php echo h($row['id']); ?>"
                                                   class="btn hacker-btn hacker-btn-warning btn-xs"
                                                   title="Extend Stay">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted"><i class="fas fa-check-circle"></i> No active checkouts today.</p>
                        <?php endif; ?>
                    </div>
                </div>
               
                <!-- Quick Extension Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-bolt"></i> Quick Extension</h3>
                    </div>
                    <div class="panel-body">
                        <form method="get" class="form-inline">
                            <div class="input-group hacker-input-group">
                                <input type="number" name="booking" class="form-control hacker-input" placeholder="Booking ID" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn hacker-btn hacker-btn-primary">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Previous Extensions Panel -->
                <?php if ($customer_info && $booking_info && count($extensions) > 0): ?>
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-history"></i> Previous Extensions</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar"></i></th>
                                        <th><i class="fas fa-calendar-minus"></i></th>
                                        <th><i class="fas fa-calendar-plus"></i></th>
                                        <th><i class="fas fa-moon"></i></th>
                                        <th><i class="fas fa-money-bill"></i></th>
                                    </tr>
                                    <tr>
                                        <th>Extended On</th>
                                        <th>Original</th>
                                        <th>New</th>
                                        <th>Nights</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($extensions as $ext): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($ext['extended_at'])); ?></td>
                                        <td><?php echo h($ext['original_checkout']); ?></td>
                                        <td><?php echo h($ext['new_checkout']); ?></td>
                                        <td><?php echo h($ext['additional_nights']); ?></td>
                                        <td>₦<?php echo number_format($ext['additional_amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Links Panel -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-link"></i> Quick Links</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="admin_booking_management.php" class="btn hacker-btn hacker-btn-primary btn-block" title="Bookings">
                                    <i class="fas fa-calendar-alt"></i>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_invoice_management.php" class="btn hacker-btn hacker-btn-info btn-block" title="Invoices">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_checkout_management.php" class="btn hacker-btn hacker-btn-warning btn-block" title="Checkouts">
                                    <i class="fas fa-sign-out-alt"></i>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_dashboard.php" class="btn hacker-btn hacker-btn-secondary btn-block" title="Dashboard">
                                    <i class="fas fa-tachometer-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateExtensionFields() {
    const nights = parseInt(document.querySelector('input[name="additional_nights"]').value) || 1;
    const current = '<?php echo isset($booking_info["checkout_date"]) ? $booking_info["checkout_date"] : ""; ?>';
    const price = <?php echo isset($booking_info["price_per_night"]) ? $booking_info["price_per_night"] : 0; ?>;
    
    if (current && price > 0) {
        const d = new Date(current);
        d.setDate(d.getDate() + nights);
        const newDate = d.toISOString().split('T')[0];
        const amount = nights * price;
        
        document.querySelector('input[name="new_checkout_date"]').value = newDate;
        document.querySelector('input[name="additional_amount_display"]').value = '₦' + amount.toLocaleString('en-US', {minimumFractionDigits: 2});
    }
}

// Initialize extension fields if needed
if (document.querySelector('input[name="additional_nights"]')) {
    updateExtensionFields();
}

// JavaScript Countdown Improvement
function updateAllCountdowns() {
    const now = new Date().getTime();
    <?php foreach ($checkouts_data as $index => $row): ?>
        const checkoutTime<?php echo h($index); ?> = new Date('<?php echo h($row['actual_checkout']); ?>').getTime();
        const diff<?php echo h($index); ?> = checkoutTime<?php echo h($index); ?> - now;
        const element<?php echo h($index); ?> = document.getElementById('countdown-<?php echo h($index); ?>');
        const card<?php echo h($index); ?> = document.getElementById('room-<?php echo h($index); ?>');
        
        if (diff<?php echo h($index); ?> <= 0) {
            // Time's up - show expired
            if (element<?php echo h($index); ?>) {
                element<?php echo h($index); ?>.innerHTML = `<span class="badge badge-danger countdown-time">EXPIRED</span>`;
            }
            if (card<?php echo h($index); ?>) {
                card<?php echo h($index); ?>.style.borderColor = '#dc3545';
            }
        } else {
            const hours = Math.floor(diff<?php echo h($index); ?> / 3600000);
            const minutes = Math.floor((diff<?php echo h($index); ?> % 3600000) / 60000);
            const seconds = Math.floor((diff<?php echo h($index); ?> % 60000) / 1000);
            let badgeClass = 'badge-success';
            if (hours < 1) badgeClass = 'badge-danger';
            else if (hours < 3) badgeClass = 'badge-warning';
            if (element<?php echo h($index); ?>) {
                element<?php echo h($index); ?>.innerHTML = `<span class="badge ${badgeClass} countdown-time">${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}</span>`;
            }
        }
    <?php endforeach; ?>
}

// Initialize countdown on page load
updateAllCountdowns();
setInterval(updateAllCountdowns, 1000);

// Auto-refresh page every 60 seconds for check-in updates
setTimeout(function() {
    window.location.reload();
}, 60000);
</script>

<?php include __DIR__.'/footer.php'; ?>