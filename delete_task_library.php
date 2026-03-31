<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

$id = $_GET['id'];
$order_id = $_GET['order_id'] ?? 0;

// delete
$conn->query("DELETE FROM task_library WHERE id=$id");

$redirect_url = ($order_id == 0) ? "task_library.php?msg=deleted" : "order_task_assignment.php?order_id=$order_id&msg=deleted";
header("Location: $redirect_url");
exit();