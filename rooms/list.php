<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Housekeeping']);
$pageTitle = 'Rooms';
$role = $_SESSION['user']['Role'];

// Housekeeping can only update status; Admin can add/edit/delete rooms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $stmt = $pdo->prepare("UPDATE Room SET Status=? WHERE RoomID=?");
    $stmt->execute([$_POST['status'], $_POST['id']]);
    flash('success', 'Room status updated.');
    redirect('list.php');
}
if ($role === 'Admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO Room (RoomNumber, FloorNo, Status, RoomTypeID) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['room_number'], $_POST['floor'], $_POST['status'], $_POST['room_type']]);
    flash('success', 'Room added.');
    redirect('list.php');
}

$rooms = $pdo->query("SELECT r.*, rt.TypeName, rt.PricePerNight FROM Room r JOIN RoomType rt ON rt.RoomTypeID = r.RoomTypeID ORDER BY r.RoomNumber")->fetchAll();
$types = $pdo->query("SELECT * FROM RoomType ORDER BY TypeName")->fetchAll();

function statusBadge($s) {
    $map = ['Available'=>'green','Reserved'=>'blue','Occupied'=>'amber','Cleaning'=>'gray','Maintenance'=>'red'];
    $c = $map[$s] ?? 'gray';
    return "<span class='badge badge-$c'>" . e($s) . "</span>";
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<?php if ($role === 'Admin'): ?>
<h3>Add room</h3>
<form class="form-box mt" method="post">
  <input type="hidden" name="action" value="add">
  <div class="form-group"><label>Room number *</label><input type="text" name="room_number" required></div>
  <div class="form-group"><label>Floor</label><input type="number" name="floor"></div>
  <div class="form-group"><label>Room type *</label>
    <select name="room_type" required>
      <?php foreach ($types as $t): ?><option value="<?= $t['RoomTypeID'] ?>"><?= e($t['TypeName']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Status</label>
    <select name="status">
      <option>Available</option><option>Maintenance</option>
    </select>
  </div>
  <button class="btn" type="submit">Add room</button>
</form>
<?php endif; ?>

<h3 class="mt">All rooms</h3>
<table class="mt">
<tr><th>Room #</th><th>Floor</th><th>Type</th><th>Price/night</th><th>Status</th><th>Update status</th></tr>
<?php foreach ($rooms as $r): ?>
<tr>
  <td><?= e($r['RoomNumber']) ?></td>
  <td><?= e($r['FloorNo']) ?></td>
  <td><?= e($r['TypeName']) ?></td>
  <td>৳<?= money($r['PricePerNight']) ?></td>
  <td><?= statusBadge($r['Status']) ?></td>
  <td>
    <form method="post" style="display:flex;gap:6px">
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="id" value="<?= $r['RoomID'] ?>">
      <select name="status" style="width:130px">
        <?php foreach (['Available','Reserved','Occupied','Cleaning','Maintenance'] as $s): ?>
          <option <?= $s===$r['Status']?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit" style="padding:6px 12px">Set</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
