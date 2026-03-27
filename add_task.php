<?php 
include "includes/config.php";
include "includes/header.php";

$order_id = $_GET['order_id'];

if(isset($_POST['save'])){
    $task = $_POST['task_name'];
    $assigned = $_POST['assigned_to'];
    $time = $_POST['est_time'];
    $status = $_POST['status'];

    $conn->query("INSERT INTO tasks(order_id,task_name,assigned_to,est_time,status)
                  VALUES('$order_id','$task','$assigned','$time','$status')");

    header("Location: view_order.php?id=$order_id");
}

$users = $conn->query("SELECT * FROM users WHERE role='staff'");

?>

<div class="card">
<div class="card-body">

<h4>Add Task</h4>

<form method="POST">
    <input class="form-control mb-2" name="task_name" placeholder="Task Name" required>
    <select name="user_id" class="form-select mb-2">
	<?php while($u = $users->fetch_assoc()){ ?>
		<option value="<?= $u['id'] ?>"><?= $u['username'] ?></option>
	<?php } ?>
	</select>
    <input class="form-control mb-2" name="est_time" placeholder="Est Time (e.g. 2h 30m)">

    <select class="form-select mb-2" name="status">
        <option value="not started">Not Started</option>
        <option value="in progress">In Progress</option>
        <option value="completed">Completed</option>
    </select>

    <button class="btn btn-success" name="save">Save Task</button>
</form>

</div>
</div>

<?php include "includes/footer.php"; ?>