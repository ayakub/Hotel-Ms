<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Services';
$role = $_SESSION['user']['Role'];

if ($role === 'Admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO Service (ServiceName, Price) VALUES (?,?)");
    $stmt->execute([$_POST['service_name'], $_POST['price']]);
    flash('success', 'Service added.');
    redirect('list.php');
}
if ($role === 'Admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM Service WHERE ServiceID=?");
        $stmt->execute([$_POST['id']]);
        flash('success', 'Service deleted.');
    } catch (PDOException $e) {
        flash('error', 'Cannot delete: service is used in existing bookings.');
    }
    redirect('list.php');
}
$services = $pdo->query("SELECT * FROM Service ORDER BY ServiceID")->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<?php if ($role === 'Admin'): ?>
<h3>Add service</h3>
<form class="form-box mt" method="post">
  <input type="hidden" name="action" value="add">
  <div class="form-group"><label>Service name *</label><input type="text" name="service_name" required></div>
  <div class="form-group"><label>Price *</label><input type="number" step="0.01" name="price" required></div>
  <button class="btn" type="submit">Add service</button>
</form>
<?php endif; ?>
<h3 class="mt">All services</h3>
<table class="mt">
<tr><th>ID</th><th>Service</th><th>Price</th><?php if($role==='Admin'):?><th>Actions</th><?php endif;?></tr>
<?php foreach ($services as $s): ?>
<tr>
  <td><?= $s['ServiceID'] ?></td><td><?= e($s['ServiceName']) ?></td><td>৳<?= money($s['Price']) ?></td>
  <?php if ($role === 'Admin'): ?>
  <td>
    <form method="post" onsubmit="return confirm('Delete this service?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $s['ServiceID'] ?>">
      <button class="btn btn-danger" type="submit" style="padding:4px 10px">Delete</button>
    </form>
  </td>
  <?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
