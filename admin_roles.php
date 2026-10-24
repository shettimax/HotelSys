<?php
// admin_roles.php - Role & Permission Management
// MATCHING YOUR EXISTING STYLE - Using panel classes

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// Super admin only
$role_name = get_current_role_name();
if ($role_name !== 'super_admin') {
    set_flash_message('danger', 'Only Super Admin can manage roles and permissions.');
    header('Location: admin_dashboard.php');
    exit;
}

// Form data for repopulation
$form_data = array(
    'role_name' => '',
    'display_name' => '',
    'description' => '',
    'is_system' => 0
);

$show_add_form = isset($_GET['add']);
$edit_role_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing_role = null;
$expanded_role = isset($_GET['expand']) ? (int)$_GET['expand'] : 0;

// ============================================================
// BATCH PERMISSION OPERATIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['batch_permission'])) {
        $role_id = (int)$_POST['role_id'];
        $action = $_POST['batch_action'];
        $permission_ids = isset($_POST['permission_ids']) ? $_POST['permission_ids'] : array();
        
        if (empty($permission_ids)) {
            set_flash_message('danger', 'Please select at least one permission.');
            header('Location: admin_roles.php?expand=' . $role_id);
            exit;
        } else {
            $role_query = mysqli_query($conn, "SELECT name, display_name FROM roles WHERE id = $role_id");
            $role_data = mysqli_fetch_assoc($role_query);
            
            $success_count = 0;
            $fail_count = 0;
            
            foreach ($permission_ids as $perm_id) {
                $perm_id = (int)$perm_id;
                
                if ($action === 'grant') {
                    $result = assign_permission_to_role($role_id, $perm_id);
                } else {
                    $result = remove_permission_from_role($role_id, $perm_id);
                }
                
                if ($result) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }
            
            $action_label = $action === 'grant' ? 'Granted' : 'Revoked';
            $action_icon = $action === 'grant' ? 'granted to' : 'revoked from';
            
            log_admin_activity('Batch ' . ucfirst($action), 
                "{$action_label} " . count($permission_ids) . " permissions " . $action_icon . " '{$role_data['name']}' role");
            
            $msg = "{$action_label} " . count($permission_ids) . " permission(s) " . $action_icon . " '" . $role_data['display_name'] . "'";
            if ($fail_count > 0) {
                $msg .= " (" . $fail_count . " failed)";
            }
            $msg_type = $fail_count > 0 && $success_count > 0 ? 'warning' : 'success';
            
            set_flash_message($msg_type, $msg);
            header('Location: admin_roles.php?expand=' . $role_id);
            exit;
        }
    }
    
    if (isset($_POST['toggle_permission'])) {
        $role_id = (int)$_POST['role_id'];
        $permission_id = (int)$_POST['permission_id'];
        $action = $_POST['action'];
        
        $role_query = mysqli_query($conn, "SELECT name, display_name FROM roles WHERE id = $role_id");
        $role_data = mysqli_fetch_assoc($role_query);
        
        $perm_query = mysqli_query($conn, "SELECT name, display_name FROM permissions WHERE id = $permission_id");
        $perm_data = mysqli_fetch_assoc($perm_query);
        
        if ($action === 'add') {
            $result = assign_permission_to_role($role_id, $permission_id);
            if ($result) {
                log_admin_activity('Grant Permission', "Granted '{$perm_data['display_name']}' to '{$role_data['name']}'");
                set_flash_message('success', "Permission '" . $perm_data['display_name'] . "' granted successfully!");
            } else {
                set_flash_message('danger', "Failed to grant permission: " . mysqli_error($conn));
            }
        } else {
            $result = remove_permission_from_role($role_id, $permission_id);
            if ($result) {
                log_admin_activity('Revoke Permission', "Revoked '{$perm_data['display_name']}' from '{$role_data['name']}'");
                set_flash_message('success', "Permission '" . $perm_data['display_name'] . "' revoked successfully!");
            } else {
                set_flash_message('danger', "Failed to revoke permission: " . mysqli_error($conn));
            }
        }
        
        header('Location: admin_roles.php?expand=' . $role_id);
        exit;
    }
}

