<?php 
include "includes/config.php";

if(!isset($_GET['id'])){
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];

// fetch user
$res = $conn->query("SELECT username FROM users WHERE id=$id");
$user = $res->fetch_assoc();

// ❌ prevent self delete
if($user['username'] == $_SESSION['user']){
    echo "❌ You cannot delete your own account!";
    exit();
}

$conn->query("DELETE FROM users WHERE id=$id");

header("Location: users.php?msg=deleted");
exit();
?>