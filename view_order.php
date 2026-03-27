<?php
include "includes/config.php";
if (isset($_POST['save_notes'])) {
    $id = (int)$_GET['id'];
    $notes = $conn->real_escape_string($_POST['notes']);
    $conn->query("UPDATE orders SET notes='$notes' WHERE id=$id");
    header("Location: view_order.php?id=$id&save_notes=notes_saved");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$id = (int)$_GET['id'];

// get order
$orderRes = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $orderRes->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// ✅ get tasks with BOTH user + resource
$taskRes = $conn->query("
    SELECT t.*, 
           u.username,
           r.name as resource_name, r.role
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN resources r ON t.resource_id = r.id
    WHERE t.order_id=$id
");

// progress
$total = $conn->query("SELECT COUNT(*) as t FROM tasks WHERE order_id=$id")->fetch_assoc()['t'];
$done = $conn->query("SELECT COUNT(*) as d FROM tasks WHERE order_id=$id AND status='completed'")->fetch_assoc()['d'];
$progress = ($total > 0) ? round(($done / $total) * 100) : 0;

// deadline alert
$today = date("Y-m-d");
$deadline = $order['deadline'];

$deadline_alert = "";
if ($deadline < $today) {
    $deadline_alert = "<div class='alert alert-danger'>⚠️ Deadline passed!</div>";
} elseif ($deadline == $today) {
    $deadline_alert = "<div class='alert alert-warning'>⚠️ Deadline is today!</div>";
}

// ✅ productivity (user + resource combined)
$productivityRes = $conn->query("
    SELECT 
        COALESCE(u.username, r.name) as person,
        COUNT(t.id) as total,
        SUM(CASE WHEN t.status='completed' THEN 1 ELSE 0 END) as completed
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN resources r ON t.resource_id = r.id
    WHERE t.order_id=$id
    GROUP BY person
");
include "includes/header.php";
?>
<div class="container">
        <?php if (isset($_GET['save_notes']) && $_GET['save_notes'] == 'notes_saved') { ?>
            <div class="alert alert-success text-center">Notes saved successfully</div>
        <?php } ?>

    <?= $deadline_alert ?>

    <!-- ORDER INFO -->
    <div class="card shadow mb-3">
        <div class="card-body">

            <h5><strong>Order #:</strong> <?= $order['order_no'] ?></h5>
            <div class="d-flex justify-content-between align-items-center">
                <p><strong>Customer:</strong> <?= $order['customer'] ?></p>
                <p><strong>Product:</strong> <?= $order['product'] ?></p>
                <p><strong>Deadline:</strong> <?= date("d-F-Y", strtotime($order['deadline'])) ?></p>
                <a href="edit_order.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-pencil-square"></i> Edit Order
                </a>
            </div>

            <!-- <p><strong>Status:</strong> 
    <span class="badge bg-dark"><?= ucfirst($order['status']) ?></span>
</p> -->



        </div>
    </div>

    <!-- TASK LIST -->
    <div class="card shadow mb-3">
        <div class="card-header">
            <h5 class=""><i class="bi bi-list-task align-middle"></i> Task List</h5>
        </div>

        <div class="card-body">

            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Task Name</th>
                        <th>Assigned To</th>
                        <th>Est. Time</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <!-- <th>Actions</th> -->
                    </tr>
                </thead>

                <tbody>

                    <?php if ($taskRes->num_rows == 0) { ?>
                        <tr>
                            <td colspan="4" class="text-center">No tasks added yet</td>
                        </tr>
                    <?php } ?>

                    <?php while ($t = $taskRes->fetch_assoc()) { ?>
                        <tr>

                            <td><?= $t['task_name'] ?></td>

                            <td>
                                <?php
                                if ($t['username']) {
                                    echo $t['username'] . " (User)";
                                } elseif ($t['resource_name']) {
                                    echo $t['resource_name'] . " (" . $t['role'] . " - Resource)";
                                } else {
                                    echo "Not Assigned";
                                }
                                ?>
                            </td>
                            <!-- Estimated Time -->
                            <td><?= isset($t['estimated_time']) ? $t['estimated_time'] . ' hrs' : '-' ?></td>
                            <!-- Status -->
                            <td>
                                <?php if ($t['status'] == 'completed') { ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php } elseif ($t['status'] == 'in_progress') { ?>
                                    <span class="badge bg-primary">In Progress</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Not Started</span>
                                <?php } ?>
                            </td>
                            <!-- Start & End Time -->
                            <td><?= ($t['start_time']) ? date("d-M H:i", strtotime($t['start_time'])) : '-' ?></td>
                            <td><?= ($t['end_time']) ? date("d-M H:i", strtotime($t['end_time'])) : '-' ?></td>
                            <!-- <td>
                                <?php if ($order['status'] != 'completed') { ?>

                                    <?php if ($t['status'] == 'not_started') { ?>
                                        <a href="start_task.php?id=<?= $t['id'] ?>" class="btn btn-success btn-sm">Start</a>
                                    <?php } elseif ($t['status'] == 'in_progress') { ?>
                                        <a href="stop_task.php?id=<?= $t['id'] ?>" class="btn btn-danger btn-sm">Stop</a>
                                    <?php } else { ?>
                                        <span class="text-success">Done</span>
                                    <?php } ?>

                                    <a href="delete_task.php?id=<?= $t['id'] ?>&order_id=<?= $id ?>"
                                        class="btn btn-outline-danger btn-sm mt-1"
                                        onclick="return confirm('Delete this task?')">
                                        Delete
                                    </a>

                                <?php } else { ?>
                                    <span class="text-muted">Locked</span>
                                <?php } ?>
                            </td> -->

                        </tr>
                    <?php } ?>

                </tbody>
            </table>

            <!-- ADD TASK -->
            <div class="text-end">
                <?php if ($order['status'] != 'completed') { ?>
                    <a href="task_library.php?order_id=<?= $id ?>" class="btn btn-success">
                        + Add Task
                    </a>
                <?php } else { ?>
                    <button class="btn btn-secondary" disabled>Order Completed</button>
                <?php } ?>
            </div>


        </div>
    </div>

    <!-- PROGRESS -->
    <div class="card shadow mb-3">
         <div class="card-header">
                <h5><i class="bi bi-binoculars-fill"></i> Progress</h5>
            </div>
        <div class="card-body">           
            <div class="d-flex align-items-center gap-3">
                <strong>Overall Completion:</strong>
                <div class="flex-grow-1">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $progress ?>%
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>


    <div class="card shadow mb-3">
        <div class="card-header">
            <h5><i class="bi bi-cpu-fill allign-middle"></i> Productivity</h5>
        </div>

        <div class="card-body">

            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <th>Total</th>
                    <th>Completed</th>
                    <th>Efficiency</th>
                </tr>

                <?php while ($p = $productivityRes->fetch_assoc()) {
                    $eff = ($p['total'] > 0) ? round(($p['completed'] / $p['total']) * 100) : 0;
                ?>
                    <tr>
                        <td><?= $p['person'] ?></td>
                        <td><?= $p['total'] ?></td>
                        <td><?= $p['completed'] ?></td>
                        <td><?= $eff ?>%</td>
                    </tr>
                <?php } ?>

            </table>

        </div>
    </div>

    <!-- NOTES -->
    <div class="card shadow">
        <div class="card-header">
            <h5><i class="bi bi-chat-left-text-fill align-middle"></i> Notes</h5>
        </div>

        <div class="card-body">

            <form method="POST">
                <textarea name="notes" class="form-control" rows="4"><?= $order['notes'] ?></textarea>
                <button name="save_notes" class="btn btn-primary mt-2">Save Notes</button>
                
            </form>

        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>