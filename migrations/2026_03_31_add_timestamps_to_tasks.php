<?php
include "includes/config.php";

$sql = "ALTER TABLE tasks ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
if ($conn->query($sql)) {
    echo "created_at column added successfully.\n";
} else {
    echo "Error adding created_at: " . $conn->error . "\n";
}

$sql = "ALTER TABLE tasks ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
if ($conn->query($sql)) {
    echo "updated_at column added successfully.\n";
} else {
    echo "Error adding updated_at: " . $conn->error . "\n";
}
?>
