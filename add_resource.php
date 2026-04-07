<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');

$id = $_GET['id'] ?? 0;
$errors = [];

$staff_q = $conn->query("SELECT id FROM roles WHERE slug = 'staff' OR role_name LIKE 'Staff%' LIMIT 1");
$staff_id_global = $staff_q->fetch_assoc()['id'] ?? 0;

// Initialize form variables
$form_data = [
    'name' => '',
    'email' => '',
    'password' => '',
    'type' => 'Regular',
    'hours' => 0,
    'minutes' => 0
];

// Load existing resource if editing
if ($id) {
    $q = $conn->query("SELECT * FROM users WHERE id=" . (int)$id);
    if ($row = $q->fetch_assoc()) {
        $form_data['name'] = $row['name'];
        $form_data['email'] = $row['email'];
        $form_data['type'] = $row['type'] ?? 'Regular';
        $working_hours = $row['working_hours'] ?? 0;
        $form_data['hours'] = floor($working_hours / 60);
        $form_data['minutes'] = $working_hours % 60;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_resource'])) {
    $form_data['name'] = trim($_POST['name'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['type'] = $_POST['type'] ?? 'Regular';
    $form_data['hours'] = (int) ($_POST['hours'] ?? 0);
    $form_data['minutes'] = (int) ($_POST['minutes'] ?? 0);

    // Validation
    if (empty($form_data['name'])) {
        $errors['name'] = "Resource name is required.";
    }

    if (empty($form_data['email'])) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (!$id && empty($form_data['password'])) {
        $errors['password'] = "Password is required for new resources.";
    }

    if ($form_data['type'] == 'Part-time') {
        $working_hours = ($form_data['hours'] * 60) + $form_data['minutes'];
        if ($working_hours <= 0) {
            $errors['hours'] = "Working hours must be greater than zero for part-time resources.";
        }
    }

    // Check for duplicate email if no errors
    if (empty($errors) && !empty($form_data['email'])) {
        $check_sql = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL";
        if ($id) {
            $check_sql .= " AND id != ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("si", $form_data['email'], $id);
        } else {
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("s", $form_data['email']);
        }
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $errors['email'] = "A resource with this email already exists.";
        }
    }

    // If no errors, save the resource
    if (empty($errors)) {
        $working_hours = ($form_data['type'] == 'Part-time') ?
            (($form_data['hours'] * 60) + $form_data['minutes']) : null;

        if ($id) {
            // Update existing resource
            if (!empty($form_data['password'])) {
                $hashed_pass = md5($form_data['password']);
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, username=?, password=?, type=?, working_hours=? WHERE id=?");
                $stmt->bind_param("ssssssi", $form_data['name'], $form_data['email'], $form_data['email'], $hashed_pass, $form_data['type'], $working_hours, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, username=?, type=?, working_hours=? WHERE id=?");
$stmt->bind_param("sssssi", $form_data['name'], $form_data['email'], $form_data['email'], $form_data['type'], $working_hours, $id);
            }
        } else {
            // Create new resource
            $hashed_pass = md5($form_data['password']);
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, type, working_hours, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssi", $form_data['name'], $form_data['email'], $form_data['email'], $hashed_pass, $form_data['type'], $working_hours, $staff_id_global);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = ($id ? "Resource updated successfully." : "Resource added successfully.");
            header("Location: resources.php");
            exit();
        } else {
            $_SESSION['error'] = "Error saving resource: " . $stmt->error;
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
                        <?= $id ? 'Edit Resource' : 'Add New Resource' ?>
                        <a href="resources.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <!-- Resource Name -->
                         <div class="row g-3">
                        <div class="col-md-6">
                            <label class="pms-form-label"><span class="text-danger">*</span> Resource Name</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="Enter full name..."
                                value="<?= htmlspecialchars($form_data['name']) ?>"
                                autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="pms-form-label"><span class="text-danger">*</span> Email / Username</label>
                            <input type="email" name="email" class="form-control"
                                placeholder="Enter email..."
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
                                <?= $id ? 'Password' : '<span class="text-danger">*</span> Password' ?>
                                <?php if ($id): ?>
                                    <span class="text-muted small">(Empty to keep current)</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Enter password..."
                                <?= !$id ? '' : '' ?>>
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Resource Type -->
                        <div class="col-md-6">
                            <label class="pms-form-label">Resource Type</label>
                            <select name="type" id="resource_type" class="form-select">
                                <option value="Regular" <?= $form_data['type'] == 'Regular' ? 'selected' : '' ?>>Regular</option>
                                <option value="Part-time" <?= $form_data['type'] == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                            </select>
                        </div>

                        <!-- Working Hours (for Part-time) -->
                        <div class="col-md-6" id="working_hours_div" style="<?= $form_data['type'] == 'Part-time' ? '' : 'display: none;' ?>">
                            <label class="pms-form-label"><span class="text-danger">*</span> Working Hours</label>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="number" id="hours" name="hours" class="form-control text-center"
                                    placeholder="0" min="0"
                                    value="<?= $form_data['hours'] ?>"
                                    <?= $form_data['type'] == 'Part-time' ? 'required' : '' ?>>
                                <span class="input-group-text bg-light text-secondary">Hrs</span>
                                <input type="number" id="minutes" name="minutes" class="form-control text-center"
                                    placeholder="0" min="0" max="59"
                                    value="<?= $form_data['minutes'] ?>"
                                    <?= $form_data['type'] == 'Part-time' ? 'required' : '' ?>>
                                <span class="input-group-text bg-light text-secondary">Mins</span>
                            </div>
                            <?php if (isset($errors['hours'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i><?= $errors['hours'] ?>
                                </div>
                            <?php else: ?>
                                <small class="text-muted d-block mt-1">e.g. 1 Hrs 30 Mins</small>
                            <?php endif; ?>
                        </div>

                        
                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="resources.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save_resource" class="pms-btn-dark">
                            <i class="bi bi-check-lg me-1"></i>Save Resource
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
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
</script>

<?php include "includes/footer.php"; ?>