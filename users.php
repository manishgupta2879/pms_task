<?php
include "includes/config.php";
include "includes/header.php";

// Restrict access
if ($_SESSION['role'] != 'superadmin') {
    die("Access denied");
}

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // Prevent self delete
    $check_user = $conn->query("SELECT username FROM users WHERE id=$del_id");
    $user_data  = $check_user->fetch_assoc();

    if ($user_data && $user_data['username'] == $_SESSION['user']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $conn->query("UPDATE users SET deleted_at=NOW() WHERE id=$del_id");
        $_SESSION['success'] = "User deleted successfully.";
    }

    header("Location: users.php");
    exit();
}

// Search + Pagination
$search   = $_GET['search'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset   = ($page - 1) * $per_page;

$where = "WHERE deleted_at IS NULL";

if ($search != '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (username LIKE '%$safe%' OR email LIKE '%$safe%')";
}

// Count
$count_res   = $conn->query("SELECT COUNT(*) as cnt FROM users $where");
$total       = $count_res->fetch_assoc()['cnt'];
$total_pages = max(1, ceil($total / $per_page));

// Fetch data
$res = $conn->query("SELECT * FROM users $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");

$qs = '&search=' . urlencode($search);
?>

<div class="pms-wrap">

    <div class="pms-panel">

        <!-- Header -->
        <div class="pms-controls">
            <div class="pms-controls-left">
                <h5 class="mb-0 fw-bold" style="color:#334155;">Users</h5>
            </div>

            <div class="pms-controls-right">
                <form method="GET" class="d-flex gap-2">
                    <div class="pms-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search"
                               class="form-control ps-5"
                               placeholder="Search users..."
                               value="<?= htmlspecialchars($search) ?>"
                               style="font-size:13px; width:220px;">
                    </div>
                </form>

                <a href="add_user.php" class="pms-btn-dark">
                    + New User
                </a>
            </div>
        </div>

        <!-- Table -->
        <table class="pms-table">
            <thead>
                <tr>
                    <th style="width:70px;">#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($res->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No users found</td>
                    </tr>
                <?php endif; ?>

                <?php $i = $offset + 1; ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>

                        <td class="text-dark fw-medium">
                            <?= htmlspecialchars($r['name']) ?>
                        </td>


                        <td class="fw-medium text-dark">
                            <?= htmlspecialchars($r['username']) ?>
                        </td>

                        <td class="text-muted">
                            <?= htmlspecialchars($r['email']) ?>
                        </td>

                        <td>
                            <?php if ($r['role'] == 'superadmin'): ?>
                                <span class="pms-status active">Super Admin</span>
                            <?php else: ?>
                                <span class="pms-status inactive">Staff</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="text-muted" style="font-size:13px;">
                                <?= $r['created_at'] ? date('M d, Y', strtotime($r['created_at'])) : '-' ?>
                            </span>
                        </td>

                        <td class="text-end">

                            <!-- Edit -->
                            <a href="edit_user.php?id=<?= $r['id'] ?>"
                               class="pms-action-btn me-1"
                               title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <!-- Delete -->
                            <?php if ($r['username'] != $_SESSION['user']): ?>
                                <a href="users.php?delete=<?= $r['id'] ?>"
                                   class="pms-action-btn pms-action-btn-danger"
                                   title="Delete"
                                   onclick="return confirm('Delete user: <?= htmlspecialchars($r['username']) ?> ?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="pms-action-btn" disabled
                                    title="Protected"
                                    data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="bi bi-trash"></i>
                                </span>
                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Footer / Pagination -->
        <div class="pms-footer">
            <?php
            $start = ($total > 0) ? $offset + 1 : 0;
            $end   = min($total, $offset + $per_page);
            ?>
            <div>
                Showing <?= $start ?> to <?= $end ?> of <?= $total ?> entries
            </div>

            <div class="pms-pagination">
                <a href="?page=<?= $page - 1 ?><?= $qs ?>"
                   class="pms-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                    Previous
                </a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?><?= $qs ?>"
                       class="pms-page-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <a href="?page=<?= $page + 1 ?><?= $qs ?>"
                   class="pms-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    Next
                </a>
            </div>
        </div>

    </div>
</div>

<?php include "includes/footer.php"; ?>