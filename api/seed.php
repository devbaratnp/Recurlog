<?php
require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Require authentication and CSRF validation
requireAuth();
$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    jsonError('Invalid or missing CSRF token', 403);
}

$db = getDB();

function bindExecute($stmt, $params) {
    $types = '';
    $refs = [];
    foreach ($params as $k => &$v) {
        if (is_int($v) || is_float($v)) {
            $types .= 'i';
        } else {
            $types .= 's';
        }
        $refs[$k] = &$v;
    }
    unset($v);
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
}

// Delete in dependency-safe order (children first)
$db->query("DELETE FROM fscrm_assignment_history");
$db->query("DELETE FROM fscrm_notifications");
$db->query("DELETE FROM fscrm_tasks");
$db->query("DELETE FROM fscrm_orders");
$db->query("DELETE FROM fscrm_services");
$db->query("DELETE FROM fscrm_localities");
$db->query("DELETE FROM fscrm_service_types");
$db->query("DELETE FROM fscrm_categories");
$db->query("DELETE FROM fscrm_staff");
$db->query("DELETE FROM fscrm_customers");
$db->query("DELETE FROM fscrm_users");

// Reset auto-increment
$db->query("ALTER TABLE fscrm_assignment_history AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_notifications AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_tasks AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_orders AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_services AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_localities AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_service_types AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_categories AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_staff AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_customers AUTO_INCREMENT = 1");
$db->query("ALTER TABLE fscrm_users AUTO_INCREMENT = 1");

// --- Users ---
// Ensure staff_id column exists (safe if migration already added it)
$colCheck = $db->query("SHOW COLUMNS FROM fscrm_users LIKE 'staff_id'");
if ($colCheck->num_rows === 0) {
    $db->query("ALTER TABLE fscrm_users ADD COLUMN staff_id INT DEFAULT NULL AFTER role");
}
$hash = password_hash('demo123', PASSWORD_DEFAULT);
$db->query("INSERT INTO fscrm_users (id, name, email, password, role, staff_id) VALUES (1, 'Admin User', 'admin@demo.com', '$hash', 'admin', NULL)");

// Staff user accounts (linked to fscrm_staff via staff_id)
$staffUsers = [
    [2, 'Ramesh Yadav', 'ramesh@demo.com', $hash, 'staff', 1],
    [3, 'Suresh Thakur', 'suresh@demo.com', $hash, 'staff', 2],
    [4, 'Bikash Sah', 'bikash@demo.com', $hash, 'staff', 3],
    [5, 'Anita Devi', 'anita@demo.com', $hash, 'staff', 4],
    [6, 'Manoj Kumar', 'manoj@demo.com', $hash, 'staff', 5]
];
$userStmt = $db->prepare("INSERT INTO fscrm_users (id, name, email, password, role, staff_id) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($staffUsers as $u) {
    bindExecute($userStmt, $u);
}

// --- Customers ---
$customers = [
    [1, 'Sharma Family', 'Adarsh Nagar, Birgunj', 'Adarsh Nagar', '+977-9801234001', 'RO,Refrigerator', 27.00, 84.87],
    [2, 'Gupta Electronics', 'Main Road, Birgunj', 'Station Road', '+977-9801234002', 'TV,AC', 27.01, 84.88],
    [3, 'Hotel Makalu', 'Ghantaghar, Birgunj', 'Ghantaghar Chowk', '+977-9801234003', 'AC,Refrigerator', 27.005, 84.875],
    [4, 'Patel Residence', 'Powerhouse Road, Birgunj', 'Powerhouse Road', '+977-9801234004', 'RO', 26.99, 84.86],
    [5, 'Singh Niwas', 'Adarshanagar, Birgunj', 'Mahabirsthan', '+977-9801234005', 'Washing Machine', 27.015, 84.885],
    [6, 'Modern Pharmacy', 'Adarsh Nagar, Birgunj', 'Adarsh Nagar', '+977-9801234006', 'Refrigerator,AC', 27.008, 84.878],
    [7, 'Khanal House', 'Murli Chowk, Birgunj', 'Murli Chowk', '+977-9801234007', 'RO,TV', 26.995, 84.865],
    [8, 'Birgunj Sweets', 'Maisthan, Birgunj', 'Maisthan', '+977-9801234008', 'Refrigerator', 27.02, 84.89]
];
$stmt = $db->prepare("INSERT INTO fscrm_customers (id, name, address, area, phone, services_for, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($customers as $c) {
    bindExecute($stmt, $c);
}

