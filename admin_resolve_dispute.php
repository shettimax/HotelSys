<?php
// admin_resolve_dispute.php - Resolve Customer Dispute
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$dispute_id = isset($_GET['dispute_id']) ? (int)$_GET['dispute_id'] : 0;
if (!$dispute_id) {
    header('Location: admin_dispute.php?alert=' . urlencode('Invalid dispute ID') . '&type=error');
    exit;
}

// Fetch dispute with all related data - UPDATED TO LEFT JOIN
$sql = "
    SELECT d.*, i.id as invoice_id, i.invoice_number, i.amount as invoice_amount, i.paid as invoice_paid,
           CONCAT(c.firstname, ' ', c.lastname) as customer_name,
           c.email as customer_email, c.phone as customer_phone,
           r.room_number, bu.name as building_name,
           b.checkin_date, b.checkout_date, b.amount as booking_amount
    FROM disputes d
    LEFT JOIN bookings b ON d.booking_id = b.id
    LEFT JOIN invoices i ON b.id = i.booking_id
    LEFT JOIN customers c ON b.customer_id = c.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN buildings bu ON r.building_id = bu.id
    WHERE d.id = $dispute_id
    LIMIT 1";

$result = mysqli_query($conn, $sql);
$dispute = mysqli_fetch_assoc($result);

// UPDATED VALIDATION
if (!$dispute || empty($dispute['customer_name'])) {
    header('Location: admin_dispute.php?alert=' . urlencode('Related booking not found or deleted') . '&type=warning');
    exit;
}

// Check if dispute is already resolved
$is_resolved = $dispute['status'] == 'resolved';

// Handle form submission only if dispute is open
$msg = '';
$msg_type = '';

