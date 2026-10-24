<?php
// admin_attendance_clock.php - Enhanced clock in/out with QR support
// FIXED: Message display without h() escaping HTML

require_once __DIR__.'/database.php';
require_once __DIR__.'/functions.php';
if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$msg = '';
$msg_type = ''; // success, danger, warning, info
$staff = null;
$attendance = null;
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$admin_id = get_current_admin_id();

// Handle clock action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pnumber = strtoupper(trim($_POST['pnumber']));
    $action = $_POST['action']; // 'in' or 'out'

    // Find staff
    $result = mysqli_query($conn, "SELECT * FROM staff WHERE pnumber = '$pnumber' AND status = 'active'");
    if (mysqli_num_rows($result) == 0) {
        $msg = "Staff not found or inactive";
        $msg_type = 'danger';
    } else {
        $staff = mysqli_fetch_assoc($result);
        $staff_id = $staff['id'];

        // Get today's LAST record to check current status
        $att_result = mysqli_query($conn, "SELECT * FROM staff_attendance WHERE staff_id = $staff_id AND date = '$today' ORDER BY id DESC LIMIT 1");
        $last_attendance = $att_result ? mysqli_fetch_assoc($att_result) : null;

        if ($action == 'in') {
            // Check if already clocked in (has a record with clock_in but no clock_out)
            if ($last_attendance && $last_attendance['clock_in'] && !$last_attendance['clock_out']) {
                $msg = "Already clocked in at " . date('h:i A', strtotime($last_attendance['clock_in'])) . " (must clock out first)";
                $msg_type = 'warning';
            } else {
                // Create NEW record for this shift
                $time = date('Y-m-d H:i:s');
                mysqli_query($conn, "INSERT INTO staff_attendance (staff_id, date, clock_in, recorded_by) VALUES ($staff_id, '$today', '$time', $admin_id)");
                $msg = "{$staff['name']} clocked in at " . date('h:i A');
                $msg_type = 'success';
            }
        } else { // out
            if (!$last_attendance || !$last_attendance['clock_in']) {
                $msg = "Cannot clock out - not clocked in today";
                $msg_type = 'warning';
            } elseif ($last_attendance['clock_out']) {
                $msg = "Already clocked out at " . date('h:i A', strtotime($last_attendance['clock_out']));
                $msg_type = 'warning';
            } else {
                $time = date('Y-m-d H:i:s');
                mysqli_query($conn, "UPDATE staff_attendance SET clock_out = '$time', recorded_by = $admin_id WHERE id = {$last_attendance['id']}");
                $msg = "{$staff['name']} clocked out at " . date('h:i A');
                $msg_type = 'success';
            }
        }
        
        // Refresh attendance data after update
        if ($staff) {
            $att_result = mysqli_query($conn, "SELECT * FROM staff_attendance WHERE staff_id = {$staff['id']} AND date = '$today' ORDER BY id DESC LIMIT 1");
            $attendance = $att_result ? mysqli_fetch_assoc($att_result) : null;
        }
    }
}

// Search by pnumber
if (isset($_GET['pnumber'])) {
    $pnumber = strtoupper(mysqli_real_escape_string($conn, $_GET['pnumber']));
    $result = mysqli_query($conn, "SELECT * FROM staff WHERE pnumber = '$pnumber' AND status = 'active'");
    if (mysqli_num_rows($result) > 0) {
        $staff = mysqli_fetch_assoc($result);

        // Get today's LAST attendance record
        $att_result = mysqli_query($conn, "SELECT * FROM staff_attendance WHERE staff_id = {$staff['id']} AND date = '$today' ORDER BY id DESC LIMIT 1");
        $attendance = $att_result ? mysqli_fetch_assoc($att_result) : null;
        
        // Get ALL attendance records for today
        $today_shifts = array();
        $shifts_result = mysqli_query($conn, 
            "SELECT * FROM staff_attendance 
             WHERE staff_id = {$staff['id']} AND date = '$today' 
             ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($shifts_result)) {
            $today_shifts[] = $row;
        }
    } else {
        $msg = "Staff PNumber not found";
        $msg_type = 'danger';
    }
}

include __DIR__.'/header.php';
?>

