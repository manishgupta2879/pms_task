<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
// requirePermission('tasks');

// reseources
$resources = $conn->query("SELECT id, name FROM users");

$resource_filter = $_GET['resource'] ?? '';
$date = $_GET['date'] ?? '';
// $date = $_GET['date'] ?? date("Y-m-d");

$limit = 10;
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;

// $total = $countRes->fetch_assoc()['total'];
// $total_pages = max(1, ceil($total / $limit));
$total = 0;
$total_pages = 0;

$where = "o.deleted_at IS NULL";

// if ($search != '') {
//     $search_esc = $conn->real_escape_string($search);
//     $where .= " AND (o.order_no LIKE '%$search_esc%' OR o.product LIKE '%$search_esc%')";
// }

if ($resource_filter != '') {
    $resource_esc = (int)$resource_filter;
    $where .= " AND t.user_id = $resource_esc";
}

// if ($priority != '') {
//     $priority_esc = $conn->real_escape_string($priority);
//     if($priority_esc == 'low'){
//         $where .= " AND (t.priority = '$priority_esc' OR t.priority IS NULL)";  
//     }
//     else{
//         $where .= " AND t.priority = '$priority_esc'";
//     }
// }

// if ($date != '') {
//     $date_esc = $conn->real_escape_string($date);
//     $where .= " AND DATE(o.deadline) = '$date_esc'";
// }

$taskRes = $conn->query("
    SELECT 
    t.*,
    u.id AS user_id,
    u.name AS user_name,
    ab.id AS assigned_by_id,
    ab.name AS assigned_by_name,
    o.deadline,
    o.id AS order_id,
    o.order_no

FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users ab ON t.assigned_by = ab.id
LEFT JOIN orders o ON t.order_id = o.id
WHERE $where
ORDER BY t.updated_at DESC
LIMIT $limit OFFSET $offset");
echo "<pre>";
print_r($taskRes->fetch_assoc());
echo "</pre>";
exit();
$employees = [
    "John" => [
        "Mon" => ["Assembly (Order 10234)", "Testing (Order 10230)", "Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Tue" => ["Testing (Order 10230)", "Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Wed" => ["Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Thu" => ["Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Fri" => ["Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Sat" => ["Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Sun" => ["Dispatch (Order 10225)"]
    ],
    "Sarah" => [
        "Mon" => ["QA (Order 10231)", "Testing (Order 10230)", "Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Tue" => ["Testing (Order 10234)", "Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Wed" => ["Packaging (Order 10229)", "Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Thu" => ["Dispatch (Order 10228)", "Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Fri" => ["Dispatch (Order 10227)", "Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Sat" => ["Dispatch (Order 10226)", "Dispatch (Order 10225)"],
        "Sun" => ["Dispatch (Order 10225)"]
    ]
];
include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-lg-4 col-md-5">
            <div class="pms-panel mb-3">
                <div class="pms-panel-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="true">
                    <span>
                        <i class="bi bi-funnel me-2"></i>Filter
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div id="filterPanel" class="collapse show">
                    <form method="GET">
                        <div class="pms-panel-body">
                            <div class="row g-3">
                                <div class="">
                                    <label class="pms-form-label">Resources</label>
                                    <select name="resource" class="form-select select2">
                                        <option value="">All Resources</option>
                                        <?php foreach ($resources as $resource): ?>
                                            <option value="<?= $resource['id'] ?>" <?= $resource_filter == $resource['id'] ? 'selected' : '' ?>><?= $resource['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="">
                                    <label class="pms-form-label">Week Filter</label>
                                    <input type="date" name="deadline" class="form-control" value="<?= htmlspecialchars($deadline) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="pms-panel-footer d-flex gap-2 text-end">
                            <a href="weakly-report.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                            <button type="submit" class="pms-btn-dark btn-sm">
                                <i class="bi bi-funnel"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-md-7">
            <div class="pms-panel mb-2">
                <div class="pms-panel-header">
                    <i class="bi bi-list-check me-2"></i>Scheduling / Workload
                </div>

                <?php if (count($employees) == 0): ?>
                    <div class="alert alert-info text-center py-4 m-3">
                        <i class="bi bi-info-circle me-2"></i> No tasks found
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <div class="pms-panel-body p-2">
                            <?php foreach ($employees as $employee => $schedule): ?>
                                <div class="mb-2">
                                    <div class="col me-2">
                                        <?php echo $employee; ?>
                                    </div>
                                    <div class="">
                                        <?php foreach ($schedule as $day => $tasks): ?>
                                            <div class="ms-5 mb-2 d-flex">
                                                <div class=""><?php echo $day; ?>:&nbsp;</div>
                                                <div>
                                                    <?php foreach ($tasks as $task): ?>
                                                        <span class="badge text-bg-secondary"><?php echo $task; ?></span>
                                                    <?php endforeach; ?>
                                                </div>

                                            </div>

                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                <?php endif; ?>
            </div>
            <!-- <div class="pms-panel mb-2">
                <div class="pms-panel-header">
                    <h5 class="mb-0 fw-bold" style="color: #334155;"><i class="bi bi-cpu-fill me-2"></i>Conflict Alerts
                    </h5>
                </div>
                <div class="pms-panel-body">
                    <div style="overflow-x: auto;">
                        <div>- John overbooked on Tuesday (6h assigned, 4h available) </div>
                        <div>- John overbooked on Tuesday (6h assigned, 4h available) </div>
                        <div>- John overbooked on Tuesday (6h assigned, 4h available) </div>
                        <div>- John overbooked on Tuesday (6h assigned, 4h available) </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>



</div>
</div>

<?php include "includes/footer.php"; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Search...",
            width: '100%'
        });
    });
</script>