// --- Staff ---
$staff = [
    [1, 'Ramesh Yadav', '+977-9812345001', 'https://ui-avatars.com/api/?name=Ramesh+Yadav&background=1DB954&color=fff&size=200'],
    [2, 'Suresh Thakur', '+977-9812345002', 'https://ui-avatars.com/api/?name=Suresh+Thakur&background=0B1E3D&color=fff&size=200'],
    [3, 'Bikash Sah', '+977-9812345003', 'https://ui-avatars.com/api/?name=Bikash+Sah&background=F59E0B&color=fff&size=200'],
    [4, 'Anita Devi', '+977-9812345004', 'https://ui-avatars.com/api/?name=Anita+Devi&background=EF4444&color=fff&size=200'],
    [5, 'Manoj Kumar', '+977-9812345005', 'https://ui-avatars.com/api/?name=Manoj+Kumar&background=0EA5E9&color=fff&size=200']
];
$stmt = $db->prepare("INSERT INTO fscrm_staff (id, name, phone, avatar) VALUES (?, ?, ?, ?)");
foreach ($staff as $s) {
    bindExecute($stmt, $s);
}

// --- Categories ---
$categories = [
    [1, 'Annual Maintenance', '#22C55E'],
    [2, 'Filter Change', '#0EA5E9'],
    [3, 'Repair', '#F59E0B'],
    [4, 'Deep Cleaning', '#8B5CF6'],
    [5, 'Installation', '#EC4899'],
    [6, 'Inspection', '#6366F1']
];
$stmt = $db->prepare("INSERT INTO fscrm_categories (id, name, color) VALUES (?, ?, ?)");
foreach ($categories as $c) {
    bindExecute($stmt, $c);
}

// --- Service Types ---
$serviceTypes = ['RO', 'Chimney', 'Refrigerator', 'TV', 'Washing Machine', 'AC', 'Other'];
$stmt = $db->prepare("INSERT INTO fscrm_service_types (name) VALUES (?)");
foreach ($serviceTypes as $st) {
    bindExecute($stmt, [$st]);
}

// --- Orders ---
$today = new DateTime('today');
function daysFromToday($n) {
    global $today;
    $d = clone $today;
    $d->modify(($n >= 0 ? '+' : '') . $n . ' days');
    return $d;
}
function toISODate($d) {
    return $d->format('Y-m-d');
}
function toISODatetime($d) {
    return $d->format('Y-m-d H:i:s');
}

