<?php
// admin_customers.php - FIXED: Proper message handling with HTML
// WITH SweetAlert2 CONFIRMATION FOR CUSTOMER SELECTION

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

// FIXED SESSION CHECK
$pending_booking = null;
if (isset($_SESSION['pending_booking'])) {
    $pending_booking = $_SESSION['pending_booking'];
}
$booking_mode = ($pending_booking !== null);

// Check if we're in booking mode from URL
if (isset($_GET['booking_mode']) && $_GET['booking_mode'] == 1) {
    $booking_mode = true;
}

// Store form data for repopulation (inline add form)
$form_data = array(
    'firstname' => '',
    'lastname' => '',
    'gender' => 'male',
    'email' => '',
    'phone' => '',
    'address' => '',
    'has_car' => 0,
    'id_type' => '',
    'id_number' => '',
    'notes' => ''
);

$show_add_form = isset($_GET['add_customer']);

// Initialize messages - FIXED: Use arrays for structured messages
$msg = '';
$msg_type = '';
$show_message = false;

// 1. Handle customer selection for booking - REDIRECT
if ($booking_mode && isset($_GET['select_customer'])) {
    $customer_id = (int)$_GET['select_customer'];
   
    if ($pending_booking) {
        $_SESSION['pending_booking']['customer_id'] = $customer_id;
        
        // Log admin activity
        $customer_result = mysqli_query($conn, "SELECT firstname, lastname FROM customers WHERE id = $customer_id");
        if ($customer_result && mysqli_num_rows($customer_result) > 0) {
            $customer = mysqli_fetch_assoc($customer_result);
            log_admin_activity('Select Customer', "Selected {$customer['firstname']} {$customer['lastname']} for booking");
        }
        
        header('Location: admin_confirm_booking.php');
        exit;
    }
}

