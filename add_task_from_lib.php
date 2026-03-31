<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

if(!isset($_POST['id']) || !isset($_POST['order_id'])){
    header("Location: orders.php");
    exit();
}

$lib_id   = (int)$_POST['id'];
$order_id = (int)$_POST['order_id'];


$user_id     = (int)($_POST['user_id'] ?? 0);
$resource_id = (int)($_POST['resource_id'] ?? 0);

if($user_id == 0 && $resource_id == 0){
    die("Please select user or resource");
}

$res = $conn->query("SELECT * FROM task_library WHERE id=$lib_id");
$task = $res->fetch_assoc();

if(!$task){
    die("Task not found");
}

$sql = "INSERT INTO tasks(
            order_id, 
            task_name, 
            est_time, 
            status, 
            user_id, 
            resource_id,
            assigned_by
        )
        VALUES(
            '$order_id',
            '{$task['task_name']}',
            '{$task['default_time']}',
            'not_started',
            ".($user_id ?: "NULL").",
            ".($resource_id ?: "NULL").",
            ".$_SESSION['user_id']."
        )";

if(!$conn->query($sql)){
    die("Insert Error: " . $conn->error);
}

header("Location: view_order.php?id=$order_id&msg=task_added");
exit();