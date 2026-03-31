<?php
include "includes/config.php";

$sql = "ALTER TABLE roles ADD COLUMN slug VARCHAR(255) NOT NULL AFTER role_name";

if ($conn->query($sql)) {
    echo "Slug column added successfully.";
    
    // Populate existing slugs
    $res = $conn->query("SELECT id, role_name FROM roles");
    while ($row = $res->fetch_assoc()) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['role_name']), '-'));
        $conn->query("UPDATE roles SET slug='$slug' WHERE id=" . $row['id']);
    }
    echo " Existing slugs updated.";
} else {
    echo "Error updating table: " . $conn->error;
}
?>
