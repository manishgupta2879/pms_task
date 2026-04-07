<?php
include __DIR__ . "/../includes/config.php";

/**
 * Database Seeder
 * Seeds users, orders, order items, and tasks
 */

echo "========================================\n";
echo "Starting Database Seeder...\n";
echo "========================================\n\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Clear existing data
echo "Clearing existing data...\n";
$conn->query("TRUNCATE TABLE task_logs");
$conn->query("TRUNCATE TABLE tasks");
$conn->query("TRUNCATE TABLE order_items");
$conn->query("TRUNCATE TABLE orders");
$conn->query("TRUNCATE TABLE task_library");
//$conn->query("TRUNCATE TABLE users");

echo "✓ Data cleared\n\n";

// ============================================
// 1. SEED USERS (Resources - Indian Names)
// ============================================
echo "Seeding Users (Resources)...\n";

$users = [
    [
        'username' => 'rajesh_kumar',
        'name' => 'Rajesh Kumar',
        'email' => 'rajesh@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9, // Staff
        // 'is_active' => 1
    ],
    [
        'username' => 'priya_sharma',
        'name' => 'Priya Sharma',
        'email' => 'priya@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'amit_patel',
        'name' => 'Amit Patel',
        'email' => 'amit@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'deepika_singh',
        'name' => 'Deepika Singh',
        'email' => 'deepika@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'vikram_verma',
        'name' => 'Vikram Verma',
        'email' => 'vikram@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'neha_gupta',
        'name' => 'Neha Gupta',
        'email' => 'neha@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'rohan_desai',
        'name' => 'Rohan Desai',
        'email' => 'rohan@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ],
    [
        'username' => 'ananya_iyer',
        'name' => 'Ananya Iyer',
        'email' => 'ananya@company.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role_id' => 9,
        // 'is_active' => 1
    ]
];

$user_ids = [];
foreach ($users as $user) {
    $username = $conn->real_escape_string($user['username']);
    $name = $conn->real_escape_string($user['name']);
    $email = $conn->real_escape_string($user['email']);
    $password = $user['password'];
    $role_id = $user['role_id'];
    // $is_active = $user['is_active'];
    
$sql = "INSERT INTO users (username, name, email, password, role_id) VALUES ('$username', '$name', '$email', '$password', $role_id)";
    
    if ($conn->query($sql)) {
        $user_ids[] = $conn->insert_id;
        echo "✓ User created: {$user['name']}\n";
    } else {
        echo "✗ Error creating user: {$user['name']} - " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// 2. SEED ORDERS
// ============================================
echo "Seeding Orders...\n";

$orders = [
    [
        'order_no' => '10001',
        'customer' => 'ABC Manufacturing Ltd',
        'product' => 'Electronics Assembly',
        'status' => 'in_progress',
        'deadline' => date('Y-m-d', strtotime('+7 days'))
    ],
    [
        'order_no' => '10002',
        'customer' => 'XYZ Industries',
        'product' => 'Sheet Metal Components',
        'status' => 'in_progress',
        'deadline' => date('Y-m-d', strtotime('+5 days'))
    ],
    [
        'order_no' => '10003',
        'customer' => 'Tech Solutions Inc',
        'product' => 'PCB Assembly Package',
        'status' => 'in_progress',
        'deadline' => date('Y-m-d', strtotime('+10 days'))
    ],
    [
        'order_no' => '10004',
        'customer' => 'Global Trade Co',
        'product' => 'Testing & QA Services',
        'status' => 'pending',
        'deadline' => date('Y-m-d', strtotime('+3 days'))
    ],
    [
        'order_no' => '10005',
        'customer' => 'Premium Parts Ltd',
        'product' => 'Metal Fabrication',
        'status' => 'in_progress',
        'deadline' => date('Y-m-d', strtotime('+14 days'))
    ]
];

$order_ids = [];
foreach ($orders as $order) {
    $order_no = $conn->real_escape_string($order['order_no']);
    $customer = $conn->real_escape_string($order['customer']);
    $product = $conn->real_escape_string($order['product']);
    $status = $order['status'];
    $deadline = $order['deadline'];
    
    $sql = "INSERT INTO orders (order_no, customer, product, status, deadline) 
            VALUES ('$order_no', '$customer', '$product', '$status', '$deadline')";
    
    if ($conn->query($sql)) {
        $order_ids[] = $conn->insert_id;
        echo "✓ Order created: {$order['order_no']} - {$order['customer']}\n";
    } else {
        echo "✗ Error creating order: {$order['order_no']} - " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// 3. SEED ORDER ITEMS (Products)
// ============================================
echo "Seeding Order Items...\n";

$order_items = [
    ['order_id' => '10001', 'product' => 'Microcontroller Unit', 'species' => 'MCU-32', 'qty' => 500],
    ['order_id' => '10001', 'product' => 'Power Supply Module', 'species' => 'PSM-500W', 'qty' => 250],
    ['order_id' => '10001', 'product' => 'Relay Assembly', 'species' => 'RLA-60', 'qty' => 1000],
    
    ['order_id' => '10002', 'product' => 'Stainless Steel Plate', 'species' => 'SS-304', 'qty' => 100],
    ['order_id' => '10002', 'product' => 'Aluminum Profile', 'species' => 'AL-6063', 'qty' => 200],
    ['order_id' => '10002', 'product' => 'Fastener Kit', 'species' => 'M5-M8', 'qty' => 5000],
    
    ['order_id' => '10003', 'product' => 'PCB Blank', 'species' => 'FR-4', 'qty' => 1500],
    ['order_id' => '10003', 'product' => 'Solder Paste', 'species' => 'Lead-Free', 'qty' => 50],
    ['order_id' => '10003', 'product' => 'Component Kit', 'species' => 'Mixed', 'qty' => 2000],
    
    ['order_id' => '10004', 'product' => 'Test Equipment Rental', 'species' => 'Standard', 'qty' => 1],
    ['order_id' => '10004', 'product' => 'Quality Checklist', 'species' => 'Standard', 'qty' => 1],
    
    ['order_id' => '10005', 'product' => 'Mild Steel Bar', 'species' => 'MS-250', 'qty' => 50],
    ['order_id' => '10005', 'product' => 'Cutting Tool Set', 'species' => 'Standard', 'qty' => 25],
];

foreach ($order_items as $item) {
    $order_id = $conn->real_escape_string($item['order_id']);
    $product = $conn->real_escape_string($item['product']);
    $species = $conn->real_escape_string($item['species']);
    $qty = $item['qty'];
    
    $sql = "INSERT INTO order_items (order_id, product, species, qty) 
            VALUES ('$order_id', '$product', '$species', $qty)";
    
    if ($conn->query($sql)) {
        echo "✓ Order item created: {$item['product']}\n";
    } else {
        echo "✗ Error creating order item: {$item['product']} - " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// 4. SEED TASKS
// ============================================
echo "Seeding Tasks...\n";

$now = date('Y-m-d H:i:s');
$tasks = [
    // Order 10001 - Assembly Tasks
    ['order_id' => $order_ids[0], 'task_name' => 'Component Procurement', 'est_time' => 480, 'priority' => 'high', 'status' => 'completed', 'user_id' => $user_ids[0]],
    ['order_id' => $order_ids[0], 'task_name' => 'PCB Assembly', 'est_time' => 1800, 'priority' => 'high', 'status' => 'in_progress', 'user_id' => $user_ids[1]],
    ['order_id' => $order_ids[0], 'task_name' => 'Soldering & Joining', 'est_time' => 1200, 'priority' => 'high', 'status' => 'not_started', 'user_id' => $user_ids[2]],
    
    // Order 10002 - Packaging & Metal Work
    ['order_id' => $order_ids[1], 'task_name' => 'Metal Cutting', 'est_time' => 600, 'priority' => 'medium', 'status' => 'completed', 'user_id' => $user_ids[3]],
    ['order_id' => $order_ids[1], 'task_name' => 'Sheet Metal Bending', 'est_time' => 900, 'priority' => 'medium', 'status' => 'in_progress', 'user_id' => $user_ids[4]],
    ['order_id' => $order_ids[1], 'task_name' => 'Packaging Materials Preparation', 'est_time' => 300, 'priority' => 'low', 'status' => 'not_started', 'user_id' => $user_ids[5]],
    
    // Order 10003 - PCB Assembly
    ['order_id' => $order_ids[2], 'task_name' => 'Board Layout Design', 'est_time' => 1200, 'priority' => 'high', 'status' => 'completed', 'user_id' => $user_ids[0]],
    ['order_id' => $order_ids[2], 'task_name' => 'Component Placement', 'est_time' => 2400, 'priority' => 'high', 'status' => 'not_started', 'user_id' => $user_ids[1]],
    ['order_id' => $order_ids[2], 'task_name' => 'Reflow Soldering', 'est_time' => 900, 'priority' => 'high', 'status' => 'not_started', 'user_id' => $user_ids[2]],
    
    // Order 10004 - Testing & QA
    ['order_id' => $order_ids[3], 'task_name' => 'Functional Testing', 'est_time' => 600, 'priority' => 'high', 'status' => 'not_started', 'user_id' => $user_ids[6]],
    ['order_id' => $order_ids[3], 'task_name' => 'Quality Inspection', 'est_time' => 480, 'priority' => 'high', 'status' => 'not_started', 'user_id' => $user_ids[7]],
    ['order_id' => $order_ids[3], 'task_name' => 'Documentation & Reporting', 'est_time' => 300, 'priority' => 'medium', 'status' => 'not_started', 'user_id' => $user_ids[0]],
    
    // Order 10005 - Metal Fabrication
    ['order_id' => $order_ids[4], 'task_name' => 'Design & Planning', 'est_time' => 1200, 'priority' => 'medium', 'status' => 'not_started', 'user_id' => $user_ids[3]],
    ['order_id' => $order_ids[4], 'task_name' => 'Material Preparation', 'est_time' => 600, 'priority' => 'medium', 'status' => 'not_started', 'user_id' => $user_ids[4]],
    ['order_id' => $order_ids[4], 'task_name' => 'Final Assembly', 'est_time' => 1500, 'priority' => 'medium', 'status' => 'not_started', 'user_id' => $user_ids[5]],
];

foreach ($tasks as $task) {
    $order_id = $task['order_id'];
    $task_name = $conn->real_escape_string($task['task_name']);
    $est_time = $task['est_time'];
    $priority = $task['priority'];
    $status = $task['status'];
    $user_id = $task['user_id'];
    
    $sql = "INSERT INTO tasks (order_id, task_name, est_time, priority, status, user_id, assigned_by, created_at) 
            VALUES ($order_id, '$task_name', $est_time, '$priority', '$status', $user_id, 1, '$now')";
    
    if ($conn->query($sql)) {
        $task_id = $conn->insert_id;
        echo "✓ Task created: {$task['task_name']}\n";
        
        // Add completed task logs for some tasks
        if ($task['status'] === 'completed') {
            $start_time = date('Y-m-d H:i:s', strtotime('-1 day'));
            $end_time = date('Y-m-d H:i:s', strtotime('-1 day +' . ($task['est_time'] + 300) . ' minutes'));
            
            $log_sql = "INSERT INTO task_logs (task_id, user_id, start_time, end_time, duration) 
                        VALUES ($task_id, $user_id, '$start_time', '$end_time', " . ($task['est_time'] + 300) . ")";
            $conn->query($log_sql);
            
            // Update task with times
            $update_sql = "UPDATE tasks SET start_time = '$start_time', end_time = '$end_time' WHERE id = $task_id";
            $conn->query($update_sql);
        }
    } else {
        echo "✗ Error creating task: {$task['task_name']} - " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// 5. SEED TASK LIBRARY
// ============================================
echo "Seeding Task Library (15 Tasks)...\n";

$library_tasks = [
    ['task_name' => 'Component Sourcing', 'default_time' => 480, 'category' => 'Procurement'],
    ['task_name' => 'Quality Verification', 'default_time' => 600, 'category' => 'QA'],
    ['task_name' => 'Assembly Line Setup', 'default_time' => 900, 'category' => 'Assembly'],
    ['task_name' => 'Precision Measurement', 'default_time' => 300, 'category' => 'Testing'],
    ['task_name' => 'Final Inspection', 'default_time' => 300, 'category' => 'QA'],
    ['task_name' => 'Packaging & Labeling', 'default_time' => 360, 'category' => 'Packaging'],
    ['task_name' => 'Soldering Process', 'default_time' => 1200, 'category' => 'Assembly'],
    ['task_name' => 'Documentation Review', 'default_time' => 240, 'category' => 'Documentation'],
    ['task_name' => 'Material Handling', 'default_time' => 180, 'category' => 'Logistics'],
    ['task_name' => 'Equipment Calibration', 'default_time' => 420, 'category' => 'Maintenance'],
    ['task_name' => 'Inventory Check', 'default_time' => 300, 'category' => 'Logistics'],
    ['task_name' => 'Performance Testing', 'default_time' => 600, 'category' => 'Testing'],
    ['task_name' => 'Safety Compliance', 'default_time' => 360, 'category' => 'Compliance'],
    ['task_name' => 'Batch Processing', 'default_time' => 1500, 'category' => 'Manufacturing'],
    ['task_name' => 'Report Generation', 'default_time' => 240, 'category' => 'Documentation'],
];

$library_ids = [];
foreach ($library_tasks as $lib_task) {
    $task_name = $conn->real_escape_string($lib_task['task_name']);
    $default_time = $lib_task['default_time'];
    $description = $conn->real_escape_string($lib_task['category']);
    
    $sql = "INSERT INTO task_library (task_name, default_time, description) 
            VALUES ('$task_name', $default_time, '$description')";
    
    if ($conn->query($sql)) {
        $library_ids[] = $conn->insert_id;
        echo "✓ Library task created: {$lib_task['task_name']}\n";
    } else {
        echo "✗ Error creating library task: {$lib_task['task_name']} - " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// 6. SEED TASKS FROM LIBRARY ASSIGNED TO ORDERS
// ============================================
echo "Creating Order Tasks from Library...\n";

$order_library_tasks = [
    // Order 10001 - Electronics Assembly
    ['order_id' => $order_ids[0], 'lib_id' => $library_ids[0], 'user_id' => $user_ids[0], 'priority' => 'high', 'status' => 'completed'],
    ['order_id' => $order_ids[0], 'lib_id' => $library_ids[6], 'user_id' => $user_ids[1], 'priority' => 'high', 'status' => 'in_progress'],
    ['order_id' => $order_ids[0], 'lib_id' => $library_ids[4], 'user_id' => $user_ids[2], 'priority' => 'high', 'status' => 'not_started'],
    
    // Order 10002 - Sheet Metal
    ['order_id' => $order_ids[1], 'lib_id' => $library_ids[3], 'user_id' => $user_ids[3], 'priority' => 'medium', 'status' => 'completed'],
    ['order_id' => $order_ids[1], 'lib_id' => $library_ids[2], 'user_id' => $user_ids[4], 'priority' => 'medium', 'status' => 'in_progress'],
    ['order_id' => $order_ids[1], 'lib_id' => $library_ids[5], 'user_id' => $user_ids[5], 'priority' => 'low', 'status' => 'not_started'],
    
    // Order 10003 - PCB Assembly
    ['order_id' => $order_ids[2], 'lib_id' => $library_ids[7], 'user_id' => $user_ids[0], 'priority' => 'high', 'status' => 'completed'],
    ['order_id' => $order_ids[2], 'lib_id' => $library_ids[11], 'user_id' => $user_ids[1], 'priority' => 'high', 'status' => 'not_started'],
    ['order_id' => $order_ids[2], 'lib_id' => $library_ids[1], 'user_id' => $user_ids[2], 'priority' => 'high', 'status' => 'not_started'],
    
    // Order 10004 - Testing & QA
    ['order_id' => $order_ids[3], 'lib_id' => $library_ids[11], 'user_id' => $user_ids[6], 'priority' => 'high', 'status' => 'not_started'],
    ['order_id' => $order_ids[3], 'lib_id' => $library_ids[4], 'user_id' => $user_ids[7], 'priority' => 'high', 'status' => 'not_started'],
    ['order_id' => $order_ids[3], 'lib_id' => $library_ids[7], 'user_id' => $user_ids[0], 'priority' => 'medium', 'status' => 'not_started'],
    
    // Order 10005 - Metal Fabrication
    ['order_id' => $order_ids[4], 'lib_id' => $library_ids[8], 'user_id' => $user_ids[3], 'priority' => 'medium', 'status' => 'not_started'],
    ['order_id' => $order_ids[4], 'lib_id' => $library_ids[13], 'user_id' => $user_ids[4], 'priority' => 'medium', 'status' => 'not_started'],
    ['order_id' => $order_ids[4], 'lib_id' => $library_ids[2], 'user_id' => $user_ids[5], 'priority' => 'medium', 'status' => 'not_started'],
];

foreach ($order_library_tasks as $task) {
    $order_id = $task['order_id'];
    $lib_id = $task['lib_id'];
    $user_id = $task['user_id'];
    $priority = $task['priority'];
    $status = $task['status'];
    
    // Get library task details
    $lib_res = $conn->query("SELECT task_name, default_time FROM task_library WHERE id = $lib_id");
    $lib_row = $lib_res->fetch_assoc();
    
    $task_name = $conn->real_escape_string($lib_row['task_name']);
    $est_time = $lib_row['default_time'];
    
    $sql = "INSERT INTO tasks (order_id, task_name, est_time, priority, status, user_id, assigned_by, created_at) 
            VALUES ($order_id, '$task_name', $est_time, '$priority', '$status', $user_id, 1, '$now')";
    
    if ($conn->query($sql)) {
        $task_id = $conn->insert_id;
        echo "✓ Task assigned to order: {$lib_row['task_name']}\n";
        
        // Add completed task logs for some tasks
        if ($status === 'completed') {
            $start_time = date('Y-m-d H:i:s', strtotime('-2 days'));
            $end_time = date('Y-m-d H:i:s', strtotime('-2 days +' . ($est_time + 150) . ' minutes'));
            
            $log_sql = "INSERT INTO task_logs (task_id, user_id, start_time, end_time, duration) 
                        VALUES ($task_id, $user_id, '$start_time', '$end_time', " . ($est_time + 150) . ")";
            $conn->query($log_sql);
            
            // Update task with times
            $update_sql = "UPDATE tasks SET start_time = '$start_time', end_time = '$end_time' WHERE id = $task_id";
            $conn->query($update_sql);
        }
    } else {
        echo "✗ Error assigning task to order - " . $conn->error . "\n";
    }
}

echo "\n";

echo "========================================\n";
echo "✓ Database Seeding Completed Successfully!\n";
echo "========================================\n";
echo "\nSeeded:\n";
echo "- " . count($users) . " Users (Resources)\n";
echo "- " . count($orders) . " Orders\n";
echo "- " . count($order_items) . " Order Items (Products)\n";
echo "- " . count($tasks) . " Old Format Tasks\n";
echo "- " . count($library_tasks) . " Task Library Templates\n";
echo "- " . count($order_library_tasks) . " Tasks Assigned to Orders\n";
echo "\nLogin Credentials:\n";
echo "- Username: rajesh_kumar\n";
echo "- Password: password123\n";
echo "\nAll users have the same password: password123\n";
echo "========================================\n";

$conn->close();
?>
