<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Edit guest';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM Guest WHERE GuestID=?");
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) { flash('error','Guest not found.'); redirect('list.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE Guest SET Name=?, Phone=?, Email=?, Address=?, NID=? WHERE GuestID=?");
    $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['nid'], $id]);
    flash('success', 'Guest updated.');
    redirect('list.php');
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<form class="form-box" method="post">
  <div class="form-group"><label>Name *</label><input type="text" name="name" value="<?= e($guest['Name']) ?>" required></div>
  <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($guest['Phone']) ?>"></div>
  <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($guest['Email']) ?>"></div>
  <div class="form-group"><label>Address</label><input type="text" name="address" value="<?= e($guest['Address']) ?>"></div>
  <div class="form-group"><label>NID</label><input type="text" name="nid" value="<?= e($guest['NID']) ?>"></div>
  <button class="btn" type="submit">Update guest</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
