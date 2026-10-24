<?php
// admin_refunds.php - INLINE CONFIRMATION
require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// ============================================================
// EXCEL EXPORT
// ============================================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

    $where_conditions = array("ch.refund_amount > 0");

    if ($filter == 'pending') {
        $where_conditions[] = "NOT EXISTS(
            SELECT 1 FROM payments p 
            WHERE p.invoice_id = ch.invoice_id 
            AND p.amount < 0 
            AND ABS(p.amount) = ch.refund_amount
        )";
    } elseif ($filter == 'paid') {
        $where_conditions[] = "EXISTS(
            SELECT 1 FROM payments p 
            WHERE p.invoice_id = ch.invoice_id 
            AND p.amount < 0 
            AND ABS(p.amount) = ch.refund_amount
        )";
    }

    if (!empty($search)) {
        $where_conditions[] = "(c.firstname LIKE '%$search%' 
            OR c.lastname LIKE '%$search%' 
            OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%'
            OR r.room_number LIKE '%$search%'
            OR bu.name LIKE '%$search%'
            OR ch.refund_description LIKE '%$search%')";
    }

    $where_clause = implode(' AND ', $where_conditions);

    $sql = "
        SELECT 
            ch.id as 'Checkout ID',
            CONCAT(c.firstname,' ',c.lastname) as 'Guest',
            c.phone as 'Phone',
            r.room_number as 'Room',
            bu.name as 'Building',
            ch.refund_amount as 'Refund Amount (₦)',
            ch.refund_description as 'Reason',
            ch.checkout_date as 'Checkout Date',
            CASE 
                WHEN EXISTS(
                    SELECT 1 FROM payments p 
                    WHERE p.invoice_id = ch.invoice_id 
                    AND p.amount < 0 
                    AND ABS(p.amount) = ch.refund_amount
                ) THEN 'Paid'
                ELSE 'Pending'
            END as 'Status'
        FROM checkouts ch
        JOIN bookings b ON ch.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
        ORDER BY ch.checkout_date DESC
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
    $filename = $clean_hotel_name . "_Refunds_" . date('Y-m-d') . ".xls";

    export_to_excel($data, $headers, $filename, 'Refunds Report');
    exit;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Track which checkout is being processed
$processing_checkout_id = isset($_POST['processing_checkout_id']) ? (int)$_POST['processing_checkout_id'] : 0;
$show_confirmation = false;
$confirmation_data = null;

// Handle process refund (show confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_refund'])) {
    $processing_checkout_id = (int)$_POST['checkout_id'];
    
    // Get refund details for confirmation
    $result = mysqli_query($conn, "
        SELECT 
            ch.id as checkout_id,
            ch.refund_amount,
            ch.refund_description,
            ch.notes,
            CONCAT(c.firstname,' ',c.lastname) as guest_name,
            r.room_number,
            bu.name as building_name,
            ch.checkout_date,
            b.id as booking_id
        FROM checkouts ch
        JOIN bookings b ON ch.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE ch.id = $processing_checkout_id 
          AND ch.refund_amount > 0
          AND NOT EXISTS(
            SELECT 1 FROM payments p 
            WHERE p.invoice_id = ch.invoice_id 
            AND p.amount < 0 
            AND ABS(p.amount) = ch.refund_amount
          )
    ");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $confirmation_data = mysqli_fetch_assoc($result);
        $show_confirmation = true;
    }
}

