<?php
$pageTitle = $pageTitle ?? 'Sari-Sari Store';
$currentPage = basename($_SERVER['PHP_SELF']);
$flashMessage = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | TindahanHub</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script defer src="assets/js/app.js"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark">T</span><span><strong>TindahanHub</strong><small>Store management</small></span></a>
    <nav class="nav-menu">
      <span class="nav-label">WORKSPACE</span>
      <a class="<?= $currentPage==='index.php'?'active':'' ?>" href="index.php"><span>▦</span> Dashboard</a>
      <a class="<?= $currentPage==='products.php'?'active':'' ?>" href="products.php"><span>▤</span> Products & Inventory</a>
      <a class="<?= $currentPage==='pos.php'?'active':'' ?>" href="pos.php"><span>▣</span> Point of Sale</a>
      <a class="<?= $currentPage==='sales.php'?'active':'' ?>" href="sales.php"><span>↗</span> Sales History</a>
      <a class="<?= $currentPage==='purchases.php'?'active':'' ?>" href="purchases.php"><span>⇩</span> Stock Purchases</a>
      <a class="<?= $currentPage==='expenses.php'?'active':'' ?>" href="expenses.php"><span>₱</span> Expenses</a>
      <a class="<?= $currentPage==='reports.php'?'active':'' ?>" href="reports.php"><span>▥</span> Reports</a>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <span class="nav-label">ADMINISTRATION</span>
        <a class="<?= $currentPage==='users.php'?'active':'' ?>" href="users.php"><span>♙</span> User Accounts</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
      <div class="profile-mini"><span class="avatar"><?= e(strtoupper(substr($_SESSION['name'] ?? 'U',0,1))) ?></span><span><strong><?= e($_SESSION['name'] ?? 'User') ?></strong><small><?= e(ucfirst($_SESSION['role'] ?? 'cashier')) ?></small></span></div>
      <a class="logout-link" href="logout.php">↪ Sign out</a>
    </div>
  </aside>
  <main class="main-area">
    <header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">☰</button><div><span class="eyebrow">STORE WORKSPACE</span><h1><?= e($pageTitle) ?></h1></div><div class="topbar-right"><span class="status-dot"></span><span>System online</span><span class="top-avatar"><?= e(strtoupper(substr($_SESSION['name'] ?? 'U',0,1))) ?></span></div></header>
    <section class="page-content">
      <?php if ($flashMessage): ?><div class="alert <?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div><?php endif; ?>
