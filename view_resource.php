<?php
include "includes/config.php";
include "includes/header.php";

$id = (int)$_GET['id'];

$res = $conn->query("SELECT * FROM resources WHERE id=$id");
$r = $res->fetch_assoc();
?>

<div class="container">
<div class="card shadow">

<div class="card-header">
    <h4>👤 Resource Details</h4>
</div>

<div class="card-body">

<p><strong>Name:</strong> <?= $r['name'] ?></p>
<p><strong>Role:</strong> <?= $r['role'] ?></p>
<p><strong>Availability:</strong> <?= $r['availability'] ?></p>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>