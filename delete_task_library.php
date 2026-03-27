<?php
include "includes/config.php";

$id = $_GET['id'];
$order_id = $_GET['order_id'] ?? 0;

// delete
$conn->query("DELETE FROM task_library WHERE id=$id");

header("Location: task_library.php?order_id=$order_id&msg=deleted");
exit();