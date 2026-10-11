<?php
require_once __DIR__ . '/includes/init.php';
require_login();
$pageTitle = 'Dashboard';

$totalGuests   = $pdo->query("SELECT COUNT(*) c FROM Guest")->fetch()['c'];
$totalRooms    = $pdo->query("SELECT COUNT(*) c FROM Room")->fetch()['c'];
$availRooms    = $pdo->query("SELECT COUNT(*) c FROM Room WHERE Status='Available'")->fetch()['c'];
$occRooms      = $pdo->query("SELECT COUNT(*) c FROM Room WHERE Status='Occupied'")->fetch()['c'];
$todayCheckIn  = $pdo->query("SELECT COUNT(*) c FROM Booking WHERE CheckInDate = CURDATE() AND Status IN ('Confirmed','Pending')")->fetch()['c'];
$todayCheckOut = $pdo->query("SELECT COUNT(*) c FROM Booking WHERE CheckOutDate = CURDATE() AND Status = 'Checked-In'")->fetch()['c'];
$totalBookings = $pdo->query("SELECT COUNT(*) c FROM Booking")->fetch()['c'];
$pendingPay    = $pdo->query("SELECT COUNT(DISTINCT BookingID) c FROM Booking WHERE Status IN ('Checked-In','Checked-Out','Confirmed')")->fetch()['c'];
$totalRevenue  = $pdo->query("SELECT COALESCE(SUM(Amount),0) s FROM Payment WHERE PaymentStatus != 'Refunded'")->fetch()['s'];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>
<div class="cards">
  <div class="card"><div class="label">Total guests</div><div class="value"><?= $totalGuests ?></div></div>
  <div class="card"><div class="label">Total rooms</div><div class="value"><?= $totalRooms ?></div></div>
  <div class="card"><div class="label">Available rooms</div><div class="value"><?= $availRooms ?></div></div>
  <div class="card"><div class="label">Occupied rooms</div><div class="value"><?= $occRooms ?></div></div>
  <div class="card"><div class="label">Today's check-ins</div><div class="value"><?= $todayCheckIn ?></div></div>
  <div class="card"><div class="label">Today's check-outs</div><div class="value"><?= $todayCheckOut ?></div></div>
  <div class="card"><div class="label">Total bookings</div><div class="value"><?= $totalBookings ?></div></div>
  <div class="card"><div class="label">Total revenue</div><div class="value">৳<?= money($totalRevenue) ?></div></div>
</div>

<h3 style="margin-bottom:10px">Recent bookings</h3>
<table>
<tr><th>ID</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
<?php
$recent = $pdo->query("SELECT b.BookingID, g.Name, b.CheckInDate, b.CheckOutDate, b.Status
                        FROM Booking b JOIN Guest g ON g.GuestID = b.GuestID
                        ORDER BY b.BookingID DESC LIMIT 8")->fetchAll();
foreach ($recent as $r): ?>
  <tr>
    <td><?= $r['BookingID'] ?></td>
    <td><?= e($r['Name']) ?></td>
    <td><?= e($r['CheckInDate']) ?></td>
    <td><?= e($r['CheckOutDate']) ?></td>
    <td><span class="badge badge-blue"><?= e($r['Status']) ?></span></td>
  </tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
