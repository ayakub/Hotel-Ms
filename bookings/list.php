<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Bookings';

$guestFilter = $_GET['guest'] ?? null;
$sql = "SELECT b.*, g.Name AS GuestName FROM Booking b JOIN Guest g ON g.GuestID = b.GuestID";
$params = [];
if ($guestFilter) { $sql .= " WHERE b.GuestID = ?"; $params[] = $guestFilter; }
$sql .= " ORDER BY b.BookingID DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

function bStatusBadge($s) {
    $map = ['Pending'=>'gray','Confirmed'=>'blue','Cancelled'=>'red','Checked-In'=>'amber','Checked-Out'=>'green'];
    return "<span class='badge badge-" . ($map[$s] ?? 'gray') . "'>" . e($s) . "</span>";
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<a class="btn" href="add.php">+ New booking</a>
<table class="mt">
<tr><th>ID</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
  <td><?= $b['BookingID'] ?></td>
  <td><?= e($b['GuestName']) ?></td>
  <td><?= e($b['CheckInDate']) ?></td>
  <td><?= e($b['CheckOutDate']) ?></td>
  <td><?= bStatusBadge($b['Status']) ?></td>
  <td class="actions"><a href="view.php?id=<?= $b['BookingID'] ?>">View / manage</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