// Handle role creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $display_name = mysqli_real_escape_string($conn, trim($_POST['display_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $is_system = isset($_POST['is_system']) ? 1 : 0;
    
    $form_data = array(
        'role_name' => htmlspecialchars($_POST['role_name']),
        'display_name' => htmlspecialchars($_POST['display_name']),
        'description' => htmlspecialchars($_POST['description']),
        'is_system' => $is_system
    );
    
    if (empty($name) || empty($display_name)) {
        set_flash_message('danger', 'Role name and display name are required.');
        $show_add_form = true;
    } else {
        $check = mysqli_query($conn, "SELECT id FROM roles WHERE name = '$name'");
        if (mysqli_num_rows($check) > 0) {
            set_flash_message('danger', "Role '" . $name . "' already exists.");
            $show_add_form = true;
        } else {
            $sql = "INSERT INTO roles (name, display_name, description, is_system) 
                    VALUES ('$name', '$display_name', '$description', $is_system)";
            if (mysqli_query($conn, $sql)) {
                log_admin_activity('Create Role', "Created new role '{$display_name}'");
                set_flash_message('success', "Role '" . $display_name . "' created successfully!");
                header('Location: admin_roles.php');
                exit;
            } else {
                set_flash_message('danger', "Failed to create role: " . mysqli_error($conn));
                $show_add_form = true;
            }
        }
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $role_id = (int)$_POST['role_id'];
    $display_name = mysqli_real_escape_string($conn, trim($_POST['display_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    if (empty($display_name)) {
        set_flash_message('danger', 'Display name is required.');
        $edit_role_id = $role_id;
    } else {
        $sql = "UPDATE roles SET display_name = '$display_name', description = '$description' WHERE id = $role_id";
        if (mysqli_query($conn, $sql)) {
            log_admin_activity('Update Role', "Updated role '{$display_name}'");
            set_flash_message('success', "Role updated successfully!");
            header('Location: admin_roles.php?expand=' . $role_id);
            exit;
        } else {
            set_flash_message('danger', "Failed to update role: " . mysqli_error($conn));
            $edit_role_id = $role_id;
        }
    }
}

// Handle role deletion
if (isset($_GET['delete_role'])) {
    $role_id = (int)$_GET['delete_role'];
    
    $check = mysqli_query($conn, "SELECT id, name, is_system, display_name FROM roles WHERE id = $role_id");
    $role_data = $check ? mysqli_fetch_assoc($check) : null;
    
    if (!$role_data) {
        set_flash_message('danger', 'Role not found.');
    } elseif ($role_data['is_system'] == 1) {
        set_flash_message('danger', "Cannot delete system role '" . $role_data['display_name'] . "'.");
    } else {
        // Count assigned users
        if (db_column_exists($conn, 'users_admin', 'role_id')) {
            $user_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM users_admin WHERE role_id = $role_id");
        } else {
            $role_name_esc = mysqli_real_escape_string($conn, $role_data['name']);
            $user_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM users_admin WHERE role = '$role_name_esc'");
        }
        $user_count = $user_check ? (int)mysqli_fetch_assoc($user_check)['count'] : 0;
        
        if ($user_count > 0) {
            set_flash_message('danger', "Cannot delete role - " . $user_count . " user(s) assigned.");
        } else {
            mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $role_id");
            mysqli_query($conn, "DELETE FROM roles WHERE id = $role_id");
            log_admin_activity('Delete Role', "Deleted role '" . $role_data['display_name'] . "'");
            set_flash_message('success', "Role deleted successfully!");
        }
    }
    header('Location: admin_roles.php');
    exit;
}

// Fetch editing role data
if ($edit_role_id > 0) {
    $edit_result = mysqli_query($conn, "SELECT * FROM roles WHERE id = $edit_role_id");
    if (mysqli_num_rows($edit_result) > 0) {
        $editing_role = mysqli_fetch_assoc($edit_result);
    }
}

// Handle page mapping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['map_page'])) {
    $page = mysqli_real_escape_string($conn, trim($_POST['page']));
    $permission_id = (int)$_POST['permission_id'];
    $action = $_POST['map_action'];
    
    if ($action === 'add') {
        $sql = "INSERT IGNORE INTO page_permissions (page, permission_id) VALUES ('$page', $permission_id)";
        if (mysqli_query($conn, $sql)) {
            set_flash_message('success', "Page '" . $page . "' mapped successfully!");
        } else {
            set_flash_message('danger', "Failed to map page: " . mysqli_error($conn));
        }
    } else {
        $sql = "DELETE FROM page_permissions WHERE page = '$page'";
        if (mysqli_query($conn, $sql)) {
            set_flash_message('success', "Page '" . $page . "' unmapped successfully!");
        } else {
            set_flash_message('danger', "Failed to unmap page: " . mysqli_error($conn));
        }
    }
    header('Location: admin_roles.php');
    exit;
}

// Fetch all data
$roles = get_all_roles();
$permissions = get_all_permissions();

$role_permissions = [];
foreach ($roles as $role) {
    $role_permissions[$role['id']] = get_role_permissions($role['id']);
}

// Get page permissions
$page_permissions = [];
$sql = "SELECT pp.page, p.id as permission_id, p.display_name as permission_name 
        FROM page_permissions pp 
        JOIN permissions p ON pp.permission_id = p.id";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $page_permissions[$row['page']] = $row;
}

