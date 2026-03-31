<?php
include "includes/config.php";

// 1. Add 'role_id' column to users table and create index
$sql1 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS role_id INT DEFAULT NULL AFTER role";
$sql2 = "CREATE INDEX IF NOT EXISTS idx_role_id ON users(role_id)";

if ($conn->query($sql1) && $conn->query($sql2)) {
    echo "role_id column and index added successfully.";
    
    // 2. Map existing users to 'staff' or 'superadmin' roles by ID
    // Find 'staff' role ID from roles table
    $res_staff = $conn->query("SELECT id FROM roles WHERE role_name LIKE 'staff%' OR slug = 'staff' LIMIT 1");
    if ($staff_row = $res_staff->fetch_assoc()) {
        $staff_id = $staff_row['id'];
        $conn->query("UPDATE users SET role_id = $staff_id WHERE role = 'staff' AND role_id IS NULL");
        echo " Updated staff roles.";
    }

    $res_admin = $conn->query("SELECT id FROM roles WHERE role_name LIKE 'superadmin%' OR slug = 'super-admin' LIMIT 1");
    if ($admin_row = $res_admin->fetch_assoc()) {
        $admin_id = $admin_row['id'];
        $conn->query("UPDATE users SET role_id = $admin_id WHERE role = 'superadmin' AND role_id IS NULL");
        echo " Updated superadmin roles.";
    }

} else {
    echo "Error updating users table: " . $conn->error;
}
?>
