<?php
// index.php
require_once __DIR__.'/database.php';
require_once __DIR__.'/session.php';
require_once __DIR__.'/functions.php';
include __DIR__.'/header.php';

$result = mysqli_query($conn, "SELECT r.*, b.name building_name, b.building_type FROM rooms r LEFT JOIN buildings b ON r.building_id=b.id WHERE r.status = 'available'");
?>
<h2>Available Rooms</h2>
<?php if (mysqli_num_rows($result) == 0): ?>
  <div class="terminal-feed">
    <p>No rooms available at the moment.</p>
  </div>
<?php else: ?>
  <div class="row">
    <?php while ($room = mysqli_fetch_assoc($result)): 
        $badge_class = $room['building_type'] == 'vip' ? 'warning' : ($room['building_type'] == 'premium' ? 'success' : 'info');
    ?>
    <div class="col-md-4">
      <div class="card">
        <strong><?php echo h($room['building_name'] . ' - ' . $room['room_number']); ?></strong>
        <span class="badge badge-<?php echo h($badge_class); ?>" style="float:right;">
          <?php echo ucfirst($room['building_type']); ?>
        </span><br>
        Capacity: <?php echo intval($room['capacity']); ?> person(s)<br>
        Price per night: <strong>₦<?php echo number_format($room['price_per_night'], 2); ?></strong><br>
        <a href="reserve.php?room_id=<?php echo intval($room['id']); ?>" class="btn btn-success btn-sm" style="margin-top:10px;">Reserve</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>
<?php include __DIR__.'/footer.php'; ?>