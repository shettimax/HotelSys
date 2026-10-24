<?php
// admin_staff.php - Staff Management + ID Card (INLINE FORMAT)
// WITH SweetAlert2 CONFIRMATION - FIXED: Single trigger

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// Store form data for repopulation
$form_data = array(
    'pnumber' => '',
    'name' => '',
    'department' => '',
    'phone' => '',
    'status' => 'active'
);

// Initialize messages
$msg = '';
$msg_type = '';
$show_message = false;

// Check if we're viewing/editing a specific staff
$view_staff_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$edit_staff_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$current_staff_data = null;

if ($view_staff_id > 0 || $edit_staff_id > 0) {
    $staff_id = $view_staff_id > 0 ? $view_staff_id : $edit_staff_id;
    $sql = "SELECT s.*, u.fullname as created_by_name 
            FROM staff s 
            LEFT JOIN users_admin u ON s.created_by = u.id 
            WHERE s.id = $staff_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $current_staff_data = mysqli_fetch_assoc($result);
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pnumber = strtoupper(trim($_POST['pnumber']));
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    
    // Store form data for repopulation
    $form_data['pnumber'] = htmlspecialchars($_POST['pnumber']);
    $form_data['name'] = htmlspecialchars($_POST['name']);
    $form_data['department'] = htmlspecialchars($_POST['department']);
    $form_data['phone'] = htmlspecialchars($_POST['phone']);
    $form_data['status'] = htmlspecialchars($_POST['status']);

    // Photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = 'img/staff/' . $pnumber . '.' . strtolower($ext);
        if (!is_dir('img/staff/')) {
            mkdir('img/staff/', 0755, true);
        }
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    }

    if (isset($_POST['add_staff'])) {
        // Auto-generate pnumber if empty
        if (empty($pnumber)) {
            $dept_code = substr($department, 0, 2);
            $result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(pnumber, 3) AS UNSIGNED)) as max_num FROM staff WHERE pnumber LIKE '$dept_code%'");
            $row = mysqli_fetch_assoc($result);
            $next = ($row['max_num'] ?: 0) + 1;
            $pnumber = strtoupper($dept_code) . str_pad($next, 3, '0', STR_PAD_LEFT);
        }

        $check = mysqli_query($conn, "SELECT id FROM staff WHERE pnumber = '$pnumber'");
        if (mysqli_num_rows($check) > 0) {
            $show_message = true;
            $msg_type = 'danger';
            $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                    <h4><span class='glyphicon glyphicon-exclamation-sign'></span> Cannot Add Staff!</h4>
                    <div class='terminal-feed'>Staff ID $pnumber already exists</div></div>";
        } else {
            $admin_id = get_current_admin_id();
            $sql = "INSERT INTO staff (pnumber, name, department, phone, photo, status, created_by)
                    VALUES ('$pnumber', '$name', '$department', '$phone', '$photo', '$status', $admin_id)";
            if (mysqli_query($conn, $sql)) {
                $show_message = true;
                $msg_type = 'success';
                $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                        <h4><span class='glyphicon glyphicon-ok-circle'></span> Staff Added Successfully!</h4>
                        <div class='terminal-feed'>
                        Staff ID: $pnumber<br>
                        Name: $name<br>
                        Department: $department<br>
                        Status: <span class='text-green'>Active</span>
                        </div></div>";
                
                echo "<script>setTimeout(function() { window.location.href = 'admin_staff.php'; }, 3000);</script>";
            } else {
                $show_message = true;
                $msg_type = 'danger';
                $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                        <h4>Error adding staff</h4>
                        <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
            }
        }
    } elseif (isset($_POST['update_staff'])) {
        $id = (int)$_POST['id'];
        $photo_sql = $photo ? ", photo = '$photo'" : "";
        $sql = "UPDATE staff SET name='$name', department='$department', phone='$phone', status='$status'$photo_sql WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $show_message = true;
            $msg_type = 'success';
            $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                    <h4><span class='glyphicon glyphicon-ok-circle'></span> Staff Updated Successfully!</h4>
                    <div class='terminal-feed'>
                    Staff ID: $pnumber<br>
                    Name: $name<br>
                    Department: $department<br>
                    Status: <span class='text-green'>" . ucfirst($status) . "</span>
                    </div></div>";
            
            echo "<script>setTimeout(function() { window.location.href = 'admin_staff.php?view=' + $id; }, 3000);</script>";
        } else {
            $show_message = true;
            $msg_type = 'danger';
            $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                    <h4>Error updating staff</h4>
                    <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
            $edit_staff_id = $id; // Stay in edit mode
        }
    }
}

