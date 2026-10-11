<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM Guest WHERE GuestID=?");
    $stmt->execute([$id]);
    flash('success', 'Guest deleted.');
} catch (PDOException $e) {
    flash('error', 'Cannot delete: this guest has existing bookings.');
}
redirect('list.php');
