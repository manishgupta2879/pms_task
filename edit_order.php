<?php 
include "includes/config.php";
include "includes/header.php";

if(!isset($_GET['id'])){
    header("Location: orders.php");
    exit();
}

$id = (int)$_GET['id'];

// get order
$res = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $res->fetch_assoc();

if(!$order){
    die("Order not found");
}

// update
if(isset($_POST['update'])){

    $customer = $conn->real_escape_string($_POST['customer']);
    $product  = $conn->real_escape_string($_POST['product']);
    $deadline = $_POST['deadline'];
    $status   = $_POST['status'];

    // ✅ DEBUG (optional remove later)
    // echo $status; exit();

    $sql = "UPDATE orders 
            SET customer='$customer',
                product='$product',
                deadline='$deadline',
                status='$status'
            WHERE id=$id";

    if($conn->query($sql)){
        header("Location: view_order.php?id=$id&msg=updated");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: ".$conn->error."</div>";
    }
}
?>

<div class="container">
<div class="card shadow">

<div class="card-header d-flex justify-content-between">
    <h4>✏️ Edit Order</h4>
    <a href="view_order.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Back</a>
</div>

<div class="card-body">

<form method="POST">

    <div class="mb-3">
        <label>Order #</label>
        <input type="text" class="form-control" value="<?= $order['order_no'] ?>" disabled>
    </div>

    <div class="mb-3">
        <label>Customer</label>
        <input type="text" name="customer" class="form-control" 
               value="<?= $order['customer'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Product</label>
        <input type="text" name="product" class="form-control" 
               value="<?= $order['product'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Deadline</label>
        <input type="date" name="deadline" class="form-control" 
               value="<?= $order['deadline'] ?>" required>
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select" required>
            <option value="active" <?= ($order['status']=='active')?'selected':'' ?>>Active</option>
            <option value="pending" <?= ($order['status']=='pending')?'selected':'' ?>>Pending</option>
            <option value="completed" <?= ($order['status']=='completed')?'selected':'' ?>>Completed</option>
        </select>
    </div>

    <button name="update" class="btn btn-warning">
        Update Order
    </button>

</form>

</div>
</div>
</div>

<?php include "includes/footer.php"; ?>