// 2. Handle customer addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $has_car = isset($_POST['has_car']) ? 1 : 0;
    $id_type = mysqli_real_escape_string($conn, $_POST['id_type']);
    $id_number = mysqli_real_escape_string($conn, trim($_POST['id_number']));
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
   
    // Repopulate form data
    $form_data = array(
        'firstname' => htmlspecialchars(trim($_POST['firstname'])),
        'lastname' => htmlspecialchars(trim($_POST['lastname'])),
        'gender' => $_POST['gender'],
        'email' => htmlspecialchars(trim($_POST['email'])),
        'phone' => htmlspecialchars(trim($_POST['phone'])),
        'address' => htmlspecialchars(trim($_POST['address'])),
        'has_car' => isset($_POST['has_car']) ? 1 : 0,
        'id_type' => $_POST['id_type'],
        'id_number' => htmlspecialchars(trim($_POST['id_number'])),
        'notes' => htmlspecialchars(trim($_POST['notes']))
    );
   
    // Check for duplicates
    $duplicate_errors = array();
    $duplicate_customers = array();
   
    $email_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE email = '$email'");
    if (mysqli_num_rows($email_check) > 0) {
        $dup = mysqli_fetch_assoc($email_check);
        $duplicate_errors[] = "Email '$email' already exists";
        $duplicate_customers[] = array('id' => $dup['id'], 'name' => $dup['firstname'] . ' ' . $dup['lastname'], 'type' => 'email', 'value' => $email);
    }
   
    if (!empty($phone)) {
        $phone_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE phone = '$phone' AND phone IS NOT NULL");
        if (mysqli_num_rows($phone_check) > 0) {
            $dup = mysqli_fetch_assoc($phone_check);
            $duplicate_errors[] = "Phone '$phone' already exists";
            $duplicate_customers[] = array('id' => $dup['id'], 'name' => $dup['firstname'] . ' ' . $dup['lastname'], 'type' => 'phone', 'value' => $phone);
        }
    }
   
    if (!empty($id_number) && !empty($id_type)) {
        $id_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE id_type = '$id_type' AND id_number = '$id_number' AND id_number IS NOT NULL");
        if (mysqli_num_rows($id_check) > 0) {
            $dup = mysqli_fetch_assoc($id_check);
            $duplicate_errors[] = ucwords(str_replace('_', ' ', $id_type)) . " number '$id_number' already exists";
            $duplicate_customers[] = array('id' => $dup['id'], 'name' => $dup['firstname'] . ' ' . $dup['lastname'], 'type' => 'id', 'value' => $id_number);
        }
    }
   
    if (!empty($duplicate_errors)) {
        $show_message = true;
        $msg_type = 'error';
        $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
            <h4><span class='glyphicon glyphicon-exclamation-sign'></span> Cannot Add Customer - Duplicate Found!</h4>
            <div class='terminal-feed'>" . implode("<br>", $duplicate_errors) . "<br><br><strong>Existing customer(s):</strong><br>";
        foreach ($duplicate_customers as $dup) {
            $msg .= "- <a href='admin_customers.php?view={$dup['id']}' class='text-green'>{$dup['name']}</a> (duplicate {$dup['type']}: {$dup['value']})<br>";
        }
        if ($booking_mode) {
            $last_dup = end($duplicate_customers);
            $msg .= "<br><strong>Suggestions:</strong><br><ol>
                <li>View the existing customer above</li>
                <li><a href='?booking_mode=1&select_customer={$last_dup['id']}' class='text-green'>Select them for this booking</a></li>
                <li>Or use different contact information</li></ol>";
        } else {
            $msg .= "<br><strong>Suggestions:</strong><br><ol>
                <li>View the existing customer above</li>
                <li><a href='admin_index.php' class='text-green'>Make a reservation</a> for them instead</li>
                <li>Or use different contact information</li></ol>";
        }
        $msg .= "</div></div>";
        $show_add_form = true;
    } else {
        $result = mysqli_query($conn, "
            INSERT INTO customers
            (firstname, lastname, gender, email, phone, address, has_car, id_type, id_number, notes)
            VALUES
            ('$firstname','$lastname','$gender','$email','$phone','$address',$has_car,'$id_type','$id_number','$notes')
        ");
       
        if ($result) {
            $new_customer_id = mysqli_insert_id($conn);
            
            // Log admin activity
            log_admin_activity('Add Customer', "Added new customer {$firstname} {$lastname}");
           
            if ($booking_mode && $pending_booking) {
                $_SESSION['pending_booking']['customer_id'] = $new_customer_id;
                header('Location: admin_confirm_booking.php');
                exit;
            }
           
            $show_message = true;
            $msg_type = 'success';
            $msg = "<div class='alert alert-success' style='border:2px solid #00ff99;background-color:#001a00;color:#00ff99;font-family:monospace;'>
                <h4><span class='glyphicon glyphicon-ok-circle'></span> Customer Added Successfully!</h4>
                <div class='terminal-feed'>
                Name: $firstname $lastname<br>
                Email: $email<br>" . (!empty($phone) ? "Phone: $phone<br>" : "") . "
                <br><strong>Next Steps:</strong><br>
                <ol>
                    <li><a href='admin_customers.php?view=$new_customer_id' class='text-green'>View customer details</a></li>
                    <li><a href='admin_index.php' class='text-green'>Make a reservation</a> for them</li>
                    <li><a href='admin_customers.php?edit=$new_customer_id' class='text-green'>Edit customer info</a></li>
                </ol>
                </div></div>";
            // Redirect after success
            echo "<script>setTimeout(function() { window.location.href = 'admin_customers.php" . ($booking_mode ? '?booking_mode=1' : '') . "'; }, 3000);</script>";
        } else {
            $show_message = true;
            $msg_type = 'error';
            $msg = "<div class='alert alert-danger' style='border:2px solid #d00;background-color:#1a0000;color:#ff6666;font-family:monospace;'>
                <h4>Error adding customer</h4>
                <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
            $show_add_form = true;
        }
    }
}

