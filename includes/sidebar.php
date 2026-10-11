<?php $role = $_SESSION['user']['Role'] ?? ''; $cur = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
  <h2>Hotel MS</h2>
  <div class="role-tag">Logged in as <?= e($_SESSION['user']['Username'] ?? '') ?> (<?= e($role) ?>)</div>
  <a href="<?= base_url('dashboard.php') ?>" class="<?= $cur=='dashboard.php'?'active':'' ?>">Dashboard</a>

  <?php if (in_array($role, ['Admin','Receptionist'])): ?>
    <a href="<?= base_url('guests/list.php') ?>">Guests</a>
    <a href="<?= base_url('bookings/list.php') ?>">Bookings</a>
    <a href="<?= base_url('bookings/add.php') ?>">New booking</a>
    <a href="<?= base_url('payments/list.php') ?>">Payments</a>
    <a href="<?= base_url('services/list.php') ?>">Services</a>
  <?php endif; ?>

  <?php if (in_array($role, ['Admin','Housekeeping'])): ?>
    <a href="<?= base_url('rooms/list.php') ?>">Rooms</a>
  <?php endif; ?>

  <?php if ($role === 'Admin'): ?>
    <a href="<?= base_url('roomtypes/list.php') ?>">Room types</a>
    <a href="<?= base_url('staff/list.php') ?>">Staff</a>
    <a href="<?= base_url('users/list.php') ?>">User accounts</a>
    <a href="<?= base_url('reports/index.php') ?>">Reports</a>
  <?php endif; ?>
</div>
<div class="main">
  <div class="topbar">
    <strong><?= isset($pageTitle) ? e($pageTitle) : '' ?></strong>
    <a class="logout" href="<?= base_url('auth/logout.php') ?>">Logout</a>
  </div>
  <div class="content">
  <?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('error')): ?><div class="alert alert-error"><?= e($m) ?></div><?php endif; ?>
