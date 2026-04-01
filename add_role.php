<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requireSuperAdmin();
include "includes/header.php";

$id = $_GET['id'] ?? 0;
$role_name = '';
$slug = '';
$status = 'Active';

if ($id) {
    $q = $conn->query("SELECT * FROM roles WHERE id=" . (int)$id);
    if ($row = $q->fetch_assoc()) {
        $role_name = $row['role_name'];
        $slug = $row['slug'];
        $status = $row['status'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_role'])) {
    $role_name = trim($_POST['role_name']);
    $role_slug = trim($_POST['slug']);
    $status = $_POST['status'];

    if ($role_name != '') {
        $check_sql = "SELECT id FROM roles WHERE role_name = ? AND deleted_at IS NULL";
        if ($id) $check_sql .= " AND id != $id";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $role_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['error'] = "Role name '$role_name' already exists.";
        } else {
            if ($id) {
                $stmt = $conn->prepare("UPDATE roles SET role_name=?, slug=?, status=? WHERE id=?");
                $stmt->bind_param("sssi", $role_name, $role_slug, $status, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO roles (role_name, slug, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $role_name, $role_slug, $status);
            }
            if ($stmt->execute()) {
                $_SESSION['success'] = ($id ? "Role updated successfully." : "Role added successfully.");
                echo "<script>window.location.href='roles.php';</script>";
                exit();
            }
        }
    }
}
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <?= $id ? 'Edit Role' : 'Add New Role' ?>
                        <a href="roles.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="pms-form-label"><span class="text-danger">*</span> Role Name</label>
                                <input type="text" name="role_name" id="role_name" class="form-control" placeholder="Enter role name..." value="<?= htmlspecialchars($role_name) ?>" required autofocus autocomplete="off">
                                <div class="invalid-feedback">
                                    Please enter role name
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="pms-form-label">Role Slug</label>
                                <input type="text" name="slug" id="role_slug" class="form-control text-lowercase bg-light" placeholder="role-slug" value="<?= htmlspecialchars($slug) ?>" readonly>
                                <span class="pms-help-block">Auto-generated identifier.</span>
                            </div>

                            <div class="col-md-4">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= $status == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="roles.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save_role" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Save Role
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
    (function () {
        'use strict'

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
    })()
</script>

<?php include "includes/footer.php"; ?>