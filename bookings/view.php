<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Booking details';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, g.Name AS GuestName, g.Phone, g.Email, s.Name AS StaffName
                        FROM Booking b
                        JOIN Guest g ON g.GuestID = b.GuestID
                        LEFT JOIN Staff s ON s.StaffID = b.StaffID
                        WHERE b.BookingID = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) { flash('error', 'Booking not found.'); redirect('list.php'); }

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'checkin' && $b['Status'] === 'Confirmed') {
        $pdo->prepare("UPDATE Booking SET Status='Checked-In', ActualCheckIn=NOW() WHERE BookingID=?")->execute([$id]);
        $roomIds = $pdo->prepare("SELECT RoomID FROM BookingRoom WHERE BookingID=?");
        $roomIds->execute([$id]);
        $upd = $pdo->prepare("UPDATE Room SET Status='Occupied' WHERE RoomID=?");
        foreach ($roomIds->fetchAll() as $r) $upd->execute([$r['RoomID']]);
        flash('success', 'Guest checked in.');
        redirect("view.php?id=$id");
    }

    if ($action === 'checkout' && $b['Status'] === 'Checked-In') {
        $pdo->prepare("UPDATE Booking SET Status='Checked-Out', ActualCheckOut=NOW() WHERE BookingID=?")->execute([$id]);
        $roomIds = $pdo->prepare("SELECT RoomID FROM BookingRoom WHERE BookingID=?");
        $roomIds->execute([$id]);
        $upd = $pdo->prepare("UPDATE Room SET Status='Cleaning' WHERE RoomID=?");
        foreach ($roomIds->fetchAll() as $r) $upd->execute([$r['RoomID']]);
        flash('success', 'Guest checked out. Rooms marked for cleaning.');
        redirect("view.php?id=$id");
    }

    if ($action === 'cancel' && in_array($b['Status'], ['Pending','Confirmed'])) {
        $pdo->prepare("UPDATE Booking SET Status='Cancelled' WHERE BookingID=?")->execute([$id]);
        flash('success', 'Booking cancelled. Rooms are available again.');
        redirect("view.php?id=$id");
    }

    if ($action === 'add_payment') {
        $bill = booking_bill($pdo, $id);
        $amount = (float)$_POST['amount'];
        if ($amount <= 0) {
            flash('error', 'Enter a valid payment amount.');
        } elseif ($amount > $bill['due'] + 0.01) {
            flash('error', 'Paid amount cannot exceed the due amount (৳' . money($bill['due']) . ').');
        } else {
            $newPaid = $bill['paid'] + $amount;
            $status = $newPaid >= $bill['total'] ? 'Paid' : 'Partial';
            $stmt = $pdo->prepare("INSERT INTO Payment (BookingID, Amount, PaymentMethod, PaymentStatus, TransactionID) VALUES (?,?,?,?,?)");
            $stmt->execute([$id, $amount, $_POST['method'], $status, $_POST['txn'] ?: null]);
            flash('success', 'Payment recorded.');
        }
        redirect("view.php?id=$id");
    }

    if ($action === 'add_service') {
        $svc = (int)$_POST['service_id'];
        $qty = max(1, (int)$_POST['quantity']);
        $exists = $pdo->prepare("SELECT Quantity FROM BookingService WHERE BookingID=? AND ServiceID=?");
        $exists->execute([$id, $svc]);
        if ($row = $exists->fetch()) {
            $pdo->prepare("UPDATE BookingService SET Quantity=? WHERE BookingID=? AND ServiceID=?")
                ->execute([$row['Quantity'] + $qty, $id, $svc]);
        } else {
            $pdo->prepare("INSERT INTO BookingService (BookingID, ServiceID, Quantity) VALUES (?,?,?)")->execute([$id, $svc, $qty]);
        }
        flash('success', 'Service added to booking.');
        redirect("view.php?id=$id");
    }
}

