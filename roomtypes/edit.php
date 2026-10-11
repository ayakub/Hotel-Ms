<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$pageTitle = 'Edit room type';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM RoomType WHERE RoomTypeID=?");
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) { flash('error','Room type not found.'); redirect('list.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE RoomType SET TypeName=?, PricePerNight=?, Capacity=?, Description=? WHERE RoomTypeID=?");
    $stmt->execute([$_POST['type_name'], $_POST['price'], $_POST['capacity'], $_POST['description'], $id]);
    flash('success', 'Room type updated.');
    redirect('list.php');
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<form class="form-box" method="post">
  <div class="form-group"><label>Type name *</label><input type="text" name="type_name" value="<?= e($t['TypeName']) ?>" required></div>
  <div class="form-group"><label>Price per night *</label><input type="number" step="0.01" name="price" value="<?= e($t['PricePerNight']) ?>" required></div>
  <div class="form-group"><label>Capacity *</label><input type="number" name="capacity" value="<?= e($t['Capacity']) ?>" required></div>
  <div class="form-group"><label>Description</label><input type="text" name="description" value="<?= e($t['Description']) ?>"></div>
  <button class="btn" type="submit">Update</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
