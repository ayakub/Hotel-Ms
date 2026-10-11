<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$pageTitle = 'Staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO Staff (Name, Position, Phone, Salary, HireDate) VALUES (?,?,?,?,?)");
    $stmt->execute([$_POST['name'], $_POST['position'], $_POST['phone'], $_POST['salary'], $_POST['hire_date']]);
    flash('success', 'Staff member added.');
    redirect('list.php');
}
$staff = $pdo->query("SELECT * FROM Staff ORDER BY StaffID")->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<h3>Add staff</h3>
<form class="form-box mt" method="post">
  <input type="hidden" name="action" value="add">
  <div class="form-group"><label>Name *</label><input type="text" name="name" required></div>
  <div class="form-group"><label>Position *</label>
    <select name="position"><option>Manager</option><option>Receptionist</option><option>Housekeeping</option></select>
  </div>
  <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
  <div class="form-group"><label>Salary</label><input type="number" step="0.01" name="salary"></div>
  <div class="form-group"><label>Hire date</label><input type="date" name="hire_date"></div>
  <button class="btn" type="submit">Add staff</button>
</form>

<h3 class="mt">All staff</h3>
<table class="mt">
<tr><th>ID</th><th>Name</th><th>Position</th><th>Phone</th><th>Salary</th><th>Hire date</th></tr>
<?php foreach ($staff as $s): ?>
<tr>
  <td><?= $s['StaffID'] ?></td><td><?= e($s['Name']) ?></td><td><?= e($s['Position']) ?></td>
  <td><?= e($s['Phone']) ?></td><td>৳<?= money($s['Salary']) ?></td><td><?= e($s['HireDate']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<p class="mt" style="color:#5f5e5a;font-size:13px">To let a staff member log in, create a linked account from <a href="../users/list.php">User accounts</a>.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
