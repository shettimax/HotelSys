<?php
// admin_logout.php - Logout confirmation page
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}

// If confirmation is received, perform logout
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Log the activity BEFORE destroying session (while admin is still logged in)
    log_admin_activity('Logout', 'User logged out');
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    if (session_destroy()) {
        header('Location: admin_login.php?alert=Logged out successfully&type=success');
        exit;
    } else {
        header('Location: admin_login.php?alert=Logout failed&type=danger');
        exit;
    }
}

// Define page title before including header
$page_title = "Logout Confirmation";
include __DIR__.'/header.php';
?>

<!-- Equal spacing from header and footer -->
<div style="height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; background: #0a0a0a;">
    <div style="width: 100%; max-width: 600px; margin: 0 auto;">
        <div class="card border-warning shadow-lg" style="border-width: 3px;">
            <div class="card-header bg-danger text-white text-center py-3">
                <h2 class="mb-0">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout Confirmation
                </h2>
            </div>
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-question-circle text-warning" style="font-size: 4rem;"></i>
                </div>
                
                <h3 class="text-warning mb-3 fw-bold">Are you sure you want to logout?</h3>
                
                <div class="mb-3">
                    <!-- Centered list -->
                    <div style="display: flex; justify-content: center;">
                        <ul class="list-unstyled mb-2" style="text-align: left; width: 100%; max-width: 400px;">
                            <li class="mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Logging out will end your session
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Any unsaved changes will be lost
                            </li>
                            <li>
                                <i class="fas fa-info-circle me-2"></i>
                                You can login again at any time
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-md-6 col-sm-8">
                        <a href="admin_dashboard.php" class="btn btn-secondary btn-lg w-100 py-2 mb-2">
                            <i class="fas fa-arrow-left me-2"></i> Cancel
                        </a>
                    </div>
                    <div class="col-md-6 col-sm-8">
                        <a href="admin_logout.php?confirm=yes" rel="nofollow" class="btn btn-danger btn-lg w-100 py-2 mb-2">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__.'/footer.php'; ?>