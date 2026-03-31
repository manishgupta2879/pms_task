<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('resources');

$res = $conn->query("SHOW CREATE VIEW resources");
if ($res) {
    print_r($res->fetch_assoc());
} else {
    $res = $conn->query("DESCRIBE resources");
    while ($row = $res->fetch_assoc()) print_r($row);
}
?>
