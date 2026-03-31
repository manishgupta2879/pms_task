<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');
include "includes/header.php";

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    $check_user = $conn->query("SELECT r.slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id=$del_id");
    $u_data = $check_user->fetch_assoc();

    if ($u_data && $u_data['slug'] == 'super-admin') {
        $_SESSION['error'] = "Cannot delete Superadmin using resource module";
    } else {
        $conn->query("UPDATE users SET deleted_at=NOW() WHERE id=$del_id");
        $_SESSION['success'] = "Resource deleted successfully.";
    }
    header("Location: resources.php");
    exit();
}

$search   = $_GET['search'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;


$where = "WHERE u.deleted_at IS NULL AND (r.slug != 'super-admin' OR r.slug IS NULL)";
if ($search != '') {
    $where .= " AND (u.name LIKE '%" . $conn->real_escape_string($search) . "%' OR u.email LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$count_sql = "SELECT COUNT(*) as cnt FROM users u LEFT JOIN roles r ON u.role_id = r.id $where";
$count_res = $conn->query($count_sql);
$total = $count_res->fetch_assoc()['cnt'];
$total_pages = max(1, (int)ceil($total / $per_page));

$sql = "SELECT u.*, r.role_name, r.slug as role_slug FROM users u  LEFT JOIN roles r ON u.role_id = r.id  $where  ORDER BY u.id DESC LIMIT $per_page OFFSET $offset";
$res = $conn->query($sql);
$qs  = '&search=' . urlencode($search);
?>

<div class="pms-wrap">

    <div class="pms-panel">

        <div class="pms-controls">
            <div class="pms-controls-left">
                <h5 class="mb-0 fw-bold" style="color: #334155;">Resources</h5>
            </div>

            <div class="pms-controls-right">
                <form method="GET" class="d-flex gap-2">
                    <div class="pms-search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="form-control ps-5" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="font-size: 13px; width: 220px;">
                    </div>
                </form>
                <a href="add_resource.php" class="pms-btn-dark">
                    + New Resource
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="pms-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">No resources found</td>
                        </tr>
                    <?php endif; ?>
                    <?php $i = $offset + 1; ?>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class="text-dark fw-medium"><?= htmlspecialchars($r['name'] ?? $r['username']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td>
                                <span class="badge border text-dark fw-normal" style="background: #f1f5f9; border-color: #cbd5e1 !important;">
                                    <?= $r['type'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['role_name'] ?? 'No Role') ?></td>
                            <td>
                                <?php $status = $r['status'] ?? 'Active'; ?>
                                <span class="pms-status <?= strtolower($status) == 'active' ? 'active' : 'inactive' ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="leave_management.php?id=<?= $r['id'] ?>" class="pms-action-btn me-1" title="Leave Management" style="color: #0284c7; border-color: #7dd3fc; background: #e0f2fe;">
                                    <i class="bi bi-calendar-event-fill"></i>
                                </a>

                                <a href="view_resource.php?id=<?= $r['id'] ?>" class="pms-action-btn me-1" title="Edit">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="add_resource.php?id=<?= $r['id'] ?>" class="pms-action-btn me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <?php if ($r['role_slug'] != 'super-admin'): ?>
                                    <a href="resources.php?delete=<?= $r['id'] ?>" class="pms-action-btn pms-action-btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this resource?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

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