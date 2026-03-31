<?php
include "includes/config.php";

// Update default_time to INT
$sql = "ALTER TABLE task_library MODIFY COLUMN default_time INT DEFAULT 0";

if ($conn->query($sql)) {
    echo "task_library table updated: default_time changed to INT successfully.";
} else {
    echo "Error updating task_library: " . $conn->error;
}
?>
