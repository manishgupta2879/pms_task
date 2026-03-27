<?php 
include "includes/config.php"; 
include "includes/header.php";

// check role
if($_SESSION['role'] != 'superadmin') {
    die("Access denied");
}

// check id
if(!isset($_GET['id'])){
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];

// fetch user
$res = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $res->fetch_assoc();

if(!$user){
    echo "User not found!";
    exit();
}

// update logic
if(isset($_POST['update'])){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 🔒 Prevent role change for self
    if($user['username'] == $_SESSION['user']){
        $role = $user['role'];
    } else {
        $role = $_POST['role'];
    }

    // password logic
    if(!empty($password)){
        if($password !== $confirm_password){
            $error = "❌ Password and Confirm Password do not match!";
        } else {
            $hashed = md5($password);

            $conn->query("UPDATE users 
                SET username='$username', email='$email', password='$hashed', role='$role' 
                WHERE id=$id");

            header("Location: users.php?msg=updated");
            exit();
        }
    } else {
        $conn->query("UPDATE users 
            SET username='$username', email='$email', role='$role' 
            WHERE id=$id");

        header("Location: users.php?msg=updated");
        exit();
    }
}
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container">
<div class="card">

<h2>Edit User</h2>

<?php if(isset($error)) { ?>
    <p style="color:red;"><?= $error ?></p>
<?php } ?>

<form method="post">

    <label>Username</label>
    <input type="text" name="username" value="<?= $user['username'] ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= $user['email'] ?>" required>

    <label>New Password</label>
    <input type="password" name="password" placeholder="Leave blank to keep old password">

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" placeholder="Re-enter new password">

    <label>Role</label>

    <?php if($user['username'] == $_SESSION['user']) { ?>
        <!-- 🔒 Self: cannot change role -->
        <input type="text" value="<?= $user['role'] ?>" disabled>
        <input type="hidden" name="role" value="<?= $user['role'] ?>">
        <small style="color:gray;">You cannot change your own role</small>
    <?php } else { ?>
        <!-- ✅ Others -->
        <select name="role">
            <option value="staff" <?= $user['role']=='staff'?'selected':'' ?>>Staff</option>
            <option value="superadmin" <?= $user['role']=='superadmin'?'selected':'' ?>>Super Admin</option>
        </select>
    <?php } ?>

    <button name="update">Update User</button>

</form>

</div>
</div>

<?php include "includes/footer.php"; ?>