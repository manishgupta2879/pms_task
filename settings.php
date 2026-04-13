<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

$user_id = $_SESSION['user_id'];
$errors = [];
$form_data = [];

// Fetch current user data
$user_res = $conn->query("SELECT * FROM users WHERE id=$user_id");
$current_user = $user_res->fetch_assoc();

// Initialize form data with current values
$form_data = [
    'name' => $current_user['name'] ?? '',
    'email' => $current_user['email'] ?? '',
    'profile_pic' => $current_user['profile_pic'] ?? '',
    'pagination_limit' => $current_user['pagination_limit'] ?? 10,
];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $form_data['pagination_limit'] = isset($_POST['pagination_limit']) ? (int) $_POST['pagination_limit'] : 10;

    // Validation
    if (empty($form_data['name'])) {
        $errors['name'] = "Name is required.";
    }

    if (empty($form_data['email'])) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    // Check for duplicate email (excluding current user)
    if (empty($errors['email']) && !empty($form_data['email'])) {
        $check_sql = "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND deleted_at IS NULL AND id != ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("si", $form_data['email'], $user_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['email'] = "This email address is already in use.";
        }
    }

    // Validate password if provided
    if (!empty($password)) {
        if (empty($confirm_password)) {
            $errors['confirm_password'] = "Please confirm your new password.";
        } elseif ($password !== $confirm_password) {
            $errors['password'] = "Passwords do not match.";
        }
    }
    if ($form_data['pagination_limit'] <= 0) {
        $errors['pagination_limit'] = "Pagination limit must be greater than 0.";
    }
    // Handle profile picture upload
    if (!empty($_FILES['profile_pic']['name'])) {
        $file = $_FILES['profile_pic'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            $errors['profile_pic'] = "Only image files (JPG, PNG, GIF, WebP) are allowed.";
        } elseif ($file['size'] > $max_size) {
            $errors['profile_pic'] = "File size must not exceed 5MB.";
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['profile_pic'] = "Error uploading file. Please try again.";
        } else {
            // Delete old profile picture if it exists
            if (!empty($form_data['profile_pic']) && file_exists($form_data['profile_pic'])) {
                unlink($form_data['profile_pic']);
            }

            // Generate unique filename
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $form_data['profile_pic'] = $file_path;
            } else {
                $errors['profile_pic'] = "Failed to save image. Please try again.";
            }
        }
    }

    // If no errors, update the user
    if (empty($errors)) {
        if (!empty($password)) {
            $hashed_password = md5($password);
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, profile_pic=?, pagination_limit=? WHERE id=?");
            $stmt->bind_param("ssssii", $form_data['name'], $form_data['email'], $hashed_password, $form_data['profile_pic'], $form_data['pagination_limit'], $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, profile_pic=?, pagination_limit=? WHERE id=?");
            $stmt->bind_param("sssii", $form_data['name'], $form_data['email'], $form_data['profile_pic'], $form_data['pagination_limit'], $user_id);
        }

        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user'] = $form_data['name'];
            $_SESSION['profile_pic'] = $form_data['profile_pic'];
            $_SESSION['pagination_limit'] = $form_data['pagination_limit'];
            $_SESSION['success'] = "Profile updated successfully.";
            header("Location: settings.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating profile: " . $stmt->error;
        }
    }
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-8">
            <form method="POST" enctype="multipart/form-data">
                <div class="pms-panel">

                    <!-- Header -->
                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <h5>Settings</h5>
                    </div>

                    <!-- Body -->
                    <div class="pms-panel-body pt-0">
                        <h6 class="my-2 fw-bold text-muted bg-body-secondary px-2 py-1">Profile</h6>
                        <!-- Profile Picture Section -->
                        <div class="row align-items-center">
                            <div class="col-1 text-center mb-3 mb-md-0">
                                <div
                                    style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background: #f1f5f9; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                    <?php if (!empty($form_data['profile_pic']) && file_exists($form_data['profile_pic'])): ?>
                                        <img src="<?= htmlspecialchars($form_data['profile_pic']) ?>" alt="Profile"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle" style="font-size: 80px; color: #cbd5e1;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-11">
                                <div class="mb-3">
                                    <label class="pms-form-label">Upload New Picture</label>
                                    <input type="file" name="profile_pic" class="form-control"
                                        accept="image/jpeg,image/png,image/gif,image/webp">
                                    <?php if (isset($errors['profile_pic'])): ?>
                                        <div class="text-danger small mt-2">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?= $errors['profile_pic'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Name -->
                            <div class="col-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Full Name
                                </label>
                                <input type="text" name="name" class="form-control" placeholder="Enter your full name"
                                    value="<?= htmlspecialchars($form_data['name']) ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= $errors['name'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="col-6">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Email Address
                                </label>
                                <input type="email" name="email" class="form-control"
                                    placeholder="Enter your email address"
                                    value="<?= htmlspecialchars($form_data['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= $errors['email'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Divider -->
                        <!-- <hr class="my-3"> -->

                        <!-- Password Section -->
                        <h6 class="mt-3 my-2 fw-bold text-muted bg-body-secondary px-2 py-1">Change Password</h6>

                        <div class="row">
                            <!-- New Password -->
                            <div class="col-6">
                                <label class="pms-form-label">New Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Leave blank to keep current password">
                                <?php if (isset($errors['password']) && $errors['password'] !== "Passwords do not match."): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= $errors['password'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password -->
                            <div class="col-6">
                                <label class="pms-form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control"
                                    placeholder="Re-enter your new password">
                                <?php if (isset($errors['password']) && $errors['password'] === "Passwords do not match."): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= $errors['password'] ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="text-danger small mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= $errors['confirm_password'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 class="mt-3 my-2 fw-bold text-muted bg-body-secondary px-2 py-1">Config</h6>
                        <!-- Pagination Limit -->
                        <div class="mb-3">
                            <label class="pms-form-label">
                                Pagination Limit
                            </label>
                            <input type="number" name="pagination_limit" class="form-control"
                                value="<?= htmlspecialchars($form_data['pagination_limit']) ?>" min="1" step="1"
                                placeholder="Enter pagination limit">

                            <?php if (isset($errors['pagination_limit'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['pagination_limit'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="pms-panel-footer text-end">
                        <a href="dashboard.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="update_profile" class="pms-btn-dark">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- Info Sidebar -->
        <div class="col-md-4">
            <div class="pms-panel">
                <div class="pms-panel-header">
                    <h5>Account Information</h5>
                </div>
                <div class="pms-panel-body">
                    <div class="mb-3">
                        <label class="text-muted small">Username</label>
                        <p class="fw-bold"><?= htmlspecialchars($current_user['username'] ?? '') ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Role</label>
                        <p class="fw-bold">
                            <span class="badge bg-primary">
                                <?= ucfirst(str_replace('-', ' ', $_SESSION['role_slug'] ?? 'staff')) ?>
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Account Created</label>
                        <p class="fw-bold">
                            <?= !empty($current_user['created_at']) ? date('M d, Y', strtotime($current_user['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>