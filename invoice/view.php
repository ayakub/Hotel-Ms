<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT b.*, g.Name, g.Phone, g.Email, g.Address FROM Booking b JOIN Guest g ON g.GuestID=b.GuestID WHERE b.BookingID=?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) die('Booking not found.');

$roomsStmt = $pdo->prepare("SELECT br.PricePerNight, r.RoomNumber, rt.TypeName FROM BookingRoom br
                             JOIN Room r ON r.RoomID=br.RoomID JOIN RoomType rt ON rt.RoomTypeID=r.RoomTypeID WHERE br.BookingID=?");
$roomsStmt->execute([$id]);
$rooms = $roomsStmt->fetchAll();

$servicesStmt = $pdo->prepare("SELECT bs.Quantity, s.ServiceName, s.Price FROM BookingService bs JOIN Service s ON s.ServiceID=bs.ServiceID WHERE bs.BookingID=?");
$servicesStmt->execute([$id]);
$services = $servicesStmt->fetchAll();

$bill = booking_bill($pdo, $id);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Invoice #<?= $id ?></title>
<style>
body{font-family:Arial,sans-serif;padding:30px;color:#222}
h1{font-size:20px;border-bottom:2px solid #185fa5;padding-bottom:10px}
table{width:100%;border-collapse:collapse;margin-top:14px}
td,th{padding:8px;border-bottom:1px solid #ddd;text-align:left;font-size:14px}
.text-right{text-align:right}
.total-row td{font-weight:bold;border-top:2px solid #333}
.btn{background:#185fa5;color:#fff;padding:8px 16px;border:none;border-radius:6px;cursor:pointer;margin-top:20px}
@media print{.btn{display:none}}
</style></head>
<body>
<h1>Hotel Reservation System &mdash; Invoice #<?= $id ?></h1>
<p><strong>Guest:</strong> <?= e($b['Name']) ?> | <strong>Phone:</strong> <?= e($b['Phone']) ?> | <strong>Email:</strong> <?= e($b['Email']) ?></p>
<p><strong>Address:</strong> <?= e($b['Address']) ?></p>
<p><strong>Check-in:</strong> <?= e($b['CheckInDate']) ?> &nbsp; <strong>Check-out:</strong> <?= e($b['CheckOutDate']) ?> &nbsp; <strong>Nights:</strong> <?= $bill['nights'] ?></p>

<table>
<tr><th>Room</th><th>Type</th><th>Price/night</th><th class="text-right">Nights</th><th class="text-right">Subtotal</th></tr>
<?php foreach ($rooms as $r): ?>
<tr><td><?= e($r['RoomNumber']) ?></td><td><?= e($r['TypeName']) ?></td><td>৳<?= money($r['PricePerNight']) ?></td>
<td class="text-right"><?= $bill['nights'] ?></td><td class="text-right">৳<?= money($r['PricePerNight']*$bill['nights']) ?></td></tr>
<?php endforeach; ?>
</table>

<?php if ($services): ?>
<table>
<tr><th>Service</th><th class="text-right">Qty</th><th class="text-right">Unit price</th><th class="text-right">Subtotal</th></tr>
<?php foreach ($services as $s): ?>
<tr><td><?= e($s['ServiceName']) ?></td><td class="text-right"><?= $s['Quantity'] ?></td><td class="text-right">৳<?= money($s['Price']) ?></td><td class="text-right">৳<?= money($s['Price']*$s['Quantity']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<table>
<tr><td>Room charge</td><td class="text-right">৳<?= money($bill['room_charge']) ?></td></tr>
<tr><td>Service charge</td><td class="text-right">৳<?= money($bill['service_charge']) ?></td></tr>
<tr class="total-row"><td>Grand total</td><td class="text-right">৳<?= money($bill['total']) ?></td></tr>
<tr><td>Paid</td><td class="text-right">৳<?= money($bill['paid']) ?></td></tr>
<tr><td>Due</td><td class="text-right">৳<?= money($bill['due']) ?></td></tr>
</table>

<button class="btn" onclick="window.print()">Print invoice</button>
</body></html>
