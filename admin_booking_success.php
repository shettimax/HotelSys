<?php
// admin_booking_success.php - Updated for PHP 5.6 compatibility
// Removed inline CSS/JS, using external app.css and function.js
// Using functions from functions.php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) { 
    header('Location: admin_login.php'); 
    exit; 
}
require_permission();

if (!isset($_SESSION['admin_booking_id']) || !isset($_SESSION['admin_invoice_id'])) {
    header('Location: admin_index.php');
    exit;
}

$booking_id = (int)$_SESSION['admin_booking_id'];
$invoice_id = (int)$_SESSION['admin_invoice_id'];

$query = "SELECT b.*, i.invoice_number, i.amount as invoice_amount, i.paid, 
                 c.firstname, c.lastname, c.email, c.phone,
                 r.room_number, r.building_id, bld.name as building_name
          FROM bookings b
          JOIN invoices i ON b.invoice_id = i.id
          JOIN customers c ON b.customer_id = c.id
          JOIN rooms r ON b.room_id = r.id
          JOIN buildings bld ON r.building_id = bld.id
          WHERE b.id = $booking_id AND i.id = $invoice_id";
          
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    echo "<div class='alert alert-danger'>Booking not found.</div>";
    include __DIR__ . '/header.php';
    include __DIR__ . '/footer.php';
    exit;
}

unset($_SESSION['admin_booking_id']);
unset($_SESSION['admin_invoice_id']);

include __DIR__ . '/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fa fa-check-circle"></i> Booking Created Successfully!</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div style="font-size: 60px; color: #00ff99;">
                                <i class="fa fa-calendar-check"></i>
                            </div>
                            <h4 class="text-success">Booking #<?php echo h($booking_id); ?> Confirmed</h4>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fa fa-calendar-alt"></i> Booking & Guest Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark table-bordered checkout-details-table">
                                        <tbody>
                                            <tr>
                                                <td width="30%"><strong>Booking ID:</strong></td>
                                                <td width="70%">#<?php echo h($booking_id); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Guest Name:</strong></td>
                                                <td><?php echo h($booking['firstname'] . ' ' . $booking['lastname']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td><?php echo h($booking['email']); ?></td>
                                            </tr>
                                            <?php if ($booking['phone']): ?>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td><?php echo h($booking['phone']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td><strong>Room:</strong></td>
                                                <td><?php echo h($booking['building_name'] . ' - ' . $booking['room_number']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check-in:</strong></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['checkin_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Check-out:</strong></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['checkout_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Nights:</strong></td>
                                                <td><?php echo intval($booking['nights']); ?> night(s)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fa fa-file-invoice-dollar"></i> Invoice Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-dark table-bordered checkout-details-table">
                                        <tbody>
                                            <tr>
                                                <td width="30%"><strong>Invoice Number:</strong></td>
                                                <td width="70%"><?php echo h($booking['invoice_number']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Invoice ID:</strong></td>
                                                <td>#<?php echo h($invoice_id); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Amount:</strong></td>
                                                <td><span class="text-success"><?php echo formatPrice($booking['invoice_amount']); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Payment Status:</strong></td>
                                                <td>
                                                    <?php if ($booking['paid']): ?>
                                                        <span class="badge badge-success"><i class="fa fa-check"></i> PAID</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning"><i class="fa fa-clock"></i> PENDING PAYMENT</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="admin_view_invoice.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-info btn-block">
                                        <i class="fa fa-eye"></i> View Invoice
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="admin_booking_management.php" class="btn btn-primary btn-block">
                                        <i class="fa fa-list"></i> View Bookings
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="admin_index.php" class="btn btn-success btn-block">
                                        <i class="fa fa-calendar-plus"></i> New Booking
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="admin_dashboard.php" class="btn btn-secondary btn-block">
                                        <i class="fa fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <h6><i class="fa fa-info-circle"></i> Invoice Access</h6>
                            <p class="mb-0">The customer can view their invoice at: <code>invoice.php?invoice_id=<?php echo h($invoice_id); ?></code></p>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="invoice.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-outline-light btn-sm">
                            <i class="fa fa-print"></i> View Public Invoice
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa fa-question-circle"></i> Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h6><i class="fa fa-check-circle"></i> Next Steps</h6>
                            <ul class="mb-0">
                                <li>Guest needs to check in on arrival</li>
                                <li>Collect payment if pending</li>
                                <li>Issue room keycard at check-in</li>
                                <li>Room will be marked as reserved</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-secondary">
                            <h6><i class="fa fa-lightbulb"></i> Quick Tips</h6>
                            <ul class="mb-0">
                                <li>Share invoice link with guest</li>
                                <li>Note booking reference number</li>
                                <li>Check room availability for date</li>
                                <li>Confirm guest contact details</li>
                            </ul>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fa fa-bolt"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="admin_checkin.php?invoice=<?php echo h($booking['invoice_number']); ?>" class="btn btn-primary btn-block">
                                        <i class="fa fa-sign-in-alt"></i> Check-in Guest
                                    </a>
                                    <a href="admin_collect_payment.php?invoice_id=<?php echo h($invoice_id); ?>" class="btn btn-warning btn-block">
                                        <i class="fa fa-credit-card"></i> Collect Payment
                                    </a>
                                    <a href="admin_view_booking.php?booking_id=<?php echo h($booking_id); ?>" class="btn btn-info btn-block">
                                        <i class="fa fa-eye"></i> View Full Booking
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-details-table {
    margin-bottom: 0;
}
.checkout-details-table td {
    padding: 10px 15px;
    border: 1px solid #444;
}
.checkout-details-table td:first-child {
    background: rgba(0, 255, 153, 0.1);
    color: #00ff99;
    font-weight: bold;
}
.checkout-details-table td:last-child {
    background: #1a1a1a;
}
.d-grid {
    display: grid;
}
.gap-2 {
    gap: 0.5rem;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>