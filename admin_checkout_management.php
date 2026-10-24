<?php
// admin_checkout_management.php - FINAL POLISHED VERSION
// Only shows checked-in guests in Pending/Overdue
// Clear "Checked In" badges
// Actions only for checked-in guests
// Improved display of actual check-in time
// PHP 5.6 compatible, clean, professional

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Success message handling
$success_message = '';
$success_type = 'success';
if (isset($_GET['alert'])) {
    $success_message = urldecode($_GET['alert']);
    $success_type = isset($_GET['type']) ? $_GET['type'] : 'success';
}

// AJAX handler
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all';
    $search = isset($_REQUEST['search']) ? mysqli_real_escape_string($conn, trim($_REQUEST['search'])) : '';
    $page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
    $offset = ($page - 1) * $per_page;

    $where_conditions = array("b.status = 'paid'");

    // FIXED FILTER LOGIC - must be checked in to show in pending/overdue
    if ($filter == 'pending') {
        $where_conditions[] = "b.keycard_issued = 1";
        $where_conditions[] = "b.actual_checkin_datetime IS NOT NULL";
        $where_conditions[] = "(b.checkout_status IS NULL OR b.checkout_status != 'checked_out')";
    } elseif ($filter == 'completed') {
        $where_conditions[] = "b.checkout_status = 'checked_out'";
    } elseif ($filter == 'overdue') {
        $where_conditions[] = "b.keycard_issued = 1";
        $where_conditions[] = "b.actual_checkin_datetime IS NOT NULL";
        $where_conditions[] = "b.checkout_date < CURDATE()";
        $where_conditions[] = "(b.checkout_status IS NULL OR b.checkout_status != 'checked_out')";
    }

    if (!empty($search)) {
        $where_conditions[] = "(c.firstname LIKE '%$search%'
            OR c.lastname LIKE '%$search%'
            OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%'
            OR r.room_number LIKE '%$search%'
            OR bu.name LIKE '%$search%')";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Summary stats with corrected logic
    $summary_sql = "
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN b.keycard_issued = 1 
                     AND b.actual_checkin_datetime IS NOT NULL 
                     AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out') THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN b.checkout_status = 'checked_out' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN b.keycard_issued = 1 
                     AND b.actual_checkin_datetime IS NOT NULL 
                     AND b.checkout_date < CURDATE() 
                     AND (b.checkout_status IS NULL OR b.checkout_status != 'checked_out') THEN 1 ELSE 0 END) as overdue_count
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
    ";

    $summary_result = mysqli_query($conn, $summary_sql);
    $summary = mysqli_fetch_assoc($summary_result) ?: [
        'total_count' => 0, 
        'pending_count' => 0, 
        'completed_count' => 0, 
        'overdue_count' => 0
    ];

    $total_pages = ceil($summary['total_count'] / $per_page);

    // Main query with improved fields
    $sql = "
        SELECT 
            b.*,
            b.checkin_date,
            b.checkin_time,
            b.checkout_date,
            b.checkout_time,
            b.checkout_status,
            b.keycard_issued,
            b.actual_checkin_datetime,
            i.id as invoice_id,
            i.invoice_number,
            i.paid as invoice_paid,
            i.amount as invoice_amount,
            CONCAT(c.firstname, ' ', c.lastname) as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            r.room_number,
            r.capacity,
            r.price_per_night,
            bu.name as building_name,
            bu.building_type,
            ch.checkout_date as checkout_timestamp,
            ch.room_condition,
            ch.additional_charges,
            ch.refund_amount
        FROM bookings b
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        LEFT JOIN invoices i ON b.id = i.booking_id
        LEFT JOIN checkouts ch ON b.id = ch.booking_id
        WHERE $where_clause
        ORDER BY b.checkout_date ASC, b.checkout_status DESC
        LIMIT $offset, $per_page
    ";

    $result = mysqli_query($conn, $sql);

    $response = array(
        'summary' => array(
            'pending_count' => number_format($summary['pending_count']),
            'completed_count' => number_format($summary['completed_count']),
            'overdue_count' => number_format($summary['overdue_count']),
            'total_count' => number_format($summary['total_count']),
            'filter_text' => ($search ? "(Search: '$search')" : ($filter == 'all' ? '(All checkouts)' : "($filter)"))
        ),
        'html' => '',
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'has_previous' => $page > 1,
            'has_next' => $page < $total_pages
        )
    );

    if ($summary['total_count'] == 0) {
        $response['html'] = '<tr><td colspan="7" class="text-center p-5 text-muted">';
        if ($search) {
            $response['html'] .= 'No checkouts found for "' . h($search) . '"';
        } elseif ($filter == 'pending') {
            $response['html'] .= 'No pending checkouts (no guests currently checked in)';
        } elseif ($filter == 'completed') {
            $response['html'] .= 'No completed checkouts found';
        } elseif ($filter == 'overdue') {
            $response['html'] .= 'No overdue checkouts found';
        } else {
            $response['html'] .= 'No checkouts found';
        }
        $response['html'] .= '</td></tr>';
    } else {
        $current_date = date('Y-m-d');
        
        while ($booking = mysqli_fetch_assoc($result)) {
            $is_overdue = false;
            if ($booking['checkout_status'] != 'checked_out' && 
                strtotime($booking['checkout_date']) < strtotime($current_date) &&
                $booking['keycard_issued'] == 1 &&
                $booking['actual_checkin_datetime'] !== null) {
                $is_overdue = true;
            }
            
            $is_checked_in = ($booking['keycard_issued'] == 1 && $booking['actual_checkin_datetime'] !== null);
            
            $row_class = '';
            if ($is_overdue) {
                $row_class = 'table-danger';
            } elseif ($booking['checkout_status'] == 'checked_out') {
                $row_class = 'table-success';
            } elseif ($is_checked_in) {
                $row_class = 'table-info';
            }
            
            $response['html'] .= '<tr id="checkout-row-' . $booking['id'] . '" class="' . $row_class . '">';
            
            // Guest
            $response['html'] .= '<td>';
            $response['html'] .= '<strong>' . h($booking['customer_name']) . '</strong><br>';
            $response['html'] .= '<small class="text-muted">' . h($booking['customer_phone']) . '</small>';
            $response['html'] .= '</td>';
            
            // Room
            $response['html'] .= '<td>';
            $response['html'] .= '<span class="badge badge-info">' . h($booking['room_number']) . '</span><br>';
            $response['html'] .= '<small class="text-muted">' . h($booking['building_name']) . '</small>';
            if ($is_checked_in && $booking['checkout_status'] != 'checked_out') {
                $response['html'] .= '<br><span class="badge badge-danger small">OCCUPIED</span>';
            }
            $response['html'] .= '</td>';
            
            // Check-in
            $response['html'] .= '<td>';
            if ($booking['actual_checkin_datetime']) {
                $actual_checkin_date = date('M d, Y', strtotime($booking['actual_checkin_datetime']));
                $actual_checkin_time = date('h:i A', strtotime($booking['actual_checkin_datetime']));
                $response['html'] .= $actual_checkin_date . '<br>';
                $response['html'] .= '<small class="text-muted">' . $actual_checkin_time . '</small>';
                $response['html'] .= '<br><span class="badge badge-success small">Checked In</span>';
            } else {
                $response['html'] .= '<span class="text-muted">—</span>';
            }
            $response['html'] .= '</td>';
            
            // Scheduled Check-out
            $response['html'] .= '<td>';
            $checkout_time_display = '12:00 PM';
            if ($booking['checkout_time'] != '00:00:00') {
                $checkout_time_display = date('h:i A', strtotime($booking['checkout_time']));
            }

            $response['html'] .= date('M d, Y', strtotime($booking['checkout_date'])) . '<br>';
            $response['html'] .= '<small class="text-muted">' . $checkout_time_display . '</small>';

            if ($is_overdue) {
                $response['html'] .= '<br><span class="badge badge-danger">OVERDUE</span>';
            }
            $response['html'] .= '</td>';
            
            // Actual Check-out
            $response['html'] .= '<td>';
            if (!empty($booking['checkout_timestamp'])) {
                $checkout_date = date('M j, Y', strtotime($booking['checkout_timestamp']));
                $checkout_time = date('h:i A', strtotime($booking['checkout_timestamp']));
                $response['html'] .= $checkout_date . '<br>';
                $response['html'] .= '<small class="text-muted">' . $checkout_time . '</small>';
            } else {
                $response['html'] .= '<span class="text-muted">—</span>';
            }
            $response['html'] .= '</td>';
            
            // Status
            $response['html'] .= '<td>';
            if ($booking['checkout_status'] == 'checked_out') {
                $response['html'] .= '<span class="badge badge-success">CHECKED OUT</span>';
            } elseif ($is_checked_in) {
                $response['html'] .= '<span class="badge badge-info">CHECKED IN</span><br>';
                $response['html'] .= '<small class="text-muted">Pending Checkout</small>';
                if ($is_overdue) {
                    $response['html'] .= '<br><span class="badge badge-danger">OVERDUE</span>';
                }
            } else {
                $response['html'] .= '<span class="badge badge-secondary">NOT CHECKED IN</span>';
            }
            $response['html'] .= '</td>';
            
            // Actions
            $response['html'] .= '<td class="text-center">';
            $response['html'] .= '<div class="btn-group btn-group-sm">';
            $response['html'] .= '<a href="admin_view_booking.php?booking_id=' . $booking['id'] . '" class="btn btn-info" title="View Booking">';
            $response['html'] .= '<i class="fas fa-eye"></i></a>';
            
            if ($booking['invoice_id']) {
                $response['html'] .= '<a href="admin_view_invoice.php?invoice_id=' . $booking['invoice_id'] . '" class="btn btn-primary" title="View Invoice">';
                $response['html'] .= '<i class="fas fa-file-invoice"></i></a>';
            }
            
            if ($is_checked_in && $booking['checkout_status'] != 'checked_out') {
                $response['html'] .= '<a href="admin_checkout.php?booking_id=' . $booking['id'] . '" class="btn btn-success" title="Checkout Guest">';
                $response['html'] .= '<i class="fas fa-sign-out-alt"></i></a>';
            } else {
                $response['html'] .= '<button type="button" class="btn btn-secondary" disabled title="Not Available">';
                $response['html'] .= '<i class="fas fa-clock"></i></button>';
            }
            $response['html'] .= '</div></td></tr>';
        }
    }

    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html .= '<nav aria-label="Checkout pagination"><ul class="pagination justify-content-center">';
        
        if ($page > 1) {
            $pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadPage(' . ($page - 1) . ')">&laquo;</a></li>';
        } else {
            $pagination_html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }
        
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            $pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                $pagination_html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadPage(' . $i . ')">' . $i . '</a></li>';
            }
        }
        
        if ($end_page < $total_pages) {
            $pagination_html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        if ($page < $total_pages) {
            $pagination_html .= '<li class="page-item"><a class="page-link" href="#" onclick="loadPage(' . ($page + 1) . ')">&raquo;</a></li>';
        } else {
            $pagination_html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }
        
        $pagination_html .= '</ul></nav>';
    }

    $response['pagination_html'] = $pagination_html;
    echo json_encode($response);
    exit;
}