include __DIR__ . '/header.php';

// Calculate stats
$total_roles = count($roles);
$system_roles = 0;
$total_perms = count($permissions);
foreach ($roles as $r) {
    if ($r['is_system'] == 1) $system_roles++;
}
$mapped_pages = count($page_permissions);
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <!-- ============================================================
             NOTE: No inline alert here - footer.php handles it via modal
             ============================================================ -->

        <!-- HEADER -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="text-terminal"><i class="fas fa-lock"></i> Role & Permission Management</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <p class="text-muted">Manage roles and assign permissions to control admin access</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <?php if (!$show_add_form && !$edit_role_id): ?>
                            <a href="?add=1" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Role
                            </a>
                        <?php else: ?>
                            <a href="admin_roles.php" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- INLINE ADD ROLE FORM -->
        <?php if ($show_add_form): ?>
        <div class="panel panel-success">
            <div class="panel-heading">
                <h4><i class="fas fa-plus"></i> Add New Role</h4>
            </div>
            <div class="panel-body">
                <form method="post">
                    <input type="hidden" name="add_role" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="role_name" class="form-control" 
                                       placeholder="e.g., accountant" 
                                       value="<?php echo h($form_data['role_name']); ?>" required>
                                <small class="text-muted">System identifier (lowercase, no spaces)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="display_name" class="form-control" 
                                       placeholder="e.g., Accountant"
                                       value="<?php echo h($form_data['display_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" 
                                  placeholder="Brief description of this role's purpose"><?php echo h($form_data['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_system" value="1" <?php echo $form_data['is_system'] ? 'checked' : ''; ?>>
                                <i class="fas fa-shield-alt"></i> System Role (protected from deletion)
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Create Role
                        </button>
                        <a href="admin_roles.php" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- INLINE EDIT ROLE FORM -->
        <?php if ($edit_role_id && $editing_role): ?>
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4><i class="fas fa-edit"></i> Edit Role: <?php echo h($editing_role['display_name']); ?></h4>
            </div>
            <div class="panel-body">
                <form method="post">
                    <input type="hidden" name="update_role" value="1">
                    <input type="hidden" name="role_id" value="<?php echo h($editing_role['id']); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Role Name</label>
                                <input type="text" class="form-control" readonly 
                                       value="<?php echo h($editing_role['name']); ?>">
                                <small class="text-muted">Role name cannot be changed</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Display Name <span class="text-danger">*</span></label>
                                <input type="text" name="display_name" class="form-control" 
                                       value="<?php echo h($editing_role['display_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo h($editing_role['description']); ?></textarea>
                    </div>
                    
                    <div class="text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="admin_roles.php" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- QUICK STATS -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-body text-center">
                        <h3 class="text-terminal"><?php echo h($total_roles); ?></h3>
                        <p><i class="fas fa-user-tag"></i> Total Roles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-info">
                    <div class="panel-body text-center">
                        <h3 class="text-info"><?php echo h($system_roles); ?></h3>
                        <p><i class="fas fa-shield-alt"></i> System Roles</p>
                        <small class="text-muted">Protected</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-success">
                    <div class="panel-body text-center">
                        <h3 class="text-success"><?php echo h($total_perms); ?></h3>
                        <p><i class="fas fa-key"></i> Permissions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="panel panel-warning">
                    <div class="panel-body text-center">
                        <h3 class="text-warning"><?php echo h($mapped_pages); ?></h3>
                        <p><i class="fas fa-link"></i> Protected Pages</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROLES - COLLAPSED BY DEFAULT -->
        <?php foreach ($roles as $role): 
            $has_perms = $role_permissions[$role['id']];
            $is_system_role = $role['is_system'] == 1;
            $is_expanded = ($expanded_role == $role['id'] || $role['name'] == 'super_admin');
            $role_id = $role['id'];
        ?>
        <div class="panel panel-default role-panel" id="role-<?php echo h($role_id); ?>">
            <div class="panel-heading role-header" onclick="toggleRole(<?php echo h($role_id); ?>)">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="panel-title">
                            <i class="fas fa-chevron-<?php echo $is_expanded ? 'down' : 'right'; ?>" id="icon-<?php echo h($role_id); ?>"></i>
                            <i class="fas fa-user-tag"></i>
                            <?php echo h($role['display_name']); ?>
                            <?php if ($is_system_role): ?>
                                <span class="label label-info">System</span>
                            <?php endif; ?>
                            <small class="text-muted">(<?php echo h($role['name']); ?>)</small>
                            <span class="label label-default"><?php echo count($has_perms); ?> permissions</span>
                        </h4>
                        <small class="text-muted"><?php echo h($role['description']); ?></small>
                    </div>
                    <div class="col-md-4 text-right" onclick="event.stopPropagation();">
                        <?php if (!$is_system_role): ?>
                            <a href="?edit=<?php echo h($role_id); ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-sm"
                               onclick="showConfirmModal('Delete <strong><?php echo h($role['display_name']); ?></strong>?', 'admin_roles.php?delete_role=<?php echo h($role_id); ?>', 'This will permanently remove this role.', 'btn-danger', 'fa-trash', '#ff0000', 'Delete Role');">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="panel-body role-body" id="body-<?php echo h($role_id); ?>" style="<?php echo $is_expanded ? 'display:block;' : 'display:none;'; ?>">
                
                <!-- Batch Actions -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-default btn-sm" onclick="selectAll(<?php echo h($role_id); ?>)">
                                <i class="fas fa-check-double"></i> All
                            </button>
                            <button type="button" class="btn btn-default btn-sm" onclick="deselectAll(<?php echo h($role_id); ?>)">
                                <i class="fas fa-times"></i> None
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="batchGrant(<?php echo h($role_id); ?>)">
                                <i class="fas fa-check"></i> Grant Selected
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="batchRevoke(<?php echo h($role_id); ?>)">
                                <i class="fas fa-times"></i> Revoke Selected
                            </button>
                        </div>
                        <span class="text-muted ml-2" id="count_<?php echo h($role_id); ?>">0 selected</span>
                    </div>
                </div>
                
                <!-- Permissions Table -->
                <div class="table-responsive">
                    <form method="post" id="batchForm_<?php echo h($role_id); ?>">
                        <input type="hidden" name="role_id" value="<?php echo h($role_id); ?>">
                        <input type="hidden" name="batch_permission" value="1">
                        <input type="hidden" name="batch_action" id="batch_action_<?php echo h($role_id); ?>" value="">
                        
                        <table class="table table-dark table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th width="30">#</th>
                                    <th width="30%">Permission</th>
                                    <th width="20%">Category</th>
                                    <th width="20%">Status</th>
                                    <th width="30%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissions as $perm):
                                    $has_it = in_array($perm['name'], $has_perms);
                                    $is_protected = $is_system_role && $perm['is_system'] == 1;
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!$is_protected): ?>
                                            <input type="checkbox" name="permission_ids[]" 
                                                   value="<?php echo h($perm['id']); ?>"
                                                   class="perm-checkbox perm-checkbox-<?php echo h($role_id); ?>"
                                                   onchange="updateCount(<?php echo h($role_id); ?>)">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo h($perm['display_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo h($perm['name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="label label-default"><?php echo h($perm['category']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($has_it): ?>
                                            <span class="label label-success"><i class="fas fa-check"></i> Granted</span>
                                        <?php else: ?>
                                            <span class="label label-default"><i class="fas fa-times"></i> Denied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_protected): ?>
                                            <span class="text-muted"><i class="fas fa-shield-alt"></i> Protected</span>
                                        <?php elseif ($has_it): ?>
                                            <form method="post" style="display:inline;" id="revokeForm_<?php echo h($role_id . '_' . $perm['id']); ?>">
                                                <input type="hidden" name="role_id" value="<?php echo h($role_id); ?>">
                                                <input type="hidden" name="permission_id" value="<?php echo h($perm['id']); ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="toggle_permission" value="1">
                                                <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="showConfirmModalForm('Revoke <strong><?php echo h($perm['display_name']); ?></strong>?', 'revokeForm_<?php echo h($role_id . '_' . $perm['id']); ?>', 'This will remove this permission.', 'btn-danger', 'fa-times-circle', '#ff0000', 'Revoke Permission');">
                                                    <i class="fas fa-times"></i> Revoke
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display:inline;" id="grantForm_<?php echo h($role_id . '_' . $perm['id']); ?>">
                                                <input type="hidden" name="role_id" value="<?php echo h($role_id); ?>">
                                                <input type="hidden" name="permission_id" value="<?php echo h($perm['id']); ?>">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="toggle_permission" value="1">
                                                <button type="button" class="btn btn-success btn-sm"
                                                        onclick="showConfirmModalForm('Grant <strong><?php echo h($perm['display_name']); ?></strong>?', 'grantForm_<?php echo h($role_id . '_' . $perm['id']); ?>', 'This will add this permission.', 'btn-success', 'fa-check-circle', '#00ff99', 'Grant Permission');">
                                                    <i class="fas fa-plus"></i> Grant
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- PAGE PERMISSIONS - COLLAPSED BY DEFAULT -->
        <div class="panel panel-default mt-4">
            <div class="panel-heading" style="cursor:pointer;" onclick="togglePagePermissions()">
                <h4 class="panel-title">
                    <i class="fas fa-chevron-right" id="page-icon"></i>
                    <i class="fas fa-link"></i> Page Permission Mapping
                    <span class="label label-info"><?php echo count($page_permissions); ?> mapped</span>
                </h4>
                <small class="text-muted">Click to expand - Map which permission is required for each admin page</small>
            </div>
            <div class="panel-body" id="page-body" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th>Required Permission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admin_files = glob("admin_*.php");
                            sort($admin_files);
                            foreach ($admin_files as $file):
                                $page = basename($file);
                                if (in_array($page, ['admin_login.php', 'admin_logout.php', 'admin_roles.php', 'setup.php'])) continue;
                                
                                $has_mapping = isset($page_permissions[$page]);
                                $current_perm = $has_mapping ? $page_permissions[$page] : null;
                            ?>
                            <tr>
                                <td><code><?php echo h($page); ?></code></td>
                                <td>
                                    <?php if ($has_mapping): ?>
                                        <span class="label label-success"><?php echo h($current_perm['permission_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">No restriction</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($has_mapping): ?>
                                        <form method="post" style="display:inline;" id="unmapForm_<?php echo h($page); ?>">
                                            <input type="hidden" name="page" value="<?php echo h($page); ?>">
                                            <input type="hidden" name="map_action" value="remove">
                                            <input type="hidden" name="map_page" value="1">
                                            <button type="button" class="btn btn-warning btn-sm"
                                                    onclick="showConfirmModalForm('Unmap <strong><?php echo h($page); ?></strong>?', 'unmapForm_<?php echo h($page); ?>', 'Page will become accessible to all logged-in users.', 'btn-warning', 'fa-unlink', '#ffcc00', 'Unmap Page');">
                                                <i class="fas fa-unlink"></i> Unmap
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;" class="form-inline">
                                            <input type="hidden" name="page" value="<?php echo h($page); ?>">
                                            <input type="hidden" name="map_action" value="add">
                                            <input type="hidden" name="map_page" value="1">
                                            <select name="permission_id" class="form-control" style="color:#fff;background:#333;">
                                                <option value="">Select Permission</option>
                                                <?php foreach ($permissions as $perm): ?>
                                                    <option value="<?php echo h($perm['id']); ?>" style="color:#fff;background:#333;">
                                                        <?php echo h($perm['display_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-link"></i> Map
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- QUICK NAVIGATION -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h5><i class="fas fa-link"></i> Quick Navigation</h5>
                    </div>
                    <div class="panel-body">
                        <div class="row text-center">
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_dashboard.php" class="btn btn-default btn-block">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_users.php" class="btn btn-info btn-block">
                                    <i class="fas fa-users-cog"></i> Admin Users
                                </a>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_logs.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-history"></i> Activity Logs
                                </a>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_settings.php" class="btn btn-warning btn-block">
                                    <i class="fas fa-cog"></i> Hotel Settings
                                </a>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_reports.php" class="btn btn-success btn-block">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-2">
                                <a href="admin_roles.php" class="btn btn-danger btn-block active">
                                    <i class="fas fa-lock"></i> Roles
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
// ============================================================
// TOGGLE FUNCTIONS
// ============================================================

function toggleRole(roleId) {
    var body = document.getElementById('body-' + roleId);
    var icon = document.getElementById('icon-' + roleId);
    
    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        icon.className = 'fas fa-chevron-down';
    } else {
        body.style.display = 'none';
        icon.className = 'fas fa-chevron-right';
    }
}

function togglePagePermissions() {
    var body = document.getElementById('page-body');
    var icon = document.getElementById('page-icon');
    
    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        icon.className = 'fas fa-chevron-down';
    } else {
        body.style.display = 'none';
        icon.className = 'fas fa-chevron-right';
    }
}

// ============================================================
// BATCH CHECKBOX FUNCTIONS
// ============================================================

function updateCount(roleId) {
    var checkboxes = document.querySelectorAll('.perm-checkbox-' + roleId + ':checked');
    var count = checkboxes.length;
    document.getElementById('count_' + roleId).textContent = count + ' selected';
}

function selectAll(roleId) {
    var checkboxes = document.querySelectorAll('.perm-checkbox-' + roleId);
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    updateCount(roleId);
}

function deselectAll(roleId) {
    var checkboxes = document.querySelectorAll('.perm-checkbox-' + roleId);
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    updateCount(roleId);
}

function batchGrant(roleId) {
    var checked = document.querySelectorAll('.perm-checkbox-' + roleId + ':checked');
    if (checked.length === 0) {
        alert('Please select at least one permission to grant.');
        return;
    }
    
    document.getElementById('batch_action_' + roleId).value = 'grant';
    
    showConfirmModalForm(
        'Grant <strong>' + checked.length + '</strong> selected permission(s)?',
        'batchForm_' + roleId,
        'This will add all selected permissions to this role.',
        'btn-success',
        'fa-check-circle',
        '#00ff99',
        'Grant Permissions'
    );
}

function batchRevoke(roleId) {
    var checked = document.querySelectorAll('.perm-checkbox-' + roleId + ':checked');
    if (checked.length === 0) {
        alert('Please select at least one permission to revoke.');
        return;
    }
    
    document.getElementById('batch_action_' + roleId).value = 'revoke';
    
    showConfirmModalForm(
        'Revoke <strong>' + checked.length + '</strong> selected permission(s)?',
        'batchForm_' + roleId,
        'This will remove all selected permissions from this role.',
        'btn-danger',
        'fa-times-circle',
        '#ff0000',
        'Revoke Permissions'
    );
}

// Initialize counts
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($roles as $role): ?>
        updateCount(<?php echo h($role['id']); ?>);
    <?php endforeach; ?>
});
</script>

<style>
.role-header {
    cursor: pointer;
}
.role-header:hover {
    background: rgba(0, 255, 153, 0.05);
}
.role-body {
    background: rgba(0, 0, 0, 0.2);
}
</style>

<?php include __DIR__ . '/footer.php'; ?>