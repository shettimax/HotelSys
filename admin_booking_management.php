<?php
// admin_booking_management.php - Updated for PHP 5.6
// Removed inline CSS/JS, using app.css and function.js
// Using functions from functions.php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$building_id = isset($_GET['building_id']) ? (int)$_GET['building_id'] : 0;
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// AJAX for filtering
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all';
    $search = isset($_REQUEST['search']) ? mysqli_real_escape_string($conn, trim($_REQUEST['search'])) : '';
    $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
    $building_id = isset($_REQUEST['building_id']) ? (int)$_REQUEST['building_id'] : 0;
    $page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
    $offset = ($page - 1) * $per_page;
    
    $where_conditions = array("b.id IS NOT NULL");
    
    if (!empty($search)) {
        $where_conditions[] = "(c.firstname LIKE '%$search%' 
            OR c.lastname LIKE '%$search%' 
            OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%'
            OR c.email LIKE '%$search%'
            OR c.phone LIKE '%$search%'
            OR r.room_number LIKE '%$search%'
            OR bu.name LIKE '%$search%'
            OR i.invoice_number LIKE '%$search%')";
    }
    
    if ($building_id > 0) {
        $where_conditions[] = "r.building_id = $building_id";
    }
    
    if ($status && in_array($status, array('confirmed', 'paid', 'cancelled'))) {
        $where_conditions[] = "b.status = '$status'";
    }
    
    if ($filter == 'active') {
        $where_conditions[] = "b.status = 'paid' AND b.checkout_status != 'checked_out' 
            AND b.checkin_date <= CURDATE() AND b.checkout_date > CURDATE()";
    } elseif ($filter == 'confirmed') {
        $where_conditions[] = "b.status = 'confirmed'";
    } elseif ($filter == 'paid') {
        $where_conditions[] = "b.status = 'paid'";
    } elseif ($filter == 'cancelled') {
        $where_conditions[] = "b.status = 'cancelled'";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Summary statistics
    $summary_sql = "
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN b.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM bookings b
        LEFT JOIN invoices i ON b.id = i.booking_id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
    ";
    
    $summary_result = mysqli_query($conn, $summary_sql);
    $total_count = 0;
    $confirmed_count = 0;
    $paid_count = 0;
    $cancelled_count = 0;
    
    if ($summary_result) {
        $summary_row = mysqli_fetch_assoc($summary_result);
        $total_count = $summary_row['total_count'] ?: 0;
        $confirmed_count = $summary_row['confirmed_count'] ?: 0;
        $paid_count = $summary_row['paid_count'] ?: 0;
        $cancelled_count = $summary_row['cancelled_count'] ?: 0;
    }
    
    $total_pages = ceil($total_count / $per_page);
    
    // Fetch bookings - ADDED keycard_issued and actual_checkin_datetime to SELECT
    $sql = "
        SELECT 
            b.*,
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
            bu.building_type
        FROM bookings b
        LEFT JOIN invoices i ON b.id = i.booking_id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
        ORDER BY b.checkin_date DESC, b.created_at DESC
        LIMIT $offset, $per_page
    ";
    
    $result = mysqli_query($conn, $sql);
    
    $filter_text = '';
    if ($search) {
        $filter_text .= "Search: '$search'";
    }
    if ($building_id > 0) {
        $building_name = '';
        $building_query = mysqli_query($conn, "SELECT name FROM buildings WHERE id = $building_id");
        if ($building_row = mysqli_fetch_assoc($building_query)) {
            $building_name = $building_row['name'];
        }
        $filter_text .= ($filter_text ? ' | ' : '') . "Building: $building_name";
    }
    if ($filter != 'all') {
        $filter_text .= ($filter_text ? ' | ' : '') . "Filter: $filter";
    }
    if (!$filter_text) {
        $filter_text = 'All bookings';
    }
    
    $response = array(
        'summary' => array(
            'confirmed_count' => number_format($confirmed_count),
            'paid_count' => number_format($paid_count),
            'cancelled_count' => number_format($cancelled_count),
            'total_count' => number_format($total_count),
            'filter_text' => $filter_text,
            'search' => $search,
            'building_id' => $building_id,
            'filter' => $filter
        ),
        'html' => '',
        'pagination' => array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'has_previous' => $page > 1,
            'has_next' => $page < $total_pages
        )
    );
    
    if ($total_count == 0) {
        $response['html'] = '<tr><td colspan="7" class="text-center p-5">';
        if ($search) {
            $response['html'] .= 'No bookings found for "' . h($search) . '"';
        } else if ($filter != 'all') {
            $response['html'] .= 'No bookings found with filter "' . $filter . '"';
        } else if ($building_id > 0) {
            $response['html'] .= 'No bookings found in selected building';
        } else {
            $response['html'] .= 'No bookings found';
        }
        $response['html'] .= '</td></tr>';
    } else {
        $current_date = date('Y-m-d');
        
        while ($row = mysqli_fetch_assoc($result)) {
            $booking = $row;
            
            // Determine if booking is overdue
            $is_overdue = false;
            if ($booking['checkout_status'] != 'checked_out' && 
                strtotime($booking['checkout_date']) < strtotime($current_date) &&
                in_array($booking['status'], ['paid', 'confirmed'])) {
                $is_overdue = true;
            }
            
            // Check if booking is active
            $is_booking_active = false;
            if ($booking['status'] == 'paid' && $booking['checkout_status'] != 'checked_out') {
                $checkin_date_obj = new DateTime($booking['checkin_date']);
                $checkout_date_obj = new DateTime($booking['checkout_date']);
                $today_obj = new DateTime($current_date);
                
                $is_booking_active = (
                    $checkin_date_obj <= $today_obj &&
                    $checkout_date_obj > $today_obj &&
                    !($booking['checkout_date'] == $current_date && 
                      $booking['checkout_time'] <= '12:00:00')
                );
            }
            
            // FIXED: Determine which actions to show
            $show_cancel_button = false;
            $show_checkout_button = false;
            $show_confirm_button = false;
            
            // Guest is physically checked in?
            $guest_checked_in = (
                $booking['actual_checkin_datetime'] !== null &&
                $booking['keycard_issued'] == 1
            );
            
            if ($booking['status'] == 'pending') {
                $show_cancel_button = true;
                $show_confirm_button = true;
            } elseif ($booking['status'] == 'confirmed') {
                $show_cancel_button = true;
            } elseif ($booking['status'] == 'paid') {
                // Only allow checkout if guest is actually checked in
                if ($guest_checked_in && $booking['checkout_status'] != 'checked_out') {
                    $show_checkout_button = true;
                }
                
                // Allow cancellation only if NOT checked in yet
                if (!$guest_checked_in && $booking['checkout_status'] != 'checked_out') {
                    $show_cancel_button = true;
                }
            }
            
            // FIXED: Row styling
            $row_class = '';
            if ($booking['status'] == 'cancelled') {
                $row_class = 'table-secondary';
            } elseif ($guest_checked_in && $booking['checkout_status'] != 'checked_out') {
                $row_class = 'table-success'; // In room
            } elseif ($booking['status'] == 'paid') {
                $row_class = 'table-info'; // Paid but awaiting check-in
            }
            if ($is_overdue) {
                $row_class = 'table-danger';
            }
            
            $response['html'] .= '<tr id="booking-row-' . $booking['id'] . '" class="' . $row_class . '">';
            
            // Guest Column
            $response['html'] .= '<td>';
            $response['html'] .= '<strong>' . h($booking['customer_name']) . '</strong><br>';
            $response['html'] .= '<small class="text-muted">' . h($booking['customer_email']) . '</small><br>';
            $response['html'] .= '<small class="text-muted">' . h($booking['customer_phone']) . '</small>';
            $response['html'] .= '</td>';
            
            // Room Column
            $response['html'] .= '<td>';
            $response['html'] .= '<span class="badge badge-info">' . h($booking['room_number']) . '</span><br>';
            $response['html'] .= '<small class="text-muted">' . h($booking['building_name']) . '</small>';
            $response['html'] .= '</td>';
            
            // Check-in Column
            $response['html'] .= '<td>';
            $response['html'] .= date('M d, Y', strtotime($booking['checkin_date']));
            if ($booking['checkin_time'] != '00:00:00' && $booking['checkin_time'] != '14:00:00') {
                $response['html'] .= '<br><small class="text-muted">' . date('h:i A', strtotime($booking['checkin_time'])) . '</small>';
            } else {
                $response['html'] .= '<br><small class="text-muted">14:00</small>';
            }
            $response['html'] .= '</td>';
            
            // Check-out Column
            $response['html'] .= '<td>';
            $response['html'] .= date('M d, Y', strtotime($booking['checkout_date']));
            $response['html'] .= '<br><small class="text-muted">12:00 PM</small>';
            if ($is_overdue) {
                $response['html'] .= '<br><span class="badge badge-danger">OVERDUE</span>';
            }
            $response['html'] .= '</td>';
            
            // Nights Column
            $response['html'] .= '<td>';
            $response['html'] .= '<span class="badge badge-secondary">' . $booking['nights'] . ' night(s)</span>';
            $response['html'] .= '</td>';
            
            // Status Column
            $response['html'] .= '<td>';
            $status_badge = array(
                'pending' => 'warning',
                'confirmed' => 'info',
                'paid' => 'success',
                'cancelled' => 'danger',
                'completed' => 'primary'
            );
            $response['html'] .= '<span class="badge badge-' . $status_badge[$booking['status']] . '">';
            $response['html'] .= ucfirst($booking['status']) . '</span>';
            
            // FIXED: Status indicators
            if ($booking['checkout_status'] == 'checked_out') {
                $response['html'] .= '<br><small class="text-success">Checked out</small>';
            } elseif ($guest_checked_in) {
                $response['html'] .= '<br><small class="text-success">In Room</small>';
            } elseif ($booking['status'] == 'paid') {
                $response['html'] .= '<br><small class="text-warning">Awaiting Check-in</small>';
            }
            $response['html'] .= '</td>';
            
            // Actions Column
            $response['html'] .= '<td class="booking-actions">';
            $response['html'] .= '<div class="btn-group btn-group-sm">';
            $response['html'] .= '<a href="admin_view_booking.php?booking_id=' . $booking['id'] . '" class="btn btn-info" title="View Booking">';
            $response['html'] .= '<i class="fas fa-eye"></i></a>';
            
            if ($booking['invoice_id']) {
                $response['html'] .= '<a href="admin_view_invoice.php?invoice_id=' . $booking['invoice_id'] . '" class="btn btn-primary" title="View Invoice">';
                $response['html'] .= '<i class="fas fa-file-invoice"></i></a>';
            }
            
            if ($show_cancel_button) {
                $response['html'] .= '<a href="admin_cancellation.php?booking_id=' . $booking['id'] . '" class="btn btn-warning" title="Cancel Booking">';
                $response['html'] .= '<i class="fas fa-times"></i></a>';
            }
            
            if ($show_checkout_button) {
                $response['html'] .= '<a href="admin_checkout.php?booking_id=' . $booking['id'] . '" class="btn btn-success" title="Checkout Guest">';
                $response['html'] .= '<i class="fas fa-sign-out-alt"></i></a>';
            }
            
            $response['html'] .= '</div></td></tr>';
        }
    }
    
    // Pagination HTML
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html .= '<nav aria-label="Booking pagination"><ul class="pagination">';
        
        if ($page > 1) {
            $pagination_html .= '<li><a href="#" onclick="loadPage(' . ($page - 1) . ')" aria-label="Previous">';
            $pagination_html .= '<span aria-hidden="true">&laquo;</span></a></li>';
        } else {
            $pagination_html .= '<li class="disabled"><span aria-hidden="true">&laquo;</span></li>';
        }
        
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            $pagination_html .= '<li class="disabled"><span>...</span></li>';
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                $pagination_html .= '<li class="active"><span>' . $i . '</span></li>';
            } else {
                $pagination_html .= '<li><a href="#" onclick="loadPage(' . $i . ')">' . $i . '</a></li>';
            }
        }
        
        if ($end_page < $total_pages) {
            $pagination_html .= '<li class="disabled"><span>...</span></li>';
        }
        
        if ($page < $total_pages) {
            $pagination_html .= '<li><a href="#" onclick="loadPage(' . ($page + 1) . ')" aria-label="Next">';
            $pagination_html .= '<span aria-hidden="true">&raquo;</span></a></li>';
        } else {
            $pagination_html .= '<li class="disabled"><span aria-hidden="true">&raquo;</span></li>';
        }
        
        $pagination_html .= '</ul></nav>';
    }
    
    $response['pagination_html'] = $pagination_html;
    
    echo json_encode($response);
    exit;
}

