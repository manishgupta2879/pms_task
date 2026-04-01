<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');
include "includes/header.php";

$order_id = (int) ($_GET['order_id'] ?? 0);

if (isset($_POST['save'])) {

    $task_name = trim($_POST['task_name']);
    $hours = isset($_POST['hours']) ? (int) $_POST['hours'] : 0;
    $minutes = isset($_POST['minutes']) ? (int) $_POST['minutes'] : 0;
    $default_time = ($hours * 60) + $minutes;
    $description = trim($_POST['description']);

    if ($task_name == '') {
        $_SESSION['error'] = "Task name is required";
    } elseif ($default_time <= 0) {
        $_SESSION['error'] = "Default time must be greater than zero.";
    } else {

        $sql = "INSERT INTO task_library(task_name, default_time, description)
                VALUES('$task_name', '$default_time', '$description')";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Task library item created successfully!";
            if ($order_id > 0) {
                header("Location: order_task_assignment.php?order_id=$order_id&msg=added");
            } else {
                header("Location: task_library.php?msg=added");
            }
            exit();
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
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
                        Add New Task
                        <?php $back_url = ($order_id > 0) ? "order_task_assignment.php?order_id=$order_id" : "task_library.php"; ?>
                        <a href="<?= $back_url ?>" class="pms-btn-secondary me-2">Back</a>
                    </div>

                    <div class="pms-panel-body">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Task Name</label>
                                <input type="text" name="task_name" class="form-control" placeholder="e.g. Assembly"
                                    required autofocus>
                                <div class="invalid-feedback">
                                    Please enter task name
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Default Time</label>
                                <div class="input-group">
                                    <input type="number" name="hours" class="form-control text-center" placeholder="0"
                                        min="0" value="0" required>
                                    <span class="input-group-text bg-light text-secondary">Hrs</span>
                                    <input type="number" name="minutes" class="form-control text-center" placeholder="0"
                                        min="0" max="59" value="0" required>
                                    <span class="input-group-text bg-light text-secondary">Mins</span>
                                </div>
                                <span class="pms-help-block">e.g. 1 Hrs 30 Mins</span>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="pms-form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="e.g. Build unit / QA process"></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="task_library.php" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" name="save" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Save Task
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>