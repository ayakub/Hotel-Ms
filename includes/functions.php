<?php
// ---------------------------------------------------------
// General helpers used across the whole project
// ---------------------------------------------------------

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect(base_url('auth/login.php'));
    }
}

function require_role($roles) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user']['Role'], $roles)) {
        die('<div style="padding:40px;font-family:sans-serif">
            <h2>403 - Access denied</h2>
            <p>Your role does not have permission to view this page.</p>
            <a href="' . base_url('dashboard.php') . '">Back to dashboard</a></div>');
    }
}

function base_url($path = '') {
    // Adjust APP_ROOT if the project is deployed in a sub-folder
    return '/hotel-reservation/' . ltrim($path, '/');
}

function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $out = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $out;
    }
    return null;
}

function money($n) {
    return number_format((float)$n, 2);
}

// Nights between two dates (Y-m-d strings)
function nights_between($checkIn, $checkOut) {
    $d1 = new DateTime($checkIn);
    $d2 = new DateTime($checkOut);
    return max(1, $d1->diff($d2)->days);
}

// Fetch rooms that are free for the given date range (and optional type filter).
// A room is unavailable if it belongs to an active (Pending/Confirmed/Checked-In)
// booking whose date range overlaps the requested range.
function available_rooms(PDO $pdo, $checkIn, $checkOut, $roomTypeId = null) {
    $sql = "SELECT r.RoomID, r.RoomNumber, r.FloorNo, r.Status, rt.RoomTypeID, rt.TypeName, rt.PricePerNight, rt.Capacity
            FROM Room r
            JOIN RoomType rt ON rt.RoomTypeID = r.RoomTypeID
            WHERE r.Status != 'Maintenance'
            AND r.RoomID NOT IN (
                SELECT br.RoomID FROM BookingRoom br
                JOIN Booking b ON b.BookingID = br.BookingID
                WHERE b.Status IN ('Pending','Confirmed','Checked-In')
                AND b.CheckInDate < :checkOut AND b.CheckOutDate > :checkIn
            )";
    $params = [':checkIn' => $checkIn, ':checkOut' => $checkOut];
    if ($roomTypeId) {
        $sql .= " AND r.RoomTypeID = :rtid";
        $params[':rtid'] = $roomTypeId;
    }
    $sql .= " ORDER BY rt.TypeName, r.RoomNumber";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Financial summary for one booking: room charge + service charge + paid + due
function booking_bill(PDO $pdo, $bookingId) {
    $stmt = $pdo->prepare("SELECT CheckInDate, CheckOutDate FROM Booking WHERE BookingID=?");
    $stmt->execute([$bookingId]);
    $b = $stmt->fetch();
    if (!$b) return null;
    $nights = nights_between($b['CheckInDate'], $b['CheckOutDate']);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(PricePerNight),0) AS s FROM BookingRoom WHERE BookingID=?");
    $stmt->execute([$bookingId]);
    $roomChargePerNight = (float)$stmt->fetch()['s'];
    $roomCharge = $roomChargePerNight * $nights;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(bs.Quantity * s.Price),0) AS s
                            FROM BookingService bs JOIN Service s ON s.ServiceID = bs.ServiceID
                            WHERE bs.BookingID=?");
    $stmt->execute([$bookingId]);
    $serviceCharge = (float)$stmt->fetch()['s'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(Amount),0) AS s FROM Payment WHERE BookingID=? AND PaymentStatus != 'Refunded'");
    $stmt->execute([$bookingId]);
    $paid = (float)$stmt->fetch()['s'];

    $total = $roomCharge + $serviceCharge;
    return [
        'nights' => $nights,
        'room_charge' => $roomCharge,
        'service_charge' => $serviceCharge,
        'total' => $total,
        'paid' => $paid,
        'due' => max(0, $total - $paid),
    ];
}
