<?php
// admin_mark_paid.php - WITH CONFIRMATION FORM + SUCCESS PAGE
// WITH SweetAlert2 CONFIRMATION - FIXED: Single trigger

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

if (empty($_GET['invoice_id'])) { 
    header('Location: admin_dashboard.php'); 
    exit; 
}
$invoice_id = (int)$_GET['invoice_id'];

// First, check if invoice exists and is already paid
$check_sql = "SELECT i.id, i.paid, i.invoice_number, i.amount, i.booking_id,
                     CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                     b.status as booking_status
              FROM invoices i
              JOIN bookings b ON i.booking_id = b.id
              JOIN customers c ON b.customer_id = c.id
              WHERE i.id = $invoice_id";
$check_result = mysqli_query($conn, $check_sql);
$invoice_data = mysqli_fetch_assoc($check_result);

if (!$invoice_data) {
    header('Location: admin_dashboard.php?alert=' . urlencode('Invoice not found!') . '&type=error');
    exit;
}

// If invoice is already paid, redirect with message
if ($invoice_data['paid'] == 1) {
    header('Location: admin_view_invoice.php?invoice_id=' . $invoice_id . '&alert=' . urlencode('Invoice #' . $invoice_data['invoice_number'] . ' is already paid!') . '&type=warning');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    // PROCESS PAYMENT
    mysqli_autocommit($conn, false);
    
    $success = true;
    // Only update if not already paid
    $success = $success && mysqli_query($conn, "UPDATE invoices SET paid = 1, paid_at = NOW() WHERE id = $invoice_id AND paid = 0");
    $success = $success && mysqli_query($conn, "UPDATE payments SET status = 'completed' WHERE invoice_id = $invoice_id AND status != 'completed'");
    $success = $success && mysqli_query($conn, "UPDATE bookings b JOIN invoices i ON b.id = i.booking_id SET b.status = 'paid' WHERE i.id = $invoice_id AND b.status != 'paid'");
    
    // Verify the update actually happened
    $verify_sql = "SELECT paid FROM invoices WHERE id = $invoice_id";
    $verify_result = mysqli_query($conn, $verify_sql);
    $verify_data = mysqli_fetch_assoc($verify_result);
    $actually_updated = ($verify_data && $verify_data['paid'] == 1);
    
    include __DIR__ . '/header.php';
    
    // Log only if everything worked
    if ($success && $actually_updated) {
        log_admin_activity('Mark Invoice Paid', "Invoice #{$invoice_data['invoice_number']} marked as paid");
        mysqli_commit($conn);
        ?>
        <hr>
        <br>
        <div class="container mt-4">
            <div class="alert alert-success" style="border:2px solid #090;background-color:#001a00;color:#00ff99;font-family:monospace;">
                <h4><i class="glyphicon glyphicon-ok-circle"></i> Payment Successfully Recorded!</h4>
                <div class="terminal-feed">
                    <p><strong>✓ Transaction Complete</strong></p>
                    <p>Invoice: <span class="text-green">#<?php echo h($invoice_data['invoice_number']); ?></span></p>
                    <p>Amount: <span class="text-green">₦<?php echo number_format($invoice_data['amount'], 2); ?></span></p>
                    <p>Customer: <span class="text-green"><?php echo h($invoice_data['customer_name']); ?></span></p>
                    <p>Timestamp: <span class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></span></p>
                    <hr style="border-color:#090;">
                    <p><i class="glyphicon glyphicon-info-sign"></i> This invoice is now marked as <strong>PAID</strong> in the system.</p>
                </div>
                <div class="mt-3">
                    <a href="admin_dashboard.php" class="btn btn-primary">
                        <i class="glyphicon glyphicon-home"></i> Dashboard
                    </a>
                    <a href="admin_view_invoice.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-success">
                        <i class="glyphicon glyphicon-eye-open"></i> View Invoice
                    </a>
                    <a href="admin_invoice_management.php" class="btn btn-info">
                        <i class="glyphicon glyphicon-list"></i> All Invoices
                    </a>
                </div>
            </div>
        </div>
        <script>
        // Simple redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'admin_view_invoice.php?invoice_id=<?php echo h($invoice_id); ?>';
        }, 5000);
        </script>
        <?php
        include __DIR__ . '/footer.php';
        exit;
    } else {
        mysqli_rollback($conn);
        ?>
        <div class="container mt-4">
            <div class="alert alert-warning" style="border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;">
                <h4><i class="glyphicon glyphicon-exclamation-sign"></i> Payment Processing Failed</h4>
                <div class="terminal-feed">
                    <p><strong>✗ Transaction Not Completed</strong></p>
                    <p>Invoice: <span class="text-red">#<?php echo h($invoice_data['invoice_number']); ?></span></p>
                    <p>Status: <span class="text-red">Could not update payment status</span></p>
                    <hr style="border-color:#d00;">
                    <p><i class="glyphicon glyphicon-warning-sign"></i> Possible reasons:</p>
                    <ul>
                        <li>Invoice was already paid</li>
                        <li>Database connection issue</li>
                        <li>System error</li>
                    </ul>
                </div>
                <div class="mt-3">
                    <a href="admin_view_invoice.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-primary">
                        <i class="glyphicon glyphicon-eye-open"></i> View Invoice
                    </a>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="glyphicon glyphicon-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
        <?php
        include __DIR__ . '/footer.php';
        exit;
    }
}

