<?php
// invoice.php - FIXED: Uses existing disputes table columns only
require_once __DIR__.'/database.php';
require_once __DIR__.'/session.php';
require_once __DIR__.'/functions.php';
include __DIR__.'/header.php';

function get_gender_icon($gender) {
    switch($gender) {
        case 'male': return '♂';
        case 'female': return '♀';
        default: return '👤';
    }
}

/** Receipt card */
function render_receipt_card($inv, $hotel) {
    ?>
    <div class="card receipt-card" style="max-width:340px;margin:0 auto;background:#0d0d0d;border:2px solid #00ff99;border-radius:8px;padding:18px 16px;font-family:'Courier New',monospace;color:#d0d0d0;box-shadow:0 0 30px rgba(0,255,153,0.1);">
        <div style="text-align:center;margin-bottom:10px;">
            <?php if (!empty($hotel['name'])): ?>
                <div style="font-weight:900;font-size:18px;letter-spacing:2px;color:#00ff99;text-shadow:0 0 20px rgba(0,255,153,0.3);text-transform:uppercase;"><?php echo h($hotel['name']); ?></div>
            <?php endif; ?>
            <?php if (!empty($hotel['tagline'])): ?>
                <div style="font-size:10px;color:#88ddbb;letter-spacing:1px;margin-top:2px;"><?php echo h($hotel['tagline']); ?></div>
            <?php endif; ?>
            <hr style="border-top:1px dashed #333;margin:8px 0;">
            <?php if (!empty($hotel['address_line1'])): ?>
                <div style="font-size:11px;color:#999;line-height:1.4;"><?php echo h($hotel['address_line1']); ?></div>
            <?php endif; ?>
            <?php if (!empty($hotel['address_line2'])): ?>
                <div style="font-size:11px;color:#999;line-height:1.4;"><?php echo h($hotel['address_line2']); ?></div>
            <?php endif; ?>
            <?php if (!empty($hotel['phone'])): ?>
                <div style="font-size:11px;color:#999;line-height:1.4;">📞 <?php echo h($hotel['phone']); ?></div>
            <?php endif; ?>
            <?php if (!empty($hotel['email'])): ?>
                <div style="font-size:11px;color:#999;line-height:1.4;">✉ <?php echo h($hotel['email']); ?></div>
            <?php endif; ?>
        </div>
        <hr style="border-top:1px dashed #333;margin:8px 0;">
        
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Invoice</span>
            <strong style="color:#eee;">#<?php echo h($inv['invoice_number']); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Guest</span>
            <strong style="color:#eee;"><?php echo h($inv['firstname'] . ' ' . $inv['lastname']); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Room</span>
            <strong style="color:#eee;"><?php echo h($inv['building_name'] . ' - ' . $inv['room_number']); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Check-in</span>
            <strong style="color:#eee;"><?php echo date('M j, Y', strtotime($inv['checkin_date'])); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Check-out</span>
            <strong style="color:#eee;"><?php echo date('M j, Y', strtotime($inv['checkout_date'])); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Nights</span>
            <strong style="color:#eee;"><?php echo intval($inv['nights']); ?></strong>
        </div>
        <hr style="border-top:1px dashed #333;margin:8px 0;">
        
        <div style="display:flex;justify-content:space-between;font-size:16px;padding:6px 0;border-bottom:2px solid #00ff99;">
            <span style="color:#888;">TOTAL</span>
            <strong style="color:#00ff99;font-size:18px;">₦<?php echo number_format($inv['amount'], 2); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Status</span>
            <strong><?php echo $inv['paid'] ? '<span style="color:#00ff99;">✓ PAID</span>' : '<span style="color:#ffcc00;">⏳ UNPAID</span>'; ?></strong>
        </div>
        <?php if ($inv['paid'] && !empty($inv['paid_at'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:11px;padding:3px 0;border-bottom:1px solid #1a1a1a;">
            <span style="color:#888;">Paid at</span>
            <strong style="color:#eee;"><?php echo date('M j, Y H:i', strtotime($inv['paid_at'])); ?></strong>
        </div>
        <?php endif; ?>
        <hr style="border-top:1px dashed #333;margin:8px 0;">
        <div style="text-align:center;font-size:10px;color:#666;margin-top:6px;">
            <?php if (!empty($hotel['website'])): ?>
                <div style="font-size:11px;color:#999;line-height:1.4;">🌐 <?php echo h($hotel['website']); ?></div>
            <?php endif; ?>
            <div style="color:#00ff99;font-weight:bold;font-size:11px;letter-spacing:1px;margin-top:4px;">Thank you for staying with us!</div>
        </div>
    </div>
    <div class="text-center mt-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="fa fa-print"></i> Print Receipt
        </button>
    </div>
    <?php
}

/**
 * RENDER COMPLAINT FORM ONLY - Uses existing table columns
 */
function render_complaint_form($inv, $conn, $customer_name) {
    $booking_id = $inv['booking_id'];
    ?>
    <div class="container-fluid main-container">
        <div class="content-wrapper">
            <div class="row justify-content-center">
                <!-- Left: Receipt Card -->
                <div class="col-md-4">
                    <?php 
                    $app_config = include __DIR__ . '/config.php';
                    $hotel = isset($app_config['hotel']) ? $app_config['hotel'] : array();
                    render_receipt_card($inv, $hotel); 
                    ?>
                </div>
                
                <!-- Right: Complaint Form Only -->
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h4><i class="fas fa-exclamation-triangle"></i> Submit Complaint</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Please provide details about your concern below. We'll review it and get back to you soon.</p>
                            
                            <?php if (isset($_POST['submit_dispute']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                                <?php
                                $subject = trim($_POST['subject']);
                                $description = trim($_POST['description']);
                                
                                if (empty($subject) || empty($description)) {
                                    echo '<div class="alert alert-danger">Please fill all required fields.</div>';
                                } else {
                                    $subject_esc = mysqli_real_escape_string($conn, $subject);
                                    $desc_esc = mysqli_real_escape_string($conn, $description);
                                    
                                    if (!empty($booking_id)) {
                                        // Check if disputes table exists
                                        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'disputes'");
                                        if (mysqli_num_rows($check_table) > 0) {
                                            // Insert using ONLY columns that exist in the table
                                            $insert_sql = "INSERT INTO disputes 
                                                           (booking_id, subject, description, status, created_at)
                                                           VALUES ($booking_id, '$subject_esc', '$desc_esc', 'open', NOW())";
                                            
                                            if (mysqli_query($conn, $insert_sql)) {
                                                echo '<div class="alert alert-success">
                                                      <strong>Thank you!</strong> Your concern has been submitted. We will review it and get back to you soon.
                                                      </div>';
                                                echo '<div class="text-center mt-3">
                                                      <a href="invoice.php?search=' . urlencode($inv['invoice_number']) . '" class="btn btn-primary">
                                                      <i class="fas fa-arrow-left"></i> Back to Invoice
                                                      </a>
                                                      </div>';
                                            } else {
                                                echo '<div class="alert alert-danger">Sorry, something went wrong. Error: ' . h(mysqli_error($conn)) . '</div>';
                                            }
                                        } else {
                                            echo '<div class="alert alert-danger">Dispute submission is currently unavailable. Please contact the front desk directly.</div>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger">Unable to submit complaint: Booking reference not found.</div>';
                                    }
                                }
                                ?>
                            <?php endif; ?>

                            <?php if (!isset($_POST['submit_dispute']) || (isset($_POST['submit_dispute']) && (empty($subject) || empty($description)))): ?>
                            <form method="post">
                                <div class="form-group">
                                    <label>Subject *</label>
                                    <input type="text" name="subject" class="form-control" placeholder="e.g., Overcharged, Room issue, Service complaint" value="<?php echo isset($_POST['subject']) ? h($_POST['subject']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Describe the issue *</label>
                                    <textarea name="description" class="form-control" rows="5" placeholder="Please explain what happened..." required><?php echo isset($_POST['description']) ? h($_POST['description']) : ''; ?></textarea>
                                </div>
                                <input type="hidden" name="search" value="<?php echo h($inv['invoice_number']); ?>">
                                <input type="hidden" name="action" value="submit_complaint">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="invoice.php?search=<?php echo urlencode($inv['invoice_number']); ?>" class="btn btn-secondary btn-block">
                                            <i class="fas fa-arrow-left"></i> Back to Invoice
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" name="submit_dispute" class="btn btn-warning btn-block">
                                            <i class="fas fa-paper-plane"></i> Submit Complaint
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * RENDER FULL INVOICE DETAILS - Normal view
 */
function display_invoice_details($inv, $conn) {
    $customer_name = h($inv['firstname'] . ' ' . $inv['lastname']);
    $booking_id = $inv['booking_id'];
    $is_paid = $inv['paid'];

    $app_config = include __DIR__ . '/config.php';
    $hotel = isset($app_config['hotel']) ? $app_config['hotel'] : array();
    ?>
    <div class="container-fluid main-container">
        <div class="content-wrapper">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <?php render_receipt_card($inv, $hotel); ?>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h4><i class="fa fa-file-invoice"></i> Invoice <?php echo h($inv['invoice_number']); ?></h4>
                        </div>
                        <div class="card-body">
                            <!-- Guest & Booking Details -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fa fa-calendar-alt"></i> Booking & Guest Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-dark table-bordered checkout-details-table">
                                            <tbody>
                                                <tr>
                                                    <td width="30%"><strong>Invoice Number:</strong></td>
                                                    <td width="70%"><?php echo h($inv['invoice_number']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Guest:</strong></td>
                                                    <td><?php echo get_gender_icon($inv['gender']); ?> <?php echo h($inv['firstname'].' '.$inv['lastname']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Room:</strong></td>
                                                    <td><?php echo h($inv['building_name'] . ' - ' . $inv['room_number']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Check-in Date:</strong></td>
                                                    <td><?php echo date('M j, Y', strtotime($inv['checkin_date'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Check-out Date:</strong></td>
                                                    <td><?php echo date('M j, Y', strtotime($inv['checkout_date'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Nights:</strong></td>
                                                    <td><?php echo intval($inv['nights']); ?> night(s)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Total Amount:</strong></td>
                                                    <td><strong class="text-success">₦<?php echo number_format($inv['amount'],2); ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Status -->
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5><i class="fa fa-credit-card"></i> Payment Status</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div style="font-size: 60px; margin: 20px 0;">
                                        <?php if ($inv['paid']): ?>
                                            <i class="fa fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fa fa-clock text-warning"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p>
                                        <strong>Status:</strong> 
                                        <?php if ($inv['paid']): ?>
                                            <span class="badge badge-success">PAID</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">PENDING PAYMENT</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($inv['paid']): ?>
                                        <p><strong>Paid at:</strong> <?php echo h($inv['paid_at']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Payment Instructions (if unpaid) -->
                            <?php if (!$inv['paid']): ?>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5><i class="fa fa-info-circle"></i> Payment Instructions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fa fa-bell"></i> Important Information</h6>
                                        <p class="mb-2">Full payment is required before check-in. Please make payment at the front desk upon arrival.</p>
                                        <div class="table-responsive">
                                            <table class="table table-dark table-bordered">
                                                <tbody>
                                                    <tr>
                                                        <td width="30%"><strong>Invoice Number:</strong></td>
                                                        <td width="70%"><strong><?php echo h($inv['invoice_number']); ?></strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Amount Due:</strong></td>
                                                        <td><strong class="text-success">₦<?php echo number_format($inv['amount'], 2); ?></strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Payment Method:</strong></td>
                                                        <td>Cash at Front Desk</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Complaint Button -->
                            <?php if ($is_paid == 1): ?>
                            <div class="card mt-4 border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Have a Concern?</h5>
                                </div>
                                <div class="card-body text-center">
                                    <p>If you have any issues with your stay, charges, or service, you can submit a complaint for review.</p>
                                    <a href="invoice.php?search=<?php echo urlencode($inv['invoice_number']); ?>&action=submit_complaint" class="btn btn-warning btn-lg">
                                        <i class="fas fa-exclamation-triangle"></i> Submit a Complaint
                                    </a>
                                    <p class="text-muted mt-3"><small>Our team will review your complaint and respond within 24-48 hours.</small></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <a href="index.php" class="btn btn-secondary btn-block">
                                            <i class="fa fa-home"></i> Back to Home
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <a href="invoice.php" class="btn btn-primary btn-block">
                                            <i class="fa fa-search"></i> Search Another Invoice
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <small class="text-muted">
                                <i class="fa fa-print"></i> Click "Print Receipt" on the receipt card for a physical copy
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// ============================================================
// MAIN LOGIC - Route to correct view
// ============================================================
$invoice_data = null;
$error_message = '';
$show_complaint_form = isset($_GET['action']) && $_GET['action'] == 'submit_complaint';

if (!empty($_GET['invoice_id']) || !empty($_POST['search']) || !empty($_GET['search'])) {
    $search_term = '';
    if (!empty($_GET['invoice_id'])) {
        $search_term = $_GET['invoice_id'];
    } elseif (!empty($_POST['search'])) {
        $search_term = $_POST['search'];
    } elseif (!empty($_GET['search'])) {
        $search_term = $_GET['search'];
    }
    
    $search_term = trim($search_term);
    
    $sql = "SELECT i.*, b.checkin_date, b.checkout_date, b.nights, b.amount, b.id as booking_id, c.firstname, c.lastname, c.gender, r.room_number, bld.name as building_name
            FROM invoices i 
            JOIN bookings b ON i.booking_id=b.id 
            JOIN customers c ON b.customer_id=c.id 
            JOIN rooms r ON b.room_id=r.id 
            JOIN buildings bld ON r.building_id=bld.id
            WHERE i.invoice_number = ? OR i.booking_id = ? OR i.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        $search_param = $search_term;
        $numeric_id = is_numeric($search_term) ? (int)$search_term : 0;
        
        mysqli_stmt_bind_param($stmt, "sii", $search_param, $numeric_id, $numeric_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $invoice_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$invoice_data) {
            $error_message = "<div class='alert alert-danger'>No invoice found with the provided reference: " . h($search_term) . "</div>";
        }
    } else {
        $error_message = "<div class='alert alert-danger'>Database error. Please try again.</div>";
    }
}

if (empty($invoice_data)) {
    // Search form
    ?>
    <div class="container-fluid main-container">
        <div class="content-wrapper">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4><i class="fa fa-search"></i> Find Your Invoice</h4>
                        </div>
                        <div class="card-body">
                            <?php echo $error_message; ?>
                            
                            <form method="POST" action="invoice.php" class="mt-4">
                                <div class="form-group">
                                    <label for="search"><i class="fa fa-file-invoice"></i> Enter Invoice/Booking Reference:</label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="search" 
                                           name="search" 
                                           placeholder="e.g., INV-2023-001 or 12345" 
                                           required
                                           value="<?php echo !empty($_POST['search']) ? h($_POST['search']) : (isset($_GET['search']) ? h($_GET['search']) : ''); ?>">
                                    <small class="form-text text-muted">
                                        Enter your invoice number, booking ID, or invoice ID
                                    </small>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fa fa-search"></i> Search Invoice
                                    </button>
                                    <a href="index.php" class="btn btn-secondary btn-lg">
                                        <i class="fa fa-home"></i> Back to Home
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-warning mb-0">
                                        <h6><i class="fa fa-exclamation-triangle"></i> Need Help?</h6>
                                        <p class="mb-0">Contact front desk: (123) 456-7890</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} elseif ($show_complaint_form && $invoice_data['paid'] == 1) {
    // SHOW COMPLAINT FORM ONLY - Clean layout with receipt
    $customer_name = h($invoice_data['firstname'] . ' ' . $invoice_data['lastname']);
    render_complaint_form($invoice_data, $conn, $customer_name);
} else {
    // SHOW FULL INVOICE DETAILS
    display_invoice_details($invoice_data, $conn);
}

// Print-only styles
?>
<style media="print">
    body * { visibility: hidden; }
    .col-md-4, .col-md-4 * { visibility: visible; }
    .col-md-4 { position: absolute; top: 0; left: 0; width: 100%; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #fff; padding: 20px; }
    .receipt-card { background: #fff !important; border: 1px solid #000 !important; color: #000 !important; box-shadow: none !important; }
    .receipt-card * { color: #000 !important; }
    .receipt-card .receipt-hotel-name { color: #000 !important; text-shadow: none !important; }
    .receipt-card [style*="color:#00ff99"] { color: #000 !important; }
    .receipt-card [style*="color:#ffcc00"] { color: #000 !important; }
    .receipt-card hr { border-top: 1px dashed #aaa !important; }
    .btn, .receipt-print-btn { display: none !important; }
    .col-md-6, .col-md-8 { display: none !important; }
    .col-md-4 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
</style>

<?php include __DIR__.'/footer.php'; ?>