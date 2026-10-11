<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$pageTitle = 'Reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Revenue report
$stmt = $pdo->prepare("SELECT PaymentDate, SUM(Amount) AS Total FROM Payment
                        WHERE PaymentStatus != 'Refunded' AND PaymentDate BETWEEN ? AND ?
                        GROUP BY PaymentDate ORDER BY PaymentDate");
$stmt->execute([$from, $to]);
$revenue = $stmt->fetchAll();
$revenueTotal = array_sum(array_column($revenue, 'Total'));

// Booking report
$stmt = $pdo->prepare("SELECT b.BookingID, g.Name, b.CheckInDate, b.CheckOutDate, b.Status
                        FROM Booking b JOIN Guest g ON g.GuestID=b.GuestID
                        WHERE b.BookingDate BETWEEN ? AND ? ORDER BY b.BookingID DESC");
$stmt->execute([$from, $to]);
$bookings = $stmt->fetchAll();

// Room occupancy report
$occupancy = $pdo->query("SELECT Status, COUNT(*) c FROM Room GROUP BY Status")->fetchAll();

// Service usage report
$stmt = $pdo->prepare("SELECT s.ServiceName, SUM(bs.Quantity) AS TotalQty, SUM(bs.Quantity*s.Price) AS TotalRevenue
                        FROM BookingService bs JOIN Service s ON s.ServiceID=bs.ServiceID
                        JOIN Booking b ON b.BookingID = bs.BookingID
                        WHERE b.BookingDate BETWEEN ? AND ?
                        GROUP BY s.ServiceID ORDER BY TotalRevenue DESC");
$stmt->execute([$from, $to]);
$serviceUsage = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<form method="get" style="display:flex;gap:10px;align-items:end">
  <div class="form-group"><label>From</label><input type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="form-group"><label>To</label><input type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn" type="submit">Filter</button>
</form>

<h3 class="mt">Revenue report (total: ৳<?= money($revenueTotal) ?>)</h3>
<table class="mt">
<tr><th>Date</th><th>Amount</th></tr>
<?php foreach ($revenue as $r): ?><tr><td><?= e($r['PaymentDate']) ?></td><td>৳<?= money($r['Total']) ?></td></tr><?php endforeach; ?>
</table>

<h3 class="mt">Booking report</h3>
<table class="mt">
<tr><th>ID</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
<?php foreach ($bookings as $b): ?>
<tr><td><?= $b['BookingID'] ?></td><td><?= e($b['Name']) ?></td><td><?= e($b['CheckInDate']) ?></td><td><?= e($b['CheckOutDate']) ?></td><td><?= e($b['Status']) ?></td></tr>
<?php endforeach; ?>
</table>

<h3 class="mt">Room occupancy</h3>
<table class="mt">
<tr><th>Status</th><th>Room count</th></tr>
<?php foreach ($occupancy as $o): ?><tr><td><?= e($o['Status']) ?></td><td><?= $o['c'] ?></td></tr><?php endforeach; ?>
</table>

<h3 class="mt">Service usage</h3>
<table class="mt">
<tr><th>Service</th><th>Total quantity</th><th>Revenue</th></tr>
<?php foreach ($serviceUsage as $s): ?>
<tr><td><?= e($s['ServiceName']) ?></td><td><?= $s['TotalQty'] ?></td><td>৳<?= money($s['TotalRevenue']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
