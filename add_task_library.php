<?php
include "includes/config.php";
include "includes/header.php";

// save task
if(isset($_POST['save'])){

    $task_name = trim($_POST['task_name']);
    $default_time = trim($_POST['default_time']);
    $description = trim($_POST['description']);

    if($task_name == ''){
        $error = "Task name is required";
    } else {

        $sql = "INSERT INTO task_library(task_name, default_time, description)
                VALUES('$task_name', '$default_time', '$description')";

        if($conn->query($sql)){
            header("Location: task_library.php?order_id=0&msg=added");
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
    <h4>➕ Add Task to Library</h4>
    <a href="task_library.php?order_id=0" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card-body">

<?php if(isset($error)){ ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

    <div class="mb-3">
        <label>Task Name</label>
        <input type="text" name="task_name" class="form-control" 
               placeholder="e.g. Assembly" required>
    </div>

    <div class="mb-3">
        <label>Default Time</label>
        <input type="text" name="default_time" class="form-control" 
               placeholder="e.g. 2h 30m">
    </div>

    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3"
                  placeholder="e.g. Build unit / QA process"></textarea>
    </div>

    <button name="save" class="btn btn-success">
        Save Task
    </button>

</form>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>