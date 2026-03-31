<?php
include "includes/config.php";

// Add 'name' to users table
$res = $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(255) AFTER id");

if ($res) {
    echo "Full name column added to users table successfully.";
    // populate name from username for existing ones
    $conn->query("UPDATE users SET name = username WHERE name IS NULL OR name = ''");
} else {
    echo "Error updating users table: " . $conn->error;
}
?>
