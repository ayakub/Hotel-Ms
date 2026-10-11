<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Guests';

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM Guest WHERE Name LIKE ? OR Phone LIKE ? OR Email LIKE ? ORDER BY GuestID DESC");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM Guest ORDER BY GuestID DESC");
}
$guests = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<form method="get" class="mt" style="margin-bottom:16px;display:flex;gap:8px;max-width:400px">
  <input type="text" name="q" placeholder="Search by name, phone, email" value="<?= e($q) ?>">
  <button class="btn" type="submit">Search</button>
</form>
<a class="btn" href="add.php">+ Add guest</a>
<table class="mt">
<tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>NID</th><th>Actions</th></tr>
<?php foreach ($guests as $g): ?>
<tr>
  <td><?= $g['GuestID'] ?></td>
  <td><?= e($g['Name']) ?></td>
  <td><?= e($g['Phone']) ?></td>
  <td><?= e($g['Email']) ?></td>
  <td><?= e($g['NID']) ?></td>
  <td class="actions">
    <a href="edit.php?id=<?= $g['GuestID'] ?>">Edit</a>
    <a href="delete.php?id=<?= $g['GuestID'] ?>" onclick="return confirm('Delete this guest?')">Delete</a>
    <a href="../bookings/list.php?guest=<?= $g['GuestID'] ?>">History</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
