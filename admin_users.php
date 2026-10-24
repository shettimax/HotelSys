<?php
// admin_users.php - COMPLETE FIXED VERSION
// FIXED: Users can view their own profile without manage_users permission
// FIXED: Hide action buttons users don't have access to

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

// Store form data for repopulation
$form_data = array(
    'username' => '',
    'fullname' => '',
    'password' => '',
    'confirm_password' => ''
);

// Initialize messages
$msg = '';
$msg_type = '';
$show_message = false;

// Check if we're viewing/editing a specific user
$view_user_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$edit_user_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$current_user_data = null;
$current_user_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

// ============================================================
// PERMISSION: Can view any user profile?
// ============================================================
$can_manage_users = has_permission('manage_users');

// If viewing/editing a specific user, fetch their data
if ($view_user_id > 0 || $edit_user_id > 0) {
    $user_id = $view_user_id > 0 ? $view_user_id : $edit_user_id;
    $sql = "SELECT * FROM users_admin WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $current_user_data = mysqli_fetch_assoc($result);
    }
}

// ============================================================
// VIEW PERMISSION: User can view profile if:
// 1. It's their own profile (always allowed)
// 2. They have manage_users permission (can view anyone)
// ============================================================
$can_view_profile = false;
$can_edit_profile = false;

if ($view_user_id > 0 && $current_user_data) {
    if ($view_user_id == $current_user_id) {
        $can_view_profile = true; // Always allow viewing own profile
    } elseif ($can_manage_users) {
        $can_view_profile = true; // Admin can view anyone
    }
}

if ($edit_user_id > 0 && $current_user_data) {
    if ($can_manage_users) {
        $can_edit_profile = true; // Admin can edit anyone
    }
    if ($edit_user_id == $current_user_id) {
        $can_edit_profile = true; // Users can edit their own profile
    }
}

// Get roles for dropdown
$all_roles = get_all_roles();
$roles_for_dropdown = array();
foreach ($all_roles as $role) {
    $roles_for_dropdown[$role['name']] = $role['display_name'];
}

