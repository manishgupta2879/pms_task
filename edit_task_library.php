<?php
include "includes/config.php";
include "includes/header.php";

if(!isset($_GET['id'])){
    header("Location: task_library.php");
    exit();
}

$id = (int)$_GET['id'];
$order_id = $_GET['order_id'] ?? 0;

// fetch task
$res = $conn->query("SELECT * FROM task_library WHERE id=$id");
$task = $res->fetch_assoc();

if(!$task){
    die("Task not found");
}

// update task
if(isset($_POST['update'])){

    $name = trim($_POST['task_name']);
    $time = trim($_POST['default_time']);
    $description = trim($_POST['description']);

    if($name == ''){
        $error = "Task name is required";
    } else {

        $sql = "UPDATE task_library 
                SET task_name='$name', 
                    default_time='$time', 
                    description='$description'
                WHERE id=$id";

        if($conn->query($sql)){
            header("Location: task_library.php?order_id=$order_id&msg=updated");
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
    <h4>✏️ Edit Task</h4>
    <a href="task_library.php?order_id=<?= $order_id ?>" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card-body">

<?php if(isset($error)){ ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

    <div class="mb-3">
        <label>Task Name</label>
        <input type="text" name="task_name" class="form-control" 
               value="<?= $task['task_name'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Default Time</label>
        <input type="text" name="default_time" class="form-control" 
               value="<?= $task['default_time'] ?>">
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3"><?= $task['description'] ?></textarea>
    </div>

    <button name="update" class="btn btn-warning">
        Update Task
    </button>

</form>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>