<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');
include "includes/header.php";

// fetch tasks
$tasks = $conn->query("SELECT * FROM task_library");
?>

<div class="pms-wrap">
    <div class="pms-panel">

        <div class="pms-controls">
            <div class="pms-controls-left">
                <h5 class="mb-0 fw-bold" style="color: #334155;">Task Library</h5>
            </div>

            <div class="pms-controls-right">
                <a href="add_task_library.php" class="pms-btn-dark btn-sm">+ Add Task</a>
            </div>
        </div>

        <!-- <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success m-3 mb-0">
            <?= $_GET['msg'] == 'added' ? 'Task added successfully' : '' ?>
            <?= $_GET['msg'] == 'updated' ? 'Task updated successfully' : '' ?>
            <?= $_GET['msg'] == 'deleted' ? 'Task deleted successfully' : '' ?>
        </div>
        <?php endif; ?> -->

        <div style="overflow-x: auto;">
        <table class="pms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Task Name</th>
                    <th>Time</th>
                    <th>Description</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($tasks->num_rows == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">No tasks found</td>
                    </tr>
                <?php endif; ?>
                <?php $i = 1; ?>
                <?php while ($t = $tasks->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="text-dark fw-medium"><strong><?= $t['task_name'] ?></strong></td>
                        <td>
                            <span class="badge bg-success">
                                <?= formatMinutes($t['default_time']) ?>
                            </span>
                        </td>
                        <td><?= $t['description'] ?: '-' ?></td>

                        <td class="text-end">
                            <a href="assign_task.php?id=<?= $t['id'] ?>" class="pms-action-btn me-1"
                                title="Assign to Order">
                                <i class="bi bi-arrow-right-circle"></i> Assign to Order
                            </a>
                            <a href="edit_task_library.php?id=<?= $t['id'] ?>&order_id=0" class="pms-action-btn me-1"
                                title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete_task_library.php?id=<?= $t['id'] ?>&order_id=0"
                                class="pms-action-btn pms-action-btn-danger" title="Delete"
                                onclick="return confirm('Delete this task?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>
        </div>

    </div>
</div>

<?php include "includes/footer.php"; ?>