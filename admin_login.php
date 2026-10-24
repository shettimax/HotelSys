<?php
// admin_login.php - Updated for PHP 5.6
// Removed inline styles, using app.css
// Using admin_login from functions.php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (is_admin_logged_in()) {
    header('Location: admin_dashboard.php');
    exit;
}
if (is_staff_logged_in()) {
    header('Location: staff_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error = "Username and password are required";
    } else {
        // UNIFIED LOGIN: try admin credentials first, then staff
        // credentials (staff log in with their staff ID / pnumber in the
        // same username field) - one form, routes to the right dashboard.
        if (admin_login($username, $password, $conn)) {
            log_admin_activity('Login', 'Successful login');
            header('Location: admin_dashboard.php');
            exit;
        } elseif (staff_login($username, $password, $conn)) {
            header('Location: staff_dashboard.php');
            exit;
        } else {
            $error = "Invalid credentials";
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="container main-container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fa fa-lock"></i> Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> <?php echo h($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label><i class="fa fa-user"></i> Username / Staff ID</label>
                            <input name="username" class="form-control" placeholder="Enter username or staff ID" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fa fa-key"></i> Password</label>
                            <input name="password" type="password" class="form-control" placeholder="Enter password" required>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>