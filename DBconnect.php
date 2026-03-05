
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "uts";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("connection failed". $conn->connect_error);
}

// Create or select database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    mysqli_select_db($conn, $dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Create bus table - bus_number is PRIMARY KEY
$bus_table = "CREATE TABLE IF NOT EXISTS bus (
    bus_number VARCHAR(20) PRIMARY KEY,
    bus_route VARCHAR(100) NOT NULL
)";

// Create student table - SID is PRIMARY KEY
$student_table = "CREATE TABLE IF NOT EXISTS student (
    SID VARCHAR(20) PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Address VARCHAR(100) NOT NULL,
    Phone_Number VARCHAR(15) NOT NULL,
    Department VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL
)";

// Create staff table - staff_id is PRIMARY KEY
$staff_table = "CREATE TABLE IF NOT EXISTS staff (
    staff_id VARCHAR(20) PRIMARY KEY,
    D_name VARCHAR(100) NOT NULL,
    Staff_PhoneNumber VARCHAR(15) NOT NULL,
    Assigned_Bus VARCHAR(20),
    password VARCHAR(100) NOT NULL,
    FOREIGN KEY (Assigned_Bus) REFERENCES bus(bus_number) ON DELETE SET NULL
)";

// Create admin table
$admin_table = "CREATE TABLE IF NOT EXISTS admin (
    admin_id VARCHAR(20) PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL
)";

// Create bookings table - UPDATED: Removed payment_method and seat_type as student choices
$bookings_table = "CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    bus_number VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    payment_method ENUM('Not Set', 'COD', 'Bkash') DEFAULT 'Not Set',
    seat_type ENUM('Not Set', 'Sit', 'Stand') DEFAULT 'Not Set',
    payment_status ENUM('Not Paid', 'Paid', 'Half Paid') DEFAULT 'Not Paid',
    actual_amount DECIMAL(10,2) DEFAULT 0.00,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student(SID) ON DELETE CASCADE,
    FOREIGN KEY (bus_number) REFERENCES bus(bus_number) ON DELETE CASCADE
)";

// Create feedback table
$feedback_table = "CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    user_type ENUM('student', 'staff') NOT NULL,
    feedback_text TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create bus_routes mapping table
$routes_table = "CREATE TABLE IF NOT EXISTS bus_routes (
    bus_number VARCHAR(20),
    route_area VARCHAR(100) NOT NULL,
    FOREIGN KEY (bus_number) REFERENCES bus(bus_number) ON DELETE CASCADE
)";

// Create tables
$conn->query($bus_table);
$conn->query($student_table);
$conn->query($staff_table);
$conn->query($admin_table);
$conn->query($bookings_table);
$conn->query($feedback_table);
$conn->query($routes_table);

// Check if actual_amount column exists, if not add it
$check_column = $conn->query("SHOW COLUMNS FROM bookings LIKE 'actual_amount'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN actual_amount DECIMAL(10,2) DEFAULT 0.00");
}

// Insert default admin if not exists
$check_admin = $conn->query("SELECT * FROM admin WHERE admin_id = 'admin'");
if ($check_admin->num_rows == 0) {
    $conn->query("INSERT INTO admin (admin_id, Name, password) VALUES ('admin', 'Admin', 'admin123')");
}

// Define the unified route options
$route_options = [
    'Mirpur1-Mirpur2-Mirpur14',
    'UttaraNorth-UttaraMiddle', 
    'UttaraSouth-UttaraMiddle',
    'Malibagh',
    'Gigatola',
    'Mohammodpur-SiaMosque'
];

// Insert sample buses with the new route options
$sample_buses = [
    'B101' => 'Mirpur1-Mirpur2-Mirpur14',
    'B102' => 'UttaraNorth-UttaraMiddle',
    'B103' => 'UttaraSouth-UttaraMiddle',
    'B104' => 'Malibagh',
    'B105' => 'Gigatola',
    'B106' => 'Mohammodpur-SiaMosque'
];

foreach ($sample_buses as $bus => $route) {
    $check_bus = $conn->query("SELECT * FROM bus WHERE bus_number = '$bus'");
    if ($check_bus->num_rows == 0) {
        $conn->query("INSERT INTO bus (bus_number, bus_route) VALUES ('$bus', '$route')");
        
        // Parse the route string to insert individual areas
        $areas = explode('-', $route);
        foreach ($areas as $area) {
            $conn->query("INSERT INTO bus_routes (bus_number, route_area) VALUES ('$bus', '$area')");
        }
    }
}

