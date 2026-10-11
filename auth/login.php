<?php
require_once __DIR__ . '/../includes/init.php';
if (is_logged_in()) redirect('../dashboard.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['PasswordHash'])) {
        unset($user['PasswordHash']);
        $_SESSION['user'] = $user;
        redirect('../dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Login - Hotel Reservation System</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <form class="login-box" method="post">
    <h1>Hotel Reservation System</h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" required autofocus>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn" type="submit" style="width:100%">Log in</button>
  </form>
</div>
</body>
</html>
