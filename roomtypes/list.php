<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$pageTitle = 'Room types';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO RoomType (TypeName, PricePerNight, Capacity, Description) VALUES (?,?,?,?)");
    $stmt->execute([$_POST['type_name'], $_POST['price'], $_POST['capacity'], $_POST['description']]);
    flash('success', 'Room type added.');
    redirect('list.php');
}
$types = $pdo->query("SELECT * FROM RoomType ORDER BY RoomTypeID")->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<h3>Add room type</h3>
<form class="form-box mt" method="post">
  <input type="hidden" name="action" value="add">
  <div class="form-group"><label>Type name *</label><input type="text" name="type_name" required></div>
  <div class="form-group"><label>Price per night *</label><input type="number" step="0.01" name="price" required></div>
  <div class="form-group"><label>Capacity *</label><input type="number" name="capacity" required></div>
  <div class="form-group"><label>Description</label><input type="text" name="description"></div>
  <button class="btn" type="submit">Add</button>
</form>

<h3 class="mt">All room types</h3>
<table class="mt">
<tr><th>ID</th><th>Type</th><th>Price/night</th><th>Capacity</th><th>Description</th><th>Actions</th></tr>
<?php foreach ($types as $t): ?>
<tr>
  <td><?= $t['RoomTypeID'] ?></td>
  <td><?= e($t['TypeName']) ?></td>
  <td>৳<?= money($t['PricePerNight']) ?></td>
  <td><?= $t['Capacity'] ?></td>
  <td><?= e($t['Description']) ?></td>
  <td class="actions">
    <a href="edit.php?id=<?= $t['RoomTypeID'] ?>">Edit</a>
    <a href="delete.php?id=<?= $t['RoomTypeID'] ?>" onclick="return confirm('Delete this room type?')">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
