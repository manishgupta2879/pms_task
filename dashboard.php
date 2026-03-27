<?php include "includes/config.php"; include "includes/header.php"; ?>
<div class="card">
<h1>Welcome <?php echo $_SESSION['user']; ?></h1>
<p>Role: <?php echo $_SESSION['role']; ?></p>
</div>
<?php include "includes/footer.php"; ?>