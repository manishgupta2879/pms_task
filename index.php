<?php include "includes/config.php";
if(isset($_POST['login'])){
$username=$_POST['username'];
$password=md5($_POST['password']);
$res=$conn->query("SELECT * FROM users WHERE username='$username' AND password='$password'");
if($res->num_rows==1){
$row=$res->fetch_assoc();
$_SESSION['user']=$row['username'];
$_SESSION['role']=$row['role'];
header("Location: dashboard.php");
}else{$err="Invalid Login";}
}
?>
<link rel="stylesheet" href="assets/style.css">
<div class="container"><div class="card">
<h2>Login</h2>
<?php if(isset($err)) echo "<p>$err</p>"; ?>
<form method="post">
<input name="username" placeholder="Username" required>
<input name="password" type="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
</div></div>