// ---- Load related data ----
$roomsStmt = $pdo->prepare("SELECT br.PricePerNight, r.RoomNumber, rt.TypeName
                             FROM BookingRoom br JOIN Room r ON r.RoomID = br.RoomID JOIN RoomType rt ON rt.RoomTypeID = r.RoomTypeID
                             WHERE br.BookingID=?");
$roomsStmt->execute([$id]);
$rooms = $roomsStmt->fetchAll();

$paymentsStmt = $pdo->prepare("SELECT * FROM Payment WHERE BookingID=? ORDER BY PaymentID");
$paymentsStmt->execute([$id]);
$payments = $paymentsStmt->fetchAll();

$servicesStmt = $pdo->prepare("SELECT bs.Quantity, s.ServiceName, s.Price, s.ServiceID
                                FROM BookingService bs JOIN Service s ON s.ServiceID = bs.ServiceID WHERE bs.BookingID=?");
$servicesStmt->execute([$id]);
$bookingServices = $servicesStmt->fetchAll();

$allServices = $pdo->query("SELECT * FROM Service ORDER BY ServiceName")->fetchAll();
$bill = booking_bill($pdo, $id);

function bStatusBadge2($s) {
    $map = ['Pending'=>'gray','Confirmed'=>'blue','Cancelled'=>'red','Checked-In'=>'amber','Checked-Out'=>'green'];
    return "<span class='badge badge-" . ($map[$s] ?? 'gray') . "'>" . e($s) . "</span>";
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<h3>Booking #<?= $b['BookingID'] ?> &nbsp; <?= bStatusBadge2($b['Status']) ?></h3>
<div class="cards mt" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
  <div class="card"><div class="label">Guest</div><div class="value" style="font-size:16px"><?= e($b['GuestName']) ?></div><div class="label"><?= e($b['Phone']) ?></div></div>
  <div class="card"><div class="label">Check-in</div><div class="value" style="font-size:16px"><?= e($b['CheckInDate']) ?></div></div>
  <div class="card"><div class="label">Check-out</div><div class="value" style="font-size:16px"><?= e($b['CheckOutDate']) ?></div></div>
  <div class="card"><div class="label">Handled by</div><div class="value" style="font-size:16px"><?= e($b['StaffName'] ?? '-') ?></div></div>
</div>

<div class="actions mt">
  <?php if ($b['Status'] === 'Confirmed'): ?>
    <form method="post" style="display:inline"><input type="hidden" name="action" value="checkin"><button class="btn" type="submit">Check in</button></form>
  <?php endif; ?>
  <?php if ($b['Status'] === 'Checked-In'): ?>
    <form method="post" style="display:inline"><input type="hidden" name="action" value="checkout"><button class="btn" type="submit">Check out</button></form>
  <?php endif; ?>
  <?php if (in_array($b['Status'], ['Pending','Confirmed'])): ?>
    <form method="post" style="display:inline" onsubmit="return confirm('Cancel this booking?')"><input type="hidden" name="action" value="cancel"><button class="btn btn-danger" type="submit">Cancel booking</button></form>
  <?php endif; ?>
  <a class="btn btn-secondary" href="../invoice/view.php?id=<?= $id ?>" target="_blank">View invoice</a>
</div>

<h3 class="mt">Rooms booked</h3>
<table class="mt">
<tr><th>Room #</th><th>Type</th><th>Price/night</th></tr>
<?php foreach ($rooms as $r): ?>
<tr><td><?= e($r['RoomNumber']) ?></td><td><?= e($r['TypeName']) ?></td><td>৳<?= money($r['PricePerNight']) ?></td></tr>
<?php endforeach; ?>
</table>

<h3 class="mt">Services</h3>
<table class="mt">
<tr><th>Service</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr>
<?php foreach ($bookingServices as $s): ?>
<tr><td><?= e($s['ServiceName']) ?></td><td><?= $s['Quantity'] ?></td><td>৳<?= money($s['Price']) ?></td><td>৳<?= money($s['Price']*$s['Quantity']) ?></td></tr>
<?php endforeach; ?>
</table>
<form method="post" class="mt" style="display:flex;gap:8px;align-items:end;max-width:420px">
  <input type="hidden" name="action" value="add_service">
  <div class="form-group" style="flex:2"><label>Add service</label>
    <select name="service_id" required>
      <?php foreach ($allServices as $s): ?><option value="<?= $s['ServiceID'] ?>"><?= e($s['ServiceName']) ?> (৳<?= money($s['Price']) ?>)</option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group" style="width:80px"><label>Qty</label><input type="number" name="quantity" value="1" min="1"></div>
  <button class="btn" type="submit">Add</button>
</form>

<h3 class="mt">Bill summary</h3>
<table class="mt" style="max-width:420px">
<tr><td>Nights</td><td class="text-right"><?= $bill['nights'] ?></td></tr>
<tr><td>Room charge</td><td class="text-right">৳<?= money($bill['room_charge']) ?></td></tr>
<tr><td>Service charge</td><td class="text-right">৳<?= money($bill['service_charge']) ?></td></tr>
<tr><td><strong>Total</strong></td><td class="text-right"><strong>৳<?= money($bill['total']) ?></strong></td></tr>
<tr><td>Paid</td><td class="text-right">৳<?= money($bill['paid']) ?></td></tr>
<tr><td><strong>Due</strong></td><td class="text-right"><strong>৳<?= money($bill['due']) ?></strong></td></tr>
</table>

<h3 class="mt">Payments</h3>
<table class="mt">
<tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Txn ID</th></tr>
<?php foreach ($payments as $p): ?>
<tr><td><?= e($p['PaymentDate']) ?></td><td>৳<?= money($p['Amount']) ?></td><td><?= e($p['PaymentMethod']) ?></td><td><?= e($p['PaymentStatus']) ?></td><td><?= e($p['TransactionID']) ?></td></tr>
<?php endforeach; ?>
</table>
<?php if ($bill['due'] > 0): ?>
<form method="post" class="mt" style="display:flex;gap:8px;align-items:end;max-width:500px">
  <input type="hidden" name="action" value="add_payment">
  <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" max="<?= $bill['due'] ?>" required></div>
  <div class="form-group"><label>Method</label>
    <select name="method"><option>Cash</option><option>Card</option><option>Mobile Banking</option></select>
  </div>
  <div class="form-group"><label>Transaction ID</label><input type="text" name="txn"></div>
  <button class="btn" type="submit">Record payment</button>
</form>
<?php else: ?>
  <p class="mt" style="color:#27500a">Fully paid.</p>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
