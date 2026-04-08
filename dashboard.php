<?php include "includes/config.php";

$result = $conn->query("SELECT COUNT(*) AS total_active_orders FROM orders WHERE status = 'active' AND deleted_at IS NULL");
$row = $result->fetch_assoc();
$totalActiveOrders = $row['total_active_orders'];

$result = $conn->query("
    SELECT COUNT(*) AS orders_due_this_week
    FROM orders
    WHERE
        status != 'completed'
        AND deleted_at IS NULL
        AND YEARWEEK(deadline, 1) = YEARWEEK(CURDATE(), 1)
");
$row = $result->fetch_assoc();
$orders_due_this_week = $row['orders_due_this_week'];

$result = $conn->query("
    SELECT COUNT(*) AS overdue_tasks
    FROM tasks t
    JOIN orders o ON t.order_id = o.id
    WHERE t.status != 'completed'
    AND DATE(o.deadline) < CURDATE()
");
$row = $result->fetch_assoc();
$overdue_tasks = $row['overdue_tasks'];

$urgent_tasks = $conn->query("
    SELECT 
        t.task_name,
        t.est_time,
        t.priority, 
        o.order_no, 
        u.name AS assigned_to, 
        DATE(o.deadline) AS due_date, 
        o.status
    FROM tasks t
    LEFT JOIN orders o ON t.order_id = o.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.priority = 'high' AND t.status <> 'completed'
    ORDER BY o.deadline ASC;
");

$result = $conn->query("
    SELECT 
        t.task_name,
        o.order_no,
        u.name AS assigned_to,
        DAYNAME(o.deadline) AS deadline_day,
        o.deadline
    FROM tasks t
    JOIN orders o ON t.order_id = o.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE 
        t.status != 'completed'
        AND o.deadline >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        AND o.deadline <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)
    ORDER BY 
        FIELD(DAYNAME(o.deadline), 
            'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
        ),
        o.deadline ASC
");

$weekly_tasks = [
    'Mon' => [],
    'Tue' => [],
    'Wed' => [],
    'Thu' => [],
    'Fri' => [],
    'Sat' => [],
    'Sun' => []
];

$dayMap = [
    'Monday' => 'Mon',
    'Tuesday' => 'Tue',
    'Wednesday' => 'Wed',
    'Thursday' => 'Thu',
    'Friday' => 'Fri',
    'Saturday' => 'Sat',
    'Sunday' => 'Sun'
];

while ($task = $result->fetch_assoc()) {
    $dayKey = $dayMap[$task['deadline_day']] ?? null;
    if ($dayKey) {
        $taskName = $task['order_no'] ? "Order #{$task['order_no']} {$task['task_name']}" : $task['task_name'];
        $weekly_tasks[$dayKey][] = [
            'name' => $taskName,
            'assigned_to' => $task['assigned_to'] ?? 'Unassigned'
        ];
    }
}


include "includes/header.php"; ?>
<!-- <div class="card">
    <h1>Welcome <?php echo $_SESSION['user']; ?></h1>
    <p>Role: <?php echo $_SESSION['role']; ?></p>
</div> -->
<?php if ($_SESSION['role'] == 'super-admin'): ?>
    <div class="">
        <div class="row g-4">
            <!-- Total Active Orders -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="">
                            <h6 class="card-title text-nowrap overflow-hidden text-truncate">Total Active Orders</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold">
                                    <?php echo $totalActiveOrders ?? 0; ?>
                                </h2>
                                <i class="bi bi-bag-check fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Due This Week -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div>
                            <h6 class="card-title text-nowrap overflow-hidden text-truncate">Orders Due This Week</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold">
                                    <?php echo $orders_due_this_week ?? 0; ?>
                                </h2>
                                <i class="bi bi-calendar-week fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Tasks -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card text-white bg-danger shadow-sm">
                    <div class="card-body">
                        <div>
                            <h6 class="card-title text-nowrap overflow-hidden text-truncate">Overdue Tasks</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold">
                                    <?php echo $overdue_tasks ?? 0; ?>
                                </h2>
                                <i class="bi bi-exclamation-triangle fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Utilization -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card text-white bg-dark shadow-sm">
                    <div class="card-body">
                        <div>
                            <h6 class="card-title text-nowrap overflow-hidden text-truncate">Staff Utilization</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold">
                                    <?php echo $staff_utilization ?? 0; ?>%
                                </h2>
                                <i class="bi bi-people fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Weekly Task Timeline -->
        <div class="mt-4">
            <div class="pms-panel">
                <div class="pms-panel-header">
                    <i class="bi bi-calendar-week me-2"></i>Weekly Task Timeline (<?php echo date('M j,Y', strtotime('this week')); ?> to  <?php echo date('M j,Y', strtotime('this week + 6 days')); ?>)
                </div>
                <div style="overflow-x: auto;">
                    <table class="pms-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Monday</th>
                                <th style="width: 100px;">Tuesday</th>
                                <th style="width: 100px;">Wednesday</th>
                                <th style="width: 100px;">Thursday</th>
                                <th style="width: 100px;">Friday</th>
                                <th style="width: 100px;">Saturday</th>
                                <th style="width: 100px;">Sunday</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Example structure: $weekly_tasks[day] = array of tasks
                                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                // $weekly_tasks = [
                                //     'Mon' => [
                                //         ['name' => 'Order #101 Packing', 'assigned_to' => 'Rahul'],
                                //         ['name' => 'Inventory Check', 'assigned_to' => 'Amit']
                                //     ],
                                //     'Tue' => [
                                //         ['name' => 'Dispatch Order #102', 'assigned_to' => 'Neha']
                                //     ],
                                //     'Wed' => [],
                                //     'Thu' => [
                                //         ['name' => 'Client Follow-up', 'assigned_to' => 'Priya']
                                //     ],
                                //     'Fri' => [
                                //         ['name' => 'Weekly Report', 'assigned_to' => 'Manager']
                                //     ]
                                // ];
                                foreach ($days as $day) {
                                    echo "<td>";

                                    if (!empty($weekly_tasks[$day])) {
                                        $count = 1;
                                        foreach ($weekly_tasks[$day] as $task) {
                                            echo "<div class='mb-2 p-2 bg-light rounded text-start'>";
                                            echo "<strong>#{$count}</strong> ";
                                            echo htmlspecialchars($task['name']) . "<br>";
                                            echo "<small class='text-muted'>Assigned: " . htmlspecialchars($task['assigned_to']) . "</small>";
                                            echo "</div>";
                                            $count++;
                                        }
                                    } else {
                                        echo "<span class='text-muted'>No Tasks</span>";
                                    }

                                    echo "</td>";
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="row g-4">
            <!-- Urgent Tasks Panel -->
            <div class="col-lg-8">
                <div class="pms-panel">
                    <div class="pms-panel-header">
                        <i class="bi bi-exclamation-triangle me-2"></i>Urgent Tasks
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="pms-table">
                            <thead>
                                <tr>
                                    <th>Task Name</th>
                                    <th>Order #</th>
                                    <th>Assigned To</th>
                                    <th>Due Date</th>
                                    <th>Est. Time</th>
                                    <th>Priority</th>
                                    <!-- <th style="width: 100px;">Status</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($urgent_tasks)) { ?>
                                    <?php foreach ($urgent_tasks as $task) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                            <td><?php echo htmlspecialchars($task['order_no']); ?></td>
                                            <td><?php echo htmlspecialchars($task['assigned_to']); ?></td>
                                            <td><?php echo date("d M Y", strtotime($task['due_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($task['est_time'] ? formatMinutes($task['est_time']) : 'N/A'); ?></td>
                                            <td>
                                                <span class="badge p-2 text-md bg-<?php echo $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </td>
                                            <!-- <td>
                                                <span class="badge p-2 text-md bg-<?php echo $task['status'] == 'pending' ? 'warning' : ($task['status'] == 'active' ? 'success' : 'secondary'); ?>">
                                                    <?php echo $task['status']; ?>
                                                </span>
                                            </td> -->
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No urgent tasks</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="pms-panel">
                    <div class="pms-panel-header">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </div>
                    <div class="pms-panel-body">
                        <div class="d-grid gap-2">
                            <a href="add_order.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> New Order
                            </a>

                            <a href="add_task_library.php" class="btn btn-success">
                                <i class="bi bi-list-task"></i>Add Task
                            </a>

                            <a href="add_resource.php" class="btn btn-dark">
                                <i class="bi bi-person-plus"></i> Add Resource
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="container d-flex justify-content-center align-items-center ">
    <div class="card welcome-card shadow-lg p-4 text-center" style="max-width: 420px; width: 100%;">
        
        <h2 class="mb-2">👋 Welcome, <?php echo $_SESSION['name']; ?>!</h2>
        <p class="text-secondary mb-4">
            Glad to have you back. Manage your work efficiently from your dashboard.
        </p>

        <div class="d-grid gap-2">
            <!-- <a href="profile.php" class="btn btn-primary">View Profile</a> -->
            <a href="my_task.php" class="btn btn-info text-white">My Tasks</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>

    </div>
</div>
<?php endif; ?>


<?php include "includes/footer.php"; ?>