<?php
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Include RBAC system
require_once(__DIR__ . '/rbac.php');

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>

<head>
    <title>My System</title>

    <!-- ✅ Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">

            <a class="navbar-brand fw-bold" href="dashboard.php">
                🚀 MySystem
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto">

                    <?php if (hasPermission('orders')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'orders.php') ? 'active' : '' ?>" href="orders.php">
                                <i class="bi bi-bag-check"></i> Orders
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isSuperAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'task_library.php') ? 'active' : '' ?>"
                                href="task_library.php">
                                <i class="bi bi-list-task"></i> Tasks
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'my_task.php') ? 'active' : '' ?>"
                                href="my_task.php">
                                <i class="bi bi-list-check"></i> My Tasks
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('resources')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'resources.php') ? 'active' : '' ?>"
                                href="resources.php">
                                <i class="bi bi-box"></i> Resources
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('roles')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'roles.php') ? 'active' : '' ?>" href="roles.php">
                                <i class="bi bi-shield-lock"></i> Roles
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('reports')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>" href="reports.php">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('settings')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : '' ?>" href="settings.php">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('users')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'users.php') ? 'active' : '' ?>" href="users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>

                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3 text-white">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']) ?>
                        <span class="badge bg-info"><?= getRoleLabel($_SESSION['role_slug'] ?? 'staff') ?></span>
                    </li>

                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div class="container-fluid mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>