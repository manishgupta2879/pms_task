<?php
include "includes/config.php";
include "includes/header.php";

if(isset($_POST['save'])){

    $name = $_POST['name'];
    $role = $_POST['role'];
    $availability = $_POST['availability'];

    $conn->query("INSERT INTO resources(name, role, availability)
                  VALUES('$name','$role','$availability')");

    header("Location: resources.php");
    exit();
}
?>

<div class="container">
<div class="card shadow">

<div class="card-header">
    <h4>➕ Add Resource</h4>
</div>

<div class="card-body">

<form method="POST">

    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Role</label>
        <input type="text" name="role" class="form-control" placeholder="Tech / QA">
    </div>

    <div class="mb-3">
        <label>Availability</label>
        <select name="availability" class="form-select">
            <option value="Available">Available</option>
            <option value="Partial">Partial</option>
            <option value="Busy">Busy</option>
        </select>
    </div>

    <button name="save" class="btn btn-success">
        Save Resource
    </button>

</form>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>