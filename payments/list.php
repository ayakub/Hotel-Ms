<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Payments';

$payments = $pdo->query("SELECT p.*, g.Name AS GuestName, b.BookingID
                          FROM Payment p JOIN Booking b ON b.BookingID=p.BookingID JOIN Guest g ON g.GuestID=b.GuestID
                          ORDER BY p.PaymentID DESC")->fetchAll();
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<table class="mt">
<tr><th>ID</th><th>Booking</th><th>Guest</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
<?php foreach ($payments as $p): ?>
<tr>
  <td><?= $p['PaymentID'] ?></td>
  <td><a href="../bookings/view.php?id=<?= $p['BookingID'] ?>">#<?= $p['BookingID'] ?></a></td>
  <td><?= e($p['GuestName']) ?></td>
  <td>৳<?= money($p['Amount']) ?></td>
  <td><?= e($p['PaymentMethod']) ?></td>
  <td><?= e($p['PaymentStatus']) ?></td>
  <td><?= e($p['PaymentDate']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
