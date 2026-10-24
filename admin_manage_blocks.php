<?php
// admin_manage_blocks.php - UPDATED: INLINE ADD AND EDIT FORMS
// WITH SweetAlert2 CONFIRMATION - FIXED: Single trigger

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

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
    'building_name' => '',
    'building_type' => 'regular',
    'base_price' => '',
    'description' => ''
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

// Handle building add (INLINE FORM)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_building'])) {
    $building_name = mysqli_real_escape_string($conn, trim($_POST['building_name']));
    $building_type = mysqli_real_escape_string($conn, $_POST['building_type']);
    $base_price = floatval($_POST['base_price']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    // Repopulate form data
    $form_data = array(
        'building_name' => htmlspecialchars($building_name),
        'building_type' => $building_type,
        'base_price' => $base_price,
        'description' => htmlspecialchars($description)
    );
    
    // Validate
    $errors = array();
    
    if (empty($building_name)) {
        $errors[] = "Building name is required";
    }
    if ($base_price < 1) {
        $errors[] = "Base price must be at least ₦1.00";
    }
    
    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
        $msg_type = 'error';
        $show_add_form = true;
    } else {
        $current_time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO buildings (name, building_type, base_price, description, created_at) 
                VALUES ('$building_name', '$building_type', '$base_price', '$description', '$current_time')";
        
        if (mysqli_query($conn, $sql)) {
            header('Location: admin_manage_blocks.php?alert=' . urlencode('Building added successfully!') . '&type=success');
            exit;
        } else {
            $msg = "Error adding building: " . mysqli_error($conn);
            $msg_type = 'error';
            $show_add_form = true;
        }
    }
}

// Handle building edit (INLINE FORM)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_building'])) {
    $building_id = (int)$_POST['building_id'];
    $building_name = mysqli_real_escape_string($conn, $_POST['building_name']);
    $building_type = mysqli_real_escape_string($conn, $_POST['building_type']);
    $new_base_price = floatval($_POST['base_price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Validate price - must be at least ₦1.00
    if ($new_base_price < 1) {
        $msg = "Base price must be at least ₦1.00!";
        $msg_type = 'error';
        $edit_id = $building_id; // Stay in edit mode
    } else {
        // Get old price for comparison
        $old_price_result = mysqli_query($conn, "SELECT base_price FROM buildings WHERE id = $building_id");
        $old_price_row = mysqli_fetch_assoc($old_price_result);
        $old_base_price = $old_price_row['base_price'];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $current_time = date('Y-m-d H:i:s');
            
            // Update building info with current timestamp
            $sql = "UPDATE buildings SET 
                    name = '$building_name',
                    building_type = '$building_type',
                    base_price = '$new_base_price',
                    description = '$description',
                    created_at = '$current_time'
                    WHERE id = $building_id";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error updating building: " . mysqli_error($conn));
            }
            
            $affected_rooms = 0;
            // If price changed, update rooms that use system pricing and don't have active bookings
            if ($new_base_price != $old_base_price) {
                // Update rooms that use system pricing and don't have active bookings
                $update_rooms_sql = "UPDATE rooms r
                                     LEFT JOIN bookings b ON r.id = b.room_id 
                                     AND b.status IN ('pending', 'confirmed', 'paid')
                                     AND b.checkout_date >= CURDATE()
                                     SET r.price_per_night = '$new_base_price',
                                         r.updated_at = '$current_time'
                                     WHERE r.building_id = $building_id 
                                     AND r.use_system_price = 1
                                     AND b.id IS NULL";
                
                if (!mysqli_query($conn, $update_rooms_sql)) {
                    throw new Exception("Error updating room prices: " . mysqli_error($conn));
                }
                
                $affected_rooms = mysqli_affected_rows($conn);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $alert_msg = "Building updated successfully! Last modified: " . date('Y-m-d H:i:s');
            if ($affected_rooms > 0) {
                $alert_msg .= " Updated price for $affected_rooms room(s) that use system pricing and don't have active bookings.";
            } else if ($new_base_price != $old_base_price) {
                $alert_msg .= " No rooms were updated (all have manual pricing or active bookings).";
            }
            
            header('Location: admin_manage_blocks.php?alert=' . urlencode($alert_msg) . '&type=success');
            exit;
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($conn);
            $msg = $e->getMessage();
            $msg_type = 'error';
            $edit_id = $building_id; // Stay in edit mode
        }
    }
}

