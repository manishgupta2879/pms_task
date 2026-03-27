<?php
include "includes/config.php";
include "includes/header.php";

if(!isset($_GET['id'])){
    header("Location: tasks.php");
    exit();
}

$task_id = (int)$_GET['id'];

// get task
$res = $conn->query("SELECT * FROM task_library WHERE id=$task_id");
$task = $res->fetch_assoc();

if(!$task){
    die("Task not found");
}

// get orders
$orders = $conn->query("SELECT id, order_no, customer FROM orders ORDER BY id DESC");

// ✅ get resources instead of users
$resources = $conn->query("SELECT id, name, role, availability FROM resources");

// assign task
if(isset($_POST['assign'])){

    $order_id = (int)$_POST['order_id'];
    $resource_id = (int)$_POST['resource_id'];

    if($order_id == 0 || $resource_id == 0){
        $error = "Please select order and resource";
    } else {
     
        $sql = "INSERT INTO tasks(order_id, task_name, est_time, status, resource_id)
                VALUES('$order_id', '{$task['task_name']}', '{$task['default_time']}', 'not_started', '$resource_id')";

        if($conn->query($sql)){
            header("Location: view_order.php?id=$order_id&msg=task_added");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<div class="container">
<div class="card shadow">

<div class="card-header d-flex justify-content-between">
    <h4>📌 Assign Task: <?= $task['task_name'] ?></h4>
    <a href="tasks.php" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card-body">

<?php if(isset($error)){ ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

    <!-- SELECT ORDER -->
    <div class="mb-3">
        <label>Select Order</label>
        <select name="order_id" class="form-select select2" required>
            <option value="">Search Order...</option>
            <?php while($o = $orders->fetch_assoc()){ ?>
                <option value="<?= $o['id'] ?>">
                    <?= $o['order_no'] ?> - <?= $o['customer'] ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <!-- ✅ SELECT RESOURCE -->
    <div class="mb-3">
        <label>Assign Resource</label>
        <select name="resource_id" class="form-select select2" required>
            <option value="">Search Resource...</option>
            <?php while($r = $resources->fetch_assoc()){ ?>
                <option value="<?= $r['id'] ?>">
                    <?= $r['name'] ?> (<?= $r['role'] ?> - <?= $r['availability'] ?>)
                </option>
            <?php } ?>
        </select>
    </div>

    <button name="assign" class="btn btn-success">
        Assign Task
    </button>

</form>

</div>
</div>
</div>

<!-- SELECT2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Search...",
        width: '100%'
    });
});
</script>

<?php include "includes/footer.php"; ?>