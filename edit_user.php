<?php 
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requireSuperAdmin();

// check id
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = (int)$_GET['id'];
$errors = [];

// Fetch user
$res  = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $res->fetch_assoc();

if (!$user) {
    echo "User not found!";
    exit();
}

// Fetch roles dynamically
$roles = [];
$role_q = $conn->query("SELECT id, role_name FROM roles WHERE deleted_at IS NULL");
while ($row = $role_q->fetch_assoc()) {
    $roles[] = $row;
}

// Initialize form data with current user data
$form_data = [
    'name' => $user['name'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role_id' => $user['role_id'], // changed to role_id
    'password' => '',
    'confirm_password' => ''
];

// update logic
if (isset($_POST['update_user'])) {
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';

    // Prevent role change for self
    if ($user['username'] == $_SESSION['user']) {
        $form_data['role_id'] = $user['role_id'];
    } else {
        $form_data['role_id'] = (int)($_POST['role_id'] ?? 0);
    }

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

    // Check for duplicate username if no errors
    if (empty($errors['username']) && !empty($form_data['username'])) {
        $check_sql = "SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("si", $form_data['username'], $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['username'] = "This username is already taken.";
        }
    }

    // Check for duplicate email if no errors
    if (empty($errors['email']) && !empty($form_data['email'])) {
        $check_sql = "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("si", $form_data['email'], $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['email'] = "This email address is already registered.";
        }
    }

    // Validate password if provided
    if (!empty($form_data['password'])) {
        if (empty($form_data['confirm_password'])) {
            $errors['confirm_password'] = "Please confirm your new password.";
        } elseif ($form_data['password'] !== $form_data['confirm_password']) {
            $errors['password'] = "Passwords do not match.";
        }
    }

    // If no errors, update the user
    if (empty($errors)) {
        if (!empty($form_data['password'])) {
            // Use better hashing in real apps (password_hash)
            $hashed_password = md5($form_data['password']);
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, password=?, role_id=? WHERE id=?");
            $stmt->bind_param(
                "ssssii", 
                $form_data['name'], 
                $form_data['username'], 
                $form_data['email'], 
                $hashed_password, 
                $form_data['role_id'], 
                $id
            );
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, role_id=? WHERE id=?");
            $stmt->bind_param(
                "sssii", 
                $form_data['name'], 
                $form_data['username'], 
                $form_data['email'], 
                $form_data['role_id'], 
                $id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "User updated successfully.";
            header("Location: users.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating user: " . $stmt->error;
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
                        Edit User
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
                            <label class="pms-form-label">New Password</label>
                            <input type="password" name="password"
                                   class="form-control"
                                   placeholder="Leave blank to keep current">
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label class="pms-form-label">Confirm Password</label>
                            <input type="password" name="confirm_password"
                                   class="form-control"
                                   placeholder="Re-enter new password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['confirm_password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="pms-form-label">Role</label>

                            <?php if ($user['username'] == $_SESSION['user']): ?>
                                <!-- 🔒 Self protected -->
                                <?php 
                                // Find role name from roles array
                                $current_role_name = '';
                                foreach ($roles as $r) {
                                    if ($r['id'] == $form_data['role_id']) {
                                        $current_role_name = $r['role_name'];
                                        break;
                                    }
                                }
                                ?>
                                <input type="text"
                                       class="form-control"
                                       value="<?= htmlspecialchars(ucfirst($current_role_name)) ?>"
                                       disabled>
                                <input type="hidden" name="role_id" value="<?= $form_data['role_id'] ?>">
                                <small class="text-muted d-block mt-1">You cannot change your own role</small>
                            <?php else: ?>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"
                                            <?= $form_data['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pms-panel-footer text-end">
                        <a href="users.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="update_user" class="pms-btn-dark">
                            <i class="bi bi-check-lg me-1"></i>Update User
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>