// Handle delete staff
if (isset($_GET['action']) && isset($_GET['id'])) {
    $staff_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'delete') {
        // Check if staff has any attendance records first
        $check_attendance = mysqli_query($conn, "SELECT COUNT(*) as count FROM staff_attendance WHERE staff_id = $staff_id");
        $attendance_row = mysqli_fetch_assoc($check_attendance);
        
        if ($attendance_row['count'] > 0) {
            $show_message = true;
            $msg_type = 'warning';
            $msg = "<div class='alert alert-warning' style='border:2px solid #ff9900;background-color:#1a0f00;color:#ffcc66;font-family:monospace;'>
                    <h4><span class='glyphicon glyphicon-warning-sign'></span> Cannot Delete Staff!</h4>
                    <div class='terminal-feed'>
                    Staff has attendance records. Please disable instead of deleting.
                    </div></div>";
        } else {
            $result = mysqli_query($conn, "DELETE FROM staff WHERE id = $staff_id");
            if ($result) {
                $show_message = true;
                $msg_type = 'success';
                $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                        <h4><span class='glyphicon glyphicon-ok-circle'></span> Staff Deleted!</h4>
                        <div class='terminal-feed'>
                        Staff member has been removed from the system.
                        </div></div>";
                
                echo "<script>setTimeout(function() { window.location.href = 'admin_staff.php'; }, 3000);</script>";
            }
        }
    }
}

// Check if add form should be shown
$show_add_form = isset($_GET['add']) || (isset($show_add_form) && $show_add_form);

// Handle search
$search_query = '';
$where_clause = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_query = "&search=" . urlencode($search);
    $where_clause = "WHERE 
        s.pnumber LIKE '%$search%' OR
        s.name LIKE '%$search%' OR
        s.department LIKE '%$search%' OR
        s.phone LIKE '%$search%'";
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count
$count_result = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM staff s
    $where_clause
");
$count_row = mysqli_fetch_assoc($count_result);
$total_staff = $count_row['total'];
$total_pages = ceil($total_staff / $limit);

