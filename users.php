<?php 
include "includes/config.php"; 
include "includes/header.php";

if($_SESSION['role'] != 'superadmin') {
    die("Access denied");
}

$res = $conn->query("SELECT * FROM users");
?>

<link rel="stylesheet" href="assets/style.css">

<div class="container">

<div class="card">

    <!-- HEADER -->
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>👥 User Management</h2>
        <a href="add_user.php" class="btn">+ Add User</a>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if(isset($_GET['msg']) && $_GET['msg']=='updated'){ ?>
        <p style="color:green;">✅ User updated successfully</p>
    <?php } ?>

    <?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'){ ?>
        <p style="color:red;">🗑️ User deleted successfully</p>
    <?php } ?>

    <!-- TABLE -->
    <table class="user-table">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Action</th>
        </tr>

        <?php while($r = $res->fetch_assoc()){ ?>
        <tr>
            <td>#<?= $r['id'] ?></td>
            <td><?= $r['username'] ?></td>
            <td><?= $r['email'] ?></td>
            <td>
                <?php if($r['role'] == 'superadmin'){ ?>
                    <span class="badge admin">Super Admin</span>
                <?php } else { ?>
                    <span class="badge staff">Staff</span>
                <?php } ?>
            </td>
            <td>
                <a href="edit_user.php?id=<?= $r['id'] ?>" class="edit">Edit</a>

                <?php if($r['username'] != $_SESSION['user']) { ?>
                    <a href="delete_user.php?id=<?= $r['id'] ?>" 
                       class="delete"
                       onclick="return confirm('Delete user: <?= $r['username'] ?> ?')">
                       Delete
                    </a>
                <?php } else { ?>
                    <span class="protected">Protected</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>

    </table>

</div>

</div>

<?php include "includes/footer.php"; ?>