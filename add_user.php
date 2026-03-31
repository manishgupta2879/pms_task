<?php 
include "includes/config.php";
include "includes/rbac.php";
requireAuth();
requireSuperAdmin();
include "includes/header.php";

if(isset($_POST['save'])){
$username=$_POST['username'];
$email=$_POST['email'];
$pass=md5($_POST['password']);
$role=$_POST['role'];
$conn->query("INSERT INTO users(username,email,password,role) VALUES('$username','$email','$pass','$role')");
header("Location: users.php");
}
?>
<div class="card">
<h2>Add User</h2>
<form method="post">
<input name="username" placeholder="Username" required>
<input name="email" placeholder="Email" required>
<input name="password" placeholder="Password" required>
<select name="role">
<option value="staff">Staff</option>
<option value="superadmin">Super Admin</option>
</select>
<button name="save">Save</button>
</form>
</div>
<?php include "includes/footer.php"; ?>