// Handle confirm refund (process payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_refund'])) {
    $checkout_id = (int)$_POST['checkout_id'];
    $payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : 'cash';
    
    $result = mysqli_query($conn, "
        SELECT ch.refund_amount, ch.invoice_id, ch.booking_id, ch.notes,
               CONCAT(c.firstname,' ',c.lastname) as guest_name,
               r.room_number
        FROM checkouts ch
        JOIN bookings b ON ch.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        WHERE ch.id = $checkout_id 
          AND ch.refund_amount > 0
    ");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $refund = mysqli_fetch_assoc($result);
        $refund_amount = $refund['refund_amount'];
        $invoice_id = $refund['invoice_id'];
        $booking_id = $refund['booking_id'];
        $guest_name = $refund['guest_name'];
        $room_number = $refund['room_number'];
        $existing_notes = isset($refund['notes']) ? $refund['notes'] : '';
        
        // FIX #1: Force admin ID resolution before transaction
        $admin_id = get_current_admin_id();
        if (!$admin_id) {
            // fallback for older sessions
            $admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
        }
        
        // ============================================================
        // FIX: Proper transaction isolation (START TRANSACTION / COMMIT / ROLLBACK)
        // Previously used mysqli_autocommit(false) without ever restoring
        // autocommit afterwards, leaving the connection in manual-commit
        // mode for the rest of the request whenever this branch didn't exit().
        // ============================================================
        mysqli_query($conn, "START TRANSACTION");
        $error = '';
        
        try {
            $payment_note = "\n\n[REFUND PAID: " . date('Y-m-d H:i:s') . 
                           " | Method: " . strtoupper($payment_method) . 
                           " | Amount: ₦" . number_format($refund_amount, 2) . "]";
            
            $update_notes = mysqli_query($conn, "
                UPDATE checkouts 
                SET notes = CONCAT(COALESCE(notes, ''), '" . mysqli_real_escape_string($conn, $payment_note) . "'),
                    updated_at = NOW()
                WHERE id = $checkout_id
            ");
            
            if (!$update_notes) {
                throw new Exception('Failed to update checkout notes: ' . mysqli_error($conn));
            }
            
            if (!$invoice_id) {
                throw new Exception('Refund has no linked invoice');
            }
            
            $txn_ref = 'REFUND' . time() . rand(100,999);
            $payment_sql = mysqli_query($conn, "
                INSERT INTO payments 
                (invoice_id, txn_ref, amount, method, status, created_at)
                VALUES (
                    $invoice_id,
                    '$txn_ref',
                    -$refund_amount,
                    'refund_$payment_method',
                    'completed',
                    NOW()
                )
            ");
            
            if (!$payment_sql) {
                throw new Exception('Failed to record refund payment: ' . mysqli_error($conn));
            }
            
            // Log admin activity INSIDE the transaction so a logging failure
            // rolls back the payment too (keeps the two in lockstep).
            $log_success = log_admin_activity(
                'Refund Processed',
                "Refund of ₦" . number_format($refund_amount, 2) . 
                " processed for $guest_name (Room $room_number) via " . strtoupper($payment_method)
            );
            
            if (!$log_success) {
                throw new Exception('Failed to log admin activity');
            }
            
            mysqli_commit($conn);
            
            // Redirect without showing confirmation panel
            header('Location: admin_refunds.php?success=1&amount=' . urlencode($refund_amount) . 
                  '&method=' . urlencode($payment_method) . '&guest=' . urlencode($guest_name));
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    } else {
        $error = 'Refund not found';
    }
}

// Check for success message - SHOW ONLY IF NOT SHOWING CONFIRMATION
$success = false;
$success_amount = 0;
$success_method = '';
$success_guest = '';
if (isset($_GET['success']) && $_GET['success'] == '1' && !$show_confirmation) {
    $success = true;
    $success_amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
    $success_method = isset($_GET['method']) ? $_GET['method'] : '';
    $success_guest = isset($_GET['guest']) ? urldecode($_GET['guest']) : '';
}

// AJAX for filtering
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'pending';
    $search = isset($_REQUEST['search']) ? mysqli_real_escape_string($conn, trim($_REQUEST['search'])) : '';
    $page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
    $offset = ($page - 1) * $per_page;
    
    $where_conditions = array("ch.refund_amount > 0");
    
    if ($filter == 'pending') {
        $where_conditions[] = "NOT EXISTS(
            SELECT 1 FROM payments p 
            WHERE p.invoice_id = ch.invoice_id 
            AND p.amount < 0 
            AND ABS(p.amount) = ch.refund_amount
        )";
    } elseif ($filter == 'paid') {
        $where_conditions[] = "EXISTS(
            SELECT 1 FROM payments p 
            WHERE p.invoice_id = ch.invoice_id 
            AND p.amount < 0 
            AND ABS(p.amount) = ch.refund_amount
        )";
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(c.firstname LIKE '%$search%' 
            OR c.lastname LIKE '%$search%' 
            OR CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%'
            OR r.room_number LIKE '%$search%'
            OR bu.name LIKE '%$search%'
            OR ch.refund_description LIKE '%$search%')";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $summary_sql = "
        SELECT 
            COUNT(*) as total_count,
            SUM(ch.refund_amount) as total_amount,
            SUM(CASE WHEN NOT EXISTS(
                SELECT 1 FROM payments p 
                WHERE p.invoice_id = ch.invoice_id 
                AND p.amount < 0 
                AND ABS(p.amount) = ch.refund_amount
            ) THEN ch.refund_amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN EXISTS(
                SELECT 1 FROM payments p 
                WHERE p.invoice_id = ch.invoice_id 
                AND p.amount < 0 
                AND ABS(p.amount) = ch.refund_amount
            ) THEN ch.refund_amount ELSE 0 END) as paid_amount
        FROM checkouts ch
        JOIN bookings b ON ch.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
    ";
    
    $summary_result = mysqli_query($conn, $summary_sql);
    $total_count = 0;
    $total_amount = 0;
    $pending_amount = 0;
    $paid_amount = 0;
    
    if ($summary_result) {
        $summary_row = mysqli_fetch_assoc($summary_result);
        $total_count = $summary_row['total_count'] ?: 0;
        $total_amount = $summary_row['total_amount'] ?: 0;
        $pending_amount = $summary_row['pending_amount'] ?: 0;
        $paid_amount = $summary_row['paid_amount'] ?: 0;
    }
    
    $total_pages = ceil($total_count / $per_page);
    
    $sql = "
        SELECT 
            ch.id as checkout_id,
            ch.refund_amount,
            ch.refund_description,
            ch.notes,
            ch.status as checkout_status,
            b.id as booking_id,
            CONCAT(c.firstname,' ',c.lastname) as guest_name,
            r.room_number,
            bu.name as building_name,
            ch.checkout_date,
            EXISTS(
                SELECT 1 FROM payments p 
                WHERE p.invoice_id = ch.invoice_id 
                AND p.amount < 0 
                AND ABS(p.amount) = ch.refund_amount
            ) as already_processed
        FROM checkouts ch
        JOIN bookings b ON ch.booking_id = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bu ON r.building_id = bu.id
        WHERE $where_clause
        ORDER BY ch.checkout_date DESC
        LIMIT $offset, $per_page
    ";
    $result = mysqli_query($conn, $sql);
    
    $response = array(
        'summary' => array(
            'total_amount' => '₦' . number_format($total_amount, 2),
            'pending_amount' => '₦' . number_format($pending_amount, 2),
            'paid_amount' => '₦' . number_format($paid_amount, 2),
            'total_count' => number_format($total_count),
            'filter_text' => ($search ? "(Search: '$search')" : ($filter == 'all' ? '(All statuses)' : "($filter only)"))
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
        $response['html'] = '<tr><td colspan="7" class="text-center text-muted p-5">';
        if ($search) {
            $response['html'] .= '<i class="glyphicon glyphicon-search"></i><br>';
            $response['html'] .= 'No refunds found for "' . h($search) . '"';
        } elseif ($filter == 'pending') {
            $response['html'] .= '<i class="glyphicon glyphicon-ok-circle"></i><br>';
            $response['html'] .= 'No pending refunds';
        } elseif ($filter == 'paid') {
            $response['html'] .= '<i class="glyphicon glyphicon-search"></i><br>';
            $response['html'] .= 'No paid refunds found';
        } else {
            $response['html'] .= '<i class="glyphicon glyphicon-list"></i><br>';
            $response['html'] .= 'No refunds found';
        }
        $response['html'] .= '</td></tr>';
    } else {
        while ($r = mysqli_fetch_assoc($result)) {
            $already_processed = (bool)$r['already_processed'];
            $response['html'] .= '<tr id="refund-row-' . $r['checkout_id'] . '" class="' . ($already_processed ? 'table-success' : 'table-warning') . '">';
            $response['html'] .= '<td>';
            if ($already_processed) {
                $response['html'] .= '<span class="badge badge-success"><i class="glyphicon glyphicon-ok"></i> Paid</span>';
            } else {
                $response['html'] .= '<span class="badge badge-warning"><i class="glyphicon glyphicon-time"></i> Pending</span>';
            }
            $response['html'] .= '</td>';
            $response['html'] .= '<td><strong class="text-warning">' . h($r['guest_name']) . '</strong></td>';
            $response['html'] .= '<td>' . h($r['room_number']) . ' - ' . h($r['building_name']) . '</td>';
            $response['html'] .= '<td><strong class="' . ($already_processed ? 'text-success' : 'text-danger') . '">';
            $response['html'] .= '₦' . number_format($r['refund_amount'], 2) . '</strong></td>';
            $response['html'] .= '<td>' . nl2br(h(isset($r['refund_description']) ? $r['refund_description'] : '—')) . '</td>';
            $response['html'] .= '<td>' . date('M j, Y H:i', strtotime($r['checkout_date'])) . '</td>';
            $response['html'] .= '<td class="refund-actions">';
            $response['html'] .= '<div class="btn-group btn-group-sm">';
            $response['html'] .= '<a href="admin_view_booking.php?booking_id=' . $r['booking_id'] . '" class="btn btn-info" title="View Booking">';
            $response['html'] .= '<i class="glyphicon glyphicon-eye-open"></i> View</a>';
            
            // Added: Related Disputes button
            $response['html'] .= '<a href="admin_dispute.php?booking_id=' . $r['booking_id'] . '" class="btn btn-warning" title="Related Disputes">';
            $response['html'] .= '<i class="glyphicon glyphicon-exclamation-triangle"></i> Disputes</a>';
            
            if (!$already_processed) {
                $response['html'] .= '<form method="post" style="display:inline;">';
                $response['html'] .= '<input type="hidden" name="checkout_id" value="' . $r['checkout_id'] . '">';
                $response['html'] .= '<input type="hidden" name="process_refund" value="1">';
                $response['html'] .= '<button type="submit" class="btn btn-success" title="Process Refund">';
                $response['html'] .= '<i class="glyphicon glyphicon-usd"></i> Process</button>';
                $response['html'] .= '</form>';
            } else {
                $response['html'] .= '<button type="button" class="btn btn-secondary" disabled title="Already Paid">';
                $response['html'] .= '<i class="glyphicon glyphicon-ok"></i> Paid</button>';
            }
            
            $response['html'] .= '</div></td></tr>';
        }
    }
    
    $pagination_html = '';
    if ($total_pages > 1) {
        $pagination_html .= '<nav aria-label="Refund pagination"><ul class="pagination">';
        
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

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <?php if ($success && !$show_confirmation): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success alert-dismissible refund-success-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4>
                        <span class="glyphicon glyphicon-ok-circle"></span>
                        Refund Processed Successfully!
                    </h4>
                    <div class="terminal-feed">
                        <p><strong>Guest:</strong> <?php echo h($success_guest); ?></p>
                        <p><strong>Amount:</strong> <span class="text-green">₦<?php echo number_format($success_amount, 2); ?></span><strong>Method:</strong> <strong><?php echo strtoupper($success_method); ?></strong></p>
                        <p><small>Refund has been marked as paid and logged in the system.</small></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error) && !$show_confirmation): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger alert-dismissible refund-error-alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4>
                        <span class="glyphicon glyphicon-exclamation-sign"></span>
                        Error!
                    </h4>
                    <div class="terminal-feed">
                        <?php echo h($error); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_confirmation && $confirmation_data): ?>
        <!-- Confirmation Panel (Inline) -->
        <div class="panel panel-default hacker-panel border-warning refund-confirmation-panel">
            <div class="panel-heading hacker-heading bg-warning refund-confirmation-header">
                <h4 class="panel-title"><i class="glyphicon glyphicon-exclamation-triangle"></i> Confirm Refund Payment</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-warning refund-details-alert">
                    <h5 class="text-center mb-3"><i class="glyphicon glyphicon-question-sign"></i> Please review refund details</h5>
                    
                    <div class="confirmation-details">
                        <table class="table table-dark table-bordered refund-details-table">
                            <tr>
                                <td width="40%"><strong>Guest:</strong></td>
                                <td width="60%"><?php echo h($confirmation_data['guest_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Room:</strong></td>
                                <td><?php echo h($confirmation_data['room_number'] . ' (' . $confirmation_data['building_name'] . ')'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Refund Amount:</strong></td>
                                <td><span class="text-warning refund-amount-large">₦<?php echo number_format($confirmation_data['refund_amount'], 2); ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Reason:</strong></td>
                                <td><?php echo nl2br(h($confirmation_data['refund_description'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3 refund-method-alert">
                        <h6><i class="glyphicon glyphicon-info-sign"></i> Select Payment Method</h6>
                        <form method="post">
                            <input type="hidden" name="confirm_refund" value="1">
                            <input type="hidden" name="checkout_id" value="<?php echo h($confirmation_data['checkout_id']); ?>">
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="method_cash" value="cash" checked>
                                        <label class="form-check-label" for="method_cash">
                                            <i class="glyphicon glyphicon-piggy-bank"></i> Cash
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="method_transfer" value="transfer">
                                        <label class="form-check-label" for="method_transfer">
                                            <i class="glyphicon glyphicon-transfer"></i> Transfer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="method_pos" value="pos">
                                        <label class="form-check-label" for="method_pos">
                                            <i class="glyphicon glyphicon-credit-card"></i> POS/Card
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="method_other" value="other">
                                        <label class="form-check-label" for="method_other">
                                            <i class="glyphicon glyphicon-option-horizontal"></i> Other
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <a href="admin_refunds.php" class="btn hacker-btn hacker-btn-secondary btn-lg btn-block">
                                        <i class="glyphicon glyphicon-edit"></i> EDIT DETAILS
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn hacker-btn hacker-btn-success btn-lg btn-block">
                                        <i class="glyphicon glyphicon-check"></i> CONFIRM PAYMENT
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="text-terminal">
                    <i class="glyphicon glyphicon-refresh"></i> Refunds Management
                </h4>
            </div>
            <div class="card-body" id="refunds-container">
                <!-- Loading overlay -->
                <div class="loading-overlay" id="loading-overlay">
                    <div class="text-center">
                        <div class="glyphicon glyphicon-refresh glyphicon-spin loading-spinner"></div>
                        <p class="mt-3">Loading refunds...</p>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="btn-group" role="group" id="filter-buttons">
                            <button type="button" onclick="applyFilter('pending')" class="btn btn-<?php echo $filter == 'pending' ? 'warning' : 'outline-warning'; ?>" id="filter-pending">
                                <i class="glyphicon glyphicon-time"></i> Pending Only
                            </button>
                            <button type="button" onclick="applyFilter('all')" class="btn btn-<?php echo $filter == 'all' ? 'warning' : 'outline-warning'; ?>" id="filter-all">
                                <i class="glyphicon glyphicon-list"></i> All Refunds
                            </button>
                            <button type="button" onclick="applyFilter('paid')" class="btn btn-<?php echo $filter == 'paid' ? 'warning' : 'outline-warning'; ?>" id="filter-paid">
                                <i class="glyphicon glyphicon-ok"></i> Paid Only
                            </button>
                            <a href="?export=excel&filter=<?php echo h($filter); ?>&search=<?php echo urlencode($search); ?>" 
                               class="btn btn-success" style="background-color:#1d6f42;border-color:#1d6f42;">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form id="search-form" onsubmit="return false;">
                            <div class="input-group hacker-input-group">
                                <input type="text" 
                                       name="search" 
                                       id="search-input"
                                       class="form-control hacker-input" 
                                       placeholder="Search guest, room, or reason..."
                                       value="<?php echo h($search); ?>"
                                       aria-label="Search refunds">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" onclick="performSearch()" aria-label="Search">
                                        <i class="glyphicon glyphicon-search"></i> Search
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="#" onclick="clearSearch()" class="btn btn-default" aria-label="Clear search">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                        <div id="search-info" class="search-info">
                            <small class="text-muted" id="search-results-info"></small>
                        </div>
                    </div>
                </div>
                <hr class="border-terminal">

                <!-- Refunds Table -->
                <div class="table-responsive">
                    <table class="table table-dark table-hover" id="refunds-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Refund Amount</th>
                                <th>Reason</th>
                                <th>Checked Out</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="refunds-tbody">
                            <!-- Will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" class="text-center mt-3">
                    <!-- Will be loaded via AJAX -->
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4 mt-3" id="summary-cards">
            <!-- Will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
let currentFilter = '<?php echo h($filter); ?>';
let currentSearch = '<?php echo h($search); ?>';
let currentPage = 1;

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    loadRefunds();
});

// Apply filter
function applyFilter(filter) {
    currentFilter = filter;
    currentPage = 1;
    updateActiveFilter();
    loadRefunds();
}

// Perform search
function performSearch() {
    const searchInput = document.getElementById('search-input');
    currentSearch = searchInput.value.trim();
    currentPage = 1;
    loadRefunds();
}

// Clear search
function clearSearch() {
    document.getElementById('search-input').value = '';
    currentSearch = '';
    currentPage = 1;
    document.getElementById('search-info').style.display = 'none';
    loadRefunds();
}

// Load page
function loadPage(page) {
    currentPage = page;
    loadRefunds();
}

// Update active filter button
function updateActiveFilter() {
    document.getElementById('filter-pending').className = 'btn btn-outline-warning';
    document.getElementById('filter-all').className = 'btn btn-outline-warning';
    document.getElementById('filter-paid').className = 'btn btn-outline-warning';
    
    document.getElementById('filter-' + currentFilter).className = 'btn btn-warning';
}

// Load refunds via AJAX
function loadRefunds() {
    const loadingOverlay = document.getElementById('loading-overlay');
    const tbody = document.getElementById('refunds-tbody');
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
    formData.append('page', currentPage);
    
    fetch('?ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        updateSummaryCards(data.summary);
        tbody.innerHTML = data.html;
        paginationContainer.innerHTML = data.pagination_html;
        
        if (currentSearch) {
            searchResultsInfo.textContent = 'Found ' + data.summary.total_count + ' refund(s) matching "' + currentSearch + '"';
            searchInfo.style.display = 'block';
        } else {
            searchInfo.style.display = 'none';
        }
        
        loadingOverlay.style.display = 'none';
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-5">Error loading refunds. Please try again.</td></tr>';
        loadingOverlay.style.display = 'none';
    });
}

// Update summary cards
function updateSummaryCards(summary) {
    const summaryCards = document.getElementById('summary-cards');
    
    summaryCards.innerHTML = `
        <div class="col-md-4">
            <div class="card text-center border-secondary">
                <div class="card-body">
                    <h4 class="text-light">${summary.total_amount}</h4>
                    <p class="mb-0">Total Refunds</p>
                    <small class="text-muted">
                        ${summary.filter_text}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h4 class="text-warning">${summary.pending_amount}</h4>
                    <p class="mb-0">Pending Payment</p>
                    <small class="text-muted">Awaiting processing</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h4 class="text-success">${summary.paid_amount}</h4>
                    <p class="mb-0">Already Paid</p>
                    <small class="text-muted">Completed refunds</small>
                </div>
            </div>
        </div>
    `;
}
</script>

<?php include __DIR__.'/footer.php'; ?>