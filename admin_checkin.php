<?php
// admin_checkin.php - COMPLETE FIXED VERSION
// FIXED: Error messages without h() escaping HTML

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Start session for storing form data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// Set timezone once globally
date_default_timezone_set('Africa/Lagos');

$today = date('Y-m-d');
$invoice_number = '';
$customer_info = null;
$booking_info = null;
$error = '';
$success = false;
$show_confirmation = false;

// Store booking_id for success redirect
$success_booking_id = null;

// Handle quick check-in via GET
if (isset($_GET['invoice']) && !empty($_GET['invoice'])) {
    $invoice_number = mysqli_real_escape_string($conn, trim($_GET['invoice']));
    
    // CORRECTED INVOICE LOOKUP QUERY: Allow check-in for today AND PAST dates
    $result = mysqli_query($conn, "
        SELECT i.*, b.*, c.firstname, c.lastname, c.email, c.phone,
               r.room_number, r.price_per_night, r.id as room_id,
               b.checkout_status, c.gender, c.id_type, c.id_number,
               b.actual_checkin_datetime, b.keycard_issued,
               b.id as booking_id, c.id as customer_id
        FROM invoices i
        JOIN bookings b ON i.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        WHERE i.invoice_number = '$invoice_number'
          AND b.status = 'paid'
          AND b.status != 'cancelled'
          AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
          -- CORRECTED: Allow check-in for today AND PAST dates (late arrivals)
          AND b.checkin_date <= CURDATE()
    ");
   
    if ($result && mysqli_num_rows($result) > 0) {
        $booking_info = mysqli_fetch_assoc($result);
        $customer_info = array(
            'firstname' => $booking_info['firstname'],
            'lastname' => $booking_info['lastname'],
            'email' => $booking_info['email'],
            'phone' => $booking_info['phone'],
            'gender' => $booking_info['gender'],
            'id_type' => $booking_info['id_type'],
            'id_number' => $booking_info['id_number']
        );
        
        // Prevent double check-in
        if ($booking_info['keycard_issued'] == 1) {
            $error = 'Guest already checked in on ' . date('M d, Y H:i', strtotime($booking_info['actual_checkin_datetime'])) . '.';
            $booking_info = null;
            $customer_info = null;
        }
    } else {
        $error = 'No valid booking found for this invoice. Please check the number.';
    }
}

// Handle invoice lookup via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lookup_invoice'])) {
        $invoice_number = mysqli_real_escape_string($conn, trim($_POST['invoice_number']));
       
        // SAME CORRECTED QUERY
        $result = mysqli_query($conn, "
            SELECT i.*, b.*, c.firstname, c.lastname, c.email, c.phone,
                   r.room_number, r.price_per_night, r.id as room_id,
                   b.checkout_status, c.gender, c.id_type, c.id_number,
                   b.actual_checkin_datetime, b.keycard_issued,
                   b.id as booking_id, c.id as customer_id
            FROM invoices i
            JOIN bookings b ON i.booking_id = b.id
            JOIN customers c ON b.customer_id = c.id
            JOIN rooms r ON b.room_id = r.id
            WHERE i.invoice_number = '$invoice_number'
              AND b.status = 'paid'
              AND b.status != 'cancelled'
              AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
              -- CORRECTED: Allow check-in for today AND PAST dates (late arrivals)
              AND b.checkin_date <= CURDATE()
        ");
       
        if ($result && mysqli_num_rows($result) > 0) {
            $booking_info = mysqli_fetch_assoc($result);
            $customer_info = array(
                'firstname' => $booking_info['firstname'],
                'lastname' => $booking_info['lastname'],
                'email' => $booking_info['email'],
                'phone' => $booking_info['phone'],
                'gender' => $booking_info['gender'],
                'id_type' => $booking_info['id_type'],
                'id_number' => $booking_info['id_number']
            );
            
            // Prevent double check-in
            if ($booking_info['keycard_issued'] == 1) {
                $error = 'Guest already checked in on ' . date('M d, Y H:i', strtotime($booking_info['actual_checkin_datetime'])) . '.';
                $booking_info = null;
                $customer_info = null;
            }
        } else {
            $error = 'No valid booking found for this invoice. Please check the number.';
        }
    }
    
    // Handle show confirmation
    elseif (isset($_POST['show_confirmation'])) {
        $show_confirmation = true;
        // Store form data in session
        $_SESSION['checkin_form_data'] = $_POST;
    }
    
    // Handle check-in confirmation
    elseif (isset($_POST['confirm_checkin'])) {
        $booking_id = (int)$_POST['booking_id'];
        $gender = mysqli_real_escape_string($conn, $_POST['gender']);
        $id_type = mysqli_real_escape_string($conn, $_POST['id_type']);
        $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
        $has_car = isset($_POST['has_car']) ? 1 : 0;
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        $customer_id = (int)$_POST['customer_id'];
        $admin_id = get_current_admin_id() ?: 0;
        
        // Get guest info for logging BEFORE we update (safer)
        $guest_result = mysqli_query($conn, "
            SELECT c.firstname, c.lastname, r.room_number
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN rooms r ON b.room_id = r.id
            WHERE b.id = $booking_id
        ");
        
        $guest_info = mysqli_fetch_assoc($guest_result);
        $guest_name = $guest_info ? $guest_info['firstname'] . ' ' . $guest_info['lastname'] : 'Unknown Guest';
        $room_number = $guest_info ? $guest_info['room_number'] : 'Unknown Room';
        
        // Update customer
        $update_customer = mysqli_query($conn, "
            UPDATE customers SET 
                gender = '$gender',
                id_type = '$id_type',
                id_number = '$id_number',
                has_car = $has_car,
                notes = '$notes'
            WHERE id = $customer_id
        ");
       
        if (!$update_customer) {
            $error = 'Failed to update customer information: ' . mysqli_error($conn);
        } else {
            $checkin_time = date('H:i:s');
            mysqli_query($conn, "UPDATE bookings SET checkin_time = '$checkin_time' WHERE id = $booking_id AND checkin_time IS NULL");
            
            $actual_datetime = date('Y-m-d H:i:s');
            
            $checkin_sql = "
                UPDATE bookings SET 
                    actual_checkin_datetime = '$actual_datetime',
                    actual_checkin_time = '$checkin_time',
                    keycard_issued = 1,
                    keycard_issued_by = $admin_id,
                    keycard_issued_at = '$actual_datetime'
                WHERE id = $booking_id 
                  AND keycard_issued = 0
            ";
            
            if (mysqli_query($conn, $checkin_sql) && mysqli_affected_rows($conn) > 0) {
                // Log admin activity - FIXED: Use safe data
                log_admin_activity('Guest Check-in', "Booking ID $booking_id ($guest_name) checked into room $room_number by admin ID $admin_id");
                
                $success = true;
                $success_booking_id = $booking_id;
                
                // Clear the session data after successful check-in
                if (isset($_SESSION['checkin_form_data'])) {
                    unset($_SESSION['checkin_form_data']);
                }
            } else {
                $error = 'Check-in failed. Guest may already be checked in.';
            }
        }
    }
}

// Get today's expected check-ins
$expected_checkins = mysqli_query($conn, "
    SELECT b.*, c.firstname, c.lastname, r.room_number, 
           i.invoice_number, b.checkin_time as scheduled_checkin,
           CONCAT(b.checkin_date, ' ', b.checkin_time) as scheduled_datetime,
           b.id as booking_id
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN invoices i ON b.id = i.booking_id
    WHERE b.status = 'paid'
      AND b.status != 'cancelled'
      AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out')
      AND b.checkin_date = CURDATE()           -- ONLY TODAY'S ARRIVALS
      AND b.actual_checkin_datetime IS NULL    -- NOT ALREADY CHECKED IN
    ORDER BY b.checkin_time ASC
");

// Get check-in stats
$stats_sql = "
    SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN actual_checkin_datetime IS NOT NULL THEN 1 ELSE 0 END) as checked_in,
        SUM(CASE WHEN actual_checkin_datetime IS NULL THEN 1 ELSE 0 END) as pending
    FROM bookings 
    WHERE status = 'paid' 
      AND status != 'cancelled'
      AND (checkout_status IS NULL OR checkout_status != 'checked_out')
      AND checkin_date = CURDATE()
";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

include __DIR__ . '/header.php';
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
                                            <i class="fas fa-search"></i> Lookup
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                       
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                       
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <h4><i class="fas fa-check-circle"></i> Check-in Complete!</h4>
                                <p>Guest checked in successfully. Room is now <strong>OCCUPIED</strong>.</p>
                                <a href="admin_view_invoice.php?invoice_id=<?php echo h($success_booking_id); ?>" class="btn hacker-btn hacker-btn-primary btn-lg">
                                    <i class="fas fa-key"></i> KEYCARD
                                </a>
                            </div>
                        <?php endif; ?>
                       
                        <?php if ($customer_info && $booking_info && !$success && !$show_confirmation): ?>
                        <!-- Guest Found Panel -->
                        <div class="alert hacker-alert-info">
                            <h5><i class="fas fa-user-check"></i> Guest Found - Ready for Check-in</h5>
                            <p><strong>Name:</strong> <?php echo h($customer_info['firstname'] . ' ' . $customer_info['lastname']); ?></p>
                            <p><strong>Room:</strong> <span class="badge hacker-badge-secondary"><?php echo h($booking_info['room_number']); ?></span></p>
                            <p><strong>Check-in Date:</strong> <?php echo date('M j, Y', strtotime($booking_info['checkin_date'])); ?></p>
                            <p><strong>Scheduled Check-in:</strong> <?php echo date('g:i A', strtotime($booking_info['checkin_time'])); ?></p>
                            
                            <?php if (isset($customer_info['id_type']) && $customer_info['id_number']): ?>
                            <p><strong>ID Verification:</strong> 
                                <span class="badge hacker-badge-info"><?php echo ucfirst(str_replace('_', ' ', h($customer_info['id_type']))); ?></span>
                                <?php echo h($customer_info['id_number']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Check-in form -->
                            <form method="post" id="checkin-form">
                                <input type="hidden" name="show_confirmation" value="1">
                                <input type="hidden" name="booking_id" value="<?php echo h($booking_info['booking_id']); ?>">
                                <input type="hidden" name="customer_id" value="<?php echo h($booking_info['customer_id']); ?>">
                                
                                <h6 class="mt-3 text-green"><i class="fas fa-user-edit"></i> Guest Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">Gender</label>
                                            <select name="gender" class="form-control hacker-input" required>
                                                <option value="male" <?php echo ($customer_info['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo ($customer_info['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="other" <?php echo ($customer_info['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">ID Type</label>
                                            <select name="id_type" class="form-control hacker-input" required>
                                                <option value="national_id" <?php echo ($customer_info['id_type'] == 'national_id') ? 'selected' : ''; ?>>National ID</option>
                                                <option value="passport" <?php echo ($customer_info['id_type'] == 'passport') ? 'selected' : ''; ?>>Passport</option>
                                                <option value="driver_license" <?php echo ($customer_info['id_type'] == 'driver_license') ? 'selected' : ''; ?>>Driver's License</option>
                                                <option value="voter_id" <?php echo ($customer_info['id_type'] == 'voter_id') ? 'selected' : ''; ?>>Voter's ID</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">ID Number</label>
                                            <input type="text" name="id_number" class="form-control hacker-input" 
                                                   value="<?php echo h($customer_info['id_number']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">&nbsp;</label>
                                            <div class="form-check mt-3">
                                                <input type="checkbox" name="has_car" id="has_car" class="form-check-input" value="1">
                                                <label class="form-check-label" for="has_car">Guest has a car</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="hacker-label">Notes (Optional)</label>
                                    <textarea name="notes" class="form-control hacker-input" rows="2" placeholder="Special requests, remarks..."></textarea>
                                </div>
                                
                                <!-- Complete Check-in and Cancel buttons -->
                                <div class="row no-gutters mt-3">
                                    <div class="col-6 pr-1">
                                        <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                            <i class="fas fa-check-circle"></i> Complete Check-in
                                        </button>
                                    </div>
                                    <div class="col-6 pl-1">
                                        <a href="admin_checkin.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                                
                                <small class="text-muted d-block mt-2 text-center">
                                    <i class="fas fa-info-circle"></i> This will record check-in time, update guest info, and mark room as OCCUPIED.
                                </small>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($show_confirmation && $customer_info && $booking_info): ?>
                        <!-- Confirmation Panel (Inline) -->
                        <div class="panel panel-default hacker-panel border-warning">
                            <div class="panel-heading hacker-heading bg-warning">
                                <h4 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Confirm Check-in</h4>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-warning">
                                    <h5 class="text-center mb-3"><i class="fas fa-question-circle"></i> Please review check-in details</h5>
                                    
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
                                                <td><strong>Check-in Date:</strong></td>
                                                <td><?php echo date('M j, Y', strtotime($booking_info['checkin_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Gender:</strong></td>
                                                <td><?php echo ucfirst(h($_POST['gender'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID Type:</strong></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', h($_POST['id_type']))); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID Number:</strong></td>
                                                <td><?php echo h($_POST['id_number']); ?></td>
                                            </tr>
                                            <?php if (isset($_POST['has_car'])): ?>
                                            <tr>
                                                <td><strong>Vehicle:</strong></td>
                                                <td><span class="badge badge-info">Has car</span></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($_POST['notes'])): ?>
                                            <tr>
                                                <td><strong>Notes:</strong></td>
                                                <td><em><?php echo h($_POST['notes']); ?></em></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                    
                                    <div class="alert alert-danger mt-3">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Important</h6>
                                        <p class="mb-2">This will mark the room as <strong>OCCUPIED</strong> and cannot be undone.</p>
                                    </div>
                                    
                                    <form method="post">
                                        <!-- Pass all form data as hidden fields -->
                                        <input type="hidden" name="confirm_checkin" value="1">
                                        <input type="hidden" name="booking_id" value="<?php echo h($booking_info['booking_id']); ?>">
                                        <input type="hidden" name="customer_id" value="<?php echo h($booking_info['customer_id']); ?>">
                                        <input type="hidden" name="gender" value="<?php echo h($_POST['gender']); ?>">
                                        <input type="hidden" name="id_type" value="<?php echo h($_POST['id_type']); ?>">
                                        <input type="hidden" name="id_number" value="<?php echo h($_POST['id_number']); ?>">
                                        <input type="hidden" name="has_car" value="<?php echo isset($_POST['has_car']) ? '1' : '0'; ?>">
                                        <input type="hidden" name="notes" value="<?php echo h($_POST['notes']); ?>">
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <a href="admin_checkin.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                                    <i class="fas fa-edit"></i> EDIT DETAILS
                                                </a>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                                    <i class="fas fa-check"></i> CONFIRM
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
                <!-- Today's Expected Check-ins Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title">
                            <i class="fas fa-calendar-day"></i> Today's Expected Check-ins
                            <small class="text-muted">(<span id="checkin-count"><?php echo mysqli_num_rows($expected_checkins); ?></span> guests)</small>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (mysqli_num_rows($expected_checkins) > 0): ?>
                            <div id="checkins-grid" class="checkin-grid">
                                <?php 
                                mysqli_data_seek($expected_checkins, 0);
                                while ($row = mysqli_fetch_assoc($expected_checkins)): 
                                    $scheduled_time = strtotime($row['scheduled_datetime']);
                                    $current_time = time();
                                    $time_diff = $scheduled_time - $current_time;
                                    $hours_until = floor($time_diff / 3600);
                                    $minutes_until = floor(($time_diff % 3600) / 60);
                                    
                                    // Always allow check-in for today's guests
                                    $is_today = true;
                                ?>
                                    <div id="checkin-<?php echo h($row['booking_id']); ?>" class="checkin-card arrived">
                                        <div class="room-number">Room <?php echo h($row['room_number']); ?></div>
                                        <div class="guest-name">
                                            <?php echo h($row['firstname'] . ' ' . $row['lastname']); ?>
                                        </div>
                                        <div class="mt-2">
                                            <?php if ($time_diff <= 0): ?>
                                                <span class="badge hacker-badge-success">ARRIVED</span>
                                            <?php else: ?>
                                                <span class="badge hacker-badge-warning">
                                                    In <?php echo h($hours_until); ?>h <?php echo h($minutes_until); ?>m
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- ICON-ONLY BUTTONS SIDE BY SIDE -->
                                            <div class="d-flex justify-content-between mt-1">
                                                <a href="admin_checkin.php?invoice=<?php echo h($row['invoice_number']); ?>" 
                                                   class="btn hacker-btn hacker-btn-success btn-xs" 
                                                   title="Check-in Now">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                </a>
                                                
                                                <a href="admin_view_invoice.php?invoice_id=<?php echo h($row['booking_id']); ?>" 
                                                   class="btn hacker-btn hacker-btn-info btn-xs"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted"><i class="fas fa-check-circle"></i> No expected check-ins today.</p>
                        <?php endif; ?>
                    </div>
                </div>
               
                <!-- Quick Check-in Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-bolt"></i> Quick Check-in</h3>
                    </div>
                    <div class="panel-body">
                        <form method="get" class="form-inline">
                            <div class="input-group hacker-input-group">
                                <input type="text" name="invoice" class="form-control hacker-input" placeholder="Invoice Number" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn hacker-btn hacker-btn-primary">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <small class="text-muted mt-2 d-block">Or use the invoice lookup above for full check-in process.</small>
                    </div>
                </div>

                <!-- Check-in Statistics Panel -->
                <div class="panel panel-default hacker-panel">
                    <div class="panel-heading hacker-heading">
                        <h3 class="panel-title"><i class="fas fa-chart-bar"></i> Check-in Statistics</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h4 class="text-primary"><?php echo h($stats['total_today']); ?></h4>
                                <small>Total Today</small>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-success"><?php echo h($stats['checked_in']); ?></h4>
                                <small>Already Checked-in</small>
                            </div>
                            <div class="col-md-4">
                                <h4 class="text-warning"><?php echo h($stats['pending']); ?></h4>
                                <small>Awaiting Check-in</small>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Guests can check-in anytime from 12:00 AM today (including late arrivals)
                            </small>
                        </div>
                    </div>
                </div>
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
                                <a href="admin_booking_management.php" class="btn hacker-btn hacker-btn-primary btn-block">
                                    <i class="fas fa-calendar-alt"></i> Bookings
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_invoice_management.php" class="btn hacker-btn hacker-btn-info btn-block">
                                    <i class="fas fa-file-invoice"></i> Invoices
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_checkout_management.php" class="btn hacker-btn hacker-btn-warning btn-block">
                                    <i class="fas fa-sign-out-alt"></i> Checkouts
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin_dashboard.php" class="btn hacker-btn hacker-btn-secondary btn-block">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
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
// Update the check-in count display
document.addEventListener('DOMContentLoaded', function() {
    // Update check-in count if there are pending check-ins
    var checkinCards = document.querySelectorAll('.checkin-card');
    var countElement = document.getElementById('checkin-count');
    if (countElement && checkinCards.length > 0) {
        countElement.textContent = checkinCards.length;
    }
    
    // Auto-refresh page every 60 seconds for check-in updates
    setTimeout(function() {
        window.location.reload();
    }, 60000);
});
</script>

<?php include __DIR__ . '/footer.php'; ?>