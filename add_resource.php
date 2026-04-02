<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');


$id = $_GET['id'] ?? 0;

$staff_q = $conn->query("SELECT id FROM roles WHERE slug = 'staff' OR role_name LIKE 'Staff%' LIMIT 1");
$staff_id_global = $staff_q->fetch_assoc()['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_resource'])) {
    $fname = trim($_POST['name']);
    $uemail = trim($_POST['email']);
    $upass = $_POST['password'];
    $utype = $_POST['type'];
    $ustatus = $_POST['status'];
    $role_id = $staff_id_global;

    $working_hours = null;
    if ($utype == 'Part-time') {
        $hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 0;
        $minutes = isset($_POST['minutes']) ? (int)$_POST['minutes'] : 0;
        $working_hours = ($hours * 60) + $minutes;
        if ($working_hours <= 0) {
            session_start();
            $_SESSION['error'] = "Working hours must be greater than zero for part-time resources.";
            header("Location: add_resource.php" . ($id ? "?id=$id" : ""));
            exit();
        }
    }

    if ($uemail != '') {
        $check_sql = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL";
        if ($id) $check_sql .= " AND id != $id";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $uemail);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            session_start();
            $_SESSION['error'] = "A resource with email '$uemail' already exists.";
            header("Location: add_resource.php" . ($id ? "?id=$id" : ""));
            exit();
        } else {
            if ($id) {
                if (!empty($upass)) {
                    $hashed_pass = md5($upass);
                    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, password=?, type=?, working_hours=?, role_id=? WHERE id=?");
                    $stmt->bind_param("sssssisi", $fname, $uemail, $uemail, $hashed_pass, $utype, $working_hours, $role_id, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, type=?, working_hours=?, role_id=? WHERE id=?");
                    $stmt->bind_param("ssssisi", $fname, $uemail, $uemail, $utype, $working_hours, $role_id, $id);
                }
            } else {
                $hashed_pass = md5($upass);
                $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, type, working_hours, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssii", $fname, $uemail, $uemail, $hashed_pass, $utype, $working_hours, $role_id);
            }
            
            if ($stmt->execute()) {
                session_start();
                $_SESSION['success'] = ($id ? "Resource updated successfully." : "Resource added successfully.");
                header("Location: resources.php");
                exit();
            }
        }
    }
}


$name = '';
$email = '';
$type = 'Regular';
$u_status = 'Active';
$working_hours = null;

if ($id) {
    $q = $conn->query("SELECT * FROM users WHERE id=" . (int)$id);
    if ($row = $q->fetch_assoc()) {
        $name = $row['name'];
        $email = $row['email'];
        $type = $row['type'];
        $u_status = $row['status'] ?? 'Active';
        $working_hours = $row['working_hours'];
    }
}
$existing_hours = $working_hours ? floor($working_hours / 60) : 0;
$existing_minutes = $working_hours ? ($working_hours % 60) : 0;
include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <?= $id ? 'Edit Resource' : 'Add New Resource' ?>
                        <a href="resources.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="pms-form-label"><span class="text-danger">*</span> Resource Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name..." value="<?= htmlspecialchars($name) ?>" required autofocus>
                                <div class="invalid-feedback">Please enter name</div>
                            </div>

                            <div class="col-md-4">
                                <label class="pms-form-label"><span class="text-danger">*</span> Email / Username</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email..." value="<?= htmlspecialchars($email) ?>" required>
                                <div class="invalid-feedback">Please enter valid email</div>
                            </div>

                            <div class="col-md-4">
                                <label class="pms-form-label"><?= $id ? 'Password (Empty to keep)' : '<span class="text-danger">*</span> Password' ?></label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password..." <?= $id ? '' : 'required' ?>>
                                <div class="invalid-feedback">Please enter password</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Resource Type</label>
                                <select name="type" id="resource_type" class="form-select">
                                    <option value="Regular" <?= $type == 'Regular' ? 'selected' : '' ?>>Regular</option>
                                    <option value="Part-time" <?= $type == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="working_hours_div" style="<?= $type == 'Part-time' ? '' : 'display: none;' ?>">
                                <label class="pms-form-label"><span class="text-danger">*</span> Working Hours</label>
                                <div class="input-group" style="max-width: 300px;">
                                    <input type="number" id="hours" name="hours" class="form-control text-center" placeholder="0" min="0" value="<?= $existing_hours ?>" <?= $type == 'Part-time' ? 'required' : '' ?>>
                                    <span class="input-group-text bg-light text-secondary">Hrs</span>
                                    <input type="number" id="minutes" name="minutes" class="form-control text-center" placeholder="0" min="0" max="59" value="<?= $existing_minutes ?>" <?= $type == 'Part-time' ? 'required' : '' ?>>
                                    <span class="input-group-text bg-light text-secondary">Mins</span>
                                </div>
                                <small class="text-muted">e.g. 1 Hrs 30 Mins</small>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active" <?= $u_status == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $u_status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <input type="hidden" name="role_id" value="<?= $staff_id_global ?>">

                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="resources.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save_resource" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Save Resource
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

        const typeSelect = document.getElementById('resource_type');
        const workingHoursDiv = document.getElementById('working_hours_div');
        const hoursInput = document.getElementById('hours');
        const minutesInput = document.getElementById('minutes');

        typeSelect.addEventListener('change', function() {
            if (this.value === 'Part-time') {
                workingHoursDiv.style.display = 'block';
                hoursInput.setAttribute('required', 'required');
                minutesInput.setAttribute('required', 'required');
            } else {
                workingHoursDiv.style.display = 'none';
                hoursInput.removeAttribute('required');
                minutesInput.removeAttribute('required');
            }
        });
    })()
</script>

<?php include "includes/footer.php"; ?>