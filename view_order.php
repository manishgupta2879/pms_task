<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');


if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$id = (int) $_GET['id'];

// get order
$orderRes = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $orderRes->fetch_assoc();

if (!$order) {
    die("Order not found");
}
// $order_items = $conn->query("SELECT * FROM order_items WHERE order_id='{$order['order_no']}'")->fetch_all(MYSQLI_ASSOC);
$order_items = $conn->query("SELECT * FROM order_items WHERE order_id='{$order['order_no']}'");


// save notes
if (isset($_POST['save_notes'])) {
    $notes = $conn->real_escape_string($_POST['notes']);
    $conn->query("UPDATE orders SET notes='$notes' WHERE id=$id");
    $_SESSION['success'] = "Notes saved successfully";
    header("Location: view_order.php?id=$id");
    exit();
}

// progress
$total = $conn->query("SELECT COUNT(*) as t FROM tasks WHERE order_id=$id")->fetch_assoc()['t'];
$done = $conn->query("SELECT COUNT(*) as d FROM tasks WHERE order_id=$id AND status='completed'")->fetch_assoc()['d'];
$progress = ($total > 0) ? round(($done / $total) * 100) : 0;

// deadline alert
$today = date("Y-m-d");
$deadline = $order['deadline'];

$deadline_class = "";
$deadline_text = "";
if ($deadline < $today) {
    $deadline_class = "bg-danger";
    $deadline_text = "⚠️ Deadline passed!";
} elseif ($deadline == $today) {
    $deadline_class = "bg-warning";
    $deadline_text = "⚠️ Deadline is today!";
}

