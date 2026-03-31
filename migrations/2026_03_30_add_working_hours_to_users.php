<?php
include "includes/config.php";

$sql = "ALTER TABLE users ADD COLUMN working_hours INT DEFAULT NULL AFTER type";
if ($conn->query($sql)) {
    echo "working_hours column added successfully.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