$orders = [
    [1, 1, 'Sharma Family', 'RO', 'Water pressure very low, filter needs urgent check', 'pending', 'urgent', null, null, null, null, '', null, null, null, null, null],
    [2, 2, 'Gupta Electronics', 'AC', 'AC not cooling properly, gas might be low', 'assigned', 'normal', 1, 'Ramesh Yadav', toISODate(daysFromToday(1)), null, 'Customer called in the morning', null, null, null, null, null],
    [3, 3, 'Hotel Makalu', 'Refrigerator', 'Commercial fridge making unusual noise, cooling inconsistent', 'assigned', 'normal', 2, 'Suresh Thakur', toISODate(daysFromToday(0)), null, 'Priority customer - hotel business', null, null, null, null, null],
    [4, 4, 'Patel Residence', 'RO', 'RO is leaking from the bottom, water all over the floor', 'completed', 'urgent', 4, 'Anita Devi', toISODate(daysFromToday(-9)), toISODate(daysFromToday(-9)), 'Leak fixed, replaced seal', null, null, null, null, null],
    [5, 5, 'Singh Niwas', 'Washing Machine', 'Drum not spinning, error code E4 showing on display', 'pending', 'normal', null, null, null, null, '', null, null, null, null, null],
    [6, 6, 'Modern Pharmacy', 'AC', 'AC installed last week but not blowing cold air', 'cancelled', 'urgent', 3, 'Bikash Sah', toISODate(daysFromToday(-11)), null, 'Customer cancelled - hired another service', null, null, null, null, null],
    [7, 7, 'Khanal House', 'TV', 'TV screen flickering when connected to HDMI', 'pending', 'normal', null, null, null, null, '', null, null, null, null, null],
    [8, 8, 'Birgunj Sweets', 'Refrigerator', 'Display cooler not maintaining temperature, sweets getting spoiled', 'assigned', 'urgent', 5, 'Manoj Kumar', toISODate(daysFromToday(0)), null, 'URGENT - food safety concern', null, null, null, null, null],
    [9, 2, 'Gupta Electronics', 'TV', 'TV not turning on, power light blinking', 'pending', 'normal', null, null, null, null, '', null, null, null, null, null],
    [10, 1, 'Sharma Family', 'Refrigerator', 'Ice maker not working, water dispenser also jammed', 'completed', 'normal', 2, 'Suresh Thakur', toISODate(daysFromToday(-14)), toISODate(daysFromToday(-14)), 'Ice maker repaired, water line unclogged', null, null, null, null, null],
    [11, 4, 'Patel Residence', 'RO', 'Bad taste in water, membrane might need replacement', 'pending', 'normal', null, null, null, null, '', null, null, null, null, null],
    [12, 6, 'Modern Pharmacy', 'Refrigerator', 'Vaccine storage fridge temperature fluctuating', 'pending', 'urgent', null, null, null, null, '', null, null, null, null, null],
    [13, 3, 'Hotel Makalu', 'AC', 'One AC unit in lobby making loud rattling sound', 'assigned', 'normal', 4, 'Anita Devi', toISODate(daysFromToday(-5)), null, 'May need fan motor replacement', null, null, null, null, null]
];

$orderStmt = $db->prepare("INSERT INTO fscrm_orders (id, customer_id, customer_name, service_for, problem, status, priority, assigned_to, assigned_staff_name, scheduled_date, completed_date, notes, dispatch_date, dispatch_by, received_name, received_contact, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($orders as $o) {
    bindExecute($orderStmt, $o);
}

// --- Services & Tasks ---
$now = new DateTime('today');
$startWindow = daysFromToday(-75);
$endWindow = daysFromToday(15);

$noteTemplates = [
    1 => ['Routine annual maintenance performed.', 'All components checked and functioning.', 'Preventive maintenance completed.', 'System running efficiently after service.', 'Annual checkup completed without issues.'],
    2 => ['Filter replaced with new unit.', 'Old filter was clogged, replaced successfully.', 'Filter change completed.', 'Customer advised on next filter change schedule.', 'Standard filter replacement done.'],
    3 => ['Faulty component identified and replaced.', 'Repair completed successfully.', 'Part needed to be ordered, repaired on revisit.', 'Diagnosed issue and fixed on site.', 'Customer reported problem, resolved after inspection.'],
    4 => ['Thorough cleaning completed.', 'Coils and vents cleaned thoroughly.', 'Deep cleaning performed with disinfectant.', 'Removed accumulated dust and debris.', 'Cleaning completed; unit performing better.'],
    5 => ['New unit installed successfully.', 'Installation completed and tested.', 'Customer trained on basic operation.', 'Mounting and setup completed.', 'Installation done as per customer preference.'],
    6 => ['Detailed inspection carried out.', 'All parameters within normal range.', 'Minor issues noted, customer advised.', 'Inspection report shared with customer.', 'System checked; no major concerns found.']
];

function getRandomNote($catId) {
    global $noteTemplates;
    $notes = isset($noteTemplates[$catId]) ? $noteTemplates[$catId] : $noteTemplates[6];
    return $notes[array_rand($notes)];
}

