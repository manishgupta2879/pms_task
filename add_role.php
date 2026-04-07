<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requireSuperAdmin();

$id = $_GET['id'] ?? 0;
$errors = [];
$form_data = [
    'role_name' => '',
    'slug' => '',
    'status' => 'Active'
];

// Load existing role if editing
if ($id) {
    $q = $conn->query("SELECT * FROM roles WHERE id=" . (int)$id);
    if ($row = $q->fetch_assoc()) {
        $form_data['role_name'] = $row['role_name'];
        $form_data['slug'] = $row['slug'];
        $form_data['status'] = $row['status'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_role'])) {
    $form_data['role_name'] = trim($_POST['role_name'] ?? '');
    $form_data['slug'] = trim($_POST['slug'] ?? '');
    $form_data['status'] = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($form_data['role_name'])) {
        $errors['role_name'] = "Role name is required.";
    }

    // Check for duplicate role name if no errors
    if (empty($errors) && !empty($form_data['role_name'])) {
        $check_sql = "SELECT id FROM roles WHERE LOWER(role_name) = LOWER(?) AND deleted_at IS NULL";
        if ($id) {
            $check_sql .= " AND id != ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("si", $form_data['role_name'], $id);
        } else {
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("s", $form_data['role_name']);
        }
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['role_name'] = "This role name already exists. Please use a different name.";
        }
    }

    // If no errors, save the role
    if (empty($errors)) {
        if ($id) {
            // Update existing role
            $stmt = $conn->prepare("UPDATE roles SET role_name=?, slug=?, status=? WHERE id=?");
            $stmt->bind_param("sssi", $form_data['role_name'], $form_data['slug'], $form_data['status'], $id);
        } else {
            // Create new role
            $stmt = $conn->prepare("INSERT INTO roles (role_name, slug, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $form_data['role_name'], $form_data['slug'], $form_data['status']);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = ($id ? "Role updated successfully." : "Role added successfully.");
            header("Location: roles.php");
            exit();
        } else {
            $_SESSION['error'] = "Error saving role: " . $stmt->error;
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

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <?= $id ? 'Edit Role' : 'Add New Role' ?>
                        <a href="roles.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row">
                            <!-- Role Name -->
                            <div class="col-md-4 mb-3">
                                <label class="pms-form-label"><span class="text-danger">*</span> Role Name</label>
                                <input type="text" name="role_name" id="role_name" class="form-control"
                                    placeholder="Enter role name..."
                                    value="<?= htmlspecialchars($form_data['role_name']) ?>"
                                     autofocus autocomplete="off">
                                <?php if (isset($errors['role_name'])): ?>
                                    <div class="text-danger small mt-1">
                                        <?= $errors['role_name'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Role Slug -->
                            <div class="col-md-4 mb-3">
                                <label class="pms-form-label">Role Slug</label>
                                <input type="text" name="slug" id="role_slug" class="form-control text-lowercase bg-light"
                                    placeholder="role-slug"
                                    value="<?= htmlspecialchars($form_data['slug']) ?>"
                                    readonly>
                                <span class="pms-help-block">Auto-generated from role name</span>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4 mb-3">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= $form_data['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $form_data['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="roles.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save_role" class="pms-btn-dark">
                            <i class="bi bi-check-lg me-1"></i>Save Role
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
    const roleInput = document.getElementById('role_name');
    const slugInput = document.getElementById('role_slug');

    roleInput.addEventListener('keyup', function() {
        let val = this.value;
        let slug = val.toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
    });
</script>

<?php include "includes/footer.php"; ?>