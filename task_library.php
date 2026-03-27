<?php
include "includes/config.php";
include "includes/header.php";

if(!isset($_GET['order_id'])){
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// fetch tasks
$tasks = $conn->query("SELECT * FROM task_library");
if(!$tasks){
    die("Query Error: " . $conn->error);
}

// ✅ fetch resources instead of users
$resources = $conn->query("SELECT id, name, role, availability FROM resources");
$resourceList = [];
while($r = $resources->fetch_assoc()){
    $resourceList[] = $r;
}
?>

<div class="container">

<div class="card shadow">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0">📚 Task Library</h4>
    <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card-body">

<!-- ✅ messages -->
<?php if(isset($_GET['msg'])){ ?>
    <div class="alert alert-success">
        <?= $_GET['msg']=='added' ? 'Task added successfully' : '' ?>
        <?= $_GET['msg']=='updated' ? 'Task updated successfully' : '' ?>
        <?= $_GET['msg']=='deleted' ? 'Task deleted successfully' : '' ?>
    </div>
<?php } ?>

<!-- ➕ ADD NEW -->
<div class="mb-3 text-end">
    <a href="add_task_library.php" class="btn btn-success">
        + Add New Task
    </a>
</div>

<?php if($tasks->num_rows == 0){ ?>
    <div class="alert alert-warning">No tasks found in library.</div>
<?php } else { ?>

<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Task Name</th>
    <th>Time</th>
    <th>Description</th>
    <th width="400">Actions</th>
</tr>
</thead>

<tbody>
<?php while($t = $tasks->fetch_assoc()){ ?>
<tr>

    <td><strong><?= $t['task_name'] ?></strong></td>

    <td>
        <span class="badge bg-info text-dark">
            <?= $t['default_time'] ?>
        </span>
    </td>

    <td>
        <?= $t['description'] ?: '<span class="text-muted">No description</span>' ?>
    </td>

    <td>
        <div class="d-flex flex-column gap-2">

            <!-- ✅ ADD TASK WITH RESOURCE -->
            <form method="POST" action="add_task_from_lib.php" 
                  class="d-flex align-items-center gap-2 flex-wrap">

                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">

                <!-- 🔍 SEARCH RESOURCE -->
                <select name="resource_id" class="form-select form-select-sm select2-resource" style="min-width:200px;" required>
                    <option value="">Search resource...</option>
                    <?php foreach($resourceList as $r){ ?>
                        <option value="<?= $r['id'] ?>">
                            <?= $r['name'] ?> (<?= $r['role'] ?> - <?= $r['availability'] ?>)
                        </option>
                    <?php } ?>
                </select>

                <button class="btn btn-success btn-sm">
                    ➕ Add
                </button>
            </form>

            <!-- ✏️ EDIT + DELETE -->
            <div class="d-flex gap-2">

                <a href="edit_task_library.php?id=<?= $t['id'] ?>&order_id=<?= $order_id ?>" 
                   class="btn btn-warning btn-sm">
                   ✏️ Edit
                </a>

                <a href="delete_task_library.php?id=<?= $t['id'] ?>&order_id=<?= $order_id ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this task?')">
                   🗑 Delete
                </a>

            </div>

        </div>
    </td>

</tr>
<?php } ?>
</tbody>
</table>

<?php } ?>

</div>
</div>

</div>

<!-- ✅ SELECT2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- ✅ jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ✅ SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2-resource').each(function(){
        $(this).select2({
            placeholder: "Search resource...",
            width: 'resolve',
            dropdownParent: $(this).parent()
        });
    });
});
</script>

<?php include "includes/footer.php"; ?>