<?php
session_start();
// $conn = new mysqli("localhost","u716023235_pms","02X/nWXPJjJ+","u716023235_pms");
$conn = new mysqli("localhost", "root", "", "u716023235_pms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function formatMinutes($total_mins)
{
    $total_mins = (int) $total_mins;
    if ($total_mins <= 0)
        return "0m";
    $hrs = floor($total_mins / 60);
    $mins = $total_mins % 60;
    $out = "";
    if ($hrs > 0)
        $out .= "{$hrs}h ";
    if ($mins > 0 || $hrs == 0)
        $out .= "{$mins}m";
    return trim($out);
}

function getAvailableResources($conn, $task_minutes = 0, $date = null)
{
    if (!$date)
        $date = date('Y-m-d');

    // Get staff role ID
    $role_res = $conn->query("SELECT id FROM roles WHERE slug = 'staff' OR role_name LIKE 'Staff' LIMIT 1");
    $staff_role_id = ($role_res && $role_res->num_rows > 0) ? $role_res->fetch_assoc()['id'] : 0;

    if (!$staff_role_id)
        return [];

    $sql = "SELECT u.id, u.name, r.role_name as role, u.type, u.working_hours,
            COALESCE(SUM(CASE WHEN t.status != 'completed' THEN t.est_time ELSE 0 END), 0) as assigned_mins
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            LEFT JOIN tasks t ON u.id = t.user_id
            WHERE u.deleted_at IS NULL 
            AND u.role_id = $staff_role_id
            GROUP BY u.id, u.name, r.role_name, u.type, u.working_hours
            ";

    // Filter by Leave using the given $date
    $sql .= " AND NOT EXISTS (
                SELECT 1 FROM leaves l 
                WHERE l.user_id = u.id 
                AND l.deleted_at IS NULL 
                AND l.status != 'Rejected'
                AND '$date' BETWEEN l.from_date AND l.to_date
            )";

    $res = $conn->query($sql);
    $resources = [];
    if ($res) {
        while ($u = $res->fetch_assoc()) {
            $is_available = true;

            // Check availability for Part-time resources
            if ($u['type'] == 'Part-time') {
                $working_hours = (int) $u['working_hours'];
                $assigned = (int) $u['assigned_mins'];
                $remaining = $working_hours - $assigned;

                if ($remaining < (int) $task_minutes) {
                    $is_available = false;
                }
            }

            if ($is_available) {
                // Add useful info to the result for display
                $u['remaining_mins'] = ($u['type'] == 'Part-time') ? ($u['working_hours'] - $u['assigned_mins']) : null;
                $resources[] = $u;
            }
        }
    }
    return $resources;
}
?>