<?php
include "includes/config.php";

if(!isset($_GET['id']) || !isset($_GET['order_id'])){
    header("Location: orders.php");
    exit();
}

$task_id = (int)$_GET['id'];
$order_id = (int)$_GET['order_id'];

// delete task
$conn->query("DELETE FROM tasks WHERE id=$task_id");

// redirect back
header("Location: view_order.php?id=$order_id&msg=task_deleted");
exit();