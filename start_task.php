<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

$id = $_GET['id'];
$user_id = $_SESSION['user_id'] ?? 1;

// update task
$conn->query("UPDATE tasks 
SET status='in_progress', start_time=NOW() 
WHERE id=$id");

// log start
$conn->query("INSERT INTO task_logs(task_id,user_id,start_time)
VALUES($id,$user_id,NOW())");

header("Location: ".$_SERVER['HTTP_REFERER']);