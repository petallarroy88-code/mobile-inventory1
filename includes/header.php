<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-mobile-alt"></i>
            <span><?= APP_NAME ?></span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>index.php" class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <div class="nav-group-label">Inventory</div>
        <a href="<?= BASE_URL ?>modules/products/index.php" class="nav-item <?= ($activePage ?? '') === 'products' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i><span>Products</span>
        </a>
        <a href="<?= BASE_URL ?>modules/categories/index.php" class="nav-item <?= ($activePage ?? '') === 'categories' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i><span>Categories</span>
        </a>
        <a href="<?= BASE_URL ?>modules/suppliers/index.php" class="nav-item <?= ($activePage ?? '') === 'suppliers' ? 'active' : '' ?>">
            <i class="fas fa-truck"></i><span>Suppliers</span>
        </a>
        <div class="nav-group-label">Operations</div>
        <a href="<?= BASE_URL ?>modules/orders/index.php" class="nav-item <?= ($activePage ?? '') === 'orders' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i><span>Purchase Orders</span>
        </a>
        <a href="<?= BASE_URL ?>modules/reports/index.php" class="nav-item <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i><span>Reports</span>
        </a>
        <?php if (currentUser()['role'] === 'admin'): ?>
        <div class="nav-group-label">Admin</div>
        <a href="<?= BASE_URL ?>modules/users/index.php" class="nav-item <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>Users</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr(currentUser()['name'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= sanitize(currentUser()['name']) ?></div>
                <div class="user-role"><?= ucfirst(currentUser()['role']) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content" id="mainContent">
    <div class="topbar">
        <button class="mobile-toggle" id="mobileToggle"><i class="fas fa-bars"></i></button>
        <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
        <div class="topbar-right">
            <span class="date-display"><?= date('D, M d Y') ?></span>
        </div>
    </div>

    <!-- Flash Message -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'times-circle' : 'info-circle') ?>"></i>
        <?= sanitize($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <div class="content-body">
