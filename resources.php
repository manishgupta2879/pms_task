<?php
include "includes/config.php";
include "includes/header.php";

// fetch resources
$res = $conn->query("SELECT * FROM resources");
?>

<div class="container">
<div class="card shadow">

<div class="card-header d-flex justify-content-between">
    <h4>🧑‍🔧 Resource List</h4>
    <a href="add_resource.php" class="btn btn-success btn-sm">+ Add Resource</a>
</div>

<div class="card-body">

<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Name</th>
    <th>Role</th>
    <th>Availability</th>
    <th width="120">Actions</th>
</tr>
</thead>

<tbody>

<?php if($res->num_rows == 0){ ?>
<tr>
    <td colspan="4" class="text-center">No resources found</td>
</tr>
<?php } ?>

<?php while($r = $res->fetch_assoc()){ ?>
<tr>
    <td><?= $r['name'] ?></td>
    <td><?= $r['role'] ?></td>

    <td>
        <?php if($r['availability']=='Available'){ ?>
            <span class="badge bg-success">Available</span>
        <?php } elseif($r['availability']=='Partial'){ ?>
            <span class="badge bg-warning text-dark">Partial</span>
        <?php } else { ?>
            <span class="badge bg-danger">Busy</span>
        <?php } ?>
    </td>

    <td>
        <a href="view_resource.php?id=<?= $r['id'] ?>" 
           class="btn btn-info btn-sm">
           View
        </a>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>