// Fetch buildings for filter
$buildings_sql = "SELECT id, name, building_type FROM buildings WHERE status = 1 ORDER BY name";
$buildings_result = mysqli_query($conn, $buildings_sql);
$buildings = array();
while ($row = mysqli_fetch_assoc($buildings_result)) {
    $buildings[] = $row;
}

include __DIR__ . '/header.php';
?>

<!-- Success Notification Container -->
<div id="success-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: none;">
    <div class="alert alert-success alert-dismissible fade" style="min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <button type="button" class="close" onclick="hideSuccessNotification()">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="glyphicon glyphicon-ok"></i> Success!</h4>
        <div id="success-message"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="glyphicon glyphicon-calendar"></i> Booking Management
        </h3>
    </div>
    <div class="panel-body">
        <!-- Loading overlay -->
        <div class="loading-overlay" id="loading-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 1000;">
            <div class="text-center">
                <i class="glyphicon glyphicon-refresh glyphicon-spin" style="font-size: 3em;"></i>
                <p class="mt-3">Loading bookings...</p>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="btn-group" role="group" id="filter-buttons">
                    <button onclick="applyFilter('all')" class="btn btn-<?php echo $filter == 'all' ? 'warning' : 'outline-warning'; ?>" id="filter-all">
                        <i class="glyphicon glyphicon-list"></i> All Bookings
                    </button>
                    <button onclick="applyFilter('active')" class="btn btn-<?php echo $filter == 'active' ? 'warning' : 'outline-warning'; ?>" id="filter-active">
                        <i class="glyphicon glyphicon-home"></i> Active
                    </button>
                    <button onclick="applyFilter('confirmed')" class="btn btn-<?php echo $filter == 'confirmed' ? 'warning' : 'outline-warning'; ?>" id="filter-confirmed">
                        <i class="glyphicon glyphicon-ok"></i> Confirmed
                    </button>
                    <button onclick="applyFilter('paid')" class="btn btn-<?php echo $filter == 'paid' ? 'warning' : 'outline-warning'; ?>" id="filter-paid">
                        <i class="glyphicon glyphicon-usd"></i> Paid
                    </button>
                    <button onclick="applyFilter('cancelled')" class="btn btn-<?php echo $filter == 'cancelled' ? 'warning' : 'outline-warning'; ?>" id="filter-cancelled">
                        <i class="glyphicon glyphicon-remove"></i> Cancelled
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
                               placeholder="Search guest, room, or invoice..."
                               value="<?php echo h($search); ?>">
                        <div class="input-group-btn">
                            <button class="btn btn-primary" type="button" onclick="performSearch()">
                                <i class="glyphicon glyphicon-search"></i>
                            </button>
                            <?php if ($search): ?>
                                <button class="btn btn-default" type="button" onclick="clearSearch()">Clear</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <div id="search-info" class="search-info mt-2" style="display: none;">
                    <small class="text-muted" id="search-results-info"></small>
                </div>
            </div>
        </div>
        
        <!-- Building Filter -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="form-inline">
                    <div class="form-group mr-2">
                        <select class="form-control building-select" name="building_id" id="building-select">
                            <option value="0">All Buildings</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?php echo h($building['id']); ?>" 
                                        <?php echo $building_id == $building['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($building['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-info btn-sm mr-2" onclick="applyBuildingFilter()">
                        <i class="glyphicon glyphicon-filter"></i> Filter by Building
                    </button>
                    <?php if ($building_id): ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearBuildingFilter()">
                            Clear Building Filter
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="table-responsive">
            <table class="table table-dark table-hover" id="bookings-table">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookings-tbody">
                    <!-- Will be loaded via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="text-center mt-4">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mt-4" id="stats-cards">
    <!-- Will be loaded via AJAX -->
</div>

<script>
// Booking Management JavaScript
let currentFilter = '<?php echo h($filter); ?>';
let currentSearch = '<?php echo h($search); ?>';
let currentBuildingId = <?php echo h($building_id); ?>;
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadBookings();
});