// Fetch staff
$staff_result = mysqli_query($conn, "
    SELECT s.*, u.fullname as created_by_name 
    FROM staff s 
    LEFT JOIN users_admin u ON s.created_by = u.id
    $where_clause
    ORDER BY s.pnumber ASC
    LIMIT $limit OFFSET $offset
");
$staff_list = array();
while ($row = mysqli_fetch_assoc($staff_result)) {
    $staff_list[] = $row;
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <!-- Display alert messages - FIXED: Output HTML directly -->
        <?php if ($show_message && $msg): ?>
            <?php echo $msg; ?>
        <?php endif; ?>

        <!-- VIEW STAFF PROFILE -->
        <?php if ($current_staff_data && $view_staff_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal">
                            <i class="fas fa-id-card"></i> Staff Profile: <?php echo h($current_staff_data['pnumber']); ?>
                            <a href="admin_staff.php" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $created_date = date('M d, Y, g:i a', strtotime($current_staff_data['created_at']));
                        $status_class = $current_staff_data['status'] == 'active' ? 'success' : 'secondary';
                        ?>
                       
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <?php if ($current_staff_data['photo'] && file_exists($current_staff_data['photo'])): ?>
                                    <img src="<?php echo h($current_staff_data['photo']); ?>" class="img-fluid rounded" style="max-width: 200px;">
                                <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width:200px;height:200px;margin:0 auto;">
                                        <i class="fas fa-user fa-5x text-light"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h3><?php echo h($current_staff_data['name']); ?></h3>
                                        <p class="text-muted">Staff ID: <?php echo h($current_staff_data['pnumber']); ?> | Created: <?php echo h($created_date); ?></p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <span class="badge badge-<?php echo h($status_class); ?>">
                                            <?php echo ucfirst($current_staff_data['status']); ?>
                                        </span>
                                        <a href="admin_staff.php?edit=<?php echo h($current_staff_data['id']); ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5><i class="fas fa-info-circle"></i> Staff Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <td width="40%"><strong>Staff ID:</strong></td>
                                                        <td><?php echo h($current_staff_data['pnumber']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Full Name:</strong></td>
                                                        <td><?php echo h($current_staff_data['name']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Department:</strong></td>
                                                        <td><?php echo h($current_staff_data['department']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Phone:</strong></td>
                                                        <td><?php echo h($current_staff_data['phone']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Status:</strong></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo h($status_class); ?>">
                                                                <?php echo ucfirst($current_staff_data['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Created By:</strong></td>
                                                        <td><?php echo h($current_staff_data['created_by_name'] ?: 'System'); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Created Date:</strong></td>
                                                        <td><?php echo h($created_date); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5><i class="fas fa-tasks"></i> Actions</h5>
                                            </div>
                                            <div class="card-body text-center">
                                                <a href="staff_id_card.php?pnumber=<?php echo h($current_staff_data['pnumber']); ?>" target="_blank" class="btn btn-info btn-lg mb-2" style="width: 100%;">
                                                    <i class="fas fa-id-card"></i> Generate ID Card
                                                </a>
                                                
                                                <a href="admin_attendance_clock.php?pnumber=<?php echo h($current_staff_data['pnumber']); ?>" class="btn btn-primary btn-lg mb-2" style="width: 100%;">
                                                    <i class="fas fa-clock"></i> Clock In/Out
                                                </a>
                                                
                                                <a href="admin_attendance.php?search=<?php echo urlencode($current_staff_data['pnumber']); ?>" class="btn btn-secondary btn-lg mb-2" style="width: 100%;">
                                                    <i class="fas fa-history"></i> View Attendance History
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <a href="admin_staff.php?edit=<?php echo h($current_staff_data['id']); ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Edit Staff
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-danger"
                                       onclick="showConfirmModal('Are you sure you want to delete <strong><?php echo h($current_staff_data['name']); ?></strong>?<br><small>Staff ID: <?php echo h($current_staff_data['pnumber']); ?></small>', 'admin_staff.php?action=delete&id=<?php echo h($current_staff_data['id']); ?>', 'This will permanently remove this staff member and all attendance records.', 'btn-danger', 'fa-user-times', '#ff0000', 'Delete Staff');">
                                        <i class="fas fa-trash"></i> Delete Staff
                                    </a>
                                    <a href="admin_staff.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- EDIT STAFF FORM -->
        <?php if ($current_staff_data && $edit_staff_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>
                            <i class="fas fa-edit"></i> Edit Staff: <?php echo h($current_staff_data['pnumber']); ?>
                            <a href="admin_staff.php" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="update_staff" value="1">
                            <input type="hidden" name="id" value="<?php echo h($edit_staff_id); ?>">
                           
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center mb-3">
                                        <?php if ($current_staff_data['photo'] && file_exists($current_staff_data['photo'])): ?>
                                            <img src="<?php echo h($current_staff_data['photo']); ?>" class="img-fluid rounded" style="max-width: 150px;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width:150px;height:150px;margin:0 auto;">
                                                <i class="fas fa-user fa-3x text-light"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Staff ID *</label>
                                                <input type="text" name="pnumber" class="form-control hacker-input"
                                                       value="<?php echo h($current_staff_data['pnumber']); ?>" required readonly>
                                                <small class="text-muted">Staff ID cannot be changed</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Full Name *</label>
                                                <input type="text" name="name" class="form-control hacker-input"
                                                       value="<?php echo h($current_staff_data['name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Department</label>
                                                <select name="department" class="form-control hacker-input" required>
                                                    <option value="Reception" <?php echo $current_staff_data['department'] == 'Reception' ? 'selected' : ''; ?>>Reception</option>
                                                    <option value="Housekeeping" <?php echo $current_staff_data['department'] == 'Housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                                                    <option value="Security" <?php echo $current_staff_data['department'] == 'Security' ? 'selected' : ''; ?>>Security</option>
                                                    <option value="Kitchen" <?php echo $current_staff_data['department'] == 'Kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                                                    <option value="Maintenance" <?php echo $current_staff_data['department'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                    <option value="General" <?php echo $current_staff_data['department'] == 'General' ? 'selected' : ''; ?>>General</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Phone</label>
                                                <input type="text" name="phone" class="form-control hacker-input"
                                                       value="<?php echo h($current_staff_data['phone']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Photo</label>
                                                <input type="file" name="photo" class="form-control hacker-input" accept="image/*">
                                                <small class="text-muted">Leave empty to keep current photo</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Status</label>
                                                <select name="status" class="form-control hacker-input" required>
                                                    <option value="active" <?php echo $current_staff_data['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $current_staff_data['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="text-right">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="admin_staff.php?view=<?php echo h($edit_staff_id); ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN LIST -->
        <?php if (!$current_staff_data): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal">
                            <i class="fas fa-users"></i> Staff Management
                        </h4>
                    </div>
                    <div class="card-body">
                       
                        <!-- Search and Actions -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="get" action="admin_staff.php">
                                    <div class="input-group hacker-input-group">
                                        <input type="text" 
                                               name="search" 
                                               class="form-control hacker-input" 
                                               placeholder="Search by ID, name, department, or phone..."
                                               value="<?php echo isset($_GET['search']) ? h($_GET['search']) : ''; ?>"
                                               aria-label="Search staff">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" aria-label="Search">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                <a href="admin_staff.php" class="btn btn-default" aria-label="Clear search">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                        <small class="text-muted">
                                            Found <?php echo h($total_staff); ?> staff member(s) matching "<?php echo h($_GET['search']); ?>"
                                        </small>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="?add=1" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Add New Staff
                                </a>
                            </div>
                        </div>
                        <hr class="border-terminal">

                        <!-- INLINE ADD STAFF FORM -->
                        <?php if ($show_add_form): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h4><i class="fas fa-user-plus"></i> Add New Staff Member</h4>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data" onsubmit="return validateAddStaffForm()">
                                    <input type="hidden" name="add_staff" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="hacker-label">Staff ID (auto if blank)</label>
                                                <input type="text" name="pnumber" id="new_pnumber" class="form-control hacker-input" 
                                                       placeholder="e.g., ST001"
                                                       value="<?php echo isset($form_data['pnumber']) ? h($form_data['pnumber']) : ''; ?>">
                                                <small class="text-muted">Leave blank to auto-generate</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="hacker-label">Full Name *</label>
                                                <input type="text" name="name" id="new_name" class="form-control hacker-input" required 
                                                       placeholder="Enter full name"
                                                       value="<?php echo isset($form_data['name']) ? h($form_data['name']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="hacker-label">Department *</label>
                                                <select name="department" id="new_department" class="form-control hacker-input" required>
                                                    <option value="">Select Department</option>
                                                    <option value="Reception" <?php echo (isset($form_data['department']) && $form_data['department'] == 'Reception') ? 'selected' : ''; ?>>Reception</option>
                                                    <option value="Housekeeping" <?php echo (isset($form_data['department']) && $form_data['department'] == 'Housekeeping') ? 'selected' : ''; ?>>Housekeeping</option>
                                                    <option value="Security" <?php echo (isset($form_data['department']) && $form_data['department'] == 'Security') ? 'selected' : ''; ?>>Security</option>
                                                    <option value="Kitchen" <?php echo (isset($form_data['department']) && $form_data['department'] == 'Kitchen') ? 'selected' : ''; ?>>Kitchen</option>
                                                    <option value="Maintenance" <?php echo (isset($form_data['department']) && $form_data['department'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                                    <option value="General" <?php echo (isset($form_data['department']) && $form_data['department'] == 'General') ? 'selected' : ''; ?>>General</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="hacker-label">Phone</label>
                                                <input type="text" name="phone" id="new_phone" class="form-control hacker-input" 
                                                       placeholder="Phone number"
                                                       value="<?php echo isset($form_data['phone']) ? h($form_data['phone']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Photo</label>
                                                <input type="file" name="photo" id="new_photo" class="form-control hacker-input" accept="image/*">
                                                <small class="text-muted">Optional. Accepted formats: JPG, PNG</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="hacker-label">Status</label>
                                                <select name="status" id="new_status" class="form-control hacker-input" required>
                                                    <option value="active" <?php echo (isset($form_data['status']) && $form_data['status'] == 'active') ? 'selected' : 'selected'; ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($form_data['status']) && $form_data['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-right mt-4">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Add Staff
                                            </button>
                                            <a href="admin_staff.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Staff Table -->
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Staff ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = $offset + 1;
                                    if (empty($staff_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-5">
                                                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                    <i class="fas fa-search"></i><br>
                                                    No staff found for "<?php echo h($_GET['search']); ?>"
                                                <?php else: ?>
                                                    <i class="fas fa-users"></i><br>
                                                    No staff members found. Add your first staff member.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($staff_list as $staff): ?>
                                    <tr <?php echo $view_staff_id == $staff['id'] || $edit_staff_id == $staff['id'] ? 'class="table-info"' : ''; ?>>
                                        <td>
                                            <?php if ($staff['photo'] && file_exists($staff['photo'])): ?>
                                                <img src="<?php echo h($staff['photo']); ?>" width="50" class="rounded">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                                    <i class="fas fa-user text-light"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong class="text-terminal"><?php echo h($staff['pnumber']); ?></strong></td>
                                        <td><?php echo h($staff['name']); ?></td>
                                        <td><?php echo h($staff['department']); ?></td>
                                        <td><?php echo h($staff['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $staff['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($staff['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($view_staff_id == $staff['id']): ?>
                                                    <button class="btn btn-info" disabled>
                                                        <i class="fas fa-eye"></i> Viewing
                                                    </button>
                                                <?php else: ?>
                                                    <a href="admin_staff.php?view=<?php echo h($staff['id']); ?>" 
                                                       class="btn btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($edit_staff_id == $staff['id']): ?>
                                                    <button class="btn btn-warning" disabled>
                                                        <i class="fas fa-edit"></i> Editing
                                                    </button>
                                                <?php else: ?>
                                                    <a href="admin_staff.php?edit=<?php echo h($staff['id']); ?>" 
                                                       class="btn btn-warning" title="Edit Staff">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="staff_id_card.php?pnumber=<?php echo h($staff['pnumber']); ?>" 
                                                   target="_blank" class="btn btn-primary" title="ID Card">
                                                    <i class="fas fa-id-card"></i> ID
                                                </a>
                                                
                                                <a href="admin_attendance_clock.php?pnumber=<?php echo h($staff['pnumber']); ?>" 
                                                   class="btn btn-secondary" title="Clock In/Out">
                                                    <i class="fas fa-clock"></i> Clock
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="text-center mt-3">
                            <nav aria-label="Staff pagination">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo h($search_query); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo h($i); ?><?php echo h($search_query); ?>"><?php echo h($i); ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo h($search_query); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to validate add staff form
function validateAddStaffForm() {
    const name = document.getElementById('new_name');
    const department = document.getElementById('new_department');
    
    if (!name || !department) {
        return true;
    }
    
    if (!name.value.trim()) {
        alert('Full name is required');
        name.focus();
        return false;
    }
    
    if (!department.value) {
        alert('Please select a department');
        department.focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('form[onsubmit="return validateAddStaffForm()"] button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }
    
    return true;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on first input in add form
    const addName = document.getElementById('new_name');
    if (addName) {
        addName.focus();
    }
    
    // Auto-focus on first input in edit form
    const editName = document.querySelector('input[name="name"]');
    if (editName) {
        editName.focus();
    }
    
    // Handle photo preview (optional enhancement)
    const photoInput = document.getElementById('new_photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                // Could add preview functionality here
            }
        });
    }
});
</script>

<?php include __DIR__.'/footer.php'; ?>