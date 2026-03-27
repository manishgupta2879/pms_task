<?php
include "includes/config.php";

$id = $_GET['id'];

// get last log
$log = $conn->query("SELECT * FROM task_logs 
WHERE task_id=$id AND end_time IS NULL 
ORDER BY id DESC LIMIT 1")->fetch_assoc();

$start = strtotime($log['start_time']);
$end = time();
$duration = $end - $start;

// update log
$conn->query("UPDATE task_logs 
SET end_time=NOW(), duration=$duration 
WHERE id=".$log['id']);

// update task
$conn->query("UPDATE tasks 
SET status='completed', end_time=NOW() 
WHERE id=$id");

header("Location: ".$_SERVER['HTTP_REFERER']);