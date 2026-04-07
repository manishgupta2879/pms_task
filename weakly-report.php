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
    $dto->setISODate((int) $year, (int) $week_num);

    $start_date = $dto->format('Y-m-d');

    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');

    $where .= " AND DATE(o.deadline) BETWEEN '$start_date' AND '$end_date'";
}
if ($resource_filter != '') {
    $resource_esc = (int) $resource_filter;
    $where .= " AND t.user_id = $resource_esc";
}

$taskRes = $conn->query("
    SELECT 
        u.id as user_id,
        u.name AS user_name,
        t.id,
        t.task_name,
        t.status,
        t.est_time,
        t.start_time,
        t.end_time,
        o.order_no,
        o.deadline,
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
    $emp_id = $row['user_id'];
    $day = date('l', strtotime($row['task_date']));
    $dayDate = date('M d, Y', strtotime($row['task_date']));

    // Calculate on-time/delay status using exact logic from view_order.php
    $delayText = '-';
    $statusColor = '#e5e7eb'; // gray for nothing

    if ($row['status'] == 'completed' && $row['start_time'] && $row['end_time'] && $row['est_time']) {

        $start = new DateTime($row['start_time']);
        $end = new DateTime($row['end_time']);

        $start->setTime($start->format('H'), $start->format('i'), 0);
        $end->setTime($end->format('H'), $end->format('i'), 0);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $actualMinutes = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
        $estMinutes = (int)$row['est_time']; // est_time is already in minutes

        if ($actualMinutes > $estMinutes) {
            
            $diffMinutesTotal = $actualMinutes - $estMinutes;
            $delayText = formatMinutes($diffMinutesTotal) . ' Delay';
            $statusColor = '#fecaca'; 
        } else {
            $delayText = '✓';
            $statusColor = '#bbf7d0';
        }
    }

    $taskData = [
        'task_name' => $row['task_name'],
        'order_no' => $row['order_no'],
        'delayText' => $delayText,
        'statusColor' => $statusColor,
        'est_time' => $row['est_time'], 
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'deadline' => $row['deadline'],
        'task_date' => $dayDate
    ];

    if (!isset($employees[$emp])) {
        $employees[$emp] = ['user_id' => $emp_id, 'days' => []];
    }

    if (!isset($employees[$emp]['days'][$day])) {
        $employees[$emp]['days'][$day] = [];
    }

    $employees[$emp]['days'][$day][] = $taskData;
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row g-3">
        <!-- Filter Panel -->
        <div class="col-md-4 col-lg-3">
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
                                    <select name="resource" class="form-select" id="select2">
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

        <!-- Scheduling/Workload Panel -->
        <div class="col-md-8 col-lg-9">
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

                            <?php
                            $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                            foreach ($employees as $emp_name => $empData):
                                $days = is_array($empData) && isset($empData['days']) ? $empData['days'] : [];
                                $emp_id = $empData['user_id'] ?? null;

                                $initials = '';
                                $parts = explode(' ', $emp_name);
                                foreach ($parts as $part) {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }

                                $avatarColors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#06b6d4'];
                                $avatarColor = $avatarColors[crc32($emp_id ?? $emp_name) % count($avatarColors)];
                            ?>
                                <!-- Team Member Section -->
                                <div>

                                    <!-- User Header -->
                                    <div style="padding: 4px;border-bottom: 2px solid #e2e8f0; display: flex; align-items: center; gap: 12px;">
                                        <div style="
                                                width: 32px;
                                                height: 32px;
                                                border-radius: 50%;
                                                background-color: <?= $avatarColor ?>;
                                                color: white;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                font-weight: 700;
                                                font-size: 18px;
                                                flex-shrink: 0;
                                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                            ">
                                            <?= $initials ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #1e293b; font-size: 14px;">
                                                <?= htmlspecialchars($emp_name) ?>
                                            </div>
                                            <div style="font-size: 10px; color: #64748b;">Resource</div>
                                        </div>
                                    </div>

                                    <!-- Days and Tasks -->
                                    <div style="padding: 16px;">
                                        <?php foreach ($weekDays as $day):
                                            $dayTasks = isset($days[$day]) ? $days[$day] : [];
                                        ?>
                                            <?php if (!empty($dayTasks)): ?>
                                                <div class="row mb-2">
                                                    <div class="col-3">
                                                        <!-- Day Header -->
                                                        <div>
                                                            <?= $day ?> <span style="color: #64748b; font-weight: 400; font-size: 12px;">( <?= $dayTasks[0]['task_date'] ?? '' ?> )</span> :-
                                                        </div>
                                                    </div>
                                                    <div class="col-9">
                                                        <!-- Tasks -->
                                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                                            <?php foreach ($dayTasks as $task): ?>
                                                                <div style="
                                                                    padding: 4px;
                                                                    background-color: <?php //$task['statusColor'] 
                                                                                        ?>;
                                                                    border: 1px solid <?php
                                                                                        if ($task['delayText'] === '✓') echo '#10b981';
                                                                                        elseif ($task['delayText'] === '-') echo '#9ca3af';
                                                                                        else echo '#ef4444';
                                                                                        ?>;
                                                                    border-radius: 6px;
                                                                    border-left: 4px solid;
                                                                    border-left-color: <?php
                                                                                        if ($task['delayText'] === '✓') echo '#10b981';
                                                                                        elseif ($task['delayText'] === '-') echo '#9ca3af';
                                                                                        else echo '#ef4444';
                                                                                        ?>;
                                                                    display: flex;
                                                                    justify-content: space-between;
                                                                    align-items: center;
                                                                    gap: 10px;
                                                                ">
                                                                    <div style="flex: 1;">
                                                                        <div style="font-weight: 600; color: #1e293b; font-size: 13px; margin-bottom: 4px;">

                                                                        </div>
                                                                        <div style="font-size: 11px; color: #64748b; display: flex; gap: 12px; flex-wrap: wrap;">
                                                                            <span><?= htmlspecialchars($task['task_name']) ?></span>
                                                                            <span>Order: <strong><?= htmlspecialchars($task['order_no']) ?></strong></span>
                                                                            <span>Est: <strong><?= formatMinutes($task['est_time']) ?></strong></span>
                                                                        </div>
                                                                    </div>
                                                                    <div style="
                                                                        font-size: 12px;
                                                                        color: #1e293b;
                                                                        white-space: nowrap;
                                                                        text-align: center;
                                                                    ">
                                                                        <?php if ($task['delayText'] === '✓'): ?>
                                                                            <span style="color: #10b981;">✓ On Time</span>
                                                                        <?php elseif ($task['delayText'] === '-'): ?>
                                                                            <span style="color: #9ca3af;">-</span>
                                                                        <?php else: ?>
                                                                            <span style="color: #ef4444;"><?= htmlspecialchars($task['delayText']) ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>


                                                </div>
                                            <?php endif; ?>
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
                    <i class="bi bi-list-check me-2"></i>Scheduling / Workload Table 2
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
                        <div class="pms-panel-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered" style="margin-bottom: 0; border-collapse: collapse; min-width: 1000px;">
                                    <thead>
                                        <tr style="background-color: #f1f5f9;">
                                            <th style="width: 120px; padding: 12px; font-weight: 600; text-align: left; border: 1px solid #e2e8f0; position: sticky; left: 0; background-color: #f1f5f9; z-index: 1;">TEAM MEMBER</th>
                                            <?php
                                            $currentWeekDays = [];
                                            $dto = new DateTime();
                                            $dto->setISODate((int) $year, (int) $week_num);

                                            for ($i = 0; $i < 7; $i++) {
                                                $dayNum = $dto->format('d');
                                                $dayName = $dto->format('l');
                                                $currentWeekDays[$dayName] = $dayNum;
                                            ?>
                                                <th style="width: 140px; padding: 12px; text-align: center; border: 1px solid #e2e8f0; font-weight: 600;">
                                                    <div style="font-size: 12px; color: #64748b; font-weight: 500;"><?= $dayName ?></div>
                                                    <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?= $dayNum ?></div>
                                                </th>
                                            <?php
                                                $dto->modify('+1 day');
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $rowColors = ['#f0f9ff', '#fdf2f8', '#f0fdf4', '#fffbeb', '#f5f3ff', '#f0fdfa', '#fef3c7'];
                                        $colorIndex = 0;

                                        foreach ($employees as $emp_name => $empData):
                                            $emp_id = $empData['user_id'] ?? null;
                                            $days = isset($empData['days']) ? $empData['days'] : [];

                                            // Generate avatar initials
                                            $initials = '';
                                            $parts = explode(' ', $emp_name);
                                            foreach ($parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }

                                            // Generate consistent avatar color
                                            $avatarColors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#06b6d4'];
                                            $avatarColor = $avatarColors[crc32($emp_id ?? $emp_name) % count($avatarColors)];
                                        ?>
                                            <tr>
                                                <td style="padding: 12px; border: 1px solid #e2e8f0; vertical-align: middle; background-color: <?= $rowColors[$colorIndex % count($rowColors)] ?>; position: sticky; left: 0; z-index: 0;">
                                                    <div style="display: flex; align-items: center; gap: 10px;">

                                                    
                                                    <div style="
                                                        width: 36px;
                                                        height: 36px;
                                                        border-radius: 50%;
                                                        background-color: <?= $avatarColor ?>;
                                                        color: white;
                                                        display: flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        font-weight: 700;
                                                        font-size: 14px;
                                                        flex-shrink: 0;
                                                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                                    ">
                                                        <?= $initials ?>
                                                    </div>
                                                    <div style="font-weight: 600; color: #1e293b; font-size: 13px;"><?= htmlspecialchars($emp_name) ?></div>
                                                    </div>
                                                </td>
                                                <?php foreach ($weekDays as $day):
                                                    $dayTasks = isset($days[$day]) ? $days[$day] : [];

                                                ?>
                                                    <td style="padding: 10px; border: 1px solid #e2e8f0; vertical-align: top; background-color: <?= $rowColors[$colorIndex % count($rowColors)] ?>;">
                                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                                            <?php if (!empty($dayTasks)): ?>
                                                                <?php foreach ($dayTasks as $task): ?>
                                                                    <div style="
                                                                        padding: 10px;
                                                                        background-color: white;
                                                                        border-left: 4px solid;
                                                                        border-left-color: <?php
                                                                                            if ($task['delayText'] === '✓') echo '#10b981';
                                                                                            elseif ($task['delayText'] === '-') echo '#9ca3af';
                                                                                            else echo '#ef4444';
                                                                                            ?>;
                                                                        border-radius: 4px;
                                                                        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
                                                                        font-size: 11px;
                                                                        min-height: 80px;
                                                                        display: flex;
                                                                        flex-direction: column;
                                                                    ">
                                                                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px; line-height: 1.3;">
                                                                            <?= htmlspecialchars(substr($task['task_name'], 0, 50)) ?>
                                                                        </div>
                                                                        <div style="color: #64748b; font-size: 10px; margin-bottom: 3px;">
                                                                            Order: <strong><?= htmlspecialchars($task['order_no']) ?></strong>
                                                                        </div>
                                                                        <?php if ($task['est_time']): ?>
                                                                            <div style="color: #64748b; font-size: 10px; margin-bottom: 6px;">
                                                                                Estimate: <strong><?= formatMinutes($task['est_time']) ?></strong>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div style="margin-top: auto;">
                                                                            <span style="
                                                                                padding: 4px 6px;
                                                                                background-color: <?= $task['statusColor'] ?>;
                                                                                color: #1e293b;
                                                                                border-radius: 3px;
                                                                                display: inline-block;
                                                                                font-weight: 500;
                                                                                font-size: 9px;
                                                                                text-transform: uppercase;
                                                                            ">
                                                                                <?= $task['delayText']   ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <div style="color: #cbd5e1; font-size: 12px; text-align: center; padding: 20px 5px;">-</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php
                                            $colorIndex++;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                <?php endif; ?>
            </div> -->
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>