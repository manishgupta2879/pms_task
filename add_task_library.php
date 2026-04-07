<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

$order_id = (int) ($_GET['order_id'] ?? 0);

// Initialize form variables and errors
$form_task_name = '';
$form_hours = 0;
$form_minutes = 0;
$form_description = '';
$field_errors = []; // For inline field errors

// Process form submission BEFORE including header
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {

    $task_name = trim($_POST['task_name']);
    $hours = isset($_POST['hours']) ? (int) $_POST['hours'] : 0;
    $minutes = isset($_POST['minutes']) ? (int) $_POST['minutes'] : 0;
    $default_time = ($hours * 60) + $minutes;
    $description = trim($_POST['description']);

    // Store form data for re-display
    $form_task_name = $task_name;
    $form_hours = $hours;
    $form_minutes = $minutes;
    $form_description = $description;

    // Validation
    if ($task_name == '') {
        $field_errors['task_name'] = "Task name is required";
    }
    
    if ($default_time <= 0) {
        $field_errors['time'] = "Default time must be greater than zero.";
    }

    // Check for duplicate task name only if other fields are valid
    if (empty($field_errors) && $task_name != '') {
        $escaped_name = $conn->real_escape_string($task_name);
        $check_sql = "SELECT id FROM task_library WHERE LOWER(task_name) = LOWER('$escaped_name')";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $field_errors['task_name'] = "This task name already exists. Please use a different name.";
        }
    }

    // If no errors, insert the task
    if (empty($field_errors)) {
        $escaped_name = $conn->real_escape_string($task_name);
        $description_escaped = $conn->real_escape_string($description);
        $sql = "INSERT INTO task_library(task_name, default_time, description)
                VALUES('$escaped_name', '$default_time', '$description_escaped')";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Task library item created successfully!";
            if ($order_id > 0) {
                header("Location: order_task_assignment.php?order_id=$order_id&msg=added");
            } else {
                header("Location: task_library.php?msg=added");
            }
            exit();
        } else {
            $_SESSION['error'] = "Error creating task: " . $conn->error;
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

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        Add New Task
                        <?php $back_url = ($order_id > 0) ? "order_task_assignment.php?order_id=$order_id" : "task_library.php"; ?>
                        <a href="<?= $back_url ?>" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Task Name</label>
                                <input type="text" name="task_name" id="task_name" class="form-control" 
                                    placeholder="e.g. Assembly" value="<?= htmlspecialchars($form_task_name) ?>" 
                                    required autofocus>
                                <?php if (isset($field_errors['task_name'])): ?>
                                    <div class="invalid-feedback d-block" style="display: block; color: #dc3545;">
                                        <?= htmlspecialchars($field_errors['task_name']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="invalid-feedback">
                                        Please enter task name
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Default Time</label>
                                <div class="input-group">
                                    <input type="number" name="hours" id="hours" class="form-control text-center <?= isset($field_errors['time']) ? 'is-invalid' : '' ?>" 
                                        placeholder="0" min="0" value="<?= $form_hours ?>" required>
                                    <span class="input-group-text bg-light text-secondary">Hrs</span>
                                    <input type="number" name="minutes" id="minutes" class="form-control text-center <?= isset($field_errors['time']) ? 'is-invalid' : '' ?>" 
                                        placeholder="0" min="0" max="59" value="<?= $form_minutes ?>" required>
                                    <span class="input-group-text bg-light text-secondary">Mins</span>
                                </div>
                                <?php if (isset($field_errors['time'])): ?>
                                    <div class="invalid-feedback d-block" style="display: block; color: #dc3545;">
                                        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($field_errors['time']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="pms-help-block">e.g. 1 Hrs 30 Mins</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="pms-form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"
                                    placeholder="e.g. Build unit / QA process"><?= htmlspecialchars($form_description) ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="task_library.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="save" class="pms-btn-dark btn-sm" id="submitBtn">
                            <i class="bi bi-check-lg"></i> Save Task
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
    // Form validation and submission
    document.querySelector('form').addEventListener('submit', function(e) {
        const taskName = document.getElementById('task_name').value.trim();
        const hours = parseInt(document.getElementById('hours').value) || 0;
        const minutes = parseInt(document.getElementById('minutes').value) || 0;
        const totalTime = (hours * 60) + minutes;

        let hasError = false;

        // Validate task name
        if (!taskName) {
            document.getElementById('task_name').classList.add('is-invalid');
            hasError = true;
        } else {
            document.getElementById('task_name').classList.remove('is-invalid');
        }

        // Validate time
        if (totalTime <= 0) {
            document.getElementById('hours').classList.add('is-invalid');
            document.getElementById('minutes').classList.add('is-invalid');
            hasError = true;
        } else {
            document.getElementById('hours').classList.remove('is-invalid');
            document.getElementById('minutes').classList.remove('is-invalid');
        }

        if (hasError) {
            e.preventDefault();
            return false;
        }

        return true;
    });
</script>

<?php include "includes/footer.php"; ?>