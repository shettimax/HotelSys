<?php
// admin_manage_rooms.php - UPDATED: INLINE ADD AND EDIT FORMS
// WITH SweetAlert2 CONFIRMATION - FIXED: Single trigger

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Form data for repopulation
$form_data = array(
    'building_id' => '',
    'room_number' => '',
    'capacity' => 1,
    'use_system_price' => 1,
    'manual_price' => '',
    'status' => 'available'
);

$show_add_form = isset($_GET['add']);
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$msg = '';
$msg_type = '';

// Check for URL alert parameters
if (isset($_GET['alert'])) {
    $msg = urldecode($_GET['alert']);
    $msg_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Handle room addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $building_id = (int)$_POST['building_id'];
    $room_number = mysqli_real_escape_string($conn, trim($_POST['room_number']));
    $capacity = (int)$_POST['capacity'];
    $use_system_price = isset($_POST['use_system_price']) ? 1 : 0;
    $manual_price = isset($_POST['manual_price']) ? (float)$_POST['manual_price'] : 0;

    // Repopulate form
    $form_data = array(
        'building_id' => $building_id,
        'room_number' => htmlspecialchars($room_number),
        'capacity' => $capacity,
        'use_system_price' => $use_system_price,
        'manual_price' => $manual_price,
        'status' => 'available'
    );

    // Validation
    $errors = array();

    if ($building_id <= 0) {
        $errors[] = "Please select a building";
    }
    if (empty($room_number)) {
        $errors[] = "Room number is required";
    }
    if ($capacity < 1) {
        $errors[] = "Capacity must be at least 1";
    }
    if (!$use_system_price && $manual_price <= 0) {
        $errors[] = "Manual price must be greater than 0 when not using system pricing";
    }

    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
        $msg_type = 'error';
        $show_add_form = true;
    } else {
        // Get building base price for system pricing
        $building_result = mysqli_query($conn, "SELECT base_price FROM buildings WHERE id = $building_id");
        $building = mysqli_fetch_assoc($building_result);
        
        // Calculate final price
        if ($use_system_price) {
            $final_price = $building['base_price'];
            $manual_price = 0;
        } else {
            $final_price = $manual_price;
        }
        
        $current_time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO rooms (building_id, room_number, capacity, price_per_night, use_system_price, manual_price, status, created_at) 
                VALUES ($building_id, '$room_number', $capacity, $final_price, $use_system_price, $manual_price, 'available', '$current_time')";
        
        if (mysqli_query($conn, $sql)) {
            header('Location: admin_manage_rooms.php?alert=' . urlencode('Room added successfully! Price set to ₦' . number_format($final_price, 2)) . '&type=success');
            exit;
        } else {
            $msg = "Error adding room: " . mysqli_error($conn);
            $msg_type = 'error';
            $show_add_form = true;
        }
    }
}