// 3. Handle customer update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $customer_id = (int)$_POST['customer_id'];
    $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $has_car = isset($_POST['has_car']) ? 1 : 0;
    $id_type = mysqli_real_escape_string($conn, $_POST['id_type']);
    $id_number = mysqli_real_escape_string($conn, trim($_POST['id_number']));
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes']));
   
    $duplicate_errors = array();
   
    $email_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE email = '$email' AND id != $customer_id");
    if (mysqli_num_rows($email_check) > 0) {
        $dup = mysqli_fetch_assoc($email_check);
        $duplicate_errors[] = "Email '$email' already belongs to customer: " . $dup['firstname'] . ' ' . $dup['lastname'];
    }
   
    if (!empty($phone)) {
        $phone_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE phone = '$phone' AND phone IS NOT NULL AND id != $customer_id");
        if (mysqli_num_rows($phone_check) > 0) {
            $dup = mysqli_fetch_assoc($phone_check);
            $duplicate_errors[] = "Phone '$phone' already belongs to customer: " . $dup['firstname'] . ' ' . $dup['lastname'];
        }
    }
   
    if (!empty($id_number) && !empty($id_type)) {
        $id_check = mysqli_query($conn, "SELECT id, firstname, lastname FROM customers WHERE id_type = '$id_type' AND id_number = '$id_number' AND id_number IS NOT NULL AND id != $customer_id");
        if (mysqli_num_rows($id_check) > 0) {
            $dup = mysqli_fetch_assoc($id_check);
            $duplicate_errors[] = ucwords(str_replace('_', ' ', $id_type)) . " number '$id_number' already belongs to customer: " . $dup['firstname'] . ' ' . $dup['lastname'];
        }
    }
   
    if (!empty($duplicate_errors)) {
        $show_message = true;
        $msg_type = 'error';
        $msg = "<div class='alert alert-danger'>
            <h4><i class='fa fa-exclamation-triangle'></i> Cannot Update Customer - Duplicate Found!</h4>
            <div class='terminal-feed'>" . implode("<br>", $duplicate_errors) . "</div></div>";
    } else {
        $result = mysqli_query($conn, "
            UPDATE customers SET
                firstname = '$firstname',
                lastname = '$lastname',
                gender = '$gender',
                email = '$email',
                phone = '$phone',
                address = '$address',
                has_car = $has_car,
                id_type = '$id_type',
                id_number = '$id_number',
                notes = '$notes'
            WHERE id = $customer_id
        ");
       
        if ($result) {
            // Log admin activity
            log_admin_activity('Update Customer', "Updated customer {$firstname} {$lastname}");
           
            $show_message = true;
            $msg_type = 'success';
            $msg = "<div class='alert alert-success'>
                <h4><i class='fa fa-check-circle'></i> Customer Updated Successfully!</h4>
                <div class='terminal-feed'>
                Name: $firstname $lastname<br>
                Email: $email<br>" . (!empty($phone) ? "Phone: $phone<br>" : "") . "
                <br><a href='admin_customers.php?view=$customer_id' class='text-green'>View updated profile</a>
                </div></div>";
            // Redirect after success - FIXED: Proper redirect without escaping HTML
            echo "<script>setTimeout(function() { window.location.href = 'admin_customers.php?view=$customer_id'; }, 3000);</script>";
        } else {
            $show_message = true;
            $msg_type = 'error';
            $msg = "<div class='alert alert-danger'>
                <h4>Error updating customer</h4>
                <div class='terminal-feed'>" . mysqli_error($conn) . "</div></div>";
        }
    }
}

// Check if we're viewing/editing a specific customer
$view_customer_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$edit_customer_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$current_customer = null;

if ($view_customer_id > 0 || $edit_customer_id > 0) {
    $customer_id = $view_customer_id > 0 ? $view_customer_id : $edit_customer_id;
    $sql = "SELECT c.*,
                   COUNT(b.id) as total_bookings,
                   SUM(b.amount) as total_spent,
                   MAX(b.created_at) as last_booking_date
            FROM customers c
            LEFT JOIN bookings b ON c.id = b.customer_id
            WHERE c.id = $customer_id
            GROUP BY c.id";
   
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $current_customer = mysqli_fetch_assoc($result);
    }
}

// NOW INCLUDE HEADER
include __DIR__.'/header.php';

// Search
$search_query = '';
$where_clause = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_query = "&search=" . urlencode($search);
    $where_clause = "WHERE
        CONCAT(c.firstname, ' ', c.lastname) LIKE '%$search%' OR
        c.email LIKE '%$search%' OR
        c.phone LIKE '%$search%' OR
        c.id_number LIKE '%$search%' OR
        c.address LIKE '%$search%'";
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$count_result = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM customers c
    $where_clause
