<?php include "includes/config.php";
include "includes/header.php"; ?>

<!-- <div class="card">
    <h1>Welcome <?php echo $_SESSION['user']; ?></h1>
    <p>Role: <?php echo $_SESSION['role']; ?></p>
</div> -->

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
                                <?php echo $total_active_orders ?? 0; ?>
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
                                <?php echo $orders_due_week ?? 0; ?>
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
    <div class=" mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Weekly Task Timeline</h5>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Example structure: $weekly_tasks[day] = array of tasks
                                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
                                $weekly_tasks = [
                                    'Mon' => [
                                        ['name' => 'Order #101 Packing', 'assigned_to' => 'Rahul'],
                                        ['name' => 'Inventory Check', 'assigned_to' => 'Amit']
                                    ],
                                    'Tue' => [
                                        ['name' => 'Dispatch Order #102', 'assigned_to' => 'Neha']
                                    ],
                                    'Wed' => [],
                                    'Thu' => [
                                        ['name' => 'Client Follow-up', 'assigned_to' => 'Priya']
                                    ],
                                    'Fri' => [
                                        ['name' => 'Weekly Report', 'assigned_to' => 'Manager']
                                    ]
                                ];
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
            <?php $urgent_tasks = [
                [
                    'name' => 'Packing Order #101',
                    'order_no' => '101',
                    'assigned_to' => 'Rahul',
                    'due_date' => '2026-03-28',
                    'status' => 'Pending'
                ],
                [
                    'name' => 'Dispatch Order #102',
                    'order_no' => '102',
                    'assigned_to' => 'Neha',
                    'due_date' => '2026-03-27',
                    'status' => 'Completed'
                ]
            ]; ?>
            <!-- Urgent Tasks Panel -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Urgent Tasks</h5>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-danger text-center">
                                    <tr>
                                        <th>Task Name</th>
                                        <th>Order #</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($urgent_tasks)) { ?>
                                        <?php foreach ($urgent_tasks as $task) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($task['name']); ?></td>
                                                <td><?php echo htmlspecialchars($task['order_no']); ?></td>
                                                <td><?php echo htmlspecialchars($task['assigned_to']); ?></td>
                                                <td><?php echo date("d M Y", strtotime($task['due_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $task['status'] == 'Pending' ? 'warning' : ($task['status'] == 'Completed' ? 'success' : 'secondary'); ?>">
                                                        <?php echo $task['status']; ?>
                                                    </span>
                                                </td>
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
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>

                        <div class="d-grid gap-3 mt-3">
                            <a href="add_order.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> + New Order
                            </a>

                            <a href="add_task.php" class="btn btn-success">
                                <i class="bi bi-list-task"></i> + Add Task
                            </a>

                            <a href="add_resource.php" class="btn btn-dark">
                                <i class="bi bi-person-plus"></i> + Add Resource
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include "includes/footer.php"; ?>