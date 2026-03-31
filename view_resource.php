<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');
include "includes/header.php";

$id = $_GET['id'] ?? 0;

if (!$id) {
    session_start();
    $_SESSION['error'] = "Invalid Resource ID.";
    header("Location: resources.php");
    exit();
}

$q = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id=?");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result();

if ($res->num_rows == 0) {
    session_start();
    $_SESSION['error'] = "Resource not found.";
    header("Location: resources.php");
    exit();
}
$user = $res->fetch_assoc();

// Handled Warnings with null coalescing operators
$name = htmlspecialchars($user['name'] ?? $user['username'] ?? 'Unknown');
$email = htmlspecialchars($user['email'] ?? 'No Email');
$type = htmlspecialchars($user['type'] ?? 'Regular');
$roleName = htmlspecialchars($user['role_name'] ?? 'No Role');
$status = htmlspecialchars($user['status'] ?? 'Active');
$joined = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';

// Fetch all approved/pending leaves for this resource to calculate availability
$leaves_q = $conn->query("SELECT from_date, to_date FROM leaves WHERE user_id=$id AND deleted_at IS NULL AND status != 'Rejected'");
$leave_periods = [];
while ($lv = $leaves_q->fetch_assoc()) {
    $leave_periods[] = [
        'from' => $lv['from_date'],
        'to' => $lv['to_date']
    ];
}

// Calculate Current Week (Monday to Sunday)
$today = new DateTime();
$monday = clone $today;

if ($monday->format('N') != 1) {
    $monday->modify('Monday this week');
}


$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = clone $monday;
    $d->modify("+$i days");

    // Check if this date falls within any leave period
    $dateString = $d->format('Y-m-d');
    $isAvailable = true;
    foreach ($leave_periods as $lp) {
        if ($dateString >= $lp['from'] && $dateString <= $lp['to']) {
            $isAvailable = false;
            break;
        }
    }

    $weekDays[] = [
        'dateObj' => $d,
        'dayName' => $d->format('D'), // Mon, Tue...
        'dateStr' => $d->format('M d'),
        'isAvailable' => $isAvailable,
        'isToday' => ($dateString == $today->format('Y-m-d'))
    ];
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row justify-content-center">

        <!-- FULL WIDTH CONTAINER -->
        <div class="col-12">

            <!-- Horizontal Resource Profile Card -->
            <div class="pms-panel mb-4">
                <div class="pms-panel-body p-4 d-flex align-items-center flex-wrap gap-4">

                    <!-- Avatar -->
                    <div
                        style="width: 70px; height: 70px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: bold; color: #64748b; flex-shrink: 0;">
                        <?= strtoupper(substr($name, 0, 1)) ?>
                    </div>

                    <!-- Details -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <h4 class="mb-0 fw-bold text-dark"><?= $name ?></h4>
                            <span class="pms-status <?= strtolower($status) == 'active' ? 'active' : 'inactive' ?>">
                                <?= $status ?>
                            </span>
                        </div>
                        <div class="text-muted mb-2"><i class="bi bi-envelope"></i> <?= $email ?></div>

                        <div class="d-flex flex-wrap gap-4 text-dark small">
                            <div><strong class="text-muted">Role:</strong> <?= $roleName ?></div>
                            <div><strong class="text-muted">Type:</strong> <?= $type ?></div>
                            <!-- <div><strong class="text-muted">Joined:</strong> <?= $joined ?></div> -->
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column gap-2" style="min-width: 160px;">
                        <a href="leave_management.php?id=<?= $id ?>" class="pms-btn-dark w-100 justify-content-center">
                            <i class="bi bi-calendar-event"></i> Manage Availability
                        </a>
                        <!-- <a href="add_resource.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </a> -->
                        <a href="resources.php" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                            Back to List
                        </a>
                    </div>

                </div>
            </div>

            <!-- Small / Compact Weekly Availability Calendar -->
            <div class="pms-panel mb-4">
                <div class="pms-panel-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-calendar2-week"></i> Weekly Availability Calendar
                    </div>
                    <div class="text-secondary small fw-bold">
                        <?= $weekDays[0]['dateObj']->format('M d') ?> - <?= $weekDays[6]['dateObj']->format('M d, Y') ?>
                    </div>
                </div>
                <div class="pms-panel-body py-3">

                    <!-- Compact Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle mb-0" style="table-layout: fixed;">
                            <thead class="bg-light">
                                <tr>
                                    <?php foreach ($weekDays as $day): ?>
                                        <th
                                            style="padding: 6px; <?= $day['isToday'] ? 'background-color: #e0f2fe; color: #0284c7; border-bottom: 2px solid #0284c7;' : '' ?>">
                                            <div style="font-size: 13px; font-weight: 600;"><?= $day['dayName'] ?></div>
                                            <div
                                                style="font-size: 11px; font-weight: normal; color: #64748b; margin-top: -2px;">
                                                <?= $day['dateStr'] ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($weekDays as $day): ?>
                                        <td
                                            style="padding: 10px; font-size: 18px; <?= $day['isToday'] ? 'background-color: #f8fafc;' : '' ?>">
                                            <?php if ($day['isAvailable']): ?>
                                                <i class="bi bi-check-circle-fill text-success" title="Available"></i>
                                            <?php else: ?>
                                                <i class="bi bi-x-circle-fill text-danger" title="Unavailable / Leave"></i>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tiny Legend -->
                    <div class="d-flex gap-4 mt-3" style="font-size: 12px; justify-content: flex-end;">
                        <div class="d-flex align-items-center gap-1 text-muted">
                            <i class="bi bi-check-circle-fill text-success"></i> Available
                        </div>
                        <div class="d-flex align-items-center gap-1 text-muted">
                            <i class="bi bi-x-circle-fill text-danger"></i> Unavailable (Leave)
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>