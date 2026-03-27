<?php
include "includes/config.php";

$user_id = $_GET['id'] ?? 0;

if (!$user_id) {
    session_start();
    $_SESSION['error'] = "Invalid Resource ID.";
    header("Location: resources.php");
    exit();
}

// Fetch user data
$q = $conn->query("SELECT * FROM users WHERE id=" . (int)$user_id . " AND deleted_at IS NULL");
if ($q->num_rows == 0) {
    session_start();
    $_SESSION['error'] = "Resource not found.";
    header("Location: resources.php");
    exit();
}
$user = $q->fetch_assoc();
$name = htmlspecialchars($user['name'] ?? $user['username']);
$u_type = $user['type'];

// Handle Leave Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_leave'])) {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $remarks = trim($_POST['remarks']);

    // Basic validation
    $today = date('Y-m-d');

    if ($from_date <= $today) {
        session_start();
        $_SESSION['error'] = "Leave can only be applied for future dates (tomorrow onwards).";
    } elseif ($from_date > $to_date) {
        session_start();
        $_SESSION['error'] = "'To Date' must be greater than or equal to 'From Date'.";
    } else {
        $stmt = $conn->prepare("INSERT INTO leaves (user_id, from_date, to_date, remarks) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $from_date, $to_date, $remarks);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['success'] = "Leave added successfully.";
            header("Location: leave_management.php?id=" . $user_id);
            exit();
        } else {
            session_start();
            $_SESSION['error'] = "Failed to add leave.";
        }
    }
}

// Handle Leave Deletion
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $check_leave = $conn->query("SELECT from_date FROM leaves WHERE id=$del_id AND user_id=$user_id");
    
    if ($check_leave->num_rows > 0) {
        $ld = $check_leave->fetch_assoc();
        if ($ld['from_date'] > date('Y-m-d')) {
            $conn->query("UPDATE leaves SET deleted_at=NOW() WHERE id=$del_id");
            session_start();
            $_SESSION['success'] = "Leave deleted successfully.";
        } else {
            session_start();
            $_SESSION['error'] = "Cannot remove leave records that have already started.";
        }
    }
    header("Location: leave_management.php?id=" . $user_id);
    exit();
}

include "includes/header.php";

// -----------------------------------------------------
// Server-Side Pagination for Leave History
// -----------------------------------------------------
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 2;
$offset   = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as cnt FROM leaves WHERE user_id=$user_id AND deleted_at IS NULL";
$count_res = $conn->query($count_sql);
$total     = $count_res->fetch_assoc()['cnt'];
$total_pages = max(1, (int)ceil($total / $per_page));

$sql_leaves = "SELECT * FROM leaves WHERE user_id=$user_id AND deleted_at IS NULL ORDER BY from_date DESC LIMIT $per_page OFFSET $offset";
$res_leaves = $conn->query($sql_leaves);
$qs = '&id=' . $user_id;

?>

<div class="pms-wrap">
    <div class="row">
        <!-- LEFT COLUMN (col-4): Profile Details & Leave Form -->
        <div class="col-lg-4 col-md-5">

            <!-- Add Leave Record Form -->
            <div class="pms-panel mb-4">
                <div class="pms-panel-header">
                    Apply for Leave
                </div>
                <?php if ($u_type == 'Part-time' || $u_type == 'Regular'): ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="pms-panel-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="pms-form-label"><span class="text-danger">*</span> From Date</label>
                                    <input type="date" name="from_date" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                    <div class="invalid-feedback">Please select a future start date.</div>
                                </div>
                                <div class="col-6">
                                    <label class="pms-form-label"><span class="text-danger">*</span> To Date</label>
                                    <input type="date" name="to_date" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                    <div class="invalid-feedback">Please select a valid end date.</div>
                                </div>
                                <div class="col-12">
                                    <label class="pms-form-label">Remarks / Reason</label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Enter reason for leave..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="pms-panel-footer">
                            <button type="submit" name="save_leave" class="pms-btn-dark justify-content-center">
                                <i class="bi bi-calendar-plus"></i> Submit Leave
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="pms-panel-body">
                        <div class="alert alert-warning mb-0 text-center">
                            Leave management is mostly applicable for Part-time / Regular resources.
                        </div>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Resource Profile Detail (Small) -->
            <div class="pms-panel mb-4">
                <div class="pms-panel-body d-flex flex-column py-4 text-center">
                    <div class="d-flex gap-3">
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; color: #64748b; margin-bottom: 15px;">
                            <?= strtoupper(substr($name, 0, 1)) ?>
                        </div>
                        <div class="text-start">
                            <h5 class="mb-1 fw-bold text-dark" style="font-size: 16px;"><?= $name ?></h5>
                            <div class="text-muted small mb-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                            </div>
                            <span class="badge border text-dark fw-normal" style="background: #f1f5f9; border-color: #cbd5e1 !important; font-size: 12px;">
                                <?= $u_type ?>
                            </span>
                        </div>

                    </div>

                </div>
            </div>


        </div>

        <!-- RIGHT COLUMN (col-8): Server-side Paginated Leave History Table -->
        <div class="col-lg-8 col-md-7">

            <div class="pms-panel">
                <div class="pms-panel-header d-flex justify-content-between align-items-center">
                    <span>Leave History</span>
                    <a href="resources.php" class="btn btn-outline-secondary btn-sm">Back to Resources</a>
                </div>

                <table class="pms-table">
                    <thead>
                        <tr>
                            <th>From Date</th>
                            <th>To Date</th>
                            <th>Duration</th>
                            <th>Remarks</th>
                            <th>Applied On</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No leave records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($leave = $res_leaves->fetch_assoc()):
                                $fDate = new DateTime($leave['from_date']);
                                $tDate = new DateTime($leave['to_date']);
                                // Inclusive days: from 1st to 2nd is 2 days
                                $days = $fDate->diff($tDate)->days + 1;
                            ?>
                                <tr>
                                    <td class="text-dark fw-medium"><?= $fDate->format('M d, Y') ?></td>
                                    <td class="text-dark fw-medium"><?= $tDate->format('M d, Y') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $days ?> Day<?= $days > 1 ? 's' : '' ?></span>
                                    </td>
                                    <td class="text-muted small"><?= nl2br(htmlspecialchars($leave['remarks'])) ?: '<em class="text-light-muted">No remarks</em>' ?></td>
                                    <td class="text-muted" style="font-size: 12px;">
                                        <?= date('M d, Y', strtotime($leave['created_at'])) ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($leave['from_date'] > date('Y-m-d')): ?>
                                        <a href="leave_management.php?id=<?= $user_id ?>&delete=<?= $leave['id'] ?>" class="pms-action-btn pms-action-btn-danger" title="Delete Leave" onclick="return confirm('Are you sure you want to remove this leave record?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small">Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pms-footer">
                    <?php
                    $start = ($total > 0) ? $offset + 1 : 0;
                    $end   = min($total, $offset + $per_page);
                    ?>
                    <div>Showing <?= $start ?> to <?= $end ?> of <?= $total ?> leaves</div>

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
    </div>
</div>

<script>
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php include "includes/footer.php"; ?>