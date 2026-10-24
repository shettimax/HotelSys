<?php
// admin_reserve.php - RESTORED TO MATCH OLD STRUCTURE
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$roomno = isset($_GET['roomno']) ? $_GET['roomno'] : '';

if (!$room_id) {
    include __DIR__ . '/header.php';
    echo "<div class='container-fluid main-container'><div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No room selected</div></div>"; 
    include __DIR__ . '/footer.php';
    exit;
}

// Get room details
$room_result = mysqli_query($conn, "
    SELECT r.*, b.name as building_name, b.building_type, b.base_price 
    FROM rooms r 
    JOIN buildings b ON r.building_id = b.id 
    WHERE r.id = $room_id
");

if (!$room_result) {
    include __DIR__ . '/header.php';
    echo "<div class='container-fluid main-container'><div class='alert alert-danger'><i class='fas fa-database'></i> Database error: " . mysqli_error($conn) . "</div></div>";
    include __DIR__ . '/footer.php';
    exit;
}

$room = mysqli_fetch_assoc($room_result);

if (!$room) { 
    include __DIR__ . '/header.php';
    echo "<div class='container-fluid main-container'><div class='alert alert-danger'><i class='fas fa-bed'></i> Room not found</div></div>"; 
    include __DIR__ . '/footer.php'; 
    exit;
}

// Check if room is available (maintenance status)
if ($room['status'] != 'available') {
    include __DIR__ . '/header.php';
    echo "<div class='container-fluid main-container'><div class='alert alert-warning'><i class='fas fa-tools'></i> Room is not currently habitable. Please select another room.</div></div>";
    include __DIR__ . '/footer.php'; 
    exit;
}

// ============================================================
// FIXED: Check if room is currently OCCUPIED 
// ============================================================
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$occupiedQuery = "SELECT 1 FROM bookings 
                  WHERE room_id = $room_id 
                  AND status IN ('confirmed', 'paid') 
                  AND checkout_status != 'checked_out'
                  AND checkin_date <= '$current_date'
                  AND (
                    checkout_date > '$current_date'
                    OR (checkout_date = '$current_date' AND checkout_time > '$current_time')
                  )";
                  
$occupiedResult = mysqli_query($conn, $occupiedQuery);
if (!$occupiedResult) {
    die("Database error: " . mysqli_error($conn));
}

if (mysqli_num_rows($occupiedResult) > 0) {
    include __DIR__ . '/header.php';
    echo "<div class='container-fluid main-container'><div class='alert alert-warning'><i class='fas fa-user-check'></i> Room is currently occupied. Please select another room.</div></div>";
    include __DIR__ . '/footer.php'; 
    exit;
}

// Handle quick booking parameters
$quick_book = isset($_GET['quick_book']) ? $_GET['quick_book'] : '';
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$day_after = date('Y-m-d', strtotime('+2 days'));

// Set default dates based on quick booking
if ($quick_book == 'tonight') {
    $default_checkin = $today;
    $default_checkout = $tomorrow;
} elseif ($quick_book == 'two_nights') {
    $default_checkin = $today;
    $default_checkout = $day_after;
} else {
    $default_checkin = $tomorrow;
    $default_checkout = $day_after;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkin_date = mysqli_real_escape_string($conn, $_POST['checkin_date']);
    $checkout_date = mysqli_real_escape_string($conn, $_POST['checkout_date']);
    
    if (!$checkin_date || !$checkout_date) { 
        $error = 'Pick both check-in and check-out dates'; 
    } else {
        $checkin = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        $interval = $checkin->diff($checkout);
        $nights = $interval->days;
        
        if ($nights <= 0) {
            $error = 'Check-out must be after check-in';
        } else {
            // ADAPTED: Use the new same-day checkout availability check
            if (!is_room_available_same_day_checkout($conn, $room_id, $checkin_date, $checkout_date)) {
                $error = 'Room not available for selected dates. Someone is checking out after 12:00 PM on your check-in date.';
            } 
            // Also check with existing datetime function for precision
            elseif (!room_is_available_datetime($conn, $room_id, $checkin_date . ' 14:00:00', $checkout_date . ' 12:00:00')) {
                $error = 'Room not available for selected dates. Please choose different dates.';
            } else {
                // Calculate check-in time: if checking in today, use current time, otherwise 14:00
                $checkin_time = ($checkin_date == $today) ? date('H:i:s') : '14:00:00';
                
                // Store with BOTH old and new keys for compatibility
                $_SESSION['pending_booking'] = array(
                    'room_id' => $room_id,
                    'room_info' => $roomno ?: ($room['building_name'] . ' - ' . $room['room_number']),
                    
                    // OLD KEYS (what admin_customers.php expects)
                    'checkin' => $checkin_date,
                    'checkout' => $checkout_date,
                    
                    // NEW KEYS (extra data)
                    'checkin_date' => $checkin_date,
                    'checkin_time' => $checkin_time,
                    'checkout_date' => $checkout_date,
                    'checkout_time' => '12:00:00',
                    'nights' => $nights,
                    'amount' => $nights * $room['price_per_night'],
                    'room_price' => $room['price_per_night'],
                    'room_capacity' => $room['capacity'],
                    'building_type' => $room['building_type']
                );
                
                header('Location: admin_customers.php?booking_mode=1'); 
                exit;
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
                    <div class="card-header">
                        <h4><i class="fas fa-calendar-plus"></i> Select Booking Dates</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
                            <p class="mb-0"><?php echo $error; ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($quick_book): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-bolt"></i> Quick Booking Active</h5>
                            <p class="mb-0">Dates pre-filled for your <?php echo $quick_book == 'tonight' ? 'tonight' : '2-night'; ?> stay. You can adjust if needed.</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Combined Room Info and Booking Summary -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <!-- Room Information (Left Column) -->
                                    <div class="col-md-6 room-info-column">
                                        <h6 class="section-title"><i class="fas fa-info-circle"></i> Room Information</h6>
                                        <div class="room-info-item">
                                            <strong>Room:</strong>
                                            <div><?php echo h($roomno ?: $room['building_name'] . ' - ' . $room['room_number']); ?></div>
                                        </div>
                                        <div class="room-info-item">
                                            <strong>Building:</strong>
                                            <div><?php echo h($room['building_name']); ?></div>
                                        </div>
                                        <div class="room-info-item double-item">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Type:</strong>
                                                    <div>
                                                        <span class="badge badge-<?php 
                                                            echo $room['building_type'] == 'vip' ? 'warning' : 
                                                                 ($room['building_type'] == 'premium' ? 'success' : 'info');
                                                        ?>">
                                                            <?php echo ucfirst($room['building_type']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Capacity:</strong>
                                                    <div><?php echo intval($room['capacity']); ?> person(s)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Booking Summary (Right Column) -->
                                    <div class="col-md-6 booking-summary-column">
                                        <h6 class="section-title"><i class="fas fa-clock"></i> Booking Summary</h6>
                                        <div id="bookingSummary">
                                            <div class="booking-summary-item">
                                                <strong>Check-in:</strong>
                                                <div id="summaryCheckin"><?php echo date('F j, Y', strtotime($default_checkin)); ?> <span class="text-info">14:00</span></div>
                                            </div>
                                            <div class="booking-summary-item">
                                                <strong>Check-out:</strong>
                                                <div id="summaryCheckout"><?php echo date('F j, Y', strtotime($default_checkout)); ?> <span class="text-warning">12:00</span></div>
                                            </div>
                                            <div class="booking-summary-item double-item">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Nights:</strong>
                                                        <div id="summaryNights"><?php echo ($default_checkout == $tomorrow) ? 1 : (($default_checkout == $day_after) ? 2 : 1); ?></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Price per night:</strong>
                                                        <div><strong class="text-success">₦<?php echo number_format($room['price_per_night'], 2); ?></strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="booking-summary-item">
                                                <strong>Total Amount:</strong>
                                                <div id="summaryTotal" class="text-success">
                                                    <?php 
                                                    $default_nights = ($default_checkout == $tomorrow) ? 1 : (($default_checkout == $day_after) ? 2 : 1);
                                                    echo '₦' . number_format($default_nights * $room['price_per_night'], 2);
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post">
                            <div class="card">
                                <div class="card-header bg-dark">
                                    <h6><i class="fas fa-calendar-alt"></i> Select Dates</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-sign-in-alt"></i> Check-in Date</label>
                                                <input type="date" name="checkin_date" class="form-control" 
                                                       value="<?php echo h($default_checkin); ?>" 
                                                       min="<?php echo date('Y-m-d'); ?>" 
                                                       required>
                                                <small class="text-muted">Check-in: 14:00 (or current time for today)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-sign-out-alt"></i> Check-out Date</label>
                                                <input type="date" name="checkout_date" class="form-control" 
                                                       value="<?php echo h($default_checkout); ?>" 
                                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                                       required>
                                                <small class="text-muted">Check-out: Always 12:00 PM</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <a href="admin_index.php" class="btn btn-secondary btn-block">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-block btn-lg">
                                        <i class="fas fa-arrow-right"></i> Next: Select Customer
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
                        <h5><i class="fas fa-question-circle"></i> Help & Tips</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-circle"></i> Important Information:</h6>
                            <ul class="mb-0">
                                <li><strong>Same-Day Checkout Rules:</strong></li>
                                <li>Check-out time is always <strong>12:00 PM</strong></li>
                                <li>Rooms become available for booking after 12:00 PM on checkout day</li>
                                <li>Check-in time is <strong>14:00</strong> for future dates, current time for today</li>
                                <li>You can book a room even if it has a future reservation, as long as your stay ends before their check-in</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-clock"></i> Time Guidelines:</h6>
                            <ul class="mb-0">
                                <li>Standard check-in: <strong>14:00 (2:00 PM)</strong></li>
                                <li>Standard check-out: <strong>12:00 (12:00 PM)</strong></li>
                                <li>Today's bookings: Check-in at current time</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success">
                            <h6><i class="fas fa-bed"></i> Room Availability:</h6>
                            <ul class="mb-0">
                                <li>Rooms free up after 12:00 PM on checkout day</li>
                                <li>Same-day turnover possible</li>
                                <li>Future reservations don't block same-day bookings</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-primary">
                            <h6><i class="fas fa-bolt"></i> Quick Book Options:</h6>
                            <ul class="mb-0">
                                <li><strong>Tonight:</strong> Check in today, out tomorrow</li>
                                <li><strong>2 Nights:</strong> Check in today, out day after</li>
                                <li><strong>Custom:</strong> Choose any dates</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-fast-forward"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <a href="admin_manage_rooms.php" class="btn btn-info btn-block">
                                    <i class="fas fa-door-closed"></i> All Rooms
                                </a>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="admin_booking_management.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-list"></i> Bookings
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
function calculateNights() {
    var checkin = document.querySelector('input[name="checkin_date"]').value;
    var checkout = document.querySelector('input[name="checkout_date"]').value;
    
    if (!checkin || !checkout) return;
    
    var checkinDate = new Date(checkin);
    var checkoutDate = new Date(checkout);
    
    // Calculate difference in days
    var timeDiff = checkoutDate.getTime() - checkinDate.getTime();
    var nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    var pricePerNight = <?php echo h($room['price_per_night']); ?>;
    var total = nights * pricePerNight;
    
    // Update summary display
    document.getElementById('summaryCheckin').innerHTML = formatDate(checkin) + ' <span class="text-info">' + (checkin === '<?php echo h($today); ?>' ? 'Current Time' : '14:00') + '</span>';
    document.getElementById('summaryCheckout').innerHTML = formatDate(checkout) + ' <span class="text-warning">12:00</span>';
    document.getElementById('summaryNights').innerHTML = nights;
    document.getElementById('summaryTotal').innerHTML = '₦' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatDate(dateStr) {
    var date = new Date(dateStr);
    var options = { month: 'long', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Add event listeners
document.querySelector('input[name="checkin_date"]').addEventListener('change', calculateNights);
document.querySelector('input[name="checkout_date"]').addEventListener('change', calculateNights);

// Initialize on page load
calculateNights();
</script>

<?php include __DIR__ . '/footer.php'; ?>