function getCompletionProb($staffId) {
    if ($staffId === 1 || $staffId === 4) return 0.90;
    if ($staffId === 5) return 0.70;
    return 0.80;
}

function getStatus($scheduledDate, $staffId) {
    global $now;
    $sDate = clone $scheduledDate;
    if ($sDate > $now) return 'pending';
    $prob = getCompletionProb($staffId);
    return (mt_rand() / mt_getrandmax()) < $prob ? 'completed' : 'missed';
}

function getCompletionDate($scheduledDate) {
    global $now;
    $s = clone $scheduledDate;
    $daysToAdd = (mt_rand() / mt_getrandmax()) < 0.7 ? 0 : 1;
    $d = clone $s;
    if ($daysToAdd > 0) $d->modify('+1 day');
    if ($d > $now) return clone $now;
    return $d;
}

$rawServices = [
    [1, 2, 'RO', 'RO Filter Change', true, 30, 'days', -75, 1],
    [1, 6, 'RO', 'RO System Inspection', true, 45, 'days', -80, 4],
    [1, 4, 'Refrigerator', 'Refrigerator Deep Cleaning', true, 90, 'days', -60, 2],
    [1, 1, 'Refrigerator', 'Refrigerator Annual Maintenance', true, 90, 'days', -70, 1],
    [1, 3, 'RO', 'RO Membrane Replacement', false, 0, '', -45, 4],
    [1, 5, 'RO', 'RO System Installation', false, 0, '', -120, 3],
    [1, 6, 'Refrigerator', 'Temperature Calibration Check', true, 45, 'days', -70, 5],
    [2, 6, 'TV', 'TV Calibration', true, 90, 'days', -65, 2],
    [2, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -75, 1],
    [2, 3, 'AC', 'AC Gas Refill', false, 0, '', -30, 3],
    [2, 2, 'AC', 'AC Filter Cleaning', true, 30, 'days', -80, 4],
    [2, 5, 'TV', 'TV Mounting Service', false, 0, '', -90, 5],
    [2, 6, 'AC', 'AC Performance Inspection', true, 45, 'days', -65, 1],
    [2, 3, 'TV', 'TV Display Repair', false, 0, '', -15, 2],
    [3, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -80, 1],
    [3, 4, 'Refrigerator', 'Refrigerator Deep Cleaning', true, 45, 'days', -70, 4],
    [3, 3, 'AC', 'AC Compressor Repair', false, 0, '', -55, 3],
    [3, 3, 'Refrigerator', 'Door Seal Replacement', false, 0, '', -40, 2],
    [3, 6, 'AC', 'AC Filter Inspection', true, 30, 'days', -85, 4],
    [3, 6, 'Refrigerator', 'Commercial Cooler Inspection', true, 30, 'days', -75, 1],
    [3, 1, 'Refrigerator', 'Walk-in Cooler Maintenance', true, 45, 'days', -60, 5],
    [3, 3, 'AC', 'AC Thermostat Calibration', false, 0, '', -10, 2],
    [4, 2, 'RO', 'RO Filter Change', true, 30, 'days', -80, 4],
    [4, 3, 'RO', 'RO Membrane Replacement', false, 0, '', -50, 1],
    [4, 6, 'RO', 'Water Quality Test', true, 60, 'days', -60, 3],
    [4, 5, 'RO', 'RO Faucet Installation', false, 0, '', -100, 5],
    [4, 6, 'RO', 'RO System Inspection', true, 45, 'days', -70, 4],
    [5, 3, 'Washing Machine', 'Drum Bearing Repair', false, 0, '', -35, 3],
    [5, 5, 'Washing Machine', 'Washing Machine Installation', false, 0, '', -95, 2],
    [5, 1, 'Washing Machine', 'Annual Maintenance', true, 45, 'days', -75, 1],
    [5, 6, 'Washing Machine', 'Performance Inspection', true, 30, 'days', -80, 4],
    [5, 4, 'Washing Machine', 'Drum Deep Cleaning', true, 60, 'days', -120, 5],
    [5, 3, 'Washing Machine', 'Water Inlet Valve Replacement', false, 0, '', -20, 2],
    [6, 1, 'Refrigerator', 'Vaccine Storage Unit Maintenance', true, 30, 'days', -80, 1],
    [6, 6, 'Refrigerator', 'Temperature Log Inspection', true, 14, 'days', -75, 4],
    [6, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -65, 2],
    [6, 4, 'Refrigerator', 'Coil Deep Cleaning', true, 60, 'days', -70, 3],
    [6, 6, 'AC', 'AC Air Quality Inspection', true, 30, 'days', -80, 4],
    [6, 3, 'Refrigerator', 'Thermostat Calibration Repair', false, 0, '', -25, 1],
    [6, 5, 'Refrigerator', 'Backup Unit Installation', false, 0, '', -110, 5],
    [6, 3, 'AC', 'AC Drain Line Repair', false, 0, '', -5, 2],
    [7, 2, 'RO', 'RO Filter Change', true, 30, 'days', -80, 4],
    [7, 6, 'TV', 'TV Picture Calibration', true, 90, 'days', -60, 2],
    [7, 6, 'RO', 'RO Water Quality Inspection', true, 45, 'days', -75, 1],
    [7, 3, 'TV', 'TV Wall Mount Repair', false, 0, '', -42, 3],
    [7, 3, 'RO', 'RO Pressure Pump Repair', false, 0, '', -18, 5],
    [7, 6, 'RO', 'RO System Audit', true, 60, 'days', -120, 4],
    [8, 4, 'Refrigerator', 'Display Cooler Deep Cleaning', true, 30, 'days', -75, 1],
    [8, 3, 'Refrigerator', 'Compressor Repair', false, 0, '', -50, 3],
    [8, 1, 'Refrigerator', 'Commercial Fridge Maintenance', true, 30, 'days', -80, 4],
    [8, 6, 'Refrigerator', 'Temperature Calibration Check', true, 14, 'days', -70, 2],
    [8, 5, 'Refrigerator', 'New Display Unit Installation', false, 0, '', -85, 5],
    [8, 6, 'Refrigerator', 'Condenser Coil Inspection', true, 45, 'days', -65, 1],
    [8, 4, 'Refrigerator', 'Storage Room Cooler Cleaning', true, 60, 'days', -90, 4],
    [8, 3, 'Refrigerator', 'Door Hinge Replacement', false, 0, '', -8, 2],
    [1, 6, 'RO', 'RO Preventive Inspection', true, 14, 'days', -72, 2],
    [1, 4, 'Refrigerator', 'Condenser Coil Cleaning', true, 30, 'days', -78, 5],
    [2, 4, 'AC', 'AC Condenser Cleaning', true, 30, 'days', -82, 3],
    [2, 6, 'TV', 'TV Surge Protector Check', true, 45, 'days', -68, 1],
    [3, 6, 'AC', 'Refrigerant Level Check', true, 30, 'days', -76, 4],
    [3, 6, 'Refrigerator', 'Kitchen Exhaust Inspection', true, 14, 'days', -70, 5],
    [4, 6, 'RO', 'RO Pressure Check', true, 30, 'days', -74, 2],
    [5, 6, 'Washing Machine', 'Belt Tension Inspection', true, 30, 'days', -80, 1],
    [5, 3, 'Washing Machine', 'Drain Hose Replacement', true, 60, 'days', -66, 3],
    [6, 6, 'Refrigerator', 'Backup Cooler Temperature Check', true, 14, 'days', -73, 4],
    [6, 4, 'AC', 'AC Duct Deep Cleaning', true, 45, 'days', -69, 2],
    [7, 6, 'RO', 'Storage Tank Pressure Check', true, 45, 'days', -77, 5],
    [7, 5, 'TV', 'Smart TV Setup Configuration', false, 0, '', -12, 1],
    [8, 6, 'Refrigerator', 'Door Gasket Seal Check', true, 30, 'days', -79, 3],
    [8, 4, 'Refrigerator', 'Ice Machine Cleaning', true, 14, 'days', -71, 4]
];