include __DIR__ . '/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <?php if ($success_message): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?php echo h($success_type); ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4>
                        <i class="fas fa-<?php echo $success_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $success_type === 'success' ? 'Success!' : 'Error!'; ?>
                    </h4>
                    <p><?php echo h($success_message); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="text-terminal"><i class="fas fa-sign-out-alt"></i> Checkout Management</h4>
            </div>
            <div class="card-body">
                <div class="loading-overlay" id="loading-overlay">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-3x"></i>
                        <p class="mt-3">Loading checkouts...</p>
                    </div>
                </div>

                <!-- Filter and Search Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="btn-group" role="group" id="filter-buttons">
                            <button onclick="applyFilter('all')" class="btn btn-<?php echo $filter == 'all' ? 'warning' : 'outline-warning'; ?>" id="filter-all">
                                <i class="fas fa-list"></i> All Checkouts
                            </button>
                            <button onclick="applyFilter('pending')" class="btn btn-<?php echo $filter == 'pending' ? 'warning' : 'outline-warning'; ?>" id="filter-pending">
                                <i class="fas fa-clock"></i> Pending
                            </button>
                            <button onclick="applyFilter('completed')" class="btn btn-<?php echo $filter == 'completed' ? 'warning' : 'outline-warning'; ?>" id="filter-completed">
                                <i class="fas fa-check"></i> Completed
                            </button>
                            <button onclick="applyFilter('overdue')" class="btn btn-<?php echo $filter == 'overdue' ? 'warning' : 'outline-warning'; ?>" id="filter-overdue">
                                <i class="fas fa-exclamation-triangle"></i> Overdue
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form id="search-form" onsubmit="return false;">
                            <div class="input-group">
                                <input type="text" 
                                       name="search" 
                                       id="search-input"
                                       class="form-control" 
                                       placeholder="Search guest, room, or building..."
                                       value="<?php echo h($search); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" onclick="performSearch()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">Clear</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                        <div id="search-info" class="search-info mt-2" style="display: none;">
                            <small class="text-muted" id="search-results-info"></small>
                        </div>
                    </div>
                </div>
                
                <hr class="border-terminal">

                <!-- Checkouts Table -->
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Scheduled Check-out</th>
                                <th>Actual Check-out</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="checkouts-tbody">
                            <!-- AJAX content will load here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="text-center mt-4">
                    <!-- AJAX pagination will load here -->
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mt-4" id="stats-cards">
            <!-- AJAX stats will load here -->
        </div>
    </div>
