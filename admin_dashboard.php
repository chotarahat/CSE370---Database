
<?php
session_start(); # to create it uniq way
require_once("DBConnect.php");

// Check if user is admin 
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Define unified route options (same for bus routes and student areas)
$route_options = [
    'Mirpur1-Mirpur2-Mirpur14',
    'UttaraNorth-UttaraMiddle', 
    'UttaraSouth-UttaraMiddle',
    'Malibagh',
    'Gigatola',
    'Mohammodpur-SiaMosque'
];

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle BUS addition (NO assigned driver here)
    if (isset($_POST['addbus'])) {
        $bus_no = mysqli_real_escape_string($conn, $_POST['bus_no']);
        $bus_route = mysqli_real_escape_string($conn, $_POST['bus_route']);
        
        // Validate that the selected route is from our predefined options
        if (!in_array($bus_route, $route_options)) {
            $error = "Invalid route selected!";
        } else {
            $sql = "INSERT INTO bus (bus_number, bus_route) VALUES ('$bus_no', '$bus_route')";
            
            if (mysqli_query($conn, $sql)) {
                // Also insert route areas into bus_routes table
                $areas = explode('-', $bus_route);
                foreach ($areas as $area) {
                    mysqli_query($conn, "INSERT INTO bus_routes (bus_number, route_area) VALUES ('$bus_no', '$area')");
                }
                $message = "Bus added successfully!";
            } else {
                $error = "Error adding bus: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle STUDENT addition
    if (isset($_POST['add_student'])) {
        $sid = mysqli_real_escape_string($conn, $_POST['sid']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        
        // Validate that the selected address is from our predefined options
        if (!in_array($address, $route_options)) {
            $error = "Invalid area selected!";
        } else {
            $sql = "INSERT INTO student (SID, Name, Address, Phone_Number, Department, password) 
                    VALUES ('$sid', '$name', '$address', '$phone', '$department', '$password')";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Student added successfully!";
            } else {
                $error = "Error adding student: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle STAFF addition
    if (isset($_POST['add_staff'])) {
        $staff_name = mysqli_real_escape_string($conn, $_POST['staff_name']);
        $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
        $phone = mysqli_real_escape_string($conn, $_POST['staff_phone']);
        $assigned_bus = mysqli_real_escape_string($conn, $_POST['assigned_bus']);
        $password = mysqli_real_escape_string($conn, $_POST['staff_password']);
        
        $sql = "INSERT INTO staff (staff_id, D_name, Staff_PhoneNumber, Assigned_Bus, password) 
                VALUES ('$staff_id', '$staff_name', '$phone', '$assigned_bus', '$password')";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Staff added successfully!";
        } else {
            $error = "Error adding staff: " . mysqli_error($conn);
        }
    }
}

// Fetch existing data for display
$buses = mysqli_query($conn, "SELECT * FROM bus ORDER BY bus_number");
$students = mysqli_query($conn, "SELECT * FROM student ORDER BY SID");
$staff = mysqli_query($conn, "SELECT * FROM staff ORDER BY D_name");

// Get available buses (buses not assigned to any staff)
$available_buses = mysqli_query($conn, "
    SELECT b.* FROM bus b 
    LEFT JOIN staff s ON b.bus_number = s.Assigned_Bus 
    WHERE s.Assigned_Bus IS NULL 
    ORDER BY b.bus_number
");

// Fetch feedback
$feedback = mysqli_query($conn, "SELECT * FROM feedback ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
        }
        
        .header {
            background-color: #1e40af;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .logout-btn {
            background-color: #dc2626;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background-color: #b91c1c;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .message {
            background-color: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #374151;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .submit-btn {
            background-color: #1e40af;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .submit-btn:hover {
            background-color: #1c3a99;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        
        tr:hover {
            background-color: #f9fafb;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background-color: #f3f4f6;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            background-color: white;
            border-bottom: 3px solid #1e40af;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .primary-key {
            background-color: #fef3c7;
            font-weight: bold;
        }
        
        .feedback-user {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .feedback-student {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .feedback-staff {
            background-color: #ede9fe;
            color: #5b21b6;
        }
        
        .feedback-text {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 4px solid #d1d5db;
        }
        
        .feedback-time {
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <?php if($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('bus')">Bus Management</div>
            <div class="tab" onclick="showTab('student')">Student Management</div>
            <div class="tab" onclick="showTab('staff')">Staff Management</div>
            <div class="tab" onclick="showTab('feedback')">Feedback Management</div>
        </div>
        
        <!-- Bus Management - NO assigned driver field -->
        <div id="bus-tab" class="tab-content active">
            <div class="section">
                <h2>Add New Bus</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bus Number *</label>
                            <input type="text" name="bus_no" required placeholder="Unique bus number">
                        </div>
                        <div class="form-group">
                            <label>Bus Route *</label>
                            <select name="bus_route" required>
                                <option value="">-- Select Route --</option>
                                <?php foreach($route_options as $route): ?>
                                    <option value="<?php echo htmlspecialchars($route); ?>">
                                        <?php echo htmlspecialchars($route); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_bus" class="submit-btn">Add Bus</button>
                    <!-- <small style="color: #666; display: block; margin-top: 10px;">* Bus Number is the PRIMARY KEY (must be unique)</small> -->
                </form>
            </div>
            
            <div class="section">
                <h2>Existing Buses</h2>
                <table>
                    <thead>
                        <tr>
                            <th class="primary-key">Bus Number</th>
                            <th>Bus Route</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($buses, 0); // Reset pointer
                        while($bus = mysqli_fetch_assoc($buses)): 
                            // Check if bus is assigned
                            $check_assigned = mysqli_query($conn, "SELECT * FROM staff WHERE Assigned_Bus = '{$bus['bus_number']}'");
                            $is_assigned = mysqli_num_rows($check_assigned) > 0;
                        ?>
                            <tr>
                                <td class="primary-key"><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                                <td><?php echo htmlspecialchars($bus['bus_route']); ?></td>
                                <td>
                                    <?php if($is_assigned): ?>
                                        <span style="color: #dc2626;">Assigned</span>
                                    <?php else: ?>
                                        <span style="color: #059669;">Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Student Management -->
        <div id="student-tab" class="tab-content">
            <div class="section">
                <h2>Add New Student</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student ID *</label>
                            <input type="text" name="sid" required placeholder="Unique student ID">
                        </div>
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" required placeholder="Name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Address (Area) *</label>
                            <select name="address" required>
                                <option value="">-- Select Area --</option>
                                <?php foreach($route_options as $route): ?>
                                    <option value="<?php echo htmlspecialchars($route); ?>">
                                        <?php echo htmlspecialchars($route); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="text" name="phone" required placeholder="Phone">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department" required>
                                <option value="">Select Department</option>
                                <option value="CSE">CSE</option>
                                <option value="ENH">ENH</option>
                                <option value="MNS">MNS</option>
                                <option value="BBA">BBA</option>
                                <option value="EEE">EEE</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="text" name="password" required placeholder="Set password">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_student" class="submit-btn">Add Student</button>
                    <!-- <small style="color: #666; display: block; margin-top: 10px;">* Student ID is the PRIMARY KEY (must be unique)</small> -->
                </form>
            </div>
            
            <div class="section">
                <h2>Existing Students</h2>
                <table>
                    <thead>
                        <tr>
                            <th class="primary-key">Student ID</th>
                            <th>Name</th>
                            <th>Address (Area)</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = mysqli_fetch_assoc($students)): ?>
                            <tr>
                                <td class="primary-key"><?php echo htmlspecialchars($student['SID']); ?></td>
                                <td><?php echo htmlspecialchars($student['Name']); ?></td>
                                <td><?php echo htmlspecialchars($student['Address']); ?></td>
                                <td><?php echo htmlspecialchars($student['Phone_Number']); ?></td>
                                <td><?php echo htmlspecialchars($student['Department']); ?></td>
                                <td><?php echo htmlspecialchars($student['password']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Staff Management -->
        <div id="staff-tab" class="tab-content">
            <div class="section">
                <h2>Add New Staff/Driver</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Staff ID *</label>
                            <input type="text" name="staff_id" required placeholder="Unique staff ID">
                        </div>
                        <div class="form-group">
                            <label>Staff Name *</label>
                            <input type="text" name="staff_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="text" name="staff_password" required placeholder="Set password">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="text" name="staff_phone" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Assigned Bus</label>
                            <select name="assigned_bus">
                                <option value="">-- Select Available Bus --</option>
                                <?php 
                                mysqli_data_seek($available_buses, 0); // Reset pointer
                                while($bus = mysqli_fetch_assoc($available_buses)): ?>
                                    <option value="<?php echo htmlspecialchars($bus['bus_number']); ?>">
                                        <?php echo htmlspecialchars($bus['bus_number']); ?> - <?php echo htmlspecialchars($bus['bus_route']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_staff" class="submit-btn">Add Staff</button>
                    <!-- <small style="color: #666; display: block; margin-top: 10px;">* Staff ID is the PRIMARY KEY (must be unique)</small> -->
                </form>
            </div>
            
            <div class="section">
                <h2>Existing Staff/Drivers</h2>
                <table>
                    <thead>
                        <tr>
                            <th class="primary-key">Staff ID</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Assigned Bus</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($staff_member = mysqli_fetch_assoc($staff)): ?>
                            <tr>
                                <td class="primary-key"><?php echo htmlspecialchars($staff_member['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['D_name']); ?></td>
                                <td><?php echo htmlspecialchars($staff_member['Staff_PhoneNumber']); ?></td>
                                <td>
                                    <?php if($staff_member['Assigned_Bus']): ?>
                                        <span style="color: #1e40af; font-weight: bold;">
                                            <?php echo htmlspecialchars($staff_member['Assigned_Bus']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #666;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($staff_member['password']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Feedback Management -->
        <div id="feedback-tab" class="tab-content">
            <div class="section">
                <h2>System Feedback</h2>
                <p style="margin-bottom: 15px; color: #666;">View feedback submitted by students and staff for system improvement.</p>
                
                <?php if(mysqli_num_rows($feedback) > 0): ?>
                    <?php while($fb = mysqli_fetch_assoc($feedback)): ?>
                        <div class="feedback-text">
                            <div style="margin-bottom: 10px;">
                                <span class="feedback-user feedback-<?php echo $fb['user_type']; ?>">
                                    <?php echo ucfirst($fb['user_type']); ?>
                                </span>
                                <strong><?php echo htmlspecialchars($fb['user_id']); ?></strong>
                            </div>
                            <p><?php echo htmlspecialchars($fb['feedback_text']); ?></p>
                            <div class="feedback-time">
                                Submitted on: <?php echo date('F j, Y \a\t h:i A', strtotime($fb['submitted_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        No feedback has been submitted yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
