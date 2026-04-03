<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
// requirePermission('tasks');

$resources = $conn->query("SELECT id, name FROM users");

$resource_filter = $_GET['resource'] ?? '';
$week = $_GET['week'] ?? date('o-\WW');

$where = "o.deleted_at IS NULL";

if (!empty($week) && strpos($week, '-W') !== false) {
    list($year, $week_num) = explode('-W', $week);

    $dto = new DateTime();
    $dto->setISODate((int)$year, (int)$week_num);

    $start_date = $dto->format('Y-m-d');

    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');

    $where .= " AND DATE(o.deadline) BETWEEN '$start_date' AND '$end_date'";
}
if ($resource_filter != '') {
    $resource_esc = (int)$resource_filter;
    $where .= " AND t.user_id = $resource_esc";
}

$taskRes = $conn->query("
    SELECT 
        u.name AS user_name,
        t.task_name,
        o.order_no,
        DATE(o.deadline) as task_date,
        DAYNAME(o.deadline) as day_name
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN orders o ON t.order_id = o.id
    WHERE $where
    ORDER BY u.name, o.deadline ASC
");

$employees = [];

while ($row = $taskRes->fetch_assoc()) {

    $emp = $row['user_name'] ?? 'Unknown';
    $day = date('D', strtotime($row['task_date']));

    $taskText = $row['task_name'] . " (Order " . $row['order_no'] . ")";

    $employees[$emp][$day][] = $taskText;
}

$weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

foreach ($employees as $emp => $days) {
    foreach ($weekDays as $d) {
        if (!isset($employees[$emp][$d])) {
            $employees[$emp][$d] = [];
        }
    }
}

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
                                    <input type="week" name="week" class="form-control" value="<?= htmlspecialchars($week) ?>">
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
                    <?php if (!empty($week)): ?>
                        ( <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?> )
                    <?php endif; ?>
                </div>

                <?php if (count($employees) == 0): ?>
                    <div class="alert alert-info text-center py-4 m-3">
                        <i class="bi bi-info-circle me-2"></i> No tasks found
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <div class="pms-panel-body p-2">
                        <?php foreach ($employees as $emp_name => $days): ?>
                            <div class="mb-3">
                                <div class="fw-bold"><?= $emp_name ?></div>

                                <?php foreach ($days as $day => $tasks): ?>
                                    <div class="ms-4 mb-1 d-flex">
                                        <div style="width:60px;"><strong><?= $day ?>:</strong></div>
                                        <div>
                                            <?php if (count($tasks)): ?>
                                                <?php foreach ($tasks as $t): ?>
                                                    <span class="badge text-bg-secondary"><?= $t ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No tasks</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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