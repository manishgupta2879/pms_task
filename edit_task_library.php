<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');
include "includes/header.php";

if (!isset($_GET['id'])) {
    header("Location: order_task_assignment.php");
    exit();
}

$id = (int) $_GET['id'];
$order_id = $_GET['order_id'] ?? 0;

// fetch task
$res = $conn->query("SELECT * FROM task_library WHERE id=$id");
$task = $res->fetch_assoc();

if (!$task) {
    die("Task not found");
}

// update task
if (isset($_POST['update'])) {

    $name = trim($_POST['task_name']);
    $hours = isset($_POST['hours']) ? (int) $_POST['hours'] : 0;
    $minutes = isset($_POST['minutes']) ? (int) $_POST['minutes'] : 0;
    $time = ($hours * 60) + $minutes;
    $description = trim($_POST['description']);

    if ($name == '') {
        //$error = "Task name is required";
        $_SESSION['error'] = "Task name is required";

    } elseif ($time <= 0) {
        //$error = "Default time must be greater than zero.";
        $_SESSION['error'] = "Default time must be greater than zero.";
    } else {

        $sql = "UPDATE task_library 
                SET task_name='$name', 
                    default_time='$time', 
                    description='$description'
                WHERE id=$id";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Task updated successfully";
            if ($order_id == 0) {
                header("Location: task_library.php?msg=updated");
            } else {
                header("Location: order_task_assignment.php?order_id=$order_id&msg=updated");
            }
            exit();
        } else {
            //$error = "Error: " . $conn->error;
            $_SESSION['error'] = "Error: " . $conn->error;
        }
    }
}
$stored_time = (int) $task['default_time'];
$existing_hours = floor($stored_time / 60);
$existing_minutes = $stored_time % 60;
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        Edit Task
                        <?php $back_url = ($order_id == 0) ? "task_library.php" : "order_task_assignment.php?order_id=$order_id"; ?>
                        <a href="<?= $back_url ?>" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Task Name</label>
                                <input type="text" name="task_name" class="form-control"
                                    value="<?= htmlspecialchars($task['task_name']) ?>" required autofocus>
                                <div class="invalid-feedback">
                                    Please enter task name
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Default Time</label>
                                <div class="input-group">
                                    <input type="number" name="hours" class="form-control text-center" placeholder="0"
                                        min="0" value="<?= $existing_hours ?>" required>
                                    <span class="input-group-text bg-light text-secondary">Hrs</span>
                                    <input type="number" name="minutes" class="form-control text-center" placeholder="0"
                                        min="0" max="59" value="<?= $existing_minutes ?>" required>
                                    <span class="input-group-text bg-light text-secondary">Mins</span>
                                </div>
                                <span class="pms-help-block">Currently stored as
                                    <?= formatMinutes($stored_time) ?></span>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="pms-form-label">Description</label>
                                <textarea name="description" class="form-control"
                                    rows="3"><?= htmlspecialchars($task['description']) ?></textarea>
                            </div>
                        </div>

                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="<?= $back_url ?>" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="update" class="pms-btn-dark btn-sm">
                            <i class="bi bi-pencil"></i> Update Task
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>