<?php
// admin_settings.php - Hotel branding settings (name, address, phone, website, logo)
// Lets a new deployment be rebranded from the admin panel instead of editing PHP files.
require_once __DIR__.'/database.php';
require_once __DIR__.'/session.php';
require_once __DIR__.'/functions.php';

if (!is_admin_logged_in()) {
    header('Location: admin_login.php');
    exit;
}
require_permission();

$config_path = __DIR__ . '/config.php';
$config = include $config_path;
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Invalid session token. Please reload the page and try again.';
    } else {
        $hotel = array(
            'name'          => trim($_POST['hotel_name']),
            'tagline'       => trim($_POST['tagline']),
            'address_line1' => trim($_POST['address_line1']),
            'address_line2' => trim($_POST['address_line2']),
            'phone'         => trim($_POST['phone']),
            'email'         => trim($_POST['email']),
            'website'       => trim($_POST['website']),
            'logo'          => trim($_POST['logo']),
        );

        if (empty($hotel['name'])) {
            $error = 'Hotel name is required.';
        } else {
            $config['hotel'] = $hotel;

            // Rewrite config.php in place. Keep a one-shot backup so a bad
            // write is recoverable without a redeploy.
            if (is_writable($config_path) || is_writable(dirname($config_path))) {
                @copy($config_path, $config_path . '.bak');
                $new_content = "<?php\n// config.php\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($config_path, $new_content) !== false) {
                    log_admin_activity('Update Hotel Settings', 'Updated hotel branding: ' . $hotel['name']);
                    set_flash_message('success', 'Hotel settings updated.');
                    header('Location: admin_settings.php');
                    exit;
                } else {
                    $error = 'Could not write config.php. Check file permissions.';
                }
            } else {
                $error = 'config.php is not writable by the web server. Update file permissions (e.g. chmod 664 config.php) and try again.';
            }
        }
    }
}

$hotel = isset($config['hotel']) ? $config['hotel'] : array();
$get = function ($key, $default = '') use ($hotel) {
    return h(isset($hotel[$key]) ? $hotel[$key] : $default);
};

include __DIR__.'/header.php';
?>
<div class="container mt-4">
    </div>

    <?php foreach (get_flash_messages() as $msg): ?>
        <div class="alert alert-<?php echo h($msg['type']); ?>"><?php echo h($msg['message']); ?></div>
    <?php endforeach; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <?php echo h($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo h(get_csrf_token()); ?>">

                        <div class="form-group">
                            <label>Hotel Name</label>
                            <input type="text" name="hotel_name" class="form-control" value="<?php echo $get('name'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tagline</label>
                            <input type="text" name="tagline" class="form-control" value="<?php echo $get('tagline'); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Address line 1</label>
                                <input type="text" name="address_line1" class="form-control" value="<?php echo $get('address_line1'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address line 2</label>
                                <input type="text" name="address_line2" class="form-control" value="<?php echo $get('address_line2'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo $get('phone'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $get('email'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Website</label>
                            <input type="text" name="website" class="form-control" value="<?php echo $get('website'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Logo path (relative to site root)</label>
                            <input type="text" name="logo" class="form-control" value="<?php echo $get('logo'); ?>">
                            <small class="text-muted">e.g. img/logox.png — used on ID cards and printouts.</small>
                        </div>

                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">Where this shows up</div>
                <div class="panel-body">
                    <ul class="mb-0">
                        <li>Staff ID cards (front & back)</li>
                        <li>Printable invoices / receipts</li>
                        <li>Public invoice lookup page</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__.'/footer.php'; ?>
