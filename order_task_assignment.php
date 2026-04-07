<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');


if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int) $_GET['order_id'];

// fetch tasks
$tasks = $conn->query("SELECT * FROM task_library");
if (!$tasks) {
    die("Query Error: " . $conn->error);
}

// we will fetch resources per task to check time / leave availability
include "includes/header.php";
?>

<div class="pms-wrap">

    <div class="pms-panel">

        <div class="pms-controls">
            <div class="pms-controls-left">
                <h5 class="mb-0 fw-bold" style="color: #334155;">Task Library</h5>
            </div>

            <div class="pms-controls-right">
                <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="bi bi-arrow-left me-1"></i>Back</a>
                <a href="add_task_library.php?order_id=<?= $order_id ?>" class="btn btn-outline-secondary btn-sm">
                    + Add New Task
                </a>
            </div>
        </div>

        <!-- ✅ messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success m-3 mb-0">
                <?= $_GET['msg'] == 'added' ? 'Task added successfully' : '' ?>
                <?= $_GET['msg'] == 'updated' ? 'Task updated successfully' : '' ?>
                <?= $_GET['msg'] == 'deleted' ? 'Task deleted successfully' : '' ?>
            </div>
        <?php endif; ?>

        <?php if ($tasks->num_rows == 0): ?>
            <div class="alert alert-warning m-3">No tasks found in library.</div>
        <?php else: ?>

            <div style="overflow-x: auto;">
            <table class="pms-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Task Name</th>
                        <th>Time</th>
                        <th>Description</th>
                        <th class="text-end">Assign Resource</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $i = 1; ?>
                    <?php while ($t = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>

                            <td class="text-dark fw-medium">
                                <strong><?= $t['task_name'] ?></strong>
                            </td>

                            <td>
                                <span class="badge bg-success">
                                    <?= formatMinutes($t['default_time']) ?>
                                </span>
                            </td>

                            <td>
                                <?= $t['description'] ?: '-' ?>
                            </td>

                            <td class="text-end" width="300px">
                                <!-- ✅ ADD TASK WITH USER -->
                                <form method="POST" action="add_task_from_lib.php"
                                    class="d-flex gap-2 justify-content-end align-items-center">

                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="order_id" value="<?= $order_id ?>">

                                    <!-- 🔍 SEARCH RESOURCE (Filtered by availability) -->
                                    <?php $taskResourceList = getAvailableResources($conn, $t['default_time']); ?>
                                    <select name="user_id" class="form-select form-select-sm select2-resource" required
                                        style="min-width:220px; font-size: 13px;">
                                        <option value="">Search resource...</option>
                                        <?php foreach ($taskResourceList as $r):
                                            $remText = ($r['type'] == 'Part-time') ? " | Available: " . formatMinutes($r['remaining_mins']) : " | Full-time";
                                            ?>
                                            <option value="<?= $r['id'] ?>">
                                                <?= $r['name'] ?> (<?= $r['role'] . $remText ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button class="btn btn-sm btn-success" style="white-space: nowrap;">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>

                                </form>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- ✅ SELECT2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- ✅ jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ✅ SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2-resource').each(function () {
            $(this).select2({
                placeholder: "Search resource...",
                width: '280px',
                dropdownParent: $(this).parent()
            });
        });
    });
</script>

<?php include "includes/footer.php"; ?>