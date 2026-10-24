<?php
// admin_logs.php - View Admin Activity Logs
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// ============================================================
// EXCEL EXPORT
// ============================================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
    $admin_filter = isset($_GET['admin']) ? (int)$_GET['admin'] : 0;
    $action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';

    $where = array();
    if ($search) $where[] = "(l.action LIKE '%$search%' OR l.description LIKE '%$search%' OR l.admin_name LIKE '%$search%')";
    if ($admin_filter > 0) $where[] = "l.admin_id = $admin_filter";
    if ($action_filter) $where[] = "l.action = '$action_filter'";
    $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT
                DATE_FORMAT(l.created_at, '%Y-%m-%d %H:%i:%s') as 'Date/Time',
                l.admin_name as 'Admin',
                l.action as 'Action',
                l.description as 'Description',
                l.ip_address as 'IP Address'
            FROM admin_logs l
            $where_clause
            ORDER BY l.created_at DESC";

    $result = mysqli_query($conn, $sql);
    $data = array();
    $headers = array();
    if ($result && mysqli_num_rows($result) > 0) {
        $first_row = mysqli_fetch_assoc($result);
        $headers = array_keys($first_row);
        $data[] = $first_row;
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }

    $config = include __DIR__ . '/config.php';
    $hotel_name = isset($config['hotel']['name']) ? $config['hotel']['name'] : 'Hotel';
    $clean_hotel_name = preg_replace('/[^a-zA-Z0-9]/', '_', $hotel_name);
    $filename = $clean_hotel_name . "_Activity_Logs_" . date('Y-m-d') . ".xls";

    export_to_excel($data, $headers, $filename, 'Activity Logs');
    exit;
}

include __DIR__ . '/header.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$admin_filter = isset($_GET['admin']) ? (int)$_GET['admin'] : 0;
$action_filter = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$where = [];
if ($search) $where[] = "(l.action LIKE '%$search%' OR l.description LIKE '%$search%' OR l.admin_name LIKE '%$search%')";
if ($admin_filter > 0) $where[] = "l.admin_id = $admin_filter";
if ($action_filter) $where[] = "l.action = '$action_filter'";

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM admin_logs l $where_clause"))['cnt'];
$total_pages = ceil($total / $per_page);

// Fetch logs
$sql = "SELECT l.*, u.fullname 
        FROM admin_logs l
        LEFT JOIN users_admin u ON l.admin_id = u.id
        $where_clause
        ORDER BY l.created_at DESC
        LIMIT $offset, $per_page";

$result = mysqli_query($conn, $sql);

// Get admins for filter
$admins_result = mysqli_query($conn, "SELECT id, username, fullname FROM users_admin ORDER BY username");
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-history"></i> Admin Activity Logs</h4>
                    </div>
                    <div class="card-body">
                        <form method="get" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search action/description..." value="<?php echo h($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="admin" class="form-control">
                                        <option value="">All Admins</option>
                                        <?php while ($admin = mysqli_fetch_assoc($admins_result)): ?>
                                        <option value="<?php echo h($admin['id']); ?>" <?php echo $admin_filter == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($admin['fullname'] ?: $admin['username']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Filter</button>
                                    <a href="admin_logs.php" class="btn btn-secondary">Reset</a>
                                    <a href="?export=excel&search=<?php echo urlencode($search); ?>&admin=<?php echo h($admin_filter); ?>&action=<?php echo urlencode($action_filter); ?>"
                                       class="btn btn-success" style="background-color:#1d6f42;border-color:#1d6f42;">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-dark">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($log = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><strong><?php echo h($log['admin_name']); ?></strong></td>
                                        <td><?php echo h($log['action']); ?></td>
                                        <td><?php echo h($log['description'] ?: '-'); ?></td>
                                        <td><small><?php echo h($log['ip_address']); ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="5" class="text-center">No logs found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Add pagination links here similar to other pages -->
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>