<?php 
include "includes/config.php";
include "includes/header.php";

if(isset($_POST['save'])){

    // ✅ get max id safely
    $res = $conn->query("SELECT MAX(id) as max_id FROM orders");
    $row = $res->fetch_assoc();

    // fix NULL case
    $next_id = ($row['max_id'] ?? 0) + 1;

    // generate order number
    $order_no = 10000 + $next_id;

    $customer = $_POST['customer'];
    $product = $_POST['product'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];

    // ✅ insert with error check
    $sql = "INSERT INTO orders(order_no,customer,product,deadline,status)
            VALUES('$order_no','$customer','$product','$deadline','$status')";

    if($conn->query($sql)){
        header("Location: orders.php?msg=created");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
?>

<div class="card shadow">
<div class="card-header">
    <h4>Create Order</h4>
</div>

<div class="card-body">

<form method="POST">

    <input class="form-control mb-2" name="customer" placeholder="Customer" required>

    <input class="form-control mb-2" name="product" placeholder="Product" required>

    <input type="date" class="form-control mb-2" name="deadline" required>

    <select class="form-select mb-2" name="status">
        <option value="active">Active</option>
        <option value="pending">Pending</option>
        <option value="completed">Completed</option>
    </select>

    <button class="btn btn-success" name="save">Save Order</button>

</form>

</div>
</div>

<?php include "includes/footer.php"; ?>