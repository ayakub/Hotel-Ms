<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'New booking';

$checkIn  = $_GET['check_in']  ?? $_POST['check_in']  ?? '';
$checkOut = $_GET['check_out'] ?? $_POST['check_out'] ?? '';
$roomType = $_GET['room_type'] ?? $_POST['room_type'] ?? '';
$rooms = [];
$searchError = null;

if ($checkIn && $checkOut) {
    if (strtotime($checkOut) <= strtotime($checkIn)) {
        $searchError = 'Check-out date must be after check-in date.';
    } else {
        $rooms = available_rooms($pdo, $checkIn, $checkOut, $roomType ?: null);
    }
}

// Handle booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $guestId = (int)$_POST['guest_id'];
    $selectedRooms = $_POST['room_ids'] ?? [];

    if (!$guestId) {
        flash('error', 'Please select a guest.');
    } elseif (empty($selectedRooms)) {
        flash('error', 'Please select at least one room.');
    } elseif (strtotime($checkOut) <= strtotime($checkIn)) {
        flash('error', 'Check-out date must be after check-in date.');
    } else {
        // Re-validate availability right before insert to avoid race conditions
        $stillAvailable = available_rooms($pdo, $checkIn, $checkOut);
        $availableIds = array_column($stillAvailable, 'RoomID');
        $chosen = array_filter($selectedRooms, fn($id) => in_array((int)$id, $availableIds));

        if (empty($chosen)) {
            flash('error', 'Selected room(s) are no longer available for these dates.');
        } else {
            $pdo->beginTransaction();
            try {
                $staffId = $_SESSION['user']['StaffID'] ?? null;
                $stmt = $pdo->prepare("INSERT INTO Booking (GuestID, StaffID, CheckInDate, CheckOutDate, Status) VALUES (?,?,?,?, 'Confirmed')");
                $stmt->execute([$guestId, $staffId, $checkIn, $checkOut]);
                $bookingId = $pdo->lastInsertId();

                $priceLookup = [];
                foreach ($stillAvailable as $r) $priceLookup[$r['RoomID']] = $r['PricePerNight'];

                $ins = $pdo->prepare("INSERT INTO BookingRoom (BookingID, RoomID, PricePerNight) VALUES (?,?,?)");
                foreach ($chosen as $rid) {
                    $ins->execute([$bookingId, $rid, $priceLookup[$rid] ?? 0]);
                }
                $pdo->commit();
                flash('success', "Booking #$bookingId created successfully.");
                redirect("view.php?id=$bookingId");
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Booking failed: ' . $e->getMessage());
            }
        }
    }
}

$guests = $pdo->query("SELECT * FROM Guest ORDER BY Name")->fetchAll();
$types  = $pdo->query("SELECT * FROM RoomType ORDER BY TypeName")->fetchAll();

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<h3>Step 1: check room availability</h3>
<form method="get" class="form-box mt" style="max-width:700px;display:flex;gap:12px;flex-wrap:wrap;align-items:end">
  <div class="form-group" style="flex:1;min-width:150px"><label>Check-in *</label><input type="date" name="check_in" value="<?= e($checkIn) ?>" required></div>
  <div class="form-group" style="flex:1;min-width:150px"><label>Check-out *</label><input type="date" name="check_out" value="<?= e($checkOut) ?>" required></div>
  <div class="form-group" style="flex:1;min-width:150px"><label>Room type</label>
    <select name="room_type">
      <option value="">Any</option>
      <?php foreach ($types as $t): ?><option value="<?= $t['RoomTypeID'] ?>" <?= $roomType==$t['RoomTypeID']?'selected':'' ?>><?= e($t['TypeName']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><button class="btn" type="submit">Check availability</button></div>
</form>

<?php if ($searchError): ?><div class="alert alert-error mt"><?= e($searchError) ?></div><?php endif; ?>

<?php if ($checkIn && $checkOut && !$searchError): ?>
<h3 class="mt">Step 2: select guest and room(s)</h3>
<form method="post">
  <input type="hidden" name="action" value="create">
  <input type="hidden" name="check_in" value="<?= e($checkIn) ?>">
  <input type="hidden" name="check_out" value="<?= e($checkOut) ?>">
  <div class="form-box mt" style="max-width:400px">
    <div class="form-group">
      <label>Guest *</label>
      <select name="guest_id" required>
        <option value="">-- select guest --</option>
        <?php foreach ($guests as $g): ?><option value="<?= $g['GuestID'] ?>"><?= e($g['Name']) ?> (<?= e($g['Phone']) ?>)</option><?php endforeach; ?>
      </select>
    </div>
    <p style="font-size:13px;color:#5f5e5a">Guest not in the list? <a href="../guests/add.php">Add a new guest</a> first.</p>
  </div>

  <table class="mt">
  <tr><th></th><th>Room #</th><th>Type</th><th>Capacity</th><th>Price/night</th></tr>
  <?php if (empty($rooms)): ?>
    <tr><td colspan="5">No rooms available for these dates.</td></tr>
  <?php endif; ?>
  <?php foreach ($rooms as $r): ?>
  <tr>
    <td><input type="checkbox" name="room_ids[]" value="<?= $r['RoomID'] ?>"></td>
    <td><?= e($r['RoomNumber']) ?></td>
    <td><?= e($r['TypeName']) ?></td>
    <td><?= $r['Capacity'] ?></td>
    <td>৳<?= money($r['PricePerNight']) ?></td>
  </tr>
  <?php endforeach; ?>
  </table>
  <?php if (!empty($rooms)): ?>
    <button class="btn mt" type="submit">Create booking</button>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
