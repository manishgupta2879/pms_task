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

<div class="pms-wrap pb-4">

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
                            <th>Product</th>
                            <th>Deadline</th>
                            <th>Priority</th>

                            <th class="text-end">Assign Resource</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i = 1; ?>
                        <?php while ($t = $tasks->fetch_assoc()):

                            $products = $conn->query("
                                SELECT oi.id, oi.product FROM orders o 
                                left join order_items oi on o.order_no = oi.order_id 
                                WHERE o.id = $order_id
                                AND oi.id NOT IN (
                                    SELECT product 
                                    FROM tasks 
                                    WHERE order_id = $order_id
                                    AND task_name = '{$t['task_name']}'
                                )
                                ORDER BY product ASC
                            ");
                        ?>

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

                                <form method="POST" action="add_task_from_lib.php">
                                    <td>
                                        <?php if ($products->num_rows > 0): ?>
                                            <select name="product" class="form-select form-select-sm select2" required
                                                style="width:200px; font-size: 13px;">
                                                <?php foreach ($products as $p): ?>
                                                    <option value="<?= $p['id'] ?>">
                                                        <?= $p['product'] ?>
                                                    </option>
                                                <?php endforeach; ?>

                                            </select>
                                        <?php else: ?>
                                            <span class="text-muted">Assigned to all products</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="date" name="deadline" class="form-control form-control-sm deadline w-auto" <?= $products->num_rows == 0 ? 'disabled' : '' ?> required>
                                    </td>
                                    <td>
                                        <select name="priority" class="form-select form-select-sm select2 w-auto " <?= $products->num_rows == 0 ? 'disabled' : '' ?>>
                                            <option value="low" selected>Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </td>
                                    <td class="text-end" width="300px">
                                        <div class="d-flex gap-2 justify-content-end align-items-center">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="order_id" value="<?= $order_id ?>">

                                            <!-- 🔍 SEARCH RESOURCE (Filtered by availability) -->
                                            <?php $taskResourceList = getAvailableResources($conn, $t['default_time']); ?>
                                            <select name="user_id" class="form-select form-select-sm select2-subtext" required <?= $products->num_rows == 0 ? 'disabled' : '' ?>
                                                style="min-width:220px; font-size: 13px;">
                                                <option value="">Search resource...</option>
                                                <?php foreach ($taskResourceList as $r):
                                                    $remText = ($r['type'] == 'Part-time') ? " | Available: " . formatMinutes($r['remaining_mins']) : " | Full-time";
                                                ?>
                                                    <option value="<?= $r['id'] ?>" data-subtext="(<?= $r['role'] . $remText ?>)">
                                                        <?= $r['name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="btn btn-sm btn-success" style="white-space: nowrap;" <?= $products->num_rows == 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-plus-circle"></i> Add
                                            </button>
                                        </div>
                                    </td>
                                </form>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include "includes/footer.php"; ?>
<script>
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.getElementsByClassName('deadline');

    for (const dateInput of dateInputs) {
        dateInput.setAttribute('min', today);
    }
</script>