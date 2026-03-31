<?php
include "includes/config.php";

$sql = "ALTER TABLE tasks ADD COLUMN assigned_by INT DEFAULT NULL AFTER user_id";
if ($conn->query($sql)) {
    echo "assigned_by column added successfully.\n";
} else {
    echo "Error adding assigned_by: " . $conn->error . "\n";
}
?>
