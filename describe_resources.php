<?php
include "includes/config.php";
$res = $conn->query("DESCRIBE resources");
if ($res) {
    echo "DESCRIBE resources:\n";
    while ($row = $res->fetch_assoc()) print_r($row);
} else {
    echo "resources table/view not found via DESCRIBE.\n";
}
?>