// Insert services
$insertedServices = [];
$nextServiceId = 1;
$svcStmt = $db->prepare("INSERT INTO fscrm_services (id, customer_id, category_id, service_for, title, problem, is_recurring, first_scheduled_date, assigned_to, notes, rec_value, rec_unit, repeat_from) VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?)");

foreach ($rawServices as $s) {
    $customerId = $s[0];
    $categoryId = $s[1];
    $serviceFor = $s[2];
    $title = $s[3];
    $isRecurring = $s[4] ? 1 : 0;
    $recValue = $s[4] ? $s[5] : null;
    $recUnit = $s[4] ? $s[6] : null;
    // Use 'last-done' (matches getNextDueDate branch) instead of 'last_service' (unrecognized)
    $repeatFrom = $s[4] ? 'last-done' : null;
    $firstDate = toISODate(daysFromToday($s[7]));
    $assignedTo = $s[8];
    $note = getRandomNote($categoryId);

    $svcParams = [$nextServiceId, $customerId, $categoryId, $serviceFor, $title, $isRecurring, $firstDate, $assignedTo, $note, $recValue, $recUnit, $repeatFrom];
    bindExecute($svcStmt, $svcParams);

    $insertedServices[] = [
        'id' => $nextServiceId,
        'customerId' => $customerId,
        'categoryId' => $categoryId,
        'serviceFor' => $serviceFor,
        'title' => $title,
        'isRecurring' => $s[4],
        'recValue' => $recValue,
        'recUnit' => $recUnit,
        'repeatFrom' => $repeatFrom,
        'firstScheduledDate' => $firstDate,
        'assignedTo' => $assignedTo
    ];
    $nextServiceId++;
}

