<?php
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Include RBAC system
require_once(__DIR__ . '/rbac.php');

// Fetch current logged-in user details
require_once(__DIR__ . '/config.php');
$user_id = $_SESSION['user_id'] ?? 0;
$profile_pic = '';
$user_name = $_SESSION['user'] ?? 'User';

if ($user_id > 0) {
    $user_res = $conn->query("SELECT name, profile_pic FROM users WHERE id=$user_id LIMIT 1");
    if ($user_res && $user_row = $user_res->fetch_assoc()) {
        $user_name = $user_row['name'] ?? $_SESSION['user'];
        $profile_pic = $user_row['profile_pic'] ?? '';
    }
}

// Generate initials for avatar fallback
$initials = '';
$parts = explode(' ', $user_name);
foreach ($parts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}

// Generate consistent avatar color based on user_id
$avatarColors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#06b6d4'];
$avatarColor = $avatarColors[crc32($user_id) % count($avatarColors)];

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>

<head>
    <title>My System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Custom CSS -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
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
                            <a class="nav-link <?= ($current_page == 'my_task.php') ? 'active' : '' ?>" href="my_task.php">
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
                        <!-- <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>" href="reports.php">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>
                        </li> -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= ($current_page == 'reports.php') ? 'active' : '' ?>"
                                href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>

                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="weakly-report.php">Weekly Report</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="daily-report.php">Daily Report</a>
                                </li>
                                <!-- <li>
                                    <a class="dropdown-item" href="user_report.php">User Report</a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="custom_report.php">Custom Report</a>
                                </li> -->
                            </ul>
                        </li>
                    <?php endif; ?>

                    <?php if (hasPermission('settings')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : '' ?>"
                                href="settings.php">
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
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'scheduling.php') ? 'active' : '' ?>"
                            href="scheduling.php">
                            <i class="bi bi-people"></i> Scheduling
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown">
                        <!-- <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> -->
                            <div class="d-flex align-items-center me-2">
                                <div>
                                    <?php if (!empty($profile_pic) && file_exists($profile_pic)): ?>
                                        <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 2px solid rgba(255,255,255,0.3);">
                                    <?php else: ?>
                                        <div style="
                                    width: 36px;
                                    height: 36px;
                                    border-radius: 50%;
                                    background-color: <?= $avatarColor ?>;
                                    color: white;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: 700;
                                    font-size: 13px;
                                    margin-right: 10px;
                                    border: 2px solid rgba(255,255,255,0.3);
                                ">
                                            <?= htmlspecialchars($initials) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-white text-capitalize  text-nowrap"><?= htmlspecialchars($user_name) ?></span>
                                    <span class="text-white text-nowrap" style="font-size: 12px;"><?= getRoleLabel($_SESSION['role_slug'] ?? 'staff') ?></span>
                                </div>
                                <a class="btn btn-danger btn-sm ms-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                            </div>

                        <!-- </a> -->
                        <!-- <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul> -->
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