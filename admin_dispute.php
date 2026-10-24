<?php
// admin_dispute.php - Dispute Management for Overcharges, Refunds & Complaints
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
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'open';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

    $where_conditions = array("d.id IS NOT NULL");

    if ($status_filter === 'open') {
        $where_conditions[] = "d.status = 'open'";
    } elseif ($status_filter === 'resolved') {
        $where_conditions[] = "d.status = 'resolved'";
    }

    if (!empty($search)) {
        $where_conditions[] = "(i.invoice_number LIKE '%$search%' 
            OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%' 
            OR c.phone LIKE '%$search%' 
            OR d.subject LIKE '%$search%' 
            OR d.description LIKE '%$search%')";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $sql = "
        SELECT 
            d.id as 'Dispute ID',
            i.invoice_number as 'Invoice',
            CONCAT(c.firstname, ' ', c.lastname) as 'Guest',
            c.phone as 'Phone',
            c.email as 'Email',
            r.room_number as 'Room',
            bu.name as 'Building',
            d.subject as 'Subject',
            d.description as 'Description',
            d.status as 'Status',
            d.created_at as 'Reported',
            d.resolved_at as 'Resolved'
        FROM disputes d
        LEFT JOIN bookings b ON d.booking_id = b.id
        LEFT JOIN invoices i ON b.id = i.booking_id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
        ORDER BY d.created_at DESC
    ";

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
    $filename = $clean_hotel_name . "_Disputes_" . date('Y-m-d') . ".xls";

    export_to_excel($data, $headers, $filename, 'Disputes Report');
    exit;
}

include __DIR__ . '/header.php';

// Messages
$msg = '';
$msg_type = 'info';
if (isset($_GET['alert'])) {
    $msg = urldecode($_GET['alert']);
    $msg_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'open';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$where_conditions = ["d.id IS NOT NULL"];

if ($status_filter === 'open') {
    $where_conditions[] = "d.status = 'open'";
} elseif ($status_filter === 'resolved') {
    $where_conditions[] = "d.status = 'resolved'";
}

if (!empty($search)) {
    $where_conditions[] = "(i.invoice_number LIKE '%$search%' 
        OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%' 
        OR c.phone LIKE '%$search%' 
        OR d.subject LIKE '%$search%' 
        OR d.description LIKE '%$search%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Count total
$count_sql = "SELECT COUNT(*) as total 
              FROM disputes d
              LEFT JOIN bookings b ON d.booking_id = b.id
              LEFT JOIN invoices i ON b.id = i.booking_id
              LEFT JOIN customers c ON b.customer_id = c.id
              WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$total = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total / $per_page);

// Fetch disputes - UPDATED TO INCLUDE invoice_id
$sql = "SELECT d.*, i.id as invoice_id, i.invoice_number, 
               CONCAT(c.firstname, ' ', c.lastname) as customer_name,
               c.phone, c.email,
               b.checkin_date, b.checkout_date,
               r.room_number, bu.name as building_name
        FROM disputes d
        LEFT JOIN bookings b ON d.booking_id = b.id
        LEFT JOIN invoices i ON b.id = i.booking_id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
        ORDER BY d.created_at DESC
        LIMIT $offset, $per_page";

$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-exclamation-triangle"></i> Dispute Management</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($msg): ?>
                            <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo h($msg); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Filters -->
                        <form method="get" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control" onchange="this.form.submit()">
                                            <option value="all" <?php echo $status_filter=='all'?'selected':''; ?>>All</option>
                                            <option value="open" <?php echo $status_filter=='open'?'selected':''; ?>>Open</option>
                                            <option value="resolved" <?php echo $status_filter=='resolved'?'selected':''; ?>>Resolved</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Search</label>
                                        <input type="text" name="search" class="form-control" placeholder="Invoice, guest name, phone, subject..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Search</button>
                                        <a href="admin_dispute.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Reset</a>
                                        <a href="?export=excel&status=<?php echo h($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn btn-success" style="background-color:#1d6f42;border-color:#1d6f42;">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Stats -->
                        <?php
                        $open_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM disputes WHERE status='open'"))['cnt'];
                        $resolved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM disputes WHERE status='resolved'"))['cnt'];
                        ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card text-center border-warning">
                                    <div class="card-body">
                                        <h4 class="text-warning"><?php echo h($open_count); ?></h4>
                                        <p>Open Disputes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center border-success">
                                    <div class="card-body">
                                        <h4 class="text-success"><?php echo h($resolved_count); ?></h4>
                                        <p>Resolved</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center border-info">
                                    <div class="card-body">
                                        <h4 class="text-info"><?php echo $open_count + $resolved_count; ?></h4>
                                        <p>Total Disputes</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Disputes Table -->
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-dark">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <?php if ($row['invoice_number'] && $row['invoice_id']): ?>
                                                    <a href="admin_view_invoice.php?invoice_id=<?php echo h($row['invoice_id']); ?>" target="_blank"><?php echo h($row['invoice_number']); ?></a>
                                                <?php else: ?>
                                                    <em class="text-muted">No invoice</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['customer_name']): ?>
                                                    <?php echo h($row['customer_name']); ?><br>
                                                    <small><?php echo h($row['phone'] ?: 'N/A'); ?></small>
                                                <?php else: ?>
                                                    <em class="text-muted">Booking deleted</em>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['building_name'] ? h($row['building_name'] . ' - ' . $row['room_number']) : '<em>Unknown room</em>'; ?></td>
                                            <td><?php echo h($row['subject']); ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'open'): ?>
                                                    <span class="badge badge-danger">Open</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Resolved</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'open'): ?>
                                                    <a href="admin_resolve_dispute.php?dispute_id=<?php echo h($row['id']); ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> Resolve
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Resolved</span>
                                                    <br>
                                                    <small>
                                                        <a href="admin_resolve_dispute.php?dispute_id=<?php echo h($row['id']); ?>" class="text-info">
                                                            <i class="fas fa-eye"></i> View Details
                                                        </a>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo h($status_filter); ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo h($i); ?>&status=<?php echo h($status_filter); ?>&search=<?php echo urlencode($search); ?>"><?php echo h($i); ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo h($status_filter); ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <h5>No disputes found</h5>
                                <p>All clear! No pending customer complaints.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-link"></i> Quick Navigation</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-2">
                                <a href="admin_dashboard.php" class="btn btn-secondary btn-block"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="admin_booking_management.php" class="btn btn-info btn-block"><i class="fas fa-list"></i> Bookings</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="admin_invoice_management.php" class="btn btn-primary btn-block"><i class="fas fa-file-invoice"></i> Invoices</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="admin_refunds.php" class="btn btn-success btn-block"><i class="fas fa-money-bill-wave"></i> Refunds</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>