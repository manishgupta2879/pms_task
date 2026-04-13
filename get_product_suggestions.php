<?php
include "includes/config.php";
include "includes/rbac.php";

// Require authentication
requireAuth();

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

// Fetch unique products from order_items that match the query
// Using DISTINCT to avoid duplicate suggestions
$search_term = '%' . $conn->real_escape_string($query) . '%';

$sql = "SELECT DISTINCT product FROM order_items WHERE product LIKE '$search_term' ORDER BY product ASC LIMIT 20";

$result = $conn->query($sql);

$suggestions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['product'];
    }
}

echo json_encode($suggestions);
exit();
?>