// Generate tasks from services
$nextTaskId = 1;
$tasks = [];

foreach ($insertedServices as $svc) {
    $firstDate = new DateTime($svc['firstScheduledDate']);
    if ($svc['isRecurring']) {
        $interval = $svc['recUnit'] === 'months' ? (int)$svc['recValue'] * 30 : (int)$svc['recValue'];
        $current = clone $firstDate;
        while ($current <= $endWindow) {
            if ($current >= $startWindow) {
                $status = getStatus($current, $svc['assignedTo']);
                $completedDate = $status === 'completed' ? getCompletionDate($current) : null;
                $tasks[] = [
                    'id' => $nextTaskId,
                    'serviceId' => $svc['id'],
                    'customerId' => $svc['customerId'],
                    'title' => $svc['title'],
                    'status' => $status,
                    'scheduledDate' => toISODate($current),
                    'completedDate' => $completedDate ? toISODate($completedDate) : null,
                    'assignedTo' => $svc['assignedTo'],
                    'notes' => getRandomNote($svc['categoryId']),
                    'categoryId' => $svc['categoryId']
                ];
                $nextTaskId++;
            }
            $current = clone $current;
            $current->modify('+' . $interval . ' days');
        }
    } else {
        $sDate = clone $firstDate;
        if ($sDate < $startWindow) {
            $randDays = mt_rand(0, 74);
            $sDate = clone $startWindow;
            $sDate->modify('+' . $randDays . ' days');
        }
        if ($sDate > $endWindow) {
            $randDays = mt_rand(0, 74);
            $sDate = clone $startWindow;
            $sDate->modify('+' . $randDays . ' days');
        }
        $status = getStatus($sDate, $svc['assignedTo']);
        $completedDate = $status === 'completed' ? getCompletionDate($sDate) : null;
        $tasks[] = [
            'id' => $nextTaskId,
            'serviceId' => $svc['id'],
            'customerId' => $svc['customerId'],
            'title' => $svc['title'],
            'status' => $status,
            'scheduledDate' => toISODate($sDate),
            'completedDate' => $completedDate ? toISODate($completedDate) : null,
            'assignedTo' => $svc['assignedTo'],
            'notes' => getRandomNote($svc['categoryId']),
            'categoryId' => $svc['categoryId']
        ];
        $nextTaskId++;
    }
}

