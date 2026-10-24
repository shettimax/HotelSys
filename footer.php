<?php
// footer.php - Updated for PHP 5.6
// Using SweetAlert2 for confirmation dialogs (already loaded)

// GLOBAL ALERT MODAL: pages that redirect with ?alert=...&type=... (e.g.
// require_permission()'s "access denied" redirect) get a SweetAlert2 popup
// instead of a raw query string sitting in the address bar. Skipped on
// pages that already render their own inline alert box for this param,
// so the message doesn't show twice.
$_pages_with_own_alert_display = array(
    'admin_invoice_management.php',
    'admin_manage_blocks.php',
    'admin_checkout_management.php',
    'admin_manage_rooms.php',
    'admin_dispute.php',
    'admin_roles.php',
);
$_current_page_for_alert = basename($_SERVER['PHP_SELF']);
$_show_global_alert = isset($_GET['alert']) && !in_array($_current_page_for_alert, $_pages_with_own_alert_display);

// Session-based flash messages (set_flash_message()/get_flash_messages() in
// session.php) get the same modal treatment. get_flash_messages() clears
// the queue on read, so a page that already looped over its own messages
// earlier in the request (e.g. admin_settings.php) naturally gets an empty
// array here - no double-rendering.
$_flash_messages = function_exists('get_flash_messages') ? get_flash_messages() : array();
?>
<script src="js/jquery-3.4.1.min.js"></script>
<script src="js/bootstrap-3.4.1.min.js"></script>
<script src="js/sweetalert2-11.min.js"></script>
<script src="js/print.js"></script>
<script src="js/functions.js"></script>

<?php if (!empty($_flash_messages)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var flashQueue = <?php echo json_encode(array_map(function($m) {
        return array('type' => $m['type'], 'message' => $m['message']);
    }, $_flash_messages)); ?>;

    function showNextFlash() {
        if (flashQueue.length === 0) return;
        var fm = flashQueue.shift();
        var swalIcon = 'info';
        if (fm.type === 'success') swalIcon = 'success';
        else if (fm.type === 'danger' || fm.type === 'error') swalIcon = 'error';
        else if (fm.type === 'warning') swalIcon = 'warning';

        Swal.fire({
            title: swalIcon === 'error' ? 'Access Denied' : (swalIcon === 'success' ? 'Success' : 'Notice'),
            text: fm.message,
            icon: swalIcon,
            confirmButtonText: 'OK',
            confirmButtonColor: swalIcon === 'error' ? '#ff0000' : '#00ff99',
            background: '#0d0d0d',
            color: '#00ff99',
            width: '450px',
            backdrop: 'rgba(0,0,0,0.8)',
            customClass: { popup: 'swal-popup' }
        }).then(function() {
            showNextFlash();
        });
    }
    showNextFlash();
});
</script>
<?php endif; ?>

<?php if ($_show_global_alert): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var alertType = <?php echo json_encode(isset($_GET['type']) ? $_GET['type'] : 'info'); ?>;
    var alertMsg = <?php echo json_encode(urldecode($_GET['alert'])); ?>;
    var swalIcon = 'info';
    if (alertType === 'success') swalIcon = 'success';
    else if (alertType === 'danger' || alertType === 'error') swalIcon = 'error';
    else if (alertType === 'warning') swalIcon = 'warning';

    Swal.fire({
        title: swalIcon === 'error' ? 'Access Denied' : (swalIcon === 'success' ? 'Success' : 'Notice'),
        text: alertMsg,
        icon: swalIcon,
        confirmButtonText: 'OK',
        confirmButtonColor: swalIcon === 'error' ? '#ff0000' : '#00ff99',
        background: '#0d0d0d',
        color: '#00ff99',
        width: '450px',
        backdrop: 'rgba(0,0,0,0.8)',
        customClass: { popup: 'swal-popup' }
    });

    // Strip the alert/type params from the URL so refreshing doesn't re-show it
    if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('alert');
        url.searchParams.delete('type');
        window.history.replaceState({}, document.title, url.toString());
    }
});
</script>
<?php endif; ?>

<script>
// Track if modal is already open to prevent double trigger
var _isModalOpen = false;

