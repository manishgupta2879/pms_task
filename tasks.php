<?php
include "includes/config.php";
include "includes/header.php";

// fetch tasks
$tasks = $conn->query("SELECT * FROM task_library");
?>

<div class="card shadow">
<div class="card-header d-flex justify-content-between">
    <h4>📚 Task Library</h4>
    <a href="add_task_library.php" class="btn btn-success btn-sm">+ Add Task</a>
</div>

<div class="card-body">

<table class="table table-bordered table-hover">
<thead class="table-dark">
<tr>
    <th>Task</th>
    <th>Time</th>
    <th>Description</th>
    <th>Assign</th>
</tr>
</thead>

<tbody>
<?php while($t = $tasks->fetch_assoc()){ ?>
<tr>
    <td><?= $t['task_name'] ?></td>
    <td><?= $t['default_time'] ?></td>
    <td><?= $t['description'] ?></td>

    <td>
        <a href="assign_task.php?id=<?= $t['id'] ?>" class="btn btn-primary btn-sm">
            Assign to Order
        </a>
    </td>
</tr>
<?php } ?>
</tbody>

</table>

</div>
</div>

<?php include "includes/footer.php"; ?>