");
$count_row = mysqli_fetch_assoc($count_result);
$total_customers = $count_row['total'];
$total_pages = ceil($total_customers / $limit);

$customers_result = mysqli_query($conn, "
    SELECT c.*,
           COUNT(b.id) as total_bookings,
           SUM(b.amount) as total_spent
    FROM customers c
    LEFT JOIN bookings b ON c.id = b.customer_id
    $where_clause
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT $limit OFFSET $offset
");

function safe_number_format($value, $decimals = 2) {
    if (empty($value) || !is_numeric($value)) {
        return number_format(0, $decimals);
    }
    return number_format($value, $decimals);
}

function get_gender_avatar($gender) {
    switch($gender) {
        case 'male': return '♂';
        case 'female': return '♀';
        default: return '👤';
    }
}

function get_id_type_label($id_type) {
    $labels = array(
        'drivers_license' => 'DL',
        'nin' => 'NIN',
        'international_passport' => 'PASS',
        'school_id' => 'SCH',
        'voters_card' => 'VOTER',
        'other' => 'OTHER'
    );
    return isset($labels[$id_type]) ? $labels[$id_type] : $id_type;
}
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
       
        <?php if ($booking_mode): ?>
        <div class="alert alert-info">
            <h4><i class="fa fa-calendar-check"></i> Select Customer for Booking</h4>
            <?php if ($pending_booking): ?>
            <div class="booking-details">
                <strong>Booking Details:</strong>
                <div class="row">
                    <div class="col-md-3">
                        <i class="fa fa-door-closed"></i>
                        <?php echo htmlspecialchars(isset($pending_booking['room_info']) ? $pending_booking['room_info'] : 'Room'); ?>
                    </div>
                    <div class="col-md-3">
                        <i class="fa fa-sign-in-alt"></i>
                        <?php echo date('M d, Y', strtotime($pending_booking['checkin'])); ?>
                    </div>
                    <div class="col-md-3">
                        <i class="fa fa-sign-out-alt"></i>
                        <?php echo date('M d, Y', strtotime($pending_booking['checkout'])); ?>
                    </div>
                    <div class="col-md-3">
                        <i class="fa fa-money-bill-wave"></i>
                        ₦<?php echo number_format($pending_booking['amount'], 2); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <p class="mt-2">Select an existing customer or add a new one for this booking.</p>
        </div>
        <?php endif; ?>

        <!-- Display alert messages - FIXED: Output HTML directly without h() -->
        <?php if ($show_message && $msg): ?>
            <?php echo $msg; ?>
        <?php endif; ?>

        <!-- VIEW CUSTOMER PROFILE -->
        <?php if ($current_customer && $view_customer_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            <i class="fa fa-user"></i> Customer Profile: <?php echo htmlspecialchars($current_customer['firstname'] . ' ' . $current_customer['lastname']); ?>
                            <a href="admin_customers.php<?php echo $booking_mode ? '?booking_mode=1' : ''; ?>" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $created_date = date('M d, Y', strtotime($current_customer['created_at']));
                        $last_booking = $current_customer['last_booking_date'] ? date('M d, Y', strtotime($current_customer['last_booking_date'])) : 'Never';
                        $gender_display = ucfirst($current_customer['gender']);
                        $has_car_display = $current_customer['has_car'] ? 'Yes' : 'No';
                        $id_type_display = !empty($current_customer['id_type']) ? ucwords(str_replace('_', ' ', $current_customer['id_type'])) : 'Not specified';
                        $total_spent_display = $current_customer['total_spent'] ? '₦' . number_format($current_customer['total_spent'], 2) : '₦0.00';
                        ?>
                       
                        <div class="row">
                            <div class="col-md-8">
                                <h3><?php echo htmlspecialchars($current_customer['firstname'] . ' ' . $current_customer['lastname']); ?></h3>
                                <p class="text-muted">Customer ID: <?php echo h($current_customer['id']); ?> | Created: <?php echo h($created_date); ?></p>
                            </div>
                            <div class="col-md-4 text-right">
                                <span class="badge badge-info"><?php echo $current_customer['total_bookings'] ? $current_customer['total_bookings'] : 0; ?> Bookings</span>
                                <a href="admin_customers.php?edit=<?php echo h($current_customer['id']); ?>" class="btn btn-warning btn-sm">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fa fa-user"></i> Personal Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Gender:</strong></td>
                                                <td><?php echo h($gender_display); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Email:</strong></td>
                                                <td><?php echo htmlspecialchars($current_customer['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td><?php echo htmlspecialchars($current_customer['phone'] ? $current_customer['phone'] : 'Not provided'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Address:</strong></td>
                                                <td><?php echo nl2br(htmlspecialchars($current_customer['address'] ? $current_customer['address'] : 'Not provided')); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Has Vehicle:</strong></td>
                                                <td><?php echo h($has_car_display); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fa fa-id-card"></i> Identification</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>ID Type:</strong></td>
                                                <td><?php echo h($id_type_display); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>ID Number:</strong></td>
                                                <td><?php echo htmlspecialchars($current_customer['id_number'] ? $current_customer['id_number'] : 'Not provided'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                               
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5><i class="fa fa-chart-line"></i> Booking Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <td><strong>Total Bookings:</strong></td>
                                                <td><?php echo $current_customer['total_bookings'] ? $current_customer['total_bookings'] : 0; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Spent:</strong></td>
                                                <td><?php echo h($total_spent_display); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Last Booking:</strong></td>
                                                <td><?php echo h($last_booking); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($current_customer['notes'])): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5><i class="fa fa-sticky-note"></i> Notes</h5>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($current_customer['notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="admin_view_customer_bookings.php?id=<?php echo h($current_customer['id']); ?>" class="btn btn-info">
                                <i class="fa fa-calendar-alt"></i> View All Bookings
                            </a>
                            <a href="admin_create_booking.php?customer_id=<?php echo h($current_customer['id']); ?>" class="btn btn-success">
                                <i class="fa fa-plus"></i> New Booking
                            </a>
                            <a href="admin_customers.php<?php echo isset($_GET['booking_mode']) ? '?booking_mode=1' : ''; ?>" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- EDIT CUSTOMER FORM -->
        <?php if ($current_customer && $edit_customer_id > 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            <i class="fa fa-edit"></i> Edit Customer: <?php echo htmlspecialchars($current_customer['firstname'] . ' ' . $current_customer['lastname']); ?>
                            <a href="admin_customers.php<?php echo $booking_mode ? '?booking_mode=1' : ''; ?>" class="btn btn-xs btn-default pull-right">Back to List</a>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="update_customer" value="1">
                            <input type="hidden" name="customer_id" value="<?php echo h($edit_customer_id); ?>">
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>First Name *</label>
                                        <input type="text" name="firstname" class="form-control"
                                               value="<?php echo htmlspecialchars($current_customer['firstname']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Name *</label>
                                        <input type="text" name="lastname" class="form-control"
                                               value="<?php echo htmlspecialchars($current_customer['lastname']); ?>" required>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gender *</label>
                                        <select name="gender" class="form-control" required>
                                            <option value="male" <?php echo $current_customer['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $current_customer['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $current_customer['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo htmlspecialchars($current_customer['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="<?php echo htmlspecialchars($current_customer['phone']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="has_car" value="1" <?php echo $current_customer['has_car'] ? 'checked' : ''; ?>> Has vehicle
                                        </label>
                                    </div>
                                </div>
                            </div>
                           
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($current_customer['address']); ?></textarea>
                            </div>
                           
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID Type</label>
                                        <select name="id_type" class="form-control">
                                            <option value="">Select ID type</option>
                                            <option value="drivers_license" <?php echo $current_customer['id_type'] == 'drivers_license' ? 'selected' : ''; ?>>Driver's License</option>
                                            <option value="nin" <?php echo $current_customer['id_type'] == 'nin' ? 'selected' : ''; ?>>NIN</option>
                                            <option value="international_passport" <?php echo $current_customer['id_type'] == 'international_passport' ? 'selected' : ''; ?>>Passport</option>
                                            <option value="school_id" <?php echo $current_customer['id_type'] == 'school_id' ? 'selected' : ''; ?>>School ID</option>
                                            <option value="voters_card" <?php echo $current_customer['id_type'] == 'voters_card' ? 'selected' : ''; ?>>Voter's Card</option>
                                            <option value="other" <?php echo $current_customer['id_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID Number</label>
                                        <input type="text" name="id_number" class="form-control"
                                               value="<?php echo htmlspecialchars($current_customer['id_number']); ?>">
                                    </div>
                                </div>
                            </div>
                           
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($current_customer['notes']); ?></textarea>
                            </div>
                           
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Save Changes</button>
                                <a href="admin_customers.php?view=<?php echo h($edit_customer_id); ?>" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN LIST -->
        <?php if (!$current_customer): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            <?php if ($booking_mode): ?>
                                <i class="fa fa-user-check"></i> Select Customer
                            <?php else: ?>
                                <i class="fa fa-users"></i> Customer Management
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                       
                        <!-- Search and Actions -->
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-8">
                                <form method="get" class="form-inline">
                                    <?php if ($booking_mode): ?>
                                    <input type="hidden" name="booking_mode" value="1">
                                    <?php endif; ?>
                                    <div class="input-group" style="width: 40%;">
                                        <input type="text"
                                               name="search"
                                               class="form-control hacker-input"
                                               placeholder="Search by name, email, phone, ID number, or address..."
                                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fa fa-search"></i> Search
                                            </button>
                                            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                                <a href="admin_customers.php<?php echo $booking_mode ? '?booking_mode=1' : ''; ?>" class="btn btn-default">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                        <small class="text-muted">
                                            Found <?php echo h($total_customers); ?> customer(s) matching "<?php echo htmlspecialchars($_GET['search']); ?>"
                                        </small>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="admin_customers.php?add_customer=1<?php echo $booking_mode ? '&booking_mode=1' : ''; ?>" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Add New Customer                                </a>
                                <?php if ($booking_mode && $pending_booking): ?>
                                    <a href="admin_reserve.php?room_id=<?php echo isset($pending_booking['room_id']) ? $pending_booking['room_id'] : ''; ?>&roomno=<?php echo urlencode(isset($pending_booking['room_info']) ? $pending_booking['room_info'] : ''); ?>" class="btn btn-warning">
                                        <i class="fa fa-arrow-left"></i> Back to Dates
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Inline Add Form -->
                        <?php if ($show_add_form): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0"><i class="fa fa-user-plus"></i> Add New Customer <?php echo $booking_mode ? 'for Booking' : ''; ?></h4>
                            </div>
                            <div class="card-body">
                                <?php if ($booking_mode): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> After adding this customer, they will be automatically selected for your booking.
                                </div>
                                <?php endif; ?>

                                <form method="post" class="form-horizontal">
                                    <input type="hidden" name="add_customer" value="1">
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">First Name *</label>
                                                <input type="text" name="firstname" class="form-control hacker-input" required value="<?php echo h($form_data['firstname']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Last Name *</label>
                                                <input type="text" name="lastname" class="form-control hacker-input" required value="<?php echo h($form_data['lastname']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Gender *</label>
                                                <select name="gender" class="form-control hacker-input" required>
                                                    <option value="male" <?php echo $form_data['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="female" <?php echo $form_data['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="other" <?php echo $form_data['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Email *</label>
                                                <input type="email" name="email" class="form-control hacker-input" required value="<?php echo h($form_data['email']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">Phone</label>
                                                <input type="text" name="phone" class="form-control hacker-input" value="<?php echo h($form_data['phone']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" name="has_car" value="1" <?php echo $form_data['has_car'] ? 'checked' : ''; ?>> Has vehicle
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="form-group">
                                        <label class="hacker-label">Address</label>
                                        <textarea name="address" class="form-control hacker-input" rows="2"><?php echo h($form_data['address']); ?></textarea>
                                    </div>
                                   
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">ID Type</label>
                                                <select name="id_type" class="form-control hacker-input">
                                                    <option value="">Select ID type</option>
                                                    <option value="drivers_license" <?php echo $form_data['id_type'] == 'drivers_license' ? 'selected' : ''; ?>>Driver's License</option>
                                                    <option value="nin" <?php echo $form_data['id_type'] == 'nin' ? 'selected' : ''; ?>>NIN</option>
                                                    <option value="international_passport" <?php echo $form_data['id_type'] == 'international_passport' ? 'selected' : ''; ?>>Passport</option>
                                                    <option value="school_id" <?php echo $form_data['id_type'] == 'school_id' ? 'selected' : ''; ?>>School ID</option>
                                                    <option value="voters_card" <?php echo $form_data['id_type'] == 'voters_card' ? 'selected' : ''; ?>>Voter's Card</option>
                                                    <option value="other" <?php echo $form_data['id_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="hacker-label">ID Number</label>
                                                <input type="text" name="id_number" class="form-control hacker-input" value="<?php echo h($form_data['id_number']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="form-group">
                                        <label class="hacker-label">Notes</label>
                                        <textarea name="notes" class="form-control hacker-input" rows="3"><?php echo h($form_data['notes']); ?></textarea>
                                    </div>
                                   
                                    <div class="form-group text-right">
                                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Add Customer</button>
                                        <a href="admin_customers.php<?php echo $booking_mode ? '?booking_mode=1' : ''; ?>" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Customers Table -->
                        <div class="table-responsive">
                            <table class="table table-dark table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>ID Type</th>
                                        <th>Car</th>
                                        <th>Bookings</th>
                                        <th>Total Spent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $counter = $offset + 1;
                                    if (mysqli_num_rows($customers_result) == 0): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">
                                                No customers found. <?php if (isset($_GET['search'])): ?>Try a different search term.<?php else: ?>Add your first customer using the button above.<?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                   
                                    <?php while ($customer = mysqli_fetch_assoc($customers_result)):
                                        $total_spent = isset($customer['total_spent']) && !empty($customer['total_spent']) ? $customer['total_spent'] : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong class="text-terminal"><?php echo htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']); ?></strong></td>
                                        <td class="text-center"><?php echo get_gender_avatar($customer['gender']); ?></td>
                                        <td>
                                            <?php if (!empty($customer['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" class="text-green">
                                                    <?php
                                                    $email = htmlspecialchars($customer['email']);
                                                    echo strlen($email) > 25 ? substr($email, 0, 22) . '...' : $email;
                                                    ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($customer['phone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>" class="text-green">
                                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($customer['id_type'])): ?>
                                                <span class="badge badge-info">
                                                    <?php echo get_id_type_label($customer['id_type']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($customer['has_car'])): ?>
                                                <span class="badge badge-success">✓</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">✗</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (isset($customer['total_bookings']) && $customer['total_bookings'] > 0): ?>
                                                <span class="badge badge-primary"><?php echo h($customer['total_bookings']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if ($total_spent > 0): ?>
                                                <strong class="text-success">₦<?php echo safe_number_format($total_spent); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">₦0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="admin_customers.php?view=<?php echo h($customer['id']); ?>" class="btn btn-info" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="admin_customers.php?edit=<?php echo h($customer['id']); ?>" class="btn btn-warning" title="Edit Customer">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <?php if ($booking_mode): ?>
                                                    <a href="javascript:void(0)" 
                                                       class="btn btn-success select-booking-btn" 
                                                       title="Select for Booking"
                                                       onclick="showConfirmModal('Are you sure you want to select <strong><?php echo htmlspecialchars(addslashes($customer['firstname'] . ' ' . $customer['lastname'])); ?></strong> for this booking?', 'admin_customers.php?booking_mode=1&select_customer=<?php echo h($customer['id']); ?>', 'This will assign the selected customer to the current booking.', 'btn-success', 'fa-user-check', '#00ff99', 'Select Customer');">
                                                        <i class="fa fa-check"></i> Select
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="text-center mt-3">
                            <nav aria-label="Customer pagination">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo h($search_query); ?><?php echo $booking_mode ? '&booking_mode=1' : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                   
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo h($i); ?><?php echo h($search_query); ?><?php echo $booking_mode ? '&booking_mode=1' : ''; ?>"><?php echo h($i); ?></a>
                                        </li>
                                    <?php endfor; ?>
                                   
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo h($search_query); ?><?php echo $booking_mode ? '&booking_mode=1' : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__.'/footer.php'; ?>