// Handle delete via GET
if (isset($_GET['delete'])) {
    $building_id = (int)$_GET['delete'];
    
    // Check if building has rooms
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms WHERE building_id = $building_id");
    $row = mysqli_fetch_assoc($check);
    
    if ($row['count'] > 0) {
        header('Location: admin_manage_blocks.php?alert=' . urlencode('Cannot delete building with rooms. Remove rooms first.') . '&type=error');
        exit;
    } else {
        if (mysqli_query($conn, "DELETE FROM buildings WHERE id = $building_id")) {
            header('Location: admin_manage_blocks.php?alert=' . urlencode('Building deleted successfully!') . '&type=success');
            exit;
        } else {
            header('Location: admin_manage_blocks.php?alert=' . urlencode('Error deleting building: ' . mysqli_error($conn)) . '&type=error');
            exit;
        }
    }
}

// Fetch building data for editing
$editing_building = null;
if ($edit_id > 0) {
    $edit_result = mysqli_query($conn, "SELECT * FROM buildings WHERE id = $edit_id");
    if (mysqli_num_rows($edit_result) > 0) {
        $editing_building = mysqli_fetch_assoc($edit_result);
    }
}

// Fetch buildings with search filter
$where_conditions = array("1=1");
if (!empty($search)) {
    $where_conditions[] = "(b.name LIKE '%$search%' 
        OR b.building_type LIKE '%$search%' 
        OR b.description LIKE '%$search%')";
}
$where_clause = implode(' AND ', $where_conditions);

