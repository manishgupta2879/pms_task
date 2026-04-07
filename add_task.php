<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

$order_id = (int) ($_GET['order_id'] ?? 0);
$errors = [];
$form_data = [
    'task_name' => '',
    'assigned_to' => '',
    'est_time' => '',
    'status' => 'not started'
];

if (isset($_POST['save'])) {
    $form_data['task_name'] = trim($_POST['task_name'] ?? '');
    $form_data['assigned_to'] = $_POST['assigned_to'] ?? '';
    $form_data['est_time'] = trim($_POST['est_time'] ?? '');
    $form_data['status'] = $_POST['status'] ?? 'not started';
    $assigned_by = $_SESSION['user_id'];

    // Validation
    if (empty($form_data['task_name'])) {
        $errors['task_name'] = "Task name is required.";
    }
    
    if (empty($form_data['assigned_to'])) {
        $errors['assigned_to'] = "Please select a staff member.";
    }
    
    if (empty($form_data['est_time'])) {
        $errors['est_time'] = "Estimated time is required.";
    }

    // If no errors, insert the task
    if (empty($errors)) {
        $task = $conn->real_escape_string($form_data['task_name']);
        $assigned = (int) $form_data['assigned_to'];
        $time = $conn->real_escape_string($form_data['est_time']);
        $status = $conn->real_escape_string($form_data['status']);

        $sql = "INSERT INTO tasks(order_id, task_name, assigned_to, est_time, status, user_id, assigned_by)
                VALUES($order_id, '$task', $assigned, '$time', '$status', $assigned, $assigned_by)";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Task added successfully.";
            header("Location: view_order.php?id=$order_id");
            exit();
        } else {
            $_SESSION['error'] = "Error adding task: " . $conn->error;
        }
    }
}

$users = $conn->query("SELECT id, username FROM users WHERE role='staff' ORDER BY username");
include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-8">

            <form method="POST" class="pms-panel">
                <div class="pms-panel-header">
                    Add New Task for Order #<?= htmlspecialchars($order_id) ?>
                </div>

                <div class="pms-panel-body">
                    <!-- Task Name -->
                    <div class="mb-3">
                        <label class="pms-form-label"><span class="text-danger">*</span> Task Name</label>
                        <input type="text" name="task_name" class="form-control" 
                            placeholder="e.g. Assembly" 
                            value="<?= htmlspecialchars($form_data['task_name']) ?>" 
                            required autofocus>
                        <?php if (isset($errors['task_name'])): ?>
                            <div class="text-danger small mt-1">
                                <i class="bi bi-exclamation-circle me-1"></i><?= $errors['task_name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Assigned To -->
                    <div class="mb-3">
                        <label class="pms-form-label"><span class="text-danger">*</span> Assign To</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">-- Select Staff Member --</option>
                            <?php while ($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>" 
                                    <?= ($form_data['assigned_to'] == $u['id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($u['username']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if (isset($errors['assigned_to'])): ?>
                            <div class="text-danger small mt-1">
                                <i class="bi bi-exclamation-circle me-1"></i><?= $errors['assigned_to'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Est Time -->
                    <div class="mb-3">
                        <label class="pms-form-label"><span class="text-danger">*</span> Estimated Time</label>
                        <input type="text" name="est_time" class="form-control" 
                            placeholder="e.g. 2h 30m" 
                            value="<?= htmlspecialchars($form_data['est_time']) ?>" 
                            required>
                        <?php if (isset($errors['est_time'])): ?>
                            <div class="text-danger small mt-1">
                                <i class="bi bi-exclamation-circle me-1"></i><?= $errors['est_time'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="pms-form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="not started" <?= ($form_data['status'] == 'not started' ? 'selected' : '') ?>>Not Started</option>
                            <option value="in progress" <?= ($form_data['status'] == 'in progress' ? 'selected' : '') ?>>In Progress</option>
                            <option value="completed" <?= ($form_data['status'] == 'completed' ? 'selected' : '') ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <div class="pms-panel-footer text-end">
                    <a href="view_order.php?id=<?= $order_id ?>" class="pms-btn-cancel">
                        <i class="bi bi-x me-1"></i>Cancel
                    </a>
                    <button type="submit" name="save" class="pms-btn-dark">
                        <i class="bi bi-check-lg me-1"></i>Save Task
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>