// Handle room edit (INLINE FORM) - UPDATED TO MATCH BUILDINGS FORMAT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_room'])) {
    $room_id = (int)$_POST['room_id'];
    $room_number = mysqli_real_escape_string($conn, trim($_POST['room_number']));
    $capacity = (int)$_POST['capacity'];
    $use_system_price = isset($_POST['use_system_price']) ? 1 : 0;
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate
    if ($capacity < 1) {
        $msg = "Capacity must be at least 1!";
        $msg_type = 'error';
        $edit_id = $room_id; // Stay in edit mode
    } else {
        // Get current room info
        $room_result = mysqli_query($conn, "SELECT building_id, manual_price, use_system_price as current_use_system FROM rooms WHERE id = $room_id");
        $room = mysqli_fetch_assoc($room_result);
        $building_id = $room['building_id'];
        
        // Handle pricing
        if ($use_system_price) {
            // Use system pricing - get building base price
            $building_result = mysqli_query($conn, "SELECT base_price FROM buildings WHERE id = $building_id");
            $building = mysqli_fetch_assoc($building_result);
            $final_price = $building['base_price'];
            $manual_price = 0;
        } else {
            // Use manual pricing
            $manual_price = isset($_POST['manual_price']) ? (float)$_POST['manual_price'] : 0;
            if ($manual_price <= 0) {
                $msg = "Manual price must be greater than 0!";
                $msg_type = 'error';
                $edit_id = $room_id; // Stay in edit mode
            } else {
                $final_price = $manual_price;
            }
        }
        
        if (!$msg) { // Only proceed if no errors
            // Update the room - SET created_at TO CURRENT TIME like buildings do
            $current_time = date('Y-m-d H:i:s');
            $sql = "UPDATE rooms SET 
                    room_number = '$room_number',
                    capacity = $capacity,
                    price_per_night = $final_price,
                    use_system_price = $use_system_price,
                    manual_price = $manual_price,
                    status = '$status',
                    created_at = '$current_time'
                    WHERE id = $room_id";
            
            if (mysqli_query($conn, $sql)) {
                $alert_msg = "Room updated successfully! Last modified: " . date('Y-m-d H:i:s');
                header('Location: admin_manage_rooms.php?alert=' . urlencode($alert_msg) . '&type=success');
                exit;
            } else {
                $msg = "Error updating room: " . mysqli_error($conn);
                $msg_type = 'error';
                $edit_id = $room_id; // Stay in edit mode
            }
        }
    }
}

// Handle room deletion
if (isset($_GET['delete'])) {
    $room_id = (int)$_GET['delete'];
    
    // Check if room has any bookings
    $check_sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE room_id = $room_id";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['booking_count'] > 0) {
        header('Location: admin_manage_rooms.php?alert=' . urlencode('Cannot delete room with existing bookings!') . '&type=error');
        exit;
    } else {
        $room_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id = $room_id"));
        $room_number = $room_info['room_number'];
        
        $sql = "DELETE FROM rooms WHERE id = $room_id";
        if (mysqli_query($conn, $sql)) {
            header('Location: admin_manage_rooms.php?alert=' . urlencode('Room ' . $room_number . ' deleted successfully!') . '&type=success');
            exit;
        } else {
            header('Location: admin_manage_rooms.php?alert=' . urlencode('Error deleting room: ' . mysqli_error($conn)) . '&type=error');
            exit;
        }
    }
}

// Fetch room data for editing
$editing_room = null;
if ($edit_id > 0) {
    $edit_result = mysqli_query($conn, "
        SELECT r.*, b.name as building_name, b.building_type, b.base_price
        FROM rooms r 
        JOIN buildings b ON r.building_id = b.id 
        WHERE r.id = $edit_id
    ");
    if (mysqli_num_rows($edit_result) > 0) {
        $editing_room = mysqli_fetch_assoc($edit_result);
    }
}

// Build search conditions
$where_conditions = array("1=1");
if (!empty($search)) {
    $where_conditions[] = "(b.name LIKE '%$search%' 
        OR r.room_number LIKE '%$search%' 
        OR r.status LIKE '%$search%'
        OR b.building_type LIKE '%$search%')";
}
$where_clause = implode(' AND ', $where_conditions);

// Fetch rooms data with search
$sql = "
    SELECT r.*, b.name as building_name, b.building_type, b.base_price
    FROM rooms r 
    JOIN buildings b ON r.building_id = b.id 
    WHERE $where_clause
    ORDER BY b.name, r.room_number
    LIMIT $offset, $per_page
";
$result = mysqli_query($conn, $sql);
$rooms = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get booking counts for each room to determine if deletable
$room_ids = array_column($rooms, 'id');
$booking_counts = array();
if (!empty($room_ids)) {
    $id_list = implode(',', $room_ids);
    $booking_query = mysqli_query($conn, "SELECT room_id, COUNT(*) as booking_count FROM bookings WHERE room_id IN ($id_list) GROUP BY room_id");
    while ($row = mysqli_fetch_assoc($booking_query)) {
        $booking_counts[$row['room_id']] = $row['booking_count'];
    }
}

// Get total count for pagination
$total_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE $where_clause");
$total_count_row = mysqli_fetch_assoc($total_count_result);
$total_count = $total_count_row['count'];
$total_pages = ceil($total_count / $per_page);

