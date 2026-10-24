<?php
// admin_index.php (COMPACT VERSION WITH PROPER AVAILABILITY LOGIC)
// FIXED: Raw HTML in availability note

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

include __DIR__.'/header.php';

$current_datetime = date('Y-m-d H:i:s');
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// UPDATED QUERY WITH ACTUAL CHECK-IN LOGIC
$query = "SELECT 
    r.*, 
    b.name AS building_name, 
    b.building_type,
    b.base_price,
    -- Calculate final price
    CASE 
        WHEN r.use_system_price = 0 AND r.manual_price IS NOT NULL 
        THEN r.manual_price
        ELSE r.price_per_night
    END AS final_price,
    -- UPDATED OCCUPANCY STATUS WITH ACTUAL CHECK-IN LOGIC
    CASE 
        -- 1. Check if room is under maintenance
        WHEN r.status = 'maintenance' THEN 'unavailable'
        
        -- 2. Currently OCCUPIED (Guest has ACTUALLY checked in AND hasn't checked out)
        WHEN EXISTS (
            SELECT 1 FROM bookings bk 
            WHERE bk.room_id = r.id 
              AND bk.status IN ('confirmed', 'paid')
              AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
              AND bk.actual_checkin_datetime IS NOT NULL
        ) THEN 'occupied'
        
        -- 3. RESERVED: Has active booking but guest hasn't physically checked in yet
        WHEN EXISTS (
            SELECT 1 FROM bookings bk 
            WHERE bk.room_id = r.id 
              AND bk.status IN ('confirmed', 'paid')
              AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
              AND bk.actual_checkin_datetime IS NULL
        ) THEN 'reserved'
        
        -- 4. If no maintenance and no active bookings, then AVAILABLE
        ELSE 'available'
    END AS room_status";

// Add subqueries for additional information
$query .= ",
    -- Active booking ID
    (
        SELECT bk.id
        FROM bookings bk
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS active_booking_id,
    
    -- Guest information
    (
        SELECT CONCAT(LEFT(c.firstname, 1), '. ', c.lastname)
        FROM bookings bk
        JOIN customers c ON bk.customer_id = c.id
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS current_guest_short,
    
    -- Full guest name for tooltip
    (
        SELECT CONCAT(c.firstname, ' ', c.lastname)
        FROM bookings bk
        JOIN customers c ON bk.customer_id = c.id
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS current_guest_full,
    
    -- UPDATED: Simplified booking info - just dates, no check-in time
    (
        SELECT CONCAT(
            DATE_FORMAT(bk.checkin_date, '%b %e'),
            ' → ',
            DATE_FORMAT(bk.checkout_date, '%b %e')
        )
        FROM bookings bk
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS booking_info,
    
    -- Get booking status
    (
        SELECT bk.status
        FROM bookings bk
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS booking_status,
    
    -- Check if guest has actually checked in
    (
        SELECT bk.actual_checkin_datetime
        FROM bookings bk
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS actual_checkin_datetime,
    
    -- Check if guest is overdue
    (
        SELECT 
            CASE 
                WHEN bk.checkout_date < '$current_date' 
                     OR (bk.checkout_date = '$current_date' AND bk.checkout_time < '$current_time')
                THEN 1 
                ELSE 0 
            END
        FROM bookings bk
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND bk.actual_checkin_datetime IS NOT NULL
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
        ORDER BY bk.checkin_date DESC
        LIMIT 1
    ) AS is_overdue,
    
    -- Simple flag to check if paid
    (
        SELECT inv.paid
        FROM bookings bk
        LEFT JOIN invoices inv ON bk.invoice_id = inv.id
        WHERE bk.room_id = r.id 
          AND bk.status IN ('confirmed', 'paid')
          AND (bk.checkout_status IS NULL OR bk.checkout_status != 'checked_out')
          AND (
            bk.checkout_date > '$current_date'
            OR (bk.checkout_date = '$current_date' AND bk.checkout_time > '$current_time')
          )
        ORDER BY 
            CASE 
                WHEN bk.actual_checkin_datetime IS NOT NULL THEN 1 
                ELSE 2 
            END,
            bk.checkin_date ASC,
            bk.checkin_time ASC
        LIMIT 1
    ) AS is_paid
    
FROM rooms r
LEFT JOIN buildings b ON r.building_id = b.id
WHERE b.status = 1
ORDER BY 
    b.name, 
    LENGTH(r.room_number), 
    r.room_number";

$result = mysqli_query($conn, $query);

// Check for query errors
if (!$result) {
    die("Database error: " . mysqli_error($conn));
}

// Count rooms by status
$room_counts = array(
    'available' => 0,
    'occupied' => 0,
    'reserved' => 0,
    'maintenance' => 0,
    'total' => 0
);

// Count all rooms
$temp_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE b.status = 1");
if ($temp_result) {
    $total_row = mysqli_fetch_assoc($temp_result);
    $room_counts['total'] = $total_row['total'];
    mysqli_free_result($temp_result);
}
?>

<div class="container-fluid" style="margin-top:30px;">
    <!-- Compact Status Legend and Controls -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-hotel"></i> <strong>Room Dashboard</strong> 
                    <span class="text-muted">• <?php echo date('l, F d, Y H:i:s'); ?></span>
                </div>
                <div>
                    <span class="badge badge-info" id="roomCount">0</span> Rooms Displayed
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="row">
                <!-- Status Badges -->
                <div class="col-md-8">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-success filter-btn active" 
                                data-filter="available" onclick="filterRooms('available')">
                            <i class="fas fa-bed"></i> Available
                        </button>
                        <button type="button" class="btn btn-outline-danger filter-btn" 
                                data-filter="occupied" onclick="filterRooms('occupied')">
                            <i class="fas fa-user-check"></i> Occupied
                        </button>
                        <button type="button" class="btn btn-outline-primary filter-btn" 
                                data-filter="all" onclick="filterRooms('all')">
                            <i class="fas fa-th"></i> All Rooms
                        </button>
                        <button type="button" class="btn btn-outline-warning filter-btn" 
                                data-filter="reserved" onclick="filterRooms('reserved')">
                            <i class="fas fa-calendar-check"></i> Reserved
                        </button>
                        <button type="button" class="btn btn-outline-secondary filter-btn" 
                                data-filter="maintenance" onclick="filterRooms('maintenance')">
                            <i class="fas fa-tools"></i> Maintenance
                        </button>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-md-4 text-right">
                    <div class="btn-group btn-group-sm">
                        <a href="admin_reserve.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> New Booking
                        </a>
                        <a href="admin_checkin.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Check-in
                        </a>
                        <a href="admin_checkout_management.php" class="btn btn-warning">
                            <i class="fas fa-sign-out-alt"></i> Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COMPACT ROOM GRID -->
    <div class="room-grid-high-density" id="roomContainer">
        <?php 
        while ($room = mysqli_fetch_assoc($result)): 
            $status = $room['room_status'];
            
            // Count rooms by status
            switch($status) {
                case 'available': 
                    $status_class = 'available';
                    $status_text = 'Available';
                    $room_counts['available']++;
                    break;
                case 'occupied': 
                    $status_class = 'occupied';
                    $status_text = 'Occupied';
                    $room_counts['occupied']++;
                    break;
                case 'reserved': 
                    $status_class = 'reserved';
                    $status_text = 'Reserved';
                    $room_counts['reserved']++;
                    break;
                case 'unavailable': 
                    $status_class = 'maintenance';
                    $status_text = 'Maintenance';
                    $room_counts['maintenance']++;
                    break;
            }
            
            // Determine display price
            if (isset($room['final_price']) && $room['final_price'] !== null) {
                $display_price = $room['final_price'];
            } else {
                $display_price = $room['price_per_night'];
            }
            
            // Format price
            $formatted_price = '₦' . number_format((float)$display_price, 0);
            
            // Guest information
            $guest_display = '';
            $guest_full = '';
            $guest_tooltip = '';
            
            if (($status == 'occupied' || $status == 'reserved') && isset($room['current_guest_short']) && !empty($room['current_guest_short'])) {
                $guest_full = isset($room['current_guest_full']) ? htmlspecialchars($room['current_guest_full'], ENT_QUOTES, 'UTF-8') : '';
                $guest_display = htmlspecialchars($room['current_guest_short'], ENT_QUOTES, 'UTF-8');
                if (strlen($guest_display) > 15) {
                    $guest_display = substr($guest_display, 0, 12) . '...';
                }
                
                // Build enhanced tooltip
                $guest_tooltip = $guest_full;
                
                // Add booking info if available
                if (!empty($room['booking_info'])) {
                    $guest_tooltip .= "\n" . htmlspecialchars($room['booking_info'], ENT_QUOTES, 'UTF-8');
                }
                
                // Add specific check-in time for occupied rooms
                if ($status == 'occupied' && !empty($room['actual_checkin_datetime'])) {
                    $checkin_time = date('M d, Y h:i A', strtotime($room['actual_checkin_datetime']));
                    $guest_tooltip .= "\nChecked in: " . $checkin_time;
                }
                
                // Add OVERDUE warning if applicable
                if (isset($room['is_overdue']) && $room['is_overdue'] == 1) {
                    $guest_tooltip .= "\nOVERDUE";
                }
            }
            
            // Check if room is overdue (for styling)
            $is_overdue = isset($room['is_overdue']) && $room['is_overdue'] == 1;
            if ($is_overdue) {
                $status_class .= ' overdue';
            }
            
            // Check availability for short stays on reserved rooms
            $availability_note = '';
            $available_tonight = false;
            $available_two_nights = false;
            
            if ($status == 'reserved' && isset($room['is_paid']) && $room['is_paid'] == 1) {
                $today = date('Y-m-d');
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                $day_after = date('Y-m-d', strtotime('+2 days'));
                
                $available_tonight = is_room_available_same_day_checkout($conn, $room['id'], $today, $tomorrow);
                $available_two_nights = is_room_available_same_day_checkout($conn, $room['id'], $today, $day_after);
                
                if ($available_tonight || $available_two_nights) {
                    $availability_note = '';
                    
                    if ($available_tonight && $available_two_nights) {
                        $availability_note .= '<i class="fas fa-info-circle"></i> 1-2 nights';
                    } elseif ($available_tonight) {
                        $availability_note .= '<i class="fas fa-moon"></i> Tonight';
                    } elseif ($available_two_nights) {
                        $availability_note .= '<i class="fas fa-bed"></i> 2 nights';
                    }
                }
            }
        ?>
        <div class="compact-room-card <?php echo h($status_class); ?>" 
             data-status="<?php echo h($status); ?>"
             data-building="<?php echo htmlspecialchars($room['building_name'], ENT_QUOTES, 'UTF-8'); ?>"
             data-room-id="<?php echo h($room['id']); ?>"
             <?php if ($is_overdue): ?>
             style="border: 3px solid #ff0000; box-shadow: 0 0 15px rgba(255, 0, 0, 0.3);"
             <?php endif; ?>>
            
            <!-- Status Badge - Top Right -->
            <div class="compact-room-status <?php echo h($status_class); ?>">
                <?php echo h($status_text); ?>
                <?php if ($is_overdue): ?>
                <span class="overdue-badge" title="Checkout overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Pax/Capacity Indicator - Top Left -->
            <div class="compact-room-pax" title="Capacity">
                <i class="fas fa-users"></i> <?php echo intval($room['capacity']); ?> pax
            </div>
            
            <!-- Room Number - Centered -->
            <div class="compact-room-number">
                <?php echo htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($is_overdue): ?>
                <div class="overdue-flash" style="color: #ff0000; font-size: 10px; animation: blinker 1s linear infinite;">
                    OVERDUE
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Building -->
            <div class="compact-room-building">
                <?php echo htmlspecialchars($room['building_name'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            
            <!-- Price -->
            <div class="compact-room-price">
                <?php echo h($formatted_price); ?>/night
            </div>
            
            <!-- Guest Info -->
            <?php if (!empty($guest_display)): ?>
                <div class="compact-room-guest" title="<?php echo h($guest_tooltip); ?>">
                    <i class="fas fa-user"></i> <?php echo h($guest_display); ?>
                    <?php if ($is_overdue): ?>
                    <span class="overdue-indicator" title="Checkout overdue">
                        <i class="fas fa-clock" style="color: #ff0000;"></i>
                    </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Availability Note - FIXED: Removed h() to render HTML -->
            <?php if (!empty($availability_note)): ?>
                <div class="compact-availability-note">
                    <?php echo $availability_note; ?>
                </div>
            <?php endif; ?>
            
            <!-- ACTION BUTTONS - SIDE BY SIDE -->
            <div class="compact-room-actions">
                <?php if ($status == 'available'): ?>
                    <!-- AVAILABLE ROOMS: Reserve -->
                    <a href="admin_reserve.php?room_id=<?php echo h($room['id']); ?>&roomno=<?php echo urlencode($room['room_number']); ?>" 
                       class="btn btn-success btn-xs" title="Reserve">
                        <i class="fas fa-calendar-plus"></i>
                    </a>
                    <a href="admin_manage_rooms.php?search=<?php echo urlencode($room['room_number']); ?>" 
                       class="btn btn-outline-light btn-xs" title="Manage">
                        <i class="fas fa-cog"></i>
                    </a>

                <?php elseif ($status == 'reserved'): ?>
                    <!-- RESERVED ROOMS: Only View Booking -->
                    <?php if (isset($room['active_booking_id']) && !empty($room['active_booking_id'])): ?>
                        <a href="admin_view_booking.php?booking_id=<?php echo h($room['active_booking_id']); ?>" 
                           class="btn btn-info btn-xs" title="View Booking">
                            <i class="fas fa-eye"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Short stay availability buttons -->
                    <?php if (!empty($availability_note)): ?>
                        <?php if ($available_tonight): ?>
                            <a href="admin_reserve.php?room_id=<?php echo h($room['id']); ?>&roomno=<?php echo urlencode($room['room_number']); ?>&quick_book=tonight" 
                               class="btn btn-success btn-xs" title="Book Tonight">
                                <i class="fas fa-moon"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($available_two_nights): ?>
                            <a href="admin_reserve.php?room_id=<?php echo h($room['id']); ?>&roomno=<?php echo urlencode($room['room_number']); ?>&quick_book=two_nights" 
                               class="btn btn-danger btn-xs" title="Book 2 Nights">
                                <i class="fas fa-bed"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php elseif ($status == 'occupied'): ?>
                    <!-- OCCUPIED ROOMS: Only View Guest -->
                    <?php if (isset($room['active_booking_id']) && !empty($room['active_booking_id'])): ?>
                        <a href="admin_view_booking.php?booking_id=<?php echo h($room['active_booking_id']); ?>" 
                           class="btn btn-info btn-xs" title="View Guest">
                            <i class="fas fa-user"></i>
                        </a>
                        <?php if ($is_overdue): ?>
                            <a href="admin_checkout.php?booking_id=<?php echo h($room['active_booking_id']); ?>" 
                               class="btn btn-danger btn-xs" title="Checkout Overdue Guest" style="background-color: #ff0000; border-color: #ff0000;">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        <?php else: ?>
                            <a href="admin_checkout.php?booking_id=<?php echo h($room['active_booking_id']); ?>" 
                               class="btn btn-warning btn-xs" title="Checkout Guest">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php elseif ($status == 'unavailable'): ?>
                    <!-- MAINTENANCE ROOMS: Mark Available -->
                    <a href="admin_manage_rooms.php?id=<?php echo h($room['id']); ?>&action=mark_available" 
                       class="btn btn-warning btn-xs" title="Mark Available">
                        <i class="fas fa-check"></i>
                    </a>
                    <a href="admin_manage_rooms.php?search=<?php echo urlencode($room['room_number']); ?>" 
                       class="btn btn-outline-light btn-xs" title="Manage">
                        <i class="fas fa-cog"></i>
                    </a>

                <?php else: ?>
                    <!-- DEFAULT: Manage -->
                    <a href="admin_manage_rooms.php?search=<?php echo urlencode($room['room_number']); ?>" 
                       class="btn btn-outline-light btn-xs" title="Manage">
                        <i class="fas fa-cog"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if ($room_counts['total'] == 0): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No rooms found in the system.
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Room Statistics Footer -->
    <div class="card mt-3">
        <div class="card-body p-2">
            <div class="row text-center">
                <div class="col-6 col-md-3">
                    <div class="h4 text-success"><?php echo h($room_counts['available']); ?></div>
                    <small class="text-muted">Available</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="h4 text-danger"><?php echo h($room_counts['occupied']); ?></div>
                    <small class="text-muted">Occupied</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="h4 text-warning"><?php echo h($room_counts['reserved']); ?></div>
                    <small class="text-muted">Reserved</small>
                </div>
                <div class="col-6 col-md-3">
                    <div class="h4 text-secondary"><?php echo h($room_counts['maintenance']); ?></div>
                    <small class="text-muted">Maintenance</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Blinking animation for overdue rooms */
@keyframes blinker {
    0% { opacity: 1; }
    50% { opacity: 0.3; }
    100% { opacity: 1; }
}

/* Overdue room styling */
.compact-room-card.overdue {
    position: relative;
}

.compact-room-card.overdue::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border: 2px solid rgba(255, 0, 0, 0.5);
    border-radius: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 0.3; }
    50% { opacity: 1; }
    100% { opacity: 0.3; }
}

.overdue-badge {
    display: inline-block;
    margin-left: 5px;
    color: #ff0000;
    animation: blinker 1s infinite;
}

.overdue-flash {
    font-weight: bold;
    margin-top: 2px;
}

/* Ensure occupied rooms with overdue status show red border */
.compact-room-card.occupied.overdue {
    border-color: #ff0000 !important;
}
</style>

<script>
// Filter rooms function
function filterRooms(filter) {
    const cards = document.querySelectorAll('.compact-room-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update button states
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-filter') === filter) {
            btn.classList.add('active');
        }
    });
    
    // Count visible rooms
    let visibleCount = 0;
    
    // Filter rooms
    cards.forEach(card => {
        const status = card.getAttribute('data-status');
        let shouldShow = false;
        
        switch(filter) {
            case 'all':
                shouldShow = true;
                break;
            case 'available':
                shouldShow = status === 'available';
                break;
            case 'occupied':
                shouldShow = status === 'occupied';
                break;
            case 'reserved':
                shouldShow = status === 'reserved';
                break;
            case 'maintenance':
                shouldShow = status === 'unavailable';
                break;
            default:
                shouldShow = true;
        }
        
        if (shouldShow) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update room count display
    document.getElementById('roomCount').textContent = visibleCount;
    
    // Show message if no rooms match filter
    const container = document.getElementById('roomContainer');
    let noResultsMsg = container.querySelector('.no-results-message');
    
    if (visibleCount === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results-message';
            noResultsMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 50px;';
            
            let filterText = 'All';
            switch(filter) {
                case 'all': filterText = 'All Rooms'; break;
                case 'available': filterText = 'Available'; break;
                case 'occupied': filterText = 'Occupied'; break;
                case 'reserved': filterText = 'Reserved'; break;
                case 'maintenance': filterText = 'Maintenance'; break;
            }
            
            noResultsMsg.innerHTML = '<div class="alert alert-warning text-center">No rooms found with "' + filterText + '" status.</div>';
            container.appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Set default filter on page load
document.addEventListener('DOMContentLoaded', function() {
    // Show Available + Occupied rooms by default (most useful for reception)
    const cards = document.querySelectorAll('.compact-room-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const status = card.getAttribute('data-status');
        if (status === 'available' || status === 'occupied') {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update room count
    document.getElementById('roomCount').textContent = visibleCount;
    
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-filter') === 'available') {
            btn.classList.add('active');
        }
    });
    
    // Auto-refresh page every 5 minutes for room status updates
    setTimeout(function() {
        location.reload();
    }, 300000); // 5 minutes
});
</script>

<?php 
// Free result
if (isset($result)) {
    mysqli_free_result($result);
}

include __DIR__.'/footer.php'; 
?>