<div class="container-fluid main-container">
    <div class="content-wrapper">
        
        <!-- Messages - FIXED: No h() on HTML messages -->
        <?php if ($msg): ?>
        <div class="alert alert-<?php echo h($msg_type); ?> alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">
                <?php if ($msg_type == 'success'): ?>
                    <i class="fas fa-check-circle"></i> Success!
                <?php elseif ($msg_type == 'danger'): ?>
                    <i class="fas fa-exclamation-circle"></i> Error!
                <?php elseif ($msg_type == 'warning'): ?>
                    <i class="fas fa-exclamation-triangle"></i> Notice
                <?php else: ?>
                    <i class="fas fa-info-circle"></i> Information
                <?php endif; ?>
            </h4>
            <div><?php echo $msg; ?></div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-clock"></i> Staff Attendance Clock</h4>
                <small class="text-light">Date: <?php echo date('l, F j, Y'); ?> | Time: <?php echo date('h:i A'); ?></small>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Left Column: Search & Clock Controls -->
                    <div class="col-md-6">
                        <!-- Search Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-search"></i> Search Staff</h5>
                            </div>
                            <div class="card-body">
                                <form method="get" id="searchForm">
                                    <div class="input-group input-group-lg">
                                        <input type="text" 
                                               name="pnumber" 
                                               class="form-control" 
                                               placeholder="Enter Staff ID (e.g., ST001)" 
                                               required 
                                               autofocus
                                               value="<?php echo isset($_GET['pnumber']) ? h($_GET['pnumber']) : ''; ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-lightbulb"></i> Tip: You can also scan QR code from staff ID card
                                    </small>
                                </form>
                                
                                <!-- QR Scanner Button (Optional) -->
                                <div class="mt-3 text-center">
                                    <button type="button" class="btn btn-outline-primary" onclick="startQRScanner()">
                                        <i class="fas fa-qrcode"></i> Scan QR Code
                                    </button>
                                    <a href="admin_staff.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-users"></i> View All Staff
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Staff Information -->
                    <div class="col-md-6">
                        <?php if ($staff): ?>
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-id-card"></i> Staff Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <?php if ($staff['photo'] && file_exists($staff['photo'])): ?>
                                            <img src="<?php echo h($staff['photo']); ?>" class="img-fluid rounded mb-2" style="max-height: 150px;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center mb-2" style="width:120px;height:120px;margin:0 auto;">
                                                <i class="fas fa-user fa-3x text-light"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <h4><?php echo h($staff['name']); ?></h4>
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td width="40%"><strong>Staff ID:</strong></td>
                                                <td><code class="h4"><?php echo h($staff['pnumber']); ?></code></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Department:</strong></td>
                                                <td><?php echo h($staff['department']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Phone:</strong></td>
                                                <td><?php echo h($staff['phone']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Current Status:</strong></td>
                                                <td>
                                                    <?php if ($attendance && $attendance['clock_in']): ?>
                                                        <?php if ($attendance['clock_out']): ?>
                                                            <span class="badge badge-info">Clocked Out</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">Clocked In</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not Clocked In</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Current Status -->
                                <div class="mt-3 p-3 rounded" style="background-color: #f8f9fa;">
                                    <h5><i class="fas fa-user-clock"></i> Current Status</h5>
                                    <?php if ($attendance && $attendance['clock_in']): ?>
                                        <div class="alert alert-success mb-2">
                                            <strong><i class="fas fa-sign-in-alt"></i> Last Clock In:</strong> 
                                            <?php echo date('h:i A', strtotime($attendance['clock_in'])); ?>
                                        </div>
                                        <?php if ($attendance['clock_out']): ?>
                                            <div class="alert alert-info mb-2">
                                                <strong><i class="fas fa-sign-out-alt"></i> Last Clock Out:</strong> 
                                                <?php echo date('h:i A', strtotime($attendance['clock_out'])); ?>
                                            </div>
                                            <!-- Clock In Button (for new shift) -->
                                            <form method="post" class="mt-3">
                                                <input type="hidden" name="pnumber" value="<?php echo h($staff['pnumber']); ?>">
                                                <input type="hidden" name="action" value="in">
                                                <button type="submit" class="btn btn-success btn-lg btn-block py-3">
                                                    <i class="fas fa-sign-in-alt fa-2x"></i><br>
                                                    <strong>CLOCK IN FOR NEW SHIFT</strong><br>
                                                    <small><?php echo date('h:i A'); ?></small>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Clock Out Button -->
                                            <form method="post" class="mt-3">
                                                <input type="hidden" name="pnumber" value="<?php echo h($staff['pnumber']); ?>">
                                                <input type="hidden" name="action" value="out">
                                                <button type="submit" class="btn btn-danger btn-lg btn-block py-3">
                                                    <i class="fas fa-sign-out-alt fa-2x"></i><br>
                                                    <strong>CLOCK OUT NOW</strong><br>
                                                    <small><?php echo date('h:i A'); ?></small>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Clock In Button -->
                                        <form method="post" class="mt-3">
                                            <input type="hidden" name="pnumber" value="<?php echo h($staff['pnumber']); ?>">
                                            <input type="hidden" name="action" value="in">
                                            <button type="submit" class="btn btn-success btn-lg btn-block py-3">
                                                <i class="fas fa-sign-in-alt fa-2x"></i><br>
                                                <strong>CLOCK IN NOW</strong><br>
                                                <small><?php echo date('h:i A'); ?></small>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Today's Shifts -->
                                <?php if (!empty($today_shifts)): ?>
                                <div class="mt-3">
                                    <h6><i class="fas fa-history"></i> Today's Shifts</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Shift #</th>
                                                    <th>Clock In</th>
                                                    <th>Clock Out</th>
                                                    <th>Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $shift_count = count($today_shifts);
                                                foreach ($today_shifts as $index => $shift): 
                                                    $duration = '';
                                                    if ($shift['clock_in'] && $shift['clock_out']) {
                                                        $start = strtotime($shift['clock_in']);
                                                        $end = strtotime($shift['clock_out']);
                                                        $hours = floor(($end - $start) / 3600);
                                                        $minutes = floor((($end - $start) % 3600) / 60);
                                                        $duration = sprintf("%dh %02dm", $hours, $minutes);
                                                    }
                                                ?>
                                                <tr <?php echo $index == 0 ? 'class="table-info"' : ''; ?>>
                                                    <td>Shift <?php echo $shift_count - $index; ?></td>
                                                    <td><?php echo date('h:i A', strtotime($shift['clock_in'])); ?></td>
                                                    <td><?php echo $shift['clock_out'] ? date('h:i A', strtotime($shift['clock_out'])) : '<span class="text-warning">In Progress</span>'; ?></td>
                                                    <td><?php echo $duration ?: '—'; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted">Total shifts today: <?php echo h($shift_count); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Instructions when no staff selected -->
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Instructions</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="display-1 text-muted mb-3">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <h4>How to Use</h4>
                                <ol class="text-left">
                                    <li>Enter Staff ID in the search box</li>
                                    <li>Or scan QR code from staff ID card</li>
                                    <li>Click <strong>Clock In</strong> when starting a shift</li>
                                    <li>Click <strong>Clock Out</strong> when ending a shift</li>
                                    <li>Each in/out pair creates a new shift record</li>
                                </ol>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    <strong>Note:</strong> Staff must be <span class="badge badge-success">Active</span> to clock in/out
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// QR Scanner functionality (requires camera permission)
function startQRScanner() {
    if (typeof Instascan === 'undefined') {
        alert('QR scanner library not loaded. Please install Instascan or use manual entry.');
        return;
    }
    
    // This is a placeholder for QR scanner implementation
    // You would need to include Instascan.js or similar library
    alert('QR Scanner would open here. For now, please use manual entry.');
}

// Auto-focus on search field
document.addEventListener('DOMContentLoaded', function() {
    const searchField = document.querySelector('input[name="pnumber"]');
    if (searchField) {
        searchField.focus();
        searchField.select();
    }
    
    // Add confirmation for clock out
    const clockOutButtons = document.querySelectorAll('button[value="out"]');
    clockOutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const staffName = '<?php echo isset($staff) ? h($staff['name']) : ""; ?>';
            if (staffName && !confirm(`Confirm clock out for ${staffName}?`)) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include __DIR__.'/footer.php'; ?>