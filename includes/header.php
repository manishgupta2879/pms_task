<?php
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
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

    <!-- 🔷 NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">

            <!-- LOGO -->
            <a class="navbar-brand fw-bold" href="dashboard.php">
                🚀 MySystem
            </a>

            <!-- TOGGLE (Mobile) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- MENU -->
            <div class="collapse navbar-collapse" id="navbarNav">

                <!-- LEFT MENU -->
                <ul class="navbar-nav me-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="tasks.php">
                            <i class="bi bi-list-task"></i> Tasks
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="resources.php">
                            <i class="bi bi-box"></i> Resources
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="roles.php">
                            <i class="bi bi-shield-lock"></i> Roles
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>


                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>

                    <!-- 👇 USERS (Superadmin only) -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'superadmin') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                    <?php } ?>

                </ul>

                <!-- RIGHT SIDE -->
                <ul class="navbar-nav align-items-center">

                    <!-- USER NAME -->
                    <li class="nav-item me-3 text-white">
                        <i class="bi bi-person-circle"></i>
                        <?= $_SESSION['user'] ?? '' ?>
                    </li>

                    <!-- LOGOUT -->
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
    <div class="container-fluid mt-3">

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