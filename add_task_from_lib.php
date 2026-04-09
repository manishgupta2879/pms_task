<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');

if (!isset($_POST['id']) || !isset($_POST['order_id'])) {
    header("Location: orders.php");
    exit();
}

$lib_id   = (int)$_POST['id'];
$order_id = (int)$_POST['order_id'];


$user_id     = (int)($_POST['user_id'] ?? 0);
$resource_id = (int)($_POST['resource_id'] ?? 0);
$deadline    = $_POST['deadline'] ?? null;
$priority    = $_POST['priority'] ?? null;
$product_id    = (int)($_POST['product'] ?? 0);
if ($user_id == 0 && $resource_id == 0) {
    die("Please select user or resource");
}
$res = $conn->query("SELECT * FROM task_library WHERE id=$lib_id");
$task = $res->fetch_assoc();


if (!$task) {
    die("Task not found");
}

$products = $conn->query("SELECT oi.id, oi.product FROM orders o left join order_items oi on o.order_no = oi.order_id WHERE o.id = $order_id AND oi.id NOT IN (SELECT product FROM tasks WHERE order_id = $order_id AND task_name = '{$task['task_name']}') ORDER BY product ASC");

if ($products->num_rows == 0) {
    $_SESSION['error'] = "All products in this order already have this task assigned. Please assign a different task or remove the existing one from the product.";
    header("Location: order_task_assignment.php?id=$order_id&msg=no_products");
}






$sql = "INSERT INTO tasks(
            order_id, 
            task_name, 
            est_time, 
            status, 
            user_id, 
            resource_id,
            assigned_by,
            deadline,
            product,
            priority
        )
        VALUES(
            '$order_id',
            '{$task['task_name']}',
            '{$task['default_time']}',
            'not_started',
            " . ($user_id ?: "NULL") . ",
            " . ($resource_id ?: "NULL") . ",
            " . $_SESSION['user_id'] . ",
            '$deadline',
            $product_id,
            '$priority'

        )";

if (!$conn->query($sql)) {
    die("Insert Error: " . $conn->error);
}

header("Location: view_order.php?id=$order_id&msg=task_added");
exit();