function applyFilter(filter) {
    currentFilter = filter;
    currentPage = 1;
    updateActiveFilter();
    loadBookings();
}

function performSearch() {
    const searchInput = document.getElementById('search-input');
    currentSearch = searchInput.value.trim();
    currentPage = 1;
    loadBookings();
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    currentSearch = '';
    currentPage = 1;
    document.getElementById('search-info').style.display = 'none';
    loadBookings();
}

function applyBuildingFilter() {
    const buildingSelect = document.getElementById('building-select');
    currentBuildingId = parseInt(buildingSelect.value);
    currentPage = 1;
    loadBookings();
}

function clearBuildingFilter() {
    currentBuildingId = 0;
    document.getElementById('building-select').value = '0';
    currentPage = 1;
    loadBookings();
}

function loadPage(page) {
    currentPage = page;
    loadBookings();
}

function updateActiveFilter() {
    const filters = ['all', 'active', 'confirmed', 'paid', 'cancelled'];
    filters.forEach(filter => {
        const element = document.getElementById('filter-' + filter);
        if (element) {
            element.className = currentFilter === filter ? 'btn btn-warning' : 'btn btn-outline-warning';
        }
    });
}

function loadBookings() {
    const loadingOverlay = document.getElementById('loading-overlay');
    const tbody = document.getElementById('bookings-tbody');
    const paginationContainer = document.getElementById('pagination-container');
    const searchInfo = document.getElementById('search-info');
    const searchResultsInfo = document.getElementById('search-results-info');
    
    loadingOverlay.style.display = 'flex';
    tbody.innerHTML = '<tr><td colspan="7" class="text-center p-5"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Loading...</td></tr>';
    paginationContainer.innerHTML = '';
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('filter', currentFilter);
    formData.append('search', currentSearch);
    formData.append('building_id', currentBuildingId);
    formData.append('page', currentPage);
    
    fetch('?ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        updateStatsCards(data.summary);
        tbody.innerHTML = data.html;
        paginationContainer.innerHTML = data.pagination_html;
        
        if (data.summary.search || data.summary.building_id > 0 || data.summary.filter != 'all') {
            let infoText = 'Found ' + data.summary.total_count + ' booking(s)';
            
            if (data.summary.search) {
                infoText += ' matching "' + data.summary.search + '"';
            }
            if (data.summary.building_id > 0) {
                infoText += ' in selected building';
            }
            if (data.summary.filter != 'all') {
                infoText += ' with filter "' + data.summary.filter + '"';
            }
            
            searchResultsInfo.textContent = infoText;
            searchInfo.style.display = 'block';
        } else {
            searchInfo.style.display = 'none';
        }
        
        loadingOverlay.style.display = 'none';
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-5">Error loading bookings. Please try again.</td></tr>';
        loadingOverlay.style.display = 'none';
    });
}

