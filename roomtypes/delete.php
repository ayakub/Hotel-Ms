<?php
require_once __DIR__ . '/../includes/init.php';
require_role('Admin');
$id = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("DELETE FROM RoomType WHERE RoomTypeID=?");
    $stmt->execute([$id]);
    flash('success', 'Room type deleted.');
} catch (PDOException $e) {
    flash('error', 'Cannot delete: rooms exist under this type.');
}
redirect('list.php');
