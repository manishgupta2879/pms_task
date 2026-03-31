<?php
include "includes/config.php";

$sql = "CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    remarks TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "Leaves table created successfully.";
} else {
    echo "Error creating leaves table: " . $conn->error;
}
?>
