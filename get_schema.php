<?php
include "includes/config.php";
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
echo "---\n";
$res = $conn->query("DESCRIBE users");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "---\n";
$res = $conn->query("DESCRIBE leaves");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
