<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');

$res = $conn->query("DESCRIBE resources");
if ($res) {
    echo "DESCRIBE resources:\n";
    while ($row = $res->fetch_assoc()) print_r($row);
} else {
    echo "resources table/view not found via DESCRIBE.\n";
}
?>