// SHOW CONFIRMATION FORM
include __DIR__ . '/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-header bg-warning text-center">
                    <h4 class="mb-0"><i class="glyphicon glyphicon-warning-sign"></i> Confirm Payment</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5 class="mb-2"><i class="glyphicon glyphicon-file"></i> Invoice Details</h5>
                        <p class="mb-1"><strong>Invoice Number:</strong> #<?php echo h($invoice_data['invoice_number']); ?></p>
                        <p class="mb-1"><strong>Customer:</strong> <?php echo h($invoice_data['customer_name']); ?></p>
                        <p class="mb-1"><strong>Amount:</strong> <span class="text-success">₦<?php echo number_format($invoice_data['amount'], 2); ?></span></p>
                        <p class="mb-0"><strong>Current Status:</strong> 
                            <span class="badge badge-warning">UNPAID</span>
                        </p>
                    </div>
                    
                    <div class="alert alert-danger">
                        <h5><i class="glyphicon glyphicon-exclamation-sign"></i> Important Warning</h5>
                        <p>You are about to mark this invoice as <strong>PAID</strong>. This action will:</p>
                        <ul>
                            <li>Mark invoice #<?php echo h($invoice_data['invoice_number']); ?> as <strong>PAID</strong></li>
                            <li>Update payment status to <strong>COMPLETED</strong></li>
                            <li>Change booking status to <strong>PAID</strong></li>
                            <li>Record payment timestamp</li>
                        </ul>
                        <p class="mb-0"><strong>This action cannot be undone!</strong></p>
                    </div>
                    
                    <form method="post" id="markPaidForm">
                        <input type="hidden" name="confirm_payment" value="1">
                        
                        <p class="text-center">
                            <strong>Have you received payment of ₦<?php echo number_format($invoice_data['amount'], 2); ?> from <?php echo h($invoice_data['customer_name']); ?>?</strong>
                        </p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <a href="admin_view_invoice.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-secondary btn-block">
                                    <i class="glyphicon glyphicon-remove"></i> No, Cancel
                                </a>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success btn-block"
                                    onclick="showConfirmModalForm('Are you sure you want to mark invoice <strong>#<?php echo h($invoice_data['invoice_number']); ?></strong> as paid?<br><small>Amount: ₦<?php echo number_format($invoice_data['amount'], 2); ?></small>', 'markPaidForm', 'This will mark the invoice as PAID and update the booking status.', 'btn-success', 'fa-check-circle', '#00ff99', 'Mark Invoice Paid');">
                                    <i class="glyphicon glyphicon-ok"></i> Yes, Mark as Paid
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted text-center">
                    <small><i class="glyphicon glyphicon-info-sign"></i> Make sure payment has been received before confirming.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>