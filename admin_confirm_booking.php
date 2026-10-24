<?php
// admin_confirm_booking.php - ADAPTED FOR SAME-DAY CHECKOUT
// FIXED: Staff tracking auto-assigns from session - NO DROPDOWN

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

// SAFEST WAY - NO UNDEFINED INDEX EVER
$pending_booking = null;
if (isset($_SESSION['pending_booking'])) {
    $pending_booking = $_SESSION['pending_booking'];
}

// Must have booking data AND customer selected
if (!$pending_booking 
    || !isset($pending_booking['room_id']) 
    || !isset($pending_booking['customer_id']) 
    || empty($pending_booking['customer_id'])) {
    
    header('Location: admin_customers.php?booking_mode=1');
    exit;
}

$customer_id = (int)$pending_booking['customer_id'];
$room_id     = (int)$pending_booking['room_id'];

// Fetch customer
$customer_result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $customer_id");
$customer = mysqli_fetch_assoc($customer_result);

if (!$customer) {
    echo "<div class='alert alert-danger'>Customer not found. Please start over.</div>";
    include __DIR__ . '/footer.php';
    exit;
}

// Fetch room with building type
$room_result = mysqli_query($conn, "
    SELECT r.*, b.name AS building_name, b.building_type 
    FROM rooms r 
    LEFT JOIN buildings b ON r.building_id = b.id 
    WHERE r.id = $room_id
");
$room = mysqli_fetch_assoc($room_result);

if (!$room) {
    echo "<div class='alert alert-danger'>Room not found.</div>";
    include __DIR__ . '/footer.php';
    exit;
}

// Handle both key formats
if (isset($pending_booking['checkin_date'])) {
    $pending_checkin_date = $pending_booking['checkin_date'];
} elseif (isset($pending_booking['checkin'])) {
    $pending_checkin_date = $pending_booking['checkin'];
} else {
    $pending_checkin_date = date('Y-m-d');
}

if (isset($pending_booking['checkout_date'])) {
    $pending_checkout_date = $pending_booking['checkout_date'];
} elseif (isset($pending_booking['checkout'])) {
    $pending_checkout_date = $pending_booking['checkout'];
} else {
    $pending_checkout_date = date('Y-m-d', strtotime('+1 day'));
}

// Ensure nights and amount are set
if (!isset($pending_booking['nights'])) {
    $checkin = new DateTime($pending_checkin_date);
    $checkout = new DateTime($pending_checkout_date);
    $interval = $checkin->diff($checkout);
    $nights = $interval->days;
    $pending_booking['nights'] = $nights;
}

if (!isset($pending_booking['amount']) && isset($pending_booking['room_price'])) {
    $pending_booking['amount'] = $pending_booking['nights'] * $pending_booking['room_price'];
}

// Handle booking confirmation
$error = '';
$success = false;
$booking_id = null;
$invoice_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    // Get current time for checkin
    $current_datetime = date('Y-m-d H:i:s');
    $checkin_date = date('Y-m-d');
    $checkin_time = date('H:i:s');
    
    if ($pending_checkin_date == $checkin_date) {
        $actual_checkin_time = $checkin_time;
    } else {
        $actual_checkin_time = '14:00:00';
    }
    
    $checkout_time = '12:00:00';
    
    // Room availability check
    if (!is_room_available_same_day_checkout($conn, $room_id, $pending_checkin_date, $pending_checkout_date)) {
        $error = "Room no longer available. Someone just booked it or someone is checking out after 12:00 PM on your check-in date!";
    } elseif (!room_is_available_datetime($conn, $room_id, $pending_checkin_date . ' ' . $actual_checkin_time, $pending_checkout_date . ' ' . $checkout_time)) {
        $error = 'Room is no longer available for the selected dates. Please choose different dates.';
    } else {
        // ============================================================
        // STAFF SALES TRACKING - Auto-assign from session
        // ============================================================
        $staff_id = null;
        $staff_name = null;
        $has_staff_columns = db_column_exists($conn, 'bookings', 'staff_id');
        
        // If staff is logged in, auto-assign
        if (is_staff_logged_in()) {
            $staff_id = get_current_staff_id();
            $staff_name = get_current_staff_name();
        }
        // If admin is logged in, check if they selected a staff (but no dropdown anymore, so this is just fallback)
        elseif (is_admin_logged_in() && $has_staff_columns && !empty($_POST['staff_id'])) {
            $posted_staff_id = (int)$_POST['staff_id'];
            $staff_lookup = mysqli_query($conn, "SELECT id, name FROM staff WHERE id = $posted_staff_id AND status = 'active' LIMIT 1");
            if ($staff_lookup && ($staff_row = mysqli_fetch_assoc($staff_lookup))) {
                $staff_id = (int)$staff_row['id'];
                $staff_name = $staff_row['name'];
            }
        }

        // Start transaction
        mysqli_autocommit($conn, false);
        
        try {
            // Create booking
            $checkin = mysqli_real_escape_string($conn, $pending_checkin_date);
            $checkout = mysqli_real_escape_string($conn, $pending_checkout_date);
            $checkin_time_esc = mysqli_real_escape_string($conn, $actual_checkin_time);
            $checkout_time_esc = mysqli_real_escape_string($conn, $checkout_time);
            $nights = (int)$pending_booking['nights'];
            $amount = (float)$pending_booking['amount'];

            if ($has_staff_columns) {
                $staff_id_sql = $staff_id ? (int)$staff_id : 'NULL';
                $staff_name_sql = $staff_name ? "'" . mysqli_real_escape_string($conn, $staff_name) . "'" : 'NULL';
                $booking_query = "INSERT INTO bookings (customer_id, room_id, checkin_date, checkout_date, checkin_time, checkout_time, nights, amount, status, staff_id, staff_name, created_at) 
                                 VALUES ($customer_id, $room_id, '$checkin', '$checkout', '$checkin_time_esc', '$checkout_time_esc', $nights, $amount, 'confirmed', $staff_id_sql, $staff_name_sql, NOW())";
            } else {
                $booking_query = "INSERT INTO bookings (customer_id, room_id, checkin_date, checkout_date, checkin_time, checkout_time, nights, amount, status, created_at) 
                                 VALUES ($customer_id, $room_id, '$checkin', '$checkout', '$checkin_time_esc', '$checkout_time_esc', $nights, $amount, 'confirmed', NOW())";
            }
            
            if (mysqli_query($conn, $booking_query)) {
                $booking_id = mysqli_insert_id($conn);
                
                // Generate invoice
                $inv_no = generate_invoice_number($conn);
                $invoice_query = "INSERT INTO invoices (invoice_number, booking_id, amount, issued_at, paid) 
                                 VALUES ('$inv_no', $booking_id, $amount, NOW(), 0)";
                
                if (mysqli_query($conn, $invoice_query)) {
                    $invoice_id = mysqli_insert_id($conn);
                    
                    // Update booking with invoice ID
                    mysqli_query($conn, "UPDATE bookings SET invoice_id = $invoice_id WHERE id = $booking_id");
                    
                    // Create payment record (pending)
                    $txn_ref = 'TXN' . time() . rand(100, 999);
                    $payment_query = "INSERT INTO payments (invoice_id, txn_ref, amount, method, status) 
                                      VALUES ($invoice_id, '$txn_ref', $amount, 'manual', 'initiated')";
                    
                    if (mysqli_query($conn, $payment_query)) {
                        log_admin_activity('Create Booking', "Booking #$booking_id created for " . $customer['firstname'] . ' ' . $customer['lastname'] . " (Invoice #$inv_no)");
                        
                        mysqli_commit($conn);
                        
                        unset($_SESSION['pending_booking']);
                        
                        $_SESSION['admin_booking_id'] = $booking_id;
                        $_SESSION['admin_invoice_id'] = $invoice_id;
                        
                        header('Location: admin_booking_success.php');
                        exit;
                    } else {
                        throw new Exception('Failed to create payment: ' . mysqli_error($conn));
                    }
                } else {
                    throw new Exception('Failed to create invoice: ' . mysqli_error($conn));
                }
            } else {
                throw new Exception('Failed to create booking: ' . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            mysqli_autocommit($conn, true);
            $error = 'Booking failed: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/header.php';

$today = date('Y-m-d');
$display_checkin_time = ($pending_checkin_date == $today) ? 
                       'Current Time (' . date('H:i') . ')' : 
                       '14:00 (Default check-in time)';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fa fa-check-circle"></i> Confirm Booking</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <h4><i class="fa fa-exclamation-triangle"></i> Error</h4>
                            <p><?php echo $error; ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fa fa-calendar-alt"></i> Booking & Customer Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="info-box">
                                            <h6><i class="fa fa-user"></i> Customer Information</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <strong>Name:</strong>
                                                        <div><?php echo h($customer['firstname'] . ' ' . $customer['lastname']); ?>
                                                            <span class="badge badge-info ml-2">
                                                                <?php 
                                                                $gender_icon = '';
                                                                switch($customer['gender']) {
                                                                    case 'male': $gender_icon = '♂'; break;
                                                                    case 'female': $gender_icon = '♀'; break;
                                                                    default: $gender_icon = '👤';
                                                                }
                                                                echo $gender_icon . ' ' . ucfirst($customer['gender']);
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Email:</strong>
                                                        <div><?php echo h($customer['email']); ?></div>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Phone:</strong>
                                                        <div><?php echo h($customer['phone'] ?: 'N/A'); ?></div>
                                                    </div>
                                                    <?php if ($customer['has_car']): ?>
                                                    <div class="info-item">
                                                        <strong>Vehicle:</strong>
                                                        <div><span class="badge badge-success">Has vehicle</span></div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <strong>Room:</strong>
                                                        <div>
                                                            <?php 
                                                            $room_info = isset($pending_booking['room_info']) ? $pending_booking['room_info'] : 
                                                                        ($room['building_name'] . ' - ' . $room['room_number']);
                                                            echo h($room_info); 
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Building Type:</strong>
                                                        <div>
                                                            <span class="badge badge-<?php 
                                                                echo $room['building_type'] == 'vip' ? 'warning' : 
                                                                     ($room['building_type'] == 'premium' ? 'success' : 'info');
                                                            ?>">
                                                                <?php echo ucfirst($room['building_type']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="info-item">
                                                        <strong>Capacity:</strong>
                                                        <div><?php echo intval($room['capacity']); ?> person(s)</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="info-box mt-3">
                                            <h6><i class="fa fa-bed"></i> Room & Booking Details</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="info-item">
                                                        <strong>Check-in:</strong>
                                                        <div>
                                                            <?php echo date('F j, Y', strtotime($pending_checkin_date)); ?> 
                                                            <span class="text-info"><?php echo h($display_checkin_time); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-item">
                                                        <strong>Check-out:</strong>
                                                        <div>
                                                            <?php echo date('F j, Y', strtotime($pending_checkout_date)); ?> 
                                                            <span class="text-warning">12:00 (Standard check-out time)</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="info-item">
                                                        <strong>Nights:</strong>
                                                        <div><?php echo intval($pending_booking['nights']); ?> night(s)</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <strong>Price per night:</strong>
                                                        <div>
                                                            ₦<?php 
                                                            if (isset($pending_booking['room_price'])) {
                                                                echo number_format($pending_booking['room_price'], 2);
                                                            } else {
                                                                if ($pending_booking['nights'] > 0) {
                                                                    echo number_format($pending_booking['amount'] / $pending_booking['nights'], 2);
                                                                } else {
                                                                    echo number_format($room['price_per_night'], 2);
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <strong>Total Amount:</strong>
                                                        <div><strong class="text-success">₦<?php echo number_format($pending_booking['amount'], 2); ?></strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post">
                            <!-- ============================================================
                                 NO STAFF DROPDOWN - Staff is auto-assigned from session
                                 ============================================================ -->
                            
                            <!-- Hidden staff tracking - auto-assign from session -->
                            <?php if (is_staff_logged_in()): ?>
                            <input type="hidden" name="staff_id" value="<?php echo h(get_current_staff_id()); ?>">
                            <?php endif; ?>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <a href="admin_customers.php?booking_mode=1" class="btn btn-default btn-block">
                                        <i class="fa fa-arrow-left"></i> Back to Customer Selection
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="confirm_booking" class="btn btn-success btn-block btn-lg">
                                        <i class="fa fa-check-circle"></i> Confirm & Create Booking
                                    </button>
                                </div>
                            </div>
                        </form>
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
                            <h6><i class="fa fa-clock"></i> Same-Day Checkout Rule:</h6>
                            <p class="mb-2">Rooms become available for new bookings after 12:00 PM on checkout day.</p>
                            <ul class="mb-0">
                                <li>Previous guest must check out by 12:00 PM</li>
                                <li>New guest can check in same day after 12:00 PM</li>
                                <li>Allows for efficient room turnover</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-secondary">
                            <h6><i class="fa fa-lightbulb"></i> Quick Tips:</h6>
                            <ul class="mb-0">
                                <li>Check-in time will be <strong>current time</strong> for today's bookings</li>
                                <li>Check-out time is always <strong>12:00 PM</strong></li>
                                <li>This creates a confirmed booking with pending payment invoice</li>
                                <li>Guest can pay later at the front desk</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-secondary">
                            <h6><i class="fa fa-exclamation-triangle"></i> Common Issues:</h6>
                            <ul class="mb-0">
                                <li>Room unavailable if booked within last 5 minutes</li>
                                <li>Same-day booking requires immediate check-in</li>
                                <li>Future bookings use 14:00 check-in time</li>
                                <li>Contact support if you encounter errors</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fa fa-fast-forward"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="admin_index.php" class="btn btn-primary btn-block">
                                    <i class="fa fa-calendar-plus"></i> New Booking
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="admin_booking_management.php" class="btn btn-info btn-block">
                                    <i class="fa fa-list"></i> View Bookings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
}
.info-box h6 {
    color: #00ff99;
    border-bottom: 2px solid #00ff99;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-size: 16px;
}
.info-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #333;
}
.info-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.info-item strong {
    display: block;
    color: #aaa;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
}
.info-item div {
    color: #fff;
    font-size: 14px;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>