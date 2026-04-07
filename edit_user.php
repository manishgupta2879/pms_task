<?php 
include "includes/config.php";

// check role
if ($_SESSION['role'] != 'super-admin') {
    die("Access denied");
}

// check id
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = (int)$_GET['id'];

// fetch user
$res  = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $res->fetch_assoc();

if (!$user) {
    echo "User not found!";
    exit();
}

// update logic
if (isset($_POST['update_user'])) {

    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $name     = $conn->real_escape_string($_POST['name']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 🔒 Prevent role change for self
    if ($user['username'] == $_SESSION['user']) {
        $role = $user['role'];
    } else {
        $role = $_POST['role'];
    }

    // password logic
    if (!empty($password)) {

        if ($password !== $confirm) {
            $error = "Password and Confirm Password do not match!";
        } else {
            $hashed = md5($password); // ⚠️ use password_hash in production

            $sql = "UPDATE users 
                    SET name='$name', username='$username', email='$email', password='$hashed', role='$role' 
                    WHERE id=$id";
        }

    } else {

        $sql = "UPDATE users 
                SET name='$name', username='$username', email='$email', role='$role' 
                WHERE id=$id";
    }

    // execute if no error
    if (!isset($error)) {
        if ($conn->query($sql)) {
            $_SESSION['success'] = "User updated successfully.";
            header("Location: users.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <!-- Header -->
                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        Edit User
                        <a href="users.php" class="btn btn-outline-secondary btn-sm">Back to Users</a>
                    </div>

                    <!-- Body -->
                    <div class="pms-panel-body">
                        <div class="row g-3">

                            <?php if (!empty($error)): ?>
                                <div class="col-12">
                                    <div class="alert alert-danger"><?= $error ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Name
                                </label>
                                <input type="text" name="name"
                                       class="form-control"
                                       value="<?= htmlspecialchars($user['name']) ?>"
                                       required autofocus>
                                <div class="invalid-feedback">Name is required</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Username
                                </label>
                                <input type="text" name="username"
                                       class="form-control"
                                       value="<?= htmlspecialchars($user['username']) ?>"
                                       required autofocus>
                                <div class="invalid-feedback">Username is required</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Email
                                </label>
                                <input type="email" name="email"
                                       class="form-control"
                                       value="<?= htmlspecialchars($user['email']) ?>"
                                       required>
                                <div class="invalid-feedback">Valid email required</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">New Password</label>
                                <input type="password" name="password"
                                       class="form-control"
                                       placeholder="Leave blank to keep current password">
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Confirm Password</label>
                                <input type="password" name="confirm_password"
                                       class="form-control"
                                       placeholder="Re-enter new password">
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Role</label>

                                <?php if ($user['username'] == $_SESSION['user']): ?>
                                    <!-- 🔒 Self protected -->
                                    <input type="text"
                                           class="form-control"
                                           value="<?= $user['role'] ?>"
                                           disabled>
                                    <input type="hidden" name="role" value="<?= $user['role'] ?>">
                                    <small class="text-muted">You cannot change your own role</small>
                                <?php else: ?>
                                    <select name="role" class="form-select">
                                        <option value="staff" <?= $user['role']=='staff'?'selected':'' ?>>Staff</option>
                                        <option value="superadmin" <?= $user['role']=='superadmin'?'selected':'' ?>>Super Admin</option>
                                    </select>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pms-panel-footer text-end">
                        <a href="users.php" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" name="update_user" class="pms-btn-dark">
                            <i class="bi bi-check-circle"></i> Update User
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<!-- Validation Script -->
<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})();
</script>

<?php include "includes/footer.php"; ?>