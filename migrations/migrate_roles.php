<?php
include "includes/config.php";

$sql = "CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Roles table created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

// Optionally seed some data
$check = $conn->query("SELECT COUNT(*) as count FROM roles");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO roles (role_name, status) VALUES ('Tech', 'Active'), ('QA', 'Active'), ('Manager', 'Active')");
    echo "\nSeeded default roles.";
}
?>