// Global function for LINK buttons (with javascript:void(0) href)
function showConfirmModal(message, confirmUrl, warningText, confirmClass, icon, iconColor, title) {
    // Prevent double trigger
    if (_isModalOpen) return false;
    _isModalOpen = true;
    
    var confirmColor = '#ff0000';
    if (confirmClass === 'btn-success') confirmColor = '#00ff99';
    else if (confirmClass === 'btn-warning') confirmColor = '#ffcc00';
    else if (confirmClass === 'btn-danger') confirmColor = '#ff0000';
    
    var swalIcon = 'warning';
    if (icon === 'fa-trash' || icon === 'fa-user-times' || icon === 'fa-times-circle') swalIcon = 'warning';
    else if (icon === 'fa-check-circle') swalIcon = 'success';
    else if (icon === 'fa-user-slash') swalIcon = 'warning';
    
    Swal.fire({
        title: title || 'Confirm Action',
        html: message + '<br><br><span style="color:#888;font-size:12px;">' + (warningText || 'This action cannot be undone.') + '</span>',
        icon: swalIcon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#555',
        confirmButtonText: 'Yes, confirm it!',
        cancelButtonText: 'Cancel',
        background: '#0d0d0d',
        color: '#00ff99',
        width: '500px',
        backdrop: 'rgba(0,0,0,0.8)',
        customClass: {
            confirmButton: 'btn-confirm-swal',
            cancelButton: 'btn-cancel-swal',
            popup: 'swal-popup'
        }
    }).then((result) => {
        _isModalOpen = false;
        if (result.isConfirmed) {
            window.location.href = confirmUrl;
        }
    });
    
    return false;
}

// Global function for FORM SUBMIT buttons - FIXED
function showConfirmModalForm(message, formId, warningText, confirmClass, icon, iconColor, title) {
    // Prevent double trigger
    if (_isModalOpen) return false;
    _isModalOpen = true;
    
    var confirmColor = '#ff0000';
    if (confirmClass === 'btn-success') confirmColor = '#00ff99';
    else if (confirmClass === 'btn-warning') confirmColor = '#ffcc00';
    else if (confirmClass === 'btn-danger') confirmColor = '#ff0000';
    
    var swalIcon = 'warning';
    if (icon === 'fa-trash' || icon === 'fa-user-times' || icon === 'fa-times-circle') swalIcon = 'warning';
    else if (icon === 'fa-check-circle') swalIcon = 'success';
    else if (icon === 'fa-user-slash') swalIcon = 'warning';
    
    Swal.fire({
        title: title || 'Confirm Action',
        html: message + '<br><br><span style="color:#888;font-size:12px;">' + (warningText || 'This action cannot be undone.') + '</span>',
        icon: swalIcon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#555',
        confirmButtonText: 'Yes, confirm it!',
        cancelButtonText: 'Cancel',
        background: '#0d0d0d',
        color: '#00ff99',
        width: '500px',
        backdrop: 'rgba(0,0,0,0.8)',
        customClass: {
            confirmButton: 'btn-confirm-swal',
            cancelButton: 'btn-cancel-swal',
            popup: 'swal-popup'
        }
    }).then((result) => {
        _isModalOpen = false;
        if (result.isConfirmed) {
            // FIXED: Submit the form using jQuery or native JS
            var form = document.getElementById(formId);
            if (form) {
                // Use jQuery if available, else use native
                if (typeof $ !== 'undefined') {
                    $('#' + formId).submit();
                } else {
                    form.submit();
                }
            } else {
                console.error('Form with ID "' + formId + '" not found');
            }
        }
    });
    
    return false;
}

// Override SweetAlert2 styles
document.addEventListener('DOMContentLoaded', function() {
    var style = document.createElement('style');
    style.textContent = `
        .swal-popup {
            border: 2px solid #00ff99 !important;
            border-radius: 8px !important;
            box-shadow: 0 0 60px rgba(0, 255, 153, 0.2) !important;
        }
        .swal-popup .swal2-title {
            color: #00ff99 !important;
        }
        .swal-popup .swal2-html-container {
            color: #ccc !important;
        }
        .swal-popup .swal2-confirm {
            font-weight: bold !important;
            padding: 10px 30px !important;
        }
        .swal-popup .swal2-cancel {
            background: #333 !important;
            color: #aaa !important;
            border: 1px solid #555 !important;
            padding: 10px 30px !important;
        }
        .swal-popup .swal2-cancel:hover {
            background: #444 !important;
            color: #fff !important;
        }
        .swal2-container {
            z-index: 999999 !important;
        }
    `;
    document.head.appendChild(style);
});
</script>

<hr>
<small>Simple PHP Hotel System</small>
</body>
</html>
<?php
?>