<?php

include "includes/config.php";
include "includes/rbac.php";

if (isset($_POST['login'])) {
    // Using trim to avoid accidental spaces in input
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));
    
    // Query user with role information
    $res = $conn->query("SELECT u.id, u.username, u.role_id, r.slug, r.role_name , 
                         u.name
                         FROM users u 
                         LEFT JOIN roles r ON u.role_id = r.id 
                         WHERE u.username='$username' AND u.password='$password' AND u.deleted_at IS NULL");

    if ($res->num_rows == 1) {
        $row = $res->fetch_assoc();
        
        // Store secure session data
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user'] = $row['username'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role_slug'] = $row['slug'] ?? 'staff'; // Fallback to 'staff' if no role assigned
        $_SESSION['role_name'] = $row['role_name'] ?? 'Staff';
        $_SESSION['role_id'] = $row['role_id'];
        
        // Legacy support (deprecated but kept for compatibility)
        $_SESSION['role'] = $row['slug'] ?? 'staff';
        
        // Ensure redirect works by calling exit immediately after header
        header("Location: dashboard.php");
        exit();
    }
    else {
        $err = "Invalid Login";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Project Management System</title>
    <link rel="stylesheet" href="assets/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <!-- Left Side: Image Content -->
        <div class="login-left">
            <div class="overlay"></div>
            <div class="content">
                <h1>Manage Your Orders Efficiently</h1>
                <p>Track every order, optimize every task, and stay on top of your repair projects.</p>
                <div class="features">
                    <div class="feature-item">
                        <span class="dot"></span>
                        Real-time Task Tracking
                    </div>
                    <div class="feature-item">
                        <span class="dot"></span>
                        Comprehensive Order History
                    </div>
                    <div class="feature-item">
                        <span class="dot"></span>
                        Resource Allocation
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-right">
            <div class="mobile-logo">
                <h2>PMS</h2>
            </div>
            <div class="login-form-wrapper">
                
                <?php if (isset($err)): ?>
                    <div class="error-msg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php echo $err; ?>
                    </div>
                <?php
endif; ?>

                <form method="post" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-container">
                            <input name="username" id="username" type="text" placeholder="Enter your username" required autocomplete="off">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-container">
                            <input name="password" id="password" type="password" placeholder="Enter your password" required>
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox"> <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn-primary">
                        Login Now
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>