</div>

<script>
let currentFilter = '<?php echo h($filter); ?>';
let currentSearch = '<?php echo h($search); ?>';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadCheckouts();
    
    // Clean URL parameters
    if (window.history.replaceState && window.location.search.includes('alert=')) {
        const url = new URL(window.location);
        url.searchParams.delete('alert');
        url.searchParams.delete('type');
        window.history.replaceState({}, '', url);
    }
});

function applyFilter(filter) {
    currentFilter = filter;
    currentPage = 1;
    updateActiveFilter();
    loadCheckouts();
}

function updateActiveFilter() {
    const filters = ['all', 'pending', 'completed', 'overdue'];
    filters.forEach(filter => {
        const btn = document.getElementById('filter-' + filter);
        if (btn) {
            btn.className = currentFilter === filter 
                ? 'btn btn-warning' 
                : 'btn btn-outline-warning';
        }
    });
}

function performSearch() {
    const searchInput = document.getElementById('search-input');
    currentSearch = searchInput.value.trim();
    currentPage = 1;
    loadCheckouts();
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    currentSearch = '';
    currentPage = 1;
    document.getElementById('search-info').style.display = 'none';
    loadCheckouts();
}

function loadPage(page) {
    currentPage = page;
    loadCheckouts();
}

function loadCheckouts() {
    const loadingOverlay = document.getElementById('loading-overlay');
    const tbody = document.getElementById('checkouts-tbody');
    const paginationContainer = document.getElementById('pagination-container');
    const searchInfo = document.getElementById('search-info');
    const searchResultsInfo = document.getElementById('search-results-info');
    
    loadingOverlay.style.display = 'flex';
    tbody.innerHTML = '<tr><td colspan="7" class="text-center p-5"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    paginationContainer.innerHTML = '';
    
    // Use form data for PHP 5.6 compatibility
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('filter', currentFilter);
    formData.append('search', currentSearch);
    formData.append('page', currentPage);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.pathname + '?ajax=1', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                updateStatsCards(data.summary);
                tbody.innerHTML = data.html;
                paginationContainer.innerHTML = data.pagination_html;
                
                if (currentSearch) {
                    searchResultsInfo.textContent = 'Found ' + data.summary.total_count + ' checkout(s) matching "' + currentSearch + '"';
                    searchInfo.style.display = 'block';
                } else {
                    searchInfo.style.display = 'none';
                }
                
                loadingOverlay.style.display = 'none';
            } catch (e) {
                console.error('JSON parse error:', e);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-5">Error loading data</td></tr>';
                loadingOverlay.style.display = 'none';
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-5">Server error: ' + xhr.status + '</td></tr>';
            loadingOverlay.style.display = 'none';
        }
    };
    
    xhr.onerror = function() {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-5">Network error</td></tr>';
        loadingOverlay.style.display = 'none';
    };
    
    xhr.send(formData);
}

function updateStatsCards(summary) {
    const statsCards = document.getElementById('stats-cards');
    
    statsCards.innerHTML = `
        <div class="col-md-3">
            <div class="card text-center border-secondary">
                <div class="card-body">
                    <h4 class="text-light">${summary.total_count}</h4>
                    <p class="mb-0">Total Checkouts</p>
                    <small class="text-muted">${summary.filter_text}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h4 class="text-warning">${summary.pending_count}</h4>
                    <p class="mb-0">Pending</p>
                    <small class="text-muted">Guests checked in</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h4 class="text-success">${summary.completed_count}</h4>
                    <p class="mb-0">Completed</p>
                    <small class="text-muted">Checked out</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h4 class="text-danger">${summary.overdue_count}</h4>
                    <p class="mb-0">Overdue</p>
                    <small class="text-muted">Late checkouts</small>
                </div>
            </div>
        </div>
    `;
}
</script>

<?php include __DIR__ . '/footer.php'; ?>