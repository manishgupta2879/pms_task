<?php
include "includes/config.php";

// Add 'type' and 'deleted_at' to users table
// 'type' will store Part-time / Regular
// we also ensure 'deleted_at' exists for soft delete
$res1 = $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS type ENUM('Regular', 'Part-time') DEFAULT 'Regular'");
$res2 = $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");

if ($res1 && $res2) {
    echo "Users table updated successfully.";
} else {
    echo "Error updating users table: " . $conn->error;
}
?>