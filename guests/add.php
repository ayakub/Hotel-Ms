<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['Admin','Receptionist']);
$pageTitle = 'Add guest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name === '') {
        flash('error', 'Name is required.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO Guest (Name, Phone, Email, Address, NID) VALUES (?,?,?,?,?)");
        $stmt->execute([$name, $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['nid']]);
        flash('success', 'Guest added successfully.');
        redirect('list.php');
    }
}
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/sidebar.php';
?>
<form class="form-box" method="post">
  <div class="form-group"><label>Name *</label><input type="text" name="name" required></div>
  <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
  <div class="form-group"><label>Email</label><input type="email" name="email"></div>
  <div class="form-group"><label>Address</label><input type="text" name="address"></div>
  <div class="form-group"><label>NID</label><input type="text" name="nid"></div>
  <button class="btn" type="submit">Save guest</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
