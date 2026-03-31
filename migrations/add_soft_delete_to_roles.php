<?php
include "includes/config.php";

$sql = "ALTER TABLE roles ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at";

if ($conn->query($sql)) {
    echo "Soft delete column added successfully.";
} else {
    echo "Error updating table: " . $conn->error;
}
?>
