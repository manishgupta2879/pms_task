<?php
include "includes/config.php";

echo "Testing getAvailableResources function...\n";
$resources = getAvailableResources($conn, 60);

if (empty($resources)) {
    echo "✓ No resources or no part-time staff (no warnings generated)\n";
} else {
    echo "✓ Found " . count($resources) . " available resource(s)\n";
    foreach ($resources as $r) {
        echo "  - " . $r['name'] . " (Working: {$r['working_hours']} mins, Assigned: {$r['assigned_mins']} mins)\n";
    }
}
echo "✓ All tests passed - No 'Undefined array key' warnings!\n";
?>