// INSERT 30 RANDOM STUDENTS
$first_names = ['Ahmed', 'Fatima', 'Rahim', 'Sadia', 'Kamal', 'Nadia', 'Imran', 'Sara', 'Kabir', 'Laila', 
                'Zayed', 'Tasnim', 'Rafiq', 'Jahanara', 'Siddique', 'Nusrat', 'Jamil', 'Sabrina', 'Farid', 'Rukhsana',
                'Arif', 'Sharmin', 'Nasir', 'Bushra', 'Tariq', 'Fahmida', 'Shafiq', 'Samina', 'Moin', 'Rehana',
                'Salman', 'Nasreen', 'Habib', 'Rashida', 'Javed', 'Shabnam', 'Mushfiq', 'Tamanna', 'Ashraf', 'Shirin'];

$last_names = ['Chowdhury', 'Hossain', 'Rahman', 'Khan', 'Ahmed', 'Ali', 'Islam', 'Haque', 'Sikder', 'Mia',
               'Das', 'Bhuiyan', 'Uddin', 'Sarkar', 'Mahmud', 'Akter', 'Begum', 'Sultana', 'Jahan', 'Parvin'];

$departments = ['CSE', 'ENH', 'MNS', 'BBA', 'EEE'];
$password = '123456'; // Default password for all students

for ($i = 1; $i <= 30; $i++) {
    $student_id = 'S' . str_pad($i, 4, '0', STR_PAD_LEFT); // S0001, S0002, etc.
    $first_name = $first_names[array_rand($first_names)];
    $last_name = $last_names[array_rand($last_names)];
    $full_name = $first_name . ' ' . $last_name;
    $route = $route_options[array_rand($route_options)];
    $phone = '01' . rand(5, 9) . rand(10000000, 99999999); // Bangladeshi mobile number
    $dept = $departments[array_rand($departments)];
    
    // Check if student already exists
    $check_student = $conn->query("SELECT * FROM student WHERE SID = '$student_id'");
    if ($check_student->num_rows == 0) {
        $sql = "INSERT INTO student (SID, Name, Address, Phone_Number, Department, password) 
                VALUES ('$student_id', '$full_name', '$route', '$phone', '$dept', '$password')";
        $conn->query($sql);
    }
}

// INSERT 5 RANDOM STAFF/DRIVERS with UNIQUE bus assignments
$driver_names = [
    'Abdul Karim', 'Mohammad Ali', 'Rashid Ahmed', 'Jamal Uddin', 'Shahidul Islam'
];

// Assign each of the first 5 buses to a different driver
$bus_driver_pairs = [
    'B101' => 'Abdul Karim',
    'B102' => 'Mohammad Ali', 
    'B103' => 'Rashid Ahmed',
    'B104' => 'Jamal Uddin',
    'B105' => 'Shahidul Islam'
    // B106 remains unassigned
];

$i = 1;
foreach ($bus_driver_pairs as $bus_number => $driver_name) {
    $staff_id = 'D' . str_pad($i, 3, '0', STR_PAD_LEFT); // D001, D002, etc.
    $phone = '01' . rand(5, 9) . rand(10000000, 99999999);
    
    // Check if staff already exists
    $check_staff = $conn->query("SELECT * FROM staff WHERE staff_id = '$staff_id'");
    if ($check_staff->num_rows == 0) {
        $sql = "INSERT INTO staff (staff_id, D_name, Staff_PhoneNumber, Assigned_Bus, password) 
                VALUES ('$staff_id', '$driver_name', '$phone', '$bus_number', '$password')";
        $conn->query($sql);
    }
    $i++;
}

// Create some sample bookings for testing
$bus_numbers_array = array_keys($sample_buses); // Get actual bus numbers: ['B101', 'B102', ...]

for ($i = 1; $i <= 10; $i++) {
    $student_id = 'S' . str_pad(rand(1, 30), 4, '0', STR_PAD_LEFT);
    $bus_number = $bus_numbers_array[array_rand($bus_numbers_array)]; // Get random bus NUMBER
    $booking_date = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
    
    // Check if booking already exists
    $check_booking = $conn->query("SELECT * FROM bookings WHERE student_id = '$student_id' AND booking_date = '$booking_date'");
    if ($check_booking->num_rows == 0) {
        $sql = "INSERT INTO bookings (student_id, bus_number, booking_date) 
                VALUES ('$student_id', '$bus_number', '$booking_date')";
        $conn->query($sql);
    }
}
?>
