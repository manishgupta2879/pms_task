<?php
include "includes/config.php";
include "includes/header.php";

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Check if the role is Superadmin
    $check_role = $conn->query("SELECT role_name, slug FROM roles WHERE id=$del_id");
    $role_data = $check_role->fetch_assoc();
    
    if ($role_data && (strtolower($role_data['role_name']) == 'superadmin' || $role_data['slug'] == 'super-admin')) {
        $_SESSION['error'] = "Cannot delete Superadmin role";
    } else {
        $conn->query("UPDATE roles SET deleted_at=NOW() WHERE id=$del_id");
        $_SESSION['success'] = "Role deleted successfully.";
    }
    header("Location: roles.php");
    exit();
}

$search   = $_GET['search'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25; // pms default style rows per page
$offset   = ($page - 1) * $per_page;

$where = "WHERE deleted_at IS NULL";
if ($search != '') {
    $where .= " AND role_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$count_res   = $conn->query("SELECT COUNT(*) as cnt FROM roles $where");
$total       = $count_res->fetch_assoc()['cnt'];
$total_pages = max(1, (int)ceil($total / $per_page));
if ($total_pages == 0) $total_pages = 1;

$res = $conn->query("SELECT * FROM roles $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");
$qs  = '&search=' . urlencode($search);
?>

<div class="pms-wrap">

    <div class="pms-panel">

        <div class="pms-controls">
            <div class="pms-controls-left">
                <h5 class="mb-0 fw-bold" style="color: #334155;">Roles</h5>
            </div>

            <div class="pms-controls-right">
                <form method="GET" class="d-flex gap-2">
                    <div class="pms-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="form-control ps-5" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="font-size: 13px; width: 220px;">
                    </div>
                </form>
                <a href="add_role.php" class="pms-btn-dark">
                    + New Role
                </a>
            </div>
        </div>

        <table class="pms-table">
            <thead>
                <tr>
                    <th style="width: 80px;">#</th>
                    <th>Role Name</th>
                    <th>Slug</th>
                    <th>Date Created</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No entries found</td>
                    </tr>
                <?php endif; ?>
                <?php $i = $offset + 1; ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="text-dark fw-medium"><?= htmlspecialchars($r['role_name']) ?></td>
                        <td class="text-muted"><code class="bg-light px-1 rounded"><?= htmlspecialchars($r['slug']) ?></code></td>
                        <td><?= date('Y-m-d H:i:s', strtotime($r['created_at'])) ?></td>
                        <td>
                            <span class="pms-status <?= $r['status'] == 'Active' ? 'active' : 'inactive' ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="add_role.php?id=<?= $r['id'] ?>" class="pms-action-btn me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (strtolower($r['role_name']) == 'superadmin' || $r['slug'] == 'super-admin'): ?>
                                
                            <?php else: ?>
                                <a href="roles.php?delete=<?= $r['id'] ?>" class="pms-action-btn pms-action-btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this role?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pms-footer">
            <?php
            $start = ($total > 0) ? $offset + 1 : 0;
            $end   = min($total, $offset + $per_page);
            ?>
            <div>Showing <?= $start ?> to <?= $end ?> of <?= $total ?> entries</div>

            <div class="pms-pagination">
                <a href="?page=<?= $page - 1 ?><?= $qs ?>" class="pms-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?><?= $qs ?>" class="pms-page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <a href="?page=<?= $page + 1 ?><?= $qs ?>" class="pms-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">Next</a>
            </div>
        </div>

    </div>
</div>

<?php include "includes/footer.php"; ?>