<?php
// admin_invoice_management.php - NO MODALS, DIRECT LINKS
require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

include __DIR__.'/header.php';

$msg = '';
$msg_type = 'info';

// Check for URL alert parameters
if (isset($_GET['alert'])) {
    $msg = urldecode($_GET['alert']);
    $msg_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Handle search
$search_query = '';
$where_conditions = array("1 = 1"); // Start with always true condition

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where_conditions[] = "(i.invoice_number LIKE '%{$search_query}%'
        OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%{$search_query}%'
        OR c.email LIKE '%{$search_query}%'
        OR r.room_number LIKE '%{$search_query}%'
        OR b.name LIKE '%{$search_query}%')";
}

// Handle filter by payment status - FIXED VERSION
$status_filter = '';
if (isset($_GET['status']) && in_array($_GET['status'], array('paid', 'unpaid'))) {
    if ($_GET['status'] == 'paid') {
        // Show paid invoices (booking paid AND invoice paid)
        $where_conditions[] = "bk.status = 'paid'";
        $where_conditions[] = "i.paid = 1";
        $status_filter = 'paid';
    } else {
        // Show unpaid invoices: confirmed bookings OR paid bookings with unpaid invoice
        $where_conditions[] = "(bk.status = 'confirmed' OR (bk.status = 'paid' AND i.paid = 0))";
        $status_filter = 'unpaid';
    }
} else {
    // Default: show all invoices for paid and confirmed bookings
    $where_conditions[] = "bk.status IN ('paid', 'confirmed')";
}

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Build SQL query for total count
$count_sql = "
    SELECT COUNT(*) as total
    FROM invoices i
    JOIN bookings bk ON i.booking_id = bk.id
    JOIN customers c ON bk.customer_id = c.id
    JOIN rooms r ON bk.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE " . implode(' AND ', $where_conditions);

$count_result = mysqli_query($conn, $count_sql);
$total_count = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_count / $per_page);

// Build SQL query for invoices with pagination
$sql = "
    SELECT
        i.*,
        i.paid as invoice_paid,
        i.paid_at,
        CONCAT(c.firstname, ' ', c.lastname) as customer_name,
        c.email as customer_email,
        c.phone as customer_phone,
        r.room_number,
        b.name as building_name,
        bk.checkin_date,
        bk.checkout_date,
        bk.amount as booking_amount,
        bk.status as booking_status,
        bk.nights,
        (SELECT COUNT(*) FROM payments p WHERE p.invoice_id = i.id AND p.status = 'completed') as completed_payments,
        (SELECT COUNT(*) FROM payments p WHERE p.invoice_id = i.id) as total_payments,
        (SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id AND p.status = 'completed') as total_paid_amount
    FROM invoices i
    JOIN bookings bk ON i.booking_id = bk.id
    JOIN customers c ON bk.customer_id = c.id
    JOIN rooms r ON bk.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE " . implode(' AND ', $where_conditions) . "
    ORDER BY i.issued_at DESC, i.paid ASC
    LIMIT $offset, $per_page
";

// Fetch invoices
$invoices_result = mysqli_query($conn, $sql);
$invoices = array();
while ($row = mysqli_fetch_assoc($invoices_result)) {
    $invoices[] = $row;
}