if (!$is_resolved && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $resolution_notes = mysqli_real_escape_string($conn, trim($_POST['resolution_notes']));
    $refund_amount = (float)$_POST['refund_amount'];
    $action = $_POST['action']; // 'resolve' or 'resolve_with_refund'

    if (empty($resolution_notes)) {
        $msg = 'Resolution notes are required';
        $msg_type = 'error';
    } elseif ($action === 'resolve_with_refund' && $refund_amount <= 0) {
        $msg = 'Refund amount must be greater than 0';
        $msg_type = 'error';
    } elseif ($action === 'resolve_with_refund' && $refund_amount > $dispute['invoice_amount']) {
        $msg = 'Refund cannot exceed invoice amount';
        $msg_type = 'error';
    } else {
        // ============================================================
        // FIX: Proper transaction isolation (START TRANSACTION / COMMIT / ROLLBACK)
        // Previously used mysqli_autocommit(false) without restoring it,
        // leaving the connection in manual-commit mode after this request.
        // ============================================================
        mysqli_query($conn, "START TRANSACTION");

        try {
            // Update dispute
            $update_sql = "UPDATE disputes 
                           SET status = 'resolved',
                               resolved_at = NOW(),
                               resolved_by = " . get_current_admin_id() . ",
                               resolution_notes = '$resolution_notes'
                           WHERE id = $dispute_id";
            if (!mysqli_query($conn, $update_sql)) {
                throw new Exception('Failed to update dispute: ' . mysqli_error($conn));
            }

            // ============================================================
            // LINK DISPUTES TO REFUNDS: creating a refund entry here reuses
            // the same `payments` schema used everywhere else in the app
            // (previously this used non-existent columns like
            // `payment_method`/`description`, which silently failed).
            // ============================================================
            if ($action === 'resolve_with_refund' && $refund_amount > 0) {
                if (empty($dispute['invoice_id'])) {
                    throw new Exception('Cannot issue refund: dispute has no linked invoice');
                }

                $refund_desc = "Dispute refund - " . mysqli_real_escape_string($conn, $dispute['subject']);
                $txn_ref = 'DISPUTEREFUND' . time() . rand(100, 999);

                $payment_sql = "INSERT INTO payments 
                                (invoice_id, txn_ref, amount, method, status, created_at)
                                VALUES ({$dispute['invoice_id']}, '$txn_ref', -$refund_amount, 'refund_dispute', 'completed', NOW())";
                if (!mysqli_query($conn, $payment_sql)) {
                    throw new Exception('Failed to record refund payment: ' . mysqli_error($conn));
                }

                // If the guest already has a checkout record for this booking,
                // reflect the refund there too so it's visible/reportable from
                // the Refunds screen. Only do this when no refund is already
                // recorded on that checkout, so the amount stays a clean 1:1
                // match with the single payment row above (the Refunds page
                // matches "paid" status by comparing payment amount to
                // checkouts.refund_amount).
                $checkout_check = mysqli_query($conn, "SELECT id, refund_amount FROM checkouts WHERE booking_id = {$dispute['booking_id']} LIMIT 1");
                if ($checkout_check && mysqli_num_rows($checkout_check) > 0) {
                    $checkout_row = mysqli_fetch_assoc($checkout_check);
                    if ((float)$checkout_row['refund_amount'] <= 0) {
                        $checkout_update = mysqli_query($conn, "
                            UPDATE checkouts 
                            SET refund_amount = $refund_amount,
                                refund_description = '$refund_desc',
                                notes = CONCAT(COALESCE(notes, ''), '\n[Dispute #$dispute_id refund: ₦" . number_format($refund_amount, 2) . " - already paid via dispute resolution]'),
                                updated_at = NOW()
                            WHERE id = {$checkout_row['id']}
                        ");
                        if (!$checkout_update) {
                            throw new Exception('Failed to update checkout refund record: ' . mysqli_error($conn));
                        }
                    }
                    // If the checkout already has its own refund_amount set,
                    // we leave it untouched — this dispute refund is still
                    // fully recorded via the payments row above.
                }
            }

            mysqli_commit($conn);
            header('Location: admin_dispute.php?alert=' . urlencode("Dispute resolved successfully" . ($action === 'resolve_with_refund' ? " with ₦" . number_format($refund_amount, 2) . " refund" : "")) . '&type=success');
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $msg = 'Error resolving dispute: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header <?php echo $is_resolved ? 'bg-success text-white' : 'bg-warning text-dark'; ?>">
                        <h4>
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?php echo $is_resolved ? 'Dispute Details' : 'Resolve Dispute'; ?> 
                            #<?php echo h($dispute_id); ?>
                            <?php if ($is_resolved): ?>
                                <span class="badge badge-light float-right">Resolved</span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($msg): ?>
                            <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <?php echo h($msg); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Dispute Summary -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-user"></i> Guest Information</h5>
                                <table class="checkout-details-table table table-sm">
                                    <tr><td><strong>Name:</strong></td><td><?php echo h($dispute['customer_name']); ?></td></tr>
                                    <tr><td><strong>Phone:</strong></td><td><?php echo h(!empty($dispute['customer_phone']) ? $dispute['customer_phone'] : 'N/A'); ?></td></tr>
                                    <tr><td><strong>Email:</strong></td><td><?php echo h(!empty($dispute['customer_email']) ? $dispute['customer_email'] : 'N/A'); ?></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-calendar-alt"></i> Booking Details</h5>
                                <table class="checkout-details-table table table-sm">
                                    <tr><td><strong>Invoice:</strong></td><td>
                                        <?php if (!empty($dispute['invoice_number']) && !empty($dispute['invoice_id'])): ?>
                                            <a href="admin_view_invoice.php?invoice_id=<?php echo h($dispute['invoice_id']); ?>" target="_blank"><?php echo h($dispute['invoice_number']); ?></a>
                                        <?php else: ?>
                                            <em>No invoice found</em>
                                        <?php endif; ?>
                                    </td></tr>
                                    <tr><td><strong>Room:</strong></td><td><?php echo !empty($dispute['building_name']) ? h($dispute['building_name'] . ' - ' . $dispute['room_number']) : '<em>Unknown room</em>'; ?></td></tr>
                                    <tr><td><strong>Stay:</strong></td><td><?php echo !empty($dispute['checkin_date']) ? date('M d', strtotime($dispute['checkin_date'])) : 'N/A'; ?> – <?php echo !empty($dispute['checkout_date']) ? date('M d, Y', strtotime($dispute['checkout_date'])) : 'N/A'; ?></td></tr>
                                    <tr><td><strong>Amount:</strong></td><td>₦<?php echo number_format(!empty($dispute['invoice_amount']) ? $dispute['invoice_amount'] : 0, 2); ?></td></tr>
                                </table>
                            </div>
                        </div>

                        <!-- Original Complaint -->
                        <div class="card mb-4 border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5><i class="fas fa-quote-left"></i> Customer Complaint</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Subject:</strong> <?php echo h($dispute['subject']); ?></p>
                                <p><strong>Reported on:</strong> <?php echo date('M d, Y H:i', strtotime($dispute['created_at'])); ?></p>
                                <hr>
                                <p><?php echo nl2br(h($dispute['description'])); ?></p>
                            </div>
                        </div>

                        <?php if ($is_resolved): ?>
                        <!-- Resolution Details (Read-only for resolved disputes) -->
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-check-circle"></i> Resolution Details</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Resolved on:</strong> <?php echo date('M d, Y H:i', strtotime($dispute['resolved_at'])); ?></p>
                                <?php if (!empty($dispute['resolved_by'])): 
                                    // Get admin name who resolved it
                                    // Try different table names based on your database structure
                                    $admin_query = mysqli_query($conn, "SELECT username FROM admin_users WHERE id = {$dispute['resolved_by']}");
                                    if (!$admin_query || mysqli_num_rows($admin_query) === 0) {
                                        // Try users_admin table name
                                        $admin_query = mysqli_query($conn, "SELECT username FROM users_admin WHERE id = {$dispute['resolved_by']}");
                                    }
                                    
                                    if ($admin_query && mysqli_num_rows($admin_query) > 0) {
                                        $admin_data = mysqli_fetch_assoc($admin_query);
                                        $admin_name = !empty($admin_data['username']) ? $admin_data['username'] : 'Unknown Admin';
                                    } else {
                                        $admin_name = 'Unknown Admin';
                                    }
                                ?>
                                    <p><strong>Resolved by:</strong> <?php echo h($admin_name); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($dispute['resolution_notes'])): ?>
                                    <p><strong>Resolution Notes:</strong></p>
                                    <div class="alert alert-info">
                                        <?php echo nl2br(h($dispute['resolution_notes'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Check if refund was issued -->
                                <?php if (!empty($dispute['booking_id']) && !empty($dispute['invoice_id'])):
                                    $refund_query = mysqli_query($conn, "SELECT SUM(amount) as total_refund FROM payments WHERE invoice_id = {$dispute['invoice_id']} AND amount < 0");
                                    $refund_data = mysqli_fetch_assoc($refund_query);
                                    $total_refund = !empty($refund_data['total_refund']) ? abs($refund_data['total_refund']) : 0;
                                    
                                    if ($total_refund > 0): ?>
                                        <p><strong>Refund Issued:</strong> <span class="text-success">₦<?php echo number_format($total_refund, 2); ?></span></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- BACK TO LIST BUTTON for resolved disputes -->
                        <div class="text-center mt-4">
                            <a href="admin_dispute.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-left"></i> Back to Disputes List
                            </a>
                        </div>
                        
                        <?php else: ?>
                        <!-- Resolution Form (Only for open disputes) -->
                        <form method="post">
                            <div class="form-group">
                                <label for="resolution_notes"><strong>Resolution Notes (Internal)</strong></label>
                                <textarea name="resolution_notes" id="resolution_notes" class="form-control" rows="5" placeholder="Explain what was discussed, agreed action, compensation if any..." required><?php echo isset($_POST['resolution_notes']) ? h($_POST['resolution_notes']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label><strong>Action</strong></label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="action" id="resolve_only" value="resolve" checked>
                                    <label class="form-check-label" for="resolve_only">Resolve without refund</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="action" id="resolve_refund" value="resolve_with_refund">
                                    <label class="form-check-label" for="resolve_refund">Resolve with refund</label>
                                </div>
                            </div>

                            <div class="form-group" id="refund_group" style="display:none;">
                                <label for="refund_amount">Refund Amount (₦)</label>
                                <input type="number" name="refund_amount" id="refund_amount" class="form-control" step="0.01" min="0" max="<?php echo !empty($dispute['invoice_amount']) ? $dispute['invoice_amount'] : 0; ?>" placeholder="0.00" value="<?php echo isset($_POST['refund_amount']) ? h($_POST['refund_amount']) : '0'; ?>">
                                <small class="text-muted">Max: ₦<?php echo number_format(!empty($dispute['invoice_amount']) ? $dispute['invoice_amount'] : 0, 2); ?></small>
                            </div>

                            <hr>

                            <div class="text-center">
                                <a href="admin_dispute.php" class="btn btn-secondary btn-lg mr-3">
                                    <i class="fas fa-arrow-left"></i> Back to Disputes List
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-check-circle"></i> Mark as Resolved
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_resolved): ?>
<script>
// Show/hide refund field based on radio selection
document.querySelectorAll('input[name="action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('refund_group').style.display = this.value === 'resolve_with_refund' ? 'block' : 'none';
        if (this.value !== 'resolve_with_refund') {
            document.getElementById('refund_amount').value = '0';
        }
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>