// Get summary counts
$summary_sql = "
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count,
        SUM(CASE WHEN r.status = 'booked' THEN 1 ELSE 0 END) as booked_count,
        SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
        SUM(r.price_per_night) as total_value
    FROM rooms r
    JOIN buildings b ON r.building_id = b.id
    WHERE $where_clause
";
$summary_result = mysqli_query($conn, $summary_sql);
$summary = mysqli_fetch_assoc($summary_result);

// Fetch buildings for dropdown
$buildings_result = mysqli_query($conn, "SELECT * FROM buildings ORDER BY name");
$buildings = mysqli_fetch_all($buildings_result, MYSQLI_ASSOC);

// Function to get status badge
function get_status_badge($status) {
    switch($status) {
        case 'available': return '<span class="badge badge-success">Available</span>';
        case 'booked': return '<span class="badge badge-warning">Booked</span>';
        case 'maintenance': return '<span class="badge badge-danger">Maintenance</span>';
        default: return '<span class="badge badge-secondary">' . $status . '</span>';
    }
}

// Function to get pricing method badge
function get_pricing_method_badge($use_system_price, $building_type) {
    if ($use_system_price) {
        return '<span class="badge badge-info">System (' . $building_type . ')</span>';
    } else {
        return '<span class="badge badge-secondary">Manual</span>';
    }
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <?php if ($msg): ?>
        <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible" 
             style="border:2px solid <?php echo $msg_type === 'success' ? '#00ff99' : '#d00'; ?>;background-color:<?php echo $msg_type === 'success' ? '#001a0000' : '#1a0000'; ?>;color:<?php echo $msg_type === 'success' ? '#00ff99' : '#ff6666'; ?>;font-family:monospace;">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4>
                <span class="glyphicon glyphicon-<?php echo $msg_type === 'success' ? 'ok-circle' : 'exclamation-sign'; ?>"></span>
                <?php echo $msg_type === 'success' ? 'Success!' : 'Error!'; ?>
            </h4>
            <div class="terminal-feed">
                <?php echo $msg; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- INLINE ADD ROOM FORM -->
        <?php if ($show_add_form && !$edit_id): ?>
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-plus"></i> Add New Room</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="add_room" value="1">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label">Building *</label>
                                <select name="building_id" class="form-control hacker-input" required onchange="updateSystemPrice()">
                                    <option value="">Select Building</option>
                                    <?php foreach ($buildings as $bldg): ?>
                                    <option value="<?php echo h($bldg['id']); ?>" data-baseprice="<?php echo number_format($bldg['base_price'], 2, '.', ''); ?>"
                                        <?php echo $form_data['building_id'] == $bldg['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($bldg['name']); ?> - ₦<?php echo number_format($bldg['base_price'], 2); ?> (<?php echo h($bldg['building_type']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label">Room Number *</label>
                                <input type="text" name="room_number" class="form-control hacker-input" required value="<?php echo h($form_data['room_number']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label">Capacity *</label>
                                <input type="number" name="capacity" class="form-control hacker-input" min="1" required value="<?php echo h($form_data['capacity']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="hacker-label">Pricing Method</label>
                                <div class="form-check">
                                    <input type="checkbox" name="use_system_price" value="1" id="use_system_price" class="form-check-input"
                                           <?php echo $form_data['use_system_price'] ? 'checked' : ''; ?> onchange="togglePriceInput()">
                                    <label class="form-check-label" for="use_system_price">Use System Pricing</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="manualPriceGroup" style="<?php echo $form_data['use_system_price'] ? 'display:none;' : ''; ?>">
                                <label class="hacker-label">Manual Price (₦) *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₦</span></div>
                                    <input type="number" name="manual_price" id="manual_price" class="form-control hacker-input" step="0.01" min="0.01"
                                           value="<?php echo h($form_data['manual_price']); ?>">
                                </div>
                            </div>

                            <div class="form-group" id="systemPriceDisplay" style="<?php echo !$form_data['use_system_price'] ? 'display:none;' : ''; ?>">
                                <label class="hacker-label">System Price</label>
                                <input type="text" class="form-control hacker-input" readonly value="Select a building to see price">
                                <small class="text-muted">Based on selected building's base price</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Room</button>
                        <a href="admin_manage_rooms.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Rooms Management Card -->
        <div class="card">
            <div class="card-header">
                <h4 class="text-terminal"><i class="fas fa-building"></i> Rooms Management</h4>
            </div>
            <div class="card-body">
                <!-- Add Room Button and Search -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <?php if (!$show_add_form && !$edit_id): ?>
                            <a href="?add=1" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Room
                            </a>
                        <?php else: ?>
                            <a href="admin_manage_rooms.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Rooms List
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <form method="get" action="admin_manage_rooms.php">
                            <div class="input-group hacker-input-group">
                                <input type="text" 
                                       name="search" 
                                       class="form-control hacker-input" 
                                       placeholder="Search building, room #, or status..."
                                       value="<?php echo h($search); ?>"
                                       aria-label="Search rooms">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" aria-label="Search">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="admin_manage_rooms.php" class="btn btn-default" aria-label="Clear search">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <hr class="border-terminal">

                <!-- INLINE EDIT ROOM FORM -->
                <?php if ($edit_id && $editing_room): ?>
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-edit"></i> Edit Room: <?php echo h($editing_room['room_number']); ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="edit_room" value="1">
                            <input type="hidden" name="room_id" value="<?php echo h($editing_room['id']); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Building</label>
                                        <input type="text" class="form-control hacker-input" readonly 
                                               value="<?php echo h($editing_room['building_name']); ?> (<?php echo h($editing_room['building_type']); ?>)">
                                        <small class="text-muted">Building cannot be changed after creation</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Room Number *</label>
                                        <input type="text" name="room_number" class="form-control hacker-input" 
                                               value="<?php echo h($editing_room['room_number']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Capacity *</label>
                                        <input type="number" name="capacity" class="form-control hacker-input" 
                                               value="<?php echo h($editing_room['capacity']); ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Pricing Method</label>
                                        <div class="form-check">
                                            <input type="checkbox" name="use_system_price" value="1" id="edit_use_system" 
                                                   <?php echo $editing_room['use_system_price'] ? 'checked' : ''; ?>
                                                   onchange="toggleEditPriceInput()" class="form-check-input">
                                            <label for="edit_use_system" class="form-check-label">Use System Pricing</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="edit_manual_price_group" style="<?php echo $editing_room['use_system_price'] ? 'display:none;' : ''; ?>">
                                        <label class="hacker-label">Manual Price (₦) *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">₦</span>
                                            </div>
                                            <input type="number" name="manual_price" id="edit_manual_price" class="form-control hacker-input" 
                                                   step="0.01" min="0.01" 
                                                   value="<?php echo !$editing_room['use_system_price'] ? number_format($editing_room['manual_price'], 2, '.', '') : number_format($editing_room['base_price'], 2, '.', ''); ?>" 
                                                   <?php echo $editing_room['use_system_price'] ? '' : 'required'; ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" id="edit_system_price_display" style="<?php echo !$editing_room['use_system_price'] ? 'display:none;' : ''; ?>">
                                        <label class="hacker-label">System Price</label>
                                        <input type="text" class="form-control hacker-input" readonly 
                                               value="₦<?php echo number_format($editing_room['base_price'], 2); ?>">
                                        <small class="text-muted">Building: <?php echo h($editing_room['building_name']); ?> (<?php echo h($editing_room['building_type']); ?>)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Status</label>
                                        <select name="status" class="form-control hacker-input">
                                            <option value="available" <?php echo $editing_room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="booked" <?php echo $editing_room['status'] == 'booked' ? 'selected' : ''; ?>>Booked</option>
                                            <option value="maintenance" <?php echo $editing_room['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Save Changes</button>
                                <a href="admin_manage_rooms.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rooms Table - MAINTAINING ORIGINAL STRUCTURE -->
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Building</th>
                                <th>Room #</th>
                                <th>Capacity</th>
                                <th>Price/Night</th>
                                <th>Pricing Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-5">
                                        <?php if ($search): ?>
                                            <i class="fas fa-search"></i><br>
                                            No rooms found for "<?php echo h($search); ?>"
                                        <?php else: ?>
                                            <i class="fas fa-list"></i><br>
                                            No rooms found. Add your first room.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $room): 
                                    $booking_count = isset($booking_counts[$room['id']]) ? $booking_counts[$room['id']] : 0;
                                    $can_delete = $booking_count == 0;
                                ?>
                                <tr <?php echo $edit_id == $room['id'] ? 'class="table-info"' : ''; ?>>
                                    <td><strong class="text-terminal"><?php echo h($room['building_name']); ?></strong></td>
                                    <td><span class="badge badge-primary" style="font-size:1em;padding:5px 10px;"><?php echo h($room['room_number']); ?></span></td>
                                    <td><span class="badge badge-info" style="font-size:1em;padding:5px 10px;"><?php echo h($room['capacity']); ?> person<?php echo $room['capacity'] != 1 ? 's' : ''; ?></span></td>
                                    <td><span class="text-green">₦<?php echo number_format($room['price_per_night'], 2); ?></span></td>
                                    <td><?php echo get_pricing_method_badge($room['use_system_price'], $room['building_type']); ?></td>
                                    <td><?php echo get_status_badge($room['status']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($edit_id == $room['id']): ?>
                                                <button class="btn btn-info" disabled>
                                                    <i class="fas fa-edit"></i> Editing...
                                                </button>
                                            <?php else: ?>
                                                <a href="?edit=<?php echo h($room['id']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                                   class="btn btn-info">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($can_delete): ?>
                                            <a href="javascript:void(0)" class="btn btn-danger" 
                                               onclick="showConfirmModal('Are you sure you want to delete Room <strong><?php echo h($room['room_number']); ?></strong>?', '?delete=<?php echo h($room['id']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>', 'This will permanently remove this room and all associated data.', 'btn-danger', 'fa-trash', '#ff0000', 'Delete Room');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-secondary" disabled title="Cannot delete (has <?php echo h($booking_count); ?> booking<?php echo $booking_count != 1 ? 's' : ''; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="text-center mt-3">
                    <nav aria-label="Rooms pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $edit_id ? '&edit=' . $edit_id : ''; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo h($i); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $edit_id ? '&edit=' . $edit_id : ''; ?>"><?php echo h($i); ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $edit_id ? '&edit=' . $edit_id : ''; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Important Note Section -->
        <div class="alert alert-secondary mt-3">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Rooms can use system pricing (follows building's base price) or manual pricing (custom price per room).<br>
            System pricing rooms update automatically when building prices change, except for rooms with active or upcoming bookings.<br>
            Rooms with existing bookings cannot be deleted.
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-secondary">
                    <div class="card-body">
                        <h4 class="text-light"><?php echo number_format($summary['total_count']); ?></h4>
                        <p class="mb-0">Total Rooms</p>
                        <small class="text-muted">
                            <?php echo $search ? "(Search: '$search')" : '(All rooms)'; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="text-success"><?php echo number_format($summary['available_count']); ?></h4>
                        <p class="mb-0">Available</p>
                        <small class="text-muted">Ready for booking</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h4 class="text-warning"><?php echo number_format($summary['booked_count']); ?></h4>
                        <p class="mb-0">Booked</p>
                        <small class="text-muted">Currently occupied</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-danger">
                    <div class="card-body">
                        <h4 class="text-danger"><?php echo number_format($summary['maintenance_count']); ?></h4>
                        <p class="mb-0">Maintenance</p>
                        <small class="text-muted">Under maintenance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Remove alert parameters from URL
    if (window.history.replaceState && (window.location.search.includes('alert=') || window.location.search.includes('type='))) {
        var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        
        var searchParam = new URLSearchParams(window.location.search).get('search');
        var pageParam = new URLSearchParams(window.location.search).get('page');
        var addParam = new URLSearchParams(window.location.search).get('add');
        var editParam = new URLSearchParams(window.location.search).get('edit');
        
        var params = [];
        if (searchParam) params.push('search=' + encodeURIComponent(searchParam));
        if (pageParam) params.push('page=' + pageParam);
        if (addParam) params.push('add=' + addParam);
        if (editParam) params.push('edit=' + editParam);
        
        if (params.length > 0) {
            cleanUrl += '?' + params.join('&');
        }
        
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    // Initialize price toggle for add form
    if (document.getElementById('use_system_price')) {
        togglePriceInput();
        updateSystemPrice();
    }
    
    // Initialize price toggle for edit form
    if (document.getElementById('edit_use_system')) {
        toggleEditPriceInput();
    }
});

// Toggle price input for add form
function togglePriceInput() {
    var useSystem = document.getElementById('use_system_price').checked;
    var manualGroup = document.getElementById('manualPriceGroup');
    var systemDisplay = document.getElementById('systemPriceDisplay');
    
    if (useSystem) {
        manualGroup.style.display = 'none';
        systemDisplay.style.display = 'block';
    } else {
        manualGroup.style.display = 'block';
        systemDisplay.style.display = 'none';
    }
}

// Update system price when building changes in add form
function updateSystemPrice() {
    var buildingSelect = document.querySelector('select[name="building_id"]');
    if (buildingSelect && buildingSelect.selectedIndex > 0) {
        var selectedBuilding = buildingSelect.options[buildingSelect.selectedIndex];
        var basePrice = selectedBuilding.getAttribute('data-baseprice');
        
        if (document.getElementById('use_system_price') && document.getElementById('use_system_price').checked) {
            document.getElementById('systemPriceDisplay').querySelector('input').value = '₦' + parseFloat(basePrice).toFixed(2);
        }
        document.getElementById('manual_price').value = basePrice;
    }
}

// Toggle price input for edit form
function toggleEditPriceInput() {
    var useSystem = document.getElementById('edit_use_system').checked;
    var manualGroup = document.getElementById('edit_manual_price_group');
    var systemDisplay = document.getElementById('edit_system_price_display');
    
    if (useSystem) {
        manualGroup.style.display = 'none';
        systemDisplay.style.display = 'block';
    } else {
        manualGroup.style.display = 'block';
        systemDisplay.style.display = 'none';
    }
}

// Form validation for add room
document.addEventListener('submit', function(e) {
    if (e.target && e.target.querySelector('input[name="add_room"]')) {
        const form = e.target;
        const capacityInput = form.querySelector('input[name="capacity"]');
        const capacity = parseInt(capacityInput.value);
        
        if (isNaN(capacity) || capacity < 1) {
            alert('Capacity must be at least 1');
            capacityInput.focus();
            e.preventDefault();
            return false;
        }
        
        const useSystem = form.querySelector('input[name="use_system_price"]').checked;
        if (!useSystem) {
            const manualPriceInput = form.querySelector('input[name="manual_price"]');
            const manualPrice = parseFloat(manualPriceInput.value);
            
            if (isNaN(manualPrice) || manualPrice <= 0) {
                alert('Manual price must be greater than 0');
                manualPriceInput.focus();
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        }
    }
    
    if (e.target && e.target.querySelector('input[name="edit_room"]')) {
        const form = e.target;
        const capacityInput = form.querySelector('input[name="capacity"]');
        const capacity = parseInt(capacityInput.value);
        
        if (isNaN(capacity) || capacity < 1) {
            alert('Capacity must be at least 1');
            capacityInput.focus();
            e.preventDefault();
            return false;
        }
        
        const useSystem = form.querySelector('input[name="use_system_price"]').checked;
        if (!useSystem) {
            const manualPriceInput = form.querySelector('input[name="manual_price"]');
            const manualPrice = parseFloat(manualPriceInput.value);
            
            if (isNaN(manualPrice) || manualPrice <= 0) {
                alert('Manual price must be greater than 0');
                manualPriceInput.focus();
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }
    }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>