// ✅ productivity - users only
$productivityRes = $conn->query("
    SELECT 
        COALESCE(u.username, 'Unassigned') as person,
        COUNT(t.id) as total,
        SUM(CASE WHEN t.status='completed' THEN 1 ELSE 0 END) as completed
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.order_id=$id
    GROUP BY u.id, u.username
");
include "includes/header.php";
?>

<div class="pms-wrap">
<div id="alertBox"></div>
    <div style="padding-bottom: 20px;">

        <!-- ORDER INFO HEADER -->
        <div class="pms-panel mb-3">
            <div class="pms-controls">
                <div class="pms-controls-left">
                    <h5 class="mb-0 fw-bold" style="color: #1e293b;">Order #<?= $order['order_no'] ?></h5>
                </div>
                <div>

                    <!-- Deadline Alert Centered in Header -->
                    <?php if ($deadline_text): ?>
                        <div style="text-align: center;">
                            <div class="alert alert-<?= str_replace('bg-', '', $deadline_class) ?> d-inline-block"
                                style="padding: 4px 12px; margin-bottom: 0px !important; border-radius: 6px; min-width: 300px;">
                                <?= $deadline_text ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="pms-controls-right">
                    <a href="orders.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="edit_order.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>
            </div>


            <!-- Order Details Grid - Smaller -->
            <div class="row g-2" style="padding: 0 20px 12px 20px;">
                <div class="col-md-3">
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 6px;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500;">Customer
                        </div>
                        <div style="font-size: 13px; color: #1e293b; font-weight: 600;">
                            <?= htmlspecialchars($order['customer']) ?>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-md-3">
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 6px;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500;">Product
                        </div>
                        <div style="font-size: 13px; color: #1e293b; font-weight: 600;">
                            <?= htmlspecialchars($order['product']) ?>
                        </div>
                    </div>
                </div> -->
                <div class="col-md-3">
                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 6px;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500;">Deadline
                        </div>
                        <div
                            style="font-size: 13px; font-weight: 600; color: <?= ($deadline < $today) ? '#ef4444' : ($deadline == $today ? '#f59e0b' : '#10b981') ?>;">
                            <?= date("M d, Y", strtotime($order['deadline'])) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="background: #f8fafc; padding: 6px 12px; border-radius: 6px;">
                        <div style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500;">Status</div>
                        <span class="pms-status <?= strtolower($order['status']) ?>"
                            style="font-weight: 600; font-size: 12px;">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pms-panel mb-3">
            <div class="pms-controls">
                <div class="pms-controls-left">
                    <h5 class="mb-0 fw-bold" style="color: #334155;"><i class="bi bi-list-task me-2"></i>Product List</h5>
                </div>
            </div>

            <?php if ($order_items->num_rows == 0): ?>
                <div class="alert alert-info text-center py-4" style="margin: 15px 20px;">
                    <i class="bi bi-info-circle me-2"></i> No products added yet
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                <table class="pms-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Product</th>
                            <th>Species</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1;
                        while ($item = $order_items->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="text-muted fw-medium"><?= $counter++ ?></td>
                                <td class="text-dark fw-medium"><?= htmlspecialchars($item['product']) ?></td>
                                <td class="text-dark fw-medium"><?= htmlspecialchars($item['species']) ?></td>
                                <td class="text-dark fw-medium"><?= htmlspecialchars($item['qty']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="pms-panel mb-3">
            <div class="pms-controls">
                <div class="pms-controls-left">
                    <h5 class="mb-0 fw-bold" style="color: #334155;"><i class="bi bi-list-task me-2"></i>Task List</h5>
                </div>
                <div class="pms-controls-right">
                    <?php if ($order['status'] != 'completed'): ?>
                        <a href="order_task_assignment.php?order_id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Task
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php 
            $taskRes = $conn->query("
                SELECT t.*, 
                       u.username,
                       u.role_id,
                       r.role_name,
                       r.slug
                FROM tasks t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE t.order_id=$id
            ");
            ?>

            <?php if ($taskRes->num_rows == 0): ?>
                <div class="alert alert-info text-center py-4" style="margin: 15px 20px;">
                    <i class="bi bi-info-circle me-2"></i> No tasks added yet
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                <table class="pms-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Task Name</th>
                            <th>Assigned To</th>
                            <th>Est. Time</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>On-Time/Delay</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $task_counter = 1;
                        while ($t = $taskRes->fetch_assoc()):
                            $is_delayed = false;
                            $delay_text = '-';
                            if ($t['status'] == 'completed' && $t['start_time'] && $t['end_time'] && $t['est_time']) {

                                $start = new DateTime($t['start_time']);
                                $end = new DateTime($t['end_time']);

                                $start->setTime($start->format('H'), $start->format('i'), 0);
                                $end->setTime($end->format('H'), $end->format('i'), 0);

                                if ($end < $start) {
                                    $end->modify('+1 day');
                                }

                                $actual_minutes = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);

                                $est_minutes = (int)$t['est_time'];

                                if ($actual_minutes > $est_minutes) {
                                    $is_delayed = true;

                                    $diff_minutes_total = $actual_minutes - $est_minutes;

                                    $diff_hours = floor($diff_minutes_total / 60);
                                    $diff_minutes = $diff_minutes_total % 60;

                                    if ($diff_hours > 0) {
                                        $delay_text = $diff_hours . 'h ' . $diff_minutes . 'm late';
                                    } else {
                                        $delay_text = $diff_minutes . 'm late';
                                    }

                                } else {
                                    $delay_text = '✓ On Time';
                                }
                            }

                        ?>
                            <tr>
                                <td class="text-muted fw-medium"><?= $task_counter++ ?></td>
                                <td class="text-dark fw-medium"><?= htmlspecialchars($t['task_name']) ?></td>
                                <td>
                                    <?php if ($t['username']): ?>
                                        <span class="text-dark fw-medium"><?= htmlspecialchars($t['username']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= $t['est_time'] ? formatMinutes($t['est_time']) : '-' ?></span>
                                </td>
                                <td>
                                    <span class="pms-status <?= str_replace('_', ' ', $t['status']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                                    </span>
                                </td>
                                <td>  
                                    <select name="priority" class="form-select py-0 <?= $t['status'] == 'completed' ? 'disabled' : 'select2 priority-change' ?> " data-task-id="<?= $t['id'] ?>" <?= $t['status'] == 'completed' ? 'disabled' : '' ?>>
                                        <option value="low" <?= $t['priority'] == 'low' ? 'selected' : '' ?> >Low</option>
                                        <option value="medium" <?= $t['priority'] == 'medium' ? 'selected' : '' ?> >Medium</option>
                                        <option value="high" <?= $t['priority'] == 'high' ? 'selected' : '' ?> >High</option>                                    
                                    </select>
                                </td>
                                <td><span class="text-muted" style="font-size: 13px;"><?= ($t['start_time']) ? date("M d, H:i", strtotime($t['start_time'])) : '-' ?></span></td>
                                <td><span class="text-muted" style="font-size: 13px;"><?= ($t['end_time']) ? date("M d, H:i", strtotime($t['end_time'])) : '-' ?></span></td>
                                <td>
                                    <?php if ($t['status'] == 'completed'): ?>
                                        <span class="badge <?= $is_delayed ? 'bg-danger' : 'bg-success' ?>" style="font-size: 11px;">
                                            <?= $delay_text ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($order['status'] != 'completed'): ?>
                                        <?php if ($t['status'] == 'not_started'): ?>
                                            <a href="start_task.php?id=<?= $t['id'] ?>" class="pms-action-btn pms-action-btn-success" title="Start Task">
                                                <i class="bi bi-play-fill"></i> Start Task
                                            </a>
                                        <?php elseif ($t['status'] == 'in_progress'): ?>
                                            <a href="stop_task.php?id=<?= $t['id'] ?>" class="pms-action-btn pms-action-btn-danger" title="End Task">
                                                <i class="bi bi-stop-fill"></i> End Task
                                            </a>
                                        <?php else: ?>
                                            <span class="text-success fw-medium">✓ Done</span>
                                        <?php endif; ?>

                                        <a href="delete_task.php?id=<?= $t['id'] ?>&order_id=<?= $id ?>" class="pms-action-btn pms-action-btn-danger" title="Delete Task" onclick="return confirm('Delete this task?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        </div>
        <!-- PRODUCTIVITY SECTION -->
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="pms-panel">
                    <div class="pms-controls">
                        <h5 class="mb-0 fw-bold" style="color: #334155;"><i class="bi bi-cpu-fill me-2"></i>Productivity
                        </h5>
                    </div>

                    <div style="overflow-x: auto;">
                    <table class="pms-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Name</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Completed</th>
                                <th class="text-end">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($productivityRes->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No data</td>
                                </tr>
                            <?php endif; ?>

                            <?php $prod_counter = 1;
                            while ($p = $productivityRes->fetch_assoc()):
                                $eff = ($p['total'] > 0) ? round(($p['completed'] / $p['total']) * 100) : 0;
                                ?>
                                <tr>
                                    <td class="text-muted fw-medium"><?= $prod_counter++ ?></td>
                                    <td class="text-dark fw-medium"><?= htmlspecialchars($p['person']) ?></td>
                                    <td class="text-center text-dark fw-medium"><?= $p['total'] ?></td>
                                    <td class="text-center text-dark fw-medium"><?= $p['completed'] ?></td>
                                    <td class="text-end">
                                        <span
                                            class="badge <?= $eff >= 80 ? 'bg-success' : ($eff >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= $eff ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROGRESS SECTION (Full width) -->
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="pms-panel">
                    <div class="pms-controls">
                        <h5 class="mb-0 fw-bold" style="color: #334155;"><i
                                class="bi bi-binoculars-fill me-2"></i>Progress</h5>
                    </div>
                    <div style="padding: 30px 20px;">
                        <!-- Progress Bar -->
                        <div style="margin-bottom: 25px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <!-- <span style="font-size: 14px; font-weight: 600; color: #334155;">Completion</span> -->
                                <span
                                    style="font-size: 18px; font-weight: 900; color: #10b981;"><?= $progress ?>%</span>
                            </div>
                            <div
                                style="background-color: #e2e8f0; border-radius: 10px; height: 12px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                                <div
                                    style="background: linear-gradient(90deg, #34d399 0%, #10b981 100%); height: 100%; width: <?= $progress ?>%; transition: width 0.5s ease; border-radius: 10px;">
                                </div>
                            </div>
                        </div>

                        <!-- Task Summary -->
                        <div style="background-color: #f1f5f9; border-radius: 8px; padding: 15px; text-align: center;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <div style="font-size: 24px; font-weight: 900; color: #10b981;"><?= $done ?></div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Completed</div>
                                </div>
                                <div>
                                    <div style="font-size: 24px; font-weight: 900; color: #3b82f6;"><?= $total ?></div>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Total Tasks</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- NOTES SECTION -->
        <div class="pms-panel">
            <div class="pms-controls">
                <h5 class="mb-0 fw-bold" style="color: #334155;"><i class="bi bi-chat-left-text-fill me-2"></i>Notes
                </h5>
            </div>

            <div style="padding: 20px;">
                <form method="POST">
                    <textarea name="notes" class="form-control mb-3" rows="6" placeholder="Add order notes..."
                        style="font-size: 14px; border-radius: 6px; border: 1px solid #e2e8f0;"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                    <div class="text-end">
                        <button type="submit" name="save_notes" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Save Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
</div>

<?php include "includes/footer.php"; ?>

<!-- SELECT2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Search...",
            width: '100%'
        });
    });
</script>
<script>
$(document).on('change', '.priority-change', function () {
    let priority = $(this).val();
    let task_id = $(this).data('task-id');

    $.ajax({
        url: 'update_priority.php',
        method: 'POST',
        data: {
            task_id: task_id,
            priority: priority
        },
        success: function (res) {
            // optional success feedback
            // console.log("Priority updated");
            showAlert('success', 'Priority updated successfully');
        },
        error: function () {
            // alert("Failed to update priority");
            showAlert('danger', 'Failed to update priority');
        }
    });
    function showAlert(type, message) {
        let alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('#alertBox').html(alertHtml);

        // Auto hide after 3 seconds
        setTimeout(() => {
            $('#alertBox .alert').alert('close');
        }, 3000);
    }
});
</script>