// Insert tasks
$taskStmt = $db->prepare("INSERT INTO fscrm_tasks (id, service_id, customer_id, title, status, scheduled_date, completed_date, assigned_to, notes, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$customerNames = [
    1 => 'Sharma Family',
    2 => 'Gupta Electronics',
    3 => 'Hotel Makalu',
    4 => 'Patel Residence',
    5 => 'Singh Niwas',
    6 => 'Modern Pharmacy',
    7 => 'Khanal House',
    8 => 'Birgunj Sweets'
];

foreach ($tasks as $t) {
    $taskParams = [$t['id'], $t['serviceId'], $t['customerId'], $t['title'], $t['status'], $t['scheduledDate'], $t['completedDate'], $t['assignedTo'], $t['notes'], $t['categoryId']];
    bindExecute($taskStmt, $taskParams);
}

// --- Notifications ---
$recentTasks = array_filter($tasks, function ($t) {
    global $now;
    $d = new DateTime($t['scheduledDate']);
    $thirtyDaysAgo = clone $now;
    $thirtyDaysAgo->modify('-30 days');
    return $d >= $thirtyDaysAgo && $d <= $now;
});

usort($recentTasks, function ($a, $b) {
    return strcmp($b['scheduledDate'], $a['scheduledDate']);
});

$pickedTasks = array_slice($recentTasks, 0, 22);

$notifStmt = $db->prepare("INSERT INTO fscrm_notifications (id, text, type, related_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?)");
$nextNotifId = 1;

foreach ($pickedTasks as $t) {
    $isRead = (mt_rand() / mt_getrandmax()) < 0.4 ? 1 : 0;
    $cName = isset($customerNames[$t['customerId']]) ? $customerNames[$t['customerId']] : 'Unknown';
    if ($t['status'] === 'completed') {
        $text = $t['title'] . ' completed for ' . $cName;
        $type = 'task_completed';
    } else {
        $text = $t['title'] . ' missed at ' . $cName;
        $type = 'task_missed';
    }
    $createdAt = $t['completedDate'] ? $t['completedDate'] . ' 12:00:00' : $t['scheduledDate'] . ' 12:00:00';
    $notifParams = [$nextNotifId, $text, $type, $t['id'], $isRead, $createdAt];
    bindExecute($notifStmt, $notifParams);
    $nextNotifId++;
}

$extraNotifs = [
    ['text' => 'New customer registered: Sharma Family', 'type' => 'customer_added', 'age' => -35],
    ['text' => 'New service added: AC Annual Maintenance for Gupta Electronics', 'type' => 'service_added', 'age' => -28],
    ['text' => 'New customer registered: Hotel Makalu', 'type' => 'customer_added', 'age' => -20],
    ['text' => 'New service added: RO Filter Change for Patel Residence', 'type' => 'service_added', 'age' => -15],
    ['text' => 'Ramesh Yadav completed 15 tasks this week', 'type' => 'task_completed', 'age' => -3]
];

foreach ($extraNotifs as $n) {
    $createdAt = daysFromToday($n['age']);
    if ($createdAt > $now) $createdAt = clone $now;
    $isRead = (mt_rand() / mt_getrandmax()) < 0.5 ? 1 : 0;
    $relatedId = null;
    $notifParams = [$nextNotifId, $n['text'], $n['type'], $relatedId, $isRead, toISODatetime($createdAt)];
    bindExecute($notifStmt, $notifParams);
    $nextNotifId++;
}

// --- Localities ---
$areaNames = [];
foreach ($customers as $c) {
    $areaNames[] = $c[3];
}
$areaNames = array_unique($areaNames);
sort($areaNames);
$locStmt = $db->prepare("INSERT INTO fscrm_localities (name) VALUES (?)");
foreach ($areaNames as $a) {
    bindExecute($locStmt, [$a]);
}

// Redirect back to settings page if form submission, otherwise return JSON
$isFormPost = !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'settings.php') !== false;
if ($isFormPost) {
    header('Location: ../pages/settings.php?reset=success');
} else {
    jsonResponse(['message' => 'Database seeded successfully']);
}
