<?php

class AddProfilePicToUsers
{
    public function up($conn)
    {
        $sql = "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL AFTER email";
        if ($conn->query($sql)) {
            echo "✓ Added profile_pic column to users table\n";
        } else {
            echo "✗ Error adding profile_pic: " . $conn->error . "\n";
        }
    }
}