// Function to get payment status badge
function get_payment_status_badge($paid) {
    return $paid == 1 
        ? '<span class="badge badge-success">PAID</span>' 
        : '<span class="badge badge-danger">UNPAID</span>';
}
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <!-- Success Notification Container -->
        <div id="success-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: none;">
            <div class="alert alert-success alert-dismissible" style="min-width: 300px;">
                <button type="button" class="close" onclick="hideSuccessNotification()">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4><i class="fas fa-check"></i> Success!</h4>
                <div id="success-message"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-terminal"><i class="fas fa-file-invoice"></i> Invoice Management</h4>
                    </div>
                    <div class="card-body" id="invoices-container">
                        <?php if ($msg): ?>
                        <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4>
                                <?php if ($msg_type == 'success'): ?>
                                <i class="fas fa-check-circle"></i> Success!
                                <?php elseif ($msg_type == 'error'): ?>
                                <i class="fas fa-exclamation-circle"></i> Error!
                                <?php else: ?>
                                <i class="fas fa-info-circle"></i> Info
                                <?php endif; ?>
                            </h4>
                            <div class="terminal-feed">
                                <?php echo $msg; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Search and Filter -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="btn-group" role="group" id="filter-buttons">
                                    <a href="admin_invoice_management.php" class="btn btn-<?php echo !$status_filter ? 'warning' : 'default'; ?>">
                                        <i class="fas fa-list"></i> All Invoices
                                    </a>
                                    <a href="admin_invoice_management.php?status=paid<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" 
                                       class="btn btn-<?php echo $status_filter == 'paid' ? 'warning' : 'default'; ?>">
                                        <i class="fas fa-check"></i> Paid Only
                                    </a>
                                    <a href="admin_invoice_management.php?status=unpaid<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" 
                                       class="btn btn-<?php echo $status_filter == 'unpaid' ? 'warning' : 'default'; ?>">
                                        <i class="fas fa-clock"></i> Unpaid Only
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <form id="search-form" method="GET" action="" class="form-inline">
                                    <div class="input-group hacker-input-group w-100">
                                        <input type="text" 
                                               name="search" 
                                               id="search-input"
                                               class="form-control hacker-input" 
                                               placeholder="Search invoice, guest, room..."
                                               value="<?php echo h($search_query); ?>"
                                               aria-label="Search invoices">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" aria-label="Search">
                                                <i class="fas fa-search"></i>
                                            </button>
                                            <?php if ($search_query): ?>
                                                <a href="admin_invoice_management.php" class="btn btn-secondary" aria-label="Clear search">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($status_filter): ?>
                                    <input type="hidden" name="status" value="<?php echo h($status_filter); ?>">
                                    <?php endif; ?>
                                </form>
                                <div id="search-info" style="display: <?php echo $search_query ? 'block' : 'none'; ?>; margin-top: 5px;">
                                    <small class="text-muted">
                                        Found <?php echo h($total_count); ?> invoice<?php echo $total_count != 1 ? 's' : ''; ?>
                                        <?php if ($search_query): ?>
                                        matching "<?php echo h($search_query); ?>"
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Invoices Table -->
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-bordered" id="invoices-table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Issued</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="invoices-tbody">
                                    <?php if (count($invoices) > 0): ?>
                                        <?php foreach ($invoices as $invoice): ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-terminal"><?php echo h($invoice['invoice_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo h($invoice['customer_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo h($invoice['customer_email']); ?></small><br>
                                                    <small class="text-muted"><?php echo h($invoice['customer_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo h($invoice['room_number']); ?></span><br>
                                                    <small class="text-muted"><?php echo h($invoice['building_name']); ?></small>
                                                </td>
                                                <td>
                                                    <strong class="text-warning">₦<?php echo number_format($invoice['amount'], 2); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo h($invoice['nights']); ?> night<?php echo $invoice['nights'] != 1 ? 's' : ''; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo get_payment_status_badge($invoice['invoice_paid']); ?>
                                                    <?php if ($invoice['invoice_paid'] == 1 && $invoice['paid_at']): ?>
                                                    <br><small class="text-muted"><?php echo date('M d, Y', strtotime($invoice['paid_at'])); ?></small>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted">Booking: <?php echo h($invoice['booking_status']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($invoice['issued_at'])); ?>
                                                </td>
                                                <td class="invoice-actions">
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($invoice['invoice_paid'] != 1): ?>
                                                        <a href="admin_mark_paid.php?invoice_id=<?php echo h($invoice['id']); ?>" 
                                                           class="btn btn-success"
                                                           title="Mark Invoice as Paid">
                                                            <i class="fas fa-money-bill-wave"></i> Mark Paid
                                                        </a>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-secondary" disabled title="Already Paid">
                                                            <i class="fas fa-check"></i> Paid
                                                        </button>
                                                        <?php endif; ?>
                                                        
                                                        <a href="admin_view_invoice.php?invoice_id=<?php echo h($invoice['id']); ?>" 
                                                           class="btn btn-primary" title="View Invoice">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                       
                                                        <!-- Link to booking for checkout management -->
                                                        <a href="admin_view_booking.php?booking_id=<?php echo h($invoice['booking_id']); ?>" 
                                                           class="btn btn-info" title="Go to Booking">
                                                            <i class="fas fa-calendar-alt"></i> Booking
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-5">
                                                <?php if ($search_query): ?>
                                                    <i class="fas fa-search"></i><br>
                                                    No invoices found for "<?php echo h($search_query); ?>"
                                                <?php elseif ($status_filter == 'paid'): ?>
                                                    <i class="fas fa-search"></i><br>
                                                    No paid invoices found
                                                <?php elseif ($status_filter == 'unpaid'): ?>
                                                    <i class="fas fa-search"></i><br>
                                                    No unpaid invoices found
                                                <?php else: ?>
                                                    <i class="fas fa-list"></i><br>
                                                    No invoices found
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div id="pagination-container" class="text-center">
                            <nav aria-label="Invoice pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo h($status_filter); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span></a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link" aria-hidden="true">&laquo;</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo h($i); ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo h($status_filter); ?>">
                                                    <?php echo h($i); ?>
                                                </a>
                                            </li>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo h($status_filter); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span></a>
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
    </div>
</div>

<script>
// Success notification functions
function showSuccessNotification(message) {
    const notification = document.getElementById('success-notification');
    const messageDiv = document.getElementById('success-message');
    
    messageDiv.innerHTML = message;
    notification.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(hideSuccessNotification, 5000);
}

function hideSuccessNotification() {
    document.getElementById('success-notification').style.display = 'none';
}

// Clean up URL parameters
document.addEventListener('DOMContentLoaded', function() {
    // Remove alert parameters from URL
    if (window.history.replaceState && (window.location.search.includes('alert=') || window.location.search.includes('type='))) {
        var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        
        var searchParam = new URLSearchParams(window.location.search).get('search');
        var statusParam = new URLSearchParams(window.location.search).get('status');
        var pageParam = new URLSearchParams(window.location.search).get('page');
        
        var params = [];
        if (searchParam) params.push('search=' + encodeURIComponent(searchParam));
        if (statusParam) params.push('status=' + statusParam);
        if (pageParam) params.push('page=' + pageParam);
        
        if (params.length > 0) {
            cleanUrl += '?' + params.join('&');
        }
        
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    // Auto-hide success alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert.alert-dismissible');
        alerts.forEach(function(alert) {
            const closeBtn = alert.querySelector('.close');
            if (closeBtn) {
                closeBtn.click();
            }
        });
    }, 5000);
});
</script>

<?php include __DIR__.'/footer.php'; ?>