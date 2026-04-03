<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
// requirePermission('orders');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $task_id = (int) $_POST['task_id'];
    $priority = $_POST['priority'];

    $allowed = ['low', 'medium', 'high'];

    if (!in_array($priority, $allowed)) {
        echo "Invalid priority";
        exit();
    }

    $stmt = $conn->prepare("UPDATE tasks SET priority=? WHERE id=?");
    $stmt->bind_param("si", $priority, $task_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}