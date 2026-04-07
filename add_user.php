<?php
include "includes/config.php";

if (isset($_POST['save_user'])) {
    
    $username = $conn->real_escape_string($_POST['username']);
    $email    = $conn->real_escape_string($_POST['email']);
    $name     = $conn->real_escape_string($_POST['name']);
    $password = md5($_POST['password']);
    $role     = $_POST['role'];

    $username_error = '';
    $email_error = '';

    $checkUsername = "SELECT id FROM users WHERE username = '$username'";
    $resUser = $conn->query($checkUsername);

    if ($resUser && $resUser->num_rows > 0) {
        $username_error = "Username already taken.";
    }
    $checkEmail = "SELECT id FROM users WHERE email = '$email'";
    $resEmail = $conn->query($checkEmail);
    if ($resEmail && $resEmail->num_rows > 0) {
        $email_error = "Email already registered.";
    }

    if (empty($username_error) && empty($email_error)) {
        $sql = "INSERT INTO users(name,username,email,password,role)
            VALUES('$name','$username','$email','$password','$role')";

        if ($conn->query($sql)) {
            $_SESSION['success'] = ("User added successfully.");
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
                        Create User
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
                                       placeholder="Enter name"
                                       value="<?= htmlspecialchars($name ?? '') ?>"
                                       required autofocus>
                                <div class="invalid-feedback">Name is required</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Username
                                </label>
                                <input type="text" name="username"
                                       class="form-control <?= !empty($username_error) ? 'is-invalid' : '' ?>"
                                       placeholder="Enter username"
                                       value="<?= htmlspecialchars($username ?? '') ?>"
                                       required autofocus>
                                       <?php if (!empty($username_error)): ?>
                                            <div class="invalid-feedback"><?= $username_error ?></div>
                                        <?php else: ?>
                                            <div class="invalid-feedback">Username is required</div>
                                        <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Email
                                </label>
                                <input type="email" name="email"
                                       class="form-control <?= !empty($email_error) ? 'is-invalid' : '' ?>"
                                       placeholder="Enter email"
                                       value="<?= htmlspecialchars($email ?? '') ?>"
                                       required>
                                <?php if (!empty($email_error)): ?>
                                    <div class="invalid-feedback"><?= $email_error ?></div>
                                <?php else: ?>
                                    <div class="invalid-feedback">Valid email required</div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Password
                                </label>
                                <input type="password" name="password"
                                       class="form-control"
                                       placeholder="Enter password"
                                       required>
                                <div class="invalid-feedback">Password is required</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="staff">Staff</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pms-panel-footer text-end">
                        <a href="users.php" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" name="save_user" class="pms-btn-dark">
                            <i class="bi bi-check-circle"></i> Save User
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