<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$pageTitle = 'User accounts';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username']);
    $existing = $pdo->prepare("SELECT UserID FROM Users WHERE Username=?");
    $existing->execute([$username]);
    if ($existing->fetch()) {
        flash('error', 'Username already exists.');
    } else {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $staffId = $_POST['staff_id'] ?: null;
        $stmt = $pdo->prepare("INSERT INTO Users (Username, PasswordHash, Role, StaffID) VALUES (?,?,?,?)");
        $stmt->execute([$username, $hash, $_POST['role'], $staffId]);
        flash('success', 'User account created.');
    }
    redirect('list.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if ((int)$_POST['id'] !== (int)$_SESSION['user']['UserID']) {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID=?");
        $stmt->execute([$_POST['id']]);
        flash('success', 'User deleted.');
    } else {
        flash('error', 'You cannot delete your own account while logged in.');
    }
    redirect('list.php');
}

$users = $pdo->query("SELECT u.*, s.Name AS StaffName FROM Users u LEFT JOIN Staff s ON s.StaffID = u.StaffID ORDER BY u.UserID")->fetchAll();
$staffList = $pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<h3>Create user account</h3>
<form class="form-box mt" method="post">
  <input type="hidden" name="action" value="add">
  <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
  <div class="form-group"><label>Password *</label><input type="password" name="password" required minlength="6"></div>
  <div class="form-group"><label>Role *</label>
    <select name="role"><option>Admin</option><option>Receptionist</option><option>Housekeeping</option></select>
  </div>
  <div class="form-group"><label>Linked staff (optional)</label>
    <select name="staff_id">
      <option value="">-- none --</option>
      <?php foreach ($staffList as $s): ?><option value="<?= $s['StaffID'] ?>"><?= e($s['Name']) ?> (<?= e($s['Position']) ?>)</option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn" type="submit">Create account</button>
</form>

<h3 class="mt">All accounts</h3>
<table class="mt">
<tr><th>ID</th><th>Username</th><th>Role</th><th>Linked staff</th><th>Created</th><th>Actions</th></tr>
<?php foreach ($users as $u): ?>
<tr>
  <td><?= $u['UserID'] ?></td><td><?= e($u['Username']) ?></td><td><?= e($u['Role']) ?></td>
  <td><?= e($u['StaffName'] ?? '-') ?></td><td><?= e($u['CreatedAt']) ?></td>
  <td>
    <form method="post" style="display:inline" onsubmit="return confirm('Delete this account?')">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= $u['UserID'] ?>">
      <button class="btn btn-danger" type="submit" style="padding:4px 10px">Delete</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