// Handle user addition (only if can manage users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!$can_manage_users) {
        set_flash_message('danger', 'You do not have permission to add users.');
        header('Location: admin_users.php');
        exit;
    }
    
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'receptionist';
    
    if (!array_key_exists($role, $roles_for_dropdown)) {
        $first_role = reset($all_roles);
        $role = $first_role ? $first_role['name'] : 'receptionist';
    }
    $role_esc = mysqli_real_escape_string($conn, $role);
    
    $form_data['username'] = htmlspecialchars($_POST['username']);
    $form_data['fullname'] = htmlspecialchars($_POST['fullname']);
    
    $errors = array();
    
    $user_check = mysqli_query($conn, "SELECT id FROM users_admin WHERE username = '$username'");
    if (mysqli_num_rows($user_check) > 0) {
        $errors[] = "Username '$username' already exists";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (!empty($errors)) {
        $show_message = true;
        $msg_type = 'error';
        $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
            <h4><span class='glyphicon glyphicon-exclamation-sign'></span> Cannot Add User!</h4>
            <div class='terminal-feed'>";
        $msg .= implode("<br>", $errors);
        $msg .= "</div></div>";
        $show_add_form = true;
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (db_column_exists($conn, 'users_admin', 'role')) {
            $result = mysqli_query($conn, "
                INSERT INTO users_admin (username, password, fullname, role) 
                VALUES ('$username', '$hashed_password', '$fullname', '$role_esc')
            ");
        } else {
            $result = mysqli_query($conn, "
                INSERT INTO users_admin (username, password, fullname) 
                VALUES ('$username', '$hashed_password', '$fullname')
            ");
        }
        
        if ($result) {
            $new_user_id = mysqli_insert_id($conn);
            $show_message = true;
            $msg_type = 'success';
            $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                <h4><span class='glyphicon glyphicon-ok-circle'></span> User Added Successfully!</h4>
                <div class='terminal-feed'>
                Username: $username<br>
                Full Name: $fullname<br>
                Status: <span class='text-green'>Enabled</span>
                </div></div>";
            echo "<script>setTimeout(function() { window.location.href = 'admin_users.php'; }, 3000);</script>";
        } else {
            $show_message = true;
            $msg_type = 'error';
            $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                <h4>Error adding user</h4>
                <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
            $show_add_form = true;
        }
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Check if user can edit this profile
    if (!$can_manage_users && $user_id != $current_user_id) {
        set_flash_message('danger', 'You do not have permission to edit this user.');
        header('Location: admin_users.php');
        exit;
    }
    
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] == 1;
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'receptionist';
    
    if (!array_key_exists($role, $roles_for_dropdown)) {
        $role = isset($current_user_data['role']) ? $current_user_data['role'] : 'receptionist';
    }
    $role_esc = mysqli_real_escape_string($conn, $role);
    
    $form_data['username'] = htmlspecialchars($_POST['username']);
    $form_data['fullname'] = htmlspecialchars($_POST['fullname']);
    
    $errors = array();
    
    $user_check = mysqli_query($conn, "SELECT id FROM users_admin WHERE username = '$username' AND id != $user_id");
    if (mysqli_num_rows($user_check) > 0) {
        $errors[] = "Username '$username' already exists";
    }
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if ($change_password) {
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        if (empty($password)) {
            $errors[] = "Password is required when changing password";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    if (!empty($errors)) {
        $show_message = true;
        $msg_type = 'error';
        $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
            <h4><span class='glyphicon glyphicon-exclamation-sign'></span> Cannot Update User!</h4>
            <div class='terminal-feed'>";
        $msg .= implode("<br>", $errors);
        $msg .= "</div></div>";
        $edit_user_id = $user_id;
    } else {
        $has_role_column = db_column_exists($conn, 'users_admin', 'role');
        if ($change_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($has_role_column) {
                $result = mysqli_query($conn, "
                    UPDATE users_admin SET 
                        username = '$username',
                        fullname = '$fullname',
                        password = '$hashed_password',
                        role = '$role_esc'
                    WHERE id = $user_id
                ");
            } else {
                $result = mysqli_query($conn, "
                    UPDATE users_admin SET 
                        username = '$username',
                        fullname = '$fullname',
                        password = '$hashed_password'
                    WHERE id = $user_id
                ");
            }
        } else {
            if ($has_role_column) {
                $result = mysqli_query($conn, "
                    UPDATE users_admin SET 
                        username = '$username',
                        fullname = '$fullname',
                        role = '$role_esc'
                    WHERE id = $user_id
                ");
            } else {
                $result = mysqli_query($conn, "
                    UPDATE users_admin SET 
                        username = '$username',
                        fullname = '$fullname'
                    WHERE id = $user_id
                ");
            }
        }
        
        if ($result) {
            $show_message = true;
            $msg_type = 'success';
            $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                <h4><span class='glyphicon glyphicon-ok-circle'></span> User Updated Successfully!</h4>
                <div class='terminal-feed'>
                Username: $username<br>
                Full Name: $fullname<br>";
            if ($change_password) {
                $msg .= "Password: <span class='text-green'>Updated</span><br>";
            }
            $msg .= "</div></div>";
            echo "<script>setTimeout(function() { window.location.href = 'admin_users.php?view=' + $user_id; }, 3000);</script>";
        } else {
            $show_message = true;
            $msg_type = 'error';
            $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                <h4>Error updating user</h4>
                <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
            $edit_user_id = $user_id;
        }
    }
}

// Handle enable/disable user (only admins with manage_users)
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (!$can_manage_users) {
        set_flash_message('danger', 'You do not have permission to manage users.');
        header('Location: admin_users.php');
        exit;
    }
    
    $user_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($user_id == $current_user_id) {
        $show_message = true;
        $msg_type = 'warning';
        $msg = "<div class='alert alert-warning' style='border:2px solid #ff9900;background-color:#1a0f00;color:#ffcc66;font-family:monospace;'>
            <h4><span class='glyphicon glyphicon-warning-sign'></span> Cannot modify your own account!</h4>
            <div class='terminal-feed'>
            You cannot enable/disable your own account for security reasons.
            </div></div>";
    } else {
        if ($action === 'disable') {
            $result = mysqli_query($conn, "UPDATE users_admin SET password = '' WHERE id = $user_id");
            if ($result) {
                $show_message = true;
                $msg_type = 'success';
                $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                    <h4><span class='glyphicon glyphicon-ok-circle'></span> User Disabled!</h4>
                    <div class='terminal-feed'>
                    User has been disabled. They will not be able to log in until a new password is set.
                    </div></div>";
            }
        } elseif ($action === 'enable') {
            header('Location: admin_users.php?edit=' . $user_id);
            exit;
        }
    }
}

// Check if add form should be shown (only for admins)
$show_add_form = isset($_GET['add']) && $can_manage_users;
$show_add_form = $show_add_form || (isset($show_add_form) && $show_add_form);

// Handle search (only for admins)
$search_query = '';
$where_clause = '';
$total_users = 0;
$total_pages = 0;
$users = array();

if ($can_manage_users) {
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, trim($_GET['search']));
        $search_query = "&search=" . urlencode($search);
        $where_clause = "WHERE 
            username LIKE '%$search%' OR
            fullname LIKE '%$search%'";
    }

    $limit = 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $count_result = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM users_admin
        $where_clause
    ");
    $count_row = mysqli_fetch_assoc($count_result);
    $total_users = $count_row['total'];
    $total_pages = ceil($total_users / $limit);

    $users_result = mysqli_query($conn, "
        SELECT * 
        FROM users_admin
        $where_clause
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    while ($row = mysqli_fetch_assoc($users_result)) {
        $users[] = $row;
    }
}

function get_user_status($password, $user_id, $current_user_id) {
    if (empty($password)) {
        return '<span class="badge badge-danger">Disabled</span>';
    } else {
        if ($user_id == $current_user_id) {
            return '<span class="badge badge-success">Active (You)</span>';
        } else {
            return '<span class="badge badge-success">Active</span>';
        }
    }
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <?php if ($show_message && $msg): ?>
            <?php echo $msg; ?>
        <?php endif; ?>

        <!-- ============================================================
             VIEW USER PROFILE
             ============================================================ -->
        <?php if ($current_user_data && $can_view_profile && $view_user_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal">
                            <i class="fas fa-user"></i> 
                            <?php if ($view_user_id == $current_user_id): ?>
                                My Profile
                            <?php else: ?>
                                User Profile: <?php echo h($current_user_data['username']); ?>
                            <?php endif; ?>
                            <a href="admin_users.php" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $created_date = date('M d, Y, g:i a', strtotime($current_user_data['created_at']));
                        $status_display = empty($current_user_data['password']) ? 'Disabled' : 'Active';
                        $status_class = empty($current_user_data['password']) ? 'danger' : 'success';
                        ?>
                       
                        <div class="row">
                            <div class="col-md-8">
                                <h3><?php echo h($current_user_data['fullname']); ?></h3>
                                <p class="text-muted">
                                    User ID: <?php echo h($current_user_data['id']); ?> | 
                                    Created: <?php echo h($created_date); ?>
                                    <?php if ($view_user_id == $current_user_id): ?>
                                        <span class="badge badge-info ml-2">You</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-<?php echo h($status_class); ?>">
                                    <?php echo h($status_display); ?>
                                </span>
                                <?php if ($can_edit_profile): ?>
                                    <a href="admin_users.php?edit=<?php echo h($view_user_id); ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-user-circle"></i> User Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Username:</strong></td>
                                                <td><?php echo h($current_user_data['username']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Full Name:</strong></td>
                                                <td><?php echo h($current_user_data['fullname']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Role:</strong></td>
                                                <td><?php echo get_role_display(isset($current_user_data['role']) ? $current_user_data['role'] : null); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td>
                                                    <span class="badge badge-<?php echo h($status_class); ?>">
                                                        <?php echo h($status_display); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Account Created:</strong></td>
                                                <td><?php echo h($created_date); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-shield-alt"></i> Security</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Account Status:</strong></td>
                                                <td>
                                                    <?php if (empty($current_user_data['password'])): ?>
                                                        <span class="badge badge-danger">Disabled</span>
                                                        <p class="text-danger mt-1"><small>User cannot log in</small></p>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Active</span>
                                                        <p class="text-success mt-1"><small>User can log in</small></p>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Password Set:</strong></td>
                                                <td>
                                                    <?php if (empty($current_user_data['password'])): ?>
                                                        <span class="badge badge-warning">No</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Yes</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <?php if ($can_edit_profile): ?>
                                <a href="admin_users.php?edit=<?php echo h($current_user_data['id']); ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($can_manage_users && $view_user_id != $current_user_id): ?>
                                <?php if (empty($current_user_data['password'])): ?>
                                    <a href="admin_users.php?action=enable&id=<?php echo h($current_user_data['id']); ?>" 
                                       class="btn btn-success">
                                        <i class="fas fa-play"></i> Enable User
                                    </a>
                                <?php else: ?>
                                    <a href="javascript:void(0)" class="btn btn-danger" 
                                       onclick="showConfirmModal('Are you sure you want to disable <strong><?php echo h($current_user_data['username']); ?></strong>?<br><small>They will not be able to log in until a new password is set.</small>', 'admin_users.php?action=disable&id=<?php echo h($current_user_data['id']); ?>', 'This will prevent the user from logging in.', 'btn-warning', 'fa-user-slash', '#ffcc00', 'Disable User');">
                                        <i class="fas fa-stop"></i> Disable User
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="admin_users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($view_user_id > 0 && !$can_view_profile): ?>
        <!-- Access Denied -->
        <div class="alert alert-danger" style="border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;">
            <h4><i class="fas fa-exclamation-triangle"></i> Access Denied</h4>
            <p>You don't have permission to view this user's profile.</p>
            <a href="admin_users.php" class="btn btn-primary">Back to User List</a>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             EDIT USER FORM
             ============================================================ -->
        <?php if ($current_user_data && $can_edit_profile && $edit_user_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>
                            <i class="fas fa-edit"></i> 
                            <?php if ($edit_user_id == $current_user_id): ?>
                                Edit My Profile
                            <?php else: ?>
                                Edit User: <?php echo h($current_user_data['username']); ?>
                            <?php endif; ?>
                            <a href="admin_users.php" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="update_user" value="1">
                            <input type="hidden" name="user_id" value="<?php echo h($edit_user_id); ?>">
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Username *</label>
                                        <input type="text" name="username" class="form-control hacker-input"
                                               value="<?php echo h($current_user_data['username']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Full Name *</label>
                                        <input type="text" name="fullname" class="form-control hacker-input"
                                               value="<?php echo h($current_user_data['fullname']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hacker-label">Role *</label>
                                        <select name="role" class="form-control hacker-input">
                                            <?php 
                                            $current_role = isset($current_user_data['role']) ? $current_user_data['role'] : '';
                                            foreach ($all_roles as $role): 
                                            ?>
                                            <option value="<?php echo h($role['name']); ?>" <?php echo $current_role === $role['name'] ? 'selected' : ''; ?>>
                                                <?php echo h($role['display_name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Controls which admin pages this user can access</small>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="change_password" id="edit_change_password" value="1" 
                                               <?php echo empty($current_user_data['password']) ? 'checked' : ''; ?>
                                               class="form-check-input" onchange="togglePasswordFields()">
                                        <label class="form-check-label" for="edit_change_password">
                                            <strong>Change Password</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                           
                            <div id="password_fields" style="<?php echo empty($current_user_data['password']) ? '' : 'display: none;'; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">New Password *</label>
                                            <input type="password" name="password" id="edit_password" class="form-control hacker-input" 
                                                   <?php echo empty($current_user_data['password']) ? 'required' : ''; ?> placeholder="Enter new password">
                                            <small class="text-muted">Minimum 6 characters</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="hacker-label">Confirm Password *</label>
                                            <input type="password" name="confirm_password" id="edit_confirm_password" class="form-control hacker-input" 
                                                   <?php echo empty($current_user_data['password']) ? 'required' : ''; ?> placeholder="Confirm new password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="text-right">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="admin_users.php?view=<?php echo h($edit_user_id); ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($edit_user_id > 0 && !$can_edit_profile): ?>
        <!-- Access Denied -->
        <div class="alert alert-danger" style="border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;">
            <h4><i class="fas fa-exclamation-triangle"></i> Access Denied</h4>
            <p>You don't have permission to edit this user's profile.</p>
            <a href="admin_users.php" class="btn btn-primary">Back to User List</a>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             MAIN USER LIST - Only for users with manage_users permission
             ============================================================ -->
        <?php if ($can_manage_users && !$current_user_data): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal">
                            <i class="fas fa-users-cog"></i> Users Management
                        </h4>
                    </div>
                    <div class="card-body">
                       
                        <!-- Search and Actions -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="get" action="admin_users.php">
                                    <div class="input-group hacker-input-group">
                                        <input type="text" 
                                               name="search" 
                                               class="form-control hacker-input" 
                                               placeholder="Search by username or full name..."
                                               value="<?php echo isset($_GET['search']) ? h($_GET['search']) : ''; ?>"
                                               aria-label="Search users">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" aria-label="Search">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                <a href="admin_users.php" class="btn btn-default" aria-label="Clear search">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                        <small class="text-muted">
                                            Found <?php echo h($total_users); ?> user(s) matching "<?php echo h($_GET['search']); ?>"
                                        </small>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="?add=1" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Add New User
                                </a>
                            </div>
                        </div>
                        <hr class="border-terminal">

                        <!-- INLINE ADD USER FORM -->
                        <?php if ($show_add_form): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h4><i class="fas fa-user-plus"></i> Add New Admin User</h4>
                            </div>
                            <div class="card-body">
                                <form method="post" onsubmit="return validateAddUserForm()">
                                    <input type="hidden" name="add_user" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Username *</label>
                                                <input type="text" name="username" id="new_username" class="form-control hacker-input" required 
                                                       autocomplete="username" placeholder="Enter username"
                                                       value="<?php echo isset($form_data['username']) ? h($form_data['username']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Full Name *</label>
                                                <input type="text" name="fullname" id="new_fullname" class="form-control hacker-input" required 
                                                       autocomplete="name" placeholder="Enter full name"
                                                       value="<?php echo isset($form_data['fullname']) ? h($form_data['fullname']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Role *</label>
                                                <select name="role" class="form-control hacker-input">
                                                    <?php foreach ($all_roles as $role): ?>
                                                    <option value="<?php echo h($role['name']); ?>" <?php echo $role['name'] === 'receptionist' ? 'selected' : ''; ?>>
                                                        <?php echo h($role['display_name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="text-muted">Controls which admin pages this user can access</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Password *</label>
                                                <input type="password" name="password" id="new_password" class="form-control hacker-input" required 
                                                       placeholder="Enter password">
                                                <small class="text-muted">Minimum 6 characters</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Confirm Password *</label>
                                                <input type="password" name="confirm_password" id="new_confirm_password" class="form-control hacker-input" 
                                                       required placeholder="Confirm password">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Add User
                                        </button>
                                        <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = $offset + 1;
                                    if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-5">
                                                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                    <i class="fas fa-search"></i><br>
                                                    No users found for "<?php echo h($_GET['search']); ?>"
                                                <?php else: ?>
                                                    <i class="fas fa-users"></i><br>
                                                    No users found. Add your first user.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($users as $user): ?>
                                    <tr <?php echo $view_user_id == $user['id'] || $edit_user_id == $user['id'] ? 'class="table-info"' : ''; ?>>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong class="text-terminal"><?php echo h($user['username']); ?></strong></td>
                                        <td><?php echo h($user['fullname']); ?></td>
                                        <td><?php echo get_role_display(isset($user['role']) ? $user['role'] : null); ?></td>
                                        <td><?php echo get_user_status($user['password'], $user['id'], $current_user_id); ?></td>
                                        <td><small><?php echo date('M d, Y, g:i a', strtotime($user['created_at'])); ?></small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <!-- VIEW - everyone can view any profile? Actually only if they have manage_users -->
                                                <a href="admin_users.php?view=<?php echo h($user['id']); ?>" 
                                                   class="btn btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <!-- EDIT - only if can manage users -->
                                                <a href="admin_users.php?edit=<?php echo h($user['id']); ?>" 
                                                   class="btn btn-warning" title="Edit User">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                
                                                <?php if ($user['id'] != $current_user_id): ?>
                                                    <?php if (empty($user['password'])): ?>
                                                        <a href="admin_users.php?action=enable&id=<?php echo h($user['id']); ?>" 
                                                           class="btn btn-success" 
                                                           title="Enable User">
                                                            <i class="fas fa-play"></i> Enable
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="javascript:void(0)" class="btn btn-danger" 
                                                           title="Disable User"
                                                           onclick="showConfirmModal('Are you sure you want to disable <strong><?php echo h($user['username']); ?></strong>?<br><small>They will not be able to log in until a new password is set.</small>', 'admin_users.php?action=disable&id=<?php echo h($user['id']); ?>', 'This will prevent the user from logging in.', 'btn-warning', 'fa-user-slash', '#ffcc00', 'Disable User');">
                                                            <i class="fas fa-stop"></i> Disable
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled title="Current User">
                                                        <i class="fas fa-user"></i> You
                                                    </button>
                                                <?php endif; ?>
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
                            <nav aria-label="User pagination">
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
        <?php elseif (!$can_manage_users && !$current_user_data): ?>
        <!-- ============================================================
             USERS WITHOUT MANAGE_USERS PERMISSION - Show only their own profile
             ============================================================ -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal">
                            <i class="fas fa-user"></i> My Profile
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch current user's data
                        $my_sql = "SELECT * FROM users_admin WHERE id = $current_user_id";
                        $my_result = mysqli_query($conn, $my_sql);
                        $my_user = mysqli_fetch_assoc($my_result);
                        
                        if ($my_user):
                            $created_date = date('M d, Y, g:i a', strtotime($my_user['created_at']));
                            $status_class = empty($my_user['password']) ? 'danger' : 'success';
                            $status_display = empty($my_user['password']) ? 'Disabled' : 'Active';
                        ?>
                        <div class="row">
                            <div class="col-md-8">
                                <h3><?php echo h($my_user['fullname']); ?></h3>
                                <p class="text-muted">Username: <?php echo h($my_user['username']); ?></p>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-<?php echo h($status_class); ?>">
                                    <?php echo h($status_display); ?>
                                </span>
                                <a href="admin_users.php?edit=<?php echo h($current_user_id); ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td><?php echo get_role_display(isset($my_user['role']) ? $my_user['role'] : null); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Account Created:</strong></td>
                                        <td><?php echo h($created_date); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo h($status_class); ?>">
                                                <?php echo h($status_display); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="admin_users.php?edit=<?php echo h($current_user_id); ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                            <a href="admin_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
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
function validateAddUserForm() {
    const password = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_confirm_password');
    const username = document.getElementById('new_username');
    const fullname = document.getElementById('new_fullname');
    
    if (!password || !confirmPassword || !username || !fullname) {
        return true;
    }
    
    if (password.value.length < 6) {
        alert('Password must be at least 6 characters');
        return false;
    }
    
    if (password.value !== confirmPassword.value) {
        alert('Passwords do not match');
        return false;
    }
    
    if (!username.value.trim()) {
        alert('Username is required');
        return false;
    }
    
    if (!fullname.value.trim()) {
        alert('Full name is required');
        return false;
    }
    
    const submitBtn = document.querySelector('form[onsubmit="return validateAddUserForm()"] button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }
    
    return true;
}

function togglePasswordFields() {
    const checkbox = document.getElementById('edit_change_password');
    const passwordFields = document.getElementById('password_fields');
    const passwordInput = document.getElementById('edit_password');
    const confirmPasswordInput = document.getElementById('edit_confirm_password');
    
    if (checkbox && passwordFields && passwordInput && confirmPasswordInput) {
        if (checkbox.checked) {
            passwordFields.style.display = 'block';
            passwordInput.required = true;
            confirmPasswordInput.required = true;
        } else {
            passwordFields.style.display = 'none';
            passwordInput.required = false;
            confirmPasswordInput.required = false;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editCheckbox = document.getElementById('edit_change_password');
    if (editCheckbox) {
        editCheckbox.addEventListener('change', togglePasswordFields);
        togglePasswordFields();
    }
    
    const addUsername = document.getElementById('new_username');
    if (addUsername) {
        addUsername.focus();
    }
    
    const editUsername = document.querySelector('input[name="username"]');
    if (editUsername) {
        editUsername.focus();
    }
});
</script>

<?php include __DIR__.'/footer.php'; ?>