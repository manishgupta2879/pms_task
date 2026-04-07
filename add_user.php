<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requireSuperAdmin();

$errors = [];
$form_data = [
    'name' => '',
    'username' => '',
    'email' => '',
    'role' => 'staff'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['role'] = $_POST['role'] ?? 'staff';
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($form_data['name'])) {
        $errors['name'] = "Name is required.";
    }

    if (empty($form_data['username'])) {
        $errors['username'] = "Username is required.";
    }

    if (empty($form_data['email'])) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    // Check for duplicate username if no errors
    if (empty($errors['username']) && !empty($form_data['username'])) {
        $check_sql = "SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND deleted_at IS NULL";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $form_data['username']);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['username'] = "This username already exists.";
        }
    }

    // Check for duplicate email if no errors
    if (empty($errors['email']) && !empty($form_data['email'])) {
        $check_sql = "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND deleted_at IS NULL";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $form_data['email']);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['email'] = "This email address is already registered.";
        }
    }

    // If no errors, insert the user
    if (empty($errors)) {
        $hashed_password = md5($password);
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $form_data['name'], $form_data['username'], $form_data['email'], $hashed_password, $form_data['role']);

        if ($stmt->execute()) {
            $_SESSION['success'] = "User added successfully.";
            header("Location: users.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating user: " . $stmt->error;
        }
    }
    
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST">
                <div class="pms-panel">

                    <!-- Header -->
                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        Create User
                        <a href="users.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <!-- Body -->
                    <div class="pms-panel-body">
                        <div class="row g-3">
                        
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="pms-form-label">
                                <span class="text-danger">*</span> Name
                            </label>
                            <input type="text" name="name"
                                   class="form-control"
                                   placeholder="Enter full name"
                                   value="<?= htmlspecialchars($form_data['name']) ?>"
                                    autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="pms-form-label">
                                <span class="text-danger">*</span> Username
                            </label>
                            <input type="text" name="username"
                                   class="form-control"
                                   placeholder="Enter username"
                                   value="<?= htmlspecialchars($form_data['username']) ?>"
                                   >
                            <?php if (isset($errors['username'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['username'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="pms-form-label">
                                <span class="text-danger">*</span> Email
                            </label>
                            <input type="email" name="email"
                                   class="form-control"
                                   placeholder="Enter email address"
                                   value="<?= htmlspecialchars($form_data['email']) ?>"
                                   >
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="pms-form-label">
                                <span class="text-danger">*</span> Password
                            </label>
                            <input type="password" name="password"
                                   class="form-control"
                                   placeholder="Enter password"
                                   >
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="pms-form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="staff" <?= $form_data['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="superadmin" <?= $form_data['role'] == 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                        </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pms-panel-footer text-end">
                        <a href="users.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save_user" class="pms-btn-dark">
                            <i class="bi bi-check-lg me-1"></i>Save User
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>