$buildings_result = mysqli_query($conn, "
    SELECT b.*, 
           (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id) as room_count
    FROM buildings b 
    WHERE $where_clause
    ORDER BY b.name
    LIMIT $offset, $per_page
");
$buildings = mysqli_fetch_all($buildings_result, MYSQLI_ASSOC);

// Get total count for pagination
$total_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM buildings WHERE $where_clause");
$total_count_row = mysqli_fetch_assoc($total_count_result);
$total_count = $total_count_row['count'];
$total_pages = ceil($total_count / $per_page);

// Function to get building type badge
function get_building_type_badge($type) {
    switch($type) {
        case 'regular': return '<span class="badge badge-info">Regular</span>';
        case 'vip': return '<span class="badge badge-warning">VIP</span>';
        case 'premium': return '<span class="badge badge-success">Premium</span>';
        default: return '<span class="badge badge-secondary">' . $type . '</span>';
    }
}

// Function to get base price display
function get_base_price_display($price) {
    return '<span class="text-success">₦' . number_format($price, 2) . '</span>';
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <?php if ($msg): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible" 
                     style="border:2px solid <?php echo $msg_type === 'success' ? '#00ff99' : '#d00'; ?>;background-color:<?php echo $msg_type === 'success' ? '#001a00' : '#1a0000'; ?>;color:<?php echo $msg_type === 'success' ? '#00ff99' : '#ff6666'; ?>;font-family:monospace;">
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
            </div>
        </div>
        <?php endif; ?>

        <!-- INLINE ADD BUILDING FORM -->
        <?php if ($show_add_form && !$edit_id): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fa fa-plus"></i> Add New Building</h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="add_building" value="1">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="building_name">Building Name *</label>
                                        <input type="text" name="building_name" id="building_name" class="form-control hacker-input" 
                                               value="<?php echo h($form_data['building_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="building_type">Building Type *</label>
                                        <select name="building_type" id="building_type" class="form-control hacker-input" required>
                                            <option value="regular" <?php echo $form_data['building_type'] == 'regular' ? 'selected' : ''; ?>>Regular</option>
                                            <option value="vip" <?php echo $form_data['building_type'] == 'vip' ? 'selected' : ''; ?>>VIP</option>
                                            <option value="premium" <?php echo $form_data['building_type'] == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="base_price">Base Price (₦) *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">₦</span>
                                            </div>
                                            <input type="number" name="base_price" id="base_price" class="form-control hacker-input" 
                                                   step="0.01" min="1" value="<?php echo h($form_data['base_price']); ?>" required 
                                                   placeholder="Enter base price">
                                        </div>
                                        <small class="text-muted">Set the base price for this building type. Minimum: ₦1.00</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="room_count">Room Count</label>
                                        <input type="text" id="room_count" class="form-control hacker-input" value="0" readonly>
                                        <small class="text-muted">Rooms can be added after creating building</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control hacker-input" rows="3"><?php echo h($form_data['description']); ?></textarea>
                            </div>
                            
                            <div class="text-right">
                                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Add Building</button>
                                <a href="admin_manage_blocks.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN BUILDINGS MANAGEMENT PANEL -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal"><i class="fa fa-building"></i> Buildings Management</h4>
                    </div>
                    <div class="card-body">
                        <!-- Add Building Button and Search -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <?php if (!$show_add_form && !$edit_id): ?>
                                    <a href="?add=1" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Add New Building
                                    </a>
                                <?php else: ?>
                                    <a href="admin_manage_blocks.php" class="btn btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Back to Buildings List
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <form method="get" action="admin_manage_blocks.php">
                                    <div class="input-group">
                                        <input type="text" 
                                               name="search" 
                                               class="form-control hacker-input" 
                                               placeholder="Search building name, type, or description..."
                                               value="<?php echo h($search); ?>"
                                               aria-label="Search buildings">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" aria-label="Search">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <?php if ($search): ?>
                                                <a href="admin_manage_blocks.php" class="btn btn-default" aria-label="Clear search">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <hr class="border-terminal">

                        <!-- INLINE EDIT BUILDING FORM -->
                        <?php if ($edit_id && $editing_building): ?>
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-primary text-white">
                                <h4><i class="fa fa-edit"></i> Edit Building: <?php echo h($editing_building['name']); ?></h4>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="edit_building" value="1">
                                    <input type="hidden" name="building_id" value="<?php echo h($editing_building['id']); ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_building_name">Building Name *</label>
                                                <input type="text" name="building_name" id="edit_building_name" class="form-control hacker-input" 
                                                       value="<?php echo h($editing_building['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_building_type">Building Type *</label>
                                                <select name="building_type" id="edit_building_type" class="form-control hacker-input" required>
                                                    <option value="regular" <?php echo $editing_building['building_type'] == 'regular' ? 'selected' : ''; ?>>Regular</option>
                                                    <option value="vip" <?php echo $editing_building['building_type'] == 'vip' ? 'selected' : ''; ?>>VIP</option>
                                                    <option value="premium" <?php echo $editing_building['building_type'] == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="edit_base_price">Base Price (₦) *</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">₦</span>
                                                    </div>
                                                    <input type="number" name="base_price" id="edit_base_price" class="form-control hacker-input" 
                                                           step="0.01" min="1" value="<?php echo number_format($editing_building['base_price'], 2, '.', ''); ?>" required>
                                                </div>
                                                <small class="text-muted">Minimum: ₦1.00. Changing this price will update rooms that use system pricing and don't have active bookings.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php 
                                                $room_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms WHERE building_id = " . $editing_building['id']);
                                                $room_count_row = mysqli_fetch_assoc($room_count_result);
                                                $room_count = $room_count_row['count'];
                                                ?>
                                                <label for="edit_room_count">Room Count</label>
                                                <input type="text" id="edit_room_count" class="form-control hacker-input" 
                                                       value="<?php echo h($room_count); ?> room<?php echo $room_count != 1 ? 's' : ''; ?>" readonly>
                                                <small class="text-muted">
                                                    <a href="admin_manage_rooms.php?search=<?php echo urlencode($editing_building['name']); ?>">
                                                        View rooms in this building
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit_description">Description</label>
                                        <textarea name="description" id="edit_description" class="form-control hacker-input" rows="3"><?php echo h($editing_building['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Save Changes</button>
                                        <a href="admin_manage_blocks.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Buildings Table -->
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Building Name</th>
                                        <th>Type</th>
                                        <th>Base Price</th>
                                        <th>Rooms</th>
                                        <th>Created</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($buildings)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-5">
                                                <?php if ($search): ?>
                                                    <i class="fa fa-search"></i><br>
                                                    No buildings found for "<?php echo h($search); ?>"
                                                <?php else: ?>
                                                    <i class="fa fa-building"></i><br>
                                                    No buildings found. Add your first building.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($buildings as $building): ?>
                                        <tr <?php echo $edit_id == $building['id'] ? 'class="table-info"' : ''; ?>>
                                            <td><strong class="text-terminal"><?php echo h($building['name']); ?></strong></td>
                                            <td><?php echo get_building_type_badge($building['building_type']); ?></td>
                                            <td><?php echo get_base_price_display($building['base_price']); ?></td>
                                            <td>
                                                <span class="badge" style="font-size:1em;padding:5px 10px;background:#00ff99;color:#000;">
                                                    <?php echo h($building['room_count']); ?> room<?php echo $building['room_count'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td><small class="text-muted"><?php echo date('M j, Y', strtotime($building['created_at'])); ?></small></td>
                                            <td><small><?php echo nl2br(h($building['description'] ?: '—')); ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($edit_id == $building['id']): ?>
                                                        <button class="btn btn-info" disabled>
                                                            <i class="fa fa-edit"></i> Editing...
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="?edit=<?php echo h($building['id']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                                           class="btn btn-info">
                                                            <i class="fa fa-edit"></i> Edit
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($building['room_count'] == 0): ?>
                                                    <a href="javascript:void(0)" class="btn btn-danger" 
                                                       onclick="showConfirmModal('Are you sure you want to delete <strong><?php echo h($building['name']); ?></strong>?', '?delete=<?php echo h($building['id']); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>', 'This will permanently remove this building and all associated data.', 'btn-danger', 'fa-trash', '#ff0000', 'Delete Building');">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-secondary" disabled title="Cannot delete (has <?php echo h($building['room_count']); ?> rooms)">
                                                        <i class="fa fa-trash"></i> Delete
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
                            <nav aria-label="Buildings pagination">
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
            </div>
        </div>

        <!-- Important Note Section -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-secondary">
                    <i class="fa fa-info-circle"></i>
                    <strong>Note:</strong> Building types are for categorization only. You must set the base price for each building individually.<br>
                    When you update a building's base price, rooms using system pricing will be updated automatically, except for rooms with active or upcoming bookings.
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <?php
            // Get summary data
            $summary_sql = "
                SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN building_type = 'regular' THEN 1 ELSE 0 END) as regular_count,
                    SUM(CASE WHEN building_type = 'vip' THEN 1 ELSE 0 END) as vip_count,
                    SUM(CASE WHEN building_type = 'premium' THEN 1 ELSE 0 END) as premium_count,
                    SUM(base_price) as total_value
                FROM buildings
                WHERE $where_clause
            ";
            $summary_result = mysqli_query($conn, $summary_sql);
            $summary = mysqli_fetch_assoc($summary_result);
            ?>
            <div class="col-md-3">
                <div class="card text-center border-secondary">
                    <div class="card-body">
                        <h4 class="text-light"><?php echo number_format($summary['total_count']); ?></h4>
                        <p class="mb-0">Total Buildings</p>
                        <small class="text-muted">
                            <?php echo $search ? "(Search: '$search')" : '(All buildings)'; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h4 class="text-info"><?php echo number_format($summary['regular_count']); ?></h4>
                        <p class="mb-0">Regular</p>
                        <small class="text-muted">Basic buildings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h4 class="text-warning"><?php echo number_format($summary['vip_count']); ?></h4>
                        <p class="mb-0">VIP</p>
                        <small class="text-muted">Premium buildings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h4 class="text-success"><?php echo number_format($summary['premium_count']); ?></h4>
                        <p class="mb-0">Premium</p>
                        <small class="text-muted">Luxury buildings</small>
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
});

// Form validation for add building
document.addEventListener('submit', function(e) {
    if (e.target && e.target.querySelector('input[name="add_building"]')) {
        const form = e.target;
        const basePriceInput = form.querySelector('input[name="base_price"]');
        const basePrice = parseFloat(basePriceInput.value);
        
        if (isNaN(basePrice) || basePrice < 1) {
            alert('Base price must be at least ₦1.00');
            basePriceInput.focus();
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
        }
    }
    
    if (e.target && e.target.querySelector('input[name="edit_building"]')) {
        const form = e.target;
        const basePriceInput = form.querySelector('input[name="base_price"]');
        const basePrice = parseFloat(basePriceInput.value);
        
        if (isNaN(basePrice) || basePrice < 1) {
            alert('Base price must be at least ₦1.00');
            basePriceInput.focus();
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
        }
    }
});
</script>
<?php include __DIR__.'/footer.php'; ?>