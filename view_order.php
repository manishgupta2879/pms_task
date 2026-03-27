<?php 
include "includes/config.php";
include "includes/header.php";

if(!isset($_GET['id'])){
    header("Location: orders.php");
    exit();
}

$id = (int)$_GET['id'];

// get order
$orderRes = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $orderRes->fetch_assoc();

if(!$order){
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
$progress = ($total > 0) ? round(($done/$total)*100) : 0;

// deadline alert
$today = date("Y-m-d");
$deadline = $order['deadline'];

$deadline_alert = "";
if($deadline < $today){
    $deadline_alert = "<div class='alert alert-danger'>⚠️ Deadline passed!</div>";
}elseif($deadline == $today){
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
?>

<div class="container">

<?= $deadline_alert ?>

<!-- ORDER INFO -->
<div class="card shadow mb-3">
<div class="card-body">

<h5><strong>Order #:</strong> <?= $order['order_no'] ?></h5>
<p><strong>Customer:</strong> <?= $order['customer'] ?></p>
<p><strong>Product:</strong> <?= $order['product'] ?></p>
<p><strong>Deadline:</strong> <?= date("F d", strtotime($order['deadline'])) ?></p>

<p><strong>Status:</strong> 
    <span class="badge bg-dark"><?= ucfirst($order['status']) ?></span>
</p>

<a href="edit_order.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
    ✏️ Edit Order
</a>

</div>
</div>

<!-- TASK LIST -->
<div class="card shadow mb-3">
<div class="card-header">
    <h5>📋 Task List</h5>
</div>

<div class="card-body">

<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Task</th>
    <th>Assigned To</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php if($taskRes->num_rows == 0){ ?>
<tr>
    <td colspan="4" class="text-center">No tasks added yet</td>
</tr>
<?php } ?>

<?php while($t = $taskRes->fetch_assoc()){ ?>
<tr>

<td><?= $t['task_name'] ?></td>

<td>
    <?php
    if($t['username']){
        echo $t['username'] . " (User)";
    } elseif($t['resource_name']){
        echo $t['resource_name']." (".$t['role']." - Resource)";
    } else {
        echo "Not Assigned";
    }
    ?>
</td>

<td>
    <?php if($t['status']=='completed'){ ?>
        <span class="badge bg-success">Completed</span>
    <?php } elseif($t['status']=='in_progress'){ ?>
        <span class="badge bg-primary">In Progress</span>
    <?php } else { ?>
        <span class="badge bg-secondary">Not Started</span>
    <?php } ?>
</td>

<td>
<?php if($order['status']!='completed'){ ?>

    <?php if($t['status']=='not_started'){ ?>
        <a href="start_task.php?id=<?= $t['id'] ?>" class="btn btn-success btn-sm">Start</a>
    <?php } elseif($t['status']=='in_progress'){ ?>
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
</td>

</tr>
<?php } ?>

</tbody>
</table>

<!-- ADD TASK -->
<?php if($order['status']!='completed'){ ?>
<a href="task_library.php?order_id=<?= $id ?>" class="btn btn-primary">
    + Add Task
</a>
<?php } else { ?>
<button class="btn btn-secondary" disabled>Order Completed</button>
<?php } ?>

</div>
</div>

<!-- PROGRESS -->
<div class="card shadow mb-3">
<div class="card-body">

<h5>📊 Progress</h5>

<div class="progress" style="height:25px;">
    <div class="progress-bar bg-success" style="width: <?= $progress ?>%">
        <?= $progress ?>%
    </div>
</div>

<p class="mt-2"><strong><?= $progress ?>% Completed</strong></p>

</div>
</div>

<!-- PRODUCTIVITY -->
<div class="card shadow mb-3">
<div class="card-header">
    <h5>📊 Productivity</h5>
</div>

<div class="card-body">

<table class="table table-bordered">
<tr>
<th>Name</th>
<th>Total</th>
<th>Completed</th>
<th>Efficiency</th>
</tr>

<?php while($p = $productivityRes->fetch_assoc()){ 
    $eff = ($p['total']>0) ? round(($p['completed']/$p['total'])*100) : 0;
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
    <h5>📝 Notes</h5>
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