function updateStatsCards(summary) {
    const statsCards = document.getElementById('stats-cards');
    
    statsCards.innerHTML = `
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card total-card" style="background: #111; border: 1px solid #333;">
                <div class="card-body text-center">
                    <h3 class="card-title" style="color: #0ff;">${summary.total_count}</h3>
                    <p class="card-text" style="color: #aaa;">Total Bookings</p>
                    <small class="text-muted">${summary.filter_text}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card confirmed-card" style="background: #111; border: 1px solid #39f;">
                <div class="card-body text-center">
                    <h3 class="card-title" style="color: #39f;">${summary.confirmed_count}</h3>
                    <p class="card-text" style="color: #aaa;">Confirmed</p>
                    <small class="text-muted">Awaiting payment</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card paid-card" style="background: #111; border: 1px solid #090;">
                <div class="card-body text-center">
                    <h3 class="card-title" style="color: #0c0;">${summary.paid_count}</h3>
                    <p class="card-text" style="color: #aaa;">Paid</p>
                    <small class="text-muted">Active & upcoming</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card stats-card cancelled-card" style="background: #111; border: 1px solid #900;">
                <div class="card-body text-center">
                    <h3 class="card-title" style="color: #f00;">${summary.cancelled_count}</h3>
                    <p class="card-text" style="color: #aaa;">Cancelled</p>
                    <small class="text-muted">Cancelled bookings</small>
                </div>
            </div>
        </div>
    `;
}

function showSuccessNotification(message) {
    const notification = document.getElementById('success-notification');
    const messageDiv = document.getElementById('success-message');
    
    messageDiv.innerHTML = message;
    notification.style.display = 'block';
    
    // Add 'show' class for fade effect
    setTimeout(() => {
        notification.querySelector('.alert').classList.add('show');
    }, 10);
    
    // Auto-hide after 5 seconds
    setTimeout(hideSuccessNotification, 5000);
}

function hideSuccessNotification() {
    const notification = document.getElementById('success-notification');
    const alertDiv = notification.querySelector('.alert');
    
    // Remove 'show' class first for fade out
    alertDiv.classList.remove('show');
    
    // Hide after fade out
    setTimeout(() => {
        notification.style.display = 'none';
    }, 300);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>